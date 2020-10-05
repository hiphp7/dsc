<?php

namespace App\Services\Store;

use App\Models\Cart;
use App\Models\Comment;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\MerchantsCategory;
use App\Models\OfflineStore;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\SellerShopinfo;
use App\Models\StoreProducts;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 商城店铺
 * Class Store
 * @package App\Services
 */
class StoreService
{
    protected $baseRepository;
    protected $config;
    protected $goodsCommonService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $city = 0;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();

        $this->city = app(AreaService::class)->areaCookie();
    }

    /**
     * 获得分类下的商品
     *
     * @param array $children
     * @param int $merchant_id
     * @param int $brand_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $min
     * @param int $max
     * @param array $filter_attr
     * @param array $keywords
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @return array
     */
    public function getStoreGetGoods($children = [], $merchant_id = 0, $brand_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $min = 0, $max = 0, $filter_attr = [], $keywords = [], $size = 10, $page = 1, $sort = 'goods_id', $order = 'DESC')
    {
        $goodsParam = [
            'children' => $children
        ];

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('user_id', $merchant_id)
            ->where(function ($query) use ($goodsParam) {
                if ($goodsParam['children']) {
                    $query->whereIn('user_cat', $goodsParam['children']);
                }
            });

        if ($brand_id) {
            if ($brand_id && !is_array($brand_id)) {
                $brand_id = explode(',', $brand_id);
            }

            $res = $res->whereIn('brand_id', $brand_id);
        }

        if ($keywords) {
            $keywordsParam = [
                'keywords' => $keywords
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
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        if (!empty($filter_attr)) {
            $attrList = GoodsAttr::whereIn('goods_attr_id', $filter_attr);
            $attrList = $this->baseRepository->getToArrayGet($attrList);
            $attr_value = $this->baseRepository->getKeyPluck($attrList, 'attr_value');

            $goodsList = GoodsAttr::whereIn('attr_value', $attr_value);
            $goodsList = $this->baseRepository->getToArrayGet($goodsList);
            $goodsList = $this->baseRepository->getKeyPluck($goodsList, 'goods_id');

            if ($goodsList) {
                $res = $res->whereIn('goods_id', $goodsList);
            }
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
            'getShopInfo'
        ]);

        $uid = session('user_id', 0);
        $res = $res->withCount([
            'getCollectGoods as is_collect' => function ($query) use ($uid) {
                $query->where('user_id', $uid);
            }
        ]);

        $start = ($page - 1) * $size;
        $res = $res->orderBy($sort, $order);

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];

        if ($res) {
            foreach ($res as $row) {
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

                $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $arr[$row['goods_id']] = $row;

                $arr[$row['goods_id']]['model_price'] = $row['model_price'];

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                /* 处理商品水印图片 */
                $watermark_img = '';

                if ($promote_price != 0) {
                    $watermark_img = "watermark_promote_small";
                } elseif ($row['is_new'] != 0) {
                    $watermark_img = "watermark_new_small";
                } elseif ($row['is_best'] != 0) {
                    $watermark_img = "watermark_best_small";
                } elseif ($row['is_hot'] != 0) {
                    $watermark_img = 'watermark_hot_small';
                }

                if ($watermark_img != '') {
                    $arr[$row['goods_id']]['watermark_img'] = $watermark_img;
                }

                $arr[$row['goods_id']]['sort_order'] = $row['sort_order'];

                $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $arr[$row['goods_id']]['name'] = $row['goods_name'];
                $arr[$row['goods_id']]['goods_brief'] = $row['goods_brief'];
                $arr[$row['goods_id']]['sales_volume'] = $row['sales_volume'];
                $arr[$row['goods_id']]['is_promote'] = $row['is_promote'];

                $arr[$row['goods_id']]['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);

                $arr[$row['goods_id']]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$row['goods_id']]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $arr[$row['goods_id']]['type'] = $row['goods_type'];
                $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $arr[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$row['goods_id']]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $arr[$row['goods_id']]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $arr[$row['goods_id']]['is_hot'] = $row['is_hot'];
                $arr[$row['goods_id']]['is_best'] = $row['is_best'];
                $arr[$row['goods_id']]['is_new'] = $row['is_new'];
                $arr[$row['goods_id']]['self_run'] = $row['get_shop_info'] ? $row['get_shop_info']['self_run'] : 0;
                $arr[$row['goods_id']]['is_shipping'] = $row['is_shipping'];

                //ecmoban模板堂 --zhuo start
                if ($row['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $row['goods_id'])->where('warehouse_id', $warehouse_id)->first();
                    $prod = $prod ? $prod->toArray() : [];
                } elseif ($row['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $row['goods_id'])->where('area_id', $area_id)->first();
                    $prod = $prod ? $prod->toArray() : [];
                } else {
                    $prod = Products::where('goods_id', $row['goods_id'])->first();
                    $prod = $prod ? $prod->toArray() : [];
                }

                if (empty($prod)) { //当商品没有属性库存时
                    $arr[$row['goods_id']]['prod'] = 1;
                } else {
                    $arr[$row['goods_id']]['prod'] = 0;
                }

                $arr[$row['goods_id']]['goods_number'] = $row['goods_number'];

                $basic_info = SellerShopinfo::where('ru_id', $row['user_id'])->first();
                $basic_info = $basic_info ? $basic_info->toArray() : [];

                $arr[$row['goods_id']]['kf_type'] = $basic_info['kf_type'];

                $chat = $this->dscRepository->chatQq($basic_info);
                $arr[$row['goods_id']]['kf_ww'] = $chat['kf_ww'];
                $arr[$row['goods_id']]['kf_qq'] = $chat['kf_qq'];

                $shop_info = $this->merchantCommonService->getShopName($row['user_id'], 3);

                $arr[$row['goods_id']]['rz_shopName'] = isset($shop_info['shop_name']) ? $shop_info['shop_name'] : ''; //店铺名称
                $arr[$row['goods_id']]['user_id'] = $row['user_id'];

                $build_uri = [
                    'urid' => $row['user_id'],
                    'append' => $arr[$row['goods_id']]['rz_shopName']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                $arr[$row['goods_id']]['store_url'] = $domain_url['domain_name'];

                /* 评分数 */
                $arr[$row['goods_id']]['zconments'] = $this->commentService->goodsZconments($row['goods_id']);

                $arr[$row['goods_id']]['review_count'] = $arr[$row['goods_id']]['zconments']['allmen'];

                $arr[$row['goods_id']]['pictures'] = $this->goodsGalleryService->getGoodsGallery($row['goods_id'], 6); // 商品相册

                if ($this->config['customer_service'] == 0) {
                    $seller_id = 0;
                } else {
                    $seller_id = $row['user_id'];
                }

                $shop_information = $this->merchantCommonService->getShopName($seller_id); //通过ru_id获取到店铺信息;
                $shop_information = $shop_information ? $shop_information : [];

                /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                $arr[$row['goods_id']]['is_IM'] = isset($shop_information['is_IM']) ?: 0; //平台是否允许商家使用"在线客服";
                //判断当前商家是平台,还是入驻商家 bylu
                if ($seller_id == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                    if ($kf_im_switch) {
                        $arr[$row['goods_id']]['is_dsc'] = true;
                    } else {
                        $arr[$row['goods_id']]['is_dsc'] = false;
                    }
                } else {
                    $arr[$row['goods_id']]['is_dsc'] = false;
                }
                /*  @author-bylu  end */

                $arr[$row['goods_id']]['is_collect'] = $row['is_collect'];

                $arr[$row['goods_id']]['shop_information'] = $shop_information;
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
    public function getStoreGoodsCount($children = [], $merchant_id = 0, $brand_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $min = 0, $max = 0, $filter_attr = [], $keywords = [])
    {
        $goodsParam = [
            'children' => $children
        ];

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('user_id', $merchant_id)
            ->where(function ($query) use ($goodsParam) {
                if ($goodsParam['children']) {
                    $query = $query->whereIn('user_cat', $goodsParam['children']);
                }
            });

        if ($brand_id) {
            if ($brand_id && !is_array($brand_id)) {
                $brand_id = explode(',', $brand_id);
            }

            $res = $res->whereIn('brand_id', $brand_id);
        }

        if ($keywords) {
            $keywordsParam = [
                'keywords' => $keywords
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
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        if (!empty($filter_attr)) {
            $attrList = GoodsAttr::whereIn('goods_attr_id', $filter_attr);
            $attrList = $this->baseRepository->getToArrayGet($attrList);
            $attr_value = $this->baseRepository->getKeyPluck($attrList, 'attr_value');

            $goodsList = GoodsAttr::whereIn('attr_value', $attr_value);
            $goodsList = $this->baseRepository->getToArrayGet($goodsList);
            $goodsList = $this->baseRepository->getKeyPluck($goodsList, 'goods_id');

            if ($goodsList) {
                $res = $res->whereIn('goods_id', $goodsList);
            }
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $res = $res->count();

        /* 返回商品总数 */
        return $res;
    }

    /**
     * 店铺热门商品
     */
    public function GetHotNewBestGoods($where = [])
    {
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        if (isset($where['children']) && $where['children']) {
            $res = $res->whereIn('user_cat', $where['children']);
        }

        if (isset($where['ru_id'])) {
            $res = $res->where('user_id', $where['ru_id']);
        }

        if (isset($where['store_hot'])) {
            $res = $res->where('store_hot', $where['store_hot']);
        }

        if (isset($where['store_new'])) {
            $res = $res->where('store_new', $where['store_new']);
        }

        if (isset($where['store_best'])) {
            $res = $res->where('store_best', $where['store_best']);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $where['area_id'], $where['area_city']);

        $where['area_pricetype'] = $this->config['area_pricetype'];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($where) {
                $query->where('region_id', $where['warehouse_id']);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $res->orderBy('last_update', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $arr[$key] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                /* 处理商品水印图片 */
                $watermark_img = '';

                if ($promote_price != 0) {
                    $watermark_img = "watermark_promote_small";
                } elseif ($row['is_new'] != 0) {
                    $watermark_img = "watermark_new_small";
                } elseif ($row['is_best'] != 0) {
                    $watermark_img = "watermark_best_small";
                } elseif ($row['is_hot'] != 0) {
                    $watermark_img = 'watermark_hot_small';
                }

                if ($watermark_img != '') {
                    $arr[$key]['watermark_img'] = $watermark_img;
                }

                $arr[$key]['goods_id'] = $row['goods_id'];
                $arr[$key]['goods_name'] = $row['goods_name'];
                $arr[$key]['name'] = $row['goods_name'];
                $arr[$key]['goods_brief'] = $row['goods_brief'];
                $arr[$key]['sales_volume'] = $row['sales_volume'];
                $arr[$key]['comments_number'] = $row['comments_number'];
                $arr[$key]['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);
                $goods_id = $row['goods_id'];

                $arr[$key]['review_count'] = Comment::where('comment_type', 0)->where('id_value', $row['goods_id'])->count();
                $arr[$key]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$key]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $arr[$key]['type'] = $row['goods_type'];
                $arr[$key]['is_promote'] = $row['is_promote'];
                $arr[$key]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $arr[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $arr[$key]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            }
        }

        return $arr;
    }

    //category父级分类ID  parent_id
    public function getCategoryStoreParent($cat_id = 0)
    {
        $parent_id = MerchantsCategory::where('cat_id', $cat_id)->value('parent_id');

        return $parent_id;
    }

    //查询店铺基本信息以及店铺信息是否存在
    public function getMerchantsStoreInfo($merchant_id, $type = 0)
    {
        $res = SellerShopinfo::where('ru_id', $merchant_id);

        if ($type == 0) {
            return $res->count();
        } elseif ($type == 1) {

            $res = $res->with([
                'getMerchantsShopInformation' => function ($query) {
                    $query->select('user_id', 'rz_shopName', 'shoprz_brandName');
                }
            ]);

            $res = $res->first();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                $res['rz_shopName'] = $res['get_merchants_shop_information']['rz_shopName'] ?? '';
                $res['shoprz_brandName'] = $res['get_merchants_shop_information']['shoprz_brandName'] ?? '';
            }

            return $res;
        }
    }

    /**
     * 获得店铺设置基本信息
     * @param int $seller_id
     * @param int $type
     * @return array
     */
    public function getShopInfo($seller_id = 0, $type = 0)
    {
        $row = SellerShopinfo::where('ru_id', $seller_id);

        if ($type > 0) {
            $with = '';
            if ($type == 1) {
                $with = 'getSellerQrcode';
            } elseif ($type == 2) {
                $with = 'getMerchantsStepsFields';
            } elseif ($type == 3) {
                $with = 'getSellerQrcode,getMerchantsStepsFields';
            } elseif ($type == 4) {
                $with = 'getMerchantsShopInformation';
            }

            if ($with) {
                $with = explode(",", $with);
                $row = $row->with($with);
            }
        }

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        if ($row) {
            $row = isset($row['get_seller_qrcode']) && $row['get_seller_qrcode'] ? array_merge($row, $row['get_seller_qrcode']) : $row;
            $row = isset($row['get_merchants_steps_fields']) && $row['get_merchants_steps_fields'] ? array_merge($row, $row['get_merchants_steps_fields']) : $row;
            $row = isset($row['get_merchants_shop_information']) && $row['get_merchants_shop_information'] ? array_merge($row, $row['get_merchants_shop_information']) : $row;
        }

        return $row;
    }

    /**
     * 获得门店数量
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getStoreCount($where = [])
    {
        /* 获取该商品有货门店 */
        $res = OfflineStore::whereRaw(1);

        if (isset($where['is_confirm'])) {
            $res = $res->where('is_confirm', $where['is_confirm']);
        }

        if ((isset($where['goods_id']) && $where['goods_id']) || (isset($where['cart_value']) && $where['cart_value'])) {
            $res = $res->whereHas('getStoreGoods', function ($query) use ($where) {
                if (isset($where['goods_id']) && $where['goods_id']) {
                    $query->where('goods_id', $where['goods_id']);
                } elseif (isset($where['cart_value']) && $where['cart_value']) {
                    $where['cart_value'] = !is_array($where['cart_value']) ? explode(",", $where['cart_value']) : $where['cart_value'];

                    $goods = Cart::selectRaw("GROUP_CONCAT(goods_id) AS goods_id")->whereIn('rec_id', $where['cart_value'])->first();
                    $goods = $goods ? $goods->toArray() : [];
                    $goods_id = $goods ? explode(",", $goods['goods_id']) : 0;

                    if ($goods_id) {
                        $query->whereIn('goods_id', $goods_id);
                    }
                }
            });
        }

        if (isset($where['province']) && $where['province'] > 0) {
            $res = $res->where('province', $where['province']);
        }

        if (isset($where['city']) && $where['city'] > 0) {
            $res = $res->where('city', $where['city']);
        }

        if (isset($where['district']) && $where['district'] > 0) {
            $res = $res->where('district', $where['district']);
        }

        $count = $res->count();

        return $count;
    }

    /**
     * 获得门店列表
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getStoreList($where = [])
    {

        /* 获取该商品有货门店 */
        $res = OfflineStore::whereRaw(1);

        if (isset($where['is_confirm'])) {
            $res = $res->where('is_confirm', $where['is_confirm']);
        }

        if ((isset($where['goods_id']) && $where['goods_id']) || (isset($where['cart_value']) && $where['cart_value'])) {
            $res = $res->whereHas('getStoreGoods', function ($query) use ($where) {
                if (isset($where['goods_id']) && $where['goods_id']) {
                    $query->where('goods_id', $where['goods_id']);
                } elseif (isset($where['cart_value']) && $where['cart_value']) {
                    $where['cart_value'] = !is_array($where['cart_value']) ? explode(",", $where['cart_value']) : $where['cart_value'];

                    $goods = Cart::selectRaw("GROUP_CONCAT(goods_id) AS goods_id")->whereIn('rec_id', $where['cart_value'])->first();
                    $goods = $goods ? $goods->toArray() : [];
                    $goods_id = $goods ? explode(",", $goods['goods_id']) : 0;

                    if ($goods_id) {
                        $query->whereIn('goods_id', $goods_id);
                    }
                }
            });
        }

        if (isset($where['province']) && $where['province'] > 0) {
            $res = $res->where('province', $where['province']);
        }

        if (isset($where['city']) && $where['city'] > 0) {
            $res = $res->where('city', $where['city']);
        }

        if (isset($where['district']) && $where['district'] > 0) {
            $res = $res->where('district', $where['district']);
        }

        $res = $res->with([
            'getStoreGoods' => function ($query) use ($where) {
                $query->select('store_id', 'goods_id', 'goods_number')
                    ->where('goods_id', $where['goods_id']);
            },
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name as province');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name as city');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name as district');
            }
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $row = $row['get_store_goods'] ? array_merge($row, $row['get_store_goods']) : $row;
                $row = $row['get_region_province'] ? array_merge($row, $row['get_region_province']) : $row;
                $row = $row['get_region_city'] ? array_merge($row, $row['get_region_city']) : $row;
                $row = $row['get_region_district'] ? array_merge($row, $row['get_region_district']) : $row;

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 获得门店信息
     *
     * @param array $where
     * @param int $type
     * @return array
     */
    public function getOfflineStoreInfo($where = [], $type = 0)
    {

        /* 获取该商品有货门店 */
        $res = OfflineStore::whereRaw(1);

        if (isset($where['store_id']) && $where['store_id'] > 0) {
            $res = $res->where('id', $where['store_id']);
        }

        if ($type == 0) {
            $res = $res->with([
                'getStoreGoods',
                'getRegionProvince' => function ($query) {
                    $query->select('region_id', 'region_name as province');
                },
                'getRegionCity' => function ($query) {
                    $query->select('region_id', 'region_name as city');
                },
                'getRegionDistrict' => function ($query) {
                    $query->select('region_id', 'region_name as district');
                }
            ]);
        }

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            $res['province_id'] = $res['province'];
            $res['city_id'] = $res['city'];
            $res['district_id'] = $res['district'];

            if ($type == 0) {
                $res = $res['get_store_goods'] ? array_merge($res, $res['get_store_goods']) : $res;
                $res = $res['get_region_province'] ? array_merge($res, $res['get_region_province']) : $res;
                $res = $res['get_region_city'] ? array_merge($res, $res['get_region_city']) : $res;
                $res = $res['get_region_district'] ? array_merge($res, $res['get_region_district']) : $res;
            }
        }

        return $res;
    }

    /**
     * 获得门店商品货品数量
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getStoreProductCount($where = [])
    {
        if (empty($where)) {
            return [];
        }

        $res = StoreProducts::whereRaw(1);

        if (isset($where['goods_id'])) {
            $res = $res->where('goods_id', $where['goods_id']);
        }

        if (isset($where['is_confirm'])) {
            $res = $res->whereHas('getOfflineStore', function ($query) use ($where) {
                $query->where('is_confirm', $where['is_confirm']);
            });
        }

        $count = $res->count();

        return $count;
    }
}
