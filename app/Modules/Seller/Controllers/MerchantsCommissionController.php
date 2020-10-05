<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Phpzip;
use App\Repositories\Common\DscRepository;
use App\Services\Commission\CommissionService;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Merchant\MerchantsCommissionManageService;
use App\Services\Order\OrderRefoundService;
use App\Services\Order\OrderService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心服务站管理
 */
class MerchantsCommissionController extends InitController
{
    protected $commissionService;
    protected $orderService;
    protected $commonManageService;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $orderRefoundService;
    protected $storeCommonService;
    protected $merchantsCommissionManageService;

    public function __construct(
        CommissionService $commissionService,
        OrderService $orderService,
        CommonManageService $commonManageService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        OrderRefoundService $orderRefoundService,
        StoreCommonService $storeCommonService,
        MerchantsCommissionManageService $merchantsCommissionManageService
    )
    {
        $this->commissionService = $commissionService;
        $this->orderService = $orderService;
        $this->commonManageService = $commonManageService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->orderRefoundService = $orderRefoundService;
        $this->storeCommonService = $storeCommonService;
        $this->merchantsCommissionManageService = $merchantsCommissionManageService;
    }

    public function index()
    {
        load_helper('order');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "merchants_commission");
        define('SUPPLIERS_ACTION_LIST', 'delivery_view,back_view');

        //ecmoban模板堂 --zhuo start
        $admin_id = get_admin_id();
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        //ecmoban模板堂 --zhuo end
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['17_merchants']);
        $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '03_merchants_commission']);

        /*佣金模式 by wu*/
        $this->smarty->assign('commission_model', $GLOBALS['_CFG']['commission_model']);
        /*------------------------------------------------------ */
        //-- 服务站列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['seller_commission'], 'href' => 'merchants_commission.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['negative_bill_list'], 'href' => 'merchants_commission.php?act=negative_bill&id=' . $adminru['ru_id']];

            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $this->smarty->assign('current', 'merchants_commission_list');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['brokerage_amount_list']); // 当前导航

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $result = $this->merchantsCommissionManageService->merchantsCommissionList();

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            /* 获得该管理员的权限 */
            $priv_str = $this->db->getOne("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str != 'all') {
                /* 查询 */
                $this->smarty->assign('no_all', 0);
                $ser_name = $GLOBALS['_LANG']['suppliers_list_server'];
            } else {
                /* 查询 */
                $this->smarty->assign('no_all', 1);
                $this->smarty->assign('action_link', ['href' => 'merchants_commission.php?act=add', 'text' => $GLOBALS['_LANG']['add_suppliers_server']]);
                $this->smarty->assign('action_link2', ['href' => 'merchants_percent.php?act=list', 'text' => $GLOBALS['_LANG']['suppliers_percent_list']]);
                $this->smarty->assign('action_link3', ['href' => 'javascript:download_list();', 'text' => $GLOBALS['_LANG']['export_all_suppliers']]); //liu
                $ser_name = $GLOBALS['_LANG']['suppliers_server_center'];
            }
            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('merchants_commission_list', $result['result']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="__TPL__/images/sort_desc.gif">');

            /* 显示模板 */

            return $this->smarty->display('merchants_commission_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 获得该管理员的权限 */
            $priv_str = $this->db->getOne("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str != 'all') {
                $this->smarty->assign('no_all', 0);
            } else {
                $this->smarty->assign('no_all', 1);
            }

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            /* 查询 */
            $result = $this->merchantsCommissionManageService->merchantsCommissionList();

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('merchants_commission_list', $result['result']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_commission_list.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //-- 添加/编辑佣金设置
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['seller_commission'], 'href' => 'merchants_commission.php?act=list'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['negative_bill_list'], 'href' => 'merchants_commission.php?act=negative_bill&id=' . $adminru['ru_id']];

            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['suppliers_list_server']); // 当前导航

            $suppliers = [];

            $sql = "SELECT ms.*, mp.percent_value FROM " . $this->dsc->table('merchants_server') . " AS ms " .
                "LEFT JOIN " . $this->dsc->table('merchants_percent') . " AS mp ON mp.percent_id = ms.suppliers_percent" .
                " WHERE user_id = '" . $adminru['ru_id'] . "'";
            $server = $this->db->getRow($sql);

            if ($server['bill_time']) {
                $server['bill_time'] = local_date("Y-m-d", $server['bill_time']);
            }

            $settlement_cycle = $GLOBALS['_LANG']['cfg_range']['settlement_cycle'];
            $server['cycle'] = isset($server) && $server['cycle'] ? $server['cycle'] : 0;

            $this->smarty->assign('user_id', $adminru['ru_id']);
            $this->smarty->assign('server', $server);
            $this->smarty->assign('settlement_cycle', $settlement_cycle[$server['cycle']]);
            $this->smarty->assign('nowtime', local_date("Y-m-d", gmtime()));


            return $this->smarty->display('merchants_commission_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商家订单别表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_list') {
            /* 检查权限 */
            admin_priv('merchants_commission');

            cache()->forget('merchants_download_content_' . $admin_id);

            $date = ['suppliers_percent'];
            $percent_id = get_table_date('merchants_server', "user_id = '" . $adminru['ru_id'] . "' ", $date, $sqlType = 2);

            $date = ['percent_value'];
            $percent_value = get_table_date('merchants_percent', "percent_id = '$percent_id'", $date, $sqlType = 2) . '%';
            $this->smarty->assign('percent_value', $percent_value);

            /* 模板赋值 */
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['03_merchants_commission'], 'href' => 'merchants_commission.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('action_link', ['href' => 'javascript:order_downloadList();', 'text' => $GLOBALS['_LANG']['export_merchant_commission']]);//liu
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['brokerage_order_list']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $order_list = $this->merchantsCommissionManageService->merchantsOrderList();

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('user_id', $adminru['ru_id']);    //liu  供批量结算用
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('brokerage_amount', $order_list['brokerage_amount']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('server_id', '<img src="__TPL__/images/sort_desc.gif">');

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_order_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_list = $this->merchantsCommissionManageService->merchantsOrderList();

            $date = ['suppliers_percent'];
            $percent_id = get_table_date('merchants_server', "user_id = '" . $adminru['ru_id'] . "' ", $date, $sqlType = 2);

            $date = ['percent_value'];
            $percent_value = get_table_date('merchants_percent', "percent_id = '$percent_id'", $date, $sqlType = 2) . '%';
            $this->smarty->assign('percent_value', $percent_value);

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('brokerage_amount', $order_list['brokerage_amount']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('user_id', $adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 排序标记 */
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_order_list.dwt'),
                '',
                ['filter' => $order_list['filter'], 'page_count' => $order_list['page_count']]
            );
        }
        /* ------------------------------------------------------ */
        //--Excel文件下载 商家佣金明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_download') {
            $result = ['is_stop' => 0];

            $page = !empty($_REQUEST['page_down']) ? intval($_REQUEST['page_down']) : 0;//处理的页数
            $page_count = !empty($_REQUEST['page_count']) ? intval($_REQUEST['page_count']) : 0;//总页数
            $merchants_order_list = $this->merchantsCommissionManageService->merchantsOrderList($page);//获取订单数组

            $merchants_download_content = cache("merchants_download_content_" . $admin_id);
            $merchants_download_content = !is_null($merchants_download_content) ? $merchants_download_content : [];

            $merchants_download_content[] = $merchants_order_list;

            cache()->forever("merchants_download_content_" . $admin_id, $merchants_download_content);

            $result['page'] = $page;
            if ($page < $page_count) {
                $result['is_stop'] = 1;//未结算标识
                $result['next_page'] = $page + 1;
            }
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //--Excel文件下载 商家佣金明细
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'merchant_download') {
            $shop_name = $this->merchantCommonService->getShopName($adminru['ru_id'], 1);
            $filename = $shop_name . "-" . date('YmdHis') . ".zip";

            // 获取所有商家的下载数据 按照商家分组

            $merchants_download_content = cache("merchants_download_content_" . $admin_id);
            $merchants_download_content = !is_null($merchants_download_content) ? $merchants_download_content : [];

            $zip = new Phpzip;
            if (!empty($merchants_download_content)) {
                foreach ($merchants_download_content as $k => $merchants_order_list) {
                    $k++;
                    $content = $this->merchantsCommissionManageService->orderDownloadList($merchants_order_list['orders']);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['label_commision_count']);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $merchants_order_list['brokerage_amount']['all_price']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['label_settled']);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $merchants_order_list['brokerage_amount']['is_settlement_price']) . "\t\n";
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $GLOBALS['_LANG']['label_not_settled']);
                    $content .= dsc_iconv(EC_CHARSET, 'GB2312', $merchants_order_list['brokerage_amount']['no_settlement_price']) . "\t\n";
                    $zip->add_file($content, date('YmdHis') . '-' . $k . '.csv');
                }
            }
            /* 清除缓存 */
            cache()->forget('merchants_download_content_' . $admin_id);

            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        }

        /*------------------------------------------------------ */
        //--商家订单列表  操作日志 by kong
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'handle_log') {
            /* 权限判断 */
            admin_priv('merchants_commission');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['handle_log']);
            $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['brokerage_order_list'], 'href' => 'merchants_commission.php?act=order_list&id=' . $user_id, 'class' => 'icon-reply']);

            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $gift_gard_log = $this->commonManageService->getGiftGardLog($id);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('gift_gard_log', $gift_gard_log['pzd_list']);
            $this->smarty->assign('filter', $gift_gard_log['filter']);
            $this->smarty->assign('record_count', $gift_gard_log['record_count']);
            $this->smarty->assign('page_count', $gift_gard_log['page_count']);
            $this->smarty->assign('order_id', $id);
            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($gift_gard_log, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            return $this->smarty->display("merchants_order_log.dwt");
        }

        /*------------------------------------------------------ */
        //--商家订单列表  操作日志 ajax by kong
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'Ajax_handle_log') {
            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $gift_gard_log = $this->commonManageService->getGiftGardLog($id);

            $this->smarty->assign('gift_gard_log', $gift_gard_log['pzd_list']);
            $this->smarty->assign('filter', $gift_gard_log['filter']);
            $this->smarty->assign('record_count', $gift_gard_log['record_count']);
            $this->smarty->assign('page_count', $gift_gard_log['page_count']);

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($gift_gard_log, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            return make_json_result($this->smarty->fetch('merchants_order_log.dwt'), '', ['filter' => $gift_gard_log['filter'], 'page_count' => $gift_gard_log['page_count']]);
        }

        /* ------------------------------------------------------ */
        //--店铺佣金冻结
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'commission_bill') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['brokerage_amount_list'], 'href' => 'merchants_commission.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['negative_bill_list'], 'href' => 'merchants_commission.php?act=negative_bill&id=' . $adminru['ru_id']];

            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $user_id = $adminru['ru_id'];
            $this->smarty->assign('user_id', $user_id);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->commissionService->commissionBillList();

            // 分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="__TPL__/images/sort_desc.gif">');

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_commission_bill.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'commission_bill_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = $this->commissionService->commissionBillList();

            // 分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_commission_bill.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //--申请账单结算
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'apply_for') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['brokerage_amount_list'], 'href' => 'merchants_commission.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['negative_bill_list'], 'href' => 'merchants_commission.php?act=negative_bill&id=' . $adminru['ru_id']];

            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $gmtime = gmtime();
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill']); // 当前导航

            $bill_id = isset($_REQUEST['bill_id']) && !empty($_REQUEST['bill_id']) ? intval($_REQUEST['bill_id']) : 0;
            $seller_id = $adminru['ru_id'];

            $this->smarty->assign('user_id', $seller_id);
            $this->smarty->assign('form_act', 'bill_update');

            $bill = $this->commissionService->getBillDetail($bill_id);

            if (empty($bill) || $bill['seller_id'] != $seller_id) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            //出账时间
            $chargeoff_time = $bill['chargeoff_time'] + 24 * 3600 * $bill['bill_freeze_day'];

            if ($gmtime < $chargeoff_time) {
                $Loaction = "merchants_commission.php?act=commission_bill&id=" . $seller_id;
                return dsc_header("Location: $Loaction\n");
            }

            $this->smarty->assign('bill', $bill);

            if ($bill['chargeoff_status'] == 0) {
                $link[0] = ['href' => 'merchants_commission.php?act=commission_bill&id=' . $seller_id, 'text' => $GLOBALS['_LANG']['commission_bill']];

                if ($bill['end_time'] > $gmtime) {
                    return sys_msg($sys_msg = $GLOBALS['_LANG']['apply_for_failure_time'], 1, $link);
                } else {
                    return sys_msg($sys_msg = $GLOBALS['_LANG']['apply_for_failure'], 1, $link);
                }
            }

            // 跨境多商户
            if (CROSS_BORDER === true) {
                $this->smarty->assign('is_cross', 1);
            } else {
                $this->smarty->assign('is_cross', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_bill_applyfor.dwt');
        }

        /* ------------------------------------------------------ */
        //--申请账单更新
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_update') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            $bill_id = isset($_REQUEST['bill_id']) && !empty($_REQUEST['bill_id']) ? intval($_REQUEST['bill_id']) : 0;
            $seller_id = $adminru['ru_id'];
            $apply_note = isset($_REQUEST['apply_note']) && !empty($_REQUEST['apply_note']) ? addslashes($_REQUEST['apply_note']) : '';

            if (empty($bill_id)) {
                $Loaction = "merchants_commission.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }

            $bill = $this->commissionService->getBillDetail($bill_id);

            if ($bill['bill_apply'] == 0) {
                $other = [
                    'apply_note' => $apply_note,
                    'bill_apply' => 1,
                    'apply_time' => gmtime()
                ];

                $negative_total = $bill['negative_bill']['total'] ?? 0;
                if ($negative_total > 0) {
                    $other['negative_amount'] = $negative_total;
                }

                $this->db->autoExecute($this->dsc->table('seller_commission_bill'), $other, 'UPDATE', "id = '$bill_id'");

                if (isset($bill['negative_bill']['negative_id'])) {
                    $negativeOther = array(
                        'commission_bill_id' => $bill['id'],
                        'commission_bill_sn' => $bill['bill_sn']
                    );
                    $this->db->autoExecute($this->dsc->table('seller_negative_bill'), $negativeOther, 'UPDATE', "id " . db_create_in($bill['negative_bill']['negative_id']) . " AND commission_bill_id = 0");
                }

                $sys_msg = $GLOBALS['_LANG']['apply_for_success'];
            } else {
                $sys_msg = $GLOBALS['_LANG']['apply_for_failure'];
            }

            $link[0] = ['href' => 'merchants_commission.php?act=commission_bill&id=' . $seller_id, 'text' => $GLOBALS['_LANG']['commission_bill']];
            return sys_msg($sys_msg, 0, $link);
        }

        /* ------------------------------------------------------ */
        //--店铺佣金账单明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_detail') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['brokerage_amount_list'], 'href' => 'merchants_commission.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['negative_bill_list'], 'href' => 'merchants_commission.php?act=negative_bill&id=' . $adminru['ru_id']];

            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $user_id = $adminru['ru_id'];
            $this->smarty->assign('user_id', $user_id);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill_detail']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->commissionService->billDetailList();

            // 分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="__TPL__/images/sort_desc.gif">');

            $bill = $this->commissionService->getBillDetail($result['filter']['bill_id']);
            $this->smarty->assign('bill', $bill);

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_bill_detail.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_detail_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = $this->commissionService->billDetailList();

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_bill_detail.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //--店铺佣金账单明细
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_goods') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['brokerage_amount_list'], 'href' => 'merchants_commission.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['negative_bill_list'], 'href' => 'merchants_commission.php?act=negative_bill&id=' . $adminru['ru_id']];

            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $user_id = $adminru['ru_id'];
            $this->smarty->assign('user_id', $user_id);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill_detail']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->commissionService->billGoodsList();

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('goods_list', $result['goods_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="__TPL__/images/sort_desc.gif">');

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            /* 显示模板 */

            return $this->smarty->display('merchants_bill_goods.dwt');
        }

        /* ------------------------------------------------------ */
        //--账单未确认收货订单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_notake_order') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['brokerage_amount_list'], 'href' => 'merchants_commission.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $user_id = $adminru['ru_id'];
            $this->smarty->assign('user_id', $user_id);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['commission_bill_detail']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = bill_notake_order_list();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('order_list', $result['order_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="__TPL__/images/sort_desc.gif">');

            $bill = $this->commissionService->getBillDetail($result['filter']['bill_id']);
            $this->smarty->assign('bill', $bill);

            if (file_exists(MOBILE_DRP)) {
                $this->smarty->assign('is_dir', 1);
            } else {
                $this->smarty->assign('is_dir', 0);
            }

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            /* 显示模板 */

            return $this->smarty->display('merchants_bill_notake_order.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_notake_order_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = bill_notake_order_list();

            $this->smarty->assign('order_list', $result['order_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_bill_notake_order.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'bill_goods_query') {
            $check_auth = check_authz_json('merchants_commission');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = $this->commissionService->billGoodsList();

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('goods_list', $result['goods_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('merchants_bill_goods.dwt'),
                '',
                ['filter' => $result['filter'], 'page_count' => $result['page_count']]
            );
        }

        /* ------------------------------------------------------ */
        //--店铺负账单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'negative_bill') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = array();
            $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['brokerage_amount_list'], 'href' => 'merchants_commission.php?act=list');
            $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']);
            $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']);
            $tab_menu[] = array('curr' => 1, 'text' => $GLOBALS['_LANG']['negative_bill_list'], 'href' => 'merchants_commission.php?act=negative_bill&id=' . $adminru['ru_id']);

            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $user_id = isset($_REQUEST['id']) && empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $this->smarty->assign('user_id', $user_id);

            if (empty($user_id)) {
                $Loaction = "merchants_commission.php?act=list";
                ecs_header("Location: $Loaction\n");
                exit;
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['negative_bill_list']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->merchantsCommissionManageService->negativeBillList();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');

            /* 显示模板 */
            return $this->smarty->display('merchants_negative_bill.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'negative_query') {
            check_authz_json('merchants_commission');

            /* 查询 */
            $result = $this->merchantsCommissionManageService->negativeBillList();

            $this->smarty->assign('bill_list', $result['bill_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('merchants_negative_bill.dwt'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
        }


        /* ------------------------------------------------------ */
        //--店铺负账单订单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'negative_order') {

            /* 检查权限 */
            admin_priv('merchants_commission');

            //页面分菜单 by wu start
            $tab_menu = array();
            $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['brokerage_amount_list'], 'href' => 'merchants_commission.php?act=list');
            $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['commission_setup'], 'href' => 'merchants_commission.php?act=edit&id=' . $adminru['ru_id']);
            $tab_menu[] = array('curr' => 0, 'text' => $GLOBALS['_LANG']['commission_bill'], 'href' => 'merchants_commission.php?act=commission_bill&id=' . $adminru['ru_id']);
            $tab_menu[] = array('curr' => 1, 'text' => $GLOBALS['_LANG']['negative_bill_list'], 'href' => 'merchants_commission.php?act=negative_bill&id=' . $adminru['ru_id']);
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['negative_order_list']); // 当前导航
            $this->smarty->assign('full_page', 1); // 翻页参数

            $result = $this->merchantsCommissionManageService->negativeOrderList();

            $this->smarty->assign('full_page', 1); // 翻页参数
            $this->smarty->assign('order_list', $result['order_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');

            $this->smarty->assign('user_id', $result['filter']['seller_id']);

            /* 显示模板 */
            return $this->smarty->display('negative_order_list.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'negative_order_query') {
            check_authz_json('merchants_commission');

            /* 查询 */
            $result = $this->merchantsCommissionManageService->negativeOrderList();

            $this->smarty->assign('order_list', $result['order_list']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('negative_order_list.dwt'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
        }
    }
}
