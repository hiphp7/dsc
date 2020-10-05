<?php

use App\Models\Suppliers;
use App\Models\Wholesale;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleProducts;
use App\Repositories\Common\BaseRepository;

/**
 * 供应商基本信息
 */
function get_suppliers_info($suppliers_id = 0, $select = array())
{
    if ($select && is_array($select)) {
        $select = implode(',', $select);
    } else {
        $select = '*';
    }

    $sql = "SELECT $select FROM " . $GLOBALS['dsc']->table('suppliers') . " WHERE suppliers_id = '$suppliers_id' LIMIT 1";
    return $GLOBALS['db']->getRow($sql);
}

/**
 * 供应商帐户变动
 *
 * @access  public
 * @param int $cat_id 分类的ID
 * @return  mix
 */
function log_suppliers_account_change($suppliers_id, $suppliers_money = 0, $frozen_money = 0)
{
    if ($suppliers_money || $frozen_money) {
        /* 更新用户信息 */
        $sql = "UPDATE " . $GLOBALS['dsc']->table('suppliers') .
            " SET suppliers_money = suppliers_money + ('$suppliers_money')," .
            " frozen_money = frozen_money + ('$frozen_money')" .
            " WHERE suppliers_id = '$suppliers_id' LIMIT 1";
        $GLOBALS['db']->query($sql);
    }
}

/**
 * 供应商帐户变动记录
 *
 * @access  public
 * @param int $cat_id 分类的ID
 * @return  mix
 */
function suppliers_account_log($suppliers_id, $user_money = 0, $frozen_money = 0, $change_desc, $change_type = 1)
{
    if ($user_money || $frozen_money) {
        $log = array(
            'user_id' => $suppliers_id,
            'user_money' => $user_money,
            'frozen_money' => $frozen_money,
            'change_time' => gmtime(),
            'change_desc' => $change_desc,
            'change_type' => $change_type
        );
        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('suppliers_account_log'), $log, 'INSERT');
    }
}

/**
 * 资金管理日志
 */
function get_suppliers_account_log()
{
    $result = get_filter();
    if ($result === false) {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sal.change_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['user_id'] = empty($_REQUEST['suppliers_id']) ? 0 : intval($_REQUEST['suppliers_id']);
        $adminru = get_admin_ru_id();
        $ex_where = ' WHERE s.review_status = 3';
        if ($adminru['suppliers_id'] > 0) {
            $ex_where .= ' AND sal.user_id="' . $adminru['suppliers_id'] . '" ';
        } elseif ($filter['user_id'] > 0) {
            $ex_where .= ' AND sal.user_id="' . $filter['user_id'] . '" ';
        } elseif ($filter['keywords']) {
            $sql = "SELECT suppliers_id FROM" . $GLOBALS['dsc']->table('suppliers') . " WHERE(suppliers_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' OR suppliers_desc LIKE '%" . mysql_like_quote($filter['keywords']) . "%') ";
            $user_id = $GLOBALS['db']->getOne($sql);
            $ex_where .= ' AND sal.user_id="' . $user_id . '" ';
        }

        $sql = "SELECT count(*) FROM " . $GLOBALS['dsc']->table('suppliers_account_log') . " AS sal " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('suppliers') . " AS s ON sal.user_id = s.suppliers_id " .
            " $ex_where";

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT sal.*, s.suppliers_name FROM " . $GLOBALS['dsc']->table('suppliers_account_log') . " AS sal " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('suppliers') . " AS s ON sal.user_id = s.suppliers_id " .
            " $ex_where " .
            " ORDER BY " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
            " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $res = $GLOBALS['db']->getAll($sql);

    $arr = array();
    for ($i = 0; $i < count($res); $i++) {
        $res[$i]['change_time'] = local_date($GLOBALS['_CFG']['time_format'], $res[$i]['change_time']);
    }

    $arr = array('log_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 申请日志列表
 */
function get_suppliers_account_log_detail($suppliers_id, $type = 0)
{
    $result = get_filter();
    if ($result === false) {
        /* 过滤条件 */
        $filter['keywords'] = !isset($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['order_id'] = !isset($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
        $filter['order_sn'] = !isset($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['out_up'] = !isset($_REQUEST['out_up']) ? 0 : intval($_REQUEST['out_up']);
        $filter['log_type'] = !isset($_REQUEST['log_type']) ? 0 : intval($_REQUEST['log_type']);
        $filter['handler'] = !isset($_REQUEST['handler']) ? 0 : intval($_REQUEST['handler']);
        $filter['rawals'] = !isset($_REQUEST['rawals']) ? 0 : intval($_REQUEST['rawals']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sal.log_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['act_type'] = !isset($_REQUEST['act_type']) ? 'detail' : $_REQUEST['act_type'];
        $filter['suppliers_id'] = !isset($_REQUEST['suppliers_id']) ? $suppliers_id : intval($_REQUEST['suppliers_id']);

        $ex_where = ' WHERE 1 ';

        //订单编号
        if ($filter['order_sn']) {
            $ex_where .= " AND (sal.apply_sn = '" . $filter['order_sn'] . "'";
            $ex_where .= " OR ";
            $ex_where .= " (SELECT order_sn FROM " . $GLOBALS['dsc']->table("order_info") . " AS oi WHERE sal.order_id = oi.order_id LIMIT 1) = '" . $filter['order_sn'] . "')";
        }

        //收入/支出
        if ($filter['out_up']) {
            if ($filter['out_up'] != 4) {
                if ($filter['out_up'] == 3) {
                    $ex_where .= " AND sal.log_type = '" . $filter['out_up'] . "'";
                }
                $ex_where .= " AND (sal.log_type > '" . $filter['out_up'] . "' OR sal.log_type =  '" . $filter['out_up'] . "')";
            } else {
                $ex_where .= " AND sal.log_type = '" . $filter['out_up'] . "'";
            }
        }
        if ($filter['rawals'] == 1) {
            $type = array(1);
        }
        //待处理
        if ($filter['handler']) {
            if ($filter['handler'] == 1) {
                $ex_where .= " AND sal.is_paid = 1";
            } else {
                $ex_where .= " AND sal.is_paid = 0";
            }
        }

        //类型
        if ($filter['log_type']) {
            $ex_where .= " AND sal.log_type = '" . $filter['log_type'] . "'";
        }
        if ($filter['order_id']) {
            $ex_where .= " AND sal.order_id = '" . $filter['order_id'] . "'";
        }
        $type = implode(',', $type);

        if ($filter['suppliers_id']) {
            $ex_where .= " AND sal.suppliers_id = '" . $filter['suppliers_id'] . "'";
        }

        $sql = "SELECT count(*) FROM " . $GLOBALS['dsc']->table('suppliers_account_log_detail') . " AS sal " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('suppliers') . " AS s ON sal.suppliers_id = s.suppliers_id " .
            " $ex_where AND sal.log_type IN($type)";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $sql = "SELECT sal.*, s.suppliers_name FROM " . $GLOBALS['dsc']->table('suppliers_account_log_detail') . " AS sal " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('suppliers') . " AS s ON sal.suppliers_id = s.suppliers_id " .
            " $ex_where AND sal.log_type IN($type)" .
            " ORDER BY " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
            " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $res = $GLOBALS['db']->getAll($sql);

    for ($i = 0; $i < count($res); $i++) {
        $order = order_info($res[$i]['order_id']);
        $res[$i]['order_sn'] = !empty($order['order_sn']) ? sprintf(lang('order.order_remark'), $order['order_sn']) : $res[$i]['apply_sn'];
        $res[$i]['amount'] = price_format($res[$i]['amount'], false);
        $res[$i]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $res[$i]['add_time']);
        $res[$i]['payment_info'] = payment_info($res[$i]['pay_id']);
        $res[$i]['apply_sn'] = sprintf($GLOBALS['_LANG']['01_apply_sn'], $res[$i]['apply_sn']);
    }

    $arr = array('log_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

//获取统计数据
function get_suppliers_statistical_data($start_date = 0, $end_date = 0, $type = 'order', $suppliers_id = 0)
{
    $data = array();

    //格林威治时间与本地时间差
    $timezone = session()->has('timezone') ? session('timezone') : $GLOBALS['_CFG']['timezone'];
    $time_diff = $timezone * 3600;
    $date_start = $start_date;
    $date_end = $end_date;
    $day_num = ceil($date_end - $date_start) / 86400;

    $where_date = '';
    //获取系统数据 start
    $no_main_order = " AND (SELECT count(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS oi2 WHERE oi2.main_order_id = oi.order_id) = 0 "; //主订单下有子订单时，则主订单不显示
    if ($suppliers_id > 0) {
        $where_date .= " AND oi.suppliers_id = '" . $suppliers_id . "'";
    }

    $sql = 'SELECT DATE_FORMAT(FROM_UNIXTIME(oi.add_time + ' . $time_diff . '),"%y-%m-%d") AS day,COUNT(*) AS count,SUM(oi.order_amount) AS money FROM ' .
        $GLOBALS['dsc']->table('wholesale_order_info') . " AS oi" . ' WHERE oi.pay_status =2 AND oi.order_status = 1 AND  oi.add_time BETWEEN ' . $date_start .
        ' AND ' . $date_end . $no_main_order . $where_date . '  GROUP BY day ORDER BY day ASC ';
    $result = $GLOBALS['db']->getAll($sql);

    foreach ($result as $key => $row) {
        $orders_series_data[$row['day']] = intval($row['count']);
        $sales_series_data[$row['day']] = floatval($row['money']);
    }

    for ($i = 1; $i <= $day_num; $i++) {
        $day = local_date("y-m-d", local_strtotime(" - " . ($day_num - $i) . " days"));
        if (empty($orders_series_data[$day])) {
            $orders_series_data[$day] = 0;
            $sales_series_data[$day] = 0;
        }
        //输出时间
        $day = local_date("m-d", local_strtotime($day));
        $orders_xAxis_data[] = $day;
        $sales_xAxis_data[] = $day;
    }

    //获取系统数据 end

    //图表公共数据 start
    $title = array(
        'text' => '',
        'subtext' => ''
    );

    $toolbox = array(
        'show' => true,
        'orient' => 'vertical',
        'x' => 'right',
        'y' => '60',
        'feature' => array(
            'magicType' => array(
                'show' => true,
                'type' => array('line', 'bar')
            ),
            'saveAsImage' => array(
                'show' => true
            )
        )
    );
    $tooltip = array('trigger' => 'axis',
        'axisPointer' => array(
            'lineStyle' => array(
                'color' => '#6cbd40'
            )
        )
    );
    $xAxis = array(
        'type' => 'category',
        'boundaryGap' => false,
        'axisLine' => array(
            'lineStyle' => array(
                'color' => '#ccc',
                'width' => 0
            )
        ),
        'data' => array());
    $yAxis = array(
        'type' => 'value',
        'axisLine' => array(
            'lineStyle' => array(
                'color' => '#ccc',
                'width' => 0
            )
        ),
        'axisLabel' => array(
            'formatter' => ''));
    $series = array(
        array(
            'name' => '',
            'type' => 'line',
            'itemStyle' => array(
                'normal' => array(
                    'color' => '#6cbd40',
                    'lineStyle' => array(
                        'color' => '#6cbd40'
                    )
                )
            ),
            'data' => array(),
            'markPoint' => array(
                'itemStyle' => array(
                    'normal' => array(
                        'color' => '#6cbd40'
                    )
                ),
                'data' => array(
                    array(
                        'type' => 'max',
                        'name' => lang('order.max_value')
                    ),
                    array(
                        'type' => 'min',
                        'name' => lang('order.min_value')
                    )
                )
            )
        ),
        array(
            'type' => 'force',
            'name' => '',
            'draggable' => false,
            'nodes' => array(
                'draggable' => false
            )
        )
    );
    $calculable = true;
    $legend = array('data' => array());
    //图表公共数据 end

    //订单统计
    if ($type == 'order') {
        $title['text'] = lang('order.order_number');
        $xAxis['data'] = $orders_xAxis_data;
        $yAxis['formatter'] = '{value}' . lang('order.individual');
        ksort($orders_series_data);
        $series[0]['name'] = lang('order.order_individual_count');
        $series[0]['data'] = array_values($orders_series_data);
    }

    //销售统计
    if ($type == 'sale') {
        $title['text'] = lang('order.sale_money');
        $xAxis['data'] = $sales_xAxis_data;
        $yAxis['formatter'] = '{value}' . lang('order.money_unit');
        ksort($sales_series_data);
        $series[0]['name'] = lang('order.sale_money');
        $series[0]['data'] = array_values($sales_series_data);
    }

    //整理数据
    $data['title'] = $title;
    $data['series'] = $series;
    $data['tooltip'] = $tooltip;
    $data['legend'] = $legend;
    $data['toolbox'] = $toolbox;
    $data['calculable'] = $calculable;
    $data['xAxis'] = $xAxis;
    $data['yAxis'] = $yAxis;

    return $data;
}

/**
 * 申请日志详细信息
 */
function get_suppliers_account_log_info($log_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('suppliers_account_log_detail') . " WHERE log_id = '$log_id' LIMIT 1";
    $res = $GLOBALS['db']->getRow($sql);

    if ($res) {
        $info = get_suppliers_info($res['suppliers_id']);
        $res['suppliers_name'] = $info['suppliers_name'];
        $res['payment_info'] = payment_info($res['pay_id']);
        $res['ru_id'] = $info['user_id'];

        /* 供应商资金 start */
        $res['suppliers_money'] = $info['suppliers_money']; //供应商可提现金额
        $res['suppliers_frozen'] = $info['frozen_money']; //供应商冻结金额
        /* 供应商资金 end */
    }

    return $res;
}


/**
 * 改变订单中商品库存
 * @param int $order_id 订单号
 * @param bool $is_dec 是否减少库存
 * @param bool $storage 减库存的时机，2，付款时； 1，下订单时；0，发货时；
 */
function suppliers_change_order_goods_storage($order_id, $is_dec = true, $storage = 0)
{
    $order_res = WholesaleOrderInfo::where('main_order_id', $order_id);
    $order_res = app(BaseRepository::class)->getToArrayGet($order_res);

    foreach ($order_res as $row) {
        $order_res = WholesaleOrderGoods::where('order_id', $row['order_id']);
        $res = app(BaseRepository::class)->getToArrayGet($order_res);

        foreach ($res as $row) {
            if ($is_dec) {
                suppliers_change_goods_storage($row['goods_id'], $row['product_id'], -$row['goods_number']);
            }
        }
    }
}

/**
 * 商品库存增与减 货品库存增与减
 *
 * @param int $goods_id 商品ID
 * @param int $product_id 货品ID
 * @param int $number 增减数量，默认0；
 * @return  bool                    true，成功；false，失败；
 */
function suppliers_change_goods_storage($goods_id = 0, $product_id = 0, $number = 0)
{
    if (!empty($product_id)) {
        $res = WholesaleProducts::whereRaw(1);
        $set_update = "product_number $number";
        $other = [
            'product_number' => DB::raw($set_update)
        ];
        $res->where('goods_id', $goods_id)
            ->where('product_id', $product_id)
            ->update($other);
    } else {
        $set_update = "goods_number $number";
        $other = [
            'goods_number' => DB::raw($set_update)
        ];
        Wholesale::where('goods_id', $goods_id)
            ->update($other);
    }

    return true;
}

/**
 * 供应商获取配置客服QQ
 *
 * @param int $goods_id 供应商ID；
 * @return
 */
function get_suppliers_kf($suppliers_id = 0)
{
    $kf_qq = Suppliers::where('suppliers_id', $suppliers_id)->value('kf_qq');
    if (!empty($kf_qq)) {
        $kf_qq = array_filter(preg_split('/\s+/', $kf_qq));
        foreach ($kf_qq as $k => $v) {
            $row['kf_qq_all'][] = explode("|", $v);
        }
        $kf_qq = $kf_qq && $kf_qq[0] ? explode("|", $kf_qq[0]) : [];
        if (isset($kf_qq[1]) && !empty($kf_qq[1])) {
            $row['kf_qq'] = $kf_qq[1];
        } else {
            $row['kf_qq'] = "";
        }

        return $row;
    }

    return [];

}




