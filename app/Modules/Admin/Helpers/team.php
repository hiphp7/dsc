<?php

use App\Models\TeamCategory;
use App\Models\TeamGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Package\PackageGoodsService;
use App\Services\User\UserCommonService;

/**
 * 获得所有频道
 */
function team_categories()
{
    $sql = 'SELECT c.id,c.name,c.parent_id,c.sort_order,c.status FROM {pre}team_category as c ' .
        "WHERE c.parent_id = 0 AND c.status = 1 ORDER BY c.sort_order ASC, c.id ASC";

    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res as $key => $row) {
        $cat_arr[$key]['id'] = $row['id'];
        $cat_arr[$key]['name'] = $row['name'];
    }
    return $cat_arr;
}

/**
 * 获得主频道下子频道
 */
function team_get_child_tree($id = 0)
{
    $three_arr = [];
    $sql = "SELECT count(*) FROM {pre}team_category WHERE parent_id = '$id' AND status = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $id == 0) {
        $child_sql = 'SELECT c.id,c.name,c.parent_id,c.sort_order,c.status,c.tc_img ' .
            'FROM {pre}team_category as c ' .
            " WHERE c.parent_id = '$id' AND c.status = 1 GROUP BY c.id ORDER BY c.sort_order ASC, c.id ASC";
        $res = $GLOBALS['db']->getAll($child_sql);
        foreach ($res as $row) {
            if ($row['status']) {
                $three_arr[$row['id']]['id'] = $row['id'];
                $three_arr[$row['id']]['name'] = $row['name'];
                $three_arr[$row['id']]['tc_img'] = get_image_path($row['tc_img']);
                $three_arr[$row['id']]['url'] = url('team/index/category', ['tc_id' => $row['id']]);
            }
            if (isset($row['cat_id']) != null) {
                $three_arr[$row['id']]['cat_id'] = team_get_child_tree($row['id']);
            }
        }
    }
    return $three_arr;
}


/**
 * 拼团子频道商品列表
 */
function team_category_goods($tc_id = 0, $keywords = '', $size = 10, $page = 1, $intro = '', $sort, $order, $brand, $min, $max)
{
    //频道id
    $where = " g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.review_status>2 and tg.is_team = 1 and tg.is_audit = 2 ";
    if ($tc_id > 0) {
        $where .= " AND  tg.tc_id = $tc_id ";
    }

    if ($intro) {
        switch ($intro) {
            case 'best':
                $where .= ' AND g.is_best = 1 ';
                break;
            case 'new':
                $where .= ' AND g.is_new = 1 ';
                break;
            case 'hot':
                $where .= ' AND g.is_hot = 1 ';
                break;
            case 'promotion':
                $time = gmtime();
                $where .= " AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time' ";
                break;
            default:
                $where .= '';
        }
    }

    $leftJoin = '';
    if ($brand > 0) {
        $leftJoin .= "LEFT JOIN " . $GLOBALS['dsc']->table('brand') . " AS b " . "ON b.brand_id = g.brand_id ";
        $where .= "AND g.brand_id = '$brand' ";
    }

    if (!empty($keywords)) {
        $where .= " AND (g.goods_name LIKE '%$keywords%' OR g.goods_sn LIKE '%$keywords%' OR g.keywords LIKE '%$keywords%')";
    }
    /*if($keywords){
        $where .= " AND $keywords ";
    }*/

    if ($min > 0) {
        $where .= " AND  tg.team_price >= $min ";
    }

    if ($max > 0) {
        $where .= " AND tg.team_price <= $max ";
    }

    if ($sort == 'last_update') {
        $sort = 'g.last_update';
    }

    $arr = [];
    $sql = 'SELECT g.goods_id, g.goods_name, g.shop_price,g.market_price,g.goods_number, g.goods_name_style, g.comments_number, g.sales_volume,g.goods_thumb , g.goods_img,g.model_price, tg.team_price, tg.team_num,tg.limit_num ' .
        ' FROM {pre}team_goods AS tg ' .
        'LEFT JOIN {pre}goods AS g ON tg.goods_id = g.goods_id ' . $leftJoin . " WHERE $where ORDER BY $sort $order";
    //echo $sql;
    $goods_list = $GLOBALS['db']->getAll($sql);
    $total = is_array($goods_list) ? count($goods_list) : 0;
    $res = $GLOBALS['dsc']->selectLimit($sql, $size, ($page - 1) * $size);
    foreach ($res as $key => $val) {
        $arr[$key]['goods_name'] = $val['goods_name'];
        $arr[$key]['shop_price'] = price_format($val['shop_price']);
        $arr[$key]['market_price'] = price_format($val['market_price']);
        $arr[$key]['goods_number'] = $val['goods_number'];
        $arr[$key]['sales_volume'] = $val['sales_volume'];

        $arr[$key]['goods_img'] = get_image_path($val['goods_img']);
        $arr[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
        $arr[$key]['url'] = url('team/goods/index', ['id' => $val['goods_id']]);
        $arr[$key]['team_price'] = price_format($val['team_price']);
        $arr[$key]['team_num'] = $val['team_num'];
        $arr[$key]['limit_num'] = $val['limit_num'];
    }

    return ['list' => array_values($arr), 'totalpage' => ceil($total / $size)];
}


/**
 * 获取推荐商品
 */
function team_goods($size = 10, $page = 1, $type = 'limit_num')
{
    $where = " g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.review_status>2 and tg.is_team = 1 and tg.is_audit = 2 ";
    switch ($type) {
        case 'limit_num':
            $type = '  ORDER BY tg.limit_num DESC';
            break;
        case 'is_new':
            $type = 'AND g.is_new = 1 ORDER BY g.add_time DESC';
            break;
        case 'is_hot':
            $type = 'AND g.is_hot = 1';
            break;
        case 'is_best':
            $type = 'AND g.is_best = 1';
            break;
        default:
            $type = '1';
    }
    $arr = [];
    $sql = 'SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_name_style, g.comments_number, g.sales_volume, g.market_price, g.goods_thumb , g.goods_img, tg.team_price, tg.team_num,tg.limit_num' .
        ' FROM {pre}team_goods AS tg ' .
        'LEFT JOIN {pre}goods AS g ON tg.goods_id = g.goods_id ' .
        "WHERE $where $type";
    $goods_list = $GLOBALS['db']->getAll($sql);
    $total = is_array($goods_list) ? count($goods_list) : 0;
    $res = $GLOBALS['dsc']->selectLimit($sql, $size, ($page - 1) * $size);
    foreach ($res as $key => $val) {
        if ($key < 3 && $page < 2) {
            $arr[$key]['key'] = $key + 1;
        }
        $arr[$key]['goods_name'] = $val['goods_name'];
        $arr[$key]['shop_price'] = price_format($val['shop_price']);
        $arr[$key]['goods_img'] = get_image_path($val['goods_img']);
        $arr[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
        $arr[$key]['url'] = url('team/goods/index', ['id' => $val['goods_id']]);
        $arr[$key]['team_price'] = price_format($val['team_price']);
        $arr[$key]['team_num'] = $val['team_num'];
        $arr[$key]['limit_num'] = $val['limit_num'];
    }
    return ['list' => array_values($arr), 'totalpage' => ceil($total / $size)];
}

/**
 * 获取拼团新品
 */
function team_new_goods($type, $ru_id = 0)
{
    $where = " g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.review_status>2 and tg.is_team = 1 and tg.is_audit = 2 ";
    if ($type == 'is_new') {
        $where .= " and g.is_new =$type and g.user_id =$ru_id  ";
    }
    $sql = 'SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_name_style, g.comments_number, g.sales_volume, g.market_price, g.goods_thumb , g.goods_img, tg.team_price, tg.team_num,tg.limit_num' .
        ' FROM {pre}team_goods AS tg ' .
        'LEFT JOIN {pre}goods AS g ON tg.goods_id = g.goods_id ' .
        "WHERE $where limit 0,10";
    $goods_list = $GLOBALS['db']->getAll($sql);

    foreach ($goods_list as $key => $val) {
        $arr[$key]['goods_name'] = $val['goods_name'];
        $arr[$key]['shop_price'] = price_format($val['shop_price']);
        $arr[$key]['goods_img'] = get_image_path($val['goods_img']);
        $arr[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
        $arr[$key]['url'] = url('team/goods/index', ['id' => $val['goods_id']]);
        $arr[$key]['team_price'] = price_format($val['team_price']);
        $arr[$key]['team_num'] = $val['team_num'];
        $arr[$key]['limit_num'] = $val['limit_num'];
    }
    return $arr;
}

/**
 * 查询商品评论
 * @param $id
 * @param string $rank
 * @param int $start
 * @param int $size
 * @return bool
 */
function get_good_comment($id, $rank = null, $hasgoods = 0, $start = 0, $size = 10)
{
    if (empty($id)) {
        return false;
    }
    $where = '';

    $rank = (empty($rank) && $rank !== 0) ? '' : intval($rank);

    if ($rank == 4) {
        //好评
        $where = ' AND  comment_rank in (4, 5)';
    } elseif ($rank == 2) {
        //中评
        $where = ' AND  comment_rank in (2, 3)';
    } elseif ($rank === 0) {
        //差评
        $where = ' AND  comment_rank in (0, 1)';
    } elseif ($rank == 1) {
        //差评
        $where = ' AND  comment_rank in (0, 1)';
    } elseif ($rank == 5) {
        $where = ' AND  comment_rank in (0, 1, 2, 3, 4,5)';
    }

    $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('comment') . " WHERE id_value = '" . $id . "' and comment_type = 0 and status = 1 and parent_id = 0 " . $where . " ORDER BY comment_id DESC LIMIT $start, $size";

    $comment = $GLOBALS['db']->getAll($sql);
    $arr = [];
    if ($comment) {
        $ids = '';
        foreach ($comment as $key => $row) {
            $ids .= $ids ? ",$row[comment_id]" : $row['comment_id'];
            $arr[$row['comment_id']]['id'] = $row['comment_id'];
            $arr[$row['comment_id']]['email'] = $row['email'];
            $arr[$row['comment_id']]['content'] = str_replace('\r\n', '<br />', $row['content']);
            $arr[$row['comment_id']]['content'] = nl2br(str_replace('\n', '<br />', $arr[$row['comment_id']]['content']));
            $arr[$row['comment_id']]['rank'] = $row['comment_rank'];
            $arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);

            //用户名、头像
            $user_nick = app(\App\Services\User\UserCommonService::class)->getUserDefault($row['user_id']);
            $arr[$row['comment_id']]['username'] = encrypt_username($user_nick['nick_name']);
            //$arr[$row['comment_id']]['headerimg']=get_image_path($user_nick['user_picture']);
            $arr[$row['comment_id']]['headerimg'] = $user_nick['user_picture'];

            if ($row['order_id'] && $hasgoods) {
                $sql = "SELECT o.goods_id, o.goods_name, o.goods_attr, g.goods_img FROM " . $GLOBALS['dsc']->table('order_goods') . " o LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " g ON o.goods_id = g.goods_id WHERE o.order_id = '" . $row['order_id'] . "' ORDER BY rec_id DESC";
                $goods = $GLOBALS['db']->getAll($sql);
                if ($goods) {
                    foreach ($goods as $k => $v) {
                        $goods[$k]['goods_img'] = get_image_path($v['goods_img']);
                        $goods[$k]['goods_attr'] = str_replace('\r\n', '<br />', $v['goods_attr']);
                    }
                }
                $arr[$row['comment_id']]['goods'] = $goods;
            }
            $sql = "SELECT img_thumb FROM {pre}comment_img WHERE comment_id = " . $row['comment_id'];
            $comment_thumb = $GLOBALS['db']->getCol($sql);
            if (count($comment_thumb) > 0) {
                foreach ($comment_thumb as $k => $v) {
                    $comment_thumb[$k] = get_image_path($v);
                }
                $arr[$row['comment_id']]['thumb'] = $comment_thumb;
            } else {
                $arr[$row['comment_id']]['thumb'] = 0;
            }
        }

        /* 取得已有回复的评论 */
        if ($ids) {
            $sql = 'SELECT * FROM ' . $GLOBALS['dsc']->table('comment') . " WHERE parent_id IN( $ids )";
            $res = $GLOBALS['dsc']->query($sql);
            foreach ($res as $row) {
                $arr[$row['parent_id']]['re_content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
                $arr[$row['parent_id']]['re_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
                $arr[$row['parent_id']]['re_email'] = $row['email'];
                $arr[$row['parent_id']]['re_username'] = $row['user_name'];
            }
        }
        $arr = array_values($arr);
    }
    return $arr;
}

/**
 * 商品评论列表
 */

function get_good_comment_as($id, $rank = null, $hasgoods = 0, $start = 0, $size = 10)
{
    if (empty($id)) {
        return false;
    }
    $where = '';

    $rank = (empty($rank) && $rank !== 0) ? '' : intval($rank);

    if ($rank == 4) {
        //好评
        $where = ' AND  comment_rank in (4, 5)';
    } elseif ($rank == 2) {
        //中评
        $where = ' AND  comment_rank in (2, 3)';
    } elseif ($rank === 0) {
        //差评
        $where = ' AND  comment_rank in (0, 1)';
    } elseif ($rank == 1) {
        //差评
        $where = ' AND  comment_rank in (0, 1)';
    } elseif ($rank == 5) {
        $where = ' AND  comment_rank in (0, 1, 2, 3, 4,5)';
    }

    $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('comment') . " WHERE id_value = '" . $id . "' and comment_type = 0 and status = 1 and parent_id = 0 " . $where . " ORDER BY comment_id DESC LIMIT $start, $size";

    $comment = $GLOBALS['db']->getAll($sql);


    $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('comment') . " WHERE id_value = '" . $id . "' and comment_type = 0 and status = 1 and parent_id = 0 " . $where;

    $max = $GLOBALS['db']->getAll($sql);

    $max = ceil(count($max) / $size);
    $arr = [];
    if ($comment) {
        $ids = '';
        foreach ($comment as $key => $row) {
            $ids .= $ids ? ",$row[comment_id]" : $row['comment_id'];
            $arr[$row['comment_id']]['id'] = $row['comment_id'];
            $arr[$row['comment_id']]['email'] = $row['email'];

            $users = app(UserCommonService::class)->getUserDefault($row['user_id']);
            $arr[$row['comment_id']]['username'] = encrypt_username($users['nick_name']);
            $arr[$row['comment_id']]['user_picture'] = get_image_path($users['user_picture']);

            $arr[$row['comment_id']]['content'] = str_replace('\r\n', '<br />', $row['content']);
            $arr[$row['comment_id']]['content'] = nl2br(str_replace('\n', '<br />', $arr[$row['comment_id']]['content']));
            $arr[$row['comment_id']]['rank'] = $row['comment_rank'];
            $arr[$row['comment_id']]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            if ($row['order_id'] && $hasgoods) {
                $sql = "SELECT o.goods_id, o.goods_name, o.goods_attr, g.goods_img FROM " . $GLOBALS['dsc']->table('order_goods') . " o LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " g ON o.goods_id = g.goods_id WHERE o.order_id = '" . $row['order_id'] . "' ORDER BY rec_id DESC";
                $goods = $GLOBALS['db']->getAll($sql);
                if ($goods) {
                    foreach ($goods as $k => $v) {
                        $goods[$k]['goods_img'] = get_image_path($v['goods_img']);
                        $goods[$k]['goods_attr'] = str_replace('\r\n', '<br />', $v['goods_attr']);
                    }
                }
                $arr[$row['comment_id']]['goods'] = $goods;
            }
            $sql = "SELECT img_thumb FROM {pre}comment_img WHERE comment_id = " . $row['comment_id'];
            $comment_thumb = $GLOBALS['db']->getCol($sql);
            if (count($comment_thumb) > 0) {
                foreach ($comment_thumb as $k => $v) {
                    $comment_thumb[$k] = get_image_path($v);
                }
                $arr[$row['comment_id']]['thumb'] = $comment_thumb;
            } else {
                $arr[$row['comment_id']]['thumb'] = 0;
            }
        }

        /* 取得已有回复的评论 */
        if ($ids) {
            $sql = 'SELECT * FROM ' . $GLOBALS['dsc']->table('comment') . " WHERE parent_id IN( $ids )";
            $res = $GLOBALS['dsc']->query($sql);
            foreach ($res as $row) {
                $arr[$row['parent_id']]['re_content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
                $arr[$row['parent_id']]['re_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
                $arr[$row['parent_id']]['re_email'] = $row['email'];
                $arr[$row['parent_id']]['re_username'] = $row['user_name'];
            }
        }
        $arr = array_values($arr);
    }
    return ['arr' => $arr, 'max' => $max];
}


/*
 * 取得商品评论条数
 */
function commentCol($id)
{
    if (empty($id)) {
        return false;
    }
    $sql = "SELECT count(comment_id) as num FROM {pre}comment WHERE id_value =" . $id . ' and comment_type = 0 and status = 1 and parent_id = 0';
    $arr['all_comment'] = $GLOBALS['db']->getOne($sql);
    $sql = "SELECT count(comment_id) as num FROM {pre}comment WHERE id_value =" . $id . ' AND  comment_rank in (4, 5) and comment_type = 0 and status = 1 and parent_id = 0 ';
    $arr['good_comment'] = $GLOBALS['db']->getOne($sql);
    $sql = "SELECT count(comment_id) as num FROM {pre}comment WHERE id_value =" . $id . ' AND  comment_rank in (2, 3) and comment_type = 0 and status = 1 and parent_id = 0 ';
    $arr['in_comment'] = $GLOBALS['db']->getOne($sql);
    $sql = "SELECT count(comment_id) as num FROM {pre}comment WHERE id_value =" . $id . ' AND  comment_rank in (0, 1) and comment_type = 0 and status = 1 and parent_id = 0 ';
    $arr['rotten_comment'] = $GLOBALS['db']->getOne($sql);
    $sql = "SELECT count( DISTINCT b.comment_id) as num FROM {pre}comment as a LEFT JOIN {pre}comment_img as b ON a.id_value=b.goods_id WHERE a.id_value =" . $id . " and a.comment_type = 0 and a.status = 1 and a.parent_id = 0 and b.img_thumb != ''";
    $arr['img_comment'] = $GLOBALS['db']->getOne($sql);
    foreach ($arr as $key => $val) {
        $arr[$key] = empty($val) ? 0 : $arr[$key];
    }
    return $arr;
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
 * 拼团商品信息
 */
function team_goods_info($goods = 0, $t_id = 0)
{
    $sql = 'SELECT g.*, tg.team_price, tg.team_num,tg.astrict_num FROM ' . $GLOBALS['dsc']->table('team_goods') . 'AS tg LEFT JOIN ' . $GLOBALS['dsc']->table('goods') . ' AS g ON tg.goods_id = g.goods_id ' . "WHERE tg.goods_id = $goods and tg.id = $t_id  and is_team = 1";
    $goods = $GLOBALS['db']->getRow($sql);
    return $goods;
}

/**
 * 验证参团活动是否结束
 */
function team_is_failure($team_id = 0)
{
    $sql = 'SELECT tg.id,tg.team_price,tg.team_num,tg.astrict_num,tg.is_team FROM ' . $GLOBALS['dsc']->table('team_log') . 'AS tl LEFT JOIN ' . $GLOBALS['dsc']->table('team_goods') . ' AS tg ON tl.t_id = tg.id ' . "WHERE  tl.team_id = $team_id";
    $team = $GLOBALS['db']->getRow($sql);
    return $team;
}


/**
 * 获取该商品已成功开团信息
 */
function team_goods_log($goods_id = 0)
{
    $sql = "select tl.team_id, tl.start_time,o.team_parent_id,g.goods_id,tg.validity_time ,tg.team_num from " . $GLOBALS['dsc']->table('team_log') . " as tl LEFT JOIN " . $GLOBALS['dsc']->table('order_info') . " as o ON tl.team_id = o.team_id LEFT JOIN  " . $GLOBALS['dsc']->table('goods') . " as g ON tl.goods_id = g.goods_id LEFT JOIN " . $GLOBALS['dsc']->table('team_goods') . " AS tg ON tl.t_id = tg.id  " . " where tl.goods_id = $goods_id and tl.status <1 and tl.is_show = 1 and o.extension_code ='team_buy' and o.team_parent_id > 0 and pay_status = 2 and tg.is_team = 1";
    $result = $GLOBALS['db']->getAll($sql);
    foreach ($result as $key => $vo) {
        $validity_time = $vo['start_time'] + ($vo['validity_time'] * 3600);
        $goods[$key]['team_id'] = $vo['team_id'];//开团id
        $goods[$key]['user_id'] = $vo['team_parent_id'];//开团id
        $goods[$key]['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $vo['start_time'] + ($vo['validity_time'] * 3600));//剩余时间
        $goods[$key]['end_time'] = local_date('Y/m/d H:i:s', $vo['start_time'] + ($vo['validity_time'] * 3600));//剩余时间
        $goods[$key]['surplus'] = $vo['team_num'] - surplus_num($vo['team_id']);//还差几人

        //用户名、头像
        $user_nick = app(UserCommonService::class)->getUserDefault($vo['team_parent_id']);
        $goods[$key]['user_name'] = encrypt_username($user_nick['nick_name']);
        $goods[$key]['headerimg'] = $user_nick['user_picture'];

        //过滤到期的拼团
        if ($validity_time <= gmtime()) {
            unset($goods[$key]);
        }
    }
    return $goods;
}

/**
 * 计算该拼团已参与人数
 * $failure  0 正在拼团中，2 失败团  统计参团人数
 */
function surplus_num($team_id = 0, $failure = 0)
{
    if ($failure == 2) {
        $sql = "SELECT count(order_id) as num  FROM " . $GLOBALS['dsc']->table('order_info') . " WHERE team_id = '" . $team_id . "' AND extension_code = 'team_buy' ";
    } else {
        $sql = "SELECT count(order_id) as num  FROM " . $GLOBALS['dsc']->table('order_info') . " WHERE team_id = '" . $team_id . "' AND extension_code = 'team_buy' and (pay_status = '" . PS_PAYED . "' or order_status = 4) ";
    }
    $res = $GLOBALS['db']->getRow($sql);
    return $res['num'];
}

/**
 * 获取我的拼团
 * @param  $type
 * @param  $limit
 * @param  $start
 */
function my_team_goods($user_id, $type = 1, $page = 1, $size = 10)
{

    /* --获取拼团列表-- */
    switch ($type) {
        case '1':
            $where = "";//全部团
            break;
        case '2':
            $where = " and t.status < 1 and '" . gmtime() . "'< (t.start_time+(tg.validity_time*3600)) and o.order_status != 2 and tg.is_team = 1 ";//拼团中
            break;
        case '3':
            $where = " and t.status = 1 ";//成功团
            break;
        case '4':
            $where = " and t.status < 1 and ('" . gmtime() . "' > (t.start_time+(tg.validity_time*3600)) || tg.is_team != 1)";//失败团
            break;

        default:
            $where = '';
    }
    $sql = "select o.order_id,o.order_status,o.pay_status,t.goods_id,t.team_id,t.start_time,t.status,g.goods_name,g.goods_thumb,g.goods_img,tg.validity_time,tg.team_num,tg.team_price,tg.limit_num from " . $GLOBALS['dsc']->table('order_info') . " as o left join " . $GLOBALS['dsc']->table('team_log') . " as t on o.team_id = t.team_id left join " . $GLOBALS['dsc']->table('team_goods') . " as tg on t.t_id = tg.id left join " . $GLOBALS['dsc']->table('goods') . " as g on g.goods_id = tg.goods_id" . " where o.user_id =$user_id and o.extension_code ='team_buy'  and t.is_show = 1 $where  ORDER BY o.add_time DESC ";
    $goods_list = $GLOBALS['db']->getAll($sql);
    $total = is_array($goods_list) ? count($goods_list) : 0;
    $res = $GLOBALS['dsc']->selectLimit($sql, $size, ($page - 1) * $size);

    foreach ($res as $key => $vo) {
        $goods[$key]['id'] = $vo['goods_id'];
        $goods[$key]['team_id'] = $vo['team_id'];
        $goods[$key]['order_id'] = $vo['order_id'];
        $goods[$key]['pay_status'] = $vo['pay_status'];
        $goods[$key]['goods_name'] = $vo['goods_name'];
        $goods[$key]['goods_img'] = get_image_path($vo['goods_img']);
        $goods[$key]['goods_thumb'] = get_image_path($vo['goods_thumb']);
        $goods[$key]['team_num'] = $vo['team_num'];
        if ($type == 4) {
            $goods[$key]['limit_num'] = surplus_num($vo['team_id'], 2);//几人参团
        } else {
            $goods[$key]['limit_num'] = surplus_num($vo['team_id']);//几人参团
        }
        //$goods[$key]['team_price'] = price_format($vo['team_price']);
        $goods[$key]['team_price'] = $vo['team_price'];
        $goods[$key]['url'] = url('goods/index', ['id' => $vo['goods_id']]);
        $goods[$key]['order_url'] = url('user/order/detail', ['order_id' => $vo['order_id']]);//查看订单
        $goods[$key]['team_url'] = url('team/goods/teamwait', ['team_id' => $vo['team_id'], 'user_id' => session('user_id')]);//查看团
        if ($vo['status'] == 1) {
            $goods[$key]['status'] = 1;//成功
        }
        $validity_time = $vo['start_time'] + ($vo['validity_time'] * 3600);
        if ($validity_time <= gmtime() && $vo['status'] != 1 || $vo['order_status'] == 2) {
            $goods[$key]['status'] = 2;//失败
        }
    }
    return ['list' => array_values($goods), 'totalpage' => ceil($total / $size)];
}


/**
 * 获取购物车商品rec_id
 * @param int $flow_type
 * @return string
 */
function get_cart_value($flow_type = 0)
{
    if (!empty(session('user_id'))) {
        $where = " c.user_id = '" . session('user_id') . "' ";
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $where = " c.session_id = '$session_id' ";
    }

    $sql = "SELECT c.rec_id FROM " . $GLOBALS['dsc']->table('cart') .
        " AS c LEFT JOIN " . $GLOBALS['dsc']->table('goods') .
        " AS g ON c.goods_id = g.goods_id WHERE $where " .
        "AND rec_type = '$flow_type' order by c.rec_id asc";

    $goods_list = $GLOBALS['db']->getAll($sql);

    $rec_id = '';
    if ($goods_list) {
        foreach ($goods_list as $key => $row) {
            $rec_id .= $row['rec_id'] . ',';
        }

        $rec_id = substr($rec_id, 0, -1);
    }

    return $rec_id;
}


/**
 * 获得用户的可用积分
 *
 * @access private
 * @return integral
 */
function flow_available_points($cart_value)
{
    if (!empty(session('user_id'))) {
        $c_sess = " c.user_id = '" . session('user_id') . "' ";
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $c_sess = " c.session_id = '$session_id' ";
    }

    $where = "";
    if (!empty($cart_value)) {
        $where = " AND c.rec_id " . db_create_in($cart_value);
    }

    $sql = "SELECT SUM(g.integral * c.goods_number) " .
        "FROM " . $GLOBALS['dsc']->table('cart') . " AS c, " . $GLOBALS['dsc']->table('goods') . " AS g " .
        "WHERE " . $c_sess . " AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 $where" .
        "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";

    $val = intval($GLOBALS['db']->getOne($sql));

    $val = app(DscRepository::class)->integralOfValue($val);

    return $val;
}


/**
 * 获取频道下商品数量
 */
function categroy_number($tc_id = 0)
{
    $model = TeamGoods::where('is_team', 1);
    if ($tc_id > 0) {
        $tc_id = categroy_id($tc_id);
        $tc_arr = explode(',', $tc_id);
        $model = $model->whereIn('tc_id', $tc_arr);
    }
    $goods_number = $model->count();
    return $goods_number;
}

/**
 * 获取频道id
 */
function categroy_id($tc_id = 0)
{
    $one = TeamCategory::select('id')
        ->where('id', $tc_id)
        ->orWhere('parent_id', $tc_id)
        ->get();
    $one = $one ? $one->toArray() : [];
    if ($one) {
        foreach ($one as $key) {
            $one_id[] = $key['id'];
        }
        $tc_id = implode(',', $one_id);
    }
    return $tc_id;
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


/*
 * 获取上下级分类列表 by wu
 * $cat_id      分类id
 * $relation    关系 0:自己 1:上级 2:下级
 * $self        是否包含自己 true:包含 false:不包含
 */

/*function get_select_category($cat_id = 0, $relation = 0, $self = true)
{
    //静态数组
    static $cat_list = [];
    $cat_list[] = intval($cat_id);

    if ($relation == 0) {
        return $cat_list;
    } elseif ($relation == 1) {
        $sql = " select parent_id from " . $GLOBALS['dsc']->table('category') . " where cat_id='" . $cat_id . "' ";
        $parent_id = $GLOBALS['db']->getOne($sql);
        if (!empty($parent_id)) {
            get_select_category($parent_id, $relation, $self);
        }
        //删除自己
        if ($self == false) {
            unset($cat_list[0]);
        }
        $cat_list[] = 0;
        //去掉重复，主要是0
        return array_reverse(array_unique($cat_list));
    } elseif ($relation == 2) {
        $sql = " select cat_id from " . $GLOBALS['dsc']->table('category') . " where parent_id='" . $cat_id . "' ";
        $child_id = $GLOBALS['db']->getCol($sql);
        if (!empty($child_id)) {
            foreach ($child_id as $key => $val) {
                get_select_category($val, $relation, $self);
            }
        }
        //删除自己
        if ($self == false) {
            unset($cat_list[0]);
        }
        return $cat_list;
    }
}*/

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

function team_get_tree($tree_id = 0)
{
    $three_arr = [];
    $where = "";
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['dsc']->table('team_category') . " WHERE parent_id = '$tree_id' AND status = 1" . $where;
    if ($GLOBALS['db']->getOne($sql) || $tree_id == 0) {
        $child_sql = 'SELECT id, name, parent_id,status ' . ' FROM ' . $GLOBALS['dsc']->table('team_category') .
            " WHERE parent_id = '$tree_id' AND status = 1 " . $where . " ORDER BY sort_order ASC, id ASC";
        $res = $GLOBALS['db']->getAll($child_sql);
        foreach ($res as $k => $row) {
            if ($row['status']) {
                $three_arr[$k]['tc_id'] = $row['id'];
                $three_arr[$k]['name'] = $row['name'];
            }
            if (isset($row['id'])) {
                $child_tree = team_get_tree($row['id']);
                if ($child_tree) {
                    $three_arr[$k]['id'] = $child_tree;
                }
            }
        }
    }
    return $three_arr;
}

/**
 * 检查订单中商品库存
 *
 * @access  public
 * @param array $arr
 *
 * @return  void
 */
function flow_cart_stock($arr, $store_id = 0)
{
    if (!empty(session('user_id'))) {
        $sess_id = " user_id = '" . session('user_id') . "' ";
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $sess_id = " session_id = '$session_id' ";
    }

    foreach ($arr as $key => $val) {
        $val = intval(make_semiangle($val));
        if ($val <= 0 || !is_numeric($key)) {
            continue;
        }

        $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code`, `warehouse_id` FROM" . $GLOBALS['dsc']->table('cart') .
            " WHERE rec_id='$key' AND " . $sess_id;
        $goods = $GLOBALS['db']->getRow($sql);

        $sql = "SELECT g.goods_name, g.goods_number, g.goods_id, c.product_id, g.model_attr " .
            "FROM " . $GLOBALS['dsc']->table('goods') . " AS g, " .
            $GLOBALS['dsc']->table('cart') . " AS c " .
            "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
        $row = $GLOBALS['db']->getRow($sql);

        //ecmoban模板堂 --zhuo start
        $sql = "select IF(g.model_inventory < 1, g.goods_number, IF(g.model_inventory < 2, wg.region_number, wag.region_number)) AS goods_number " .
            " from " . $GLOBALS['dsc']->table('goods') . " as g " .
            " left join " . $GLOBALS['dsc']->table('warehouse_goods') . " as wg on g.goods_id = wg.goods_id" .
            " left join " . $GLOBALS['dsc']->table('warehouse_area_goods') . " as wag on g.goods_id = wag.goods_id" .
            " where g.goods_id = '" . $row['goods_id'] . "'";
        $goods_number = $GLOBALS['db']->getOne($sql);

        $row['goods_number'] = $goods_number;
        //ecmoban模板堂 --zhuo end

        //系统启用了库存，检查输入的商品数量是否有效
        if (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy' && $store_id == 0) {
            //ecmoban模板堂 --zhuo start
            /* 是货品 */
            $row['product_id'] = trim($row['product_id']);
            if (!empty($row['product_id'])) {
                //ecmoban模板堂 --zhuo start
                if ($row['model_attr'] == 1) {
                    $table_products = "products_warehouse";
                } elseif ($row['model_attr'] == 2) {
                    $table_products = "products_area";
                } else {
                    $table_products = "products";
                }
                //ecmoban模板堂 --zhuo end

                $sql = "SELECT product_number FROM " . $GLOBALS['dsc']->table($table_products) . " WHERE goods_id = '" . $row['goods_id'] . "' and product_id = '" . $row['product_id'] . "'";
                $product_number = $GLOBALS['db']->getOne($sql);
                if ($product_number < $val) {
                    return sys_msg(sprintf(
                        L('stock_insufficiency'),
                        $row['goods_name'],
                        $product_number,
                        $product_number
                    ));
                }
            } else {
                if ($row['goods_number'] < $val) {
                    return sys_msg(sprintf(
                        L('stock_insufficiency'),
                        $row['goods_name'],
                        $row['goods_number'],
                        $row['goods_number']
                    ));
                }
            }
            //ecmoban模板堂 --zhuo end
        } elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $store_id > 0) {
            $sql = "SELECT goods_number,ru_id FROM" . $GLOBALS['dsc']->table("store_goods") . " WHERE store_id = '$store_id' AND goods_id = '" . $row['goods_id'] . "' ";
            $goodsInfo = $GLOBALS['db']->getRow($sql);

            $products = app(GoodsWarehouseService::class)->getWarehouseAttrNumber($row['goods_id'], $goods['goods_attr_id'], 0, 0, 0, '', $store_id);//获取属性库存

            $attr_number = $products['product_number'];
            if ($goods['goods_attr_id']) { //当商品没有属性库存时
                $row['goods_number'] = $attr_number;
            } else {
                $row['goods_number'] = $goodsInfo['goods_number'];
            }
            if ($row['goods_number'] < $val) {
                return sys_msg(sprintf(
                    L('stock_store_shortage'),
                    $row['goods_name'],
                    $row['goods_number'],
                    $row['goods_number']
                ));
            }
        } elseif (intval($GLOBALS['_CFG']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy') {
            if (app(PackageGoodsService::class)->judgePackageStock($goods['goods_id'], $val)) {
                return sys_msg(L('package_stock_insufficiency'));
            }
        }
    }
}

/**
 * 获得指定拼团分类下的子分类的数组
 *
 * @access  public
 * @param int $cat_id 分类的ID
 * @param int $selected 当前选中分类的ID
 * @param boolean $re_type 返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param int $level 限定返回的级数。为0时返回所有级数
 * @return  mix
 */
function team_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
{
    static $res = null;
    if ($res === null) {
        $data = read_static_cache('team_cat_pid_releate');
        if ($data === false) {
            $sql = "SELECT c.*, COUNT(s.id) AS has_children" .
                " FROM {pre}team_category AS c " .
                " LEFT JOIN {pre}team_category  AS s ON s.parent_id=c.id" .
                " where c.status = 1" .
                " GROUP BY c.id " .
                " ORDER BY parent_id, sort_order DESC";
            $res = $GLOBALS['dsc']->query($sql);
            write_static_cache('team_cat_pid_releate', $res);
        } else {
            $res = $data;
        }
    }

    if (empty($res) == true) {
        return $re_type ? '' : [];
    }

    $options = team_cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    /* 截取到指定的缩减级别 */
    if ($level > 0) {
        if ($cat_id == 0) {
            $end_level = $level;
        } else {
            $first_item = reset($options); // 获取第一个元素
            $end_level = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options as $key => $val) {
            if ($val['level'] >= $end_level) {
                unset($options[$key]);
            }
        }
    }

    $pre_key = 0;
    foreach ($options as $key => $value) {
        $options[$key]['has_children'] = 1;
        if ($pre_key > 0) {
            if ($options[$pre_key]['id'] == $options[$key]['parent_id']) {
                $options[$pre_key]['has_children'] = 1;
            }
        }
        $pre_key = $key;
    }

    if ($re_type == true) {
        $select = '';
        foreach ($options as $var) {
            $select .= '<option value="' . $var['id'] . '" ';
            //$select .= ' cat_type="' . $var['cat_type'] . '" ';
            $select .= ($selected == $var['id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0) {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['name'])) . '</option>';
        }

        return $select;
    } else {
        foreach ($options as $key => $value) {
            $options[$key]['url'] = app(DscRepository::class)->buildUri('article_cat', ['acid' => $value['cat_id']], $value['cat_name']);
        }
        return $options;
    }
}

/**
 * 过滤和排序所有拼团，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param int $cat_id 上级分类ID
 * @param array $arr 含有所有分类的数组
 * @param int $level 级别
 * @return  void
 */
function team_cat_options($spec_cat_id, $arr)
{
    static $cat_options = [];
    if (isset($cat_options[$spec_cat_id])) {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0])) {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = [];
        while (!empty($arr)) {
            foreach ($arr as $key => $value) {
                $cat_id = $value['id'];
                if ($level == 0 && $last_cat_id == 0) {
                    if ($value['parent_id'] > 0) {
                        break;
                    }
                    $options[$cat_id] = $value;
                    $options[$cat_id]['level'] = $level;
                    $options[$cat_id]['id'] = $cat_id;
                    $options[$cat_id]['name'] = $value['name'];
                    unset($arr[$key]);

                    if ($value['has_children'] == 0) {
                        continue;
                    }
                    $last_cat_id = $cat_id;

                    $cat_id_array = [$cat_id];
                    $level_array[$last_cat_id] = ++$level;
                    continue;
                }

                if ($value['parent_id'] == $last_cat_id) {
                    $options[$cat_id] = $value;
                    $options[$cat_id]['level'] = $level;
                    $options[$cat_id]['id'] = $cat_id;
                    $options[$cat_id]['name'] = $value['name'];

                    unset($arr[$key]);

                    if ($value['has_children'] > 0) {
                        if (end($cat_id_array) != $last_cat_id) {
                            $cat_id_array[] = $last_cat_id;
                        }
                        $last_cat_id = $cat_id;
                        $cat_id_array[] = $cat_id;
                        $level_array[$last_cat_id] = ++$level;
                    }
                } elseif ($value['parent_id'] > $last_cat_id) {
                    break;
                }
            }

            $count = count($cat_id_array);
            if ($count > 1) {
                $last_cat_id = array_pop($cat_id_array);
            } elseif ($count == 1) {
                if ($last_cat_id != end($cat_id_array)) {
                    $last_cat_id = end($cat_id_array);
                } else {
                    $level = 0;
                    $last_cat_id = 0;
                    $cat_id_array = [];
                    continue;
                }
            }

            if ($last_cat_id && isset($level_array[$last_cat_id])) {
                $level = $level_array[$last_cat_id];
            } else {
                $level = 0;
            }
        }
        $cat_options[0] = $options;
    } else {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id) {
        return $options;
    } else {
        if (empty($options[$spec_cat_id])) {
            return [];
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options as $key => $value) {
            if ($key != $spec_cat_id) {
                unset($options[$key]);
            } else {
                break;
            }
        }

        $spec_cat_id_array = [];
        foreach ($options as $key => $value) {
            if (($spec_cat_id_level == $value['level'] && $value['id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level'])
            ) {
                break;
            } else {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}


/**
 * 记录拼团退款资金变动
 * @param int $user_id 用户id
 * @param float $user_money 可用余额变动
 * @param float $frozen_money 冻结余额变动
 * @param int $rank_points 等级积分变动
 * @param int $pay_points 消费积分变动
 * @param string $change_desc 变动说明
 * @param int $change_type 变动类型：参见常量文件
 * @return  void
 */
function team_log_account_change($user_id, $shop_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type)
{
    if ($change_type == ACT_TRANSFERRED) {
        /* 插入帐户变动记录 */
        $account_log = [
            'user_id' => $user_id,
            'user_money' => -$shop_money,
            'frozen_money' => $frozen_money,
            'rank_points' => -$rank_points,
            'pay_points' => -$pay_points,
            'change_time' => gmtime(),
            'change_desc' => $change_desc,
            'change_type' => $change_type
        ];
        $GLOBALS['dsc']->autoExecute($GLOBALS['dsc']->table('account_log'), $account_log, 'INSERT');
    }
    if ($change_type == ACT_TRANSFERRED) {
        /* 更新用户信息 */
        $sql = "UPDATE " . $GLOBALS['dsc']->table('users') .
            " SET user_money = user_money - ('$shop_money')," .
            " frozen_money = frozen_money - ('$frozen_money')," .
            " rank_points = rank_points - ('$rank_points')," .
            " pay_points = pay_points - ('$pay_points')" .
            " WHERE user_id = '$user_id' LIMIT 1";
        $GLOBALS['dsc']->query($sql);
    }
}


/**
 * 记录修改拼团订单状态
 * @param int $order_id 订单id
 * @param int $action_user 操作人员
 * @param int $order_status 订单状态
 * @param int $shipping_status 配送状态
 * @param int $pay_status 支付状态
 * @param string $action_note 变动说明
 * @return  void
 */
function team_order_action_change($order_id, $action_user = 'admin', $order_status = 0, $shipping_status = 0, $pay_status = 0, $action_note = '')
{
    /* 插入订单变动记录 */
    $action_log = [
        'order_id' => $order_id,
        'action_user' => $action_user,
        'order_status' => $order_status,
        'shipping_status' => $shipping_status,
        'pay_status' => $pay_status,
        'action_note' => $action_note,
        'log_time' => gmtime()
    ];
    $GLOBALS['dsc']->autoExecute($GLOBALS['dsc']->table('order_action'), $action_log, 'INSERT');

    /* 更新订单状态 */
    $sql = "UPDATE " . $GLOBALS['dsc']->table('order_info') .
        " SET order_status = $order_status," .
        " shipping_status = $shipping_status," .
        " pay_status = $pay_status" .
        " WHERE order_id = '$order_id' LIMIT 1";
    $GLOBALS['dsc']->query($sql);
}

/**
 * 拼团主频道，获取子频道列表
 *
 * @param int $tc_id 拼团频道ID
 * @return array
 */
function teamCategories($tc_id = 0)
{
    $team = TeamCategory::whereRaw(1);
    if ($tc_id > 0) {
        $team->where('parent_id', $tc_id);
    } else {
        $team->where('parent_id', 0);
    }
    $team = $team->where('status', 1)
        ->orderby('id', 'asc');

    $team_list = app(BaseRepository::class)->getToArrayGet($team);

    $list = [];
    if ($team_list) {
        foreach ($team_list as $key => $val) {
            $list[$key]['tc_id'] = $val['id'];
            $list[$key]['name'] = $val['name'];
            $list[$key]['tc_img'] = app(DscRepository::class)->getImagePath($val['tc_img']);
        }
    }

    return $list;
}