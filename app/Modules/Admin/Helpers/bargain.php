<?php

use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;

/**
 * 砍价活动商品详情
 */
function get_bargain_goods_info($bargain_id = 0)
{
    $time = gmtime();
    $where = " g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.review_status>2 and bg.status != 1 and bg.is_delete !=1 and bg.is_audit = 2 and $time > bg.start_time and $time < bg.end_time ";

    $sql = 'SELECT bg.id,bg.bargain_name,bg.goods_id,bg.goods_price,bg.start_time,bg.end_time,bg.target_price,bg.total_num,bg.bargain_desc,g.user_id,g.goods_sn, g.goods_name,g.is_real,g.is_shipping, g.shop_price, g.market_price,g.goods_thumb, g.goods_img,g.goods_number,g.goods_type,g.goods_brief,g.model_attr,g.review_status,g.cloud_id FROM ' . $GLOBALS['dsc']->table('bargain_goods') . 'AS bg LEFT JOIN ' . $GLOBALS['dsc']->table('goods') . ' AS g ON bg.goods_id = g.goods_id ' .
        "WHERE $where and bg.id = $bargain_id";

    $bargain = $GLOBALS['db']->getRow($sql);

    if ($bargain) {

        $seller = app(MerchantCommonService::class)->getShopName($bargain['user_id'], 3);

        $bargain['goods_img'] = get_image_path($bargain['goods_img']);
        $bargain['goods_thumb'] = get_image_path($bargain['goods_thumb']);
        $bargain['rz_shopName'] = $seller['shop_name'];
        $bargain['store_url'] = route('store/index/shop_info', array('id' => $bargain['user_id']));//店铺连接
        $bargain['shopinfo'] = $seller['shopinfo'];
        $bargain['shopinfo']['logo_thumb'] = get_image_path(str_replace('../', '', $bargain['shopinfo']['logo_thumb']));
        $bargain['shopinfo']['brand_thumb'] = get_image_path($bargain['shopinfo']['brand_thumb']);
    }

    return $bargain;
}


/**
 * 验证砍价活动是否结束
 */
function bargain_is_failure($bargain_id = 0)
{
    $sql = 'SELECT start_time,end_time,status FROM ' . $GLOBALS['dsc']->table('bargain_goods') . " WHERE id = $bargain_id";
    $bargain = $GLOBALS['db']->getRow($sql);
    return $bargain;
}

/**
 * 验证是否参与当前活动
 */
function is_add_bargain($bargain_id = 0, $user_id = 0)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['dsc']->table('bargain_statistics_log') . " WHERE bargain_id = $bargain_id and user_id =$user_id and status != 1 ";
    $bargain = $GLOBALS['db']->getRow($sql);
    return $bargain;
}

/**
 * 验证是否砍价
 */
function is_bargain_join($bs_id = 0, $user_id = 0)
{
    $sql = 'SELECT user_id,subtract_price FROM ' . $GLOBALS['dsc']->table('bargain_statistics') . " WHERE bs_id = $bs_id and user_id =$user_id ";
    $bargain = $GLOBALS['db']->getRow($sql);
    if ($bargain) {
        //用户名、头像
        $user_nick = app(UserCommonService::class)->getUserDefault($bargain['user_id']);
        $bargain['user_name'] = $user_nick['nick_name'];
        $bargain['headerimg'] = $user_nick['user_picture'];
        $bargain['subtract_price'] = price_format($bargain['subtract_price']);
    }
    return $bargain;
}


/**
 * //亲友帮列表
 */
function get_bargain_statistics($bs_id = 0)
{
    $sql = 'SELECT user_id ,add_time,subtract_price FROM ' . $GLOBALS['dsc']->table('bargain_statistics') . " WHERE bs_id = $bs_id order by add_time desc ";

    $bargain_list = $GLOBALS['db']->getAll($sql);
    foreach ($bargain_list as $key => $val) {
        //用户名、头像
        $user_nick = app(UserCommonService::class)->getUserDefault($val['user_id']);
        $arr[$key]['user_name'] = encrypt_username($user_nick['nick_name']);
        $arr[$key]['headerimg'] = $user_nick['user_picture'];
        $arr[$key]['subtract_price'] = price_format($val['subtract_price']);
        $arr[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
    }
    return $arr;
}

/**
 * 曲线图
 */
function get_bargain_graph_list($bs_id = 0)
{
    $sql = 'SELECT subtract_price FROM ' . $GLOBALS['dsc']->table('bargain_statistics') . " WHERE bs_id = $bs_id order by add_time desc limit 0,10 ";

    $graph_list = $GLOBALS['db']->getAll($sql);
    $str = '';
    foreach ($graph_list as $key => $val) {
        $str .= $val['subtract_price'] . ',';
    }
    $graph = substr($str, 0, -1);

    return $graph;
}


/**
 * //砍价排行榜
 */
function get_bargain_goods_ranking($bargain_id = 0)
{
    $sql = "SELECT bsl.user_id ,
                IFNULL((select sum(subtract_price) from {pre}bargain_statistics where bs_id = bsl.id),0) as money
                FROM {pre}bargain_statistics_log as bsl
                LEFT JOIN {pre}bargain_statistics as bs on bsl.id = bs.bs_id
                WHERE bsl.bargain_id = '" . $bargain_id . "'
                GROUP BY bsl.id
                order by money desc ";

    $bargain_list = $GLOBALS['db']->getAll($sql);
    foreach ($bargain_list as $key => $val) {
        if ($key === 0) {
            $arr[$key]['img'] = asset('static/img/rank-1.png');
        } elseif ($key === 1) {
            $arr[$key]['img'] = asset('static/img/rank-2.png');
        } elseif ($key === 2) {
            $arr[$key]['img'] = asset('static/img/rank-3.png');
        } else {
            $arr[$key]['key'] = $key + 1;
        }
        //用户名、头像
        $user_nick = app(UserCommonService::class)->getUserDefault($val['user_id']);
        $arr[$key]['user_name'] = encrypt_username($user_nick['nick_name']);
        $arr[$key]['headerimg'] = $user_nick['user_picture'];
        $arr[$key]['subtract_price'] = price_format($val['money']);
        $arr[$key]['user_id'] = $val['user_id'];
    }
    return $arr;
}

/**
 * //砍价爆款
 */
function get_bargain_goods_hot()
{
    $time = gmtime();
    $where = " g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.review_status>2 and bg.status != 1 and bg.is_delete !=1 and bg.is_audit = 2 and $time > bg.start_time and $time < bg.end_time and bg.is_hot = 1";

    $sql = 'SELECT bg.id,bg.bargain_name,bg.goods_id,bg.start_time,bg.end_time,bg.target_price,bg.total_num,g.user_id, g.goods_name, g.shop_price, g.market_price, g.goods_thumb , g.goods_img,g.goods_brief FROM ' . $GLOBALS['dsc']->table('bargain_goods') . 'AS bg LEFT JOIN ' . $GLOBALS['dsc']->table('goods') . ' AS g ON bg.goods_id = g.goods_id ' .
        "WHERE $where limit 0,10";
    $bargain_list = $GLOBALS['db']->getAll($sql);
    foreach ($bargain_list as $key => $val) {
        $arr[$key]['url'] = $val['goods_name'];
        $arr[$key]['goods_name'] = $val['goods_name'];
        $arr[$key]['goods_img'] = get_image_path($val['goods_img']);
        $arr[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
        $arr[$key]['target_price'] = price_format($val['target_price']);
        $arr[$key]['shop_price'] = price_format($val['shop_price']);
        $arr[$key]['url'] = route('bargain/goods/index', ['id' => $val['id']]);
    }
    return $arr;
}

/**
 * //获取砍价商品属性最低价格
 */
function get_bargain_target_price($bargain_id = 0)
{
    $sql = 'SELECT min(target_price) as target_price FROM ' . $GLOBALS['dsc']->table('activity_goods_attr') . " WHERE bargain_id = $bargain_id ";
    $bargain = $GLOBALS['db']->getOne($sql);
    return $bargain;
}

//获取中文字符拼音首字母
function getFirstCharter($str)
{
    if (empty($str)) {
        return '';
    }
    $fchar = ord($str{0});
    if ($fchar >= ord('A') && $fchar <= ord('z')) {
        return strtoupper($str{0});
    }
    $s1 = iconv('UTF-8', 'gb2312', $str);
    $s2 = iconv('gb2312', 'UTF-8', $s1);
    $s = $s2 == $str ? $s1 : $str;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 && $asc <= -20284) {
        return 'A';
    }
    if ($asc >= -20283 && $asc <= -19776) {
        return 'B';
    }
    if ($asc >= -19775 && $asc <= -19219) {
        return 'C';
    }
    if ($asc >= -19218 && $asc <= -18711) {
        return 'D';
    }
    if ($asc >= -18710 && $asc <= -18527) {
        return 'E';
    }
    if ($asc >= -18526 && $asc <= -18240) {
        return 'F';
    }
    if ($asc >= -18239 && $asc <= -17923) {
        return 'G';
    }
    if ($asc >= -17922 && $asc <= -17418) {
        return 'H';
    }
    if ($asc >= -17417 && $asc <= -16475) {
        return 'J';
    }
    if ($asc >= -16474 && $asc <= -16213) {
        return 'K';
    }
    if ($asc >= -16212 && $asc <= -15641) {
        return 'L';
    }
    if ($asc >= -15640 && $asc <= -15166) {
        return 'M';
    }
    if ($asc >= -15165 && $asc <= -14923) {
        return 'N';
    }
    if ($asc >= -14922 && $asc <= -14915) {
        return 'O';
    }
    if ($asc >= -14914 && $asc <= -14631) {
        return 'P';
    }
    if ($asc >= -14630 && $asc <= -14150) {
        return 'Q';
    }
    if ($asc >= -14149 && $asc <= -14091) {
        return 'R';
    }
    if ($asc >= -14090 && $asc <= -13319) {
        return 'S';
    }
    if ($asc >= -13318 && $asc <= -12839) {
        return 'T';
    }
    if ($asc >= -12838 && $asc <= -12557) {
        return 'W';
    }
    if ($asc >= -12556 && $asc <= -11848) {
        return 'X';
    }
    if ($asc >= -11847 && $asc <= -11056) {
        return 'Y';
    }
    if ($asc >= -11055 && $asc <= -10247) {
        return 'Z';
    }
    return null;
}


/**
 * 单条数据
 * 获取商品属性ID
 * goods_attr_id
 * $where_select 查询条件
 * $select 查询内容
 * $attr_type 唯一属性、单选属性、复选属性
 * $retuen_db 返回值模式（0-单条、1-单组、2-多组）
 */
function bargain_get_goods_attr_id($where_select = [], $select = [], $attr_type = 0, $retuen_db = 0)
{
    if ($where_select) {
    }

    if ($select) {
        $select = implode(",", $select);
    } else {
        $select = "ga.*, a.*";
    }

    $where = '';
    if (isset($where_select['goods_id']) && !empty($where_select['goods_id'])) {
        $where .= " AND ga.goods_id = '" . $where_select['goods_id'] . "'";
    }

    if (isset($where_select['attr_value']) && !empty($where_select['attr_value'])) {
        $where .= " AND ga.attr_value = '" . $where_select['attr_value'] . "'";
    }

    if (isset($where_select['attr_id']) && !empty($where_select['attr_id'])) {
        $where .= " AND ga.attr_id = '" . $where_select['attr_id'] . "'";
    }

    if (isset($where_select['goods_attr_id']) && !empty($where_select['goods_attr_id'])) {
        $where .= " AND ga.goods_attr_id = '" . $where_select['goods_attr_id'] . "'";
    }

    if (isset($where_select['admin_id']) && !empty($where_select['admin_id'])) {
        $where .= " AND ga.admin_id = '" . $where_select['admin_id'] . "'";
    }

    if ($attr_type && is_array($attr_type)) {
        $attr_type = implode(",", $attr_type);
        $where .= " AND a.attr_type IN($attr_type)";
    } else {
        if ($attr_type) {
            $where .= " AND a.attr_type = '$attr_type'";
        }
    }

    if ($retuen_db == 1) {
        $where .= " LIMIT 1";
    }

    $sql = " SELECT $select FROM " . $GLOBALS['dsc']->table('goods_attr') . " AS ga, " .
        $GLOBALS['dsc']->table('attribute') . " AS a" .
        " WHERE ga.attr_id = a.attr_id $where";

    if ($retuen_db == 1) {
        return $GLOBALS['db']->getRow($sql);
    } elseif ($retuen_db == 2) {
        return $GLOBALS['db']->getAll($sql);
    } else {
        return $GLOBALS['db']->getOne($sql, true);
    }
}

/**
 * 删除商品复选属性ID
 */
function get_goods_unset_attr_bargain($goods_id = 0, $attr_arr = [])
{
    $arr = [];

    if ($attr_arr) {
        $where_select = [];

        $where_select['goods_id'] = $goods_id;

        foreach ($attr_arr as $key => $row) {
            if ($row) {
                $where_select['attr_value'] = $row[0];
                $attr_info = bargain_get_goods_attr_id($where_select, ['ga.goods_id', 'ga.attr_value', 'a.attr_id', 'a.attr_type'], 2, 1);
                if ($attr_info && $row[0] == $attr_info['attr_value']) {
                    unset($row);
                } else {
                    $arr[$key] = $row;
                }
            }
        }
    }

    return $arr;
}


//通过一组属性获取货品的相关信息 by wu
function get_product_info_by_attr_bargain($bargain_id = 0, $goods_id = 0, $attr_arr = [], $goods_model = 0, $region_id = 0)
{
    if (!empty($attr_arr)) {
        $where = "";
        //判断商品类型
        if ($goods_model == 1) {
            $table = "products_warehouse";
            $where .= " AND warehouse_id = '$region_id' ";
        } elseif ($goods_model == 2) {
            $table = "products_area";
            $where .= " AND area_id = '$region_id' ";
        } else {
            $table = "products";
        }

        $where_select = ['goods_id' => $goods_id];


        //获取属性组合
        $attr = [];
        foreach ($attr_arr as $key => $val) {
            $where_select['attr_value'] = $val;
            $goods_attr_id = bargain_get_goods_attr_id($where_select, ['ga.goods_attr_id'], 1);

            if ($goods_attr_id) {
                $attr[] = $goods_attr_id;
            }
        }

        $set = "";
        foreach ($attr as $key => $val) {
            $set .= " AND FIND_IN_SET('$val', REPLACE(goods_attr, '|', ',')) ";
        }
        $sql = " SELECT * FROM " . $GLOBALS['dsc']->table($table) . " WHERE 1 $set AND goods_id = '$goods_id' " . $where . " LIMIT 1 ";
        $product_info = $GLOBALS['db']->getRow($sql);
        if ($bargain_id > 0) {
            $sql = " SELECT * FROM " . $GLOBALS['dsc']->table('activity_goods_attr') . " WHERE bargain_id = '$bargain_id'  AND goods_id = '$goods_id' and product_id = '" . $product_info['product_id'] . "'  LIMIT 1 ";
            $attr_info = $GLOBALS['db']->getRow($sql);
            $product_info['goods_attr_id'] = $attr_info['id'];
            $product_info['target_price'] = $attr_info['target_price'];
        }
        return $product_info;
    } else {
        return false;
    }
}

/**
 * 获取商品ajax属性是否都选中
 */
function get_goods_attr_ajax($goods_id, $goods_attr, $goods_attr_id)
{
    $arr = [];
    $arr['attr_id'] = '';
    $goods_attr = implode(",", $goods_attr);
    if ($goods_attr) {
        if ($goods_attr_id) {
            $goods_attr_id = implode(",", $goods_attr_id);
            $where = " AND ga.goods_attr_id IN($goods_attr_id)";
        } else {
            $where = '';
        }

        $sql = "SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value  FROM " . $GLOBALS['dsc']->table('goods_attr') . " AS ga" .
            " LEFT JOIN " . $GLOBALS['dsc']->table('attribute') . " AS a ON ga.attr_id = a.attr_id " .
            " WHERE ga.attr_id IN($goods_attr) AND ga.goods_id = '$goods_id' $where AND a.attr_type > 0 ORDER BY a.sort_order, ga.attr_id";
        $res = $GLOBALS['db']->getAll($sql);

        foreach ($res as $key => $row) {
            $arr[$row['attr_id']][$row['goods_attr_id']] = $row;

            $arr['attr_id'] .= $row['attr_id'] . ",";
        }

        if ($arr['attr_id']) {
            $arr['attr_id'] = substr($arr['attr_id'], 0, -1);
            $arr['attr_id'] = explode(",", $arr['attr_id']);
        } else {
            $arr['attr_id'] = [];
        }
    }

    return $arr;
}

/**
 * 获得指定商品属性详情
 */
function get_attr_value($goods_id, $attr_id)
{
    $sql = "select * from " . $GLOBALS['dsc']->table('goods_attr') . " where goods_id='$goods_id' and goods_attr_id='$attr_id'";
    $re = $GLOBALS['db']->getRow($sql);

    if (!empty($re)) {
        return $re;
    } else {
        return false;
    }
}


/**
 * 获得指定商品的model_attr
 */
function bargain_get_table_date($goods_id)
{
    $sql = "select model_attr from " . $GLOBALS['dsc']->table('goods') . " where goods_id='$goods_id'";
    $model_attr = $GLOBALS['db']->getOne($sql);

    return $model_attr;
}


/**
 * 获得指定商品属性活动最低价
 */
function bargain_target_price($bargain_id = 0, $goods_id = 0, $spec = [], $warehouse_id = 0, $area_id = 0)
{
    if (!empty($spec)) {
        $price = 0;
        if (is_array($spec)) {
            foreach ($spec as $key => $val) {
                $spec[$key] = addslashes($val);
            }
        } else {
            $spec = addslashes($spec);
        }
        $model_attr = bargain_get_table_date($goods_id);
        $attr['price'] = 0;
        if ($GLOBALS['_CFG']['goods_attr_price'] == 1) {
            $spec = implode("|", $spec);
            $where = "goods_id = '$goods_id'";
            if ($model_attr == 1) { //仓库属性
                $table = "products_warehouse";
                $where .= " AND warehouse_id = '$warehouse_id' AND goods_attr = '$spec'";
            } elseif ($model_attr == 2) { //地区属性
                $table = "products_area";
                $where .= " AND area_id = '$area_id' AND goods_attr = '$spec'";
            } else {
                $table = "products";
                $where .= " AND goods_attr = '$spec'";
            }

            $sql = 'SELECT product_id FROM ' . $GLOBALS['dsc']->table($table) . " WHERE $where";
            $product_id = $GLOBALS['db']->getOne($sql);
            if ($product_id) {
                $sql = 'SELECT target_price FROM ' . $GLOBALS['dsc']->table('activity_goods_attr') . " WHERE bargain_id = '$bargain_id' and goods_id = '$goods_id' and product_id = '$product_id' ";
                $price = $GLOBALS['db']->getOne($sql);
            }
        }
    } else {
        $price = 0;
    }


    return floatval($price);
}
