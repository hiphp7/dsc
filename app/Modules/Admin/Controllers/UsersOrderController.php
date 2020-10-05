<?php

namespace App\Modules\Admin\Controllers;

use App\Exports\UserOrderExport;
use App\Repositories\Common\DscRepository;
use App\Services\Order\OrderCommonService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 会员排行统计程序
 */
class UsersOrderController extends InitController
{
    protected $orderCommonService;
    protected $dscRepository;

    public function __construct(
        OrderCommonService $orderCommonService,
        DscRepository $dscRepository
    )
    {
        $this->orderCommonService = $orderCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang('statistic', 'admin');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' || $_REQUEST['act'] == 'download')) {

            /* 检查权限 */
            $check_auth = check_authz_json('client_flow_stats');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (isset($_REQUEST['start_date']) && strstr($_REQUEST['start_date'], '-') === false) {
                $_REQUEST['start_date'] = local_date('Y-m-d', $_REQUEST['start_date']);
                $_REQUEST['end_date'] = local_date('Y-m-d', $_REQUEST['end_date']);
            }

            if ($_REQUEST['act'] == 'download') {
                $filename = $_REQUEST['start_date'] . '_' . $_REQUEST['end_date'] . 'users_order';

                return Excel::download(new UserOrderExport, $filename . '.xlsx');
            }

            $user_orderinfo = $this->orderCommonService->getUserOrderInfo();
            $this->smarty->assign('filter', $user_orderinfo['filter']);
            $this->smarty->assign('record_count', $user_orderinfo['record_count']);
            $this->smarty->assign('page_count', $user_orderinfo['page_count']);
            $this->smarty->assign('user_orderinfo', $user_orderinfo['user_orderinfo']);

            $sort_flag = sort_flag($user_orderinfo['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('users_order.dwt'), '', ['filter' => $user_orderinfo['filter'], 'page_count' => $user_orderinfo['page_count']]);
        } else {
            /* 权限判断 */
            admin_priv('client_flow_stats');
            /* 时间参数 */
            if (!isset($_REQUEST['start_date'])) {
                $start_date = local_strtotime('-7 days');
            }
            if (!isset($_REQUEST['end_date'])) {
                $end_date = local_strtotime('today');
            }

            /* 取得会员排行数据 */
            $user_orderinfo = $this->orderCommonService->getUserOrderInfo();

            /* 赋值到模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['report_users']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['download_amount_sort'],
                'href' => "#download"]);
            $this->smarty->assign('filter', $user_orderinfo['filter']);
            $this->smarty->assign('record_count', $user_orderinfo['record_count']);
            $this->smarty->assign('page_count', $user_orderinfo['page_count']);
            $this->smarty->assign('user_orderinfo', $user_orderinfo['user_orderinfo']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('start_date', local_date('Y-m-d H:i:s', $start_date));
            $this->smarty->assign('end_date', local_date('Y-m-d H:i:s', $end_date));
            $this->smarty->assign('sort_order_num', '<img src="' . __TPL__ . '/images/sort_desc.gif">');
            /* 页面显示 */

            return $this->smarty->display('users_order.dwt');
        }
    }
}
