<?php

use App\Models\MerchantsShopInformation;
use App\Repositories\Common\BaseRepository;
use App\Services\Category\CategoryService;
use App\Services\Commission\CommissionService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;

/***************** 统计计算 *****************/

//店铺数量
function statistical_field_shop_num()
{
    return "COUNT(DISTINCT spi.ru_id)";
}

//下单量
function statistical_field_order_num()
{
    return "COUNT(DISTINCT o.order_id)";
}

//下单会员总数
function statistical_field_user_num($alias = 'o.')
{
    return "COUNT(DISTINCT {$alias}user_id)";
}

//退款订单数量
function statistical_field_return_num()
{
    return "COUNT(DISTINCT re.order_id)";
}

//商品数量
function statistical_field_goods_num()
{
    return "COUNT(DISTINCT g.goods_id)";
}

//订单商品数量
function statistical_field_order_goods_num()
{
    return "COUNT(DISTINCT og.goods_id)";
}

//未下单商品数量
function statistical_field_no_order_goods_num()
{
    return statistical_field_goods_num() . "-" . statistical_field_order_goods_num();
}

//有效下单数量
function statistical_field_valid_num()
{
    //return "SUM(IF(o.order_status!=".OS_INVALID.", 1, 0))"; //会有重复情况
    return "COUNT(DISTINCT IF(o.order_status!=" . OS_INVALID . ", o.order_id, NULL))";
}

//下单总金额
function statistical_field_total_fee()
{
    return "SUM(" . app(OrderService::class)->orderAmountField('o.') . ")";
}

//有效下单金额
function statistical_field_valid_fee()
{
    return "SUM(IF(o.order_status!=" . OS_INVALID . ", " . app(OrderService::class)->orderAmountField('o.') . ", 0))";
}

//退款总金额
function statistical_field_return_fee()
{
    return "SUM(re.actual_return)";
}

//销售额
function statistical_field_sale_money()
{
    return "SUM(o.money_paid + o.surplus)";
}

//订单商品销量
function statistical_field_order_goods_number()
{
    return "SUM(og.goods_number)";
}

//订单商品销售额
function statistical_field_order_goods_amount()
{
    return "SUM(og.goods_number * og.goods_price)";
}

//订单商品销售总额
function statistical_field_goods_amount()
{
    return "SUM(o.goods_amount)";
}

//有效商品销售额
function statistical_field_valid_goods_amount()
{
    return "(" . statistical_field_order_goods_amount() . "/" . statistical_field_goods_amount() . "*" . statistical_field_valid_fee() . ")";
}

//订单商品平均价格
function statistical_field_average_goods_price()
{
    return "ROUND(" . statistical_field_order_goods_amount() . "/" . statistical_field_order_goods_number() . ", 2)";
}

//平均客单价
function statistical_field_average_total_fee()
{
    return "ROUND(" . statistical_field_total_fee() . '/' . statistical_field_order_num() . ", 2)";
}

//有效平均客单价
function statistical_field_average_valid_fee()
{
    return "ROUND(" . statistical_field_valid_fee() . '/' . statistical_field_valid_num() . ", 2)";
}

//会员充值金额
function statistical_field_user_recharge_money()
{
    return "SUM(IF(al.change_type=0, al.user_money, 0))";
}

//会员消费金额
function statistical_field_user_consumption_money()
{
    return "SUM(IF(al.change_type=99 AND al.user_money<0, al.user_money, 0))";
}

//会员提现金额
function statistical_field_user_cash_money()
{
    return "SUM(IF(al.change_type=1, al.frozen_money, 0))";
}

//会员退款金额
function statistical_field_user_return_money()
{
    return "SUM(IF(al.change_type=99 AND al.user_money>0, al.user_money, 0))";
}

//会员剩余金额
function statistical_field_user_money()
{
    return "SUM(u.user_money)";
}

/***************** 统计计算 *****************/

//格林威治时间与本地时间差
function get_time_diff()
{
    $timezone = session()->has('timezone') ? session('timezone') : $GLOBALS['_CFG']['timezone'];
    $time_diff = $timezone * 3600;
    return $time_diff;
}

//新增店铺
function get_statistical_new_shop($search_data = array())
{
    $data = array();

    $date_start = $search_data['start_date'];
    $date_end = $search_data['end_date'];

    //时间差
    $time_diff = get_time_diff();
    $day_num = ceil($date_end - $date_start) / 86400;

    //获取系统数据 start
    $result = MerchantsShopInformation::selectRaw("FROM_UNIXTIME(add_time + $time_diff, '%y-%m-%d') AS day, COUNT(*) AS count")
        ->whereBetween('add_time', [$date_start, $date_end]);

    //筛选条件
    if (isset($search_data['shop_categoryMain']) && !empty($search_data['shop_categoryMain'])) {
        $result = $result->where('shop_categoryMain', $search_data['shop_categoryMain']);
    }

    if (isset($search_data['shopNameSuffix']) && !empty($search_data['shopNameSuffix'])) {
        $result = $result->where('shopNameSuffix', $search_data['shopNameSuffix']);
    }

    $result = $result->GroupBy('day');
    $result = $result->orderBy('day');

    $result = app(BaseRepository::class)->getToArrayGet($result);

    if ($result) {
        foreach ($result as $key => $row) {
            $series_data[$row['day']] = intval($row['count']);
        }
    }

    for ($i = 1; $i <= $day_num; $i++) {
        $day = local_date("y-m-d", local_strtotime(" - " . ($day_num - $i) . " days"));
        if (empty($series_data[$day])) {
            $series_data[$day] = 0;
        }
        //输出时间
        $day = local_date("m-d", local_strtotime($day));
        $xAxis_data[] = $day;
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
                        'name' => '最大值'),
                    array(
                        'type' => 'min',
                        'name' => '最小值')
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

    //数据统计
    $title['text'] = '';
    $xAxis['data'] = $xAxis_data;
    $yAxis['formatter'] = '{value}个';
    ksort($series_data);
    $series[0]['name'] = '新增店铺';
    $series[0]['data'] = array_values($series_data);

    //整理数据
    $data['title'] = $title;
    $data['series'] = $series;
    $data['tooltip'] = $tooltip;
    $data['legend'] = $legend;
    $data['toolbox'] = $toolbox;
    $data['calculable'] = $calculable;
    $data['xAxis'] = $xAxis;
    $data['yAxis'] = $yAxis;
    $data['xy_file'] = get_dir_file_list();

    return $data;
}

/**
 *  店铺综合统计
 *
 * @access  public
 * @param
 *
 * @return void
 */
function shop_total_stats()
{
    /* 过滤信息 */
    $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
    $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : (strpos($_REQUEST['start_date'], '-') > 0 ? local_strtotime($_REQUEST['start_date']) : $_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (strpos($_REQUEST['end_date'], '-') > 0 ? local_strtotime($_REQUEST['end_date']) : $_REQUEST['end_date']);
    $filter['shop_categoryMain'] = empty($_REQUEST['shop_categoryMain']) ? 0 : intval($_REQUEST['shop_categoryMain']);
    $filter['shopNameSuffix'] = empty($_REQUEST['shopNameSuffix']) ? '' : trim($_REQUEST['shopNameSuffix']);

    if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
        $filter['keywords'] = json_str_iconv($filter['keywords']);
    }

    /* 查询语句 */
    $where_msi = ' WHERE 1 AND o.main_count = 0 ';

    if ($filter['start_date']) {
        $where_msi .= " AND o.add_time >= '$filter[start_date]'";
    }
    if ($filter['end_date']) {
        $where_msi .= " AND o.add_time <= '$filter[end_date]'";
    }
    if ($filter['keywords']) {
        $where_msi .= " AND (msi.rz_shopName LIKE '%" . $filter['keywords'] . "%')";
    }
    if ($filter['shop_categoryMain']) {
        $where_msi .= " AND msi.shop_categoryMain = '$filter[shop_categoryMain]'";
    }
    if ($filter['shopNameSuffix']) {
        $where_msi .= " AND msi.shopNameSuffix = '$filter[shopNameSuffix]'";
    }

    /* 关联查询 */
    $leftJoin = " LEFT JOIN " . $GLOBALS['dsc']->table('order_return') . " AS re ON re.order_id = o.order_id ";
    $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('merchants_shop_information') . " AS msi ON msi.user_id = o.ru_id ";

    /* 查询 */
    $sql = "SELECT " .
        statistical_field_order_num() . " AS total_order_num, " . //下单量
        statistical_field_user_num() . " AS total_user_num, " .  //下单会员总数
        statistical_field_return_num() . " AS total_return_num, " .  //退款订单数量
        statistical_field_valid_num() . " AS total_valid_num, " . //有效下单量
        statistical_field_total_fee() . " AS total_fee, " . //下单金额
        statistical_field_valid_fee() . " AS valid_fee, " . //有效下单金额
        statistical_field_return_fee() . " AS return_amount " . //退款金额
        " FROM " . $GLOBALS['dsc']->table('order_info') . " AS o " .
        $leftJoin .
        $where_msi;

    $row = $GLOBALS['db']->getRow($sql);

    /* 格式化数据 */
    foreach ($row as $key => $val) {
        $row[$key] = (!isset($val) || empty($val)) ? '0' : $val;
    }

    return $row;
}

//新增会员
function get_statistical_new_user($search_data = array())
{
    $data = array();

    //筛选条件
    $where_data = "";
    $date_start = $search_data['start_date'];
    $date_end = $search_data['end_date'];

    //时间差
    $time_diff = get_time_diff();
    $day_num = ceil($date_end - $date_start) / 86400;

    //获取系统数据 start
    $sql = "SELECT FROM_UNIXTIME(u.reg_time+$time_diff,'%y-%m-%d') AS day, COUNT(*) AS count FROM " . $GLOBALS['dsc']->table('users') . " AS u" . ' WHERE u.reg_time BETWEEN ' . $date_start . ' AND ' . $date_end . $where_data . ' GROUP BY day ORDER BY day ASC ';
    $result = $GLOBALS['db']->getAll($sql);

    if ($result) {
        foreach ($result as $key => $row) {
            $series_data[$row['day']] = intval($row['count']);
        }
    }

    for ($i = 1; $i <= $day_num; $i++) {
        $day = local_date("y-m-d", local_strtotime(" - " . ($day_num - $i) . " days"));
        if (empty($series_data[$day])) {
            $series_data[$day] = 0;
        }
        //输出时间
        $day = local_date("m-d", local_strtotime($day));
        $xAxis_data[] = $day;
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
                        'name' => '最大值'),
                    array(
                        'type' => 'min',
                        'name' => '最小值')
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

    //数据统计
    $title['text'] = '';
    $xAxis['data'] = $xAxis_data;
    $yAxis['formatter'] = '{value}个';
    ksort($series_data);
    $series[0]['name'] = '新增会员';
    $series[0]['data'] = array_values($series_data);

    //整理数据
    $data['title'] = $title;
    $data['series'] = $series;
    $data['tooltip'] = $tooltip;
    $data['legend'] = $legend;
    $data['toolbox'] = $toolbox;
    $data['calculable'] = $calculable;
    $data['xAxis'] = $xAxis;
    $data['yAxis'] = $yAxis;
    $data['xy_file'] = get_dir_file_list();

    return $data;
}

//销售分析
function get_statistical_sale($search_data = array())
{
    $data = array();
    $data['total_volume'] = 0;
    $data['total_money'] = 0;

    //筛选条件
    $where_data = "";
    $date_start = $search_data['start_date'];
    $date_end = $search_data['end_date'];

    //时间差
    $time_diff = get_time_diff();
    $day_num = ceil($date_end - $date_start) / 86400;

    //获取系统数据 start
    $sql = " SELECT FROM_UNIXTIME(o.add_time+$time_diff,'%y-%m-%d') AS day, " .
        statistical_field_order_num() . " AS volume, " .
        statistical_field_sale_money() . " AS money " .
        " FROM " . $GLOBALS['dsc']->table('order_info') . " AS o" . ' WHERE o.main_count = 0 AND o.add_time BETWEEN ' . $date_start . ' AND ' . $date_end . $where_data . ' GROUP BY day ORDER BY day ASC ';
    $result = $GLOBALS['db']->getAll($sql);

    if ($result) {
        foreach ($result as $key => $row) {
            if ($search_data['type'] == 'money') {
                $series_data[$row['day']] = floatval($row['money']);
                $data['total_money'] += floatval($row['money']);
            } else {
                $series_data[$row['day']] = intval($row['volume']);
                $data['total_volume'] += floatval($row['volume']);
            }
        }
    }

    for ($i = 1; $i <= $day_num; $i++) {
        $day = local_date("y-m-d", local_strtotime(" - " . ($day_num - $i) . " days"));
        if (empty($series_data[$day])) {
            $series_data[$day] = 0;
        }
        //输出时间
        $day = local_date("m-d", local_strtotime($day));
        $xAxis_data[] = $day;
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
                        'name' => lang('order.max_value')),
                    array(
                        'type' => 'min',
                        'name' => lang('order.min_value'))
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

    //数据统计
    $title['text'] = '';
    $xAxis['data'] = $xAxis_data;
    $yAxis['formatter'] = '{value}' . lang('order.individual');
    ksort($series_data);
    $series[0]['name'] = ($search_data['type'] == 'money') ? lang('order.sale_money') : lang('order.goods_salevolume');
    $series[0]['data'] = array_values($series_data);

    //整理数据
    $data['title'] = $title;
    $data['series'] = $series;
    $data['tooltip'] = $tooltip;
    $data['legend'] = $legend;
    $data['toolbox'] = $toolbox;
    $data['calculable'] = $calculable;
    $data['xAxis'] = $xAxis;
    $data['yAxis'] = $yAxis;
    $data['xy_file'] = get_dir_file_list();

    return $data;
}

//会员等级统计
function get_statistical_user_rank()
{
    $data = array();

    $sql = " SELECT rank_id, rank_name, special_rank FROM " . $GLOBALS['dsc']->table('user_rank');
    $arr = $GLOBALS['db']->getAll($sql);
    $rank_list = array_column($arr, 'rank_id');
    foreach ($arr as $key => $val) {
        $arr[$key]['user_num'] = get_table_date('users', "user_rank='$val[rank_id]'", array('COUNT(*)'), 2);
    }

    //没有会员等级
    $no_rank_user_num = get_table_date('users', 'user_rank' . db_create_in($rank_list, '', 'NOT'), array('COUNT(*)'), 2);
    $no_rank = array(
        'rank_id' => 0,
        'rank_name' => '无等级',
        'user_num' => $no_rank_user_num
    );
    $arr[] = $no_rank;

    //会员总数
    $user_count = get_table_date('users', '1', array('COUNT(*)'), 2);

    //整合数据
    $data['text'] = array();
    $data['list'] = array();
    foreach ($arr as $key => $val) {
        //数据列表
        $data['list'][] = array(
            'name' => $val['rank_name'],
            'value' => $val['user_num']
        );
        //数据标题
        $data['text'][] = $val['rank_name'];
        //占比处理
        $arr[$key]['percent'] = round($val['user_num'] / $user_count, 4) * 100;
    }

    $data['source'] = $arr;

    return $data;
}

/**
 *  行业分析
 *
 * @access  public
 * @param
 *
 * @return void
 */
function industry_analysis($page = 0)
{
    $result = get_filter();
    if ($result === false) {
        /* 过滤信息 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : (strpos($_REQUEST['start_date'], '-') > 0 ? local_strtotime($_REQUEST['start_date']) : $_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (strpos($_REQUEST['end_date'], '-') > 0 ? local_strtotime($_REQUEST['end_date']) : $_REQUEST['end_date']);
        $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);

        /* 默认信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_amount' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        /* 查询语句 */
        $where_c = ' WHERE 1 ';
        $where_o = '';

        if ($filter['start_date']) {
            $where_o .= " AND o.add_time >= '$filter[start_date]'";
        }
        if ($filter['end_date']) {
            $where_o .= " AND o.add_time <= '$filter[end_date]'";
        }
        if ($filter['keywords']) {
            $where_c .= " AND (c.cat_name LIKE '%" . $filter['keywords'] . "%')";
        }
        if ($filter['cat_id']) {
            $where_c .= " AND " . get_children($filter['cat_id'], 0, 0, 'category', 'c.cat_id');
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
        if ($page > 0) {
            $filter['page'] = $page;
        }

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        /* 分组 */
        $groupBy = " GROUP BY c.cat_id ";

        /* 关联查询 */
        $leftJoin = '';
        $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " AS g ON g.cat_id = c.cat_id ";
        $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('order_goods') . " AS og ON og.goods_id = g.goods_id ";
        $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('order_info') . " AS o ON o.order_id = og.order_id ";

        /* 记录总数 */
        $sql = "SELECT c.cat_id FROM " . $GLOBALS['dsc']->table('category') . " AS c " .
            $leftJoin .
            $where_c . $where_o . $groupBy;

        $record_count = count($GLOBALS['db']->getAll($sql));

        $filter['record_count'] = $record_count;
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT c.cat_id, c.cat_name, " .
            statistical_field_order_goods_amount() . " AS goods_amount, " .
            statistical_field_valid_goods_amount() . " AS valid_goods_amount, " .
            statistical_field_goods_num() . " AS goods_num, " .
            statistical_field_no_order_goods_num() . " AS no_order_goods_num, " .
            statistical_field_order_goods_num() . " AS order_goods_num, " .
            statistical_field_user_num() . " AS user_num, " .
            statistical_field_order_num() . " as order_num, " .
            statistical_field_valid_num() . " as valid_num " .
            " FROM " . $GLOBALS['dsc']->table('category') . " AS c " .
            $leftJoin .
            $where_c . $where_o . $groupBy .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    /* 格式化数据 */
    foreach ($row as $key => $value) {
        $row[$key]['formated_goods_amount'] = price_format($value['goods_amount']);
        $row[$key]['formated_valid_goods_amount'] = price_format($value['valid_goods_amount']);
    }

    $arr = array('orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

//新增店铺
function get_statistical_industry_analysis($search_data = array())
{
    $data = array();

    $cat_list = app(CategoryService::class)->catList();
    if ($cat_list) {
        $xAxis_data = array();
        $series_data = array();
        foreach ($cat_list as $key => $val) {
            $where_cat = get_children($key, 0, 0, 'category', 'c.cat_id');
            $xAxis_data[] = $val['cat_alias_name'];
            $sql = " SELECT " .
                statistical_field_order_goods_amount() . " AS order_fee, " .
                statistical_field_order_num() . " AS order_num, " .
                statistical_field_order_goods_number() . " AS order_goods_num " .
                " FROM " . $GLOBALS['dsc']->table('order_goods') . " as og " .
                " LEFT JOIN " . $GLOBALS['dsc']->table('order_info') . " AS o ON o.order_id = og.order_id " .
                " LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " AS g ON g.goods_id = og.goods_id " .
                " LEFT JOIN " . $GLOBALS['dsc']->table('category') . " AS c ON c.cat_id = g.cat_id " .
                " WHERE 1 AND " . $where_cat;
            $data = $GLOBALS['db']->getRow($sql);

            //区分数据
            if ($search_data['type'] == 'order_fee') {
                $series_data[] = floatval($data['order_fee']);
            } elseif ($search_data['type'] == 'order_num') {
                $series_data[] = intval($data['order_num']);
            } elseif ($search_data['type'] == 'order_goods_num') {
                $series_data[] = intval($data['order_goods_num']);
            }
        }

        //区分名称
        if ($search_data['type'] == 'order_fee') {
            $series_name = lang('order.order_amount');
        } elseif ($search_data['type'] == 'order_num') {
            $series_name = lang('order.order_number');
        } elseif ($search_data['type'] == 'order_goods_num') {
            $series_name = lang('order.payorder_goods_number');
        }

        //图表公共数据 start
        $title = array(
            'text' => '',
            'subtext' => ''
        );

        $toolbox = array(
            'show' => true,
            'feature' => array(
                'magicType' => array(
                    'show' => true,
                    'type' => array('line', 'bar')
                ),
                'restore' => array(
                    'show' => true
                ),
                'saveAsImage' => array(
                    'show' => true
                )
            )
        );
        $tooltip = array(
            'trigger' => 'axis');
        $xAxis = array(
            'type' => 'category');
        $yAxis = array(
            'type' => 'value');
        $series = array(
            array(
                'name' => '',
                'type' => 'bar',
                'data' => array(),
                'markPoint' => array(
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
                ),
                'markLine' => array(
                    'data' => array(
                        array(
                            'type' => 'average',
                            'name' => lang('order.average_value')
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

        //数据统计
        $title['text'] = '';
        $xAxis['data'] = $xAxis_data;
        $yAxis['formatter'] = '{value}' . lang('order.individual');
        ksort($series_data);
        $series[0]['name'] = $series_name;
        $series[0]['data'] = array_values($series_data);

        //整理数据
        $data['title'] = $title;
        $data['series'] = $series;
        $data['tooltip'] = $tooltip;
        $data['legend'] = $legend;
        $data['toolbox'] = $toolbox;
        $data['calculable'] = $calculable;
        $data['xAxis'] = $xAxis;
        $data['yAxis'] = $yAxis;
        $data['xy_file'] = get_dir_file_list();
    }

    return $data;
}

//今日销售走势
function get_statistical_today_trend($search_data = array())
{
    $data = array();

    //筛选条件
    $where_data = "";
    $date_start = $search_data['start_date'];
    $date_end = $search_data['end_date'];

    //时间差
    $time_diff = get_time_diff();
    $hour_num = ceil($date_end - $date_start) / 3600;

    //获取系统数据 start
    $sql = " SELECT FROM_UNIXTIME(o.add_time,'%y-%m-%d-%H') AS hour, " .
        statistical_field_order_num() . " AS volume, " .
        statistical_field_sale_money() . " AS money " .
        " FROM " . $GLOBALS['dsc']->table('order_info') . " AS o" . ' WHERE o.main_count = 0 AND o.add_time BETWEEN ' . $date_start . ' AND ' . $date_end . $where_data . ' GROUP BY hour ORDER BY hour ASC ';
    $result = $GLOBALS['db']->getAll($sql);

    if ($result) {
        foreach ($result as $key => $row) {
            if ($search_data['type'] == 'money') {
                $series_data[$row['hour']] = floatval($row['money']);
            } else {
                $series_data[$row['hour']] = intval($row['volume']);
            }
        }
    }

    for ($i = 0; $i < $hour_num; $i++) {
        //$hour = local_date("y-m-d-H", local_strtotime(" - " . ($hour_num - $i) . " hours"));
        $this_time = local_strtotime(local_date('Y-m-d')) + 3600 * $i - $time_diff;
        $hour = local_date("y-m-d-H", $this_time);
        if (empty($series_data[$hour])) {
            $series_data[$hour] = 0;
        }
        //输出时间
        $hour = local_date("H:i", local_strtotime($hour));
        $xAxis_data[] = $hour;
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

    //数据统计
    $title['text'] = '';
    $xAxis['data'] = $xAxis_data;
    $yAxis['formatter'] = '{value}' . lang('order.individual');
    ksort($series_data);
    $series[0]['name'] = ($search_data['type'] == 'money') ? lang('order.sale_money') : lang('order.goods_salevolume');
    $series[0]['data'] = array_values($series_data);

    //整理数据
    $data['title'] = $title;
    $data['series'] = $series;
    $data['tooltip'] = $tooltip;
    $data['legend'] = $legend;
    $data['toolbox'] = $toolbox;
    $data['calculable'] = $calculable;
    $data['xAxis'] = $xAxis;
    $data['yAxis'] = $yAxis;
    $data['xy_file'] = get_dir_file_list();

    return $data;
}

//今日销售分析
function get_statistical_today_sale($search_data = array())
{
    //筛选条件
    $where_data = "";
    $date_start = $search_data['start_date'];
    $date_end = $search_data['end_date'];

    //获取系统数据 start
    $sql = " SELECT " .
        statistical_field_average_total_fee() . " AS average_total_fee, " .
        statistical_field_average_goods_price() . " AS average_goods_price, " .
        statistical_field_order_goods_number() . " AS goods_number, " .
        statistical_field_user_num() . " AS user_num, " .
        statistical_field_order_num() . " AS order_num, " .
        statistical_field_total_fee() . " AS total_fee " .
        " FROM " . $GLOBALS['dsc']->table('order_info') . " AS o" .
        " LEFT JOIN " . $GLOBALS['dsc']->table('order_goods') . " AS og ON og.order_id = o.order_id " .
        ' WHERE o.main_count = 0 AND o.add_time BETWEEN ' . $date_start . ' AND ' . $date_end . $where_data;
    $result = $GLOBALS['db']->getRow($sql);

    foreach ($result as $key => $val) {
        $result[$key] = empty($val) ? 0 : $val;
    }

    return $result;
}

/**
 *  会员账户概览
 *
 * @access  public
 * @param
 *
 * @return void
 */
function member_account_stats($page = 0)
{
    $result = get_filter();
    if ($result === false) {
        /* 过滤信息 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : (strpos($_REQUEST['start_date'], '-') > 0 ? local_strtotime($_REQUEST['start_date']) : $_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (strpos($_REQUEST['end_date'], '-') > 0 ? local_strtotime($_REQUEST['end_date']) : $_REQUEST['end_date']);
        $filter['source_start_date'] = trim($_REQUEST['start_date']);
        $filter['source_end_date'] = trim($_REQUEST['end_date']);

        /* 默认信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'u.user_money' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        /* 查询语句 */
        $where_u = ' WHERE 1 ';
        $where_al = '';

        if ($filter['start_date']) {
            $where_al .= " AND al.change_time >= '$filter[start_date]'";
        }
        if ($filter['end_date']) {
            $where_al .= " AND al.change_time <= '$filter[end_date]'";
        }
        if ($filter['keywords']) {
            $where_u .= " AND ((u.user_name LIKE '%" . $filter['keywords'] . "%')" .
                " OR (u.email LIKE '%" . $filter['keywords'] . "%') " .
                " OR (u.mobile_phone LIKE '%" . $filter['keywords'] . "%') " .
                " OR (u.nick_name LIKE '%" . $filter['keywords'] . "%')) ";
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
        if ($page > 0) {
            $filter['page'] = $page;
        }

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        /* 分组 */
        $groupBy = " GROUP BY u.user_id ";

        /* 关联查询 */
        $leftJoin = '';
        $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('user_rank') . " AS ur ON ur.rank_id = u.user_rank ";
        $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('account_log') . " AS al ON al.user_id = u.user_id ";

        /* 记录总数 */
        $sql = "SELECT u.user_id FROM " . $GLOBALS['dsc']->table('users') . " AS u " .
            $leftJoin .
            $where_u . $where_al . $groupBy;

        $record_count = count($GLOBALS['db']->getAll($sql));

        $filter['record_count'] = $record_count;
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT u.user_id, u.user_name, u.nick_name, u.user_money, u.frozen_money, ur.rank_name " .
            " FROM " . $GLOBALS['dsc']->table('users') . " AS u " .
            $leftJoin .
            $where_u . $where_al . $groupBy .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    /* 格式化数据 */
    foreach ($row as $key => $value) {
    }

    $arr = array('orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 *  余额统计
 *
 * @access  public
 * @param
 *
 * @return void
 */
function balance_stats($page = 0)
{
    $result = get_filter();
    if ($result === false) {
        /* 过滤信息 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : (strpos($_REQUEST['start_date'], '-') > 0 ? local_strtotime($_REQUEST['start_date']) : $_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (strpos($_REQUEST['end_date'], '-') > 0 ? local_strtotime($_REQUEST['end_date']) : $_REQUEST['end_date']);

        /* 默认信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'u.user_money' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        /* 查询语句 */
        $where_u = ' WHERE 1 ';
        $where_al = '';

        if ($filter['start_date']) {
            $where_al .= " AND al.change_time >= '$filter[start_date]'";
        }
        if ($filter['end_date']) {
            $where_al .= " AND al.change_time <= '$filter[end_date]'";
        }
        if ($filter['keywords']) {
            $where_u .= " AND (u.user_name LIKE '%" . $filter['keywords'] . "%')";
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
        if ($page > 0) {
            $filter['page'] = $page;
        }

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        /* 分组 */
        $groupBy = " GROUP BY u.user_id ";

        /* 关联查询 */
        $leftJoin = '';
        $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('account_log') . " AS al ON al.user_id = u.user_id ";

        /* 记录总数 */
        $sql = "SELECT u.user_id FROM " . $GLOBALS['dsc']->table('users') . " AS u " .
            $leftJoin .
            $where_u . $where_al . $groupBy;

        $record_count = count($GLOBALS['db']->getAll($sql));

        $filter['record_count'] = $record_count;
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT u.user_id, u.user_name, u.user_money, " .
            statistical_field_user_recharge_money() . " AS recharge_money, " .
            statistical_field_user_consumption_money() . " AS consumption_money, " .
            statistical_field_user_cash_money() . " AS cash_money, " .
            statistical_field_user_return_money() . " AS return_money " .
            " FROM " . $GLOBALS['dsc']->table('users') . " AS u " .
            $leftJoin .
            $where_u . $where_al . $groupBy .
            " ORDER BY $filter[sort_by] $filter[sort_order] " .
            " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    /* 格式化数据 */
    foreach ($row as $key => $value) {
        $row[$key]['formated_user_money'] = price_format($value['user_money']);
        $row[$key]['formated_recharge_money'] = price_format($value['recharge_money']);
        $row[$key]['formated_consumption_money'] = price_format(-$value['consumption_money']); //取正
        $row[$key]['formated_cash_money'] = price_format(-$value['cash_money']); //取正
        $row[$key]['formated_return_money'] = price_format($value['return_money']);
    }

    $arr = array('orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 *  余额综合统计
 *
 * @access  public
 * @param
 *
 * @return void
 */
function balance_total_stats()
{
    /* 过滤信息 */
    $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
    $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : (strpos($_REQUEST['start_date'], '-') > 0 ? local_strtotime($_REQUEST['start_date']) : $_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (strpos($_REQUEST['end_date'], '-') > 0 ? local_strtotime($_REQUEST['end_date']) : $_REQUEST['end_date']);

    if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
        $filter['keywords'] = json_str_iconv($filter['keywords']);
    }

    /* 查询语句 */
    $where_u = ' WHERE 1 ';
    $where_al = '';

    if ($filter['start_date']) {
        $where_al .= " AND al.change_time >= '$filter[start_date]'";
    }
    if ($filter['end_date']) {
        $where_al .= " AND al.change_time <= '$filter[end_date]'";
    }
    if ($filter['keywords']) {
        $where_u .= " AND (u.user_name LIKE '%" . $filter['keywords'] . "%')";
    }

    /* 关联查询 */
    $leftJoin = '';
    $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('account_log') . " AS al ON al.user_id = u.user_id ";

    /* 查询 */
    $sql = "SELECT " .
        statistical_field_user_num('al.') . " AS user_num, " .
        //statistical_field_user_money() . " AS user_money, " .
        statistical_field_user_recharge_money() . " AS recharge_money, " .
        statistical_field_user_consumption_money() . " AS consumption_money, " .
        statistical_field_user_cash_money() . " AS cash_money, " .
        statistical_field_user_return_money() . " AS return_money " .
        " FROM " . $GLOBALS['dsc']->table('users') . " AS u " .
        $leftJoin .
        $where_u . $where_al;

    $row = $GLOBALS['db']->getRow($sql);

    /* 剩余总金额 */
    $row['user_money'] = get_table_date('users', '1', array('SUM(user_money)'), 2);

    /* 格式化数据 */
    foreach ($row as $key => $val) {
        if ($val < 0) {
            $val = -$val;
        }
        $row[$key] = (!isset($val) || empty($val)) ? '0' : $val;
    }

    return $row;
}

/**
 *  获取商家佣金列表
 *
 * @access  public
 * @param is_pagination  是否分页
 *
 * @return void
 */
function merchants_commission_list()
{
    $adminru = get_admin_ru_id();

    $result = get_filter();
    if ($result === false) {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        $filter['user_name'] = !isset($_REQUEST['user_name']) && empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);

        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $filter['user_name'] = json_str_iconv($filter['user_name']);
        }

        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'mis.user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ? local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ? local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);
        $filter['cycle'] = !isset($_REQUEST['cycle']) ? '-1' : intval($_REQUEST['cycle']);

        $where = 'WHERE 1 ';
        $left_join = '';

        if ($filter['user_name']) {
            $sql = "SELECT GROUP_CONCAT(user_id) AS user_id FROM " . $GLOBALS['dsc']->table('users') . " WHERE user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%'";
            $user_list = $GLOBALS['db']->getOne($sql);

            $where .= " AND mis.user_id " . db_create_in($user_list);
        }

        if ($filter['cycle'] != -1) {
            $where .= " AND ms.cycle = '$filter[cycle]' ";
            $left_join .= " LEFT JOIN " . $GLOBALS['dsc']->table('merchants_server') . " AS ms ON ms.user_id = mis.user_id ";
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        $store_search_where = '';
        if ($filter['store_search'] != 0) {
            if ($adminru['ru_id'] == 0) {
                if ($_REQUEST['store_type']) {
                    $store_search_where = "AND msi.shopNameSuffix = '" . $_REQUEST['store_type'] . "'";
                }

                if ($filter['store_search'] == 1) {
                    $where .= " AND mis.user_id = '" . $filter['merchant_id'] . "' ";
                } elseif ($filter['store_search'] == 2) {
                    $where .= " AND mis.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                } elseif ($filter['store_search'] == 3) {
                    $where .= " AND mis.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                }
            }
        }

        $where .= " AND mis.merchants_audit = 1 ";
        //管理员查询的权限 -- 店铺查询 end

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('merchants_shop_information') . " as mis " . $left_join . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */  //ecmoban模板堂 -zhuo suppliers_sn
        $sql = "SELECT mis.*, msf.companyName, msf.company_adress, msf.company_contactTel FROM " . $GLOBALS['dsc']->table("merchants_shop_information") . " as mis " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('merchants_steps_fields') . " as msf on mis.user_id = msf.user_id " . $left_join . $where .
            " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'];

        $sql .= " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
        set_filter($filter, $sql);
        $this_sql = $sql; //记录本次
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    //计算平台佣金
    $admin_commission = array();
    for ($i = 0; $i < count($row); $i++) {
        $sql = "SELECT server_id, suppliers_desc, suppliers_percent FROM " . $GLOBALS['dsc']->table('merchants_server') . " WHERE user_id = '" . $row[$i]['user_id'] . "' LIMIT 1";
        $server_info = $GLOBALS['db']->getRow($sql);

        if ($server_info) {
            $row[$i]['server_id'] = $server_info['server_id'];
            $row[$i]['suppliers_desc'] = $server_info['suppliers_desc'];
            $row[$i]['suppliers_percent'] = $server_info['suppliers_percent'];
        } else {
            $row[$i]['server_id'] = 0;
            $row[$i]['suppliers_desc'] = '';
            $row[$i]['suppliers_percent'] = '';
        }

        $row[$i]['user_name'] = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['dsc']->table('users') . " WHERE user_id = '" . $row[$i]['user_id'] . "'");

        $row[$i]['server_id'] = $row[$i]['server_id'];
        $valid = app(CommissionService::class)->merchantsOrderValidRefund($row[$i]['user_id']); //订单有效总额

        $row[$i]['valid_total'] = $valid['total_fee'];
        $row[$i]['order_valid_total'] = price_format($valid['total_fee']);
        $row[$i]['is_goods_rate'] = $valid['is_goods_rate'];

        $row[$i]['order_total_fee'] = $valid['order_total_fee'];
        $row[$i]['goods_total_fee'] = $valid['goods_total_fee'];

        /* 微分销 */
        if (file_exists(MOBILE_DRP)) {
            $row[$i]['order_drp_commission'] = price_format($valid['drp_money']); //dis liu
        }

        $row[$i]['refund_total'] = $valid['return_amount'];
        $row[$i]['order_refund_total'] = price_format($valid['return_amount']);
        $row[$i]['store_name'] = app(MerchantCommonService::class)->getShopName($row[$i]['user_id'], 1);

        /* 统计代码 start */
        $settlemen = app(CommissionService::class)->sellerOrderSettlementLog($row[$i]['user_id']);

        $is_settlement = $settlemen['is_settlement']; //已结算佣金金额  liu
        $no_settlement = $settlemen['no_settlement']; //未结算佣金金额  liu

        if (file_exists(MOBILE_DRP)) {
            $is_settlement = $is_settlement['all_price'];
            $no_settlement = $no_settlement['all_price'];
        }
        $row[$i]['platform_commission'] = $valid['total_fee'] - $is_settlement;
        $row[$i]['is_settlement'] = $is_settlement;
        $row[$i]['no_settlement'] = $no_settlement;
        $row[$i]['formated_platform_commission'] = price_format($row[$i]['platform_commission']);
        $row[$i]['formated_is_settlement'] = price_format($is_settlement);
        $row[$i]['formated_no_settlement'] = price_format($no_settlement);
        /* 统计代码 end */

        $admin_commission['is_settlement'] += $is_settlement; //已结算佣金金额  liu
        $admin_commission['no_settlement'] += $no_settlement; //未结算佣金金额  liu

        $row[$i]['total_fee_price'] = number_format($valid['total_fee'], 2, '.', '');
        $row[$i]['order_refund_total'] = number_format($valid['return_amount'], 2, '.', '');
        $row[$i]['is_settlement_price'] = $is_settlement; //已结算佣金金额  liu
        $row[$i]['no_settlement_price'] = $no_settlement; //未结算佣金金额  liu

        $sql = "SELECT ss.shop_name, ss.shop_address, ss.mobile, " .
            "concat(IFNULL(p.region_name, ''), " .
            "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
            " FROM " . $GLOBALS['dsc']->table('seller_shopinfo') . " AS ss " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS p ON ss.province = p.region_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS t ON ss.city = t.region_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS d ON ss.district = d.region_id " .
            " WHERE ss.ru_id = '" . $row[$i]['user_id'] . "' LIMIT 1";
        $seller_shopinfo = $GLOBALS['db']->getRow($sql);

        if ($seller_shopinfo['shop_name']) {
            $row[$i]['companyName'] = $seller_shopinfo['shop_name'];
            $row[$i]['company_adress'] = "[" . $seller_shopinfo['region'] . "] " . $seller_shopinfo['shop_address'];
        }

        if ($seller_shopinfo['mobile']) {
            $row[$i]['company_contactTel'] = $seller_shopinfo['mobile'];
        } else {
            $row[$i]['company_contactTel'] = $row[$i]['contactPhone'];
        }
    }

    $admin_commission['is_settlement'] = price_format($admin_commission['is_settlement']);
    $admin_commission['no_settlement'] = price_format($admin_commission['no_settlement']);
    set_filter($filter, $this_sql); //记录本次
    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count'], 'admin_commission' => $admin_commission);
    return $arr;
}

/**
 *  结算综合统计
 *
 * @access  public
 * @param
 *
 * @return void
 */
function settlement_total_stats()
{
    $where = " WHERE 1 AND mis.merchants_audit = 1 ";
    $left_join = '';
    $sql = " SELECT mis.user_id FROM " . $GLOBALS['dsc']->table('merchants_shop_information') . " AS mis " . $left_join . $where;
    $seller = $GLOBALS['db']->getCol($sql);
    /* 计算销售总额 */
    $store_num = count($seller);
    if ($store_num > 0) {
        $sql = " SELECT SUM(" . order_commission_field('o.') . ") AS total_amount " .
            " FROM " . $GLOBALS['dsc']->table('order_info') . " AS o WHERE o.ru_id " . db_create_in($seller);
        $total_amount = $GLOBALS['db']->getOne($sql);
    }
    $total_amount = (!isset($total_amount) || empty($total_amount)) ? '0.00' : $total_amount;

    $admin_commission = array();
    $admin_commission['is_settlement'] = 0;
    $admin_commission['no_settlement'] = 0;
    $admin_commission['valid_fee'] = 0;
    $admin_commission['refund_fee'] = 0;
    if ($seller) {
        for ($i = 0; $i < count($seller); $i++) {

            $settlemen = app(CommissionService::class)->sellerOrderSettlementLog($seller[$i]);

            $admin_commission['is_settlement'] += $settlemen['is_settlement']; //已结算佣金金额  liu
            $admin_commission['no_settlement'] += $settlemen['no_settlement']; //未结算佣金金额  liu

            /* 统计代码 start */
            $valid = app(CommissionService::class)->merchantsOrderValidRefund($seller[$i]); //订单有效总额
            $admin_commission['valid_fee'] += $valid['total_fee'];
            $admin_commission['refund_fee'] += $valid['return_amount'];
            /* 统计代码 end */
        }
    }

    $admin_all = array();
    $admin_all['store_num'] = $store_num;
    $admin_all['total_amount'] = $total_amount;
    $admin_all['is_settlement'] = empty($admin_commission['is_settlement']) ? '0.00' : sprintf("%.2f", $admin_commission['is_settlement']);
    $admin_all['no_settlement'] = empty($admin_commission['no_settlement']) ? '0.00' : sprintf("%.2f", $admin_commission['is_settlement']);
    $admin_all['valid_fee'] = empty($admin_commission['valid_fee']) ? '0.00' : sprintf("%.2f", $admin_commission['valid_fee']);
    $admin_all['refund_fee'] = empty($admin_commission['refund_fee']) ? '0.00' : sprintf("%.2f", $admin_commission['refund_fee']);
    $admin_all['actual_fee'] = sprintf("%.2f", $admin_all['valid_fee'] - $admin_all['refund_fee']);
    $admin_all['platform_commission'] = sprintf("%.2f", $admin_all['valid_fee'] - $admin_all['is_settlement']);

    return $admin_all;
}

//新增店铺
function get_statistical_shop_area($search_data = array())
{
    $data = array();

    //筛选条件
    $where_data = "";
    if (isset($search_data['shop_categoryMain']) && !empty($search_data['shop_categoryMain'])) {
        $where_data .= " AND msi.shop_categoryMain = '$search_data[shop_categoryMain]' ";
    }
    if (isset($search_data['shopNameSuffix']) && !empty($search_data['shopNameSuffix'])) {
        $where_data .= " AND msi.shopNameSuffix = '$search_data[shopNameSuffix]' ";
    }
    if (isset($search_data['start_date']) && !empty($search_data['start_date'])) {
        $where_data .= " AND msi.add_time >= '$search_data[start_date]'";
    }
    if (isset($search_data['end_date']) && !empty($search_data['end_date'])) {
        $where_data .= " AND msi.add_time <= '$search_data[end_date]'";
    }
    if (isset($search_data['area']) && !empty($search_data['area'])) {
        $sql = " SELECT region_id FROM " . $GLOBALS['dsc']->table('merchants_region_info') . " WHERE ra_id = '$search_data[area]' ";
        $region_ids = $GLOBALS['db']->getCol($sql);
        $where_data .= " AND spi.province " . db_create_in($region_ids);
    }

    //获取系统数据 start
    /* 分组 */
    $groupBy = " GROUP BY spi.province ";
    /* 关联查询 */
    $leftJoin = '';
    $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('merchants_shop_information') . " AS msi ON msi.user_id = spi.ru_id ";
    $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('region') . " AS r ON r.region_id = spi.province ";
    /* 数据查询 */
    $sql = "SELECT spi.province, r.region_name as province_name, " .
        statistical_field_shop_num() . " AS store_num " . //店铺数量
        " FROM " . $GLOBALS['dsc']->table('seller_shopinfo') . " AS spi " .
        $leftJoin .
        $where_data . $groupBy;
    $result = $GLOBALS['db']->getAll($sql);

    $series_data = array();
    $value_arr = array();
    foreach ($result as $key => $val) {
        $series_data[] = array('name' => $val['province_name'], 'value' => $val['store_num']);
        $value_arr[] = $val['store_num'];
    }
    $max = max($value_arr);
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
        'y' => 'center',
        'feature' => array(
            'mark' => array(
                'show' => true
            ),
            'dataView' => array(
                'show' => true,
                'readOnly' => false
            )
        )
    );
    $tooltip = array('trigger' => 'item');
    $dataRange = array(
        'orient' => 'horizontal',
        'min' => 0,
        'max' => $max,
        'text' => array('高', '低'),
        'splitNumber' => 0
    );
    $series = array(
        array(
            'name' => '',
            'type' => 'map',
            'mapType' => 'china',
            'mapLocation' => array(
                'x' => 'center'
            ),
            'selectedMode' => 'multiple',
            'itemStyle' => array(
                'normal' => array(
                    'label' => array(
                        'show' => true
                    )
                ),
                'emphasis' => array(
                    'label' => array(
                        'show' => true
                    )
                )
            ),
            'data' => array(),
        )
    );
    $animation = false;
    //图表公共数据 end

    //数据统计
    $title['text'] = lang('order.seller_area_distribution');
    $yAxis['formatter'] = '{value}' . lang('order.individual');
    ksort($series_data);
    $series[0]['name'] = lang('order.seller_area_distribution');
    $series[0]['data'] = array_values($series_data);

    //整理数据
    $data['title'] = $title;
    $data['series'] = $series;
    $data['tooltip'] = $tooltip;
    $data['toolbox'] = $toolbox;
    $data['animation'] = $animation;
    $data['dataRange'] = $dataRange;
    $data['xy_file'] = get_dir_file_list();

    return $data;
}

//列表数据导出
function list_download($config = array())
{
    //文件名
    if ($config['filename']) {
        $filename = $config['filename'];
    } else {
        $filename = time();
    }

    header("Content-type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename.xls");

    /* 商品名称,订单号,商品数量,销售价格,销售日期 */
    echo dsc_iconv(EC_CHARSET, 'GB2312', $config['thead'][0]) . "\t";
    echo dsc_iconv(EC_CHARSET, 'GB2312', $config['thead'][1]) . "\t";
    echo dsc_iconv(EC_CHARSET, 'GB2312', $config['thead'][2]) . "\t";
    echo dsc_iconv(EC_CHARSET, 'GB2312', $config['thead'][3]) . "\t";
    echo dsc_iconv(EC_CHARSET, 'GB2312', $config['thead'][4]) . "\t";
    echo dsc_iconv(EC_CHARSET, 'GB2312', $config['thead'][5]) . "\t";
    echo dsc_iconv(EC_CHARSET, 'GB2312', $config['thead'][6]) . "\t";
    echo dsc_iconv(EC_CHARSET, 'GB2312', $config['thead'][7]) . "\t\n";

    foreach ($config['tdata'] as $data) {
        echo dsc_iconv(EC_CHARSET, 'GB2312', $data['user_id'] ?? 0) . "\t";
        echo dsc_iconv(EC_CHARSET, 'GB2312', $data['user_name'] ?? '') . "\t";
        echo dsc_iconv(EC_CHARSET, 'GB2312', $data['total_num'] ?? 0) . "\t";
        echo dsc_iconv(EC_CHARSET, 'GB2312', $data['total_fee'] ?? 0) . "\t";
        echo dsc_iconv(EC_CHARSET, 'GB2312', $data['valid_num'] ?? 0) . "\t";
        echo dsc_iconv(EC_CHARSET, 'GB2312', $data['valid_fee'] ?? 0) . "\t";
        echo dsc_iconv(EC_CHARSET, 'GB2312', $data['return_num'] ?? 0) . "\t";
        echo dsc_iconv(EC_CHARSET, 'GB2312', $data['return_fee'] ?? 0) . "\t";
        echo "\n";
    }

    exit;
}
