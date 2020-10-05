<?php

namespace App\Modules\Admin\Controllers;

use App\Exports\UserStatsAreaExport;
use App\Exports\UserStatsRankExport;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Order\OrderCommonService;
use Maatwebsite\Excel\Facades\Excel;

class UserStatsController extends InitController
{
    protected $orderCommonService;
    protected $timeRepository;
    protected $commonManageService;
    protected $dscRepository;

    public function __construct(
        OrderCommonService $orderCommonService,
        TimeRepository $timeRepository,
        CommonManageService $commonManageService,
        DscRepository $dscRepository
    )
    {
        $this->orderCommonService = $orderCommonService;
        $this->timeRepository = $timeRepository;
        $this->commonManageService = $commonManageService;
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
        $this->smarty->assign('area_list', $this->commonManageService->getAreaRegionList());

        /*------------------------------------------------------ */
        //-- 新会员
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'new') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['newadd_user']);

            return $this->smarty->display('new_user_stats.dwt');
        }

        /*------------------------------------------------------ */
        //-- 新会员异步
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_chart_data') {
            $search_data = array();
            $search_data['start_date'] = $start_date;
            $search_data['end_date'] = $end_date;
            $chart_data = get_statistical_new_user($search_data);

            return make_json_result($chart_data);
        }

        /*------------------------------------------------------ */
        //-- 会员统计
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_analysis') {
            $order_list = $this->orderCommonService->userSaleStats();

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['user_analysis']);

            return $this->smarty->display('user_analysis.dwt');
        }

        /*------------------------------------------------------ */
        //-- 会员统计查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_analysis_query') {
            $order_list = $this->orderCommonService->userSaleStats();

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('user_analysis.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 会员区域分析
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_area_analysis') {
            $order_list = $this->orderCommonService->userAreaStats();

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['user_area_analysis']);

            return $this->smarty->display('user_area_analysis.dwt');
        }

        /*------------------------------------------------------ */
        //-- 会员区域分析查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_area_analysis_query') {
            $order_list = $this->orderCommonService->userAreaStats();

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('user_area_analysis.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 会员等级分析
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_rank_analysis') {
            $user_rank = get_statistical_user_rank();
            $this->smarty->assign('user_rank', $user_rank['source']);
            $this->smarty->assign('json_data', json_encode($user_rank));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['user_rank_analysis']);

            return $this->smarty->display('user_rank_analysis.dwt');
        }

        /*------------------------------------------------------ */
        //-- 会员消费排行
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_consumption_rank') {
            $order_list = $this->orderCommonService->userSaleStats();

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['user_consumption_rank']);

            return $this->smarty->display('user_consumption_rank.dwt');
        }

        /*------------------------------------------------------ */
        //-- 会员消费排行查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_consumption_rank_query') {
            $order_list = $this->orderCommonService->userSaleStats();

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('user_consumption_rank.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 导出地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download_area') {
            $_GET['uselastfilter'] = 1;

            $filename = 'users_tats_area_' . $this->timeRepository->getLocalDate('Y-m-d H:i:s');
            return Excel::download(new UserStatsAreaExport, $filename . '.xlsx');
        }

        /*------------------------------------------------------ */
        //-- 导出排行
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download_rank') {
            $_GET['uselastfilter'] = 1;

            $filename = 'users_tats_rank_' . $this->timeRepository->getLocalDate('Y-m-d H:i:s');
            return Excel::download(new UserStatsRankExport, $filename . '.xlsx');
        }
    }
}
