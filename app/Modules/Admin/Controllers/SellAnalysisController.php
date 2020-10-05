<?php

namespace App\Modules\Admin\Controllers;

use App\Models\OrderInfo;
use App\Repositories\Common\DscRepository;

class SellAnalysisController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper(['order', 'statistical']);

        $this->dscRepository->helpersLang(['statistic'], 'admin');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 时间参数 */
        if (isset($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
            $start_date = local_strtotime($_REQUEST['start_date']);
            $end_date = local_strtotime($_REQUEST['end_date']);
            if ($start_date == $end_date) {
                $end_date = $start_date + 86400;
            }
        } else {
            $today = local_strtotime(local_date('Y-m-d'));
            $start_date = $today - 86400 * 6;
            $end_date = $today + 86400;
        }

        $this->smarty->assign('start_date', local_date('Y-m-d H:i:s', $start_date));
        $this->smarty->assign('end_date', local_date('Y-m-d H:i:s', $end_date));

        /*------------------------------------------------------ */
        //-- 销售量
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'sales_volume') {
            /* 主子订单处理 */
            $total_num = OrderInfo::where('main_count', 0)
                ->distinct('order_id')
                ->count();
            $this->smarty->assign('total_num', $total_num);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sales_volume']);

            return $this->smarty->display('sales_volume_stats.dwt');
        }

        /*------------------------------------------------------ */
        //-- 销售额
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sales_money') {
            /* 主子订单处理 */
            $total_fee = OrderInfo::selectRaw('SUM(money_paid + surplus) as total_fee')
                ->where('main_count', 0)
                ->value('total_fee');
            $total_fee = $total_fee ? $total_fee : 0;

            $this->smarty->assign('total_fee', $total_fee);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sales_money']);

            return $this->smarty->display('sales_money_stats.dwt');
        }

        /*------------------------------------------------------ */
        //-- 账单统计异步
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_chart_data') {
            $search_data = array();
            $search_data['start_date'] = $start_date;
            $search_data['end_date'] = $end_date;
            $search_data['type'] = empty($_REQUEST['type']) ? 'volume' : trim($_REQUEST['type']);
            $chart_data = get_statistical_sale($search_data);

            return make_json_result($chart_data);
        }

        /*------------------------------------------------------ */
        //-- 订单统计
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_stats') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_stats']);

            return $this->smarty->display('sales_order_stats.dwt');
        }
    }
}
