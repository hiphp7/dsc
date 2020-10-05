<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\BaitiaoLog;
use App\Models\Brand;
use App\Models\Complaint;
use App\Models\ComplaintImg;
use App\Models\Feedback;
use App\Models\Goods;
use App\Models\OfflineStore;
use App\Models\OrderAction;
use App\Models\OrderCloud;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\OrderReturnExtend;
use App\Models\PayLog;
use App\Models\Payment;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\ReturnCause;
use App\Models\ReturnGoods;
use App\Models\ReturnImages;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\ShippingPoint;
use App\Models\TradeSnapshot;
use App\Models\UserOrderNum;
use App\Models\Users;
use App\Models\ValueCardRecord;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\GroupBuyService;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartGoodsService;
use App\Services\Cart\CartService;
use App\Services\Comment\CommentService;
use App\Services\Commission\CommissionService;
use App\Services\Common\AreaService;
use App\Services\Common\CommonService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Erp\JigonManageService;
use App\Services\Flow\FlowUserService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderRefoundService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;
use App\Services\User\UserAddressService;
use App\Services\User\UserBaitiaoService;
use App\Services\User\UserCommonService;
use App\Services\User\UserOrderService;
use App\Services\User\UserService;

class UserOrderController extends InitController
{
    protected $dscRepository;
    protected $userCommonService;
    protected $articleCommonService;
    protected $config;
    protected $commonRepository;
    protected $commonService;
    protected $userOrderService;
    protected $paymentService;
    protected $orderService;
    protected $orderRefoundService;
    protected $commissionService;
    protected $userService;
    protected $merchantCommonService;
    protected $goodsGalleryService;
    protected $commentService;
    protected $goodsAttrService;
    protected $goodsService;
    protected $goodsCommonService;
    protected $areaService;
    protected $userAddressService;
    protected $baseRepository;
    protected $jigonManageService;
    protected $flowUserService;
    protected $userBaitiaoService;
    protected $cartGoodsService;
    protected $cartService;

    public function __construct(
        DscRepository $dscRepository,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        CommonRepository $commonRepository,
        CommonService $commonService,
        UserOrderService $userOrderService,
        PaymentService $paymentService,
        OrderService $orderService,
        OrderRefoundService $orderRefoundService,
        CommissionService $commissionService,
        UserService $userService,
        MerchantCommonService $merchantCommonService,
        GoodsGalleryService $goodsGalleryService,
        CommentService $commentService,
        GoodsAttrService $goodsAttrService,
        GoodsService $goodsService,
        GoodsCommonService $goodsCommonService,
        AreaService $areaService,
        UserAddressService $userAddressService,
        BaseRepository $baseRepository,
        JigonManageService $jigonManageService,
        FlowUserService $flowUserService,
        UserBaitiaoService $userBaitiaoService,
        CartGoodsService $cartGoodsService,
        CartService $cartService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->userCommonService = $userCommonService;
        $this->articleCommonService = $articleCommonService;
        $this->config = $dscRepository->dscConfig();
        $this->commonRepository = $commonRepository;
        $this->commonService = $commonService;
        $this->userOrderService = $userOrderService;
        $this->paymentService = $paymentService;
        $this->orderService = $orderService;
        $this->orderRefoundService = $orderRefoundService;
        $this->commissionService = $commissionService;
        $this->userService = $userService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->commentService = $commentService;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsService = $goodsService;
        $this->goodsCommonService = $goodsCommonService;
        $this->areaService = $areaService;
        $this->userAddressService = $userAddressService;
        $this->baseRepository = $baseRepository;
        $this->jigonManageService = $jigonManageService;
        $this->flowUserService = $flowUserService;
        $this->userBaitiaoService = $userBaitiaoService;
        $this->cartGoodsService = $cartGoodsService;
        $this->cartService = $cartService;
        $this->groupBuyService = app(GroupBuyService::class);
    }

    public function index()
    {
        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        $this->dscRepository->helpersLang(['user']);

        $user_id = session('user_id', 0);
        $action = addslashes(trim(request()->input('act', 'default')));
        $action = $action ? $action : 'default';

        $not_login_arr = $this->userCommonService->notLoginArr();

        $ui_arr = $this->userCommonService->uiArr('order');

        /* 未登录处理 */
        $requireUser = $this->userCommonService->requireLogin(session('user_id'), $action, $not_login_arr, $ui_arr);
        $action = $requireUser['action'];
        $require_login = $requireUser['require_login'];

        if ($require_login == 1) {
            //未登录提交数据。非正常途径提交数据！
            return dsc_header('location:' . $this->dscRepository->dscUrl('user.php'));
        }

        /* 区分登录注册底部样式 */
        $footer = $this->userCommonService->userFooter();
        if (in_array($action, $footer)) {
            $this->smarty->assign('footer', 1);
        }

        $is_apply = $this->userCommonService->merchantsIsApply($user_id);
        $this->smarty->assign('is_apply', $is_apply);

        $user_default_info = $this->userCommonService->getUserDefault($user_id);
        $this->smarty->assign('user_default_info', $user_default_info);

        /* 如果是显示页面，对页面进行相应赋值 */
        if (in_array($action, $ui_arr)) {
            assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['user_core']);
            $this->smarty->assign('page_title', $position['title']); // 页面标题
            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版
            $this->smarty->assign('ur_here', $position['ur_here']);

            $this->smarty->assign('car_off', $this->config['anonymous_buy']);

            /* 是否显示积分兑换 */
            if (!empty($this->config['points_rule']) && unserialize($this->config['points_rule'])) {
                $this->smarty->assign('show_transform_points', 1);
            }

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助
            $this->smarty->assign('data_dir', DATA_DIR);   // 数据目录
            $this->smarty->assign('action', $action);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $info = $user_default_info;

            if ($user_id) {
                //验证邮箱
                if (isset($info['is_validated']) && !$info['is_validated'] && $this->config['user_login_register'] == 1) {
                    $Location = url('/') . '/' . 'user.php?act=user_email_verify';
                    return dsc_header('location:' . $Location);
                }
            }

            $count = AdminUser::where('ru_id', session('user_id'))->count();
            if ($count) {
                $is_merchants = 1;
            } else {
                $is_merchants = 0;
            }

            $this->smarty->assign('is_merchants', $is_merchants);
            $this->smarty->assign('shop_reg_closed', $this->config['shop_reg_closed']);

            $this->smarty->assign('filename', 'user');
        } else {
            if (!in_array($action, $not_login_arr) || $user_id == 0) {
                $referer = '?back_act=' . urlencode(request()->server('REQUEST_URI'));
                $back_act = $this->dscRepository->dscUrl('user.php' . $referer);
                return dsc_header('location:' . $back_act);
            }
        }

        $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();
        $wholesaleUse = $this->commonService->judgeWholesaleUse(session('user_id'), 1);
        $wholesale_use = $supplierEnabled && $wholesaleUse ? 1 : 0;

        $this->smarty->assign('wholesale_use', $wholesale_use);


        /* ------------------------------------------------------ */
        //-- 查看订单列表
        /* ------------------------------------------------------ */
        if ($action == 'order_list' || $action == 'order_recycle') {
            load_helper(['payment', 'order']);

            $show_type = 0;
            if ($action == 'order_list') {
                $show_type = 0;
            } elseif ($action == 'order_recycle') {
                $show_type = 1;
            }

            load_helper('clips');

            //订单数量 v1.4.4

            $orderNum = UserOrderNum::where('user_id', $user_id)->first();
            $orderNum = $orderNum ? $orderNum->toArray() : [];

            $orderCount = [
                'all' => $orderNum['order_all_num'] ?? 0, //订单数量
                'nopay' => $orderNum['order_nopay'] ?? 0, //待付款订单数量
                'nogoods' => $orderNum['order_nogoods'] ?? 0, //待收货订单数量
                'isfinished' => $orderNum['order_isfinished'] ?? 0, //已完成订单数量
                'isdelete' => $orderNum['order_isdelete'] ?? 0, //回收站订单数量
                'team_num' => $orderNum['order_team_num'] ?? 0, //拼团订单数量
                'not_comment' => $orderNum['order_not_comment'] ?? 0,  //待评价订单数量
                'return_count' => $orderNum['order_return_count'] ?? 0 //待同意状态退换货申请数量
            ];

            $this->smarty->assign('allorders', $orderCount['all']);

            //待评价
            $this->smarty->assign('signNum', $orderCount['not_comment']);

            //待付款
            $this->smarty->assign('pay_count', $orderCount['nopay']);

            //已发货,用户尚未确认收货
            $this->smarty->assign('to_confirm_order', $orderCount['nogoods']);

            //已发货，用户已确认收货
            $this->smarty->assign('to_finished', $orderCount['isfinished']);
            //订单数量 end

            load_helper('transaction');

            $order_type = addslashes(trim(request()->input('order_type', '')));
            $page = (int)request()->input('page', 1);

            $basic_info = SellerShopinfo::where('ru_id', 0)->first();
            $basic_info = $basic_info ? $basic_info->toArray() : [];

            $chat = $this->dscRepository->chatQq($basic_info);
            $basic_info['kf_ww'] = $chat['kf_ww'];
            $basic_info['kf_qq'] = $chat['kf_qq'];

            $this->smarty->assign('basic_info', $basic_info);

            $status_list = lang('user.cs');
            unset($status_list[0]);

            $this->smarty->assign('status_list', $status_list);   // 订单状态

            if ($action == 'order_list') {
                $this->smarty->assign('action', $action);
            } elseif ($action == 'order_recycle') { //订单回收站
                $this->smarty->assign('action', $action);
            }

            $category = get_oneTwo_category();
            $this->smarty->assign('category', $category);

            if ($this->dscRepository->strLen($order_type) == $this->dscRepository->strLen('toBe_unconfirmed')) {
                $order_where = 1;
            } elseif ($this->dscRepository->strLen($order_type) == $this->dscRepository->strLen('toBe_pay')) {
                $order_where = 2;
            } elseif ($this->dscRepository->strLen($order_type) == $this->dscRepository->strLen('toBe_confirmed')) {
                $order_where = 3;
            } elseif ($this->dscRepository->strLen($order_type) == $this->dscRepository->strLen('toBe_finished')) {
                $order_where = 4;
            } else {
                $order_where = 0;
            }

            $size = 10;
            $where = [
                'user_id' => $user_id,
                'show_type' => $show_type,
                'is_zc_order' => 0,
                'page' => $page,
                'size' => $size
            ];

            $record_count = $this->userOrderService->getUserOrdersCount($where, $action);

            $where['record_count'] = $record_count;
            $orders = $this->userOrderService->getUserOrdersList($where, $action);

            $merge = get_user_merge($user_id);
            $this->smarty->assign('order_type', $order_type);
            $this->smarty->assign('order_where', $order_where);
            $this->smarty->assign('merge', $merge);
            $this->smarty->assign('orders', $orders);
            $this->smarty->assign('open_delivery_time', $this->config['open_delivery_time']);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 查看订单详情
        /* ------------------------------------------------------ */
        elseif ($action == 'order_detail' || $action == 'auction_order_detail') {
            load_helper(['transaction', 'payment', 'order', 'clips']);

            $order_id = (int)request()->input('order_id', 0);

            $noTime = gmtime();

            /* 查询订单信息，检查状态 */
            $where = [
                'order_id' => $order_id
            ];
            $orderInfo = $this->orderService->getOrderInfo($where);
            if (empty($orderInfo) || $user_id != $orderInfo['user_id']) {
                return redirect('user.php');
            }

            if ($this->config['open_delivery_time'] == 1) {
                if (($orderInfo['order_status'] == OS_CONFIRMED || $orderInfo['order_status'] == OS_SPLITED) && $orderInfo['shipping_status'] == SS_SHIPPED && $orderInfo['pay_status'] == PS_PAYED) { //发货状态
                    $delivery_time = $orderInfo['shipping_time'] + 24 * 3600 * $orderInfo['auto_delivery_time'];

                    if ($noTime > $delivery_time) { //自动确认发货操作

                        $confirm_take_time = gmtime();
                        $other = [
                            'order_status' => OS_SPLITED,
                            'shipping_status' => SS_RECEIVED,
                            'pay_status' => PS_PAYED,
                            'confirm_take_time' => $confirm_take_time
                        ];
                        $res = OrderInfo::where('order_id', $order_id)->update($other);

                        if ($res) {
                            $note = $GLOBALS['_LANG']['self_motion_goods'];
                            order_action($orderInfo['order_sn'], OS_SPLITED, SS_RECEIVED, PS_PAYED, $note, $GLOBALS['_LANG']['buyer'], 0, $confirm_take_time);

                            $seller_id = OrderGoods::where('order_id', $order_id)->value('ru_id');
                            $value_card = ValueCardRecord::where('order_id', $order_id)->value('use_val');

                            $return_amount_info = $this->orderRefoundService->orderReturnAmount($order_id);

                            if ($orderInfo['order_amount'] > 0 && $orderInfo['order_amount'] > $orderInfo['rate_fee']) {
                                $order_amount = $orderInfo['order_amount'] - $orderInfo['rate_fee'];
                            } else {
                                $order_amount = $orderInfo['order_amount'];
                            }

                            $other = [
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
                            ];

                            if ($seller_id) {
                                $this->commissionService->getOrderBillLog($other);
                            }
                        }
                    }
                }
            }

            // 如果开启验证支付密码
            if ($this->config['use_paypwd'] == 1) {
                $this->smarty->assign('open_pay_password', 1);
            } else {
                $this->smarty->assign('open_pay_password', 0);
            }

            /* 订单详情 */
            $order = get_order_detail($order_id, $user_id);

            /* 支付方式说明 */
            $payment = payment_info($orderInfo['pay_id']);
            if ($payment) {
                $this->smarty->assign('pay_desc', $payment['pay_desc']);
            }

            /* 查询更新支付状态 start */
            if (($orderInfo['order_status'] == OS_UNCONFIRMED || $orderInfo['order_status'] == OS_CONFIRMED || $orderInfo['order_status'] == OS_SPLITED) && $orderInfo['pay_status'] == PS_UNPAYED) {
                $pay_log = get_pay_log($orderInfo['order_id'], 1);
                if ($pay_log && $pay_log['is_paid'] == 0) {
                    if ($payment && strpos($payment['pay_code'], 'pay_') === false) {
                        $payObject = $this->commonRepository->paymentInstance($payment['pay_code']);

                        /* 订单查询 */
                        if (!is_null($payObject)) {
                            /* 判断类对象方法是否存在 */
                            if (is_callable([$payObject, 'orderQuery'])) {
                                $order_other = [
                                    'order_sn' => $order['order_sn'],
                                    'log_id' => $pay_log['log_id'],
                                    'order_amount' => $order['order_amount'],
                                ];

                                $payObject->orderQuery($order_other);

                                $where = [
                                    'order_id' => $orderInfo['order_id']
                                ];
                                $order_info = $this->orderService->getOrderInfo($where);

                                if ($order_info) {
                                    $orderInfo['order_status'] = $order['order_status'] = $order_info['order_status'];
                                    $orderInfo['shipping_status'] = $order['shipping_status'] = $order_info['shipping_status'];
                                    $orderInfo['pay_status'] = $order['pay_status'] = $order_info['pay_status'];
                                    $orderInfo['pay_time'] = $order['pay_time'] = $order_info['pay_time'];
                                }
                            }
                        }
                    }
                }
            }
            /* 查询更新支付状态 end */

            /* 判断秒杀订单是否有效 */
            if ($order['extension_code'] == 'seckill') {
                $seckill_status = $this->userService->is_invalid($order['extension_id']);
                $this->smarty->assign('seckill_status', $seckill_status);
            }

            /* 增值发票 start */
            if ($order['invoice_type'] == 1) {
                $where = [
                    'user_id' => $user_id
                ];
                $vat_info = $this->userService->getUsersVatInvoicesInfo($where);
                $this->smarty->assign('vat_info', $vat_info);
            }
            /* 增值发票 end */

            /*获取订单门店信息 by kong 20160725 start*/
            $where = [
                'order_id' => $order_id
            ];
            $stores = $this->orderService->getStoreOrderInfo($where);

            if ($stores) {
                $order['store_id'] = $stores['store_id'];
                $order['pick_code'] = $stores['pick_code'];
                $order['take_time'] = $stores['take_time'];

                if ($order['store_id'] > 0) {

                    $offline_store = OfflineStore::where('id', $order['store_id'])->first();
                    if ($offline_store) {
                        $offline_store['province'] = Region::where('region_id', $offline_store['province'])->value('region_name');
                        $offline_store['city'] = Region::where('region_id', $offline_store['city'])->value('region_name');
                        $offline_store['district'] = Region::where('region_id', $offline_store['district'])->value('region_name');
                    }
                    $offline_store = $offline_store ? $offline_store->toArray() : [];

                    $offline_store['stores_img'] = $offline_store ? get_image_path($offline_store['stores_img']) : '';
                    $this->smarty->assign('offline_store', $offline_store);
                }
            }

            /*获取订单门店信息 by kong 20160725 end*/
            if ($order === false) {
                return $this->err->show($GLOBALS['_LANG']['back_home_lnk'], './');
            }

            //详情页申请退换货
            if ($orderInfo['order_status'] == OS_SPLITED && $orderInfo['shipping_status'] == SS_RECEIVED && $orderInfo['pay_status'] == PS_PAYED) {
                $order['return_url'] = "user_order.php?act=goods_order&order_id=$order_id";
            } elseif ($orderInfo['order_status'] == OS_CONFIRMED && $orderInfo['shipping_status'] == SS_RECEIVED && $orderInfo['pay_status'] == PS_PAYED) { //已确认，付款，收货 liu
                $order['return_url'] = "user_order.php?act=goods_order&order_id=$order_id";
            }

            $order['affirm_received'] = '';
            if (($orderInfo['order_status'] == OS_CONFIRMED || $orderInfo['order_status'] == OS_SPLITED) && $orderInfo['shipping_status'] == SS_SHIPPED && $orderInfo['pay_status'] == PS_PAYED) {
                $order['affirm_received'] = "user_order.php?act=affirm_received&order_id=$order_id&action=info";
            }

            //发货日期起可退换货时间
            $sign_time = $this->config['sign'];

            //判断发货日期起可退换货时间
            if ($sign_time > 0) {
                $log_time = OrderAction::where('order_id', $orderInfo['order_id']);

                if ($orderInfo['pay_status'] == PS_UNPAYED) {
                    $log_time = $log_time->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                        ->whereIn('shipping_status', [SS_RECEIVED])
                        ->whereIn('pay_status', [PS_UNPAYED]);
                } elseif ($orderInfo['pay_status'] == PS_PAYED) {
                    $log_time = $log_time->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                        ->whereIn('shipping_status', [SS_UNSHIPPED, SS_SHIPPED, SS_RECEIVED, SS_SHIPPED_PART, OS_SHIPPED_PART])
                        ->whereIn('pay_status', [PS_PAYED]);
                }

                $log_time = $log_time->orderBy('action_id', 'desc');

                $log_time = $log_time->value('log_time');

                $order['is_return'] = 0;
                $order_status = [OS_CANCELED, OS_INVALID, OS_RETURNED];
                if (!in_array($orderInfo['order_status'], $order_status)) {
                    if (!$log_time) {
                        $log_time = !empty($orderInfo['pay_time']) ? $orderInfo['pay_time'] : $orderInfo['add_time'];
                    }

                    $signtime = $log_time + $sign_time * 3600 * 24;

                    if ($noTime < $signtime) {
                        $order['is_return'] = 1;
                    }
                }

                if ($order['is_return'] != 1) {
                    $order['return_url'] = "";
                }
            }

            /* 是否显示添加到购物车 */
            if ($order['extension_code'] != 'group_buy' && $order['extension_code'] != 'exchange_goods' && $order['extension_code'] != 'presale') {
                $this->smarty->assign('allow_to_cart', 1);
            }

            /* 订单商品 */
            $goods_list = order_goods($order_id);

            $order['goods_list_count'] = 0;
            if ($goods_list) {
                foreach ($goods_list as $key => $value) {
                    $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
                    $goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);
                    $goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);
                    /* 虚拟商品卡密 by wu */
                    if ($value['is_real'] == 0) {
                        $goods_list[$key]['virtual_info'] = get_virtual_goods_info($value['rec_id']);
                    }
                    $order['goods_list_count'] += $value['goods_number'];
                }
            }

            $shop_info = order_shop_info($order_id);
            $order['shop_name'] = $shop_info['shop_name'];
            $order['shop_url'] = $shop_info['shop_url'] ? $shop_info['shop_url'] : "index.php";

            //获取众筹商品信息 by wu
            $zc_goods_info = get_zc_goods_info($order_id);
            $this->smarty->assign('zc_goods_info', $zc_goods_info);

            /* 设置能否修改使用余额数 */
            if ($order['order_amount'] > 0) {
                if ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED) {
                    $where = [
                        'user_id' => $order['user_id']
                    ];
                    $user_info = $this->userService->userInfo($where);

                    // 后台安装了"余额支付"才显示余额支付输入框 bylu;

                    $where = [
                        'pay_code' => 'balance'
                    ];
                    $payment = $this->paymentService->getPaymentInfo($where);

                    $is_balance = $payment ? $payment['enabled'] : 0;

                    if ($user_info['user_money'] + $user_info['credit_line'] > 0 && $is_balance) {
                        $this->smarty->assign('allow_edit_surplus', 1);
                        $this->smarty->assign('max_surplus', sprintf(lang('user.max_surplus'), "<em id='max_surplus'>" . $user_info['user_money'] . "</em>"));
                    }
                }
            }

            /* 未发货，未付款时允许更换支付方式 */
            if ($order['order_amount'] > 0 && ($order['pay_status'] == PS_UNPAYED || $order['pay_status'] == PS_PAYED_PART) && $order['shipping_status'] == SS_UNSHIPPED) {
                $payment_list = available_payment_list(false, 0, true);

                /*  @author-bylu 判断商家是否开启了"在线支付",如未开启则不显示在线支付的一系列方法 start */

                $where = [
                    'pay_code' => 'onlinepay'
                ];
                $payment = $this->paymentService->getPaymentInfo($where);

                $is_onlinepay = $payment ? $payment['enabled'] : 0;

                if ($is_onlinepay != 0) {
                    $this->smarty->assign('is_onlinepay', $is_onlinepay);
                }
                /*  @author-bylu  end */

                $seller_grade = 1;
                if ($order['ru_id']) {
                    $sg_ru_id = [$order['ru_id']];
                    $seller_grade = get_seller_grade($sg_ru_id, 1);
                }

                /* 过滤掉当前支付方式和余额支付方式 */
                if (is_array($payment_list)) {
                    if ($payment_list) {
                        foreach ($payment_list as $key => $payment) {

                            //pc端去除ecjia的支付方式
                            if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                                unset($payment_list[$key]);
                                continue;
                            }

                            if ($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance') {
                                unset($payment_list[$key]);
                            }

                            //判断当前用户是否拥有白条支付授权(有的话才显示"白条支付按钮") bylu;
                            if ($payment['pay_code'] == 'chunsejinrong') {
                                if (count($goods_list) > 1 || (count($goods_list) == 1 && $goods_list[0]['stages_qishu'] < 0)) {
                                    unset($payment_list[$key]);
                                }

                                if ($seller_grade == 0) {
                                    unset($payment_list[$key]);
                                }
                            }
                        }
                    }
                }

                $this->smarty->assign('payment_list', $payment_list);
            }
            /*
             * 正常订单显示支付倒计时
             */
            $pay_code = Payment::where('pay_code', 'cod')->value('pay_id');
            if ($order['extension_code'] != 'presale' && $order['pay_status'] == PS_UNPAYED && $pay_code != $order['pay_id']) {

                $pay_effective_time = isset($this->config['pay_effective_time']) && $this->config['pay_effective_time'] > 0 ? intval($this->config['pay_effective_time']) : 0; //订单时效
                $pay_effective_time = $order['add_time'] + $pay_effective_time * 60;
                if ($pay_effective_time < gmtime()) {
                    $order['pay_effective_time'] = 0;
                } else {
                    $order['pay_effective_time'] = local_date('Y-m-d H:i:s', $pay_effective_time);
                }
            }

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $order['formated_rate_fee'] = empty($order['rate_fee']) ? price_format(0) : price_format($order['rate_fee']);
            }

            /* 订单详情 状态追踪 */
            $order['order_tracking'] = 1;
            if ($order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED || $order['order_status'] == OS_SPLITING_PART || $order['order_status'] == OS_RETURNED_PART) {
                $order['order_tracking'] = 2;

                if ($order['pay_status'] == PS_PAYED) {
                    $order['order_tracking'] = 3;

                    if ($order['shipping_status'] == SS_SHIPPED) {
                        $order['order_tracking'] = 4;
                    } elseif ($order['shipping_status'] == SS_RECEIVED) {
                        $order['order_tracking'] = 5;
                    }
                }
            }
            //
            //延迟收货申请 start
            $order['dsc_shipping_status'] = $order['shipping_status'];
            //获取延迟收货时间段 end

            /* 订单 支付 配送 状态语言项 */
            $order['order_status_desc'] = lang('user.os.' . $order['order_status']);
            $order['pay_status_desc'] = lang('user.ps.' . $order['pay_status']);
            $order['shipping_status_desc'] = lang('user.ss.' . $order['shipping_status']);

            if (in_array($order['order_status'], [OS_CANCELED, OS_INVALID, OS_RETURNED, OS_RETURNED_PART, OS_ONLY_REFOUND])) {
                $order['show_pay'] = 0;
            } else {
                $order['show_pay'] = 1;
            }

            //留言数
            $feedback_num = Feedback::where('parent_id', 0)
                ->where('order_id', $order_id)
                ->where('user_id', $user_id)
                ->count();

            //收货地址id
            $address_id = Users::where('user_id', $user_id)->value('address_id');

            /* 自提点信息 */
            $shipping_code = Shipping::where('shipping_id', $order['shipping_id'])->value('shipping_code');
            if ($shipping_code == 'cac') {
                $point_id = OrderInfo::select('point_id')->where('order_id', $order_id)->first();
                $point_id = $point_id ? $point_id->toArray() : [];
                $point = ShippingPoint::where('id', $point_id['point_id'])->first();
                $order['point'] = $point ? $point->toArray() : [];
                $order['point']['pickDate'] = $order['shipping_dateStr'];
            }

            /*  @author-bylu 分期信息 start */

            //判断当前订单是否子订单;
            $main_order_id = OrderInfo::where('order_id', $order_id)->value('main_order_id');

            if ($main_order_id) {
                $stages_info = BaitiaoLog::where('order_id', $main_order_id)->where('user_id', $user_id)->first();
            } else {
                $stages_info = BaitiaoLog::where('order_id', $order_id)->where('user_id', $user_id)->first();
            }

            $stages_info = $stages_info ? $stages_info->toArray() : [];

            if ($stages_info) {
                //还款日;
                $repay_dates = unserialize($stages_info['repay_date']);

                $stages_info['repay_date'] = $repay_dates[$stages_info['yes_num'] + 1] ?? $repay_dates[$stages_info['yes_num']];

                $this->smarty->assign('is_baitiao', true);
                $this->smarty->assign('stages_info', $stages_info);
            }

            /*  @author-bylu 分期信息 end */
            if ($order['extension_code'] == 'presale' && $order['pay_status'] == PS_PAYED_PART) {//判断是否在支付尾款时期，获取支付尾款时间 预售用 liu
                $this->smarty->assign('is_presale', true);
                $result = $this->userService->PresaleSettleStatus($order['extension_id']);
                $this->smarty->assign('settle_status', $result['settle_status']);
                $this->smarty->assign('pay_start_time', $result['start_time']);
                $this->smarty->assign('pay_end_time', $result['end_time']);
            }

            /* 处理运单号 */
            if (!empty($order['invoice_no'])) {
                $invoice_no_list = explode(',', $order['invoice_no']);
                $order['invoice_no_list'] = $invoice_no_list;
                $order['invoice_no_count'] = count($invoice_no_list);
            }

            $order = $this->userOrderService->mainShipping($order);

            if ($order['main_count'] > 0) {
                $order['shop_name'] = $this->config['shop_name'];
                $order['ru_id'] = 0;
            }

            // 团购支付保证金标识
            if ($order['extension_code'] == 'group_buy') {
                $order['is_group_deposit'] = 0;
                $group_buy = $this->groupBuyService->getGroupBuyInfo(['group_buy_id' => $order['extension_id']]);
                if (isset($group_buy) && $group_buy['deposit'] > 0 && $group_buy['is_finished'] == 0) {
                    $order['is_group_deposit'] = 1;
                }
            }

            $this->smarty->assign('order', $order);
            $this->smarty->assign('address_id', $address_id);
            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('goods_list_count', $order['goods_list_count']);
            $this->smarty->assign('feedback_num', $feedback_num);

            //延迟收货设置
            $this->smarty->assign('open_order_delay', $this->config['open_order_delay']);
            $this->smarty->assign('can_invoice', $this->config['can_invoice']);//是否可以开发票

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $web = app(CrossBorderService::class)->webExists();

                if (!empty($web)) {
                    $web->smartyAssign();
                }
            }

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 删除、还原、永久性删除订单
        /* ------------------------------------------------------ */
        elseif ($action == 'order_delete_restore') {

            $order = strip_tags(urldecode(request()->input('order', '')));
            $order = json_str_iconv($order);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'order_id' => ''];

            $order = dsc_decode($order);
            $act = $order->act;
            $order_id = $order->order_id;
            $result['order_id'] = $order_id;

            if ($order->order_id <= 0) {
                $result['error'] = 1;
                return response()->json($result);
            }

            if ($order->action == 'delete') { //删除
                $type = 1;

                $show_type = 0;
                $this->smarty->assign('action', 'order_list');
            } elseif ($order->action == 'restore') { //还原
                $type = 0;

                $show_type = 1;
                $this->smarty->assign('action', 'order_recycle');
            } elseif ($order->action == 'thorough') { //永久性删除
                $show_type = 1;
                $this->smarty->assign('action', 'order_recycle');
            }

            if ($order->action != 'thorough') {
                $parent = [
                    'is_delete' => $type
                ];

                OrderInfo::where('order_id', $order_id)->update($parent);
            } else {

                $type = 2;
                $parent = [
                    'is_delete' => $type
                ];
                OrderInfo::where('order_id', $order_id)->update($parent);

                $where = [
                    'order_id' => $order_id
                ];
                $order_info = $this->orderService->getOrderInfo($where);

                $parent = [
                    'order_id' => $order_id,
                    'action_user' => $GLOBALS['_LANG']['buyer'],
                    'order_status' => $order_info['order_status'],
                    'shipping_status' => $order_info['shipping_status'],
                    'pay_status' => $order_info['pay_status'],
                    'action_note' => $GLOBALS['_LANG']['delete_order'],
                    'log_time' => gmtime()
                ];
                OrderAction::insert($parent);
            }

            $main_count = OrderInfo::where('order_id', $order_id)->value('main_count');

            if ($main_count > 0) {
                OrderInfo::where('main_order_id', $order_id)->update([
                    'is_delete' => $type
                ]);
            }

            load_helper('transaction');
            $page = (int)request()->input('page', 1);

            $record_count = OrderInfo::where('user_id', $user_id)->where('is_delete', $show_type)->count();
            $action = 'order_list';
            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);

            $where = [
                'user_id' => $user_id,
                'show_type' => $show_type,
                'is_zc_order' => 0,
                'page' => $pager['start'],
                'size' => $pager['size']
            ];

            $record_count = $this->userOrderService->getUserOrdersCount($where, $act);

            $where['record_count'] = $record_count;
            $orders = $this->userOrderService->getUserOrdersList($where, $act);

            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('orders', $orders);

            $insert_arr = [
                'act' => $order->action,
                'filename' => 'user'
            ];

            $this->smarty->assign('no_records', insert_get_page_no_records($insert_arr));

            $result['content'] = $this->smarty->fetch("library/user_order_list.lbi");
            $result['page_content'] = $this->smarty->fetch("library/pages.lbi");

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 查询订单
        /* ------------------------------------------------------ */
        elseif ($action == 'order_to_query') {
            $order = strip_tags(urldecode(request()->input('order', '')));
            $order = json_str_iconv($order);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'order_id' => ''];

            $order = dsc_decode($order);

            $order->keyword = addslashes(trim($order->keyword));

            if (isset($order->order_id) && $order->order_id > 0) {
                $result['error'] = 1;
                return response()->json($result);
            }

            if ($order->action == 'order_list' || $order->action == 'auction') {
                $show_type = 0;
            } else {
                $show_type = 1;
            }

            load_helper('transaction');

            $page = (int)request()->input('page', 1);

            $size = 10;
            $where = [
                'user_id' => $user_id,
                'show_type' => $show_type,
                'is_zc_order' => 0,
                'page' => $page,
                'size' => $size
            ];

            $record_count = $this->userOrderService->getUserOrdersCount($where, $order);

            $where['record_count'] = $record_count;
            $orders = $this->userOrderService->getUserOrdersList($where, $order);

            $status_keyword = '';
            $date_keyword = '';
            if ($order->idTxt == 'submitDate') {
                $date_keyword = $order->keyword;
                $status_keyword = $order->status_keyword;
            } elseif ($order->idTxt == 'status_list') {
                $date_keyword = $order->date_keyword;
                $status_keyword = $order->keyword;
            } elseif ($order->idTxt == 'payId' || $order->idTxt == 'to_finished' || $order->idTxt == 'to_confirm_order' || $order->idTxt == 'to_unconfirmed' || $order->idTxt == 'signNum') {
                $status_keyword = $order->keyword;
            }

            $result['date_keyword'] = $date_keyword;
            $result['status_keyword'] = $status_keyword;
            $this->smarty->assign('orders', $orders);
            $this->smarty->assign('status_list', lang('user.cs'));   // 订单状态
            $this->smarty->assign('date_keyword', $date_keyword);
            $this->smarty->assign('status_keyword', $status_keyword);

            $insert_arr = [
                'act' => $order->action,
                'filename' => 'user'
            ];
            $this->smarty->assign('no_records', insert_get_page_no_records($insert_arr));
            $this->smarty->assign('open_delivery_time', $this->config['open_delivery_time']);
            $this->smarty->assign('action', $order->action);
            $this->smarty->assign('order_type', $order->idTxt);
            $result['content'] = $this->smarty->fetch("library/user_order_list.lbi");

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 取消订单
        /* ------------------------------------------------------ */
        elseif ($action == 'cancel_order') {
            load_helper(['transaction', 'order']);

            $order_id = (int)request()->input('order_id', 0);

            if (cancel_order($order_id, $user_id)) {
                return dsc_header("Location: user_order.php?act=order_list\n");
            } else {
                return $this->err->show($GLOBALS['_LANG']['order_list_lnk'], 'user_order.php?act=order_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 交易投诉
        /* ------------------------------------------------------ */
        elseif ($action == 'complaint_list') {
            load_helper('transaction');

            $page = (int)request()->input('page', 1);
            //获取到举报列表  1以举报，0未举报
            $is_complaint = (int)request()->input('is_complaint', 0);
            // 是否异步查询
            $is_ajax = (int)request()->input('is_ajax', 0);
            // 关键字
            $keyword = addslashes(trim(request()->input('keyword', '')));

            $complain_time = 15;
            if ($this->config['receipt_time'] > 0) {
                $complain_time = $this->config['receipt_time'];
            }

            $dealy_time = $complain_time * 86400;

            $record_count = $this->userService->GetComplaintCount($user_id, $dealy_time, $is_complaint, $keyword);

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page);
            $complaint_list = $this->userService->GetComplaintList($user_id, $dealy_time, $pager['size'], $pager['start'], $is_complaint, $keyword);
            $this->smarty->assign('no_records', $GLOBALS['_LANG']['no_records']);
            $this->smarty->assign("page", $page);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('is_complaint', $is_complaint);
            $this->smarty->assign('orders', $complaint_list);
            $this->smarty->assign("action", $action);
            if ($is_ajax == 1) {
                $result = ['error' => 0, 'content' => '', 'pager' => ''];
                $result['pager'] = get_pager('user.php', ['act' => $action], $record_count, $page);
                $result['content'] = $this->smarty->fetch("library/complaint_list.lbi");

                return response()->json($result);
            } else {
                return $this->smarty->display('user_clips.dwt');
            }
        }

        /* ------------------------------------------------------ */
        //-- 投诉申请
        /* ------------------------------------------------------ */
        elseif ($action == 'complaint_apply') {
            load_helper(['transaction', 'order']);

            $complaint_id = (int)request()->input('complaint_id', 0);
            $order_id = (int)request()->input('order_id', 0);

            if ($complaint_id > 0) {
                $complaint_info = get_complaint_info($complaint_id);
                $order_id = $complaint_info['order_id'];
                //获取谈话列表
                if ($complaint_info['complaint_state'] > 1) {
                    $talk_list = checkTalkView($complaint_id, 'user');
                    $this->smarty->assign("talk_list", $talk_list);
                }
                $this->smarty->assign("complaint_info", $complaint_info);
            } else {
                $complaint_title = get_complaint_title();
                $this->smarty->assign("complaint_title", $complaint_title);
            }
            //获取订单详情

            $orders = order_info($order_id);

            $basic_info = SellerShopinfo::where('ru_id', 0)->first();
            $basic_info = $basic_info ? $basic_info->toArray() : [];

            $orders['shop_name'] = $this->merchantCommonService->getShopName($orders['ru_id'], 1); //店铺名称'
            $build_uri = [
                'urid' => $orders['ru_id'],
                'append' => $orders['shop_name']
            ];
            $domain_url = $this->merchantCommonService->getSellerDomainUrl($orders['ru_id'], $build_uri);
            $orders['shop_url'] = $domain_url['domain_name'];

            $chat = $this->dscRepository->chatQq($basic_info);
            $orders['kf_qq'] = $chat['kf_qq'];
            $orders['kf_ww'] = $chat['kf_ww'];

            if ($this->config['customer_service'] == 0) {
                $ru_id = 0;
            } else {
                $ru_id = $orders['ru_id'];
            }

            $shop_information = $this->merchantCommonService->getShopName($ru_id); //通过ru_id获取到店铺信息;
            $orders['is_IM'] = $shop_information['is_IM']; //平台是否允许商家使用"在线客服";

            //判断当前商家是平台,还是入驻商家 bylu
            if ($ru_id == 0) {
                //判断平台是否开启了IM在线客服
                $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                if ($kf_im_switch) {
                    $orders['is_dsc'] = true;
                } else {
                    $orders['is_dsc'] = false;
                }
            } else {
                $orders['is_dsc'] = false;
            }
            $orders['order_goods'] = get_order_goods_toInfo($order_id);

            //获取投诉相册
            $where = [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'complaint_id' => $complaint_id
            ];
            $img_list = $this->userService->getComplaintImgList($where);

            if ($img_list) {
                foreach ($img_list as $key => $row) {
                    $img_list[$key]['id'] = $row['img_id'];
                    $img_list[$key]['comment_img'] = get_image_path($row['img_file']);
                }
            }

            //模板赋值
            $this->smarty->assign('img_list', $img_list);
            $this->smarty->assign('sessid', SESS_ID);
            $this->smarty->assign("complaint_id", $complaint_id);
            $this->smarty->assign("order", $orders);
            $this->smarty->assign("order_id", $order_id);
            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 订单投诉入库处理
        /* ------------------------------------------------------ */
        elseif ($action == 'complaint_submit') {
            $order_id = (int)request()->input('order_id', 0);
            $title_id = (int)request()->input('title_id', 0);
            $complaint_content = addslashes(trim(request()->input('complaint_content', '')));
            //判断该订单是否已经投诉 防止重复提交
            $complaint_count = Complaint::where('order_id', $order_id)->count();

            if ($complaint_count > 0) {
                return show_message($GLOBALS['_LANG']['complaint_reprat']);
            }
            if ($title_id == 0) {
                return show_message($GLOBALS['_LANG']['complaint_title_null']);
            } elseif ($complaint_content == '') {
                return show_message($GLOBALS['_LANG']['complaint_content_null']);
            } else {
                //获取订单信息
                $order_info = [];
                $order_info['order_sn'] = OrderInfo::where('order_id', $order_id)->value('order_sn');
                $order_info['ru_id'] = OrderGoods::where('order_id', $order_id)->value('ru_id');

                $shop_name = $this->merchantCommonService->getShopName($order_info['ru_id'], 1);
                $time = gmtime();
                //更新数据
                $other = [
                    'user_id' => $user_id,
                    'user_name' => session('user_name', ''),
                    'order_id' => $order_id,
                    'shop_name' => $shop_name,
                    'order_sn' => $order_info['order_sn'],
                    'ru_id' => $order_info['ru_id'],
                    'title_id' => $title_id,
                    'add_time' => $time,
                    'complaint_content' => $complaint_content
                ];

                //入库处理
                $complaint_id = Complaint::insertGetId($other);

                //更新图片
                if ($complaint_id > 0) {
                    ComplaintImg::where('user_id', $user_id)->where('order_id', $order_id)->where('complaint_id', 0)->update(['complaint_id' => $complaint_id]);
                }
                return show_message($GLOBALS['_LANG']['complaint_success'], $GLOBALS['_LANG']['back_complaint_list'], 'user_order.php?act=complaint_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 提交仲裁
        /* ------------------------------------------------------ */
        elseif ($action == 'arbitration') {
            $complaint_id = (int)request()->input('complaint_id', 0);
            $complaint_state = (int)request()->input('complaint_state', 3);

            $other = [
                'complaint_state' => $complaint_state,
            ];

            if ($complaint_state == 4) {
                $other['end_handle_messg'] = $GLOBALS['_LANG']['buyer_closes'];
            }

            Complaint::where('complaint_id', $complaint_id)->update($other);

            return show_message($GLOBALS['_LANG']['apply_success'], $GLOBALS['_LANG']['back_page_up'], 'user_order.php?act=complaint_apply&complaint_id=' . $complaint_id);
        }

        /* ------------------------------------------------------ */
        //-- 交易快照
        /* ------------------------------------------------------ */
        elseif ($action == 'trade') {
            $tradeId = (int)request()->input('tradeId', 0);
            $snapshot = request()->exists('snapshot') ? true : false;

            $row = TradeSnapshot::where('trade_id', $tradeId)->first();
            $row = $row ? $row->toArray() : [];

            if ($row && $row['goods_desc'] && $this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            if ($row['goods_desc']) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $row['goods_desc']);
                $row['goods_desc'] = $desc_preg['goods_desc'];
            }

            $row['goods_img'] = get_image_path($row['goods_img']);
            $this->smarty->assign('pictures', $this->goodsGalleryService->getGoodsGallery($row['goods_id']));                    // 商品相册
            //
            //格式化时间戳
            $row['snapshot_time'] = local_date('Y-m-d H:i:s', $row['snapshot_time']);

            $row['shop_url'] = '';
            if ($row['ru_id'] > 0) {
                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($row['ru_id']); //商家所有商品评分类型汇总
                $this->smarty->assign('merch_cmt', $merchants_goods_comment);

                $build_uri = [
                    'urid' => $row['ru_id'],
                    'append' => $this->merchantCommonService->getShopName($row['ru_id'], 3)
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['ru_id'], $build_uri);
                $row['shop_url'] = $domain_url['domain_name'];
            }

            // 判断当前商家是否允许"在线客服" start
            $shop_information = $this->merchantCommonService->getShopName($row['ru_id']);

            //判断当前商家是平台,还是入驻商家
            if ($row['ru_id'] == 0) {
                //判断平台是否开启了IM在线客服
                $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                if ($kf_im_switch) {
                    $shop_information['is_dsc'] = true;
                } else {
                    $shop_information['is_dsc'] = false;
                }
            } else {
                $shop_information['is_dsc'] = false;
            }

            $properties = $this->goodsAttrService->getGoodsProperties($row['goods_id'], $warehouse_id, $area_id, $area_city);  // 获得商品的规格和属性 属性没有保存到快照内 调用实时的
            $this->smarty->assign('properties', $properties['pro']);                              // 商品规格
            $this->smarty->assign('specification', $properties['spe']);                              // 商品属性

            $this->smarty->assign('shop_information', $shop_information);

            $this->smarty->assign('page_title', $row['goods_name']);  // 页面标题
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());     // 网店帮助
            $this->smarty->assign('snapshot', $snapshot);
            $this->smarty->assign('goods', $row);

            return $this->smarty->display('trade_snapshot.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 退换货申请订单  --->商品列表
        /* ------------------------------------------------------ */
        elseif ($action == 'goods_order') {
            load_helper('transaction');
            load_helper('order');
            /* 根据订单id或订单号查询订单信息 */
            if (request()->exists('order_id')) {
                $order_id = (int)request()->input('order_id', 0);
            } else {
                /* 如果参数不存在，退出 */
                return 'invalid parameter';
            }

            /* 订单商品 */
            $goods_list = order_goods($order_id);

            $price = [];
            if ($goods_list) {
                foreach ($goods_list as $key => $value) {
                    if ($value['extension_code'] != 'package_buy') {
                        $price[] = $value['subtotal'];
                        $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
                        $goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);
                        $goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);
                        $goods_list[$key]['is_refound'] = get_is_refound($value['rec_id']);   //判断是否退换货过
                        $goods_list[$key]['goods_attr'] = str_replace(' ', '&nbsp;&nbsp;&nbsp;&nbsp;', $value['goods_attr']);

                        $where = [
                            'goods_id' => $value['goods_id'],
                            'warehouse_id' => $warehouse_id,
                            'area_id' => $area_id,
                            'area_city' => $area_city
                        ];
                        $goods_info = $this->goodsService->getGoodsInfo($where);

                        $goods_list[$key]['goods_cause'] = $this->goodsCommonService->getGoodsCause($goods_info['goods_cause']);
                    } else {
                        unset($goods_list[$key]);
                    }
                }
            }

            $this->smarty->assign('package_buy', false);

            $formated_goods_amount = $price ? $this->dscRepository->getPriceFormat(array_sum($price), false) : $this->dscRepository->getPriceFormat(0);
            $this->smarty->assign('formated_goods_amount', $formated_goods_amount);

            /* 取得订单商品及货品 */
            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('goods_list', $goods_list);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 退换货申请列表
        /* ------------------------------------------------------ */
        elseif ($action == 'service_detail') {
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 退换货申请列表
        /* ------------------------------------------------------ */
        elseif ($action == 'apply_return') {
            load_helper('transaction');
            load_helper('order');
            /* 根据订单id或订单号查询订单信息 */
            if (request()->exists('rec_id')) {
                $rec_id = (int)request()->input('rec_id', 0);
            } else {
                /* 如果参数不存在，退出 */
                return 'invalid parameter';
            }

            $order_id = (int)request()->input('order_id', 0);

            $order = order_info($order_id);

            // 检查订单商品是否包含赠品  有赠品仅支持整单退
            $gift_count = OrderGoods::where('order_id', $order_id)->where('is_gift', '>', 0)->count();
            if ($gift_count > 0) {
                return show_message($GLOBALS['_LANG']['only_can_return_all'], '', '', 'info', true);
            }

            /* 退货权限 by wu */
            $return_allowable = OrderInfo::where('order_id', $order_id)->where('shipping_status', '>', 0)->count();
            $this->smarty->assign('return_allowable', $return_allowable);

            /* 订单商品 */
            $goods_info = rec_goods($rec_id);
            $parent_cause = get_parent_cause();

            $consignee = [
                'country' => $order['country'],
                'province' => $order['province'],
                'city' => $order['city'],
                'district' => $order['district'],
                'address' => $order['address'],
                'mobile' => $order['mobile'],
                'consignee' => $order['consignee'],
                'user_id' => $order['user_id'],
                'region' => $order['region']
            ];

            $this->smarty->assign('consignee', $consignee);
            $this->smarty->assign('show_goods_thumb', $this->config['show_goods_in_cart']);      /* 增加是否在购物车里显示商品图 */
            $this->smarty->assign('show_goods_attribute', $this->config['show_attr_in_cart']);   /* 增加是否在购物车里显示商品属性 */
            $this->smarty->assign("goods", $goods_info);
            $this->smarty->assign("goods_return", $goods_info);

            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('cause_list', $parent_cause);
            $this->smarty->assign('order_sn', $order['order_sn']);
            $this->smarty->assign('order', $order);

            $country_list = $this->areaService->getRegionsLog();
            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
            $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

            $where = [
                'goods_id' => $goods_info['goods_id'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goods_info = $this->goodsService->getGoodsInfo($where);

            $goods_cause = $this->goodsCommonService->getGoodsCause($goods_info['goods_cause']);

            //获取贡云拓展数据
            $cloud_count = OrderCloud::where('rec_id', $rec_id)->count();

            //如果存在  剔除维修 换货
            if ($cloud_count > 0 && !empty($goods_cause)) {
                foreach ($goods_cause as $k => $v) {
                    if ($v['cause'] == 0 || $v['cause'] == 2) {
                        unset($goods_cause[$k]);
                    }
                }
            }

            if ($order['shipping_status'] == 0) {
                if (strstr($goods_info['goods_cause'], '3')) {
                    $goods_info['goods_cause'] = '3';
                } else {
                    $goods_info['goods_cause'] = '';
                }
            }
            $goods_cause = $this->goodsCommonService->getGoodsCause($goods_info['goods_cause']);

            $this->smarty->assign('goods_cause', $goods_cause);

            //图片列表
            $where = [
                'user_id' => $user_id,
                'rec_id' => $rec_id
            ];
            $img_list = app(OrderRefoundService::class)->getReturnImagesList($where);
            $this->smarty->assign('img_list', $img_list);

            $sn = 0;
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('street_list', $street_list);
            $this->smarty->assign('sn', $sn);
            $this->smarty->assign('sessid', SESS_ID);
            $this->smarty->assign('return_pictures', $this->config['return_pictures']);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 会员退货申请的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'apply_info') {
            load_helper('transaction');
            load_helper('order');

            if (request()->exists('goods_id')) {
                $goods_id = (int)request()->input('goods_id', 0);
                $order_id = (int)request()->input('order_id', 0);
            } else {
                /* 如果参数不存在，退出 */
                return 'invalid parameter';
            }

            $order_goods = OrderGoods::where('goods_id', $goods_id)->first();
            $order_goods = $order_goods ? $order_goods->toArray() : [];

            if ($order_goods) {
                $goods = Goods::select('goods_number', 'suppliers_id')->where('goods_id', $order_goods['goods_id']);
                $order_goods['storage'] = $goods ? $goods['goods_number'] : 0;
                $order_goods['suppliers_id'] = $goods ? $goods['suppliers_id'] : 0;

                if ($order_goods['product_id']) {
                    if ($order_goods['model_attr'] == 1) {
                        $prod = ProductsWarehouse::select('product_number', 'product_sn')->where('product_id', $order_goods['product_id']);
                    } elseif ($order_goods['model_attr'] == 2) {
                        $prod = ProductsArea::select('product_number', 'product_sn')->where('product_id', $order_goods['product_id']);
                    } else {
                        $prod = Products::select('product_number', 'product_sn')->where('product_id', $order_goods['product_id']);
                    }

                    $prod = $prod->first();
                    $prod = $prod ? $prod->toArray() : [];

                    $order_goods['storage'] = $prod ? $prod['product_number'] : 0;
                    $order_goods['product_sn'] = $prod ? $prod['product_sn'] : 0;

                    if ($goods) {
                        $brand = Brand::where('brand_id', $goods['brand_id']);
                        $order_goods['brand_name'] = $brand ? $brand['brand_name'] : '';
                    }
                }
            }

            $goods_info = $order_goods;

            $where = [
                'order_id' => $order_id
            ];
            $user_info = $this->orderService->getOrderInfo($where);

            $this->smarty->assign('lang', $GLOBALS['_LANG']);


            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());

            /* 获得用户所有的收货人信息 */
            $consignee_list = $this->userAddressService->getUserAddressList($user_id, 5);

            $this->smarty->assign('consignee_list', $consignee_list);

            $province_list = [];
            $city_list = [];
            $district_list = [];

            //取得国家列表，如果有收货人列表，取得省市区列表
            foreach ($consignee_list as $region_id => $consignee) {
                $consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 0;
                $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
                $consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : 0;

                $province_list[$region_id] = get_regions(1, $consignee['country']);
                $city_list[$region_id] = get_regions(2, $consignee['province']);
                $district_list[$region_id] = get_regions(3, $consignee['city']);
            }

            /* 获取默认收货ID */
            $address_id = Users::where('user_id', $user_id)->value('address_id');

            //赋值于模板
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('address', $address_id);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('currency_format', $this->config['currency_format']);
            $this->smarty->assign('sn', 0);

            $this->smarty->assign('user_info', $user_info);
            $this->smarty->assign('goods_info', $goods_info);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 会员退货申请的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'submit_return') {
            load_helper('transaction');
            load_helper('order');

            //判断是否重复提交申请退换货

            $rec_id = (int)request()->input('rec_id', 0);
            $last_option = request()->input('last_option', request()->input('parent_id'));
            $return_remark = addslashes(trim(request()->input('return_remark', '')));
            $return_brief = addslashes(trim(request()->input('return_brief', '')));
            $chargeoff_status = (int)request()->input('chargeoff_status', 0);

            if ($rec_id > 0) {
                $num = OrderReturn::where('rec_id', $rec_id)->count();
                if ($num > 0) {
                    return show_message($GLOBALS['_LANG']['Repeated_submission'], '', '', 'info', true);
                }
            } else {
                return show_message($GLOBALS['_LANG']['Return_abnormal'], '', '', 'info', true);
            }

            $where = [
                'rec_id' => $rec_id
            ];
            $order_goods = $this->orderService->getOrderGoodsInfo($where);

            if ($order_goods) {
                $goods = Goods::select('goods_name', 'goods_sn', 'brand_id')
                    ->where('goods_id', $order_goods['goods_id']);
                $goods = $goods->with([
                    'getBrand' => function ($query) {
                        $query->select('brand_id', 'brand_name');
                    }
                ]);

                $goods = $this->baseRepository->getToArrayFirst($goods);
                $goods = $goods && $goods['get_brand'] ? array_merge($goods, $goods['get_brand']) : $goods;
                $order_goods = $goods ? array_merge($order_goods, $goods) : $order_goods;
            }

            $where = [
                'order_id' => $order_goods ? $order_goods['order_id'] : 0
            ];
            $order_info = $this->orderService->getOrderInfo($where);

            //换货数量
            $return_num = (int)request()->input('return_num', 0);

            //换货数量
            $maintain_number = (int)request()->input('maintain_number', 0);
            $maintain_number = $return_num;//1.4.3修改，废弃，老模板用

            //退货数量
//            $back_number = (int)request()->input('attr_num', 0);
            $back_number = $return_num;//1.4.3修改，换货和退货都是一个值
            //仅退款退换所有商品
            $goods_number = (int)request()->input('return_g_number', 0);
            //退换货类型
            $return_type = (int)request()->input('return_type', 0);

            $maintain = 0;
            $return_status = 0;

            if ($return_type == 1) {
                $back = 1;
                $exchange = 0;
                $return_number = $return_num;
            } elseif ($return_type == 2) {
                $back = 0;
                $exchange = 2;
                $return_number = $back_number;
            } elseif ($return_type == 3) {
                $back = 0;
                $exchange = 0;
                $return_number = $goods_number;
                $return_status = -1;
            } else {
                $back = 0;
                $exchange = 0;
                $return_number = $maintain_number;
            }

            // 贡云售后信息
            $jigonWhere = [
                'user_id' => $user_id,
                'rec_id' => $rec_id,
                'return_type' => $return_type,
                'return_number' => $return_number,
                'return_brief' => $return_brief
            ];
            $aftersn = $this->jigonManageService->jigonAfterSales($jigonWhere);

            //获取属性ID数组
            $attr_val = request()->input('attr_val', []);
            $return_attr_id = !empty($attr_val) ? implode(',', $attr_val) : '';
            $attr_val = get_goods_attr_info_new($attr_val, 'pice'); // 换回商品属性

            $order_return = [
                'rec_id' => $rec_id,
                'goods_id' => $order_goods['goods_id'],
                'order_id' => $order_goods['order_id'],
                'order_sn' => $order_info['order_sn'],
                'chargeoff_status' => $chargeoff_status,
                'return_type' => $return_type, //唯一标识
                'maintain' => $maintain, //维修标识
                'back' => $back, //退货标识
                'exchange' => $exchange, //换货标识
                'user_id' => session('user_id'),
                'goods_attr' => $order_goods['goods_attr'],   //换出商品属性
                'attr_val' => $attr_val,
                'return_brief' => $return_brief,
                'remark' => $return_remark,
                'credentials' => (int)request()->input('credentials', 0),
                'country' => (int)request()->input('country', 0),
                'province' => (int)request()->input('province', 0),
                'city' => (int)request()->input('city', 0),
                'district' => (int)request()->input('district', 0),
                'street' => (int)request()->input('street', 0),
                'cause_id' => $last_option, //退换货原因
                'apply_time' => gmtime(),
                'actual_return' => '',
                'address' => addslashes(trim(request()->input('return_address', ''))),
                'zipcode' => request()->input('code'),
                'addressee' => addslashes(trim(request()->input('addressee', ''))),
                'phone' => addslashes(trim(request()->input('mobile', ''))),
                'return_status' => $return_status,
                'goods_bonus' => $order_goods['goods_bonus'],
                'goods_coupons' => $order_goods['goods_coupons']
            ];

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $order_return['return_rate_price'] = $order_goods['rate_price'] / $order_goods['goods_number'] * $return_number;
            }

            if (in_array($return_type, [1, 3])) {
                $return_info = get_return_refound($order_return['order_id'], $order_return['rec_id'], $return_number);
                $order_return['should_return'] = $return_info['return_price'];
                $order_return['return_shipping_fee'] = $return_info['return_shipping_fee'];
            } else {
                $order_return['should_return'] = 0;
                $order_return['return_shipping_fee'] = 0;
            }

            /* 插入订单表 */
            $order_return['return_sn'] = get_order_sn(); //获取新订单号
            $ret_id = OrderReturn::insertGetId($order_return);

            if ($ret_id) {

                app(OrderCommonService::class)->getUserOrderNumServer($order_return['user_id']);

                /* 记录log */
                return_action($ret_id, $GLOBALS['_LANG']['Apply_refund'], '', $order_return['remark'], $GLOBALS['_LANG']['buyer']);

                $return_goods['rec_id'] = $order_return['rec_id'];
                $return_goods['ret_id'] = $ret_id;
                $return_goods['goods_id'] = $order_goods['goods_id'];
                $return_goods['goods_name'] = isset($order_goods['goods_name']) ? $order_goods['goods_name'] : '';
                $return_goods['brand_name'] = isset($order_goods['brand_name']) ? $order_goods['brand_name'] : '';
                $return_goods['product_id'] = isset($order_goods['product_id']) ? $order_goods['product_id'] : 0;
                $return_goods['goods_sn'] = isset($order_goods['goods_sn']) ? $order_goods['goods_sn'] : '';
                $return_goods['is_real'] = $order_goods['is_real'];
                $return_goods['goods_attr'] = $attr_val;  //换货的商品属性名称
                $return_goods['attr_id'] = $return_attr_id; //换货的商品属性ID值
                $return_goods['refound'] = $order_goods['goods_price'];

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $return_goods['rate_price'] = $order_goods['rate_price'] / $order_goods['goods_number'];
                }

                //添加到退换货商品表中
                $return_goods['return_type'] = $return_type; //退换货
                $return_goods['return_number'] = $return_number; //退换货数量

                if ($return_type == 1) { //退货
                    $return_goods['out_attr'] = '';
                } elseif ($return_type == 2) { //换货
                    $return_goods['out_attr'] = $attr_val;
                    $return_goods['return_attr_id'] = $return_attr_id;
                } else {
                    $return_goods['out_attr'] = '';
                }

                ReturnGoods::insert($return_goods);

                $images_count = ReturnImages::where('rec_id', $rec_id)->where('user_id', $user_id)->count();

                if ($images_count > 0) {
                    $images['rg_id'] = $order_goods['goods_id'];
                    ReturnImages::where('rec_id', $rec_id)->where('user_id', $user_id)->update($images);
                }

                //退货数量插入退货表扩展表  by kong
                $order_return_extend = [
                    'ret_id' => $ret_id,
                    'return_number' => $return_number,
                    'aftersn' => $aftersn
                ];

                OrderReturnExtend::insert($order_return_extend);

                $address_detail = $order_goods['region'] . ' ' . $order_return['address'];
                $order_return['address_detail'] = $address_detail;
                $order_return['apply_time'] = local_date("Y-m-d H:i:s", $order_return['apply_time']);
                return show_message($GLOBALS['_LANG']['Apply_Success_Prompt'], $GLOBALS['_LANG']['See_Returnlist'], 'user_order.php?act=return_list', 'info', true, $order_return);
            } else {
                return show_message($GLOBALS['_LANG']['Apply_abnormal'], '', '', 'info', true);
            }
        }

        /* ------------------------------------------------------ */
        //-- 批量申请退换货
        /* ------------------------------------------------------ */
        elseif ($action == 'batch_applied') {
            load_helper('transaction');
            load_helper('order');
            /* 根据订单id或订单号查询订单信息 */
            if (request()->exists('checkboxes')) {
                $order_id = (int)request()->input('order_id', 0);

                // 检查订单商品是否包含赠品  有赠品仅支持整单退
                $gift_count = OrderGoods::where('order_id', $order_id)->where('is_gift', '>', 0)->count();
                if ($gift_count > 0) {
                    $select_count = count(request()->input('checkboxes'));
                    $goods_count = OrderGoods::where('order_id', $order_id)->count();

                    if ($select_count < $goods_count) {
                        return show_message($GLOBALS['_LANG']['only_can_return_all'], '', '', 'info', true);
                    }
                }

                $order = order_info($order_id);
                $this->err->or = 0;
                $cause_arr = '';
                $goods_info_arr = [];
                $shop_name = '';
                $rec_ids = [];
                $checkboxes = request()->input('checkboxes', []);
                foreach ($checkboxes as $key => $val) {
                    $goods = rec_goods($val);

                    $where = [
                        'goods_id' => $goods['goods_id'],
                        'warehouse_id' => $warehouse_id,
                        'area_id' => $area_id,
                        'area_city' => $area_city
                    ];
                    $goods_info = $this->goodsService->getGoodsInfo($where);

                    if (empty($cause_arr)) {
                        $cause_arr = explode(",", $goods_info['goods_cause']);
                    }
                    $cause_arr_next = explode(",", $goods_info['goods_cause']);

                    if (!$goods_info['goods_cause'] || $goods_info['extension_code'] == 'virtual_card') {
                        $this->err->or += 1;
                    } else {
                        if ($cause_arr) {
                            $cause_arr = array_intersect($cause_arr, $cause_arr_next); //比较数组返回交集
                        }
                        $goods_info_arr[$key] = $goods; //订单商品
                        $shop_name = $goods['user_name'];
                        $rec_ids[] = $goods['rec_id'];
                    }
                }

                if ($this->err->or) {
                    return show_message($GLOBALS['_LANG']['nonsupport_return_goods'], '', '', 'info', true);
                } else {
                    $cause_str = implode(",", $cause_arr);
                    if ($order['shipping_status'] == 0) {
                        if (in_array(3, $cause_arr)) {
                            $cause_str = '3';
                        } else {
                            $cause_str = '';
                        }
                    }
                    $goods_cause = $this->goodsCommonService->getGoodsCause($cause_str);
                }
            } else {
                return show_message($GLOBALS['_LANG']['please_select_goods'], '', '', 'info', true);
            }

            $parent_cause = get_parent_cause();

            $consignee = $this->flowUserService->getConsignee(session('user_id'));
            $this->smarty->assign('consignee', $consignee);
            $this->smarty->assign('show_goods_thumb', $this->config['show_goods_in_cart']);      /* 增加是否在购物车里显示商品图 */
            $this->smarty->assign('show_goods_attribute', $this->config['show_attr_in_cart']);   /* 增加是否在购物车里显示商品属性 */
            $this->smarty->assign("goods", $goods_info_arr);
            $this->smarty->assign("goods_return", $goods_info_arr);
            $this->smarty->assign("shop_name", $shop_name);

            $rec_ids = $rec_ids ? implode("-", $rec_ids) : '';
            $this->smarty->assign("rec_ids", $rec_ids);
            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('cause_list', $parent_cause);
            $this->smarty->assign('order_sn', $order['order_sn']);
            $this->smarty->assign('order', $order);

            $country_list = $this->areaService->getRegionsLog(0, 0);
            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
            $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

            /* 退换货标志列表 */
            $this->smarty->assign('goods_cause', $goods_cause);

            $sn = 0;
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('street_list', $street_list);
            $this->smarty->assign('sn', $sn);
            $this->smarty->assign('sessid', SESS_ID);
            $this->smarty->assign('return_pictures', $this->config['return_pictures']);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批量处理会员退货申请
        /* ------------------------------------------------------ */
        elseif ($action == 'submit_batch_return') {
            load_helper('transaction');
            load_helper('order');

            $return_rec_id = request()->input('return_rec_id', []);
            if (!empty($return_rec_id)) {
                //表单传值
                $return_remark = addslashes(trim(request()->input('return_remark', '')));
                $return_brief = addslashes(trim(request()->input('return_brief', '')));
                $chargeoff_status = (int)request()->input('chargeoff_status', 0);
                $last_option = request()->input('last_option', request()->input('parent_id', ''));
                $attr_val = request()->input('attr_val', []);

                $return_attr_id = !empty($attr_val) ? implode(',', $attr_val) : '';

                foreach ($return_rec_id as $rec_id) {
                    //判断是否重复提交申请退换货
                    if ($rec_id > 0) {
                        $num = OrderReturn::where('rec_id', $rec_id)->count();

                        if ($num > 0) {
                            return show_message($GLOBALS['_LANG']['Repeated_submission'], '', '', 'info', true);
                        }
                    } else {
                        return show_message($GLOBALS['_LANG']['Return_abnormal'], '', '', 'info', true);
                    }

                    $where = [
                        'rec_id' => $rec_id
                    ];
                    $order_goods = $this->orderService->getOrderGoodsInfo($where);

                    if ($order_goods) {
                        $goods = Goods::select('goods_name', 'goods_sn', 'brand_id')->where('goods_id', $order_goods['goods_id'])->first();
                        $goods = $goods ? $goods->toArray() : [];
                        $order_goods = $goods ? array_merge($order_goods, $goods) : $order_goods;
                    }

                    $return_number = $goods_number = $order_goods['goods_number']; //批量退回所有商品

                    //退换货类型
                    $return_type = (int)request()->input('return_type', 0);
                    $maintain = 0;
                    $return_status = 0;

                    if ($return_type == 1) {
                        $back = 1;
                        $exchange = 0;
                    } elseif ($return_type == 2) {
                        $back = 0;
                        $exchange = 2;
                    } elseif ($return_type == 3) {
                        $back = 0;
                        $exchange = 0;
                        $return_status = -1;
                    } else {
                        $back = 0;
                        $exchange = 0;
                    }

                    // 贡云售后信息
                    $jigonWhere = [
                        'user_id' => $user_id,
                        'rec_id' => $rec_id,
                        'return_type' => $return_type,
                        'return_number' => $return_number,
                        'return_brief' => $return_brief
                    ];
                    $aftersn = $this->jigonManageService->jigonAfterSales($jigonWhere);

                    $order_return = [
                        'rec_id' => $rec_id,
                        'goods_id' => $order_goods['goods_id'],
                        'order_id' => $order_goods['order_id'],
                        'order_sn' => $order_goods['goods_sn'],
                        'chargeoff_status' => $chargeoff_status,
                        'return_type' => $return_type, //唯一标识
                        'maintain' => $maintain, //维修标识
                        'back' => $back, //退货标识
                        'exchange' => $exchange, //换货标识
                        'user_id' => session('user_id'),
                        'return_brief' => $return_brief,
                        'remark' => $return_remark,
                        'credentials' => (int)request()->input('credentials', 0),
                        'country' => (int)request()->input('country', 0),
                        'province' => (int)request()->input('province', 0),
                        'city' => (int)request()->input('city', 0),
                        'district' => (int)request()->input('district', 0),
                        'street' => (int)request()->input('street', 0),
                        'cause_id' => $last_option, //退换货原因
                        'apply_time' => gmtime(),
                        'actual_return' => '',
                        'address' => addslashes(trim(request()->input('return_address', ''))),
                        'zipcode' => request()->input('code'),
                        'addressee' => addslashes(trim(request()->input('addressee', ''))),
                        'phone' => addslashes(trim(request()->input('mobile', ''))),
                        'return_status' => $return_status,
                        'goods_bonus' => $order_goods['goods_bonus'],
                        'goods_coupons' => $order_goods['goods_coupons']
                    ];

                    if (CROSS_BORDER === true) // 跨境多商户
                    {
                        $order_return['return_rate_price'] = $order_goods['rate_price'] / $order_goods['goods_number'] * $return_number;
                    }

                    if (in_array($return_type, [1, 3])) {
                        $return_info = get_return_refound($order_return['order_id'], $order_return['rec_id'], $return_number);
                        $order_return['should_return'] = $return_info['return_price'];
                        $order_return['return_shipping_fee'] = $return_info['return_shipping_fee'];
                    } else {
                        $order_return['should_return'] = 0;
                        $order_return['return_shipping_fee'] = 0;
                    }

                    /* 插入订单表 */
                    $this->err->or_no = 0;
                    do {
                        $order_return['return_sn'] = get_order_sn(); //获取新订单号
                        $ret_id = OrderReturn::insertGetId($order_return);
                    } while ($this->err->or_no == 1062); //如果是订单号重复则重新提交数据

                    if ($ret_id) {

                        app(OrderCommonService::class)->getUserOrderNumServer($order_return['user_id']);

                        /* 记录log */
                        return_action($ret_id, $GLOBALS['_LANG']['Apply_refund'], '', $order_return['remark'], $GLOBALS['_LANG']['buyer']);

                        $return_goods['rec_id'] = $order_return['rec_id'];
                        $return_goods['ret_id'] = $ret_id;
                        $return_goods['goods_id'] = $order_goods['goods_id'];
                        $return_goods['goods_name'] = isset($order_goods['goods_name']) ? $order_goods['goods_name'] : '';
                        $return_goods['brand_name'] = isset($order_goods['brand_name']) ? $order_goods['brand_name'] : '';
                        $return_goods['product_id'] = isset($order_goods['product_id']) ? $order_goods['product_id'] : 0;
                        $return_goods['goods_sn'] = isset($order_goods['goods_sn']) ? $order_goods['goods_sn'] : '';
                        $return_goods['is_real'] = $order_goods['is_real'];
                        $return_goods['goods_attr'] = (isset($attr_val) && !empty($attr_val)) ? $attr_val : '';  //换货的商品属性名称
                        $return_goods['attr_id'] = isset($return_attr_id) ? $return_attr_id : ''; //换货的商品属性ID值
                        $return_goods['refound'] = isset($order_goods['goods_price']) ? $order_goods['goods_price'] : 0;

                        //添加到退换货商品表中
                        $return_goods['return_type'] = $return_type; //退换货
                        $return_goods['return_number'] = $return_number; //退换货数量

                        if ($return_type == 1) { //退货
                            $return_goods['out_attr'] = '';
                        } elseif ($return_type == 2) { //换货
                            $return_goods['out_attr'] = (isset($attr_val) && !empty($attr_val)) ? $attr_val : '';
                            $return_goods['return_attr_id'] = $return_attr_id;
                        } else {
                            $return_goods['out_attr'] = '';
                        }
                        ReturnGoods::insert($return_goods);

                        $images_count = ReturnImages::where('rec_id', $rec_id)->where('user_id', $user_id)->count();

                        if ($images_count > 0) {
                            $images['rg_id'] = $order_goods['goods_id'];
                            ReturnImages::where('rec_id', $rec_id)->where('user_id', $user_id)->update($images);
                        }
                        //退货数量插入退货表扩展表  by kong
                        $order_return_extend = [
                            'ret_id' => $ret_id,
                            'return_number' => $return_number,
                            'aftersn' => $aftersn
                        ];
                        OrderReturnExtend::insert($order_return_extend);

                        $address_detail = $order_goods['region'] . ' ' . $order_return['address'];
                        $order_return['address_detail'] = $address_detail;
                        $order_return['apply_time'] = local_date("Y-m-d H:i:s", $order_return['apply_time']);
                    } else {
                        return show_message($GLOBALS['_LANG']['Apply_abnormal'], '', '', 'info', true);
                    }
                }
                return show_message($GLOBALS['_LANG']['Apply_Success_Prompt'], $GLOBALS['_LANG']['See_Returnlist'], 'user_order.php?act=return_list', 'info', true);
            }
        }

        /* ------------------------------------------------------ */
        //-- 退换货订单
        /* ------------------------------------------------------ */
        elseif ($action == 'return_list') {
            load_helper('transaction');
            load_helper('order');


            $page = (int)request()->input('page', 1);
            $size = 10;

            $count = OrderReturn::where('user_id', $user_id);
            $count = $count->whereHas('getGoods', function ($query) {
                $query->select('goods_id')->where('goods_id', '>', 0);
            });
            $record_count = $count->count();

            $pager = get_pager('user.php', ['act' => $action], $record_count, $page, $size);

            $return_list = return_order($size, $pager['start']);

            $this->smarty->assign('orders', $return_list);
            $this->smarty->assign('pager', $pager);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 取消退换货订单
        /* ------------------------------------------------------ */
        elseif ($action == 'cancel_return') {
            load_helper(['transaction', 'order']);
            $ret_id = (int)request()->input('ret_id', 0);

            if (cancel_return($ret_id, $user_id)) {
                return dsc_header("Location: user_order.php?act=return_list\n");
            } else {
                return $this->err->show($GLOBALS['_LANG']['return_list_lnk'], 'user_order.php?act=return_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 换货确认收货
        /* ------------------------------------------------------ */
        elseif ($action == 'return_delivery') {
            load_helper(['transaction', 'order']);

            $order_id = (int)request()->input('order_id', 0);

            if (affirm_return_received($order_id, $user_id)) {
                return dsc_header("Location: user_order.php?act=return_list\n");
            } else {
                return $this->err->show($GLOBALS['_LANG']['return_list_lnk'], 'user_order.php?act=return_list');
            }
        }

        /* ------------------------------------------------------ */
        //-- 确定激活该退换货订单
        /* ------------------------------------------------------ */
        elseif ($action == 'activation_return_order') {
            $res = ['err_msg' => '', 'result' => '', 'error' => 0];

            $ret_id = (int)request()->input('ret_id', 0);
            $activation_number_type = (intval($this->config['activation_number_type']) > 0) ? intval($this->config['activation_number_type']) : 2;

            $activation_number = OrderReturn::where('ret_id', $ret_id)->value('activation_number');

            if ($activation_number_type > $activation_number) {
                $other = [
                    'return_status' => 0
                ];
                OrderReturn::where('ret_id', $ret_id)->increment('activation_number', 1, $other);
            } else {
                $res['error'] = 1;
                $res['err_msg'] = sprintf($GLOBALS['_LANG']['activation_number_msg'], $activation_number_type);
            }

            return response()->json($res);
        }

        /* ------------------------------------------------------ */
        //-- 退货订单详情
        /* ------------------------------------------------------ */
        elseif ($action == 'return_detail') {
            load_helper(['transaction', 'order']);
            $ret_id = (int)request()->input('ret_id', 0);

            /* 订单详情 */
            $order = get_return_detail($ret_id);

            if ($order === false) {
                return $this->err->show($GLOBALS['_LANG']['back_home_lnk'], './');
            }

            $where = [
                'order_id' => $order['order_id']
            ];
            $shippinOrderInfo = $this->orderService->getOrderGoodsInfo($where);

            //快递公司
            $region = [$order['country'], $order['province'], $order['city'], $order['district']];
            $shipping_list = available_shipping_list($region, $shippinOrderInfo);

            if ($shipping_list) {
                foreach ($shipping_list as $key => $val) {
                    $shipping_cfg = unserialize_config($val['configure']);
                    $shipping_fee = 0;
                    $val['insure'] = isset($val['insure']) ? $val['insure'] : 0;
                    $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
                    $shipping_list[$key]['shipping_fee'] = $shipping_fee;
                    $shipping_list[$key]['free_money'] = price_format($shipping_cfg['free_money'], false);
                    $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure'];
                }
            }

            //修正退货单状态
            if ($order['return_type'] == 0) {
                if ($order['return_status1'] == 4) {
                    $order['refound_status1'] = FF_MAINTENANCE;
                } else {
                    $order['refound_status1'] = FF_NOMAINTENANCE;
                }
            } elseif ($order['return_type'] == 1) {
                if ($order['refound_status1'] == 1) {
                    $order['refound_status1'] = FF_REFOUND;
                } else {
                    $order['refound_status1'] = FF_NOREFOUND;
                }
            } elseif ($order['return_type'] == 2) {
                if ($order['return_status1'] == 4) {
                    $order['refound_status1'] = FF_EXCHANGE;
                } else {
                    $order['refound_status1'] = FF_NOEXCHANGE;
                }
            }

            // 取得退换货商品客户上传图片凭证
            $this->smarty->assign('shipping_list', $shipping_list);

            //获取退换货扩展信息
            $aftersn = OrderReturnExtend::where('ret_id', $ret_id)->value('aftersn');

            //如果存在贡云退换货信息  获取退换货地址
            $return_info = $aftersn ? $this->jigonManageService->jigonRefundAddress(['afterSn' => $aftersn]) : [];

            $this->smarty->assign('return_info', $return_info);

            $this->smarty->assign('goods', $order);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 编辑退换货快递信息
        /* ------------------------------------------------------ */
        elseif ($action == 'edit_express') {

            $ret_id = request()->input('ret_id', '');
            $order_id = request()->input('order_id', '');
            $back_shipping_name = request()->input('express_name', '');
            $back_other_shipping = request()->input('other_express', '');
            $back_invoice_no = request()->input('express_sn', '');

            $other = [
                'back_shipping_name' => $back_shipping_name,
                'back_other_shipping' => $back_other_shipping,
                'back_invoice_no' => $back_invoice_no
            ];
            OrderReturn::where('ret_id', $ret_id)->update($other);

            return show_message(lang('user.edit_shipping_success'), lang('user.return_info'), 'user_order.php?act=return_detail&order_id=' . $order_id . '&ret_id=' . $ret_id);
        }

        /* ------------------------------------------------------ */
        //-- 选择退换货原因
        /* ------------------------------------------------------ */
        elseif ($action == 'ajax_select_cause') {

            $res = ['error' => 0, 'message' => '', 'option' => '', 'rec_id' => 0];
            $c_id = (int)request()->input('c_id', 0);
            $rec_id = (int)request()->input('rec_id', 0);

            if (isset($c_id) && isset($rec_id)) {
                $result = ReturnCause::where('parent_id', $c_id)->where('is_show', 1)->orderBy('sort_order')->get();
                $result = $result ? $result->toArray() : [];

                if ($result) {
                    $select = '<select name="last_option" id="last_option_' . $rec_id . '">';
                    foreach ($result as $var) {
                        $select .= '<option value="' . $var['cause_id'] . '" ';
                        $select .= '';
                        $select .= '>';
                        $select .= htmlspecialchars(addslashes($var['cause_name']), ENT_QUOTES) . '</option>';
                    }
                    $select .= '</select>';

                    $res['option'] = $select;
                    $res['rec_id'] = $rec_id;
                }

                return response()->json($res);
            } else {
                $res['error'] = 100;
                $res['message'] = '';
                return response()->json($res);
            }
        }

        /* ------------------------------------------------------ */
        //-- 微信支付改变状态
        /* ------------------------------------------------------ */
        elseif ($action == 'checkorder') {

            $pay_code = addslashes(trim(request()->input('pay_code', '')));
            $baitiao_id = (int)request()->input('baitiao_id', 0);
            $log_id = (int)request()->input('log_id', 0);
            $stages_num = (int)request()->input('stages_num', 0);

            if ($baitiao_id) {
                /* 已还款 */
                $where_pay_other = [
                    'baitiao_id' => $baitiao_id,
                    'log_id' => $log_id,
                    'stages_num' => $stages_num,
                    'is_pay' => 1
                ];
                $bt_pay_log_info = $this->userBaitiaoService->getBaitiaoPayLogInfo($where_pay_other);

                if ($bt_pay_log_info) {
                    $result = ['code' => 1, 'pay_code' => $pay_code];
                } else {
                    $result = ['code' => 0, 'pay_code' => $pay_code];
                }
            } else {
                //其他
                $count = PayLog::where('log_id', $log_id)->where('is_paid', 1)->count();

                if ($count) {
                    $result = ['code' => 1, 'pay_code' => $pay_code];
                } else {
                    $result = ['code' => 0, 'pay_code' => $pay_code];
                }
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 自动确认收货
        /* ------------------------------------------------------ */
        elseif ($action == 'return_order_status') {
            $res = ['result' => '', 'error' => 0, 'msg' => ''];
            $order_id = (int)request()->input('order_id', 0);

            if (session()->has('user_id') && !empty(session('user_id'))) {
                $noTime = gmtime();
                /* 查询订单信息，检查状态 */
                $where = [
                    'order_id' => $order_id,
                    'user_id' => session('user_id'),
                ];
                $order = $this->orderService->getOrderInfo($where);

                if ($this->config['open_delivery_time'] == 1) {
                    if (($order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED) && $order['shipping_status'] == SS_SHIPPED) { //发货状态
                        $delivery_time = $order['shipping_time'] + 24 * 3600 * $order['auto_delivery_time'];

                        //自动确认发货操作
                        if ($noTime >= $delivery_time) {
                            $confirm_take_time = gmtime();
                            $other = [
                                'order_status' => OS_SPLITED,
                                'shipping_status' => SS_RECEIVED,
                                'confirm_take_time' => $confirm_take_time
                            ];
                            $res = OrderInfo::where('order_id', $order_id)->update($other);

                            if ($res) {

                                /* 更新会员订单信息 */
                                $dbRaw = [
                                    'order_nogoods' => "order_nogoods - 1",
                                    'order_isfinished' => "order_isfinished + 1"
                                ];
                                $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
                                UserOrderNum::where('user_id', session('user_id'))->update($dbRaw);

                                /* 记录日志 */
                                $note = lang('user.self_motion_goods');
                                order_action($order['order_sn'], OS_SPLITED, SS_RECEIVED, $order['pay_status'], $note, lang('common.buyer'), 0, $confirm_take_time);

                                $seller_id = OrderGoods::where('order_id', $order_id)->value('ru_id');
                                $value_card = ValueCardRecord::where('order_id', $order_id)->value('use_val');

                                $return_amount_info = $this->orderRefoundService->orderReturnAmount($order_id);

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
                                }
                            }

                            $res['ss_received'] = lang('user.ss_received');
                            $res['error'] = 1;

                            $res['msg'] = "<a href='user_order.php?act=order_detail&amp;order_id=" . $order_id . "' class='sc-btn'>" . lang('user.order_detail') . "</a>" .
                                "<a href='javascript:get_order_delete_restore('delete', " . $order_id . ");' class='sc-btn'>" . lang('user.delete_order') . "</a>" .
                                "<a href='user_message.php?act=commented_view&amp;order_id=" . $order_id . "' class='sc-btn'>" . lang('user.single_comment') . "</a>";
                        }
                    }
                }
            }

            return response()->json($res);
        }

        /* ------------------------------------------------------ */
        //-- 将指定订单中商品添加到购物车
        /* ------------------------------------------------------ */
        elseif ($action == 'return_to_cart') {
            load_helper('transaction');
            $request = get_request_filter(request()->all(), 1);

            $result = ['error' => 0, 'message' => '', 'cart_info' => ''];
            $order_id = isset($request['order_id']) ? intval($request['order_id']) : 0;
            $recStr = isset($request['rec_id']) ? trim($request['rec_id']) : ''; //订单商品编号
            $rec_id = [];

            if ($order_id == 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['order_id_empty'];
                return response()->json($result);
            }

            if ($user_id == 0) {
                /* 用户没有登录 */
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['login_please'];
                return response()->json($result);
            }

            if (!empty($recStr)) {
                $rec_id = explode(",", $recStr);
            }

            /* 检查订单是否属于该用户 */
            $order_user = OrderInfo::where('order_id', $order_id)->value('user_id');

            if (empty($order_user)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['order_exist'];
                return response()->json($result);
            } else {
                if ($order_user != $user_id) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['no_priv'];
                    return response()->json($result);
                }
            }

            $message = $this->cartGoodsService->returnToCart($order_id, $rec_id);

            //调用购物车信息
            $cart_info = $this->cartService->getUserCartInfo();
            $result['cart_info'] = $cart_info;

            if ($message === true) {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['return_to_cart_success'];
                return response()->json($result);
            } else {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['order_exist'];
                return response()->json($result);
            }
        }

        /* ------------------------------------------------------ */
        //-- 确认收货
        /* ------------------------------------------------------ */
        elseif ($action == 'affirm_received') {
            load_helper('transaction');

            $order_id = (int)request()->input('order_id', 0);
            $act = request()->input('action', '');

            if (affirm_received($order_id, $user_id)) {

                //获取店铺客服电话
                if ($this->config['sms_order_received'] == '1') {
                    $order_info = $this->orderService->getOrderInfo(['order_id' => $order_id]);
                    //获取店铺客服电话
                    $seller_shop_info = SellerShopinfo::where('ru_id', $order_info['ru_id']);
                    $seller_shop_info = $this->baseRepository->getToArrayFirst($seller_shop_info);
                    //阿里大鱼短信接口参数
                    if (isset($seller_shop_info['mobile']) && $seller_shop_info['mobile']) {
                        $smsParams = [
                            'ordersn' => $order_info['order_sn'],
                            'consignee' => $order_info['consignee'],
                            'ordermobile' => $order_info['mobile']
                        ];

                        $this->commonRepository->smsSend($seller_shop_info['mobile'], $smsParams, 'sms_order_received');
                    }
                }

                //更改智能权重里的商品统计数量
                $res = OrderGoods::select('goods_id', 'goods_number')->where('order_id', $order_id)->get();
                $res = $res ? $res->toArray() : [];

                if ($res) {
                    foreach ($res as $val) {
                        $user_number = OrderGoods::where('goods_id', $val['goods_id'])->groupBy('user_id')->get();
                        $user_number = $user_number ? $user_number->toArray() : [];
                        $user_number = $user_number ? count($user_number) : 0;

                        $num = ['goods_number' => $val['goods_number'], 'goods_id' => $val['goods_id'], 'user_number' => $user_number];
                        update_manual($val['goods_id'], $num);
                    }
                }

                if ($act == 'auction') {
                    return dsc_header("Location: user_activity.php?act=auction\n");
                } elseif ($act == 'crowdfunding') {
                    return dsc_header("Location: user_crowdfund.php?act=crowdfunding\n");
                } elseif ($act == 'info') {
                    return dsc_header("Location: user_order.php?act=order_detail&order_id=$order_id\n");
                } else {
                    return dsc_header("Location: user_order.php?act=order_list\n");
                }
            } else {
                if ($act == 'auction') {
                    return $this->err->show($GLOBALS['_LANG']['order_list_lnk'], 'user_activity.php?act=auction');
                } elseif ($act == 'crowdfunding') {
                    return $this->err->show($GLOBALS['_LANG']['order_list_lnk'], 'user_crowdfund.php?act=crowdfunding');
                } else {
                    return $this->err->show($GLOBALS['_LANG']['order_list_lnk'], 'user_order.php?act=order_list');
                }
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除已完成退换货订单
        /* ------------------------------------------------------ */
        elseif ($action == 'order_delete_return') {
            $order = strip_tags(urldecode(request()->input('order', '')));
            $order = json_str_iconv($order);

            $result = ['error' => 0, 'content' => '', 'order_id' => '', 'pager' => ''];
            $order = dsc_decode($order);

            $order_id = $order->order_id;
            $result['order_id'] = $order_id;

            $res = OrderReturn::where('user_id', $user_id)->where('ret_id', $result['order_id'])->detele();

            if ($res) {
                $return_list = return_order();
                $this->smarty->assign('orders', $return_list);
                $page = (int)request()->input('page', 1);
                $record_count = OrderReturn::where('user_id', $user_id)->count();
                $action = 'order_return';
                $result['pager'] = get_pager('user.php', ['act' => $action], $record_count, $page);
                $result['content'] = $this->smarty->fetch("library/user_return_order_list.lbi");
                return response()->json($result);
            }
        }

    }
}
