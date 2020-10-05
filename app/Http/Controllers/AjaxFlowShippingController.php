<?php

namespace App\Http\Controllers;

use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Cart\CartCommonService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Flow\FlowActivityService;
use App\Services\Flow\FlowUserService;

class AjaxFlowShippingController extends InitController
{
    protected $dscRepository;
    protected $sessionRepository;
    protected $flowUserService;
    protected $cartCommonService;
    protected $flowActivityService;

    public function __construct(
        DscRepository $dscRepository,
        SessionRepository $sessionRepository,
        FlowUserService $flowUserService,
        CartCommonService $cartCommonService,
        FlowActivityService $flowActivityService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->sessionRepository = $sessionRepository;
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
        /*------------------------------------------------------ */
        //-- 改变配送方式
        /*------------------------------------------------------ */
        if ($step == 'select_shipping') {
            $result = ['error' => '', 'content' => '', 'need_insure' => 0];

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
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $order['shipping_id'] = intval(request()->input('shipping', 0));

                $regions = [$consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']];
                $shipping_info = shipping_info($order['shipping_id'], $regions);

                $cart_goods_list = cart_goods($flow_type, $cart_value, 1); // 取得商品列表，计算合计

                $this->smarty->assign('goods_list', $cart_goods_list);

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee);
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

                $result['cod_fee'] = $shipping_info['pay_fee'];
                if (strpos($result['cod_fee'], '%') === false) {
                    $result['cod_fee'] = price_format($result['cod_fee'], false);
                }

                $ru_list = get_ru_info_list($total['ru_list']); //商家运费详细信息
                $this->smarty->assign('warehouse_fee', $ru_list);
                $this->smarty->assign('freight_model', $this->config['freight_model']);

                $result['need_insure'] = ($shipping_info['insure'] > 0 && !empty($order['need_insure'])) ? 1 : 0;

                $result['content'] = $this->smarty->fetch('library/order_total.lbi');
            }

            return response()->json($result);
        }

    }
}
