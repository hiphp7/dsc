<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\BaseRepository;
use App\Services\Exchange\ExchangeDetailManageService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 管理商家积分明细程序文件
 */
class ExchangeDetailController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $exchangeDetailManageService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        ExchangeDetailManageService $exchangeDetailManageService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->exchangeDetailManageService = $exchangeDetailManageService;
    }

    public function index()
    {
        /* ------------------------------------------------------ */
        //--商家积分明细导出
        /* ------------------------------------------------------ */
        if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'detail_query' || $_REQUEST['act'] == 'download')) {
            /* ------------------------------------------------------ */
            //--Excel文件下载
            /* ------------------------------------------------------ */
            if ($_REQUEST['act'] == 'download') {
                $file_name = $GLOBALS['_LANG']['store_integral_detail'];
                $exchange_detail = $this->exchangeDetailManageService->getShopExchangeDetail(false);

                header("Content-type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=$file_name.xls");

                /* 文件标题 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['store_integral_detail']) . "\t\n";

                /* 商品名称,订单号,商品数量,销售价格,销售日期 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_steps_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['gift_consumption_score']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['gift_grade_integral']) . "\t\n";

                foreach ($exchange_detail['detail'] as $key => $value) {
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['shop_name']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['give_integral']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['rank_integral']) . "\t";
                    echo "\n";
                }
                exit;
            }

            $exchange_detail_data = $this->exchangeDetailManageService->getShopExchangeDetail();
            $this->smarty->assign('filter', $exchange_detail_data['filter']);
            $this->smarty->assign('record_count', $exchange_detail_data['record_count']);
            $this->smarty->assign('page_count', $exchange_detail_data['page_count']);
            $this->smarty->assign('detail', $exchange_detail_data['detail']);

            return make_json_result($this->smarty->fetch('exchange_detail_list.dwt'), '', ['filter' => $exchange_detail_data['filter'], 'page_count' => $exchange_detail_data['page_count']]);
        }

        /* ------------------------------------------------------ */
        //--商家订单列表导出
        /* ------------------------------------------------------ */
        if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'order_query' || $_REQUEST['act'] == 'order_download')) {
            if ($_REQUEST['act'] == 'order_download') {
                $file_name = lang('admin/exchange_detail.order_list');
                $order_list = $this->exchangeDetailManageService->giveIntegralOrderList();
                header("Content-type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=$file_name.xls");

                /* 文件标题 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['give_integral_order_list']) . "\t\n";

                /* 商品名称,订单号,商品数量,销售价格,销售日期 */
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['order_sn']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_steps_name']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['goods_number']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['give_integral']) . "\t";
                echo dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['rank_integral']) . "\t\n";

                foreach ($order_list['item'] as $key => $value) {
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['order_sn']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_name']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['goods_number']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['give_integral']) . "\t";
                    echo dsc_iconv(EC_CHARSET, 'GB2312', $value['rank_integral']) . "\t";
                    echo "\n";
                }
            }

            $order_list_data = $this->exchangeDetailManageService->giveIntegralOrderList();
            $this->smarty->assign('filter', $order_list_data['filter']);
            $this->smarty->assign('record_count', $order_list_data['record_count']);
            $this->smarty->assign('page_count', $order_list_data['page_count']);
            $this->smarty->assign('order_list', $order_list_data['item']);

            return make_json_result($this->smarty->fetch('give_integral_orders.dwt'), '', ['filter' => $order_list_data['filter'], 'page_count' => $order_list_data['page_count']]);
        }

        /* ------------------------------------------------------ */
        //--积分明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'detail') {
            admin_priv('exchange');
            $exchange_detail = $this->exchangeDetailManageService->getShopExchangeDetail(true);
            $this->smarty->assign('filter', $exchange_detail['filter']);
            $this->smarty->assign('record_count', $exchange_detail['record_count']);
            $this->smarty->assign('page_count', $exchange_detail['page_count']);
            $this->smarty->assign('detail', $exchange_detail['detail']);
            $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'exchange_count']);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('exchange_detail_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 积分明细翻页，排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'detail_query') {
            $exchange_detail = $this->exchangeDetailManageService->getShopExchangeDetail(true);
            $this->smarty->assign('filter', $exchange_detail['filter']);
            $this->smarty->assign('record_count', $exchange_detail['record_count']);
            $this->smarty->assign('page_count', $exchange_detail['page_count']);
            $this->smarty->assign('detail', $exchange_detail['detail']);

            $sort_flag = sort_flag($exchange_detail['filter']);

            return make_json_result($this->smarty->fetch('exchange_detail_list.dwt'), '', ['filter' => $exchange_detail['filter'], 'page_count' => $exchange_detail['page_count']]);
        }

        /* ------------------------------------------------------ */
        //--商家积分明细查看商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'exchange_goods') {
            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $goods = $this->exchangeDetailManageService->getExchangeList($user_id, $is_pagination = true);
            $this->smarty->assign('filter', $goods['filter']);
            $this->smarty->assign('record_count', $goods['record_count']);
            $this->smarty->assign('page_count', $goods['page_count']);
            $this->smarty->assign('goods', $goods['goods']);
            $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'exchange_count_goods']);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('exchange_goods_detail_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商家积分明细查看商品翻页，排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'goods_detail_query') {

            /*$check_auth = check_authz_json('exchange_goods');
            if ($check_auth !== true) {
                return $check_auth;
            }*/

            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $goods = $this->exchangeDetailManageService->getExchangeList($user_id, $is_pagination = true);
            $this->smarty->assign('filter', $goods['filter']);
            $this->smarty->assign('record_count', $goods['record_count']);
            $this->smarty->assign('page_count', $goods['page_count']);
            $this->smarty->assign('goods', $goods['goods']);

            $sort_flag = sort_flag($goods['filter']);

            return make_json_result($this->smarty->fetch('exchange_goods_detail_info.dwt'), '', ['filter' => $goods['filter'], 'page_count' => $goods['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 查看赠送积分订单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_view') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['give_integral_order_list']);
            $this->smarty->assign('action_link', ['href' => 'exchange_detail.php?act=export_orders&', 'text' => $GLOBALS['_LANG']['export']]);

            $order_list = $this->exchangeDetailManageService->giveIntegralOrderList();
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('order_list', $order_list['item']);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('give_integral_orders.dwt');
        }

        /*------------------------------------------------------ */
        //-- 积分订单翻页，排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_query') {
            $order_list = $this->exchangeDetailManageService->giveIntegralOrderList();
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('order_list', $order_list['item']);

            $sort_flag = sort_flag($order_list['filter']);

            return make_json_result($this->smarty->fetch('give_integral_orders.dwt'), '', ['filter' => $order_list['filter'], 'page_count' => $order_list['page_count']]);
        }
    }
}
