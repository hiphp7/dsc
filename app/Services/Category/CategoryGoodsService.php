<?php

namespace App\Services\Category;

use App\Models\Comment;
use App\Models\Coupons;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsCat;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\CouponsService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;

class CategoryGoodsService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $goodsCommonService;
    protected $config;
    protected $userCommonService;
    protected $merchantCommonService;
    protected $goodsGalleryService;
    protected $goodsWarehouseService;
    protected $city = 0;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        UserCommonService $userCommonService,
        MerchantCommonService $merchantCommonService,
        GoodsGalleryService $goodsGalleryService,
        GoodsWarehouseService $goodsWarehouseService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->userCommonService = $userCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->goodsWarehouseService = $goodsWarehouseService;

        /* 获取地区缓存 */
        $area_cookie = app(AreaService::class)->areaCookie();
        $this->city = $area_cookie['city'];

        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得指定分类下的推荐商品
     *
     * @param string $cats 分类的ID
     * @param string $type 推荐类型，可以是 best, new, hot, promote
     * @param int $brand_id 品牌的ID
     * @param int $warehouse_id 仓库ID
     * @param int $area_id 仓库地区ID
     * @param int $area_city 仓库地区城市ID
     * @param array $where_ext
     * @param int $min 最小金额
     * @param int $max 最大金额
     * @param int $num 查询条数
     * @param int $start 起始查询条数
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getCategoryRecommendGoods($cats = '', $type = '', $brand_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $where_ext = [], $min = 0, $max = 0, $num = 10, $start = 0)
    {
        $cats_cache = $cats && is_array($cats) ? implode($cats) : $cats;
        $where_ext_cache = is_array($where_ext) ? implode($where_ext) : $where_ext;

        $cache_name = "get_category_recommend_goods_" . '_' . $cats_cache . '_' . $type . '_' . $brand_id . '_' . $warehouse_id .
            '_' . $area_id . '_' . $area_city . '_' . $where_ext_cache . '_' . $min . '_' . $max . '_' . $num . '_' . $start;

        $goods = cache($cache_name);
        $goods = !is_null($goods) ? $goods : false;

        if ($goods === false) {
            $cats = is_array($cats) ? $cats : explode(',', $cats);
            /* 查询扩展分类数据 */
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $cats);
            $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
            $extension_goods = $this->baseRepository->getFlatten($extension_goods);

            $goodsParam = [
                'children' => $cats,
                'extension_goods' => $extension_goods
            ];

            /* 查询分类商品数据 */
            $res = Goods::where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0)
                ->where('is_show', 1)
                ->where(function ($query) use ($goodsParam) {
                    $query = $query->whereIn('cat_id', $goodsParam['children']);
                    if ($goodsParam['extension_goods']) {
                        $query->orWhere(function ($query) use ($goodsParam) {
                            $query->whereIn('goods_id', $goodsParam['extension_goods']);
                        });
                    }
                });

            if ($brand_id) {
                $brand_id = !is_array($brand_id) ? explode(",", $brand_id) : $brand_id;
                $res = $res->whereIn('brand_id', $brand_id);
            }

            if ($where_ext) {

                /* 查询仅自营和标识自营店铺的商品 */
                if ($where_ext['self'] == 1) {
                    $res = $res->where(function ($query) {
                        $query->where('user_id', 0)->orWhere(function ($query) {
                            $query->whereHas('getShopInfo', function ($query) {
                                $query->where('self_run', 1);
                            });
                        });
                    });
                }

                if ($where_ext['have'] == 1) {
                    $res = $res->where('goods_number', '>', 0);
                }

                if ($where_ext['ship'] == 1) {
                    $res = $res->where('is_shipping', 1);
                }
            }

            if ($min > 0) {
                $res = $res->where('shop_price', '>=', $min);
            }

            if ($max > 0) {
                $res = $res->where('shop_price', '<=', $max);
            }

            $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

            switch ($type) {
                case 'best':
                    $res = $res->where('is_best', 1);
                    break;
                case 'new':
                    $res = $res->where('is_new', 1);
                    break;
                case 'hot':
                    $res = $res->where('is_hot', 1);
                    break;
                case 'promote':
                    $time = $this->timeRepository->getGmTime();
                    $res = $res->where('is_promote', 1)
                        ->where('promote_start_date', '<=', $time)
                        ->where('promote_end_date', '>=', $time);
                    break;
                //随机by wu
                case 'rand':
                    $res = $res->where('is_best', 1);
                    break;
            }

            if ($this->config['review_goods']) {
                $res = $res->where('review_status', '>', 2);
            }

            /* 关联地区 */
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
                'getBrand'
            ]);

            $order_type = $this->config['recommend_order'];
            if ($type == 'rand') {
                $order_type = 1;
            }

            //随机
            if ($order_type == 1) {
                $res = $res->orderByRaw('RAND()');
            } else {
                $res = $res->orderByRaw('sort_order, last_update desc');
            }

            if ($start > 0) {
                $res = $res->skip($start);
            }

            if ($num > 0) {
                $res = $res->take($num);
            }

            $res = $this->baseRepository->getToArrayGet($res);

            $idx = 0;
            $goods = [];
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

                    $goods[$idx] = $row;

                    if ($row['promote_price'] > 0) {
                        $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    } else {
                        $promote_price = 0;
                    }

                    $goods[$idx]['id'] = $row['goods_id'];
                    $goods[$idx]['comments_number'] = $row['comments_number'];
                    $goods[$idx]['sales_volume'] = $row['sales_volume'];
                    $goods[$idx]['name'] = $row['goods_name'];
                    $goods[$idx]['brief'] = $row['goods_brief'];
                    $goods[$idx]['brand_name'] = $row['get_brand']['brand_name'];
                    $goods[$idx]['short_name'] = $this->config['goods_name_length'] > 0 ?
                        $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                    $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                    $goods[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                    $goods[$idx]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                    $goods[$idx]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                    $goods[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                    $goods[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

                    $goods[$idx]['short_style_name'] = $this->goodsCommonService->addStyle($goods[$idx]['short_name'], $row['goods_name_style']);
                    $idx++;
                }
            }

            cache()->forever($cache_name, $goods);
        }

        return $goods;
    }

    /**
     * 获得对比商品
     *
     * @param $goods_ids
     * @param string $compare
     * @param string $highlight
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getCatCompare($goods_ids, $compare = '', $highlight = '', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $goods_ids = $this->baseRepository->getExplode($goods_ids);

        $cmtres = Comment::selectRaw('id_value , AVG(comment_rank) AS cmt_rank, COUNT(*) AS cmt_count');
        $cmtres = $cmtres->whereIn('id_value', $goods_ids)
            ->where('comment_type', 0);
        $cmtres = $cmtres->groupBy('id_value');

        $cmtres = $this->baseRepository->getToArrayGet($cmtres);

        $cmt = [];
        if ($cmtres) {
            foreach ($cmtres as $row) {
                $cmt[$row['id_value']] = $row;
            }
        }

        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->whereIn('goods_id', $goods_ids);

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

            'getGoodsAttrList' => function ($query) {
                $query->orderBy('goods_attr_id');
            },
            'getBrand'
        ]);

        $res = $res->orderBy('goods_id');

        $res = $this->baseRepository->getToArrayGet($res);

        $type_id = 0;
        $basic_arr = [];
        $goods_list = [];
        if ($res) {
            foreach ($res as $row) {
                $brand = $row['get_brand'];
                $goods_attr = $row['get_goods_attr_list'];

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

                $goods_id = $row['goods_id'];

                $goods_list[$goods_id] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $type_id = $row['goods_type'];
                $goods_list[$goods_id]['goods_id'] = $goods_id;
                $goods_list[$goods_id]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $goods_list[$goods_id]['goods_name'] = $row['goods_name'];
                $goods_list[$goods_id]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $goods_list[$goods_id]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $goods_list[$goods_id]['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                    ceil($row['goods_weight']) . $GLOBALS['_LANG']['kilogram'] : ceil($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];
                $goods_list[$goods_id]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $goods_list[$goods_id]['goods_brief'] = $row['goods_brief'];
                $goods_list[$goods_id]['brand_name'] = $brand['brand_name'];

                $tmp = $goods_ids;
                $key = array_search($goods_id, $tmp);

                if ($key !== null && $key !== false) {
                    unset($tmp[$key]);
                }

                $goods_list[$goods_id]['ids'] = !empty($tmp) ? "goods[]=" . implode('&amp;goods[]=', $tmp) : '';

                if ($goods_attr) {
                    foreach ($goods_attr as $gakey => $garow) {
                        $basic_arr[$goods_id]['properties'][$garow['attr_id']]['name'] = $garow['get_goods_attribute']['attr_name'];
                        $basic_arr[$goods_id]['properties'][$garow['attr_id']]['value'][$gakey] = $garow['attr_value'];
                    }
                }

                if ($cmt && !isset($basic_arr[$goods_id]['comment_rank'])) {
                    $basic_arr[$goods_id]['comment_rank'] = isset($cmt[$goods_id]) ? ceil($cmt[$goods_id]['cmt_rank']) : 0;
                    $basic_arr[$goods_id]['comment_number'] = isset($cmt[$goods_id]) ? $cmt[$goods_id]['cmt_count'] : 0;
                    $basic_arr[$goods_id]['comment_number'] = sprintf($GLOBALS['_LANG']['comment_num'], $basic_arr[$goods_id]['comment_number']);
                }
            }
        }

        if ($basic_arr) {
            foreach ($basic_arr as $key => $val) {
                foreach ($basic_arr[$key]['properties'] as $k => $v) {
                    $basic_arr[$key]['properties'][$k]['value'] = implode(",", $v['value']);
                }
            }
        }

        $res = [
            'goods_list' => $goods_list,
            'basic_arr' => $basic_arr,
            'type_id' => $type_id
        ];

        return $res;
    }

    /**
     * 获得分类下的商品
     *
     * @param int $uid
     * @param array $keywords
     * @param string $children
     * @param int $brand_id
     * @param int $price_min
     * @param int $price_max
     * @param array $filter_attr
     * @param array $where_ext
     * @param int $goods_num
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @return array
     * @throws \Exception
     */
    public function getMobileCategoryGoodsList($uid = 0, $keywords = [], $children = '', $brand_id = 0, $price_min = 0, $price_max = 0, $filter_attr = [], $where_ext = [], $goods_num = 0, $size = 10, $page = 1, $sort = 'goods_id', $order = 'DESC')
    {
        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_show', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        if ($keywords) {
            $keywordsParam = [
                'keywords' => $keywords,
                'sc_ds' => $where_ext['sc_ds'] ?? '',
                'time' => $this->timeRepository->getGmTime()
            ];
            $res = $res->where(function ($query) use ($keywordsParam) {
                foreach ($keywordsParam['keywords'] as $key => $val) {
                    if ($val) {
                        $query->where(function ($query) use ($val) {
                            $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');
                            $query = $query->orWhere('goods_sn', 'like', '%' . $val . '%');
                            $query = $query->orWhere('keywords', 'like', '%' . $val . '%');
                            if (!empty($keywordsParam['sc_ds'])) {
                                $query->orWhere('goods_desc', 'like', '%' . $val . '%');
                            }
                        });
                    }
                }
            });

            //兼容预售
            $res = $res->orWhere(function ($query) use ($keywordsParam) {
                $query->whereHas('getPresaleActivity', function ($query) use ($keywordsParam) {
                    $query->where(function ($query) use ($keywordsParam) {
                        foreach ($keywordsParam['keywords'] as $key => $val) {
                            $query->where('start_time' ,'<' ,$keywordsParam['time'])->where('end_time', '>', $keywordsParam['time']);
                            $query->where(function ($query) use ($val) {
                                $val = $this->dscRepository->mysqlLikeQuote(trim($val));
                                $query->orWhere('goods_name', 'like', '%' . $val . '%');
                            });
                        }
                    });
                });
            });
        } else {
            /* 查询扩展分类数据 */
            $extension_goods = [];
            if ($children) {
                $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children);
                $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
                $extension_goods = $this->baseRepository->getFlatten($extension_goods);
            }

            $goodsParam = [
                'children' => $children,
                'extension_goods' => $extension_goods
            ];

            // 子分类 或 扩展分类
            $res = $res->where(function ($query) use ($goodsParam) {
                if ($goodsParam['children']) {
                    $query = $query->whereIn('cat_id', $goodsParam['children']);
                }
                if ($goodsParam['extension_goods']) {
                    $query->orWhere(function ($query) use ($goodsParam) {
                        $query->whereIn('goods_id', $goodsParam['extension_goods']);
                    });
                }
            });
        }

        if ($brand_id) {
            $brand_id = $this->baseRepository->getExplode($brand_id);
            $res = $res->whereIn('brand_id', $brand_id);
        }

        //仅看有货
        if ($goods_num > 0) {
            $res = $res->where('goods_number', '>', 0);
        }

        $ru_id = $where_ext['ru_id'] ?? 0;
        if ($ru_id > 0) {
            $res = $res->where('user_id', $ru_id);
        }

        if ($price_min > 0) {
            $res = $res->where('shop_price', '>=', $price_min);
        }

        if ($price_max > 0) {
            $res = $res->where('shop_price', '<=', $price_max);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        if (!empty($filter_attr)) {
            $goodsList = GoodsAttr::whereIn('goods_attr_id', $filter_attr)->pluck('goods_id');
            $goodsList = $goodsList ? $goodsList->toArray() : [];
            $goodsList = $goodsList ? array_unique($goodsList) : [];

            if ($goodsList) {
                $res = $res->whereIn('goods_id', $goodsList);
            }
        }

        /* 查询仅自营和标识自营店铺的商品 */
        if (isset($where_ext['self']) && $where_ext['self'] == 1) {
            $res = $res->where(function ($query) {
                $query->where('user_id', 0)
                    ->orWhere(function ($query) {
                        $query->whereHas('getShopInfo', function ($query) {
                            $query->where('self_run', 1);
                        });
                    });
            });
        }

        // 是否免邮
        if (isset($where_ext['ship']) && $where_ext['ship'] == 1) {
            $res = $res->where('is_shipping', 1);
        }

        $where = [
            'warehouse_id' => $where_ext['warehouse_id'] ?? 0,
            'area_id' => $where_ext['area_id'] ?? 0,
            'area_city' => $where_ext['area_city'] ?? 0,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        /* 关联地区 */
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
            'getShopInfo',
            'getPresaleActivity'
        ]);

        $intro = $where_ext['intro'] ?? '';

        $promotion = $where_ext['promotion'] ?? 0;
        if ($promotion) {
            $intro = 'promote';
        }

        if ($intro == 'hot') {
            $res = $res->where('is_hot', 1);
        } elseif ($intro == 'new') {
            $res = $res->where('is_new', 1);
        } elseif ($intro == 'best') {
            $res = $res->where('is_best', 1);
        } elseif ($intro == 'promote') {
            $time = $this->timeRepository->getGmTime();
            $res = $res->where('is_promote', 1)
                ->where('promote_price', '>', 0)
                ->where('promote_start_date', '<=', $time)
                ->where('promote_end_date', '>=', $time);
        }

        // 优惠券商品条件
        $cou_id = $where_ext['cou_id'] ?? 0;
        if ($children == 0 && $cou_id > 0) {
            $cou_data = Coupons::where('cou_id', $cou_id);
            $cou_data = $this->baseRepository->getToArrayFirst($cou_data);

            if ($cou_data) {
                $res = $res->where('user_id', $cou_data['ru_id']);
                if ($cou_data['cou_goods']) {
                    $cou_data['cou_goods'] = $this->baseRepository->getExplode($cou_data['cou_goods']);
                    $res = $res->whereIn('goods_id', $cou_data['cou_goods']);
                } elseif ($cou_data['spec_cat']) {
                    $cou_children = app(CouponsService::class)->getCouChildren($cou_data['spec_cat']);
                    $cou_children = $cou_children ? explode(",", $cou_children) : [];
                    if ($cou_children) {
                        $res = $res->whereIn('cat_id', $cou_children);
                    }
                }
            }
        }

        if (strpos($sort, 'goods_id') !== false) {
            $sort = "sort_order";
            $res = $res->orderBy($sort, $order)->orderBy('goods_id', $order);
        } else {
            $res = $res->orderBy($sort, $order);
        }

        $start = ($page - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

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

                //兼容预售
                $presale_is_finished = $row['get_presale_activity']['is_finished'] ?? 0;
                $arr[$k]['presale_id'] = $row['get_presale_activity']['act_id'] ?? 0;
                $arr[$k]['on_presale_activity'] = ($arr[$k]['presale_id'] > 0 && $presale_is_finished == 0) ? 1 : 0;

                $arr[$k]['model_price'] = $row['model_price'];

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                /* 处理商品水印图片 */
                $watermark_img = '';
                if ($promote_price > 0) {
                    $watermark_img = "watermark_promote_small";
                } elseif ($row['is_new'] != 0) {
                    $watermark_img = "watermark_new_small";
                } elseif ($row['is_best'] != 0) {
                    $watermark_img = "watermark_best_small";
                } elseif ($row['is_hot'] != 0) {
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
                $arr[$k]['promote_start_date'] = $row['promote_start_date'];
                $arr[$k]['promote_end_date'] = $row['promote_end_date'];

                $arr[$k]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$k]['shop_price'] = $row['shop_price'];
                if ($promote_price > 0) {
                    $arr[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['promote_price']);
                } else {
                    $arr[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                }
                $arr[$k]['type'] = $row['goods_type'];
                $arr[$k]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($row['promote_price']) : '';
                $arr[$k]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$k]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $arr[$k]['original_img'] = $this->dscRepository->getImagePath($row['original_img']);
                $arr[$k]['is_hot'] = $row['is_hot'];
                $arr[$k]['is_best'] = $row['is_best'];
                $arr[$k]['is_new'] = $row['is_new'];

                $arr[$k]['self_run'] = isset($row['get_shop_info']) ? $row['get_shop_info']['self_run'] : 0;

                $arr[$k]['is_shipping'] = $row['is_shipping'];

                $arr[$k]['goods_number'] = $row['goods_number'];

                $arr[$k]['rz_shopName'] = $this->merchantCommonService->getShopName($row['user_id'], 1);
                $arr[$k]['user_id'] = $row['user_id'];

                $arr[$k]['url'] = dsc_url('/#/goods/' . $row['goods_id']);
                $arr[$k]['app_page'] = config('route.goods.detail') . $row['goods_id'];
                $arr[$k]['sale'] = $row['sales_volume'];
            }
        }

        return $arr;
    }

    /**
     * 获得分类下的商品总数
     *
     * @param array $children
     * @param int $brand_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $min
     * @param int $max
     * @param array $cat_filter_attr
     * @param array $filter_attr
     * @param array $where_ext
     * @return mixed
     */
    public function getCagtegoryGoodsCount($children = [], $brand_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $min = 0, $max = 0, $cat_filter_attr = [], $filter_attr = [], $where_ext = [])
    {
        /* 查询扩展分类数据 */
        $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children);
        $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
        $extension_goods = $this->baseRepository->getFlatten($extension_goods);

        $goodsParam = [
            'children' => $children,
            'extension_goods' => $extension_goods
        ];

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->where(function ($query) use ($goodsParam) {
                $query = $query->whereIn('cat_id', $goodsParam['children']);
                if ($goodsParam['extension_goods']) {
                    $query->orWhere(function ($query) use ($goodsParam) {
                        $query->whereIn('goods_id', $goodsParam['extension_goods']);
                    });
                }
            });

        if ($brand_id) {
            $brand_id = !is_array($brand_id) ? explode(",", $brand_id) : $brand_id;
            $res = $res->whereIn('brand_id', $brand_id);
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        if (!empty($filter_attr)) {
            $list = [];
            foreach ($filter_attr as $k => $v) {
                $attr_value = GoodsAttr::where('goods_attr_id', $v)->value('attr_value');

                $cat_filter_attr[$k] = $cat_filter_attr[$k] ?? 0;

                $goodsList = GoodsAttr::select('goods_id')
                    ->where('attr_id', $cat_filter_attr[$k])
                    ->where('attr_value', $attr_value);
                $goodsList = $this->baseRepository->getToArrayGet($goodsList);
                $list[$k] = $this->baseRepository->getKeyPluck($goodsList, 'goods_id');
            }

            $goodsList = $this->baseRepository->getFlatten($list);

            if ($goodsList) {
                $res = $res->whereIn('goods_id', $goodsList);
            }
        }

        /* 查询仅自营和标识自营店铺的商品 */
        if ($where_ext['self'] == 1) {
            $res = $res->where(function ($query) {
                $query->where('user_id', 0)->orWhere(function ($query) {
                    $query->whereHas('getShopInfo', function ($query) {
                        $query->where('self_run', 1);
                    });
                });
            });
        }

        if ($where_ext['have'] == 1) {
            $res = $res->where('goods_number', '>', 0);
        }

        if ($where_ext['ship'] == 1) {
            $res = $res->where('is_shipping', 1);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        /* 关联地区显示商品 */
        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $res = $res->count();

        /* 返回商品总数 */
        return $res;
    }

    /**
     * 获得分类下的商品
     *
     * @param array $children
     * @param int $brand_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $min
     * @param int $max
     * @param array $cat_filter_attr
     * @param array $filter_attr
     * @param array $where_ext
     * @param int $goods_num
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @return array
     * @throws \Exception
     */
    public function getCategoryGoodsList($children = [], $brand_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $min = 0, $max = 0, $cat_filter_attr = [], $filter_attr = [], $where_ext = [], $goods_num = 0, $size = 10, $page = 1, $sort = 'goods_id', $order = 'DESC')
    {
        /* 查询扩展分类数据 */
        $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children);
        $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
        $extension_goods = $this->baseRepository->getFlatten($extension_goods);

        $goodsParam = [
            'children' => $children,
            'extension_goods' => $extension_goods
        ];

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->where(function ($query) use ($goodsParam) {
                $query = $query->whereIn('cat_id', $goodsParam['children']);
                if ($goodsParam['extension_goods']) {
                    $query->orWhere(function ($query) use ($goodsParam) {
                        $query->whereIn('goods_id', $goodsParam['extension_goods']);
                    });
                }
            });

        if ($brand_id) {
            $brand_id = $this->baseRepository->getExplode($brand_id);
            $res = $res->whereIn('brand_id', $brand_id);
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        if (!empty($filter_attr)) {
            $list = [];
            foreach ($filter_attr as $k => $v) {
                if (!empty($v)) {
                    $attr_value = GoodsAttr::where('goods_attr_id', $v)->value('attr_value');

                    $cat_filter_attr[$k] = $cat_filter_attr[$k] ?? 0;

                    $goodsList = GoodsAttr::select('goods_id')
                        ->where('attr_id', $cat_filter_attr[$k])
                        ->where('attr_value', $attr_value);
                    $goodsList = $this->baseRepository->getToArrayGet($goodsList);
                    $list[$k] = $this->baseRepository->getKeyPluck($goodsList, 'goods_id');
                }
            }

            $goodsList = $this->baseRepository->getFlatten($list);

            if ($goodsList) {
                $res = $res->whereIn('goods_id', $goodsList);
            }
        }

        /* 查询仅自营和标识自营店铺的商品 */
        if ($where_ext['self'] == 1) {
            $res = $res->where(function ($query) {
                $query->where('user_id', 0)->orWhere(function ($query) {
                    $query->whereHas('getShopInfo', function ($query) {
                        $query->where('self_run', 1);
                    });
                });
            });
        }

        if ($where_ext['have'] == 1) {
            $res = $res->where('goods_number', '>', 0);
        }

        if ($where_ext['ship'] == 1) {
            $res = $res->where('is_shipping', 1);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        /* 关联地区显示商品 */
        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'warehouse_id' => $warehouse_id,
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
            'getShopInfo',
            'getProductsWarehouse' => function ($query) use ($warehouse_id) {
                $query->where('warehouse_id', $warehouse_id);
            },
            'getProductsArea' => function ($query) use ($where) {
                $query = $query->where('area_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
            'getProducts',
            'getSellerShopInfo'
        ]);

        $res = $res->withCount([
            'getComment as review_count' => function ($query) {
                $query->where('status', 1)
                    ->where('parent_id', 0)
                    ->whereIn('comment_rank', [1, 2, 3, 4, 5]);
            },
            'getCollectGoods as is_collect'
        ]);

        //瀑布流加载分类商品 by wu
        if ($goods_num) {
            $start = $goods_num;
        } else {
            $start = ($page - 1) * $size;
        }

        if (strpos($sort, 'goods_id') !== false) {
            $sort = "sort_order";
            $res = $res->orderBy($sort, $order)->orderBy('goods_id', $order);
        } else {
            $res = $res->orderBy($sort, $order);
        }

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

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

                if ($promote_price > 0) {
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

                /* 商品仓库货品 */
                if ($row['model_price'] == 1) {
                    $prod = $row['get_products_warehouse'] ?? [];
                } elseif ($row['model_price'] == 2) {
                    $prod = $row['get_products_area'] ?? [];
                } else {
                    $prod = $row['get_products'] ?? [];
                }

                if (empty($prod)) { //当商品没有属性库存时
                    $arr[$row['goods_id']]['prod'] = 1;
                } else {
                    $arr[$row['goods_id']]['prod'] = 0;
                }

                $arr[$row['goods_id']]['goods_number'] = $row['goods_number'];

                $basic_info = $row['get_seller_shop_info'] ?? [];

                $chat = $this->dscRepository->chatQq($basic_info);

                $arr[$row['goods_id']]['kf_type'] = $chat['kf_type'];
                $arr[$row['goods_id']]['kf_ww'] = $chat['kf_ww'];
                $arr[$row['goods_id']]['kf_qq'] = $chat['kf_qq'];

                /* 评分数 */
                $arr[$row['goods_id']]['review_count'] = $row['review_count'];

                $arr[$row['goods_id']]['pictures'] = $this->goodsGalleryService->getGoodsGallery($row['goods_id'], 6); // 商品相册

                if ($this->config['customer_service'] == 0) {
                    $seller_id = 0;
                } else {
                    $seller_id = $row['user_id'];
                }

                $shop_information = $this->merchantCommonService->getShopName($seller_id); //通过ru_id获取到店铺信息;
                $shop_information = $shop_information ? $shop_information : [];

                $arr[$row['goods_id']]['rz_shopName'] = isset($shop_information['shop_name']) ? $shop_information['shop_name'] : ''; //店铺名称
                $arr[$row['goods_id']]['user_id'] = $row['user_id'];

                $build_uri = [
                    'urid' => $row['user_id'],
                    'append' => $arr[$row['goods_id']]['rz_shopName']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                $arr[$row['goods_id']]['store_url'] = $domain_url['domain_name'];

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
     * 获得当前分类下商品价格的最大值、最小值
     *
     * @param array $children
     * @param int $brand_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param array $where_ext
     * @param array $goods_ids
     * @param array $keywords
     * @param string $sc_ds
     * @param string $cat_type
     * @return array
     */
    public function getGoodsPriceMaxMin($children = [], $brand_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $where_ext = [], $goods_ids = [], $keywords = [], $sc_ds = '', $cat_type = 'cat_id')
    {
        /* 查询扩展分类数据 */
        if ($cat_type == 'cat_id') {
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children);
            $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
            $extension_goods = $this->baseRepository->getFlatten($extension_goods);
        } else {
            $extension_goods = [];
        }

        $goodsParam = [
            'children' => $children,
            'extension_goods' => $extension_goods,
            'goods_ids' => $goods_ids,
            'cat_type' => $cat_type
        ];

        /* 查询分类商品数据 */
        $res = Goods::select('shop_price', 'model_price')->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where(function ($query) use ($goodsParam) {
                if ($goodsParam['children']) {
                    $query = $query->whereIn($goodsParam['cat_type'], $goodsParam['children']);
                }

                if ($goodsParam['extension_goods']) {
                    $query->orWhere(function ($query) use ($goodsParam) {
                        $query = $query->whereIn('goods_id', $goodsParam['extension_goods']);

                        if ($goodsParam['goods_ids']) {
                            $query->orWhereIn('goods_id', $goodsParam['goods_ids']);
                        }
                    });
                }
            });

        if ($keywords) {
            $keywordsParam = [
                'keywords' => $keywords,
                'sc_ds' => $sc_ds
            ];

            $res = $res->where(function ($query) use ($keywordsParam) {
                foreach ($keywordsParam['keywords'] as $key => $val) {
                    $query->where(function ($query) use ($val) {
                        $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');

                        $query = $query->orWhere('goods_sn', 'like', '%' . $val . '%');

                        $query = $query->orWhere('keywords', 'like', '%' . $val . '%');

                        if (!empty($keywordsParam['sc_ds'])) {
                            $query = $query->orWhere('goods_desc', 'like', '%' . $val . '%');
                        }
                    });

                    /*$query->orWhere(function ($query) use ($val) {
                        $query->whereHas('getPresaleActivity', function ($query) use ($val) {
                            $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');
                        });
                    });*/
                }
            });
        }

        if ($brand_id) {
            $brand_id = !is_array($brand_id) ? explode(",", $brand_id) : $brand_id;
            $res = $res->whereIn('brand_id', $brand_id);
        }

        $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

        /* 查询仅自营和标识自营店铺的商品 */
        if (isset($where_ext['self']) && $where_ext['self'] == 1) {
            $res = $res->where(function ($query) {
                $query->where('user_id', 0)->orWhere(function ($query) {
                    $query->whereHas('getShopInfo', function ($query) {
                        $query->where('self_run', 1);
                    });
                });
            });
        }

        if (isset($where_ext['have']) && $where_ext['have'] == 1) {
            $res = $res->where('goods_number', '>', 0);
        }

        if (isset($where_ext['ship']) && $where_ext['ship'] == 1) {
            $res = $res->where('is_shipping', 1);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        /* 关联地区 */
        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $res = $res->with(['getWarehouseGoods' => function ($query) use ($warehouse_id) {
            $query->where('region_id', $warehouse_id);
        },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                if ($val['model_price'] == 1) {
                    $res[$key]['shop_price'] = $val['get_warehouse_goods'] ? $val['get_warehouse_goods']['warehouse_price'] : 0;
                } elseif ($val['model_price'] == 2) {
                    $res[$key]['shop_price'] = $val['get_warehouse_area_goods'] ? $val['get_warehouse_area_goods']['region_price'] : 0;
                } else {
                    $res[$key]['shop_price'] = $val['shop_price'];
                }

                if ($res[$key]['shop_price'] <= 0) {
                    unset($res[$key]);
                }
            }
        }

        $min = $res ? collect($res)->min('shop_price') : 0;
        $max = $res ? collect($res)->max('shop_price') : 0;

        $arr = [
            'list' => $res,
            'min' => $min,
            'max' => $max
        ];

        return $arr;
    }

    /**
     * 获得当前分类下商品价格的跨度
     *
     * @access  public
     * @param array $list
     * @param int $min
     * @param int $dx
     *
     * @return array
     */
    public function getGoodsPriceGrade($list, $min, $dx)
    {
        $arr = [];
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['sn'] = intval(floor(($val['shop_price'] - $min) / $dx));
            }

            $list = collect($list)->groupBy('sn')->toArray();

            foreach ($list as $key => $val) {
                $arr[$key]['sn'] = $key;
                $arr[$key]['goods_num'] = collect($val)->count();
            }
        }

        return $arr;
    }
}
