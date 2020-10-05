<?php

namespace App\Services\Exchange;

use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsCat;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Common\TemplateService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserRankService;

/**
 * 积分商城
 * Class User
 * @package App\Services
 */
class ExchangeService
{
    protected $timeRepository;
    protected $config;
    protected $goodsAttrService;
    protected $baseRepository;
    protected $goodsMobileService;
    protected $dscRepository;
    protected $userRankService;
    protected $goodsGalleryService;
    protected $goodsCommonService;
    protected $merchantCommonService;
    protected $goodsWarehouseService;
    protected $cartCommonService;
    protected $templateService;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        GoodsMobileService $goodsMobileService,
        DscRepository $dscRepository,
        UserRankService $userRankService,
        GoodsGalleryService $goodsGalleryService,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        CartCommonService $cartCommonService,
        TemplateService $templateService
    )
    {
        //加载外部类
        $files = [
            'clips',
            'common',
            'main',
            'order',
            'function',
            'base',
            'goods',
            'ecmoban'
        ];
        load_helper($files);

        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsMobileService = $goodsMobileService;
        $this->dscRepository = $dscRepository;
        $this->userRankService = $userRankService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->config = $this->dscRepository->dscConfig();
        $this->cartCommonService = $cartCommonService;
        $this->templateService = $templateService;
    }

    /**
     * 获得分类下的商品
     * @access  public
     * @param string $children
     * @return  array
     */
    public function getExchangeGetGoods($children, $min, $max, $page = 1, $size = 10, $sort = 'g.goods_id', $order)
    {
        if ($sort == 'popularity') {
            $sort = 'e.exchange_integral';
        }
        if ($sort == 'amount') {
            $sort = 'g.sales_volume';
        }
        $begin = ($page - 1) * $size;
        $res = Goods::from('goods as g')
            ->leftJoin('exchange_goods as e', 'e.goods_id', '=', 'g.goods_id')
            ->where('g.is_delete', 0)
            ->where('e.is_exchange', 1)
            ->where('e.review_status', 3)
            ->select('g.goods_id', 'g.goods_name', 'g.goods_name_style', 'g.market_price', 'g.goods_type', 'g.goods_brief', 'g.goods_thumb', 'g.goods_img', 'g.sales_volume', 'e.exchange_integral', 'e.is_hot')
            ->offset($begin)
            ->limit($size)
            ->orderBy($sort, $order)
            ->get();

        $res = $res ? $res->toArray() : [];
        $arr = [];
        if (!empty($res)) {
            foreach ($res as $key => $row) {
                $row['is_hot'] = $row['is_hot'];
                $row['exchange_integral'] = $row['exchange_integral'];

                /* 处理商品水印图片 */
                $watermark_img = '';
                if ($row['is_hot'] != 0) {
                    $watermark_img = 'watermark_hot_small';
                }

                if ($watermark_img != '') {
                    $arr[$key]['watermark_img'] = $watermark_img;
                }

                $arr[$key]['goods_id'] = $row['goods_id'];
                $arr[$key]['goods_name'] = $row['goods_name'];
                $arr[$key]['name'] = $row['goods_name'];
                $arr[$key]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$key]['goods_brief'] = $row['goods_brief'];
                $arr[$key]['sales_volume'] = $row['sales_volume'];
                $arr[$key]['exchange_integral'] = $row['exchange_integral'];
                $arr[$key]['type'] = $row['goods_type'];
                $arr[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
            }
        }

        return $arr;
    }

    /**
     * 获得分类下的商品总数
     *
     * @access  public
     * @param string $cat_id
     * @return  integer
     */
    public function getExchangeGoodsCount($children, $min = 0, $max = 0)
    {
        $res = Goods::where('is_delete', 0);

        /* 查询扩展分类数据 */
        $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children)->get();
        $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
        $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];

        if ($children) {
            $goodsWhere = [
                'children' => $children,
                'extension_goods' => $extension_goods
            ];

            $res = $res->where(function ($query) use ($goodsWhere) {
                $query = $query->whereIn('cat_id', $goodsWhere['children']);
                $query = $query->orWhereIn('goods_id', $goodsWhere['extension_goods']);
            });
        }

        $maxmin = [
            'min' => $min,
            'max' => $max,
        ];
        $res = $res->whereHas('getExchangeGoods', function ($query) use ($maxmin) {
            $query = $query->where('is_exchange', 1)->where('review_status', 3);

            if ($maxmin['min'] > 0) {
                $query = $query->where('exchange_integral', '>=', $maxmin['min']);
            }

            if ($maxmin['max'] > 0) {
                $query->where('exchange_integral', '<=', $maxmin['max']);
            }
        });

        /* 返回商品总数 */
        return $res->count();
    }

    /**
     * 获得指定分类下的推荐商品
     *
     * @access  public
     * @param string $type 推荐类型，可以是 best, new, hot, promote
     * @param string $cats 分类的ID
     * @param integer $min 商品积分下限
     * @param integer $max 商品积分上限
     * @param string $ext 商品扩展查询
     * @return  array
     */
    public function getExchangeRecommendGoods($where = [])
    {
        $num = 0;
        if (isset($where['type'])) {
            $type2lib = ['best' => 'exchange_best', 'new' => 'exchange_new', 'hot' => 'exchange_hot'];
            $num = $this->templateService->getLibraryNumber($type2lib[$where['type']], 'exchange_list');
        }

        $res = Goods::select(['goods_id', 'goods_name', 'brand_id', 'market_price', 'goods_name_style', 'goods_brief', 'goods_thumb', 'goods_img'])
            ->where('is_delete', 0);

        /* 查询扩展分类数据 */
        if (isset($where['cats']) && $where['cats']) {
            $where['cats'] = $where['cats'] && !is_array($where['cats']) ? explode(",", $where['cats']) : $where['cats'];

            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $where['cats'])->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];

            $where['extension_goods'] = $extension_goods;

            $res = $res->where(function ($query) use ($where) {
                if (isset($where['cats'])) {
                    $query = $query->whereIn('cat_id', $where['cats']);
                }

                if ($where['extension_goods']) {
                    $query->orWhereIn('goods_id', $where['extension_goods']);
                }
            });
        }

        $res = $res->whereHas('getExchangeGoods', function ($query) use ($where) {
            $query = $query->where('is_exchange', 1)->where('review_status', 3);

            if (isset($where['type'])) {
                switch ($where['type']) {
                    case 'best':
                        $query = $query->where('is_best', 1);
                        break;
                    case 'new':
                        $query = $query->where('is_new', 1);
                        break;
                    case 'hot':
                        $query = $query->where('is_hot', 1);
                        break;
                }
            }

            if (isset($where['min']) && $where['min'] > 0) {
                $query = $query->where('exchange_integral', '>=', $where['min']);
            }

            if (isset($where['max']) && $where['max'] > 0) {
                $query->where('exchange_integral', '<=', $where['max']);
            }
        });

        $res = $res->with([
            'getExchangeGoods' => function ($query) {
                $query->select('goods_id', 'exchange_integral');
            },
            'getBrand' => function ($query) {
                $query->select('brand_id', 'brand_name');
            }
        ]);

        $order_type = $this->config['recommend_order'];

        if ($order_type == 0) {
            $res = $res->orderByRaw('sort_order, last_update desc');
        } else {
            $res = $res->orderByRaw('RAND()');
        }

        $res = $res->take($num);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $idx = 0;
        $goods = [];
        if ($res) {
            foreach ($res as $row) {
                $exchange = $row['get_exchange_goods'];
                $brand = $row['get_brand'];

                $goods[$idx]['id'] = $row['goods_id'];
                $goods[$idx]['name'] = $row['goods_name'];
                $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods[$idx]['brief'] = $row['goods_brief'];
                $goods[$idx]['brand_name'] = $brand ? $brand['brand_name'] : '';
                $goods[$idx]['short_name'] = $this->config['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $goods[$idx]['exchange_integral'] = $exchange ? $exchange['exchange_integral'] : '';
                $goods[$idx]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $goods[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $goods[$idx]['url'] = $this->dscRepository->buildUri('exchange_goods', ['gid' => $row['goods_id']], $row['goods_name']);

                $goods[$idx]['short_style_name'] = $this->goodsCommonService->addStyle($goods[$idx]['short_name'], $row['goods_name_style']);
                $idx++;
            }
        }

        return $goods;
    }

    /**
     * 获得积分兑换商品的详细信息
     *
     * @param int $user_id
     * @param int $goods_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array|bool
     * @throws \Exception
     */
    public function getExchangeGoodsInfo($user_id = 0, $goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $lang = lang('common');

        $res = Goods::where('goods_id', $goods_id)->where('is_delete', 0);

        $res = $res->whereHas('getExchangeGoods', function ($query) {
            $query->where('is_exchange', 1)->where('review_status', 3);
        });

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $res = $res->with([
            'getCategory' => function ($query) {
                $query->select('cat_id', 'measure_unit');
            },
            'getExchangeGoods' => function ($query) {
                $query->select('goods_id', 'exchange_integral', 'market_integral', 'is_exchange');
            },
            'getWarehouseGoods' => function ($query) use ($where) {
                $query->where('region_id', $where['warehouse_id']);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
            'getBrand' => function ($query) {
                $query->select('brand_id', 'brand_name');
            },
            'getGoodsExtend'
        ]);

        $uid = session('user_id', 0);
        $res = $res->withCount([
            'getCollectGoods as is_collect' => function ($query) use ($uid) {
                $query->where('user_id', $uid);
            }
        ]);

        $res = $res->first();

        $row = $res ? $res->toArray() : [];

        if (!empty($row)) {
            $exchange = isset($row['get_exchange_goods']) && $row['get_exchange_goods'] ? $row['get_exchange_goods'] : '';

            $row['exchange_integral'] = $exchange ? $exchange['exchange_integral'] : 0;
            $row['market_integral'] = $exchange ? $exchange['market_integral'] : 0;
            $row['is_exchange'] = $exchange ? $exchange['is_exchange'] : 0;
            //是否收藏
            $collect = $this->findOne($row['goods_id'], $user_id);

            $row['is_collect'] = empty($collect) ? 0 : 1;

            $brand = isset($row['get_brand']) && $row['get_brand'] ? $row['get_brand'] : '';
            $row['goods_brand'] = $brand ? $brand['brand_name'] : '';

            $category = isset($row['get_category']) && $row['get_category'] ? $row['get_category'] : '';
            $row['measure_unit'] = $category ? $category['measure_unit'] : '';
            $where = [
                'goods_id' => $row['goods_id'],
            ];
            $row['gallery_list'] = $this->goodsGalleryService->getGalleryList($where);
            // 获得商品的规格和属性
            $row['exchange_goods_attr'] = $this->goodsAttrService->goodsAttr($row['goods_id']);
            $attr_str = [];
            if ($row['exchange_goods_attr']) {
                $row['attr_name'] = '';
                foreach ($row['exchange_goods_attr'] as $k => $v) {
                    $select_key = 0;

                    if ($v['attr_key'][0]['attr_type'] == 0) {
                        unset($row['exchange_goods_attr'][$k]);
                        continue;
                    }

                    foreach ($v['attr_key'] as $key => $val) {
                        if ($val['attr_checked'] == 1) {
                            $select_key = $key;
                            break;
                        }
                    }
                    //默认选择第一个属性为checked
                    if ($select_key == 0) {
                        $row['exchange_goods_attr'][$k]['attr_key'][0]['attr_checked'] = 1;
                    }
                    if ($row['attr_name']) {
                        $row['attr_name'] = $row['attr_name'] . '' . $v['attr_key'][$select_key]['attr_value'];
                    } else {
                        $row['attr_name'] = $v['attr_key'][$select_key]['attr_value'];
                    }
                    $attr_str[] = $v['attr_key'][$select_key]['goods_attr_id'];
                }

                $row['exchange_goods_attr'] = array_values($row['exchange_goods_attr']);
            }
            if ($attr_str) {
                sort($attr_str);
            }

            /*获取商品规格参数*/
            $row['attr_parameter'] = $this->goodsAttrService->goodsAttrParameter($where['goods_id']);

            if ($user_id > 0) {
                $user_rank = $this->userRankService->getUserRankInfo($user_id);
            } else {
                $user_rank['discount'] = 1;
            }

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

            $price = $this->goodsCommonService->getGoodsPrice($price, $user_rank['discount'], $row);
            $row['shop_price'] = $price['shop_price'];
            $row['promote_price'] = $price['promote_price'];
            $row['goods_number'] = $price['goods_number'];

            //默认第一个属性的库存

            if (!empty($attr_str)) {
                $row['ru_id'] = $row['user_id'] ?? 0;
                $row['goods_number'] = $this->goodsWarehouseService->goodsAttrNumber($row['goods_id'], $attr_str, $warehouse_id, $area_id, $area_city);
            }

            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($row['is_new'] != 0) {
                $watermark_img = "watermark_new";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot';
            }

            if ($watermark_img != '') {
                $row['watermark_img'] = $watermark_img;
            }

            /* 修正重量显示 */
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                $row['goods_weight'] . $lang['kilogram'] :
                ($row['goods_weight'] * 1000) . $lang['gram'];

            /* 修正上架时间显示 */
            $row['add_time'] = $this->timeRepository->getLocalDate($row['add_time']);

            $row['market_integral'] = !empty($row['market_integral']) ? $row['market_integral'] : 0; //市场积分

            /* 修正商品图片 */
            $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);

            $row['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['shop_price']);
            $row['marketPrice'] = $row['market_price'];
            $row['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
            $row['market_price_formated'] = $this->dscRepository->getPriceFormat($row['market_price']);
            $row['goods_price'] = $this->dscRepository->getPriceFormat($row['exchange_integral'] * $this->config['integral_scale'] / 100);

            $row['rz_shopName'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称

            $row['shopinfo'] = $this->merchantCommonService->getShopName($row['user_id'], 2);
            if ($row['shopinfo'] && $row['shopinfo']['brand_thumb']) {
                $row['shopinfo']['brand_thumb'] = str_replace(['../'], '', $row['shopinfo']['brand_thumb']);
            }

            $row['integral_money'] = $this->dscRepository->getPriceFormat($this->config['integral_scale']);

            //判断是否支持退货服务
            $is_return_service = 0;
            $row['goods_extend'] = $row['get_goods_extend'] ?? [];

            if (isset($row['goods_cause']) && $row['goods_cause']) {
                $goods_cause = explode(',', $row['goods_cause']);

                $fruit1 = [1, 2, 3]; //退货，换货，仅退款
                $intersection = array_intersect($fruit1, $goods_cause); //判断商品是否设置退货相关
                if (!empty($intersection)) {
                    $is_return_service = 1;
                }
            }
            //判断是否设置包退服务  如果设置了退换货标识，没有设置包退服务  那么修正包退服务为已选择
            if ($is_return_service == 1 && isset($row['goods_extend']['is_return']) && !$row['goods_extend']['is_return']) {
                $row['goods_extend']['is_return'] = 1;
            }

            // 商品详情图 PC
            if (empty($row['desc_mobile']) && !empty($row['goods_desc'])) {
                $desc_preg = $this->dscRepository->descImagesPreg($row['goods_desc']);
                $row['goods_desc'] = $desc_preg['goods_desc'];
            }

            if (!empty($row['desc_mobile'])) {
                // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
                $desc_preg = $this->dscRepository->descImagesPreg($row['desc_mobile'], 'desc_mobile', 1);
                $row['goods_desc'] = $desc_preg['desc_mobile'];
            }

            //查询关联商品描述 start
            if (empty($row['desc_mobile']) && empty($row['goods_desc'])) {
                $GoodsDesc = $this->goodsMobileService->getLinkGoodsDesc($row['goods_id'], $row['user_id']);
                $link_desc = $GoodsDesc ? $GoodsDesc['goods_desc'] : '';

                if (!empty($link_desc)) {
                    $row['goods_desc'] = $link_desc;
                }
            }
            //查询关联商品描述 end

            return $row;
        } else {
            return false;
        }
    }

    /**
     * 查找我的收藏商品
     * @param $goodsId
     * @param $uid
     * @return array
     */
    public function findOne($goodsId, $uid)
    {
        $cg = CollectGoods::where('goods_id', $goodsId)
            ->where('user_id', $uid)
            ->first();

        if ($cg === null) {
            return [];
        }
        return $cg->toArray();
    }

    /**
     * 购买积分商城商品
     * @param $goodsId
     * @param $uid
     * @return array
     */
    public function buy($user_id, $number = 1, $goods_id = 0, $attr = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $lang = lang('common');

        if ($attr) {
            sort($attr);
        }
        $res = [];
        $goods = $this->getExchangeGoodsInfo($user_id, $goods_id, $warehouse_id, $area_id, $area_city);
        if ($goods['is_exchange'] == 0) {
            $res['error'] = 1;
            $res['msg'] = $lang['eg_error_status'];
            return $res;
        }
        $user = Users::where('user_id', $user_id)
            ->first();
        $user = $user ? $user->toArray() : [];
        $user_points = $user['pay_points']; // 用户的积分总数

        //积分不足兑换此商品
        if ($goods['exchange_integral'] > $user_points) {
            $res['error'] = 1;
            $res['msg'] = $lang['eg_error_integral'];
            return $res;
        }
        $product_info = ['product_number' => 0, 'product_id' => 0];
        if ($attr) {
            $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $attr, $warehouse_id, $area_id, $area_city);
        }

        if ($goods['model_attr'] == 1) {
            $prod = ProductsWarehouse::where('goods_id', $goods['goods_id'])->where('warehouse_id', $warehouse_id)->first();
            $prod = $prod ? $prod->toArray() : [];
        } elseif ($goods['model_attr'] == 2) {
            $prod = ProductsArea::where('goods_id', $goods['goods_id'])->where('area_id', $area_id);
            if ($this->config['area_pricetype'] == 1) {
                $prod = $prod->where('city_id', $area_city);
            }
            $prod = $prod->first();
            $prod = $prod ? $prod->toArray() : [];
        } else {
            $prod = Products::where('goods_id', $goods['goods_id'])->first();
            $prod = $prod ? $prod->toArray() : [];
        }

        if ($this->config['use_storage'] == 1) {
            $is_product = 0;
            if (is_spec($attr) && (!empty($prod))) {
                if (($product_info['product_number'] == 0)) {
                    $res['error'] = 1;
                    $res['msg'] = $lang['eg_error_number'];
                    return $res;
                }
            } else {
                $is_product = 1;
            }

            if ($is_product == 1) {
                /* 查询：检查兑换商品是否有库存 */
                if ($goods['goods_number'] == 0) {
                    $res['error'] = 1;
                    $res['msg'] = $lang['eg_error_number'];
                    return $res;
                }
            }
        }

        /* 查询：查询规格名称和值，不考虑价格 */
        $attr_list = [];
        $attr_res = GoodsAttr::from('goods_attr as g')
            ->select('*')
            ->join('attribute as a', 'a.attr_id', '=', 'g.attr_id')
            ->whereIn('g.goods_attr_id', $attr)
            ->get();

        $attr_res = $attr_res ? $attr_res->toArray() : [];

        foreach ($attr_res as $row) {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);

        $this->cartCommonService->clearCart($user_id, CART_EXCHANGE_GOODS);

        //积分等值金额
        $goods['exchange_integral_money'] = $goods['exchange_integral'] * $this->config['integral_scale'] / 100;

        $goods_attr_id = implode(',', $attr);

        /* 更新：加入购物车 */
        $cart = [
            'user_id' => $user_id,
            'session_id' => 0,
            'goods_id' => $goods['goods_id'],
            'product_id' => $product_info['product_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['marketPrice'],
            'goods_price' => 0, //$goods['exchange_integral']
            'goods_number' => $number,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'warehouse_id' => $warehouse_id, //ecmoban模板堂 --zhuo 仓库
            'area_id' => $area_id, //ecmoban模板堂 --zhuo 仓库地区
            'ru_id' => $goods['user_id'],
            'is_real' => $goods['is_real'],
            'extension_code' => addslashes($goods['extension_code']),
            'parent_id' => 0,
            'rec_type' => CART_EXCHANGE_GOODS,
            'is_gift' => 0,
            'freight' => $goods['freight'],
            'tid' => $goods['tid'],
            'shipping_fee' => $goods['shipping_fee']
        ];
        $id = Cart::insertGetId($cart);

        if ($id) {
            $res['error'] = 0;
            return $res;
        } else {
            $res['error'] = 1;
            $res['msg'] = $lang['unknown_error'];
            return $res;
        }
    }
}
