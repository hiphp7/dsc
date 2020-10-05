<?php

use App\Models\Ad;
use App\Models\Category;
use App\Models\FloorContent;
use App\Models\Goods;
use App\Models\OrderInfo;
use App\Models\Region;
use App\Models\Sessions;
use App\Models\ShopConfig;
use App\Models\Users;
use App\Models\WholesaleCart;
use App\Models\WholesaleGoodsAttr;
use App\Models\ZcProject;
use App\Models\ZcTopic;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Ads\AdsService;
use App\Services\Article\ArticleService;
use App\Services\Cart\CartsertService;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Seckill\SeckillInsertService;
use App\Services\User\UserCommonService;
use App\Services\User\UserInsertService;

/**
 * 获得查询次数以及查询时间
 *
 * @access  public
 * @return  string
 */
function insert_query_info()
{
    if ($GLOBALS['db']->queryTime == '') {
        $query_time = 0;
    } else {
        if (PHP_VERSION >= '5.0.0') {
            $query_time = number_format(microtime(true) - $GLOBALS['db']->queryTime, 6);
        } else {
            list($now_usec, $now_sec) = explode(' ', microtime());
            list($start_usec, $start_sec) = explode(' ', $GLOBALS['db']->queryTime);
            $query_time = number_format(($now_sec - $start_sec) + ($now_usec - $start_usec), 6);
        }
    }

    /* 内存占用情况 */
    if ($GLOBALS['_LANG']['memory_info'] && function_exists('memory_get_usage')) {
        $memory_usage = sprintf($GLOBALS['_LANG']['memory_info'], memory_get_usage() / 1048576);
    } else {
        $memory_usage = '';
    }

    /* 是否启用了 gzip */
    $gzip_enabled = gzip_enabled() ? $GLOBALS['_LANG']['gzip_enabled'] : $GLOBALS['_LANG']['gzip_disabled'];

    $online_count = Sessions::whereRaw(1)->count();

    /* 加入触发cron代码 */
    $cron_method = empty($GLOBALS['_CFG']['cron_method']) ? '<img src="' . asset('cron.php?t=') . gmtime() . '" alt="" style="width:0px;height:0px;" />' : '';

    return sprintf($GLOBALS['_LANG']['query_info'], $GLOBALS['db']->queryCount, $query_time, $online_count) . $gzip_enabled . $memory_usage . $cron_method;
}

/**
 * 调用浏览历史by wang修改
 *
 * @access  public
 * @return  string
 */
function insert_history()
{
    $str = '<ul>';

    $ecsCookie = request()->cookie('ECS');
    if (isset($ecsCookie['history']) && !empty($ecsCookie['history'])) {

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $areaInfo = app(AreaService::class)->getAreaInfo();

        $warehouse_id = $areaInfo['area']['warehouse_id'];
        $area_id = $areaInfo['area']['area_id'];
        $area_city = $areaInfo['area']['city_id'];
        /* End */

        $history = !is_array($ecsCookie['history']) ? explode(",", $ecsCookie['history']) : $ecsCookie['history'];

        /* 查询商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->whereIn('goods_id', $history);

        if ($GLOBALS['_CFG']['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = app(DscRepository::class)->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
        ];

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

        $res = $res->orderByRaw("INSTR('" . $ecsCookie['history'] . "', goods_id)");

        $res = $res->take(5);

        $res = app(BaseRepository::class)->getToArrayGet($res);

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

                $price = app(GoodsCommonService::class)->getGoodsPrice($price, session('discount'), $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                if ($row['promote_price'] > 0) {
                    $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $goods['goods_id'] = $row['goods_id'];
                $goods['goods_name'] = $row['goods_name'];
                $goods['goods_number'] = $row['goods_number'];
                $goods['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? app(DscRepository::class)->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                $goods['goods_thumb'] = get_image_path($row['goods_thumb']);
                $goods['shop_price'] = price_format($row['shop_price']);
                $goods['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';

                if ($promote_price > 0) {
                    $price = $goods['shop_price'];
                } else {
                    $price = $goods['promote_price'];
                }

                $goods['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                //by wang
                $str .= '<li><div class="p-img"><a href="' . $goods['url'] . '" target="_blank" title="' . $goods['goods_name'] . '"><img src="' . $goods['goods_thumb'] . '" width="178" height="178"></a></div>
                            <div class="p-name"><a href="' . $goods['url'] . '" target="_blank">' . $goods['short_name'] . '</a></div><div class="p-price">' . $price . '</div>
                            <a href="javascript:addToCart(' . $goods['goods_id'] . ');" class="btn">加入购物车</a></li>';
            }
        }
    }

    $str .= "</ul>";

    return $str;
}

function insert_history_info($warehouse_id = 0, $area_id = 0, $area_city = 0, $num = 0)
{
    $res = [];

    $num = !empty($num) ? intval($num) : 0;

    $ecsCookie = request()->cookie('ECS');
    if (isset($ecsCookie['history']) && !empty($ecsCookie['history'])) {
        $history = !is_array($ecsCookie['history']) ? explode(",", $ecsCookie['history']) : $ecsCookie['history'];

        /* 查询商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->whereIn('goods_id', $history);

        if ($GLOBALS['_CFG']['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = app(DscRepository::class)->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
        ];

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

        $res = $res->orderByRaw("INSTR('" . $ecsCookie['history'] . "', goods_id)");

        if ($num > 0) {
            $res = $res->take($num);
        } else {
            $res = $res->take(14);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);

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

                $price = app(GoodsCommonService::class)->getGoodsPrice($price, session('discount'), $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $res[$key] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $res[$key]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? app(DscRepository::class)->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                $res[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
                $res[$key]['shop_price'] = price_format($row['shop_price']);
                $res[$key]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
                $res[$key]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            }
        }
    }

    return $res;
}

/**
 * 调用购物车信息
 *
 * @param int $type
 * @param int $num
 * @return mixed
 */
function insert_cart_info($type = 0, $num = 0)
{
    return app(CartsertService::class)->insertCartInfo($type, $num);
}

/**
 * 调用购物车加减返回信息
 *
 * @param $goods_price
 * @param $market_price
 * @param $saving
 * @param $save_rate
 * @param $goods_amount
 * @param $real_goods_count
 * @param int $cart_total_rate
 * @return mixed
 */
function insert_flow_info($goods_price, $market_price, $saving, $save_rate, $goods_amount, $real_goods_count, $cart_total_rate = 0)
{
    if ($cart_total_rate > 0 && CROSS_BORDER === true) { // 跨境多商户
        $subtotal_rate = price_format($cart_total_rate);
        $goods_price = htmlspecialchars($goods_price);
        $goods_price = str_replace('¥', '', $goods_price);
        $goods_price += $cart_total_rate;
        $goods_price = price_format($goods_price);
        $GLOBALS['smarty']->assign('cart_total_rate', $cart_total_rate);
        $GLOBALS['smarty']->assign('subtotal_rate', $subtotal_rate);
    }

    $GLOBALS['smarty']->assign('goods_price', $goods_price);
    $GLOBALS['smarty']->assign('market_price', $market_price);
    $GLOBALS['smarty']->assign('saving', $saving);
    $GLOBALS['smarty']->assign('save_rate', $save_rate);
    $GLOBALS['smarty']->assign('goods_amount', $goods_amount);
    $GLOBALS['smarty']->assign('real_goods_count', $real_goods_count);

    $output = $GLOBALS['smarty']->fetch('library/flow_info.lbi');
    return $output;
}

/**
 * 购物车弹出框返回信息
 *
 * @access  public
 * @return  string
 */
function insert_show_div_info($goods_number, $script_name, $goods_id, $goods_recommend, $goods_amount, $real_goods_count)
{
    $GLOBALS['smarty']->assign('goods_number', $goods_number);
    $GLOBALS['smarty']->assign('script_name', $script_name);
    $GLOBALS['smarty']->assign('goods_id', $goods_id);
    $GLOBALS['smarty']->assign('goods_recommend', $goods_recommend);
    $GLOBALS['smarty']->assign('goods_amount', $goods_amount);
    $GLOBALS['smarty']->assign('real_goods_count', $real_goods_count);

    $output = $GLOBALS['smarty']->fetch('library/show_div_info.lbi');
    return $output;
}

/**
 * 调用会员信息
 *
 * @access  public
 * @return  string
 */
function insert_member_info()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;

    if (session('user_id') > 0) {
        $GLOBALS['smarty']->assign('user_info', get_user_info());
    } else {
        $ecsCookie = request()->cookie('ECS');
        if (isset($ecsCookie['username']) && !empty($ecsCookie['username'])) {
            $GLOBALS['smarty']->assign('ecs_username', stripslashes($ecsCookie['username']));
        }
        $captcha = intval($GLOBALS['_CFG']['captcha']);
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
            $GLOBALS['smarty']->assign('enabled_captcha', 1);
            $GLOBALS['smarty']->assign('rand', mt_rand());
        }
    }

    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);

    $GLOBALS['smarty']->assign('shop_reg_closed', $GLOBALS['_CFG']['shop_reg_closed']);

    $urlHtmlKey = [
        'user'
    ];

    $urlHtml = app(DscRepository::class)->getUrlHtml($urlHtmlKey);
    $GLOBALS['smarty']->assign('user', $urlHtml['user']);

    $register = app(DscRepository::class)->buildUri('user', ['act' => 'register']);
    $GLOBALS['smarty']->assign('register', $register);

    $logout = app(DscRepository::class)->buildUri('user', ['act' => 'logout']);
    $GLOBALS['smarty']->assign('logout', $logout);

    $output = $GLOBALS['smarty']->fetch('library/member_info.lbi');

    $GLOBALS['smarty']->caching = $need_cache;

    return $output;
}

/**
 * 调用评论信息
 *
 * @access  public
 * @return  string
 */
function insert_comments($arr)
{
    $arr['id'] = isset($arr['id']) && !empty($arr['id']) ? intval($arr['id']) : 0;
    $arr['type'] = isset($arr['type']) ? addslashes($arr['type']) : '';

    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }

    $GLOBALS['smarty']->assign('username', stripslashes(session('user_name')));
    $GLOBALS['smarty']->assign('email', session('email'));
    $GLOBALS['smarty']->assign('comment_type', $arr['type']);
    $GLOBALS['smarty']->assign('id', $arr['id']);
    $cmt = assign_comment($arr['id'], $arr['type']);

    $GLOBALS['smarty']->assign('comments', $cmt['comments']);
    $GLOBALS['smarty']->assign('pager', $cmt['pager']);
    $GLOBALS['smarty']->assign('count', $cmt['count']);
    $GLOBALS['smarty']->assign('size', $cmt['size']);


    $val = $GLOBALS['smarty']->fetch('library/comments_list.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 通过类型与传入的ID获取广告内容  修改 zuo start
 *
 * @param string $type
 * @param int $id
 * @return string
 */
//广告位大图
function insert_get_adv($arr)
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }

    $ad_type = substr($arr['logo_name'], 0, 12);
    $GLOBALS['smarty']->assign('ad_type', $ad_type);

    $name = $arr['logo_name'];
    $GLOBALS['smarty']->assign('ad_posti', get_ad_posti($name, $ad_type));

    $val = $GLOBALS['smarty']->fetch('library/position_get_adv.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;


    return $val;
}

function get_ad_posti($name = '', $ad_type = '')
{
    $name = !empty($name) ? addslashes($name) : '';

    $time = gmtime();

    $res = Ad::where('media_type', 0)
        ->where('start_time', '<', $time)
        ->where('end_time', '>', $time)
        ->where('enabled', 1)
        ->where('ad_name', $name);

    $theme = $GLOBALS['_CFG']['template'];
    $res = $res->whereHas('getAdPosition', function ($query) use ($theme) {
        $query->where('theme', $theme);
    });

    $res = $res->with([
        'getAdPosition' => function ($query) {
            $query->select('position_id', 'ad_width', 'ad_height');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_ad_position']);

            $arr[$key]['ad_name'] = $row['ad_name'];
            $arr[$key]['ad_code'] = get_image_path(DATA_DIR . '/afficheimg/' . $row['ad_code']);

            if ($row["ad_link"]) {
                if (strpos($row["ad_link"], 'http') === false) {
                    $row["ad_link"] = 'affiche.php?ad_id=' . $row['ad_id'] . '&amp;uri=' . urlencode($row["ad_link"]);
                }
            }

            $arr[$key]['ad_link'] = $row["ad_link"];
            $arr[$key]['ad_width'] = $row['ad_width'];
            $arr[$key]['ad_height'] = $row['ad_height'];
            $arr[$key]['link_color'] = $row['link_color'];
            $arr[$key]['posti_type'] = $ad_type;
            $arr[$key]['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
            $arr[$key]['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
            $arr[$key]['ad_type'] = $row['ad_type'];
            $arr[$key]['goods_name'] = $row['goods_name'];
        }
    }

    return $arr;
}

//广告位小图
function insert_get_adv_child($arr)
{
    $arr['id'] = isset($arr['id']) && !empty($arr['id']) ? intval($arr['id']) : 0;

    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $arr['warehouse_id'] = isset($arr['warehouse_id']) && !empty($arr['warehouse_id']) ? intval($arr['warehouse_id']) : 0;
    $arr['area_id'] = isset($arr['area_id']) && !empty($arr['area_id']) ? intval($arr['area_id']) : 0;
    $arr['area_city'] = isset($arr['area_city']) && !empty($arr['area_city']) ? intval($arr['area_city']) : 0;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }

    if ($arr['id'] && $arr['ad_arr'] != '') {
        $id_name = '_' . $arr['id'] . "',";
        $str_ad = str_replace(',', $id_name, $arr['ad_arr']);
        $in_ad_arr = substr($str_ad, 0, strlen($str_ad) - 1);
    } else {
        $id_name = "',";
        $str_ad = str_replace(',', $id_name, $arr['ad_arr']);
        $in_ad_arr = substr($str_ad, 0, strlen($str_ad) - 1);
    }
    $ad_child = app(AdsService::class)->getAdPostiChild($in_ad_arr, $arr['warehouse_id'], $arr['area_id'], $arr['area_city']);
    $GLOBALS['smarty']->assign('ad_child', $ad_child);

    $index_ad = substr(substr($arr['ad_arr'], 0, 9), 1);
    $cat_goods_banner = substr(substr($arr['ad_arr'], 0, 17), 1);
    $cat_goods_hot = substr(substr($arr['ad_arr'], 0, 14), 1);
    $index_brand = substr(substr($arr['ad_arr'], 0, 19), 1);

    $val = $GLOBALS['smarty']->fetch('library/position_get_adv_small.lbi');

    if ($index_ad == 'index_ad') {
        $val = $GLOBALS['smarty']->fetch('library/index_ad_position.lbi');
    } elseif ($cat_goods_banner == 'cat_goods_banner' && isset($arr['floor_style_tpl'])) {
        $GLOBALS['smarty']->assign('floor_style_tpl', $arr['floor_style_tpl']);
        $val = $GLOBALS['smarty']->fetch('library/cat_goods_banner.lbi');
    }

    if ($cat_goods_hot == 'cat_goods_hot') {
        $val = $GLOBALS['smarty']->fetch('library/cat_goods_hot.lbi');
    }

    if ($index_brand == 'index_brand_banner') {
        $val = $GLOBALS['smarty']->fetch('library/index_brand_banner.lbi');
    } elseif ($index_brand == 'index_group_banner') {
        $val = $GLOBALS['smarty']->fetch('library/index_group_banner.lbi');
    }

    //登录页轮播广告 by wu
    $login_banner = substr(substr($arr['ad_arr'], 0, 13), 1);
    if ($login_banner == 'login_banner') {
        $val = $GLOBALS['smarty']->fetch('library/login_banner.lbi');
    }
    //顶级分类页（家电/食品）幻灯广告 by wu
    $top_style_cate_banner = substr(substr($arr['ad_arr'], 0, 22), 1);
    if ($top_style_cate_banner == 'top_style_elec_banner') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_ad.lbi');
    } elseif ($top_style_cate_banner == 'top_style_food_banner') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_ad.lbi');
    }
    //顶级分类页（家电）底部横幅广告 by wu
    $top_style_cate_row = substr(substr($arr['ad_arr'], 0, 20), 1);
    if ($top_style_cate_row == 'top_style_elec_foot') {
        $val = $GLOBALS['smarty']->fetch('library/top_style_food.lbi');
    }
    //顶级分类页（家电/食品）楼层横幅广告 by wu
    $top_style_cate_row = substr(substr($arr['ad_arr'], 0, 19), 1);
    if ($top_style_cate_row == 'top_style_elec_row') {
        $val = $GLOBALS['smarty']->fetch('library/top_style_food.lbi');
    } elseif ($top_style_cate_row == 'top_style_food_row') {
        $val = $GLOBALS['smarty']->fetch('library/top_style_food.lbi');
    }
    //顶级分类页（家电）品牌广告 by wu
    $top_style_elec_brand = substr(substr($arr['ad_arr'], 0, 21), 1);
    if ($top_style_elec_brand == 'top_style_elec_brand') {
        $val = $GLOBALS['smarty']->fetch('library/top_style_elec_brand.lbi');
    }
    //顶级分类页（家电/食品）楼层左侧广告 by wu
    $top_style_elec_left = substr(substr($arr['ad_arr'], 0, 20), 1);
    if ($top_style_elec_left == 'top_style_elec_left') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_floor_ad.lbi');
    }
    $top_style_food_left = substr(substr($arr['ad_arr'], 0, 20), 1);
    if ($top_style_food_left == 'top_style_food_left') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_floor_ad.lbi');
    }
    //顶级分类页（食品）热门广告 by wu
    $top_style_food_hot = substr(substr($arr['ad_arr'], 0, 19), 1);
    if ($top_style_food_hot == 'top_style_food_hot') {
        $val = $GLOBALS['smarty']->fetch('library/top_style_food_hot.lbi');
    }

    //众筹首页轮播图 by wu
    $zc_index_banner = substr(substr($arr['ad_arr'], 0, 16), 1);
    if ($zc_index_banner == 'zc_index_banner') {
        $val = $GLOBALS['smarty']->fetch('library/zc_index_banner.lbi');
    }

    // 预售首页 大轮播图
    $presale_banner = substr(substr($arr['ad_arr'], 0, 15), 1);
    if ($presale_banner == 'presale_banner') {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner.lbi');
    }

    //预售首页小轮播
    $presale_banner_small = substr(substr($arr['ad_arr'], 0, 21), 1);
    if ($presale_banner_small == 'presale_banner_small') {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_small.lbi');
    }
    //预售首页小轮播  左侧的banner
    $presale_banner_small_left = substr(substr($arr['ad_arr'], 0, 26), 1);
    if ($presale_banner_small_left == 'presale_banner_small_left') {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_small_left.lbi');
    }

    //新闻首页小轮播  左侧的banner
    $news_banner_small_left = substr(substr($arr['ad_arr'], 0, 23), 1);
    if ($news_banner_small_left == 'news_banner_small_left') {
        $val = $GLOBALS['smarty']->fetch('library/news_banner_small_left.lbi');
    }

    //新闻首页小轮播  右侧的banner
    $news_banner_small_right = substr(substr($arr['ad_arr'], 0, 24), 1);
    if ($news_banner_small_right == 'news_banner_small_right') {
        $val = $GLOBALS['smarty']->fetch('library/news_banner_small_right.lbi');
    }

    //预售首页小轮播  右侧的banner
    $presale_banner_small_right = substr(substr($arr['ad_arr'], 0, 27), 1);
    if ($presale_banner_small_right == 'presale_banner_small_right') {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_small_right.lbi');
    }
    //预售 新品页轮播图
    $presale_banner_new = substr(substr($arr['ad_arr'], 0, 19), 1);
    if ($presale_banner_new == 'presale_banner_new') {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_new.lbi');
    }
    //预售 抢先订页 轮播图
    $presale_banner_advance = substr(substr($arr['ad_arr'], 0, 23), 1);
    if ($presale_banner_advance == 'presale_banner_advance') {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_advance.lbi');
    }

    //预售 抢先订页 轮播图
    $presale_banner_category = substr(substr($arr['ad_arr'], 0, 24), 1);
    if ($presale_banner_category == 'presale_banner_category') {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_category.lbi');
    }

    //品牌首页分类下广告by wang
    $brand_cat_ad = substr(substr($arr['ad_arr'], 0, 13), 1);
    if ($brand_cat_ad == 'brand_cat_ad') {
        $val = $GLOBALS['smarty']->fetch('library/brand_cat_ad.lbi');
    }

    //顶级分类页首页幻灯片by wang
    $cat_top_ad = substr(substr($arr['ad_arr'], 0, 11), 1);
    if ($cat_top_ad == 'cat_top_ad') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_ad.lbi');
    }

    //顶级分类页首页新品首发左侧上广告by wang
    $cat_top_new_ad = substr(substr($arr['ad_arr'], 0, 15), 1);

    if ($cat_top_new_ad == 'cat_top_new_ad') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_new_ad.lbi');
    }

    //顶级分类页首页新品首发左侧下广告by wang
    $cat_top_newt_ad = substr(substr($arr['ad_arr'], 0, 16), 1);

    if ($cat_top_newt_ad == 'cat_top_newt_ad') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_newt_ad.lbi');
    }

    //顶级分类页首页楼层左侧广告幻灯片by wang
    $cat_top_floor_ad = substr(substr($arr['ad_arr'], 0, 17), 1);
    if ($cat_top_floor_ad == 'cat_top_floor_ad') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_floor_ad.lbi');
    }

    //首页幻灯片下优惠商品左侧广告by wang
    $cat_top_prom_ad = substr(substr($arr['ad_arr'], 0, 16), 1);
    if ($cat_top_prom_ad == 'cat_top_prom_ad') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_prom_ad.lbi');
    }

    //CMS频道页面左侧广告
    $article_channel_left_ad = substr(substr($arr['ad_arr'], 0, 24), 1);

    if ($article_channel_left_ad == 'article_channel_left_ad') {
        $val = $GLOBALS['smarty']->fetch('library/article_channel_left_ad.lbi');
    }

    //CMS频道页面商城公告下方广告
    $notic_down_ad = substr(substr($arr['ad_arr'], 0, 14), 1);
    if ($notic_down_ad == 'notic_down_ad') {
        $val = $GLOBALS['smarty']->fetch('library/notic_down_ad.lbi');
    }

    //品牌商品页面上方左侧广告
    $brand_list_left_ad = substr(substr($arr['ad_arr'], 0, 19), 1);
    if ($brand_list_left_ad == 'brand_list_left_ad') {
        $val = $GLOBALS['smarty']->fetch('library/brand_list_left_ad.lbi');
    }

    //品牌商品页面上方右侧广告
    $brand_list_right_ad = substr(substr($arr['ad_arr'], 0, 20), 1);
    if ($brand_list_right_ad == 'brand_list_right_ad') {
        $val = $GLOBALS['smarty']->fetch('library/brand_list_right_ad.lbi');
    } elseif ($brand_list_right_ad == 'category_top_banner') {
        $val = $GLOBALS['smarty']->fetch('library/category_top_banner.lbi');
    }

    //搜索商品页面上方左侧广告
    $search_left_ad = substr(substr($arr['ad_arr'], 0, 15), 1);
    if ($search_left_ad == 'search_left_ad') {
        $val = $GLOBALS['smarty']->fetch('library/search_left_ad.lbi');
    }

    //搜索商品页面上方右侧广告
    $search_right_ad = substr(substr($arr['ad_arr'], 0, 16), 1);
    if ($search_right_ad == 'search_right_ad') {
        $val = $GLOBALS['smarty']->fetch('library/search_right_ad.lbi');
    }

    //搜索全部分类页左边广告
    $category_all_left = substr(substr($arr['ad_arr'], 0, 18), 1);
    if ($category_all_left == 'category_all_left') {
        $val = $GLOBALS['smarty']->fetch('library/category_all_left.lbi');
    } elseif ($category_all_left == 'category_top_left') {
        $val = $GLOBALS['smarty']->fetch('library/category_top_left.lbi');
    }

    //搜索全部分类页右边广告
    $category_all_right = substr(substr($arr['ad_arr'], 0, 19), 1);
    if ($category_all_right == 'category_all_right') {
        $val = $GLOBALS['smarty']->fetch('library/category_all_right.lbi');
    }
    /*活动广告图  by kong*/
    $activity_top_banner = substr(substr($arr['ad_arr'], 0, 16), 1);
    if ($activity_top_banner == 'activity_top_ad') {
        $val = $GLOBALS['smarty']->fetch('library/activity_top_ad.lbi');
    }
    /*活动广告图  by kong*/
    $store_street_ad = substr(substr($arr['ad_arr'], 0, 16), 1);
    if ($store_street_ad == 'store_street_ad') {
        $val = $GLOBALS['smarty']->fetch('library/store_street_ad.lbi');
    }
    //品牌首页广告 qin
    $brandn_top_ad = substr(substr($arr['ad_arr'], 0, 14), 1);
    $brandn_left_ad = substr(substr($arr['ad_arr'], 0, 15), 1);
    if ($brandn_top_ad == 'brandn_top_ad') {
        $val = $GLOBALS['smarty']->fetch('library/brandn_top_ad.lbi');
    }
    if ($brandn_left_ad == 'brandn_left_ad') {
        $val = $GLOBALS['smarty']->fetch('library/brandn_left_ad.lbi');
    }


    /*  @author-bylu 优惠券首页顶部轮播广告 start */
    $coupons_index = substr(substr($arr['ad_arr'], 0, 14), 1);

    if ($coupons_index == 'coupons_index') {
        $val = $GLOBALS['smarty']->fetch('library/coupons_index.lbi');
    }
    /*  @author-bylu  end */

    /* 商品分类页 --zhuo start  */
    $category_top_ad = substr(substr($arr['ad_arr'], 0, 16), 1);

    if ($category_top_ad == 'category_top_ad') {
        $val = $GLOBALS['smarty']->fetch('library/category_top_ad.lbi');
    }
    /* 商品分类页 --zhuo end  */

    //新首页模板首页分类广告图 liu
    $recommend_category = substr(substr($arr['ad_arr'], 0, 19), 1);
    if ($recommend_category == 'recommend_category') {
        $val = $GLOBALS['smarty']->fetch('library/index_ad_cat.lbi');
    }

    //新首页模板达人专区广告 liu
    $export_field_ad = substr(substr($arr['ad_arr'], 0, 16), 1);
    if ($export_field_ad == 'expert_field_ad') {
        $val = $GLOBALS['smarty']->fetch('library/expert_field.lbi');
    }

    //新首页模板推荐店铺广告 liu
    $recommend_merchants = substr(substr($arr['ad_arr'], 0, 20), 1);
    if ($recommend_merchants == 'recommend_merchants') {
        $GLOBALS['smarty']->assign('cat_id', $arr['id']);
        $val = $GLOBALS['smarty']->fetch('library/recommend_merchants.lbi');
    }

    //秒杀活动顶部广告 liu
    $seckill_top_ad = substr(substr($arr['ad_arr'], 0, 15), 1);
    if ($seckill_top_ad == 'seckill_top_ad') {
        $val = $GLOBALS['smarty']->fetch('library/seckill_top_ad.lbi');
    }

    //新首页模板楼层左侧广告 liu

    $cat_goods_ad_left = substr(substr($arr['ad_arr'], 0, 18), 1);
    if ($cat_goods_ad_left == 'cat_goods_ad_left') {
        $GLOBALS['smarty']->assign('floor_style_tpl', $arr['floor_style_tpl']);
        $val = $GLOBALS['smarty']->fetch('library/cat_goods_ad_left.lbi');
    }
    //顶级分类页（家电模板）全部分类右侧广告
    $cate_layer_elec_row = substr(substr($arr['ad_arr'], 0, 20), 1);
    if ($cate_layer_elec_row == 'cate_layer_elec_row') {
        $val = $GLOBALS['smarty']->fetch('library/cate_layer_right.lbi');
    }
    //顶级分类页（家电模板）轮播右侧广告
    $top_style_right_banner = substr(substr($arr['ad_arr'], 0, 23), 1);
    if ($top_style_right_banner == 'top_style_right_banner') {
        $val = $GLOBALS['smarty']->fetch('library/cate_layer_right.lbi');
    }
    //顶级分类页（家电模板）品牌左侧广告
    $top_style_elec_brand_left = substr(substr($arr['ad_arr'], 0, 26), 1);
    if ($top_style_elec_brand_left == 'top_style_elec_brand_left') {
        $val = $GLOBALS['smarty']->fetch('library/cate_layer_right.lbi');
    }
    //顶级分类页（女装）楼层右侧广告
    $cat_top_floor_ad_right = substr(substr($arr['ad_arr'], 0, 23), 1);
    if ($cat_top_floor_ad_right == 'cat_top_floor_ad_right') {
        $val = $GLOBALS['smarty']->fetch('library/cat_top_floor_ad_right.lbi');
    }
    //入驻首页头部广告
    $merchants_index_top = substr(substr($arr['ad_arr'], 0, 20), 1);
    if ($merchants_index_top == 'merchants_index_top') {
        $val = $GLOBALS['smarty']->fetch('library/merchants_index_top_ad.lbi');
    }
    //入驻首页类目广告
    $merchants_index_category_ad = substr(substr($arr['ad_arr'], 0, 28), 1);
    if ($merchants_index_category_ad == 'merchants_index_category_ad') {
        if ($arr['id'] > 0) {
            $cat_name = Category::where('parent_id', 0)
                ->where('is_show', 1)
                ->where('cat_id', $arr['id'])
                ->value('cat_name');

            $GLOBALS['smarty']->assign('cat_name', $cat_name);
        }
        $val = $GLOBALS['smarty']->fetch('library/merchants_index_category_ad.lbi');
    }
    //入驻首页成功案例
    $merchants_index_case_ad = substr(substr($arr['ad_arr'], 0, 24), 1);
    if ($merchants_index_case_ad == 'merchants_index_case_ad') {
        $val = $GLOBALS['smarty']->fetch('library/merchants_index_case_ad.lbi');
    }
    //入驻首页成功案例
    $wholesale_ad = substr(substr($arr['ad_arr'], 0, 13), 1);
    if ($wholesale_ad == 'wholesale_ad') {
        $val = $GLOBALS['smarty']->fetch('library/wholesale_ad.lbi');
    }

    $bonushome_ad = substr(substr($arr['ad_arr'], 0, 10), 1);
    if ($bonushome_ad == 'bonushome') {
        if (request()->cookie('bonushome_adv') == 1) {
            $val = '';
        } else {
            cookie()->queue('bonushome_adv', 1, 60 * 10);
            $val = $GLOBALS['smarty']->fetch('library/bonushome_ad.lbi');
        }
    }

    //新首页模板楼层右侧广告 liu
    $cat_goods_ad_right = substr(substr($arr['ad_arr'], 0, 19), 1);
    if ($cat_goods_ad_right == 'cat_goods_ad_right') {
        $GLOBALS['smarty']->assign('floor_style_tpl', $arr['floor_style_tpl']);
        $val = $GLOBALS['smarty']->fetch('library/cat_goods_ad_right.lbi');
    }

    //新模板品牌首页广告 by wu
    $brand_index_ad = substr(substr($arr['ad_arr'], 0, 15), 1);
    if ($brand_index_ad == 'brand_index_ad') {
        $val = $GLOBALS['smarty']->fetch('library/brand_index_ad.lbi');
    }

    //新模板首页楼层 liu
    $category_top_default_brand = substr(substr($arr['ad_arr'], 0, 27), 1);
    if ($category_top_default_brand == 'category_top_default_brand') {
        $val = $GLOBALS['smarty']->fetch('library/category_top_default_brand.lbi');
    }

    //新模板顶级分类页广告 by wu
    $category_top_ad = substr(substr($arr['ad_arr'], 0, 16), 1);
    if ($category_top_ad == 'category_top_default_best_head' || $category_top_ad == 'category_top_default_new_head') {
        $val = $GLOBALS['smarty']->fetch('library/category_top_default_head.lbi');
    } elseif ($category_top_ad == 'category_top_default_best_left' || $category_top_ad == 'category_top_default_new_left') {
        $val = $GLOBALS['smarty']->fetch('library/category_top_default_left.lbi');
    }

    $merchants_index = substr(substr($arr['ad_arr'], 0, 20), 1);
    if ($merchants_index == 'merchants_index') {
        $val = $GLOBALS['smarty']->fetch('library/category_top_banner.lbi');
    }

    $merchants_index_flow = substr(substr($arr['ad_arr'], 0, 21), 1);
    if ($merchants_index_flow == 'merchants_index_flow') {
        $val = $GLOBALS['smarty']->fetch('library/merchants_index_flow.lbi');
    }

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 首页轮播图右侧登录入口
 */
function insert_index_user_info()
{

    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $login_right = ShopConfig::where('code', 'login_right')->value('value');
    $login_right_link = ShopConfig::where('code', 'login_right_link')->value('value');

    $GLOBALS['smarty']->assign('login_right_link', $login_right_link);
    $GLOBALS['smarty']->assign('login_right', $login_right);
    $GLOBALS['smarty']->assign('user_id', session('user_id'));
    $GLOBALS['smarty']->assign('info', app(UserCommonService::class)->getUserDefault(session('user_id')));
    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['index_article_cat']);

    //首页文章栏目
    if (!empty($GLOBALS['_CFG']['index_article_cat'])) {
        $index_article_cat = [];
        $index_article_cat_arr = explode(',', $GLOBALS['_CFG']['index_article_cat']);

        foreach ($index_article_cat_arr as $key => $val) {
            $index_article_cat[] = app(ArticleService::class)->getAssignArticles($val, 3);
        }

        $GLOBALS['smarty']->assign('index_article_cat', $index_article_cat);
    }

    $val = $GLOBALS['smarty']->fetch('library/index_user_info.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 批发轮播图右侧登录入口
 */
function insert_business_user_info()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $GLOBALS['smarty']->assign('user_id', session('user_id'));
    $GLOBALS['smarty']->assign('info', app(UserCommonService::class)->getUserDefault(session('user_id')));
    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['index_article_cat']);

    //批发首页文章栏目
    if (!empty($GLOBALS['_CFG']['wholesale_article_cat'])) {
        $wholesale_article_cat = [];
        $wholesale_article_cat_arr = explode(',', $GLOBALS['_CFG']['wholesale_article_cat']);

        foreach ($wholesale_article_cat_arr as $key => $val) {
            $wholesale_article_cat[] = app(ArticleService::class)->getAssignArticles($val, 3);
        }

        $GLOBALS['smarty']->assign('wholesale_article_cat', $wholesale_article_cat);
    }

    $val = $GLOBALS['smarty']->fetch('library/business_user_info.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 左侧分类树导航
 * 新模板 ecmoban_dsc2017
 */
function insert_category_tree_nav($arr = [])
{
    $nav_cat_model = isset($arr['cat_model']) && !empty($arr['cat_model']) ? addslashes($arr['cat_model']) : '';
    $nav_cat_num = isset($arr['cat_num']) && !empty($arr['cat_num']) ? intval($arr['cat_num']) : 0;

    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $categories_pro = get_category_tree_leve_one();
    $GLOBALS['smarty']->assign('categories_pro', $categories_pro); // 分类树加强版

    $GLOBALS['smarty']->assign('nav_cat_model', $nav_cat_model);
    $GLOBALS['smarty']->assign('nav_cat_num', $nav_cat_num);

    $val = $GLOBALS['smarty']->fetch('library/category_tree_nav.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 首页悬浮登录入口
 * by yanxin
 */
function insert_index_suspend_info()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $GLOBALS['smarty']->assign('user_id', session('user_id'));
    $GLOBALS['smarty']->assign('info', app(UserCommonService::class)->getUserDefault(session('user_id')));

    $val = $GLOBALS['smarty']->fetch('library/index_suspend_info.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 首页秒杀活动
 *
 * @param array $seckillid
 * @param string $temp
 * @return bool
 */
function insert_index_seckill_goods($seckillid = [], $temp = '')
{
    return app(SeckillInsertService::class)->insertIndexSeckillGoods($seckillid, $temp);
}

/**
 * 网站左侧浮动框内容
 */
function insert_user_menu_position()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $rank = get_rank_info();
    if ($rank) {
        $GLOBALS['smarty']->assign('rank_name', $rank['rank_name']);
    }

    $GLOBALS['smarty']->assign('info', app(UserCommonService::class)->getUserDefault(session('user_id')));

    $cart_info = insert_cart_info(1);
    $GLOBALS['smarty']->assign('cart_info', $cart_info);

    $val = $GLOBALS['smarty']->fetch('library/user_menu_position.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 商品详情页讨论圈title
 */
function insert_goods_comment_title($arr)
{
    $arr['goods_id'] = isset($arr['goods_id']) && !empty($arr['goods_id']) ? intval($arr['goods_id']) : 0;

    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $goods_id = $arr['goods_id'];
    $comment_allCount = app(CommentService::class)->getGoodsCommentCount($goods_id);
    $comment_good = app(CommentService::class)->getGoodsCommentCount($goods_id, 1);
    $comment_middle = app(CommentService::class)->getGoodsCommentCount($goods_id, 2);
    $comment_short = app(CommentService::class)->getGoodsCommentCount($goods_id, 3);

    $GLOBALS['smarty']->assign('comment_allCount', $comment_allCount);
    $GLOBALS['smarty']->assign('comment_good', $comment_good);
    $GLOBALS['smarty']->assign('comment_middle', $comment_middle);
    $GLOBALS['smarty']->assign('comment_short', $comment_short);

    $val = $GLOBALS['smarty']->fetch('library/goods_comment_title.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 商品详情页讨论圈title
 */
function insert_goods_discuss_title($arr)
{
    $arr['goods_id'] = isset($arr['goods_id']) && !empty($arr['goods_id']) ? intval($arr['goods_id']) : 0;

    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $goods_id = $arr['goods_id'];
    $all_count = get_discuss_type_count($goods_id); //帖子总数
    $t_count = get_discuss_type_count($goods_id, 1); //讨论帖总数
    $w_count = get_discuss_type_count($goods_id, 2); //问答帖总数
    $q_count = get_discuss_type_count($goods_id, 3); //圈子帖总数
    $s_count = get_commentImg_count($goods_id); //晒单帖总数

    $all_count += $s_count;//总数加上晒单贴的总数

    $GLOBALS['smarty']->assign('all_count', $all_count);
    $GLOBALS['smarty']->assign('t_count', $t_count);
    $GLOBALS['smarty']->assign('w_count', $w_count);
    $GLOBALS['smarty']->assign('q_count', $q_count);
    $GLOBALS['smarty']->assign('s_count', $s_count);

    $val = $GLOBALS['smarty']->fetch('library/goods_discuss_title.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 获得推荐品牌信息
 *
 * @param $arr
 * @param string $brand_id
 * @return array
 */
function insert_recommend_brands($arr = [], $brand_id = '')
{
    return app(UserInsertService::class)->insertRecommendBrands($arr, $brand_id);
}

/**
 * 随机关键字
 *
 * @return string
 */
function insert_rand_keyword()
{
    $searchkeywords = explode(',', trim($GLOBALS['_CFG']['search_keywords']));
    if (count($searchkeywords) > 0) {
        return $searchkeywords[rand(0, count($searchkeywords) - 1)];
    } else {
        return '';
    }
}

//获得楼层设置内容by wang
function insert_get_floor_content($arr)
{
    $filename = !empty($arr['filename']) ? addslashes(trim($arr['filename'])) : '0';
    $region = !empty($arr['region']) ? addslashes(trim($arr['region'])) : '0';
    $id = !empty($arr['id']) ? intval($arr['id']) : '0';
    $field = !empty($arr['field']) ? addslashes(trim($arr['field'])) : 'brand_id';
    $theme = $GLOBALS['_CFG']['template'];

    $res = FloorContent::select($field)
        ->where('filename', $filename)
        ->where('region', $region)
        ->where('id', $id)
        ->where('theme', $theme);

    $res = app(BaseRepository::class)->getToArrayGet($res);
    $field = app(BaseRepository::class)->getKeyPluck($res, $field);

    return $field;
}

/**
 * 调用浏览历史 //ecmoban模板堂 --zhuo
 *
 * @access  public
 * @return  string
 */
function insert_history_goods($parameter)
{
    $warehouse_id = isset($parameter['warehouse_id']) && !empty($parameter['warehouse_id']) ? intval($parameter['warehouse_id']) : 0;
    $goods_id = isset($parameter['goods_id']) && !empty($parameter['goods_id']) ? intval($parameter['goods_id']) : 0;
    $area_id = isset($parameter['area_id']) && !empty($parameter['area_id']) ? intval($parameter['area_id']) : 0;
    $area_city = isset($parameter['area_city']) && !empty($parameter['area_city']) ? intval($parameter['area_city']) : 0;

    if (empty($warehouse_id)) {
        $warehouse_id = app(AreaService::class)->getGoodsSelectWarehouse();
    }

    $ecsCookie = request()->cookie('ECS');
    if (isset($ecsCookie['history']) && !empty($ecsCookie['history'])) {
        $history = !is_array($ecsCookie['history']) ? explode(",", $ecsCookie['history']) : $ecsCookie['history'];

        /* 查询商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->whereIn('goods_id', $history);

        if ($goods_id > 0) {
            $res = $res->where('goods_id', '<>', $goods_id);
        }

        if ($GLOBALS['_CFG']['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = app(DscRepository::class)->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
        ];

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

        $res = $res->orderByRaw("INSTR('" . $ecsCookie['history'] . "', goods_id)");

        $res = $res->take(10);

        $res = app(BaseRepository::class)->getToArrayGet($res);

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

                $price = app(GoodsCommonService::class)->getGoodsPrice($price, session('discount'), $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $res[$key] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $res[$key]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                    app(DscRepository::class)->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                $res[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
                $res[$key]['goods_img'] = get_image_path($row['goods_img']);
                $res[$key]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $res[$key]['shop_name'] = app(MerchantCommonService::class)->getShopName($row['user_id'], 1); //店铺名称
                $res[$key]['shopUrl'] = app(DscRepository::class)->buildUri('merchants_store', ['urid' => $row['user_id']]);

                $res[$key]['market_price'] = price_format($row['market_price']);
                $res[$key]['shop_price'] = price_format($row['shop_price']);
                $res[$key]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            }
        }
    }

    $GLOBALS['smarty']->assign('history_goods', $res);
    $val = $GLOBALS['smarty']->fetch('library/history_goods.lbi');

    return $val;
}

//调用浏览记录 by wu
function insert_history_goods_pro()
{
    $history_goods = get_history_goods(0, $GLOBALS['region_id'], $GLOBALS['area_id'], $GLOBALS['area_city']);
    $history_count = [];
    if ($history_goods) {
        for ($i = 0; $i < count($history_goods) / 6; $i++) {
            //$history_count[$i]=$i; 修改浏览记录 by wu
            for ($j = 0; $j < 6; $j++) {
                if (pos($history_goods)) {
                    $history_count[$i][] = pos($history_goods);
                    next($history_goods);
                } else {
                    break;
                }
            }
        }
    }

    $GLOBALS['smarty']->assign('history_count', $history_count);
    $GLOBALS['smarty']->assign('history_goods', $history_goods);

    $val = $GLOBALS['smarty']->fetch('library/cate_top_history_goods.lbi');
    return $val;
}

//众筹支持者列表 by wu
function get_backer_list($zcid = 0, $page = 1, $size = 10)
{
    $zcid = !empty($zcid) ? intval($zcid) : 0;
    $page = !empty($page) ? intval($page) : 0;
    $size = !empty($size) ? intval($size) : 0;

    $GLOBALS['smarty']->assign('zcid', $zcid);

    $start = (($page - 1) * $size);

    /* 支持者数量 */
    $record_count = ZcProject::where('id', $zcid)->value('join_num');

    /* 支持者列表 */
    $backer_list = OrderInfo::select('user_id', 'zc_goods_id')->where('is_zc_order', 1)->where('pay_status', PS_PAYED);

    $backer_list = $backer_list->whereHas('getZcGoods', function ($query) use ($zcid) {
        $query->whereHas('getZcProject', function ($query) use ($zcid) {
            $query->where('id', $zcid);
        });
    });

    $backer_list = $backer_list->with([
        'getZcGoods' => function ($query) {
            $query->select('id', 'price');
        },
        'getUsers' => function ($query) {
            $query->select('user_id', 'user_name', 'user_picture');
        }
    ]);

    $backer_list = $backer_list->orderBy('order_id', 'desc');

    if ($start > 0) {
        $backer_list = $backer_list->skip($start);
    }

    if ($size > 0) {
        $backer_list = $backer_list->take($size);
    }

    $backer_list = app(BaseRepository::class)->getToArrayGet($backer_list);

    if ($backer_list) {
        foreach ($backer_list as $key => $val) {
            $val['price'] = $val['get_zc_goods'] ? $val['get_zc_goods']['price'] : 0;
            $val['user_name'] = $val['get_users'] ? $val['get_users']['user_name'] : '';

            $backer_list[$key]['user_picture'] = $val['get_users'] ? $val['get_users']['user_picture'] : '';

            //用户名匿名
            $backer_list[$key]['user_name'] = setAnonymous($val['user_name']);

            //格式化价格
            $backer_list[$key]['formated_price'] = price_format($val['price']);

            //支持数量
            $backer_list[$key]['back_num'] = OrderInfo::where('user_id', $val['user_id'])->where('is_zc_order', 1)->count();
        }
    }

    $GLOBALS['smarty']->assign('backer_list', $backer_list);

    //页面跳转信息
    $GLOBALS['smarty']->assign('curr_page', $page); //当前页
    $GLOBALS['smarty']->assign('prev_page', $page - 1);
    $GLOBALS['smarty']->assign('next_page', $page + 1);
    $GLOBALS['smarty']->assign('third_page', $page + 2);
    $pager = get_pager('', ['act' => 'list'], $record_count, $page, $size);
    $GLOBALS['smarty']->assign('pager', $pager);

    $html = $GLOBALS['smarty']->fetch('library/zc_backer_list.lbi');
    return $html;
}

//众筹话题列表 by wu
function get_topic_list($zcid = 0, $page = 1, $size = 10)
{
    $zcid = !empty($zcid) ? intval($zcid) : 0;
    $page = !empty($page) ? intval($page) : 0;
    $size = !empty($size) ? intval($size) : 0;

    $GLOBALS['smarty']->assign('zcid', $zcid);

    $start = (($page - 1) * $size);

    //总数量
    $record_count = ZcTopic::where('pid', $zcid)->where('parent_topic_id', 0)->where('topic_status', 1)->count();

    //话题列表
    $topic_list = ZcTopic::where('pid', $zcid)->where('parent_topic_id', 0)->where('topic_status', 1);

    $topic_list = $topic_list->orderBy('topic_id', 'desc');

    if ($start > 0) {
        $topic_list = $topic_list->skip($start);
    }

    if ($size > 0) {
        $topic_list = $topic_list->take($size);
    }

    $topic_list = app(BaseRepository::class)->getToArrayGet($topic_list);

    //补充信息
    if ($topic_list) {
        foreach ($topic_list as $key => $val) {
            //用户名、头像
            $user_info = Users::select(['user_name', 'user_picture'])->where('user_id', $val['user_id']);
            $user_info = app(BaseRepository::class)->getToArrayFirst($user_info);

            if ($user_info) {
                $topic_list[$key]['user_name'] = setAnonymous($user_info['user_name']);
                $topic_list[$key]['user_picture'] = $user_info['user_picture'];
            }

            //时间的处理
            $topic_list[$key]['time_past'] = get_time_past($val['add_time'], gmtime());

            //子评论列表
            $child_topic = ZcTopic::where('parent_topic_id', $val['topic_id'])->where('topic_status', 1)
                ->orderBy('topic_id', 'desc')
                ->take(5);
            $child_topic = app(BaseRepository::class)->getToArrayGet($child_topic);

            if ($child_topic) {
                foreach ($child_topic as $k => $v) {
                    $child_user_info = Users::select(['user_name', 'user_picture'])->where('user_id', $v['user_id']);
                    $child_user_info = app(BaseRepository::class)->getToArrayFirst($child_user_info);

                    $child_topic[$k]['user_name'] = setAnonymous($child_user_info['user_name']);
                    $child_topic[$k]['user_picture'] = $child_user_info['user_picture'];
                    $child_topic[$k]['time_past'] = get_time_past($v['add_time'], gmtime());

                    //回复对象
                    if ($v['reply_topic_id'] > 0) {
                        $reply_user_info = ZcTopic::where('topic_id', $v['reply_topic_id'])->where('topic_status', 1);
                        $reply_user_info = $reply_user_info->whereHas('getUsers');
                        $reply_user_info = $reply_user_info->with(['getUsers']);
                        $reply_user_info = app(BaseRepository::class)->getToArrayFirst($reply_user_info);

                        if ($reply_user_info && $reply_user_info['get_users']) {
                            $reply_user_info = array_merge($reply_user_info, $reply_user_info['get_users']);
                        }

                        $child_topic[$k]['reply_user'] = isset($reply_user_info['user_name']) ? setAnonymous($reply_user_info['user_name']) : '';
                    }
                }
            }
            $topic_list[$key]['child_topic'] = $child_topic;

            //子评论数量
            $child_topic_num = ZcTopic::where('parent_topic_id', $val['topic_id'])->where('topic_status', 1)->count();
            $topic_list[$key]['child_topic_num'] = $child_topic_num;
        }
    }

    $GLOBALS['smarty']->assign('topic_list', $topic_list);

    //页面跳转信息
    $GLOBALS['smarty']->assign('curr_page', $page); //当前页
    $GLOBALS['smarty']->assign('prev_page', $page - 1);
    $GLOBALS['smarty']->assign('next_page', $page + 1);
    $GLOBALS['smarty']->assign('third_page', $page + 2);
    $pager = get_pager('', ['act' => 'list'], $record_count, $page, $size);
    $GLOBALS['smarty']->assign('pager', $pager);

    $html = $GLOBALS['smarty']->fetch('library/zc_topic_list.lbi');
    return $html;
}

/* 会员中心语言函数 */
function insert_get_page_no_records($arr)
{
    if (isset($GLOBALS['_LANG'][$arr['filename']][$arr['act']]['no_records'])) {
        return $GLOBALS['_LANG'][$arr['filename']][$arr['act']]['no_records'];
    } else {
        return lang('user.no_records');
    }
}

/**
 * 调用商品地区信息
 *
 * @access  public
 * @return  string
 */
function insert_goods_delivery_area_js($arr)
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $area = [
        'goods_id' => $arr['area']['goods_id'],
        'region_id' => $arr['area']['region_id'],
        'province_id' => $arr['area']['province_id'],
        'city_id' => $arr['area']['city_id'],
        'district_id' => $arr['area']['district_id'],
        'street_id' => $arr['area']['street_id'],
        'street_list' => $arr['area']['street_list'],
        'merchant_id' => $arr['area']['merchant_id'],
        'user_id' => $arr['area']['user_id'],
        'area_id' => $arr['area']['area_id'],
        'area_city' => $arr['area']['area_city']
    ];

    $area_cache_name = app(AreaService::class)->getCacheName('area_cookie');
    $area_cookie = cache($area_cache_name);
    $area_cookie = !is_null($area_cookie) ? $area_cookie : [];

    $cookie_area['province'] = $area_cookie['province'] ?? 0;
    $cookie_area['city'] = $area_cookie['city_id'] ?? 0;
    $cookie_area['district'] = $area_cookie['district'] ?? 0;
    $cookie_area['street'] = $area_cookie['street'] ?? 0;
    $cookie_area['street_list'] = $area_cookie['street_list'] ?? 0;

    $area['region_id'] = request()->hasCookie('flow_region') && !empty(request()->cookie('flow_region')) ? request()->cookie('flow_region') : $area['region_id'];
    $area['province_id'] = isset($cookie_area['province']) && !empty($cookie_area['province']) ? $cookie_area['province'] : $area['province_id'];
    $area['city_id'] = isset($cookie_area['city']) && !empty($cookie_area['city']) ? $cookie_area['city'] : $area['city_id'];
    $area['district_id'] = isset($cookie_area['district']) && !empty($cookie_area['district']) ? $cookie_area['district'] : $area['district_id'];
    $area['street_id'] = isset($cookie_area['street']) && !empty($cookie_area['street']) ? $cookie_area['street'] : $area['street_id'];
    $area['street_list'] = isset($cookie_area['street_list']) && !empty($cookie_area['street_list']) ? $cookie_area['street_list'] : $area['street_list'];

    $GLOBALS['smarty']->assign('area', $area);
    $val = $GLOBALS['smarty']->fetch('library/goods_delivery_area_js.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 调用批发购物车信息
 */

function insert_wholesale_cart_info()
{
    $user_id = session('user_id', 0);
    $session_id = app(SessionRepository::class)->realCartMacIp();

    $where = [
        'user_id' => $user_id,
        'session_id' => $session_id
    ];

    $supplierEnabled = app(CommonRepository::class)->judgeSupplierEnabled();

    //获取商品
    if ($supplierEnabled) {
        $row = WholesaleCart::whereRaw(1);

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $row = $row->where('session_id', $session_id);
        }

        $row = $row->with([
            'getWholesale' => function ($query) {
                $query->select('goods_id', 'goods_name', 'goods_thumb', 'cat_id');
            }
        ]);
    } else {
        $row = Goods::select('goods_id', 'goods_thumb')
            ->whereHas('getWholesaleCart', function ($query) use ($where) {
                if (!empty($where['user_id'])) {
                    $query->where('user_id', $where['user_id']);
                } else {
                    $query->where('session_id', $where['session_id']);
                }
            });

        $row = $row->with([
            'getWholesaleCart',
            'getWholesale' => function ($query) {
                $query->select('goods_id', 'goods_name', 'goods_thumb', 'cat_id');
            }
        ]);
    }

    $row = app(BaseRepository::class)->getToArrayGet($row);

    $arr = [];
    $cart_value = '';
    if ($row) {
        foreach ($row as $k => $v) {
            $v = isset($v['get_wholesale_cart']) ? app(BaseRepository::class)->getArrayMerge($v, $v['get_wholesale_cart']) : $v;
            $v = isset($v['get_wholesale']) ? app(BaseRepository::class)->getArrayMerge($v, $v['get_wholesale']) : $v;

            $arr[$k] = $v;

            $arr[$k]['rec_id'] = $v['rec_id'];
            $arr[$k]['url'] = app(DscRepository::class)->buildUri('wholesale_goods', ['aid' => $v['goods_id']], $v['goods_name']);
            $arr[$k]['goods_thumb'] = isset($v['goods_thumb']) && !empty($v['goods_thumb']) ? get_image_path($v['goods_thumb']) : '';
            $arr[$k]['goods_number'] = $v['goods_number'];
            $arr[$k]['goods_price'] = $v['goods_price'];
            $arr[$k]['goods_name'] = $v['goods_name'];

            $attr_array = get_wholesale_attr_array($v['goods_attr_id']);
            $arr[$k]['goods_attr'] = $attr_array ? array_values($attr_array) : [];
        }

        $cart_value = app(BaseRepository::class)->getKeyPluck($arr, 'rec_id');
        $cart_value = app(BaseRepository::class)->getImplode($cart_value);
    }

    $row = WholesaleCart::selectRaw("COUNT(*) AS cart_number, SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount")
        ->whereRaw(1);

    if (!empty($where['user_id'])) {
        $row = $row->where('user_id', $user_id);
    } else {
        $row = $row->where('session_id', $session_id);
    }

    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if ($row) {
        $cart_number = intval($row['cart_number']);
        $number = intval($row['number']);
        $amount = price_format(floatval($row['amount']));
    } else {
        $cart_number = 0;
        $number = 0;
        $amount = 0;
    }

    $GLOBALS['smarty']->assign('cart_value', $cart_value);
    $GLOBALS['smarty']->assign('number', $number);
    $GLOBALS['smarty']->assign('amount', $amount);
    $GLOBALS['smarty']->assign('str', $cart_number);
    $GLOBALS['smarty']->assign('goods', $arr);

    $output = $GLOBALS['smarty']->fetch('library/wholesale_cart_info.lbi');
    return $output;
}

/**
 * 调用批发购物车加减返回信息
 *
 * @access  public
 * @return  string
 */
function insert_wholesale_flow_info($goods_price)
{
    $GLOBALS['smarty']->assign('goods_price', $goods_price);

    $output = $GLOBALS['smarty']->fetch('library/wholesale_flow_info.lbi');
    return $output;
}

//by wang 随机关键字
function insert_wholesale_rand_keyword()
{
    $searchkeywords = explode(',', trim($GLOBALS['_CFG']['wholesale_search_keywords']));
    if (count($searchkeywords) > 0) {
        return $searchkeywords[rand(0, count($searchkeywords) - 1)];
    } else {
        return '';
    }
}

/*
 * 处理属性，返回数组
 * goods_attr_id 字符串，如 1,2,3
 * 返回数组
 */
function get_wholesale_attr_array($goods_attr_id = '')
{
    if (empty($goods_attr_id)) {
        return [];
    }

    $goods_attr_id = app(BaseRepository::class)->getExplode($goods_attr_id);

    $res = WholesaleGoodsAttr::select('goods_attr_id', 'attr_id', 'attr_value')
        ->whereIn('goods_attr_id', $goods_attr_id);
    $res = $res->with([
        'getGoodsAttribute' => function ($query) {
            $query->select('attr_id', 'sort_order');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods_attribute']);

            $res[$key] = $row;
        }

        $res = app(BaseRepository::class)->getSortBy($res, 'sort_order');
    }

    return $res;
}


/**
 * 获取头部城市筛选模块
 *
 * @return mixed
 */
function insert_header_region()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $region_list = get_header_region();
    $GLOBALS['smarty']->assign('region_list', $region_list);

    $pin_region_list = app(BaseRepository::class)->getDiskForeverData('forever_pin_regions');

    if ($pin_region_list === false) {
        $pin_region_list = [];
    }

    $GLOBALS['smarty']->assign('pin_region_list', $pin_region_list);

    $GLOBALS['smarty']->assign('site_domain', url('/') . '/');
    $val = $GLOBALS['smarty']->fetch('library/header_region_style.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 获取头部推荐地区
 *
 * @return array
 */
function get_header_region()
{
    $arr = [];
    if (isset($GLOBALS['_CFG']['header_region']) && $GLOBALS['_CFG']['header_region']) {
        $header_region = app(BaseRepository::class)->getExplode($GLOBALS['_CFG']['header_region']);
        $header_region = app(BaseRepository::class)->getArrayUnique($header_region);

        $row = Region::whereIn('region_id', $header_region);
        $row = app(BaseRepository::class)->getToArrayGet($row);

        if ($row) {
            foreach ($row as $key => $val) {
                $arr[$key]['region_id'] = $val['region_id'];
                $arr[$key]['region_name'] = $val['region_name'];
            }
        }
    }

    return $arr;
}
