<?php

namespace App\Services\Store;

use App\Models\CollectStore;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\MerchantsShopBrand;
use App\Models\MerchantsShopInformation;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\SellerGrade;
use App\Models\SellerQrcode;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\User\UserCommonService;
use Endroid\QrCode\QrCode;

/**
 * Class StoreStreetMobileService
 * @package App\Services\Store
 */
class StoreStreetMobileService
{
    protected $goodsService;
    protected $baseRepository;
    protected $userCommonService;
    protected $goodsAttrService;
    protected $config;
    protected $dscRepository;
    protected $goodsCommonService;
    protected $commentService;
    protected $city = 0;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        UserCommonService $userCommonService,
        GoodsAttrService $goodsAttrService,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        CommentService $commentService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->userCommonService = $userCommonService;
        $this->goodsAttrService = $goodsAttrService;
        $this->dscRepository = $dscRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->commentService = $commentService;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;

        $this->city = app(AreaService::class)->areaCookie();
    }

    /**
     * 分类店铺列表
     *
     * @param int $cat_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @param int $user_id
     * @param int $lat
     * @param int $lng
     * @param int $city_id
     * @return mixed
     * @throws \Exception
     */
    public function getCatStoreList($cat_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $size = 10, $page = 1, $sort = 'goods_id', $order = 'DESC', $user_id = 0, $lat = 0, $lng = 0, $city_id = 0)
    {
        $store_user = [];
        if ($cat_id) {
            $store_user = $this->get_cat_store_list($cat_id);
        }

        $current = ($page - 1) * $size;
        $store = MerchantsShopInformation::where('shop_close', 1)
            ->where('is_street', 1);

        if ($store_user) {
            $store = $store->whereIn('user_id', $store_user);
        }

        if ($city_id) {
            $store = $store->whereHas('getSellerShopinfo', function ($query) use ($city_id) {
                $query->where('city', $city_id);
            });
        }

        $store = $store->whereHas('goods');

        if ($user_id) {
            $rank = $this->userCommonService->getUserRankByUid($user_id);
            $user_rank = isset($rank['rank_id']) ? $rank['rank_id'] : 1;
            $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $user_rank = 1;
            $user_discount = 100;
        }

        $where = [
            'lat' => $lat,
            'lng' => $lng,
            'user_rank' => $user_rank,
            'warehouse_id' => $warehouse_id,
            'area_city' => $area_city,
            'area_id' => $area_id,
            'area_pricetype' => $this->config['area_pricetype']
        ];
        $store = $store->with([
            'getSellerShopinfo' => function ($query) {
                $query->select('ru_id', 'logo_thumb');
            },
            'getGoods' => function ($query) use ($where) {
                $query = $query->where('is_on_sale', '1')
                    ->where('is_alone_sale', '1')
                    ->where('review_status', '>', 2)
                    ->where('is_delete', '0');

                /* 关联地区 */
                $query = $this->dscRepository->getAreaLinkGoods($query, $where['area_id'], $where['area_city']);

                $query->with([
                    'getMemberPrice' => function ($query) use ($where) {
                        $query->where('user_rank', $where['user_rank']);
                    },
                    'getWarehouseGoods' => function ($query) use ($where) {
                        if (isset($where['warehouse_id'])) {
                            $query->where('region_id', $where['warehouse_id']);
                        }
                    },
                    'getWarehouseAreaGoods' => function ($query) use ($where) {
                        $query = $query->where('region_id', $where['area_id']);

                        if ($where['area_pricetype'] == 1) {
                            $query->where('city_id', $where['area_city']);
                        }
                    }
                ]);

                $query->orderBy('sort_order', 'ASC');
            }
        ]);

        $store = $store->withCount([
            'getSellerShopinfo as distance' => function ($query) use ($where) {
                if ($where['lat'] && $where['lng']) {
                    $query->selectRaw('( 6371 * acos( cos( radians(' . $where['lat'] . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $where['lng'] . ') ) + sin( radians(' . $where['lat'] . ') ) * sin( radians( latitude ) ) )) AS distance');
                }
            }
        ]);

        $store = $store->offset($current)->limit($size);

        if ($sort == 'distance') {
            $store = $store->orderBy('distance', $order);
        } else {
            $store = $store->orderBy('sort_order', $order);
        }

        $store = $store->get();

        $store = $store ? $store->toArray() : [];
        if ($store) {
            foreach ($store as $key => $val) {

                $val['logo_thumb'] = $val['get_seller_shopinfo']['logo_thumb'] ?? '';
                $store[$key]['logo_thumb'] = $this->dscRepository->getImagePath(str_replace('../', '', $val['logo_thumb']));
                $store[$key]['count_gaze'] = CollectStore::where('ru_id', $val['user_id'])->count();

                $store[$key]['is_collect_shop'] = 0;

                if ($user_id) {
                    $store[$key]['is_collect_shop'] = CollectStore::where('ru_id', $val['user_id'])->where('user_id', $user_id)->count();
                }

                $res = $val['get_goods'] ?? [];

                $res = $this->baseRepository->getTake($res, 4);

                if ($res) {
                    foreach ($res as $idx => $goods) {
                        $price = [
                            'model_price' => isset($goods['model_price']) ? $goods['model_price'] : 0,
                            'user_price' => isset($goods['get_member_price']['user_price']) ? $goods['get_member_price']['user_price'] : 0,
                            'percentage' => isset($goods['get_member_price']['percentage']) ? $goods['get_member_price']['percentage'] : 0,
                            'warehouse_price' => isset($goods['get_warehouse_goods']['warehouse_price']) ? $goods['get_warehouse_goods']['warehouse_price'] : 0,
                            'region_price' => isset($goods['get_warehouse_area_goods']['region_price']) ? $goods['get_warehouse_area_goods']['region_price'] : 0,
                            'shop_price' => isset($goods['shop_price']) ? $goods['shop_price'] : 0,
                            'warehouse_promote_price' => isset($goods['get_warehouse_goods']['warehouse_promote_price']) ? $goods['get_warehouse_goods']['warehouse_promote_price'] : 0,
                            'region_promote_price' => isset($goods['get_warehouse_area_goods']['region_promote_price']) ? $goods['get_warehouse_area_goods']['region_promote_price'] : 0,
                            'promote_price' => isset($goods['promote_price']) ? $goods['promote_price'] : 0,
                            'wg_number' => isset($goods['get_warehouse_goods']['region_number']) ? $goods['get_warehouse_goods']['region_number'] : 0,
                            'wag_number' => isset($goods['get_warehouse_area_goods']['region_number']) ? $goods['get_warehouse_area_goods']['region_number'] : 0,
                            'goods_number' => isset($goods['goods_number']) ? $goods['goods_number'] : 0
                        ];

                        $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount / 100, $goods);

                        $res[$idx]['shop_price'] = $price['shop_price'];
                        $res[$idx]['promote_price'] = $price['promote_price'];
                        $res[$idx]['goods_number'] = $price['goods_number'];
                        $res[$idx]['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
                    }
                }

                $store[$key]['goods'] = $res;
                $store[$key]['distance'] = !empty($val['distance']) ? round($val['distance'], 2) : 0;
            }
        }

        return $store;
    }

    /**
     * 获得店铺分类下的商品
     *
     * @param int $uid
     * @param int $ru_id
     * @param int $children
     * @param string $keywords
     * @param int $brand_id
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @param array $filter_attr
     * @param array $where_ext
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function getStoreGoodsList($uid = 0, $ru_id = 0, $children = 0, $keywords = '', $brand_id = 0, $size = 10, $page = 1, $sort = 'goods_id', $order = 'DESC', $filter_attr = [], $where_ext = [], $type = '')
    {
        /* 查询分类商品数据 */
        $res = Goods::where('user_id', $ru_id)
            ->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('goods_number', '>', 0);

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        if (!empty($type)) {
            if ($type == 'store_new') {
                $res = $res->where('store_new', 1);
            } elseif ($type == 'is_promote') {
                $time = $this->timeRepository->getGmTime();
                $res = $res->where('is_promote', 1)
                    ->where('promote_start_date', '<=', $time)
                    ->where('promote_end_date', '>=', $time);
            }
        }

        // 搜索
        if ($keywords) {
            $keywordsParam = [
                'keywords' => $keywords,
            ];
            $res = $res->where(function ($query) use ($keywordsParam) {
                foreach ($keywordsParam['keywords'] as $key => $val) {
                    $query->where(function ($query) use ($val) {
                        $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');
                        $query = $query->orWhere('goods_sn', 'like', '%' . $val . '%');
                        $query->orWhere('keywords', 'like', '%' . $val . '%');
                    });
                }
            });
        } else {
            $goodsParam = [
                'children' => $children
            ];
            // 子分类
            $res = $res->where(function ($query) use ($goodsParam) {
                if (isset($goodsParam['children']) && $goodsParam['children']) {
                    $query->whereIn('user_cat', $goodsParam['children']);  // 商家分类id
                }
            });
        }

        if ($brand_id) {
            $brand_id = $this->baseRepository->getExplode($brand_id);
            $res = $res->whereIn('brand_id', $brand_id);
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        if (!empty($filter_attr)) {
            $goodsList = GoodsAttr::whereIn('goods_attr_id', $filter_attr)->get();
            $goodsList = $goodsList ? $goodsList->toArray() : [];

            if ($goodsList) {
                $res = $res->whereIn('goods_id', $goodsList);
            }
        }

        $where = [
            'warehouse_id' => $where_ext['warehouse_id'] ?? 0,
            'area_id' => $where_ext['area_id'] ?? 0,
            'area_city' => $where_ext['area_city'] ?? 0,
            'area_pricetype' => $this->config['area_pricetype'] ?? 0,
        ];

        $res = $this->dscRepository->getAreaLinkGoods($res, $where['area_id'], $where['area_city']);

        if ($uid > 0) {
            $rank = $this->userCommonService->getUserRankByUid($uid);
            $user_rank = $rank['rank_id'];
            $discount = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $user_rank = 1;
            $discount = 100;
        }

        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($where) {
                if (isset($where['warehouse_id']) && $where['warehouse_id']) {
                    $query->where('region_id', $where['warehouse_id']);
                }
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
            'getShopInfo'
        ]);

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->offset($start);
        }

        if ($size > 0) {
            $res = $res->limit($size);
        }

        if ($sort == 'promote') {
            $sort = 'promote_price';
        }

        $res = $res->orderBy($sort, $order);

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $k => $row) {
                $price = [
                    'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                    'user_price' => isset($row['get_member_price']['user_price']) ? $row['get_member_price']['user_price'] : 0,
                    'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                    'warehouse_price' => isset($row['get_warehouse_goods']['warehouse_price']) ? $row['get_warehouse_goods']['warehouse_price'] : 0,
                    'region_price' => isset($row['get_warehouse_area_goods']['region_price']) ? $row['get_warehouse_area_goods']['region_price'] : 0,
                    'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                    'warehouse_promote_price' => isset($row['get_warehouse_goods']['warehouse_promote_price']) ? $row['get_warehouse_goods']['warehouse_promote_price'] : 0,
                    'region_promote_price' => isset($row['get_warehouse_area_goods']['region_promote_price']) ? $row['get_warehouse_area_goods']['region_promote_price'] : 0,
                    'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                    'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                    'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                    'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
                ];

                $price = $this->goodsCommonService->getGoodsPrice($price, $discount / 100, $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $arr[$k] = $row;

                $arr[$k]['model_price'] = $row['model_price'];

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                //商品促销价格及属性促销价格
                $attr = $this->goodsAttrService->goodsAttr($row['goods_id']);
                $attr_str = [];
                if ($attr) {
                    foreach ($attr as $z => $v) {
                        $select_key = 0;
                        foreach ($v['attr_key'] as $key => $val) {
                            if ($val['attr_checked'] == 1) {
                                $select_key = $key;
                                break;
                            }
                        }
                        //默认选择第一个属性为checked
                        if ($select_key == 0) {
                            $attr[$z]['attr_key'][0]['attr_checked'] = 1;
                        }

                        $attr_str[] = $v['attr_key'][$select_key]['goods_attr_id'];
                    }
                }

                if ($attr_str) {
                    sort($attr_str);
                }

                /* 处理商品水印图片 */
                $watermark_img = '';
                if ($promote_price != 0) {
                    $watermark_img = "watermark_promote_small";
                } elseif ($row['store_new'] != 0) {
                    $watermark_img = "watermark_new_small";
                } elseif ($row['store_best'] != 0) {
                    $watermark_img = "watermark_best_small";
                } elseif ($row['store_hot'] != 0) {
                    $watermark_img = 'watermark_hot_small';
                }

                if ($watermark_img != '') {
                    $arr[$k]['watermark_img'] = $watermark_img;
                }
                $arr[$k]['sort_order'] = $row['sort_order'];

                $arr[$k]['goods_id'] = $row['goods_id'];
                $arr[$k]['goods_name'] = $row['goods_name'];
                $arr[$k]['name'] = $row['goods_name'];
                $arr[$k]['goods_brief'] = $row['goods_brief'];

                $arr[$k]['sales_volume'] = $row['sales_volume'];
                $arr[$k]['is_promote'] = $row['is_promote'];

                $arr[$k]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$k]['shop_price'] = $row['shop_price'];
                if ($promote_price > 0) {
                    $arr[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['promote_price']);
                } else {
                    $arr[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                }
                $arr[$k]['type'] = $row['goods_type'];
                $arr[$k]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $arr[$k]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$k]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);

                if ($row['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $row['goods_id'])->where('warehouse_id', $where['warehouse_id'])->first();
                    $prod = $prod ? $prod->toArray() : [];
                } elseif ($row['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $row['goods_id'])->where('area_id', $where['area_id'])->first();
                    $prod = $prod ? $prod->toArray() : [];
                } else {
                    $prod = Products::where('goods_id', $row['goods_id'])->first();
                    $prod = $prod ? $prod->toArray() : [];
                }

                if (empty($prod)) { //当商品没有属性库存时
                    $arr[$k]['prod'] = 1;
                } else {
                    $arr[$k]['prod'] = 0;
                }

                $arr[$k]['goods_number'] = $row['goods_number'];
                $arr[$k]['user_id'] = $row['user_id'];
            }
        }

        return $arr;
    }

    /**
     * 店铺的品牌
     */
    public function StoreBrand($ru_id)
    {
        $data = MerchantsShopBrand::from('merchants_shop_brand')
            ->select('bid', 'bank_name_letter', 'brandName')
            ->where('user_id', $ru_id)
            ->get();

        $data = $data ? $data->toArray() : [];

        return $data;
    }

    /**
     * 店铺详情
     *
     * @param $ru_id
     * @param int $user_id
     * @return array|mixed
     * @throws \Endroid\QrCode\Exception\InvalidPathException
     */
    public function StoreDetail($ru_id, $user_id = 0)
    {
        $data = MerchantsShopInformation::where('shop_close', 1)
            ->where('user_id', $ru_id);

        $data = $data->with([
            'getSellerShopinfo'
        ]);

        $data = $data->withCount([
            'collectstore as collect_count' => function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            }
        ]);

        $data = $this->baseRepository->getToArrayFirst($data);

        $data = isset($data['get_seller_shopinfo']) && $data['get_seller_shopinfo'] ? array_merge($data, $data['get_seller_shopinfo']) : $data;

        $info = [];
        if ($data) {
            $collect_count = CollectStore::where('ru_id', $data['user_id'])->count();

            $info = $this->shopdata($data);

            $info['shoprz_brandName'] = $data['shoprz_brandName']; // 主营品牌
            $grade_info = $this->get_seller_grade($ru_id);
            $info['grade_img'] = $this->dscRepository->getImagePath($grade_info['grade_img']);
            $info['logo_thumb'] = $this->dscRepository->getImagePath(str_replace('../', '', $data['logo_thumb']));
            $info['shop_logo'] = $this->dscRepository->getImagePath(str_replace('../', '', $data['shop_logo']));
            $info['grade_name'] = $grade_info['grade_name'];
            $info['street_desc'] = $data['street_desc'];
            $info['count_gaze'] = intval($collect_count);
            $info['lat'] = $data['latitude'];
            $info['long'] = $data['longitude'];
            $info['is_collect_shop'] = $data['collect_count'];

            //二维码内容
            $url = asset('/mobile#/shopHome/' . $ru_id);

            // 生成的文件位置
            $path = storage_public('data/attached/shop_qrcode/');

            $qrcode_thumb = SellerQrcode::where('ru_id', $ru_id)->value('qrcode_thumb');

            // 水印logo
            $water_logo = empty($qrcode_thumb) ? '' : storage_public($qrcode_thumb);

            // 输出二维码路径
            $out_img = $path . 'shop_qrcode_' . $ru_id . '.png';

            if (!is_dir($path)) {
                @mkdir($path, 0777);
            }

            if (!is_file($out_img)) {
                $qrCode = new QrCode($url);

                $qrCode->setSize(357);
                $qrCode->setMargin(15);

                if (is_file($water_logo)) {
                    $qrCode->setLogoPath($water_logo); // 默认居中
                    $qrCode->setLogoWidth(60);
                }

                $qrCode->writeFile($out_img); // 保存二维码
            }

            $image_name = 'data/attached/shop_qrcode/' . basename($out_img);

            $info['shop_qrcode_file'] = $image_name;
            $info['shop_qrcode'] = $this->dscRepository->getImagePath($image_name);

            // 营业执照信息
            $basic_info = SellerShopinfo::where('ru_id', $ru_id);

            $basic_info = $basic_info->with([
                'getSellerQrcode',
                'getMerchantsStepsFields'
            ]);

            $basic_info = $this->baseRepository->getToArrayFirst($basic_info);

            if ($basic_info) {
                $basic_info = isset($basic_info['get_seller_qrcode']) && $basic_info['get_seller_qrcode'] ? array_merge($basic_info, $basic_info['get_seller_qrcode']) : $basic_info;
                $basic_info = isset($basic_info['get_merchants_steps_fields']) && $basic_info['get_merchants_steps_fields'] ? array_merge($basic_info, $basic_info['get_merchants_steps_fields']) : $basic_info;
            }

            $info['kf_tel'] = $basic_info['kf_tel'];

            if ($basic_info) {
                //营业执照有限期
                $basic_info['business_term'] = (isset($basic_info['business_term']) && !empty($basic_info['business_term'])) ? str_replace(',', '-', $basic_info['business_term']) : '';
                //处理营业执照所在地
                $license_comp_adress = '';
                if (isset($basic_info['license_comp_adress']) && $basic_info['license_comp_adress']) {
                    $adress = explode(',', $basic_info['license_comp_adress']);
                    if (!empty($adress)) {
                        foreach ($adress as $v) {
                            $license_comp_adress .= get_table_date('region', "region_id='$v'", ['region_name'], 2);
                        }
                    }
                }
                $basic_info['license_comp_adress'] = $license_comp_adress;

                // 处理公司地址
                $company_located = '';
                if (isset($basic_info['company_located']) && $basic_info['company_located']) {
                    $adress = explode(',', $basic_info['company_located']);
                    if (!empty($adress)) {
                        foreach ($adress as $v) {
                            $company_located .= get_table_date('region', "region_id='$v'", ['region_name'], 2);
                        }
                    }
                    $company_located .= "&nbsp;&nbsp;" . $basic_info['company_adress'];
                }
                $basic_info['company_located'] = $company_located;
                $basic_info['merchants_url'] = $url;
            }

            $info['basic_info'] = $basic_info;
        }

        return $info;
    }

    protected function shopdata($data = [])
    {
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
        if (empty($user_id)) {
            return false;
        }

        $info['count_goods'] = $this->GoodsInfo($user_id);//所有商品

        $info['count_goods_new'] = $this->GoodsInfo($user_id, 1, 0); //所有新品
        $info['count_goods_promote'] = $this->GoodsInfo($user_id, 0, 1); //促销品
        $info['shop_id'] = $data['shop_id'];
        $info['ru_id'] = $data['user_id'];

        $info['shop_logo'] = $this->dscRepository->getImagePath(str_replace('../', '', $data['logo_thumb']));
        $info['street_thumb'] = $this->dscRepository->getImagePath(str_replace('../', '', $data['street_thumb']));
        $info['shop_name'] = $data['rz_shopName'];
        $info['shop_desc'] = $data['shop_name'];

        $info['shop_start'] = $data['shop_expireDateStart'];
        $info['shop_address'] = $data['shop_address'];
        $info['shop_flash'] = $this->dscRepository->getImagePath($data['street_thumb']);
        $info['shop_wangwang'] = $data['kf_ww'];

        $info['shop_qq'] = $data['kf_qq'];
        $info['shop_tel'] = $data['kf_tel'];
        $info['is_im'] = $data['is_IM'];
        $info['self_run'] = $data['self_run'];
        $info['meiqia'] = $data['meiqia'];
        $info['kf_appkey'] = $data['kf_appkey'];

        //评分 start
        if ($data['user_id'] > 0) {
            //商家所有商品评分类型汇总
            $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($data['user_id']);

            $info['commentrank'] = $merchants_goods_comment['cmt']['commentRank']['zconments']['score'] . '分';//商品评分
            $info['commentserver'] = $merchants_goods_comment['cmt']['commentServer']['zconments']['score'] . '分';//服务评分
            $info['commentdelivery'] = $merchants_goods_comment['cmt']['commentDelivery']['zconments']['score'] . '分';//时效评分

            $info['commentrank_font'] = $this->font($merchants_goods_comment['cmt']['commentRank']['zconments']['score']);
            $info['commentserver_font'] = $this->font($merchants_goods_comment['cmt']['commentServer']['zconments']['score']);
            $info['commentdelivery_font'] = $this->font($merchants_goods_comment['cmt']['commentDelivery']['zconments']['score']);
        }

        return $info;
    }

    /*获取商家等级*/
    public function get_seller_grade($ru_id = 0)
    {
        $res = SellerGrade::from('seller_grade as s')
            ->select('s.grade_name', 's.grade_img', 's.grade_introduce', 's.white_bar', 'g.grade_id', 'g.add_time', 'g.year_num', 'g.amount')
            ->leftjoin('merchants_grade as g', 's.id', 'g.grade_id')
            ->where('g.ru_id', $ru_id)
            ->first();
        $res = $res ? $res->toArray() : '';

        return $res;
    }

    public function GoodsInfo($user_id, $store_new = 0, $is_promote = 0)
    {
        $time = $this->timeRepository->getGmTime();

        $res = Goods::select('goods_id')
            ->where('is_delete', 0)
            ->where('is_on_sale', 1)
            ->where('user_id', $user_id);

        if ($store_new > 0) {
            $res = $res->where('store_new', 1);
        }

        if ($is_promote > 0) {
            $res = $res->where('is_promote', 1)
                ->where('promote_start_date', '<', $time)
                ->where('promote_end_date', '>', $time);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $res->count();

        return $res;
    }

    public function font($key)
    {
        if ($key > 4) {
            return lang('store_street.font_high');
        } elseif ($key > 3) {
            return lang('store_street.font_middle');
        } else {
            return lang('store_street.font_low');
        }
    }

    //查询所有商家的顶级分类
    public function get_cat_store_list($cat_id)
    {
        $res = MerchantsShopInformation::select('user_shopMain_category AS user_cat', 'user_id')
            ->where('user_shopMain_category', '<>', '')
            ->where('merchants_audit', 1)->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        foreach ($res as $key => $row) {
            $row['cat_str'] = '';
            $row['user_cat'] = explode('-', $row['user_cat']);

            foreach ($row['user_cat'] as $uck => $ucrow) {
                if ($ucrow) {
                    $row['user_cat'][$uck] = explode(':', $ucrow);
                    if (!empty($row['user_cat'][$uck][0])) {
                        $row['cat_str'] .= $row['user_cat'][$uck][0] . ",";
                    }
                }
            }

            if ($row['cat_str']) {
                $row['cat_str'] = substr($row['cat_str'], 0, -1);
                $row['cat_str'] = explode(',', $row['cat_str']);
                if (in_array($cat_id, $row['cat_str']) || $cat_id == 0) {
                    $arr[] = $row['user_id'];
                }
            }
        }

        return $arr;
    }

    public function StoreMap($lat = 0, $lng = 0)
    {
        $store = MerchantsShopInformation::from('merchants_shop_information as ms')
            ->select('ms.rz_shopname', 'ss.*')
            ->leftjoin('seller_shopinfo as ss', 'ms.user_id', 'ss.ru_id')
            ->where('ms.shop_close', 1)
            ->where('ms.is_street', 1);

        if ($lat && $lng) {
            $store = $store->selectRaw('( 6371 * acos( cos( radians(' . $lat . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $lng . ') ) + sin( radians(' . $lat . ') ) * sin( radians( latitude ) ) )) AS distance');
        }

        $store = $store->limit(10);

        $store = $store->orderBy('distance')->get();

        $seller_shopinfo = $store ? $store->toArray() : [];

        $list = [];
        $store = '';
        if ($seller_shopinfo) {
            foreach ($seller_shopinfo as $key => $vo) {
                $province = Region::where('region_id', $vo['province'])->value('region_name');
                $province = $province ? $province : '';

                $city = Region::where('region_id', $vo['city'])->value('region_name');
                $city = $city ? $city : '';

                $district = Region::where('region_id', $vo['district'])->value('region_name');
                $district = $district ? $district : '';

                $address = $province . $city . $district . $vo['shop_address'];

                $info = [
                    'coord' => $vo['latitude'] . ',' . $vo['longitude'],
                    'title' => empty($vo['shop_name']) ? $vo['rz_shopname'] : $vo['shop_name'],
                    'addr' => $address
                ];
                if (empty($vo['latitude']) || empty($vo['longitude'])) {
                    continue;
                }
                $list[] = urldecode(str_replace('=', ':', http_build_query($info, '', ';')));
            }
            if ($list) {
                $store = implode('|', $list);
            }
        }

        if (empty($store)) {
            $url = '';
        } else {
            $key = $this->config['tengxun_key'];
            $url = 'http://apis.map.qq.com/tools/poimarker?type=0&marker=' . $store . '&key=' . $key . '&referer=ectouch';
        }
        return $url;
    }
}
