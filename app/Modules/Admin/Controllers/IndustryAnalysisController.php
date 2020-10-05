<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;

class IndustryAnalysisController extends InitController
{
    protected $categoryService;
    protected $dscRepository;

    public function __construct(
        CategoryService $categoryService,
        DscRepository $dscRepository
    )
    {
        $this->categoryService = $categoryService;
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

        /*------------------------------------------------------ */
        //-- 账单统计管理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $order_list = industry_analysis();

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_industry_analysis']);

            return $this->smarty->display('industry_analysis.dwt');
        }

        /*------------------------------------------------------ */
        //-- 店铺销售查询
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $order_list = industry_analysis();

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('industry_analysis.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 异步
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_chart_data') {
            $search_data = array();
            $search_data['type'] = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $chart_data = get_statistical_industry_analysis($search_data);

            return make_json_result($chart_data);
        }

        /*------------------------------------------------------ */
        //-- 导出
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download') {
            $_GET['uselastfilter'] = 1;
            $order_list = industry_analysis();
            $tdata = $order_list['orders'];
            $thead = array($GLOBALS['_LANG']['03_category_manage'], $GLOBALS['_LANG']['sale_money'], $GLOBALS['_LANG']['effective_sale_money'], $GLOBALS['_LANG']['total_quantity'], $GLOBALS['_LANG']['effective_quantity'], $GLOBALS['_LANG']['goods_total_num'], $GLOBALS['_LANG']['effective_goods_num'], $GLOBALS['_LANG']['not_sale_money_goods_num'], $GLOBALS['_LANG']['order_user_total']);
            $tbody = array('cat_name', 'goods_amount', 'valid_goods_amount', 'order_num', 'valid_num', 'goods_num', 'order_goods_num', 'no_order_goods_num', 'user_num');

            $config = array(
                'filename' => $GLOBALS['_LANG']['04_industry_analysis'],
                'thead' => $thead,
                'tbody' => $tbody,
                'tdata' => $tdata
            );
            list_download($config);
        }
    }
}
