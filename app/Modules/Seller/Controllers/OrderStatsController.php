<?php

namespace App\Modules\Seller\Controllers;

use App\Exports\OrderExport;
use App\Repositories\Common\DscRepository;
use App\Services\Order\OrderService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 订单统计
 */
class OrderStatsController extends InitController
{
    protected $orderService;
    protected $dscRepository;

    public function __construct(
        OrderService $orderService,
        DscRepository $dscRepository
    )
    {
        $this->orderService = $orderService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang(['statistic', 'common'], 'seller');

        $menus = session()->has('menus') ? session('menus') : '';
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $adminru = get_admin_ru_id();
        $this->smarty->assign('ru_id', $adminru['ru_id']);

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 时间参数 */
        if (isset($_POST['start_date']) && !empty($_POST['end_date'])) {
            $start_date = local_strtotime($_POST['start_date']);
            $end_date = local_strtotime($_POST['end_date']);
            if ($start_date == $end_date) {
                $end_date = $start_date + 86400;
            }
        } else {
            $today = strtotime(local_date('Y-m-d'));   //本地时间
            $start_date = $today - 86400 * 6;
            $end_date = $today + 86400;               //至明天零时
        }
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['06_stats']);

        $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'report_order']);

        /*------------------------------------------------------ */
        //--订单统计
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('sale_order_stats');

            $this->smarty->assign('current', 'order_stats_list');

            /* 取得订单转化率数据 ecmoban模板堂 --zhuo */
            $order_general = $this->get_order_general(1);
            $order_general['total_turnover'] = floatval($order_general['total_turnover']);

            $order_general2 = $this->get_order_general();
            $order_total = floatval($order_general2['total_turnover']);
            $this->smarty->assign('order_total', $order_total);

            /* 取得商品总点击数量 */
            $where = " AND user_id = '{$adminru['ru_id']}' ";
            $sql = 'SELECT SUM(click_count) FROM ' . $this->dsc->table('goods') . ' WHERE is_delete = 0' . $where;
            $click_count = floatval($this->db->getOne($sql));

            /* 每千个点击的订单数 */
            $click_ordernum = $click_count > 0 ? round(($order_general['total_order_num'] * 1000) / $click_count, 2) : 0;

            /* 每千个点击的购物额 */
            $click_turnover = $click_count > 0 ? round(($order_general['total_turnover'] * 1000) / $click_count, 2) : 0;

            /* 时间参数 */
            $is_multi = empty($_POST['is_multi']) ? false : true;

            $start_date_arr = [];
            $end_date_arr = [];
            if (!empty($_POST['year_month'])) {
                $tmp = $_POST['year_month'];

                for ($i = 0; $i < count($tmp); $i++) {
                    if (!empty($tmp[$i])) {

                        //过滤多余天数
                        $tem_arr = explode('-', $tmp[$i]);
                        if (count($tem_arr) > 2) {
                            $tem_arr = array_slice($tem_arr, 0, 2);
                            $tmp[$i] = implode('-', $tem_arr);
                        }

                        $tmp_time = local_strtotime($tmp[$i] . '-1');
                        $start_date_arr[] = $tmp_time;
                        $end_date_arr[] = local_strtotime($tmp[$i] . '-' . date('t', $tmp_time));
                    }
                }
            } else {
                $tmp_time = local_strtotime(local_date('Y-m-d'));
                $start_date_arr[] = local_strtotime(local_date('Y-m') . '-1');
                $end_date_arr[] = local_strtotime(local_date('Y-m') . '-31');
            }

            $order_data = [];
            $ship_data = [];
            $pay_data = [];
            $area_data = [];
            $sale_data = [];

            /* 按月份交叉查询 */
            if ($is_multi) {
                /* 订单概况 */
                foreach ($start_date_arr as $k => $val) {
                    $seriesName = local_date('Y-m', $val);
                    $order_info = $this->get_orderinfo($start_date_arr[$k], $end_date_arr[$k]);
                    $order_data[0][] = $seriesName; //月份
                    $order_data[1][] = $order_info['confirmed_num']; //已确认
                    $order_data[2][] = $order_info['succeed_num']; //已成交
                    $order_data[3][] = $order_info['unconfirmed_num']; //未确认
                    $order_data[4][] = $order_info['invalid_num']; //无效或取消
                }

                /* 销售概况 */
                foreach ($start_date_arr as $k => $val) {
                    $seriesName = local_date('Y-m', $val);
                    $order_info = $this->get_orderinfo($start_date_arr[$k], $end_date_arr[$k], 1);
                    $sale_data[0][] = $seriesName; //月份
                    $sale_data[1][] = $order_info['confirmed_num']; //已确认
                    $sale_data[2][] = $order_info['succeed_num']; //已成交
                    $sale_data[3][] = $order_info['unconfirmed_num']; //未确认
                    $sale_data[4][] = $order_info['invalid_num']; //无效或取消
                }

                /* 配送方式 */
                $ship_res1 = $ship_res2 = $this->get_shipping_type($start_date, $end_date, $adminru['ru_id']);
                if ($ship_res1) {
                    $ship_arr = $this->get_to_array($ship_res1, $ship_res2, 'shipping_id', 'ship_arr', 'ship_name');
                    foreach ($ship_arr as $row) {
                        $ship_data[0][] = $row['ship_name'];
                        $ship_data[1][] = [
                            'value' => count($row['ship_arr']),
                            'name' => $row['ship_name']
                        ];
                    }
                }

                /* 支付方式 */
                $pay_item1 = $pay_item2 = $this->get_pay_type($start_date, $end_date, $adminru['ru_id']);
                if ($pay_item1) {
                    $pay_arr = $this->get_to_array($pay_item1, $pay_item2, 'pay_id', 'pay_arr', 'pay_name');
                    foreach ($pay_arr as $row) {
                        $pay_data[0][] = $row['pay_name'];
                        $pay_data[1][] = [
                            'value' => count($row['pay_arr']),
                            'name' => $row['pay_name']
                        ];
                    }
                }

                /* 配送地区 */
                $countries = !empty($_POST['country']) ? intval($_POST['country']) : 1;
                $pro = !empty($_POST['province']) ? intval($_POST['province']) : 0;
                $order_area = $this->get_area($countries, $pro);
                if ($order_area) {
                    foreach ($order_area as $row) {
                        $row['region_name'] = empty($row['region_name']) ? $GLOBALS['_LANG']['unknown'] : $row['region_name'];
                        $area_data[0][] = $row['region_name'];
                        $area_data[1][] = [
                            'value' => $row['area_num'],
                            'name' => $row['region_name']
                        ];
                    }
                }
            } else {
                /* 订单概况 */
                $order_data['order'] = get_statistical_data($start_date, $end_date, 'order');
                $order_data['sale'] = get_statistical_data($start_date, $end_date, 'sale');

                /* 配送方式 */
                $ship_res1 = $ship_res2 = $this->get_shipping_type($start_date, $end_date, $adminru['ru_id']);
                if ($ship_res1) {
                    $ship_arr = $this->get_to_array($ship_res1, $ship_res2, 'shipping_id', 'ship_arr', 'ship_name');
                    foreach ($ship_arr as $row) {
                        $ship_data[0][] = $row['ship_name'];
                        $ship_data[1][] = [
                            'value' => count($row['ship_arr']),
                            'name' => $row['ship_name']
                        ];
                    }
                }

                /* 支付方式 */
                $pay_item1 = $pay_item2 = $this->get_pay_type($start_date, $end_date, $adminru['ru_id']);
                if ($pay_item1) {
                    $pay_arr = $this->get_to_array($pay_item1, $pay_item2, 'pay_id', 'pay_arr', 'pay_name');
                    foreach ($pay_arr as $row) {
                        $pay_data[0][] = $row['pay_name'];
                        $pay_data[1][] = [
                            'value' => count($row['pay_arr']),
                            'name' => $row['pay_name']
                        ];
                    }
                }

                /* 配送地区 */
                $countries = !empty($_POST['country']) ? intval($_POST['country']) : 1;
                $pro = !empty($_POST['province']) ? intval($_POST['province']) : 0;
                $order_area = $this->get_area($countries, $pro);
                if ($order_area) {
                    foreach ($order_area as $row) {
                        $row['region_name'] = empty($row['region_name']) ? $GLOBALS['_LANG']['unknown'] : $row['region_name'];
                        $area_data[0][] = $row['region_name'];
                        $area_data[1][] = [
                            'value' => $row['area_num'],
                            'name' => $row['region_name']
                        ];
                    }
                }
            }

            /* 统计数据 */
            $this->smarty->assign('order_data', json_encode($order_data));
            $this->smarty->assign('sale_data', json_encode($sale_data));
            $this->smarty->assign('ship_data', json_encode($ship_data));
            $this->smarty->assign('pay_data', json_encode($pay_data));
            $this->smarty->assign('area_data', json_encode($area_data));

            /* 赋值到模板 */
            $this->smarty->assign('order_general', $order_general);
            $this->smarty->assign('total_turnover', price_format($order_general['total_turnover']));
            $this->smarty->assign('click_count', $click_count);         //商品总点击数
            $this->smarty->assign('click_ordernum', $click_ordernum);      //每千点订单数
            $this->smarty->assign('click_turnover', price_format($click_turnover));  //每千点购物额

            $this->smarty->assign('is_multi', $is_multi);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['report_order']);
            $this->smarty->assign('start_date', local_date($GLOBALS['_CFG']['time_format'], $start_date));
            $this->smarty->assign('end_date', local_date($GLOBALS['_CFG']['time_format'], $end_date));

            for ($i = 0; $i < 5; $i++) {
                if (isset($start_date_arr[$i])) {
                    $start_date_arr[$i] = local_date('Y-m', $start_date_arr[$i]);
                } else {
                    $start_date_arr[$i] = null;
                }
            }
            $this->smarty->assign('start_date_arr', $start_date_arr);

            if (!$is_multi) {
                $filename = local_date('Ymd', $start_date) . '_' . local_date('Ymd', $end_date);
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['down_order_statistics'], 'href' => 'order_stats.php?act=download&start_date=' . $start_date . '&end_date=' . $end_date . '&filename=' . $filename]);
            }


            return $this->smarty->display('order_stats.dwt');
        }

        /*------------------------------------------------------ */
        //--订单统计
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'list_old') {
            admin_priv('sale_order_stats');

            $this->smarty->assign('current', 'order_stats_list');

            /* 随机的颜色数组 */
            $color_array = ['33FF66', 'FF6600', '3399FF', '009966', 'CC3399', 'FFCC33', '6699CC', 'CC3366'];

            /* 取得订单转化率数据 ecmoban模板堂 --zhuo */
            $order_general = $this->get_order_general();
            $order_general['total_turnover'] = floatval($order_general['total_turnover']);

            $order_total = floatval($order_general['total_turnover']);
            $this->smarty->assign('order_total', $order_total);

            /* 取得商品总点击数量 */
            $sql = 'SELECT SUM(click_count) FROM ' . $this->dsc->table('goods') . ' WHERE is_delete = 0 AND user_id = ' . $adminru['ru_id'];
            $click_count = floatval($this->db->getOne($sql));

            /* 每千个点击的订单数 */
            $click_ordernum = $click_count > 0 ? round(($order_general['total_order_num'] * 1000) / $click_count, 2) : 0;

            /* 每千个点击的购物额 */
            $click_turnover = $click_count > 0 ? round(($order_general['total_turnover'] * 1000) / $click_count, 2) : 0;

            /* 时间参数 */
            $is_multi = empty($_POST['is_multi']) ? false : true;

            $start_date_arr = [];
            $end_date_arr = [];
            if (!empty($_POST['year_month'])) {
                $tmp = $_POST['year_month'];

                for ($i = 0; $i < count($tmp); $i++) {
                    if (!empty($tmp[$i])) {
                        $tmp_time = local_strtotime($tmp[$i] . '-1');
                        $start_date_arr[] = $tmp_time;
                        $end_date_arr[] = local_strtotime($tmp[$i] . '-' . date('t', $tmp_time));
                    }
                }
            } else {
                $tmp_time = local_strtotime(local_date('Y-m-d'));
                $start_date_arr[] = local_strtotime(local_date('Y-m') . '-1');
                $end_date_arr[] = local_strtotime(local_date('Y-m') . '-31');
            }

            /* 按月份交叉查询 */
            if ($is_multi) {
                /* 订单概况 */
                $order_general_xml = "<chart caption='" . $GLOBALS['_LANG']['order_circs'] . "' shownames='1' showvalues='0' decimals='0' outCnvBaseFontSize='12' baseFontSize='12' >";
                $order_general_xml .= "<categories><category label='" . $GLOBALS['_LANG']['confirmed'] . "' />" .
                    "<category label='" . $GLOBALS['_LANG']['succeed'] . "' />" .
                    "<category label='" . $GLOBALS['_LANG']['unconfirmed'] . "' />" .
                    "<category label='" . $GLOBALS['_LANG']['invalid'] . "' /></categories>";
                foreach ($start_date_arr as $k => $val) {
                    $seriesName = local_date('Y-m', $val);
                    $order_info = $this->get_orderinfo($start_date_arr[$k], $end_date_arr[$k]);
                    $order_general_xml .= "<dataset seriesName='$seriesName' color='$color_array[$k]' showValues='0'>";
                    $order_general_xml .= "<set value='$order_info[confirmed_num]' />";
                    $order_general_xml .= "<set value='$order_info[succeed_num]' />";
                    $order_general_xml .= "<set value='$order_info[unconfirmed_num]' />";
                    $order_general_xml .= "<set value='$order_info[invalid_num]' />";
                    $order_general_xml .= "</dataset>";
                }
                $order_general_xml .= "</chart>";

                /* 支付方式 */
                $pay_xml = "<chart caption='" . $GLOBALS['_LANG']['pay_method'] . "' shownames='1' showvalues='0' decimals='0' outCnvBaseFontSize='12' baseFontSize='12' >";

                $payment = [];
                $payment_count = [];

                foreach ($start_date_arr as $k => $val) {
                    //ecmoban模板堂 --zhuo start
                    $pay_res1 = $this->get_pay_type_top($start_date_arr[$k], $adminru['ru_id'], 1);
                    $pay_res2 = $this->get_pay_type_top($start_date_arr[$k], $adminru['ru_id']);
                    if ($pay_res1) {
                        $pay_arr = $this->get_to_array($pay_res1, $pay_res2, 'pay_id', 'pay_arr', 'pay_name', 'pay_time');
                        foreach ($pay_arr as $row) {
                            $payment[$row['pay_name']] = null;

                            $paydate = local_date('Y-m', $row['pay_time']);

                            $payment_count[$row['pay_name']][$paydate] = count($row['pay_arr']);
                        }
                    }
                    //ecmoban模板堂 --zhuo end
                }

                $pay_xml .= "<categories>";
                foreach ($payment as $k => $val) {
                    $pay_xml .= "<category label='$k' />";
                }
                $pay_xml .= "</categories>";

                foreach ($start_date_arr as $k => $val) {
                    $date = local_date('Y-m', $start_date_arr[$k]);
                    $pay_xml .= "<dataset seriesName='$date' color='$color_array[$k]' showValues='0'>";
                    foreach ($payment as $k => $val) {
                        $count = 0;
                        if (!empty($payment_count[$k][$date])) {
                            $count = $payment_count[$k][$date];
                        }

                        $pay_xml .= "<set value='$count' name='$date' />";
                    }
                    $pay_xml .= "</dataset>";
                }
                $pay_xml .= "</chart>";

                /* 配送方式 */
                $ship = [];
                $ship_count = [];

                $ship_xml = "<chart caption='" . $GLOBALS['_LANG']['shipping_method'] . "' shownames='1' showvalues='0' decimals='0' outCnvBaseFontSize='12' baseFontSize='12' >";

                foreach ($start_date_arr as $k => $val) {
                    //ecmoban模板堂 --zhuo start
                    $ship_res1 = $this->get_shipping_type($start_date, $end_date, $adminru['ru_id'], 1);
                    $ship_res2 = $this->get_shipping_type($start_date, $end_date, $adminru['ru_id']);
                    if ($ship_res1) {
                        $ship_arr = $this->get_to_array($ship_res1, $ship_res2, 'shipping_id', 'ship_arr', 'ship_name', 'shipping_time');
                        foreach ($ship_arr as $row) {
                            $ship[$row['ship_name']] = null;

                            $shipdate = local_date('Y-m', $row['shipping_time']);

                            $ship_count[$row['ship_name']][$shipdate] = count($row['ship_arr']);
                        }
                    }
                    //ecmoban模板堂 --zhuo end
                }

                $ship_xml .= "<categories>";
                foreach ($ship as $k => $val) {
                    $ship_xml .= "<category label='$k' />";
                }
                $ship_xml .= "</categories>";

                foreach ($start_date_arr as $k => $val) {
                    $date = local_date('Y-m', $start_date_arr[$k]);

                    $ship_xml .= "<dataset seriesName='$date' color='$color_array[$k]' showValues='0'>";
                    foreach ($ship as $k => $val) {
                        $count = 0;
                        if (!empty($ship_count[$k][$date])) {
                            $count = $ship_count[$k][$date];
                        }
                        $ship_xml .= "<set value='$count' name='$date' />";
                    }
                    $ship_xml .= "</dataset>";
                }
                $ship_xml .= "</chart>";
            } /* 按时间段查询 */
            else {
                /* 订单概况 */
                $order_info = $this->get_orderinfo($start_date, $end_date);

                $order_general_xml = "<graph caption='" . $GLOBALS['_LANG']['order_circs'] . "' decimalPrecision='2' showPercentageValues='0' showNames='1' showValues='1' showPercentageInLabel='0' pieYScale='45' pieBorderAlpha='40' pieFillAlpha='70' pieSliceDepth='15' pieRadius='100' outCnvBaseFontSize='13' baseFontSize='12'>";

                $order_general_xml .= "<set value='" . $order_info['confirmed_num'] . "' name='" . $GLOBALS['_LANG']['confirmed'] . "' color='" . $color_array[5] . "' />";

                $order_general_xml .= "<set value='" . $order_info['succeed_num'] . "' name='" . $GLOBALS['_LANG']['succeed'] . "' color='" . $color_array[0] . "' />";

                $order_general_xml .= "<set value='" . $order_info['unconfirmed_num'] . "' name='" . $GLOBALS['_LANG']['unconfirmed'] . "' color='" . $color_array[1] . "'  />";

                $order_general_xml .= "<set value='" . $order_info['invalid_num'] . "' name='" . $GLOBALS['_LANG']['invalid'] . "' color='" . $color_array[4] . "' />";
                $order_general_xml .= "</graph>";

                /* 支付方式 */
                $pay_xml = "<graph caption='" . $GLOBALS['_LANG']['pay_method'] . "' decimalPrecision='2' showPercentageValues='0' showNames='1' numberPrefix='' showValues='1' showPercentageInLabel='0' pieYScale='45' pieBorderAlpha='40' pieFillAlpha='70' pieSliceDepth='15' pieRadius='100' outCnvBaseFontSize='13' baseFontSize='12'>";

                //ecmoban模板堂 --zhuo satrt
                $pay_item1 = $this->get_pay_type($start_date, $end_date, $adminru['ru_id']);
                $pay_item2 = $this->get_pay_type($start_date, $end_date, $adminru['ru_id']);
                if ($pay_item1) {
                    $pay_arr = $this->get_to_array($pay_item1, $pay_item2, 'pay_id', 'pay_arr', 'pay_name');
                    foreach ($pay_arr as $row) {
                        $pay_xml .= "<set value='" . count($row['pay_arr']) . "' name='" . strip_tags($row['pay_name']) . "' color='" . $color_array[mt_rand(0, 7)] . "'/>";
                    }
                }
                //ecmoban模板堂 --zhuo end

                $pay_xml .= "</graph>";

                /* 配送方式 */
                $ship_xml = "<graph caption='" . $GLOBALS['_LANG']['shipping_method'] . "' decimalPrecision='2' showPercentageValues='0' showNames='1' numberPrefix='' showValues='1' showPercentageInLabel='0' pieYScale='45' pieBorderAlpha='40' pieFillAlpha='70' pieSliceDepth='15' pieRadius='100' outCnvBaseFontSize='13' baseFontSize='12'>";

                //ecmoban模板堂 --zhuo satrt
                $ship_res1 = $this->get_shipping_type($start_date, $end_date, $adminru['ru_id'], 1);
                $ship_res2 = $this->get_shipping_type($start_date, $end_date, $adminru['ru_id']);
                if ($ship_res1) {
                    $ship_arr = $this->get_to_array($ship_res1, $ship_res2, 'shipping_id', 'ship_arr', 'ship_name');
                    foreach ($ship_arr as $row) {
                        $ship_xml .= "<set value='" . count($row['ship_arr']) . "' name='" . $row['ship_name'] . "' color='" . $color_array[mt_rand(0, 7)] . "' />";
                    }
                }
                //ecmoban模板堂 --zhuo end

                $ship_xml .= "</graph>";
            }
            /* 赋值到模板 */
            $this->smarty->assign('order_general', $order_general);
            $this->smarty->assign('total_turnover', price_format($order_general['total_turnover']));
            $this->smarty->assign('click_count', $click_count);         //商品总点击数
            $this->smarty->assign('click_ordernum', $click_ordernum);      //每千点订单数
            $this->smarty->assign('click_turnover', price_format($click_turnover));  //每千点购物额

            $this->smarty->assign('is_multi', $is_multi);

            $this->smarty->assign('order_general_xml', $order_general_xml);
            $this->smarty->assign('ship_xml', $ship_xml);
            $this->smarty->assign('pay_xml', $pay_xml);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['report_order']);
            $this->smarty->assign('start_date', local_date($GLOBALS['_CFG']['date_format'], $start_date));
            $this->smarty->assign('end_date', local_date($GLOBALS['_CFG']['date_format'], $end_date));

            for ($i = 0; $i < 5; $i++) {
                if (isset($start_date_arr[$i])) {
                    $start_date_arr[$i] = local_date('Y-m', $start_date_arr[$i]);
                } else {
                    $start_date_arr[$i] = null;
                }
            }
            $this->smarty->assign('start_date_arr', $start_date_arr);

            if (!$is_multi) {
                $filename = local_date('Ymd', $start_date) . '_' . local_date('Ymd', $end_date);
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['down_order_statistics'], 'href' => 'order_stats.php?act=download&start_date=' . $start_date . '&end_date=' . $end_date . '&filename=' . $filename, 'class' => 'icon-download-alt']);
            }


            return $this->smarty->display('order_stats.dwt');
        } /**
         * 订单统计报表下载
         */
        elseif ($_REQUEST['act'] == 'download') {
            $filename = !empty($_REQUEST['filename']) ? trim($_REQUEST['filename']) : '';

            return Excel::download(new OrderExport, $filename . '.xlsx');
        }
    }
    /*------------------------------------------------------ */
    //--订单统计需要的函数
    /*------------------------------------------------------ */
    /**
     * 取得订单概况数据(包括订单的几种状态)
     * @param       $start_date    开始查询的日期
     * @param       $end_date      查询的结束日期
     * @param       $type          查询类型：0为订单数量，1为销售额
     * @return      $order_info    订单概况数据
     */
    private function get_orderinfo($start_date, $end_date, $type = 0)
    {
        $order_info = [];
        $adminru = get_admin_ru_id();

        //卖场 start
        if ($adminru['rs_id'] > 0) {
            $filed = " (SELECT og.ru_id FROM " . $GLOBALS['dsc']->table('order_goods') . ' as og ' . " WHERE og.order_id = o.order_id LIMIT 1) ";
            $where = get_rs_null_where($filed, $adminru['rs_id']);
        } else {
            $where = " AND o.ru_id = '" . $adminru['ru_id'] . "' ";
        }
        //卖场 end

        if ($type == 1) {
            /* 未确认订单数 */
            $sql = 'SELECT IFNULL(SUM(' . $this->orderService->orderAmountField('o.') . '), 0) FROM ' . $GLOBALS['dsc']->table('order_info') . " as o " .
                " WHERE o.order_status = '" . OS_UNCONFIRMED . "' AND o.add_time >= '$start_date'" .
                " AND o.add_time < '" . ($end_date + 86400) . "'" .
                $where .
                " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示

            $order_info['unconfirmed_num'] = $GLOBALS['db']->getOne($sql);

            /* 已确认订单数 */
            $sql = 'SELECT IFNULL(SUM(' . $this->orderService->orderAmountField('o.') . '), 0) FROM ' . $GLOBALS['dsc']->table('order_info') . " as o " .
                " WHERE o.order_status = '" . OS_CONFIRMED . "' AND o.shipping_status NOT " . db_create_in([SS_SHIPPED, SS_RECEIVED]) . " AND o.pay_status NOT" . db_create_in([PS_PAYED, PS_PAYING]) . " AND o.add_time >= '$start_date'" .
                " AND o.add_time < '" . ($end_date + 86400) . "'" .
                $where .
                " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示
            $order_info['confirmed_num'] = $GLOBALS['db']->getOne($sql);

            /* 已成交订单数 */
            $sql = 'SELECT IFNULL(SUM(' . $this->orderService->orderAmountField('o.') . '), 0) FROM ' . $GLOBALS['dsc']->table('order_info') . ' as o ' .
                " WHERE 1 " . $this->orderService->orderQuerySql('finished', 'o.') .
                " AND o.add_time >= '$start_date' AND o.add_time < '" . ($end_date + 86400) . "'" .
                $where .
                " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示

            $order_info['succeed_num'] = $GLOBALS['db']->getOne($sql);

            /* 无效或已取消订单数 */
            $sql = 'SELECT IFNULL(SUM(' . $this->orderService->orderAmountField('o.') . '), 0) FROM ' . $GLOBALS['dsc']->table('order_info') . ' as o ' .
                " WHERE o.order_status " . db_create_in([OS_CANCELED, OS_INVALID]) .
                " AND o.add_time >= '$start_date' AND o.add_time < '" . ($end_date + 86400) . "'" .
                $where .
                " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示

            $order_info['invalid_num'] = $GLOBALS['db']->getOne($sql);
        } else {
            /* 未确认订单数 */
            $sql = 'SELECT count(*) FROM ' . $this->dsc->table('order_info') . " as o " .
                " WHERE o.order_status = '" . OS_UNCONFIRMED . "' AND o.add_time >= '$start_date'" .
                " AND o.add_time < '" . ($end_date + 86400) . "'" .
                " AND o.ru_id = '" . $adminru['ru_id'] . "' " .
                " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示

            $order_info['unconfirmed_num'] = $this->db->getOne($sql);

            /* 已确认订单数 */
            $sql = 'SELECT count(*) FROM ' . $this->dsc->table('order_info') . " as o " .
                " WHERE o.order_status = '" . OS_CONFIRMED . "' AND o.shipping_status NOT " . db_create_in([SS_SHIPPED, SS_RECEIVED]) . " AND o.pay_status NOT" . db_create_in([PS_PAYED, PS_PAYING]) . " AND o.add_time >= '$start_date'" .
                " AND o.add_time < '" . ($end_date + 86400) . "'" .
                " AND o.ru_id = '" . $adminru['ru_id'] . "' " .
                " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示
            $order_info['confirmed_num'] = $this->db->getOne($sql);

            /* 已成交订单数 */
            $sql = 'SELECT count(*) FROM ' . $this->dsc->table('order_info') . ' as o ' .
                " WHERE 1 " . $this->orderService->orderQuerySql('real_pay', 'o.') .
                " AND o.add_time >= '$start_date' AND o.add_time < '" . ($end_date + 86400) . "'" .
                " AND o.ru_id = '" . $adminru['ru_id'] . "' " .
                " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示

            $order_info['succeed_num'] = $this->db->getOne($sql);

            /* 无效或已取消订单数 */
            $sql = "SELECT count(*) FROM " . $this->dsc->table('order_info') . ' as o ' .
                " WHERE o.order_status " . db_create_in([OS_CANCELED, OS_INVALID]) .
                " AND o.add_time >= '$start_date' AND o.add_time < '" . ($end_date + 86400) . "'" .
                " AND o.ru_id = '" . $adminru['ru_id'] . "' " .
                " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示

            $order_info['invalid_num'] = $this->db->getOne($sql);
        }

        return $order_info;
    }

    private function get_order_general($type = 0)
    {
        $adminru = get_admin_ru_id();

        /* 计算订单各种费用之和的语句 */
        $total_fee = "SUM(" . order_commission_field('o.') . ") AS total_turnover ";

        $where = " AND o.ru_id = '" . $adminru['ru_id'] . "' ";

        $sql = "SELECT count(*) as total_order_num, " . $total_fee . " FROM " . $this->dsc->table('order_info') . ' as o' .
            " WHERE 1 " . $this->orderService->orderQuerySql('real_pay', 'o.') .
            $where . " AND o.main_count = 0";  //主订单下有子订单时，则主订单不显示;

        return $this->db->getRow($sql);
    }

    //multi--One start
    private function get_pay_type_top($start_date, $ru_id, $type = 0)
    {

        //当月的开始日期和结束日期
        $day = local_date('Y-m-d', $start_date);
        $time = $this->getthemonth($day);

        $start_date = local_strtotime($time[0]);
        $end_date = local_strtotime($time[1]);

        $res = $this->get_pay_type($start_date, $end_date, $ru_id, $type);

        return $res;
    }

    private function get_shipping_type_top($start_date, $ru_id, $type = 0)
    {

        //当月的开始日期和结束日期
        $day = local_date('Y-m-d', $start_date);
        $time = $this->getthemonth($day);

        $start_date = local_strtotime($time[0]);
        $end_date = local_strtotime($time[1]);

        $res = $this->get_shipping_type($start_date, $end_date, $ru_id, $type);

        return $res;
    }
    //multi--One end

    //multi--Two start
    private function get_pay_type($start_date, $end_date, $ru_id)
    {
        $sql = 'SELECT i.pay_id, p.pay_name, i.pay_time ' .
            'FROM ' . $this->dsc->table('payment') . ' AS p, ' . $this->dsc->table('order_info') . ' AS i ' .
            "WHERE p.pay_id = i.pay_id " . $this->orderService->orderQuerySql('real_pay') .
            "AND i.add_time >= '$start_date' AND i.add_time <= '$end_date' " .
            " AND i.ru_id = '$ru_id' " .
            " AND i.main_count = 0 " .  //主订单下有子订单时，则主订单不显示;
            "ORDER BY i.add_time DESC";

        return $this->db->getAll($sql);
    }

    private function get_shipping_type($start_date, $end_date, $ru_id)
    {
        $sql = 'SELECT sp.shipping_id, sp.shipping_name AS ship_name, i.shipping_time ' .
            'FROM ' . $this->dsc->table('shipping') . ' AS sp, ' . $this->dsc->table('order_info') . ' AS i ' .
            'WHERE sp.shipping_id = i.shipping_id ' . $this->orderService->orderQuerySql('real_pay') .
            "AND i.add_time >= '$start_date' AND i.add_time <= '$end_date' " .
            " AND i.ru_id = '$ru_id' " .
            " AND i.main_count = 0 " .  //主订单下有子订单时，则主订单不显示;
            "ORDER BY i.add_time DESC";

        return $this->db->getAll($sql);
    }
    //multi--Two end

    //转为二维数组
    private function get_to_array($arr1, $arr2, $str1 = '', $str2 = '', $str3 = '', $str4 = '')
    {
        $ship_arr = [];
        foreach ($arr1 as $key1 => $row1) {
            foreach ($arr2 as $key2 => $row2) {
                if ($row1["{$str1}"] == $row2["{$str1}"]) {
                    $ship_arr[$row1["{$str1}"]]["{$str2}"][$key2] = $row2;
                    $ship_arr[$row1["{$str1}"]]["{$str3}"] = $row1["{$str3}"];
                    if (!empty($str4)) {
                        $ship_arr[$row1["{$str1}"]]["{$str4}"] = $row1["{$str4}"];
                    }
                }
            }
        }

        return $ship_arr;
    }

    private function getthemonth($date)
    {
        $firstday = local_date('Y-m-01', local_strtotime($date));
        $lastday = local_date('Y-m-d', local_strtotime("$firstday +1 month -1 day"));
        return [$firstday, $lastday];
    }


    private function get_area($countries, $pro = 0)
    {
        $where = " AND i.main_count = 0 ";  //主订单下有子订单时，则主订单不显示
        $where .= " AND i.order_status " . db_create_in([OS_CONFIRMED, OS_SPLITED]) . "  AND i.shipping_status = '" . SS_RECEIVED . "' AND i.pay_status " . db_create_in([PS_PAYED, PS_PAYING]);
        if ($countries == 1 && $pro == 0) {
            $sql = "SELECT COUNT(*) AS area_num ,r.region_name AS region_name FROM " . $this->dsc->table('order_info') . " AS i " .
                " LEFT JOIN " . $this->dsc->table('region') . " AS r ON r.region_id = i.province" .
                " WHERE i.country = '$countries' " . $where . " GROUP BY i.province";
        } elseif ($countries == 1 && $pro > 0) {
            $sql = "SELECT COUNT(*) AS area_num ,r.region_name AS region_name FROM " . $this->dsc->table('order_info') . " AS i " .
                " LEFT JOIN " . $this->dsc->table('region') . " AS r ON r.region_id = i.city" .
                " WHERE i.province = '$pro' " . $where . " GROUP BY i.city";
        }
        $res = $this->db->getAll($sql);
        return $res;
    }
}
