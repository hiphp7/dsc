<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Sale\SaleGeneralManageService;

/**
 * 销售概况
 */
class SaleGeneralController extends InitController
{
    protected $merchantCommonService;
    protected $dscRepository;
    protected $baseRepository;
    protected $saleGeneralManageService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        SaleGeneralManageService $saleGeneralManageService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->saleGeneralManageService = $saleGeneralManageService;
    }

    public function index()
    {
        $this->dscRepository->helpersLang('statistic', 'admin');

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        /* 权限判断 */
        admin_priv('sale_order_stats');

        $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'report_sell']);

        /* act操作项的初始化 */
        if (empty($_REQUEST['act']) || !in_array($_REQUEST['act'], ['list', 'download', 'query'])) {
            $_REQUEST['act'] = 'list';
        }

        /* ------------------------------------------------------ */
        //-- 显示统计信息
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $start_time = local_mktime(0, 0, 0, local_date('m'), 1, local_date('Y')); //本月第一天
            $end_time = local_mktime(0, 0, 0, local_date('m'), local_date('t'), local_date('Y')) + 24 * 60 * 60 - 1; //本月最后一天
            $start_time = local_date($GLOBALS['_CFG']['time_format'], $start_time);
            $end_time = local_date($GLOBALS['_CFG']['time_format'], $end_time);

            $this->smarty->assign('start_time', $start_time);
            $this->smarty->assign('end_time', $end_time);

            /* 载入订单状态、付款状态、发货状态 */
            $this->smarty->assign('os_list', $this->saleGeneralManageService->getStatusList('order'));
            $this->smarty->assign('ss_list', $this->saleGeneralManageService->getStatusList('shipping'));

            $data = $this->saleGeneralManageService->getDataList();

            $this->smarty->assign('data_list', $data['data_list']);
            $this->smarty->assign('filter', $data['filter']);
            $this->smarty->assign('record_count', $data['record_count']);
            $this->smarty->assign('page_count', $data['page_count']);

            $data['start_time'] = isset($data['start_time']) ? $data['start_time'] : '';
            $data['end_time'] = isset($data['end_time']) ? $data['end_time'] : '';
            $this->smarty->assign('date_start_time', $data['start_time']);
            $this->smarty->assign('date_end_time', $data['end_time']);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_order_time', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['down_sales_stats'],
                'href' => 'sale_general.php?act=download&start_time=' . $start_time . '&end_time=' . $end_time]);

            /* 显示模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['report_sell']);

            return $this->smarty->display('sale_general.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $data = $this->saleGeneralManageService->getDataList();
            $this->smarty->assign('data_list', $data['data_list']);
            $this->smarty->assign('filter', $data['filter']);
            $this->smarty->assign('record_count', $data['record_count']);
            $this->smarty->assign('page_count', $data['page_count']);

            $sort_flag = sort_flag($data['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return make_json_result($this->smarty->fetch('library/sale_general.lbi'), '', ['filter' => $data['filter'], 'page_count' => $data['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 下载EXCEL报表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download') {
            $data = $this->saleGeneralManageService->getDataList();
            $data_list = $data['data_list'];

            /* 文件名 */
            $filename = str_replace(" ", "-", local_date($GLOBALS['_CFG']['time_format'], gmtime())) . "_" . rand(0, 1000);

            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=$filename.xls");

            /* 文件标题 */
            echo dsc_iconv(EC_CHARSET, 'GB2312', $filename . $GLOBALS['_LANG']['sales_statistics']) . "\t\n";

            /* 订单数量, 销售出商品数量, 销售金额 */
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_steps_name']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_name']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['pro_code']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['category']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['amount']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['unit_price']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['total_amount']) . "\t";
            echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['sale_date']) . "\t\n";

            foreach ($data_list as $data) {
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['shop_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['goods_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['goods_sn']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['cat_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['goods_number']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['goods_price']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['total_fee']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data['add_time']) . "\t";
                echo "\n";
            }
        }
    }
}
