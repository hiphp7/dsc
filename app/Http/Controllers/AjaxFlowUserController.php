<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Cart\CartCommonService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Flow\FlowActivityService;
use App\Services\Flow\FlowUserService;

class AjaxFlowUserController extends InitController
{
    protected $sessionRepository;
    protected $baseRepository;
    protected $dscRepository;
    protected $flowUserService;
    protected $cartCommonService;
    protected $flowActivityService;

    public function __construct(
        SessionRepository $sessionRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        FlowUserService $flowUserService,
        CartCommonService $cartCommonService,
        FlowActivityService $flowActivityService
    )
    {
        $this->sessionRepository = $sessionRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->flowUserService = $flowUserService;
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
        /* ------------------------------------------------------ */
        //-- 检查用户输入的余额
        /* ------------------------------------------------------ */
        if ($step == 'check_integral') {

            $points = floatval(request()->input('integral', 0));

            $user_info = Users::where('user_id', $user_id);
            $user_info = $this->baseRepository->getToArrayFirst($user_info);

            if (empty($user_info)) {
                return response()->json('user not found.');
            }

            $flow_points = $this->flowActivityService->getFlowAvailablePoints($cart_value, $warehouse_id, $area_id, $area_city);  // 该订单允许使用的积分
            $user_points = $user_info['pay_points'] ?? 0; // 用户的积分总数

            if ($points > $user_points) {
                return response()->json($GLOBALS['_LANG']['integral_not_enough']);
            }

            if ($points > $flow_points) {
                return response()->json(sprintf($GLOBALS['_LANG']['integral_too_much'], $flow_points));
            }
        }

        /* ------------------------------------------------------ */
        //-- 改变缺货处理时的方式
        /* ------------------------------------------------------ */
        elseif ($step == 'change_oos') {
            /* 取得订单信息 */
            $order = flow_order_info();
            $order['how_oos'] = intval(request()->input('oos', 0));

            /* 保存 session */
            session([
                'flow_order' => $order
            ]);

            return response()->json('success');
        }

        /* ------------------------------------------------------ */
        //-- 检查用户输入的余额
        /* ------------------------------------------------------ */
        elseif ($step == 'check_surplus') {
            $surplus = floatval(request()->input('surplus', 0));

            $user_info = Users::where('user_id', $user_id);
            $user_info = $this->baseRepository->getToArrayFirst($user_info);

            if (empty($user_info)) {
                return response()->json('user not found.');
            }

            if (($user_info['user_money'] + $user_info['credit_line'] < $surplus)) {
                return response()->json($GLOBALS['_LANG']['surplus_not_enough']);
            }
        }

        /* ------------------------------------------------------ */
        //-- 改变发票的设置
        /* ------------------------------------------------------ */
        elseif ($step == 'change_needinv') {
            $result = ['error' => '', 'content' => ''];

            $inv_type = json_str_iconv(urldecode(request()->input('inv_type', '')));
            $inv_payee = json_str_iconv(urldecode(request()->input('inv_payee', '')));
            $inv_content = json_str_iconv(urldecode(request()->input('inv_content', '')));
            $tax_id = json_str_iconv(urldecode(request()->input('tax_id', '')));

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type, $cart_value); // 取得商品列表，计算合计

            if (empty($cart_goods) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
                return response()->json($result);
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $need_inv = intval(request()->input('need_inv', 0));
                if (isset($need_inv) && intval($need_inv) == 1) {
                    $order['need_inv'] = 1;
                    $order['inv_type'] = trim(stripslashes($inv_type));
                    $order['inv_payee'] = trim(stripslashes($inv_payee));
                    $order['inv_content'] = trim(stripslashes($inv_content));
                    $order['tax_id'] = trim(stripslashes($tax_id));
                } else {
                    $order['need_inv'] = 0;
                    $order['inv_type'] = '';
                    $order['inv_payee'] = '';
                    $order['inv_content'] = '';
                    $order['tax_id'] = '';
                }

                $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
                $this->smarty->assign('cart_goods_number', $cart_goods_number);

                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['street_name'] = get_goods_region_name($consignee['street']);
                $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];
                $this->smarty->assign('consignee', $consignee);

                $cart_goods_list = cart_goods($flow_type, $cart_value, 1); // 取得商品列表，计算合计
                $this->smarty->assign('goods_list', $cart_goods_list);

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee, 0, $cart_value, 0, $cart_goods_list);
                if (CROSS_BORDER === true) // 跨境多商户
                { // 这里不用传rate_price
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

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->smarty->assign('is_group_buy', 1);
                } elseif ($flow_type == CART_EXCHANGE_GOODS) {
                    // 积分兑换 qin
                    $this->smarty->assign('is_exchange_goods', 1);
                }

                return $this->smarty->fetch('library/order_total.lbi');
            }
        }

        /*------------------------------------------------------ */
        //-- 改变余额
        /*------------------------------------------------------ */
        elseif ($step == 'change_surplus') {
            $surplus = floatval(request()->input('surplus', 0));

            $user_info = Users::where('user_id', $user_id);
            $user_info = $this->baseRepository->getToArrayFirst($user_info);

            if (empty($user_info)) {
                return response()->json([]);
            }

            $shipping_id = strip_tags(urldecode(request()->input('shipping_id', '')));
            $tmp_shipping_id_arr = dsc_decode($shipping_id, true);

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));
            $store_id = intval(request()->input('store_id', 0));

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('store_id', $store_id);

            if ($user_info['user_money'] + $user_info['credit_line'] < $surplus) {
                $result['error'] = $GLOBALS['_LANG']['surplus_not_enough'];
            } else {
                /* 取得购物类型 */
                $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

                /* 取得购物流程设置 */
                $this->smarty->assign('config', $GLOBALS['_CFG']);

                /* 获得收货人信息 */
                $consignee = $this->flowUserService->getConsignee(session('user_id'));

                /* 对商品信息赋值 */
                $cart_goods = cart_goods($flow_type, $cart_value, 0, $warehouse_id, $area_id, $area_city); // 取得商品列表，计算合计

                if (empty($cart_goods)) {
                    $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
                } elseif (!$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                    $result['error'] = $GLOBALS['_LANG']['no_consignee'];
                } else {
                    /* 取得订单信息 */
                    $order = flow_order_info();
                    $order['surplus'] = $surplus;

                    $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
                    $this->smarty->assign('cart_goods_number', $cart_goods_number);

                    $consignee['province_name'] = get_goods_region_name($consignee['province']);
                    $consignee['city_name'] = get_goods_region_name($consignee['city']);
                    $consignee['district_name'] = get_goods_region_name($consignee['district']);
                    $consignee['street_name'] = get_goods_region_name($consignee['street']);
                    $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];
                    $this->smarty->assign('consignee', $consignee);

                    $cart_goods_list = cart_goods($flow_type, $cart_value, 1, $warehouse_id, $area_id, $area_city, '', $store_id); // 取得商品列表，计算合计
                    $this->smarty->assign('goods_list', $cart_goods_list);

                    //切换配送方式 by kong
                    $cart_goods_list = get_flowdone_goods_list($cart_goods_list, $tmp_shipping_id_arr);

                    /* 计算订单的费用 */
                    $total = order_fee($order, $cart_goods, $consignee, 0, $cart_value, 0, $cart_goods_list, 0, 0, $store_id);

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

                    /* 团购标志 */
                    if ($flow_type == CART_GROUP_BUY_GOODS) {
                        $this->smarty->assign('is_group_buy', 1);
                    } elseif ($flow_type == CART_EXCHANGE_GOODS) {
                        // 积分兑换 qin
                        $this->smarty->assign('is_exchange_goods', 1);
                    }

                    $result['content'] = $this->smarty->fetch('library/order_total.lbi');
                }
            }

            return response()->json($result);
        }

    }
}
