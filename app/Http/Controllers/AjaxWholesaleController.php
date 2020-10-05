<?php

namespace App\Http\Controllers;

use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\DscRepository;
use App\Services\Flow\FlowUserService;
use App\Services\Wholesale\CartService;
use App\Services\Wholesale\OrderService;

class AjaxWholesaleController extends InitController
{
    protected $orderService;
    protected $cartService;
    protected $dscRepository;
    protected $config;
    protected $flowUserService;

    public function __construct(
        OrderService $orderService,
        CartService $cartService,
        DscRepository $dscRepository,
        FlowUserService $flowUserService
    )
    {
        $this->orderService = $orderService;
        $this->cartService = $cartService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->flowUserService = $flowUserService;
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

        $user_id = session('user_id', 0);

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        //jquery Ajax跨域
        $is_jsonp = intval(request()->input('is_jsonp', 0));
        $act = addslashes(trim(request()->input('act', '')));

        $wholesale_cart_value = $this->cartService->getWholesaleCartValue(); //供应链商品

        /*------------------------------------------------------ */
        //-- 批发订单分页查询
        /*------------------------------------------------------ */
        if ($act == 'wholesale_order_gotopage') {

            $lang_user = lang('user');
            $lang_wholesale = lang('wholesale');

            $lang = array_merge($lang_user, $lang_wholesale);

            $id = json_str_iconv(request()->input('id', []));
            $page = intval(request()->input('page', 1));

            if ($id) {
                $id = explode("=", $id);
            }

            $user_id = $id[0];

            $record_count = WholesaleOrderInfo::mainOrderCount()
                ->where('user_id', $user_id)
                ->where('is_delete', 0);

            $record_count = $record_count->count();

            $size = 10;
            $where = [
                'user_id' => $user_id,
                'page' => $page,
                'size' => $size
            ];

            $wholesale_orders = $this->orderService->getWholesaleOrders($record_count, $where);

            $this->smarty->assign('lang', $lang);
            $this->smarty->assign('orders', $wholesale_orders);

            $result['content'] = $this->smarty->fetch("library/user_wholesale_order_list.lbi");
        }

        /* ------------------------------------------------------ */
        //-- 批发-改变发票的设置
        /* ------------------------------------------------------ */
        elseif ($act == 'edit_wholesale_invoice') {
            load_helper(['suppliers']);

            $result = ['error' => 0, 'content' => ''];
            $invoice_type = intval(request()->input('invoice_type', 0));
            $from = request()->input('from', '');

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 对商品信息赋值 */
            $cart_goods = $this->cartService->wholesaleCartGoods(0, $wholesale_cart_value); // 取得商品列表，计算合计

            if (empty($cart_goods) && empty($from) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type) && empty($from)) {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['cart_and_info_null'];

                return response()->json($result);
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $this->config);

                /* 如果能开发票，取得发票内容列表 */
                if ((!isset($this->config['can_invoice']) || $this->config['can_invoice'] == '1') && isset($this->config['invoice_content']) && trim($this->config['invoice_content']) != '' && $flow_type != CART_EXCHANGE_GOODS) {
                    $inv_content_list = explode("\n", str_replace("\r", '', $this->config['invoice_content']));
                    $this->smarty->assign('inv_content_list', $inv_content_list);

                    $inv_type_list = [];
                    foreach ($this->config['invoice_type']['type'] as $key => $type) {
                        if (!empty($type)) {
                            $inv_type_list[$type] = $type . ' [' . floatval($this->config['invoice_type']['rate'][$key]) . '%]';
                        }
                    }
                    //抬头名称
                    $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('order_invoice') . " WHERE user_id='$user_id' LIMIT 10";
                    $order_invoice = $GLOBALS['db']->getAll($sql);
                    $this->smarty->assign('order_invoice', $order_invoice);
                    $this->smarty->assign('inv_type_list', $inv_type_list);

                    /* 取得国家列表 */
                    $this->smarty->assign('country_list', get_regions());

                    $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

                    /* 增票信息 */
                    $sql = " SELECT * FROM " . $GLOBALS['dsc']->table('users_vat_invoices_info') . " WHERE user_id='$user_id' LIMIT 1 ";
                    if ($vat_info = $GLOBALS['db']->getRow($sql)) {
                        $this->smarty->assign('vat_info', $vat_info);
                        $this->smarty->assign('audit_status', $vat_info['audit_status']);
                    }
                }
                $this->smarty->assign('invoice_type', $invoice_type);
                $this->smarty->assign('user_id', $user_id);
                $result['content'] = $this->smarty->fetch('library/invoice.lbi');
            }
        }

        /* ------------------------------------------------------ */
        //-- 批发-修改并保存发票的设置
        /* ------------------------------------------------------ */
        elseif ($act == 'wholesale_gotoInvoice') {
            load_helper(['wholesale', 'suppliers']);

            $result = ['error' => '', 'content' => ''];

            $invoice_id = intval(request()->input('invoice_id', 0));

            $inv_content = json_str_iconv(urldecode(request()->input('inv_content', '')));
            $store_id = intval(request()->input('store_id', 0));
            $invoice_type = intval(request()->input('invoice_type', 0));
            $tax_id = json_str_iconv(urldecode(request()->input('tax_id', '')));
            $inv_payee = json_str_iconv(urldecode(request()->input('inv_payee', '')));
            $inv_payee = !empty($inv_payee) ? addslashes(trim($inv_payee)) : '';

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));
            $from = request()->input('from', '');

            /* 保存发票纳税人识别码 */
            if (empty($invoice_id)) {
                $sql = "SELECT invoice_id FROM " . $GLOBALS['dsc']->table('order_invoice') . " WHERE inv_payee = '$inv_payee' AND user_id = '$user_id'";
                if (!$GLOBALS['db']->getOne($sql)) {
                    $sql = "INSERT INTO " . $GLOBALS['dsc']->table('order_invoice') . " (`tax_id`) VALUES ('$tax_id')";
                    $GLOBALS['db']->query($sql);
                }
            } else {
                $sql = "UPDATE " . $GLOBALS['dsc']->table('order_invoice') . " SET tax_id='$tax_id' WHERE invoice_id='$invoice_id'";
                $GLOBALS['db']->query($sql);
            }

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 对商品信息赋值 */
            $cart_goods = $this->cartService->wholesaleCartGoods(0, $wholesale_cart_value); // 取得商品列表，计算合计
            $this->smarty->assign('goods_list', $cart_goods);

            if (empty($cart_goods) && empty($from) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type) && empty($from)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];

                return response()->json($result);
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $this->config);

                /* 取得订单信息 */
                $order = flow_order_info();

                if ($inv_content) {
                    if ($invoice_id > 0) {
                        $sql = "SELECT inv_payee FROM " . $GLOBALS['dsc']->table('order_invoice') . " WHERE invoice_id='$invoice_id'";
                        $inv_payee = $GLOBALS['db']->getOne($sql);
                    } else {
                        $inv_payee = $GLOBALS['_LANG']['personal'];
                    }
                    $order['tax_id'] = $tax_id;
                    $order['need_inv'] = 1;
                    $order['inv_type'] = '';
                    $order['inv_payee'] = $inv_payee;
                    $order['inv_content'] = $inv_content;
                } else {
                    $order['need_inv'] = 0;
                    $order['inv_type'] = '';
                    $order['inv_payee'] = '';
                    $order['inv_content'] = '';
                    $order['tax_id'] = '';
                }

                //ecmoban模板堂 --zhuo start
                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['address'];
                $this->smarty->assign('consignee', $consignee);

                $this->smarty->assign('store_id', $store_id);

                /* 计算订单的费用 */
                $total = $this->cartService->wholesaleCartInfo(0, $wholesale_cart_value);
                $this->smarty->assign('total', $total);
                //ecmoban模板堂 --zhuo end

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->smarty->assign('is_group_buy', 1);
                }

                $result['invoice_type'] = $GLOBALS['_LANG']['invoice_ordinary'];
                if ($invoice_type) {
                    $result['type'] = 1;
                    $result['invoice_type'] = $GLOBALS['_LANG']['need_invoice'][1];
                }

                $result['inv_payee'] = $order['inv_payee'];
                $result['inv_content'] = $order['inv_content'];
                $result['tax_id'] = $order['tax_id'];

                $this->smarty->assign('warehouse_id', $warehouse_id);
                $this->smarty->assign('area_id', $area_id);

                $sc_rand = rand(1000, 9999);
                $sc_guid = sc_guid();

                $account_cookie = MD5($sc_guid . "-" . $sc_rand);
                cookie()->queue('done_cookie', $account_cookie, 60 * 24 * 30);

                $this->smarty->assign('sc_guid', $sc_guid);
                $this->smarty->assign('sc_rand', $sc_rand);

                $result['content'] = $this->smarty->fetch('library/wholesale_order_total.lbi');
            }
        }

        if ($is_jsonp) {
            $jsoncallback = trim(request()->input('jsoncallback', ''));
            return $jsoncallback . "(" . response()->json($result) . ")";
        } else {
            return response()->json($result);
        }
    }
}
