<?php

namespace App\Modules\Suppliers\Controllers;

use App\Models\Shipping;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * 记录管理员操作日志
 */
class SuppliersStatsController extends InitController
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $this->dscRepository->helpersLang(['statistic'], 'suppliers');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $adminru = get_admin_ru_id();
        $this->smarty->assign('ru_id', $adminru['ru_id']);
        $suppliers_id = $adminru['suppliers_id'];
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['04_suppliers_sale_order_stats']);

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

        $this->smarty->assign('menu_select', array('action' => '04_suppliers_sale_order_stats', 'current' => 'suppliers_stats'));

        /* ------------------------------------------------------ */
        //--订单统计
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('suppliers_stats');
            $order_general = $this->get_order_general();
            $this->smarty->assign('order_general', $order_general);

            /* 时间参数 */
            $is_multi = empty($_POST['is_multi']) ? false : true;

            $start_date_arr = array();
            $end_date_arr = array();
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
                $start_date_arr[] = local_strtotime(local_date('Y-m') . '-1');
                $end_date_arr[] = local_strtotime(local_date('Y-m') . '-31');
            }


            /* 订单概况 */
            $order_data = array();
            $order_data['order'] = get_suppliers_statistical_data($start_date, $end_date, 'order', $suppliers_id);
            $order_data['sale'] = get_suppliers_statistical_data($start_date, $end_date, 'sale', $suppliers_id);
            /* 配送方式 */
            $ship_data = array();
            $ship_res1 = $ship_res2 = $this->get_shipping_type($start_date, $end_date, $suppliers_id);
            if ($ship_res1) {
                $ship_arr = $this->get_to_array($ship_res1, $ship_res2, 'shipping_id', 'ship_arr', 'ship_name');
                foreach ($ship_arr as $row) {
                    $ship_data[0][] = $row['ship_name'];
                    $ship_data[1][] = array(
                        'value' => count($row['ship_arr']),
                        'name' => $row['ship_name']
                    );
                }
            }

            /* 支付方式 */
            $pay_data = array();

            $pay_item1 = $pay_item2 = $this->get_pay_type($start_date, $end_date, $suppliers_id);

            if ($pay_item1) {
                $pay_arr = $this->get_to_array($pay_item1, $pay_item2, 'pay_id', 'pay_arr', 'pay_name');
                foreach ($pay_arr as $row) {
                    $pay_data[0][] = $row['pay_name'];
                    $pay_data[1][] = array(
                        'value' => count($row['pay_arr']),
                        'name' => $row['pay_name']
                    );
                }
            }

            /* 配送地区 */
            $countries = !empty($_POST['country']) ? intval($_POST['country']) : 1;
            $pro = !empty($_POST['province']) ? intval($_POST['province']) : 0;
            $this->smarty->assign('order_general', $order_general);
            $this->smarty->assign('total_turnover', price_format($order_general['total_turnover']));
            /* 统计数据 */
            $this->smarty->assign('order_data', json_encode($order_data));
            $this->smarty->assign('ship_data', json_encode($ship_data));
            $this->smarty->assign('pay_data', json_encode($pay_data));

            /* 赋值到模板 */
            $this->smarty->assign('is_multi', $is_multi);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['report_order']);
            $this->smarty->assign('start_date', local_date('Y-m-d H:i:s', $start_date));
            $this->smarty->assign('end_date', local_date('Y-m-d H:i:s', $end_date));

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
                $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['down_order_statistics'], 'href' => 'suppliers_stats.php?act=download&start_date=' . $start_date . '&end_date=' . $end_date . '&filename=' . $filename));
            }


            return $this->smarty->display('suppliers_stats.dwt');
        }

        /* ------------------------------------------------------ */
        //--导出
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download') {
            admin_priv('suppliers_stats');
            $filename = !empty($_REQUEST['filename']) ? trim($_REQUEST['filename']) : '';
            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=$filename.xls");
            $start_date = empty($_REQUEST['start_date']) ? strtotime('-20 day') : intval($_REQUEST['start_date']);
            $end_date = empty($_REQUEST['end_date']) ? time() : intval($_REQUEST['end_date']);
            /* 订单概况 */
            $order_info = $this->get_suppliers_id_orderinfo($start_date, $end_date);
            $data = $GLOBALS['_LANG']['order_circs'] . "\n";
            $data .= $GLOBALS['_LANG']['pay_uncomplete'] . " \t " . $GLOBALS['_LANG']['succeed'] . " \t " . $GLOBALS['_LANG']['unconfirmed'] . " \t " . $GLOBALS['_LANG']['suppliers_return'] . " \n";
            $data .= $order_info['confirmed_num'] . " \t " . $order_info['succeed_num'] . " \t " . $order_info['unconfirmed_num'] . " \t " . $order_info['invalid_num'] . "\n";
            $data .= "\n" . $GLOBALS['_LANG']['pay_method'] . "\n";

            $pay_arr = array();
            $ship_arr = array();
            /* 支付方式 */
            //ecmoban模板堂 --zhuo satrt
            $pay_item1 = $this->get_pay_type($start_date, $end_date, $adminru['suppliers_id']);
            $pay_item2 = $this->get_pay_type($start_date, $end_date, $adminru['suppliers_id']);
            if ($pay_item1) {
                $pay_arr = $this->get_to_array($pay_item1, $pay_item2, 'pay_id', 'pay_arr', 'pay_name');
            }
            //ecmoban模板堂 --zhuo end

            foreach ($pay_arr as $val) {
                $data .= $val['pay_name'] . "\t";
            }
            $data .= "\n";
            foreach ($pay_arr as $val) {
                $data .= count($val['pay_arr']) . "\t";
            }

            //ecmoban模板堂 --zhuo satrt
            $ship_res1 = $this->get_shipping_type($start_date, $end_date, $adminru['ru_id']);
            $ship_res2 = $this->get_shipping_type($start_date, $end_date, $adminru['ru_id']);
            if ($ship_res1) {
                $ship_arr = $this->get_to_array($ship_res1, $ship_res2, 'shipping_id', 'ship_arr', 'ship_name');
            }
            //ecmoban模板堂 --zhuo end

            $data .= "\n" . $GLOBALS['_LANG']['shipping_method'] . "\n";
            foreach ($ship_arr as $val) {
                $data .= $val['ship_name'] . "\t";
            }
            $data .= "\n";
            foreach ($ship_arr as $val) {
                $data .= count($val['ship_arr']) . "\t";
            }

            echo dsc_iconv(EC_CHARSET, 'GB2312', $data) . "\t";
            exit;
        }
    }

    /**
     * 取得订单概况数据(包括订单的几种状态)
     * @param       $start_date    开始查询的日期
     * @param       $end_date      查询的结束日期
     * @return      $order_info    订单概况数据
     */
    private function get_suppliers_id_orderinfo($start_date, $end_date)
    {
        $order_info = [];
        $adminru = get_admin_ru_id();

        /* 未支付未完成 */
        $time = $end_date + 86400;

        $unconfirmed_num = WholesaleOrderInfo::mainOrderCount()
            ->where('order_status', 0)
            ->where('pay_status', 0)
            ->where('shipping_status', 0)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $time);

        if ($adminru['suppliers_id'] > 0) {
            $unconfirmed_num = $unconfirmed_num->where('suppliers_id', $adminru['suppliers_id']);
        }

        $order_info['unconfirmed_num'] = $unconfirmed_num->count();

        /* 已支付未完成 */
        $confirmed_num = WholesaleOrderInfo::mainOrderCount()
            ->where('order_status', 0)
            ->where('pay_status', PS_PAYED)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $time);

        if ($adminru['suppliers_id'] > 0) {
            $confirmed_num = $confirmed_num->where('suppliers_id', $adminru['suppliers_id']);
        }

        $order_info['confirmed_num'] = $confirmed_num->count();

        /* 已成交订单数 */
        $succeed_num = WholesaleOrderInfo::mainOrderCount()
            ->where('order_status', 1)
            ->where('pay_status', PS_PAYED)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $time);

        if ($adminru['suppliers_id'] > 0) {
            $succeed_num = $succeed_num->where('suppliers_id', $adminru['suppliers_id']);
        }

        $order_info['succeed_num'] = $succeed_num->count();

        /* 退换货 */
        $invalid_num = WholesaleOrderInfo::mainOrderCount()
            ->where('order_status', 4)
            ->where('pay_status', '>', 0)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<', $time);

        if ($adminru['suppliers_id'] > 0) {
            $invalid_num = $invalid_num->where('suppliers_id', $adminru['suppliers_id']);
        }

        $order_info['invalid_num'] = $invalid_num->count();

        return $order_info;
    }

    /**
     * @param int $type
     * @return mixed
     */
    private function get_order_general()
    {
        $adminru = get_admin_ru_id();

        $now_data = local_mktime(0, 0, 0, local_date('m'), local_date('d'), local_date('Y'));//今日零点时间戳
        $today_amout = "SUM(CASE WHEN add_time > $now_data  THEN order_amount ELSE 0 END) AS today_amout, ";//今日成交总额
        $today_num = "SUM(CASE WHEN add_time > $now_data  THEN 1 ELSE 0 END) AS today_num";//今日订单数量

        $order_info = WholesaleOrderInfo::mainOrderCount()
            ->selectRaw("count(*) as total_order_num, SUM(order_amount) AS total_turnover, " . $today_amout . $today_num)
            ->where('pay_status', PS_PAYED)
            ->where('order_status', 1);

        if ($adminru['suppliers_id'] > 0) {
            $order_info = $order_info->where('suppliers_id', $adminru['suppliers_id']);
        }

        $order_info = $this->baseRepository->getToArrayFirst($order_info);

        return $order_info;
    }

    //multi--Two start
    private function get_pay_type($start_date, $end_date, $suppliers_id = 0)
    {
        $res = WholesaleOrderInfo::mainOrderCount()
            ->where('pay_status', PS_PAYED)
            ->where('order_status', 1)
            ->where('add_time', '>=', $start_date)
            ->where('add_time', '<=', $end_date);

        if ($suppliers_id > 0) {
            $res = $res->where('suppliers_id', $suppliers_id);
        }

        $res = $res->with(['getPayment']);

        $res = $res->orderBy('add_time', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['pay_name'] = $row['get_payment']['pay_name'] ?? '';
            }
        }

        return $res;
    }

    private function get_shipping_type($start_date, $end_date, $suppliers_id = 0)
    {
        $where = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'suppliers_id' => $suppliers_id
        ];
        $res = Shipping::whereHas('getWholesaleOrderInfo', function ($query) use ($where) {
            $query = $query->whereHas('getMainOrderId', function ($query) {
                $query->selectRaw("count(*) as count")->Having('count', 0);
            });

            $query = $query->where('pay_status', PS_PAYED)
                ->where('order_status', 1)
                ->where('add_time', '>=', $where['start_date'])
                ->where('add_time', '<=', $where['end_date']);

            if ($where['suppliers_id'] > 0) {
                $query->where('suppliers_id', $where['suppliers_id']);
            }
        });

        $res = $res->with([
            'getWholesaleOrderInfo'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $res[$key]['ship_name'] = $val['shipping_name'];
                $res[$key]['shipping_time'] = $val['get_wholesale_order_info']['shipping_time'] ?? 0;
                $res[$key]['add_time'] = $val['get_wholesale_order_info']['add_time'] ?? 0;
            }
        }

        $res = $this->baseRepository->getSortBy($res, 'add_time', 'desc');

        return $res;
    }

    //转为二维数组
    private function get_to_array($arr1, $arr2, $str1 = '', $str2 = '', $str3 = '', $str4 = '')
    {
        $ship_arr = array();
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
}
