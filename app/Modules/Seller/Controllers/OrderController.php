<?php

namespace App\Modules\Seller\Controllers;


use App\Libraries\Phpzip;
use App\Models\BackOrder;
use App\Models\BaitiaoLog;
use App\Models\DeliveryOrder;
use App\Models\Goods;
use App\Models\GoodsTransportExpress;
use App\Models\GoodsTransportExtend;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\SellerNegativeOrder;
use App\Models\Shipping;
use App\Models\Stages;
use App\Models\UserOrderNum;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\BonusService;
use App\Services\Activity\GroupBuyService;
use App\Services\Cart\CartCommonService;
use App\Services\Commission\CommissionService;
use App\Services\Common\OfficeService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Flow\FlowUserService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderRefoundService;
use App\Services\Order\OrderService;
use App\Services\Order\OrderTransportService;
use App\Services\Order\OrderManageService;
use App\Services\Package\PackageGoodsService;
use App\Services\Store\StoreCommonService;
use App\Services\User\UserAddressService;
use App\Services\User\UserBaitiaoService;
use App\Services\User\UserService;
use Chumper\Zipper\Zipper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 订单管理
 * Class OrderController
 * @package App\Modules\Seller\Controllers
 */
class OrderController extends InitController
{
    protected $groupBuyService;
    protected $userService;
    protected $config;
    protected $orderService;
    protected $commonRepository;
    protected $commissionService;
    protected $baseRepository;
    protected $goodsWarehouseService;
    protected $merchantCommonService;
    protected $userBaitiaoService;
    protected $dscRepository;
    protected $orderRefoundService;
    protected $userAddressService;
    protected $orderCommonService;
    protected $storeCommonService;
    protected $cartCommonService;
    protected $goodsAttrService;
    protected $flowUserService;
    protected $packageGoodsService;
    protected $orderTransportService;

    public function __construct(
        GroupBuyService $groupBuyService,
        UserService $userService,
        OrderService $orderService,
        CommonRepository $commonRepository,
        CommissionService $commissionService,
        BaseRepository $baseRepository,
        GoodsWarehouseService $goodsWarehouseService,
        MerchantCommonService $merchantCommonService,
        UserBaitiaoService $userBaitiaoService,
        DscRepository $dscRepository,
        OrderRefoundService $orderRefoundService,
        UserAddressService $userAddressService,
        OrderCommonService $orderCommonService,
        StoreCommonService $storeCommonService,
        CartCommonService $cartCommonService,
        GoodsAttrService $goodsAttrService,
        FlowUserService $flowUserService,
        PackageGoodsService $packageGoodsService,
        OrderTransportService $orderTransportService
    )
    {
        $this->groupBuyService = $groupBuyService;
        $this->userService = $userService;
        $this->orderService = $orderService;
        $this->commonRepository = $commonRepository;
        $this->commissionService = $commissionService;
        $this->baseRepository = $baseRepository;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->merchantCommonService = $merchantCommonService;
        $this->userBaitiaoService = $userBaitiaoService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderRefoundService = $orderRefoundService;
        $this->userAddressService = $userAddressService;
        $this->orderCommonService = $orderCommonService;
        $this->storeCommonService = $storeCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->goodsAttrService = $goodsAttrService;
        $this->flowUserService = $flowUserService;
        $this->packageGoodsService = $packageGoodsService;
        $this->orderTransportService = $orderTransportService;
    }

    public function index()
    {
        load_helper('order');
        load_helper('goods');
        load_helper('comment', 'seller');

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "order");

        $user_action_list = get_user_action_list(session('seller_id'));

        //商家单个权限 ecmoban模板堂 start
        $order_back_apply = get_merchants_permissions($user_action_list, 'order_back_apply');
        $this->smarty->assign('order_back_apply', $order_back_apply); //退换货权限

        $order_os_remove = get_merchants_permissions($user_action_list, 'order_os_remove');
        $this->smarty->assign('order_os_remove', $order_os_remove); //订单删除
        //商家单个权限 ecmoban模板堂 end

        //ecmoban模板堂 --zhuo start
        $admin_id = get_admin_id();
        $adminru = get_admin_ru_id();

        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        //ecmoban模板堂 --zhuo end
        $this->smarty->assign('primary_cat', lang('seller/common.04_order'));
        /*------------------------------------------------------ */
        //-- 订单查询
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'order_query') {
            /* 检查权限 */
            admin_priv('order_view');
            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);
            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => lang('seller/common.02_order_list'), 'href' => 'order.php?act=list'];
            $tab_menu[] = ['curr' => 1, 'text' => lang('seller/common.03_order_query'), 'href' => 'order.php?act=order_query'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            $this->smarty->assign('ur_here', lang('seller/common.03_order_query'));

            /* 载入配送方式 */
            $this->smarty->assign('shipping_list', shipping_list());

            /* 载入支付方式 */
            $this->smarty->assign('pay_list', payment_list());

            /* 载入国家 */
            $this->smarty->assign('country_list', get_regions());
            $this->smarty->assign('selProvinces_list', get_regions(1, 1));
            /* 载入订单状态、付款状态、发货状态 */
            $this->smarty->assign('os_list', $this->get_status_list('order'));
            $this->smarty->assign('ps_list', $this->get_status_list('payment'));
            $this->smarty->assign('ss_list', $this->get_status_list('shipping'));

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            /* 显示模板 */

            return $this->smarty->display('order_query.dwt');
        }

        /*------------------------------------------------------ */
        //-- 修改设置自动确认收货的时间（天为单位） ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_auto_delivery_time') {
            $check_auth = check_authz_json('order_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_id = intval($_POST['id']);
            $delivery_time = json_str_iconv(trim($_POST['val']));

            /* 删除数据 */
            $sql = "UPDATE " . $this->dsc->table('order_info') . " SET auto_delivery_time = '$delivery_time'" . " WHERE order_id = '$order_id'";
            $this->db->query($sql);

            clear_cache_files();
            return make_json_result($delivery_time);
        }

        /*------------------------------------------------------ */
        //-- 订单列表
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('order_view');

            cache()->forget('order_download_content_' . $admin_id);

            $this->smarty->assign('primary_cat', lang('seller/common.04_order'));
            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);
            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 1, 'text' => lang('seller/common.02_order_list'), 'href' => 'order.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => lang('seller/common.03_order_query'), 'href' => 'order.php?act=order_query'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 by wu end

            /* 模板赋值 */
            $this->smarty->assign('ur_here', lang('seller/common.02_order_list'));

            //ecmoban模板堂 --zhuo start 订单导出
            $this->smarty->assign('action_link3', ['href' => 'javascript:download_orderlist();', 'text' => lang('seller/common.11_order_export')]);
            //ecmoban模板堂 --zhuo end 订单导出

            $this->smarty->assign('status_list', lang('seller/order.cs'));   // 订单状态

            $this->smarty->assign('os_unconfirmed', OS_UNCONFIRMED);
            $this->smarty->assign('cs_await_pay', CS_AWAIT_PAY);
            $this->smarty->assign('cs_await_ship', CS_AWAIT_SHIP);
            $this->smarty->assign('full_page', 1);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $serch_type = isset($_GET['serch_type']) ? $_GET['serch_type'] : -1;
            $this->smarty->assign('serch_type', $serch_type);

            $order_list = $this->order_list();
            $page_count_arr = [];
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('sort_order_time', '<img src="__TPL__/images/sort_desc.gif">');

            /* 显示模板 */

            return $this->smarty->display('store_order.dwt');
        } /**
         * 退换货订单
         * by Leah
         */
        elseif ($_REQUEST['act'] == 'return_list') {
            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '12_back_apply']);

            /* 检查权限 */
            admin_priv('order_back_apply');
            $this->smarty->assign('current', '12_back_apply');
            $this->smarty->assign('primary_cat', lang('seller/common.04_order'));
            /* 模板赋值 */
            $this->smarty->assign('ur_here', lang('seller/common.02_order_list'));

            $this->smarty->assign('full_page', 1);
            $order_list = return_order_list();
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);


            return $this->smarty->display('return_list.dwt');
        } /**
         * 退换货分页 by Leah
         */
        elseif ($_REQUEST['act'] == 'return_list_query') {
            /* 检查权限 */
            admin_priv('order_view');
            /* 模板赋值 */
            $order_list = return_order_list();
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return make_json_result($this->smarty->fetch('return_list.dwt'), '', ['filter' => $order_list['filter'], 'page_count' => $order_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //--Excel文件下载数组处理
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_download') {
            $result = ['is_stop' => 0];
            $page = !empty($_REQUEST['page_down']) ? intval($_REQUEST['page_down']) : 0;//处理的页数
            $page_count = !empty($_REQUEST['page_count']) ? intval($_REQUEST['page_count']) : 0;//总页数

            $order_list = $this->order_list($page);//获取订单数组

            $merchants_download_content = cache("order_download_content_" . $page . "_" . $admin_id);
            $merchants_download_content = !is_null($merchants_download_content) ? $merchants_download_content : [];

            $merchants_download_content = $order_list;

            cache()->forever("order_download_content_" . $page . "_" . $admin_id, $merchants_download_content);

            $result['page'] = $page;
            $result['page_count'] = $page_count;
            if ($page < $page_count) {
                $result['is_stop'] = 1;//未结算标识
                $result['next_page'] = $page + 1;
            }
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //--导出当前分页订单csv文件
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'download_list_csv') {
            $page = !empty($_REQUEST['page_down']) ? intval($_REQUEST['page_down']) : 0;//处理的页数
            $page_count = !empty($_REQUEST['page_count']) ? intval($_REQUEST['page_count']) : 0;//总页数
            // 获取所有商家的下载数据 按照商家分组
            $order_list = cache("order_download_content_" . $page . "_" . $admin_id);
            $order_list = !is_null($order_list) ? $order_list : false;

            if (!empty($order_list)) {
                // 需要导出字段名称
                $head = [
                    ['column_name' => lang('admin/common.download.order_sn')],
                    ['column_name' => lang('admin/common.download.seller_name')],
                    ['column_name' => lang('admin/common.download.order_user')],
                    ['column_name' => lang('admin/common.download.order_time')],
                    ['column_name' => lang('admin/common.download.consignee')],
                    ['column_name' => lang('admin/common.download.tel')],
                    ['column_name' => lang('admin/common.download.address')],
                    ['column_name' => lang('admin/common.download.goods_info')],
                    ['column_name' => lang('admin/common.download.goods_sn')],
                    ['column_name' => lang('admin/common.download.goods_amount')],
                    ['column_name' => lang('admin/common.download.tax')],
                    ['column_name' => lang('admin/common.download.shipping_fee')],
                    ['column_name' => lang('admin/common.download.insure_fee')],
                    ['column_name' => lang('admin/common.download.pay_fee')],
                    ['column_name' => lang('admin/common.download.rate_fee')],
                    ['column_name' => lang('admin/common.download.total_fee')],
                    ['column_name' => lang('admin/common.download.discount')],
                    ['column_name' => lang('admin/common.download.total_fee_order')],
                    ['column_name' => lang('admin/common.download.surplus')],
                    ['column_name' => lang('admin/common.download.integral_money')],
                    ['column_name' => lang('admin/common.download.bonus')],
                    ['column_name' => lang('admin/common.download.coupons')],
                    ['column_name' => lang('admin/common.download.value_card')],
                    ['column_name' => lang('admin/common.download.money_paid')],
                    ['column_name' => lang('admin/common.download.order_amount')],
                    ['column_name' => lang('admin/common.download.order_status')],
                    ['column_name' => lang('admin/common.download.pay_status')],
                    ['column_name' => lang('admin/common.download.shipping_status')],
                    ['column_name' => lang('admin/common.download.froms')],
                    ['column_name' => lang('admin/common.download.pay_name')],
                ];
                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $head[]['column_name'] = lang('admin/common.download.rel_name');
                    $head[]['column_name'] = lang('admin/common.download.id_num');
                }

                // 需要导出字段 须和查询数据里的字段名保持一致
                $fields = [
                    'order_sn',
                    'seller_name',
                    'order_user',
                    'order_time',
                    'consignee',
                    'tel',
                    'address',
                    'goods_info',
                    'goods_sn',
                    'goods_amount',
                    'tax',
                    'shipping_fee',
                    'insure_fee',
                    'pay_fee',
                    'rate_fee',
                    'total_fee',
                    'discount',
                    'total_fee_order',
                    'surplus',
                    'integral_money',
                    'bonus',
                    'coupons',
                    'value_card',
                    'money_paid',
                    'order_amount',
                    'order_status',
                    'pay_status',
                    'shipping_status',
                    'froms',
                    'pay_name'
                ];

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $fields[] = 'rel_name';
                    $fields[] = 'id_num';
                }

                $list = app(OrderManageService::class)->downloadOrderListContent($order_list['orders']);

                // 文件名
                $title = date('YmdHis');

                $spreadsheet = new OfficeService();

                // 文件下载目录
                $dir = 'data/attached/file/';
                $file_path = storage_public($dir);
                if (!is_dir($file_path)) {
                    Storage::disk('public')->makeDirectory($dir);
                }

                $options = [
                    'savePath' => $file_path, // 指定文件下载目录
                ];

                // 默认样式
                $spreadsheet->setDefaultStyle();

                // 文件名按分页命名
                $out_title = $title . '-' . $page;

                if ($list) {
                    $spreadsheet->exportExcel($out_title, $head, $fields, $list, $options);
                }
                // 关闭
                $spreadsheet->disconnect();

            }
            /* 清除缓存 */
            cache()->forget('order_download_content_' . $page . "_" . $admin_id);

            if ($page < $page_count) {
                $result['is_stop'] = 1;//未结算标识
            } else {
                $result['is_stop'] = 0;
            }
            $result['error'] = 1;
            $result['page'] = $page;

            return response()->json($result);

        }
        /* ------------------------------------------------------ */
        //--Excel文件下载 订单下载
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'order_download') {
            // 文件下载目录
            $dir = 'data/attached/file/';
            $file_path = storage_public($dir);
            if (!is_dir($file_path)) {
                Storage::disk('public')->makeDirectory($dir);
            }

            // 压缩打包文件并下载
            $zip_path = storage_public($dir . 'zip/');
            if (!is_dir($zip_path)) {
                Storage::disk('public')->makeDirectory($dir . 'zip/');
            }

            $zip_name = lang('admin/common.order_export_alt') . date('YmdHis') . ".zip";

            $zipper = new Zipper();
            $files = glob($file_path . '*.*'); // 排除子目录

            $zipper->make($zip_path . $zip_name)->add($files)->close();
            if (file_exists($zip_path . $zip_name)) {
                // 删除文件
                $files = Storage::disk('public')->files($dir);
                Storage::disk('public')->delete($files);
                return response()->download($zip_path . $zip_name)->deleteFileAfterSend(); // 下载完成删除zip压缩包
            }
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            admin_priv('order_view');
            $order_list = $this->order_list();

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            return make_json_result($this->smarty->fetch('store_order.dwt'), '', ['filter' => $order_list['filter'], 'page_count' => $order_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 订单详情        页面
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'info') {
            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);
            $this->smarty->assign('current', '02_order_list');
            $this->smarty->assign('primary_cat', lang('seller/common.04_order'));

            /* 根据订单id或订单号查询订单信息 */
            if (isset($_REQUEST['order_id'])) {
                $order_id = intval($_REQUEST['order_id']);
                $order = order_info($order_id, '', $adminru['ru_id']);
            } elseif (isset($_REQUEST['order_sn'])) {
                $order_sn = trim($_REQUEST['order_sn']);
                $order = order_info(0, $order_sn, $adminru['ru_id']);
            } else {
                /* 如果参数不存在，退出 */
                return 'invalid parameter';
            }

            /* 如果订单不存在，退出 */
            if (empty($order)) {
                return 'order does not exist';
            }

            /* 查询更新支付状态 start */
            if (($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED) && $order['pay_status'] == PS_UNPAYED) {
                $pay_log = get_pay_log($order['order_id'], 1);

                if ($pay_log && $pay_log['is_paid'] == 0) {
                    $payment = payment_info($order['pay_id']);

                    if ($payment && strpos($payment['pay_code'], 'pay_') === false) {
                        /* 取得在线支付方式的支付按钮 */
                        $payObj = $this->commonRepository->paymentInstance($payment['pay_code']);

                        if (!is_null($payObj)) {
                            /* 判断类对象方法是否存在 */
                            if (is_callable([$payObj, 'orderQuery'])) {
                                $order_other = [
                                    'order_sn' => $order['order_sn'],
                                    'log_id' => $pay_log['log_id'],
                                    'order_amount' => $order['order_amount'],
                                ];

                                $payObj->orderQuery($order_other);

                                $sql = "SELECT order_status, shipping_status, pay_status, pay_time FROM " . $this->dsc->table('order_info') . " WHERE order_id = '" . $order['order_id'] . "' LIMIT 1";
                                $order_info = $this->db->getRow($sql);
                                if ($order_info) {
                                    $order['order_status'] = $order_info['order_status'];
                                    $order['shipping_status'] = $order_info['shipping_status'];
                                    $order['pay_status'] = $order_info['pay_status'];
                                    $order['pay_time'] = $order_info['pay_time'];
                                }
                            }
                        }
                    }
                }
            }
            /* 查询更新支付状态 end */

            if ($order['ru_id'] != $adminru['ru_id']) {
                $Loaction = "order.php?act=list";
                return dsc_header("Location: $Loaction\n");
            }
            //获取支付方式code
            $sql = "SELECT pay_code FROM " . $this->dsc->table('payment') . " WHERE pay_id = '" . $order['pay_id'] . "'";
            $pay_code = $this->db->getOne($sql, true);

            if ($pay_code == "cod" || $pay_code == "bank") {
                $this->smarty->assign('pay_code', 1);
            } else {
                $this->smarty->assign('pay_code', 0);
            }

            /*判断订单状态 by kong*/
            if ($order['order_status'] == OS_INVALID || $order['order_status'] == OS_CANCELED) {
                $order['can_remove'] = 1;
            } else {
                $order['can_remove'] = 0;
            }

            $order['delivery_id'] = $this->db->getOne("SELECT delivery_id FROM " . $this->dsc->table('delivery_order') . " WHERE order_sn = '" . $order['order_sn'] . "'", true);

            /* 处理确认收货时间 start */
            if ($GLOBALS['_CFG']['open_delivery_time'] == 1) {

                /* 查询订单信息，检查状态 */
                $res = OrderInfo::where('order_id', $order['order_id']);

                $res = $res->with([
                    'getSellerNegativeOrder'
                ]);

                $orderInfo = $this->baseRepository->getToArrayFirst($res);

                if (($orderInfo['order_status'] == OS_CONFIRMED || $orderInfo['order_status'] == OS_SPLITED) && $orderInfo['shipping_status'] == SS_SHIPPED && $orderInfo['pay_status'] == PS_PAYED) { //发货状态
                    $delivery_time = $orderInfo['shipping_time'] + 24 * 3600 * $orderInfo['auto_delivery_time'];

                    $confirm_take_time = gmtime();

                    if ($confirm_take_time > $delivery_time) { //自动确认发货操作

                        $sql = "UPDATE " . $this->dsc->table('order_info') . " SET order_status = '" . $orderInfo['order_status'] . "', " .
                            "shipping_status = '" . SS_RECEIVED . "', pay_status = '" . $orderInfo['pay_status'] . "', confirm_take_time = '$confirm_take_time' WHERE order_id = '" . $order['order_id'] . "'";
                        $this->db->query($sql);

                        /* 更新会员订单信息 */
                        $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

                        /* 记录日志 */
                        $note = $GLOBALS['_LANG']['self_motion_goods'];
                        order_action($orderInfo['order_sn'], $orderInfo['order_status'], SS_RECEIVED, $orderInfo['pay_status'], $note, $GLOBALS['_LANG']['system_handle'], 0, $confirm_take_time);

                        $seller_id = $order['ru_id'];
                        $value_card = $this->db->getOne("SELECT use_val FROM " . $this->dsc->table('value_card_record') . " WHERE order_id = '" . $order['order_id'] . "'", true);

                        if (empty($orderInfo['get_seller_negative_order'])) {
                            $return_amount_info = $this->orderRefoundService->orderReturnAmount($order['order_id']);
                        } else {
                            $return_amount_info['return_amount'] = 0;
                            $return_amount_info['return_rate_fee'] = 0;
                            $return_amount_info['ret_id'] = [];
                        }

                        if ($orderInfo['order_amount'] > 0 && $orderInfo['order_amount'] > $orderInfo['rate_fee']) {
                            $order_amount = $orderInfo['order_amount'] - $orderInfo['rate_fee'];
                        } else {
                            $order_amount = $orderInfo['order_amount'];
                        }

                        $other = array(
                            'user_id' => $orderInfo['user_id'],
                            'seller_id' => $seller_id,
                            'order_id' => $orderInfo['order_id'],
                            'order_sn' => $orderInfo['order_sn'],
                            'order_status' => $orderInfo['order_status'],
                            'shipping_status' => SS_RECEIVED,
                            'pay_status' => $orderInfo['pay_status'],
                            'order_amount' => $order_amount,
                            'return_amount' => $return_amount_info['return_amount'],
                            'goods_amount' => $orderInfo['goods_amount'],
                            'tax' => $orderInfo['tax'],
                            'tax_id' => $orderInfo['tax_id'],
                            'invoice_type' => $orderInfo['invoice_type'],
                            'shipping_fee' => $orderInfo['shipping_fee'],
                            'insure_fee' => $orderInfo['insure_fee'],
                            'pay_fee' => $orderInfo['pay_fee'],
                            'pack_fee' => $orderInfo['pack_fee'],
                            'card_fee' => $orderInfo['card_fee'],
                            'bonus' => $orderInfo['bonus'],
                            'integral_money' => $orderInfo['integral_money'],
                            'coupons' => $orderInfo['coupons'],
                            'discount' => $orderInfo['discount'],
                            'value_card' => $value_card,
                            'money_paid' => $orderInfo['money_paid'],
                            'surplus' => $orderInfo['surplus'],
                            'confirm_take_time' => $confirm_take_time,
                            'rate_fee' => $orderInfo['rate_fee'],
                            'return_rate_fee' => $return_amount_info['return_rate_price']
                        );

                        if ($seller_id) {
                            $this->commissionService->getOrderBillLog($other);
                            $this->commissionService->setBillOrderReturn($return_amount_info['ret_id'], $other['order_id']);
                        }
                    }
                } else {
                    if (empty($order['confirm_take_time'])) {
                        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('seller_bill_order') . " WHERE order_id = '" . $order['order_id'] . "'";
                        $bill_order_count = $this->db->getOne($sql, true);

                        if ($bill_order_count > 0 && $order['shipping_status'] == SS_RECEIVED) {
                            $sql = "SELECT MAX(log_time) AS log_time FROM " . $this->dsc->table('order_action') . " WHERE order_id = '" . $order['order_id'] . "' AND shipping_status = '" . SS_RECEIVED . "'";
                            $confirm_take_time = $this->db->getOne($sql, true);

                            if (empty($confirm_take_time)) {
                                $confirm_take_time = gmtime();

                                $note = $GLOBALS['_LANG']['admin_order_list_motion'];
                                order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], $note, $GLOBALS['_LANG']['system_handle'], 0, $confirm_take_time);

                                $order['confirm_take_time'] = $confirm_take_time;
                            }

                            $log_other = array(
                                'confirm_take_time' => $confirm_take_time
                            );

                            $this->db->autoExecute($this->dsc->table('order_info'), $log_other, 'UPDATE', "order_id = '" . $order['order_id'] . "'");
                            $this->db->autoExecute($this->dsc->table('seller_bill_order'), $log_other, 'UPDATE', "order_id = '" . $order['order_id'] . "'");
                        }
                    }
                }
            }
            /* 处理确认收货时间 end */

            /* 对发货号处理 */
            if (!empty($order['invoice_no'])) {
                $shipping_code = Shipping::where('shipping_id', $order['shipping_id'])->value('shipping_code');

                $shippingObject = $this->commonRepository->shippingInstance($shipping_code);
                if (!is_null($shippingObject)) {
                    $order['shipping_code_name'] = $shippingObject->get_code_name();
                }
            }
            /* 根据订单是否完成检查权限 */
            if (order_finished($order)) {
                admin_priv('order_view_finished');
            } else {
                admin_priv('order_view');
            }

            /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
            $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
            $agency_id = $this->db->getOne($sql);
            if ($agency_id > 0) {
                if ($order['agency_id'] != $agency_id) {
                    return sys_msg(lang('seller/common.priv_error'));
                }
            }

            $ecscpCookie = request()->cookie('ECSCP');

            /* 取得上一个、下一个订单号 */
            if (isset($ecscpCookie['lastfilter']) && !empty($ecscpCookie['lastfilter'])) {
                $filter = unserialize(urldecode($ecscpCookie['lastfilter']));
                if (!empty($filter['composite_status'])) {
                    $where = '';
                    //综合状态
                    switch ($filter['composite_status']) {
                        case CS_AWAIT_PAY:
                            $where .= $this->orderService->orderQuerySql('await_pay');
                            break;

                        case CS_AWAIT_SHIP:
                            $where .= $this->orderService->orderQuerySql('await_ship');
                            break;

                        case CS_FINISHED:
                            $where .= $this->orderService->orderQuerySql('finished');
                            break;

                        default:
                            if ($filter['composite_status'] != -1) {
                                $where .= " AND o.order_status = '$filter[composite_status]' ";
                            }
                    }
                }
            }
            $sql = "SELECT MAX(order_id) FROM " . $this->dsc->table('order_info') . " as o WHERE order_id < '$order[order_id]'";
            if ($agency_id > 0) {
                $sql .= " AND agency_id = '$agency_id'";
            }
            if (!empty($where)) {
                $sql .= $where;
            }
            $this->smarty->assign('prev_id', $this->db->getOne($sql));
            $sql = "SELECT MIN(order_id) FROM " . $this->dsc->table('order_info') . " as o WHERE order_id > '$order[order_id]'";
            if ($agency_id > 0) {
                $sql .= " AND agency_id = '$agency_id'";
            }
            if (!empty($where)) {
                $sql .= $where;
            }
            $this->smarty->assign('next_id', $this->db->getOne($sql));

            /* 取得用户名 */
            if ($order['user_id'] > 0) {
                $where = [
                    'user_id' => $order['user_id']
                ];
                $user_info = $this->userService->userInfo($where);

                if (!empty($user_info)) {
                    $order['user_name'] = $user_info['user_name'];

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $order['user_name'] = $this->dscRepository->stringToStar($order['user_name']);
                    }
                }
            }

            /* 取得所有办事处 */
            $sql = "SELECT agency_id, agency_name FROM " . $this->dsc->table('agency');
            $this->smarty->assign('agency_list', $this->db->getAll($sql));

            /* 格式化金额 */
            if ($order['order_amount'] < 0) {
                $order['money_refund'] = abs($order['order_amount']);
                $order['formated_money_refund'] = price_format(abs($order['order_amount']));
            }

            /* 其他处理 */
            $order['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
            $order['pay_time'] = $order['pay_time'] > 0 ?
                local_date($GLOBALS['_CFG']['time_format'], $order['pay_time']) : $GLOBALS['_LANG']['ps'][PS_UNPAYED];
            $order['shipping_time'] = $order['shipping_time'] > 0 ?
                local_date($GLOBALS['_CFG']['time_format'], $order['shipping_time']) : $GLOBALS['_LANG']['ss'][SS_UNSHIPPED];
            $order['confirm_take_time'] = $order['confirm_take_time'] > 0 ?
                local_date($GLOBALS['_CFG']['time_format'], $order['confirm_take_time']) : ($order['shipping_status'] == 1 ? $GLOBALS['_LANG']['not_confirm_order'] : $GLOBALS['_LANG']['ss'][SS_UNSHIPPED]);
            $order['status'] = $GLOBALS['_LANG']['os'][$order['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$order['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$order['shipping_status']];
            $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $GLOBALS['_LANG']['ss'][SS_UNSHIPPED] : $order['invoice_no'];

            /* 取得订单的来源 */
            if ($order['from_ad'] == 0) {
                $order['referer'] = empty($order['referer']) ? $GLOBALS['_LANG']['from_self_site'] : $order['referer'];
            } elseif ($order['from_ad'] == -1) {
                $order['referer'] = $GLOBALS['_LANG']['from_goods_js'] . ' (' . $GLOBALS['_LANG']['from'] . $order['referer'] . ')';
            } else {
                /* 查询广告的名称 */
                $ad_name = $this->db->getOne("SELECT ad_name FROM " . $this->dsc->table('ad') . " WHERE ad_id='$order[from_ad]'");
                $order['referer'] = $GLOBALS['_LANG']['from_ad_js'] . $ad_name . ' (' . $GLOBALS['_LANG']['from'] . $order['referer'] . ')';
            }

            /* 此订单的发货备注(此订单的最后一条操作记录) */
            $sql = "SELECT action_note FROM " . $this->dsc->table('order_action') .
                " WHERE order_id = '$order[order_id]' AND shipping_status = 1 ORDER BY log_time DESC";
            $order['invoice_note'] = $this->db->getOne($sql);

            /* 自提点信息 */
            $sql = "SELECT shipping_code FROM " . $this->dsc->table('shipping') . " WHERE shipping_id = '$order[shipping_id]'";
            if ($this->db->getOne($sql) == 'cac') {
                $sql = "SELECT * FROM " . $this->dsc->table('shipping_point') . " WHERE id IN (SELECT point_id FROM " . $this->dsc->table('order_info') . " WHERE order_id='" . $order['order_id'] . "')";
                $order['point'] = $this->db->getRow($sql);
            }

            /* 判断当前订单是否是白条分期付订单 bylu */
            $sql = "SELECT stages_total,stages_one_price,is_stages FROM " . $this->dsc->table('baitiao_log') . " WHERE order_id = '$order_id'";
            $baitiao_info = $this->db->getRow($sql);
            if ($baitiao_info['is_stages'] == 1) {
                $order['is_stages'] = 1;
                $order['stages_total'] = $baitiao_info['stages_total'];
                $order['stages_one_price'] = $baitiao_info['stages_one_price'];
            }

            /*增值发票 start*/
            if ($order['invoice_type'] == 1) {
                $user_id = $order['user_id'];
                $sql = " SELECT * FROM " . $this->dsc->table('users_vat_invoices_info') . " WHERE user_id = '$user_id' LIMIT 1";
                $res = $this->db->getRow($sql);
                $region = ['province' => $res['province'], 'city' => $res['city'], 'district' => $res['district']];
                $res['region'] = get_area_region_info($region);
                $this->smarty->assign('vat_info', $res);
            }
            /*增值发票 end*/

            /* 取得订单商品总重量 */
            $weight_price = order_weight_price($order['order_id']);
            $order['total_weight'] = $weight_price['formated_weight'];

            /*判断是否评论 by kong*/
            $order['is_comment'] = 0;
            $sql = " SELECT comment_id , add_time FROM" . $this->dsc->table('comment') . " WHERE order_id = '" . $order['order_id'] . "' AND user_id = '" . $order['user_id'] . "'";
            $comment = $this->db->getRow($sql);
            if ($comment) {
                $order['is_comment'] = 1;
                $order['comment_time'] = $comment['add_time'] > 0 ?
                    local_date($GLOBALS['_CFG']['time_format'], $order['add_time']) : lang('seller/order.no_comment');
            }
            /* 参数赋值：订单 */
            $this->smarty->assign('order', $order);

            /* 取得用户信息 */
            if ($order['user_id'] > 0) {
                /* 用户等级 */
                if ($user_info['user_rank'] > 0) {
                    $where = " WHERE rank_id = '$user_info[user_rank]' ";
                } else {
                    $where = " WHERE min_points <= " . intval($user_info['rank_points']) . " ORDER BY min_points DESC ";
                }
                $sql = "SELECT rank_name FROM " . $this->dsc->table('user_rank') . $where;
                $user_info['rank_name'] = $this->db->getOne($sql);

                // 用户红包数量
                $day = getdate();
                $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
                $sql = "SELECT COUNT(*) " .
                    "FROM " . $this->dsc->table('bonus_type') . " AS bt, " . $this->dsc->table('user_bonus') . " AS ub " .
                    "WHERE bt.type_id = ub.bonus_type_id " .
                    "AND ub.user_id = '$order[user_id]' " .
                    "AND ub.order_id = 0 " .
                    "AND bt.use_start_date <= '$today' " .
                    "AND bt.use_end_date >= '$today'";
                $user_info['bonus_count'] = $this->db->getOne($sql);
                $this->smarty->assign('user', $user_info);

                // 地址信息
                $sql = "SELECT * FROM " . $this->dsc->table('user_address') . " WHERE user_id = '$order[user_id]' AND address_id = '" . $user_info['address_id'] . "'";
                $address_list = $this->db->getAll($sql);

                if ($address_list) {
                    foreach ($address_list as $key => $row) {
                        if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                            $address_list[$key]['mobile'] = app(DscRepository::class)->stringToStar($row['mobile']);
                        }
                    }
                }

                $this->smarty->assign('address_list', $address_list);
            }

            /* 获取订单门店信息 */
            $sql = "SELECT store_id,pick_code,take_time  FROM" . $this->dsc->table("store_order") . " WHERE order_id = '" . $order['order_id'] . "'";
            $stores = $this->db->getRow($sql);
            $order['store_id'] = $stores['store_id'];

            $offline_store = [];
            if ($order['store_id'] > 0) {
                $sql = "SELECT o.*,p.region_name as province,c.region_name as city,d.region_name as district FROM" . $this->dsc->table('offline_store') . " AS o "
                    . "LEFT JOIN " . $this->dsc->table('region') . " AS p ON p.region_id = o.province "
                    . "LEFT JOIN " . $this->dsc->table('region') . " AS c ON c.region_id = o.city "
                    . "LEFT JOIN " . $this->dsc->table('region') . " AS d ON d.region_id = o.district WHERE o.id = '" . $order['store_id'] . "'";
                $offline_store = $this->db->getRow($sql);
                $offline_store['pick_code'] = $stores['pick_code'];
                $offline_store['take_time'] = $stores['take_time'];
            }
            $this->smarty->assign('offline_store', $offline_store);

            /* 取得订单商品及货品 */
            $goods_list = [];
            $goods_attr = [];
            $sql = "SELECT o.*, c.measure_unit, g.goods_number AS storage, g.model_inventory, g.model_attr as model_attr, o.goods_attr, g.suppliers_id, p.product_sn,g.goods_thumb,
            g.user_id AS ru_id, g.brand_id, g.give_integral, g.bar_code, IF(oi.extension_code != '', oi.extension_code, o.extension_code), oi.extension_id , o.extension_code as o_extension_code, oi.extension_code as oi_extension_code
            FROM " . $this->dsc->table('order_goods') . " AS o
                LEFT JOIN " . $this->dsc->table('products') . " AS p
                    ON p.product_id = o.product_id
                LEFT JOIN " . $this->dsc->table('goods') . " AS g
                    ON o.goods_id = g.goods_id
                LEFT JOIN " . $this->dsc->table('category') . " AS c
                    ON g.cat_id = c.cat_id
				LEFT JOIN " . $this->dsc->table('order_info') . " AS oi
					ON o.order_id = oi.order_id
            WHERE o.order_id = '$order[order_id]'";
            $res = $this->db->query($sql);

            foreach ($res as $row) {
                if ($row['model_inventory'] == 1) {
                    $row['storage'] = get_warehouse_area_goods($row['warehouse_id'], $row['goods_id'], 'warehouse_goods');
                } elseif ($row['model_inventory'] == 2) {
                    $row['storage'] = get_warehouse_area_goods($row['area_id'], $row['goods_id'], 'warehouse_area_goods');
                }

                $row['give_integral'] = $row['give_integral'];

                //ecmoban模板堂 --zhuo start 商品金额促销
                $row['goods_amount'] = $row['goods_price'] * $row['goods_number'];
                $goods_con = $this->cartCommonService->getConGoodsAmount($row['goods_amount'], $row['goods_id'], 0, 0, $row['parent_id']);

                $goods_con['amount'] = explode(',', $goods_con['amount']);
                $row['amount'] = min($goods_con['amount']);

                $row['dis_amount'] = $row['goods_amount'] - $row['amount'];
                $row['discount_amount'] = price_format($row['dis_amount'], false);
                //ecmoban模板堂 --zhuo end 商品金额促销

                //ecmoban模板堂 --zhuo start //库存查询
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $row['model_attr']);
                $row['goods_storage'] = $products ? $products['product_number'] : 0;

                if ($row['product_id']) {
                    $row['bar_code'] = $products['bar_code'] ?? '';
                }

                if ($row['model_attr'] == 1) {
                    $table_products = "products_warehouse";
                    $type_files = " and warehouse_id = '" . $row['warehouse_id'] . "'";
                } elseif ($row['model_attr'] == 2) {
                    $table_products = "products_area";
                    $type_files = " and area_id = '" . $row['area_id'] . "'";
                } else {
                    $table_products = "products";
                    $type_files = "";
                }

                $sql = "SELECT * FROM " . $this->dsc->table($table_products) . " WHERE goods_id = '" . $row['goods_id'] . "'" . $type_files . " LIMIT 0, 1";
                $prod = $this->db->getRow($sql);

                if (empty($prod)) { //当商品没有属性库存时
                    $row['goods_storage'] = $row['storage'];
                }

                $row['goods_storage'] = !empty($row['goods_storage']) ? $row['goods_storage'] : 0;
                $row['storage'] = $row['goods_storage'];
                $row['product_sn'] = $products ? $products['product_sn'] : '';
                //ecmoban模板堂 --zhuo end //库存查询

                $brand = get_goods_brand_info($row['brand_id']);
                $row['brand_name'] = $brand['brand_name'];

                $row['formated_subtotal'] = price_format($row['amount']);
                $row['formated_goods_price'] = price_format($row['goods_price']);

                $row['warehouse_name'] = $this->db->getOne("select region_name from " . $this->dsc->table('region_warehouse') . " where region_id = '" . $row['warehouse_id'] . "'");

                $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

                if ($row['extension_code'] == 'package_buy') {
                    $row['storage'] = '';
                    $row['brand_name'] = '';
                    $row['package_goods_list'] = $this->packageGoodsService->getPackageGoods($row['goods_id']);
                }

                ///仅退款申通通过则限制生成发货单 start
                $sql = "SELECT ret_id, agree_apply, return_status, refound_status FROM " . $this->dsc->table('order_return') . " WHERE rec_id = '$row[rec_id]' AND return_type = 3 "; //仅退款
                $rec_info = $this->db->getRow($sql);

                if ($rec_info) {
                    $row = array_merge($row, $rec_info);
                }
                //仅退款申通通过则限制生成发货单 end

                //判断是否是贡云订单
                $sql = "SELECT COUNT(*) FROM" . $this->dsc->table('order_cloud') . "WHERE rec_id = '" . $row['rec_id'] . "'";
                $cloud_count = $this->db->getOne($sql);
                $row['is_cloud_order'] = 0;
                if ($cloud_count > 0) {
                    $row['is_cloud_order'] = 1;
                    $is_cloud_order = 1;
                }

                //图片显示
                $row['goods_thumb'] = get_image_path($row['goods_thumb']);

//                $goods_where = " AND d.delivery_id = any(SELECT dg.delivery_id FROM " . $this->dsc->table('delivery_goods') . " AS dg WHERE dg.goods_id = '" . $row['goods_id'] . "' AND goods_attr = '" . $row['goods_attr'] . "')";
//                $sql = "SELECT d.delivery_id, d.status FROM" . $this->dsc->table('delivery_order') . " AS d WHERE d.order_sn = '" . $order['order_sn'] . "' $goods_where LIMIT 1";
//                $row['delivery'] = $this->db->getRow($sql);
                $goods_id = $row['goods_id'];
                $goods_attr_where = $row['goods_attr'];
                $d_res = DeliveryOrder::where('order_sn', $order['order_sn']);
                $d_res = $d_res->whereHas('getDeliveryGoods', function ($query) use ($goods_id, $goods_attr_where) {
                    $query->where('goods_id', $goods_id)->where('goods_attr', $goods_attr_where);
                });
                //多条发货单
                $row['delivery'] = $this->baseRepository->getToArrayGet($d_res);

                $row['formated_goods_bonus'] = sprintf($GLOBALS['_LANG']['average_bonus'], $this->dscRepository->getPriceFormat($row['goods_bonus']));

                $goods_list[] = $row;
            }

            $attr = [];
            $arr = [];
            if ($goods_attr) {
                foreach ($goods_attr as $index => $array_val) {
                    $array_val = $this->baseRepository->getExplode($array_val);
                    if ($array_val) {
                        foreach ($array_val as $value) {
                            $arr = explode(':', $value);//以 : 号将属性拆开
                            $attr[$index][] = @['name' => $arr[0], 'value' => $arr[1]];
                        }
                    }
                }
            }

            $this->smarty->assign('goods_attr', $attr);
            $this->smarty->assign('goods_list', $goods_list);

            /* 取得能执行的操作列表 */
            $operable_list = $this->operable_list($order);
            $this->smarty->assign('operable_list', $operable_list);

            /* 判断退换货订单申请是否通过 strat */
            $sql = "SELECT agree_apply FROM " . $this->dsc->table('order_return') . " WHERE order_id = '$order[order_id]'";
            $is_apply = $this->db->getOne($sql);
            $this->smarty->assign('is_apply', $is_apply);
            /* 判断退换货订单申请是否通过 end */

            /**
             * 取得用户收货时间 以快物流信息显示为准，目前先用用户收货时间为准，后期修改TODO by Leah S
             */
            $sql = "SELECT log_time  FROM " . $this->dsc->table('order_action') . " WHERE order_id = '$order[order_id]' ";
            $res_time = local_date($GLOBALS['_CFG']['time_format'], $this->db->getOne($sql));
            $this->smarty->assign('res_time', $res_time);
            /**
             * by Leah E
             */

            /* 取得订单操作记录 */
            $act_list = [];
            $sql = "SELECT * FROM " . $this->dsc->table('order_action') . " WHERE order_id = '$order[order_id]' ORDER BY log_time DESC,action_id DESC";
            $res = $this->db->query($sql);
            foreach ($res as $row) {
                $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                $row['pay_status'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
                $row['shipping_status'] = $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                $row['action_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['log_time']);
                $act_list[] = $row;
            }
            $this->smarty->assign('action_list', $act_list);

            /* 取得是否存在实体商品 */
            $this->smarty->assign('exist_real_goods', $this->flowUserService->existRealGoods($order['order_id']));

            /* 返回门店列表 */
            if ($order['pay_status'] == 2 && $order['shipping_status'] == 0) {
                $sql = " SELECT COUNT(*) FROM " . $this->dsc->table('store_order') . " WHERE order_id = '$order[order_id]' AND store_id > 0 ";
                $have_store_order = $this->db->getOne($sql);
                if ($have_store_order == 0) {
                    $this->smarty->assign('can_set_grab_order', 1);
                }
            }

            //商家店铺信息打印到订单和快递单上
            $sql = "select shop_name,country,province,city,district,shop_address,kf_tel from " . $this->dsc->table('seller_shopinfo') . " where ru_id='" . $order['ru_id'] . "'";
            $store = $this->db->getRow($sql);

            $store['shop_name'] = $this->merchantCommonService->getShopName($order['ru_id'], 1);

            /* 是否打印订单，分别赋值 */
            if (isset($_GET['print'])) {
                $this->smarty->assign('shop_name', $store['shop_name']);
                $this->smarty->assign('shop_url', $this->dsc->seller_url());
                $this->smarty->assign('shop_address', $store['shop_address']);
                $this->smarty->assign('service_phone', $store['kf_tel']);
                $this->smarty->assign('print_time', local_date($GLOBALS['_CFG']['time_format']));
                $this->smarty->assign('action_user', session('seller_name'));

                $this->smarty->template_dir = storage_public(DATA_DIR);
                return $this->smarty->display('order_print.html');
            } /* 打印快递单 */
            elseif (isset($_GET['shipping_print'])) {
                //快递鸟、电子面单 start
                if (get_print_type($adminru['ru_id'])) {
                    $url = 'tp_api.php?act=kdniao_print&order_id=' . $order_id;
                    return dsc_header("Location: $url\n");
                }
                //快递鸟、电子面单 end

                //发货地址所在地
                $region_array = [];
                $region = $this->db->getAll("SELECT region_id, region_name FROM " . $this->dsc->table("region")); //打印快递单地区 by wu
                if (!empty($region)) {
                    foreach ($region as $region_data) {
                        $region_array[$region_data['region_id']] = $region_data['region_name'];
                    }
                }

                $province = $region_array[$store['province']] ?? 0;
                $city = $region_array[$store['city']] ?? 0;
                $this->smarty->assign('shop_name', $store['shop_name']);
                $this->smarty->assign('order_id', $order_id);
                $this->smarty->assign('province', $province);
                $this->smarty->assign('city', $city);
                $this->smarty->assign('shop_address', $store['shop_address']);
                $this->smarty->assign('service_phone', $store['kf_tel']);
                $shipping = $this->db->getRow("SELECT * FROM " . $this->dsc->table("shipping_tpl") . " WHERE shipping_id = '" . $order['shipping_id'] . "' and ru_id='" . $adminru['ru_id'] . "'");

                //打印单模式
                if ($shipping['print_model'] == 2) {
                    /* 可视化 */
                    /* 快递单 */
                    $shipping['print_bg'] = empty($shipping['print_bg']) ? '' : get_image_path($shipping['print_bg']);

                    /* 取快递单背景宽高 */
                    if (!empty($shipping['print_bg'])) {
                        $_size = @getimagesize($shipping['print_bg']);

                        if ($_size != false) {
                            $shipping['print_bg_size'] = ['width' => $_size[0], 'height' => $_size[1]];
                        }
                    }

                    if (empty($shipping['print_bg_size'])) {
                        $shipping['print_bg_size'] = ['width' => '1024', 'height' => '600'];
                    }

                    /* 标签信息 */
                    $lable_box = [];
                    $lable_box['t_shop_country'] = $region_array[$store['country']]; //网店-国家
                    $lable_box['t_shop_city'] = $region_array[$store['city']]; //网店-城市
                    $lable_box['t_shop_province'] = $region_array[$store['province']]; //网店-省份
                    $lable_box['t_shop_name'] = $this->merchantCommonService->getShopName($order['ru_id'], 1);
                    $lable_box['t_shop_district'] = ''; //网店-区/县
                    $lable_box['t_shop_tel'] = $store['kf_tel']; //网店-联系电话
                    $lable_box['t_shop_address'] = $store['shop_address']; //网店-地址
                    $lable_box['t_customer_country'] = $region_array[$order['country']]; //收件人-国家
                    $lable_box['t_customer_province'] = $region_array[$order['province']]; //收件人-省份
                    $lable_box['t_customer_city'] = $region_array[$order['city']]; //收件人-城市
                    $lable_box['t_customer_district'] = $region_array[$order['district']]; //收件人-区/县
                    $lable_box['t_customer_tel'] = $order['tel']; //收件人-电话
                    $lable_box['t_customer_mobel'] = $order['mobile']; //收件人-手机
                    $lable_box['t_customer_post'] = $order['zipcode']; //收件人-邮编
                    $lable_box['t_customer_address'] = $order['address']; //收件人-详细地址
                    $lable_box['t_customer_name'] = $order['consignee']; //收件人-姓名
                    $gmtime_utc_temp = gmtime(); //获取 UTC 时间戳
                    $lable_box['t_year'] = local_date('Y', $gmtime_utc_temp); //年-当日日期
                    $lable_box['t_months'] = local_date('m', $gmtime_utc_temp); //月-当日日期
                    $lable_box['t_day'] = local_date('d', $gmtime_utc_temp); //日-当日日期

                    $lable_box['t_order_no'] = $order['order_sn']; //订单号-订单
                    $lable_box['t_order_postscript'] = $order['postscript']; //备注-订单
                    $lable_box['t_order_best_time'] = $order['best_time']; //送货时间-订单
                    $lable_box['t_pigeon'] = '√'; //√-对号
                    $lable_box['t_custom_content'] = ''; //自定义内容

                    //标签替换
                    $temp_config_lable = explode('||,||', $shipping['config_lable']);
                    if (!is_array($temp_config_lable)) {
                        $temp_config_lable[] = $shipping['config_lable'];
                    }
                    foreach ($temp_config_lable as $temp_key => $temp_lable) {
                        $temp_info = explode(',', $temp_lable);
                        if (is_array($temp_info)) {
                            $temp_info[1] = $lable_box[$temp_info[0]];
                        }
                        $temp_config_lable[$temp_key] = implode(',', $temp_info);
                    }
                    $shipping['config_lable'] = implode('||,||', $temp_config_lable);

                    $this->smarty->assign('shipping', $shipping);

                    return $this->smarty->display('print.dwt');
                } elseif (!empty($shipping['shipping_print'])) {
                    /* 代码 */
                    echo $this->smarty->fetch("str:" . $shipping['shipping_print']);
                } else {
                    $shipping_code = $this->db->getOne("SELECT shipping_code FROM " . $this->dsc->table('shipping') . " WHERE shipping_id='" . $order['shipping_id'] . "'");

                    /* 处理app */
                    if ($order['referer'] == 'mobile') {
                        $shipping_code = str_replace('ship_', '', $shipping_code);
                    }

                    if ($shipping_code) {
                        include_once(plugin_path('Shipping/' . Str::studly($shipping_code) . '/' . Str::studly($shipping_code) . '.php'));
                    }

                    if (!empty($GLOBALS['_LANG']['shipping_print'])) {
                        echo $this->smarty->fetch("str:" . $GLOBALS['_LANG']['shipping_print']);
                    } else {
                        echo $GLOBALS['_LANG']['no_print_shipping'];
                    }
                }
            } else {
                /* 模板赋值 */
                $this->smarty->assign('ur_here', lang('seller/order.order_info'));
                $this->smarty->assign('action_link', ['href' => 'order.php?act=list&' . list_link_postfix(), 'text' => lang('seller/common.02_order_list')]);

                //查询订单是否有商品退款
                $sql = "SELECT count(*) FROM " . $this->dsc->table('order_return') . " WHERE order_id = '$order_id'";
                $this->db->getOne($sql);

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $seller = app(CrossBorderService::class)->sellerExists();

                    if (!empty($seller)) {
                        $seller->smartyAssign();
                    }
                }

                /* 显示模板 */

                return $this->smarty->display('store_order_info.dwt');
            }
        }

        /* ------------------------------------------------------ */
        //-- 退货单详情
        /* ------------------------------------------------------ */
        /**
         * by Leah
         */
        elseif ($_REQUEST['act'] == 'return_info') {
            /* 检查权限 */
            admin_priv('order_back_apply');

            $ret_id = intval(trim($_REQUEST['ret_id']));
            $rec_id = intval(trim($_REQUEST['rec_id']));
            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '12_back_apply']);

            /* 根据发货单id查询发货单信息 */
            if (!empty($ret_id) || !empty($rec_id)) {
                $back_order = return_order_info($ret_id);
            } else {
                return 'order does not exist';
            }

            /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
            $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
            $agency_id = $this->db->getOne($sql);
            if ($agency_id > 0) {
                if ($back_order['agency_id'] != $agency_id) {
                    return sys_msg(lang('seller/common.priv_error'));
                }

                /* 取当前办事处信息 */
                $sql = "SELECT agency_name FROM " . $this->dsc->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";
                $agency_name = $this->db->getOne($sql);
                $back_order['agency_name'] = $agency_name;
            }

            /* 取得用户名 */
            if ($back_order['user_id'] > 0) {
                $where = [
                    'user_id' => $back_order['user_id']
                ];
                $user_info = $this->userService->userInfo($where);

                if (!empty($user_info)) {
                    $back_order['user_name'] = $user_info['user_name'];

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $back_order['user_name'] = $this->dscRepository->stringToStar($back_order['user_name']);
                    }
                }
            }

            /* 取得区域名 */
            $back_order['region'] = $back_order['address_detail'];

            /* 是否保价 */
            $back_order['insure_yn'] = empty($back_order['insure_fee']) ? 0 : 1;/* 取得发货单商品 */;
            $goods_list = get_return_order_goods($rec_id);
            /**
             * 取的退换货订单商品
             */
            $return_list = get_return_goods($ret_id);

            $where = [
                'order_id' => $back_order['order_id']
            ];
            $shippinOrderInfo = $this->orderService->getOrderGoodsInfo($where);

            //快递公司
            /* 取得可用的配送方式列表 */
            $region_id_list = [
                $back_order['country'], $back_order['province'], $back_order['city'], $back_order['district']
            ];

            $shipping_list = available_shipping_list($region_id_list, $shippinOrderInfo);

            /* 取得配送费用 */
            $total = order_weight_price($back_order['order_id']);
            foreach ($shipping_list as $key => $shipping) {
                $shipping_fee = $this->dscRepository->shippingFee($shipping['shipping_code'], unserialize($shipping['configure']), $total['weight'], $total['amount'], $total['number']); //计算运费
                $free_price = free_price($shipping['configure']);   //免费额度
                $shipping_list[$key]['shipping_fee'] = $shipping_fee;
                $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee);
                $shipping_list[$key]['free_money'] = price_format($free_price['configure']['free_money']);
            }
            $this->smarty->assign('shipping_list', $shipping_list);

            /* 取得退货订单操作记录 */
            $action_list = get_return_action($ret_id);
            $this->smarty->assign('action_list', $action_list);

            /* 模板赋值 */
            $this->smarty->assign('back_order', $back_order);

            if ($back_order['is_zc_order']) {
                $this->smarty->assign('exist_real_goods', true);
            } else {

                /* 查询：是否存在实体商品 */
                $exist_real_goods = $this->flowUserService->existRealGoods($back_order['order_id']);
                $this->smarty->assign('exist_real_goods', $exist_real_goods);
            }

            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('return_list', $return_list);

            $back_id = isset($back_order['delivery_id']) ? $back_order['delivery_id'] : 0;
            $this->smarty->assign('back_id', $back_id); // 发货单id

            /* 显示模板 */
            $this->smarty->assign('ur_here', lang('seller/order.back_operate') . lang('seller/order.detail'));
            $this->smarty->assign('action_link', ['href' => 'order.php?act=return_list&' . list_link_postfix(), 'text' => lang('seller/common.12_back_apply'), 'class' => 'icon-reply']);

            return $this->smarty->display('return_order_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 发货单列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_list') {
            /* 检查权限 */
            admin_priv('delivery_view');

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '09_delivery_order']);
            $this->smarty->assign('current', '09_delivery_order');
            /* 查询 */
            $result = $this->delivery_list();

            /* 模板赋值 */
            $this->smarty->assign('ur_here', lang('seller/common.09_delivery_order'));

            $this->smarty->assign('os_unconfirmed', OS_UNCONFIRMED);
            $this->smarty->assign('cs_await_pay', CS_AWAIT_PAY);
            $this->smarty->assign('cs_await_ship', CS_AWAIT_SHIP);
            $this->smarty->assign('full_page', 1);
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);

            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('delivery_list', $result['delivery']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_update_time', '<img src="/assets/seller/images/sort_desc.gif">');
            //http://dscx.test/assets/seller/images/sort_desc.gif

            /* 显示模板 */

            return $this->smarty->display('delivery_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 搜索、排序、分页
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_query') {
            /* 检查权限 */
            admin_priv('delivery_view');
            $this->smarty->assign('current', '09_delivery_order');
            $result = $this->delivery_list();
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('delivery_list', $result['delivery']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            return make_json_result($this->smarty->fetch('delivery_list.dwt'), '', ['filter' => $result['filter'], 'page_count' => $result['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 发货单详细
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_info') {
            /* 检查权限 */
            admin_priv('delivery_view');

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '09_delivery_order']);
            $delivery_id = intval(trim($_REQUEST['delivery_id']));

            /* 根据发货单id查询发货单信息 */
            if (!empty($delivery_id)) {
                $delivery_order = $this->delivery_order_info($delivery_id);
            } else {
                return 'order does not exist';
            }

            /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
            $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
            $agency_id = $this->db->getOne($sql);
            if ($agency_id > 0) {
                if ($delivery_order['agency_id'] != $agency_id) {
                    return sys_msg(lang('seller/common.priv_error'));
                }

                /* 取当前办事处信息 */
                $sql = "SELECT agency_name FROM " . $this->dsc->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";
                $agency_name = $this->db->getOne($sql);
                $delivery_order['agency_name'] = $agency_name;
            }

            /* 取得用户名 */
            if ($delivery_order['user_id'] > 0) {
                $where = [
                    'user_id' => $delivery_order['user_id']
                ];
                $user_info = $this->userService->userInfo($where);

                if (!empty($user_info)) {
                    $delivery_order['user_name'] = $user_info['user_name'];

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $delivery_order['user_name'] = $this->dscRepository->stringToStar($delivery_order['user_name']);
                    }
                }
            }

            /* 是否保价 */
            $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

            /* 取得发货单商品 */
            $goods_sql = "SELECT dg.*, g.brand_id, g.goods_thumb FROM " . $this->dsc->table('delivery_goods') . " AS dg " .
                "LEFT JOIN " . $this->dsc->table('goods') . " AS g ON g.goods_id = dg.goods_id " .
                "WHERE dg.delivery_id = '" . $delivery_order['delivery_id'] . "'";
            $goods_list = $this->db->getAll($goods_sql);

            foreach ($goods_list as $key => $row) {
                $brand = get_goods_brand_info($row['brand_id']);
                $goods_list[$key]['brand_name'] = $brand['brand_name'];

                //图片显示
                $row['goods_thumb'] = get_image_path($row['goods_thumb']);

                $goods_list[$key]['goods_thumb'] = $row['goods_thumb'];
            }

            /* 是否存在实体商品 */
            $exist_real_goods = 0;
            if ($goods_list) {
                foreach ($goods_list as $value) {
                    if ($value['is_real']) {
                        $exist_real_goods++;
                    }
                }
            }

            $orderInfo = OrderInfo::where('order_id', $delivery_order['order_id']);
            $orderInfo = $this->baseRepository->getToArrayFirst($orderInfo);
            $this->smarty->assign('order_info', $orderInfo);

            /* 取得订单操作记录 */
            $act_list = [];
            $sql = "SELECT * FROM " . $this->dsc->table('order_action') . " WHERE order_id = '" . $delivery_order['order_id'] . "' AND action_place = 1 ORDER BY log_time DESC,action_id DESC";
            $res = $this->db->query($sql);
            foreach ($res as $row) {
                $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                $row['pay_status'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
                $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? $GLOBALS['_LANG']['ss_admin'][SS_SHIPPED_ING] : $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                $row['action_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['log_time']);
                $act_list[] = $row;
            }
            $this->smarty->assign('action_list', $act_list);

            /* 模板赋值 */
            $this->smarty->assign('delivery_order', $delivery_order);
            $this->smarty->assign('exist_real_goods', $exist_real_goods);
            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('delivery_id', $delivery_id); // 发货单id

            /* 显示模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['delivery_operate'] . lang('seller/order.detail'));
            $this->smarty->assign('action_link', ['href' => 'order.php?act=delivery_list&' . list_link_postfix(), 'text' => lang('seller/common.09_delivery_order'), 'class' => 'icon-reply']);
            $this->smarty->assign('action_act', ($delivery_order['status'] == DELIVERY_CREATE) ? 'delivery_ship' : 'delivery_cancel_ship');

            return $this->smarty->display('delivery_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 发货单发货确认
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_ship') {
            /* 检查权限 */
            admin_priv('delivery_view');


            /* 定义当前时间 */
            define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

            /* 取得参数 */
            $delivery = [];
            $order_id = intval(trim($_REQUEST['order_id']));        // 订单id
            $delivery_id = intval(trim($_REQUEST['delivery_id']));        // 发货单id
            $delivery['invoice_no'] = isset($_REQUEST['invoice_no']) ? trim($_REQUEST['invoice_no']) : '';
            $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

            /* 根据发货单id查询发货单信息 */
            if (!empty($delivery_id)) {
                $delivery_order = $this->delivery_order_info($delivery_id);
            } else {
                return 'order does not exist';
            }

            /* 查询订单信息 */
            $order = order_info($order_id);
            /* 检查此单发货商品库存缺货情况  ecmoban模板堂 --zhuo start 下单减库存*/
            $delivery_stock_sql = "SELECT G.model_attr, G.model_inventory, DG.goods_id, DG.delivery_id, DG.is_real, DG.send_number AS sums, G.goods_number AS storage, G.goods_name, DG.send_number," .
                " OG.goods_attr_id, OG.warehouse_id, OG.area_id, OG.area_city, OG.ru_id, OG.order_id, OG.product_id FROM " . $this->dsc->table('delivery_goods') . " AS DG, " .
                $this->dsc->table('goods') . " AS G, " .
                $this->dsc->table('delivery_order') . " AS D, " .
                $this->dsc->table('order_goods') . " AS OG " .
                " WHERE DG.goods_id = G.goods_id AND DG.delivery_id = D.delivery_id AND D.order_id = OG.order_id AND DG.delivery_id = '$delivery_id' GROUP BY OG.rec_id ";

            $delivery_stock_result = $this->db->getAll($delivery_stock_sql);

            $virtual_goods = [];
            for ($i = 0; $i < count($delivery_stock_result); $i++) {
                $delivery_stock_result[$i]['goods_id'] = isset($delivery_stock_result[$i]['goods_id']) ? $delivery_stock_result[$i]['goods_id'] : 0;
                $delivery_stock_result[$i]['goods_attr_id'] = isset($delivery_stock_result[$i]['goods_attr_id']) ? $delivery_stock_result[$i]['goods_attr_id'] : '';
                $delivery_stock_result[$i]['ru_id'] = isset($delivery_stock_result[$i]['ru_id']) ? $delivery_stock_result[$i]['ru_id'] : 0;
                $delivery_stock_result[$i]['warehouse_id'] = isset($delivery_stock_result[$i]['warehouse_id']) ? $delivery_stock_result[$i]['warehouse_id'] : 0;
                $delivery_stock_result[$i]['area_id'] = isset($delivery_stock_result[$i]['area_id']) ? $delivery_stock_result[$i]['area_id'] : 0;
                $delivery_stock_result[$i]['area_city'] = isset($delivery_stock_result[$i]['area_city']) ? $delivery_stock_result[$i]['area_city'] : 0;
                $delivery_stock_result[$i]['model_attr'] = isset($delivery_stock_result[$i]['model_attr']) ? $delivery_stock_result[$i]['model_attr'] : '';
                if ($delivery_stock_result[$i]['model_attr'] == 1) {
                    $table_products = "products_warehouse";
                    $type_files = " and warehouse_id = '" . $delivery_stock_result[$i]['warehouse_id'] . "'";
                } elseif ($delivery_stock_result[$i]['model_attr'] == 2) {
                    $table_products = "products_area";
                    $type_files = " and area_id = '" . $delivery_stock_result[$i]['area_id'] . "'";
                } else {
                    $table_products = "products";
                    $type_files = "";
                }

                $sql = "SELECT * FROM " . $this->dsc->table($table_products) . " WHERE goods_id = '" . $delivery_stock_result[$i]['goods_id'] . "'" . $type_files . " LIMIT 0, 1";
                $prod = $this->db->getRow($sql);

                /* 如果商品存在规格就查询规格，如果不存在规格按商品库存查询 */
                if (empty($prod)) {
                    if ($delivery_stock_result[$i]['model_inventory'] == 1) {
                        $delivery_stock_result[$i]['storage'] = get_warehouse_area_goods($delivery_stock_result[$i]['warehouse_id'], $delivery_stock_result[$i]['goods_id'], 'warehouse_goods');
                    } elseif ($delivery_stock_result[$i]['model_inventory'] == 2) {
                        $delivery_stock_result[$i]['storage'] = get_warehouse_area_goods($delivery_stock_result[$i]['area_id'], $delivery_stock_result[$i]['goods_id'], 'warehouse_area_goods');
                    }
                } else {
                    $products = $this->goodsWarehouseService->getWarehouseAttrNumber($delivery_stock_result[$i]['goods_id'], $delivery_stock_result[$i]['goods_attr_id'], $delivery_stock_result[$i]['warehouse_id'], $delivery_stock_result[$i]['area_id'], $delivery_stock_result[$i]['area_city'], $delivery_stock_result[$i]['model_attr']);
                    $delivery_stock_result[$i]['storage'] = $products['product_number'];
                }

                if (($delivery_stock_result[$i]['sums'] > $delivery_stock_result[$i]['storage'] || $delivery_stock_result[$i]['storage'] <= 0) && (($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) || ($GLOBALS['_CFG']['use_storage'] == '0' && $delivery_stock_result[$i]['is_real'] == 0))) {
                    /* 操作失败 */
                    $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                    return sys_msg(sprintf(lang('seller/order.act_good_vacancy'), $delivery_stock_result[$i]['goods_name']), 1, $links);
                    break;
                }

                /* 虚拟商品列表 virtual_card*/
                if ($delivery_stock_result[$i]['is_real'] == 0) {
                    $virtual_goods[] = [
                        'goods_id' => $delivery_stock_result[$i]['goods_id'],
                        'goods_name' => $delivery_stock_result[$i]['goods_name'],
                        'num' => $delivery_stock_result[$i]['send_number']
                    ];
                }
            }
            //ecmoban模板堂 --zhuo end 下单减库存

            /* 发货 */
            /* 处理虚拟卡 商品（虚货） */
            if ($virtual_goods && is_array($virtual_goods) && count($virtual_goods) > 0) {
                foreach ($virtual_goods as $virtual_value) {
                    virtual_card_shipping($virtual_value, $order['order_sn'], $msg, 'split');
                }

                //虚拟卡缺货
                if (!empty($msg)) {
                    $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                    return sys_msg($msg, 1, $links);
                }
            }

            /* 如果使用库存，且发货时减库存，则修改库存 */
            if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                foreach ($delivery_stock_result as $value) {

                    /* 商品（实货）、超级礼包（实货） ecmoban模板堂 --zhuo */
                    if ($value['is_real'] != 0) {
                        //（货品）
                        if (!empty($value['product_id'])) {
                            if ($value['model_attr'] == 1) {
                                $minus_stock_sql = "UPDATE " . $this->dsc->table('products_warehouse') . "
                                            SET product_number = product_number - " . $value['sums'] . "
                                            WHERE product_id = " . $value['product_id'];
                            } elseif ($value['model_attr'] == 2) {
                                $minus_stock_sql = "UPDATE " . $this->dsc->table('products_area') . "
                                            SET product_number = product_number - " . $value['sums'] . "
                                            WHERE product_id = " . $value['product_id'];
                            } else {
                                $minus_stock_sql = "UPDATE " . $this->dsc->table('products') . "
                                            SET product_number = product_number - " . $value['sums'] . "
                                            WHERE product_id = " . $value['product_id'];
                            }
                        } else {
                            if ($value['model_inventory'] == 1) {
                                $minus_stock_sql = "UPDATE " . $this->dsc->table('warehouse_goods') . "
                                            SET region_number = region_number - " . $value['sums'] . "
                                            WHERE goods_id = " . $value['goods_id'] . " AND region_id = " . $value['warehouse_id'];
                            } elseif ($value['model_inventory'] == 2) {
                                $minus_stock_sql = "UPDATE " . $this->dsc->table('warehouse_area_goods') . "
                                            SET region_number = region_number - " . $value['sums'] . "
                                            WHERE goods_id = " . $value['goods_id'] . " AND region_id = " . $value['area_id'];
                            } else {
                                $minus_stock_sql = "UPDATE " . $this->dsc->table('goods') . "
                                            SET goods_number = goods_number - " . $value['sums'] . "
                                            WHERE goods_id = " . $value['goods_id'];
                            }
                        }

                        $this->db->query($minus_stock_sql, 'SILENT');

                        //库存日志
                        $logs_other = [
                            'goods_id' => $value['goods_id'],
                            'order_id' => $value['order_id'],
                            'use_storage' => $GLOBALS['_CFG']['stock_dec_time'],
                            'admin_id' => session('seller_id'),
                            'number' => "- " . $value['sums'],
                            'model_inventory' => $value['model_inventory'],
                            'model_attr' => $value['model_attr'],
                            'product_id' => $value['product_id'],
                            'warehouse_id' => $value['warehouse_id'],
                            'area_id' => $value['area_id'],
                            'add_time' => gmtime()
                        ];

                        $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                    }
                }
            }

            /* 修改发货单信息 */
            $invoice_no = str_replace(',', ',', $delivery['invoice_no']);
            $invoice_no = trim($invoice_no, ',');
            $_delivery['invoice_no'] = $invoice_no;
            $_delivery['status'] = DELIVERY_SHIPPED; // 0，为已发货
            $query = $this->db->autoExecute($this->dsc->table('delivery_order'), $_delivery, 'UPDATE', "delivery_id = $delivery_id", 'SILENT');
            if (!$query) {
                /* 操作失败 */
                $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                return sys_msg(lang('seller/order.act_false'), 1, $links);
            }

            /* 标记订单为已确认 “已发货” */
            /* 更新发货时间 */
            $order_finish = $this->get_all_delivery_finish($order_id);
            $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
            $arr['shipping_status'] = $shipping_status;
            $arr['shipping_time'] = GMTIME_UTC; // 发货时间
            $arr['invoice_no'] = !empty($invoice_no) ? $invoice_no : $order['invoice_no'];
            update_order($order_id, $arr);

            /* 发货单发货记录log */
            order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, session('seller_name'), 1);

            /* 如果当前订单已经全部发货 */
            if ($order_finish) {
                /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                if ($order['user_id'] > 0) {
                    /* 计算并发放积分 */
                    $integral = integral_to_give($order);
                    /*如果已配送子订单的赠送积分大于0   减去已配送子订单积分*/
                    if (!empty($child_order)) {
                        $integral['custom_points'] = $integral['custom_points'] - $child_order['custom_points'];
                        $integral['rank_points'] = $integral['rank_points'] - $child_order['rank_points'];
                    }
                    log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));

                    /* 发放红包 */
                    send_order_bonus($order_id);

                    /* 发放优惠券 bylu */
                    send_order_coupons($order_id);
                }

                /* 发送邮件 */
                $cfg = $GLOBALS['_CFG']['send_ship_email'];
                if ($cfg == '1') {
                    $order['invoice_no'] = $invoice_no;
                    $tpl = get_mail_template('deliver_notice');
                    $this->smarty->assign('order', $order);
                    $this->smarty->assign('send_time', local_date($GLOBALS['_CFG']['time_format']));
                    $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                    $this->smarty->assign('confirm_url', $this->dsc->url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']); //by wu
                    $this->smarty->assign('send_msg_url', $this->dsc->url() . 'user_message.php?act=message_list&order_id=' . $order['order_id']);
                    $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                    if (!$this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                        $msg = lang('seller/order.send_mail_fail');
                    }
                }

                /* 如果需要，发短信 */
                if ($GLOBALS['_CFG']['sms_order_shipped'] == '1' && $order['mobile'] != '') {

                    //短信接口参数
                    if ($order['ru_id']) {
                        $shop_name = $this->merchantCommonService->getShopName($order['ru_id'], 1);
                    } else {
                        $shop_name = "";
                    }

                    $user_info = get_admin_user_info($order['user_id']);

                    $smsParams = [
                        'shop_name' => $shop_name,
                        'shopname' => $shop_name,
                        'user_name' => $user_info['user_name'],
                        'username' => $user_info['user_name'],
                        'consignee' => $order['consignee'],
                        'order_sn' => $order['order_sn'],
                        'ordersn' => $order['order_sn'],
                        'mobile_phone' => $order['mobile'],
                        'mobilephone' => $order['mobile']
                    ];

                    $this->commonRepository->smsSend($order['mobile'], $smsParams, 'sms_order_shipped');
                }

                if (file_exists(MOBILE_WECHAT)) {
                    $pushData = [
                        'first' => ['value' => $GLOBALS['_LANG']['you_order_delivery']], // 标题
                        'keyword1' => ['value' => $order['order_sn']], //订单
                        'keyword2' => ['value' => $order['shipping_name']], //物流服务
                        'keyword3' => ['value' => $invoice_no], //快递单号
                        'keyword4' => ['value' => $order['consignee']], // 收货信息
                        'remark' => ['value' => $GLOBALS['_LANG']['order_delivery_wait']]
                    ];
                    $shop_url = url('/') . '/'; // 根域名 兼容商家后台
                    $order_url = dsc_url('/#/user/orderDetail/' . $order_id);

                    push_template_curl('OPENTM202243318', $pushData, $order_url, $order['user_id'], $shop_url);
                }

                /* 更新商品销量 */
                get_goods_sale($order_id);
            }

            /* 更新会员订单信息 */
            $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

            /* 操作成功 */
            $links[] = ['text' => lang('seller/common.09_delivery_order'), 'href' => 'order.php?act=delivery_list'];
            $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
            return sys_msg(lang('seller/order.act_ok'), 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 检测确认收货订单 ecmoban模板堂 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_detection') {
            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '11_order_detection']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['11_order_detection']);
            $this->smarty->assign('store_list', $store_list);

            $order_list = get_order_detection_list();

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $this->smarty->assign('sort_order_time', '<img src="__TPL__/images/sort_desc.gif">');

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('is_detection', 1);
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            return $this->smarty->display('order_detection_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 发货中订单列表排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'detection_query') {
            /* 检查权限 */
            admin_priv('order_detection');
            $order_list = get_order_detection_list();

            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);
            $sort_flag = sort_flag($order_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('is_detection', 1);

            return make_json_result($this->smarty->fetch('order_detection_list.dwt'), '', ['filter' => $order_list['filter'], 'page_count' => $order_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 处理系统设置订单自动确认收货订单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'auto_order_detection') {
            /* 检查权限 */
            admin_priv('order_detection');

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '11_order_detection']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['11_order_detection']);
            $order_list = get_order_detection_list(3);
            if ($order_list['orders']) {
                $is_ajax_detection = 1;
            } else {
                $is_ajax_detection = 0;
            }

            session([
                'is_ajax_detection' => $is_ajax_detection
            ]);

            $order_list = get_order_detection_list(1);
            $this->smarty->assign('is_detection', 2);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display('order_detection_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理系统设置订单自动确认收货订单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_order_detection') {
            /* 检查权限 */
            admin_priv('order_detection');


            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);

            if (session()->has('is_ajax_detection') && session('is_ajax_detection') == 1) {
                $order_list = get_order_detection_list(2);

                $result['page'] = $order_list['filter']['page'] + 1;
                $result['page_size'] = $order_list['filter']['page_size'];
                $result['record_count'] = $order_list['filter']['record_count'];
                $result['page_count'] = $order_list['filter']['page_count'];

                $result['order'] = $order_list['orders'][0];

                if ($result['order'] && $result['order']['shipping_status'] == SS_SHIPPED) {
                    $confirm_take_time = gmtime();
                    $operator = $GLOBALS['_LANG']['system_handle'];

                    /* 记录日志 */
                    $note = $GLOBALS['_LANG']['self_motion_goods'];
                    order_action($result['order']['order_sn'], $result['order']['order_status'], SS_RECEIVED, $result['order']['pay_status'], $note, $operator, 0, $confirm_take_time);

                    $this->db->query("UPDATE " . $this->dsc->table('order_info') .
                        " SET confirm_take_time = '$confirm_take_time', " .
                        "order_status = '" . $result['order']['order_status'] . "', " .
                        "shipping_status = '" . SS_RECEIVED . "', pay_status = '" . $result['order']['pay_status'] . "'" .
                        " WHERE order_id = '" . $result['order']['order_id'] . "'");

                    /* 更新会员订单信息 */
                    $dbRaw = [
                        'order_nogoods' => "order_nogoods - 1",
                        'order_isfinished' => "order_isfinished + 1"
                    ];
                    $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
                    UserOrderNum::where('user_id', $user_id)->update($dbRaw);

                    /* 生成账单订单记录 start */
                    $sql = "SELECT order_id, ru_id, user_id, order_sn , order_status, shipping_status, pay_status, " .
                        "order_amount, goods_amount, tax, shipping_fee, insure_fee, pay_fee, pack_fee, card_fee, " .
                        "bonus, integral_money, coupons, discount, money_paid, surplus, confirm_take_time, rate_fee " .
                        "FROM " . $this->dsc->table('order_info') . " WHERE order_id = '" . $result['order']['order_id'] . "'";

                    $order = $this->db->GetRow($sql);

                    $seller_id = $order['ru_id'];
                    $value_card = $this->db->getOne("SELECT use_val FROM " . $this->dsc->table('value_card_record') . " WHERE order_id = '" . $order['order_id'] . "'", true);

                    $negativeCount = SellerNegativeOrder::where('order_id', $order['order_id'])->count();

                    if ($negativeCount <= 0) {
                        $return_amount_info = $this->orderRefoundService->orderReturnAmount($order['order_id']);
                    } else {
                        $return_amount_info['return_amount'] = 0;
                        $return_amount_info['return_rate_fee'] = 0;
                        $return_amount_info['ret_id'] = [];
                    }

                    if ($order['order_amount'] > 0 && $order['order_amount'] > $order['rate_fee']) {
                        $order_amount = $order['order_amount'] - $order['rate_fee'];
                    } else {
                        $order_amount = $order['order_amount'];
                    }

                    $other = [
                        'user_id' => $order['user_id'],
                        'seller_id' => $seller_id,
                        'order_id' => $order['order_id'],
                        'order_sn' => $order['order_sn'],
                        'order_status' => $order['order_status'],
                        'shipping_status' => $order['shipping_status'],
                        'pay_status' => $order['pay_status'],
                        'order_amount' => $order_amount,
                        'return_amount' => $return_amount_info['return_amount'],
                        'goods_amount' => $order['goods_amount'],
                        'tax' => $order['tax'],
                        'shipping_fee' => $order['shipping_fee'],
                        'insure_fee' => $order['insure_fee'],
                        'pay_fee' => $order['pay_fee'],
                        'pack_fee' => $order['pack_fee'],
                        'card_fee' => $order['card_fee'],
                        'bonus' => $order['bonus'],
                        'integral_money' => $order['integral_money'],
                        'coupons' => $order['coupons'],
                        'discount' => $order['discount'],
                        'value_card' => $value_card,
                        'money_paid' => $order['money_paid'],
                        'surplus' => $order['surplus'],
                        'confirm_take_time' => $confirm_take_time,
                        'rate_fee' => $order['rate_fee'],
                        'return_rate_fee' => $return_amount_info['return_rate_price']
                    ];

                    if ($seller_id) {
                        $this->commissionService->getOrderBillLog($other);
                        $this->commissionService->setBillOrderReturn($return_amount_info['ret_id'], $other['order_id']);
                    }
                    /* 生成账单订单记录 end */
                }

                if ($order_list['filter']['page'] > $order_list['filter']['page_count']) {
                    $result['stop_ajax'] = 0;

                    session([
                        'is_ajax_detection' => 0
                    ]);
                } else {
                    $result['stop_ajax'] = 1;
                }
            } else {
                $result['order'] = '';
                $result['stop_ajax'] = 0;
            }


            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 发货单取消发货
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_cancel_ship') {
            /* 检查权限 */
            admin_priv('delivery_view');

            /* 取得参数 */
            $delivery = [];
            $order_id = intval(trim($_REQUEST['order_id']));        // 订单id
            $delivery_id = intval(trim($_REQUEST['delivery_id']));        // 发货单id
            $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

            /* 根据发货单id查询发货单信息 */
            if (!empty($delivery_id)) {
                $delivery_order = $this->delivery_order_info($delivery_id);
            } else {
                return 'order does not exist';
            }

            /* 查询订单信息 */
            $order = order_info($order_id);

            /* 取消当前发货单物流单号 */
            $_delivery['invoice_no'] = '';
            $_delivery['status'] = DELIVERY_CREATE;
            $query = $this->db->autoExecute($this->dsc->table('delivery_order'), $_delivery, 'UPDATE', "delivery_id = $delivery_id", 'SILENT');
            if (!$query) {
                /* 操作失败 */
                $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                return sys_msg(lang('seller/order.act_false'), 1, $links);
            }

            if ($order['invoice_no']) {
                /* 修改定单发货单号 */
                $invoice_no_order = explode(',', $order['invoice_no']);
                $invoice_no_delivery = explode(',', $delivery_order['invoice_no']);
                foreach ($invoice_no_order as $key => $value) {
                    $delivery_key = array_search($value, $invoice_no_delivery);
                    if ($delivery_key !== false) {
                        unset($invoice_no_order[$key], $invoice_no_delivery[$delivery_key]);
                        if (count($invoice_no_delivery) == 0) {
                            break;
                        }
                    }
                }
                $_order['invoice_no'] = implode(',', $invoice_no_order);
            } else {
                $_order['invoice_no'] = '';
            }

            /* 更新配送状态 */
            $order_finish = $this->get_all_delivery_finish($order_id);
            $shipping_status = ($order_finish == -1) ? SS_SHIPPED_PART : SS_SHIPPED_ING;
            $arr['shipping_status'] = $shipping_status;
            if ($shipping_status == SS_SHIPPED_ING) {
                $arr['shipping_time'] = ''; // 发货时间
            }
            $arr['invoice_no'] = $_order['invoice_no'];
            update_order($order_id, $arr);

            /* 发货单取消发货记录log */
            order_action($order['order_sn'], $order['order_status'], $shipping_status, $order['pay_status'], $action_note, session('seller_name'), 1);

            /* 如果使用库存，则增加库存 */
            if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                // 检查此单发货商品数量
                $virtual_goods = [];
                $delivery_stock_sql = "SELECT DG.goods_id, DG.product_id, DG.is_real, SUM(DG.send_number) AS sums
            FROM " . $this->dsc->table('delivery_goods') . " AS DG
            WHERE DG.delivery_id = '$delivery_id'
            GROUP BY DG.goods_id ";
                $delivery_stock_result = $this->db->getAll($delivery_stock_sql);
                foreach ($delivery_stock_result as $key => $value) {
                    /* 虚拟商品 */
                    if ($value['is_real'] == 0) {
                        continue;
                    }

                    //（货品）
                    if (!empty($value['product_id'])) {
                        $minus_stock_sql = "UPDATE " . $this->dsc->table('products') . "
                                    SET product_number = product_number + " . $value['sums'] . "
                                    WHERE product_id = " . $value['product_id'];
                        $this->db->query($minus_stock_sql, 'SILENT');
                    }

                    $minus_stock_sql = "UPDATE " . $this->dsc->table('goods') . "
                                SET goods_number = goods_number + " . $value['sums'] . "
                                WHERE goods_id = " . $value['goods_id'];
                    $this->db->query($minus_stock_sql, 'SILENT');
                }
            }

            /* 发货单全退回时，退回其它 */
            if ($order['order_status'] == SS_SHIPPED_ING) {
                /* 如果订单用户不为空，计算积分，并退回 */
                if ($order['user_id'] > 0) {
                    /* 计算并退回积分 */
                    $integral = integral_to_give($order);
                    log_account_change($order['user_id'], 0, 0, (-1) * intval($integral['rank_points']), (-1) * intval($integral['custom_points']), sprintf(lang('seller/order.return_order_gift_integral'), $order['order_sn']));

                    /* todo 计算并退回红包 */
                    return_order_bonus($order_id);
                }
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 操作成功 */
            $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
            return sys_msg(lang('seller/order.act_ok'), 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 退货单列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'back_list') {
            /* 检查权限 */
            admin_priv('back_view');
            $this->smarty->assign('current', '10_back_order');

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '10_back_order']);

            /* 查询 */
            $result = $this->back_list();

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['10_back_order']);
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('os_unconfirmed', OS_UNCONFIRMED);
            $this->smarty->assign('cs_await_pay', CS_AWAIT_PAY);
            $this->smarty->assign('cs_await_ship', CS_AWAIT_SHIP);
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign('back_list', $result['back']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_update_time', '<img src="__TPL__/images/sort_desc.gif">');

            /* 显示模板 */

            return $this->smarty->display('back_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 搜索、排序、分页
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'back_query') {
            /* 检查权限 */
            admin_priv('back_view');

            $result = $this->back_list();
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('back_list', $result['back']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            return make_json_result($this->smarty->fetch('back_list.dwt'), '', ['filter' => $result['filter'], 'page_count' => $result['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 退货单详细
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'back_info') {
            /* 检查权限 */
            admin_priv('back_view');

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '10_back_order']);
            $back_id = intval(trim($_REQUEST['back_id']));

            /* 根据发货单id查询发货单信息 */
            if (!empty($back_id)) {
                $back_order = $this->back_order_info($back_id);
            } else {
                return 'order does not exist';
            }

            /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
            $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
            $agency_id = $this->db->getOne($sql);
            if ($agency_id > 0) {
                if ($back_order['agency_id'] != $agency_id) {
                    return sys_msg(lang('seller/common.priv_error'));
                }

                /* 取当前办事处信息*/
                $sql = "SELECT agency_name FROM " . $this->dsc->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";
                $agency_name = $this->db->getOne($sql);
                $back_order['agency_name'] = $agency_name;
            }

            /* 取得用户名 */
            if ($back_order['user_id'] > 0) {
                $where = [
                    'user_id' => $back_order['user_id']
                ];
                $user_info = $this->userService->userInfo($where);

                if (!empty($user_info)) {
                    $back_order['user_name'] = $user_info['user_name'];

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $back_order['user_name'] = $this->dscRepository->stringToStar($back_order['user_name']);
                    }
                }
            }

            /* 是否保价 */
            $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

            /* 取得发货单商品 */
            $goods_sql = "SELECT bg.*, g.brand_id FROM " . $this->dsc->table('back_goods') . " AS bg " .
                "LEFT JOIN " . $this->dsc->table('goods') . " AS g ON g.goods_id = bg.goods_id " .
                "WHERE bg.back_id = '" . $back_order['back_id'] . "'";
            $goods_list = $this->db->getAll($goods_sql);

            foreach ($goods_list as $key => $row) {
                $brand = get_goods_brand_info($row['brand_id']);
                $goods_list[$key]['brand_name'] = $brand['brand_name'];
                //图片显示
                $goods_list[$key]['goods_thumb'] = empty($row['goods_thumb']) ? '' : get_image_path($row['goods_thumb']);
            }

            /* 是否存在实体商品 */
            $exist_real_goods = 0;
            if ($goods_list) {
                foreach ($goods_list as $value) {
                    if ($value['is_real']) {
                        $exist_real_goods++;
                    }
                }
            }

            /* 模板赋值 */
            $this->smarty->assign('back_order', $back_order);
            $this->smarty->assign('exist_real_goods', $exist_real_goods);
            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('back_id', $back_id); // 发货单id

            /* 显示模板 */
            $this->smarty->assign('ur_here', lang('seller/order.back_operate') . lang('seller/order.detail'));
            $this->smarty->assign('action_link', ['href' => 'order.php?act=back_list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['10_back_order'], 'class' => 'icon-reply']);

            return $this->smarty->display('back_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 修改退换金额 --zhuo
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_refound_amount') {
            /* 检查权限 */
            admin_priv('order_edit');
            $check_auth = check_authz_json('order_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $type = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']);
            $refound_amount = empty($_REQUEST['refound_amount']) ? 0 : floatval($_REQUEST['refound_amount']);
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $rec_id = empty($_REQUEST['rec_id']) ? 0 : intval($_REQUEST['rec_id']);
            $ret_id = empty($_REQUEST['ret_d']) ? 0 : intval($_REQUEST['ret_d']);

            $should_return = 0;
            $order_shipping_fee = 0;
            $shipping_fee = 0;

            if ($type == 1) {
                $order_return = OrderReturn::where('ret_id', $ret_id);
                $order_return = $this->baseRepository->getToArrayFirst($order_return);

                $order = order_info($order_id);

                $paid_amount = $order['money_paid'] + $order['surplus'];
                if ($paid_amount > 0 && $paid_amount >= $order['shipping_fee']) {
                    $paid_amount = $paid_amount - $order['shipping_fee'];
                }

                if ($ret_id > 0) {
                    /**
                     * 单品退款
                     */
                    $refound_fee = order_refound_fee($order_id, $ret_id); //已退金额

                    if ($refound_fee > 0 && $order_return['should_return'] > $refound_fee) {
                        $paid_amount = $paid_amount - $refound_fee;

                        if ($refound_amount > $paid_amount) {
                            $should_return = $paid_amount;
                        }
                    } else {
                        $should_return = $order_return['should_return'];
                    }

                    if ($order_return['goods_coupons'] > 0) {
                        $should_return -= $order_return['goods_coupons'];
                    }

                    if ($order_return['goods_bonus'] > 0) {
                        $should_return -= $order_return['goods_bonus'];
                    }
                } else {
                    /**
                     * 整单退款
                     */
                    $should_return = $refound_amount;
                }

                if ($should_return > $paid_amount) {
                    $should_return = $paid_amount;
                }
            } elseif ($type == 2) {

                /* 退运费 */
                $sql = "SELECT shipping_fee FROM " . $this->dsc->table('order_info') . " WHERE order_id = '$order_id'";
                $order_shipping_fee = $this->db->getOne($sql);

                //判断运费退款是否大于实际运费退款金额
                $is_refound_shippfee = order_refound_shipping_fee($order_id, $ret_id);
                $is_refound_shippfee_amount = $is_refound_shippfee + $refound_amount;

                if ($is_refound_shippfee_amount > $order_shipping_fee) {
                    $shipping_fee = $order_shipping_fee - $is_refound_shippfee;
                } else {
                    $shipping_fee = $refound_amount;
                }

                $refound_amount = $shipping_fee;
            }

            $should_return = number_format($should_return, 2, '.', '');

            $data = [
                'should_return' => !empty($should_return) ? $should_return : 0, //订单金额
                'refound_amount' => $refound_amount, //退款订单金额
                'order_shipping_fee' => $order_shipping_fee, //订单运费
                'shipping_fee' => $shipping_fee, //可退运费
                'type' => $type
            ];

            clear_cache_files();
            return make_json_result($data);
        }

        /*------------------------------------------------------ */
        //-- 修改订单退货退储值卡金额
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_refound_value_card') {
            /* 检查权限 */
            admin_priv('order_edit');
            $check_auth = check_authz_json('order_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $vc_id = empty($_REQUEST['vc_id']) ? 0 : intval($_REQUEST['vc_id']);
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $ret_id = empty($_REQUEST['ret_id']) ? 0 : intval($_REQUEST['ret_id']);
            $refound_vcard = empty($_REQUEST['refound_vcard']) ? 0 : floatval($_REQUEST['refound_vcard']);

            if ($ret_id > 0) {
                $return_order = return_order_info($ret_id);
                $should_return = $return_order['should_return'] - $return_order['discount_amount'];
            } else {
                $order = order_info($order_id);
                $order_amount = $order['money_paid'] + $order['surplus'];

                if ($order_amount > 0 && $order_amount > $order['shipping_fee']) {
                    $order_amount = $order_amount - $order['shipping_fee'];
                } else {
                    $should_return = $order['total_fee'];
                }
            }

            $sql = "SELECT vc_id, use_val FROM " . $this->dsc->table('value_card_record') . " WHERE vc_id = '$vc_id' AND order_id = '$order_id' LIMIT 1";
            $value_card = $this->db->getRow($sql);

            if ($value_card) {
                if ($value_card['use_val'] > $should_return) {
                    $value_card['use_val'] = $should_return;
                }
            }

            if ($value_card && $refound_vcard > $value_card['use_val']) {
                $refound_vcard = $value_card['use_val'];
            }

            $data = [
                'refound_vcard' => !empty($refound_vcard) ? $refound_vcard : 0, //储值卡金额
            ];

            clear_cache_files();
            return make_json_result($data);
        }

        /*------------------------------------------------------ */
        //-- 修改订单（处理提交）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'step_post') {
            /* 检查权限 */
            admin_priv('order_edit');

            /* 取得参数 step */
            $step_list = ['user', 'edit_goods', 'add_goods', 'goods', 'consignee', 'shipping', 'payment', 'other', 'money', 'invoice'];
            $step = isset($_REQUEST['step']) && in_array($_REQUEST['step'], $step_list) ? $_REQUEST['step'] : 'user';

            /* 取得参数 order_id */
            $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
            if ($order_id > 0) {
                $old_order = order_info($order_id);
            }

            /* 取得参数 step_act 添加还是编辑 */
            $step_act = isset($_REQUEST['step_act']) ? $_REQUEST['step_act'] : 'add';

            /* 插入订单信息 */
            if ('user' == $step) {
                /* 取得参数：user_id */
                $user_id = ($_POST['anonymous'] == 1) ? 0 : intval($_POST['user']);

                /* 插入新订单，状态为无效 */
                $order = [
                    'user_id' => $user_id,
                    'add_time' => gmtime(),
                    'order_status' => OS_INVALID,
                    'shipping_status' => SS_UNSHIPPED,
                    'pay_status' => PS_UNPAYED,
                    'from_ad' => 0,
                    'referer' => $GLOBALS['_LANG']['admin']
                ];

                do {
                    $order['order_sn'] = get_order_sn();
                    if ($this->db->autoExecute($this->dsc->table('order_info'), $order, 'INSERT', '', 'SILENT')) {
                        break;
                    } else {
                        if ($this->db->errno() != 1062) {
                            return $this->db->error();
                        }
                    }
                } while (true); // 防止订单号重复

                $order_id = $this->db->insert_id();

                /* todo 记录日志 */
                admin_log($order['order_sn'], 'add', 'order');

                /* 记录log */
                $action_note = sprintf(lang('seller/order.add_order_info'), session('seller_name'));
                order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], $action_note, session('seller_name'));

                /* 插入 pay_log */
                $sql = 'INSERT INTO ' . $this->dsc->table('pay_log') . " (order_id, order_amount, order_type, is_paid)" .
                    " VALUES ('$order_id', 0, '" . PAY_ORDER . "', 0)";
                $this->db->query($sql);

                /* 下一步 */
                return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
            } /* 编辑商品信息 */
            elseif ('edit_goods' == $step) {
                if (isset($_POST['rec_id'])) {
                    foreach ($_POST['rec_id'] as $key => $rec_id) {
                        $sql = "SELECT warehouse_id, area_id FROM " . $this->dsc->table('order_goods') . " WHERE rec_id = '$rec_id' LIMIT 1";
                        $order_goods = $this->db->getRow($sql);

                        $sql = "SELECT goods_number " .
                            'FROM ' . $this->dsc->table('goods') .
                            "WHERE goods_id =" . $_POST['goods_id'][$key];
                        /* 取得参数 */
                        $goods_price = floatval($_POST['goods_price'][$key]);
                        $goods_number = intval($_POST['goods_number'][$key]);
                        $goods_attr = $_POST['goods_attr'][$key];
                        $product_id = intval($_POST['product_id'][$key]);
                        if ($product_id) {
                            $sql = "SELECT product_number " .
                                'FROM ' . $this->dsc->table('products') .
                                " WHERE product_id =" . $_POST['product_id'][$key];
                        }
                        $goods_number_all = $this->db->getOne($sql);
                        if ($goods_number_all >= $goods_number) {
                            /* 修改 */
                            $sql = "UPDATE " . $this->dsc->table('order_goods') .
                                " SET goods_price = '$goods_price', " .
                                "goods_number = '$goods_number', " .
                                "goods_attr = '$goods_attr', " .
                                "warehouse_id = '" . $order_goods['warehouse_id'] . "', " .
                                "area_id = '" . $order_goods['area_id'] . "' " .
                                "WHERE rec_id = '$rec_id' LIMIT 1";
                            $this->db->query($sql);
                        } else {
                            return sys_msg($GLOBALS['_LANG']['goods_num_err']);
                        }
                    }

                    /* 更新商品总金额和订单总金额 */
                    $goods_amount = order_amount($order_id);
                    update_order($order_id, ['goods_amount' => $goods_amount]);
                    $this->update_order_amount($order_id);

                    /* 更新 pay_log */
                    update_pay_log($order_id);

                    /* todo 记录日志 */
                    $sn = $old_order['order_sn'];
                    $new_order = order_info($order_id);
                    if ($old_order['total_fee'] != $new_order['total_fee']) {
                        $sn .= ',' . sprintf($GLOBALS['_LANG']['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
                    }
                    admin_log($sn, 'edit', 'order');
                }

                /* 跳回订单商品 */
                return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
            } /* 添加商品 */
            elseif ('add_goods' == $step) {
                /* 取得参数 */
                $goods_id = isset($_POST['goodslist']) && !empty($_POST['goodslist']) ? intval($_POST['goodslist']) : 0;
                $warehouse_id = isset($_POST['warehouse_id']) && !empty($_POST['warehouse_id']) ? intval($_POST['warehouse_id']) : 0;
                $area_id = isset($_POST['area_id']) && !empty($_POST['area_id']) ? intval($_POST['area_id']) : 0;
                $model_attr = isset($_POST['model_attr']) && !empty($_POST['model_attr']) ? intval($_POST['model_attr']) : 0;
                $attr_price = isset($_POST['attr_price']) && $_POST['attr_price'] < 0 ? floatval($_POST['attr_price']) : 0;

                $input_price = isset($_POST['input_price']) ? floatval($_POST['input_price']) : 0;
                $add_price = isset($_POST['add_price']) ? floatval($_POST['add_price']) : 0;

                $spec_count = isset($_POST['spec_count']) ? intval($_POST['spec_count']) : 0;

                $goods_price = $add_price != 'user_input' ? $add_price : $input_price;
                $goods_price = $goods_price + $attr_price;

                $goods_info = Goods::select('goods_id', 'goods_name', 'user_id', 'goods_sn', 'market_price', 'is_real', 'extension_code')
                    ->where('goods_id', $goods_id);
                $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

                $goods_attr = '0';
                for ($i = 0; $i < $spec_count; $i++) {
                    if (isset($_POST['spec_' . $i]) && is_array($_POST['spec_' . $i])) {
                        $temp_array = $_POST['spec_' . $i];
                        $temp_array_count = count($_POST['spec_' . $i]);
                        for ($j = 0; $j < $temp_array_count; $j++) {
                            if ($temp_array[$j] !== null) {
                                $goods_attr .= ',' . $temp_array[$j];
                            }
                        }
                    } else {
                        if (isset($_POST['spec_' . $i]) && $_POST['spec_' . $i] !== null) {
                            $goods_attr .= ',' . $_POST['spec_' . $i];
                        }
                    }
                }
                $goods_number = isset($_POST['add_number']) && !empty($_POST['add_number']) ? $_POST['add_number'] : 0;
                $attr_list = $goods_attr;

                $goods_attr = explode(',', $goods_attr);
                $k = array_search(0, $goods_attr);
                unset($goods_attr[$k]);

                //ecmoban模板堂 --zhuo start
                $attr_leftJoin = '';
                $select = '';
                if ($model_attr == 1) {
                    $select = " wap.attr_price as warehouse_attr_price, ";
                    $attr_leftJoin = 'LEFT JOIN ' . $this->dsc->table('warehouse_attr') . " AS wap ON g.goods_attr_id = wap.goods_attr_id AND wap.warehouse_id = '$warehouse_id' ";
                } elseif ($model_attr == 2) {
                    $select = " waa.attr_price as area_attr_price, ";
                    $attr_leftJoin = 'LEFT JOIN ' . $this->dsc->table('warehouse_area_attr') . " AS waa ON g.goods_attr_id = waa.goods_attr_id AND area_id = '$area_id' ";
                }

                $goods_attr_value = '';
                $attr_value = [];
                if ($attr_list) {
                    $where = "g.goods_attr_id in($attr_list)";

                    $sql = "SELECT g.attr_value, " . $select . " g.attr_price " .
                        'FROM ' . $this->dsc->table('goods_attr') . " AS g " .
                        "LEFT JOIN" . $this->dsc->table('attribute') . " AS a ON g.attr_id = a.attr_id " .
                        $attr_leftJoin .
                        "WHERE $where ORDER BY a.sort_order, a.attr_id, g.goods_attr_id";

                    $res = $this->db->query($sql);
                    foreach ($res as $row) {
                        if ($model_attr == 1) {
                            $row['attr_price'] = $row['warehouse_attr_price'];
                        } elseif ($model_attr == 2) {
                            $row['attr_price'] = $row['area_attr_price'];
                        } else {
                            $row['attr_price'] = $row['attr_price'];
                        }

                        $attr_price = '';
                        if ($row['attr_price'] > 0) {
                            $attr_price = ":[" . price_format($row['attr_price']) . "]";
                        }
                        $attr_value[] = $row['attr_value'] . $attr_price;
                    }
                    //ecmoban模板堂 --zhuo end

                    if ($attr_value) {
                        $goods_attr_value = implode(",", $attr_value);
                    }
                }

                //ecmoban模板堂 --zhuo start
                if ($model_attr == 1) {
                    $table_products = "products_warehouse";
                    $type_files = " and warehouse_id = '$warehouse_id'";
                } elseif ($model_attr == 2) {
                    $table_products = "products_area";
                    $type_files = " and area_id = '$area_id'";
                } else {
                    $table_products = "products";
                    $type_files = "";
                }

                $sql = "SELECT * FROM " . $this->dsc->table($table_products) . " WHERE goods_id = '$goods_id'" . $type_files . " LIMIT 0, 1";
                $prod = $this->db->getRow($sql);
                //ecmoban模板堂 --zhuo end

                if (is_spec($goods_attr) && !empty($prod)) {
                    $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $goods_attr, $warehouse_id, $area_id); //ecmoban模板堂 --zhuo
                }

                //商品存在规格 是货品 检查该货品库存
                if (is_spec($goods_attr) && !empty($prod)) {
                    if (!empty($goods_attr)) {
                        /* 取规格的货品库存 */
                        if ($goods_number > $product_info['product_number']) {
                            $url = "order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods";

                            echo '<a href="' . $url . '">' . $GLOBALS['_LANG']['goods_num_err'] . '</a>';


                            return false;
                        }
                    }
                }

                $other = [
                    'order_id' => $order_id,
                    'goods_id' => $goods_info['goods_id'],
                    'goods_name' => $goods_info['goods_name'],
                    'goods_sn' => $goods_info['goods_sn'],
                    'goods_number' => $goods_number,
                    'market_price' => $goods_info['market_price'],
                    'goods_price' => $goods_price,
                    'is_real' => $goods_info['is_real'],
                    'extension_code' => $goods_info['extension_code'],
                    'parent_id' => 0,
                    'is_gift' => 0,
                    'model_attr' => $model_attr,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'ru_id' => $goods_info['user_id']
                ];

                if ($goods_attr && is_spec($goods_attr) && !empty($prod)) {
                    /* 插入订单商品 */
                    $other['goods_attr'] = $goods_attr_value;
                    $other['product_id'] = $product_info['product_id'];
                    $other['goods_attr_id'] = implode(',', $goods_attr);
                }

                OrderGoods::insert($other);

                /* 如果使用库存，且下订单时减库存，则修改库存 */
                if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                    //ecmoban模板堂 --zhuo start
                    $model_inventory = get_table_date("goods", "goods_id = '$goods_id'", ['model_inventory'], 2);

                    //（货品）
                    if (!empty($product_info['product_id'])) {
                        if ($model_attr == 1) {
                            $sql = "UPDATE " . $this->dsc->table('products_warehouse') . "
                            SET product_number = product_number - " . $goods_number . "
                            WHERE product_id = " . $product_info['product_id'];
                        } elseif ($model_attr == 2) {
                            $sql = "UPDATE " . $this->dsc->table('products_area') . "
                            SET product_number = product_number - " . $goods_number . "
                            WHERE product_id = " . $product_info['product_id'];
                        } else {
                            $sql = "UPDATE " . $this->dsc->table('products') . "
                            SET product_number = product_number - " . $goods_number . "
                            WHERE product_id = " . $product_info['product_id'];
                        }
                    } else {
                        if ($model_inventory == 1) {
                            $sql = "UPDATE " . $this->dsc->table('warehouse_goods') . "
                            SET region_number = region_number - " . $goods_number . "
                            WHERE goods_id = '$goods_id' AND region_id = '$warehouse_id'";
                        } elseif ($model_inventory == 2) {
                            $sql = "UPDATE " . $this->dsc->table('warehouse_area_goods') . "
                            SET region_number = region_number - " . $goods_number . "
                            WHERE goods_id = '$goods_id' AND region_id = '$area_id'";
                        } else {
                            $sql = "UPDATE " . $this->dsc->table('goods') . "
                            SET goods_number = goods_number - " . $goods_number . "
                            WHERE goods_id = '$goods_id'";
                        }
                    }
                    //ecmoban模板堂 --zhuo end

                    $this->db->query($sql);
                }

                /* 更新商品总金额和订单总金额 */
                update_order($order_id, ['goods_amount' => order_amount($order_id)]);
                $this->update_order_amount($order_id);

                /* 更新 pay_log */
                update_pay_log($order_id);

                /* todo 记录日志 */
                $sn = $old_order['order_sn'];
                $new_order = order_info($order_id);
                if ($old_order['total_fee'] != $new_order['total_fee']) {
                    $sn .= ',' . sprintf($GLOBALS['_LANG']['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
                }
                admin_log($sn, 'edit', 'order');

                /* 跳回订单商品 */
                return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
            } /* 商品 */
            elseif ('goods' == $step) {
                /* 下一步 */
                if (isset($_POST['next'])) {
                    return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=consignee\n");
                } /* 完成 */
                elseif (isset($_POST['finish'])) {
                    /* 初始化提示信息和链接 */
                    $msgs = [];
                    $links = [];

                    /* 如果已付款，检查金额是否变动，并执行相应操作 */
                    $order = order_info($order_id);
                    $this->handle_order_money_change($order, $msgs, $links);

                    /* 显示提示信息 */
                    if (!empty($msgs)) {
                        return sys_msg(join(chr(13), $msgs), 0, $links);
                    } else {
                        /* 跳转到订单详情 */
                        return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                    }
                }
            } /* 保存收货人信息 */
            elseif ('consignee' == $step) {
                /* 保存订单 */
                $order = $_POST;
                $order['agency_id'] = get_agency_by_regions([$order['country'], $order['province'], $order['city'], $order['district']]);
                update_order($order_id, $order);

                /* 该订单所属办事处是否变化 */
                $agency_changed = $old_order['agency_id'] != $order['agency_id'];

                /* todo 记录日志 */
                $sn = $old_order['order_sn'];
                admin_log($sn, 'edit', 'order');

                if (isset($_POST['next'])) {
                    /* 下一步 */
                    if ($this->flowUserService->existRealGoods($order_id)) {
                        /* 存在实体商品，去配送方式 */
                        return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=shipping\n");
                    } else {
                        /* 不存在实体商品，去支付方式 */
                        return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=payment\n");
                    }
                } elseif (isset($_POST['finish'])) {
                    /* 如果是编辑且存在实体商品，检查收货人地区的改变是否影响原来选的配送 */
                    if ('edit' == $step_act && $this->flowUserService->existRealGoods($order_id)) {
                        $order = order_info($order_id);

                        $where = [
                            'order_id' => $order_id
                        ];
                        $shippinOrderInfo = $this->orderService->getOrderGoodsInfo($where);

                        /* 取得可用配送方式 */
                        $region_id_list = [
                            $order['country'], $order['province'], $order['city'], $order['district']
                        ];
                        $shipping_list = available_shipping_list($region_id_list, $shippinOrderInfo);

                        /* 判断订单的配送是否在可用配送之内 */
                        $exist = false;
                        foreach ($shipping_list as $shipping) {
                            if ($shipping['shipping_id'] == $order['shipping_id']) {
                                $exist = true;
                                break;
                            }
                        }

                        /* 如果不在可用配送之内，提示用户去修改配送 */
                        if (!$exist) {
                            // 修改配送为空，配送费和保价费为0
                            update_order($order_id, ['shipping_id' => 0, 'shipping_name' => '']);
                            $links[] = ['text' => $GLOBALS['_LANG']['step']['shipping'], 'href' => 'order.php?act=edit&order_id=' . $order_id . '&step=shipping'];
                            return sys_msg($GLOBALS['_LANG']['continue_shipping'], 1, $links);
                        }
                    }

                    /* 完成 */
                    if ($agency_changed) {
                        return dsc_header("Location: order.php?act=list\n");
                    } else {
                        return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                    }
                }
            } /* 保存配送信息 */
            elseif ('shipping' == $step) {
                /* 如果不存在实体商品，退出 */
                if (!$old_order['is_zc_order']) {
                    if (!$this->flowUserService->existRealGoods($order_id)) {
                        die('Hacking Attemp');
                    }
                }

                /* 取得订单信息 */
                $orderInfo = order_info($order_id);

                /* 保存订单 */
                $shipping_id = isset($_POST['shipping']) && !empty($_POST['shipping']) ? intval($_POST['shipping']) : 0;
                $shipping = shipping_info($shipping_id);
                $shipping_name = $shipping['shipping_name'] ?? '';
                $shipping_code = $shipping['shipping_code'] ?? '';

                $order = [
                    'shipping_id' => $shipping_id,
                    'shipping_name' => addslashes($shipping_name),
                    'shipping_code' => $shipping_code
                ];

                update_order($order_id, $order);

                /* 清除首页缓存：发货单查询 */
                clear_cache_files('index.dwt');

                if (isset($_POST['next'])) {
                    /* 下一步 */
                    return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=payment\n");
                } elseif (isset($_POST['finish'])) {
                    /* 初始化提示信息和链接 */
                    $msgs = [];
                    $links = [];

                    /* 如果已付款，检查金额是否变动，并执行相应操作 */
                    $order = order_info($order_id);
                    $this->handle_order_money_change($order, $msgs, $links);

                    /* 如果是编辑且配送不支持货到付款且原支付方式是货到付款 */
                    if ('edit' == $step_act && $shipping['support_cod'] == 0) {
                        $payment = payment_info($order['pay_id']);
                        if ($payment['is_cod'] == 1) {
                            /* 修改支付为空 */
                            update_order($order_id, ['pay_id' => 0, 'pay_name' => '']);
                            $msgs[] = $GLOBALS['_LANG']['continue_payment'];
                            $links[] = ['text' => $GLOBALS['_LANG']['step']['payment'], 'href' => 'order.php?act=' . $step_act . '&order_id=' . $order_id . '&step=payment'];
                        }
                    }

                    /* 显示提示信息 */
                    if (!empty($msgs)) {
                        return sys_msg(join(chr(13), $msgs), 0, $links);
                    } else {
                        if ($shipping_id != $orderInfo['shipping_id']) {
                            $order_note = sprintf($GLOBALS['_LANG']['update_shipping'], $orderInfo['shipping_name'], $shipping_name);
                            order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], $order_note, session('seller_name'), 0, gmtime());
                        }

                        /* 完成 */
                        return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                    }
                }
            } /* 保存支付信息 */
            elseif ('payment' == $step) {
                /* 取得支付信息 */
                $pay_id = $_POST['payment'];
                $payment = payment_info($pay_id);

                /* 计算支付费用 */
                $order_amount = order_amount($order_id);
                if ($payment['is_cod'] == 1) {
                    $order = order_info($order_id);
                    $region_id_list = [
                        $order['country'], $order['province'], $order['city'], $order['district']
                    ];
                    $shipping = shipping_info($order['shipping_id']);
                    $pay_fee = pay_fee($pay_id, $order_amount, $shipping['pay_fee']);
                } else {
                    $pay_fee = pay_fee($pay_id, $order_amount);
                }

                /* 保存订单 */
                $order = [
                    'pay_id' => $pay_id,
                    'pay_name' => addslashes($payment['pay_name']),
                    'pay_fee' => $pay_fee
                ];
                update_order($order_id, $order);
                $this->update_order_amount($order_id);

                /* 更新 pay_log */
                update_pay_log($order_id);

                /* todo 记录日志 */
                $sn = $old_order['order_sn'];
                $new_order = order_info($order_id);
                if ($old_order['total_fee'] != $new_order['total_fee']) {
                    $sn .= ',' . sprintf($GLOBALS['_LANG']['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
                }
                admin_log($sn, 'edit', 'order');

                if (isset($_POST['next'])) {
                    /* 下一步 */
                    return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=other\n");
                } elseif (isset($_POST['finish'])) {
                    /* 初始化提示信息和链接 */
                    $msgs = [];
                    $links = [];

                    /* 如果已付款，检查金额是否变动，并执行相应操作 */
                    $order = order_info($order_id);
                    $this->handle_order_money_change($order, $msgs, $links);

                    /* 显示提示信息 */
                    if (!empty($msgs)) {
                        return sys_msg(join(chr(13), $msgs), 0, $links);
                    } else {
                        /* 完成 */
                        return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                    }
                }
            } elseif ('other' == $step) {
                /* 保存订单 */
                $order = [];
                if (isset($_POST['pack']) && $_POST['pack'] > 0) {
                    $pack = pack_info($_POST['pack']);
                    $order['pack_id'] = $pack['pack_id'];
                    $order['pack_name'] = addslashes($pack['pack_name']);
                    $order['pack_fee'] = $pack['pack_fee'];
                } else {
                    $order['pack_id'] = 0;
                    $order['pack_name'] = '';
                    $order['pack_fee'] = 0;
                }
                if (isset($_POST['card']) && $_POST['card'] > 0) {
                    $card = card_info($_POST['card']);
                    $order['card_id'] = $card['card_id'];
                    $order['card_name'] = addslashes($card['card_name']);
                    $order['card_fee'] = $card['card_fee'];
                    $order['card_message'] = $_POST['card_message'];
                } else {
                    $order['card_id'] = 0;
                    $order['card_name'] = '';
                    $order['card_fee'] = 0;
                    $order['card_message'] = '';
                }
                $order['inv_type'] = $_POST['inv_type'];
                $order['inv_payee'] = $_POST['inv_payee'];
                $order['inv_content'] = $_POST['inv_content'];
                $order['how_oos'] = $_POST['how_oos'];
                $order['postscript'] = $_POST['postscript'];
                $order['to_buyer'] = $_POST['to_buyer'];
                update_order($order_id, $order);
                $this->update_order_amount($order_id);

                /* 更新 pay_log */
                update_pay_log($order_id);

                /* todo 记录日志 */
                $sn = $old_order['order_sn'];
                admin_log($sn, 'edit', 'order');

                if (isset($_POST['next'])) {
                    /* 下一步 */
                    return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=money\n");
                } elseif (isset($_POST['finish'])) {
                    /* 完成 */
                    return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                }
            } elseif ('money' == $step) {

                /* 取得订单信息 */
                $old_order = order_info($order_id, '', $adminru['ru_id']);

                if (empty($old_order)) {
                    return dsc_header("Location: order.php?act=list" . "\n");
                }

                if ($old_order['user_id'] > 0) {

                    /* 取得用户信息 */
                    $where = [
                        'user_id' => $old_order['user_id']
                    ];
                    $user_info = $this->userService->userInfo($where);
                }

                /* 保存信息 */
                $order['goods_amount'] = $old_order['goods_amount'];
                $order['discount'] = isset($_POST['discount']) && floatval($_POST['discount']) >= 0 ? round(floatval($_POST['discount']), 2) : 0;
                $order['tax'] = isset($_POST['tax']) ? round(floatval($_POST['tax']), 2) : 0;
                $order['shipping_fee'] = isset($_POST['shipping_fee']) && floatval($_POST['shipping_fee']) >= 0 ? round(floatval($_POST['shipping_fee']), 2) : 0;
                $order['insure_fee'] = isset($_POST['insure_fee']) && floatval($_POST['insure_fee']) >= 0 ? round(floatval($_POST['insure_fee']), 2) : 0;
                $order['pay_fee'] = isset($_POST['pay_fee']) && floatval($_POST['pay_fee']) >= 0 ? round(floatval($_POST['pay_fee']), 2) : 0;
                $order['pack_fee'] = isset($_POST['pack_fee']) && floatval($_POST['pack_fee']) >= 0 ? round(floatval($_POST['pack_fee']), 2) : 0;
                $order['card_fee'] = isset($_POST['card_fee']) && floatval($_POST['card_fee']) >= 0 ? round(floatval($_POST['card_fee']), 2) : 0;
                $order['coupons'] = isset($_POST['coupons']) && floatval($_POST['coupons']) >= 0 ? round(floatval($_POST['coupons']), 2) : 0;

                $order['money_paid'] = $old_order['money_paid'];
                $order['surplus'] = 0;
                $order['integral'] = isset($_POST['integral']) && intval($_POST['integral']) >= 0 ? intval($_POST['integral']) : 0;
                $order['integral_money'] = 0;
                $order['bonus'] = isset($_POST['bonus']) && floatval($_POST['bonus']) >= 0 ? round(floatval($_POST['bonus']), 2) : 0;
                $order['bonus_id'] = 0;
                $order['bonus'] = isset($_POST['bonus']) && floatval($_POST['bonus']) >= 0 ? round(floatval($_POST['bonus']), 2) : 0;
                $_POST['bonus_id'] = isset($_POST['bonus_id']) && !empty($_POST['bonus_id']) ? intval($_POST['bonus_id']) : 0;

                /* 计算待付款金额 */
                $order['order_amount'] = $order['goods_amount']
                    + $order['tax']
                    + $order['shipping_fee']
                    + $order['insure_fee']
                    + $order['pay_fee']
                    + $order['pack_fee']
                    + $order['card_fee']
                    - $order['discount'];

                $money_paid = 0;
                $order_amount = 0;
                if ($order['order_amount'] > 0) { //0
                    $is_coupons = 0;
                    /* 检测优惠券金额是否大于待付款金额 start */
                    if ($order['coupons'] > $order['order_amount']) {
                        $order['coupons'] = $order['order_amount'];

                        $is_coupons = 1;
                    }
                    /* 检测优惠券金额是否大于待付款金额 end */

                    $order['order_amount'] = $order['order_amount'] - ($order['coupons'] + $old_order['use_val']);
                    $order_amount = $order['order_amount'];

                    if ($order['order_amount'] > 0) { //3
                        $order['order_amount'] -= $order['money_paid'];

                        if ($order['order_amount'] > 0) { //2

                            //1
                            if ($old_order['user_id'] > 0) {
                                /* 如果选择了红包，先使用红包支付 */
                                if ($_POST['bonus_id'] > 0 && !isset($_POST['bonus'])) {
                                    /* todo 检查红包是否可用 */
                                    $order['bonus_id'] = $_POST['bonus_id'];
                                    $bonus = bonus_info($_POST['bonus_id']);
                                    $order['bonus'] = $bonus['type_money'];

                                    $order['order_amount'] -= $order['bonus'];
                                }

                                /* 使用红包之后待付款金额仍大于0 */
                                if ($order['order_amount'] > 0) {
                                    if ($old_order['extension_code'] != 'exchange_goods') {
                                        /* 如果设置了积分，再使用积分支付 */
                                        if (isset($_POST['integral']) && intval($_POST['integral']) > 0) {
                                            /* 检查积分是否足够 */
                                            $order['integral'] = intval($_POST['integral']);
                                            $order['integral_money'] = $this->dscRepository->valueOfIntegral($order['integral']);
                                            if ($old_order['integral'] + $user_info['pay_points'] < $order['integral']) {
                                                return sys_msg($GLOBALS['_LANG']['pay_points_not_enough']);
                                            }

                                            $order['order_amount'] -= $order['integral_money'];
                                        }
                                    } else {
                                        if (intval($_POST['integral']) > $user_info['pay_points'] + $old_order['integral']) {
                                            return sys_msg($GLOBALS['_LANG']['pay_points_not_enough']);
                                        }
                                    }
                                    if ($order['order_amount'] > 0) {
                                        /* 如果设置了余额，再使用余额支付 */
                                        if (isset($_POST['surplus']) && floatval($_POST['surplus']) >= 0) {
                                            /* 检查余额是否足够 */
                                            $order['surplus'] = round(floatval($_POST['surplus']), 2);
                                            if ($old_order['surplus'] + $user_info['user_money'] + $user_info['credit_line'] < $order['surplus']) {
                                                return sys_msg($GLOBALS['_LANG']['user_money_not_enough']);
                                            }

                                            /* 如果红包和积分和余额足以支付，把待付款金额改为0，退回部分积分余额 */
                                            $order['order_amount'] -= $order['surplus'];
                                            if ($order['order_amount'] < 0) {
                                                $order['surplus'] += $order['order_amount'];
                                                $order['order_amount'] = 0;
                                            }
                                        }
                                    } else {
                                        /* 如果红包和积分足以支付，把待付款金额改为0，退回部分积分 */
                                        $order['integral_money'] += $order['order_amount'];
                                        $order['integral'] = $this->dscRepository->integralOfValue($order['integral_money']);
                                        $order['order_amount'] = 0;
                                    }
                                } else {
                                    /* 如果红包足以支付，把待付款金额设为0 */
                                    $order['order_amount'] = 0;
                                }
                            }

                            $return_type = 1;
                        } else {
                            if ($order['money_paid'] > 0) {
                                $money_paid = $order_amount - $old_order['integral_money'];
                                $order_amount = $order['money_paid'] - $money_paid;

                                if ($order_amount >= 0) {
                                    $order_amount += $old_order['surplus'];
                                    $order['surplus'] = 0;
                                } else {
                                    $order['surplus'] = $old_order['surplus'];
                                }

                                $order['integral'] = $old_order['integral'];
                                $order['integral_money'] = $old_order['integral_money'];
                            } else {
                                $order['coupons'] = $old_order['coupons'];
                            }

                            $return_type = 2;
                        }
                    } else {
                        if ($is_coupons == 1) {
                            $order['order_amount'] = (-1) * ($old_order['surplus'] + $old_order['money_paid']);
                            $order['surplus'] = 0;
                            $order['money_paid'] = 0;
                            $order['integral'] = 0;
                            $order['integral_money'] = 0;
                        } else {
                            $order['order_amount'] = (-1) * ($old_order['surplus'] + $old_order['money_paid'] + $order['order_amount']);
                            $order['surplus'] = $order['surplus'] + $order['order_amount'];

                            if ($order['coupons'] > 0 && $order['coupons'] < $old_order['coupons']) {
                                $order['coupons'] = $old_order['coupons'] - $order['coupons'];
                            }

                            $order['integral_money'] = $old_order['integral_money'];
                        }

                        $return_type = 3;
                    }
                } else {
                    $return_type = 0;
                }

                if ($order['order_amount'] <= 0) {
                    if (($old_order['surplus'] + $old_order['money_paid']) > 0) {
                        if ($return_type == 1) {
                            if ($old_order['surplus'] - $order['surplus'] > 0) {
                                $order['order_amount'] = (-1) * ($old_order['surplus'] - $order['surplus']);
                            } else {
                                $order['order_amount'] = 0;
                            }
                        } elseif ($return_type == 2) {
                            if ($order_amount > 0) {
                                $order['order_amount'] = (-1) * $order_amount;
                            } else {
                                $order['order_amount'] = 0;
                            }

                            $order['money_paid'] = $money_paid;
                            $order['bonus'] = $old_order['bonus'];
                        } elseif ($return_type == 3) {
                            $order['bonus'] = $old_order['bonus'];
                        } else {
                            $order['order_amount'] = (-1) * ($old_order['surplus'] + $old_order['money_paid'] - $old_order['coupons'] - $old_order['use_val'] - $old_order['integral_money'] - $old_order['bonus']);
                            $order['surplus'] = 0;
                            $order['money_paid'] = 0;

                            $order['coupons'] = 0;
                            $order['bonus'] = 0;
                            $order['integral'] = 0;
                            $order['integral_money'] = 0;
                        }
                    } else {
                        if ($order['integral_money'] <= 0) {
                            $order['integral'] = 0;
                        }
                    }
                }

                if ($order['bonus_id'] == 0) {
                    $order['bonus'] = 0;
                }

                if ($order['order_amount'] == 0) {
                    $order['order_amount'] = 0;

                    $order['order_status'] = OS_CONFIRMED;
                    $order['shipping_status'] = !empty($old_order['shipping_status']) ? $old_order['shipping_status'] : SS_UNSHIPPED;
                    $order['pay_status'] = PS_PAYED;
                }

                $order_amount = $order['goods_amount'] + $order['tax'] + $order['shipping_fee'] + $order['insure_fee'] + $order['pay_fee'] + $order['pack_fee'] + $order['card_fee'];

                $activity_amount = ($order['money_paid']) + $order['surplus'] + $order['coupons'] + $order['integral_money'] + $old_order['use_val'] + $old_order['discount'] + $order['bonus'];

                if ($activity_amount > $order_amount && ($order['bonus'] > 0 && $order['bonus'] > $order['order_amount'] && $order['order_amount'] < 0)) {
                    $order['bonus'] = $order['bonus'] + $order['order_amount'];
                }

                $stages_qishu = $old_order['get_order_goods']['stages_qishu'] ?? 0;
                if ($stages_qishu > 0) {

                    $stagesInfo = $this->userBaitiaoService->getStagesInfo(['order_sn' => $old_order['order_sn']]);

                    //获取该白条分期订单的总价;
                    $shop_price_total = $order['order_amount'];

                    if ($stages_qishu == 1) {
                        $stages_one_price = $shop_price_total;
                    } else {
                        //计算每期价格(每期价格=总价*费率+总价/期数);
                        $stages_one_price = round(($shop_price_total * ($stagesInfo['stages_rate'] / 100)) + ($shop_price_total / $stages_qishu), 2);
                    }

                    Stages::where('order_sn', $old_order['order_sn'])
                        ->where('yes_num', 0)
                        ->update([
                            'stages_one_price' => $stages_one_price
                        ]);

                    BaitiaoLog::where('order_id', $old_order['order_id'])
                        ->where('is_repay', 0)
                        ->update([
                            'stages_one_price' => $stages_one_price
                        ]);
                }

                update_order($order_id, $order);

                /* 更新 pay_log */
                update_pay_log($order_id);

                /* todo 记录日志 */
                $sn = $old_order['order_sn'];
                $new_order = order_info($order_id);
                if ($old_order['total_fee'] != $new_order['total_fee']) {
                    //如果是编辑订单，且金额发生变化时，重新生成订单编号,防止微信支付失败
                    if ($step_act == 'edit') {
                        $new_order_sn = correct_order_sn($old_order['order_sn']);
                        $sn = $new_order_sn;
                        $old_order['order_sn'] = $new_order_sn;
                    }
                    $sn .= ',' . sprintf($GLOBALS['_LANG']['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
                }
                admin_log($sn, 'edit', 'order');

                /* 如果余额、积分、红包有变化，做相应更新 */
                if ($old_order['user_id'] > 0) {
                    $user_money_change = $old_order['surplus'] - $order['surplus'];
                    if ($user_money_change != 0) {
                        log_account_change($user_info['user_id'], $user_money_change, 0, 0, 0, sprintf($GLOBALS['_LANG']['change_use_surplus'], $old_order['order_sn']));
                    }

                    $pay_points_change = $old_order['integral'] - $order['integral'];
                    if ($pay_points_change != 0) {
                        log_account_change($user_info['user_id'], 0, 0, 0, $pay_points_change, sprintf($GLOBALS['_LANG']['change_use_integral'], $old_order['order_sn']));
                    }

                    if ($old_order['bonus_id'] != $order['bonus_id']) {
                        if ($old_order['bonus_id'] > 0) {
                            $sql = "UPDATE " . $this->dsc->table('user_bonus') .
                                " SET used_time = 0, order_id = 0 " .
                                "WHERE bonus_id = '$old_order[bonus_id]' LIMIT 1";
                            $this->db->query($sql);
                        }

                        if ($order['bonus_id'] > 0) {
                            $sql = "UPDATE " . $this->dsc->table('user_bonus') .
                                " SET used_time = '" . gmtime() . "', order_id = '$order_id' " .
                                "WHERE bonus_id = '$order[bonus_id]' LIMIT 1";
                            $this->db->query($sql);
                        }
                    }
                }

                if (isset($_POST['finish'])) {
                    /* 完成 */
                    if ($step_act == 'add') {
                        /* 订单改为已确认，（已付款） */
                        $arr['order_status'] = OS_CONFIRMED;
                        $arr['confirm_time'] = gmtime();
                        if ($order['order_amount'] <= 0) {
                            $arr['pay_status'] = PS_PAYED;
                            $arr['pay_time'] = gmtime();
                        }
                        update_order($order_id, $arr);
                    }

                    /* 初始化提示信息和链接 */
                    $msgs = [];
                    $links = [];

                    /* 如果已付款，检查金额是否变动，并执行相应操作 */
                    $order = order_info($order_id);
                    $this->handle_order_money_change($order, $msgs, $links);

                    if ($step_act == 'add') {
                        /* 记录log */
                        $action_note = sprintf(lang('seller/order.add_order_info'), session('seller_name'));
                        order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], $action_note, session('seller_name'));
                    }

                    /* 显示提示信息 */
                    if (!empty($msgs)) {
                        return sys_msg(join(chr(13), $msgs), 0, $links);
                    } else {
                        return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                    }
                }
            } /* 保存发货后的配送方式和发货单号 */
            elseif ('invoice' == $step) {
                /* 如果不存在实体商品，退出 */
                if (!$old_order['is_zc_order']) {
                    if (!$this->flowUserService->existRealGoods($order_id)) {
                        return 'Hacking Attemp';
                    }
                }

                /* 保存订单 */
                $shipping_id = intval($_POST['shipping']);
                $shipping = shipping_info($shipping_id);
                $invoice_no = trim($_POST['invoice_no']);
                $invoice_no = str_replace(',', ',', $invoice_no);
                $order = [
                    'shipping_id' => $shipping_id,
                    'shipping_name' => addslashes($shipping['shipping_name']),
                    'invoice_no' => $invoice_no
                ];
                update_order($order_id, $order);

                /* todo 记录日志 */
                $sn = $old_order['order_sn'];
                admin_log($sn, 'edit', 'order');

                if (isset($_POST['finish'])) {
                    return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                }
            }
        } /**
         * 修改退换货订单  by Leah
         */ elseif ($_REQUEST['act'] == 'return_edit') {
            load_helper('transaction');
            /* 检查权限 */
            admin_priv('order_edit');
            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
            $ret_id = isset($_GET['ret_id']) ? intval($_GET['ret_id']) : 0;

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '12_back_apply']);

            /* 取得参数 step */
            $step_list = ['user', 'goods', 'consignee', 'back_shipping', 'payment', 'other', 'money'];
            $step = isset($_GET['step']) && in_array($_GET['step'], $step_list) ? $_GET['step'] : 'user';
            $this->smarty->assign('step', $step);

            /* 取得参数 act */
            $act = $_GET['act'];
            $this->smarty->assign('ur_here', lang('seller/order.add_order'));
            $this->smarty->assign('step_act', $act);
            $this->smarty->assign('step', $act);

            $order = order_info($order_id);

            /* 取得订单信息 */
            if ($order_id > 0) {
                $return = get_return_detail($ret_id);
                $this->smarty->assign('return', $return);
                $this->smarty->assign('order', $order);
            }

            $where = [
                'order_id' => $order_id
            ];
            $shippinOrderInfo = $this->orderService->getOrderGoodsInfo($where);

            // 选择配送方式
            if ('back_shipping' == $step) {
                /* 取得可用的配送方式列表 */
                $region_id_list = [
                    $order['country'], $order['province'], $order['city'], $order['district']
                ];
                $shipping_list = available_shipping_list($region_id_list, $shippinOrderInfo);

                /* 取得配送费用 */
                $total = order_weight_price($order_id);
                foreach ($shipping_list as $key => $shipping) {
                    $shipping_fee = $this->dscRepository->shippingFee($shipping['shipping_code'], unserialize($shipping['configure']), $total['weight'], $total['amount'], $total['number']); //计算运费
                    $free_price = free_price($shipping['configure']);   //免费额度
                    $shipping_list[$key]['shipping_fee'] = $shipping_fee;
                    $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee);
                    $shipping_list[$key]['free_money'] = price_format($free_price['configure']['free_money']);
                }
                $this->smarty->assign('shipping_list', $shipping_list);
            }

            /* 显示模板 */

            return $this->smarty->display('order_step.dwt');
        } /**
         * 修改退换货订单快递信息
         * by leah
         */ elseif ($_REQUEST['act'] == 'edit_shipping') {
            $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
            $ret_id = isset($_REQUEST['ret_id']) ? intval($_REQUEST['ret_id']) : 0;
            $rec_id = isset($_REQUEST['rec_id']) ? intval($_REQUEST['rec_id']) : 0;

            $shipping_id = isset($_REQUEST['shipping']) ? intval($_REQUEST['shipping']) : 0;
            $invoice_no = isset($_REQUEST['invoice_no']) ? $_REQUEST['invoice_no'] : '';


            $this->db->query("UPDATE " . $this->dsc->table('order_return') . " SET out_shipping_name = '$shipping_id' , out_invoice_no ='$invoice_no'" .
                "WHERE ret_id = '$ret_id'");

            $links[] = ['text' => lang('seller/order.return_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=return_info&ret_id=' . $ret_id . 'rec_id=' . $rec_id];
            return sys_msg(lang('seller/order.act_ok'), 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 修改订单（载�        �页面）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('order_edit');

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '08_add_order']);
            $this->smarty->assign('current', '08_add_order');
            /* 取得参数 order_id */
            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
            $this->smarty->assign('order_id', $order_id);

            /* 取得参数 step */
            $step_list = ['user', 'goods', 'consignee', 'shipping', 'payment', 'other', 'money'];
            $step = isset($_GET['step']) && in_array($_GET['step'], $step_list) ? $_GET['step'] : 'user';
            $this->smarty->assign('step', $step);

            $warehouse_list = get_warehouse_list_goods();
            $this->smarty->assign('warehouse_list', $warehouse_list); //仓库列表

            /* 取得参数 act */
            $act = $_GET['act'];
            $this->smarty->assign('ur_here', lang('seller/order.add_order'));
            $this->smarty->assign('step_act', $act);

            /* 取得订单信息 */
            if ($order_id > 0) {
                $order = order_info($order_id);

                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '$order_id'";
                $goods_count = $this->db->getOne($sql);

                if ($goods_count > 0) {
                    if ($order['ru_id'] != $adminru['ru_id']) {
                        $Loaction = "order.php?act=list";
                        return dsc_header("Location: $Loaction\n");
                    }
                }

                if ($order['invoice_type'] == 1) {
                    $user_id = $order['user_id'];
                    $sql = " SELECT * FROM " . $this->dsc->table('users_vat_invoices_info') . " WHERE user_id = '$user_id' LIMIT 1";
                    $res = $this->db->getRow($sql);
                    $this->smarty->assign('vat_info', $res);
                }

                /* 发货单格式化 */
                $order['invoice_no'] = str_replace('<br>', ',', $order['invoice_no']);

                /* 如果已发货，就不能修改订单了（配送方式和发货单号除外） */
                if ($order['shipping_status'] == SS_SHIPPED || $order['shipping_status'] == SS_RECEIVED) {
                    if ($step != 'shipping') {
                        return sys_msg($GLOBALS['_LANG']['cannot_edit_order_shipped']);
                    } else {
                        if ($order['invoice_no']) {
                            return sys_msg($GLOBALS['_LANG']['cannot_edit_order_shipped']);
                        }

                        $step = 'invoice';
                        $this->smarty->assign('step', $step);
                    }
                }

                if ($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYED_PART) {
                    if ($step == 'payment') {
                        return sys_msg($GLOBALS['_LANG']['cannot_edit_order_payed']);
                    }
                }

                $this->smarty->assign('order', $order);
            } else {
                if ($act != 'add' || $step != 'user') {
                    return 'invalid params';
                }
            }

            /* 选择会员 */
            if ('user' == $step) {
                // 无操作
            } /* 增删改商品 */
            elseif ('goods' == $step) {
                /* 取得订单商品 */
                $goods_list = order_goods($order_id);
                if (!empty($goods_list)) {
                    foreach ($goods_list as $key => $goods) {
                        /* 计算属性数 */
                        $attr = $goods['goods_attr'];
                        if ($attr == '') {
                            $goods_list[$key]['rows'] = 1;
                        } else {
                            $goods_list[$key]['rows'] = count(explode(chr(13), $attr));
                        }
                    }
                }

                $this->smarty->assign('goods_list', $goods_list);

                /* 取得商品总金额 */
                $this->smarty->assign('goods_amount', order_amount($order_id));
            } // 设置收货人
            elseif ('consignee' == $step) {
                $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['14_user_address_edit']);
                /* 查询是否存在实体商品 */
                $exist_real_goods = $this->flowUserService->existRealGoods($order_id);
                $this->smarty->assign('exist_real_goods', $exist_real_goods);

                /* 取得收货地址列表 */
                if ($order['user_id'] > 0) {
                    $this->smarty->assign('address_list', address_list($order['user_id']));

                    $address_id = isset($_REQUEST['address_id']) ? intval($_REQUEST['address_id']) : 0;
                    if ($address_id > 0) {
                        $address = address_info($address_id);
                        if ($address) {
                            $order['consignee'] = $address['consignee'];
                            $order['country'] = $address['country'];
                            $order['province'] = $address['province'];
                            $order['city'] = $address['city'];
                            $order['district'] = $address['district'];
                            $order['street'] = $address['street'];
                            $order['email'] = $address['email'];
                            $order['address'] = $address['address'];
                            $order['zipcode'] = $address['zipcode'];
                            $order['tel'] = $address['tel'];
                            $order['mobile'] = $address['mobile'];
                            $order['sign_building'] = $address['sign_building'];
                            $order['best_time'] = $address['best_time'];
                            $this->smarty->assign('order', $order);
                        }
                    }
                }

                if ($exist_real_goods) {
                    /* 取得国家 */
                    $this->smarty->assign('country_list', get_regions());
                    if ($order['country'] > 0) {
                        /* 取得省份 */
                        $this->smarty->assign('province_list', get_regions(1, $order['country']));
                        if ($order['province'] > 0) {
                            /* 取得城市 */
                            $this->smarty->assign('city_list', get_regions(2, $order['province']));
                            if ($order['city'] > 0) {
                                /* 取得区域 */
                                $this->smarty->assign('district_list', get_regions(3, $order['city']));
                                if ($order['district'] > 0) {
                                    /* 取得街道 */
                                    $this->smarty->assign('street_list', get_regions(4, $order['district']));
                                }
                            }
                        }
                    }
                }
            } // 选择配送方式
            elseif ('shipping' == $step) {
                /* 如果不存在实体商品 */
                if (!$order['is_zc_order']) {
                    if (!$this->flowUserService->existRealGoods($order_id)) {
                        return 'Hacking Attemp';
                    }
                }

                $where = [
                    'order_id' => $order_id
                ];
                $shippinOrderInfo = $this->orderService->getOrderGoodsInfo($where);

                /* 取得可用的配送方式列表 */
                $region_id_list = [
                    $order['country'], $order['province'], $order['city'], $order['district'], $order['street']
                ];
                $shipping_list = available_shipping_list($region_id_list, $shippinOrderInfo);

                $consignee = [
                    'country' => $order['country'],
                    'province' => $order['province'],
                    'city' => $order['city'],
                    'district' => $order['district']
                ];

                $goods_list = order_goods($order_id);
                $cart_goods = $goods_list;

                $shipping_fee = 0;
                /* 取得配送费用 */
                foreach ($shipping_list as $key => $val) {
                    if (substr($val['shipping_code'], 0, 5) != 'ship_') {
                        if ($GLOBALS['_CFG']['freight_model'] == 0) {
                            $configure_value = '';
                            /* 商品单独设置运费价格 start */
                            if ($cart_goods) {
                                if (count($cart_goods) == 1) {
                                    $cart_goods = array_values($cart_goods);
                                    if (!empty($cart_goods[0]['freight']) && $cart_goods[0]['is_shipping'] == 0) {
                                        if ($cart_goods[0]['freight'] == 1) {
                                            $configure_value = $cart_goods[0]['shipping_fee'] * $cart_goods[0]['goods_number'];
                                        } else {
                                            $trow = get_goods_transport($cart_goods[0]['tid']);

                                            if ($trow['freight_type']) {
                                                $cart_goods[0]['user_id'] = $cart_goods[0]['ru_id'];
                                                $transport_tpl = get_goods_transport_tpl($cart_goods[0], $region_id_list, $val, $cart_goods[0]['goods_number']);

                                                $configure_value = isset($transport_tpl['shippingFee']) ? $transport_tpl['shippingFee'] : 0;
                                            } else {

                                                /**
                                                 * 商品运费模板
                                                 * 自定义
                                                 */
                                                $custom_shipping = $this->orderTransportService->getGoodsCustomShipping($cart_goods);

                                                /* 运费模板配送方式 start */
                                                $transport = ['top_area_id', 'area_id', 'tid', 'ru_id', 'sprice'];
                                                $goods_transport = GoodsTransportExtend::select($transport)
                                                    ->where('ru_id', $cart_goods[0]['ru_id'])
                                                    ->where('tid', $cart_goods[0]['tid']);

                                                $goods_transport = $goods_transport->whereRaw("(FIND_IN_SET('" . $consignee['city'] . "', area_id))");

                                                $goods_transport = $goods_transport->first();

                                                $goods_transport = $goods_transport ? $goods_transport->toArray() : [];
                                                /* 运费模板配送方式 end */

                                                /* 运费模板配送方式 start */
                                                $ship_transport = ['tid', 'ru_id', 'shipping_fee'];
                                                $goods_ship_transport = GoodsTransportExpress::select($ship_transport)
                                                    ->where('ru_id', $cart_goods[0]['ru_id'])
                                                    ->where('tid', $cart_goods[0]['tid']);

                                                $goods_ship_transport = $goods_ship_transport->whereRaw("(FIND_IN_SET('" . $val['shipping_id'] . "', shipping_id))");

                                                $goods_ship_transport = $goods_ship_transport->first();

                                                $goods_ship_transport = $goods_ship_transport ? $goods_ship_transport->toArray() : [];
                                                /* 运费模板配送方式 end */

                                                $goods_transport['sprice'] = isset($goods_transport['sprice']) ? $goods_transport['sprice'] : 0;
                                                $goods_ship_transport['shipping_fee'] = isset($goods_ship_transport['shipping_fee']) ? $goods_ship_transport['shipping_fee'] : 0;

                                                /* 是否免运费 start */
                                                if ($custom_shipping && $custom_shipping[$cart_goods[0]['tid']]['amount'] >= $trow['free_money'] && $trow['free_money'] > 0) {
                                                    $is_shipping = 1; /* 免运费 */
                                                } else {
                                                    $is_shipping = 0; /* 有运费 */
                                                }
                                                /* 是否免运费 end */
                                                if ($is_shipping == 0) {
                                                    if ($trow['type'] == 1) {
                                                        $configure_value = $goods_transport['sprice'] * $cart_goods[0]['goods_number'] + $goods_ship_transport['shipping_fee'] * $cart_goods[0]['goods_number'];
                                                    } else {
                                                        $configure_value = $goods_transport['sprice'] + $goods_ship_transport['shipping_fee'];
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        /* 有配送按配送区域计算运费 */
                                        $configure_type = 1;
                                    }
                                } else {
                                    $order_transpor = $this->orderTransportService->getOrderTransport($cart_goods, $consignee, $val['shipping_id'], $val['shipping_code']);

                                    if ($order_transpor['freight']) {
                                        /* 有配送按配送区域计算运费 */
                                        $configure_type = 1;
                                    }

                                    $configure_value = isset($order_transpor['sprice']) ? $order_transpor['sprice'] : 0;
                                }
                            }
                            /* 商品单独设置运费价格 end */

                            $shipping_fee = $configure_value;
                        }

                        $shipping_cfg = unserialize_config($val['configure']);

                        $shipping_list[$key]['shipping_id'] = $val['shipping_id'];
                        $shipping_list[$key]['shipping_name'] = $val['shipping_name'];
                        $shipping_list[$key]['shipping_code'] = $val['shipping_code'];
                        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
                        $shipping_list[$key]['shipping_fee'] = $shipping_fee;
                        $shipping_list[$key]['insure_formated'] = empty($val['insure']) ? '' : (isset($val['insure']) && strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure']);
                        $shipping_list[$key]['format_free_money'] = empty($shipping_cfg['free_money']) ? '' : price_format($shipping_cfg['free_money'], false);
                        $shipping_list[$key]['free_money'] = $shipping_cfg['free_money'] ?? '';

                        /* 当前的配送方式是否支持保价 */
                        $insure_disabled = false;
                        $cod_disabled = false;
                        if ($val['shipping_id'] == $order['shipping_id']) {
                            $insure_disabled = ($val['insure'] == 0);
                            $cod_disabled = ($val['support_cod'] == 0);
                        }

                        $shipping_list[$key]['insure_disabled'] = $insure_disabled;
                        $shipping_list[$key]['cod_disabled'] = $cod_disabled;
                    }

                    // 兼容过滤ecjia配送方式
                    if (substr($val['shipping_code'], 0, 5) == 'ship_') {
                        unset($shipping_list[$key]);
                    }
                }

                $this->smarty->assign('shipping_list', $shipping_list);
            } // 选择支付方式
            elseif ('payment' == $step) {
                $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['013_payment_edit']);
                /* 取得可用的支付方式列表 */
                if ($this->flowUserService->existRealGoods($order_id)) {
                    /* 存在实体商品 */
                    $region_id_list = [
                        $order['country'], $order['province'], $order['city'], $order['district'], $order['street']
                    ];
                    $shipping_area = shipping_info($order['shipping_id']);
                    $pay_fee = ($shipping_area['support_cod'] == 1) ? $shipping_area['pay_fee'] : 0;

                    $payment_list = available_payment_list($shipping_area['support_cod'], $pay_fee);
                } else {
                    /* 不存在实体商品 */
                    $payment_list = available_payment_list(false);
                }

                /* 过滤掉使用余额支付 */
                foreach ($payment_list as $key => $payment) {
                    if ($payment['pay_code'] == 'balance') {
                        unset($payment_list[$key]);
                    }
                }
                $this->smarty->assign('payment_list', $payment_list);
            } // 选择包装、贺卡
            elseif ('other' == $step) {
                $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['013_fapiao_edit']);
                /* 查询是否存在实体商品 */
                $exist_real_goods = $this->flowUserService->existRealGoods($order_id);
                $this->smarty->assign('exist_real_goods', $exist_real_goods);

                if ($exist_real_goods) {
                    /* 取得包装列表 */
                    $this->smarty->assign('pack_list', pack_list());

                    /* 取得贺卡列表 */
                    $this->smarty->assign('card_list', card_list());
                }
            } // 费用
            elseif ('money' == $step) {
                /* 查询是否存在实体商品 */
                $exist_real_goods = $this->flowUserService->existRealGoods($order_id);
                $this->smarty->assign('exist_real_goods', $exist_real_goods);

                /* 取得用户信息 */
                if ($order['user_id'] > 0) {
                    $where = [
                        'user_id' => $order['user_id']
                    ];
                    $user_info = $this->userService->userInfo($where);

                    /* 计算可用余额 */
                    $this->smarty->assign('available_user_money', $order['surplus'] + $user_info['user_money']);

                    /* 计算可用积分 */
                    $this->smarty->assign('available_pay_points', $order['integral'] + $user_info['pay_points']);

                    /* 取得用户可用红包 */
                    $user_bonus = app(BonusService::class)->getUserBonusInfo($order['user_id'], $order['goods_amount']);

                    $arr = [];
                    foreach ($user_bonus as $key => $row) {
                        $sql = "SELECT order_id FROM " . $this->dsc->table('order_info') . " WHERE bonus_id = '" . $row['bonus_id'] . "'";
                        if (!$this->db->getOne($sql)) {
                            $arr[] = $row;
                        }
                    }

                    $this->smarty->assign('available_bonus', $arr);
                }
            } // 发货后修改配送方式和发货单号
            elseif ('invoice' == $step) {
                $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['013_fahuodan_edit']);
                /* 如果不存在实体商品 */
                if (!$this->flowUserService->existRealGoods($order_id)) {
                    return 'Hacking Attemp';
                }

                $where = [
                    'order_id' => $order_id
                ];
                $shippinOrderInfo = $this->orderService->getOrderGoodsInfo($where);

                /* 取得可用的配送方式列表 */
                $region_id_list = [
                    $order['country'], $order['province'], $order['city'], $order['district']
                ];

                $shipping_list = available_shipping_list($region_id_list, $shippinOrderInfo);
                $this->smarty->assign('shipping_list', $shipping_list);
            }

            /* 显示模板 */

            return $this->smarty->display('order_step.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询仓库地区
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_area') {
            $check_auth = check_authz_json('order_view');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $warehouse_id = intval($_REQUEST['warehouse_id']);

            $sql = "SELECT region_id, region_name FROM " . $this->dsc->table('region_warehouse') . " WHERE region_type = 1 AND parent_id = '$warehouse_id'";
            $region_list = $this->db->getAll($sql);

            $select = '<select name="area_id">';
            $select .= '<option value="0">' . lang('seller/common.please_select') . '</option>';
            if ($region_list) {
                foreach ($region_list as $key => $row) {
                    $select .= '<option value="' . $row['region_id'] . '">' . $row['region_name'] . '</option>';
                }
            }
            $select .= '</select>';

            $result = $select;

            return make_json_result($result);
        }

        /*------------------------------------------------------ */
        //-- 处理
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'process') {
            /* 取得参数 func */
            $func = isset($_GET['func']) ? $_GET['func'] : '';

            /* 删除订单商品 */
            if ('drop_order_goods' == $func) {
                /* 检查权限 */
                admin_priv('order_edit');

                /* 取得参数 */
                $rec_id = intval($_GET['rec_id']);
                $step_act = $_GET['step_act'];
                $order_id = intval($_GET['order_id']);

                /* 如果使用库存，且下订单时减库存，则修改库存 */
                if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                    $goods = $this->db->getRow("SELECT goods_id, goods_number FROM " . $this->dsc->table('order_goods') . " WHERE rec_id = " . $rec_id);
                    $sql = "UPDATE " . $this->dsc->table('goods') .
                        " SET `goods_number` = goods_number + '" . $goods['goods_number'] . "' " .
                        " WHERE `goods_id` = '" . $goods['goods_id'] . "' LIMIT 1";
                    $this->db->query($sql);
                }

                /* 删除 */
                $sql = "DELETE FROM " . $this->dsc->table('order_goods') .
                    " WHERE rec_id = '$rec_id' LIMIT 1";
                $this->db->query($sql);

                /* 更新商品总金额和订单总金额 */
                update_order($order_id, ['goods_amount' => order_amount($order_id)]);
                $this->update_order_amount($order_id);

                /* 跳回订单商品 */
                return dsc_header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
            } /* 取消刚添加或编辑的订单 */
            elseif ('cancel_order' == $func) {
                $step_act = $_GET['step_act'];
                $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
                if ($step_act == 'add') {
                    /* 如果是添加，删除订单，返回订单列表 */
                    if ($order_id > 0) {
                        $sql = "DELETE FROM " . $this->dsc->table('order_info') .
                            " WHERE order_id = '$order_id' LIMIT 1";
                        $this->db->query($sql);
                    }
                    return dsc_header("Location: order.php?act=list\n");
                } else {
                    /* 如果是编辑，返回订单信息 */
                    return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                }
            } /* 编辑订单时由于订单已付款且金额减少而退款 */
            elseif ('refund' == $func) {
                /* 处理退款 */
                $order_id = $_REQUEST['order_id'];
                $refund_type = intval($_REQUEST['refund']);
                $refund_note = $_REQUEST['refund_note'];
                $refund_amount = $_REQUEST['refund_amount'];
                $order = order_info($order_id);

                $refund_order_amount = $order['order_amount'] < 0 ? $order['order_amount'] * -1 : $order['order_amount'];

                if ($order['order_amount'] < 0 && $refund_amount > $refund_order_amount) {
                    $link[] = ['text' => lang('seller/common.go_back'), 'href' => 'order.php?act=process&func=load_refund&anonymous=0&order_id=' . $order_id . '&refund_amount=' . $refund_amount];
                    return sys_msg($GLOBALS['_LANG']['return_money_fail_tip'], 1, $link);
                }

                $is_ok = order_refund($order, $refund_type, "【" . $order['order_sn'] . "】" . $refund_note, $refund_amount);

                if ($is_ok == 2 && $refund_type == 1) {
                    /* 提示信息 */
                    $links[] = ['href' => 'order.php?act=info&order_id=' . $order_id, 'text' => $GLOBALS['_LANG']['return_order_info']];
                    return sys_msg($GLOBALS['_LANG']['return_money_fail_account_fu'], 1, $links);
                }

                if ($order['order_amount'] < 0) {
                    $update_order['order_amount'] = $order['order_amount'] + $refund_amount;
                }

                /* 修改应付款金额为0，已付款金额减少 $refund_amount */
                update_order($order_id, $update_order);

                if ($refund_type == 1) {
                    $refund_note = "【" . $GLOBALS['_LANG']['return_user_money'] . "】" . $GLOBALS['_LANG']['shipping_refund'] . "，" . $refund_note;
                } elseif ($refund_type == 2) {
                    $refund_note = "【" . $GLOBALS['_LANG']['create_user_account'] . "】" . $GLOBALS['_LANG']['shipping_refund'] . "，" . $refund_note;
                }

                /* 记录log */
                $action_note = sprintf($refund_note, price_format($refund_amount));
                order_action($order['order_sn'], $arr['order_status'], $shipping_status, $order['pay_status'], $action_note, session('seller_name'));

                /* 返回订单详情 */
                return dsc_header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            } /* 载入退款页面 */
            elseif ('load_refund' == $func) {
                $refund_amount = floatval($_REQUEST['refund_amount']);
                $this->smarty->assign('refund_amount', $refund_amount);
                $this->smarty->assign('formated_refund_amount', price_format($refund_amount));

                $anonymous = $_REQUEST['anonymous'];
                $this->smarty->assign('anonymous', $anonymous); // 是否匿名

                $order_id = intval($_REQUEST['order_id']);
                $this->smarty->assign('order_id', $order_id); // 订单id

                /* 显示模板 */
                $this->smarty->assign('ur_here', lang('seller/order.refund'));

                return $this->smarty->display('order_refund.dwt');
            } else {
                return 'invalid params';
            }
        }

        /*------------------------------------------------------ */
        //-- 合并订单
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'merge') {
            /* 检查权限 */
            $check_auth = check_authz_json('order_os_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $data = ['error' => 0];
            $merchant_id = empty($adminru['ru_id']) ? 0 : intval($adminru['ru_id']);
            if ($merchant_id > 0) {
                $where = " AND o.ru_id = '$merchant_id' ";
                $where .= " AND o.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

                /* 取得满足条件的订单 */
                $sql = "SELECT o.order_sn, u.user_name " .
                    "FROM " . $this->dsc->table('order_info') . " AS o " .
                    "LEFT JOIN " . $this->dsc->table('users') . " AS u ON o.user_id = u.user_id " .
                    " LEFT JOIN " . $this->dsc->table('order_goods') . " AS og ON o.order_id=og.order_id " .
                    " LEFT JOIN " . $this->dsc->table('goods') . " AS g ON og.goods_id=g.goods_id " .
                    "WHERE o.user_id > 0 " . $where .
                    "AND o.extension_code = '' " . $this->orderService->orderQuerySql('unprocessed') . " GROUP BY o.order_id";
                $order_list = $this->db->getAll($sql);
                $this->smarty->assign('order_list', $order_list);
            }
            $result['content'] = $this->smarty->fetch('merge_order.dwt');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 订单打印模板（载入页面）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'templates') {
            /* 检查权限 */
            admin_priv('order_print'); //ecmoban模板堂 --zhuo

            /* 读入订单打印模板文件 */
            $file_path = storage_public(DATA_DIR . '/order_print.html');
            $file_content = file_get_contents($file_path);
            @fclose($file_content);

            /* 编辑器 */
            create_html_editor('fckeditor', $file_content);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', lang('seller/order.edit_order_templates'));
            $this->smarty->assign('action_link', ['href' => 'order.php?act=list', 'text' => lang('seller/common.02_order_list')]);
            $this->smarty->assign('act', 'edit_templates');

            /* 显示模板 */

            return $this->smarty->display('order_templates.dwt');
        }
        /*------------------------------------------------------ */
        //-- 订单打印模板（提交修改）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_templates') {
            /* 更新模板文件的内容 */
            $file_name = @fopen(storage_public(DATA_DIR . '/order_print.html'), 'w+');
            @fwrite($file_name, stripslashes($_POST['FCKeditor1']));
            @fclose($file_name);

            /* 提示信息 */
            $link[] = ['text' => lang('seller/common.back_list'), 'href' => 'order.php?act=list'];
            return sys_msg(lang('seller/order.edit_template_success'), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 操作订单状态（载入页面）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'operate') {
            /* 检查权限 */
            admin_priv('order_os_edit');

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);

            $order_id = isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id']) ? addslashes_deep($_REQUEST['order_id']) : 0;
            $rec_id = isset($_REQUEST['rec_id']) && !empty($_REQUEST['rec_id']) ? intval($_REQUEST['rec_id']) : 0;
            $ret_id = isset($_REQUEST['ret_id']) && !empty($_REQUEST['ret_id']) ? intval($_REQUEST['ret_id']) : 0;

            /* 取得订单id（可能是多个，多个sn）和操作备注（可能没有） */
            $batch = isset($_REQUEST['batch']); // 是否批处理
            $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

            /* 确认 */
            if (isset($_POST['confirm'])) {
                $require_note = false;
                $action = lang('seller/order.op_confirm');
                $operation = 'confirm';
            }

            /* ------------------------------------------------------ */
            //-- start 一键发货
            /* ------------------------------------------------------ */
            elseif (isset($_POST['to_shipping'])) {
                /* 定义当前时间 */
                $invoice_no = empty($_REQUEST['invoice_no']) ? '' : trim($_REQUEST['invoice_no']);  //快递单号

                if (empty($invoice_no)) {
                    /* 操作失败 */
                    $links[] = ['text' => $GLOBALS['_LANG']['invoice_no_null'], 'href' => 'order.php?act=info&order_id=' . $order_id];
                    return sys_msg(lang('seller/order.act_false'), 0, $links);
                }

                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

                $delivery_info = get_delivery_info($order_id);

                if (!empty($invoice_no) && !$delivery_info) {
                    $order_id = intval(trim($order_id));

                    /* 查询：根据订单id查询订单信息 */
                    if (!empty($order_id)) {
                        $order = order_info($order_id);
                    } else {
                        return 'order does not exist';
                    }
                    /* 查询：根据订单是否完成 检查权限 */
                    if (order_finished($order)) {
                        admin_priv('order_view_finished');
                    } else {
                        admin_priv('order_view');
                    }

                    /* 查询：如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
                    $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
                    $agency_id = $this->db->getOne($sql);
                    if ($agency_id > 0) {
                        if ($order['agency_id'] != $agency_id) {
                            return sys_msg(lang('seller/common.priv_error'), 0);
                        }
                    }
                    /* 查询：取得用户名 */
                    if ($order['user_id'] > 0) {
                        $where = [
                            'user_id' => $order['user_id']
                        ];
                        $user_info = $this->userService->userInfo($where);

                        if (!empty($user_info)) {
                            $order['user_name'] = $user_info['user_name'];

                            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                                $order['user_name'] = $this->dscRepository->stringToStar($order['user_name']);
                            }
                        }
                    }
                    /* 查询：取得区域名 */

                    $order['region'] = $this->db->getOne($sql);

                    /* 查询：其他处理 */
                    $order['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
                    $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $GLOBALS['_LANG']['ss'][SS_UNSHIPPED] : $order['invoice_no'];

                    /* 查询：是否保价 */
                    $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;
                    /* 查询：是否存在实体商品 */
                    $exist_real_goods = $this->flowUserService->existRealGoods($order_id);


                    /* 查询：取得订单商品 */
                    $_goods = $this->get_order_goods(['order_id' => $order['order_id'], 'order_sn' => $order['order_sn']]);

                    $attr = $_goods['attr'];
                    $goods_list = $_goods['goods_list'];
                    unset($_goods);

                    /* 查询：商品已发货数量 此单可发货数量 */
                    if ($goods_list) {
                        foreach ($goods_list as $key => $goods_value) {
                            if (!$goods_value['goods_id']) {
                                continue;
                            }

                            /* 超级礼包 */
                            if (($goods_value['extension_code'] == 'package_buy') && (count($goods_value['package_goods_list']) > 0)) {
                                $goods_list[$key]['package_goods_list'] = $this->package_goods($goods_value['package_goods_list'], $goods_value['goods_number'], $goods_value['order_id'], $goods_value['extension_code'], $goods_value['goods_id']);

                                foreach ($goods_list[$key]['package_goods_list'] as $pg_key => $pg_value) {
                                    $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = '';
                                    /* 使用库存 是否缺货 */
                                    if ($pg_value['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                                        $goods_list[$key]['package_goods_list'][$pg_key]['send'] = lang('seller/order.act_good_vacancy');
                                        $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';
                                    } /* 将已经全部发货的商品设置为只读 */
                                    elseif ($pg_value['send'] <= 0) {
                                        $goods_list[$key]['package_goods_list'][$pg_key]['send'] = lang('seller/order.act_good_delivery');
                                        $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';
                                    }
                                }
                            } else {
                                $goods_list[$key]['sended'] = $goods_value['send_number'];
                                $goods_list[$key]['sended'] = $goods_value['goods_number'];
                                $goods_list[$key]['send'] = $goods_value['goods_number'] - $goods_value['send_number'];
                                $goods_list[$key]['readonly'] = '';
                                /* 是否缺货 */
                                if ($goods_value['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                                    $goods_list[$key]['send'] = lang('seller/order.act_good_vacancy');
                                    $goods_list[$key]['readonly'] = 'readonly="readonly"';
                                } elseif ($goods_list[$key]['send'] <= 0) {
                                    $goods_list[$key]['send'] = lang('seller/order.act_good_delivery');
                                    $goods_list[$key]['readonly'] = 'readonly="readonly"';
                                }
                            }
                        }
                    }

                    $suppliers_id = 0;

                    $delivery['order_sn'] = trim($order['order_sn']);
                    $delivery['add_time'] = trim($order['order_time']);
                    $delivery['user_id'] = intval(trim($order['user_id']));
                    $delivery['how_oos'] = trim($order['how_oos']);
                    $delivery['shipping_id'] = trim($order['shipping_id']);
                    $delivery['shipping_fee'] = trim($order['shipping_fee']);
                    $delivery['consignee'] = trim($order['consignee']);
                    $delivery['address'] = trim($order['address']);
                    $delivery['country'] = intval(trim($order['country']));
                    $delivery['province'] = intval(trim($order['province']));
                    $delivery['city'] = intval(trim($order['city']));
                    $delivery['district'] = intval(trim($order['district']));
                    $delivery['sign_building'] = trim($order['sign_building']);
                    $delivery['email'] = trim($order['email']);
                    $delivery['zipcode'] = trim($order['zipcode']);
                    $delivery['tel'] = trim($order['tel']);
                    $delivery['mobile'] = trim($order['mobile']);
                    $delivery['best_time'] = trim($order['best_time']);
                    $delivery['postscript'] = trim($order['postscript']);
                    $delivery['how_oos'] = trim($order['how_oos']);
                    $delivery['insure_fee'] = floatval(trim($order['insure_fee']));
                    $delivery['shipping_fee'] = floatval(trim($order['shipping_fee']));
                    $delivery['agency_id'] = intval(trim($order['agency_id']));
                    $delivery['shipping_name'] = trim($order['shipping_name']);

                    /* 检查能否操作 */
                    $operable_list = $this->operable_list($order);

                    /* 初始化提示信息 */
                    $msg = '';

                    /* 取得订单商品 */
                    $_goods = $this->get_order_goods(['order_id' => $order_id, 'order_sn' => $delivery['order_sn']]);
                    $goods_list = $_goods['goods_list'];


                    /* 检查此单发货商品库存缺货情况 */
                    /* $goods_list已经过处理 超值礼包中商品库存已取得 */
                    $virtual_goods = [];
                    $package_virtual_goods = [];
                    /* 生成发货单 */
                    /* 获取发货单号和流水号 */
                    $delivery['delivery_sn'] = get_delivery_sn();
                    $delivery_sn = $delivery['delivery_sn'];

                    /* 获取当前操作员 */
                    $delivery['action_user'] = session('seller_name');

                    /* 获取发货单生成时间 */
                    $delivery['update_time'] = GMTIME_UTC;
                    $delivery_time = $delivery['update_time'];
                    $sql = "select add_time from " . $this->dsc->table('order_info') . " WHERE order_sn = '" . $delivery['order_sn'] . "'";
                    $delivery['add_time'] = $this->db->GetOne($sql);
                    /* 获取发货单所属供应商 */
                    $delivery['suppliers_id'] = $suppliers_id;

                    /* 设置默认值 */
                    $delivery['status'] = DELIVERY_CREATE; // 正常
                    $delivery['order_id'] = $order_id;

                    /* 过滤字段项 */
                    $filter_fileds = [
                        'order_sn', 'add_time', 'user_id', 'how_oos', 'shipping_id', 'shipping_fee',
                        'consignee', 'address', 'country', 'province', 'city', 'district', 'sign_building',
                        'email', 'zipcode', 'tel', 'mobile', 'best_time', 'postscript', 'insure_fee',
                        'agency_id', 'delivery_sn', 'action_user', 'update_time',
                        'suppliers_id', 'status', 'order_id', 'shipping_name'
                    ];
                    $_delivery = [];
                    foreach ($filter_fileds as $value) {
                        $_delivery[$value] = $delivery[$value];
                    }

                    /* 发货单入库 */
                    $query = $this->db->autoExecute($this->dsc->table('delivery_order'), $_delivery, 'INSERT', '', 'SILENT');
                    $delivery_id = $this->db->insert_id();

                    if ($delivery_id) {
                        $delivery_goods = [];

                        //发货单商品入库
                        if (!empty($goods_list)) {
                            foreach ($goods_list as $value) {
                                // 商品（实货）（虚货）
                                if (empty($value['extension_code']) || $value['extension_code'] == 'virtual_card' || $value['extension_code'] == 'presale' || substr($value['extension_code'], 0, 7) == 'seckill') {
                                    $delivery_goods = ['delivery_id' => $delivery_id,
                                        'goods_id' => $value['goods_id'],
                                        'product_id' => $value['product_id'],
                                        'product_sn' => $value['product_sn'],
                                        'goods_id' => $value['goods_id'],
                                        'goods_name' => $value['goods_name'],
                                        'brand_name' => $value['brand_name'],
                                        'goods_sn' => $value['goods_sn'],
                                        'send_number' => $value['goods_number'],
                                        'parent_id' => 0,
                                        'is_real' => $value['is_real'],
                                        'goods_attr' => $value['goods_attr']
                                    ];
                                    /* 如果是货品 */
                                    if (!empty($value['product_id'])) {
                                        $delivery_goods['product_id'] = $value['product_id'];
                                    }
                                    $query = $this->db->autoExecute($this->dsc->table('delivery_goods'), $delivery_goods, 'INSERT', '', 'SILENT');
                                    $sql = "UPDATE " . $this->dsc->table('order_goods') . "
                SET send_number = " . $value['goods_number'] . "
                WHERE order_id = '" . $value['order_id'] . "'
                AND goods_id = '" . $value['goods_id'] . "' ";
                                    $this->db->query($sql, 'SILENT');
                                } // 商品（超值礼包）
                                elseif ($value['extension_code'] == 'package_buy') {
                                    foreach ($value['package_goods_list'] as $pg_key => $pg_value) {
                                        $delivery_pg_goods = ['delivery_id' => $delivery_id,
                                            'goods_id' => $pg_value['goods_id'],
                                            'product_id' => $pg_value['product_id'],
                                            'product_sn' => $pg_value['product_sn'],
                                            'goods_name' => $pg_value['goods_name'],
                                            'brand_name' => '',
                                            'goods_sn' => $pg_value['goods_sn'],
                                            'send_number' => $value['goods_number'],
                                            'parent_id' => $value['goods_id'], // 礼包ID
                                            'extension_code' => $value['extension_code'], // 礼包
                                            'is_real' => $pg_value['is_real']
                                        ];
                                        $query = $this->db->autoExecute($this->dsc->table('delivery_goods'), $delivery_pg_goods, 'INSERT', '', 'SILENT');
                                        $sql = "UPDATE " . $this->dsc->table('order_goods') . "
                                        SET send_number = " . $value['goods_number'] . "
                                        WHERE order_id = '" . $value['order_id'] . "'
                                        AND goods_id = '" . $pg_value['goods_id'] . "' ";
                                        $this->db->query($sql, 'SILENT');
                                    }
                                }
                            }
                        }
                    } else {
                        /* 操作失败 */
                        $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                        return sys_msg(lang('seller/order.act_false'), 1, $links);
                    }
                    unset($filter_fileds, $delivery, $_delivery, $order_finish);

                    /* 定单信息更新处理 */
                    if (true) {

                        /* 标记订单为已确认 “发货中” */
                        /* 更新发货时间 */
                        $order_finish = $this->get_order_finish($order_id);
                        $shipping_status = SS_SHIPPED_ING;
                        if ($order['order_status'] != OS_CONFIRMED && $order['order_status'] != OS_SPLITED && $order['order_status'] != OS_SPLITING_PART) {
                            $arr['order_status'] = OS_CONFIRMED;
                            $arr['confirm_time'] = GMTIME_UTC;
                        }
                        $arr['order_status'] = $order_finish ? OS_SPLITED : OS_SPLITING_PART; // 全部分单、部分分单
                        $arr['shipping_status'] = $shipping_status;
                        update_order($order_id, $arr);
                    }

                    /* 记录log */
                    order_action($order['order_sn'], $arr['order_status'], $shipping_status, $order['pay_status'], $action_note, session('seller_name'));

                    /* 清除缓存 */
                    clear_cache_files();

                    /* 根据发货单id查询发货单信息 */
                    if (!empty($delivery_id)) {
                        $delivery_order = $this->delivery_order_info($delivery_id);
                    } elseif (!empty($order_sn)) {
                        $delivery_id = $this->db->getOne("SELECT delivery_id FROM " . $this->dsc->table('delivery_order') . " WHERE order_sn = '$order_sn'");
                        $delivery_order = $this->delivery_order_info($delivery_id);
                    } else {
                        return 'order does not exist';
                    }

                    /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
                    $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
                    $agency_id = $this->db->getOne($sql);
                    if ($agency_id > 0) {
                        if ($delivery_order['agency_id'] != $agency_id) {
                            return sys_msg(lang('seller/common.priv_error'));
                        }

                        /* 取当前办事处信息 */
                        $sql = "SELECT agency_name FROM " . $this->dsc->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";
                        $agency_name = $this->db->getOne($sql);
                        $delivery_order['agency_name'] = $agency_name;
                    }

                    /* 取得用户名 */
                    if ($delivery_order['user_id'] > 0) {
                        $where = [
                            'user_id' => $delivery_order['user_id']
                        ];
                        $user_info = $this->userService->userInfo($where);

                        if (!empty($user_info)) {
                            $delivery_order['user_name'] = $user_info['user_name'];

                            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                                $delivery_order['user_name'] = $this->dscRepository->stringToStar($delivery_order['user_name']);
                            }
                        }
                    }

                    /* 取得区域名 */
                    $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
                        "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
                        "FROM " . $this->dsc->table('order_info') . " AS o " .
                        "LEFT JOIN " . $this->dsc->table('region') . " AS c ON o.country = c.region_id " .
                        "LEFT JOIN " . $this->dsc->table('region') . " AS p ON o.province = p.region_id " .
                        "LEFT JOIN " . $this->dsc->table('region') . " AS t ON o.city = t.region_id " .
                        "LEFT JOIN " . $this->dsc->table('region') . " AS d ON o.district = d.region_id " .
                        "WHERE o.order_id = '" . $delivery_order['order_id'] . "'";
                    $delivery_order['region'] = $this->db->getOne($sql);

                    /* 是否保价 */
                    $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

                    /* 取得发货单商品 */
                    $goods_sql = "SELECT *
                  FROM " . $this->dsc->table('delivery_goods') . "
                  WHERE delivery_id = " . $delivery_order['delivery_id'];
                    $goods_list = $this->db->getAll($goods_sql);

                    /* 是否存在实体商品 */
                    $exist_real_goods = 0;
                    if ($goods_list) {
                        foreach ($goods_list as $value) {
                            if ($value['is_real']) {
                                $exist_real_goods++;
                            }
                        }
                    }

                    /* 取得订单操作记录 */
                    $act_list = [];
                    $sql = "SELECT * FROM " . $this->dsc->table('order_action') . " WHERE order_id = '" . $delivery_order['order_id'] . "' AND action_place = 1 ORDER BY log_time DESC,action_id DESC";
                    $res = $this->db->query($sql);
                    foreach ($res as $row) {
                        $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                        $row['pay_status'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
                        $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? $GLOBALS['_LANG']['ss_admin'][SS_SHIPPED_ING] : $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                        $row['action_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['log_time']);
                        $act_list[] = $row;
                    }

                    /* 同步发货 */
                    /* 判断支付方式是否支付宝 */
                    $alipay = false;
                    $order = order_info($delivery_order['order_id']);  //根据订单ID查询订单信息，返回数组$order
                    $payment = payment_info($order['pay_id']);           //取得支付方式信息

                    /* 根据发货单id查询发货单信息 */
                    if (!empty($delivery_id)) {
                        $delivery_order = $this->delivery_order_info($delivery_id);
                    } else {
                        return 'order does not exist';
                    }

                    /* 检查此单发货商品库存缺货情况  ecmoban模板堂 --zhuo start 下单减库存*/
                    $delivery_stock_sql = "SELECT DG.rec_id AS dg_rec_id, OG.rec_id AS og_rec_id, G.model_attr, G.model_inventory, DG.goods_id, DG.delivery_id, DG.is_real, DG.send_number AS sums, G.goods_number AS storage, G.goods_name, DG.send_number," .
                        " OG.goods_attr_id, OG.warehouse_id, OG.area_id, OG.area_city, OG.ru_id, OG.order_id, OG.product_id FROM " . $this->dsc->table('delivery_goods') . " AS DG, " .
                        $this->dsc->table('goods') . " AS G, " .
                        $this->dsc->table('delivery_order') . " AS D, " .
                        $this->dsc->table('order_goods') . " AS OG " .
                        " WHERE DG.goods_id = G.goods_id AND DG.delivery_id = D.delivery_id AND D.order_id = OG.order_id AND DG.goods_sn = OG.goods_sn AND DG.product_id = OG.product_id AND DG.delivery_id = '$delivery_id' GROUP BY OG.rec_id ";

                    $delivery_stock_result = $this->db->getAll($delivery_stock_sql);

                    $virtual_goods = [];
                    for ($i = 0; $i < count($delivery_stock_result); $i++) {
                        $delivery_stock_result[$i]['goods_id'] = isset($delivery_stock_result[$i]['goods_id']) ? $delivery_stock_result[$i]['goods_id'] : 0;
                        $delivery_stock_result[$i]['goods_attr_id'] = isset($delivery_stock_result[$i]['goods_attr_id']) ? $delivery_stock_result[$i]['goods_attr_id'] : '';
                        $delivery_stock_result[$i]['ru_id'] = isset($delivery_stock_result[$i]['ru_id']) ? $delivery_stock_result[$i]['ru_id'] : 0;
                        $delivery_stock_result[$i]['warehouse_id'] = isset($delivery_stock_result[$i]['warehouse_id']) ? $delivery_stock_result[$i]['warehouse_id'] : 0;
                        $delivery_stock_result[$i]['area_id'] = isset($delivery_stock_result[$i]['area_id']) ? $delivery_stock_result[$i]['area_id'] : 0;
                        $delivery_stock_result[$i]['area_city'] = isset($delivery_stock_result[$i]['area_city']) ? $delivery_stock_result[$i]['area_city'] : 0;
                        $delivery_stock_result[$i]['model_attr'] = isset($delivery_stock_result[$i]['model_attr']) ? $delivery_stock_result[$i]['model_attr'] : '';
                        if ($delivery_stock_result[$i]['model_attr'] == 1) {
                            $table_products = "products_warehouse";
                            $type_files = " and warehouse_id = '" . $delivery_stock_result[$i]['warehouse_id'] . "'";
                        } elseif ($delivery_stock_result[$i]['model_attr'] == 2) {
                            $table_products = "products_area";
                            $type_files = " and area_id = '" . $delivery_stock_result[$i]['area_id'] . "'";
                        } else {
                            $table_products = "products";
                            $type_files = "";
                        }

                        $sql = "SELECT * FROM " . $this->dsc->table($table_products) . " WHERE goods_id = '" . $delivery_stock_result[$i]['goods_id'] . "'" . $type_files . " LIMIT 0, 1";
                        $prod = $this->db->getRow($sql);

                        /* 如果商品存在规格就查询规格，如果不存在规格按商品库存查询 */
                        if (empty($prod)) {
                            if ($delivery_stock_result[$i]['model_inventory'] == 1) {
                                $delivery_stock_result[$i]['storage'] = get_warehouse_area_goods($delivery_stock_result[$i]['warehouse_id'], $delivery_stock_result[$i]['goods_id'], 'warehouse_goods');
                            } elseif ($delivery_stock_result[$i]['model_inventory'] == 2) {
                                $delivery_stock_result[$i]['storage'] = get_warehouse_area_goods($delivery_stock_result[$i]['area_id'], $delivery_stock_result[$i]['goods_id'], 'warehouse_area_goods');
                            }
                        } else {
                            $products = $this->goodsWarehouseService->getWarehouseAttrNumber($delivery_stock_result[$i]['goods_id'], $delivery_stock_result[$i]['goods_attr_id'], $delivery_stock_result[$i]['warehouse_id'], $delivery_stock_result[$i]['area_id'], $delivery_stock_result[$i]['area_city'], $delivery_stock_result[$i]['model_attr']);
                            $delivery_stock_result[$i]['storage'] = $products['product_number'];
                        }

                        if (($delivery_stock_result[$i]['sums'] > $delivery_stock_result[$i]['storage'] || $delivery_stock_result[$i]['storage'] <= 0) && (($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) || ($GLOBALS['_CFG']['use_storage'] == '0' && $delivery_stock_result[$i]['is_real'] == 0))) {
                            /* 操作失败 */
                            $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                            return sys_msg(sprintf(lang('seller/order.act_good_vacancy'), $value['goods_name']), 1, $links);
                            break;
                        }

                        /* 虚拟商品列表 virtual_card*/
                        if ($delivery_stock_result[$i]['is_real'] == 0) {
                            $virtual_goods[] = [
                                'goods_id' => $delivery_stock_result[$i]['goods_id'],
                                'goods_name' => $delivery_stock_result[$i]['goods_name'],
                                'num' => $delivery_stock_result[$i]['send_number']
                            ];
                        }
                    }
                    //ecmoban模板堂 --zhuo end 下单减库存

                    /* 发货 */
                    /* 处理虚拟卡 商品（虚货） */
                    if ($virtual_goods && is_array($virtual_goods) && count($virtual_goods) > 0) {
                        foreach ($virtual_goods as $virtual_value) {
                            virtual_card_shipping($virtual_value, $order['order_sn'], $msg, 'split');
                        }

                        //虚拟卡缺货
                        if (!empty($msg)) {
                            $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                            return sys_msg($msg, 1, $links);
                        }
                    }

                    /* 如果使用库存，且发货时减库存，则修改库存 */
                    if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                        foreach ($delivery_stock_result as $value) {

                            /* 商品（实货）、超级礼包（实货） ecmoban模板堂 --zhuo */
                            if ($value['is_real'] != 0) {
                                //（货品）
                                if (!empty($value['product_id'])) {
                                    if ($value['model_attr'] == 1) {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('products_warehouse') . "
                                            SET product_number = product_number - " . $value['sums'] . "
                                            WHERE product_id = " . $value['product_id'];
                                    } elseif ($value['model_attr'] == 2) {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('products_area') . "
                                            SET product_number = product_number - " . $value['sums'] . "
                                            WHERE product_id = " . $value['product_id'];
                                    } else {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('products') . "
                                            SET product_number = product_number - " . $value['sums'] . "
                                            WHERE product_id = " . $value['product_id'];
                                    }
                                } else {
                                    if ($value['model_inventory'] == 1) {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('warehouse_goods') . "
                                            SET region_number = region_number - " . $value['sums'] . "
                                            WHERE goods_id = " . $value['goods_id'] . " AND region_id = " . $value['warehouse_id'];
                                    } elseif ($value['model_inventory'] == 2) {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('warehouse_area_goods') . "
                                            SET region_number = region_number - " . $value['sums'] . "
                                            WHERE goods_id = " . $value['goods_id'] . " AND region_id = " . $value['area_id'];
                                    } else {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('goods') . "
                                            SET goods_number = goods_number - " . $value['sums'] . "
                                            WHERE goods_id = " . $value['goods_id'];
                                    }
                                }

                                $this->db->query($minus_stock_sql, 'SILENT');

                                //库存日志
                                $logs_other = [
                                    'goods_id' => $value['goods_id'],
                                    'order_id' => $value['order_id'],
                                    'use_storage' => $GLOBALS['_CFG']['stock_dec_time'],
                                    'admin_id' => session('seller_id'),
                                    'number' => "- " . $value['sums'],
                                    'model_inventory' => $value['model_inventory'],
                                    'model_attr' => $value['model_attr'],
                                    'product_id' => $value['product_id'],
                                    'warehouse_id' => $value['warehouse_id'],
                                    'area_id' => $value['area_id'],
                                    'add_time' => gmtime()
                                ];

                                $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                            }
                        }
                    }

                    /* 修改发货单信息 */
                    $invoice_no = trim($invoice_no);
                    $_delivery['invoice_no'] = $invoice_no;
                    $_delivery['status'] = DELIVERY_SHIPPED; // 0，为已发货
                    $query = $this->db->autoExecute($this->dsc->table('delivery_order'), $_delivery, 'UPDATE', "delivery_id = $delivery_id", 'SILENT');
                    if (!$query) {
                        /* 操作失败 */
                        $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                        return sys_msg(lang('seller/order.act_false'), 1, $links);
                    }

                    /* 标记订单为已确认 “已发货” */
                    /* 更新发货时间 */
                    $order_finish = $this->get_all_delivery_finish($order_id);
                    $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
                    $arr['shipping_status'] = $shipping_status;
                    $arr['shipping_time'] = GMTIME_UTC; // 发货时间
                    $arr['invoice_no'] = trim($order['invoice_no'] . ',' . $invoice_no, ',');

                    if (empty($order['pay_time'])) {
                        $arr['pay_time'] = gmtime();
                    }

                    update_order($order_id, $arr);

                    /* 发货单发货记录log */
                    order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, session('seller_name'), 1);

                    /* 如果当前订单已经全部发货 */
                    if ($order_finish) {
                        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                        if ($order['user_id'] > 0) {

                            /* 计算并发放积分 */
                            $integral = integral_to_give($order);
                            /* 如果已配送子订单的赠送积分大于0   减去已配送子订单积分 */
                            if (!empty($child_order)) {
                                $integral['custom_points'] = $integral['custom_points'] - $child_order['custom_points'];
                                $integral['rank_points'] = $integral['rank_points'] - $child_order['rank_points'];
                            }
                            log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));

                            /* 发放红包 */
                            send_order_bonus($order_id);

                            /* 发放优惠券 bylu */
                            send_order_coupons($order_id);
                        }

                        /* 发送邮件 */
                        $cfg = $GLOBALS['_CFG']['send_ship_email'];
                        if ($cfg == '1') {
                            $order['invoice_no'] = $invoice_no;
                            $tpl = get_mail_template('deliver_notice');
                            $this->smarty->assign('order', $order);
                            $this->smarty->assign('send_time', local_date($GLOBALS['_CFG']['time_format']));
                            $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                            $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                            $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                            $this->smarty->assign('confirm_url', $this->dsc->url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']); //by wu
                            $this->smarty->assign('send_msg_url', $this->dsc->url() . 'user_message.php?act=message_list&order_id=' . $order['order_id']);
                            $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                            if (!$this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                                $msg = lang('seller/order.send_mail_fail');
                            }
                        }

                        /* 如果需要，发短信 */
                        if ($GLOBALS['_CFG']['sms_order_shipped'] == '1' && $order['mobile'] != '') {

                            //阿里大鱼短信接口参数
                            if ($order['ru_id']) {
                                $shop_name = $this->merchantCommonService->getShopName($order['ru_id'], 1);
                            } else {
                                $shop_name = "";
                            }

                            $user_info = get_admin_user_info($order['user_id']);

                            $smsParams = [
                                'shop_name' => $shop_name,
                                'shopname' => $shop_name,
                                'user_name' => $user_info['user_name'],
                                'username' => $user_info['user_name'],
                                'consignee' => $order['consignee'],
                                'order_sn' => $order['order_sn'],
                                'ordersn' => $order['order_sn'],
                                'mobile_phone' => $order['mobile'],
                                'mobilephone' => $order['mobile']
                            ];

                            $this->commonRepository->smsSend($order['mobile'], $smsParams, 'sms_order_shipped');
                        }

                        // 微信通模板消息 发货通知
                        if (file_exists(MOBILE_WECHAT)) {
                            $pushData = [
                                'first' => ['value' => $GLOBALS['_LANG']['you_order_delivery']], // 标题
                                'keyword1' => ['value' => $order['order_sn']], //订单
                                'keyword2' => ['value' => $order['shipping_name']], //物流服务
                                'keyword3' => ['value' => $invoice_no], //快递单号
                                'keyword4' => ['value' => $order['consignee']], // 收货信息
                                'remark' => ['value' => $GLOBALS['_LANG']['order_delivery_wait']]
                            ];
                            $shop_url = url('/') . '/'; // 根域名 兼容商家后台
                            $order_url = dsc_url('/#/user/orderDetail/' . $order_id);

                            push_template_curl('OPENTM202243318', $pushData, $order_url, $order['user_id'], $shop_url);
                        }

                        /* 更新商品销量 */
                        get_goods_sale($order_id);
                    }

                    update_order($order_id, $arr);

                    /* 更新会员订单信息 */
                    $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

                    /* 操作成功 */
                    $links[] = ['text' => lang('seller/common.09_delivery_order'), 'href' => 'order.php?act=delivery_list'];
                    $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                    return sys_msg(lang('seller/order.act_ok'), 0, $links);
                }
            }
            /* ------------------------------------------------------ */
            //-- end一键发货
            /* ------------------------------------------------------ */

            /* 付款 */
            elseif (isset($_POST['pay'])) {
                /* 检查权限 */
                admin_priv('order_ps_edit');
                $require_note = $GLOBALS['_CFG']['order_pay_note'] == 1;
                $action = $GLOBALS['_LANG']['op_pay'];
                $operation = 'pay';
            } /* 配货 */
            elseif (isset($_POST['prepare'])) {
                $require_note = false;
                $action = $GLOBALS['_LANG']['op_prepare'];
                $operation = 'prepare';
            } /* 分单 */
            elseif (isset($_POST['ship'])) {
                /* 查询：检查权限 */
                admin_priv('order_ss_edit');

                $order_id = intval(trim($order_id));
                $action_note = trim($action_note);

                /* 查询：根据订单id查询订单信息 */
                if (!empty($order_id)) {
                    $order = order_info($order_id);
                } else {
                    return 'order does not exist';
                }

                /* 查询：根据订单是否完成 检查权限 */
                if (order_finished($order)) {
                    admin_priv('order_view_finished');
                } else {
                    admin_priv('order_view');
                }

                /* 查询：如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
                $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
                $agency_id = $this->db->getOne($sql);
                if ($agency_id > 0) {
                    if ($order['agency_id'] != $agency_id) {
                        return sys_msg(lang('seller/common.priv_error'), 0);
                    }
                }

                /* 查询：取得用户名 */
                if ($order['user_id'] > 0) {
                    $where = [
                        'user_id' => $order['user_id']
                    ];
                    $user_info = $this->userService->userInfo($where);

                    if (!empty($user_info)) {
                        $order['user_name'] = $user_info['user_name'];

                        if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                            $order['user_name'] = $this->dscRepository->stringToStar($order['user_name']);
                        }
                    }
                }

                /* 查询：其他处理 */
                $order['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
                $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $GLOBALS['_LANG']['ss'][SS_UNSHIPPED] : $order['invoice_no'];
                $order['pay_time'] = $order['pay_time'] > 0 ?
                    local_date($GLOBALS['_CFG']['time_format'], $order['pay_time']) : $GLOBALS['_LANG']['ps'][PS_UNPAYED];
                $order['shipping_time'] = $order['shipping_time'] > 0 ?
                    local_date($GLOBALS['_CFG']['time_format'], $order['shipping_time']) : $GLOBALS['_LANG']['ss'][SS_UNSHIPPED];
                $order['confirm_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['confirm_time']);
                /* 查询：是否保价 */
                $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

                /* 查询：是否存在实体商品 */
                $exist_real_goods = $this->flowUserService->existRealGoods($order_id);

                /* 查询：取得订单商品 */
                $_goods = $this->get_order_goods(['order_id' => $order['order_id'], 'order_sn' => $order['order_sn']]);

                $attr = $_goods['attr'];
                $goods_list = $_goods['goods_list'];
                unset($_goods);

                /* 查询：商品已发货数量 此单可发货数量 */
                if ($goods_list) {
                    foreach ($goods_list as $key => $goods_value) {
                        if (!$goods_value['goods_id']) {
                            continue;
                        }

                        /* 超级礼包 */
                        if (($goods_value['extension_code'] == 'package_buy') && (count($goods_value['package_goods_list']) > 0)) {
                            $goods_list[$key]['package_goods_list'] = $this->package_goods($goods_value['package_goods_list'], $goods_value['goods_number'], $goods_value['order_id'], $goods_value['extension_code'], $goods_value['goods_id']);

                            foreach ($goods_list[$key]['package_goods_list'] as $pg_key => $pg_value) {
                                $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = '';
                                /* 使用库存 是否缺货 */
                                if ($pg_value['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                                    //$goods_list[$key]['package_goods_list'][$pg_key]['send'] = lang('seller/order.act_good_vacancy');
                                    $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';
                                } /* 将已经全部发货的商品设置为只读 */
                                elseif ($pg_value['send'] <= 0) {
                                    //$goods_list[$key]['package_goods_list'][$pg_key]['send'] = lang('seller/order.act_good_delivery');
                                    $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';
                                }
                            }
                        } else {
                            $goods_list[$key]['sended'] = $goods_value['send_number'];
                            $goods_list[$key]['send'] = $goods_value['goods_number'] - $goods_value['send_number'];

                            $goods_list[$key]['readonly'] = '';
                            /* 是否缺货 */
                            if ($goods_value['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                                $goods_list[$key]['send'] = lang('seller/order.act_good_vacancy');
                                $goods_list[$key]['readonly'] = 'readonly="readonly"';
                            } elseif ($goods_list[$key]['send'] <= 0) {
                                $goods_list[$key]['send'] = lang('seller/order.act_good_delivery');
                                $goods_list[$key]['readonly'] = 'readonly="readonly"';
                            }
                        }
                    }
                }

                /* 模板赋值 */
                $this->smarty->assign('order', $order);
                $this->smarty->assign('exist_real_goods', $exist_real_goods);
                $this->smarty->assign('goods_attr', $attr);
                $this->smarty->assign('goods_list', $goods_list);
                $this->smarty->assign('order_id', $order_id); // 订单id
                $this->smarty->assign('operation', 'split'); // 订单id
                $this->smarty->assign('action_note', $action_note); // 发货操作信息

                $suppliers_list = $this->get_suppliers_list();
                $suppliers_list_count = count($suppliers_list);
                $this->smarty->assign('suppliers_name', suppliers_list_name()); // 取供货商名
                $this->smarty->assign('suppliers_list', ($suppliers_list_count == 0 ? 0 : $suppliers_list)); // 取供货商列表
                $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '09_delivery_order']);
                /* 显示模板 */
                $this->smarty->assign('ur_here', lang('seller/order.order_operate') . $GLOBALS['_LANG']['op_split']);
                /* 取得订单操作记录 */
                $act_list = [];
                $sql = "SELECT * FROM " . $this->dsc->table('order_action') . " WHERE order_id = '$order_id' ORDER BY log_time DESC,action_id DESC";
                $res = $this->db->query($sql);
                foreach ($res as $row) {
                    $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                    $row['pay_status'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
                    $row['shipping_status'] = $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                    $row['action_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['log_time']);
                    $act_list[] = $row;
                }
                $this->smarty->assign('action_list', $act_list);

                return $this->smarty->display('order_delivery_info.dwt');
            } /* 未发货 */
            elseif (isset($_POST['unship'])) {
                /* 检查权限 */
                admin_priv('order_ss_edit');

                $require_note = $GLOBALS['_CFG']['order_unship_note'] == 1;
                $action = $GLOBALS['_LANG']['op_unship'];
                $operation = 'unship';
            } /* 收货确认 */
            elseif (isset($_POST['receive'])) {
                $require_note = $GLOBALS['_CFG']['order_receive_note'] == 1;
                $action = $GLOBALS['_LANG']['op_receive'];
                $operation = 'receive';
            } /* 取消 */
            elseif (isset($_POST['cancel'])) {
                $require_note = $GLOBALS['_CFG']['order_cancel_note'] == 1;
                $action = $GLOBALS['_LANG']['op_cancel'];
                $operation = 'cancel';
                $show_cancel_note = true;
                //兼容order_id 传值为sn的情况
                if ($order_id > 2019000000000000000) {
                    $order = order_info(0, $order_id);
                } else {
                    $order = order_info($order_id);
                }
                if (isset($order['pay_status']) && $order['pay_status'] > 0) {
                    $show_refund = true;
                }
                $anonymous = $order['user_id'] == 0;
            } /* 无效 */
            elseif (isset($_POST['invalid'])) {
                $require_note = $GLOBALS['_CFG']['order_invalid_note'] == 1;
                $action = $GLOBALS['_LANG']['op_invalid'];
                $operation = 'invalid';
            } /* 售后 */
            elseif (isset($_POST['after_service'])) {
                $require_note = true;
                $action = $GLOBALS['_LANG']['op_after_service'];
                $operation = 'after_service';
            } /* 退货 */
            elseif (isset($_POST['return'])) {
                $sql = "SELECT ret_id FROM " . $this->dsc->table('order_return') . " WHERE order_id = '" . $order_id . "'";
                $ret_id = $this->db->getOne($sql);
                if ($ret_id > 0) {
                    $links[] = ['text' => lang('seller/common.go_back'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                    return sys_msg($GLOBALS['_LANG']['order_have_return_cant_return'], 0, $links);
                } else {
                    $require_note = $GLOBALS['_CFG']['order_return_note'] == 1;
                    $order = order_info($order_id);
                    if ($order['pay_status'] > 0) {
                        $show_refund = true;
                    }
                    $anonymous = $order['user_id'] == 0;
                    $action = $GLOBALS['_LANG']['op_return'];
                    $operation = 'return';
                }

                $sql = "SELECT vc_id, use_val FROM " . $this->dsc->table('value_card_record') . " WHERE order_id = '" . $order['order_id'] . "' LIMIT 1";
                $value_card = $this->db->getRow($sql);

                $paid_amount = $order['money_paid'] + $order['surplus'];
                if ($paid_amount > 0 && $order['shipping_fee'] > 0 && $paid_amount >= $order['shipping_fee']) {
                    $refound_amount = $paid_amount - $order['shipping_fee'];
                } else {
                    $refound_amount = $paid_amount;
                }

                $this->smarty->assign('refound_amount', $refound_amount);
                $this->smarty->assign('shipping_fee', $order['shipping_fee']);
                $this->smarty->assign('value_card', $value_card);
                $this->smarty->assign('is_whole', 1);
            } /**
             * 同意申请
             * by ecmoban模板堂 --zhuo
             */ elseif (isset($_POST['agree_apply'])) {
                $require_note = false;
                $action = lang('seller/order.op_confirm');
                $operation = 'agree_apply';
            } /* 退款
     * by Leah
     */
            elseif (isset($_POST['refound'])) {
                $require_note = $GLOBALS['_CFG']['order_return_note'] == 1;
                $order = order_info($order_id);
                $refound_amount = empty($_REQUEST['refound_amount']) ? 0 : floatval($_REQUEST['refound_amount']);
                $return_shipping_fee = empty($_REQUEST['return_shipping_fee']) ? 0 : floatval($_REQUEST['return_shipping_fee']);

                //判断运费退款是否大于实际运费退款金额
                $is_refound_shippfee = order_refound_shipping_fee($order_id, $ret_id);
                $is_refound_shippfee_amount = $is_refound_shippfee + $return_shipping_fee;

                if (($is_refound_shippfee_amount > $order['shipping_fee']) || ($return_shipping_fee == 0 && $is_refound_shippfee > 0)) {
                    $return_shipping_fee = $order['shipping_fee'] - $is_refound_shippfee;
                } elseif ($return_shipping_fee == 0 && $is_refound_shippfee == 0) {
                    $return_shipping_fee = $order['shipping_fee'];
                }

                // 判断退货单订单中是否只有一个商品   如果只有一个则退订单的全部积分   如果多个则按商品积分的比例来退  by kong
                $count_goods = $this->db->getAll(" SELECT rec_id ,goods_id FROM " . $this->dsc->table("order_goods") . " WHERE order_id = '$order_id'");
                if (count($count_goods) > 1) {
                    foreach ($count_goods as $k => $v) {
                        $all_goods_id[] = $v['goods_id'];
                    }
                    $count_integral = $this->db->getOne(" SELECT sum(integral) FROM" . $this->dsc->table("goods") . " WHERE  goods_id" . db_create_in($all_goods_id)); //获取该订单的全部可用积分
                    $return_integral = $this->db->getOne(' SELECT g.integral FROM' . $this->dsc->table("goods") . " as g LEFT JOIN " . $this->dsc->table("order_return") . " as o on o.goods_id = g.goods_id  WHERE o.ret_id = '$ret_id'"); //退货商品的可用积分
                    $count_integral = !empty($count_integral) ? $count_integral : 1;
                    $return_ratio = $return_integral / $count_integral; //退还积分比例

                    $return_price = (empty($order['pay_points']) ? 0 : $order['pay_points']) * $return_ratio; //那比例最多返还的积分
                } else {
                    $return_price = empty($order['pay_points']) ? 0 : $order['pay_points']; //by kong 赋值支付积分
                }
                $goods_number = $this->db->getOne(" SELECT goods_number FROM " . $this->dsc->table("order_goods") . " WHERE rec_id = '$rec_id'"); //获取该商品的订单数量
                $return_number = $this->db->getOne(" SELECT return_number FROM " . $this->dsc->table("order_return_extend") . " WHERE ret_id = '$ret_id'"); //获取退货数量
                //*如果退货数量小于订单商品数量   则按比例返还*/
                if ($return_number < $goods_number) {
                    $refound_pay_points = intval($return_price * ($return_number / $goods_number));
                } else {
                    $refound_pay_points = intval($return_price);
                }
                if ($order['pay_status'] > 0) {
                    $show_refund1 = true;
                }
                $anonymous = $order['user_id'] == 0;
                $action = $GLOBALS['_LANG']['op_return'];
                $operation = 'refound';

                $sql = "SELECT vc_id, use_val FROM " . $this->dsc->table('value_card_record') . " WHERE order_id = '" . $order['order_id'] . "' LIMIT 1";
                $value_card = $this->db->getRow($sql);

                $return_order = return_order_info($ret_id);

                $should_return = $return_order['should_return'] - $return_order['discount_amount'];
                if ($value_card) {
                    if ($value_card['use_val'] > $should_return) {
                        $value_card['use_val'] = $should_return;
                    }
                }

                if ($return_order['goods_coupons'] > 0) {
                    $refound_amount = $refound_amount - $return_order['goods_coupons'];
                }

                if ($return_order['goods_bonus'] > 0) {
                    $refound_amount = $refound_amount - $return_order['goods_bonus'];
                }

                $paid_amount = $order['money_paid'] + $order['surplus'];
                if ($paid_amount > 0 && $paid_amount >= $order['shipping_fee']) {
                    $paid_amount = $paid_amount - $order['shipping_fee'];
                }

                if ($refound_amount > $paid_amount) {
                    $refound_amount = $paid_amount;
                }

                $this->smarty->assign('refound_pay_points', $refound_pay_points); // by kong  页面赋值
                $this->smarty->assign('refound_amount', $refound_amount);
                $this->smarty->assign('shipping_fee', $return_shipping_fee);
                $this->smarty->assign('value_card', $value_card);

                /* 检测订单是否只有一个退货商品的订单 start */
                $is_whole = 0;
                $is_diff = get_order_return_rec($order['order_id']);
                if ($is_diff) {
                    //整单退换货
                    $return_count = return_order_info_byId($order['order_id'], 0);
                    if ($return_count == 1) {
                        $is_whole = 1;
                    }
                }

                $this->smarty->assign('is_whole', $is_whole);
                /* 检测订单是否只有一个退货商品的订单 end */
            } /**
             * 收到退换货商品
             * by Leah
             */ elseif (isset($_POST['receive_goods'])) {
                $require_note = false;
                $action = lang('seller/order.op_confirm');
                $operation = 'receive_goods';
            } /**
             * 换出商品 --  快递信息
             * by Leah
             */ elseif (isset($_POST['send_submit'])) {
                $shipping_id = $_POST['shipping_name'];
                $invoice_no = $_POST['invoice_no'];
                $action_note = $_POST['action_note'];
                $sql = "SELECT shipping_name FROM " . $this->dsc->table('shipping') . " WHERE shipping_id =" . $shipping_id;
                $shipping_name = $this->db->getOne($sql);
                $require_note = false;
                $action = lang('seller/order.op_confirm');
                $operation = 'receive_goods';
                $this->db->query("UPDATE " . $this->dsc->table('order_return') . " SET out_shipping_name = '$shipping_id' ,out_invoice_no ='$invoice_no'" .
                    "WHERE rec_id = '$rec_id'");
            } /**
             * 商品分单寄出
             * by Leah
             */ elseif (isset($_POST['swapped_out'])) {
                $require_note = false;
                $action = lang('seller/order.op_confirm');
                $operation = 'swapped_out';
            } /**
             * 商品分单寄出  分单
             * by Leah
             */ elseif (isset($_POST['swapped_out_single'])) {
                $require_note = false;
                $action = lang('seller/order.op_confirm');
                $operation = 'swapped_out_single';
            } /**
             * 完成退换货
             * by Leah
             */ elseif (isset($_POST['complete'])) {
                $require_note = false;
                $action = lang('seller/order.op_confirm');
                $operation = 'complete';
            } /**
             * 拒绝申请
             * by Leah
             */ elseif (isset($_POST['refuse_apply'])) {
                $require_note = true;
                $action = lang('seller/order.refuse_apply');
                $operation = 'refuse_apply';
            } /* 指派 */
            elseif (isset($_POST['assign'])) {
                /* 取得参数 */
                $new_agency_id = isset($_POST['agency_id']) ? intval($_POST['agency_id']) : 0;
                if ($new_agency_id == 0) {
                    return sys_msg($GLOBALS['_LANG']['js_languages']['pls_select_agency']);
                }

                /* 查询订单信息 */
                $order = order_info($order_id);

                /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
                $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
                $admin_agency_id = $this->db->getOne($sql);
                if ($admin_agency_id > 0) {
                    if ($order['agency_id'] != $admin_agency_id) {
                        return sys_msg(lang('seller/common.priv_error'));
                    }
                }

                /* 修改订单相关所属的办事处 */
                if ($new_agency_id != $order['agency_id']) {
                    $query_array = ['order_info', // 更改订单表的供货商ID
                        'delivery_order', // 更改订单的发货单供货商ID
                        'back_order'// 更改订单的退货单供货商ID
                    ];
                    foreach ($query_array as $value) {
                        $this->db->query("UPDATE " . $this->dsc->table($value) . " SET agency_id = '$new_agency_id' " .
                            "WHERE order_id = '$order_id'");
                    }
                }

                /* 操作成功 */
                $links[] = ['href' => 'order.php?act=list&' . list_link_postfix(), 'text' => lang('seller/common.02_order_list')];
                return sys_msg(lang('seller/order.act_ok'), 0, $links);
            } /* 订单删除 */
            elseif (isset($_POST['remove'])) {
                $require_note = false;
                $operation = 'remove';
                if (!$batch) {
                    /* 检查能否操作 */
                    $order = order_info($order_id);

                    if ($order['ru_id'] != $adminru['ru_id']) {
                        return sys_msg(lang('seller/order.order_removed'), 0, [['href' => 'order.php?act=list&' . list_link_postfix(), 'text' => lang('seller/order.return_list')]]);
                    }

                    $operable_list = $this->operable_list($order);
                    if (!isset($operable_list['remove'])) {
                        return 'Hacking attempt';
                    }

                    $return_order = return_order_info(0, '', $order['order_id']);
                    if ($return_order) {
                        return sys_msg(sprintf(lang('seller/order.order_remove_failure'), $order['order_sn']), 0, [['href' => 'order.php?act=list&' . list_link_postfix(), 'text' => lang('seller/order.return_list')]]);
                    }

                    /* 删除订单 */
                    $this->db->query("DELETE FROM " . $this->dsc->table('order_info') . " WHERE order_id = '$order_id'");
                    $this->db->query("DELETE FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '$order_id'");
                    $this->db->query("DELETE FROM " . $this->dsc->table('order_action') . " WHERE order_id = '$order_id'");

                    $this->db->query("DELETE FROM " . $this->dsc->table('store_order') . " WHERE order_id = '$order_id'");

                    $action_array = ['delivery', 'back'];
                    $this->del_delivery($order_id, $action_array);

                    /* todo 记录日志 */
                    admin_log($order['order_sn'], 'remove', 'order');

                    /* 返回 */
                    return sys_msg(lang('seller/order.order_removed'), 0, [['href' => 'order.php?act=list&' . list_link_postfix(), 'text' => lang('seller/order.return_list')]]);
                }
            } /* 发货单删除 */
            elseif (isset($_REQUEST['remove_invoice'])) {
                // 删除发货单
                $delivery_id = isset($_REQUEST['delivery_id']) ? $_REQUEST['delivery_id'] : $_REQUEST['checkboxes'];
                $delivery_id = is_array($delivery_id) ? $delivery_id : [$delivery_id];

                foreach ($delivery_id as $value_is) {
                    $value_is = intval(trim($value_is));

                    // 查询：发货单信息
                    $delivery_order = $this->delivery_order_info($value_is);

                    // 如果status不是退货
                    if ($delivery_order['status'] != DELIVERY_REFOUND) {
                        /* 处理退货 */
                        $this->delivery_return_goods($value_is, $delivery_order);
                    }

                    // 如果status是已发货并且发货单号不为空
                    if ($delivery_order['status'] == DELIVERY_SHIPPED && $delivery_order['invoice_no'] != '') {
                        /* 更新：删除订单中的发货单号 */
                        $this->del_order_invoice_no($delivery_order['order_id'], $delivery_order['invoice_no']);
                    }

                    // 更新：删除发货单
                    $sql = "DELETE FROM " . $this->dsc->table('delivery_order') . " WHERE delivery_id = '$value_is'";
                    $this->db->query($sql);
                }

                /* 返回 */
                return sys_msg(lang('seller/order.tips_delivery_del'), 0, [['href' => 'order.php?act=delivery_list', 'text' => lang('seller/order.return_list')]]);
            } /* 退货单删除 */
            elseif (isset($_REQUEST['remove_back'])) {
                $back_id = isset($_REQUEST['back_id']) ? $_REQUEST['back_id'] : $_POST['checkboxes'];
                /* 删除退货单 */
                if (is_array($back_id)) {
                    foreach ($back_id as $value_is) {
                        $sql = "DELETE FROM " . $this->dsc->table('back_order') . " WHERE back_id = '$value_is'";
                        $this->db->query($sql);
                    }
                } else {
                    $sql = "DELETE FROM " . $this->dsc->table('back_order') . " WHERE back_id = '$back_id'";
                    $this->db->query($sql);
                }
                /* 返回 */
                return sys_msg(lang('seller/order.tips_back_del'), 0, [['href' => 'order.php?act=back_list', 'text' => lang('seller/order.return_list')]]);
            } /* 批量打印订单 */
            elseif (isset($_POST['print'])) {
                if (empty($_POST['order_id'])) {
                    return sys_msg(lang('seller/order.pls_select_order'));
                }

                if (isset($this->config['tp_api']) && $this->config['tp_api']) {
                    //快递鸟、电子面单 start
                    $url = 'tp_api.php?act=order_print&order_sn=' . $_POST['order_id'];
                    return dsc_header("Location: $url\n");
                    //快递鸟、电子面单 end
                }

                /* 赋值公用信息 */
                $this->smarty->assign('print_time', local_date($GLOBALS['_CFG']['time_format']));
                $this->smarty->assign('action_user', session('seller_name'));

                $html = '';
                $order_sn_list = explode(',', $_POST['order_id']);
                foreach ($order_sn_list as $order_sn) {
                    if ($order_sn) {

                        /* 取得订单信息 */
                        $order = order_info(0, $order_sn);
                        if (empty($order)) {
                            continue;
                        }

                        /* 根据订单是否完成检查权限 */
                        if (order_finished($order)) {
                            if (!admin_priv('order_view_finished', '', false)) {
                                continue;
                            }
                        } else {
                            if (!admin_priv('order_view', '', false)) {
                                continue;
                            }
                        }

                        /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
                        $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
                        $agency_id = $this->db->getOne($sql);
                        if ($agency_id > 0) {
                            if ($order['agency_id'] != $agency_id) {
                                continue;
                            }
                        }

                        /* 取得用户名 */
                        if ($order['user_id'] > 0) {
                            $where = [
                                'user_id' => $order['user_id']
                            ];
                            $user_info = $this->userService->userInfo($where);

                            if (!empty($user_info)) {
                                $order['user_name'] = $user_info['user_name'];

                                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                                    $order['user_name'] = $this->dscRepository->stringToStar($order['user_name']);
                                }
                            }
                        }

                        /* 其他处理 */
                        $order['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
                        $order['pay_time'] = $order['pay_time'] > 0 ?
                            local_date($GLOBALS['_CFG']['time_format'], $order['pay_time']) : $GLOBALS['_LANG']['ps'][PS_UNPAYED];
                        $order['shipping_time'] = $order['shipping_time'] > 0 ?
                            local_date($GLOBALS['_CFG']['time_format'], $order['shipping_time']) : $GLOBALS['_LANG']['ss'][SS_UNSHIPPED];
                        $order['status'] = $GLOBALS['_LANG']['os'][$order['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$order['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$order['shipping_status']];
                        $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $GLOBALS['_LANG']['ss'][SS_UNSHIPPED] : $order['invoice_no'];

                        /* 此订单的发货备注(此订单的最后一条操作记录) */
                        $sql = "SELECT action_note FROM " . $this->dsc->table('order_action') .
                            " WHERE order_id = '$order[order_id]' AND shipping_status = 1 ORDER BY log_time DESC";
                        $order['invoice_note'] = $this->db->getOne($sql);

                        /* 参数赋值：订单 */
                        $this->smarty->assign('order', $order);

                        /* 取得订单商品 */
                        $goods_list = [];
                        $goods_attr = [];
                        $sql = "SELECT o.*, c.measure_unit, g.goods_number AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, g.bar_code " .
                            "FROM " . $this->dsc->table('order_goods') . " AS o " .
                            "LEFT JOIN " . $this->dsc->table('goods') . " AS g ON o.goods_id = g.goods_id " .
                            "LEFT JOIN " . $this->dsc->table('brand') . " AS b ON g.brand_id = b.brand_id " .
                            'LEFT JOIN ' . $this->dsc->table('category') . ' AS c ON g.cat_id = c.cat_id ' .
                            "WHERE o.order_id = '$order[order_id]' ";
                        $res = $this->db->query($sql);
                        foreach ($res as $row) {
                            $products = $this->goodsWarehouseService->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $row['model_attr']);
                            if ($row['product_id']) {
                                $row['bar_code'] = $products['bar_code'];
                            }

                            /* 虚拟商品支持 */
                            if ($row['is_real'] == 0) {
                                /* 取得语言项 */
                                $filename = app_path('Plugins/' . $row['extension_code'] . '/Languages/common_' . $GLOBALS['_CFG']['lang'] . '.php');
                                if (file_exists($filename)) {
                                    include_once($filename);
                                    if (!empty($GLOBALS['_LANG'][$row['extension_code'] . '_link'])) {
                                        $row['goods_name'] = $row['goods_name'] . sprintf($GLOBALS['_LANG'][$row['extension_code'] . '_link'], $row['goods_id'], $order['order_sn']);
                                    }
                                }
                            }

                            $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                            $row['formated_goods_price'] = price_format($row['goods_price']);

                            $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
                            $goods_list[] = $row;
                        }

                        $attr = [];
                        $arr = [];
                        if ($goods_attr) {
                            foreach ($goods_attr as $index => $array_val) {
                                $array_val = $this->baseRepository->getExplode($array_val);
                                if ($array_val) {
                                    foreach ($array_val as $value) {
                                        $arr = explode(':', $value); //以 : 号将属性拆开
                                        $attr[$index][] = @['name' => $arr[0], 'value' => $arr[1]];
                                    }
                                }
                            }
                        }

                        /* 取得商家信息 by  kong */
                        $sql = "select shop_name,country,province,city,shop_address,kf_tel from " . $this->dsc->table('seller_shopinfo') . " where ru_id='" . $order['ru_id'] . "'";
                        $store = $this->db->getRow($sql);

                        $store['shop_name'] = $this->merchantCommonService->getShopName($order['ru_id'], 1);

                        $sql = "SELECT domain_name FROM " . $this->dsc->table("seller_domain") . " WHERE ru_id = '" . $order['ru_id'] . "' AND  is_enable = 1"; //获取商家域名
                        $domain_name = $this->db->getOne($sql);
                        $this->smarty->assign('domain_name', $domain_name);

                        $this->smarty->assign('shop_name', $store['shop_name']);
                        $this->smarty->assign('shop_url', $this->dsc->seller_url());
                        $this->smarty->assign('shop_address', $store['shop_address']);
                        $this->smarty->assign('service_phone', $store['kf_tel']);

                        $this->smarty->assign('goods_attr', $attr);
                        $this->smarty->assign('goods_list', $goods_list);
                        $this->smarty->template_dir = storage_public(DATA_DIR);
                        $html .= $this->smarty->fetch('order_print.html') .
                            '<div style="PAGE-BREAK-AFTER:always"></div>';
                    }
                }
                return $html;
            } /* 去发货 */
            elseif (isset($_POST['to_delivery'])) {
                $url = 'order.php?act=delivery_list&order_sn=' . $_REQUEST['order_sn'];

                return dsc_header("Location: $url\n");
            } /* 批量发货 by wu */
            elseif (isset($_REQUEST['batch_delivery'])) {
                /* 检查权限 */
                admin_priv('delivery_view');
                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

                $delivery_id = isset($_REQUEST['delivery_id']) ? $_REQUEST['delivery_id'] : $_REQUEST['checkboxes'];
                $delivery_id = is_array($delivery_id) ? $delivery_id : [$delivery_id];
                $invoice_nos = isset($_REQUEST['invoice_no']) ? $_REQUEST['invoice_no'] : [];
                $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

                foreach ($delivery_id as $value_is) {
                    $msg = '';
                    $value_is = intval(trim($value_is));
                    $delivery_info = get_table_date('delivery_order', "delivery_id='$value_is'", ['order_id', 'status']);

                    //跳过已发货、退货订单
                    if ($delivery_info['status'] != DELIVERY_CREATE || !isset($invoice_nos[$value_is])) {
                        continue;
                    }

                    /* 取得参数 */
                    $delivery = [];
                    $order_id = $delivery_info['order_id'];        // 订单id
                    $delivery_id = $value_is;        // 发货单id
                    $delivery['invoice_no'] = $invoice_nos[$value_is];
                    $action_note = $action_note;

                    /* 根据发货单id查询发货单信息 */
                    if (!empty($delivery_id)) {
                        $delivery_order = $this->delivery_order_info($delivery_id);
                    } else {
                        return 'order does not exist';
                    }

                    /* 查询订单信息 */
                    $order = order_info($order_id);
                    /* 检查此单发货商品库存缺货情况  ecmoban模板堂 --zhuo start 下单减库存 */
                    $delivery_stock_sql = "SELECT DG.rec_id AS dg_rec_id, OG.rec_id AS og_rec_id, G.model_attr, G.model_inventory, DG.goods_id, DG.delivery_id, DG.is_real, DG.send_number AS sums, G.goods_number AS storage, G.goods_name, DG.send_number," .
                        " OG.goods_attr_id, OG.warehouse_id, OG.area_id, OG.area_city, OG.ru_id, OG.order_id, OG.product_id FROM " . $this->dsc->table('delivery_goods') . " AS DG, " .
                        $this->dsc->table('goods') . " AS G, " .
                        $this->dsc->table('delivery_order') . " AS D, " .
                        $this->dsc->table('order_goods') . " AS OG " .
                        " WHERE DG.goods_id = G.goods_id AND DG.delivery_id = D.delivery_id AND D.order_id = OG.order_id AND DG.goods_sn = OG.goods_sn AND DG.product_id = OG.product_id AND DG.delivery_id = '$delivery_id' GROUP BY OG.rec_id ";

                    $delivery_stock_result = $this->db->getAll($delivery_stock_sql);

                    $virtual_goods = [];
                    for ($i = 0; $i < count($delivery_stock_result); $i++) {
                        if ($delivery_stock_result[$i]['model_attr'] == 1) {
                            $table_products = "products_warehouse";
                            $type_files = " and warehouse_id = '" . $delivery_stock_result[$i]['warehouse_id'] . "'";
                        } elseif ($delivery_stock_result[$i]['model_attr'] == 2) {
                            $table_products = "products_area";
                            $type_files = " and area_id = '" . $delivery_stock_result[$i]['area_id'] . "'";
                        } else {
                            $table_products = "products";
                            $type_files = "";
                        }

                        $sql = "SELECT * FROM " . $this->dsc->table($table_products) . " WHERE goods_id = '" . $delivery_stock_result[$i]['goods_id'] . "'" . $type_files . " LIMIT 0, 1";
                        $prod = $this->db->getRow($sql);

                        /* 如果商品存在规格就查询规格，如果不存在规格按商品库存查询 */
                        if (empty($prod)) {
                            if ($delivery_stock_result[$i]['model_inventory'] == 1) {
                                $delivery_stock_result[$i]['storage'] = get_warehouse_area_goods($delivery_stock_result[$i]['warehouse_id'], $delivery_stock_result[$i]['goods_id'], 'warehouse_goods');
                            } elseif ($delivery_stock_result[$i]['model_inventory'] == 2) {
                                $delivery_stock_result[$i]['storage'] = get_warehouse_area_goods($delivery_stock_result[$i]['area_id'], $delivery_stock_result[$i]['goods_id'], 'warehouse_area_goods');
                            }
                        } else {
                            $products = $this->goodsWarehouseService->getWarehouseAttrNumber($delivery_stock_result[$i]['goods_id'], $delivery_stock_result[$i]['goods_attr_id'], $delivery_stock_result[$i]['warehouse_id'], $delivery_stock_result[$i]['area_id'], $delivery_stock_result[$i]['area_city'], $delivery_stock_result[$i]['model_attr']);
                            $delivery_stock_result[$i]['storage'] = $products['product_number'];
                        }

                        if (($delivery_stock_result[$i]['sums'] > $delivery_stock_result[$i]['storage'] || $delivery_stock_result[$i]['storage'] <= 0) && (($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) || ($GLOBALS['_CFG']['use_storage'] == '0' && $delivery_stock_result[$i]['is_real'] == 0))) {
                            /* 操作失败 */
                            $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                            //return sys_msg(sprintf(lang('seller/order.act_good_vacancy'), $value['goods_name']), 1, $links);
                            break;
                        }

                        /* 虚拟商品列表 virtual_card */
                        if ($delivery_stock_result[$i]['is_real'] == 0) {
                            $virtual_goods[] = [
                                'goods_id' => $delivery_stock_result[$i]['goods_id'],
                                'goods_name' => $delivery_stock_result[$i]['goods_name'],
                                'num' => $delivery_stock_result[$i]['send_number']
                            ];
                        }
                    }
                    //ecmoban模板堂 --zhuo end 下单减库存

                    /* 发货 */
                    /* 处理虚拟卡 商品（虚货） */
                    if ($virtual_goods && is_array($virtual_goods) && count($virtual_goods) > 0) {
                        foreach ($virtual_goods as $virtual_value) {
                            virtual_card_shipping($virtual_value, $order['order_sn'], $msg, 'split');
                        }

                        //虚拟卡缺货
                        if (!empty($msg)) {
                            continue;
                        }
                    }

                    /* 如果使用库存，且发货时减库存，则修改库存 */
                    if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                        foreach ($delivery_stock_result as $value) {

                            /* 商品（实货）、超级礼包（实货） ecmoban模板堂 --zhuo */
                            if ($value['is_real'] != 0) {
                                //（货品）
                                if (!empty($value['product_id'])) {
                                    if ($value['model_attr'] == 1) {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('products_warehouse') . "
													SET product_number = product_number - " . $value['sums'] . "
													WHERE product_id = " . $value['product_id'];
                                    } elseif ($value['model_attr'] == 2) {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('products_area') . "
													SET product_number = product_number - " . $value['sums'] . "
													WHERE product_id = " . $value['product_id'];
                                    } else {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('products') . "
													SET product_number = product_number - " . $value['sums'] . "
													WHERE product_id = " . $value['product_id'];
                                    }
                                } else {
                                    if ($value['model_inventory'] == 1) {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('warehouse_goods') . "
													SET region_number = region_number - " . $value['sums'] . "
													WHERE goods_id = " . $value['goods_id'] . " AND region_id = " . $value['warehouse_id'];
                                    } elseif ($value['model_inventory'] == 2) {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('warehouse_area_goods') . "
													SET region_number = region_number - " . $value['sums'] . "
													WHERE goods_id = " . $value['goods_id'] . " AND region_id = " . $value['area_id'];
                                    } else {
                                        $minus_stock_sql = "UPDATE " . $this->dsc->table('goods') . "
													SET goods_number = goods_number - " . $value['sums'] . "
													WHERE goods_id = " . $value['goods_id'];
                                    }
                                }

                                $this->db->query($minus_stock_sql, 'SILENT');

                                //库存日志
                                $logs_other = [
                                    'goods_id' => $value['goods_id'],
                                    'order_id' => $value['order_id'],
                                    'use_storage' => $GLOBALS['_CFG']['stock_dec_time'],
                                    'admin_id' => session('seller_id'),
                                    'number' => "- " . $value['sums'],
                                    'model_inventory' => $value['model_inventory'],
                                    'model_attr' => $value['model_attr'],
                                    'product_id' => $value['product_id'],
                                    'warehouse_id' => $value['warehouse_id'],
                                    'area_id' => $value['area_id'],
                                    'add_time' => gmtime()
                                ];

                                $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                            }
                        }
                    }

                    /* 修改发货单信息 */
                    $invoice_no = str_replace(',', ',', $delivery['invoice_no']);
                    $invoice_no = trim($invoice_no, ',');
                    $_delivery['invoice_no'] = $invoice_no;
                    $_delivery['status'] = DELIVERY_SHIPPED; // 0，为已发货
                    $query = $this->db->autoExecute($this->dsc->table('delivery_order'), $_delivery, 'UPDATE', "delivery_id = $delivery_id", 'SILENT');
                    if (!$query) {
                        /* 操作失败 */
                        $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                        //return sys_msg(lang('seller/order.act_false'), 1, $links);
                        continue;
                    }

                    /* 标记订单为已确认 “已发货” */
                    /* 更新发货时间 */
                    $order_finish = $this->get_all_delivery_finish($order_id);
                    $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
                    $arr['shipping_status'] = $shipping_status;
                    $arr['shipping_time'] = GMTIME_UTC; // 发货时间
                    $arr['invoice_no'] = trim($order['invoice_no'] . '<br>' . $invoice_no, '<br>');
                    update_order($order_id, $arr);

                    if (empty($_delivery['invoice_no'])) {
                        $_delivery['invoice_no'] = "N/A";
                    }

                    $_note = sprintf(lang('seller/order.order_ship_delivery'), $delivery_order['delivery_sn']) . "<br/>" . sprintf(lang('seller/order.order_ship_invoice'), $_delivery['invoice_no']);

                    /* 发货单发货记录log */
                    order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $_note . $action_note, session('seller_name'), 1);

                    /* 如果当前订单已经全部发货 */
                    if ($order_finish) {
                        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                        if ($order['user_id'] > 0) {

                            /* 计算并发放积分 */
                            $integral = integral_to_give($order);
                            /* 如果已配送子订单的赠送积分大于0   减去已配送子订单积分 */
                            if (!empty($child_order)) {
                                $integral['custom_points'] = $integral['custom_points'] - $child_order['custom_points'];
                                $integral['rank_points'] = $integral['rank_points'] - $child_order['rank_points'];
                            }
                            log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));

                            /* 发放红包 */
                            send_order_bonus($order_id);

                            /* 发放优惠券 bylu */
                            send_order_coupons($order_id);
                        }

                        /* 发送邮件 */
                        $cfg = $GLOBALS['_CFG']['send_ship_email'];
                        if ($cfg == '1') {
                            $order['invoice_no'] = $invoice_no;
                            $tpl = get_mail_template('deliver_notice');
                            $this->smarty->assign('order', $order);
                            $this->smarty->assign('send_time', local_date($GLOBALS['_CFG']['time_format']));
                            $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                            $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                            $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                            //$this->smarty->assign('confirm_url', $this->dsc->url() . 'receive.php?id=' . $order['order_id'] . '&con=' . rawurlencode($order['consignee']));
                            $this->smarty->assign('confirm_url', $this->dsc->url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']); //by wu
                            $this->smarty->assign('send_msg_url', $this->dsc->url() . 'user_message.php?act=message_list&order_id=' . $order['order_id']);
                            $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                            if (!$this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                                $msg = lang('seller/order.send_mail_fail');
                            }
                        }

                        /* 如果需要，发短信 */
                        if ($GLOBALS['_CFG']['sms_order_shipped'] == '1' && $order['mobile'] != '') {

                            //短信接口参数
                            if ($order['ru_id']) {
                                $shop_name = $this->merchantCommonService->getShopName($order['ru_id'], 1);
                            } else {
                                $shop_name = "";
                            }

                            $user_info = get_admin_user_info($order['user_id']);

                            $smsParams = [
                                'shop_name' => $shop_name,
                                'shopname' => $shop_name,
                                'user_name' => $user_info['user_name'],
                                'username' => $user_info['user_name'],
                                'consignee' => $order['consignee'],
                                'order_sn' => $order['order_sn'],
                                'ordersn' => $order['order_sn'],
                                'mobile_phone' => $order['mobile'],
                                'mobilephone' => $order['mobile']
                            ];

                            $this->commonRepository->smsSend($order['mobile'], $smsParams, 'sms_order_shipped', false);
                        }

                        // 微信通模板消息 发货通知
                        if (file_exists(MOBILE_WECHAT)) {
                            $pushData = [
                                'first' => ['value' => $GLOBALS['_LANG']['you_order_delivery']], // 标题
                                'keyword1' => ['value' => $order['order_sn']], //订单
                                'keyword2' => ['value' => $order['shipping_name']], //物流服务
                                'keyword3' => ['value' => $invoice_no], //快递单号
                                'keyword4' => ['value' => $order['consignee']], // 收货信息
                                'remark' => ['value' => $GLOBALS['_LANG']['order_delivery_wait']]
                            ];
                            $shop_url = url('/') . '/'; // 根域名 兼容商家后台
                            $order_url = dsc_url('/#/user/orderDetail/' . $order_id);

                            push_template_curl('OPENTM202243318', $pushData, $order_url, $order['user_id'], $shop_url);
                        }

                        /* 更新商品销量 */
                        get_goods_sale($order_id);
                    }

                    /* 更新会员订单信息 */
                    $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

                    /* 清除缓存 */
                    clear_cache_files();

                    /* 操作成功 */
                    $links[] = ['text' => lang('seller/common.09_delivery_order'), 'href' => 'order.php?act=delivery_list'];
                    $links[] = ['text' => lang('seller/order.delivery_sn') . lang('seller/order.detail'), 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                    continue;
                }

                /* 返回 */
                return sys_msg($GLOBALS['_LANG']['batch_delivery_success'], 0, [['href' => 'order.php?act=delivery_list', 'text' => lang('seller/order.return_list')]]);
            }

            /*  @bylu 判断当前退款订单是否为白条支付订单(白条支付订单退款只能退到白条额度) start */
            $sql = "select log_id from {$this->dsc->table('baitiao_log')} where order_id" . db_create_in(explode(',', $order_id));
            $baitiao = $this->db->getOne($sql);
            if ($baitiao) {
                $this->smarty->assign('is_baitiao', $baitiao); // 是否要求填写备注
            }
            /*  @bylu  end */


            /* 直接处理还是跳到详细页面 ecmoban模板堂 --zhuo ($require_note && $action_note == '')*/
            $require_note = (isset($require_note) && !empty($require_note)) ? $require_note : '';
            if ($require_note || isset($show_invoice_no) || isset($show_refund)) {

                /* 模板赋值 */
                $this->smarty->assign('require_note', $require_note); // 是否要求填写备注
                $this->smarty->assign('action_note', $action_note);   // 备注
                $this->smarty->assign('show_cancel_note', isset($show_cancel_note)); // 是否显示取消原因
                $this->smarty->assign('show_invoice_no', isset($show_invoice_no)); // 是否显示发货单号
                $this->smarty->assign('show_refund', isset($show_refund)); // 是否显示退款
                $this->smarty->assign('show_refund1', isset($show_refund1)); // 是否显示退款 // by Leah
                $this->smarty->assign('anonymous', isset($anonymous) ? $anonymous : true); // 是否匿名
                $this->smarty->assign('order_id', $order_id); // 订单id
                $this->smarty->assign('rec_id', $rec_id); // 订单商品id    //by Leah
                $this->smarty->assign('ret_id', $ret_id); // 订单商品id   // by Leah
                $this->smarty->assign('batch', $batch);   // 是否批处理
                $this->smarty->assign('operation', $operation); // 操作
                $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '12_back_apply']);
                // 如果是确认收货，左侧菜单当前位置设为订单列表
                if (isset($_POST['receive'])) {
                    $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);
                }
                /* 显示模板 */
                $this->smarty->assign('ur_here', lang('seller/order.order_operate') . $action);

                return $this->smarty->display('order_operate.dwt');
            } else {

                $operation = $operation ?? '';

                /* 直接处理 */
                if (!$batch) {
                    // by　Leah S
                    if (!empty($_REQUEST['ret_id'])) {
                        return dsc_header("Location: order.php?act=operate_post&order_id=" . $order_id .
                            "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "&rec_id=" . $rec_id . "&ret_id=" . $ret_id . "\n");
                    } else {

                        /* 一个订单 */
                        return dsc_header("Location: order.php?act=operate_post&order_id=" . $order_id .
                            "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "\n");
                    }
                    //by Leah E
                } else {
                    /* 多个订单 */
                    return dsc_header("Location: order.php?act=batch_operate_post&order_id=" . $order_id .
                        "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "\n");
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 操作订单状态（处理批量提交）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'batch_operate_post') {
            /* 检查权限 */
            admin_priv('order_os_edit');

            $this->smarty->assign('menu_select', ['action' => '04_order', 'current' => '02_order_list']);

            /* 取得参数 */
            $order_id = $_REQUEST['order_id'];        // 订单id（逗号格开的多个订单id）
            $operation = $_REQUEST['operation'];       // 订单操作
            $action_note = $_REQUEST['action_note'];     // 操作备注

            if ($order_id) {
                $order_id = $this->dscRepository->delStrComma($order_id);
                $order_id_list = explode(',', $order_id);
            } else {
                $order_id_list = [];
            }

            /* 初始化处理的订单sn */
            $sn_list = [];
            $sn_not_list = [];

            /* 确认 */
            if ('confirm' == $operation) {
                foreach ($order_id_list as $id_order) {
                    $sql = "SELECT * FROM " . $this->dsc->table('order_info') .
                        " WHERE order_sn = '$id_order'" .
                        " AND order_status = '" . OS_UNCONFIRMED . "'";
                    $order = $this->db->getRow($sql);

                    if ($order) {
                        /* 检查能否操作 */
                        $operable_list = $this->operable_list($order);
                        if (!isset($operable_list[$operation])) {
                            $sn_not_list[] = $id_order;
                            continue;
                        }

                        if ($order['order_status'] == OS_RETURNED || $order['order_status'] == OS_RETURNED_PART) {
                            continue;
                        }

                        $order_id = $order['order_id'];

                        /* 标记订单为已确认 */
                        update_order($order_id, ['order_status' => OS_CONFIRMED, 'confirm_time' => gmtime()]);
                        $this->update_order_amount($order_id);

                        /* 记录log */
                        order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $action_note, session('seller_name'));

                        /* 发送邮件 */
                        if ($GLOBALS['_CFG']['send_confirm_email'] == '1') {
                            $tpl = get_mail_template('order_confirm');
                            $order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
                            $this->smarty->assign('order', $order);
                            $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                            $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
                            $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
                            $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                            $this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                        }

                        $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

                        $sn_list[] = $order['order_sn'];
                    } else {
                        $sn_not_list[] = $id_order;
                    }
                }

                $sn_str = $GLOBALS['_LANG']['confirm_order'];
                $this->smarty->assign('ur_here', lang('seller/order.order_operate') . lang('seller/order.op_confirm'));
            } /* 无效 */
            elseif ('invalid' == $operation) {
                foreach ($order_id_list as $id_order) {
                    $sql = "SELECT * FROM " . $this->dsc->table('order_info') .
                        " WHERE order_sn = $id_order" . $this->orderService->orderQuerySql('unpay_unship');

                    $order = $this->db->getRow($sql);

                    /*判断门店订单，获取门店id by kong */
                    $store_order_id = get_store_id($order['order_id']);
                    $store_id = ($store_order_id > 0) ? $store_order_id : 0;

                    if ($order) {
                        /* 检查能否操作 */
                        $operable_list = $this->operable_list($order);
                        if (!isset($operable_list[$operation])) {
                            $sn_not_list[] = $id_order;
                            continue;
                        }

                        $order_id = $order['order_id'];

                        /* 标记订单为“无效” */
                        update_order($order_id, ['order_status' => OS_INVALID]);

                        /* 记录log */
                        order_action($order['order_sn'], OS_INVALID, SS_UNSHIPPED, PS_UNPAYED, $action_note, session('seller_name'));

                        /* 如果使用库存，且下订单时减库存，则增加库存 */
                        if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                            change_order_goods_storage($order_id, false, SDT_PLACE, 2, session('seller_id'), $store_id);
                        }

                        /* 发送邮件 */
                        if ($GLOBALS['_CFG']['send_invalid_email'] == '1') {
                            $tpl = get_mail_template('order_invalid');
                            $this->smarty->assign('order', $order);
                            $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                            $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
                            $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
                            $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                            $this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                        }

                        /* 退还用户余额、积分、红包 */
                        return_user_surplus_integral_bonus($order);

                        $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

                        $sn_list[] = $order['order_sn'];
                    } else {
                        $sn_not_list[] = $id_order;
                    }
                }

                $sn_str = $GLOBALS['_LANG']['invalid_order'];
            } elseif ('cancel' == $operation) {
                foreach ($order_id_list as $id_order) {
                    $sql = "SELECT * FROM " . $this->dsc->table('order_info') .
                        " WHERE order_sn = $id_order" . $this->orderService->orderQuerySql('unpay_unship');

                    $order = $this->db->getRow($sql);

                    /*判断门店订单，获取门店id by kong */

                    $store_order_id = get_store_id($order['order_id']);
                    $store_id = ($store_order_id > 0) ? $store_order_id : 0;
                    if ($order) {
                        /* 检查能否操作 */
                        $operable_list = $this->operable_list($order);
                        if (!isset($operable_list[$operation])) {
                            $sn_not_list[] = $id_order;
                            continue;
                        }

                        $order_id = $order['order_id'];

                        /* 标记订单为“取消”，记录取消原因 */
                        $cancel_note = trim($_REQUEST['cancel_note']);
                        update_order($order_id, ['order_status' => OS_CANCELED, 'to_buyer' => $cancel_note]);

                        /* 记录log */
                        order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, $action_note, session('seller_name'));

                        /* 如果使用库存，且下订单时减库存，则增加库存 */
                        if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                            change_order_goods_storage($order_id, false, SDT_PLACE, 3, session('seller_id'), $store_id);
                        }

                        /* 发送邮件 */
                        if ($GLOBALS['_CFG']['send_cancel_email'] == '1') {
                            $tpl = get_mail_template('order_cancel');
                            $this->smarty->assign('order', $order);
                            $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                            $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
                            $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
                            $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                            $this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                        }

                        /* 退还用户余额、积分、红包 */
                        return_user_surplus_integral_bonus($order);

                        $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

                        $sn_list[] = $order['order_sn'];
                    } else {
                        $sn_not_list[] = $id_order;
                    }
                }

                $sn_str = $GLOBALS['_LANG']['cancel_order'];
            } elseif ('remove' == $operation) {
                foreach ($order_id_list as $id_order) {
                    /* 检查能否操作 */
                    $order = order_info('', $id_order);
                    $operable_list = $this->operable_list($order);
                    if (!isset($operable_list['remove'])) {
                        $sn_not_list[] = $id_order;
                        continue;
                    }

                    $return_order = return_order_info(0, '', $order['order_id']);
                    if ($return_order) {
                        return sys_msg(sprintf(lang('seller/order.order_remove_failure'), $order['order_sn']), 0, [['href' => 'order.php?act=list&' . list_link_postfix(), 'text' => lang('seller/order.return_list')]]);
                    }

                    /* 删除订单 */
                    $this->db->query("DELETE FROM " . $this->dsc->table('order_info') . " WHERE order_id = '$order[order_id]'");
                    $this->db->query("DELETE FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '$order[order_id]'");
                    $this->db->query("DELETE FROM " . $this->dsc->table('order_action') . " WHERE order_id = '$order[order_id]'");

                    $this->db->query("DELETE FROM " . $this->dsc->table('store_order') . " WHERE order_id = '$order[order_id]'");

                    $action_array = ['delivery', 'back'];
                    $this->del_delivery($order['order_id'], $action_array);

                    /* todo 记录日志 */
                    admin_log($order['order_sn'], 'remove', 'order');

                    $sn_list[] = $order['order_sn'];
                }

                $sn_str = $GLOBALS['_LANG']['remove_order'];
                $this->smarty->assign('ur_here', lang('seller/order.order_operate') . $GLOBALS['_LANG']['remove']);

                $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);
            } else {
                return 'invalid params';
            }

            /* 取得备注信息 */
//    $action_note = $_REQUEST['action_note'];

            if (empty($sn_not_list)) {
                $sn_list = empty($sn_list) ? '' : $GLOBALS['_LANG']['updated_order'] . join($sn_list, ',');
                $msg = $sn_list;
                $links[] = ['text' => lang('seller/order.return_list'), 'href' => 'order.php?act=list&' . list_link_postfix()];
                return sys_msg($msg, 0, $links);
            } else {
                $order_list_no_fail = [];
                $sql = "SELECT * FROM " . $this->dsc->table('order_info') .
                    " WHERE order_sn " . db_create_in($sn_not_list);
                $res = $this->db->query($sql);
                foreach ($res as $row) {
                    $order_list_no_fail[$row['order_id']]['order_id'] = $row['order_id'];
                    $order_list_no_fail[$row['order_id']]['order_sn'] = $row['order_sn'];
                    $order_list_no_fail[$row['order_id']]['order_status'] = $row['order_status'];
                    $order_list_no_fail[$row['order_id']]['shipping_status'] = $row['shipping_status'];
                    $order_list_no_fail[$row['order_id']]['pay_status'] = $row['pay_status'];

                    $order_list_fail = '';
                    foreach ($this->operable_list($row) as $key => $value) {
                        if ($key != $operation) {
                            $order_list_fail .= $GLOBALS['_LANG']['op_' . $key] . ',';
                        }
                    }
                    $order_list_no_fail[$row['order_id']]['operable'] = $order_list_fail;
                }

                /* 模板赋值 */
                $this->smarty->assign('order_info', $sn_str);
                $this->smarty->assign('action_link', ['href' => 'order.php?act=list', 'text' => lang('seller/common.02_order_list'), 'class' => 'icon-reply']);
                $this->smarty->assign('order_list', $order_list_no_fail);

                /* 显示模板 */

                return $this->smarty->display('order_operate_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 操作订单状态（处理提交）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'operate_post') {
            /* 检查权限 */
            admin_priv('order_os_edit');

            /* 取得参数 */
            $order_id = intval(trim($_REQUEST['order_id']));        // 订单id
            $rec_id = empty($_REQUEST['rec_id']) ? 0 : $_REQUEST['rec_id'];     //by　Leah
            $ret_id = empty($_REQUEST['ret_id']) ? 0 : $_REQUEST['ret_id'];  //by Leah
            $goods_delivery = isset($_REQUEST['delivery']) && !empty($_REQUEST['delivery']) ? $_REQUEST['delivery'] : [];  //by Leah
            $return = '';   //by leah
            //by Leah S
            if ($ret_id) {
                $return = 1;
            }
            //by Leah E
            $operation = $_REQUEST['operation'];                 // 订单操作

            /* 查询订单信息 */
            $order = order_info($order_id);

            /*判断门店订单，获取门店id by kong */
            $store_order_id = get_store_id($order_id);
            $store_id = ($store_order_id > 0) ? $store_order_id : 0;

            /* 订单单商品发货操作不检测 */
            if (empty($goods_delivery)) {
                /* 检查能否操作 */
                $operable_list = $this->operable_list($order);
                if (!isset($operable_list[$operation])) {
                    die('Hacking attempt');
                }
            }

            /* 取得备注信息 */
            $action_note = !empty($_REQUEST['action_note']) ? $_REQUEST['action_note'] : '0';

            /* 初始化提示信息 */
            $msg = '';

            /* 确认 */
            if ('confirm' == $operation) {
                /* 标记订单为已确认 */
                update_order($order_id, ['order_status' => OS_CONFIRMED, 'confirm_time' => gmtime()]);
                $this->update_order_amount($order_id);

                /* 记录log */
                order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $action_note, session('seller_name'));

                /* 如果原来状态不是“未确认”，且使用库存，且下订单时减库存，则减少库存 */
                if ($order['order_status'] != OS_UNCONFIRMED && $GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                    change_order_goods_storage($order_id, true, SDT_PLACE, 4, session('seller_id'), $store_id);
                }

                /* 发送邮件 */
                $cfg = $GLOBALS['_CFG']['send_confirm_email'];
                if ($cfg == '1') {
                    $tpl = get_mail_template('order_confirm');
                    $this->smarty->assign('order', $order);
                    $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
                    $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                    if (!$this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                        $msg = lang('seller/order.send_mail_fail');
                    }
                }

                $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);
            } /* 付款 */
            elseif ('pay' == $operation) {
                /* 检查权限 */
                admin_priv('order_ps_edit');

                /* 标记订单为已确认、已付款，更新付款时间和已支付金额，如果是货到付款，同时修改订单为“收货确认” */
                if ($order['order_status'] != OS_CONFIRMED) {
                    $arr['order_status'] = OS_CONFIRMED;
                    $arr['confirm_time'] = gmtime();
                    if ($GLOBALS['_CFG']['sales_volume_time'] == SALES_PAY) {
                        $arr['is_update_sale'] = 1;
                    }
                }
                $arr['pay_time'] = gmtime();
                //预售定金处理
                if ($order['extension_code'] == 'presale' && $order['pay_status'] == 0) {
                    $arr['pay_status'] = PS_PAYED_PART;
                    $arr['money_paid'] = $order['money_paid'] + $order['order_amount'];
                    $arr['order_amount'] = $order['goods_amount'] + $order['shipping_fee'] + $order['insure_fee'] + $order['pay_fee'] + $order['tax'] + $order['pack_fee'] + $order['card_fee'] -
                        $order['surplus'] - $order['money_paid'] - $order['integral_money'] - $order['bonus'] - $order['order_amount'] - $order['discount'];
                } else {
                    $arr['pay_status'] = PS_PAYED;
                    $arr['money_paid'] = $order['money_paid'] + $order['order_amount'];
                    $arr['order_amount'] = 0;
                }

                $payment = payment_info($order['pay_id']);

                if ($payment['is_cod']) {
                    $arr['shipping_status'] = SS_RECEIVED;
                    $order['shipping_status'] = SS_RECEIVED;
                }

                update_order($order_id, $arr);

                //付款成功创建快照
                create_snapshot($order_id);

                /* 如果使用库存，且付款时减库存，且订单金额为0，则减少库存 */
                $sql = 'SELECT store_id FROM ' . $this->dsc->table("store_order") . " WHERE order_id = '$order_id' LIMIT 1";
                $store_id = $this->db->getOne($sql);
                if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PAID) {
                    change_order_goods_storage($order_id, true, SDT_PAID, $GLOBALS['_CFG']['stock_dec_time'], 0, $store_id);
                }

                /* 更新商品销量 ecmoban模板堂 --zhuo */
                get_goods_sale($order_id);

                //门店处理 付款成功发送短信
                $sql = 'SELECT store_id,pick_code,order_id FROM ' . $this->dsc->table("store_order") . " WHERE order_id = '$order_id' LIMIT 1";
                $stores_order = $this->db->getRow($sql);
                $user_mobile_phone = '';
                $sql = "SELECT mobile_phone,user_name FROM " . $this->dsc->table('users') . " WHERE user_id = '" . $order['user_id'] . "' LIMIT 1";
                $orderUsers = $this->db->getRow($sql);
                if ($stores_order['store_id'] > 0) {
                    if ($order['mobile']) {
                        $user_mobile_phone = $order['mobile'];
                    } else {
                        $user_mobile_phone = $orderUsers['mobile_phone'];
                    }
                }
                if ($user_mobile_phone != '') {
                    //门店短信处理
                    $store_smsParams = '';
                    $sql = "SELECT id, country, province, city, district, stores_address, stores_name, stores_tel FROM " . $this->dsc->table('offline_store') . " WHERE id = '" . $stores_order['store_id'] . "' LIMIT 1";
                    $stores_info = $this->db->getRow($sql);
                    $store_address = get_area_region_info($stores_info) . $stores_info['stores_address'];
                    $user_name = !empty($orderUsers['user_name']) ? $orderUsers['user_name'] : '';

                    //门店订单->短信接口参数
                    $store_smsParams = [
                        'user_name' => $user_name,
                        'username' => $user_name,
                        'order_sn' => $order['order_sn'],
                        'ordersn' => $order['order_sn'],
                        'code' => $stores_order['pick_code'],
                        'store_address' => $store_address,
                        'storeaddress' => $store_address,
                        'mobile_phone' => $user_mobile_phone,
                        'mobilephone' => $user_mobile_phone
                    ];

                    $this->commonRepository->smsSend($user_mobile_phone, $store_smsParams, 'store_order_code');
                }

                $confirm_take_time = gmtime();
                if (($arr['order_status'] == OS_CONFIRMED || $arr['order_status'] == OS_SPLITED) && $arr['pay_status'] == PS_PAYED && (isset($arr['shipping_status']) && $arr['shipping_status'] == SS_RECEIVED)) {

                    /* 查询订单信息，检查状态 */
                    $res = OrderInfo::where('order_id', $order_id);

                    $res = $res->with([
                        'getSellerNegativeOrder'
                    ]);

                    $bill_order = $this->baseRepository->getToArrayFirst($res);

                    $seller_id = $this->db->getOne("SELECT ru_id FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '$order_id'", true);
                    $value_card = $this->db->getOne("SELECT use_val FROM " . $this->dsc->table('value_card_record') . " WHERE order_id = '$order_id'", true);

                    if (empty($bill_order['get_seller_negative_order'])) {
                        $return_amount_info = $this->orderRefoundService->orderReturnAmount($order_id);
                    } else {
                        $return_amount_info['return_amount'] = 0;
                        $return_amount_info['return_rate_fee'] = 0;
                        $return_amount_info['ret_id'] = [];
                    }

                    if ($bill_order['order_amount'] > 0 && $bill_order['order_amount'] > $bill_order['rate_fee']) {
                        $order_amount = $bill_order['order_amount'] - $bill_order['rate_fee'];
                    } else {
                        $order_amount = $bill_order['order_amount'];
                    }

                    $other = [
                        'user_id' => $bill_order['user_id'],
                        'seller_id' => $seller_id,
                        'order_id' => $bill_order['order_id'],
                        'order_sn' => $bill_order['order_sn'],
                        'order_status' => $bill_order['order_status'],
                        'shipping_status' => SS_RECEIVED,
                        'pay_status' => $bill_order['pay_status'],
                        'order_amount' => $order_amount,
                        'return_amount' => $return_amount_info['return_amount'],
                        'goods_amount' => $bill_order['goods_amount'],
                        'tax' => $bill_order['tax'],
                        'shipping_fee' => $bill_order['shipping_fee'],
                        'insure_fee' => $bill_order['insure_fee'],
                        'pay_fee' => $bill_order['pay_fee'],
                        'pack_fee' => $bill_order['pack_fee'],
                        'card_fee' => $bill_order['card_fee'],
                        'bonus' => $bill_order['bonus'],
                        'integral_money' => $bill_order['integral_money'],
                        'coupons' => $bill_order['coupons'],
                        'discount' => $bill_order['discount'],
                        'value_card' => $value_card,
                        'money_paid' => $bill_order['money_paid'],
                        'surplus' => $bill_order['surplus'],
                        'confirm_take_time' => $confirm_take_time,
                        'rate_fee' => $bill_order['rate_fee'],
                        'return_rate_fee' => $return_amount_info['return_rate_price']
                    ];

                    if ($seller_id) {
                        $this->commissionService->getOrderBillLog($other);
                        $this->commissionService->setBillOrderReturn($return_amount_info['ret_id'], $other['order_id']);
                    }
                }

                /* 记录log */
                if ($order['extension_code'] == 'presale' && $order['pay_status'] == 0) {
                    order_action($order['order_sn'], OS_CONFIRMED, $order['shipping_status'], PS_PAYED_PART, $action_note, session('seller_name'));
                    /* 更新 pay_log */
                    update_pay_log($order_id);
                } else {
                    order_action($order['order_sn'], OS_CONFIRMED, $order['shipping_status'], PS_PAYED, $action_note, session('seller_name'), 0, $confirm_take_time);
                }

                /* 更新会员订单信息 */
                $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

            } /* 配货 */
            elseif ('prepare' == $operation) {
                /* 标记订单为已确认，配货中 */
                if ($order['order_status'] != OS_CONFIRMED) {
                    $arr['order_status'] = OS_CONFIRMED;
                    $arr['confirm_time'] = gmtime();
                }
                $arr['shipping_status'] = SS_PREPARING;
                update_order($order_id, $arr);

                /* 记录log */
                order_action($order['order_sn'], OS_CONFIRMED, SS_PREPARING, $order['pay_status'], $action_note, session('seller_name'));

                /* 清除缓存 */
                clear_cache_files();
            } /* 分单确认 */
            elseif ('split' == $operation) {
                /* 检查权限 */
                admin_priv('order_ss_edit');

                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳
                $delivery_info = get_delivery_info($order_id);
                if (true) {
                    /* 获取表单提交数据 */
                    $suppliers_id = isset($_REQUEST['suppliers_id']) ? intval(trim($_REQUEST['suppliers_id'])) : '0';

                    array_walk($_REQUEST['delivery'], [$this, "trim_array_walk"]);
                    $delivery = $_REQUEST['delivery'];
                    array_walk($_REQUEST['send_number'], [$this, "trim_array_walk"]);
                    array_walk($_REQUEST['send_number'], [$this, "intval_array_walk"]);

                    $send_number = $_REQUEST['send_number'];
                    $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';
                    $delivery['user_id'] = intval($delivery['user_id']);
                    $delivery['country'] = intval($delivery['country']);
                    $delivery['province'] = intval($delivery['province']);
                    $delivery['city'] = intval($delivery['city']);
                    $delivery['district'] = intval($delivery['district']);
                    $delivery['agency_id'] = intval($delivery['agency_id']);
                    $delivery['insure_fee'] = floatval($delivery['insure_fee']);
                    $delivery['shipping_fee'] = floatval($delivery['shipping_fee']);

                    /* 订单是否已全部分单检查 */
                    if ($order['order_status'] == OS_SPLITED) {
                        /* 操作失败 */
                        $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                        return sys_msg(sprintf(
                            $GLOBALS['_LANG']['order_splited_sms'],
                            $order['order_sn'],
                            $GLOBALS['_LANG']['os'][OS_SPLITED],
                            $GLOBALS['_LANG']['ss'][SS_SHIPPED_ING],
                            $GLOBALS['_CFG']['shop_name']
                        ), 1, $links);
                    }

                    /* 取得订单商品 */
                    $_goods = $this->get_order_goods(['order_id' => $order_id, 'order_sn' => $delivery['order_sn']]);
                    $goods_list = $_goods['goods_list'];

                    /* 检查此单发货数量填写是否正确 合并计算相同商品和货品 */
                    if (!empty($send_number) && !empty($goods_list)) {
                        $goods_no_package = [];
                        foreach ($goods_list as $key => $value) {
                            /* 去除 此单发货数量 等于 0 的商品 */
                            if (!isset($value['package_goods_list']) || !is_array($value['package_goods_list'])) {
                                // 如果是货品则键值为商品ID与货品ID的组合
                                $_key = empty($value['product_id']) ? $value['goods_id'] : ($value['goods_id'] . '_' . $value['product_id']);

                                // 统计此单商品总发货数 合并计算相同ID商品或货品的发货数
                                if (isset($send_number[$value['rec_id']]) && $send_number[$value['rec_id']] > 0) {
                                    if (empty($goods_no_package[$_key])) {
                                        $goods_no_package[$_key] = $send_number[$value['rec_id']];
                                    } else {
                                        $goods_no_package[$_key] += $send_number[$value['rec_id']];
                                    }
                                }

                                //去除
                                if (!isset($send_number[$value['rec_id']]) || $send_number[$value['rec_id']] <= 0) {
                                    unset($send_number[$value['rec_id']], $goods_list[$key]);
                                    continue;
                                }
                            } else {
                                /* 组合超值礼包信息 */
                                $goods_list[$key]['package_goods_list'] = $this->package_goods($value['package_goods_list'], $value['goods_number'], $value['order_id'], $value['extension_code'], $value['goods_id']);

                                /* 超值礼包 */
                                foreach ($value['package_goods_list'] as $pg_key => $pg_value) {
                                    // 如果是货品则键值为商品ID与货品ID的组合
                                    $_key = empty($pg_value['product_id']) ? $pg_value['goods_id'] : ($pg_value['goods_id'] . '_' . $pg_value['product_id']);

                                    //统计此单商品总发货数 合并计算相同ID产品的发货数
                                    if (isset($send_number[$value['rec_id']]) && $send_number[$value['rec_id']] > 0) {
                                        if (empty($goods_no_package[$_key])) {
                                            $goods_no_package[$_key] = $send_number[$value['rec_id']][$pg_value['g_p']];
                                        } //否则已经存在此键值
                                        else {
                                            $goods_no_package[$_key] += $send_number[$value['rec_id']][$pg_value['g_p']];
                                        }
                                    }

                                    //去除
                                    if (!isset($send_number[$value['rec_id']]) || $send_number[$value['rec_id']][$pg_value['g_p']] <= 0) {
                                        unset($send_number[$value['rec_id']][$pg_value['g_p']], $goods_list[$key]['package_goods_list'][$pg_key]);
                                    }
                                }

                                if (count($goods_list[$key]['package_goods_list']) <= 0) {
                                    unset($send_number[$value['rec_id']], $goods_list[$key]);
                                    continue;
                                }
                            }

                            /* 发货数量与总量不符 */
                            if (!isset($value['package_goods_list']) || !is_array($value['package_goods_list'])) {
                                $sended = $this->order_delivery_num($order_id, $value['goods_id'], $value['product_id']);
                                if (($value['goods_number'] - $sended - $send_number[$value['rec_id']]) < 0) {
                                    /* 操作失败 */
                                    $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                                    return sys_msg($GLOBALS['_LANG']['act_ship_num'], 1, $links);
                                }
                            } else {
                                /* 超值礼包 */
                                foreach ($goods_list[$key]['package_goods_list'] as $pg_key => $pg_value) {
                                    if (($pg_value['order_send_number'] - $pg_value['sended'] - $send_number[$value['rec_id']][$pg_value['g_p']]) < 0) {
                                        /* 操作失败 */
                                        $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                                        return sys_msg($GLOBALS['_LANG']['act_ship_num'], 1, $links);
                                    }
                                }
                            }
                        }
                    }

                    /* 对上一步处理结果进行判断 兼容 上一步判断为假情况的处理 */
                    if (empty($send_number) || empty($goods_list)) {
                        /* 操作失败 */
                        $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                        return sys_msg(lang('seller/order.act_false'), 1, $links);
                    }

                    /* 检查此单发货商品库存缺货情况 */
                    /* $goods_list已经过处理 超值礼包中商品库存已取得 */
                    $virtual_goods = [];
                    $package_virtual_goods = [];

                    foreach ($goods_list as $key => $value) {
                        // 商品（超值礼包）
                        if ($value['extension_code'] == 'package_buy') {
                            foreach ($value['package_goods_list'] as $pg_key => $pg_value) {
                                if ($pg_value['goods_number'] < $goods_no_package[$pg_value['g_p']] && (($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) || ($GLOBALS['_CFG']['use_storage'] == '0' && $pg_value['is_real'] == 0))) {
                                    /* 操作失败 */
                                    $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                                    return sys_msg(sprintf(lang('seller/order.act_good_vacancy'), $pg_value['goods_name']), 1, $links);
                                }

                                /* 商品（超值礼包） 虚拟商品列表 package_virtual_goods*/
                                if ($pg_value['is_real'] == 0) {
                                    $package_virtual_goods[] = [
                                        'goods_id' => $pg_value['goods_id'],
                                        'goods_name' => $pg_value['goods_name'],
                                        'num' => $send_number[$value['rec_id']][$pg_value['g_p']]
                                    ];
                                }
                            }
                        } // 商品（虚货）
                        elseif ($value['extension_code'] == 'virtual_card' || $value['is_real'] == 0) {
                            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('virtual_card') . " WHERE goods_id = '" . $value['goods_id'] . "' AND is_saled = 0 ";
                            $num = $this->db->GetOne($sql);
                            if (($num < $goods_no_package[$value['goods_id']]) && !($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE)) {
                                /* 操作失败 */
                                $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                                return sys_msg(sprintf($GLOBALS['_LANG']['virtual_card_oos'] . '【' . $value['goods_name'] . '】'), 1, $links);
                            }

                            /* 虚拟商品列表 virtual_card*/
                            if ($value['extension_code'] == 'virtual_card') {
                                $virtual_goods[$value['extension_code']][] = ['goods_id' => $value['goods_id'], 'goods_name' => $value['goods_name'], 'num' => $send_number[$value['rec_id']]];
                            }
                        } // 商品（实货）、（货品）
                        else {
                            //如果是货品则键值为商品ID与货品ID的组合
                            $_key = empty($value['product_id']) ? $value['goods_id'] : ($value['goods_id'] . '_' . $value['product_id']);
                            $num = $value['storage']; //ecmoban模板堂 --zhuo

                            if (($num < $goods_no_package[$_key]) && $GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                                /* 操作失败 */
                                $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                                return sys_msg(sprintf(lang('seller/order.act_good_vacancy'), $value['goods_name']), 1, $links);
                            }
                        }
                    }

                    /* 生成发货单 */
                    /* 获取发货单号和流水号 */
                    $delivery['delivery_sn'] = get_delivery_sn();
                    $delivery_sn = $delivery['delivery_sn'];
                    /* 获取当前操作员 */
                    $delivery['action_user'] = session('seller_name');
                    /* 获取发货单生成时间 */
                    $delivery['update_time'] = GMTIME_UTC;
                    $delivery_time = $delivery['update_time'];
                    $sql = "select add_time from " . $this->dsc->table('order_info') . " WHERE order_sn = '" . $delivery['order_sn'] . "'";
                    $delivery['add_time'] = $this->db->GetOne($sql);
                    /* 获取发货单所属供应商 */
                    $delivery['suppliers_id'] = $suppliers_id;
                    /* 设置默认值 */
                    $delivery['status'] = DELIVERY_CREATE; // 正常
                    $delivery['order_id'] = $order_id;
                    /* 过滤字段项 */
                    $filter_fileds = [
                        'order_sn', 'add_time', 'user_id', 'how_oos', 'shipping_id', 'shipping_fee',
                        'consignee', 'address', 'country', 'province', 'city', 'district', 'sign_building',
                        'email', 'zipcode', 'tel', 'mobile', 'best_time', 'postscript', 'insure_fee',
                        'agency_id', 'delivery_sn', 'action_user', 'update_time',
                        'suppliers_id', 'status', 'order_id', 'shipping_name'
                    ];
                    $_delivery = [];
                    foreach ($filter_fileds as $value) {
                        $_delivery[$value] = $delivery[$value];
                    }

                    /* 发货单入库 */
                    $query = $this->db->autoExecute($this->dsc->table('delivery_order'), $_delivery, 'INSERT', '', 'SILENT');
                    $delivery_id = $this->db->insert_id();
                    if ($delivery_id) {
                        $delivery_goods = [];

                        //发货单商品入库
                        if (!empty($goods_list)) {
                            //分单操作
                            $split_action_note = "";

                            foreach ($goods_list as $value) {
                                // 商品（实货）（虚货）
                                if (empty($value['extension_code']) || $value['extension_code'] == 'virtual_card') {
                                    $delivery_goods = ['delivery_id' => $delivery_id,
                                        'goods_id' => $value['goods_id'],
                                        'product_id' => $value['product_id'],
                                        'product_sn' => $value['product_sn'],
                                        'goods_id' => $value['goods_id'],
                                        'goods_name' => addslashes($value['goods_name']),
                                        'brand_name' => addslashes($value['brand_name']),
                                        'goods_sn' => $value['goods_sn'],
                                        'send_number' => $send_number[$value['rec_id']],
                                        'parent_id' => 0,
                                        'is_real' => $value['is_real'],
                                        'goods_attr' => addslashes($value['goods_attr'])
                                    ];

                                    /* 如果是货品 */
                                    if (!empty($value['product_id'])) {
                                        $delivery_goods['product_id'] = $value['product_id'];
                                    }

                                    $query = $this->db->autoExecute($this->dsc->table('delivery_goods'), $delivery_goods, 'INSERT', '', 'SILENT');

                                    //分单操作
                                    $split_action_note .= sprintf($GLOBALS['_LANG']['split_action_note'], $value['goods_sn'], $send_number[$value['rec_id']]) . "<br/>";
                                } // 商品（超值礼包）
                                elseif ($value['extension_code'] == 'package_buy') {
                                    foreach ($value['package_goods_list'] as $pg_key => $pg_value) {
                                        $delivery_pg_goods = ['delivery_id' => $delivery_id,
                                            'goods_id' => $pg_value['goods_id'],
                                            'product_id' => $pg_value['product_id'],
                                            'product_sn' => $pg_value['product_sn'],
                                            'goods_name' => $pg_value['goods_name'],
                                            'brand_name' => '',
                                            'goods_sn' => $pg_value['goods_sn'],
                                            'send_number' => $send_number[$value['rec_id']][$pg_value['g_p']],
                                            'parent_id' => $value['goods_id'], // 礼包ID
                                            'extension_code' => $value['extension_code'], // 礼包
                                            'is_real' => $pg_value['is_real']
                                        ];
                                        $query = $this->db->autoExecute($this->dsc->table('delivery_goods'), $delivery_pg_goods, 'INSERT', '', 'SILENT');
                                    }

                                    //分单操作
                                    $split_action_note .= sprintf($GLOBALS['_LANG']['split_action_note'], $GLOBALS['_LANG']['14_package_list'], 1) . "<br/>";
                                }
                            }
                        }
                    } else {
                        /* 操作失败 */
                        $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                        return sys_msg(lang('seller/order.act_false'), 1, $links);
                    }

                    $_note = sprintf(lang('seller/order.order_ship_delivery'), $delivery['delivery_sn']) . "<br/>";

                    unset($filter_fileds, $delivery, $_delivery, $order_finish);

                    /* 定单信息更新处理 */
                    if (true) {
                        /* 定单信息 */
                        $_sended = &$send_number;
                        foreach ($_goods['goods_list'] as $key => $value) {
                            if ($value['extension_code'] != 'package_buy') {
                                unset($_goods['goods_list'][$key]);
                            }
                        }
                        foreach ($goods_list as $key => $value) {
                            if ($value['extension_code'] == 'package_buy') {
                                unset($goods_list[$key]);
                            }
                        }
                        $_goods['goods_list'] = $goods_list + $_goods['goods_list'];
                        unset($goods_list);

                        /* 更新订单的虚拟卡 商品（虚货） */
                        $_virtual_goods = isset($virtual_goods['virtual_card']) ? $virtual_goods['virtual_card'] : '';
                        $this->update_order_virtual_goods($order_id, $_sended, $_virtual_goods);

                        /* 更新订单的非虚拟商品信息 即：商品（实货）（货品）、商品（超值礼包）*/
                        $this->update_order_goods($order_id, $_sended, $_goods['goods_list']);

                        /* 标记订单为已确认 “发货中” */
                        /* 更新发货时间 */
                        $order_finish = $this->get_order_finish($order_id);
                        $shipping_status = SS_SHIPPED_ING;
                        if ($order['order_status'] != OS_CONFIRMED && $order['order_status'] != OS_SPLITED && $order['order_status'] != OS_SPLITING_PART) {
                            $arr['order_status'] = OS_CONFIRMED;
                            $arr['confirm_time'] = GMTIME_UTC;
                        }
                        $arr['order_status'] = $order_finish ? OS_SPLITED : OS_SPLITING_PART; // 全部分单、部分分单
                        $arr['shipping_status'] = $shipping_status;
                        update_order($order_id, $arr);
                    }

                    /* 分单操作 */
                    $action_note = $split_action_note . $action_note;

                    /* 记录log */
                    order_action($order['order_sn'], $arr['order_status'], $shipping_status, $order['pay_status'], $_note . $action_note, session('seller_name'));

                    /* 清除缓存 */
                    clear_cache_files();
                }
            } /* 设为未发货 */
            elseif ('unship' == $operation) {
                /* 检查权限 */
                admin_priv('order_ss_edit');

                /* 标记订单为“未发货”，更新发货时间, 订单状态为“确认” */
                update_order($order_id, ['shipping_status' => SS_UNSHIPPED, 'shipping_time' => 0, 'invoice_no' => '', 'order_status' => OS_CONFIRMED]);

                /* 记录log */
                order_action($order['order_sn'], $order['order_status'], SS_UNSHIPPED, $order['pay_status'], $action_note, session('seller_name'));

                /* 如果订单用户不为空，计算积分，并退回 */
                if ($order['user_id'] > 0) {

                    /* 计算并退回积分 */
                    $integral = integral_to_give($order);
                    log_account_change($order['user_id'], 0, 0, (-1) * intval($integral['rank_points']), (-1) * intval($integral['custom_points']), sprintf(lang('seller/order.return_order_gift_integral'), $order['order_sn']));

                    /* todo 计算并退回红包 */
                    return_order_bonus($order_id);
                }

                /* 如果使用库存，则增加库存 */
                if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                    change_order_goods_storage($order['order_id'], false, SDT_SHIP, 5, session('seller_id'), $store_id);
                }

                /* 删除发货单 */
                $this->del_order_delivery($order_id);

                /* 将订单的商品发货数量更新为 0 */
                $sql = "UPDATE " . $this->dsc->table('order_goods') . "
                SET send_number = 0
                WHERE order_id = '$order_id'";
                $this->db->query($sql, 'SILENT');

                /* 清除缓存 */
                clear_cache_files();
            } /* 收货确认 */
            elseif ('receive' == $operation) {
                $confirm_take_time = gmtime();

                /* 标记订单为“收货确认”，如果是货到付款，同时修改订单为已付款 */
                $arr = ['shipping_status' => SS_RECEIVED, 'confirm_take_time' => $confirm_take_time];
                $payment = payment_info($order['pay_id']);
                if ($payment['is_cod']) {
                    $arr['pay_status'] = PS_PAYED;
                    $order['pay_status'] = PS_PAYED;
                }
                update_order($order_id, $arr);

                /* 更新会员订单信息 */
                $dbRaw = [
                    'order_nogoods' => "order_nogoods - 1",
                    'order_isfinished' => "order_isfinished + 1"
                ];
                $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
                UserOrderNum::where('user_id', $order['user_id'])->update($dbRaw);

                //更改智能权重里的商品统计数量
                $sql = 'SELECT goods_id, goods_number FROM ' . $this->dsc->table('order_goods') . ' WHERE order_id =' . $order_id;
                $res = $this->db->getAll($sql);

                foreach ($res as $val) {
                    $sql = 'SELECT user_id FROM ' . $this->dsc->table('order_goods') . ' WHERE goods_id=' . $val['goods_id'] . ' GROUP BY user_id';
                    $user_number = COUNT($this->db->getAll($sql));
                    $num = ['goods_number' => $val['goods_number'], 'goods_id' => $val['goods_id'], 'user_number' => $user_number];
                    update_manual($val['goods_id'], $num);
                }

                /* 记录log */
                order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], $action_note, session('seller_name'), 0, $confirm_take_time);

                $bill = [
                    'order_id' => $order['order_id']
                ];
                $bill_order = $this->commissionService->getBillOrder($bill);

                if (!$bill_order) {
                    $seller_id = $this->db->getOne("SELECT ru_id FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '" . $order['order_id'] . "'", true);
                    $value_card = $this->db->getOne("SELECT use_val FROM " . $this->dsc->table('value_card_record') . " WHERE order_id = '" . $order['order_id'] . "'", true);

                    if (empty($order['get_seller_negative_order'])) {
                        $return_amount_info = $this->orderRefoundService->orderReturnAmount($order['order_id']);
                    } else {
                        $return_amount_info['return_amount'] = 0;
                        $return_amount_info['return_rate_fee'] = 0;
                        $return_amount_info['ret_id'] = [];
                    }

                    if ($order['order_amount'] > 0 && $order['order_amount'] > $order['rate_fee']) {
                        $order_amount = $order['order_amount'] - $order['rate_fee'];
                    } else {
                        $order_amount = $order['order_amount'];
                    }

                    $other = [
                        'user_id' => $order['user_id'],
                        'seller_id' => $seller_id,
                        'order_id' => $order['order_id'],
                        'order_sn' => $order['order_sn'],
                        'order_status' => $order['order_status'],
                        'shipping_status' => SS_RECEIVED,
                        'pay_status' => $order['pay_status'],
                        'order_amount' => $order_amount,
                        'return_amount' => $return_amount_info['return_amount'],
                        'goods_amount' => $order['goods_amount'],
                        'tax' => $order['tax'],
                        'shipping_fee' => $order['shipping_fee'],
                        'insure_fee' => $order['insure_fee'],
                        'pay_fee' => $order['pay_fee'],
                        'pack_fee' => $order['pack_fee'],
                        'card_fee' => $order['card_fee'],
                        'bonus' => $order['bonus'],
                        'integral_money' => $order['integral_money'],
                        'coupons' => $order['coupons'],
                        'discount' => $order['discount'],
                        'value_card' => $value_card,
                        'money_paid' => $order['money_paid'],
                        'surplus' => $order['surplus'],
                        'confirm_take_time' => $confirm_take_time,
                        'rate_fee' => $order['rate_fee'],
                        'return_rate_fee' => $return_amount_info['return_rate_price']
                    ];

                    if ($seller_id) {
                        $this->commissionService->getOrderBillLog($other);
                        $this->commissionService->setBillOrderReturn($return_amount_info['ret_id'], $other['order_id']);
                    }
                }
            } /*
             * 订单单品退换货同意申请
             * by　ecmoban模板堂 --zhuo
             */
            elseif ('agree_apply' == $operation) {
                $arr = ['agree_apply' => 1]; //收到用户退回商品
                $this->db->autoExecute($this->dsc->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");

                /* 记录log TODO_LOG */
                return_action($ret_id, RF_AGREE_APPLY, '', $action_note);
            } /*
             * 收到退换货商品
             * by　Leah
             */
            elseif ('receive_goods' == $operation) {
                $arr = ['return_status' => 1]; //收到用户退回商品


                $res = $this->db->autoExecute($this->dsc->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
                if ($res) {
                    //获取退换货商品ID
                    $sql = 'SELECT goods_id,return_number FROM ' . $this->dsc->table('return_goods') . ' WHERE rec_id = ' . $rec_id;
                    $res = $this->db->getRow($sql);

                    $return_num = ['return_number' => $res['return_number'], 'goods_id' => $res['goods_id']];
                    update_return_num($res['goods_id'], $return_num);
                }
                $arr['pay_status'] = PS_PAYED;
                $order['pay_status'] = PS_PAYED;

                /* 记录log TODO_LOG */
                return_action($ret_id, RF_RECEIVE, '', $action_note);
            } /**
             * 换出商品寄出 ---- 分单
             * by Leah
             */ elseif ('swapped_out_single' == $operation) {
                $arr = ['return_status' => 2]; //换出商品寄出

                $this->db->autoExecute($this->dsc->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
                return_action($ret_id, RF_SWAPPED_OUT_SINGLE, '', $action_note);
            } /**
             * 换出商品寄出
             * by leah
             */ elseif ('swapped_out' == $operation) {
                $arr = ['return_status' => 3]; //换出商品寄出

                $this->db->autoExecute($this->dsc->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
                return_action($ret_id, RF_SWAPPED_OUT, '', $action_note);
            } /**
             * 拒绝申请
             * by leah
             */ elseif ('refuse_apply' == $operation) {
                $arr = ['return_status' => 6]; //换出商品寄出

                $this->db->autoExecute($this->dsc->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
                return_action($ret_id, REFUSE_APPLY, '', $action_note);
            } /**
             * 完成退换货
             * by Leah
             */ elseif ('complete' == $operation) {
                $arr = ['return_status' => 4]; //完成退换货

                $sql = "SELECT return_type FROM " . $this->dsc->table('order_return') . " WHERE rec_id = '$rec_id'";
                $return_type = $this->db->getOne($sql);

                if ($return_type == 0) {
                    $return_note = FF_MAINTENANCE;
                } elseif ($return_type == 1) {
                    $return_note = FF_REFOUND;
                } elseif ($return_type == 2) {
                    $return_note = FF_EXCHANGE;
                }

                $this->db->autoExecute($this->dsc->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
                return_action($ret_id, RF_COMPLETE, $return_note, $action_note);
            } /* 取消 */
            elseif ('cancel' == $operation) {
                /* 标记订单为“取消”，记录取消原因 */
                $cancel_note = isset($_REQUEST['cancel_note']) ? trim($_REQUEST['cancel_note']) : '';
                $arr = [
                    'order_status' => OS_CANCELED,
                    'to_buyer' => $cancel_note,
                    'pay_status' => PS_UNPAYED,
                    'pay_time' => 0,
                    'money_paid' => 0,
                    'order_amount' => $order['money_paid']
                ];
                update_order($order_id, $arr);

                /* todo 处理退款 */
                if ($order['money_paid'] > 0) {
                    $refund_type = isset($_REQUEST['refund']) && !empty($_REQUEST['refund']) ? tirm($_REQUEST['refund']) : '';
                    $refund_note = isset($_REQUEST['refund']) && !empty($_REQUEST['refund_note']) ? tirm($_REQUEST['refund_note']) : '';

                    if ($refund_note) {
                        $refund_note = "【" . $GLOBALS['_LANG']['setorder_cancel'] . "】【" . $order['order_sn'] . "】" . $refund_note;
                    }

                    order_refund($order, $refund_type, $refund_note);
                }

                /* 记录log */
                order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, $action_note, session('seller_name'));

                /* 如果使用库存，且下订单时减库存，则增加库存 */
                if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                    change_order_goods_storage($order_id, false, SDT_PLACE, 3, session('seller_id'), $store_id);
                }

                /* 退还用户余额、积分、红包 */
                return_user_surplus_integral_bonus($order);

                /* 发送邮件 */
                $cfg = $GLOBALS['_CFG']['send_cancel_email'];
                if ($cfg == '1') {
                    $tpl = get_mail_template('order_cancel');
                    $this->smarty->assign('order', $order);
                    $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
                    $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                    if (!$this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                        $msg = lang('seller/order.send_mail_fail');
                    }
                }

                $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);
            } /* 设为无效 */
            elseif ('invalid' == $operation) {
                /* 标记订单为“无效”、“未付款” */
                update_order($order_id, ['order_status' => OS_INVALID]);

                /* 记录log */
                order_action($order['order_sn'], OS_INVALID, $order['shipping_status'], PS_UNPAYED, $action_note, session('seller_name'));

                /* 如果使用库存，且下订单时减库存，则增加库存 */
                if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                    change_order_goods_storage($order_id, false, SDT_PLACE, 2, session('seller_id'), $store_id);
                }

                /* 发送邮件 */
                $cfg = $GLOBALS['_CFG']['send_invalid_email'];
                if ($cfg == '1') {
                    $tpl = get_mail_template('order_invalid');
                    $this->smarty->assign('order', $order);
                    $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
                    $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                    if (!$this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                        $msg = lang('seller/order.send_mail_fail');
                    }
                }

                /* 退货用户余额、积分、红包 */
                return_user_surplus_integral_bonus($order);

                $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);
            } /**
             * 退款
             * by  Leah
             */
            elseif ('refound' == $operation) {

                load_helper('transaction');
                //TODO
                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

                $is_whole = 0;
                $is_diff = get_order_return_rec($order_id);
                if ($is_diff) {
                    //整单退换货
                    $return_count = return_order_info_byId($order_id, false);
                    if ($return_count == 1) {

                        //退还红包
                        $bonus = $order['bonus'];
                        $sql = "UPDATE " . $this->dsc->table('user_bonus') . " SET used_time = '' , order_id = '' WHERE order_id = " . $order_id;
                        $this->db->query($sql);

                        /*  @author-bylu 退还优惠券 start */
                        unuse_coupons($order_id);

                        $is_whole = 1;
                    }
                }

                /* 过滤数据 */
                $_REQUEST['refund'] = isset($_REQUEST['refund']) ? $_REQUEST['refund'] : ''; // 退款类型
                $_REQUEST['refund_amount'] = isset($_REQUEST['refund_amount']) ? $_REQUEST['refund_amount'] :
                    $_REQUEST['action_note'] = isset($_REQUEST['action_note']) ? $_REQUEST['action_note'] : ''; //退款说明
                $refound_pay_points = isset($_REQUEST['refound_pay_points']) ? $_REQUEST['refound_pay_points'] : 0;//退回积分  by kong

                $return_amount = isset($_REQUEST['refound_amount']) && !empty($_REQUEST['refound_amount']) ? floatval($_REQUEST['refound_amount']) : 0; //退款金额
                $is_shipping = isset($_REQUEST['is_shipping']) && !empty($_REQUEST['is_shipping']) ? intval($_REQUEST['is_shipping']) : 0; //是否退运费
                $shippingFee = !empty($is_shipping) ? floatval($_REQUEST['shipping']) : 0; //退款运费金额

                $refound_vcard = isset($_REQUEST['refound_vcard']) && !empty($_REQUEST['refound_vcard']) ? floatval($_REQUEST['refound_vcard']) : 0; //储值卡金额
                $vc_id = isset($_REQUEST['vc_id']) && !empty($_REQUEST['vc_id']) ? intval($_REQUEST['vc_id']) : 0; //储值卡金额

                $return_goods = get_return_order_goods1($rec_id); //退换货商品
                $return_info = return_order_info($ret_id);        //退换货订单

                $rate_price = 0;
                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $rate_price = request()->input('rate_price', '');
                    $rate_price = !empty($rate_price) ? floatval($rate_price) : 0; //税费
                    if ($rate_price > $order['rate_fee']) {
                        $rate_price = $order['rate_fee'];
                    }
                    $return_amount = $rate_price > 0 ? $return_amount + $rate_price : $return_amount;
                }

                /* todo 处理退款 */
                if ($order['pay_status'] != PS_UNPAYED) {
                    $order_goods = $this->get_order_goods($order);             //订单商品
                    $refund_type = intval($_REQUEST['refund']);

                    //判断商品退款是否大于实际商品退款金额
                    $refound_fee = order_refound_fee($order_id, $ret_id); //已退金额
                    $paid_amount = $order['money_paid'] + $order['surplus'] - $refound_fee;
                    if ($return_amount > $paid_amount) {
                        $return_amount = $paid_amount - $order['shipping_fee'];
                    }

                    //判断运费退款是否大于实际运费退款金额
                    $is_refound_shippfee = order_refound_shipping_fee($order_id, $ret_id);
                    $is_refound_shippfee_amount = $is_refound_shippfee + $shippingFee;

                    if ($is_refound_shippfee_amount > $order['shipping_fee']) {
                        $shippingFee = $order['shipping_fee'] - $is_refound_shippfee;
                    }

                    $refund_amount = $return_amount + $shippingFee;
                    $get_order_arr = get_order_arr($return_info['return_number'], $return_info['rec_id'], $order_goods['goods_list'], $order);
                    $refund_note = addslashes(trim($_REQUEST['refund_note']));

                    //退款
                    if (!empty($_REQUEST['action_note'])) {
                        $order['should_return'] = $return_info['should_return'];
                        $is_ok = order_refound($order, $refund_type, $refund_note, $refund_amount, $operation);

                        if ($is_ok == 2 && $refund_type == 1) {
                            /* 提示信息 */
                            $links[] = ['href' => 'order.php?act=return_info&ret_id=' . $ret_id . '&rec_id=' . $return_info['rec_id'], 'text' => $GLOBALS['_LANG']['back_return_delivery_handle']];
                            return sys_msg($GLOBALS['_LANG']['return_money_fail_account_fu'], 1, $links);
                        }

                        //标记order_return 表
                        $return_status = [
                            'refound_status' => 1,
                            'agree_apply' => 1,
                            'actual_return' => $refund_amount,
                            'return_shipping_fee' => $shippingFee,
                            'refund_type' => $refund_type,
                            'return_time' => gmtime()
                        ];
                        $this->db->autoExecute($this->dsc->table('order_return'), $return_status, 'UPDATE', "ret_id = '$ret_id'");

                        /* 负账单订单 start */
                        if ($order['ru_id'] > 0) {

                            $negative_time = gmtime();

                            $refund_amount = $refund_amount - $shippingFee - $rate_price;

                            $other = [
                                'order_id' => $order['order_id'],
                                'order_sn' => $order['order_sn'],
                                'ret_id' => $ret_id,
                                'return_sn' => $return_info['return_sn'],
                                'seller_id' => $order['ru_id'],
                                'return_amount' => $refund_amount,
                                'return_shippingfee' => $shippingFee,
                                'return_rate_price' => $rate_price,
                                'add_time' => $negative_time
                            ];

                            SellerNegativeOrder::insert($other);
                        }
                        /* 负账单订单 end */

                        update_order($order_id, $get_order_arr);

                        $sales_volume = Goods::where('goods_id', $return_goods['goods_id'])
                            ->value('sales_volume');
                        $sales_volume = !empty($sales_volume) ? $sales_volume : 0;

                        if ($sales_volume >= $return_info['return_number']) {
                            Goods::where('goods_id', $return_goods['goods_id'])->decrement('sales_volume', $return_info['return_number']);
                        }
                    }
                }

                /* 退回订单赠送的积分 */
                return_integral_rank($ret_id, $order['user_id'], $order['order_sn'], $rec_id, $refound_pay_points);

                if ($is_whole == 1) {
                    return_card_money($order_id, $ret_id, $return_info['return_sn']);
                } else {
                    /* 退回订单消费储值卡金额 */
                    get_return_vcard($order_id, $vc_id, $refound_vcard, $return_info['return_sn'], $ret_id);
                }

                /* 如果使用库存，则增加库存（不论何时减库存都需要） */
                if ($GLOBALS['_CFG']['use_storage'] == '1') {
                    if ($GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                        change_order_goods_storage($order_id, false, SDT_SHIP, 6, session('seller_id'), $store_id);
                    } elseif ($GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                        change_order_goods_storage($order_id, false, SDT_PLACE, 6, session('seller_id'), $store_id);
                    } elseif ($GLOBALS['_CFG']['stock_dec_time'] == SDT_PAID) {
                        change_order_goods_storage($order_id, false, SDT_PAID, 6, session('seller_id'), $store_id);
                    }
                }

                /* 记录log */
                return_action($ret_id, '', FF_REFOUND, $action_note);

                $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);
            } /* 退货 by Leah */
            elseif ('return' == $operation) {
                //TODO
                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

                /* 过滤数据 */
                $_REQUEST['refund'] = isset($_REQUEST['refund']) ? $_REQUEST['refund'] : '';
                $_REQUEST['refund_note'] = isset($_REQUEST['refund_note']) ? $_REQUEST['refund'] : '';

                /* 手动修改退款金额 start */
                $return_amount = isset($_REQUEST['refound_amount']) && !empty($_REQUEST['refound_amount']) ? floatval($_REQUEST['refound_amount']) : 0; //退款金额
                $is_shipping = isset($_REQUEST['is_shipping']) && !empty($_REQUEST['is_shipping']) ? intval($_REQUEST['is_shipping']) : 0; //是否退运费
                $shipping_fee = !empty($is_shipping) ? floatval($_REQUEST['shipping']) : 0; //退款运费金额
                /* 手动修改退款金额 end */

                $refound_vcard = isset($_REQUEST['refound_vcard']) && !empty($_REQUEST['refound_vcard']) ? floatval($_REQUEST['refound_vcard']) : 0; //储值卡金额
                $vc_id = isset($_REQUEST['vc_id']) && !empty($_REQUEST['vc_id']) ? intval($_REQUEST['vc_id']) : 0; //储值卡金额

                $order_return_amount = $return_amount + $shipping_fee;

                /* todo 处理退款 */
                if ($order['pay_status'] != PS_UNPAYED) {
                    $order['order_status'] = OS_RETURNED;
                    $order['pay_status'] = PS_UNPAYED;
                    $order['shipping_status'] = SS_UNSHIPPED;

                    $refund_type = intval($_REQUEST['refund']);
                    $refund_note = isset($_REQUEST['action_note']) ? addslashes(trim($_REQUEST['action_note'])) : '';
                    $refund_note = "【" . lang('seller/order.refund') . "】" . "【" . $order['order_sn'] . "】" . $refund_note;
                    $is_ok = order_refund($order, $refund_type, $refund_note, $return_amount, $shipping_fee);

                    if ($is_ok == 2 && $refund_type == 1) {
                        /* 提示信息 */
                        $links[] = ['href' => 'order.php?act=info&order_id=' . $order_id, 'text' => $GLOBALS['_LANG']['return_order_info']];
                        return sys_msg($GLOBALS['_LANG']['return_money_fail_account_fu'], 1, $links);
                    }

                    /* 余额已放入冻结资金 */
                    $order['surplus'] = 0;
                }

                /* 标记订单为“退货”、“未付款”、“未发货” */
                $arr = ['order_status' => OS_RETURNED,
                    'pay_status' => PS_UNPAYED,
                    'shipping_status' => SS_UNSHIPPED,
                    'money_paid' => 0,
                    'invoice_no' => '',
                    'return_amount' => $order_return_amount,
                    'order_amount' => $order_return_amount
                ];
                update_order($order_id, $arr);

                /* 记录log */
                order_action($order['order_sn'], OS_RETURNED, SS_UNSHIPPED, PS_UNPAYED, $action_note, session('seller_name'));

                /* 如果订单用户不为空，计算积分，并退回 */
                if ($order['user_id'] > 0) {
                    /* 取得用户信息 */
                    $where = [
                        'user_id' => $order['user_id']
                    ];
                    $user_info = $this->userService->userInfo($where);

                    $sql = "SELECT  goods_number, send_number FROM" . $this->dsc->table('order_goods') . "
                WHERE order_id = '" . $order['order_id'] . "'";

                    $goods_num = $this->db->getRow($sql);

                    if ($goods_num['goods_number'] == $goods_num['send_number']) {
                        /* 计算并退回积分 */
                        $integral = integral_to_give($order);
                        log_account_change($order['user_id'], 0, 0, (-1) * intval($integral['rank_points']), (-1) * intval($integral['custom_points']), sprintf(lang('seller/order.return_order_gift_integral'), $order['order_sn']));
                    }
                    /* todo 计算并退回红包 */
                    return_order_bonus($order_id);
                }

                /* 如果使用库存，则增加库存（不论何时减库存都需要） */
                if ($GLOBALS['_CFG']['use_storage'] == '1') {
                    if ($GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                        change_order_goods_storage($order['order_id'], false, SDT_SHIP, 6, session('seller_id'), $store_id);
                    } elseif ($GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                        change_order_goods_storage($order['order_id'], false, SDT_PLACE, 6, session('seller_id'), $store_id);
                    } elseif ($GLOBALS['_CFG']['stock_dec_time'] == SDT_PAID) {
                        change_order_goods_storage($order_id, false, SDT_PAID, 6, session('seller_id'), $store_id);
                    }
                }

                /* 退回订单消费储值卡金额 */
                return_card_money($order_id);

                /* 退货用户余额、积分、红包 */
                return_user_surplus_integral_bonus($order);

                /* 获取当前操作员 */
                $delivery['action_user'] = session('seller_name');
                /* 添加退货记录 */
                $delivery_list = [];
                $sql_delivery = "SELECT *
                         FROM " . $this->dsc->table('delivery_order') . "
                         WHERE status IN (0, 2)
                         AND order_id = " . $order['order_id'];
                $delivery_list = $this->db->getAll($sql_delivery);
                if ($delivery_list) {
                    foreach ($delivery_list as $list) {
                        $sql_back = "INSERT INTO " . $this->dsc->table('back_order') . " (delivery_sn, order_sn, order_id, add_time, shipping_id, user_id, action_user, consignee, address, Country, province, City, district, sign_building, Email,Zipcode, Tel, Mobile, best_time, postscript, how_oos, insure_fee, shipping_fee, update_time, suppliers_id, return_time, agency_id, invoice_no) VALUES ";

                        $sql_back .= " ( '" . $list['delivery_sn'] . "', '" . $list['order_sn'] . "',
                              '" . $list['order_id'] . "', '" . $list['add_time'] . "',
                              '" . $list['shipping_id'] . "', '" . $list['user_id'] . "',
                              '" . $delivery['action_user'] . "', '" . $list['consignee'] . "',
                              '" . $list['address'] . "', '" . $list['country'] . "', '" . $list['province'] . "',
                              '" . $list['city'] . "', '" . $list['district'] . "', '" . $list['sign_building'] . "',
                              '" . $list['email'] . "', '" . $list['zipcode'] . "', '" . $list['tel'] . "',
                              '" . $list['mobile'] . "', '" . $list['best_time'] . "', '" . $list['postscript'] . "',
                              '" . $list['how_oos'] . "', '" . $list['insure_fee'] . "',
                              '" . $list['shipping_fee'] . "', '" . $list['update_time'] . "',
                              '" . $list['suppliers_id'] . "', '" . GMTIME_UTC . "',
                              '" . $list['agency_id'] . "', '" . $list['invoice_no'] . "'
                              )";
                        $this->db->query($sql_back, 'SILENT');
                        $back_id = $this->db->insert_id();

                        $sql_back_goods = "INSERT INTO " . $this->dsc->table('back_goods') . " (back_id, goods_id, product_id, product_sn, goods_name,goods_sn, is_real, send_number, goods_attr)
                                   SELECT '$back_id', goods_id, product_id, product_sn, goods_name, goods_sn, is_real, send_number, goods_attr
                                   FROM " . $this->dsc->table('delivery_goods') . "
                                   WHERE delivery_id = " . $list['delivery_id'];
                        $res = $this->db->query($sql_back_goods, 'SILENT');
                        if ($res) {
                            //获取退换货商品ID
                            $sql = 'SELECT goods_id,send_number FROM ' . $this->dsc->table('back_goods') . ' WHERE back_id = ' . $back_id;
                            $res = $this->db->getRow($sql);

                            $return_num = ['return_number' => $res['send_number'], 'goods_id' => $res['goods_id']];
                            update_return_num($res['goods_id'], $return_num);
                        }
                    }
                }

                /* 修改订单的发货单状态为退货 */
                $sql_delivery = "UPDATE " . $this->dsc->table('delivery_order') . "
                         SET status = 1
                         WHERE status IN (0, 2)
                         AND order_id = '" . $order['order_id'] . "'";
                $this->db->query($sql_delivery, 'SILENT');

                $sql = "SELECT goods_id, send_number FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '" . $order['order_id'] . "' AND extension_code <> 'virtual_card' AND send_number >= 1";
                $goods_list = $this->db->getAll($sql);

                if ($goods_list) {
                    foreach ($goods_list as $key => $row) {
                        $sql = "UPDATE " . $this->dsc->table('goods') . " SET sales_volume = sales_volume - '" . $row['send_number'] . "' WHERE goods_id = '" . $row['goods_id'] . "'";
                        $this->db->query($sql);
                    }
                }

                /* 将订单的商品发货数量更新为 0 */
                $sql = "UPDATE " . $this->dsc->table('order_goods') . "
                SET send_number = 0
                WHERE order_id = '$order_id'";
                $this->db->query($sql, 'SILENT');

                $this->orderCommonService->getUserOrderNumServer($order['user_id'] ?? 0);

                /* 清除缓存 */
                clear_cache_files();
            } elseif ('after_service' == $operation) {
                /* 记录log */
                order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], '[' . $GLOBALS['_LANG']['op_after_service'] . '] ' . $action_note, session('seller_name'));
            } else {
                return 'invalid params';
            }

            /**
             * by Leah s
             */
            if ($return) {
                $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=return_info&ret_id=' . $ret_id . '&rec_id=' . $rec_id]; //by Leah

                return sys_msg(lang('seller/order.act_ok') . $msg, 0, $links);
            } else {
                /* 操作成功 */
                $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
                return sys_msg(lang('seller/order.act_ok') . $msg, 0, $links);
            }
            /**
             * by Leah e
             */
        } //ecmoban模板堂 --zhuo start
        elseif ($_REQUEST['act'] == 'json') {

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            //ecmoban模板堂 --zhuo start
            $warehouse_id = isset($_REQUEST['warehouse_id']) ? intval($_REQUEST['warehouse_id']) : 0;
            $area_id = isset($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : 0;
            $area_city = isset($_REQUEST['area_city']) ? intval($_REQUEST['area_city']) : 0;
            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $model_attr = isset($_REQUEST['model_attr']) ? intval($_REQUEST['model_attr']) : 0;
            $goods_number = isset($_REQUEST['goods_number']) ? intval($_REQUEST['goods_number']) : 0;
            //ecmoban模板堂 --zhuo end

            $func = $_REQUEST['func'];
            if ($func == 'get_goods_info') {
                /* 取得商品信息 */

                $leftJoin = " left join " . $this->dsc->table('warehouse_goods') . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
                $leftJoin .= " left join " . $this->dsc->table('warehouse_area_goods') . " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";

                $sql = "SELECT g.goods_id, c.cat_name, g.goods_sn, g.goods_name, b.brand_name, g.market_price, g.model_attr, g.user_id, " .
                    'IF(g.model_price < 1, g.goods_number, IF(g.model_price < 2, wg.region_number, wag.region_number)) AS goods_number, ' .
                    'IFNULL(IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)), g.shop_price) AS shop_price, ' .
                    "IFNULL(IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)), g.promote_price) AS promote_price, " .
                    "g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_type, g.is_promote " .
                    "FROM " . $this->dsc->table('goods') . " AS g " .
                    $leftJoin .
                    "LEFT JOIN " . $this->dsc->table('brand') . " AS b ON g.brand_id = b.brand_id " .
                    "LEFT JOIN " . $this->dsc->table('category') . " AS c ON g.cat_id = c.cat_id " .
                    " WHERE g.goods_id = '$goods_id'";
                $goods = $this->db->getRow($sql);
                $today = gmtime();
                $goods['goods_price'] = ($goods['is_promote'] == 1 &&
                    $goods['promote_start_date'] <= $today && $goods['promote_end_date'] >= $today) ?
                    $goods['promote_price'] : $goods['shop_price'];

                $goods['warehouse_id'] = $warehouse_id;
                $goods['area_id'] = $area_id;

                /* 取得会员价格 */
                $sql = "SELECT p.user_price, r.rank_name " .
                    "FROM " . $this->dsc->table('member_price') . " AS p, " .
                    $this->dsc->table('user_rank') . " AS r " .
                    "WHERE p.user_rank = r.rank_id " .
                    "AND p.goods_id = '$goods_id' ";
                $goods['user_price'] = $this->db->getAll($sql);

                //ecmoban模板堂 --zhuo satrt
                $attr_leftJoin = '';
                $select = '';
                if ($goods['model_attr'] == 1) {
                    $select = " wap.attr_price as warehouse_attr_price, ";
                    $attr_leftJoin = 'LEFT JOIN ' . $this->dsc->table('warehouse_attr') . " AS wap ON g.goods_attr_id = wap.goods_attr_id AND wap.warehouse_id = '$warehouse_id' ";
                } elseif ($goods['model_attr'] == 2) {
                    $select = " waa.attr_price as area_attr_price, ";
                    $attr_leftJoin = 'LEFT JOIN ' . $this->dsc->table('warehouse_area_attr') . " AS waa ON g.goods_attr_id = waa.goods_attr_id AND area_id = '$area_id' ";
                }
                //ecmoban模板堂 --zhuo end

                /* 取得商品属性 */
                $sql = "SELECT a.attr_id, a.attr_name, g.goods_attr_id, g.attr_value, " .
                    $select .
                    " g.attr_price, a.attr_input_type, a.attr_type " .
                    "FROM " . $this->dsc->table('goods_attr') . " AS g " .
                    "LEFT JOIN" . $this->dsc->table('attribute') . " AS a ON g.attr_id = a.attr_id " .
                    $attr_leftJoin .
                    "WHERE g.goods_id = '$goods_id' ORDER BY a.sort_order, a.attr_id, g.goods_attr_id";
                $goods['attr_list'] = [];

                $res = $this->db->query($sql);
                foreach ($res as $row) {
                    //ecmoban模板堂 --zhuo satrt
                    if ($goods['model_attr'] == 1) {
                        $row['attr_price'] = $row['warehouse_attr_price'];
                    } elseif ($goods['model_attr'] == 2) {
                        $row['attr_price'] = $row['area_attr_price'];
                    } else {
                        $row['attr_price'] = $row['attr_price'];
                    }
                    //ecmoban模板堂 --zhuo end

                    $goods['attr_list'][$row['attr_id']][] = $row;
                }
                $goods['attr_list'] = array_values($goods['attr_list']);

                $goods_attr_id = '';
                $attr_price = 0;
                //ecmoban模板堂 --zhuo start
                if ($goods['attr_list']) {
                    foreach ($goods['attr_list'] as $attr_key => $attr_row) {
                        $goods_attr_id .= $attr_row[0]['goods_attr_id'] . ",";
                        $attr_price += floatval($attr_row[0]['attr_price']);
                    }

                    $goods_attr_id = substr($goods_attr_id, 0, -1);
                    $goods['attr_price'] = $attr_price;
                }

                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $goods_attr_id, $warehouse_id, $area_id, $area_city);
                $attr_number = $products ? $products['product_number'] : 0;

                if ($goods['model_attr'] == 1) {
                    $table_products = "products_warehouse";
                    $type_files = " and warehouse_id = '$warehouse_id'";
                } elseif ($goods['model_attr'] == 2) {
                    $table_products = "products_area";
                    $type_files = " and area_id = '$area_id'";
                } else {
                    $table_products = "products";
                    $type_files = "";
                }

                $sql = "SELECT * FROM " . $this->dsc->table($table_products) . " WHERE goods_id = '$goods_id'" . $type_files . " LIMIT 0, 1";
                $prod = $this->db->getRow($sql);

                if (empty($prod)) { //当商品没有属性库存时
                    $attr_number = $goods['goods_number'];
                }

                $attr_number = !empty($attr_number) ? $attr_number : 0;

                $goods['goods_storage'] = $attr_number;
                //ecmoban模板堂 --zhuo end

                return response()->json($goods);
            } elseif ($func == 'get_goods_attr_number') {
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $_REQUEST['attr'], $warehouse_id, $area_id, $area_city);
                $attr_number = $products ? $products['product_number'] : 0;

                if ($model_attr == 1) {
                    $table_products = "products_warehouse";
                    $type_files = " and warehouse_id = '$warehouse_id'";
                } elseif ($model_attr == 2) {
                    $table_products = "products_area";
                    $type_files = " and area_id = '$area_id'";
                } else {
                    $table_products = "products";
                    $type_files = "";
                }

                $sql = "SELECT * FROM " . $this->dsc->table($table_products) . " WHERE goods_id = '$goods_id'" . $type_files . " LIMIT 1";
                $prod = $this->db->getRow($sql);

                if (empty($prod)) { //当商品没有属性库存时
                    $attr_number = $goods_number;
                }

                $attr_number = !empty($attr_number) ? $attr_number : 0;

                $attr_leftJoin = '';
                $select = '';
                if ($model_attr == 1) {
                    $select = " wap.attr_price as warehouse_attr_price, ";
                    $attr_leftJoin = 'LEFT JOIN ' . $this->dsc->table('warehouse_attr') . " AS wap ON g.goods_attr_id = wap.goods_attr_id AND wap.warehouse_id = '$warehouse_id' ";
                } elseif ($model_attr == 2) {
                    $select = " waa.attr_price as area_attr_price, ";
                    $attr_leftJoin = 'LEFT JOIN ' . $this->dsc->table('warehouse_area_attr') . " AS waa ON g.goods_attr_id = waa.goods_attr_id AND area_id = '$area_id' ";
                }

                $goodsAttr = '';
                if (isset($_REQUEST['attr']) && !empty($_REQUEST['attr'])) {
                    $goodsAttr = " and g.goods_attr_id in(" . $_REQUEST['attr'] . ") ";
                }

                /* 取得商品属性 */
                $sql = "SELECT a.attr_id, a.attr_name, g.goods_attr_id, g.attr_value, " .
                    $select .
                    " g.attr_price, a.attr_input_type, a.attr_type " .
                    "FROM " . $this->dsc->table('goods_attr') . " AS g " .
                    "LEFT JOIN" . $this->dsc->table('attribute') . " AS a ON g.attr_id = a.attr_id " .
                    $attr_leftJoin .
                    "WHERE g.goods_id = '$goods_id' " . $goodsAttr . " ORDER BY a.sort_order, a.attr_id, g.goods_attr_id";
                $goods['attr_list'] = [];

                $res = $this->db->query($sql);
                foreach ($res as $row) {
                    if ($model_attr == 1) {
                        $row['attr_price'] = $row['warehouse_attr_price'];
                    } elseif ($model_attr == 2) {
                        $row['attr_price'] = $row['area_attr_price'];
                    } else {
                        $row['attr_price'] = $row['attr_price'];
                    }

                    $goods['attr_list'][$row['attr_id']][] = $row;
                }
                $goods['attr_list'] = array_values($goods['attr_list']);

                $goods['attr_price'] = 0;
                if ($goods['attr_list']) {
                    foreach ($goods['attr_list'] as $attr_key => $attr_row) {
                        $attr_price += $attr_row[0]['attr_price'];
                    }

                    $goods['attr_price'] = $attr_price;
                }

                $goods['goods_id'] = $goods_id;
                $goods['warehouse_id'] = $warehouse_id;
                $goods['area_id'] = $area_id;
                $goods['user_id'] = $user_id;
                $goods['attr'] = $_REQUEST['attr'];
                $goods['model_attr'] = $model_attr;
                $goods['goods_number'] = $goods_number;
                $goods['goods_storage'] = $attr_number;

                return response()->json($goods);
            }
        }

        /*------------------------------------------------------ */
        //-- 合并订单查询现有订单列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_merge_order_list') {
            /* 检查权限 */
            admin_priv('order_os_edit');


            $merchant_id = empty($_POST['merchant_id']) ? '' : intval($_POST['merchant_id']);
            $store_search = empty($_POST['store_search']) ? -1 : intval($_POST['store_search']);

            if ($store_search != 1) {
                $merchant_id = 0;
            }

            $where = " AND o.ru_id = '$merchant_id' AND o.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

            /* 取得满足条件的订单 */
            $sql = "SELECT o.order_sn, u.user_name " .
                "FROM " . $this->dsc->table('order_info') . " AS o " .
                "LEFT JOIN " . $this->dsc->table('users') . " AS u ON o.user_id = u.user_id " .
                " LEFT JOIN " . $this->dsc->table('order_goods') . " AS og ON o.order_id=og.order_id " .
                " LEFT JOIN " . $this->dsc->table('goods') . " AS g ON og.goods_id=g.goods_id " .
                "WHERE o.user_id > 0 " . $where .
                "AND o.extension_code = '' " . $this->orderService->orderQuerySql('unprocessed') . " GROUP BY o.order_id";
            $order_list = $this->db->getAll($sql);

            $this->smarty->assign('order_list', $order_list);

            return make_json_result($this->smarty->fetch('merge_order_list.htm'));
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 合并订单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_merge_order') {
            /* 检查权限 */
            admin_priv('order_os_edit');


            $from_order_sn = empty($_POST['from_order_sn']) ? '' : json_str_iconv(substr($_POST['from_order_sn'], 1));
            $to_order_sn = empty($_POST['to_order_sn']) ? '' : json_str_iconv(substr($_POST['to_order_sn'], 1));

            $m_result = merge_order($from_order_sn, $to_order_sn);
            $result = ['error' => 0, 'content' => ''];
            if ($m_result === true) {
                $result['message'] = lang('seller/order.act_ok');
            } else {
                $result['error'] = 1;
                $result['message'] = $m_result;
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除订单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_order') {
            /* 检查权限 */
            admin_priv('order_edit');

            $order_id = intval($_REQUEST['id']);

            /* 检查权限 */
            $check_auth = check_authz_json('order_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 检查订单是否允许删除操作 */
            $order = order_info($order_id);
            $operable_list = $this->operable_list($order);
            if (!isset($operable_list['remove'])) {
                return make_json_error('Hacking attempt');
            }

            $return_order = return_order_info(0, '', $order['order_id']);
            if ($return_order) {
                return make_json_error(sprintf(lang('seller/order.order_remove_failure'), $order['order_sn']));
            }

            $this->db->query("DELETE FROM " . $this->dsc->table('order_info') . " WHERE order_id = '$order_id'");
            $this->db->query("DELETE FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '$order_id'");
            $this->db->query("DELETE FROM " . $this->dsc->table('order_action') . " WHERE order_id = '$order_id'");
            $action_array = ['delivery', 'back'];
            $this->del_delivery($order_id, $action_array);

            if ($this->db->errno() == 0) {
                $url = 'order.php?act=query&' . str_replace('act=remove_order', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            } else {
                return make_json_error($this->db->errorMsg());
            }
        }

        /*------------------------------------------------------ */
        //-- 根据�        �键字和id搜索用户
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_users') {

            $id_name = empty($_GET['id_name']) ? '' : json_str_iconv(trim($_GET['id_name']));

            $result = ['error' => 0, 'message' => '', 'content' => ''];
            if ($id_name != '') {
                $sql = "SELECT user_id, user_name FROM " . $this->dsc->table('users') .
                    " WHERE user_name LIKE '%" . mysql_like_quote($id_name) . "%'" .
                    " LIMIT 20";
                $res = $this->db->query($sql);

                $result['userlist'] = [];
                foreach ($res as $row) {

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $row['user_name'] = $this->dscRepository->stringToStar($row['user_name']);
                    }

                    $result['userlist'][] = ['user_id' => $row['user_id'], 'user_name' => $row['user_name']];
                }
            } else {
                $result['error'] = 1;
                $result['message'] = 'NO KEYWORDS!';
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 根据�        �键字搜索商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_goods') {

            $keyword = empty($_GET['keyword']) ? '' : json_str_iconv(trim($_GET['keyword']));
            $order_id = empty($_GET['order_id']) ? '' : intval($_GET['order_id']);
            $warehouse_id = empty($_GET['warehouse_id']) ? '' : intval($_GET['warehouse_id']);
            $area_id = empty($_GET['area_id']) ? '' : intval($_GET['area_id']);

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '$order_id'";
            $goods_count = $this->db->getOne($sql);

            if ($goods_count) {
                $sql = "SELECT ru_id FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '$order_id'";
                $ru_id = $this->db->getAll($sql);
            } else {
                $ru_id = [
                    '0' => ['ru_id' => $adminru['ru_id']]
                ];
            }


            $where = '';
            if ($ru_id) {
                foreach ($ru_id as $key => $row) {
                    $ru_str .= $row['ru_id'] . ",";
                }

                $ru_str = substr($ru_str, 0, -1);
                $ru_str = explode(',', $ru_str);
                $ru_str = array_unique($ru_str);
                $ru_str = implode(',', $ru_str);

                $where = " AND user_id IN($ru_str)";
            }

            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if ($keyword != '') {
                $sql = "SELECT goods_id, goods_name, goods_sn, user_id FROM " . $this->dsc->table('goods') .
                    " WHERE is_delete = 0" .
                    " AND is_on_sale = 1" .
                    $where .
                    " AND is_alone_sale = 1" .
                    " AND (goods_id LIKE '%" . mysql_like_quote($keyword) . "%'" .
                    " OR goods_name LIKE '%" . mysql_like_quote($keyword) . "%'" .
                    " OR goods_sn LIKE '%" . mysql_like_quote($keyword) . "%')" .
                    " LIMIT 20";
                $res = $this->db->query($sql);

                $result['goodslist'] = [];
                foreach ($res as $row) {
                    $result['warehouse_id'] = $warehouse_id;
                    $result['area_id'] = $area_id;
                    $result['goodslist'][] = ['goods_id' => $row['goods_id'], 'name' => $row['goods_id'] . '  ' . $row['goods_name'] . '  ' . $row['goods_sn'], 'user_id' => $row['user_id']];
                }
            } else {
                $result['error'] = 1;
                $result['message'] = 'NO KEYWORDS';
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 编辑收货单号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_invoice_no') {
            /* 检查权限 */
            $check_auth = check_authz_json('order_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $no = empty($_POST['val']) ? 'N/A' : json_str_iconv(trim($_POST['val']));
            $no = $no == 'N/A' ? '' : $no;
            $order_id = empty($_POST['id']) ? 0 : intval($_POST['id']);

            if ($order_id == 0) {
                return make_json_error('NO ORDER ID');
            }

            $sql = 'SELECT order_sn, order_status, shipping_status, pay_status, invoice_no FROM ' . $this->dsc->table('order_info') . " WHERE order_id = '$order_id' LIMIT 1";
            $order = $this->db->getRow($sql);

            $sql = 'UPDATE ' . $this->dsc->table('order_info') . " SET invoice_no='$no' WHERE order_id = '$order_id'";
            if ($this->db->query($sql)) {
                if (!empty($no) && $no != $order['invoice_no']) {
                    $sql = 'UPDATE ' . $this->dsc->table('delivery_order') . " SET invoice_no = '$no' WHERE order_id = '$order_id'";
                    $this->db->query($sql);

                    if (empty($order['invoice_no'])) {
                        $order['invoice_no'] = 'N/A';
                    }

                    $note = sprintf(lang('seller/order.edit_order_invoice'), $order['invoice_no'], $no);
                    order_action($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], $note, session('seller_name'));
                }

                if (empty($no)) {
                    return make_json_result('N/A');
                } else {
                    return make_json_result(stripcslashes($no));
                }
            } else {
                return make_json_error($this->db->errorMsg());
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑付款备注
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_pay_note') {
            /* 检查权限 */
            $check_auth = check_authz_json('order_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $no = empty($_POST['val']) ? 'N/A' : json_str_iconv(trim($_POST['val']));
            $no = $no == 'N/A' ? '' : $no;
            $order_id = empty($_POST['id']) ? 0 : intval($_POST['id']);

            if ($order_id == 0) {
                return make_json_error('NO ORDER ID');
            }

            $sql = 'UPDATE ' . $this->dsc->table('order_info') . " SET pay_note='$no' WHERE order_id = '$order_id'";
            if ($this->db->query($sql)) {
                if (empty($no)) {
                    return make_json_result('N/A');
                } else {
                    return make_json_result(stripcslashes($no));
                }
            } else {
                return make_json_error($this->db->errorMsg());
            }
        }

        /*------------------------------------------------------ */
        //-- 获取订单商品信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_goods_info') {
            /* 取得订单商品 */
            $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
            if (empty($order_id)) {
                return make_json_response('', 1, $GLOBALS['_LANG']['error_get_goods_info']);
            }
            $goods_list = [];
            $goods_attr = [];
            $sql = "SELECT o.*, g.goods_thumb, g.brand_id, g.user_id AS ru_id, g.goods_number AS storage, o.goods_attr " .
                "FROM " . $this->dsc->table('order_goods') . " AS o " .
                "LEFT JOIN " . $this->dsc->table('goods') . " AS g ON o.goods_id = g.goods_id " .
                "WHERE o.order_id = '{$order_id}' ";
            $res = $this->db->query($sql);

            foreach ($res as $row) {
                /* 虚拟商品支持 */
                if ($row['is_real'] == 0) {
                    /* 取得语言项 */
                    $filename = app_path('Plugins/' . $row['extension_code'] . '/Languages/common_' . $GLOBALS['_CFG']['lang'] . '.php');
                    if (file_exists($filename)) {
                        include_once($filename);
                        if (!empty($GLOBALS['_LANG'][$row['extension_code'] . '_link'])) {
                            $row['goods_name'] = $row['goods_name'] . sprintf($GLOBALS['_LANG'][$row['extension_code'] . '_link'], $row['goods_id'], $order['order_sn']);
                        }
                    }
                }

                $brand = get_goods_brand_info($row['brand_id']);
                $row['brand_name'] = $brand['brand_name'];

                $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                $row['formated_goods_price'] = price_format($row['goods_price']);

                //图片显示
                $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);

                $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
                $goods_list[] = $row;
            }
            $attr = [];
            $arr = [];
            if ($goods_attr) {
                foreach ($goods_attr as $index => $array_val) {

                    $array_val = $this->baseRepository->getExplode($array_val);

                    if ($array_val) {
                        foreach ($array_val as $value) {
                            $arr = explode(':', $value);//以 : 号将属性拆开
                            $attr[$index][] = @['name' => $arr[0], 'value' => $arr[1]];
                        }
                    }
                }
            }

            $this->smarty->assign('goods_attr', $attr);
            $this->smarty->assign('goods_list', $goods_list);
            $str = $this->smarty->fetch('order_goods_info.htm');
            $goods[] = ['order_id' => $order_id, 'str' => $str];
            return make_json_result($goods);
        } /**
         * 修改收货时间
         * by Leah
         */
        elseif ($_REQUEST['act'] == 'update_info') {
            $sign_time = local_strtotime($_REQUEST['time']);

            $order_id = $_REQUEST['order_id'];
            $sql = 'UPDATE ' . $this->dsc->table('order_info') . 'set sign_time =' . $sign_time . ' WHERE order_id =' . $order_id;
            $this->db->query($sql);
        }

        /*------------------------------------------------------ */
        //-- 设置抢单页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_grab_order') {
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $this->smarty->assign('order_id', $order_id);
            $store_list = get_store_list($order_id);
            $store_order_info = get_store_order_info($order_id, 'order_id');
            if (!empty($store_order_info)) {
                $grab_store_arr = explode(',', $store_order_info['grab_store_list']);
                foreach ($store_list as $key => $val) {
                    $store_list[$key]['is_check'] = 0;
                    if (in_array($val['id'], $grab_store_arr)) {
                        $store_list[$key]['is_check'] = 1;
                    }
                }
            }

            $this->smarty->assign('store_list', $store_list);
            $result['content'] = $this->smarty->fetch('library/set_grab_order.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 设置抢单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_grab') {
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $ru_id = get_ru_id($order_id);
            if (isset($_REQUEST['checkboxes']) && count($_REQUEST['checkboxes']) > 0) {
                $grab_store_list = implode(',', $_REQUEST['checkboxes']);
                $store_order_info = get_store_order_info($order_id, 'order_id');
                if (empty($store_order_info)) {
                    $sql = " INSERT INTO " . $this->dsc->table('store_order') . " (order_id, store_id, ru_id, is_grab_order, grab_store_list) " .
                        " VALUES ('$order_id', '0', '$ru_id', '1', '$grab_store_list') ";
                    $this->db->query($sql);
                } else {
                    $sql = " UPDATE " . $this->dsc->table('store_order') . " SET grab_store_list = '$grab_store_list' WHERE order_id = '$order_id' ";
                    $this->db->query($sql);
                }
            } else {
                $sql = " DELETE FROM" . $this->dsc->table('store_order') . " WHERE order_id = '$order_id' AND ru_id = '$ru_id' AND store_id = 0";
                $this->db->query($sql);
            }

            return sys_msg($GLOBALS['_LANG']['set_success']);
        }

        /*------------------------------------------------------ */
        //-- 部分发货弹窗 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'part_ship') {
            $check_auth = check_authz_json('order_ss_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $rec_id = empty($_REQUEST['rec_id']) ? 0 : intval($_REQUEST['rec_id']);
            //查询数据
            $sql = "SELECT o.*, g.model_inventory, g.model_attr AS model_attr, g.suppliers_id AS suppliers_id, g.goods_number AS storage, g.goods_thumb, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, p.product_sn, g.bar_code  " .
                "FROM " . $this->dsc->table('order_goods') . " AS o " .
                "LEFT JOIN " . $this->dsc->table('products') . " AS p ON o.product_id = p.product_id " .
                "LEFT JOIN " . $this->dsc->table('goods') . " AS g ON o.goods_id = g.goods_id " .
                "LEFT JOIN " . $this->dsc->table('brand') . " AS b ON g.brand_id = b.brand_id " .
                "WHERE o.rec_id = '$rec_id' ";
            $row = $this->db->getRow($sql);
            //剩余发货数量
            $row['left_number'] = $row['goods_number'] - $row['send_number'];
            //ecmoban模板堂 --zhuo start
            if ($row['product_id'] > 0) {
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $row['model_attr']);
                $row['storage'] = $products['product_number'];
            } else {
                if ($row['model_inventory'] == 1) {
                    $row['storage'] = get_warehouse_area_goods($row['warehouse_id'], $row['goods_id'], 'warehouse_goods');
                } elseif ($row['model_inventory'] == 2) {
                    $row['storage'] = get_warehouse_area_goods($row['area_id'], $row['goods_id'], 'warehouse_area_goods');
                }
            }
            //ecmoban模板堂 --zhuo end
            if ($row['extension_code'] == 'package_buy') {
                $row['storage'] = '';
            }
            //订单商品
            $order_goods = $row;
            $this->smarty->assign('order_goods', $order_goods);
            //订单详情
            $order = order_info($order_goods['order_id']);
            $this->smarty->assign('order', $order);
            $this->smarty->assign('operation', 'split');
            $content = $this->smarty->fetch('library/order_part_ship.lbi');
            return make_json_result($content);
        }

        /*------------------------------------------------------ */
        //-- 批量发货弹窗 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_ship') {
            $check_auth = check_authz_json('order_ss_edit');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $delivery_ids = empty($_REQUEST['delivery_ids']) ? '' : trim($_REQUEST['delivery_ids']);
            $delivery_ids = explode(',', $delivery_ids);
            if (empty($delivery_ids)) {
                $result = ['error' => 1, 'message' => lang('seller/common.illegal_operate'), 'content' => ''];
                return response()->json($result);
            }

            $delivery_orders = DeliveryOrder::whereIn('delivery_id', $delivery_ids)->where('status', 2);
            $delivery_orders = $delivery_orders->with([
                'getRegionProvince' => function ($query) {
                    $query->select('region_id', 'region_name');
                },
                'getRegionCity' => function ($query) {
                    $query->select('region_id', 'region_name');
                },
                'getRegionDistrict' => function ($query) {
                    $query->select('region_id', 'region_name');
                },
                'getRegionStreet' => function ($query) {
                    $query->select('region_id', 'region_name');
                }
            ]);
            $delivery_orders = $this->baseRepository->getToArrayGet($delivery_orders);

            //按快递分组
            $new_array = [];
            foreach ($delivery_orders as $key => $val) {
                /* 取得区域名 */
                $province = $val['get_region_province']['region_name'] ?? '';
                $city = $val['get_region_city']['region_name'] ?? '';
                $district = $val['get_region_district']['region_name'] ?? '';
                $street = $val['get_region_street']['region_name'] ?? '';
                $val['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

                $new_array[$val['shipping_name']][] = $val;
            }
            //统计同快递各发货单数量
            foreach ($new_array as $key => $val) {
                $arr = [];
                $arr['count'] = count($val);
                $arr['list'] = $val;
                $new_array[$key] = $arr;
            }

            $this->smarty->assign('delivery_orders', $new_array);
            $content = $this->smarty->fetch('library/order_batch_ship.lbi');
            return make_json_result($content);
        }
    }

    /**
     * 取得状态列表
     * @param string $type 类型：all | order | shipping | payment
     */
    private function get_status_list($type = 'all')
    {
        $list = [];

        if ($type == 'all' || $type == 'order') {
            $pre = $type == 'all' ? 'os_' : '';
            foreach ($GLOBALS['_LANG']['os'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'shipping') {
            $pre = $type == 'all' ? 'ss_' : '';
            foreach ($GLOBALS['_LANG']['ss'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'payment') {
            $pre = $type == 'all' ? 'ps_' : '';
            foreach ($GLOBALS['_LANG']['ps'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }
        return $list;
    }

    /**
     * 更新订单总金额
     * @param int $order_id 订单id
     * @return  bool
     */
    private function update_order_amount($order_id)
    {
        load_helper('order');
        //更新订单总金额
        $sql = "UPDATE " . $this->dsc->table('order_info') .
            " SET order_amount = " . order_due_field() .
            " WHERE order_id = '$order_id' LIMIT 1";

        return $this->db->query($sql);
    }

    /**
     * 返回某个订单可执行的操作列表，包括权限判断
     * @param array $order 订单信息 order_status, shipping_status, pay_status
     * @param bool $is_cod 支付方式是否货到付款
     * @return  array   可执行的操作  confirm, pay, unpay, prepare, ship, unship, receive, cancel, invalid, return, drop
     * 格式 array('confirm' => true, 'pay' => true)
     */
    private function operable_list($order)
    {
        /* 取得订单状态、发货状态、付款状态 */
        $os = $order['order_status'];
        $ss = $order['shipping_status'];
        $ps = $order['pay_status'];

        /* 佣金账单状态 0 未出账 1 出账 2 结账 */
        $chargeoff_status = $order['chargeoff_status'];

        /* 取得订单操作权限 */
        $actions = session('seller_action_list');
        if ($actions == 'all') {
            $priv_list = ['os' => true, 'ss' => true, 'ps' => true, 'edit' => true];
        } else {
            $actions = ',' . $actions . ',';
            $priv_list = [
                'os' => strpos($actions, ',order_os_edit,') !== false,
                'ss' => strpos($actions, ',order_ss_edit,') !== false,
                'ps' => strpos($actions, ',order_ps_edit,') !== false,
                'edit' => strpos($actions, ',order_edit,') !== false
            ];
        }

        /* 取得订单支付方式是否货到付款 */
        $payment = payment_info($order['pay_id']);
        if (isset($payment['is_cod']) && $payment['is_cod'] == 1) {
            $is_cod = true;
        } else {
            $is_cod = false;
        }

        /* 根据状态返回可执行操作 */
        $list = [];
        if (OS_UNCONFIRMED == $os) {
            /* 状态：未确认 => 未付款、未发货 */
            if ($priv_list['os']) {
                $list['confirm'] = true; // 确认
                $list['invalid'] = true; // 无效
                $list['cancel'] = true; // 取消
                if ($is_cod) {
                    /* 货到付款 */
                    if ($priv_list['ss']) {
                        $list['prepare'] = true; // 配货
                        $list['split'] = true; // 分单
                    }
                } else {
                    /* 不是货到付款 */
                    if ($priv_list['ps']) {
                        $list['pay'] = true;  // 付款
                    }
                }
            }
        } elseif (OS_RETURNED_PART == $os || (SS_RECEIVED != $ss && $chargeoff_status > 0)) {

            /* 状态：未付款 */
            if ($priv_list['ps'] < 2) {
                $list['pay'] = true; // 付款
            }

            if ($ss != SS_RECEIVED) {
                /* 状态：部分退货 */
                $list['receive'] = true; // 收货确认
            }
        } elseif (OS_CONFIRMED == $os || OS_SPLITED == $os || OS_SPLITING_PART == $os) {
            /* 状态：已确认 */
            if (PS_UNPAYED == $ps || PS_PAYED_PART == $ps) {
                /* 状态：已确认、未付款 */
                if (SS_UNSHIPPED == $ss || SS_PREPARING == $ss) {
                    /* 状态：已确认、未付款、未发货（或配货中） */
                    if ($priv_list['os']) {
                        $list['cancel'] = true; // 取消
                        $list['invalid'] = true; // 无效
                    }
                    if ($is_cod) {
                        /* 货到付款 */
                        if ($priv_list['ss']) {
                            if (SS_UNSHIPPED == $ss) {
                                $list['prepare'] = true; // 配货
                            }
                            $list['split'] = true; // 分单
                        }
                    } else {
                        /* 不是货到付款 */
                        if ($priv_list['ps']) {
                            $list['pay'] = true; // 付款
                        }
                    }
                } /* 状态：已确认、未付款、发货中 */
                elseif (SS_SHIPPED_ING == $ss || SS_SHIPPED_PART == $ss) {
                    // 部分分单
                    if (OS_SPLITING_PART == $os) {
                        $list['split'] = true; // 分单
                    }
                    $list['to_delivery'] = true; // 去发货
                } else {
                    /* 状态：已确认、未付款、已发货或已收货 => 货到付款 */
                    if ($priv_list['ps']) {
                        $list['pay'] = true; // 付款
                    }
                    if ($priv_list['ss']) {
                        if (SS_SHIPPED == $ss) {
                            $list['receive'] = true; // 收货确认
                        }
                        $list['unship'] = true; // 设为未发货
                        if ($priv_list['os']) {
                            $list['return'] = true; // 退货
                        }
                    }
                }
            } else {
                /* 状态：已确认、已付款和付款中 */
                if (SS_UNSHIPPED == $ss || SS_PREPARING == $ss) {
                    /* 状态：已确认、已付款和付款中、未发货（配货中） => 不是货到付款 */
                    if ($priv_list['ss']) {
                        if (SS_UNSHIPPED == $ss) {
                            $list['prepare'] = true; // 配货
                        }
                        $list['split'] = true; // 分单
                    }
                    if ($priv_list['ps']) {
                        $list['unpay'] = true; // 设为未付款
                        if ($priv_list['os']) {
                            //$list['cancel'] = true; // 取消  暂时注释 liu
                        }
                    }
                } /* 状态：已确认、未付款、发货中 */
                elseif (SS_SHIPPED_ING == $ss || SS_SHIPPED_PART == $ss) {
                    // 部分分单
                    if (OS_SPLITING_PART == $os) {
                        $list['split'] = true; // 分单
                    }
                    $list['to_delivery'] = true; // 去发货
                } else {
                    /* 状态：已确认、已付款和付款中、已发货或已收货 */
                    if ($priv_list['ss']) {
                        if (SS_SHIPPED == $ss) {
                            $list['receive'] = true; // 收货确认
                        }
                        if (!$is_cod && $ss != SS_RECEIVED) {
                            $list['unship'] = true; // 设为未发货
                        }
                    }
                    if ($priv_list['ps'] && $is_cod) {
                        $list['unpay'] = true; // 设为未付款
                    }
                    if ($priv_list['os'] && $priv_list['ss'] && $priv_list['ps']) {
                        $list['return'] = true; // 退货（包括退款）
                    }
                }
            }
        } elseif (OS_CANCELED == $os) {
            /* 状态：取消 */
            if ($priv_list['os']) {
                // $list['confirm'] = true; 暂时注释 liu
            }
            if ($priv_list['edit']) {
                $list['remove'] = true;
            }
        } elseif (OS_INVALID == $os) {
            /* 状态：无效 */
            if ($priv_list['os']) {
                //$list['confirm'] = true; 暂时注释 liu
            }
            if ($priv_list['edit']) {
                $list['remove'] = true;
            }
        } elseif (OS_RETURNED == $os) {
            /* 状态：退货 */
            if ($priv_list['os']) {
                $list['confirm'] = true;
            }
        }

        if ((OS_CONFIRMED == $os || OS_SPLITED == $os || OS_SHIPPED_PART == $os) && PS_PAYED == $ps && (SS_UNSHIPPED == $ss || SS_SHIPPED_PART == $ss)) {
            /* 状态：（已确认、已分单）、已付款和未发货 */
            if ($priv_list['os'] && $priv_list['ss'] && $priv_list['ps']) {
                $list['return'] = true; // 退货（包括退款）
            }
        }

        /* 修正发货操作 */
        if (!empty($list['split'])) {
            /* 如果是团购活动且未处理成功，不能发货 */
            if ($order['extension_code'] == 'group_buy') {
                $where = [
                    'group_buy_id' => intval($order['extension_id']),
                ];
                $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

                if ($group_buy['status'] != GBS_SUCCEED) {
                    unset($list['split']);
                    unset($list['to_delivery']);
                }
            }

            /* 如果部分发货 不允许 取消 订单 */
            if ($this->order_deliveryed($order['order_id'])) {
                $list['return'] = true; // 退货（包括退款）
                unset($list['cancel']); // 取消
            }
        }

        /* 同意申请 */
        /*
     * by Leah
     */
        $list['after_service'] = true;
        $list['receive_goods'] = true;
        $list['agree_apply'] = true;
        $list['refound'] = true;
        $list['swapped_out_single'] = true;
        $list['swapped_out'] = true;
        $list['complete'] = true;
        $list['refuse_apply'] = true;
        /*
     * by Leah
     */

        return $list;
    }

    /**
     * 处理编辑订单时订单金额变动
     * @param array $order 订单信息
     * @param array $msgs 提示信息
     * @param array $links 链接信息
     */
    private function handle_order_money_change($order, &$msgs, &$links)
    {
        $order_id = $order['order_id'];
        if ($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYING) {
            /* 应付款金额 */
            $money_dues = $order['order_amount'];
            if ($money_dues > 0) {
                /* 修改订单为未付款 */
                update_order($order_id, ['pay_status' => PS_UNPAYED, 'pay_time' => 0]);
                $msgs[] = $GLOBALS['_LANG']['amount_increase'];
                $links[] = ['text' => lang('seller/order.order_info'), 'href' => 'order.php?act=info&order_id=' . $order_id];
            } elseif ($money_dues < 0) {
                $anonymous = $order['user_id'] > 0 ? 0 : 1;
                $msgs[] = $GLOBALS['_LANG']['amount_decrease'];
                $links[] = ['text' => lang('seller/order.refund'), 'href' => 'order.php?act=process&func=load_refund&anonymous=' .
                    $anonymous . '&order_id=' . $order_id . '&refund_amount=' . abs($money_dues)];
            }
        }
    }

    /**
     *  获取订单列表信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function order_list($page = 0)
    {
        $adminru = get_admin_ru_id();

        $result = get_filter();
        if ($result === false) {
            /* 过滤信息 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
            $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);
            $filter['shipped_deal'] = empty($_REQUEST['shipped_deal']) ? '' : trim($_REQUEST['shipped_deal']);

            if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
                $filter['order_sn'] = json_str_iconv($filter['order_sn']);
                $filter['consignee'] = json_str_iconv($filter['consignee']);
                $filter['address'] = json_str_iconv($filter['address']);
            }

            $filter['email'] = empty($_REQUEST['email']) ? '' : trim($_REQUEST['email']);
            $filter['zipcode'] = empty($_REQUEST['zipcode']) ? '' : trim($_REQUEST['zipcode']);
            $filter['tel'] = empty($_REQUEST['tel']) ? '' : trim($_REQUEST['tel']);
            $filter['mobile'] = empty($_REQUEST['mobile']) ? 0 : trim($_REQUEST['mobile']);
            $filter['country'] = empty($_REQUEST['order_country']) ? 0 : intval($_REQUEST['order_country']);
            $filter['province'] = empty($_REQUEST['order_province']) ? 0 : intval($_REQUEST['order_province']);
            $filter['city'] = empty($_REQUEST['order_city']) ? 0 : intval($_REQUEST['order_city']);
            $filter['district'] = empty($_REQUEST['order_district']) ? 0 : intval($_REQUEST['order_district']);
            $filter['street'] = empty($_REQUEST['order_street']) ? 0 : intval($_REQUEST['order_street']);
            $filter['shipping_id'] = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
            $filter['pay_id'] = empty($_REQUEST['pay_id']) ? 0 : intval($_REQUEST['pay_id']);
            $filter['order_status'] = isset($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : -1;
            $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : -1;
            $filter['pay_status'] = isset($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : -1;
            $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
            $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
            $filter['composite_status'] = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : -1;
            $filter['group_buy_id'] = isset($_REQUEST['group_buy_id']) ? intval($_REQUEST['group_buy_id']) : 0;
            $filter['presale_id'] = isset($_REQUEST['presale_id']) ? intval($_REQUEST['presale_id']) : 0; // 预售id
            $filter['store_id'] = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0; // 门店id
            $filter['order_cat'] = isset($_REQUEST['order_cat']) ? trim($_REQUEST['order_cat']) : '';

            $filter['source'] = empty($_REQUEST['source']) ? '' : trim($_REQUEST['source']); //来源起始页

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ? local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
            $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ? local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);

            //确认收货时间 bylu:
            $filter['start_take_time'] = empty($_REQUEST['start_take_time']) ? '' : (strpos($_REQUEST['start_take_time'], '-') > 0 ? local_strtotime($_REQUEST['start_take_time']) : $_REQUEST['start_take_time']);
            $filter['end_take_time'] = empty($_REQUEST['end_take_time']) ? '' : (strpos($_REQUEST['end_take_time'], '-') > 0 ? local_strtotime($_REQUEST['end_take_time']) : $_REQUEST['end_take_time']);

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';
            $filter['store_type'] = isset($_REQUEST['store_type']) ? trim($_REQUEST['store_type']) : '';

            $filter['serch_type'] = !isset($_REQUEST['serch_type']) ? -1 : intval($_REQUEST['serch_type']);

            $where = ' WHERE 1 AND o.main_count = 0 ';

            if ($filter['keywords']) {
                $where .= " AND (" .
                    "o.order_sn LIKE '%" . $filter['keywords'] . "%'";

                $where .= " OR (SELECT COUNT(*) FROM " . $this->dsc->table('order_goods') . " AS iog WHERE iog.order_id = o.order_id " .
                    " AND (iog.goods_name LIKE '%" . $filter['keywords'] . "%' " .
                    " OR iog.goods_sn LIKE '%" . $filter['keywords'] . "%')) > 0 ";

                $where .= ")";
            }

            if ($adminru['ru_id'] > 0) {
                $where .= " AND o.ru_id = '" . $adminru['ru_id'] . "' ";
            }

            if ($filter['shipped_deal']) {
                $where .= " AND o.shipping_status<>" . SS_RECEIVED;
            }

            $store_search = -1;
            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] > -1) {
                if ($adminru['ru_id'] == 0) {
                    if ($filter['store_search'] > 0) {
                        if ($filter['store_type']) {
                            $store_search_where = "AND msi.shopNameSuffix = '" . $filter['store_type'] . "'";
                        }

                        if ($filter['store_search'] == 1) {
                            $where .= " AND o.ru_id = '" . $filter['merchant_id'] . "' ";
                        } elseif ($filter['store_search'] == 2) {
                            $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                        } elseif ($filter['store_search'] == 3) {
                            $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                        }

                        if ($filter['store_search'] > 1) {
                            $where .= " AND (SELECT COUNT(*) FROM " . $this->dsc->table('merchants_shop_information') . ' AS msi ' .
                                " WHERE msi.user_id = o.ru_id $store_where) > 0 ";
                        }
                    } else {
                        $store_search = 0;
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            if ($filter['store_id'] > 0) {
                $where .= " AND (SELECT COUNT(*) FROM " . $this->dsc->table('store_order') . " AS sto WHERE sto.order_id = o.order_id AND sto.store_id  = '" . $filter['store_id'] . "') > 0 ";
            }

            if ($filter['order_sn']) {
                $where .= " AND o.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
            }
            if ($filter['consignee']) {
                $where .= " AND o.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
            }
            if ($filter['email']) {
                $where .= " AND o.email LIKE '%" . mysql_like_quote($filter['email']) . "%'";
            }
            if ($filter['address']) {
                $where .= " AND o.address LIKE '%" . mysql_like_quote($filter['address']) . "%'";
            }
            if ($filter['zipcode']) {
                $where .= " AND o.zipcode LIKE '%" . mysql_like_quote($filter['zipcode']) . "%'";
            }
            if ($filter['tel']) {
                $where .= " AND o.tel LIKE '%" . mysql_like_quote($filter['tel']) . "%'";
            }
            if ($filter['mobile']) {
                $where .= " AND o.mobile LIKE '%" . mysql_like_quote($filter['mobile']) . "%'";
            }
            if ($filter['country']) {
                $where .= " AND o.country = '$filter[country]'";
            }
            if ($filter['province']) {
                $where .= " AND o.province = '$filter[province]'";
            }
            if ($filter['city']) {
                $where .= " AND o.city = '$filter[city]'";
            }
            if ($filter['district']) {
                $where .= " AND o.district = '$filter[district]'";
            }
            if ($filter['street']) {
                $where .= " AND o.street = '$filter[street]'";
            }
            if ($filter['shipping_id']) {
                $where .= " AND o.shipping_id  = '$filter[shipping_id]'";
            }
            if ($filter['pay_id']) {
                $where .= " AND o.pay_id  = '$filter[pay_id]'";
            }
            if ($filter['order_status'] != -1) {
                $where .= " AND o.order_status  = '$filter[order_status]'";
            }
            if ($filter['shipping_status'] != -1) {
                $where .= " AND o.shipping_status = '$filter[shipping_status]'";
            }
            if ($filter['pay_status'] != -1) {
                $where .= " AND o.pay_status = '$filter[pay_status]'";
            }
            if ($filter['user_id']) {
                $where .= " AND o.user_id = '$filter[user_id]'";
            }
            if ($filter['user_name']) {
                $where .= " AND (SELECT u.user_id FROM " . $this->dsc->table('users') . " AS u WHERE u.user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%' LIMIT 1) = o.user_id";
            }
            if ($filter['start_time']) {
                $where .= " AND o.add_time >= '$filter[start_time]'";
            }
            if ($filter['end_time']) {
                $where .= " AND o.add_time <= '$filter[end_time]'";
            }

            $alias = "o.";
            //综合状态
            switch ($filter['composite_status']) {
                case CS_AWAIT_PAY:
                    $where .= $this->orderService->orderQuerySql('await_pay', $alias);
                    break;

                case CS_AWAIT_SHIP:
                    $where .= $this->orderService->orderQuerySql('await_ship', $alias);
                    break;

                case CS_FINISHED:
                    $where .= $this->orderService->orderQuerySql('finished', $alias);
                    break;
                //确认收货 bylu;
                case CS_CONFIRM_TAKE:
                    $where .= $this->orderService->orderQuerySql('confirm_take', $alias);
                    break;

                case PS_PAYING:
                    if ($filter['composite_status'] != -1) {
                        $where .= " AND o.pay_status = '$filter[composite_status]' ";
                    }
                    break;
                case OS_SHIPPED_PART:
                    if ($filter['composite_status'] != -1) {
                        $where .= " AND o.shipping_status  = '$filter[composite_status]'-2 ";
                    }
                    break;
                default:
                    if ($filter['composite_status'] != -1) {
                        $where .= " AND o.order_status = '$filter[composite_status]' ";
                    }
            }

            /* 团购订单 */
            if ($filter['group_buy_id']) {
                $where .= " AND o.extension_code = 'group_buy' AND o.extension_id = '$filter[group_buy_id]' ";
            }
            /* 预售订单 */
            if ($filter['presale_id']) {
                $where .= " AND o.extension_code = 'presale' AND o.extension_id = '$filter[presale_id]' ";
            }

            /* 如果管理员属于某个办事处，只列出这个办事处管辖的订单 */
            $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "' AND action_list <> 'all'";
            $agency_id = $this->db->getOne($sql);

            if ($agency_id > 0) {
                $where .= " AND o.agency_id = '$agency_id' ";
            }

            if ($filter['order_cat']) {
                switch ($filter['order_cat']) {
                    case 'stages':
                        $where .= " AND (SELECT COUNT(*) FROM " . $this->dsc->table('baitiao_log') . " AS b WHERE b.order_id = o.order_id) > 0 ";
                        $where .= " AND b.order_id > 0 ";
                        break;
                    case 'zc':
                        $where .= " AND o.is_zc_order = 1 ";
                        break;
                    case 'store':
                        $where .= " AND (SELECT COUNT(*) FROM " . $this->dsc->table('store_order') . " AS s WHERE s.order_id = o.order_id) > 0 ";
                        $where .= " AND s.order_id > 0 ";
                        break;
                    case 'other':
                        $where .= " AND length(o.extension_code) > 0 ";
                        break;
                    case 'dbdd':
                        $where .= " AND o.extension_code = 'snatch' ";
                        break;
                    case 'msdd':
                        $where .= " AND o.extension_code = 'seckill' ";
                        break;
                    case 'tgdd':
                        $where .= " AND o.extension_code = 'group_buy' ";
                        break;
                    case 'pmdd':
                        $where .= " AND o.extension_code = 'auction' ";
                        break;
                    case 'jfdd':
                        $where .= " AND o.extension_code = 'exchange_goods' ";
                        break;
                    case 'ysdd':
                        $where .= " AND o.extension_code = 'presale' ";
                        break;
                    default:
                }
            }

            /* 分页大小 */
            $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

            if ($page > 0) {
                $filter['page'] = $page;
            }

            $page_size = request()->cookie('dsccp_page_size');
            if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
                $filter['page_size'] = intval($_REQUEST['page_size']);
            } elseif (intval($page_size) > 0) {
                $filter['page_size'] = intval($page_size);
            } else {
                $filter['page_size'] = 15;
            }

            $where_store = '';
            if (empty($filter['start_take_time']) || empty($filter['end_take_time'])) {
                if ($store_search == 0 && $adminru['ru_id'] == 0) {
                    $where_store = " AND o.ru_id = 0 AND o.main_count = 0";
                }
            }

            if (!empty($filter['start_take_time']) || !empty($filter['end_take_time'])) {
                $where_action = '';
                if ($filter['start_take_time']) {
                    $where_action .= " AND oa.log_time >= '$filter[start_take_time]'";
                }
                if ($filter['end_take_time']) {
                    $where_action .= " AND oa.log_time <= '$filter[end_take_time]'";
                }

                $where_action .= order_take_query_sql('finished', "oa.");

                $where .= " AND (SELECT COUNT(*) FROM " . $this->dsc->table('order_action') . " AS oa WHERE o.order_id = oa.order_id $where_action) > 0";
            }

            //判断订单筛选条件
            switch ($filter['serch_type']) {
                case 0:
                    $where .= " AND o.order_status = '" . OS_UNCONFIRMED . "' "; //待确认
                    break;
                case 100:
                    $where .= " AND o.pay_status = '" . PS_UNPAYED . "' AND o.order_status = '" . OS_CONFIRMED . "' "; //待付款
                    break;
                case 101:
                    $where .= " AND (o.shipping_status = '" . SS_UNSHIPPED . "' OR o.shipping_status = '" . SS_PREPARING . "') AND o.pay_status = '" . PS_PAYED . "' AND o.order_status = '" . OS_CONFIRMED . "' AND (SELECT ore.ret_id FROM " . $this->dsc->table('order_return') . " AS ore WHERE ore.order_id = o.order_id LIMIT 1) IS NULL "; //待发货
                    break;
                case 102:
                    $where .= " AND o.shipping_status = '" . SS_RECEIVED . "' "; //已完成
                    break;
                case 1:
                    $where .= " AND o.pay_status = '" . PS_PAYING . "' "; //付款中
                    break;
                case 2:
                    $where .= " AND o.order_status = '" . OS_CANCELED . "' "; //取消
                    break;
                case 3:
                    $where .= " AND o.order_status = '" . OS_INVALID . "' "; //无效
                    break;
                case 4:
                    $where .= "AND (SELECT COUNT(*) FROM " . $this->dsc->table('order_return') . " AS ore WHERE ore.order_id = o.order_id AND ore.return_type IN(1, 3) AND ore.refound_status = 0 AND ore.return_status != 6) > 0 " .
                        " AND o.order_status NOT IN ('" . OS_CANCELED . "', '" . OS_INVALID . "', '" . OS_RETURNED . "', '" . OS_RETURNED_PART . "', '" . OS_ONLY_REFOUND . "')"; //退货
                    break;
                case 6:
                    $where .= " AND o.shipping_status = '" . SS_SHIPPED . "' "; //待收货
            }

            /* 记录总数 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('order_info') . " AS o " .
                $where . $where_store;

            $filter['record_count'] = $this->db->getOne($sql);

            $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

            /* 分页大小 */
            $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
            if ($page > 0) {
                $filter['page'] = $page;
            }

            $page_size = request()->cookie('dsccp_page_size');
            if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
                $filter['page_size'] = intval($_REQUEST['page_size']);
            } elseif (intval($page_size) > 0) {
                $filter['page_size'] = intval($page_size);
            } else {
                $filter['page_size'] = 15;
            }

            $cross_select = '';

            if (CROSS_BORDER === true) {
                $cross_select = ', o.rel_name, o.id_num, o.rate_fee ';
            }

            /* 查询 */
            $select = "(SELECT bai.is_stages FROM " . $this->dsc->table('baitiao_log') . " as bai WHERE o.order_id=bai.order_id LIMIT 1) AS is_stages,";
            $sql = "SELECT $select o.order_id, o.main_order_id, o.order_sn, o.ru_id, o.add_time, o.order_status, o.shipping_status, o.pay_status, o.order_amount, o.money_paid, o.is_delete," .
                "o.shipping_fee, o.insure_fee, o.pay_fee, o.pack_fee, o.card_fee, o.surplus,o.tax, o.integral_money, o.bonus, o.discount, o.coupons," .
                "o.shipping_time, o.auto_delivery_time, o.consignee, o.address, o.email, o.tel, o.mobile, o.extension_code, o.rate_fee, " .
                "o.extension_id, o.user_id, o.referer, o.froms, o.chargeoff_status, o.pay_id, o.pay_name, o.shipping_id, o.shipping_name, o.goods_amount, " .
                "(" . $this->orderService->orderAmountField('o.') . ") AS total_fee, (o.goods_amount + o.tax + o.shipping_fee + o.insure_fee + o.pay_fee + o.pack_fee + o.card_fee - o.discount) AS total_fee_order, o.pay_id " . $cross_select .
                " FROM " . $this->dsc->table('order_info') . " AS o " .
                $where . $where_store .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

            foreach (['order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name'] as $val) {
                $filter[$val] = stripslashes($filter[$val]);
            }

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $row = $this->db->getAll($sql);

        /* 格式话数据 */
        foreach ($row as $key => $value) {

            $row[$key]['ru_id'] = $value['ru_id'];

            if (!$value['is_stages']) {
                $row[$key]['is_stages'] = 0;
            }

            //判断订单是不是门店抢单订单
            $sql = "SELECT id FROM " . $this->dsc->table('store_order') . " WHERE order_id = '" . $value['order_id'] . "'";
            $row[$key]['store_order_id'] = $this->db->getOne($sql);

            /* 查询更新支付状态 start */
            if (($value['order_status'] == OS_UNCONFIRMED || $value['order_status'] == OS_CONFIRMED || $value['order_status'] == OS_SPLITED) && $value['pay_status'] == PS_UNPAYED) {
                $pay_log = get_pay_log($value['order_id'], 1);
                if ($pay_log && $pay_log['is_paid'] == 0) {
                    $payment = payment_info($value['pay_id']);

                    if ($payment && strpos($payment['pay_code'], 'pay_') === false) {
                        $payObj = $this->commonRepository->paymentInstance($payment['pay_code']);

                        if (!is_null($payObj)) {
                            /* 判断类对象方法是否存在 */
                            if (is_callable([$payObj, 'orderQuery'])) {
                                $order_other = [
                                    'order_sn' => $value['order_sn'],
                                    'log_id' => $pay_log['log_id'],
                                    'order_amount' => $value['order_amount'],
                                ];

                                $payObj->orderQuery($order_other);

                                $sql = "SELECT order_status, shipping_status, pay_status, pay_time FROM " . $this->dsc->table('order_info') . " WHERE order_id = '" . $value['order_id'] . "' LIMIT 1";
                                $order_info = $this->db->getRow($sql);
                                if ($order_info) {
                                    $value['order_status'] = $order_info['order_status'];
                                    $value['shipping_status'] = $order_info['shipping_status'];
                                    $value['pay_status'] = $order_info['pay_status'];
                                    $value['pay_time'] = $order_info['pay_time'];
                                }
                            }
                        }
                    }
                }
            }
            /* 查询更新支付状态 end */

            /* 处理确认收货时间 start */
            if ($GLOBALS['_CFG']['open_delivery_time'] == 1) {
                if (($value['order_status'] == OS_CONFIRMED || $value['order_status'] == OS_SPLITED) && $value['pay_status'] == PS_PAYED && $value['shipping_status'] == SS_SHIPPED && $value['chargeoff_status'] == 0) {
                    $delivery_time = $value['shipping_time'] + 24 * 3600 * $value['auto_delivery_time'];

                    $confirm_take_time = gmtime();

                    if ($confirm_take_time > $delivery_time) { //自动确认发货操作

                        $sql = "UPDATE " . $this->dsc->table('order_info') . " SET order_status = '" . $value['order_status'] . "', " .
                            "shipping_status = '" . SS_RECEIVED . "', pay_status = '" . $value['pay_status'] . "', confirm_take_time = '$confirm_take_time' WHERE order_id = '" . $value['order_id'] . "'";
                        $this->db->query($sql);

                        $row[$key]['shipping_status'] = SS_RECEIVED;

                        /* 记录日志 */
                        $note = $GLOBALS['_LANG']['self_motion_goods'];
                        order_action($value['order_sn'], $value['order_status'], SS_RECEIVED, $value['pay_status'], $note, $GLOBALS['_LANG']['system_handle'], 0, $confirm_take_time);

                        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('seller_bill_order') . " WHERE order_id = '" . $value['order_id'] . "'";
                        $bill_order_info = $this->db->getOne($sql);

                        if ($bill_order_info <= 0) {


                            $negativeCount = SellerNegativeOrder::where('order_id', $value['order_id'])->count();

                            $seller_id = $value['ru_id'];
                            $value_card = $this->db->getOne("SELECT use_val FROM " . $this->dsc->table('value_card_record') . " WHERE order_id = '" . $value['order_id'] . "'", true);

                            if ($negativeCount <= 0) {
                                $return_amount_info = $this->orderRefoundService->orderReturnAmount($value['order_id']);
                            } else {
                                $return_amount_info['return_amount'] = 0;
                                $return_amount_info['return_rate_fee'] = 0;
                                $return_amount_info['ret_id'] = [];
                            }

                            if ($value['order_amount'] > 0 && $value['order_amount'] > $value['rate_fee']) {
                                $order_amount = $value['order_amount'] - $value['rate_fee'];
                            } else {
                                $order_amount = $value['order_amount'];
                            }

                            $other = array(
                                'user_id' => $value['user_id'],
                                'seller_id' => $seller_id,
                                'order_id' => $value['order_id'],
                                'order_sn' => $value['order_sn'],
                                'order_status' => $value['order_status'],
                                'shipping_status' => SS_RECEIVED,
                                'pay_status' => $value['pay_status'],
                                'order_amount' => $order_amount,
                                'return_amount' => $return_amount_info['return_amount'],
                                'goods_amount' => $value['goods_amount'],
                                'tax' => $value['tax'],
                                'shipping_fee' => $value['shipping_fee'],
                                'insure_fee' => $value['insure_fee'],
                                'pay_fee' => $value['pay_fee'],
                                'pack_fee' => $value['pack_fee'],
                                'card_fee' => $value['card_fee'],
                                'bonus' => $value['bonus'],
                                'integral_money' => $value['integral_money'],
                                'coupons' => $value['coupons'],
                                'discount' => $value['discount'],
                                'value_card' => $value_card ? $value_card : 0,
                                'money_paid' => $value['money_paid'],
                                'surplus' => $value['surplus'],
                                'confirm_take_time' => $value['confirm_take_time'],
                                'rate_fee' => $value['rate_fee'],
                                'return_rate_fee' => $return_amount_info['return_rate_price']
                            );

                            if ($seller_id) {
                                $this->commissionService->getOrderBillLog($other);
                                $this->commissionService->setBillOrderReturn($return_amount_info['ret_id'], $other['order_id']);
                            }
                        }
                    }
                } else {
                    if (empty($value['confirm_take_time'])) {
                        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('seller_bill_order') . " WHERE order_id = '" . $value['order_id'] . "'";
                        $bill_order_count = $this->db->getOne($sql, true);

                        if ($bill_order_count > 0 && $value['shipping_status'] == SS_RECEIVED) {
                            $sql = "SELECT MAX(log_time) AS log_time FROM " . $this->dsc->table('order_action') . " WHERE order_id = '" . $value['order_id'] . "' AND shipping_status = '" . SS_RECEIVED . "'";
                            $confirm_take_time = $this->db->getOne($sql, true);

                            if (empty($confirm_take_time)) {
                                $confirm_take_time = gmtime();

                                $note = $GLOBALS['_LANG']['admin_order_list_motion'];
                                order_action($value['order_sn'], $value['order_status'], $value['shipping_status'], $value['pay_status'], $note, $GLOBALS['_LANG']['system_handle'], 0, $confirm_take_time);
                            }

                            $log_other = array(
                                'confirm_take_time' => $confirm_take_time
                            );

                            $this->db->autoExecute($this->dsc->table('order_info'), $log_other, 'UPDATE', "order_id = '" . $value['order_id'] . "'");
                            $this->db->autoExecute($this->dsc->table('seller_bill_order'), $log_other, 'UPDATE', "order_id = '" . $value['order_id'] . "'");
                        }
                    }
                }
            }
            /* 处理确认收货时间 end */

            //账单编号
            if ($value['chargeoff_status'] > 0) {
                $bill = $this->db->getRow(" SELECT scb.id, scb.bill_sn, scb.seller_id, scb.proportion, scb.commission_model FROM " . $this->dsc->table('seller_bill_order') . " AS sbo " .
                    "LEFT JOIN " . $this->dsc->table('seller_commission_bill') . " AS scb ON sbo.bill_id = scb.id " .
                    "WHERE sbo.order_id = '" . $value['order_id'] . "' ");
            } else {
                $sql = "SELECT bill_id, chargeoff_status FROM " . $this->dsc->table('seller_bill_order') . " WHERE order_id = '" . $value['order_id'] . "' AND chargeoff_status > 0";
                $bill = $this->db->getRow($sql);

                if ($bill) {
                    $bill = $this->db->getRow("SELECT * FROM " . $this->dsc->table('seller_commission_bill') .
                        "WHERE id = '" . $bill['bill_id'] . "'");

                    $sql = "UPDATE " . $this->dsc->table('order_info') . " SET chargeoff_status = '" . $bill['chargeoff_status'] . "' WHERE order_id = '" . $value['order_id'] . "'";
                    $this->db->query($sql);
                }
            }

            if ($bill) {
                $row[$key]['bill_id'] = $bill['id'];
                $row[$key]['bill_sn'] = $bill['bill_sn'];
                $row[$key]['seller_id'] = $bill['seller_id'];
                $row[$key]['proportion'] = $bill['proportion'];
                $row[$key]['commission_model'] = $bill['commission_model'];
            }

            //取得团购活动信息
            if ($value['extension_code'] == 'group_buy') {
                $where = [
                    'group_buy_id' => intval($value['extension_id']),
                    'path' => 'seller'
                ];
                $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

                //团购状态
                $status = $this->groupBuyService->getGroupBuyStatus($group_buy);
                if ($status == 0) {
                    $row[$key]['cur_status'] = $GLOBALS['_LANG']['no_start'];
                } elseif ($status == 1) {
                    $row[$key]['cur_status'] = $GLOBALS['_LANG']['ongoing'];
                } elseif ($status == 2) {
                    $row[$key]['cur_status'] = $GLOBALS['_LANG']['ended_to_group_check_page'];
                } elseif ($status == 3) {
                    $row[$key]['cur_status'] = $GLOBALS['_LANG']['group_success'];
                } else {
                    $row[$key]['cur_status'] = $GLOBALS['_LANG']['group_fail'];
                }
            }

            //查商家ID
            $sql = "SELECT ru_id, extension_code AS iog_extension_code FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '" . $value['order_id'] . "' ";
            $order_goods = $this->db->getAll($sql);

            $first_order = reset($order_goods);
            $value['ru_id'] = $first_order['ru_id'];
            if (count($order_goods) > 1) {
                $iog_extension_codes = array_column($order_goods, 'iog_extension_code');
                $row[$key]['iog_extension_codes'] = array_unique($iog_extension_codes);
            } else {
                $row[$key]['iog_extension_code'] = $first_order['iog_extension_code'];
            }

            //查会员名称
            $sql = " SELECT user_name FROM " . $this->dsc->table('users') . " WHERE user_id = '" . $value['user_id'] . "'";
            $value['buyer'] = $this->db->getOne($sql, true);
            $row[$key]['buyer'] = !empty($value['buyer']) ? $value['buyer'] : $GLOBALS['_LANG']['anonymous'];

            $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
            $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
            $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
            $row[$key]['old_shipping_fee'] = $value['shipping_fee'];
            $row[$key]['shipping_fee'] = price_format($value['shipping_fee']);
            $row[$key]['short_order_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);

            $value_card = $this->db->getOne("SELECT use_val FROM " . $this->dsc->table('value_card_record') . " WHERE order_id = '" . $value['order_id'] . "'", true);
            $row[$key]['value_card'] = $value_card ? $value_card : 0;
            $row[$key]['formated_value_card'] = price_format($value_card);

            $row[$key]['formated_total_fee_order'] = price_format($value['total_fee_order']);
            /* 取得区域名 */
            $row[$key]['region'] = $this->userAddressService->getUserRegionAddress($value['order_id']);

            //判断是否为门店订单 by wu
            $is_store_order = $this->db->getOne(" SELECT COUNT(*) FROM " . $this->dsc->table('store_order') . " WHERE order_id = '" . $value['order_id'] . "' ", true);
            $row[$key]['is_store_order'] = empty($is_store_order) ? 0 : 1;

            //判断是否为退换货订单 start
            $sql = "SELECT ret_id FROM " . $this->dsc->table('order_return') . " WHERE order_id =" . $value['order_id'];
            $row[$key]['is_order_return'] = $this->db->getOne($sql);
            //判断是否为退换货订单 end

            //ecmoban模板堂 --zhuo start
            $row[$key]['user_name'] = $this->merchantCommonService->getShopName($value['ru_id'], 1);

            $order_id = $value['order_id'];
            $date = ['order_id'];

            $order_child = count(get_table_date('order_info', "main_order_id='$order_id'", $date, 1));
            $row[$key]['order_child'] = $order_child;

            $date = ['order_sn'];
            $child_list = get_table_date('order_info', "main_order_id='$order_id'", $date, 1);
            $row[$key]['child_list'] = $child_list;
            //ecmoban模板堂 --zhuo end

            $order = [
                'order_id' => $value['order_id'],
                'order_sn' => $value['order_sn']
            ];

            $goods = $this->get_order_goods($order);
            $row[$key]['goods_list'] = $goods['goods_list'];
            if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED) {
                /* 如果该订单为无效或取消则显示删除链接 */
                $row[$key]['can_remove'] = 1;
            } else {
                $row[$key]['can_remove'] = 0;
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$key]['mobile'] = $this->dscRepository->stringToStar($value['mobile']);
                $row[$key]['buyer'] = $this->dscRepository->stringToStar($row[$key]['buyer']);
                $row[$key]['tel'] = $this->dscRepository->stringToStar($value['tel']);
                $row[$key]['email'] = $this->dscRepository->stringToStar($value['email']);
            }
        }

        $arr = ['orders' => $row, 'filter' => $filter, 'page_count' => intval($filter['page_count']), 'record_count' => $filter['record_count']];
        return $arr;
    }

    /**
     * 取得供货商列表
     * @return array    二维数组
     */
    private function get_suppliers_list()
    {
        $sql = 'SELECT *
            FROM ' . $this->dsc->table('suppliers') . '
            WHERE is_check = 1
            ORDER BY suppliers_name ASC';
        $res = $this->db->getAll($sql);

        if (!is_array($res)) {
            $res = [];
        }

        return $res;
    }

    /**
     * 取得订单商品
     * @param array $order 订单数组
     * @return array
     */
    private function get_order_goods($order)
    {
        $goods_list = [];
        $goods_attr = [];
        $sql = "SELECT o.*, o.extension_code AS iog_extension_code, g.model_inventory, g.model_attr AS model_attr, g.suppliers_id AS suppliers_id, g.goods_number AS storage, g.goods_thumb, g.goods_img, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, p.product_sn, g.bar_code, g.goods_unit, " .
            "oi.order_sn, oi.extension_code as oi_extension_code, oi.extension_id " .
            "FROM " . $this->dsc->table('order_goods') . " AS o " .
            "LEFT JOIN " . $this->dsc->table('products') . " AS p ON o.product_id = p.product_id " .
            "LEFT JOIN " . $this->dsc->table('goods') . " AS g ON o.goods_id = g.goods_id " .
            "LEFT JOIN " . $this->dsc->table('brand') . " AS b ON g.brand_id = b.brand_id " .
            "LEFT JOIN " . $this->dsc->table('order_info') . " AS oi ON oi.order_id = o.order_id " .
            "WHERE o.order_id = '$order[order_id]' ";
        $res = $this->db->query($sql);

        if ($res) {
            foreach ($res as $row) {
                $sql = "SELECT ret_id FROM " . $this->dsc->table('order_return') . " WHERE rec_id = '" . $row['rec_id'] . "'";
                $row['ret_id'] = $this->db->getOne($sql);

                // 虚拟商品支持
                if ($row['is_real'] == 0) {
                    /* 取得语言项 */
                    $filename = app_path('Plugins/' . $row['extension_code'] . '/Languages/common_' . $GLOBALS['_CFG']['lang'] . '.php');
                    if (file_exists($filename)) {
                        include_once($filename);
                        if (!empty($GLOBALS['_LANG'][$row['extension_code'] . '_link'])) {
                            $row['goods_name'] = $row['goods_name'] . sprintf($GLOBALS['_LANG'][$row['extension_code'] . '_link'], $row['goods_id'], $order['order_sn']);
                        }
                    }
                }

                //ecmoban模板堂 --zhuo start
                if ($row['product_id'] > 0) {
                    $products = $this->goodsWarehouseService->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $row['model_attr']);
                    $row['storage'] = $products['product_number'] ?? 0;
                } else {
                    if ($row['model_inventory'] == 1) {
                        $row['storage'] = get_warehouse_area_goods($row['warehouse_id'], $row['goods_id'], 'warehouse_goods');
                    } elseif ($row['model_inventory'] == 2) {
                        $row['storage'] = get_warehouse_area_goods($row['area_id'], $row['goods_id'], 'warehouse_area_goods');
                    }
                }
                //ecmoban模板堂 --zhuo end

                $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                $row['formated_goods_price'] = price_format($row['goods_price']);

                $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

                //处理货品id
                $row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];

                //货号调用
                if (empty($row['product_sn'])) {
                    if ($row['model_attr'] == 1) {
                        $table = 'products_warehouse';
                    } elseif ($row['model_attr'] == 2) {
                        $table = 'products_area';
                    } else {
                        $table = 'products';
                    }

                    $sql = "SELECT product_sn FROM " . $this->dsc->table($table) . " WHERE product_id = '" . $row['product_id'] . "'";
                    $row['product_sn'] = $this->db->getOne($sql);
                }

                //图片显示
                $row['goods_thumb'] = get_image_path($row['goods_thumb']);
                $row['goods_img'] = get_image_path($row['goods_img']);

                $trade_id = $this->orderCommonService->getFindSnapshot($row['order_sn'], $row['goods_id']);
                if ($trade_id) {
                    $row['trade_url'] = $this->dscRepository->dscUrl("trade_snapshot.php?act=trade&tradeId=" . $trade_id . "&snapshot=true");
                }

                $row['back_order'] = return_order_info($row['ret_id']);

                //处理商品链接
                $row['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

                if ($row['extension_code'] == 'package_buy') {
                    $row['storage'] = '';
                    $row['brand_name'] = '';
                    $row['package_goods_list'] = $this->get_package_goods_list($row['goods_id']);

                    $activity = get_goods_activity_info($row['goods_id'], ['act_id', 'activity_thumb']);
                    if ($activity) {
                        $row['goods_thumb'] = get_image_path($activity['activity_thumb']);
                    }
                }

                $sql = "SELECT is_reality, is_return, is_fast FROM " . $this->dsc->table('goods_extend') . " WHERE goods_id = '" . $row['goods_id'] . "' LIMIT 1";
                $goods_extend = $this->db->getRow($sql);

                if (isset($row['is_reality'])) {
                    if ($row['is_reality'] == -1 && $goods_extend) {
                        $row['is_reality'] = $goods_extend['is_reality'];
                    }
                }
                if ($goods_extend['is_return'] == -1 && $goods_extend) {
                    $row['is_return'] = $goods_extend['is_return'];
                }
                if ($goods_extend['is_fast'] == -1 && $goods_extend) {
                    $row['is_fast'] = $goods_extend['is_fast'];
                }

                //获得退货表数据
                $sql = "SELECT ret_id FROM " . $this->dsc->table('order_return') . " WHERE rec_id = '" . $row['rec_id'] . "'";
                $row['ret_id'] = $this->db->getOne($sql);

                $row['back_order'] = return_order_info($row['ret_id']);

                $goods_list[] = $row;
            }
        }

        $attr = [];
        if ($goods_attr) {
            foreach ($goods_attr as $index => $array_val) {

                $array_val = $this->baseRepository->getExplode($array_val);

                if ($array_val) {
                    foreach ($array_val as $value) {
                        $arr = $value ? explode(':', $value) : [];//以 : 号将属性拆开

                        $arr[0] = $arr[0] ?? '';
                        $arr[1] = $arr[1] ?? '';
                        $attr[$index][] = @['name' => $arr[0], 'value' => $arr[1]];
                    }
                }
            }
        }

        return ['goods_list' => $goods_list, 'attr' => $attr];
    }

    /**
     * 取得礼包列表
     * @param integer $package_id 订单商品表礼包类商品id
     * @return array
     */
    private function get_package_goods_list($package_id)
    {
        $sql = "SELECT pg.goods_id, g.goods_name, (CASE WHEN pg.product_id > 0 THEN p.product_number ELSE g.goods_number END) AS goods_number, p.goods_attr, p.product_id, pg.goods_number AS
            order_goods_number, g.goods_sn, g.is_real, p.product_sn
            FROM " . $this->dsc->table('package_goods') . " AS pg
                LEFT JOIN " . $this->dsc->table('goods') . " AS g ON pg.goods_id = g.goods_id
                LEFT JOIN " . $this->dsc->table('products') . " AS p ON pg.product_id = p.product_id
            WHERE pg.package_id = '$package_id'";
        $resource = $this->db->query($sql);
        if (!$resource) {
            return [];
        }

        $row = [];

        /* 生成结果数组 取存在货品的商品id 组合商品id与货品id */
        $good_product_str = '';
        foreach ($resource as $_row) {
            if ($_row['product_id'] > 0) {
                /* 取存商品id */
                $good_product_str .= ',' . $_row['goods_id'];

                /* 组合商品id与货品id */
                $_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];
            } else {
                /* 组合商品id与货品id */
                $_row['g_p'] = $_row['goods_id'];
            }

            //生成结果数组
            $row[] = $_row;
        }
        $good_product_str = trim($good_product_str, ',');

        /* 释放空间 */
        unset($resource, $_row, $sql);

        /* 取商品属性 */
        if ($good_product_str != '') {
            $sql = "SELECT ga.goods_attr_id, ga.attr_value, ga.attr_price, a.attr_name
                FROM " . $this->dsc->table('goods_attr') . " AS ga, " . $this->dsc->table('attribute') . " AS a
                WHERE a.attr_id = ga.attr_id
                AND a.attr_type = 1
                AND goods_id IN ($good_product_str) ORDER BY a.sort_order, a.attr_id, ga.goods_attr_id";
            $result_goods_attr = $this->db->getAll($sql);

            $_goods_attr = [];
            foreach ($result_goods_attr as $value) {
                $_goods_attr[$value['goods_attr_id']] = $value;
            }
        }

        /* 过滤货品 */
        $format[0] = "%s:%s[%d] <br>";
        $format[1] = "%s--[%d]";
        foreach ($row as $key => $value) {
            if ($value['goods_attr'] != '') {
                $goods_attr_array = explode('|', $value['goods_attr']);

                $goods_attr = [];
                foreach ($goods_attr_array as $_attr) {
                    $goods_attr[] = sprintf($format[0], $_goods_attr[$_attr]['attr_name'], $_goods_attr[$_attr]['attr_value'], $_goods_attr[$_attr]['attr_price']);
                }

                $row[$key]['goods_attr_str'] = implode('', $goods_attr);
            }

            $row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['order_goods_number']);
        }

        return $row;
    }

    /**
     * 订单单个商品或货品的已发货数量
     *
     * @param int $order_id 订单 id
     * @param int $goods_id 商品 id
     * @param int $product_id 货品 id
     *
     * @return  int
     */
    private function order_delivery_num($order_id, $goods_id, $product_id = 0)
    {
        $sql = 'SELECT SUM(G.send_number) AS sums
            FROM ' . $this->dsc->table('delivery_goods') . ' AS G, ' . $this->dsc->table('delivery_order') . ' AS O
            WHERE O.delivery_id = G.delivery_id
            AND O.status = 0
            AND O.order_id = ' . $order_id . '
            AND G.extension_code <> "package_buy"
            AND G.goods_id = ' . $goods_id;

        $sql .= ($product_id > 0) ? " AND G.product_id = '$product_id'" : '';

        $sum = $this->db->getOne($sql);

        if (empty($sum)) {
            $sum = 0;
        }

        return $sum;
    }

    /**
     * 判断订单是否已发货（含部分发货）
     * @param int $order_id 订单 id
     * @return  int     1，已发货；0，未发货
     */
    private function order_deliveryed($order_id)
    {
        $return_res = 0;

        if (empty($order_id)) {
            return $return_res;
        }

        $sql = 'SELECT COUNT(delivery_id)
            FROM ' . $this->dsc->table('delivery_order') . '
            WHERE order_id = \'' . $order_id . '\'
            AND status = 0';
        $sum = $this->db->getOne($sql);

        if ($sum) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     * 更新订单商品信息
     * @param int $order_id 订单 id
     * @param array $_sended Array(‘商品id’ => ‘此单发货数量’)
     * @param array $goods_list
     * @return  Bool
     */
    private function update_order_goods($order_id, $_sended, $goods_list = [])
    {
        if (!is_array($_sended) || empty($order_id)) {
            return false;
        }

        foreach ($_sended as $key => $value) {
            // 超值礼包
            if (is_array($value)) {
                if (!is_array($goods_list)) {
                    $goods_list = [];
                }

                foreach ($goods_list as $goods) {
                    if (($key != $goods['rec_id']) || (!isset($goods['package_goods_list']) || !is_array($goods['package_goods_list']))) {
                        continue;
                    }

                    $goods['package_goods_list'] = $this->package_goods($goods['package_goods_list'], $goods['goods_number'], $goods['order_id'], $goods['extension_code'], $goods['goods_id']);
                    $pg_is_end = true;

                    foreach ($goods['package_goods_list'] as $pg_key => $pg_value) {
                        if ($pg_value['order_send_number'] != $pg_value['sended']) {
                            $pg_is_end = false; // 此超值礼包，此商品未全部发货

                            break;
                        }
                    }

                    // 超值礼包商品全部发货后更新订单商品库存
                    if ($pg_is_end) {
                        $sql = "UPDATE " . $this->dsc->table('order_goods') . "
                            SET send_number = goods_number
                            WHERE order_id = '$order_id'
                            AND goods_id = '" . $goods['goods_id'] . "' ";

                        $this->db->query($sql, 'SILENT');
                    }
                }
            } // 商品（实货）（货品）
            elseif (!is_array($value)) {
                /* 检查是否为商品（实货）（货品） */
                foreach ($goods_list as $goods) {
                    if ($goods['rec_id'] == $key && $goods['is_real'] == 1) {
                        $sql = "UPDATE " . $this->dsc->table('order_goods') . "
                            SET send_number = send_number + $value
                            WHERE order_id = '$order_id'
                            AND rec_id = '$key' ";
                        $this->db->query($sql, 'SILENT');
                        break;
                    }
                }
            }
        }

        return true;
    }

    /**
     * 更新订单虚拟商品信息
     * @param int $order_id 订单 id
     * @param array $_sended Array(‘商品id’ => ‘此单发货数量’)
     * @param array $virtual_goods 虚拟商品列表
     * @return  Bool
     */
    private function update_order_virtual_goods($order_id, $_sended, $virtual_goods)
    {
        if (!is_array($_sended) || empty($order_id)) {
            return false;
        }
        if (empty($virtual_goods)) {
            return true;
        } elseif (!is_array($virtual_goods)) {
            return false;
        }

        foreach ($virtual_goods as $goods) {
            $sql = "UPDATE " . $this->dsc->table('order_goods') . "
                SET send_number = send_number + '" . $goods['num'] . "'
                WHERE order_id = '" . $order_id . "'
                AND goods_id = '" . $goods['goods_id'] . "' ";
            if (!$this->db->query($sql, 'SILENT')) {
                return false;
            }
        }

        return true;
    }

    /**
     * 订单中的商品是否已经全部发货
     * @param int $order_id 订单 id
     * @return  int     1，全部发货；0，未全部发货
     */
    private function get_order_finish($order_id)
    {
        $return_res = 0;

        if (empty($order_id)) {
            return $return_res;
        }

        $sql = 'SELECT COUNT(rec_id)
            FROM ' . $this->dsc->table('order_goods') . '
            WHERE order_id = \'' . $order_id . '\'
            AND goods_number > send_number';

        $sum = $this->db->getOne($sql);
        if (empty($sum)) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     * 判断订单的发货单是否全部发货
     * @param int $order_id 订单 id
     * @return  int     1，全部发货；0，未全部发货；-1，部分发货；-2，完全没发货；
     */
    private function get_all_delivery_finish($order_id)
    {
        $return_res = 0;

        if (empty($order_id)) {
            return $return_res;
        }

        /* 未全部分单 */
        if (!$this->get_order_finish($order_id)) {
            return $return_res;
        } /* 已全部分单 */
        else {
            // 是否全部发货
            $sql = "SELECT COUNT(delivery_id)
                FROM " . $this->dsc->table('delivery_order') . "
                WHERE order_id = '$order_id'
                AND status = 2 ";
            $sum = $this->db->getOne($sql);
            // 全部发货
            if (empty($sum)) {
                $return_res = 1;
            } // 未全部发货
            else {
                /* 订单全部发货中时：当前发货单总数 */
                $sql = "SELECT COUNT(delivery_id)
            FROM " . $this->dsc->table('delivery_order') . "
            WHERE order_id = '$order_id'
            AND status <> 1 ";
                $_sum = $this->db->getOne($sql);
                if ($_sum == $sum) {
                    $return_res = -2; // 完全没发货
                } else {
                    $return_res = -1; // 部分发货
                }
            }
        }

        return $return_res;
    }

    private function trim_array_walk(&$array_value)
    {
        if (is_array($array_value)) {
            array_walk($array_value, [$this, "trim_array_walk"]);
        } else {
            $array_value = trim($array_value);
        }
    }

    private function intval_array_walk(&$array_value)
    {
        if (is_array($array_value)) {
            array_walk($array_value, [$this, "intval_array_walk"]);
        } else {
            $array_value = intval($array_value);
        }
    }

    /**
     * 删除发货单(不包括已退货的单子)
     * @param int $order_id 订单 id
     * @return  int     1，成功；0，失败
     */
    private function del_order_delivery($order_id)
    {
        $return_res = 0;

        if (empty($order_id)) {
            return $return_res;
        }

        $sql = 'DELETE O, G
            FROM ' . $this->dsc->table('delivery_order') . ' AS O, ' . $this->dsc->table('delivery_goods') . ' AS G
            WHERE O.order_id = \'' . $order_id . '\'
            AND O.status = 0
            AND O.delivery_id = G.delivery_id';
        $query = $this->db->query($sql, 'SILENT');

        if ($query) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     * 删除订单所有相关单子
     * @param int $order_id 订单 id
     * @param int $action_array 操作列表 Array('delivery', 'back', ......)
     * @return  int     1，成功；0，失败
     */
    private function del_delivery($order_id, $action_array)
    {
        $return_res = 0;

        if (empty($order_id) || empty($action_array)) {
            return $return_res;
        }

        $query_delivery = 1;
        $query_back = 1;
        if (in_array('delivery', $action_array)) {
            $sql = 'DELETE O, G
                FROM ' . $this->dsc->table('delivery_order') . ' AS O, ' . $this->dsc->table('delivery_goods') . ' AS G
                WHERE O.order_id = \'' . $order_id . '\'
                AND O.delivery_id = G.delivery_id';
            $query_delivery = $this->db->query($sql, 'SILENT');
        }
        if (in_array('back', $action_array)) {
            $sql = 'DELETE O, G
                FROM ' . $this->dsc->table('back_order') . ' AS O, ' . $this->dsc->table('back_goods') . ' AS G
                WHERE O.order_id = \'' . $order_id . '\'
                AND O.back_id = G.back_id';
            $query_back = $this->db->query($sql, 'SILENT');
        }

        if ($query_delivery && $query_back) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     *  获取发货单列表信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function delivery_list()
    {
        $adminru = get_admin_ru_id();
        $where = " WHERE o.ru_id = '" . $adminru['ru_id'] . "' ";

        $result = get_filter();
        if ($result === false) {
            $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

            /* 过滤信息 */

            $filter['delivery_sn'] = empty($_REQUEST['delivery_sn']) ? '' : trim($_REQUEST['delivery_sn']);
            $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $filter['order_id'] = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $filter['goods_id'] = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            if ($aiax == 1 && !empty($_REQUEST['consignee'])) {
                $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
            }
            $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
            $filter['status'] = isset($_REQUEST['status']) ? $_REQUEST['status'] : -1;

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'do.update_time' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            if ($filter['order_sn']) {
                $where .= " AND do.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
            }
            if ($filter['goods_id']) {
                $where .= " AND (SELECT dg.goods_id FROM " . $this->dsc->table('delivery_goods') . " AS dg WHERE dg.delivery_id = do.delivery_id LIMIT 1) = '" . $filter['goods_id'] . "' ";
            }
            if ($filter['consignee']) {
                $where .= " AND do.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
            }

            if ($filter['delivery_sn']) {
                $where .= " AND do.delivery_sn LIKE '%" . mysql_like_quote($filter['delivery_sn']) . "%'";
            }

            /* 获取管理员信息 */
            $admin_info = admin_info();

            /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
            if ($admin_info['agency_id'] > 0) {
                $where .= " AND do.agency_id = '" . $admin_info['agency_id'] . "' ";
            }

            /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
            if ($admin_info['suppliers_id'] > 0) {
                $where .= " AND do.suppliers_id = '" . $admin_info['suppliers_id'] . "' ";
            }

            /* 分页大小 */
            $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

            $page_size = request()->cookie('dsccp_page_size');
            if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
                $filter['page_size'] = intval($_REQUEST['page_size']);
            } elseif (intval($page_size) > 0) {
                $filter['page_size'] = intval($page_size);
            } else {
                $filter['page_size'] = 15;
            }

            if ($filter['status'] > -1) {
                $where .= " AND do.status = '$filter[status]' ";
            }

            $leftJoin = " LEFT JOIN " . $this->dsc->table('order_info') . " AS o ON o.order_id = do.order_id ";

            /* 记录总数 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('delivery_order') . " as do " . $leftJoin . $where;
            $filter['record_count'] = $this->db->getOne($sql);
            $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

            /* 查询 */
            $sql = "SELECT do.delivery_id, do.delivery_sn, do.order_sn, do.order_id, do.add_time, do.action_user, do.consignee, do.country,
                       do.province, do.city, do.district, do.tel, do.status, do.update_time, do.email, do.suppliers_id
                FROM " . $this->dsc->table("delivery_order") . " as do " .
                $leftJoin . $where .
                " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] . "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];

            $filter = $result['filter'];
        }

        /* 获取供货商列表 */
        $suppliers_list = $this->get_suppliers_list();
        $_suppliers_list = [];
        foreach ($suppliers_list as $value) {
            $_suppliers_list[$value['suppliers_id']] = $value['suppliers_name'];
        }

        $row = $this->db->getAll($sql);

        /* 格式化数据 */
        foreach ($row as $key => $value) {
            $row[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
            $row[$key]['update_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['update_time']);
            if ($value['status'] == DELIVERY_REFOUND) {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][1];
            } elseif ($value['status'] == DELIVERY_CREATE) {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][2];
            } else {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][0];
            }
            $row[$key]['suppliers_name'] = isset($_suppliers_list[$value['suppliers_id']]) ? $_suppliers_list[$value['suppliers_id']] : '';

            $sql = "SELECT ru_id FROM " . $this->dsc->table('order_goods') . " WHERE order_id = '" . $value['order_id'] . "' LIMIT 0,1";
            $ru_id = $this->db->getOne($sql);
            $row[$key]['ru_name'] = $this->merchantCommonService->getShopName($ru_id, 1); //ecmoban模板堂 --zhuo
        }
        $arr = ['delivery' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     *  获取退货单列表信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function back_list()
    {
        $adminru = get_admin_ru_id();
        $where = "WHERE o.ru_id = '" . $adminru['ru_id'] . "' ";

        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['delivery_sn'] = empty($_REQUEST['delivery_sn']) ? '' : trim($_REQUEST['delivery_sn']);
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['order_id'] = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
        if ($aiax == 1 && !empty($_REQUEST['consignee'])) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
        }
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'bo.update_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        if ($filter['order_sn']) {
            $where .= " AND bo.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
        }
        if ($filter['consignee']) {
            $where .= " AND bo.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
        }
        if ($filter['delivery_sn']) {
            $where .= " AND bo.delivery_sn LIKE '%" . mysql_like_quote($filter['delivery_sn']) . "%'";
        }

        /* 获取管理员信息 */
        $admin_info = admin_info();

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0) {
            $where .= " AND bo.agency_id = '" . $admin_info['agency_id'] . "' ";
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0) {
            $where .= " AND bo.suppliers_id = '" . $admin_info['suppliers_id'] . "' ";
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        $leftJoin = " LEFT JOIN " . $this->dsc->table('order_info') . " AS o ON o.order_id = bo.order_id ";

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('back_order') . " as bo " . $leftJoin . $where;
        $filter['record_count'] = $this->db->getOne($sql);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT bo.back_id, bo.delivery_sn, bo.order_sn, bo.order_id, bo.add_time, bo.action_user, bo.consignee, bo.country,
                       bo.province, bo.city, bo.district, bo.tel, bo.status, bo.update_time, bo.email, bo.return_time
                FROM " . $this->dsc->table("back_order") . " as bo " . $leftJoin .
            $where .
            " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] . "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        $row = $this->db->getAll($sql);

        /* 格式化数据 */
        foreach ($row as $key => $value) {
            $row[$key]['return_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['return_time']);
            $row[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
            $row[$key]['update_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['update_time']);
            if ($value['status'] == 1) {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][1];
            } else {
                $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][0];
            }
        }
        $arr = ['back' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 取得发货单信息
     *
     * @param int $delivery_id 发货单id
     * @param string $delivery_sn 发货单号
     * @return array
     */
    private function delivery_order_info($delivery_id = 0, $delivery_sn = '')
    {
        $return_order = [];
        if (empty($delivery_id) || !is_numeric($delivery_id)) {
            return $return_order;
        }

        /* 获取管理员信息 */
        $admin_info = admin_info();

        $delivery = DeliveryOrder::whereRaw(1);

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0) {
            $delivery = $delivery->where('agency_id', $admin_info['agency_id']);
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0) {
            $delivery = $delivery->where('suppliers_id', $admin_info['suppliers_id']);
        }

        if ($delivery_id > 0) {
            $delivery = $delivery->where('delivery_id', $delivery_id);
        } else {
            $delivery = $delivery->where('delivery_sn', $delivery_sn);
        }

        $delivery = $delivery->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name');
            }
        ]);

        $delivery = $this->baseRepository->getToArrayFirst($delivery);

        if ($delivery) {
            /* 取得区域名 */
            $province = $delivery['get_region_province']['region_name'] ?? '';
            $city = $delivery['get_region_city']['region_name'] ?? '';
            $district = $delivery['get_region_district']['region_name'] ?? '';
            $street = $delivery['get_region_street']['region_name'] ?? '';
            $delivery['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

            /* 格式化金额字段 */
            $delivery['formated_insure_fee'] = price_format($delivery['insure_fee'], false);
            $delivery['formated_shipping_fee'] = price_format($delivery['shipping_fee'], false);

            /* 格式化时间字段 */
            $delivery['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $delivery['add_time']);
            $delivery['formated_update_time'] = local_date($GLOBALS['_CFG']['time_format'], $delivery['update_time']);

            $return_order = $delivery;
        }

        return $return_order;
    }

    /**
     * 取得退货单信息
     * @param int $back_id 退货单 id（如果 back_id > 0 就按 id 查，否则按 sn 查）
     * @return  array   退货单信息（金额都有相应格式化的字段，前缀是 formated_ ）
     */
    private function back_order_info($back_id)
    {
        $return_order = [];
        if (empty($back_id) || !is_numeric($back_id)) {
            return $return_order;
        }

        /* 获取管理员信息 */
        $admin_info = admin_info();

        $back = BackOrder::where('back_id', $back_id);

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的发货单 */
        if ($admin_info['agency_id'] > 0) {
            $back = $back->where('agency_id', $admin_info['agency_id']);
        }

        /* 如果管理员属于某个供货商，只列出这个供货商的发货单 */
        if ($admin_info['suppliers_id'] > 0) {
            $back = $back->where('suppliers_id', $admin_info['suppliers_id']);
        }

        $back = $back->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name');
            }
        ]);

        $back = $this->baseRepository->getToArrayFirst($back);

        if ($back) {

            /* 取得区域名 */
            $province = $back['get_region_province']['region_name'] ?? '';
            $city = $back['get_region_city']['region_name'] ?? '';
            $district = $back['get_region_district']['region_name'] ?? '';
            $street = $back['get_region_street']['region_name'] ?? '';
            $back['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

            /* 格式化金额字段 */
            $back['formated_insure_fee'] = price_format($back['insure_fee'], false);
            $back['formated_shipping_fee'] = price_format($back['shipping_fee'], false);

            $order = OrderInfo::where('order_id', $back['order_id']);
            $order = $this->baseRepository->getToArrayFirst($order);

            $back['is_zc_order'] = $order['is_zc_order'] ?? 0;

            /* 格式化时间字段 */
            $back['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $back['add_time']);
            $back['formated_update_time'] = local_date($GLOBALS['_CFG']['time_format'], $back['update_time']);
            $back['formated_return_time'] = local_date($GLOBALS['_CFG']['time_format'], $back['return_time']);

            $return_order = $back;
        }

        return $return_order;
    }

    /**
     * 超级礼包发货数处理
     * @param array   超级礼包商品列表
     * @param int     发货数量
     * @param int     订单ID
     * @param varchar 虚拟代码
     * @param int     礼包ID
     * @return  array   格式化结果
     */
    private function package_goods(&$package_goods, $goods_number, $order_id, $extension_code, $package_id)
    {
        $return_array = [];

        if (count($package_goods) == 0 || !is_numeric($goods_number)) {
            return $return_array;
        }

        foreach ($package_goods as $key => $value) {
            $return_array[$key] = $value;
            $return_array[$key]['order_send_number'] = $value['order_goods_number'] * $goods_number;
            $return_array[$key]['sended'] = $this->package_sended($package_id, $value['goods_id'], $order_id, $extension_code, $value['product_id']);
            $return_array[$key]['send'] = ($value['order_goods_number'] * $goods_number) - $return_array[$key]['sended'];
            $return_array[$key]['storage'] = $value['goods_number'];


            if ($return_array[$key]['send'] <= 0) {
                $return_array[$key]['send'] = lang('seller/order.act_good_delivery');
                $return_array[$key]['readonly'] = 'readonly="readonly"';
            }

            /* 是否缺货 */
            if ($return_array[$key]['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1') {
                $return_array[$key]['send'] = lang('seller/order.act_good_vacancy');
                $return_array[$key]['readonly'] = 'readonly="readonly"';
            }
        }

        return $return_array;
    }

    /**
     * 获取超级礼包商品已发货数
     *
     * @param int $package_id 礼包ID
     * @param int $goods_id 礼包的产品ID
     * @param int $order_id 订单ID
     * @param varchar $extension_code 虚拟代码
     * @param int $product_id 货品id
     *
     * @return  int     数值
     */
    private function package_sended($package_id, $goods_id, $order_id, $extension_code, $product_id = 0)
    {
        if (empty($package_id) || empty($goods_id) || empty($order_id) || empty($extension_code)) {
            return false;
        }

        $sql = "SELECT SUM(DG.send_number)
            FROM " . $this->dsc->table('delivery_goods') . " AS DG, " . $this->dsc->table('delivery_order') . " AS o
            WHERE o.delivery_id = DG.delivery_id
            AND o.status IN (0, 2)
            AND o.order_id = '$order_id'
            AND DG.parent_id = '$package_id'
            AND DG.goods_id = '$goods_id'
            AND DG.extension_code = '$extension_code'";
        $sql .= ($product_id > 0) ? " AND DG.product_id = '$product_id'" : '';

        $send = $this->db->getOne($sql);

        return empty($send) ? 0 : $send;
    }

    /**
     * 改变订单中商品库存
     * @param int $order_id 订单 id
     * @param array $_sended Array(‘商品id’ => ‘此单发货数量’)
     * @param array $goods_list
     * @return  Bool
     */
    private function change_order_goods_storage_split($order_id, $_sended, $goods_list = [])
    {
        /* 参数检查 */
        if (!is_array($_sended) || empty($order_id)) {
            return false;
        }

        foreach ($_sended as $key => $value) {
            // 商品（超值礼包）
            if (is_array($value)) {
                if (!is_array($goods_list)) {
                    $goods_list = [];
                }
                foreach ($goods_list as $goods) {
                    if (($key != $goods['rec_id']) || (!isset($goods['package_goods_list']) || !is_array($goods['package_goods_list']))) {
                        continue;
                    }

                    // 超值礼包无库存，只减超值礼包商品库存
                    foreach ($goods['package_goods_list'] as $package_goods) {
                        if (!isset($value[$package_goods['goods_id']])) {
                            continue;
                        }

                        // 减库存：商品（超值礼包）（实货）、商品（超值礼包）（虚货）
                        $sql = "UPDATE " . $this->dsc->table('goods') . "
                            SET goods_number = goods_number - '" . $value[$package_goods['goods_id']] . "'
                            WHERE goods_id = '" . $package_goods['goods_id'] . "' ";
                        $this->db->query($sql);
                    }
                }
            } // 商品（实货）
            elseif (!is_array($value)) {
                /* 检查是否为商品（实货） */
                foreach ($goods_list as $goods) {
                    if ($goods['rec_id'] == $key && $goods['is_real'] == 1) {
                        $sql = "UPDATE " . $this->dsc->table('goods') . "
                            SET goods_number = goods_number - '" . $value . "'
                            WHERE goods_id = '" . $goods['goods_id'] . "' ";
                        $this->db->query($sql, 'SILENT');
                        break;
                    }
                }
            }
        }

        return true;
    }

    /**
     *  超值礼包虚拟卡发货、跳过修改订单商品发货数的虚拟卡发货
     *
     * @access  public
     * @param array $goods 超值礼包虚拟商品列表数组
     * @param string $order_sn 本次操作的订单
     *
     * @return  boolen
     */
    private function package_virtual_card_shipping($goods, $order_sn)
    {
        if (!is_array($goods)) {
            return false;
        }

        /* 包含加密解密函数所在文件 */
        load_helper('code');

        // 取出超值礼包中的虚拟商品信息
        foreach ($goods as $virtual_goods_key => $virtual_goods_value) {
            /* 取出卡片信息 */
            $sql = "SELECT card_id, card_sn, card_password, end_date, crc32
                FROM " . $this->dsc->table('virtual_card') . "
                WHERE goods_id = '" . $virtual_goods_value['goods_id'] . "'
                AND is_saled = 0
                LIMIT " . $virtual_goods_value['num'];
            $arr = $this->db->getAll($sql);
            /* 判断是否有库存 没有则推出循环 */
            if (count($arr) == 0) {
                continue;
            }

            $card_ids = [];
            $cards = [];

            foreach ($arr as $virtual_card) {
                $card_info = [];

                /* 卡号和密码解密 */
                if ($virtual_card['crc32'] == 0 || $virtual_card['crc32'] == crc32(AUTH_KEY)) {
                    $card_info['card_sn'] = decrypt($virtual_card['card_sn']);
                    $card_info['card_password'] = decrypt($virtual_card['card_password']);
                } elseif ($virtual_card['crc32'] == crc32(OLD_AUTH_KEY)) {
                    $card_info['card_sn'] = decrypt($virtual_card['card_sn'], OLD_AUTH_KEY);
                    $card_info['card_password'] = decrypt($virtual_card['card_password'], OLD_AUTH_KEY);
                } else {
                    return false;
                }
                $card_info['end_date'] = local_date($GLOBALS['_CFG']['date_format'], $virtual_card['end_date']);
                $card_ids[] = $virtual_card['card_id'];
                $cards[] = $card_info;
            }

            /* 标记已经取出的卡片 */
            $sql = "UPDATE " . $this->dsc->table('virtual_card') . " SET " .
                "is_saled = 1 ," .
                "order_sn = '$order_sn' " .
                "WHERE " . db_create_in($card_ids, 'card_id');
            if (!$this->db->query($sql)) {
                return false;
            }

            /* 获取订单信息 */
            $sql = "SELECT order_id, order_sn, consignee, email FROM " . $this->dsc->table('order_info') . " WHERE order_sn = '$order_sn'";
            $order = $this->db->GetRow($sql);

            $cfg = $GLOBALS['_CFG']['send_ship_email'];
            if ($cfg == '1') {
                /* 发送邮件 */
                $GLOBALS['smarty']->assign('virtual_card', $cards);
                $GLOBALS['smarty']->assign('order', $order);
                $GLOBALS['smarty']->assign('goods', $virtual_goods_value);

                $GLOBALS['smarty']->assign('send_time', date('Y-m-d H:i:s'));
                $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));
                $GLOBALS['smarty']->assign('sent_date', date('Y-m-d'));

                $tpl = get_mail_template('virtual_card');
                $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
                $this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
            }
        }

        return true;
    }

    /**
     * 删除发货单时进行退货
     *
     * @access   public
     * @param int $delivery_id 发货单id
     * @param array $delivery_order 发货单信息数组
     *
     * @return  void
     */
    private function delivery_return_goods($delivery_id, $delivery_order)
    {
        /* 查询：取得发货单商品 */
        $goods_sql = "SELECT *
                 FROM " . $this->dsc->table('delivery_goods') . "
                 WHERE delivery_id = " . $delivery_order['delivery_id'];
        $goods_list = $this->db->getAll($goods_sql);
        /* 更新： */
        foreach ($goods_list as $key => $val) {
            $sql = "UPDATE " . $this->dsc->table('order_goods') .
                " SET send_number = send_number-'" . $goods_list[$key]['send_number'] . "'" .
                " WHERE order_id = '" . $delivery_order['order_id'] . "' AND goods_id = '" . $goods_list[$key]['goods_id'] . "' LIMIT 1";
            $this->db->query($sql);
        }
        $sql = "UPDATE " . $this->dsc->table('order_info') .
            " SET shipping_status = '0' , order_status = 1" .
            " WHERE order_id = '" . $delivery_order['order_id'] . "' LIMIT 1";
        $this->db->query($sql);
    }

    /**
     * 删除发货单时删除其在订单中的发货单号
     *
     * @access   public
     * @param int $order_id 定单id
     * @param string $delivery_invoice_no 发货单号
     *
     * @return  void
     */
    private function del_order_invoice_no($order_id, $delivery_invoice_no)
    {
        /* 查询：取得订单中的发货单号 */
        $sql = "SELECT invoice_no
            FROM " . $this->dsc->table('order_info') . "
            WHERE order_id = '$order_id'";
        $order_invoice_no = $this->db->getOne($sql);

        /* 如果为空就结束处理 */
        if (empty($order_invoice_no)) {
            return;
        }

        /* 去除当前发货单号 */
        $order_array = explode(',', $order_invoice_no);
        $delivery_array = explode(',', $delivery_invoice_no);

        foreach ($order_array as $key => $invoice_no) {
            if ($ii = array_search($invoice_no, $delivery_array)) {
                unset($order_array[$key], $delivery_array[$ii]);
            }
        }

        $arr['invoice_no'] = implode(',', $order_array);
        update_order($order_id, $arr);
    }


    //ecmoban模板堂 --zhuo start
    private function download_orderlist($result)
    {
        if (empty($result)) {
            return $this->i(lang('admin/common.not_fuhe_date'));
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $data = $this->i(lang('admin/common.cbec_download_orderlist') . "\n");
        } else {
            $data = $this->i(lang('admin/common.download_orderlist_notic') . "\n");
        }

        $lang_goods = lang('admin/common.download_goods');

        $count = count($result);
        for ($i = 0; $i < $count; $i++) {
            // 订单商品信息
            $goods_info = '';
            $goods_sn = '';
            if (!empty($result[$i]['goods_list'])) {
                foreach ($result[$i]['goods_list'] as $j => $g) {
                    if (!empty($g['goods_attr'])) {
                        $goods_info .= $g['goods_name'] . "(" . $lang_goods['export_prize'] . $g['goods_price'] . " " . $lang_goods['export_num'] . $g['goods_number'] . $g['goods_unit'] . ' ' . rtrim($g['goods_attr']) . ")" . "\r\n";
                    } else {
                        $goods_info .= $g['goods_name'] . "(" . $lang_goods['export_prize'] . $g['goods_price'] . " " . $lang_goods['export_num'] . $g['goods_number'] . $g['goods_unit'] . ")" . "\r\n";
                    }
                    $goods_sn .= $g['goods_sn'] . "\r\n";
                }
                $goods_info = rtrim($goods_info); // 去除最末位换行符
                $goods_info = "\"$goods_info\""; // 转义字符是关键 不然不是表格内换行
                $goods_sn = rtrim($goods_sn); // 去除最末位换行符
                $goods_sn = "\"$goods_sn\""; // 转义字符是关键 不然不是表格内换行
            }

            $order_sn = $this->i('#' . $result[$i]['order_sn']); //订单号前加'#',避免被四舍五入 by wu
            $order_user = $this->i($result[$i]['buyer']);
            $order_time = $this->i($result[$i]['short_order_time']);
            $consignee = $this->i($result[$i]['consignee']);
            $tel = !empty($result[$i]['mobile']) ? $this->i($result[$i]['mobile']) : $this->i($result[$i]['tel']);
            $address = $this->i(addslashes(str_replace(",", "，", "[" . $result[$i]['region'] . "] " . $result[$i]['address'])));
            $goods_info = $this->i($goods_info); // 商品信息
            $goods_sn = $this->i($goods_sn); // 商品货号
            $order_amount = $this->i($result[$i]['order_amount']);
            $goods_amount = $this->i($result[$i]['goods_amount']);
            $shipping_fee = $this->i($result[$i]['old_shipping_fee']);//配送费用
            $insure_fee = $this->i($result[$i]['insure_fee']);//保价费用
            $pay_fee = $this->i($result[$i]['pay_fee']);//支付费用
            if (CROSS_BORDER === true) // 跨境多商户
            {
                $rate_fee = $this->i($result[$i]['rate_fee']);//综合税费
                $rel_name = $this->i($result[$i]['rel_name']);//真实姓名
                $id_num = $this->i('#' . $result[$i]['id_num']);//身份证号
            }
            $surplus = $this->i($result[$i]['surplus']);//余额费用
            $money_paid = $this->i($result[$i]['money_paid']);//已付款金额
            $integral_money = $this->i($result[$i]['integral_money']);//积分金额
            $bonus = $this->i($result[$i]['bonus']);//红包金额
            $tax = $this->i($result[$i]['tax']);//发票税额
            $discount = $this->i($result[$i]['discount']);//折扣金额
            $coupons = $this->i($result[$i]['coupons']);//优惠券金额
            $value_card = $this->i($result[$i]['value_card']); // 储值卡
            $order_status = $this->i(preg_replace("/\<.+?\>/", "", $GLOBALS['_LANG']['os'][$result[$i]['order_status']])); //去除标签
            $seller_name = $this->i($result[$i]['user_name']); //商家名称
            $pay_status = $this->i($GLOBALS['_LANG']['ps'][$result[$i]['pay_status']]);
            $shipping_status = $this->i($GLOBALS['_LANG']['ss'][$result[$i]['shipping_status']]);
            $froms = $this->i($result[$i]['referer']);

            if ($froms == 'touch') {
                $froms = "WAP";
            } elseif ($froms == 'mobile') {
                $froms = "APP";
            } elseif ($froms == 'H5') {
                $froms = "H5";
            } elseif ($froms == 'wxapp') {
                $froms = $GLOBALS['_LANG']['wxapp'];
            } elseif ($froms == 'ecjia-cashdesk') {
                $froms = $GLOBALS['_LANG']['cashdesk'];
            } else {
                $froms = "PC";
            }

            $pay_name = $this->i($result[$i]['pay_name']);
            $total_fee = $this->i($result[$i]['total_fee']); // 总金额
            $total_fee_order = $this->i($result[$i]['total_fee_order']); // 订单总金额

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $data .= $order_sn . ',' . $seller_name . ',' . $order_user . ',' . $rel_name . ',' . $id_num . ',' .
                    $order_time . ',' . $consignee . ',' . $tel . ',' .
                    $address . ',' . $goods_info . ',' . $goods_sn . ',' . $goods_amount . ',' . $tax . ',' .
                    $shipping_fee . ',' . $insure_fee . ',' .
                    $pay_fee . ',' . $rate_fee . ',' . $total_fee . ',' . $discount . ',' . $total_fee_order . ',' .
                    $surplus . ',' . $integral_money . ',' . $bonus . ',' .
                    $coupons . ',' . $value_card . ',' . $money_paid . ',' . $order_amount . ',' .
                    $order_status . ',' . $pay_status . ',' . $shipping_status . ',' . $froms . ',' . $pay_name . "\n";
            } else {
                $data .= $order_sn . ',' . $seller_name . ',' . $order_user . ',' .
                    $order_time . ',' . $consignee . ',' . $tel . ',' .
                    $address . ',' . $goods_info . ',' . $goods_sn . ',' . $goods_amount . ',' . $tax . ',' .
                    $shipping_fee . ',' . $insure_fee . ',' .
                    $pay_fee . ',' . $total_fee . ',' . $discount . ',' . $total_fee_order . ',' .
                    $surplus . ',' . $integral_money . ',' . $bonus . ',' .
                    $coupons . ',' . $value_card . ',' . $money_paid . ',' . $order_amount . ',' .
                    $order_status . ',' . $pay_status . ',' . $shipping_status . ',' . $froms . ',' . $pay_name . "\n";
            }
        }
        return $data;
    }

    private function i($strInput)
    {
        if ($strInput && !is_array($strInput)) {
            return iconv('utf-8', 'gb2312//TRANSLIT//IGNORE', $strInput);//页面编码为utf-8时使用，否则导出的中文为乱码
        } else {
            if ($strInput) {
                if (is_array($strInput)) {
                    return '';
                }
            } else {
                return '';
            }
        }
    }
}
