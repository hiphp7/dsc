<?php

namespace App\Services\Brand;

use App\Models\Goods;
use App\Models\GoodsCat;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\AreaService;
use App\Services\Common\TemplateService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Merchant\MerchantCommonService;

class BrandGoodsService
{
    protected $baseRepository;
    protected $templateService;
    protected $dscRepository;
    protected $config;
    protected $timeRepository;
    protected $goodsCommonService;
    protected $merchantCommonService;
    protected $goodsGalleryService;
    protected $city;

    public function __construct(
        BaseRepository $baseRepository,
        TemplateService $templateService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService,
        GoodsGalleryService $goodsGalleryService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->templateService = $templateService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsGalleryService = $goodsGalleryService;

        /* 获取地区缓存 */
        $area_cookie = app(AreaService::class)->areaCookie();
        $this->city = $area_cookie['city'];
    }

    /**
     * 获得指定品牌下的推荐和促销商品
     *
     * @param array $where
     * @return mixed
     * @throws \Exception
     */
    public function getBrandRecommendGoods($where = [])
    {

        $num = $where['size'] ?? 0;
        $start = $where['start'] ?? 0;
        $type = $where['type'] ?? '';
        $brand_id = $where['brand_id'] ?? 0;
        $self = $where['where_ext']['self'] ?? 0;
        $have = $where['where_ext']['have'] ?? 0;
        $ship = $where['where_ext']['ship'] ?? 0;
        $area_pricetype = $this->config['area_pricetype'];
        $warehouse_id = $where['warehouse_id'] ?? 0;
        $area_id = $where['area_id'] ?? 0;
        $area_city = $where['area_city'] ?? 0;

        $cache_cats = '';
        if (isset($where['cats']) && $where['cats']) {
            $cache_cats = is_array($where['cats']) ? implode(',', $where['cats']) : $where['cats'];
        }

        $user_rank = session('user_rank');
        $discount = session('discount');

        $where['area_pricetype'] = $area_pricetype;
        $where['user_rank'] = $user_rank;
        $where['discount'] = $discount;

        //模板缓存
        $cache_id = $num . '_' . $start . '_' . $type . '_' . $brand_id . '_' . $self . '_' . $have . '_' . $ship . '_' . $area_pricetype . '_' .
            $warehouse_id . '_' . $area_id . '_' . $area_city . '_' . $user_rank . '_' . $discount . '-' . $cache_cats;
        $cache_id = sprintf('%X', crc32($cache_id));

        $goodsInfo = cache()->rememberForever('get_brand_recommend_goods.' . $cache_id, function () use ($num, $start, $where) {

            if (isset($where['type']) && $num == 0) {
                $type2lib = ['best' => 'recommend_best', 'new' => 'recommend_new', 'hot' => 'recommend_hot', 'promote' => 'recommend_promotion'];
                $num = $this->templateService->getLibraryNumber($type2lib[$where['type']]);
            }

            $goodsParam = [];
            if (isset($where['cats']) && $where['cats']) {
                $where['cats'] = !is_array($where['cats']) ? explode(",", $where['cats']) : $where['cats'];

                /* 查询扩展分类数据 */
                $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $where['cats']);
                $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
                $extension_goods = $this->baseRepository->getFlatten($extension_goods);

                $goodsParam = [
                    'children' => $where['cats'],
                    'extension_goods' => $extension_goods
                ];
            }


            /* 查询分类商品数据 */
            $res = Goods::where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0)
                ->where('is_show', 1);

            if ($goodsParam) {
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

            if (isset($where['brand_id']) && $where['brand_id']) {
                $res = $res->where('brand_id', $where['brand_id']);
            }

            if (isset($where['where_ext'])) {

                /* 查询仅自营和标识自营店铺的商品 */
                if (isset($where['where_ext']['self']) && $where['where_ext']['self'] == 1) {
                    $res = $res->where(function ($query) {
                        $query->where('user_id', 0)->orWhere(function ($query) {
                            $query->whereHas('getShopInfo', function ($query) {
                                $query->where('self_run', 1);
                            });
                        });
                    });
                }

                if (isset($where['where_ext']['have']) && $where['where_ext']['have'] == 1) {
                    $res = $res->where('goods_number', '>', 0);
                }

                if (isset($where['where_ext']['ship']) && $where['where_ext']['ship'] == 1) {
                    $res = $res->where('is_shipping', 1);
                }
            }

            if (isset($where['min']) && $where['min'] > 0) {
                $res = $res->where('shop_price', '>=', $where['min']);
            }

            if (isset($where['max']) && $where['max'] > 0) {
                $res = $res->where('shop_price', '<=', $where['max']);
            }

            $res = $this->dscRepository->getWhereRsid($res, 'user_id', 0, $this->city);

            if (isset($where['type'])) {
                switch ($where['type']) {
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
            }

            if ($this->config['review_goods']) {
                $res = $res->where('review_status', '>', 2);
            }

            $res = $this->dscRepository->getAreaLinkGoods($res, $where['area_id'], $where['area_city']);

            $where['area_pricetype'] = $this->config['area_pricetype'];

            $res = $res->with([
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
                },
                'getBrand'
            ]);

            $order_type = $this->config['recommend_order'];
            if (isset($where['type']) && $where['type'] == 'rand') {
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

            $result = $this->baseRepository->getToArrayGet($res);

            $idx = 0;
            $goods = [];

            if ($result) {
                foreach ($result as $row) {
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

                    $goods[$idx]['promote_price'] = $promote_price > 0 ? $this->dscRepository->getPriceFormat($promote_price) : '';

                    $goods[$idx]['id'] = $row['goods_id'];
                    $goods[$idx]['name'] = $row['goods_name'];

                    $brand = $row['get_brand'];

                    if ($goods[$idx]['brand_id']) {
                        $goods[$idx]['brand_id'] = isset($brand['brand_id']) ? $brand['brand_id'] : '';
                        $goods[$idx]['brand_name'] = isset($brand['brand_name']) ? $brand['brand_name'] : '';
                        $goods[$idx]['brand_url'] = $this->dscRepository->buildUri('brandn', ['bid' => $goods[$idx]['brand_id']], $goods[$idx]['brand_name']);
                    }

                    $goods[$idx]['sales_volume'] = $row['sales_volume'];
                    $goods[$idx]['comments_number'] = $row['comments_number'];
                    $goods[$idx]['brief'] = $row['goods_brief'];
                    $goods[$idx]['short_style_name'] = $this->config['goods_name_length'] > 0 ?
                        $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                    $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                    $goods[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                    $goods[$idx]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                    $goods[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                    $goods[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

                    $idx++;
                }
            }

            return $goods;
        });

        return $goodsInfo;
    }

    /**
     * 获得指定的品牌下的商品总数
     *
     * @param int $brand_id
     * @param $cat
     * @param int $min
     * @param int $max
     * @param array $where_ext
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return mixed
     */
    public function getGoodsCountByBrand($brand_id = 0, $cat, $min = 0, $max = 0, $where_ext = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $extension_goods = [];
        if ($cat) {
            $cat = $this->baseRepository->getExplode($cat);

            /* 查询扩展分类数据 */
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $cat);
            $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
            $extension_goods = $this->baseRepository->getFlatten($extension_goods);
        }

        $goodsParam = [
            'children' => $cat,
            'extension_goods' => $extension_goods
        ];

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->where(function ($query) use ($goodsParam) {
                if ($goodsParam['children']) {
                    $query = $query->whereIn('cat_id', $goodsParam['children']);
                }

                if ($goodsParam['extension_goods']) {
                    $query->orWhere(function ($query) use ($goodsParam) {
                        $query->whereIn('goods_id', $goodsParam['extension_goods']);
                    });
                }
            });

        if ($brand_id) {
            $res = $res->where('brand_id', $brand_id);
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
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

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $res = $res->count();

        return $res;
    }

    /**
     * 获得品牌下的商品
     *
     * @param int $brand_id
     * @param array $cat
     * @param int $min
     * @param int $max
     * @param array $where_ext
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $goods_num
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @return array
     * @throws \Exception
     */
    public function getBrandGoodsList($brand_id = 0, $cat = [], $min = 0, $max = 0, $where_ext = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $goods_num = 0, $size = 10, $page = 1, $sort = 'goods_id', $order = 'DESC')
    {
        $extension_goods = [];
        if ($cat) {
            $cat = $this->baseRepository->getExplode($cat);

            /* 查询扩展分类数据 */
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $cat);
            $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
            $extension_goods = $this->baseRepository->getFlatten($extension_goods);
        }

        $goodsParam = [
            'children' => $cat,
            'extension_goods' => $extension_goods
        ];

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->where(function ($query) use ($goodsParam) {
                if ($goodsParam['children']) {
                    $query = $query->whereIn('cat_id', $goodsParam['children']);
                }

                if ($goodsParam['extension_goods']) {
                    $query->orWhere(function ($query) use ($goodsParam) {
                        $query->whereIn('goods_id', $goodsParam['extension_goods']);
                    });
                }
            });

        if ($brand_id) {
            $res = $res->where('brand_id', $brand_id);
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
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
            'getBrand',
            'getShopInfo',
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

        $res = $res->orderBy($sort, $order);

        //瀑布流加载分类商品 by wu
        if ($goods_num) {
            $start = $goods_num;
        } else {
            $start = ($page - 1) * $size;
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

                if ($row['model_attr'] == 1) {
                    $prod = $row['get_products_warehouse'] ?? [];
                } elseif ($row['model_attr'] == 2) {
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

                $arr[$row['goods_id']]['review_count'] = $row['review_count'];

                $arr[$row['goods_id']]['pictures'] = $this->goodsGalleryService->getGoodsGallery($row['goods_id'], 6); // 商品相册

                if ($this->config['customer_service'] == 0) {
                    $seller_id = 0;
                } else {
                    $seller_id = $row['user_id'];
                }

                $shop_information = $this->merchantCommonService->getShopName($seller_id); //通过ru_id获取到店铺信息;

                if ($this->config['customer_service'] == 0) {
                    $shop_information['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);
                }

                $arr[$row['goods_id']]['rz_shopName'] = $shop_information['shop_name'];
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
            }
        }

        return $arr;
    }
}
