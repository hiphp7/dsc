<?php

use App\Models\AuctionLog;
use App\Models\CartCombo;
use App\Models\Category;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsCat;
use App\Models\GoodsExtend;
use App\Models\GroupGoods;
use App\Models\MerchantsGrade;
use App\Models\MerchantsShopBrand;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Seckill;
use App\Models\SeckillGoods;
use App\Models\SeckillGoodsRemind;
use App\Models\SeckillTimeBucket;
use App\Models\Wholesale;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\SeckillService;
use App\Services\Category\CategoryService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 商品推荐usort用自定义排序行数
 */
function goods_sort($goods_a, $goods_b)
{
    if ($goods_a['sort_order'] == $goods_b['sort_order']) {
        return 0;
    }
    return ($goods_a['sort_order'] < $goods_b['sort_order']) ? -1 : 1;
}

/**
 * 调用当前分类的销售排行榜
 *
 * @access  public
 * @param string $cats 查询的分类
 * @return  array
 */
function get_top10($cats = 0, $presale = '', $ru_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
{
    $CategoryRep = app(CategoryService::class);

    $children = [];
    if (!empty($cats)) {
        $children = $CategoryRep->getCatListChildren($cats);
    }

    /* 查询扩展分类数据 */
    $extension_goods = [];
    if ($children) {
        $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children);
        $extension_goods = app(BaseRepository::class)->getToArrayGet($extension_goods);
        $extension_goods = app(BaseRepository::class)->getFlatten($extension_goods);
    }

    $goodsParam = [
        'children' => $children,
        'extension_goods' => $extension_goods,
        'order_status' => [OS_CONFIRMED, OS_SPLITED],
        'pay_status' => [PS_PAYED, PS_PAYING],
        'shipping_status' => [SS_SHIPPED, SS_RECEIVED],
        'cfg_top' => $GLOBALS['_CFG']['top10_time'],
        'top_time' => [
            'one_year' => local_date('Ymd', gmtime() - 365 * 86400),
            'half_year' => local_date('Ymd', gmtime() - 180 * 86400),
            'three_month' => local_date('Ymd', gmtime() - 90 * 86400),
            'one_month' => local_date('Ymd', gmtime() - 30 * 86400)
        ]
    ];

    $arr = Goods::where('is_on_sale', 1)
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

    if ($GLOBALS['_CFG']['review_goods']) {
        $arr = $arr->where('review_status', '>', 2);
    }

    $arr = app(DscRepository::class)->getAreaLinkGoods($arr, $area_id, $area_city);

    $arr = $arr->whereHas('getOrderGoods', function ($query) use ($goodsParam) {

        /* 排行统计的时间 */
        $query->whereHas('getOrder', function ($query) use ($goodsParam) {
            $query = $query->where('main_count', 0)
                ->whereIn('order_status', $goodsParam['order_status'])
                ->whereIn('pay_status', $goodsParam['pay_status'])
                ->whereIn('shipping_status', $goodsParam['shipping_status']);

            // 一年
            if ($goodsParam['cfg_top'] == 1) {
                $query->where('order_sn', '>=', $goodsParam['top_time']['one_year']);
            } // 半年
            elseif ($goodsParam['cfg_top'] == 2) {
                $query->where('order_sn', '>=', $goodsParam['top_time']['half_year']);
            } // 三个月
            elseif ($goodsParam['cfg_top'] == 3) {
                $query->where('order_sn', '>=', $goodsParam['top_time']['three_month']);
            } // 一个月
            elseif ($goodsParam['cfg_top'] == 4) {
                $query->where('order_sn', '>=', $goodsParam['top_time']['one_month']);
            }
        });
    });

    if ($presale == 'presale') {
        $arr = $arr->whereHas('getPresaleActivity', function ($query) {
            $query->where('review_status', 3);
        });
    }

    $where = [
        'area_id' => $area_id,
        'area_city' => $area_city,
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
    ];

    $user_rank = session('user_rank');
    $arr = $arr->with([
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
        'getOrderGoodsList'
    ]);

    if ($GLOBALS['_CFG']['top_number'] > 0) {
        $arr = $arr->take($GLOBALS['_CFG']['top_number']);
    }

    $arr = $arr->groupBy('goods_id');

    $arr = app(BaseRepository::class)->getToArrayGet($arr);

    if ($arr) {
        foreach ($arr as $key => $row) {
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

            $price = app(GoodsCommonService::class)->getGoodsPrice($price, session('discount'), $row);

            $row['shop_price'] = $price['shop_price'];
            $row['promote_price'] = $price['promote_price'];
            $row['goods_number'] = $price['goods_number'];

            $arr[$key] = $row;

            $arr[$key]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                app(DscRepository::class)->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $arr[$key]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            $arr[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);

            if ($row['promote_price'] > 0) {
                $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $arr[$key]['market_price'] = price_format($row['market_price']);
            $arr[$key]['shop_price'] = price_format($row['shop_price']);
            $arr[$key]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            $arr[$key]['sales_volume'] = app(BaseRepository::class)->getSum($row['get_order_goods_list'], 'goods_number');
        }

        $arr = app(BaseRepository::class)->getSortBy($arr, 'sales_volume', 'desc');

        //判断是否启用库存，库存数量是否大于0
        if ($GLOBALS['_CFG']['use_storage'] == 1) {
            $arr = app(BaseRepository::class)->getWhere($arr, ['str' => 'goods_number', 'estimate' => '>', 'val' => '0']);
        }
    }

    return $arr;
}

//查找品牌
function get_goods_brand($brand_id = 0, $ru_id = 0)
{
    $res = MerchantsShopBrand::select('bid as brand_id', 'brandName as goods_brand')
        ->where('bid', $brand_id)
        ->where('user_id', $ru_id)
        ->where('audit_status', 1);

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

//by wang获得商品扩展信息
function get_goods_extends($goods_id = 0)
{
    $res = GoodsExtend::where('goods_id', $goods_id);

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

//ecmoban模板堂 --zhuo end

/**
 * 获得指定分类下的商品
 *
 * @access  public
 * @param integer $cat_id 分类ID
 * @param integer $num 数量
 * @param string $from 来自web/wap的调用
 * @param string $order_rule 指定商品排序规则
 * @return  array
 */
function assign_cat_goods($cat_id, $num = 0, $from = 'web', $order_rule = '', $return = 'cat', $warehouse_id = 0, $area_id = 0, $area_city = 0, $floor_sort_order = 0)
{

    /* 分类信息 */
    $cat_info = Category::where('cat_id', $cat_id)
        ->where('is_show', 1);

    $cat_info = app(BaseRepository::class)->getToArrayFirst($cat_info);

    $cat['name'] = $cat_info['cat_name'] ?? '';
    $cat['alias_name'] = $cat_info['cat_alias_name'] ?? '';

    $cat['url'] = app(DscRepository::class)->buildUri('category', ['cid' => $cat_id], $cat['name']);
    $cat['id'] = $cat_id;

    //获取二级分类下的商品
    $goods_index_cat1 = app(CategoryService::class)->getChildTree($cat_id);
    $goods_index_cat2 = get_cat_goods_index_cat2($cat_id, $num, $warehouse_id, $area_id, $area_city);

    $cat['goods_level2'] = array_values($goods_index_cat1);
    $cat['goods_level3'] = $goods_index_cat2;

    $cat['floor_num'] = $num;
    $cat['warehouse_id'] = $warehouse_id;
    $cat['area_id'] = $area_id;
    $cat['area_city'] = $area_city;

    $cat['floor_banner'] = 'floor_banner' . $cat_id;
    $cat['floor_sort_order'] = $floor_sort_order + 1;

    /* zhangyh_100322 end */

    return $cat;
}

/**
 * 查询子分类
 */
function get_cat_goods_index_cat2($cat_id = 0, $num = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
{
    $res = Category::where('parent_id', $cat_id)
        ->where('is_show', 1)
        ->orderBy('sort_order')
        ->orderBy('cat_id')
        ->take(10);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $where = [
        'area_id' => $area_id,
        'area_city' => $area_city,
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
    ];

    $arr = [];
    if ($res) {
        $CategoryRep = app(CategoryService::class);

        foreach ($res as $key => $value) {
            if ($key == 0) {
                $children = $CategoryRep->getCatListChildren($value['cat_id']);

                /* 查询扩展分类数据 */
                $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children);
                $extension_goods = app(BaseRepository::class)->getToArrayGet($extension_goods);
                $extension_goods = app(BaseRepository::class)->getFlatten($extension_goods);

                $goods_res = Goods::where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('is_delete', 0);

                if ($GLOBALS['_CFG']['review_goods']) {
                    $goods_res = $goods_res->where('review_status', '>', 2);
                }

                $goods_res = app(DscRepository::class)->getAreaLinkGoods($goods_res, $area_id, $area_city);

                $areaCookie = app(AreaService::class)->areaCookie();
                $goods_res = app(DscRepository::class)->getWhereRsid($goods_res, 'user_id', 0, $areaCookie['city']);

                $where['children'] = $children;
                $where['extension_goods'] = $extension_goods;

                $goods_res = $goods_res->where(function ($query) use ($where) {
                    $query = $query->where('cat_id', $where['children']);

                    if ($where['extension_goods']) {
                        $query->orWhere('goods_id', $where['extension_goods']);
                    }
                });

                $user_rank = session('user_rank');
                $goods_res = $goods_res->with([
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

                if ($num > 0) {
                    $goods_res = $goods_res->take($num);
                }

                $goods_res = $goods_res->orderByRaw("sort_order, goods_id");

                $goods_res = app(BaseRepository::class)->getToArrayGet($goods_res);

                if ($goods_res) {
                    foreach ($goods_res as $idx => $row) {
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

                        $price = app(GoodsCommonService::class)->getGoodsPrice($price, session('discount'), $row);

                        $row['shop_price'] = $price['shop_price'];
                        $row['promote_price'] = $price['promote_price'];
                        $row['goods_number'] = $price['goods_number'];

                        $goods_res[$idx] = $row;

                        if ($row['promote_price'] > 0) {
                            $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                        } else {
                            $promote_price = 0;
                        }

                        $goods_res[$idx]['is_promote'] = $row['is_promote'];
                        $goods_res[$idx]['goods_thumb'] = get_image_path($row['goods_thumb']);
                        $goods_res[$idx]['market_price'] = price_format($row['market_price']);
                        $goods_res[$idx]['shop_price'] = price_format($row['shop_price']);
                        $goods_res[$idx]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
                        $goods_res[$idx]['shop_price'] = price_format($row['shop_price']);
                        $goods_res[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? app(DscRepository::class)->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                        $goods_res[$idx]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                        $arr[$key]['goods'] = $goods_res;
                    }
                }
            } else {
                $arr[$key]['goods'] = [];
            }

            $arr[$key]['cats'] = $value['cat_id'];
            $arr[$key]['floor_num'] = $num;
            $arr[$key]['warehouse_id'] = $warehouse_id;
            $arr[$key]['area_id'] = $area_id;
        }
    }


    return $arr;
}

function get_brands_theme2($brands)
{
    $arr = [];
    if ($brands) {
        foreach ($brands as $key => $row) {
            if ($key < 8) {
                $arr['one_brands'][$key] = $row;
            } elseif ($key >= 8 && $key <= 15) {
                $arr['two_brands'][$key] = $row;
            } elseif ($key >= 16 && $key <= 23) {
                $arr['three_brands'][$key] = $row;
            } elseif ($key >= 24 && $key <= 31) {
                $arr['foure_brands'][$key] = $row;
            } elseif ($key >= 32 && $key <= 39) {
                $arr['five_brands'][$key] = $row;
            }
        }

        $arr = array_values($arr);
    }

    return $arr;
}

/**
 * 获得所有扩展分类属于指定分类的所有商品ID
 *
 * @access  public
 * @param array $cats 分类信息
 * @return  array;
 */
function get_extension_goods($cats = [])
{
    $goods_id = [];
    if ($cats) {

        /* 查询扩展分类数据 */
        $res = GoodsCat::select('goods_id')->whereIn('cat_id', $cats);
        $res = app(BaseRepository::class)->getToArrayGet($res);
        $goods_id = app(BaseRepository::class)->getKeyPluck($res, 'goods_id');
    }

    return $goods_id;
}

/**
 * 取得拍卖活动出价记录
 * @param int $act_id 活动id
 * @return  array
 */
function auction_log($act_id = 0, $type = 0, $is_anonymous = 1)
{
    if ($type == 1) {
        $log = AuctionLog::where('act_id', $act_id);

        $log = $log->whereHas('getUsers');

        $log = $log->count();
    } else {
        $res = AuctionLog::where('act_id', $act_id);

        $res = $res->whereHas('getUsers');

        $res = $res->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name');
            }
        ]);

        $res = $res->orderBy('log_id', 'desc');

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $log = [];
        if ($res) {
            foreach ($res as $row) {
                if ($row['get_users']) {
                    $row = array_merge($row, $row['get_users']);
                }

                $row['user_name'] = isset($row['user_name']) ? $is_anonymous ? setAnonymous($row['user_name']) : $row['user_name'] : ''; //处理用户名 by wu

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row['user_name'] = app(DscRepository::class)->stringToStar($row['user_name']);
                }

                $row['bid_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['bid_time']);
                $row['formated_bid_price'] = price_format($row['bid_price'], false);
                $log[] = $row;
            }
        }
    }

    return $log;
}

/**
 * 取得优惠活动信息
 * @param int $act_id 活动id
 * @return  array
 */
function favourable_info($act_id, $path = '')
{
    $row = FavourableActivity::where('act_id', $act_id);

    if (empty($path)) {
        $row = $row->where('review_status', 3);
    }

    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if (!empty($row)) {
        $row['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
        $row['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
        $row['formated_min_amount'] = price_format($row['min_amount']);
        $row['formated_max_amount'] = price_format($row['max_amount']);
        $row['gift'] = unserialize($row['gift']);
        if ($row['act_type'] == FAT_GOODS) {
            $row['act_type_ext'] = round($row['act_type_ext']);
        }
    }

    return $row;
}

/**
 * 批发信息
 * @param int $act_id 活动id
 * @return  array
 */
function wholesale_info($act_id)
{
    $row = Wholesale::where('act_id', $act_id);

    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if (!empty($row)) {
        $row['price_list'] = isset($row['prices']) ? unserialize($row['prices']) : 0;
    }

    return $row;
}

/**
 * 限时批发信息
 * @param int $act_id 活动id
 * @return  array
 */
function wholesale_limit_info($act_id)
{
    $row = WholesaleLimit::where('act_id', $act_id);

    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if (!empty($row)) {
        $row['start_time'] = local_date($GLOBALS['_CFG']['date_format'], $row['start_time']);
        $row['end_time'] = local_date($GLOBALS['_CFG']['date_format'], $row['end_time']);
        $row['price_list'] = unserialize($row['prices']);
        $row['act_desc'] = $row['ext_info'];
    }

    return $row;
}

/**
 * 取得商品属性
 * @param int $goods_id 商品id
 * @return  array
 */
function get_goods_attr($goods_id)
{
    $attr_list = [];
    $res = Goods::select('goods_id', 'goods_type')
        ->where('goods_id', $goods_id);

    $res = $res->whereHas('getGoodsAttribute', function ($query) {
        $query->where('attr_type');
    });

    $res = $res->with([
        'getGoodsAttribute' => function ($query) {
            $query->select('cat_id', 'attr_id', 'attr_name');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $attr) {
            $attr = app(BaseRepository::class)->getArrayMerge($attr['get_goods_attribute']);

            if (defined('ECS_ADMIN')) {
                $attr['goods_attr_list'] = [0 => $GLOBALS['_LANG']['select_please']];
            } else {
                $attr['goods_attr_list'] = [];
            }

            $attr_list[$attr['attr_id']] = $attr;
        }

        $attr_id_list = app(BaseRepository::class)->getKeyPluck($attr_list, 'attr_id');

        $res = GoodsAttr::select('attr_id', 'goods_attr_id', 'attr_value')
            ->whereIn('attr_id', $attr_id_list)
            ->where('goods_id', $goods_id);

        $res = app(BaseRepository::class)->getToArrayGet($res);

        if ($res) {
            foreach ($res as $goods_attr) {
                $attr_list[$goods_attr['attr_id']]['goods_attr_list'][$goods_attr['goods_attr_id']] = $goods_attr['attr_value'];
            }
        }
    }


    return $attr_list;
}

/**
 * 获得购物车中商品的配件
 *
 * @access  public
 * @param array $goods_list
 * @return  array
 */
function get_goods_fittings($goods_list = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $rev = '', $type = 0, $goods_equal = [], $user_id = 0, $userrank = [])
{
    if (empty($goods_list)) {
        return [];
    }
    if ($user_id > 0) {
        $user_id = $user_id;
    } else {
        $user_id = session('user_id', 0);
    }

    $session_id = app(SessionRepository::class)->realCartMacIp();

    $goods_list = !is_array($goods_list) ? explode(",", $goods_list) : $goods_list;
    $res = GroupGoods::whereIn('parent_id', $goods_list);

    $res = $res->whereHas('getGoods', function ($query) {
        $query->where('is_on_sale', 1)
            ->where('is_delete', 0);
    });

    $comboOther = [
        'fitts_goodsList' => $goods_equal,
        'user_id' => $user_id,
        'session_id' => $session_id,
        'rev' => $rev,
        'type' => $type
    ];

    if ($type > 0) {
        $res = $res->whereHas('getCartCombo', function ($query) use ($comboOther) {
            if ($comboOther['fitts_goodsList']) {
                $query = $query->whereIn('goods_id', $comboOther['fitts_goodsList']);
            }

            if ($comboOther['type'] == 1) {
                if (!empty($comboOther['user_id'])) {
                    $query = $query->where('user_id', $comboOther['user_id']);
                } else {
                    $query = $query->where('session_id', $comboOther['session_id']);
                }

                $query->where('group_id', $comboOther['rev']);
            }
        });

        if ($type == 2) {
            $res = $res->where('group_id', $rev);
        }
    }

    $where = [
        'area_id' => $area_id,
        'area_city' => $area_city,
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
    ];

    if ($userrank) {
        $user_rank = $userrank['rank_id'];
    } else {
        $user_rank = session('user_rank');
    }

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
        },
        'getCartCombo' => function ($query) {
            $query->selectRaw('goods_id, goods_attr_id, group_id as cc_group_id');
        }
    ]);

    $res = $res->orderByRaw('parent_id, goods_id asc');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $temp_index = 1;
    $arr = [];
    if ($res) {
        $warehouse_area = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];

        foreach ($res as $row) {
            $row['goods_attr_id'] = isset($row['goods_attr_id']) ? $row['goods_attr_id'] : "";

            $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;
            $row = $row['get_cart_combo'] ? array_merge($row, $row['get_cart_combo']) : $row;

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

            $discount = isset($userrank['discount']) && $userrank['discount'] ? $userrank['discount'] : session('discount');
            $price = app(GoodsCommonService::class)->getGoodsPrice($price, $discount, $row);

            $row['shop_price'] = $price['shop_price'];
            $row['promote_price'] = $price['promote_price'];
            $row['goods_number'] = $price['goods_number'];

            $arr[$temp_index] = $row;

            $row['parent_name'] = $row['goods_name'];

            $arr[$temp_index]['parent_id'] = $row['parent_id']; //配件的基本件ID
            $arr[$temp_index]['parent_name'] = $row['parent_name']; //配件的基本件的名称
            $arr[$temp_index]['parent_short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                app(DscRepository::class)->subStr($row['parent_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['parent_name']; //配件的基本件显示的名称
            $arr[$temp_index]['goods_id'] = $row['goods_id']; //配件的商品ID
            $arr[$temp_index]['goods_name'] = $row['goods_name']; //配件的名称
            $arr[$temp_index]['comments_number'] = $row['comments_number'];
            $arr[$temp_index]['sales_volume'] = $row['sales_volume'];
            $arr[$temp_index]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                app(DscRepository::class)->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name']; //配件显示的名称
            $arr[$temp_index]['fittings_price'] = price_format($row['goods_price']); //配件价格
            $arr[$temp_index]['shop_price'] = price_format($row['shop_price']); //配件原价格
            $arr[$temp_index]['spare_price'] = price_format($row['get_goods']['shop_price'] - $row['goods_price']); //节省的差价 by mike

            $arr[$temp_index]['market_price'] = $row['market_price'];

            $minMax_price = get_goods_minMax_price($row['goods_id'], $warehouse_id, $area_id, $area_city, $row['goods_price'], $row['market_price']); //配件价格min与max
            $arr[$temp_index]['fittings_minPrice'] = $minMax_price['goods_min'];
            $arr[$temp_index]['fittings_maxPrice'] = $minMax_price['goods_max'];

            $arr[$temp_index]['market_minPrice'] = $minMax_price['market_min'];
            $arr[$temp_index]['market_maxPrice'] = $minMax_price['market_max'];

            if (!empty($row['goods_attr_id'])) {
                $prod_attr = explode(',', $row['goods_attr_id']);
            } else {
                $prod_attr = [];
            }

            $attr_price = app(GoodsAttrService::class)->specPrice($prod_attr, $row['goods_id'], $warehouse_area);
            $arr[$temp_index]['attr_price'] = $attr_price;

            $arr[$temp_index]['shop_price_ori'] = $row['shop_price']; //配件原价格 by mike
            $arr[$temp_index]['fittings_price_ori'] = $row['goods_price']; //配件价格 by mike
            $arr[$temp_index]['spare_price_ori'] = ($row['get_goods']['shop_price'] - $row['goods_price']); //节省的差价 by mike
            $arr[$temp_index]['group_id'] = $row['group_id']; //套餐组 by mike

            if ($type == 2) {
                $cc_rev = "m_goods_" . $rev . "_" . $row['parent_id'];
                $img_flie = CartCombo::where('goods_id', $row['goods_id'])
                    ->where('group_id', $cc_rev);

                if (!empty($user_id)) {
                    $img_flie = $img_flie->where('user_id', $user_id);
                } else {
                    $img_flie = $img_flie->where('session_id', $session_id);
                }
            } else {
                $img_flie = CartCombo::where('goods_id', $row['goods_id'])
                    ->where('group_id', $rev);

                if (!empty($user_id)) {
                    $img_flie = $img_flie->where('user_id', $user_id);
                } else {
                    $img_flie = $img_flie->where('session_id', $session_id);
                }
            }

            $img_flie = $img_flie->value('img_flie');
            $arr[$temp_index]['img_flie'] = $img_flie;

            if (!empty($img_flie)) {
                $arr[$temp_index]['goods_thumb'] = $arr[$temp_index]['img_flie'];
            } else {
                $arr[$temp_index]['goods_thumb'] = $row['goods_thumb'];
            }

            $arr[$temp_index]['goods_thumb'] = get_image_path($arr[$temp_index]['goods_thumb']);
            $arr[$temp_index]['goods_img'] = get_image_path($row['goods_img']);
            $arr[$temp_index]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            $arr[$temp_index]['attr_id'] = !empty($row['goods_attr_id']) ? str_replace(',', '|', $row['goods_attr_id']) : "";

            //求组合购买商品已选择属性的库存量 start
            if (empty($row['goods_attr_id'])) {
                $arr[$temp_index]['goods_number'] = get_goods_fittings_gnumber($row['goods_number'], $row['goods_id'], $warehouse_id, $area_id, $area_city);
            } else {
                $products = app(GoodsWarehouseService::class)->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $warehouse_id, $area_id, $area_city);
                $attr_number = $products ? $products['product_number'] : 0;

                if ($row['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $row['goods_id'])->where('warehouse_id', $warehouse_id);
                } elseif ($row['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $row['goods_id'])->where('area_id', $area_id);

                    if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                        $prod = $prod->where('city_id', $area_city);
                    }
                } else {
                    $prod = Products::where('goods_id', $row['goods_id']);
                }

                $prod = app(BaseRepository::class)->getToArrayFirst($prod);

                if (empty($prod)) { //当商品没有属性库存时
                    $attr_number = $row['goods_number'];
                }

                $arr[$temp_index]['goods_number'] = $attr_number;
            }
            //求组合购买商品已选择属性的库存量 end

            $row['goods_attr_id'] = isset($row['goods_attr_id']) ? $row['goods_attr_id'] : '';
            $arr[$temp_index]['properties'] = app(GoodsAttrService::class)->getGoodsProperties($row['goods_id'], $warehouse_id, $area_id, $area_city, $row['goods_attr_id']);

            if ($type == 2) {
                $group_id = "m_goods_" . $rev . "_" . $row['parent_id'];
                $rec_id = CartCombo::where('goods_id', $row['goods_id'])
                    ->where('group_id', $group_id);

                if (!empty($user_id)) {
                    $rec_id = $rec_id->where('user_id', $user_id);
                } else {
                    $rec_id = $rec_id->where('session_id', $session_id);
                }

                $rec_id = $rec_id->value('rec_id');

                $group_cnt = "m_goods_" . $rev . "=" . $row['parent_id'];
                $arr[$temp_index]['group_top'] = $row['goods_id'] . "|" . $warehouse_id . "|" . $area_id . "|" . $group_cnt;

                if ($rec_id > 0) {
                    $arr[$temp_index]['selected'] = 1;
                } else {
                    $arr[$temp_index]['selected'] = 0;
                }
            }

            $temp_index++;
        }
    }

    return $arr;
}

/**
 * 查询商品是否存在配件
 */
function get_group_goods_count($goods_id = 0)
{
    $count = GroupGoods::where('parent_id', $goods_id)->count();
    return $count;
}

//获取组合购买里面的单个商品（属性总）库存量
function get_goods_fittings_gnumber($goods_number, $goods_id, $warehouse_id, $area_id, $area_city = 0)
{
    $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');

    if ($model_attr == 1) {
        $res = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
    } elseif ($model_attr == 2) {
        $res = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);

        if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
            $res = $res->where('city_id', $area_city);
        }
    } else {
        $res = Products::where('goods_id', $goods_id);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    //当商品没有属性库存时
    if ($res) {
        $arr['product_number'] = 0;
        foreach ($res as $key => $row) {
            $arr[$key] = $row;
            $arr['product_number'] += $row['product_number'];
        }
    } else {
        $arr['product_number'] = $goods_number;
    }

    return $arr['product_number'];
}

/**
 * 获得组合购买的的主件商品
 *
 * @access  public
 * @param array $goods_list
 * @return  array
 */
function get_goods_fittings_info($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $rev = '', $type = 0, $fittings_goods = 0, $fittings_attr = [], $user_id = 0, $userrank = [])
{
    if (empty($user_id)) {
        $user_id = session('user_id');
    }


    $temp_index = 0;
    $arr = [];

    $res = Goods::where('is_on_sale', 1)
        ->where('is_alone_sale', 1)
        ->where('is_delete', 0)
        ->where('goods_id', $goods_id);

    if ($GLOBALS['_CFG']['review_goods']) {
        $res = $res->where('review_status', '>', 2);
    }

    $res = app(DscRepository::class)->getAreaLinkGoods($res, $area_id, $area_city);

    $session_id = app(SessionRepository::class)->realCartMacIp();
    if ($type == 0) {
        $comboWhere = [
            'rev' => $rev,
            'user_id' => $user_id,
            'session_id' => $session_id
        ];
        $res = $res->whereHas('getCartCombo', function ($query) use ($comboWhere) {
            $query = $query->where('group_id', $comboWhere['rev']);

            if (!empty($comboWhere['user_id'])) {
                $query->where('user_id', $comboWhere['user_id']);
            } else {
                $query->where('session_id', $comboWhere['session_id']);
            }
        });
    }

    $where = [
        'area_id' => $area_id,
        'area_city' => $area_city,
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
    ];

    $user_rank = session('user_rank');

    $user_rank = isset($userrank['rank_id']) && $userrank['rank_id'] ? $userrank['rank_id'] : $user_rank;
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
        'getCartCombo' => function ($query) {
            $query->select('goods_id', 'parent_id', 'goods_attr_id', 'goods_price', 'group_id');
        }
    ]);

    $res = $res->orderBy('goods_id', 'desc');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        $warehouse_area = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];

        foreach ($res as $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_cart_combo']);

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
            $discount = isset($userrank['discount']) && $userrank['discount'] ? $userrank['discount'] : session('discount');

            $price = app(GoodsCommonService::class)->getGoodsPrice($price, $discount, $row);

            $row['shop_price'] = $price['shop_price'];
            $row['promote_price'] = $price['promote_price'];
            $row['goods_number'] = $price['goods_number'];

            $arr[$temp_index] = $row;

            $arr[$temp_index]['parent_id'] = isset($row['parent_id']) ? $row['parent_id'] : 0; //配件的基本件ID
            $row['parent_name'] = isset($row['parent_name']) ? $row['parent_name'] : ''; //配件的基本件的名称
            $arr[$temp_index]['parent_name'] = $row['parent_name'];
            $arr[$temp_index]['parent_short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? app(DscRepository::class)->subStr($row['parent_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['parent_name']; //配件的基本件显示的名称
            $arr[$temp_index]['goods_id'] = $row['goods_id']; //配件的商品ID
            $arr[$temp_index]['goods_name'] = $row['goods_name']; //配件的名称
            $arr[$temp_index]['comments_number'] = isset($row['comments_number']) ? $row['comments_number'] : 0;
            $arr[$temp_index]['sales_volume'] = $row['sales_volume'];
            $arr[$temp_index]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? app(DscRepository::class)->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name']; //配件显示的名称

            $row['goods_price'] = isset($row['goods_price']) ? $row['goods_price'] : 0;
            $arr[$temp_index]['fittings_price'] = price_format($row['goods_price']); //配件价格

            if ($row['promote_price'] > 0) {
                $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            if (isset($row['goods_attr_id']) && !empty($row['goods_attr_id'])) {
                $prod_attr = explode(',', $row['goods_attr_id']);
            } else {
                $prod_attr = [];
            }

            if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                $add_tocart = 0;

                if (empty($fittings_goods)) {
                    $fittings_goods = $row['goods_id'];
                }

                if (empty($fittings_attr)) {
                    $fittings_attr = $prod_attr;
                }

                $goods_price = app(GoodsCommonService::class)->getFinalPrice($fittings_goods, 1, true, $fittings_attr, $warehouse_id, $area_id, $area_city, 0, 0, $add_tocart, 0, 0, $userrank);
            } else {
                $goods_price = ($promote_price > 0) ? $promote_price : $row['shop_price'];
            }
            $arr[$temp_index]['market_price'] = $row['market_price'];

            $arr[$temp_index]['shop_price'] = price_format($goods_price); //配件原价格
            $arr[$temp_index]['spare_price'] = price_format(0); //节省的差价 by mike

            $minMax_price = get_goods_minMax_price($row['goods_id'], $warehouse_id, $area_id, $area_city, $goods_price, $row['market_price']); //配件价格min与max
            $arr[$temp_index]['fittings_minPrice'] = $minMax_price['goods_min'];
            $arr[$temp_index]['fittings_maxPrice'] = $minMax_price['goods_max'];

            $arr[$temp_index]['market_minPrice'] = $minMax_price['market_min'];
            $arr[$temp_index]['market_maxPrice'] = $minMax_price['market_max'];

            if (!empty($row['goods_attr_id'])) {
                $prod_attr = explode(',', $row['goods_attr_id']);
            } else {
                $prod_attr = [];
            }

            $attr_price = app(GoodsAttrService::class)->specPrice($prod_attr, $row['goods_id'], $warehouse_area);
            $arr[$temp_index]['attr_price'] = $attr_price;

            $arr[$temp_index]['shop_price_ori'] = $goods_price; //配件原价格 by mike
            $arr[$temp_index]['fittings_price_ori'] = 0; //配件价格 by mike
            $arr[$temp_index]['spare_price_ori'] = 0; //节省的差价 by mike

            $row['group_id'] = isset($row['group_id']) ? $row['group_id'] : 0; //套餐组 by mike
            $arr[$temp_index]['group_id'] = $row['group_id'];

            $img_flie = CartCombo::where('goods_id', $row['goods_id'])
                ->where('group_id', $rev);

            if (!empty($user_id)) {
                $img_flie = $img_flie->where('user_id', $user_id);
            } else {
                $img_flie = $img_flie->where('session_id', $session_id);
            }

            $img_flie = $img_flie->value('img_flie');
            $arr[$temp_index]['img_flie'] = $img_flie;

            if (!empty($img_flie)) {
                $arr[$temp_index]['goods_thumb'] = $arr[$temp_index]['img_flie'];
            } else {
                $arr[$temp_index]['goods_thumb'] = $row['goods_thumb'];
            }

            $arr[$temp_index]['goods_img'] = get_image_path($row['goods_img']);
            $arr[$temp_index]['goods_thumb'] = get_image_path($arr[$temp_index]['goods_thumb']);
            $arr[$temp_index]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            $arr[$temp_index]['attr_id'] = isset($row['goods_attr_id']) ? str_replace(',', '|', $row['goods_attr_id']) : '';

            $row['goods_attr_id'] = isset($row['goods_attr_id']) ? $row['goods_attr_id'] : '';

            $products = app(GoodsWarehouseService::class)->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $warehouse_id, $area_id, $area_city);
            $attr_number = $products ? $products['product_number'] : 0;

            if ($row['model_attr'] == 1) {
                $prod = ProductsWarehouse::where('goods_id', $row['goods_id'])->where('warehouse_id', $warehouse_id);
            } elseif ($row['model_attr'] == 2) {
                $prod = ProductsArea::where('goods_id', $row['goods_id'])->where('area_id', $area_id);

                if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                    $prod = $prod->where('city_id', $area_city);
                }
            } else {
                $prod = Products::where('goods_id', $row['goods_id']);
            }

            $prod = app(BaseRepository::class)->getToArrayFirst($prod);

            //当商品没有属性库存时
            if (empty($prod)) {
                $attr_number = $row['goods_number'];
            }

            $attr_number = !empty($attr_number) ? $attr_number : 0;
            $arr[$temp_index]['goods_number'] = $attr_number;

            $arr[$temp_index]['properties'] = app(GoodsAttrService::class)->getGoodsProperties($row['goods_id'], $warehouse_id, $area_id, $area_city, $row['goods_attr_id']);

            $temp_index++;
        }
    }

    return $arr;
}

/**
 * 取得秒杀活动信息
 * @param int $seckill_id 秒杀活动id
 * @param int $current_num 本次购买数量（计算当前价时要加上的数量）
 * @return  array
 *                  status          状态：
 */
function seckill_info($seckill_id, $current_num = 0, $path = '')
{
    $seckill_id = intval($seckill_id);

    $seckill = SeckillGoods::where('id', $seckill_id);

    $seckill = $seckill->whereHas('getSeckill', function ($query) use ($path) {
        $query = $query->where('is_putaway', 1);

        if ($path) {
            $query->where('review_status', 3);
        }
    });

    $seckill = $seckill->with([
        'getGoods',
        'getSeckill',
        'getSeckillTimeBucket' => function ($query) {
            $query->select('id', 'begin_time', 'end_time');
        }
    ]);

    $seckill = app(BaseRepository::class)->getToArrayFirst($seckill);

    if ($seckill) {
        $seckill['sec_goods_id'] = $seckill['id'];
    }

    if (isset($seckill['get_goods'])) {
        $seckill = app(BaseRepository::class)->getArrayMerge($seckill, $seckill['get_goods']);
    }

    if (isset($seckill['get_seckill'])) {
        $seckill = app(BaseRepository::class)->getArrayMerge($seckill, $seckill['get_seckill']);
    }

    if (isset($seckill['get_seckill_time_bucket'])) {
        $seckill = app(BaseRepository::class)->getArrayMerge($seckill, $seckill['get_seckill_time_bucket']);
    }

    if ($seckill) {
        $seckill['id'] = $seckill['sec_goods_id'];
    }

    /* 如果为空，返回空数组 */
    if (empty($seckill)) {
        return [];
    }

    $tmr = 0;
    if (isset($_REQUEST['tmr']) && $_REQUEST['tmr'] == 1) {
        $tmr = 86400;
    }
    $begin_time = local_strtotime($seckill['begin_time']) + $tmr;
    $end_time = local_strtotime($seckill['end_time']) + $tmr;

    /* 格式化时间 */
    $seckill['formated_start_date'] = local_date('Y-m-d H:i:s', $begin_time);
    $seckill['formated_end_date'] = local_date('Y-m-d H:i:s', $end_time);

    $now = gmtime();
    if ($begin_time < $now && $end_time > $now) {
        $seckill['status'] = true;
    } else {
        $seckill['status'] = false;
    }
    $seckill['is_end'] = $now > $end_time ? 1 : 0;

    $stat = app(SeckillService::class)->secGoodsStats($seckill_id, $begin_time, $end_time);
    $seckill = app(BaseRepository::class)->getArrayMerge($seckill, $stat);

    $seckill['rz_shopName'] = app(MerchantCommonService::class)->getShopName($seckill['user_id'], 1); //店铺名称

    $seckill['goods_thumb'] = get_image_path($seckill['goods_thumb']);

    $build_uri = [
        'urid' => $seckill['user_id'],
        'append' => $seckill['rz_shopName']
    ];

    $domain_url = app(MerchantCommonService::class)->getSellerDomainUrl($seckill['user_id'], $build_uri);
    $seckill['store_url'] = $domain_url['domain_name'];

    $seckill['shopinfo'] = app(MerchantCommonService::class)->getShopName($seckill['user_id'], 2);
    $seckill['shopinfo']['brand_thumb'] = str_replace(['../'], '', $seckill['shopinfo']['brand_thumb']);
    if ($seckill['user_id'] == 0) {
        $seckill['brand'] = get_brand_url($seckill['brand_id']);
    }

    if ($GLOBALS['_CFG']['open_oss'] == 1) {
        $bucket_info = app(DscRepository::class)->getBucketInfo();
        $endpoint = $bucket_info['endpoint'];
    } else {
        $endpoint = url('/');
    }

    if ($seckill['goods_desc']) {
        $desc_preg = get_goods_desc_images_preg($endpoint, $seckill['goods_desc']);
        $seckill['goods_desc'] = $desc_preg['goods_desc'];
    }

    $seckill['formated_sec_price'] = price_format($seckill['sec_price']);
    $seckill['formated_market_price'] = price_format($seckill['market_price']);

    return $seckill;
}

//秒杀活动列表页
function seckill_goods_list()
{
    $now = gmtime();
    $day = 24 * 60 * 60;
    $sec_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    /* 取得秒杀活动商品ID */
    $goods_id = SeckillGoods::where('id', $sec_id)->value('goods_id');

    /* 取得秒杀活动用户设置提醒商品ID */
    $user_id = session('user_id');
    $beginYesterday = local_mktime(0, 0, 0, local_date('m'), local_date('d') - 1, local_date('Y'));

    $row = SeckillGoodsRemind::select('sec_goods_id')
        ->where('user_id', $user_id)
        ->where('add_time', '>', $beginYesterday);

    $row = app(BaseRepository::class)->getToArrayGet($row);
    $sec_goods_ids = app(BaseRepository::class)->getKeyPluck($row, 'sec_goods_id');

    $date_begin = local_strtotime(local_date('Ymd'));
    $date_next = local_strtotime(local_date('Ymd')) + $day;

    $cat_id = isset($_GET['cat_id']) && !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

    $res = seckill_goods_results($date_begin, $cat_id);
    $res_tmr = seckill_goods_results($date_next, $cat_id, $day);

    $stb = SeckillTimeBucket::whereRaw(1)
        ->orderBy('begin_time');

    $stb = app(BaseRepository::class)->getToArrayGet($stb);

    $sec_id_today = Seckill::select('sec_id')->where('begin_time', '<=', $date_begin)
        ->where('acti_time', '>', $date_begin)
        ->where('is_putaway', 1);
    $sec_id_today = app(BaseRepository::class)->getToArrayGet($sec_id_today);
    $sec_id_today = app(BaseRepository::class)->getKeyPluck($sec_id_today, 'sec_id');

    $arr = [];
    if ($stb) {
        foreach ($stb as $k => $v) {
            $v['local_end_time'] = local_strtotime($v['end_time']);
            if ($v['local_end_time'] > $now && $sec_id_today) {
                $arr[$k]['title'] = $v['title'];
                $arr[$k]['status'] = false;
                $arr[$k]['is_end'] = false;
                $arr[$k]['soon'] = false;
                $arr[$k]['begin_time'] = $begin_time = local_strtotime($v['begin_time']);
                $arr[$k]['end_time'] = $end_time = local_strtotime($v['end_time']);
                if ($begin_time < $now && $end_time > $now) {
                    $arr[$k]['status'] = true;
                }
                if ($end_time < $now) {
                    $arr[$k]['is_end'] = true;
                }
                if ($begin_time > $now) {
                    $arr[$k]['soon'] = true;
                }
            }
        }

        if (count($arr) < 4 && count($res_tmr) > 0) {
            foreach ($stb as $k => $v) {
                $arr['tmr' . $k]['title'] = $v['title'];
                $arr['tmr' . $k]['status'] = false;
                $arr['tmr' . $k]['is_end'] = false;
                $arr['tmr' . $k]['soon'] = true;
                $arr['tmr' . $k]['begin_time'] = local_strtotime($v['begin_time']) + $day;
                $arr['tmr' . $k]['end_time'] = local_strtotime($v['end_time']) + $day;
            }
        }
    }

    if ($arr) {
        foreach ($arr as $k => $v) {
            if ($res) {
                $arr1 = $arr2 = [];
                foreach ($res as $val) {
                    if ($v['end_time'] > $now && $val['begin_time'] == $v['begin_time']) {
                        if ($goods_id == $val['goods_id'] || in_array($val['id'], $sec_goods_ids)) {//把设置提醒的商品筛选出来
                            $arr1[$val['goods_id']] = $val;
                            if (in_array($val['id'], $sec_goods_ids)) {//设置过提醒的商品标识
                                $arr1[$val['goods_id']]['is_remind'] = 1;
                            }
                        } else {
                            $arr2[$val['goods_id']] = $val;
                        }
                    }
                }

                if ($arr1) {
                    $arr[$k]['goods'] = array_merge($arr1, $arr2);
                } else {
                    $arr[$k]['goods'] = $arr2;
                }

                unset($arr1, $arr2);
            }

            if (substr($k, 0, 3) == 'tmr') {
                if ($res_tmr) {
                    $arr1 = $arr2 = [];
                    foreach ($res_tmr as $val) {
                        if ($val['begin_time'] == $v['begin_time']) {
                            if (in_array($val['id'], $sec_goods_ids)) {//把设置提醒的商品筛选出来
                                $arr1[$val['goods_id']] = $val;
                                $arr1[$val['goods_id']]['is_remind'] = 1;
                            } else {
                                $arr2[$val['goods_id']] = $val;
                            }
                            $arr[$k]['tomorrow'] = 1;
                        }
                    }
                    if ($arr1) {
                        $arr[$k]['goods'] = array_merge($arr1, $arr2);
                    } else {
                        $arr[$k]['goods'] = $arr2;
                    }
                    unset($arr1, $arr2);
                }
            }
        }
    }

    return $arr;
}

//秒杀日期内的商品
function seckill_goods_results($date = '', $cat_id = 0, $day = 0)
{
    $date_begin = local_strtotime(local_date('Ymd')) + $day;

    $seckill = Seckill::select("sec_id")
        ->where('begin_time', '<=', $date_begin)
        ->where('acti_time', '>', $date_begin);

    $seckill = app(BaseRepository::class)->getToArrayGet($seckill);
    $seckill = app(BaseRepository::class)->getKeyPluck($seckill, 'sec_id');

    $where = [
        'date_begin' => $date_begin,
        'date' => $date,
        'seckill' => $seckill
    ];
    $res = SeckillGoods::select('id', 'goods_id', 'tb_id', 'sec_id', 'sec_price', 'sec_num', 'sec_limit')
        ->whereHas('getSeckill', function ($query) use ($where) {
            $query = $query->where('is_putaway', 1)
                ->where('review_status', 3)
                ->where('begin_time', '<=', $where['date_begin'])
                ->where('acti_time', '>=', $where['date']);

            if ($where['seckill']) {
                $query->whereIn('sec_id', $where['seckill']);
            }
        });

    if ($cat_id) {
        $CategoryRep = app(CategoryService::class);
        $children = $CategoryRep->getCatListChildren($cat_id);

        if ($children) {
            $res = $res->whereHas('getGoods', function ($query) use ($children) {
                $query->whereIn('cat_id', $children);
            });
        }
    } else {
        $res = $res->whereHas('getGoods');
    }

    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'goods_thumb', 'shop_price', 'market_price', 'goods_name');
        },
        'getSeckill' => function ($query) {
            $query->select('sec_id', 'acti_title', 'acti_time');
        },
        'getSeckillTimeBucket' => function ($query) {
            $query->select('id', 'begin_time', 'end_time');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $k => $v) {
            $v = app(BaseRepository::class)->getArrayMerge($v, $v['get_goods']);
            $v = app(BaseRepository::class)->getArrayMerge($v, $v['get_seckill']);

            $v['begin_time'] = $v['get_seckill_time_bucket']['begin_time'];
            $v['end_time'] = $v['get_seckill_time_bucket']['end_time'];

            $res[$k] = $v;

            $res[$k]['begin_time'] = local_strtotime($v['begin_time']) + $day;
            $res[$k]['end_time'] = local_strtotime($v['end_time']) + $day;
            $res[$k]['sec_price_formated'] = price_format($v['sec_price']);
            $res[$k]['market_price_formated'] = price_format($v['market_price']);

            if ($day > 0) {
                $res[$k]['url'] = app(DscRepository::class)->buildUri('seckill', ['act' => "view", 'secid' => $v['id'], 'tmr' => 1], $v['goods_name']);
            } else {
                $res[$k]['url'] = app(DscRepository::class)->buildUri('seckill', ['act' => "view", 'secid' => $v['id']], $v['goods_name']);
            }

            $res[$k]['sales_volume'] = app(SeckillService::class)->secGoodsStats($v['id'], $res[$k]['begin_time'], $res[$k]['end_time']);
            $res[$k]['percent'] = ($v['sec_num'] == 0) ? 100 : intval($res[$k]['sales_volume']['valid_goods'] / ($v['sec_num'] + $res[$k]['sales_volume']['valid_goods']) * 100);
            $res[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
        }

        $res = app(BaseRepository::class)->getSortBy($res, 'begin_time');
    }


    return $res;
}

/**
 * 获取当前商家的等级信息
 *
 * @param int $ru_id
 * @return array
 */
function get_merchants_grade_rank($ru_id = 0)
{
    if (empty($ru_id)) {
        return [];
    }

    $model = MerchantsGrade::query()->where('ru_id', $ru_id);

    $model = $model->whereHas('getSellerGrade');

    $model = $model->with([
        'getSellerGrade' => function ($query) {
            $query->select('id', 'goods_sun', 'seller_temp', 'favorable_rate', 'give_integral', 'rank_integral', 'pay_integral', 'grade_name', 'grade_img', 'grade_introduce');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayFirst($model);

    if ($res) {
        $seller_grade = $res['get_seller_grade'];

        $res['goods_sun'] = $seller_grade['goods_sun'];
        $res['seller_temp'] = $seller_grade['seller_temp'];
        $res['favorable_rate'] = $seller_grade['favorable_rate'];
        $res['give_integral'] = $seller_grade['give_integral'];
        $res['rank_integral'] = $seller_grade['rank_integral'];
        $res['pay_integral'] = $seller_grade['pay_integral'];

        $res['grade_name'] = $seller_grade['grade_name'] ?? '';
        $res['grade_img'] = $seller_grade['grade_img'] ?? '';
        $res['grade_introduce'] = $seller_grade['grade_introduce'] ?? '';
    }

    $res['give_integral'] = isset($res['give_integral']) && !empty($res['give_integral']) ? $res['give_integral'] / 100 : 1;
    $res['rank_integral'] = isset($res['give_integral']) && !empty($res['rank_integral']) ? $res['rank_integral'] / 100 : 1;

    return $res;
}

/**
 * 判断商品分类是否可用
 *
 * @access  public
 * @param string $cat_id
 * @return  bool
 */
function judge_goods_cat_enabled($cat_id = 0)
{
    if ($cat_id > 0) {
        while ($cat_id > 0) {
            $cat_info = Category::select('is_show', 'parent_id')->where('cat_id', $cat_id);
            $cat_info = app(BaseRepository::class)->getToArrayFirst($cat_info);

            if ($cat_info && $cat_info['is_show'] == 1) {
                $cat_id = $cat_info['parent_id'];
            } else {
                return false;
            }
        }
        return true;
    } else {
        return false;
    }
}
