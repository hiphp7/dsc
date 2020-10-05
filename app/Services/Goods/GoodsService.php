<?php

namespace App\Services\Goods;

use App\Libraries\Http;
use App\Models\Attribute;
use App\Models\AutoManage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\GoodsArticle;
use App\Models\GoodsAttr;
use App\Models\LinkDescGoodsid;
use App\Models\LinkGoods;
use App\Models\OrderGoods;
use App\Models\SeckillGoods;
use App\Models\Suppliers;
use App\Models\UserRank;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryService;
use App\Services\Common\AreaService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserRankService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Str;
use think\Image;

class GoodsService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $timeRepository;
    protected $config;
    protected $userRankService;
    protected $goodsCommonService;
    protected $goodsCommentService;
    protected $merchantCommonService;
    protected $goodsWarehouseService;
    protected $city = 0;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        UserRankService $userRankService,
        GoodsCommonService $goodsCommonService,
        GoodsCommentService $goodsCommentService,
        MerchantCommonService $merchantCommonService,
        GoodsWarehouseService $goodsWarehouseService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->userRankService = $userRankService;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsCommentService = $goodsCommentService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->config = $this->dscRepository->dscConfig();

        $this->city = app(AreaService::class)->areaCookie();
    }

    /**
     * 获得推荐商品
     *
     * @param array $where
     * @param int $num 限制显示数量
     * @return mixed
     * @throws \Exception
     */
    public function getRecommendGoods($where = [], $num = 3)
    {

        $type = $where['type'] ?? '';
        $seller_id = $where['seller_id'] ?? 0;
        $warehouse_id = $where['warehouse_id'] ?? 0;
        $area_id = $where['area_id'] ?? 0;
        $area_city = $where['area_city'] ?? 0;
        $presale = $where['presale'] ?? '';
        $rec_type = $where['rec_type'] ?? 0;
        $discount = session('discount');
        $user_rank = $user_rank = session('user_rank');
        $area_pricetype = $this->config['area_pricetype'];

        $where['discount'] = $discount;
        $where['user_rank'] = $user_rank;
        $where['area_pricetype'] = $area_pricetype;
        $where['num'] = $num;

        //缓存
        $cache_id = $type . '_' . $seller_id . '_' . $warehouse_id . '_' . $area_id . '_' . $area_city . '_' . $presale . '_' . $rec_type . '_' . $discount . '_' . $user_rank . '_' . $area_pricetype;

        $content = cache()->rememberForever('get_recommend_goods.' . $cache_id, function () use ($where) {
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

                $goods_res = $goods_res->orderBy('sort_order', 'ASC')->orderBy('goods_id', 'DESC');

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
                            $num = $where['num'];
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
                $result = Goods::where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('is_delete', 0)
                    ->where('is_show', 1);

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

                $result = $result->with([
                    'getMemberPrice' => function ($query) use ($where) {
                        $query->where('user_rank', $where['user_rank']);
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

                        $price = $this->goodsCommonService->getGoodsPrice($price, $where['discount'], $row);

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
        });

        return $content;
    }

    /**
     * 获得促销商品
     *
     * @param array $where
     * @param int $num 限制显示数量
     * @return array
     */
    public function getPromoteGoods($where = [], $num = 3)
    {
        $time = $this->timeRepository->getGmTime();
        $order_type = $this->config['recommend_order'];

        $result = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_promote', 1);

        $result = $result->where('promote_start_date', '<=', $time)
            ->where('promote_end_date', '>=', $time);

        $result = $this->dscRepository->getAreaLinkGoods($result, $where['area_id'], $where['area_city']);

        if ($this->config['review_goods'] == 1) {
            $result = $result->where('review_status', '>', 2);
        }

        $where['area_pricetype'] = $this->config['area_pricetype'];

        $user_rank = session('user_rank');
        $result = $result->with([
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

        if ($order_type == 0) {
            $result = $result->orderByRaw('sort_order, last_update desc');
        } else {
            $result = $result->orderByRaw('RAND()');
        }

        $result = $result->take($num);

        $result = $this->baseRepository->getToArrayGet($result);

        $goods = [];
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

                $row = $row['get_brand'] ? array_merge($row, $row['get_brand']) : $row;

                $goods[$idx] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $goods[$idx]['id'] = $row['goods_id'];
                $goods[$idx]['s_time'] = $row['promote_start_date'];
                $goods[$idx]['e_time'] = $row['promote_end_date'];
                $goods[$idx]['t_now'] = $time;
                $goods[$idx]['id'] = $row['goods_id'];
                $goods[$idx]['name'] = $row['goods_name'];
                $goods[$idx]['brief'] = $row['goods_brief'];
                $goods[$idx]['brand_name'] = isset($row['brand_name']) ? $row['brand_name'] : '';
                $goods[$idx]['comments_number'] = $row['comments_number'];
                $goods[$idx]['sales_volume'] = $row['sales_volume'];
                $goods[$idx]['goods_style_name'] = $this->goodsCommonService->addStyle($row['goods_name'], $row['goods_name_style']);
                $goods[$idx]['short_name'] = $this->config['goods_name_length'] > 0 ? $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $goods[$idx]['short_style_name'] = $this->goodsCommonService->addStyle($goods[$idx]['short_name'], $row['goods_name_style']);
                $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $goods[$idx]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $goods[$idx]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $goods[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $goods[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            }
        }

        return $goods;
    }

    /**
     * 获得商品的详细信息
     *
     * @param array $where
     * @return mixed
     * @throws \Exception
     */
    public function getGoodsInfo($where = [])
    {

        $area_pricetype = $this->config['area_pricetype'];

        if (session()->has('user_rank')) {
            $user_rank = session('user_rank');
            $discount = session('discount');
        } else {
            $user_rank = 1;
            $discount = 1;
            if (isset($where['user_id']) && $where['user_id']) {
                $user_rank = $this->userRankService->getUserRankInfo($where['user_id']);
                if ($user_rank) {
                    $user_rank = $user_rank['user_rank'] ?? 1;
                    $discount = $user_rank['discount'] ?? 1;
                }
            }
        }

        $where['area_pricetype'] = $area_pricetype;
        $where['user_rank'] = $user_rank;
        $where['discount'] = $discount;

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

        $res = $res->with([
            'getGoodsCategory',
            'getMemberPrice' => function ($query) use ($where) {
                $query->where('user_rank', $where['user_rank']);
            },
            'getWarehouseGoods' => function ($query) use ($where) {
                $where['warehouse_id'] = $where['warehouse_id'] ?? 0;
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

        $row = $this->baseRepository->getToArrayFirst($res);
        $time = $this->timeRepository->getGmTime();

        $tag = [];
        if ($row) {
            $category = $row['get_goods_category'];
            $brand = $row['get_brand'];

            if ($brand) {
                $brand['url'] = $this->dscRepository->buildUri('brand', ['bid' => $brand['brand_id']], $brand['brand_name']);
                $brand['brand_logo'] = $this->dscRepository->getImagePath($this->dscRepository->dataDir() . '/brandlogo/' . $brand['brand_logo']);

                $row['brand'] = $brand;
                $row['goods_brand_url'] = !empty($brand) ? $brand['url'] : '';
            }

            $row['cat_measure_unit'] = $category['measure_unit'];

            if ($row['brand_id']) {
                $row['brand_name'] = $brand['brand_name'];
                $row['brand_url'] = $this->dscRepository->buildUri('brand', ['bid' => $row['brand_id']], $row['brand_name']);
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

            $row['shop_price_original'] = $price['shop_price'];
            $price = $this->goodsCommonService->getGoodsPrice($price, $where['discount'], $row);

            $row['shop_price'] = $price['shop_price'];
            $row['promote_price'] = $price['promote_price'];
            $row['integral'] = $price['integral'];
            $row['goods_number'] = $price['goods_number'];

            //@author-bylu 将分期数据反序列化为数组 start
            if (!empty($row)) {
                $row['stages'] = unserialize($row['stages']);
            }
            //@author-bylu  end

            /* 修正促销价格 */
            if ($row['promote_price'] > 0) {
                $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

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

            $row['promote_price_org'] = $promote_price;
            $row['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';

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

            /* 促销时间倒计时 */
            if ($time >= $row['promote_start_date'] && $time <= $row['promote_end_date']) {
                $row['gmt_end_time'] = $row['promote_end_date'];
            } else {
                $row['gmt_end_time'] = 0;
            }

            $row['promote_end_time'] = !empty($row['gmt_end_time']) ? $this->timeRepository->getLocalDate($this->config['time_format'], $row['gmt_end_time']) : 0;

            /* 是否显示商品库存数量 */
            $row['goods_number'] = ($this->config['use_storage'] == 1) ? $row['goods_number'] : 1;

            /* 修正积分：转换为可使用多少积分（原来是可以使用多少钱的积分） */
            $row['integral'] = $this->config['integral_scale'] ? round($row['integral'] * 100 / $this->config['integral_scale']) : 0;

            /* 修正商品图片 */

            //查询关联商品描述 start
            if ($row['goods_desc'] == '<p><br/></p>' || empty($row['goods_desc'])) {
                $GoodsDesc = $this->getLinkGoodsDesc($row['goods_id'], $row['user_id']);
                $link_desc = $GoodsDesc ? $GoodsDesc['goods_desc'] : '';

                if (!empty($link_desc)) {
                    $row['goods_desc'] = $link_desc;
                }
            }
            //查询关联商品描述 end

            $desc_preg = $this->dscRepository->descImagesPreg($row['goods_desc']);
            $row['goods_desc'] = $desc_preg['goods_desc'];

            $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $row['original_img'] = $this->dscRepository->getImagePath($row['original_img']);
            $row['goods_video_path'] = !empty($row['goods_video']) ? $this->dscRepository->getImagePath($row['goods_video']) : '';

            /* 获得商品的销售价格 */
            $row['marketPrice'] = $row['market_price'];
            $row['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
            if ($promote_price > 0) {
                $row['shop_price_formated'] = $row['promote_price'];
                $row['goods_price'] = $promote_price;
            } else {
                $row['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $row['goods_price'] = $row['shop_price'];
            }

            $row['shop_price'] = round($row['shop_price'], 2);
            $row['promote_price'] = $promote_price > 0 ? $this->dscRepository->getPriceFormat($promote_price) : '';

            $row['format_promote_price'] = $promote_price > 0 ? $promote_price : 0;

            $row['goodsweight'] = $row['goods_weight'];

            $row['isHas_attr'] = GoodsAttr::where('goods_id', $row['goods_id'])->count();

            $seller_info = $this->merchantCommonService->getShopName($row['user_id'], 3); //店铺信息
            $row['rz_shopName'] = isset($seller_info['shop_name']) ? $seller_info['shop_name'] : '';

            $build_uri = [
                'urid' => $row['user_id'],
                'append' => $row['rz_shopName']
            ];
            $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
            $row['store_url'] = $domain_url['domain_name'];

            if (isset($seller_info['shopinfo'])) {
                $row['shopinfo'] = $seller_info['shopinfo'];
                $row['shopinfo']['brand_thumb'] = get_brand_image_path($row['shopinfo']['brand_thumb']);
                $row['shopinfo']['brand_thumb'] = str_replace(['../'], '', $row['shopinfo']['brand_thumb']);
                $row['shopinfo']['brand_thumb'] = $this->dscRepository->getImagePath($row['shopinfo']['brand_thumb']);
            }

            $row['goods_url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

            $consumption = app(CartCommonService::class)->getGoodsConList($row['goods_id'], 'goods_consumption'); //满减订单金额
            $row['consumption'] = $consumption;

            /* 修正重量显示 */
            $row['goods_weight'] = $row['goods_weight'] . lang('goods.kilogram');

            $suppliers = Suppliers::where('suppliers_id', $row['suppliers_id'])->first();
            $suppliers = $suppliers ? $suppliers->toArray() : [];

            if ($suppliers) {
                $row['suppliers_name'] = $suppliers['suppliers_name'];
            }

            //买家印象
            if ($row['goods_product_tag']) {
                $impression_list = !empty($row['goods_product_tag']) ? explode(',', $row['goods_product_tag']) : '';

                if ($impression_list) {
                    foreach ($impression_list as $kk => $vv) {
                        $tag[$kk]['txt'] = $vv;
                        //印象数量
                        $tag[$kk]['num'] = $this->goodsCommentService->commentGoodsTagNum($row['goods_id'], $vv);
                    }
                }

                $row['impression_list'] = $tag;
            }
            //上架下架时间

            $manage_info = AutoManage::where('type', 'goods')->where('item_id', $row['goods_id'])->first();
            $manage_info = $manage_info ? $manage_info->toArray() : [];

            if ($manage_info) {
                $row['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $manage_info['starttime']);
            } else {
                /* 修正上架时间显示 */
                $row['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
            }

            $row['end_time'] = $manage_info ? $this->timeRepository->getLocalDate($this->config['time_format'], $manage_info['endtime']) : '';
        }

        return $row;
    }

    /**
     * 获得商品列表
     *
     * @param array $where
     * @return mixed
     */
    public function getGoodsList($where = [])
    {
        $res = Goods::whereRaw(1);

        if (isset($where['goods_id'])) {
            $res = $res->where('goods_id', $where['goods_id']);
        }

        if (isset($where['is_delete'])) {
            $res = $res->where('is_delete', $where['is_delete']);
        }

        if (isset($where['is_on_sale'])) {
            $res = $res->where('is_on_sale', $where['is_on_sale']);
        }

        if (isset($where['is_alone_sale'])) {
            $res = $res->where('is_alone_sale', $where['is_alone_sale']);
        }

        if (isset($where['cat_id'])) {
            $where['cat_id'] = !is_array($where['cat_id']) ? explode(",", $where['cat_id']) : $where['cat_id'];
            $res = $res->whereIn('cat_id', $where['cat_id']);
        }

        if (isset($where['user_cat'])) {
            $where['user_cat'] = !is_array($where['user_cat']) ? explode(",", $where['user_cat']) : $where['user_cat'];
            $res = $res->whereIn('user_cat', $where['user_cat']);
        }

        if (isset($where['brand_id'])) {
            $res = $res->where('brand_id', $where['brand_id']);
        }

        if (isset($where['intro_type']) && $where['intro_type'] == 'is_promote') {
            $res = $res->where('promote_start_date', '<=', $where['time']);
            $res = $res->where('promote_end_date', '>=', $where['time']);
        }

        if (isset($where['intro_type']) && $where['intro_type']) {
            $res = $res->where($where['intro_type'], 1);
        }

        if (isset($where['collect']) && $where['collect'] == 'collect_goods') {
            $res = $res->whereHas('getCollectGoods', function ($query) use ($where) {
                if (isset($where['user_id'])) {
                    $query->where('user_id', $where['user_id']);
                }
            });
        }

        $where['area_pricetype'] = $this->config['area_pricetype'];
        $where['warehouse_id'] = $where['warehouse_id'] ?? 0;
        $where['area_id'] = $where['area_id'] ?? 0;
        $where['area_city'] = $where['area_city'] ?? 0;

        $user_rank = session('user_rank');
        $res = $res->with([
            'getGoodsCategory',
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

        if (isset($where['sort_rnd'])) {
            $res = $res->orderByRaw($where['sort_rnd']);
        } else {
            if (isset($where['sort']) && isset($where['order'])) {
                if (is_array($where['sort'])) {
                    $where['sort'] = implode(",", $where['sort']);
                    $res = $res->orderByRaw($where['sort'] . " " . $where['order']);
                } else {
                    $res = $res->orderBy($where['sort'], $where['order']);
                }
            }
        }

        if (isset($where['page'])) {
            $start = ($where['page'] - 1) * $where['size'];
        }

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if (isset($where['size']) && $where['size'] > 0) {
            $res = $res->take($where['size']);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
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

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $row['promote_price'] = $promote_price;

                $cat = $row['get_goods_category'] ? $row['get_goods_category'] : [];
                $row['cat_id'] = $cat ? $cat['cat_id'] : 0;
                $row['cat_name'] = $cat ? $cat['cat_name'] : '';

                $brand = $row['get_brand'] ? $row['get_brand'] : [];
                $row['brand_id'] = $brand ? $brand['brand_id'] : 0;
                $row['brand_name'] = $brand ? $brand['brand_name'] : '';

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 获得指定商品的关联商品
     *
     * @access  public
     * @param integer $goods_id
     * @return  array
     */
    public function getLinkedGoods($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $res = LinkGoods::where('goods_id', $goods_id);

        $where = [
            'open_area_goods' => $this->config['open_area_goods'],
            'area_id' => $area_id,
            'area_city' => $area_city
        ];
        $res = $res->whereHas('getGoods', function ($query) use ($where) {
            $query = $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            $this->dscRepository->getAreaLinkGoods($query, $where['area_id'], $where['area_city']);
        });

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getGoods',
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
            }
        ]);

        $res = $res->take($this->config['related_goods_number']);

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $row = $this->baseRepository->getArrayMerge($row, $row['get_goods']);

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

                $goods_id = $row['link_goods_id'];

                $arr[$goods_id] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

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
                    $arr[$goods_id]['watermark_img'] = $watermark_img;
                }

                $arr[$goods_id]['goods_id'] = $row['goods_id'];
                $arr[$goods_id]['goods_name'] = $row['goods_name'];
                $arr[$goods_id]['short_name'] = $this->config['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $arr[$goods_id]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$goods_id]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $arr[$goods_id]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$goods_id]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $arr[$goods_id]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $arr[$goods_id]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $arr[$goods_id]['sales_volume'] = $row['sales_volume'];
            }
        }

        return $arr;
    }

    /**
     * 获得指定商品的关联文章
     *
     * @param $goods_id
     * @return array|\Illuminate\Support\Collection
     */
    public function getLinkedArticles($goods_id)
    {
        $res = GoodsArticle::where('goods_id', $goods_id);

        $res = $res->whereHas('getArticleInfo', function ($query) {
            $query->where('is_open', 1);
        });

        $res = $res->with(['getArticleInfo']);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $row = $row['get_article_info'] ? array_merge($row, $row['get_article_info']) : $row;

                $row['url'] = $row['open_type'] != 1 ?
                    $this->dscRepository->buildUri('article', ['aid' => $row['article_id']], $row['title']) : trim($row['file_url']);
                $row['add_time'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['add_time']);
                $row['short_title'] = $this->config['article_title_length'] > 0 ?
                    $this->dscRepository->subStr($row['title'], $this->config['article_title_length']) : $row['title'];

                $res[$key] = $row;
            }

            $res = collect($res)->sortByDesc('add_time');
            $res = $res->values()->all();
        }


        return $res;
    }

    /**
     * 获得指定商品的各会员等级对应的价格
     *
     * @access  public
     * @param integer $goods_id
     * @return  array
     */
    public function getUserRankPrices($goods_id = 0, $shop_price = 0)
    {
        if (empty($shop_price)) {
            $shop_price = 0;
        }

        $res = UserRank::select('r.rank_id', 'mp.user_price', 'r.discount', 'r.rank_name', 'mp.user_price')
            ->from('user_rank as r')
            ->leftJoin('member_price AS mp', function ($join) use ($goods_id) {
                $join->on('mp.user_rank', '=', 'r.rank_id')
                    ->where('mp.goods_id', '=', $goods_id);
            })
            ->where('r.show_price', 1)
            ->orWhere('r.rank_id', session('user_rank'));
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                if ($row['user_price']) {
                    $row['price'] = $row['user_price'];
                } else {
                    $row['price'] = $row['discount'] * $shop_price / 100;
                }

                $arr[$row['rank_id']] = [
                    'rank_name' => htmlspecialchars($row['rank_name']),
                    'price' => $this->dscRepository->getPriceFormat($row['price'])];
            }
        }

        return $arr;
    }

    /**
     * 获得购买过该商品的人还买过的商品
     *
     * @access  public
     * @param integer $goods_id
     * @return  array
     */
    public function getAlsoBought($where = [])
    {
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->whereHas('getOrderGoods', function ($query) use ($where) {
                $query = $query->where('goods_id', $where['goods_id']);

                $query->whereHas('getOrderGoodsSeller', function ($query) use ($where) {
                    $query->where('goods_id', '<>', $where['goods_id']);
                });
            });

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

        $res = $res->take($this->config['bought_goods']);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
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

                if ($row['promote_price'] > 0) {
                    $row['promote_price'] = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    $row['formated_promote_price'] = $this->dscRepository->getPriceFormat($row['promote_price']);
                } else {
                    $row['promote_price'] = 0;
                }

                $order_goods = OrderGoods::where('goods_id', $row['goods_id']);
                $order_goods = $order_goods->with(['getOrderGoodsSellerList']);
                $row['num'] = $order_goods && $order_goods['get_order_goods_seller_list'] ? count($order_goods['get_order_goods_seller_list']) : 0;

                $row['short_name'] = $this->config['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $row['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 获得指定商品的销售排名
     *
     * @access  public
     * @param integer $goods_id
     * @return  integer
     */
    public function getGoodsRank($goods_id)
    {
        /* 统计时间段 */
        $period = intval($this->config['top10_time']);
        $add_time = $this->timeRepository->timePeriod($period);

        /* 查询该商品销量 */
        $res = OrderGoods::selectRaw("SUM(goods_number) AS goods_number")
            ->where('goods_id', $goods_id);

        $where = [
            'os_confirmed' => OS_CONFIRMED,
            'ss_shipped' => SS_SHIPPED,
            'ss_received' => SS_RECEIVED,
            'ps_payed' => PS_PAYED,
            'ps_paying' => PS_PAYING,
            'add_time' => $add_time,
            'period' => $period
        ];
        $res = $res->whereHas('getOrder', function ($query) use ($where) {
            $query = $query->where('main_count', 0);
            $query = $query->where('order_status', $where['os_confirmed'])
                ->whereIn('shipping_status', [$where['ss_shipped'], $where['ss_received']])
                ->whereIn('pay_status', [$where['ps_payed'], $where['ps_paying']]);

            if ($where['add_time']) {
                $query->where('add_time', '>', $where['add_time']);
            }
        });

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        $sales_count = isset($res['goods_number']) ? $res['goods_number'] : 0;

        if ($sales_count > 0) {
            /* 只有在商品销售量大于0时才去计算该商品的排行 */
            $res = OrderGoods::selectRaw("DISTINCT SUM(goods_number) AS num");

            $res = $res->whereHas('getOrder', function ($query) use ($where) {
                $query = $query->where('order_status', $where['os_confirmed'])
                    ->whereIn('shipping_status', [$where['ss_shipped'], $where['ss_received']])
                    ->whereIn('pay_status', [$where['ps_payed'], $where['ps_paying']]);

                if ($where['add_time']) {
                    $query->where('add_time', '>', $where['add_time']);
                }
            });

            $res = $res->groupBy('goods_id');

            $res = $res->havingRaw("SUM(goods_number) > $sales_count");

            $rank = $res->count();

            $rank = $rank + 1;

            if ($rank > 10) {
                $rank = 0;
            }
        } else {
            $rank = 0;
        }

        return $rank;
    }

    /**
     * 取得跟商品关联的礼包列表
     *
     * @param int $goods_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getPackageGoodsList($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $now = $this->timeRepository->getGmTime();

        $res = GoodsActivity::where('review_status', 3)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now);

        $res = $res->whereHas('getPackageGoods', function ($query) use ($goods_id) {
            $query->where('goods_id', $goods_id);
        });

        $whereGoods = [
            'goods_id' => $goods_id,
            'user_rank' => session('user_rank', 1),
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $res = $res->with([
            'getPackageGoodsList' => function ($query) use ($whereGoods) {
                $query = $query->select('package_id', 'goods_id', 'goods_number', 'admin_id', 'product_id');
                $query->with([
                    'getGoods' => function ($query) use ($whereGoods) {
                        $query->with([
                            'getMemberPrice' => function ($query) use ($whereGoods) {
                                $query->where('user_rank', $whereGoods['user_rank']);
                            },
                            'getWarehouseGoods' => function ($query) use ($whereGoods) {
                                $query->where('region_id', $whereGoods['warehouse_id']);
                            },
                            'getWarehouseAreaGoods' => function ($query) use ($whereGoods) {
                                $query = $query->where('region_id', $whereGoods['area_id']);

                                if ($whereGoods['area_pricetype'] == 1) {
                                    $query->where('city_id', $whereGoods['area_city']);
                                }
                            },

                        ]);
                    },
                    'getGoodsAttrList' => function ($query) {
                        $query = $query->whereHas('getGoodsAttribute', function ($query) {
                            $query->where('attr_type', 1);
                        });

                        $query->orderBy('attr_sort');
                    },
                    'getProducts' => function ($query) {
                        $query->select('product_id', 'goods_attr');
                    },
                    'getProductsWarehouse' => function ($query) use ($whereGoods) {
                        $query->select('product_id', 'goods_attr')
                            ->where('warehouse_id', $whereGoods['warehouse_id']);
                    },
                    'getProductsArea' => function ($query) use ($whereGoods) {
                        $query = $query->select('product_id', 'goods_attr')
                            ->where('area_id', $whereGoods['area_id']);

                        if ($whereGoods['area_pricetype'] == 1) {
                            $query->where('city_id', $whereGoods['area_city']);
                        }
                    }
                ]);
            }
        ]);

        $res = $res->orderBy('act_id');

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $tempkey => $value) {

                $res[$tempkey] = $value;

                $subtotal = 0;
                $row = unserialize($value['ext_info']);
                unset($value['ext_info']);
                if ($row) {
                    foreach ($row as $key => $val) {
                        $res[$tempkey][$key] = $val;
                    }
                }

                $goods_res = $value['get_package_goods_list'] ? $value['get_package_goods_list'] : [];

                $result_goods_attr = [];
                if ($goods_res) {
                    foreach ($goods_res as $key => $val) {
                        $val['goods_thumb'] = '';
                        $val['market_price'] = 0;
                        $val['shop_price'] = 0;
                        $val['promote_price'] = 0;

                        $goods = $val['get_goods'] ?? [];

                        /* 取商品属性 */
                        $result_goods_attr[] = $val['get_goods_attr_list'];

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
                            'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                            'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                            'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
                        ];

                        $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $goods);

                        $goods['shop_price'] = $price['shop_price'];
                        $goods['promote_price'] = $price['promote_price'];

                        if ($goods['promote_price'] > 0) {
                            $goods['promote_price'] = $this->goodsCommonService->getBargainPrice($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
                        } else {
                            $goods['promote_price'] = 0;
                        }

                        $val = $goods ? array_merge($val, $goods) : $val;


                        $goods_res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                        $goods_res[$key]['market_price'] = $this->dscRepository->getPriceFormat($val['market_price']);
                        $goods_res[$key]['rank_price'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                        $goods_res[$key]['promote_price'] = $this->dscRepository->getPriceFormat($val['promote_price']);
                        $subtotal += $goods['shop_price'] * $val['goods_number'];

                        if ($price['model_price'] == 1) {
                            $products = $val['get_products_warehouse'] ?? [];
                        } else if ($price['model_price'] == 1) {
                            $products = $val['get_parehouse_areaGoods'] ?? [];
                        } else {
                            $products = $val['get_products'] ?? [];
                        }

                        $goods_res[$key]['goods_attr'] = $products['goods_attr'] ?? '';
                    }
                }

                /* 取商品属性 */
                $result_goods_attr = $this->baseRepository->getArrayCollapse($result_goods_attr);

                $_goods_attr = [];
                if ($result_goods_attr) {
                    foreach ($result_goods_attr as $attrValue) {
                        if ($attrValue && $attrValue['attr_value']) {
                            $_goods_attr[$attrValue['goods_attr_id']] = $attrValue['attr_value'];
                        }
                    }
                }

                /* 处理货品 */
                $format = '[%s]';
                if ($goods_res) {
                    foreach ($goods_res as $key => $val) {
                        if (isset($val['goods_attr']) && $val['goods_attr'] != '') {
                            $goods_attr_array = explode('|', $val['goods_attr']);

                            $goods_attr = [];
                            foreach ($goods_attr_array as $_attr) {
                                if (isset($_goods_attr[$_attr]) && $_goods_attr[$_attr]) {
                                    $goods_attr[] = $_goods_attr[$_attr];
                                }
                            }

                            $goods_res[$key]['goods_attr_str'] = sprintf($format, implode('，', $goods_attr));
                        }
                    }
                }

                $res[$tempkey]['goods_list'] = $goods_res;
                $res[$tempkey]['subtotal'] = $this->dscRepository->getPriceFormat($subtotal);
                $res[$tempkey]['saving'] = $this->dscRepository->getPriceFormat(($subtotal - $res[$tempkey]['package_price']));
                $res[$tempkey]['package_price'] = $this->dscRepository->getPriceFormat($res[$tempkey]['package_price']);
            }
        }

        return $res;
    }

    /*
     * 相关分类
     */
    public function getGoodsRelatedCat($cat_id)
    {
        $parent_id = Category::where('cat_id', $cat_id)->value('parent_id');

        $res = Category::where('parent_id', $parent_id)->get();
        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['cat_id'] = $row['cat_id'];
                $res[$key]['cat_name'] = $row['cat_name'];
                $res[$key]['url'] = $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
            }
        }

        return $res;
    }

    /*
     * 同类其他品牌
     */
    public function getGoodsSimilarBrand($cat_id = 0)
    {
        $brand = Goods::select('brand_id')
            ->where('cat_id', $cat_id)
            ->groupBy('brand_id')
            ->get();
        $brand = $brand ? $brand->toArray() : [];
        $brand = $brand ? collect($brand)->pluck('brand_id')->all() : [];

        if ($brand) {
            $res = Brand::whereIn('brand_id', $brand)->where('is_show', 1)->get();
            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $key => $row) {
                    $res[$key]['url'] = $this->dscRepository->buildUri('brand', ['bid' => $row['brand_id']], $row['brand_name']);
                }
            }
        }

        return $res;
    }

    /**
     * 获取商品ajax属性是否都选中
     *
     * @param $goods_id
     * @param $goods_attr
     * @param $goods_attr_id
     * @return array
     */
    public function getGoodsAttrAjax($goods_id, $goods_attr, $goods_attr_id)
    {
        $arr = [];
        $arr['attr_id'] = [];
        if ($goods_attr) {
            $goods_attr = !is_array($goods_attr) ? explode(",", $goods_attr) : $goods_attr;

            $res = GoodsAttr::whereIn('attr_id', $goods_attr)
                ->where('goods_id', $goods_id)
                ->whereHas('getGoodsAttribute', function ($query) {
                    $query->where('attr_type', '>', 0);
                });

            if ($goods_attr_id) {
                $goods_attr_id = !is_array($goods_attr_id) ? explode(",", $goods_attr_id) : $goods_attr_id;
                $res = $res->whereIn('goods_attr_id', $goods_attr_id);
            }

            $res = $res->with([
                'getGoodsAttribute' => function ($query) {
                    $query->select('attr_id', 'attr_name', 'sort_order');
                }
            ]);

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $key => $row) {
                    $attribute = $row['get_goods_attribute'];

                    $res[$key]['sort_order'] = $attribute['sort_order'];
                    $res[$key]['attr_id'] = $attribute['attr_id'];
                    $res[$key]['attr_name'] = $attribute['attr_name'];
                }

                $res = $this->baseRepository->getSortBy($res, 'sort_order');

                $arr['attr_id'] = collect($res)->pluck('attr_id')->all();

                foreach ($res as $key => $row) {
                    $arr[$row['attr_id']][$row['goods_attr_id']] = $row;
                }
            }
        }

        return $arr;
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
     * 筛选获取分类/品牌/商品ID下的商品数量
     * @param array $filter
     * @param string $type
     */
    public function getFilterGoodsListCount($filter = ['goods_ids' => '', 'cat_ids' => '', 'brand_ids' => '', 'user_id' => 0, 'mer_ids' => ''], $size = 0)
    {
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1);

        //商品
        if (isset($filter['goods_ids']) && !empty($filter['goods_ids'])) {
            $goods_ids = !is_array($filter['goods_ids']) ? explode(",", $filter['goods_ids']) : $filter['goods_ids'];

            $res = $res->whereIn('goods_id', $goods_ids);
        }

        //分类
        if (isset($filter['cat_ids']) && !empty($filter['cat_ids'])) {
            $cat_list = explode(',', $filter['cat_ids']);

            $cat_ids = [];
            foreach ($cat_list as $key => $val) {
                $cat_ids[] = $val;

                $cat_keys = app(CategoryService::class)->getCatListChildren($val);

                $cat_ids = array_merge($cat_ids, $cat_keys);
            }

            $cat_ids = array_unique($cat_ids);

            $res = $res->whereIn('cat_id', $cat_ids);
        }

        //品牌
        if (isset($filter['brand_ids']) && !empty($filter['brand_ids'])) {
            $brand_ids = !is_array($filter['brand_ids']) ? explode(",", $filter['brand_ids']) : $filter['brand_ids'];

            $res = $res->whereIn('brand_id', $brand_ids);
        }

        if ($this->config['region_store_enabled']) {
            //卖场 卖场优惠活动 liu
            if (isset($filter['mer_ids']) && !empty($filter['mer_ids'])) {
                $mer_ids = !is_array($filter['mer_ids']) ? explode(",", $filter['mer_ids']) : $filter['mer_ids'];

                $res = $res->whereIn('user_id', $mer_ids);
            }
        } else {
            //商家
            if (isset($filter['user_id'])) {
                $res = $res->where('user_id', $filter['user_id']);
            }
        }

        if ($size > 0) {
            $res = $res->take($size);

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            $count = 0;
            if ($res) {
                $count = collect($res)->count();
            }
        } else {
            $count = $res->count();
        }

        return $count;
    }

    /**
     * 筛选获取分类/品牌/商品ID下的商品列表
     * @param array $filter
     * @param string $type
     */
    public function getFilterGoodsList($filter = ['goods_ids' => '', 'cat_ids' => '', 'brand_ids' => '', 'user_id' => 0, 'mer_ids' => ''], $type = '', $warehouse_id = 0, $area_id = 0, $area_city = 0, $size = 10, $page = 1, $sort = "sort_order", $order = "ASC")
    {
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1);

        //商品
        if (isset($filter['goods_ids']) && !empty($filter['goods_ids'])) {
            $goods_ids = !is_array($filter['goods_ids']) ? explode(",", $filter['goods_ids']) : $filter['goods_ids'];

            $res = $res->whereIn('goods_id', $goods_ids);
        }

        //分类
        if (isset($filter['cat_ids']) && !empty($filter['cat_ids'])) {
            $cat_list = explode(',', $filter['cat_ids']);

            $cat_ids = [];
            foreach ($cat_list as $key => $val) {
                $cat_ids[] = $val;

                $cat_keys = app(CategoryService::class)->getCatListChildren($val);

                $cat_ids = array_merge($cat_ids, $cat_keys);
            }

            $cat_ids = array_unique($cat_ids);

            $res = $res->whereIn('cat_id', $cat_ids);
        }

        //品牌
        if (isset($filter['brand_ids']) && !empty($filter['brand_ids'])) {
            $brand_ids = !is_array($filter['brand_ids']) ? explode(",", $filter['brand_ids']) : $filter['brand_ids'];

            $res = $res->whereIn('brand_id', $brand_ids);
        }

        if ($this->config['region_store_enabled']) {
            //卖场 卖场优惠活动 liu
            if (isset($filter['mer_ids']) && !empty($filter['mer_ids'])) {
                $mer_ids = !is_array($filter['mer_ids']) ? explode(",", $filter['mer_ids']) : $filter['mer_ids'];

                $res = $res->whereIn('user_id', $mer_ids);
            }
        } else {
            //商家
            if (isset($filter['user_id'])) {
                $res = $res->where('user_id', $filter['user_id']);
            }
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

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
            }
        ]);

        $res = $res->orderBy($sort, $order);

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        //处理
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

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $arr[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$row['goods_id']]['goods_video'] = $this->dscRepository->getImagePath($row['goods_video']);
                $arr[$row['goods_id']]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $arr[$row['goods_id']]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $arr[$row['goods_id']]['promote_price'] = ($promote_price) > 0 ? $this->dscRepository->getPriceFormat($promote_price) : '';
            }
        }

        //总数
        if ($type == 'goods') {
            $record_count = $this->getFilterGoodsListCount($filter, $size);
        } else {
            $record_count = $this->getFilterGoodsListCount($filter);
        }

        $page_count = $record_count > 0 ? ceil($record_count / $size) : 1;

        return ['goods_list' => $arr, 'page_count' => $page_count, 'record_count' => $record_count];
    }

    /**
     * 取商品的规格列表
     *
     * @param int $goods_id 商品id
     * @param string $conditions sql条件
     *
     * @return  array
     */
    public function getSpecificationsList($goods_id = 0)
    {
        /* 取商品属性 */

        $result = GoodsAttr::select(['goods_attr_id', 'attr_id', 'attr_value'])
            ->where('goods_id', $goods_id);

        $result = $result->whereHas('getGoodsAttribute');

        $result = $result->with([
            'getGoodsAttribute' => function ($query) {
                $query->select('attr_id', 'attr_name');
            }
        ]);

        $result = $this->baseRepository->getToArrayGet($result);

        $return_array = [];
        if ($result) {
            foreach ($result as $value) {
                if ($value['get_goods_attribute']) {
                    $value = array_merge($value, $value['get_goods_attribute']);
                }

                $return_array[$value['goods_attr_id']] = $value;
            }
        }

        return $return_array;
    }

    /**
     * 获取商品属性列表
     *
     * @access  public
     * @param array $cat
     *
     * @return array
     */
    public function getGoodsAttrList($specs = [])
    {
        if (empty($specs)) {
            return '';
        }

        $specs = !is_array($specs) ? explode(",", $specs) : $specs;

        $attr_list = [];
        $goods_attr = [];
        $res = GoodsAttr::select('a.attr_name', 'g.attr_value')
            ->from('goods_attr as g')
            ->join('attribute as a', 'g.attr_id', 'a.attr_id')
            ->whereIn('g.goods_attr_id', $specs)
            ->orderBy('a.sort_order')
            ->orderBy('a.attr_id')
            ->orderBy('g.goods_attr_id')
            ->get();
        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $row) {
                $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
            }
            $goods_attr = join(chr(13) . chr(10), $attr_list);
        }

        return $goods_attr;
    }

    /**
     * 查看是否秒杀
     *
     * @param int $goods_id
     * @return int
     */
    public function get_is_seckill($goods_id = 0)
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
     * 验证属性是多选，单选
     * @param $goods_attr_id
     * @return mixed
     */
    public function getGoodsAttrType($goods_attr_id = 0)
    {
        $attr_type = Attribute::wherehas('getGoodsAttr', function ($query) use ($goods_attr_id) {
            $query->where('goods_attr_id', $goods_attr_id);
        })->value('attr_type');

        $attr_type = $attr_type ? $attr_type : 0;

        return $attr_type;
    }

    /**
     * 验证是否关联地区
     *
     * @param $goods_id
     * @param int $area_id
     * @param int $area_city
     * @return mixed
     */
    public function getHasLinkAreaGods($goods_id, $area_id = 0, $area_city = 0)
    {
        $res = Goods::where('goods_id', $goods_id);
        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);
        $count = $res->count();

        return $count;
    }

    /**
     * 获取店铺二维码
     * @param int $ru_id
     * @return string
     */
    public function getShopQrcode($ru_id = 0)
    {
        // 二维码内容
        $url = dsc_url('/#/shopHome/' . $ru_id);

        // 生成的文件位置
        $path = storage_public('data/attached/shophome_qrcode/');

        // 输出二维码路径
        $out_img = $path . 'shop_qrcode_' . $ru_id . '.png';

        if (!is_dir($path)) {
            @mkdir($path, 0777);
        }

        // 生成二维码条件
        $generate = false;
        if (file_exists($out_img)) {
            $lastmtime = filemtime($out_img) + 3600 * 24 * 30; // 30天有效期之后重新生成
            if (time() >= $lastmtime) {
                $generate = true;
            }
        }

        if (!file_exists($out_img) || $generate == true) {
            $qrCode = new QrCode($url);

            $qrCode->setSize(357);
            $qrCode->setMargin(15);

            $qrCode->writeFile($out_img); // 保存二维码
        }

        $image_name = 'data/attached/shophome_qrcode/' . basename($out_img);

        $this->dscRepository->getOssAddFile([$image_name]);

        return $this->dscRepository->getImagePath($image_name);

    }

    /**
     * 生成商品二维码
     * @param array $goods
     * @return array
     */
    public function getGoodsQrcode($goods = [])
    {
        if (empty($goods)) {
            return [];
        }

        // 二维码内容
        $two_code_links = trim($this->config['two_code_links']);
        $two_code_links = empty($two_code_links) ? url('/') . '/' : $two_code_links;
        $url = rtrim($two_code_links, '/') . '/goods.php?id=' . $goods['goods_id'];

        // 保存二维码目录
        $file_path = storage_public('images/weixin_img/');
        if (!file_exists($file_path)) {
            make_dir($file_path);
        }
        // logo目录
        $logo_file = storage_public('images/weixin_img/logo/');
        if (!file_exists($logo_file)) {
            make_dir($logo_file);
        }
        // 输出logo
        $logo = $logo_file . 'logo_' . $goods['goods_id'] . '.png';
        // 输出图片
        $out_img = $file_path . 'weixin_code_' . $goods['goods_id'] . '.png';

        // 生成二维码条件
        $generate = false;
        if (file_exists($out_img)) {
            $lastmtime = filemtime($out_img) + 3600 * 24 * 1; // 1天有效期之后重新生成
            if (time() >= $lastmtime) {
                $generate = true;
            }
        }

        if (!file_exists($out_img) || $generate == true) {
            // 生成二维码
            $qrCode = new QrCode($url);

            $qrCode->setSize(150);
            $qrCode->setMargin(15);
            $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel('quartile'));

            /**
             * 生成二维码+logo
             */
            // 优先用商品缩略图
            $goods_img = !empty($goods['goods_thumb']) ? $goods['goods_thumb'] : $goods['goods_img'];
            $two_code_logo = trim($this->config['two_code_logo']);
            $logo_picture = empty($two_code_logo) ? $this->dscRepository->getImagePath($goods_img) : str_replace('../', '', $two_code_logo);
            if (!empty($logo_picture)) {
                // 远程图片（非本站）
                if (strtolower(substr($logo_picture, 0, 4)) == 'http' && stripos($logo_picture, asset('/')) === false) {
                    $logo_picture = Http::doGet($logo_picture);
                    $avatar_open = $logo;
                    file_put_contents($avatar_open, $logo_picture);
                } else {
                    // 本站图片 带http 或 不带http
                    if (strtolower(substr($logo_picture, 0, 4)) == 'http') {
                        $logo_picture = str_replace(storage_url('/'), '', $logo_picture);
                    }
                    // 默认图片
                    if (stripos($logo_picture, 'no_image') !== false) {
                        $avatar_open = $logo_picture;
                    } else {
                        $avatar_open = storage_public($logo_picture);
                    }
                }
                if (file_exists($avatar_open)) {
                    Image::open($avatar_open)->thumb(36, 36, Image::THUMB_FILLED)->save($logo);
                }
            }

            $linkExists = $this->dscRepository->remoteLinkExists($logo);
            if ($linkExists) {
                $qrCode->setLogoPath($logo);
                $qrCode->setLogoWidth(36); // 默认居中
            }

            $qrCode->writeFile($out_img); // 保存二维码
        }

        $image_name = 'images/weixin_img/' . basename($out_img);

        // 同步镜像上传到OSS
        $this->dscRepository->getOssAddFile([$image_name]);

        return [
            'url' => $this->dscRepository->getImagePath($image_name) . '?v=' . Str::random(32)
        ];
    }

    /**
     * 查询商品库存
     *
     * @param int $goods_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param array $select
     * @return mixed
     */
    public function getGoodsStock($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $select = [])
    {
        if ($select) {
            array_push($select, 'model_attr', 'goods_number');
            $select = $this->baseRepository->getExplode($select);
            $res = Goods::select($select)->where('goods_id', $goods_id);
        } else {
            $res = Goods::where('goods_id', $goods_id);
        }

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];

        $res = $res->with([
            'getWarehouseGoods' => function ($query) use ($where) {
                $query->where('region_id', $where['warehouse_id']);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($this->config['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);

        if ($res) {
            if ($res['model_attr'] == 1) {
                $goods_number = $res['get_warehouse_goods']['region_number'] ?? 0;
            } elseif ($res['model_attr'] == 2) {
                $goods_number = $res['get_warehouse_area_goods']['region_number'] ?? 0;
            } else {
                $goods_number = $res['goods_number'];
            }

            $res['goods_number'] = $goods_number;
        }

        return $res;
    }

}
