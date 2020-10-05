<?php

namespace App\Modules\Admin\Controllers;

use App\Exports\SaleOrderExport;
use App\Repositories\Common\DscRepository;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 商品销售排行
 */
class SaleOrderController extends InitController
{
    protected $orderService;
    protected $orderCommonService;
    protected $dscRepository;

    public function __construct(
        OrderService $orderService,
        OrderCommonService $orderCommonService,
        DscRepository $dscRepository
    )
    {
        $this->orderService = $orderService;
        $this->orderCommonService = $orderCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang('statistic', 'admin');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'sell_stats']);

        if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' || $_REQUEST['act'] == 'download')) {
            /* 检查权限 */
            $check_auth = check_authz_json('sale_order_stats');
            if ($check_auth !== true) {
                return $check_auth;
            }
            if (strstr($_REQUEST['start_date'], '-') === false) {
                $_REQUEST['start_date'] = local_date('Y-m-d H:i:s', $_REQUEST['start_date']);
                $_REQUEST['end_date'] = local_date('Y-m-d H:i:s', $_REQUEST['end_date']);
            }

            /* 下载报表 */
            if ($_REQUEST['act'] == 'download') {
                $filename = $_REQUEST['start_date'] . '_' . $_REQUEST['end_date'] . 'sale_order';

                return Excel::download(new SaleOrderExport, $filename . '.xlsx');
            }
            $goods_order_data = $this->orderCommonService->getSalesOrder($adminru['ru_id']);
            $this->smarty->assign('goods_order_data', $goods_order_data['sales_order_data']);
            $this->smarty->assign('filter', $goods_order_data['filter']);
            $this->smarty->assign('record_count', $goods_order_data['record_count']);
            $this->smarty->assign('page_count', $goods_order_data['page_count']);

            $sort_flag = sort_flag($goods_order_data['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('sale_order.dwt'), '', ['filter' => $goods_order_data['filter'], 'page_count' => $goods_order_data['page_count']]);
        } else {
            /* 权限检查 */
            admin_priv('sale_order_stats');

            /* 时间参数 */
            if (!isset($_REQUEST['start_date'])) {
                $start_date = local_strtotime('-7 day');
            }
            if (!isset($_REQUEST['end_date'])) {
                $end_date = local_strtotime('today');
            }
            $goods_order_data = $this->orderCommonService->getSalesOrder($adminru['ru_id']);
            /* 赋值到模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sell_stats']);
            $this->smarty->assign('goods_order_data', $goods_order_data['sales_order_data']);
            $this->smarty->assign('filter', $goods_order_data['filter']);
            $this->smarty->assign('record_count', $goods_order_data['record_count']);
            $this->smarty->assign('page_count', $goods_order_data['page_count']);
            $this->smarty->assign('filter', $goods_order_data['filter']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_goods_num', '<img src="' . __TPL__ . '/images/sort_desc.gif">');
            $this->smarty->assign('start_date', local_date('Y-m-d H:i:s', $start_date));
            $this->smarty->assign('end_date', local_date('Y-m-d H:i:s', $end_date));
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['download_sale_sort'], 'href' => '#download']);

            /* 显示页面 */

            return $this->smarty->display('sale_order.dwt');
        }
    }
}
