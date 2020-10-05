<?php

namespace App\Http\Controllers;

use App\Libraries\CaptchaVerify;
use App\Models\Article;
use App\Models\AutoSms;
use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\CollectStore;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsTransportExtend;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderInvoice;
use App\Models\Region;
use App\Models\SaleNotice;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\ShippingPoint;
use App\Models\Users;
use App\Models\UsersVatInvoicesInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryBrandService;
use App\Services\Common\AreaService;
use App\Services\Common\CommonService;
use App\Services\Cron\CronService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Flow\FlowUserService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGuessService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;
use App\Services\Store\StoreStreetService;

/**
 * 提交用户评论
 */
class AjaxDialogController extends InitController
{
    protected $areaService;
    protected $storeStreetService;
    protected $config;
    protected $baseRepository;
    protected $cartCommonService;
    protected $goodsAttrService;
    protected $categoryBrandService;
    protected $goodsWarehouseService;
    protected $merchantCommonService;
    protected $goodsCommonService;
    protected $dscRepository;
    protected $commonRepository;
    protected $cronService;
    protected $orderGoodsService;
    protected $flowUserService;
    protected $commonService;

    public function __construct(
        AreaService $areaService,
        StoreStreetService $storeStreetService,
        BaseRepository $baseRepository,
        CartCommonService $cartCommonService,
        GoodsAttrService $goodsAttrService,
        CategoryBrandService $categoryBrandService,
        GoodsWarehouseService $goodsWarehouseService,
        MerchantCommonService $merchantCommonService,
        GoodsCommonService $goodsCommonService,
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        CronService $cronService,
        OrderGoodsService $orderGoodsService,
        FlowUserService $flowUserService,
        CommonService $commonService
    )
    {
        $this->areaService = $areaService;
        $this->storeStreetService = $storeStreetService;
        $this->config = $this->config();
        $this->baseRepository = $baseRepository;
        $this->cartCommonService = $cartCommonService;
        $this->goodsAttrService = $goodsAttrService;
        $this->categoryBrandService = $categoryBrandService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsCommonService = $goodsCommonService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->cronService = $cronService;
        $this->orderGoodsService = $orderGoodsService;
        $this->flowUserService = $flowUserService;
        $this->commonService = $commonService;
    }

    public function index()
    {
        if ($this->checkReferer() === false) {
            return response()->json(['error' => 1, 'msg' => 'referer error']);
        }

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

        load_helper('order');

        $user_id = session('user_id', 0);

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        //jquery Ajax跨域
        $is_jsonp = intval(request()->input('is_jsonp', 0));

        $cart_value = $this->cartCommonService->getCartValue();

        $act = addslashes(trim(request()->input('act', '')));
        /*------------------------------------------------------ */
        //-- 购物车确认订单页面配送方式  0 快递 1 自提
        /*------------------------------------------------------ */
        if ($act == 'shipping_type') {
            load_helper('order');

            $result = ['error' => 0, 'massage' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1];
            //商家
            $ru_id = intval(request()->input('ru_id', 0));
            $tmp_shipping_id = intval(request()->input('shipping_id', 0));

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));
            $shipping = request()->input('shipping', '');

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));

            /* 配送方式 */
            $shipping_type = intval(request()->input('type', 0));

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type, $cart_value, 0, $warehouse_id, $area_id); // 取得商品列表，计算合计

            if (empty($cart_goods) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                //ecmoban模板堂 --zhuo start
                if (empty($cart_goods)) {
                    $result['error'] = 1;
                    $result['massage'] = $GLOBALS['_LANG']['no_goods_in_cart'];
                } elseif (!$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                    $result['error'] = 2;
                    $result['massage'] = $GLOBALS['_LANG']['no_address'];
                }
                //ecmoban模板堂 --zhuo end
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $this->config);

                /* 取得订单信息 */
                $order = flow_order_info();

                /* 保存 session */
                session([
                    'flow_order' => $order
                ]);

                session()->put('merchants_shipping.' . $ru_id . '.shipping_type', $shipping_type);

                //ecmoban模板堂 --zhuo start
                $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
                $this->smarty->assign('cart_goods_number', $cart_goods_number);

                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['address'];

                $this->smarty->assign('consignee', $consignee);
                $cart_goods_list = cart_goods($flow_type, $cart_value, 1); // 取得商品列表，计算合计

                $goods_list = cart_by_favourable($cart_goods_list);
                $this->smarty->assign('goods_list', $goods_list);

                //切换配送方式
                foreach ($cart_goods_list as $key => $val) {
                    if ($tmp_shipping_id > 0 && $val['ru_id'] == $ru_id) {
                        $cart_goods_list[$key]['tmp_shipping_id'] = $tmp_shipping_id;
                    }
                }

                $type = [
                    'type' => 0,
                    'shipping_list' => $shipping,
                ];

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee, $type, $cart_value, 0, $cart_goods_list);
                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $web = app(CrossBorderService::class)->webExists();

                    if (!empty($web)) {
                        $arr = [
                            'consignee' => $consignee ?? '',
                            'rec_type' => $flow_type ?? 0,
                            'store_id' => $store_id ?? 0,
                            'cart_value' => $cart_value ?? '',
                            'type' => $type ?? 0
                        ];
                        $amount = $web->assignNewRatePrice($cart_goods_list, $total['amount'], $arr);
                        $total['amount'] = $amount['amount'];
                        $total['amount_formated'] = $amount['amount_formated'];
                    } else {
                        return response()->json(['error' => 1, 'message' => 'service not exists']);
                    }
                }
                $this->smarty->assign('total', $total);
                //ecmoban模板堂 --zhuo end

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->smarty->assign('is_group_buy', 1);
                }

                //有存在虚拟和实体商品 start
                get_goods_flow_type($cart_value);
                //有存在虚拟和实体商品 end

                $this->smarty->assign('warehouse_id', $warehouse_id);
                $this->smarty->assign('area_id', $area_id);

                $sc_rand = rand(1000, 9999);
                $sc_guid = sc_guid();

                $account_cookie = MD5($sc_guid . "-" . $sc_rand);
                cookie()->queue('done_cookie', $account_cookie, 60 * 24 * 30);

                $this->smarty->assign('sc_guid', $sc_guid);
                $this->smarty->assign('sc_rand', $sc_rand);

                $result['content'] = $this->smarty->fetch('library/order_total.lbi');
            }

            $result['ru_id'] = $ru_id;
            $result['shipping_type'] = $shipping_type;
            $result['shipping_id'] = $tmp_shipping_id;

            $shipping_info = get_shipping_code($tmp_shipping_id);
            $result['shipping_code'] = $shipping_info['shipping_code'] ?? '';
        }

        /*------------------------------------------------------ */
        //-- 改变发票的设置
        /*------------------------------------------------------ */
        elseif ($act == 'edit_invoice') {
            $result = ['error' => 0, 'content' => ''];

            $invoice_type = (int)request()->input('invoice_type', 0);
            $from = request()->input('from', '');

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type, $cart_value); // 取得商品列表，计算合计

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
                    if ($this->config['invoice_type']['type']) {
                        foreach ($this->config['invoice_type']['type'] as $key => $type) {
                            if (!empty($type)) {
                                $inv_type_list[$type] = $type . ' [' . floatval($this->config['invoice_type']['rate'][$key]) . '%]';
                            }
                        }
                    }

                    //抬头名称
                    $order_invoice = OrderInvoice::where('user_id', $user_id)->take(10)->get();
                    $order_invoice = $order_invoice ? $order_invoice->toArray() : [];

                    $this->smarty->assign('order_invoice', $order_invoice);
                    $this->smarty->assign('inv_type_list', $inv_type_list);

                    /* 取得国家列表 */
                    $this->smarty->assign('country_list', get_regions());

                    $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

                    /* 增票信息 */
                    $vat_info = UsersVatInvoicesInfo::where('user_id', $user_id)->first();
                    $vat_info = $vat_info ? $vat_info->toArray() : [];

                    if ($vat_info) {
                        $this->smarty->assign('vat_info', $vat_info);
                        $this->smarty->assign('audit_status', $vat_info['audit_status']);
                    }
                }
                $this->smarty->assign('invoice_type', $invoice_type);
                $this->smarty->assign('user_id', $user_id);
                $this->smarty->assign('consignee', $consignee);
                $result['content'] = $this->smarty->fetch('library/invoice.lbi');
            }
        }

        /*------------------------------------------------------ */
        //-- 保存发票抬头名称
        /*------------------------------------------------------ */
        elseif ($act == 'update_invoicename') {
            $result = ['error' => 0, 'msg' => '', 'content' => '', 'invoice_id' => 0];

            $inv_payee = json_str_iconv(urldecode(request()->input('inv_payee', '')));
            $inv_payee = !empty($inv_payee) ? addslashes(trim($inv_payee)) : '';

            $invoice_id = intval(request()->input('invoice_id', 0));
            $tax_id = (int)request()->input('tax_id', 0);

            if (empty($user_id) || empty($inv_payee)) {
                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['Parameter_error'];
                return response()->json($result);
            }

            if (empty($invoice_id)) {
                $invoice_id = OrderInvoice::where('inv_payee', $inv_payee)->where('user_id', $user_id)->value('invoice_id');
                if (!$invoice_id) {
                    $other = [
                        'user_id' => $user_id,
                        'inv_payee' => $inv_payee,
                        'tax_id' => $tax_id
                    ];
                    $invoice_id = OrderInvoice::insertGetId($other);

                    $result['invoice_id'] = $invoice_id;
                } else {
                    $result['error'] = 1;
                    $result['msg'] = $GLOBALS['_LANG']['invoice_top_exists'];
                    return response()->json($result);
                }
            } else {
                $other = [
                    'inv_payee' => $inv_payee,
                    'tax_id' => $tax_id
                ];
                OrderInvoice::where('invoice_id', $invoice_id)->update($other);

                $result['invoice_id'] = $invoice_id;
            }

            $result['tax_id'] = $tax_id;
        }

        /*------------------------------------------------------ */
        //-- 删除发票抬头名称
        /*------------------------------------------------------ */
        elseif ($act == 'del_invoicename') {
            $result = ['error' => 0, 'msg' => '', 'content' => ''];

            $invoice_id = intval(request()->input('invoice_id', 0));

            if (empty($user_id) || empty($invoice_id)) {
                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['Parameter_error'];
                return response()->json($result);
            } else {
                OrderInvoice::where('invoice_id', $invoice_id)->delete();
            }
        }

        /*------------------------------------------------------ */
        //-- 修改并保存发票的设置
        /*------------------------------------------------------ */
        elseif ($act == 'gotoInvoice') {
            $result = ['error' => '', 'content' => ''];

            $invoice_id = intval(request()->input('invoice_id', 0));
            $inv_content = json_str_iconv(urldecode(request()->input('inv_content', '')));
            $store_id = intval(request()->input('store_id', 0));

            $invoice_type = (int)request()->input('invoice_type', 0);
            $tax_id = json_str_iconv(urldecode(request()->input('tax_id', '')));
            $shipping_id = strip_tags(urldecode(request()->input('shipping_id', '')));
            $tmp_shipping_id_arr = dsc_decode($shipping_id);

            $inv_payee = json_str_iconv(urldecode(request()->input('inv_payee', '')));
            $inv_payee = !empty($inv_payee) ? addslashes(trim($inv_payee)) : '';

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));
            $from = request()->input('from', '');

            if (empty($user_id)) {
                $result['error'] = 1;
                $result['msg'] = $GLOBALS['_LANG']['Parameter_error'];
                return response()->json($result);
            }

            /* 保存发票纳税人识别码 */
            if (!empty($tax_id)) {
                $data = [
                    'tax_id' => $tax_id,
                ];
                if (empty($invoice_id)) {
                    $invoice_id = OrderInvoice::where('inv_payee', $inv_payee)->where('user_id', $user_id)->value('invoice_id');
                    if (!$invoice_id) {
                        OrderInvoice::insert($data);
                    } else {
                        $result['error'] = 1;
                        $result['msg'] = $GLOBALS['_LANG']['invoice_top_exists'];
                        return response()->json($result);
                    }
                } else {
                    OrderInvoice::where('invoice_id', $invoice_id)->update($data);
                }
            }

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type, $cart_value); // 取得商品列表，计算合计

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
                        $inv_payee = OrderInvoice::where('invoice_id', $invoice_id)->value('inv_payee');
                    } else {
                        $inv_payee = '个人';
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
                $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
                $this->smarty->assign('cart_goods_number', $cart_goods_number);

                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['address'];
                $this->smarty->assign('consignee', $consignee);

                $cart_goods_list = cart_goods($flow_type, $cart_value, 1); // 取得商品列表，计算合计
                $this->smarty->assign('goods_list', $cart_goods_list);
                $this->smarty->assign('store_id', $store_id);

                //切换配送方式 by kong
                $cart_goods_list = get_flowdone_goods_list($cart_goods_list, $tmp_shipping_id_arr);

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee, 0, $cart_value, 0, $cart_goods_list);
                $this->smarty->assign('total', $total);
                //ecmoban模板堂 --zhuo end

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->smarty->assign('is_group_buy', 1);
                }

                if ($invoice_type == 0) {
                    $result['invoice_type'] = $GLOBALS['_LANG']['invoice_ordinary'];
                } elseif ($invoice_type == 1) {
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

                $result['content'] = $this->smarty->fetch('library/order_total.lbi');
            }
        }

        /*------------------------------------------------------ */
        //-- 删除购物车商品
        /*------------------------------------------------------ */
        elseif ($act == 'delete_cart_goods') {
            $cart_value = json_str_iconv(request()->input('cart_value', ''));

            if ($cart_value) {
                $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;
                Cart::whereIn('rec_id', $cart_value)->delete();

                $cart_value = implode(",", $cart_value);
            }

            $result['cart_value'] = $cart_value;
        }

        /*------------------------------------------------------ */
        //-- 删除并移除注
        /*------------------------------------------------------ */
        elseif ($act == 'drop_to_collect') {
            if ($user_id > 0) {
                $cart_value = json_str_iconv(request()->input('cart_value', ''));

                if ($cart_value) {
                    $cart_value = explode(',', $cart_value);
                    $goods_list = Cart::select(['goods_id', 'rec_id'])->whereIn('rec_id', $cart_value)->get();
                    $goods_list = $goods_list ? $goods_list->toArray() : [];

                    if ($goods_list) {
                        foreach ($goods_list as $row) {
                            $count = CollectGoods::where('user_id', $user_id)->where('goods_id', $row['goods_id'])->count();

                            if (empty($count)) {
                                $time = gmtime();

                                $other = [
                                    'user_id' => $user_id,
                                    'goods_id' => $row['goods_id'],
                                    'add_time' => $time
                                ];
                                CollectGoods::insert($other);
                            }
                            flow_drop_cart_goods($row['rec_id']);
                        }
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 我的发票分页查询
        /*------------------------------------------------------ */
        elseif ($act == 'user_inv_gotopage') {
            load_helper('transaction');

            $id = json_str_iconv(request()->input('id', []));
            $page = (int)request()->input('page', 1);

            if ($id) {
                $id = explode("=", $id);
            }

            $order = (object)[];
            if (count($id) > 1) {
                $user_id = $id[0];

                $id = explode("|", $id[1]);
                $order = $this->dscRepository->getStrArray1($id);

                $record_count = OrderInfo::where('main_count', 0)
                    ->where('user_id', $user_id)
                    ->searchKeyword($order);

                if ($order->idTxt == 'signNum') {
                    $record_count = $record_count->whereHas('getOrderGoods', function ($query) use ($user_id) {
                        $query->goodsCommentCount($user_id);
                    });
                }

                $record_count = $record_count->count();
            } else {
                $user_id = $id[0];

                $record_count = OrderInfo::where('main_count', 0)
                    ->where('user_id', $user_id);

                $record_count = $record_count->count();
            }

            $order->action = "invoice";

            $invoice_list = invoice_list($user_id, $record_count, $page);

            $this->smarty->assign('lang', lang('user'));
            $this->smarty->assign('invoice_list', $invoice_list);
            $this->smarty->assign('action', $order->action);
            $this->smarty->assign('open_delivery_time', $this->config['open_delivery_time']);

            $result['content'] = $this->smarty->fetch("library/user_inv_list.lbi");
        }

        /*------------------------------------------------------ */
        //-- 店铺街分页查询
        /*------------------------------------------------------ */
        elseif ($act == 'store_shop_gotoPage') {

            //引入相关语言包
            $this->dscRepository->helpersLang('search');
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $id = json_str_iconv(request()->input('id', []));

            $page = (int)request()->input('page', 1);

            $type = intval(request()->input('type', 0));
            $libType = intval(request()->input('libType', 0));

            if ($libType == 1) {
                $size = 10;
            } else {
                $size = 16;
            }

            if ($id) {
                $id = explode("|", $id);
                $id = $this->dscRepository->getStrArray2($id);

                if ($id) {
                    $id = get_request_filter($id, 2);
                }

                $sort = addslashes_deep(request()->input('sort', 'shop_id'));
                $order = addslashes_deep(request()->input('order', 'DESC'));
                $keywords = addslashes_deep(request()->input('keywords', ''));

                $region_id = intval(request()->input('region_id', 0));
                $area_id = intval(request()->input('area_id', 0));
                $area_city = intval(request()->input('area_city', 0));

                $store_province = isset($id['store_province']) && !empty($id['store_province']) ? intval($id['store_province']) : '';
                $store_city = isset($id['store_city']) && !empty($id['store_city']) ? intval($id['store_city']) : '';
                $store_district = isset($id['store_district']) && !empty($id['store_district']) ? intval($id['store_district']) : '';
                $store_user = isset($id['store_user']) && !empty($id['store_user']) ? addslashes_deep($id['store_user']) : '';

                $count = $this->storeStreetService->getStoreShopCount($keywords, $sort, $store_province, $store_city, $store_district, $store_user, $libType);

                $store_shop_list = $this->storeStreetService->getStoreShopList($libType, $keywords, $count, $size, $page, $sort, $order, $region_id, $area_id, $area_city, $store_province, $store_city, $store_district, $store_user);
                $shop_list = $store_shop_list['shop_list'];
                $this->smarty->assign('store_shop_list', $shop_list);
                $this->smarty->assign('pager', $store_shop_list['pager']);
                $this->smarty->assign('count', $count);
            } else {
                $this->smarty->assign('store_shop_list', []);
                $this->smarty->assign('pager', '');
                $this->smarty->assign('count', 0);
            }

            $this->smarty->assign('size', $size);
            $this->smarty->assign('user_id', $user_id);

            if ($libType == 1) {
                $result['content'] = $this->smarty->fetch("library/search_store_shop_list.lbi");
            } else {
                $result['content'] = $this->smarty->fetch("library/store_shop_list.lbi");
                $result['pages'] = $this->smarty->fetch("library/pages_ajax.lbi");
            }
        }

        /*------------------------------------------------------ */
        //-- 无货结算
        /*------------------------------------------------------ */
        elseif ($act == 'goods_stock_exhausted') {

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            //缺货商品
            $rec_number = htmlspecialchars(request()->input('rec_number', ''));
            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));

            $area_city = intval(request()->input('area_city', 0));
            //门店id
            $store_id = intval(request()->input('store_id', 0));
            $store_seller = request()->input('store_seller', '');

            if (!empty($rec_number)) {
                $cart_value = get_sc_str_replace($cart_value, $rec_number, 1);

                /* 对商品信息赋值 */
                $cart_goods_list = cart_goods($flow_type, $rec_number, 1, $warehouse_id, $area_id, $area_city); // 取得商品列表，计算合计
                $cart_goods_list_new = cart_by_favourable($cart_goods_list);
                $this->smarty->assign('goods_list', $cart_goods_list_new);
                $this->smarty->assign('cart_value', $cart_value);
                $this->smarty->assign('store_seller', $store_seller);
                $this->smarty->assign('store_id', $store_id);

                $result['error'] = 1;
                $result['cart_value'] = $cart_value;
                $result['content'] = $this->smarty->fetch('library/goods_stock_exhausted.lbi');
            }
        }

        /*------------------------------------------------------ */
        //-- 不支持�        �送结算 （购物流程下单一个商品时）
        /*------------------------------------------------------ */
        elseif ($act == 'shipping_prompt') {

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            //不支持配送商品
            $shipping_prompt = addslashes(request()->input('shipping_prompt', ''));

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));

            $area_city = intval(request()->input('area_city', 0));
            //门店id
            $store_id = intval(request()->input('store_id', 0));
            $store_seller = request()->input('store_seller', '');

            if ($shipping_prompt) {
                $cart_value = get_sc_str_replace($cart_value, $shipping_prompt, 1);

                /* 对商品信息赋值 */
                $cart_goods_list = cart_goods($flow_type, $shipping_prompt, 1, $warehouse_id, $area_id, $area_city); // 取得商品列表，计算合计
                $cart_goods_list_new = cart_by_favourable($cart_goods_list);
                $this->smarty->assign('goods_list', $cart_goods_list_new);
                $this->smarty->assign('cart_value', $cart_value);
                $this->smarty->assign('store_seller', $store_seller);
                $this->smarty->assign('store_id', $store_id);

                $result['error'] = 1;
                $result['cart_value'] = $cart_value;
                $result['content'] = $this->smarty->fetch('library/goods_shipping_prompt.lbi');
            }
        }

        /*------------------------------------------------------ */
        //-- 获取商品属性列表
        /*------------------------------------------------------ */
        elseif ($act == 'ajax_get_spec') {
            $result = ['error' => 0, 'message' => '', 'attr_val' => ''];

            $rec_id = intval(request()->input('rec_id', 0));
            $g_id = intval(request()->input('g_id', 0));
            $g_number = intval(request()->input('g_number', 0));

            $order_goods = OrderGoods::select('warehouse_id', 'area_id', 'area_city')->where('rec_id', $rec_id)->first();
            $order_goods = $order_goods ? $order_goods->toArray() : [];

            if ($rec_id == 0 || $g_id == 0) {
                $result['err_msg'] = $GLOBALS['_LANG']['Can_get_attr'];
                $result['err_no'] = 1;
            } else {
                $goods_attr_id = OrderGoods::where('rec_id', $rec_id)->value('goods_attr_id');

                $goods_attr = [];
                if (!empty($goods_attr_id)) {
                    $goods_attr = explode(',', $goods_attr_id);
                }

                $properties = $this->goodsAttrService->getGoodsProperties($g_id, $order_goods['warehouse_id'], $order_goods['area_id'], $order_goods['area_city']);  // 获得商品的规格和属性
                $spec = $properties['spe'];

                if (!empty($spec)) {
                    foreach ($spec as $key => $value) {
                        if ($value['values']) {
                            $result['spec'] .= '<div class="catt"><span class="type_item">' . $value['name'] . '：</span>';
                            $result['spec'] .= '<input type="hidden"  value="" id="attr_' . $key . '" name="attr_val[]"/>';
                            $result['spec'] .= '<span class="type_con">';
                            foreach ($value['values'] as $k => $v) {
                                $arr_class = get_user_attr_checked($goods_attr, $v['id']);

                                if ($arr_class['class'] == 'cattsel') {
                                    $result['attr_val'] .= $key . '_' . $arr_class['attr_val'] . ",";
                                }

                                if ($value['is_checked'] == 1) {
                                    $padding = '';
                                    if (!empty($v['img_flie'])) {
                                        $img_flie = '<img src="' . $v['img_flie'] . '" width="25" height="25">' . $v['label'];
                                    } else {
                                        $img_flie = $v['label'];
                                        $padding = 'style="padding:3px 7px !important;"';
                                    }

                                    $result['spec'] .= '<a ' . $padding . ' class="' . $arr_class['class'] . '" title="' . $v['label'] . '[' . $v['format_price'] . ']" onclick="setChange(' . $v['id'] . ' , this , ' . $key . ')" >' . $img_flie . '<i></i></a>';
                                } else {
                                    $result['spec'] .= '<a class="' . $arr_class['class'] . '" title="' . $v['label'] . '[' . $v['format_price'] . ']" onclick="setChange(' . $v['id'] . ',this , ' . $key . ')" >' . $v['label'] . '<i></i></a>';
                                }
                            }
                        }
                        $result['spec'] .= '</span>';
                        $result['spec'] .= '</div>';
                    }
                }

                $result['spec'] .= '<div id="back_div">';
                $result['spec'] .= '<div class="type_item">' . $GLOBALS['_LANG']['exchange_number'] . '</div>';
                $result['spec'] .= '<div class="type_con"><a onclick="buyNumber.minus(this, 2)" href="javascript:;" id="decrease" class="plus_minus">-</a>';
                $result['spec'] .= '<input class="return_num" type="text" id="back_num" value="1" defaultnumber="1" name="attr_num" ' . " onblur=check_attr_num(this.id," . $g_number . "," . $rec_id . ") />";
                $result['spec'] .= '</div><a onclick="buyNumber.plus(this, 2)" href="javascript:;" id="increase" class="plus_minus">+</a>';
                $result['spec'] .= '</div>';

                $result['rec_id'] = $rec_id;

                if (!empty($result['attr_val'])) {
                    $result['attr_val'] = substr($result['attr_val'], 0, -1);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 商品地区
        /*------------------------------------------------------ */
        elseif ($act == 'goods_delivery_area') {
            load_helper('transaction');

            $area_val = strip_tags(urldecode(request()->input('area', '')));
            $area_val = json_str_iconv($area_val);

            if (empty($area_val)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $area = dsc_decode($area_val);

            $province_id = isset($area->province_id) && !empty($area->province_id) ? intval($area->province_id) : 0;
            $city_id = isset($area->city_id) && !empty($area->city_id) ? intval($area->city_id) : 0;
            $district_id = isset($area->district_id) && !empty($area->district_id) ? intval($area->district_id) : 0;
            $street_id = isset($area->street_id) && !empty($area->street_id) ? intval($area->street_id) : 0;
            $street_list = isset($area->street_list) && !empty($area->street_list) ? intval($area->street_list) : 0;
            $goods_id = isset($area->goods_id) && !empty($area->goods_id) ? intval($area->goods_id) : 0;
            $user_id = isset($area->user_id) && !empty($area->user_id) ? intval($area->user_id) : 0;
            $region_id = isset($area->region_id) && !empty($area->region_id) ? intval($area->region_id) : 0;
            $area_id = isset($area->area_id) && !empty($area->area_id) ? intval($area->area_id) : 0;
            $area_city = isset($area->area_city) && !empty($area->area_city) ? intval($area->area_city) : 0;
            $merchant_id = isset($area->merchant_id) && !empty($area->merchant_id) ? intval($area->merchant_id) : 0;
            $warehouse_type = isset($area->warehouse_type) && !empty($area->warehouse_type) ? addslashes($area->warehouse_type) : '';

            $province_list = $this->areaService->getWarehouseProvince();

            $city_list = Region::where('parent_id', $this->province_id)->get();
            $city_list = $city_list ? $city_list->toArray() : [];

            $district_list = Region::where('parent_id', $city_id)->get();
            $district_list = $district_list ? $district_list->toArray() : [];

            $warehouse_list = get_warehouse_list_goods();
            $warehouse_name = get_warehouse_name_id($region_id);

            foreach ($province_list as $k => $v) {
                $province_list[$k]['choosable'] = true;
            }
            foreach ($city_list as $k => $v) {
                $city_list[$k]['choosable'] = true;
            }
            foreach ($district_list as $k => $v) {
                $district_list[$k]['choosable'] = true;
            }

            $this->smarty->assign('province_list', $province_list); //省、直辖市
            $this->smarty->assign('city_list', $city_list); //省下级市
            $this->smarty->assign('district_list', $district_list);//市下级县
            $this->smarty->assign('goods_id', $goods_id); //商品ID
            $this->smarty->assign('warehouse_list', $warehouse_list);
            $this->smarty->assign('warehouse_name', $warehouse_name); //仓库名称
            $this->smarty->assign('region_id', $region_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('area_city', $area_city);
            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('merchant_id', $merchant_id);
            $this->smarty->assign('warehouse_type', $warehouse_type); //仓库跳转标识

            /* 获得用户所有的收货人信息 */
            $consignee_list = get_new_consignee_list($user_id);
            $this->smarty->assign('consignee_list', $consignee_list); //收货地址列表

            /* 获取默认收货ID */
            $address_id = Users::where('user_id', $user_id)->value('address_id');
            $this->smarty->assign('address_id', $address_id); //收货地址列表

            $province_row = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $province_id)->first();
            $province_row = $province_row ? $province_row->toArray() : [];
            $city_row = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $city_id)->first();
            $city_row = $city_row ? $city_row->toArray() : [];
            $district_row = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $district_id)->first();
            $district_row = $district_row ? $district_row->toArray() : [];

            $this->smarty->assign('province_row', $province_row);
            $this->smarty->assign('city_row', $city_row);
            $this->smarty->assign('district_row', $district_row);
            $this->smarty->assign('show_warehouse', $this->config['show_warehouse']); //ecmoban模板堂 --zhuo 开启可选仓库

            $result['goods_id'] = $goods_id;
            $result['area'] = [
                'region_id' => $region_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id' => $district_id,
                'street_id' => $street_id,
                'street_list' => $street_list
            ];

            $result['is_theme'] = 1;

            $result['content'] = $this->smarty->fetch('library/goods_delivery_area.lbi');
            $result['warehouse_content'] = $this->smarty->fetch('library/goods_warehouse.lbi');
        }

        /*------------------------------------------------------ */
        //-- 商品地区配送
        /*------------------------------------------------------ */
        elseif ($act == 'user_area_shipping') {
            if (request()->exists('area')) {
                $area_val = strip_tags(urldecode(request()->input('area', '')));
                $area_val = json_str_iconv($area_val);

                if (empty($area_val)) {
                    $result['error'] = 1;
                    return response()->json($result);
                }

                $area = dsc_decode($area_val);

                $goods_id = $area->goods_id;
                $province_id = $area->province_id;
                $city_id = $area->city_id;
                $district_id = $area->district_id;
                $street_id = $area->street_id;
                $street_list = $area->street_list;
                $region_id = $area->region_id;
                $area_id = $area->area_id;
                $area_city = $area->area_city;

                $region = [1, $province_id, $city_id, $district_id, $street_id, $street_list];
                $shippingFee = goodsShippingFee($goods_id, $region_id, $area_id, $area_city, $region);
                $this->smarty->assign('shippingFee', $shippingFee);

                $is_shipping = Goods::where('goods_id', $goods_id)->value('is_shipping');
                $this->smarty->assign('is_shipping', $is_shipping);

                $result['content'] = $this->smarty->fetch('library/user_area_shipping.lbi');
            } else {
                $result['content'] = '';
            }
        }

        /*------------------------------------------------------ */
        //-- 获取属性图片
        /*------------------------------------------------------ */
        elseif ($act == 'getInfo') {
            $result = ['error' => 0, 'message' => ''];
            $attr_id = intval(request()->input('attr_id', 0));
            $goods_id = intval(request()->input('goods_id', 0));

            $attr_gallery_flie = GoodsAttr::where('goods_attr_id', $attr_id)->where('goods_id', $goods_id)->value('attr_gallery_flie');

            $result['t_img'] = !empty($attr_gallery_flie) ? get_image_path($attr_gallery_flie) : '';
        }

        /*------------------------------------------------------ */
        //-- 商品降价通知
        /*------------------------------------------------------ */
        elseif ($act == 'price_notice') {
            $result = ['msg' => '', 'status' => ''];

            $goods_id = intval(request()->input('goods_id', 0));
            $email = trim(request()->input('email', ''));
            $cellphone = trim(request()->input('cellphone', ''));

            $hopeDiscount = trim(request()->input('hopeDiscount', 0));

            $add_time = gmtime();

            if ($user_id && $email) {
                $one = SaleNotice::where('goods_id', $goods_id)->where('user_id', $user_id)->count();

                if ($one) {
                    $other = [
                        'cellphone' => $cellphone,
                        'email' => $email,
                        'hopeDiscount' => $hopeDiscount,
                        'add_time' => $add_time
                    ];
                    SaleNotice::where('goods_id', $goods_id)->where('user_id', $user_id)->update($other);

                    $result['msg'] = $GLOBALS['_LANG']['update_Success'];
                } else {
                    $other = [
                        'user_id' => $user_id,
                        'goods_id' => $goods_id,
                        'cellphone' => $cellphone,
                        'email' => $email,
                        'hopeDiscount' => $hopeDiscount,
                        'add_time' => $add_time
                    ];
                    SaleNotice::insert($other);

                    $result['msg'] = $GLOBALS['_LANG']['Submit_Success'];
                }
                $result['status'] = 0;
            } else {
                $result['msg'] = $GLOBALS['_LANG']['Submit_fail'];
                $result['status'] = 1;
            }
        }

        /*------------------------------------------------------ */
        //-- 首页商品模块重新获取商品信息
        /*------------------------------------------------------ */
        elseif ($act == 'getguessYouLike') {
            $result = $this->commonService->getguessYouLike();
        }

        /*------------------------------------------------------ */
        //-- 定位
        /*------------------------------------------------------ */
        elseif ($act == 'in_stock') {
            $res = ['err_msg' => '', 'result' => '', 'qty' => 1];

            $area = $this->areaService->areaCookie();

            $goods_id = intval(request()->input('id', 0));
            $province = intval(request()->input('province', 0));
            $city = intval(request()->input('city', 0));
            $district = intval(request()->input('district', 0));

            $province = $province ? $province : $area['province'] ?? 0;
            $city = $city ? $city : $area['city'] ?? 0;
            $district = $district ? $district : $area['district'] ?? 0;

            $d_null = intval(request()->input('d_null', 0));
            $user_id = intval(request()->input('user_id', 0));

            $user_address = get_user_address_region($user_id);
            $user_address = explode(",", $user_address['region_address']);

            $street_info = Region::select('region_id')->where('parent_id', $district)->get();
            $street_info = $street_info ? $street_info->toArray() : [];
            $street_info = $street_info ? collect($street_info)->flatten()->all() : [];

            $street_list = 0;
            $street_id = 0;

            if ($street_info) {
                $street_id = $street_info[0];
                $street_list = implode(",", $street_info);
            }

            //清空
            $time = 60 * 24 * 30;
            cookie()->queue('type_province', 0, $time);
            cookie()->queue('type_city', 0, $time);
            cookie()->queue('type_district', 0, $time);

            $res['d_null'] = $d_null;

            if ($d_null == 0) {
                if (in_array($district, $user_address)) {
                    $res['isRegion'] = 1;
                } else {
                    $res['message'] = lang('goods.Distribution_message');
                    $res['isRegion'] = 88; //原为0
                }
            } else {
                $district = '';
            }

            /* 删除缓存 */
            $this->areaService->getCacheNameForget('area_cookie');
            $this->areaService->getCacheNameForget('area_info');
            $this->areaService->getCacheNameForget('warehouse_id');

            $area_cache_name = $this->areaService->getCacheName('area_cookie');

            $area_cookie_cache = [
                'province' => $province,
                'city_id' => $city,
                'district' => $district,
                'street' => $street_id,
                'street_area' => $street_list
            ];

            cache()->forever($area_cache_name, $area_cookie_cache);

            $res['goods_id'] = $goods_id;

            $flow_warehouse = get_warehouse_goods_region($province);
            cookie()->queue('flow_region', $flow_warehouse['region_id'] ?? 0, 60 * 24 * 30);

            return response()->json($res);
        }



        /*------------------------------------------------------ */
        //-- 切换商家入驻文章
        /*------------------------------------------------------ */
        elseif ($act == 'merchants_article') {
            $result = ['error' => 0, 'content' => '', 'message' => ''];

            $title = trim(request()->input('title', ''));

            $article = Article::where('title', $title)->value('content');

            if ($article) {
                $result['error'] = 1;
                $this->smarty->assign("article", html_out($article));
                $this->smarty->assign("act", $act);
                $this->smarty->assign('title', $title);
                $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            } else {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['merchants_article'];
            }
        }

        /*------------------------------------------------------ */
        //-- 购物流程判断商品是否支持配送
        /*------------------------------------------------------ */
        elseif ($act == 'flow_shipping') {

            /* 过滤 XSS 攻击和SQL注入 */
            get_request_filter();
            $rec = addslashes(request()->input('rec_id', ''));

            $shipping_list = addslashes(request()->input('shipping_list', ''));
            $shipping_list = !empty($shipping_list) ? explode(",", $shipping_list) : [];

            $cart_info = [];
            if ($rec) {
                $cart_info = explode(",", $rec);
            }

            $region = [];
            if (session('flow_consignee')) {
                $region = [
                    session('flow_consignee.country'),
                    session('flow_consignee.province'),
                    session('flow_consignee.city'),
                    session('flow_consignee.district'),
                    session('flow_consignee.street')
                ];
            }

            $rec_id = '';
            $arr = [];
            $seller = [];
            $cart_value = '';
            if ($cart_info) {
                foreach ($cart_info as $key => $row) {
                    $list = explode("|", $row);
                    $arr[$list[0]][$key] = $list[1];
                }

                $shipping_id = 0;
                foreach ($arr as $key => $row) {
                    foreach ($shipping_list as $skey => $srow) {
                        $srow = explode("-", $srow);
                        if ($srow[0] == $key) {
                            $shipping_id = $srow[1];
                        }
                    }

                    foreach ($row as $rckey => $rcrow) {
                        $list = explode("_", $rcrow);

                        $cart_value .= $list[0] . ",";

                        if ($list && $list[3]) {
                            $trow = get_goods_transport($list[3]);

                            if ($list[2] == 2) {
                                $shipping_code = get_table_date('shipping', "shipping_id='$shipping_id'", ['shipping_code'], 2);

                                $seller[$key][$list[1]][$rckey]['seller_id'] = $key;
                                $seller[$key][$list[1]][$rckey]['rec_id'] = $list[0];
                                $seller[$key][$list[1]][$rckey]['goods_id'] = $list[1];


                                //自提单独验证
                                if ($shipping_code == 'cac') {
                                    $district = intval(session('flow_consignee.district', 0));

                                    $shipping_count = ShippingPoint::whereHas('getAreaRegion', function ($query) use ($district) {
                                        $query->where('region_id', $district);
                                    });

                                    $shipping_count = $shipping_count->whereHas('getShippingArea');

                                    $shipping_count = $shipping_count->count();
                                } else {
                                    if ($trow['freight_type'] == 1) {
                                        $shipping_count = Shipping::where('enabled', 1)
                                            ->where('shipping_id', $shipping_id);

                                        $whereTpl = [
                                            'user_id' => $key,
                                            'list' => $list,
                                            'region' => $region
                                        ];
                                        $shipping_count = $shipping_count->whereHas('getGoodsTransportTpl', function ($query) use ($whereTpl) {
                                            $query->where('user_id', $whereTpl['user_id'])
                                                ->where('tid', $whereTpl['list'][3])
                                                ->whereRaw("(FIND_IN_SET('" . $whereTpl['region'][1] . "', region_id) OR FIND_IN_SET('" . $whereTpl['region'][2] . "', region_id) OR FIND_IN_SET('" . $whereTpl['region'][3] . "', region_id) OR FIND_IN_SET('" . $whereTpl['region'][4] . "', region_id))");
                                        });

                                        $shipping_count = $shipping_count->count();
                                    } else {
                                        $shipping_count = GoodsTransportExtend::where('tid', $list[3])->where('ru_id', $key);

                                        $where = [
                                            'ru_id' => $key,
                                            'shipping_id' => $shipping_id
                                        ];
                                        $shipping_count = $shipping_count->whereHas('getGoodsTransportExpress', function ($query) use ($where) {
                                            $query->where('ru_id', $where['ru_id'])
                                                ->whereRaw("FIND_IN_SET('" . $where['shipping_id'] . "', shipping_id)");
                                        });

                                        $shipping_count = $shipping_count->whereRaw("((FIND_IN_SET('" . $region[1] . "', top_area_id)) OR (FIND_IN_SET('" . $region[2] . "', area_id) OR FIND_IN_SET('" . $region[3] . "', area_id) OR FIND_IN_SET('" . $region[4] . "', area_id)))");

                                        $shipping_count = $shipping_count->count();
                                    }
                                }
                                if ($shipping_count) {
                                    $seller[$key][$list[1]][$rckey]['is_shipping'] = 1;
                                } else {
                                    $seller[$key][$list[1]][$rckey]['is_shipping'] = 0;
                                    $rec_id .= $list[0] . ",";
                                }
                            }
                        }
                    }
                }
            }

            if ($rec_id) {
                $rec_id = $this->dscRepository->delStrComma($rec_id);
                $cart_value = $this->dscRepository->delStrComma($cart_value);
                $cart_value = get_sc_str_replace($cart_value, $rec_id, 1);

                /* 取得购物类型 */
                $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

                $warehouse_id = intval(request()->input('warehouse_id', 0));
                $area_id = intval(request()->input('area_id', 0));
                $area_city = intval(request()->input('area_city', 0));
                //门店id
                $store_id = intval(request()->input('store_id', 0));
                $store_seller = request()->input('store_seller', '');

                /* 对商品信息赋值 */
                $cart_goods_list = cart_goods($flow_type, $rec_id, 1, $warehouse_id, $area_id, $area_city); // 取得商品列表，计算合计
                $cart_goods_list_new = cart_by_favourable($cart_goods_list);
                $this->smarty->assign('goods_list', $cart_goods_list_new);
                $this->smarty->assign('cart_value', $cart_value);
                $this->smarty->assign('store_seller', $store_seller);
                $this->smarty->assign('store_id', $store_id);

                $result['error'] = 1;
                $result['cart_value'] = $cart_value;
                $result['content'] = $this->smarty->fetch('library/goods_shipping_prompt.lbi');
            }
        }

        /*------------------------------------------------------ */
        //-- 登录弹框
        /*------------------------------------------------------ */
        elseif ($act == 'get_login_dialog') {
            $back_act = trim(request()->input('back_act', ''));

            $dsc_token = get_dsc_token();
            $this->smarty->assign('dsc_token', $dsc_token);

            /* 验证码相关设置 */
            $captcha = intval($this->config['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            /* 获取安装的地第三方登录 */
            $website_dir = resource_path("website/config/");
            $website_list = get_dir_file_list($website_dir, 1, "_");

            if ($website_list) {
                for ($i = 0; $i < count($website_list); $i++) {
                    if ($website_list[$i]['file'] == 'index.htm' || $website_list[$i]['file'] == 'index.html') {
                        unset($website_list[$i]);
                    }
                }
            }


            $count = !empty($website_list) ? count($website_list) : 0;
            if (file_exists(storage_public("wechat_oauth.php"))) {
                $website_list[$count]['web_type'] = 'weixin';
            }

            $this->smarty->assign('website_list', $website_list);

            $this->smarty->assign('site_domain', $this->config['site_domain']);
            $this->smarty->assign('back_act', $back_act);
            $this->smarty->assign('user_lang', lang('user'));
            $this->smarty->assign('is_jsonp', $is_jsonp);
            $result['content'] = $this->smarty->fetch('library/login_dialog_body.lbi');
        }

        /*------------------------------------------------------ */
        //-- 可视化
        //-- 删除首页模板OSS标识文件
        /*------------------------------------------------------ */
        elseif ($act == 'del_hometemplates') {
            $code = addslashes(trim(request()->input('suffix', '')));

            dsc_unlink(storage_public('data/sc_file/hometemplates/' . $code . ".php"));
        }

        /*------------------------------------------------------ */
        //-- 可视化
        //-- 删除专题模板OSS标识文件
        /*------------------------------------------------------ */
        elseif ($act == 'del_topictemplates') {
            $seller_id = addslashes(trim(request()->input('seller_id', '')));
            $code = addslashes(trim(request()->input('suffix', '')));

            dsc_unlink(storage_public('data/sc_file/topic/topic_' . $seller_id . "/" . $code . ".php"));
        }

        /*------------------------------------------------------ */
        //-- 可视化
        //-- 删除店铺模板OSS标识文件
        /*------------------------------------------------------ */
        elseif ($act == 'del_sellertemplates') {
            $seller_id = addslashes(trim(request()->input('seller_id', '')));
            $code = addslashes(trim(request()->input('suffix', '')));

            dsc_unlink(storage_public('data/sc_file/sellertemplates/seller_tem_' . $seller_id . "/" . $code . ".php"));
        }

        /*------------------------------------------------------ */
        //-- 商品收藏
        //-- 商品详情商品收藏状态与数量
        /*------------------------------------------------------ */
        elseif ($act == 'goods_collection') {
            $goods_id = intval(request()->input('goods_id', 0));

            $count = CollectGoods::where('goods_id', $goods_id)->where('user_id', $user_id)->count();
            $result['collect_count'] = $count;
            $result['is_collect'] = $result['collect_count'];
        }

        /*------------------------------------------------------ */
        //-- 店铺关注
        //-- 商品详情店铺关注状态
        /*------------------------------------------------------ */
        elseif ($act == 'goods_collect_store') {
            $seller_id = intval(request()->input('seller_id', 0));

            if ($seller_id) {
                //是否收藏店铺
                $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $seller_id)->value('rec_id');
                if ($rec_id > 0) {
                    $result['error'] = 1;
                } else {
                    $result['error'] = 0;
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 发送邮件
        /*------------------------------------------------------ */
        elseif ($act == 'ajax_send_mail') {
            $order_id = intval(request()->input('order_id', 0));

            $order_info = get_main_order_info($order_id, 1);
            $ru_id = explode(",", $order_info['all_ruId']['ru_id']);
            $order = order_info($order_id); //订单详情

            /* 订单商品 */
            $where = [
                'order_id' => $order_id
            ];
            $goods_list = $this->orderGoodsService->getOrderGoodsList($where);

            $sellerId = $ru_id[0];
            $shop_name = $this->merchantCommonService->getShopName($sellerId, 1);

            if ($sellerId == 0) {//接收邮箱的地址
                $service_email = $this->config['service_email'];
            } else {
                $seller_shopinfo = SellerShopinfo::where('ru_id', $sellerId);
                $seller_shopinfo = $this->baseRepository->getToArrayFirst($seller_shopinfo);
                $service_email = isset($seller_shopinfo['seller_email']) && !empty($seller_shopinfo['seller_email']) ? $seller_shopinfo['seller_email'] : '';
            }

            $auto_sms = $this->cronService->getSmsOpen();

            if (!empty($auto_sms)) {
                $other = [
                    'item_type' => 2,
                    'user_id' => $order['user_id'],
                    'ru_id' => $sellerId,
                    'order_id' => $order['order_id'],
                    'add_time' => gmtime()
                ];
                AutoSms::insert($other);
            } else {
                $tpl = get_mail_template('remind_of_new_order');
                $this->smarty->assign('order', $order);
                $this->smarty->assign('goods_list', $goods_list);
                $this->smarty->assign('shop_name', $shop_name);
                $this->smarty->assign('send_date', local_date($this->config['time_format'], gmtime()));
                $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                $this->commonRepository->sendEmail($this->config['shop_name'], $service_email, $tpl['template_subject'], $content, $tpl['is_html']);
            }
        }

        /*------------------------------------------------------ */
        //-- 验证码通用
        /*------------------------------------------------------ */
        elseif ($act == 'ajax_captcha') {
            $this->err->or = true;

            $captcha_str = trim(request()->input('captcha', ''));
            $seKey = trim(request()->input('seKey', ''));

            /* 验证码检查 */
            $verify = app(CaptchaVerify::class);
            $captcha_code = $verify->check($captcha_str, $seKey, '', 'ajax');

            if (!$captcha_code) {
                $this->err->or = false;
            }
            return json_encode($this->err->or);
        }

        /*------------------------------------------------------ */
        //-- 预售商品详情，看了又看
        /*------------------------------------------------------ */
        elseif ($act == 'see_more_presale') {
            $goods_id = intval(request()->input('goods_id', 0));
            $cat_id = intval(request()->input('cat_id', 0));

            $look_top = get_top_presale_goods($goods_id, $cat_id);
            $this->smarty->assign('look_top', $look_top); // 看了又看

            $result['content'] = $this->smarty->fetch('library/see_more_presale.lbi');
        }

        /*------------------------------------------------------ */
        //-- 加载首页弹出广告
        /*------------------------------------------------------ */
        elseif ($act == 'ajax_Homeindex_Bonusadv') {
            load_helper('visual');

            $suffix = trim(request()->input('suffix', ''));

            $bonusadv = getleft_attr("bonusadv", 0, $suffix, $this->config['template']);

            if (isset($bonusadv['img_file']) && $bonusadv['img_file']) {
                $bonusadv['img_file'] = get_image_path($bonusadv['img_file']);

                if (request()->hasCookie('index_img_file') && strpos($bonusadv['img_file'], request()->cookie('index_img_file')) !== false) {
                    if (request()->hasCookie('bonusadv') && request()->cookie('bonusadv') == 1) {
                        $bonusadv['img_file'] = '';
                    } else {
                        if ($bonusadv['img_file']) {
                            cookie()->queue('bonusadv', 1, 60 * 10);
                            cookie()->queue('index_img_file', $bonusadv['img_file'], 60 * 10);
                        }
                    }
                } else {
                    cookie()->queue('bonusadv', 1, 60 * 10);
                    cookie()->queue('index_img_file', $bonusadv['img_file'], 60 * 10);
                }

                //格式化
                $ad_child = [];
                $bonusadv['ad_link'] = $bonusadv['fileurl'];
                $bonusadv['ad_code'] = $bonusadv['img_file'];
                $ad_child[] = $bonusadv;
                $this->smarty->assign('ad_child', $ad_child);

                $result['content'] = $this->smarty->fetch('library/bonushome_ad.lbi');
            } else {
                $result['error'] = 1;
            }
        }

        /* ------------------------------------------------------ */
        //-- 更新首页排行商品
        /* ------------------------------------------------------ */
        elseif ($act == 'checked_home_rank') {
            $goodsids = trim(request()->input('goodsids', ''));
            $activitytype = trim(request()->input('activitytype', 'snatch'));

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));
            $area_city = intval(request()->input('area_city', 0));

            $time = gmtime();
            if ($activitytype == 'is_new' || $activitytype == 'is_best' || $activitytype == 'is_hot') {
                $goods_list = $this->goodsCommonService->getFloorAjaxGoods([], 6, $warehouse_id, $area_id, $area_city, $goodsids, 0, $activitytype);
            } else {
                $goods_list = Goods::whereRaw(1);

                if (!empty($goodsids)) {
                    $goodsids = !is_array($goodsids) ? explode(",", $goodsids) : $goodsids;
                    $goods_list = $goods_list->whereIn('goods_id', $goodsids);
                }

                if ($activitytype == 'exchange') {
                    $goods_list = $goods_list->whereHas('getExchangeGoods', function ($query) {
                        $query->where('review_status', 3)
                            ->where('is_exchange', 1);
                    });

                    $goods_list = $goods_list->where('is_delete', 0);

                    $goods_list = $goods_list->with(['getExchangeGoods']);
                } elseif ($activitytype == 'presale') {
                    $goods_list = $goods_list->whereHas('getPresaleActivity', function ($query) use ($time) {
                        $query->where('review_status', 3)
                            ->where('start_time', '<=', $time)
                            ->where('end_time', '>=', $time)
                            ->where('is_finished', 0);
                    });

                    $goods_list = $goods_list->with(['getPresaleActivity']);
                } else {
                    if ($activitytype == 'snatch') {
                        $act_type = GAT_SNATCH;
                    } elseif ($activitytype == 'auction') {
                        $act_type = GAT_AUCTION;
                    } elseif ($activitytype == 'group_buy') {
                        $act_type = GAT_GROUP_BUY;
                    }

                    $activityWhere = [
                        'time' => $time,
                        'act_type' => $act_type
                    ];
                    $goods_list = $goods_list->whereHas('getGoodsActivity', function ($query) use ($activityWhere) {
                        $query->where('review_status', 3)
                            ->where('start_time', '<=', $activityWhere['time'])
                            ->where('end_time', '>=', $activityWhere['time'])
                            ->where('is_finished', 0)
                            ->where('act_type', $activityWhere['act_type']);
                    });

                    $goods_list = $goods_list->with(['getGoodsActivity']);
                }

                $goods_list = $goods_list->take(6);

                $goods_list = $goods_list->get();

                $goods_list = $goods_list ? $goods_list->toArray() : [];

                if (!empty($goods_list)) {
                    foreach ($goods_list as $key => $val) {
                        $val = isset($val['get_exchange_goods']) && $val['get_exchange_goods'] ? array_merge($val, $val['get_exchange_goods']) : $val;
                        $val = isset($val['get_presale_activity']) && $val['get_presale_activity'] ? array_merge($val, $val['get_presale_activity']) : $val;
                        $val = isset($val['get_goods_activity']) && $val['get_goods_activity'] ? array_merge($val, $val['get_goods_activity']) : $val;

                        if ($val['promote_price'] > 0 && $time >= $val['promote_start_date'] && $time <= $val['promote_end_date']) {
                            $goods_list[$key]['promote_price'] = price_format($this->goodsCommonService->getBargainPrice($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']));
                        } else {
                            $goods_list[$key]['promote_price'] = '';
                        }

                        $goods_list[$key]['market_price'] = price_format($val['market_price']);
                        $goods_list[$key]['shop_price'] = price_format($val['shop_price']);
                        $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
                        $goods_list[$key]['original_img'] = get_image_path($val['original_img']);

                        if ($activitytype == 'snatch') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('snatch', ['sid' => $val['act_id']]);
                        } elseif ($activitytype == 'auction') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('auction', ['auid' => $val['act_id']]);
                            $ext_info = unserialize($val['ext_info']);
                            $auction = array_merge($val, $ext_info);
                            $goods_list[$key]['promote_price'] = price_format($auction['start_price']);
                        } elseif ($activitytype == 'group_buy') {
                            $ext_info = unserialize($val['ext_info']);
                            $group_buy = array_merge($val, $ext_info);
                            $goods_list[$key]['promote_price'] = price_format($group_buy['price_ladder'][0]['price']);
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('group_buy', ['gbid' => $val['act_id']]);
                        } elseif ($activitytype == 'exchange') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('exchange_goods', ['gid' => $val['goods_id']], $val['goods_name']);
                            $goods_list[$key]['exchange_integral'] = "积分：" . $val['exchange_integral'];
                        } elseif ($activitytype == 'presale') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('presale', ['act' => 'view', 'presaleid' => $val['act_id']], $val['goods_name']);
                        }
                        $goods_list[$key]['goods_name'] = !empty($val['act_name']) ? $val['act_name'] : $val['goods_name'];
                    }
                }
            }

            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('activitytype', $activitytype);
            $this->smarty->assign('type', 'home_rank');
            $result['content'] = $GLOBALS['smarty']->fetch('library/guessYouLike_list.lbi');
        }

        /* ------------------------------------------------------ */
        //-- 检测客服
        /* ------------------------------------------------------ */
        elseif ($act == 'check_kefu') {
            $result['content'] = is_dir(__DIR__ . '/kefu') ? 1 : 0;
        }

        /*------------------------------------------------------ */
        //-- 头部名称
        /*------------------------------------------------------ */
        elseif ($act == 'header_region_name') {
            $result = $this->commonService->headerRegionName($this->region_name);
        }

        /*------------------------------------------------------ */
        //-- 猜你喜欢
        /*------------------------------------------------------ */
        elseif ($act == 'guess_you_like') {

            $where = [
                'warehouse_id' => $this->warehouseId(),
                'area_id' => $this->areaId(),
                'area_city' => $this->areaCity(),
                'user_id' => session('user_id', 0),
                'history' => 1,
                'page' => 1,
                'limit' => 7
            ];

            $guess_goods = app(GoodsGuessService::class)->getGuessGoods($where);

            $this->smarty->assign('guess_goods', $guess_goods);

            $result['content'] = $this->smarty->fetch('library/ajax_guess_you_like.lbi');
        }

        return response()->json($result);
    }
}
