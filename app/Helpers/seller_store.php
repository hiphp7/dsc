<?php

use App\Models\Goods;
use App\Models\SellerShopbg;
use App\Models\SellerShopheader;
use App\Models\SellerShopslide;
use App\Models\SellerShopwindow;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsCommonService;

//获得店铺头部设置
function get_store_header($merchant_id, $store_theme)
{
    $shopheader = SellerShopheader::where('ru_id', $merchant_id)
        ->where('seller_theme', $store_theme);

    $shopheader = app(BaseRepository::class)->getToArrayFirst($shopheader);

    if ($shopheader) {
        if ($shopheader['content']) {
            $content = $shopheader['content'];
            if ($content == '<p><br/></p>') {
                $content = '';
            }

            $content = htmlspecialchars_decode($content);
            $shopheader['content'] = $content;
        }

        if (!empty($shopheader['headbg_img'])) {
            $shopheader['headbg_img'] = str_replace("../", "", $shopheader['headbg_img']);
            $shopheader['headbg_img'] = get_image_path($shopheader['headbg_img']);
        }
    }

    return $shopheader;
}

//幻灯片轮播图
function get_store_banner_list($ru_id = 0, $store_theme)
{
    $res = SellerShopslide::where('ru_id', $ru_id)
        ->where('is_show', 1)
        ->where('seller_theme', $store_theme);

    $res = $res->orderBy('img_order');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $key += 1;

            $arr[$key]['img_url'] = str_replace("../", "", $row['img_url']);
            $arr[$key]['img_url'] = get_image_path($arr[$key]['img_url']);
            $arr[$key]['img_link'] = $row['img_link'];
            $arr[$key]['slide_type'] = $row['slide_type'];
        }
    }

    return $arr;
}

//店铺橱窗
function get_store_win_list($ru_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $seller_theme)
{
    $res = SellerShopwindow::where('ru_id', $ru_id)
        ->where('is_show', 1)
        ->where('seller_theme', $seller_theme)
        ->orderBy('win_order');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$key]['win_type'] = $row['win_type'];
            $arr[$key]['win_color'] = $row['win_color'];
            $arr[$key]['win_name'] = $row['win_name'];
            $arr[$key]['win_order'] = $row['win_order'];

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $bucket_info = app(DscRepository::class)->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            if ($row['win_custom']) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $row['win_custom'], 'win_custom');
                $row['win_custom'] = $desc_preg['win_custom'];
            }

            $arr[$key]['win_custom'] = htmlspecialchars_decode($row['win_custom']);
            $arr[$key]['win_goods_type'] = $row['win_goods_type'];
            if (!empty($row['win_goods'])) {
                $arr[$key]['goods_list'] = get_win_goods_list($ru_id, $row['win_goods'], $warehouse_id, $area_id, $area_city);
            }
        }
    }

    return $arr;
}

//橱窗商品列表
function get_win_goods_list($ru_id, $win_goods = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
{
    $res = Goods::where('is_on_sale', 1)
        ->where('is_alone_sale', 1)
        ->where('is_delete', 0)
        ->where('user_id', $ru_id);

    if ($win_goods) {
        $win_goods = app(BaseRepository::class)->getExplode($win_goods);
        $win_goods = array_unique($win_goods);

        $res = $res->whereIn('goods_id', $win_goods);
    }

    if ($GLOBALS['_CFG']['review_goods']) {
        $res = $res->where('review_status', '>', 2);
    }

    $res = app(DscRepository::class)->getAreaLinkGoods($res, $area_id, $area_city);

    $user_rank = session('user_rank');
    $res = $res->with([
        'getMemberPrice' => function ($query) use ($user_rank) {
            $query->where('user_rank', $user_rank);
        },
        'getWarehouseGoods' => function ($query) use ($warehouse_id) {
            $query->where('region_id', $warehouse_id);
        },
        'getWarehouseAreaGoods' => function ($query) use ($area_id) {
            $query->where('region_id', $area_id);
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];

    if ($res) {
        foreach ($res as $key => $row) {
            $key += 1;

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

            if ($row['promote_price'] > 0) {
                $promote_price = app(GoodsCommonService::class)->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $arr[$key]['goods_id'] = $row['goods_id'];
            $arr[$key]['goods_name'] = $row['goods_name'];
            $arr[$key]['market_price'] = price_format($row['market_price']);
            $arr[$key]['shop_price'] = price_format($row['shop_price']);
            $arr[$key]['type'] = $row['goods_type'];
            $arr[$key]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $arr[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
            $arr[$key]['goods_img'] = get_image_path($row['goods_img']);
            $arr[$key]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
        }
    }

    return $arr;
}

//店铺背景
function get_store_bg($merchant_id, $seller_theme)
{
    $res = SellerShopbg::where('ru_id', $merchant_id)
        ->where('seller_theme', $seller_theme);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    if ($res) {
        $res['bgimg'] = get_image_path($res['bgimg']);
    }

    return $res;
}

//店铺分类
function get_merchant_cat($ru_id)
{
    $shopMain_category = get_seller_mainshop_cat($ru_id);
    $cat_list = get_category_child_tree($shopMain_category, $ru_id, 1);

    return $cat_list;
}
