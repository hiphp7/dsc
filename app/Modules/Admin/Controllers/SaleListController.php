<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Sale\SaleGeneralManageService;
use App\Services\Sale\SaleListManageService;

/**
 * 销售明细列表程序
 */
class SaleListController extends InitController
{
    protected $merchantCommonService;
    protected $dscRepository;
    protected $baseRepository;
    protected $saleGeneralManageService;
    protected $saleListManageService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        SaleGeneralManageService $saleGeneralManageService,
        SaleListManageService $saleListManageService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->saleGeneralManageService = $saleGeneralManageService;
        $this->saleListManageService = $saleListManageService;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang('statistic', 'admin');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'sale_list']);

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
            /* ------------------------------------------------------ */
            //--Excel文件下载
            /* ------------------------------------------------------ */
            if ($_REQUEST['act'] == 'download') {
                $file_name = str_replace(" ", "--", $_REQUEST['start_date'] . '_' . $_REQUEST['end_date'] . '_sale');
                $goods_sales_list = $this->saleListManageService->getSaleList(false);

                header("Content-type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=$file_name.xls");

                /* 文件标题 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $_REQUEST['start_date'] . $GLOBALS['_LANG']['to'] . $_REQUEST['end_date'] . $GLOBALS['_LANG']['sales_list']) . "\t\n";

                /* 商品名称,订单号,商品数量,销售价格,销售日期 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_steps_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['pro_code']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['order_sn']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['amount']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['sell_price']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['total_amount']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['sell_date']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['order_status']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['shipping_status']) . "\t\n";

                foreach ($goods_sales_list['sale_list_data'] as $key => $value) {
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['shop_name']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_sn']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_name']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', '[ ' . $value['order_sn'] . ' ]') . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_num']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['sales_price']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['total_fee']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['sales_time']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['order_status_format']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['shipping_status_format']) . "\t";
                    echo "\n";
                }
            } else {
                $sale_list_data = $this->saleListManageService->getSaleList();

                $this->smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
                $this->smarty->assign('filter', $sale_list_data['filter']);
                $this->smarty->assign('record_count', $sale_list_data['record_count']);
                $this->smarty->assign('page_count', $sale_list_data['page_count']);

                return make_json_result($this->smarty->fetch('sale_list.dwt'), '', ['filter' => $sale_list_data['filter'], 'page_count' => $sale_list_data['page_count']]);
            }
        }
        /* ------------------------------------------------------ */
        //--商品明细列表
        /* ------------------------------------------------------ */
        else {
            /* 权限判断 */
            admin_priv('sale_order_stats');
            /* 时间参数 */
            if (!isset($_REQUEST['start_date'])) {
                $start_date = local_strtotime('-7 days');
            }
            if (!isset($_REQUEST['end_date'])) {
                $end_date = local_strtotime('today');
            }

            $sale_list_data = $this->saleListManageService->getSaleList();

            /* 赋值到模板 */
            $this->smarty->assign('filter', $sale_list_data['filter']);
            $this->smarty->assign('record_count', $sale_list_data['record_count']);
            $this->smarty->assign('page_count', $sale_list_data['page_count']);
            $this->smarty->assign('goods_sales_list', $sale_list_data['sale_list_data']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sell_stats']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('start_date', local_date('Y-m-d H:i:s', $start_date ?? null));
            $this->smarty->assign('end_date', local_date('Y-m-d H:i:s', $end_date ?? null));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sale_list']);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['down_sales'], 'href' => '#download']);

            /* 载入订单状态、付款状态、发货状态 */
            $this->smarty->assign('os_list', $this->saleGeneralManageService->getStatusList('order'));
            $this->smarty->assign('ss_list', $this->saleGeneralManageService->getStatusList('shipping'));

            /* 显示页面 */

            return $this->smarty->display('sale_list.dwt');
        }
    }
}
