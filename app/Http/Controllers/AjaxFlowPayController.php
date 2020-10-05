<?php

namespace App\Http\Controllers;

use App\Models\UsersPaypwd;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Flow\FlowRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Common\AreaService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Flow\FlowActivityService;
use App\Services\Flow\FlowUserService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;

class AjaxFlowPayController extends InitController
{
    protected $areaService;
    protected $dscRepository;
    protected $sessionRepository;
    protected $flowRepository;
    protected $paymentService;
    protected $flowUserService;
    protected $orderService;
    protected $baseRepository;
    protected $config;
    protected $cartCommonService;
    protected $flowActivityService;

    public function __construct(
        AreaService $areaService,
        DscRepository $dscRepository,
        SessionRepository $sessionRepository,
        FlowRepository $flowRepository,
        PaymentService $paymentService,
        FlowUserService $flowUserService,
        OrderService $orderService,
        BaseRepository $baseRepository,
        CartCommonService $cartCommonService,
        FlowActivityService $flowActivityService
    )
    {
        $this->areaService = $areaService;
        $this->dscRepository = $dscRepository;
        $this->sessionRepository = $sessionRepository;
        $this->flowRepository = $flowRepository;
        $this->paymentService = $paymentService;
        $this->flowUserService = $flowUserService;
        $this->orderService = $orderService;
        $this->baseRepository = $baseRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->cartCommonService = $cartCommonService;
        $this->flowActivityService = $flowActivityService;
    }

    public function index()
    {
        load_helper('order');

        /* 载入语言文件 */
        $this->dscRepository->helpersLang(['flow', 'user', 'shopping_flow']);

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

        $session_id = $this->sessionRepository->realCartMacIp();
        $user_id = session('user_id', 0);

        $cart_value = $this->cartCommonService->getCartValue();

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $step = addslashes(trim(request()->input('step', '')));
        /*------------------------------------------------------ */
        //-- 改变支付方式
        /*------------------------------------------------------ */
        if ($step == 'select_payment') {

            $result = ['error' => 0, 'massage' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1];

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));
            $store_id = intval(request()->input('store_id', 0));

            $store_seller = addslashes(request()->input('store_seller', ''));

            $store_seller = ($store_id > 0) ? 'store_seller' : $store_seller;

            $shipping_id = strip_tags(urldecode(request()->input('shipping_id', '')));
            $tmp_shipping_id_arr = dsc_decode($shipping_id, true);

            $this->smarty->assign('store_id', $store_id);
            $this->smarty->assign('seller_store', $store_seller);

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            if ($store_id > 0) {

                /* 对商品信息赋值 */
                $cart_goods_list = cart_goods($flow_type, $cart_value, 1, $warehouse_id, $area_id, $area_city, '', $store_id); // 取得商品列表，计算合计
                $cart_goods_list_new = cart_by_favourable($cart_goods_list);

                $cart_goods = $this->flowRepository->getNewGroupCartGoods($cart_goods_list_new);
                if (empty($cart_goods)) {
                    if (empty($cart_goods)) {
                        $result['error'] = 1;
                    }
                } else {
                    /* 取得购物流程设置 */
                    $this->smarty->assign('config', $GLOBALS['_CFG']);

                    /* 取得订单信息 */
                    $order = flow_order_info();
                    $order['pay_id'] = intval(request()->input('payment', 0));

                    $where = [
                        'pay_id' => $order['pay_id'],
                        'enabled' => 1
                    ];
                    $payment_info = $this->paymentService->getPaymentInfo($where);

                    $result['pay_code'] = $payment_info['pay_code'];

                    /* 保存 session */
                    session([
                        'flow_order' => $order
                    ]);

                    $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
                    $this->smarty->assign('cart_goods_number', $cart_goods_number);

                    $this->smarty->assign('goods_list', $cart_goods_list_new);

                    //切换配送方式
                    $cart_goods_list = get_flowdone_goods_list($cart_goods_list, $tmp_shipping_id_arr);

                    /* 计算订单的费用 */
                    $total = order_fee($order, $cart_goods, '', 0, $cart_value, 0, $cart_goods_list, 0, 0, $store_id, $store_seller);
                    if (CROSS_BORDER === true) // 跨境多商户
                    {
                        $web = app(CrossBorderService::class)->webExists();

                        if (!empty($web)) {
                            $arr = [
                                'consignee' => $consignee ?? '',
                                'rec_type' => $flow_type ?? 0,
                                'store_id' => $store_id ?? 0,
                                'cart_value' => $cart_value ?? '',
                                'type' => $type ?? 0,
                                'uc_id' => $order['uc_id'] ?? 0
                            ];
                            $amount = $web->assignNewRatePrice($cart_goods_list, $total['amount'], $arr);
                            $total['amount'] = $amount['amount'];
                            $total['amount_formated'] = $amount['amount_formated'];
                        } else {
                            return response()->json(['error' => 1, 'message' => 'service not exists']);
                        }
                    }
                    $this->smarty->assign('total', $total);

                    /* 取得可以得到的积分和红包 */
                    $cartWhere = [
                        'user_id' => $user_id,
                        'session_id' => $session_id,
                        'include_gift' => false,
                        'rec_type' => $flow_type,
                        'cart_value' => $cart_value
                    ];
                    $cart_total = $this->cartCommonService->getCartAmount($cartWhere);

                    $this->smarty->assign('total_integral', $cart_total - $total['bonus'] - $total['integral_money']);

                    $total_bonus = $this->flowActivityService->getTotalBonus();
                    $this->smarty->assign('total_bonus', price_format($total_bonus, false));

                    //有存在虚拟和实体商品 start
                    get_goods_flow_type($cart_value);
                    //有存在虚拟和实体商品 end

                    $result['content'] = $this->smarty->fetch('library/order_total.lbi');
                }
            } else {

                /* 获得收货人信息 */
                $consignee = $this->flowUserService->getConsignee(session('user_id'));

                /* 对商品信息赋值 */
                $cart_goods_list = cart_goods($flow_type, $cart_value, 1, $warehouse_id, $area_id, $area_city); // 取得商品列表，计算合计
                $cart_goods_list_new = cart_by_favourable($cart_goods_list);

                $cart_goods = $this->flowRepository->getNewGroupCartGoods($cart_goods_list_new);
                if (empty($cart_goods) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                    //ecmoban模板堂 --zhuo start
                    if (empty($cart_goods)) {
                        $result['error'] = 1;
                    } elseif (!$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                        $result['error'] = 2;
                    }
                    //ecmoban模板堂 --zhuo end
                } else {
                    /* 取得购物流程设置 */
                    $this->smarty->assign('config', $GLOBALS['_CFG']);

                    /* 取得订单信息 */
                    $order = flow_order_info();
                    $order['surplus'] = 0;

                    $order['pay_id'] = intval(request()->input('payment', 0));

                    $where = [
                        'pay_id' => $order['pay_id']
                    ];
                    $payment_info = $this->paymentService->getPaymentInfo($where);

                    $result['pay_code'] = $payment_info['pay_code'] ?? '';

                    /* 保存 session */
                    session([
                        'flow_order' => $order
                    ]);

                    $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
                    $this->smarty->assign('cart_goods_number', $cart_goods_number);

                    $consignee['province_name'] = get_goods_region_name($consignee['province']);
                    $consignee['city_name'] = get_goods_region_name($consignee['city']);
                    $consignee['district_name'] = get_goods_region_name($consignee['district']);
                    $consignee['street_name'] = get_goods_region_name($consignee['street']);
                    $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];
                    $this->smarty->assign('consignee', $consignee);

                    $this->smarty->assign('goods_list', $cart_goods_list_new);

                    //切换配送方式
                    $cart_goods_list = get_flowdone_goods_list($cart_goods_list, $tmp_shipping_id_arr);

                    /* 计算订单的费用 */
                    $total = order_fee($order, $cart_goods, $consignee, 0, $cart_value, 0, $cart_goods_list, 0, 0, $store_id, $store_seller);
                    if (CROSS_BORDER === true) // 跨境多商户
                    {
                        $web = app(CrossBorderService::class)->webExists();

                        if (!empty($web)) {
                            $arr = [
                                'consignee' => $consignee ?? '',
                                'rec_type' => $flow_type ?? 0,
                                'store_id' => $store_id ?? 0,
                                'cart_value' => $cart_value ?? '',
                                'type' => $type ?? 0,
                                'uc_id' => $order['uc_id'] ?? 0
                            ];
                            $amount = $web->assignNewRatePrice($cart_goods_list, $total['amount'], $arr);
                            $total['amount'] = $amount['amount'];
                            $total['amount_formated'] = $amount['amount_formated'];
                        } else {
                            return response()->json(['error' => 1, 'message' => 'service not exists']);
                        }
                    }
                    $this->smarty->assign('total', $total);

                    /* 取得可以得到的积分和红包 */
                    $cartWhere = [
                        'user_id' => $user_id,
                        'session_id' => $session_id,
                        'include_gift' => false,
                        'rec_type' => $flow_type,
                        'cart_value' => $cart_value
                    ];
                    $cart_total = $this->cartCommonService->getCartAmount($cartWhere);

                    $this->smarty->assign('total_integral', $cart_total - $total['bonus'] - $total['integral_money']);

                    $total_bonus = $this->flowActivityService->getTotalBonus();
                    $this->smarty->assign('total_bonus', price_format($total_bonus, false));

                    /* 团购标志 */
                    if ($flow_type == CART_GROUP_BUY_GOODS) {
                        $this->smarty->assign('is_group_buy', 1);
                    } elseif ($flow_type == CART_EXCHANGE_GOODS) {
                        // 积分兑换 qin
                        $this->smarty->assign('is_exchange_goods', 1);
                    }

                    //有存在虚拟和实体商品 start
                    get_goods_flow_type($cart_value);
                    //有存在虚拟和实体商品 end

                    $result['content'] = $this->smarty->fetch('library/order_total.lbi');
                }
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 微信支付改变状态
        /*------------------------------------------------------ */
        elseif ($step == 'checkorder') {
            $order_id = intval(request()->input('order_id', 0));

            $where = [
                'order_id' => $order_id
            ];
            $order_info = $this->orderService->getOrderInfo($where);

            $where = [
                'pay_id' => $order_info['pay_id'] ?? 0,
                'enabled' => 1
            ];
            $pay = $this->paymentService->getPaymentInfo($where);
            if ($pay) {
                //已付款
                if ($order_info && $order_info['pay_status'] == PS_PAYED || $order_info['pay_status'] == PS_PAYED_PART) {
                    $json = ['code' => 1, 'pay_name' => $pay['pay_name'], 'pay_code' => $pay['pay_code']];
                    return response()->json($json);
                } else {
                    $json = ['code' => 0, 'pay_name' => $pay['pay_name'], 'pay_code' => $pay['pay_code']];
                    return response()->json($json);
                }
            }

            return response()->json(['code' => 0]);
        }

        /*------------------------------------------------------ */
        //-- 验证支付密码
        /*------------------------------------------------------ */
        elseif ($step == 'pay_pwd') {
            $res = ['error' => 0, 'err_msg' => '', 'content' => ''];

            $pay_pwd = addslashes(request()->input('pay_pwd', ''));

            // 验证用户支付密码
            $users_paypwd = UsersPaypwd::where('user_id', $user_id);
            $users_paypwd = $this->baseRepository->getToArrayFirst($users_paypwd);

            if ($this->config['use_paypwd'] == 1) {
                // 加密因子
                $ec_salt = $users_paypwd ? $users_paypwd['ec_salt'] : 0;
                $new_password = md5(md5($pay_pwd) . $ec_salt);

                if (empty($pay_pwd)) {
                    $res['error'] = 1;
                } elseif (isset($users_paypwd['pay_password']) && $new_password != $users_paypwd['pay_password']) {
                    $res['error'] = 2;
                }
            }

            return response()->json($res);
        }

    }
}
