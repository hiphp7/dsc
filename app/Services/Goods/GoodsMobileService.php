<?php

namespace App\Services\Goods;

use App\Libraries\Http;
use App\Models\Cart;
use App\Models\CartCombo;
use App\Models\CollectGoods;
use App\Models\DrpShop;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\GoodsAttr;
use App\Models\GoodsConshipping;
use App\Models\GoodsConsumption;
use App\Models\GroupGoods;
use App\Models\LinkDescGoodsid;
use App\Models\MemberPrice;
use App\Models\Products;
use App\Models\SeckillGoods;
use App\Models\Suppliers;
use App\Models\Users;
use App\Plugins\UserRights\Discount\Services\DiscountRightsService;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\DiscountService;
use App\Services\Category\CategoryService;
use App\Services\Common\AreaService;
use App\Services\Common\TemplateService;
use App\Services\Coupon\CouponService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Str;
use think\Image;


/**
 * Class GoodsMobileService
 * @package App\Services\Goods
 */
class GoodsMobileService
{
    protected $goodsAttrService;
    protected $couponService;
    protected $categoryService;
    protected $baseRepository;
    protected $dscRepository;
    protected $config;
    protected $goodsGalleryService;
    protected $goodsCommonService;
    protected $goodsWarehouseService;
    protected $goodsCommentService;
    protected $merchantCommonService;
    protected $sessionRepository;
    protected $timeRepository;
    protected $discountService;
    protected $city = 0;
    protected $userCommonService;
    protected $templateService;

    public function __construct(
        GoodsAttrService $goodsAttrService,
        CouponService $couponService,
        TimeRepository $timeRepository,
        CategoryService $categoryService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        DiscountService $discountService,
        GoodsGalleryService $goodsGalleryService,
        GoodsCommonService $goodsCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        GoodsCommentService $goodsCommentService,
        MerchantCommonService $merchantCommonService,
        SessionRepository $sessionRepository,
        UserCommonService $userCommonService,
        TemplateService $templateService
    )
    {
        //加载外部类
        $files = [
            'clips',
            'common',
            'time',
            'main',
            'order',
            'function',
            'base',
            'goods',
            'ecmoban'
        ];
        load_helper($files);
        $this->goodsAttrService = $goodsAttrService;
        $this->couponService = $couponService;
        $this->timeRepository = $timeRepository;
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->discountService = $discountService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->goodsCommentService = $goodsCommentService;
        $this->merchantCommonService = $merchantCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->userCommonService = $userCommonService;
        $this->templateService = $templateService;

        $this->city = app(AreaService::class)->areaCookie();
    }


    /**
     * 商品详情
     *
     * @param $id
     * @param int $uid
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function goodsInfo($id, $uid = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $goods = Goods::where('goods_id', $id)
            ->where('is_delete', 0);

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        if ($uid > 0) {
            $rank = $this->userCommonService->getUserRankByUid($uid);
            $user_rank = $rank['rank_id'];
            $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $user_rank = 1;
            $user_discount = 100;
        }

        $goods = $goods->with([
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
        ]);

        $goods = $this->baseRepository->getToArrayFirst($goods);

        if ($goods) {
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

            $goods['shop_price'] = $price['shop_price'];
            $goods['promote_price'] = $price['promote_price'];
            $goods['goods_number'] = $price['goods_number'];
        }

        return $goods;
    }

    /**
     * 获得推荐商品
     *
     * @access  public
     * @param string $type 推荐类型，可以是 best, new, hot
     * @return  array
     *
     */
    public function getRecommendGoods($where = [])
    {
        if (isset($where['type']) && !in_array($where['type'], ['best', 'new', 'hot'])) {
            return [];
        }

        //取不同推荐对应的商品
        static $type_goods = [];

        if (isset($where['type']) && empty($type_goods[$where['type']])) {
            //初始化数据
            $type_goods['best'] = [];
            $type_goods['new'] = [];
            $type_goods['hot'] = [];

            if (isset($where['seller_id'])) {
                $goods_res = Goods::selectRaw('goods_id, brand_id, store_new as is_new, store_hot as is_hot, store_best as is_best');
            } else {
                $goods_res = Goods::select('goods_id', 'brand_id', 'is_best', 'is_new', 'is_hot', 'is_promote', 'sort_order');
            }

            $goods_res = $goods_res->where('is_on_sale', 1)->where('is_alone_sale', 1)->where('is_delete', 0);

            if ($this->config['review_goods']) {
                $goods_res = $goods_res->where('review_status', '>', 2);
            }

            $goods_res = $this->dscRepository->getAreaLinkGoods($goods_res, $where['area_id'], $where['area_city']);

            if (isset($where['seller_id'])) {
                $goods_res = $goods_res->where('user_id', isset($where['seller_id']));
                $goods_res = $goods_res->where(function ($query) {
                    $query->orWhere('store_hot', 1)
                        ->orWhere('store_new', 1)
                        ->orWhere('store_best', 1);
                });
            } else {
                $goods_res = $goods_res->where(function ($query) {
                    $query->orWhere('is_best', 1)
                        ->orWhere('is_new', 1)
                        ->orWhere('is_hot', 1);
                });

                $goods_res = $this->dscRepository->getWhereRsid($goods_res, 'user_id', 0, $this->city);
            }

            if (isset($where['presale']) && isset($where['presale']) == 'presale') {
                $goods_res = $goods_res->whereHas('getPresaleActivity', function ($query) {
                    $query->where('review_status', 3);
                });
            }

            $goods_res = $goods_res->with([
                'getBrand'
            ]);

            $goods_res = $goods_res->orderByRaw('sort_order, last_update desc');

            $goods_res = $goods_res->take(20);

            $goods_res = $this->baseRepository->getToArrayGet($goods_res);

            //定义推荐,最新，热门，促销商品
            $goods_data['best'] = [];
            $goods_data['new'] = [];
            $goods_data['hot'] = [];
            $goods_data['brand'] = [];
            if (!empty($goods_res)) {
                foreach ($goods_res as $key => $data) {
                    $data['brand_name'] = !empty($data['get_brand']) ? $data['get_brand']['brand_name'] : '';
                    $goods_res[$key]['brand_name'] = $data['brand_name'];

                    if ($data['is_best'] == 1) {
                        $goods_data['best'][] = ['goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']];
                    }
                    if ($data['is_new'] == 1) {
                        $goods_data['new'][] = ['goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']];
                    }
                    if ($data['is_hot'] == 1) {
                        $goods_data['hot'][] = ['goods_id' => $data['goods_id'], 'sort_order' => $data['sort_order']];
                    }
                    if ($data['brand_name'] != '') {
                        $goods_data['brand'][$data['goods_id']] = $data['brand_name'];
                    }
                }
            }

            $order_type = $this->config['recommend_order'];

            //按推荐数量及排序取每一项推荐显示的商品 order_type可以根据后台设定进行各种条件显示
            static $type_array = [];
            $type2lib = [];
            $type_merge = [];

            if (isset($where['rec_type']) && $where['rec_type'] == 0) {
                $type2lib = ['best' => 'recommend_best', 'new' => 'recommend_new', 'hot' => 'recommend_hot'];
            } elseif (isset($where['rec_type']) && $where['rec_type'] == 1) {
                $type2lib = ['best' => 'recommend_best_goods', 'new' => 'recommend_new_goods', 'hot' => 'recommend_hot_goods'];
            }

            if (empty($type_array)) {
                foreach ($type2lib as $key => $data) {
                    if (!empty($goods_data[$key])) {
                        $num = $this->templateService->getLibraryNumber($data);

                        $data_count = count($goods_data[$key]);
                        $num = $data_count > $num ? $num : $data_count;
                        if ($order_type == 0) {
                            $rand_key = array_slice($goods_data[$key], 0, $num);
                            foreach ($rand_key as $key_data) {
                                $type_array[$key][] = $key_data['goods_id'];
                            }
                        } else {
                            $rand_key = array_rand($goods_data[$key], $num);
                            if ($num == 1) {
                                $type_array[$key][] = $goods_data[$key][$rand_key]['goods_id'];
                            } else {
                                foreach ($rand_key as $key_data) {
                                    $type_array[$key][] = $goods_data[$key][$key_data]['goods_id'];
                                }
                            }
                        }
                    } else {
                        $type_array[$key] = [];
                    }
                }
            } else {
                $type_merge = array_merge($type_array['new'], $type_array['best'], $type_array['hot']);
                $type_merge = array_unique($type_merge);
            }

            //取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
            $result = Goods::where('is_on_sale', 1)->where('is_alone_sale', 1)->where('is_delete', 0);

            if ($type_merge) {
                $result = $result->whereIn('goods_id', $type_merge);
            }

            if ($this->config['review_goods']) {
                $result = $result->where('review_status', '>', 2);
            }

            $result = $this->dscRepository->getAreaLinkGoods($result, $where['area_id'], $where['area_city']);

            if (isset($where['seller_id'])) {
                $result = $result->where('user_id', $where['seller_id']);
                $result = $result->where(function ($query) {
                    $query->orWhere('store_hot', 1)
                        ->orWhere('store_new', 1)
                        ->orWhere('store_best', 1);
                });
            } else {
                $result = $result->where(function ($query) {
                    $query->orWhere('is_best', 1)
                        ->orWhere('is_new', 1)
                        ->orWhere('is_hot', 1);
                });

                $result = $this->dscRepository->getWhereRsid($result, 'user_id', 0, $this->city);
            }

            if (isset($where['presale']) && $where['presale'] == 'presale') {
                $result = $result->whereHas('getPresaleActivity', function ($query) {
                    $query->where('review_status', 3);
                });
            }

            $where['area_pricetype'] = $this->config['area_pricetype'];

            $result = $result->with([
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

            $result = $result->orderByRaw('sort_order, last_update desc');

            $result = $result->take(20);

            $result = $this->baseRepository->getToArrayGet($result);

            if ($result) {
                foreach ($result as $idx => $row) {
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

                    $goods[$idx] = $row;

                    if ($row['promote_price'] > 0) {
                        $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    } else {
                        $promote_price = 0;
                    }

                    $goods[$idx]['id'] = $row['goods_id'];
                    $goods[$idx]['name'] = $row['goods_name'];
                    $goods[$idx]['is_promote'] = $row['is_promote'];
                    $goods[$idx]['brief'] = $row['goods_brief'];
                    $goods[$idx]['comments_number'] = $row['comments_number'];
                    $goods[$idx]['sales_volume'] = $row['sales_volume'];
                    $goods[$idx]['brand_name'] = isset($goods_data['brand'][$row['goods_id']]) ? $goods_data['brand'][$row['goods_id']] : '';
                    $goods[$idx]['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);
                    $goods[$idx]['short_name'] = $this->config['goods_name_length'] > 0 ? $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                    $goods[$idx]['short_style_name'] = $this->goodsCommonService->addStyle($goods[$idx]['short_name'], $row['goods_name_style']);
                    $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                    $goods[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                    $goods[$idx]['promote_price'] = $promote_price > 0 ? $this->dscRepository->getPriceFormat($promote_price) : '';
                    $goods[$idx]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                    $goods[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                    $goods[$idx]['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);
                    $goods[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                    $goods[$idx]['shopUrl'] = $this->dscRepository->buildUri('merchants_store', ['urid' => $row['user_id']]);
                    if ($type_array && in_array($row['goods_id'], $type_array['best'])) {
                        $type_goods['best'][] = $goods[$idx];
                    }
                    if ($type_array && in_array($row['goods_id'], $type_array['new'])) {
                        $type_goods['new'][] = $goods[$idx];
                    }
                    if ($type_array && in_array($row['goods_id'], $type_array['hot'])) {
                        $type_goods['hot'][] = $goods[$idx];
                    }
                }
            }
        }

        return $type_goods[$where['type']];
    }

    /**
     * 获得促销商品
     *
     * @access  public
     * @return  array
     */
    public function getPromoteGoods($where = [])
    {
        $time = $this->timeRepository->getGmTime();

        $where['area_pricetype'] = $this->config['area_pricetype'];
        $where['warehouse_id'] = $where['warehouse_id'] ?? 0;
        $where['area_id'] = $where['area_id'] ?? 0;
        $where['area_city'] = $where['area_city'] ?? 0;

        $num = 10;
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_promote', 1);

        $res = $res->where('promote_start_date', '<=', $time)
            ->where('promote_end_date', '>=', $time);

        $res = $this->dscRepository->getAreaLinkGoods($res, $where['area_id'], $where['area_city']);

        if ($this->config['review_goods'] == 1) {
            $res = $res->where('review_status', '>', 2);
        }

        if ($where['uid'] > 0) {
            $rank = $this->userCommonService->getUserRankByUid($where['uid']);
            $user_rank = $rank['rank_id'];
            $user_discount = $rank['discount'];
        } else {
            $user_rank = 1;
            $user_discount = 100;
        }

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
            },
            'getBrand'
        ]);

        if ($this->config['recommend_order'] == 0) {
            $res = $res->orderByRaw('sort_order, last_update desc');
        } else {
            $res = $res->orderByRaw('RAND()');
        }

        $res = $res->take($num);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];


        $goods = [];
        if ($res) {
            foreach ($res as $idx => $row) {
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

                $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount / 100, $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $goods[$idx] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $goods[$idx]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);

                $goods[$idx]['s_time'] = $row['promote_start_date'];
                $goods[$idx]['e_time'] = $row['promote_end_date'];

                $goods[$idx]['id'] = $row['goods_id'];
                $goods[$idx]['name'] = $row['goods_name'];
                $goods[$idx]['brief'] = $row['goods_brief'];

                $goods[$idx]['brand_name'] = $row['get_brand']['brand_name'] ?? '';
                $goods[$idx]['comments_number'] = $row['comments_number'];
                $goods[$idx]['sales_volume'] = $row['sales_volume'];
            }
        }

        return $goods;
    }

    /**
     * 获得商品的详细信息
     *
     * @param array $where
     * @return array
     */
    public function getGoodsInfo($where = [])
    {
        $where['uid'] = $where['uid'] ?? 0;

        $res = Goods::where('goods_id', $where['goods_id']);

        if (isset($where['is_delete'])) {
            $res = $res->where('is_delete', $where['is_delete']);
        }

        if (isset($where['is_on_sale'])) {
            $res = $res->where('is_on_sale', $where['is_on_sale']);
        }

        if (isset($where['is_alone_sale'])) {
            $res = $res->where('is_alone_sale', $where['is_alone_sale']);
        }

        if (isset($where['uid']) && $where['uid'] > 0) {
            $rank = $this->userCommonService->getUserRankByUid($where['uid']);
            $user_rank = isset($rank['rank_id']) ? $rank['rank_id'] : 1;
            $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $user_rank = 1;
            $user_discount = 100;
        }

        $where['warehouse_id'] = $where['warehouse_id'] ?? 0;
        $where['area_id'] = $where['area_id'] ?? 0;
        $where['area_city'] = $where['area_city'] ?? 0;
        $where['area_pricetype'] = $this->config['area_pricetype'];

        $res = $res->with([
            'getGoodsCategory',
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
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
            },
            'getBrand',
            'getGoodsExtend',
            'getSellerShopInfo'
        ]);

        $res = $res->first();

        $row = $res ? $res->toArray() : [];

        //当前时间
        $time = $this->timeRepository->getGmTime();

        $tag = [];
        if ($row) {
            $category = $row['get_goods_category'];
            $brand = $row['get_brand'];

            if ($brand) {
                $row['brand'] = $brand;
            }

            $row['cat_measure_unit'] = $category['measure_unit'];

            if ($row['brand_id']) {
                $row['brand_name'] = $brand['brand_name'];
            }

            // 获得商品的规格和属性
            $row['attr'] = $this->goodsAttrService->goodsAttr($where['goods_id']);
            $attr_str = [];
            if ($row['attr']) {
                $row['attr_name'] = '';
                $row['goods_attr_id'] = '';
                foreach ($row['attr'] as $k => $v) {
                    $select_key = 0;

                    if ($v['attr_key'][0]['attr_type'] == 0) {
                        unset($row['attr'][$k]);
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
                        $row['attr'][$k]['attr_key'][0]['attr_checked'] = 1;
                    }
                    if ($row['attr_name']) {
                        $row['attr_name'] = $row['attr_name'] . '' . $v['attr_key'][$select_key]['attr_value'];
                        $row['goods_attr_id'] = $row['goods_attr_id'] . ',' . $v['attr_key'][$select_key]['goods_attr_id'];
                    } else {
                        $row['attr_name'] = $v['attr_key'][$select_key]['attr_value'];
                        $row['goods_attr_id'] = $v['attr_key'][$select_key]['goods_attr_id'];
                    }
                    $attr_str[] = $v['attr_key'][$select_key]['goods_attr_id'];
                }

                $row['attr'] = array_values($row['attr']);
            }
            if ($attr_str) {
                sort($attr_str);
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
                'integral' => isset($row['integral']) ? $row['integral'] : 0,
                'wpay_integral' => isset($row['get_warehouse_goods']['pay_integral']) ? $row['get_warehouse_goods']['pay_integral'] : 0,
                'apay_integral' => isset($row['get_warehouse_area_goods']['pay_integral']) ? $row['get_warehouse_area_goods']['pay_integral'] : 0,
                'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0,
                'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
            ];

            $row['shop_price_original'] = $price['shop_price']; // 商品原价不含折扣
            $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount / 100, $row);

            $row['shop_price'] = round($price['shop_price'], 2);
            $row['promote_price'] = round($price['promote_price'], 2);
            $row['integral'] = $price['integral'];
            $row['goods_number'] = $price['goods_number'];

            if ($row['promote_price'] > 0) {
                $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $is_promote = 0;
            //当前商品正在促销时间内
            if ($time >= $row['promote_start_date'] && $time <= $row['promote_end_date'] && $row['is_promote']) {
                $is_promote = 1;
            }

            // 当前商品正在最小起订量
            if ($time >= $row['minimum_start_date'] && $time <= $row['minimum_end_date'] && $row['is_minimum']) {
                $row['is_minimum'] = 1;
            } else {
                $row['is_minimum'] = 0;
            }

            $products = [];
            if (!empty($attr_str)) {
                $row['shop_price'] = $this->getFinalPrice($where['uid'], $row['goods_id'], 1, true, $attr_str, $where['warehouse_id'], $where['area_id'], $where['area_city']);

                $attr_str = is_array($attr_str) ? implode(',', $attr_str) : $attr_str;
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($row['goods_id'], $attr_str, $where['warehouse_id'], $where['area_id'], $where['area_city']);

                $row['goods_number'] = $this->goodsWarehouseService->goodsAttrNumber($row['goods_id'], $attr_str, $where['warehouse_id'], $where['area_id'], $where['area_city']);
            }

            if (CROSS_BORDER === true) {
                // 跨境多商户
                $cbec = app(CrossBorderService::class)->cbecExists();

                if (!empty($cbec)) {
                    $row['goods_rate'] = $cbec->get_goods_rate($row['goods_id'], $row['shop_price']);
                    $row['formated_goods_rate'] = $this->dscRepository->getPriceFormat($row['goods_rate']);
                    if ($row['user_id'] > 0) {
                        $row['cross_border'] = true;
                    }
                }
            }

            //@author-bylu 将分期数据反序列化为数组 start
            if (!empty($row['stages'])) {
                $row['stages'] = unserialize($row['stages']);
            }
            //@author-bylu  end

            /* 计算商品的促销价格 */
            if ($this->config['add_shop_price'] == 0 && $is_promote == 1) {
                $promote_price = $row['shop_price'];
            }

            if (!($time >= $row['promote_start_date'] && $time <= $row['promote_end_date'])) {
                $row['promote_start_date'] = 0;
                $row['promote_end_date'] = 0;
            }

            $row['now_promote'] = $promote_price;
            $row['promote_price_org'] = $promote_price;
            $row['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);

            /* 处理商品水印图片 */
            $watermark_img = '';
            if ($promote_price != 0) {
                $watermark_img = "watermark_promote";
            } elseif ($row['is_new'] != 0) {
                $watermark_img = "watermark_new";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot';
            }
            if ($watermark_img != '') {
                $row['watermark_img'] = $watermark_img;
            }

            /*获取优惠券数量*/
            $count = $this->couponService->goodsCoupons($where['uid'], $where['goods_id'], $row['user_id']);
            $row['coupon_count'] = $count['total'];

            /*商品相册*/
            $row['gallery_list'] = $this->goodsGalleryService->getGalleryList($where);
            if ($row['gallery_list']) {
                foreach ($row['gallery_list'] as $k => $v) {
                    $row['gallery_list'][$k]['img_original'] = $this->dscRepository->getImagePath($v['img_original']);
                    $row['gallery_list'][$k]['img_url'] = $this->dscRepository->getImagePath($v['img_url']);
                    $row['gallery_list'][$k]['thumb_url'] = $this->dscRepository->getImagePath($v['thumb_url']);
                }
            }

            $row['ru_id'] = $row['user_id'] ?? 0;

            /*获取商品规格参数*/
            $row['attr_parameter'] = $this->goodsAttrService->goodsAttrParameter($where['goods_id']);

            if ($where['uid'] > 0) {
                /*会员关注状态*/
                $collect_goods = CollectGoods::where('user_id', $where['uid'])
                    ->where('goods_id', $where['goods_id'])
                    ->count();
                if ($collect_goods > 0) {
                    $row['is_collect'] = 1;
                } else {
                    $row['is_collect'] = 0;
                }
            } else {
                $row['is_collect'] = 0;
            }

            /* 获取商家等级信息、等级积分 */
            $grade_rank = get_merchants_grade_rank($row['user_id']);

            /* 获取最高赠送消费积分 */
            $row['use_give_integral'] = 0;
            if ($row['user_id'] > 0 && $grade_rank) {
                if ($promote_price) { //促销
                    $row['use_give_integral'] = intval($grade_rank['give_integral'] * $promote_price);
                } else { //本店价
                    $row['use_give_integral'] = intval($grade_rank['give_integral'] * $row['shop_price']);
                }
            }

            /* 判断商家 */
            if ($row['user_id'] > 0) {
                if ($row['give_integral'] == -1) {
                    if (isset($row['use_give_integral']) && ($row['shop_price'] > $row['use_give_integral'] || $promote_price > $row['use_give_integral'])) {
                        $row['give_integral'] = intval($row['use_give_integral']);
                    } else {
                        $row['give_integral'] = 0;
                    }
                }
            } else {
                /* 判断赠送消费积分是否默认为-1 */
                if ($row['give_integral'] == -1) {
                    if ($promote_price) {
                        $row['give_integral'] = intval($promote_price);
                    } else {
                        $row['give_integral'] = intval($row['shop_price']);
                    }
                }
            }

            /* 获取商家等级信息 */
            if ($row['user_id'] > 0 && $grade_rank) {
                $row['grade_name'] = $grade_rank['grade_name'] ?? '';
                $row['grade_img'] = empty($grade_rank['grade_img']) ? '' : $this->dscRepository->getImagePath($grade_rank['grade_img']);
                $row['grade_introduce'] = $grade_rank['grade_introduce'] ?? '';
            }

            /* 是否显示商品库存数量 */
            $row['goods_number'] = ($this->config['use_storage'] > 0) ? $row['goods_number'] : 1;

            /* 修正积分：转换为可使用多少积分（原来是可以使用多少钱的积分） */
            $row['integral'] = $this->config['integral_scale'] ? round($row['integral'] * 100 / $this->config['integral_scale']) : 0;

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
                $GoodsDesc = $this->getLinkGoodsDesc($row['goods_id'], $row['user_id']);
                $link_desc = $GoodsDesc ? $GoodsDesc['goods_desc'] : '';

                if (!empty($link_desc)) {
                    $row['goods_desc'] = $link_desc;
                }
            }
            //查询关联商品描述 end

            /* 修正商品图片 */
            $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $row['original_img'] = $this->dscRepository->getImagePath($row['original_img']);
            $row['goods_video'] = empty($row['goods_video']) ? '' : $this->dscRepository->getImagePath($row['goods_video']);

            /* 获得商品的销售价格 */
            $row['marketPrice'] = !empty($products) && (floatval($products['product_market_price']) > 0) ? $products['product_market_price'] : round($row['market_price'], 2);
            $row['market_price_formated'] = $this->dscRepository->getPriceFormat($row['marketPrice']);
            $row['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['shop_price']);
            $row['promote_price_formated'] = $is_promote == 1 ? $this->dscRepository->getPriceFormat($promote_price) : '';

            $row['goodsweight'] = $row['goods_weight'];

            $row['isHas_attr'] = empty($attr_str) ? 0 : 1;

            if (isset($row['shopinfo'])) {
                $row['shopinfo']['brand_thumb'] = get_brand_image_path($row['shopinfo']['brand_thumb']);
                $row['shopinfo']['brand_thumb'] = str_replace(['../'], '', $row['shopinfo']['brand_thumb']);
                $row['shopinfo']['brand_thumb'] = $this->dscRepository->getImagePath($row['shopinfo']['brand_thumb']);
            }
            // 购物车商品数量
            if ($where['uid']) {
                $row['cart_number'] = Cart::where('user_id', $where['uid'])->where('rec_type', 0)
                    ->where('store_id', 0)
                    ->sum('goods_number');
            } else {
                $session_id = $this->sessionRepository->realCartMacIp();
                $row['cart_number'] = Cart::where('session_id', $session_id)->where('rec_type', 0)
                    ->where('store_id', 0)
                    ->sum('goods_number');
            }

            $row['goods_extend'] = $row['get_goods_extend'] ?? [];

            $row['volume_price_list'] = $this->goodsCommonService->getVolumePriceList($where['goods_id'], 1, 1);
            // 商品满减
            $row['consumption'] = $this->goodsConList($row['goods_id'], 'goods_consumption');

            if (file_exists(MOBILE_DRP)) {
                $row['drp_shop'] = DrpShop::where('user_id', $where['uid'])->where('membership_card_id', '>', 0)->count();

                // 非分销商,显示分销权益卡绑定的会员特价权益（最低折扣）
                if (empty($row['drp_shop'])) {
                    $row['membership_card_discount_price'] = app(DiscountRightsService::class)->membershipCardDiscount('discount', $row, 1, $attr_str, $where['warehouse_id'], $where['area_id'], $where['area_city']);
                    $row['membership_card_discount_price_formated'] = $this->dscRepository->getPriceFormat($row['membership_card_discount_price']);
                }
            } else {
                $row['drp_shop'] = 0;
            }

            $suppliers_name = Suppliers::where('suppliers_id', $row['suppliers_id'])->value('suppliers_name');
            if (!empty($suppliers_name)) {
                $row['suppliers_name'] = $suppliers_name;
            }

            //买家印象
            if ($row['goods_product_tag']) {
                $impression_list = !empty($row['goods_product_tag']) ? explode(',', $row['goods_product_tag']) : '';
                foreach ($impression_list as $kk => $vv) {
                    $tag[$kk]['txt'] = $vv;
                    //印象数量
                    $tag[$kk]['num'] = $this->goodsCommentService->commentGoodsTagNum($row['goods_id'], $vv);
                }
                $row['impression_list'] = $tag;
            }
            //上架下架时间

            //商品未审核，展示状态已下架
            if ($row['review_status'] <= 2) {
                $row['is_on_sale'] = 0;
            }

            //商品设置->显示设置
            $row['show_goodsnumber'] = $this->config['show_goodsnumber']; // 是否显示库存
            $row['show_marketprice'] = $this->config['show_marketprice']; // 是否显示市场价格

            //当前时间戳
            $row['current_time'] = $time;
            // 格式化上架时间
            $row['add_time_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);

            return $row;
        } else {
            return [];
        }
    }

    /**
     * 查询猜你喜欢商品
     *
     * @param int $uid
     * @param array $link_cats
     * @param array $link_goods
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $is_volume
     * @param int $limit
     * @return array|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getUserOrderGoodsGuess($uid = 0, $link_cats = [], $link_goods = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $is_volume = 0, $limit = 10)
    {

        $cache_link_cats = $link_cats && is_array($link_cats) ? implode(',', $link_cats) : '';
        $cache_link_goods = $link_goods && is_array($link_goods) ? implode(',', $link_goods) : '';

        $cache_name = 'get_user_order_goods_guess_mobiel_' . $uid . '_' . $cache_link_cats . '_' . $cache_link_goods . '_' . $warehouse_id . '_' . $area_id . '_' . $area_city . '_' . $is_volume . '_' . $limit;
        $res = cache($cache_name);

        if (is_null($res)) {
            $res = Goods::where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0)
                ->where('is_show', 1);

            if ($is_volume == 1) {
                $res = $res->where('sales_volume', '>', 0);
            } elseif ($is_volume == 2) {
                $res = $res->where('sales_volume', '>', 0)->where('is_hot', 1);
            }

            if ($link_cats) {
                $res = $res->whereIn('cat_id', $link_cats);
            }

            $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

            if ($link_goods) {
                $res = $res->whereNotIn('goods_id', $link_goods);
            }

            if ($uid > 0) {
                $rank = $this->userCommonService->getUserRankByUid($uid);
                $user_rank = isset($rank['rank_id']) ? $rank['rank_id'] : 1;
                $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
            } else {
                $user_rank = 1;
                $user_discount = 100;
            }

            $where = [
                'area_pricetype' => $this->config['area_pricetype'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];

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

            $res = $res->orderBy('sort_order', 'ASC')->orderBy('sales_volume', 'DESC');

            if ($limit > 0) {
                $res = $res->limit($limit);
            }

            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $k => $v) {

                    $price = [
                        'model_price' => isset($v['model_price']) ? $v['model_price'] : 0,
                        'user_price' => isset($v['get_member_price']['user_price']) ? $v['get_member_price']['user_price'] : 0,
                        'percentage' => isset($v['get_member_price']['percentage']) ? $v['get_member_price']['percentage'] : 0,
                        'warehouse_price' => isset($v['get_warehouse_goods']['warehouse_price']) ? $v['get_warehouse_goods']['warehouse_price'] : 0,
                        'region_price' => isset($v['get_warehouse_area_goods']['region_price']) ? $v['get_warehouse_area_goods']['region_price'] : 0,
                        'shop_price' => isset($v['shop_price']) ? $v['shop_price'] : 0,
                        'warehouse_promote_price' => isset($v['get_warehouse_goods']['warehouse_promote_price']) ? $v['get_warehouse_goods']['warehouse_promote_price'] : 0,
                        'region_promote_price' => isset($v['get_warehouse_area_goods']['region_promote_price']) ? $v['get_warehouse_area_goods']['region_promote_price'] : 0,
                        'promote_price' => isset($v['promote_price']) ? $v['promote_price'] : 0,
                        'integral' => isset($v['integral']) ? $v['integral'] : 0,
                        'wpay_integral' => isset($v['get_warehouse_goods']['pay_integral']) ? $v['get_warehouse_goods']['pay_integral'] : 0,
                        'apay_integral' => isset($v['get_warehouse_area_goods']['pay_integral']) ? $v['get_warehouse_area_goods']['pay_integral'] : 0,
                        'goods_number' => isset($v['goods_number']) ? $v['goods_number'] : 0,
                        'wg_number' => isset($v['get_warehouse_goods']['region_number']) ? $v['get_warehouse_goods']['region_number'] : 0,
                        'wag_number' => isset($v['get_warehouse_area_goods']['region_number']) ? $v['get_warehouse_area_goods']['region_number'] : 0,
                    ];

                    $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount / 100, $v);

                    $v['shop_price'] = round($price['shop_price'], 2);
                    $v['promote_price'] = round($price['promote_price'], 2);
                    $v['integral'] = $price['integral'];
                    $v['goods_number'] = $price['goods_number'];

                    if ($v['promote_price'] > 0) {
                        $promote_price = $this->goodsCommonService->getBargainPrice($v['promote_price'], $v['promote_start_date'], $v['promote_end_date']);
                    } else {
                        $promote_price = 0;
                    }

                    $res[$k]['promote_price_formated'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';

                    $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                    $res[$k]['goods_img'] = $this->dscRepository->getImagePath($v['goods_img']);
                    $res[$k]['original_img'] = $this->dscRepository->getImagePath($v['original_img']);
                    $res[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($v['shop_price']);
                    $res[$k]['market_price_formated'] = $this->dscRepository->getPriceFormat($v['market_price']);
                }

                cache()->forever($cache_name, $res);
            }
        }

        return $res;
    }

    /**
     * 获取商品统一描述内容
     *
     * @access  public
     * @param  $goods_id
     * @param  $seller_id
     * @return  array
     */
    public function getLinkGoodsDesc($goods_id = 0, $seller_id = 0)
    {
        $res = LinkDescGoodsid::where('goods_id', $goods_id);

        $res = $res->whereHas('getLinkGoodsDesc', function ($query) use ($seller_id) {
            $query->where('ru_id', $seller_id)->where('review_status', '>', 2);
        });

        $res = $res->with(['getLinkGoodsDesc']);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            $res['goods_desc'] = $res['get_link_goods_desc']['goods_desc'];
        }

        return $res;
    }

    /**
     * 根据商品 获取货品信息
     * @param $goodsId
     * @param $goodsAttr
     * @return mixed
     */
    public function getProductByGoods($goodsId, $goodsAttr)
    {
        $product = Products::select('product_id as id', 'product_sn')
            ->where('goods_id', $goodsId)
            ->where('goods_attr', $goodsAttr)
            ->first();

        if ($product === null) {
            return [];
        }

        return $product->toArray();
    }

    /**
     * 商品市场价格（多模式下）
     *
     * @param $goods_id
     * @param $attr_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return int|mixed
     */
    public function goodsMarketPrice($goods_id, $attr_id, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $goods = $this->goodsInfo($goods_id);//商品详情
        $products = app(GoodsProdutsService::class)->getProductsAttrPrice($goods_id, $attr_id, $warehouse_id, $area_id, $area_city, $goods['model_attr']); //获取有属性价格

        if (empty($products) || $products['product_price'] <= 0) {
            $market_price = !empty($goods['market_price']) ? $goods['market_price'] : 0;
        } else {
            $attr_price = $products['product_price'];

            // SKU价格模式： 商品价格 + 属性货品价格 时， 市场价 = 原市场价 + 属性货品价格
            if ($this->config['add_shop_price'] == 1) {
                $market_price = $attr_price + $goods['market_price'];
            } else {
                // SKU价格模式： 属性货品价格 时， 市场价 = 属性市场价格
                $market_price = !empty($products['product_market_price']) ? $products['product_market_price'] : 0;
            }
        }

        return !empty($market_price) ? $market_price : 0;
    }

    /**
     * 验证属性是多选，单选
     * @param $goods_attr_id
     * @return mixed
     */
    public function getGoodsAttrId($goods_attr_id)
    {
        $res = GoodsAttr::from('goods_attr as ga')
            ->select('a.attr_type')
            ->join('attribute as a', 'ga.attr_id', '=', 'a.attr_id')
            ->where('ga.goods_attr_id', $goods_attr_id)
            ->first();
        if ($res === null) {
            return [];
        }

        return $res['attr_type'];
    }

    /**
     * 商品属性图片
     * @param $goods_id
     * @return mixed
     */
    public function getAttrImgFlie($goods_id, $attr_id = 0)
    {
        $attr_id = $this->baseRepository->getExplode($attr_id);

        $res = [];
        if ($attr_id) {
            foreach ($attr_id as $key => $val) {
                $res = GoodsAttr::select('attr_img_flie', 'attr_gallery_flie')
                    ->where('goods_id', $goods_id)
                    ->where('goods_attr_id', $val);
                $res = $this->baseRepository->getToArrayFirst($res);
                if ($res) {
                    if (isset($res['attr_gallery_flie']) && !empty($res['attr_gallery_flie'])) {
                        $res['attr_img_flie'] = $res['attr_gallery_flie'];
                        break;
                    }
                    if (isset($res['attr_img_flie']) && !empty($res['attr_img_flie'])) {
                        break;
                    }
                }
            }
        }

        return $res;
    }


    /**
     * 商品属性价格与库存
     *
     * @param $uid
     * @param $goods_id
     * @param $attr_id
     * @param int $num
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $store_id
     * @return array
     */
    public function goodsPropertiesPrice($uid, $goods_id, $attr_id, $num = 1, $warehouse_id = 0, $area_id = 0, $area_city = 0, $store_id = 0)
    {

        $attr_id = $this->baseRepository->getExplode($attr_id);

        $result = [
            'stock' => '',       //库存
            'market_price' => '',      //市场价
            'qty' => '',               //数量
            'spec_price' => '',        //属性价格
            'goods_price' => '',           //商品价格(最终使用价格)
            'attr_img' => ''           //商品属性图片
        ];

        if ($attr_id) {
            sort($attr_id);
        }

        $result['stock'] = $this->goodsWarehouseService->goodsAttrNumber($goods_id, $attr_id, $warehouse_id, $area_id, $area_city, $store_id);

        $result['market_price'] = $this->goodsMarketPrice($goods_id, $attr_id, $warehouse_id, $area_id, $area_city);
        $result['market_price_formated'] = $this->dscRepository->getPriceFormat($result['market_price'], true);
        $result['qty'] = $num;

        $result['spec_price'] = app(GoodsProdutsService::class)->goodsPropertyPrice($goods_id, $attr_id, $warehouse_id, $area_id, $area_city);
        $result['spec_price_formated'] = $this->dscRepository->getPriceFormat($result['spec_price'], true);

        $result['spec_promote_price'] = 0;
        if ($this->config['add_shop_price'] == 0) {
            $result['spec_promote_price'] = app(GoodsProdutsService::class)->goodsPropertyPrice($goods_id, $attr_id, $warehouse_id, $area_id, $area_city, 'product_promote_price');
        }

        $result['spec_promote_price_formated'] = $this->dscRepository->getPriceFormat($result['spec_promote_price'], true);

        $result['goods_price'] = $this->getFinalPrice($uid, $goods_id, $num, true, $attr_id, $warehouse_id, $area_id, $area_city);
        $result['goods_price_formated'] = $this->dscRepository->getPriceFormat($result['goods_price'], true);

        $result['shop_price'] = $result['goods_price'];
        $result['shop_price_formated'] = $result['goods_price_formated'];

        if ($attr_id) {
            $attr_img = $this->getAttrImgFlie($goods_id, $attr_id);
            if (!empty($attr_img['attr_img_flie'])) {
                $result['attr_img'] = $this->dscRepository->getImagePath($attr_img['attr_img_flie']);
            }
            $name = [];
            foreach ($attr_id as $k => $v) {
                $name[$k] = GoodsAttr::where('goods_id', $goods_id)
                    ->where('goods_attr_id', $v)
                    ->value('attr_value');
            }
            $result['attr_name'] = implode(' ', $name);
        }

        return $result;
    }


    /**
     * 商品属性名称
     * @param $goods_id
     * @param $attr_id
     * @return string
     */
    public function getAttrName($goods_id, $attr_id)
    {
        $attr_name = '';
        if ($attr_id) {
            $name = [];
            foreach ($attr_id as $k => $v) {
                $name[$k] = GoodsAttr::where('goods_id', $goods_id)
                    ->where('goods_attr_id', $v)
                    ->value('attr_value');
            }
            $attr_name = implode(' ', $name);
        }

        return $attr_name;
    }

    /**
     * 取得商品最终使用价格
     *
     * @param string $goods_id 商品编号
     * @param string $goods_num 购买数量
     * @param boolean $is_spec_price 是否加入规格价格
     * @param array $property 规格ID的数组或者逗号分隔的字符串
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return  float|int|mixed 商品最终购买价格
     */
    public function getFinalPrice($uid, $goods_id, $goods_num = '1', $is_spec_price = false, $property = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $final_price = 0; //商品最终购买价格
        $volume_price = 0; //商品优惠价格
        $promote_price = 0; //商品促销价格
        $user_price = 0; //商品会员价格
        $spec_price = 0;

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($property)) {
                $spec_price = app(GoodsProdutsService::class)->goodsPropertyPrice($goods_id, $property, $warehouse_id, $area_id, $area_city);
            }
        }

        //取得商品优惠价格列表
        $price_list = $this->goodsCommonService->getVolumePriceList($goods_id);
        if (!empty($price_list)) {
            foreach ($price_list as $value) {
                if ($goods_num >= $value['number']) {
                    $volume_price = $value['price'];
                }
            }
        }

        if ($uid > 0) {
            $rank = $this->userCommonService->getUserRankByUid($uid);
            $user_rank = $rank['rank_id'];
            $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $user_rank = 1;
            $user_discount = 100;
        }

        /* 取得商品信息 */
        $goods = Goods::where('goods_id', $goods_id);

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $goods = $goods->with([
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
        ]);

        $goods = $this->baseRepository->getToArrayFirst($goods);

        if ($goods) {
            $goods['user_price'] = $goods['get_member_price'] ? $goods['get_member_price']['user_price'] : 0;

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

            $goods['shop_price'] = $price['shop_price'];
            $goods['promote_price'] = $price['promote_price'];
            $goods['goods_number'] = $price['goods_number'];

        } else {
            $goods['user_price'] = 0;
            $goods['shop_price'] = 0;
        }

        $time = $this->timeRepository->getGmTime();
        $now_promote = 0;

        //当前商品正在促销时间内
        if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date'] && $goods['is_promote']) {
            $now_promote = 1;
        }

        /* 计算商品的属性促销价格 */
        if ($property && $this->config['add_shop_price'] == 0) {
            $goods['promote_price'] = app(GoodsProdutsService::class)->goodsPropertyPrice($goods_id, $property, $warehouse_id, $area_id, $area_city, 'product_promote_price');
        }

        /* 计算商品的促销价格 */
        if (isset($goods['promote_price']) && $goods['promote_price'] > 0) {
            $promote_price = $this->goodsCommonService->getBargainPrice($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
        } else {
            $promote_price = 0;
        }

        //取得商品会员价格列表
        if (!empty($property) && $this->config['add_shop_price'] == 0) {
            /* 会员等级价格 */
            if ($goods['user_price'] > 0 && $goods['user_price'] < $spec_price) {
                if (isset($price['percentage']) && $price['percentage'] == 1) {
                    $user_price = $goods['shop_price'] * $price['user_price'] / 100;
                } else {
                    $user_price = $goods['user_price'];
                }
            } else {
                if ($now_promote == 1) {
                    $user_price = $promote_price;
                } else {
                    $user_price = $spec_price * $user_discount / 100;
                }
            }
        } else {
            $user_price = $goods['shop_price'];
        }

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && $now_promote == 0) {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        } elseif (!empty($volume_price) && $now_promote == 0) {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        } elseif (empty($volume_price) && $now_promote == 1) {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        } elseif (!empty($volume_price) && $now_promote == 1) {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        } else {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($property)) {
                if ($this->config['add_shop_price'] == 1) {
                    $final_price += $spec_price;
                }
            }
        }

        //返回商品最终购买价格
        return $final_price;
    }

    /**
     * 获取用户等级价格
     *
     * @param int $uid
     * @param int $goods_id
     * @return float|int|mixed
     * @throws \Exception
     */
    public function getMemberRankPriceByGid($uid = 0, $goods_id = 0)
    {
        $user_rank = $this->userCommonService->getUserRankByUid($uid);

        $shop_price = Goods::where('goods_id', $goods_id)->pluck('shop_price');
        $shop_price = $shop_price[0];

        if ($user_rank) {
            if ($price = $this->getMemberPriceByUid($user_rank['rank_id'], $goods_id)) {
                return $price;
            }
            if ($user_rank['discount']) {
                $member_price = $shop_price * $user_rank['discount'];
            } else {
                $member_price = $shop_price;
            }
            return $member_price;
        } else {
            return $shop_price;
        }
    }

    /**
     * 根据用户ID获取会员价格
     * @param $rank
     * @param $goods_id
     * @return mixed
     */
    public function getMemberPriceByUid($rank, $goods_id)
    {
        $price = MemberPrice::where('user_rank', $rank)->where('goods_id', $goods_id)->pluck('user_price');
        $price = $price ? $price->toArray() : [];

        if (!empty($price)) {
            $price = $price[0];
        }

        return $price;
    }

    /**
     * 获取促销活动
     *
     * @param
     * @param
     *
     * @return
     */
    public function goodsActivityList($goods_id = 0, $ru_id = 0)
    {
        //当前时间
        $gmtime = $this->timeRepository->getGmTime();

        $list = GoodsActivity::select('act_id', 'act_name', 'act_type', 'start_time', 'end_time')
            ->where('user_id', $ru_id)
            ->where('review_status', 3)
            ->where('is_finished', 0)
            ->where('start_time', '<=', $gmtime)
            ->where('end_time', '>=', $gmtime)
            ->where('goods_id', $goods_id);

        if (!empty($goods_id)) {
            $list = $list->where('goods_id', $goods_id);
        }
        $list = $list->limit(10)
            ->get();
        $list = $list ? $list->toArray() : [];

        return $list;
    }


    /**
     * 生成商品海报
     * @param $user_id
     * @param int $goods_id
     * @param int $share_type
     * @return string
     */
    public function createsharePoster($user_id, $goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $share_type = 0)
    {
        // 分享链接
        $param = [
            'parent_id' => $user_id
        ];
        $goods_url = dsc_url('/#/goods/' . $goods_id) . '?' . http_build_query($param, '', '&');

        // 显示商品原价
        $goods = $this->goodsInfo($goods_id);
        if (empty($goods)) {
            return [];
        }

        $users = Users::select('user_picture', 'nick_name', 'user_name')->where('user_id', $user_id)->first();
        $users = $users ? $users->toArray() : [];
        if (empty($users)) {
            return [];
        }

        $file_path = storage_public('data/attached/goods_share/');
        if (!file_exists($file_path)) {
            make_dir($file_path);
        }
        $avatar_file = storage_public('data/attached/avatar/');
        if (!file_exists($avatar_file)) {
            make_dir($avatar_file);
        }
        $goods_thumb_file = $file_path . 'goods_thumb/';
        if (!file_exists($goods_thumb_file)) {
            make_dir($goods_thumb_file);
        }

        // 背景图片
        $goods_bg = public_path('img/goods_bg.png');
        // 二维码
        $qr_code = $file_path . 'goods_qrcode_' . $share_type . '_' . $goods_id . '_' . $user_id . '.png';
        // 用户头像
        $avatar = $avatar_file . 'avatar_' . $user_id . '.png';
        // 输出图片
        $out_img = $file_path . 'goods_share_' . $share_type . '_' . $goods_id . '_' . $user_id . '.png';

        // 生成二维码条件
        $generate = false;
        if (file_exists($out_img)) {
            $lastmtime = filemtime($out_img) + 3600 * 24 * 1; // 1天有效期之后重新生成
            if (time() >= $lastmtime) {
                $generate = true;
            }
        }

        if (!file_exists($out_img) || $generate == true) {

            // 1. 生成背景图+商品信息
            $bg_width = Image::open($goods_bg)->width(); // 背景图宽
            $bg_height = Image::open($goods_bg)->height(); // 背景图高

            // 处理商品标题
            $goods_title = strip_tags($goods['goods_name']);
            $goods_title_first = Str::limit($goods_title, 35);

            $goods_price = $this->dscRepository->getPriceFormat($goods['shop_price']);

            // 生成文字
            $fonts_path = storage_public('data/attached/fonts/msyh.ttf');
            $font_color = "#333333";

            // 生成商品标题 + 价格
            if (!empty($goods_title_first) && !empty($goods_price)) {
                Image::open($goods_bg)->text($goods_title_first, $fonts_path, 20, $font_color, [20, $bg_width + 20])->save($out_img);
                Image::open($out_img)->text($goods_price, $fonts_path, 28, '#EC5151', [40, $bg_width + 70])->save($out_img);
            }

            // 商品图 默认相册图第一张
            $gallery_list = $this->goodsGalleryService->getGalleryList(['goods_id' => $goods_id]);
            $pictures_one = $gallery_list['0'] ?? '';
            $goods_img = isset($pictures_one['img_url']) ? $pictures_one['img_url'] : $goods['goods_img'];
            // 保存缩略图路径
            $goods_thumb = $goods_thumb_file . $goods_id . '_' . basename($goods_img);

            if (!file_exists($goods_thumb) && $goods_img) {
                // 生成商品图缩略图
                // 远程图片（非本站）
                if (strtolower(substr($goods_img, 0, 4)) == 'http' && strpos($goods_img, asset('/')) === false) {
                    $goodsimg = Http::doGet($goods_img);
                    $goodsthumb = $goods_thumb;
                    file_put_contents($goodsthumb, $goodsimg);
                } else {
                    // 本站图片 带http 或 不带http
                    if (strtolower(substr($goods_img, 0, 4)) == 'http') {
                        $goods_img = str_replace(storage_url('/'), '', $goods_img);
                    }
                    // 默认图片
                    if (strpos($goods_img, 'no_image') !== false) {
                        $goodsthumb = $goods_img;
                    } else {
                        $goodsthumb = storage_public($goods_img);
                    }
                }

                if (file_exists($goodsthumb)) {
                    Image::open($goodsthumb)->thumb($bg_width, $bg_width, Image::THUMB_FILLED)->save($goods_thumb);
                }
            }

            // 商品缩略图
            if (file_exists($goods_thumb)) {
                Image::open($out_img)->water($goods_thumb, [0, 0], 100)->save($out_img);
            }

            // 2.生成二维码
            $qrCode = new QrCode($goods_url);

            $qrCode->setSize(257);
            $qrCode->setMargin(15);

            // 生成二维码+微信头像
            $user_picture = empty($users['user_picture']) ? public_path('img/user_default.png') : $this->dscRepository->getImagePath($users['user_picture']);
            // 生成微信头像缩略图
            if (!empty($user_picture)) {
                // 远程图片（非本站）
                if (strtolower(substr($user_picture, 0, 4)) == 'http' && strpos($user_picture, asset('/')) === false) {
                    $user_picture = Http::doGet($user_picture);
                    $avatar_open = $avatar;
                    file_put_contents($avatar_open, $user_picture);
                } else {
                    // 本站图片 带http 或 不带http
                    if (strtolower(substr($user_picture, 0, 4)) == 'http') {
                        $user_picture = str_replace(storage_url('/'), '', $user_picture);
                    }
                    // 默认图片
                    if (strpos($user_picture, 'user_default') !== false || strpos($user_picture, 'no_image') !== false) {
                        $avatar_open = $user_picture;
                    } else {
                        $avatar_open = storage_public($user_picture);
                    }
                }
                if (file_exists($avatar_open)) {
                    Image::open($avatar_open)->thumb(60, 60, Image::THUMB_FILLED)->save($avatar);
                }
            }
            if (file_exists($avatar)) {
                $qrCode->setLogoPath($avatar);
                $qrCode->setLogoWidth(50); // 头像默认居中
            }

            $qrCode->writeFile($qr_code); // 保存二维码

            $nickname = empty($users['nick_name']) ? $users['user_name'] : $this->dscRepository->subStr($users['nick_name'], 10, false); //用户昵称
            if ($share_type == 1) {
                // 分销文案
                $text_description = lang('goods.share_drp_desc', ['name' => $nickname, 'shop_name' => $this->config['shop_name']]);
            } else {
                // 推荐分成文案
                $text_description = lang('goods.share_desc', ['name' => $nickname]);
            }
            // 二维码坐标
            $qr_left = 50; // 左50
            $qr_top = $bg_width + 130;
            // 文案文字坐标
            $text_left = $bg_width / 8; // 背景图宽度的1/8位置 使文字居中显示
            $text_top = $bg_height - 40; // 背景图高减去40

            // 3. 最后生成 商品图+二维码
            Image::open($out_img)->water($qr_code, [$qr_left, $qr_top], 100)->text($text_description, $fonts_path, 18, $font_color, [$text_left, $text_top])->save($out_img);
        }

        $image_name = 'data/attached/goods_share/' . basename($out_img);

        return [
            'file' => $image_name,
            'url' => $this->dscRepository->getImagePath($image_name) . '?v=' . Str::random(32)
        ];
    }

    /**
     * 清空配件购物车
     * @param int $goods_id
     * @return string
     */
    public function clearCartCombo($user_id = 0, $goods_id = 0)
    {
        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;
        $cart = CartCombo::Where('goods_id', $goods_id)
            ->where('parent_id', 0)
            ->orWhere('parent_id', $goods_id);

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        return $cart->delete();
    }


    /**
     * 配件商品价格
     *
     * @param int $goods_id
     * @param int $parent_id
     * @return int
     */
    public function groupGoodsInfo($goods_id = 0, $parent_id = 0)
    {
        $goods_price = GroupGoods::where('goods_id', $goods_id)->where('parent_id', $parent_id)->value('goods_price');
        $goods_price = $goods_price ? $goods_price : 0;
        return $goods_price;
    }

    /**
     * 更新配件购物车
     *
     * @param int $user_id
     * @param int $group_id
     * @param int $goods_id
     * @param array $cart_combo_data
     * @return mixed
     */
    public function updateCartCombo($user_id = 0, $group_id = 0, $goods_id = 0, $cart_combo_data = [])
    {
        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;
        $cart = CartCombo::Where('goods_id', $goods_id)
            ->where('group_id', $group_id);

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        return $cart->update($cart_combo_data);
    }

    /**
     * 查看是否秒杀
     */
    public function getIsSeckill($goods_id = 0)
    {
        $date_begin = $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate('Ymd'));
        $stb_time = $this->timeRepository->getLocalDate('H:i:s');

        $res = SeckillGoods::select('tb_id', 'id as sec_goods_id')
            ->where('goods_id', $goods_id);

        $where = [
            'date_begin' => $date_begin,
            'stb_time' => $stb_time
        ];

        $res = $res->whereHas('getSeckillTimeBucket', function ($query) use ($where) {
            $query->where('begin_time', '<=', $where['stb_time'])
                ->where('end_time', '>', $where['stb_time']);
        });

        $res = $res->whereHas('getSeckill', function ($query) use ($where) {
            $query->where('begin_time', '<=', $where['date_begin'])
                ->where('acti_time', '>', $where['date_begin'])
                ->where('is_putaway', 1)
                ->where('review_status', 3);
        });

        $res = $res->with([
            'getSeckillTimeBucket' => function ($query) {
                $query->select('id', 'begin_time');
            }
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $res[$key]['begin_time'] = $val['get_seckill_time_bucket'] ? $val['get_seckill_time_bucket']['begin_time'] : 0;
                $res[$key]['begin_time'] = $res[$key]['begin_time'] ? $this->timeRepository->getLocalStrtoTime($res[$key]['begin_time']) : $res[$key]['begin_time'];
            }
        }

        $res = $this->baseRepository->getSortBy($res, 'begin_time');
        $res = $this->baseRepository->getTake($res, 1);

        $sec_goods_id = 0;
        if ($res) {
            $sec_goods_id = $res[0]['sec_goods_id'];
        }

        return $sec_goods_id;
    }

    /**
     *  所有的促销活动信息
     *
     * @access  public
     * @return  array
     */
    public function getPromotionInfo($user_id = 0, $goods_id = 0, $ru_id = 0, $goods = [])
    {
        $snatch = [];
        $group = [];
        $auction = [];
        $package = [];
        $favourable = [];

        // 获取促销活动
        $list = $this->goodsActivityList($goods_id, $ru_id);
        foreach ($list as $data) {
            switch ($data['act_type']) {
                case GAT_SNATCH: //夺宝奇兵
                    $snatch[$data['act_id']]['act_id'] = $data['act_id'];
                    $snatch[$data['act_id']]['act_name'] = $data['act_name'];
                    $snatch[$data['act_id']]['url'] = url('snatch/index/detail', ['id' => $data['act_id']]);
                    $snatch[$data['act_id']]['time'] = sprintf(L('promotion_time'), $this->timeRepository->getLocalDate('Y-m-d', $data['start_time']), $this->timeRepository->getLocalDate('Y-m-d', $data['end_time']));
                    $snatch[$data['act_id']]['sort'] = $data['start_time'];
                    $snatch[$data['act_id']]['type'] = 'snatch';
                    break;

                case GAT_GROUP_BUY: //团购
                    $group[$data['act_id']]['act_id'] = $data['act_id'];
                    $group[$data['act_id']]['act_name'] = $data['act_name'];
                    $group[$data['act_id']]['url'] = route('api.groupbuy.detail', ['group_buy_id' => $data['act_id']]);
                    $group[$data['act_id']]['time'] = sprintf(L('promotion_time'), $this->timeRepository->getLocalDate('Y-m-d', $data['start_time']), $this->timeRepository->getLocalDate('Y-m-d', $data['end_time']));
                    $group[$data['act_id']]['sort'] = $data['start_time'];
                    $group[$data['act_id']]['type'] = 'group_buy';
                    break;

                case GAT_AUCTION: //拍卖
                    $auction[$data['act_id']]['act_id'] = $data['act_id'];
                    $auction[$data['act_id']]['act_name'] = $data['act_name'];
                    $auction[$data['act_id']]['url'] = route('api.auction.detail', ['id' => $data['act_id']]);
                    $auction[$data['act_id']]['time'] = sprintf(L('promotion_time'), $this->timeRepository->getLocalDate('Y-m-d', $data['start_time']), $this->timeRepository->getLocalDate('Y-m-d', $data['end_time']));
                    $auction[$data['act_id']]['sort'] = $data['start_time'];
                    $auction[$data['act_id']]['type'] = 'auction';
                    break;

                case GAT_PACKAGE: //礼包
                    $package[$data['act_id']]['act_id'] = $data['act_id'];
                    $package[$data['act_id']]['act_name'] = $data['act_name'];
                    $package[$data['act_id']]['url'] = route('api.package.list');
                    $package[$data['act_id']]['time'] = sprintf(L('promotion_time'), $this->timeRepository->getLocalDate('Y-m-d', $data['start_time']), $this->timeRepository->getLocalDate('Y-m-d', $data['end_time']));
                    $package[$data['act_id']]['sort'] = $data['start_time'];
                    $package[$data['act_id']]['type'] = 'package';
                    break;
            }
        }

        //查询符合条件的优惠活动
        $res = $this->discountService->activityListAll($user_id, $ru_id);
        if (empty($goods_id)) {
            foreach ($res as $rows) {
                $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                $favourable[$rows['act_id']]['url'] = $favourable[$rows['act_id']]['url'] = route('api.activity.show', ['act_id' => $rows['act_id']]);
                $favourable[$rows['act_id']]['time'] = sprintf(L('promotion_time'), $this->timeRepository->getLocalDate('Y-m-d', $rows['start_time']), $this->timeRepository->getLocalDate('Y-m-d', $rows['end_time']));
                $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                $favourable[$rows['act_id']]['type'] = 'favourable';
                $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
            }
        } else {
            // 商品信息

            $category_id = $goods['cat_id'];
            $brand_id = $goods['brand_id'];

            foreach ($res as $rows) {
                if ($rows['act_range'] == FAR_ALL) {
                    $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                    $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    $favourable[$rows['act_id']]['url'] = route('api.activity.show', ['act_id' => $rows['act_id']]);
                } elseif ($rows['act_range'] == FAR_CATEGORY) {
                    /* 找出分类id的子分类id */
                    $id_list = [];
                    $raw_id_list = explode(',', $rows['act_range_ext']);

                    foreach ($raw_id_list as $id) {
                        /**
                         * 当前分类下的所有子分类
                         * 返回一维数组
                         */
                        $cat_list = $this->discountService->arr_foreach($this->discountService->catList($id));
                        $id_list = array_merge($id_list, $cat_list);
                        array_unshift($id_list, $id);
                    }
                    $ids = join(',', array_unique($id_list));
                    if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                        $favourable[$rows['act_id']]['url'] = route('api.activity.show', ['act_id' => $rows['act_id']]);
                    }
                } elseif ($rows['act_range'] == FAR_BRAND) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                        $favourable[$rows['act_id']]['url'] = route('api.activity.show', ['act_id' => $rows['act_id']]);
                    }
                } elseif ($rows['act_range'] == FAR_GOODS) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                        $favourable[$rows['act_id']]['url'] = route('api.activity.show', ['act_id' => $rows['act_id']]);
                    }
                }
            }
        }

        $sort_time = [];
        $arr = array_merge($snatch, $group, $auction, $package, $favourable);
        foreach ($arr as $key => $value) {
            $sort_time[] = $value['sort'];
        }
        array_multisort($sort_time, SORT_NUMERIC, SORT_DESC, $arr);

        return $arr;
    }

    /**
     * 查询商品满减促销信息
     * @param int $goods_id
     * @param string $table
     * @param int $type
     * @return array
     */
    public function goodsConList($goods_id = 0, $table = '', $type = 0)
    {
        if ($table == 'goods_consumption') {
            $res = GoodsConsumption::where('goods_id', $goods_id);
        } else {
            $res = GoodsConshipping::where('goods_id', $goods_id);
        }
        $res = $res->get();
        $res = $res ? $res->toArray() : [];
        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $arr[$key]['id'] = $row['id'];
                if ($type == 0) {
                    $arr[$key]['cfull'] = $row['cfull'];
                    $arr[$key]['creduce'] = $row['creduce'];
                } elseif ($type == 1) {
                    $arr[$key]['sfull'] = $row['sfull'];
                    $arr[$key]['sreduce'] = $row['sreduce'];
                }
            }

            if ($type == 1) {
                $sort = 'sfull';
            } else {
                $sort = 'cfull';
            }
            $arr = collect($arr)->sortBy($sort)->values()->all();
        }
        return $arr;
    }

    /**
     * 获取商品的视频列表
     * @param int $goods_id
     * @param $table
     * @param int $type
     */
    public function getVideoList($size = 10, $page = 1, $user_id = 0, $where)
    {
        $sort = 'goods_id';
        $order = 'DESC';

        $res = Goods::select('goods_id', 'user_id', 'goods_video', 'goods_name', 'goods_thumb', 'sales_volume', 'model_price', 'shop_price', 'promote_price', 'integral', 'goods_number')
            ->where('goods_video', '<>', '')
            ->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('review_status', '>', 2);
        if ($user_id > 0) {
            $collectWhere = [
                'user_id' => $user_id
            ];
            $res = $res->withCount([
                'getCollectGoods as is_collect' => function ($query) use ($collectWhere) {
                    $query->where('user_id', $collectWhere['user_id']);
                }
            ]);
        }
        $res = $res->withCount([
            'getComment as comment_num'
        ]);

        $where['warehouse_id'] = $where['warehouse_id'] ?? 0;
        $where['area_id'] = $where['area_id'] ?? 0;
        $where['area_city'] = $where['area_city'] ?? 0;
        $where['area_pricetype'] = $this->config['area_pricetype'];
        if ($user_id > 0) {
            $rank = $this->userCommonService->getUserRankByUid($user_id);
            $user_rank = $rank['rank_id'];
            $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $user_rank = 1;
            $user_discount = 100;
        }
        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
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
            },
            'getGoodsExtend',
            'getSellerShopInfo' => function ($query) {
                $query->select('shop_name', 'ru_id', 'shop_logo');
            },
        ]);

        $start = ($page - 1) * $size;

        $res = $res->orderBy($sort, $order);

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $key => $val) {
            $price = [
                'model_price' => isset($val['model_price']) ? $val['model_price'] : 0,
                'user_price' => isset($val['get_member_price']['user_price']) ? $val['get_member_price']['user_price'] : 0,
                'percentage' => isset($val['get_member_price']['percentage']) ? $val['get_member_price']['percentage'] : 0,
                'warehouse_price' => isset($val['get_warehouse_goods']['warehouse_price']) ? $val['get_warehouse_goods']['warehouse_price'] : 0,
                'region_price' => isset($val['get_warehouse_area_goods']['region_price']) ? $val['get_warehouse_area_goods']['region_price'] : 0,
                'shop_price' => isset($val['shop_price']) ? $val['shop_price'] : 0,
                'warehouse_promote_price' => isset($val['get_warehouse_goods']['warehouse_promote_price']) ? $val['get_warehouse_goods']['warehouse_promote_price'] : 0,
                'region_promote_price' => isset($val['get_warehouse_area_goods']['region_promote_price']) ? $val['get_warehouse_area_goods']['region_promote_price'] : 0,
                'promote_price' => isset($val['promote_price']) ? $val['promote_price'] : 0,
                'integral' => isset($val['integral']) ? $val['integral'] : 0,
                'wpay_integral' => isset($val['get_warehouse_goods']['pay_integral']) ? $val['get_warehouse_goods']['pay_integral'] : 0,
                'apay_integral' => isset($val['get_warehouse_area_goods']['pay_integral']) ? $val['get_warehouse_area_goods']['pay_integral'] : 0,
                'goods_number' => isset($val['goods_number']) ? $val['goods_number'] : 0,
                'wg_number' => isset($val['get_warehouse_goods']['region_number']) ? $val['get_warehouse_goods']['region_number'] : 0,
                'wag_number' => isset($val['get_warehouse_area_goods']['region_number']) ? $val['get_warehouse_area_goods']['region_number'] : 0,
            ];
            $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount / 100, $val);

            $res[$key]['shop_price_formated'] = $this->dscRepository->getPriceFormat($price['shop_price']);
            if ($user_id == 0) {
                $res[$key]['is_collect'] = 0;
            }
            $res[$key]['goods_thumb'] = empty($val['goods_thumb']) ? '' : $this->dscRepository->getImagePath($val['goods_thumb']);
            $res[$key]['goods_video'] = empty($val['goods_video']) ? '' : $this->dscRepository->getImagePath($val['goods_video']);
            $res[$key]['shop_logo'] = empty($val['get_seller_shop_info']['shop_logo']) ? '' : $this->dscRepository->getImagePath($val['get_seller_shop_info']['shop_logo']);
            $res[$key]['shop_name'] = $val['get_seller_shop_info']['shop_name'];

        }

        return $res;
    }

    /**
     * 更新商品点击量
     * @param int $goods_id
     * @return bool
     */
    public function updateGoodsClick($goods_id = 0)
    {
        if (empty($goods_id)) {
            return false;
        }
        return Goods::where('goods_id', $goods_id)->increment('click_count', 1);
    }

}
