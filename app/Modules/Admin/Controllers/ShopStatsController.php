<?php

namespace App\Modules\Admin\Controllers;

use App\Exports\ShopStatsAreaExport;
use App\Exports\ShopStatsExport;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryService;
use App\Services\Common\CommonManageService;
use App\Services\Order\OrderCommonService;
use Maatwebsite\Excel\Facades\Excel;

class ShopStatsController extends InitController
{
    protected $orderCommonService;
    protected $timeRepository;
    protected $categoryService;
    protected $commonManageService;
    protected $dscRepository;

    public function __construct(
        OrderCommonService $orderCommonService,
        TimeRepository $timeRepository,
        CategoryService $categoryService,
        CommonManageService $commonManageService,
        DscRepository $dscRepository
    )
    {
        $this->orderCommonService = $orderCommonService;
        $this->timeRepository = $timeRepository;
        $this->categoryService = $categoryService;
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

        $main_category = $this->categoryService->catList();
        $this->smarty->assign('main_category', $main_category);

        $this->smarty->assign('store_type', $GLOBALS['_LANG']['store_type']);
        $this->smarty->assign('area_list', $this->commonManageService->getAreaRegionList());

        /*------------------------------------------------------ */
        //-- 新店铺
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'new') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['newadd_shop']);

            return $this->smarty->display('new_shop_stats.dwt');
        }

        /*------------------------------------------------------ */
        //-- 新店铺异步
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_chart_data') {
            $search_data = array();
            $search_data['start_date'] = $start_date;
            $search_data['end_date'] = $end_date;
            $search_data['shop_categoryMain'] = empty($_REQUEST['shop_categoryMain']) ? 0 : intval($_REQUEST['shop_categoryMain']);
            $search_data['shopNameSuffix'] = empty($_REQUEST['shopNameSuffix']) ? '' : trim($_REQUEST['shopNameSuffix']);
            $chart_data = get_statistical_new_shop($search_data);
            return make_json_result($chart_data);
        }

        /*------------------------------------------------------ */
        //-- 店铺销售
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'shop_sale_stats') {
            $this->smarty->assign('total_stats', shop_total_stats());

            $order_list = $this->orderCommonService->shopSaleStats();

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['shop_sale_stats']);

            return $this->smarty->display('shop_sale_stats.dwt');
        }

        /*------------------------------------------------------ */
        //-- 店铺销售查询
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'shop_sale_stats_query') {
            $order_list = $this->orderCommonService->shopSaleStats();

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('shop_sale_stats.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 店铺综合统计
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'get_total_stats') {
            $total_stats = shop_total_stats();
            return make_json_result('', '', $total_stats);
        }

        /*------------------------------------------------------ */
        //-- 店铺地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'shop_area') {
            $order_list = $this->orderCommonService->shopAreaStats();

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['shop_area_distribution']);
            return $this->smarty->display('shop_area_distribution.dwt');
        }

        /*------------------------------------------------------ */
        //-- 店铺地区查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'shop_area_query') {
            $order_list = $this->orderCommonService->shopAreaStats();

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('shop_area_distribution.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 店铺地区分布
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_area_chart_data') {
            $search_data = array();
            $search_data['start_date'] = empty($_REQUEST['start_date']) ? '' : (strpos($_REQUEST['start_date'], '-') > 0 ? local_strtotime($_REQUEST['start_date']) : $_REQUEST['start_date']);
            $search_data['end_date'] = empty($_REQUEST['end_date']) ? '' : (strpos($_REQUEST['end_date'], '-') > 0 ? local_strtotime($_REQUEST['end_date']) : $_REQUEST['end_date']);
            $search_data['area'] = empty($_REQUEST['area']) ? 0 : intval($_REQUEST['area']);
            $search_data['shop_categoryMain'] = empty($_REQUEST['shop_categoryMain']) ? 0 : intval($_REQUEST['shop_categoryMain']);
            $search_data['shopNameSuffix'] = empty($_REQUEST['shopNameSuffix']) ? '' : trim($_REQUEST['shopNameSuffix']);
            $chart_data = get_statistical_shop_area($search_data);
            return make_json_result($chart_data);
        }

        /*------------------------------------------------------ */
        //-- 导出销售
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download') {
            $_GET['uselastfilter'] = 1;

            $filename = 'shop_sale_stats_' . $this->timeRepository->getLocalDate('Y-m-d H:i:s');
            return Excel::download(new ShopStatsExport, $filename . '.xlsx');
        }

        /*------------------------------------------------------ */
        //-- 导出地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download_area') {
            $_GET['uselastfilter'] = 1;

            $filename = 'shop_stats_area_' . $this->timeRepository->getLocalDate('Y-m-d H:i:s');
            return Excel::download(new ShopStatsAreaExport, $filename . '.xlsx');
        }
    }
}
