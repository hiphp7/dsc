<?php

namespace App\Modules\Suppliers\Controllers;

use App\Models\AdminUser;
use App\Models\Agency;
use App\Models\DeliveryOrder;
use App\Models\MerchantsShopInformation;
use App\Models\OrderGoods;
use App\Models\PayLog;
use App\Models\Payment;
use App\Models\Region;
use App\Models\SellerDomain;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\ShippingTpl;
use App\Models\Suppliers;
use App\Models\UserAddress;
use App\Models\UserRank;
use App\Models\Users;
use App\Models\UsersVatInvoicesInfo;
use App\Models\Wholesale;
use App\Models\WholesaleDeliveryGoods;
use App\Models\WholesaleDeliveryOrder;
use App\Models\WholesaleOrderAction;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleOrderReturn;
use App\Models\WholesaleProducts;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Payment\PaymentService;
use App\Services\Wholesale\OrderManageService;
use App\Services\Wholesale\OrderService as WholesaleOrderService;
use App\Services\Wholesale\WholesaleService;
use Illuminate\Support\Str;

/**
 * 记录管理员操作日志
 */
class OrderController extends InitController
{
    protected $orderManageService;
    protected $config;
    protected $paymentService;
    protected $baseRepository;
    protected $wholesaleService;
    protected $wholesaleOrderService;
    protected $commonRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $orderCommonService;

    public function __construct(
        OrderManageService $orderManageService,
        PaymentService $paymentService,
        BaseRepository $baseRepository,
        WholesaleService $wholesaleService,
        WholesaleOrderService $wholesaleOrderService,
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        OrderCommonService $orderCommonService
    )
    {
        $this->orderManageService = $orderManageService;
        $this->paymentService = $paymentService;
        $this->baseRepository = $baseRepository;
        $this->wholesaleService = $wholesaleService;
        $this->wholesaleOrderService = $wholesaleOrderService;
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderCommonService = $orderCommonService;
    }

    public function index()
    {
        load_helper(['order']);

        $this->dscRepository->helpersLang(['wholesale_order'], 'suppliers');

        $this->smarty->assign('menus', session('menus', ''));
        $this->smarty->assign('action_type', "wholesale_order");

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_suppliers_order']);
        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        /*------------------------------------------------------ */
        //-- 采购订单列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('suppliers_order_view');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_order_list']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_suppliers_order']);
            $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '01_order_list'));
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('status_list', $GLOBALS['_LANG']['qs']);   // 订单状态

            $list = $this->wholesale_order_list();

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);

            $this->smarty->assign('order_list', $list['orders']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);


            return $this->smarty->display('order_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 订单导出
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'order_export') {
            setlocale(LC_ALL, 'en_US.UTF-8');
            $filename = local_date('YmdHis') . ".csv";
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=" . $filename);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');

            $order_list = $this->wholesale_order_list();

            echo $this->download_orderlist($order_list['orders']);
            exit;
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('suppliers_order_view');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $list = $this->wholesale_order_list();

            /* 订单状态传值 */
            $composite_status = isset($_REQUEST['composite_status']) ? trim($_REQUEST['composite_status']) : -1;
            $this->smarty->assign('status', $composite_status);

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);

            $this->smarty->assign('order_list', $list['orders']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $sort_flag = sort_flag($list['filter']);

            if ($sort_flag) {
                $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            }

            return make_json_result($this->smarty->fetch('order_list.dwt'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 订单详情页面
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'info') {
            admin_priv('suppliers_order_view');
            /* 根据订单id或订单号查询订单信息 */
            if (isset($_REQUEST['order_id'])) {
                $order_id = intval($_REQUEST['order_id']);
                $order = $this->orderManageService->wholesaleOrderInfo($order_id);
            } elseif (isset($_REQUEST['order_sn'])) {
                $order_sn = trim($_REQUEST['order_sn']);
                $order = $this->orderManageService->wholesaleOrderInfo(0, $order_sn);
            } else {
                /* 如果参数不存在，退出 */
                die('invalid parameter');
            }

            /* 如果订单不存在，退出 */
            if (empty($order)) {
                die('order does not exist');
            }

            /* 取得用户名 */
            $user = [];
            if ($order['user_id'] > 0) {

                $user = Users::where('user_id', $order['user_id']);
                $user = $this->baseRepository->getToArrayFirst($user);

                if (!empty($user)) {
                    $order['user_name'] = $user['user_name'];
                }
            }


            /* 格式化金额 */
            if ($order['order_amount'] < 0) {
                $order['money_refund'] = abs($order['order_amount']);
                $order['formated_money_refund'] = price_format(abs($order['order_amount']));
            }

            /* 其他处理 */
            $order['order_time'] = local_date($this->config['time_format'], $order['add_time']);
            $order['status'] = $GLOBALS['_LANG']['os'][$order['order_status']];

            $order_child = WholesaleOrderInfo::where('main_order_id', $order['order_id'])->count();
            $order['order_child'] = $order_child;

            $order['status'] = $GLOBALS['_LANG']['ps'][$order['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$order['shipping_status']] . ',' . $GLOBALS['_LANG']['os'][$order['order_status']];

            /* 取得能执行的操作列表 */
            $operable_list = $this->operable_list($order);
            $this->smarty->assign('operable_list', $operable_list);

            /* 已安装快递列表 */
            $this->smarty->assign('shipping_list', shipping_list());

            /*增值发票 start*/
            if ($order['invoice_type'] == 1) {
                $res = UsersVatInvoicesInfo::where('user_id', $order['user_id']);
                $res = $this->baseRepository->getToArrayFirst($res);

                if (!empty($res)) {
                    $region = [
                        'province' => $res['province'],
                        'city' => $res['city'],
                        'district' => $res['district']
                    ];
                    $res['region'] = get_area_region_info($region);

                    $this->smarty->assign('vat_info', $res);
                }
            }
            /*增值发票 end*/

            /* 参数赋值：订单 */
            $this->smarty->assign('order', $order);

            /* 取得用户信息 */
            if ($order['user_id'] > 0) {
                /* 用户等级 */
                $rank_name = UserRank::whereRaw(1);

                if ($user) {
                    if ($user['user_rank'] > 0) {
                        $rank_name = $rank_name->where('rank_id', $user['user_rank']);
                    } else {
                        $rank_name = $rank_name->where('min_points', '<=', intval($user['rank_points']))
                            ->orderByRaw('min_points DESC');
                    }

                    $user['rank_name'] = $rank_name->value('rank_name');
                }

                // 地址信息
                $address_list = UserAddress::where('user_id', $order['user_id']);
                $address_list = $this->baseRepository->getToArrayGet($address_list);

                $this->smarty->assign('address_list', $address_list);
            }

            /* 取得订单商品及货品 */
            $goods_list = array();
            $goods_attr = array();
            $res = WholesaleOrderGoods::where('order_id', $order['order_id']);
            $res = $res->with([
                'getWholesaleProducts',
                'getWholesale' => function ($query) {
                    $query->with('getSuppliers');
                }
            ]);

            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $key => $row) {
                    $wholesale = $row['get_wholesale'];
                    if ($row['product_id']) {
                        $product_number = WholesaleProducts::where('product_id', $row['product_id'])
                            ->where('goods_id', $row['goods_id'])
                            ->value('product_number');
                        $row['storage'] = $product_number ? $product_number : 0;
                    } else {
                        $row['storage'] = $wholesale['goods_number'] ?? 0;
                    }

                    $row['suppliers_id'] = $wholesale['suppliers_id'] ?? 0;
                    $row['brand_id'] = $wholesale['brand_id'] ?? 0;
                    $row['goods_thumb'] = $wholesale['goods_thumb'] ?? '';
                    $row['bar_code'] = $wholesale['bar_code'] ?? '';
                    $row['ru_id'] = $wholesale['get_suppliers']['user_id'] ?? 0;
                    $row['product_sn'] = $row['get_wholesale_products']['product_sn'] ?? '';

                    $_goods_thumb = $row['goods_thumb'] ? get_image_path($row['goods_thumb']) : '';
                    $row['goods_thumb'] = $_goods_thumb;

                    $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                    $row['formated_goods_price'] = price_format($row['goods_price']);


                    $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

                    $goods_list[] = $row;
                }
            }

            $attr = array();
            if ($goods_attr) {
                foreach ($goods_attr as $index => $array_val) {

                    $array_val = $this->baseRepository->getExplode($array_val);

                    if ($array_val) {
                        foreach ($array_val as $value) {
                            $arr = explode(':', $value);//以 : 号将属性拆开
                            $attr[$index][] = @array('name' => $arr[0], 'value' => $arr[1]);
                        }
                    }
                }
            }

            $this->smarty->assign('goods_attr', $attr);
            $this->smarty->assign('goods_list', $goods_list);

            /**
             * 取得用户收货时间 以快物流信息显示为准，目前先用用户收货时间为准
             */
            $res_time = WholesaleOrderAction::where('order_id', $order['order_id'])->value('log_time');
            $res_time = $res_time ? local_date($this->config['time_format'], $res_time) : 0;
            $this->smarty->assign('res_time', $res_time);

            /* 是否打印订单，分别赋值 */
            if (isset($_GET['print'])) {
                $this->smarty->assign('shop_url', $this->dsc->url());
                $this->smarty->assign('print_time', local_date($this->config['time_format']));
                $this->smarty->assign('action_user', session('supply_name'));

                $this->smarty->template_dir = '../' . DATA_DIR;
                return $this->smarty->display('order_print.html');
            } /* 打印快递单 */
            elseif (isset($_GET['shipping_print'])) {

                //商家店铺信息打印到订单和快递单上
                $store = SellerShopinfo::where('ru_id', $order['ru_id']);
                $store = $this->baseRepository->getToArrayFirst($store);

                //发货地址所在地
                $region_array = [];
                $region = Region::whereRaw(1);
                $region = $this->baseRepository->getToArrayGet($region);

                if (!empty($region)) {
                    foreach ($region as $region_data) {
                        $region_array[$region_data['region_id']] = $region_data['region_name'];
                    }
                }

                $region_array[$store['province']] = $region_array[$store['province']] ?? '';
                $region_array[$store['city']] = $region_array[$store['city']] ?? '';
                $region_array[$store['district']] = $region_array[$store['district']] ?? '';

                $this->smarty->assign('shop_name', $store['shop_name']);
                $this->smarty->assign('order_id', $order_id);
                $this->smarty->assign('province', $region_array[$store['province']]);
                $this->smarty->assign('city', $region_array[$store['city']]);
                $this->smarty->assign('district', $region_array[$store['district']]);
                $this->smarty->assign('shop_address', $store['shop_address']);
                $this->smarty->assign('service_phone', $store['kf_tel']);

                $shipping = ShippingTpl::where('shipping_id', $order['shipping_id'])
                    ->where('ru_id', $order['ru_id']);
                $shipping = $this->baseRepository->getToArrayFirst($shipping);

                //打印单模式
                if ($shipping['print_model'] == 2) {
                    /* 可视化 */
                    /* 快递单 */
                    $shipping['print_bg'] = empty($shipping['print_bg']) ? '' : $this->get_site_root_url() . $shipping['print_bg'];

                    /* 取快递单背景宽高 */
                    if (!empty($shipping['print_bg'])) {
                        $_size = @getimagesize($shipping['print_bg']);

                        if ($_size != false) {
                            $shipping['print_bg_size'] = array('width' => $_size[0], 'height' => $_size[1]);
                        }
                    }

                    if (empty($shipping['print_bg_size'])) {
                        $shipping['print_bg_size'] = array('width' => '1024', 'height' => '600');
                    }

                    /* 标签信息 */
                    $lable_box = array();
                    $lable_box['t_shop_country'] = $region_array[$store['country']]; //网店-国家
                    $lable_box['t_shop_city'] = $region_array[$store['city']]; //网店-城市
                    $lable_box['t_shop_province'] = $region_array[$store['province']]; //网店-省份

                    $ru_id = OrderGoods::whereHas('getOrder', function ($query) use ($order) {
                        $query->where('order_id', $order['order_id']);
                    })->value('ru_id');

                    if ($ru_id > 0) {
                        $shop_info = MerchantsShopInformation::where('user_id', $ru_id);
                        $shop_info = $this->baseRepository->getToArrayFirst($shop_info);

                        $lable_box['t_shop_name'] = $shop_info['shoprz_brandName'] . $shop_info['shopNameSuffix']; //店铺-名称
                    } else {
                        $lable_box['t_shop_name'] = $this->config['shop_name']; //网店-名称
                    }

                    $lable_box['t_shop_district'] = $region_array[$store['district']]; //网店-区/县
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
                    $shipping_code = Shipping::where('shipping_id', $order['shipping_id'])->value('shipping_code');

                    $modules = [];
                    if ($shipping_code) {
                        $shipping_name = Str::studly($shipping_code);
                        $modules = plugin_path('Shipping/' . $shipping_name . '/config.php');

                        if (file_exists($modules)) {
                            $modules = include_once($modules);
                        }
                    }

                    $this->smarty->assign('modules', $modules);

                    if (!empty($GLOBALS['_LANG']['shipping_print'])) {
                        echo $this->smarty->fetch("str:" . $GLOBALS['_LANG']['shipping_print']);
                    } else {
                        echo $GLOBALS['_LANG']['no_print_shipping'];
                    }
                }
            } else {
                /* 模板赋值 */
                $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_suppliers_order']);
                $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '01_order_list'));
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_info']);
                $this->smarty->assign('action_link', array('href' => 'order.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['02_order_list']));

                /* 取得订单操作记录 */
                $act_list = array();

                $res = WholesaleOrderAction::where('order_id', $order['order_id'])
                    ->orderByRaw('log_time, action_id DESC');
                $res = $this->baseRepository->getToArrayGet($res);

                if ($res) {
                    foreach ($res as $key => $row) {
                        $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                        $row['pay_status'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
                        $row['shipping_status'] = $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                        $row['action_time'] = local_date($this->config['time_format'], $row['log_time']);
                        $act_list[] = $row;
                    }
                }

                $this->smarty->assign('action_list', $act_list);

                /* 显示模板 */

                return $this->smarty->display('order_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 订单操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'operate') {
            /* 检查权限 */
            admin_priv('suppliers_order_view');

            $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '01_order_list'));

            $order_id = isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id']) ? $_REQUEST['order_id'] : 0;
            $rec_id = isset($_REQUEST['rec_id']) && !empty($_REQUEST['rec_id']) ? intval($_REQUEST['rec_id']) : 0;
            $ret_id = isset($_REQUEST['ret_id']) && !empty($_REQUEST['ret_id']) ? intval($_REQUEST['ret_id']) : 0;

            $order_id_list = $order_id ? explode(',', $order_id) : [];
            /* 取得订单id（可能是多个，多个sn）和操作备注（可能没有） */
            $batch = isset($_REQUEST['batch']); //是否批处理
            $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';
            /* 订单删除 */
            if (isset($_POST['remove'])) {
                $require_note = false;
                $operation = 'remove';

                if ($batch) {
                    /* 删除订单 */
                    if ($order_id_list) {
                        foreach ($order_id_list as $row) {
                            $order = $this->orderManageService->wholesaleOrderInfo(0, $row);
                            /* 检查能否操作 */
                            if ($order) {
                                WholesaleOrderInfo::where('order_sn', $row)
                                    ->update([
                                        'is_delete' => 1
                                    ]);
                                /* todo 记录日志 */
                                admin_log($row, 'remove', 'order');
                            }
                        }
                    }

                    /* 返回 */
                    return sys_msg($GLOBALS['_LANG']['order_removed'], 0, array(array('href' => 'order.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['return_list'])));
                }
            } /* 批量打印订单 */
            elseif (isset($_POST['print'])) {
                if (empty($_POST['order_id'])) {
                    return sys_msg($GLOBALS['_LANG']['pls_select_order']);
                }

                if (isset($this->config['tp_api']) && $this->config['tp_api']) {
                    //快递鸟、电子面单 start
                    $url = 'tp_api.php?act=order_print&order_sn=' . $_POST['order_id'] . '&order_type=wholesale_order';
                    return dsc_header("Location: $url\n");
                    //快递鸟、电子面单 end
                }

                /* 赋值公用信息 */
                $this->smarty->assign('print_time', local_date($this->config['time_format']));
                $this->smarty->assign('action_user', session('supply_name'));

                $html = '';
                $order_sn_list = explode(',', $_POST['order_id']);
                foreach ($order_sn_list as $order_sn) {
                    /* 取得订单信息 */
                    $order = $this->orderManageService->wholesaleOrderInfo(0, $order_sn);
                    if (empty($order)) {
                        continue;
                    }

                    $user_id = !empty($order['user_id']) ? $order['user_id'] : 0;

                    /* 取得用户名 */
                    if ($user_id > 0) {

                        $user = Users::where('user_id', $user_id);
                        $user = $this->baseRepository->getToArrayFirst($user);

                        if (!empty($user)) {
                            $order['user_name'] = $user['user_name'];
                        }
                    }


                    /* 其他处理 */
                    $add_time = !empty($order['add_time']) ? $order['add_time'] : 0;
                    $order['order_time'] = local_date($this->config['time_format'], $add_time);
                    $order_status = !empty($order['order_status']) ? $order['order_status'] : 0;
                    $order['status'] = $GLOBALS['_LANG']['os'][$order_status];


                    /* 参数赋值：订单 */
                    $this->smarty->assign('order', $order);

                    /* 取得订单商品 */
                    $order_id = !empty($order['order_id']) ? $order['order_id'] : 0;
                    $goods_list = array();
                    $goods_attr = array();

                    $res = WholesaleOrderGoods::where('order_id', $order_id);
                    $res = $res->with([
                        'getWholesale' => function ($query) {
                            $query->with([
                                'getSuppliers'
                            ]);
                        },
                        'getWholesaleOrderInfo'
                    ]);

                    $res = $this->baseRepository->getToArrayGet($res);

                    if ($res) {
                        foreach ($res as $key => $row) {
                            $wholesale = $row['get_wholesale'];

                            $row['goods_thumb'] = $wholesale['goods_thumb'] ?? '';
                            $row['goods_sn'] = $wholesale['goods_sn'] ?? '';
                            $row['brand_id'] = $wholesale['brand_id'] ?? 0;
                            $row['storage'] = $wholesale['goods_number'] ?? 0;
                            $row['goods_id'] = $wholesale['goods_id'] ?? 0;
                            $row['ru_id'] = $wholesale['get_suppliers']['user_id'] ?? 0;
                            $row['order_sn'] = $wholesale['get_wholesale_order_info']['order_sn'] ?? '';

                            if (empty($prod)) { //当商品没有属性库存时
                                $row['goods_storage'] = $row['storage'];
                            }
                            $row['storage'] = !empty($row['goods_storage']) ? $row['goods_storage'] : 0;
                            $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                            $row['formated_goods_price'] = price_format($row['goods_price']);
                            $row['goods_id'] = $row['goods_id'];
                            //图片显示
                            $row['goods_thumb'] = get_image_path($row['goods_thumb']);

                            $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
                            $goods_list[] = $row;
                        }
                    }

                    $attr = array();

                    if ($goods_attr) {
                        foreach ($goods_attr as $index => $array_val) {

                            $array_val = $this->baseRepository->getExplode($array_val);

                            if ($array_val) {
                                foreach ($array_val as $value) {
                                    $arr = explode(':', $value);//以 : 号将属性拆开
                                    $attr[$index][] = @array('name' => $arr[0], 'value' => $arr[1]);
                                }
                            }
                        }
                    }

                    /* 取得商家信息 */
                    $store = SellerShopinfo::where('ru_id', $order['ru_id']);
                    $store = $this->baseRepository->getToArrayFirst($store);

                    $store['shop_name'] = $this->merchantCommonService->getShopName($order['ru_id'], 1);

                    $domain_name = SellerDomain::where('ru_id', $order['ru_id'])
                        ->where('is_enable', 1);
                    $domain_name = $domain_name->value('domain_name');
                    $this->smarty->assign('domain_name', $domain_name);

                    $this->smarty->assign('shop_name', $store['shop_name']);
                    $this->smarty->assign('shop_url', url('/') . '/');
                    $this->smarty->assign('shop_address', $store['shop_address']);
                    $this->smarty->assign('service_phone', $store['kf_tel']);

                    $this->smarty->assign('goods_attr', $attr);
                    $this->smarty->assign('goods_list', $goods_list);
                    $this->smarty->template_dir = '../' . DATA_DIR;
                    $html .= $this->smarty->fetch('wholesale_order_print.html') .
                        '<div style="PAGE-BREAK-AFTER:always"></div>';
                }

                echo $html;
                exit;
            } /* 分单 */
            elseif (isset($_POST['ship'])) {
                $order_id = intval(trim($order_id));
                $action_note = trim($action_note);

                /* 查询：根据订单id查询订单信息 */
                if (!empty($order_id)) {
                    $order = $this->orderManageService->wholesaleOrderInfo($order_id);
                } else {
                    die('order does not exist');
                }

                /* 查询：取得用户名 */
                if ($order['user_id'] > 0) {

                    $user = Users::where('user_id', $order['user_id']);
                    $user = $this->baseRepository->getToArrayFirst($user);

                    if (!empty($user)) {
                        $order['user_name'] = $user['user_name'];
                    }
                }

                /* 查询：其他处理 */
                $order['order_time'] = local_date($this->config['time_format'], $order['add_time']);
                $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED ? $GLOBALS['_LANG']['ss'][SS_UNSHIPPED] : $order['invoice_no'];
                $order['pay_time'] = $order['pay_time'] ? $order['pay_time'] : $GLOBALS['_LANG']['ps'][PS_UNPAYED];
                $order['shipping_time'] = $order['shipping_time'] ? $order['shipping_time'] : $GLOBALS['_LANG']['ss'][SS_UNSHIPPED];

                /* 查询：取得订单商品 */
                $_goods = $this->get_wholesale_order_goods($order['order_id']);

                $attr = $_goods['attr'];
                $goods_list = $_goods['goods_list'];
                unset($_goods);

                /* 模板赋值 */
                $this->smarty->assign('order', $order);
                $this->smarty->assign('goods_attr', $attr);
                $this->smarty->assign('goods_list', $goods_list);
                $this->smarty->assign('order_id', $order_id); // 订单id
                $this->smarty->assign('operation', 'split'); // 订单id
                $this->smarty->assign('action_note', $action_note); // 发货操作信息
                $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '01_order_list'));

                /* 显示模板 */
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_operate'] . $GLOBALS['_LANG']['op_split']);

                /* 取得订单操作记录 */
                $act_list = array();

                $res = WholesaleOrderAction::where('order_id', $order_id)
                    ->orderByRaw('log_time, action_id desc');
                $res = $this->baseRepository->getToArrayGet($res);

                if ($res) {
                    foreach ($res as $key => $row) {
                        $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                        $row['pay_status'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
                        $row['shipping_status'] = $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                        $row['action_time'] = local_date($this->config['time_format'], $row['log_time']);
                        $act_list[] = $row;
                    }
                }

                $this->smarty->assign('action_list', $act_list);

                return $this->smarty->display('order_delivery_info.dwt');
                $this->smarty->assign('operable_list', $operable_list);
            }
            /* ------------------------------------------------------ */
            //-- start 一键发货
            /* ------------------------------------------------------ */
            elseif (isset($_POST['to_shipping'])) {
                /* 定义当前时间 */
                $invoice_no = empty($_REQUEST['invoice_no']) ? '' : trim($_REQUEST['invoice_no']);  //快递单号
                $shipping_id = empty($_REQUEST['shipping_id']) ? '' : trim($_REQUEST['shipping_id']);  //快递公司ID

                if (empty($invoice_no)) {
                    /* 操作失败 */
                    $links[] = array('text' => $GLOBALS['_LANG']['invoice_no_null'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                    return sys_msg($GLOBALS['_LANG']['act_false'], 0, $links);
                }

                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

                $order_id = intval(trim($order_id));

                /* 查询：根据订单id查询订单信息 */
                if (!empty($order_id)) {
                    $order = $this->orderManageService->wholesaleOrderInfo($order_id);
                } else {
                    die('order does not exist');
                }

                /* 查询：其他处理 */
                $order['order_time'] = local_date($this->config['time_format'], $order['add_time']);
                $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $GLOBALS['_LANG']['ss'][SS_UNSHIPPED] : $order['invoice_no'];

                /* 检查能否操作 */
                $operable_list = $this->operable_list($order);
                $this->smarty->assign('operable_list', $operable_list);

                /* 取得订单商品 */
                $_goods = $this->get_order_goods(array('order_id' => $order_id, 'order_sn' => $order['order_sn']));
                $goods_list = $_goods['goods_list'];

                /* 更改订单商品发货数量 */
                if (!empty($goods_list)) {
                    foreach ($goods_list as $value) {
                        WholesaleOrderGoods::where('order_id', $value['order_id'])
                            ->where('goods_id', $value['goods_id'])
                            ->where('product_id', $value['product_id'])
                            ->update([
                                'send_number' => $value['goods_number']
                            ]);
                    }
                }

                $delivery['order_sn'] = trim($order['order_sn']);
                $delivery['add_time'] = trim($order['order_time']);
                $delivery['user_id'] = intval(trim($order['user_id']));
                $delivery['shipping_id'] = trim($shipping_id);
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
                $delivery['shipping_name'] = trim($order['shipping_name']);

                /* 取得订单商品 */
                $_goods = $this->get_wholesale_order_goods($order_id);
                $goods_list = $_goods['goods_list'];

                /* 检查此单发货商品库存缺货情况 */
                /* $goods_list已经过处理 超值礼包中商品库存已取得 */
                $virtual_goods = array();
                $package_virtual_goods = array();
                /* 生成发货单 */
                /* 获取发货单号和流水号 */
                $delivery['delivery_sn'] = get_delivery_sn();
                $delivery_sn = $delivery['delivery_sn'];

                /* 获取当前操作员 */
                $delivery['action_user'] = session('supply_name');

                /* 获取发货单生成时间 */
                $delivery['update_time'] = GMTIME_UTC;
                $delivery_time = $delivery['update_time'];
                $add_time = WholesaleOrderInfo::where('order_sn', $delivery['order_sn'])->value('add_time');
                $delivery['add_time'] = $add_time ? $add_time : 0;

                /* 设置默认值 */
                $delivery['status'] = 2; // 正常
                $delivery['order_id'] = $order_id;

                $suppliers_id = AdminUser::where('user_id', session('supply_id'))->value('suppliers_id');
                $suppliers_id = $suppliers_id ? $suppliers_id : 0;
                $delivery['suppliers_id'] = $suppliers_id;

                /* 过滤字段项 */
                $filter_fileds = array(
                    'order_sn', 'add_time', 'user_id', 'shipping_id',
                    'consignee', 'address', 'country', 'province', 'city', 'district', 'sign_building',
                    'email', 'zipcode', 'tel', 'mobile', 'postscript',
                    'delivery_sn', 'action_user', 'update_time',
                    'suppliers_id', 'status', 'order_id', 'shipping_name'
                );
                $_delivery = array();
                foreach ($filter_fileds as $value) {
                    $_delivery[$value] = $delivery[$value];
                }

                /* 发货单入库 */
                $delivery_id = WholesaleDeliveryOrder::insertGetId($_delivery);

                if ($delivery_id) {
                    $delivery_goods = array();

                    //发货单商品入库
                    if (!empty($goods_list)) {
                        //分单操作
                        $split_action_note = "";

                        foreach ($goods_list as $value) {
                            // 商品（实货）
                            if (empty($value['extension_code'])) {
                                $delivery_goods = array(
                                    'delivery_id' => $delivery_id,
                                    'goods_id' => $value['goods_id'],
                                    'product_id' => $value['product_id'],
                                    'goods_id' => $value['goods_id'],
                                    'goods_name' => addslashes($value['goods_name']),
                                    'goods_sn' => $value['goods_sn'],
                                    'send_number' => $value['goods_number'],
                                    'parent_id' => 0,
                                    'is_real' => $value['is_real'],
                                    'goods_attr' => addslashes($value['goods_attr'])
                                );
                                /* 如果是货品 */
                                if (!empty($value['product_id'])) {
                                    $delivery_goods['product_id'] = $value['product_id'];
                                }

                                WholesaleDeliveryGoods::insert($delivery_goods);

                                //分单操作
                                $split_action_note .= sprintf($GLOBALS['_LANG']['split_action_note'], $value['goods_sn'], $value['goods_number']) . "<br/>";
                            }
                        }
                    }
                } else {
                    /* 操作失败 */
                    $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                    return sys_msg($GLOBALS['_LANG']['act_false'], 1, $links);
                }
                unset($filter_fileds, $delivery, $_delivery);

                /* 定单信息更新处理 */
                if (true) {
                    /* 标记订单为已确认 “发货中” */
                    /* 更新发货时间 */
                    $shipping_status = SS_SHIPPED_ING;
                    if ($order['order_status'] != OS_UNCONFIRMED) {
                        $arr['order_status'] = OS_CONFIRMED;
                        $arr['confirm_time'] = GMTIME_UTC;
                    }

                    $arr['shipping_status'] = $shipping_status;
                    $this->update_wholesale_order($order_id, $arr);
                }

                /* 记录log */
                $this->wholesaleService->wholesaleOrderAction($order['order_sn'], OS_UNCONFIRMED, $shipping_status, $order['pay_status'], lang('common.deliver_goods'), session('supply_name'));

                /* 清除缓存 */
                clear_cache_files();

                /* 根据发货单id查询发货单信息 */
                if (!empty($delivery_id)) {
                    $delivery_order = $this->wholesale_delivery_info($delivery_id);
                } elseif (!empty($order_sn)) {
                    $delivery_id = DeliveryOrder::where('order_sn', $order_sn)->value('delivery_id');
                    $delivery_id = $delivery_id ? $delivery_id : 0;

                    $delivery_order = $this->wholesale_delivery_info($delivery_id);
                } else {
                    die('order does not exist');
                }

                /* 取得用户名 */
                if ($delivery_order['user_id'] > 0) {

                    $user = Users::where('user_id', $delivery_order['user_id']);
                    $user = $this->baseRepository->getToArrayFirst($user);

                    if (!empty($user)) {
                        $delivery_order['user_name'] = $user['user_name'];
                    }
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

                /* 检查此单发货商品库存缺货情况  ecmoban模板堂 --zhuo start 下单减库存*/
                $delivery_stock_result = WholesaleDeliveryGoods::where('delivery_id', $delivery_id);
                $delivery_stock_result = $delivery_stock_result->with([
                    'getWholesale',
                    'getWholesaleDeliveryOrder'
                ]);

                $delivery_stock_result = $this->baseRepository->getToArrayGet($delivery_stock_result);

                if ($delivery_stock_result) {
                    foreach ($delivery_stock_result as $key => $row) {
                        $wholesale = $row['get_wholesale'];

                        $delivery_stock_result[$key]['storage'] = $wholesale['goods_number'] ?? 0;
                        $delivery_stock_result[$key]['goods_name'] = $row['goods_name'];

                        $delivery_order = $row['get_wholesale_delivery_order'];
                        $delivery_stock_result[$key]['order_id'] = $delivery_order['order_id'] ?? 0;

                        $orderGoods = WholesaleOrderGoods::where('order_id', $delivery_stock_result[$key]['order_id'])
                            ->where('goods_id', $row['goods_id'])
                            ->where('product_id', $row['product_id']);

                        $orderGoods = $this->baseRepository->getToArrayFirst($orderGoods);
                        $delivery_stock_result[$key]['goods_attr_id'] = $orderGoods['goods_attr_id'] ?? '';
                        $delivery_stock_result[$key]['sums'] = $row['send_number'];
                    }

                    for ($i = 0; $i < count($delivery_stock_result); $i++) {
                        if (($delivery_stock_result[$i]['sums'] > $delivery_stock_result[$i]['storage'] || $delivery_stock_result[$i]['storage'] <= 0) && (($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_SHIP) || ($this->config['use_storage'] == '0' && $delivery_stock_result[$i]['is_real'] == 0))) {
                            /* 操作失败 */
                            $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
                            return sys_msg(sprintf($GLOBALS['_LANG']['act_good_vacancy'], $value['goods_name']), 1, $links);
                            break;
                        }
                    }
                }

                /* 发货 */
                if ($delivery_stock_result) {
                    /* 如果使用库存，且发货时减库存，则修改库存 */
                    if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_SHIP) {
                        foreach ($delivery_stock_result as $value) {

                            /* 商品（实货） */
                            if ($value['is_real'] != 0) {
                                //（货品）
                                if (!empty($value['product_id'])) {
                                    WholesaleProducts::where('product_id', $value['product_id'])
                                        ->decrement('product_number', $value['sums']);
                                } else {
                                    Wholesale::where('goods_id', $value['goods_id'])
                                        ->decrement('goods_number', $value['sums']);
                                }
                            }
                        }
                    }
                }

                /* 修改发货单信息 */
                $_delivery['invoice_no'] = $invoice_no;
                $_delivery['status'] = 0; // 0，为已发货
                $res = WholesaleDeliveryOrder::where('delivery_id', $delivery_id)->update($_delivery);

                if (!$res) {
                    /* 操作失败 */
                    $links[] = array('text' => $GLOBALS['_LANG']['delivery_sn'] . $GLOBALS['_LANG']['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
                    return sys_msg($GLOBALS['_LANG']['act_false'], 1, $links);
                }

                /* 标记订单为已确认 “已发货” */
                /* 更新发货时间 */
                $order_finish = $this->get_all_delivery_finish($order_id);
                $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;

                $arr['shipping_status'] = $shipping_status;
                $arr['shipping_time'] = GMTIME_UTC; // 发货时间
                $arr['invoice_no'] = !empty($invoice_no) ? $invoice_no : $order['invoice_no'];
                $arr['shipping_id'] = $shipping_id;
                $this->update_wholesale_order($order_id, $arr);

                /* 发货单发货记录log */
                $this->wholesaleService->wholesaleOrderAction($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, session('supply_name'), 1);

                /* 如果当前订单已经全部发货 */
                if ($order_finish) {
                    /* 发送邮件 */
                    $cfg = $this->config['send_ship_email'];
                    if ($cfg == '1') {
                        $order['invoice_no'] = $invoice_no;
                        $tpl = get_mail_template('deliver_notice');
                        $this->smarty->assign('order', $order);
                        $this->smarty->assign('send_time', local_date($this->config['time_format']));
                        $this->smarty->assign('shop_name', $this->config['shop_name']);
                        $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                        $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                        $this->smarty->assign('confirm_url', $this->dsc->url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']); //by wu
                        $this->smarty->assign('send_msg_url', $this->dsc->url() . 'user_message.php?act=message_list&order_id=' . $order['order_id']);
                        $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                        if (!$this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                            $msg = $GLOBALS['_LANG']['send_mail_fail'];
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

                        $smsParams = array(
                            'shop_name' => $shop_name,
                            'shopname' => $shop_name,
                            'user_name' => $user_info['user_name'],
                            'username' => $user_info['user_name'],
                            'consignee' => $order['consignee'],
                            'order_sn' => $order['order_sn'],
                            'ordersn' => $order['order_sn'],
                            'mobile_phone' => $order['mobile'],
                            'mobilephone' => $order['mobile']
                        );

                        $this->commonRepository->smsSend($order['mobile'], $smsParams, 'sms_order_shipped');
                    }

                    /* 更新商品销量 */
                    $this->get_wholesale_sale($order_id);
                }

                /* 清除缓存 */
                clear_cache_files();

                /* 操作成功 */
                $links[] = array('text' => $GLOBALS['_LANG']['09_delivery_order'], 'href' => 'order.php?act=delivery_list');
                $links[] = array('text' => $GLOBALS['_LANG']['delivery_sn'] . $GLOBALS['_LANG']['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
                return sys_msg($GLOBALS['_LANG']['act_ok'], 0, $links);
            }
            /* ------------------------------------------------------ */
            //-- end一键发货
            /* ------------------------------------------------------ */

            /* 售后 */
            elseif (isset($_POST['after_service'])) {
                $require_note = true;
                $action = $GLOBALS['_LANG']['op_after_service'];
                $operation = 'after_service';
            } /* 退货 */
            elseif (isset($_POST['return'])) {
                $require_note = $this->config['order_return_note'] == 1;
                $order = $this->orderManageService->wholesaleOrderInfo($order_id);
                if ($order['pay_status'] > 0) {
                    $show_refund = true;
                }
                $anonymous = $order['user_id'] == 0;
                $action = $GLOBALS['_LANG']['op_return'];
                $operation = 'return';
                $refound_amount = $order['order_amount'];
                if ($refound_amount > 0 && $order['return_amount'] > 0) {
                    $refound_amount = $refound_amount - $order['return_amount'];
                } else {
                    $refound_amount = $refound_amount;
                }

                $this->smarty->assign('refound_amount', $refound_amount);
                $this->smarty->assign('is_whole', 1);
            } /* 去发货 */
            elseif (isset($_POST['to_delivery'])) {
                $url = 'order.php?act=delivery_list&order_sn=' . $_REQUEST['order_sn'];

                return dsc_header("Location: $url\n");
            } /* 发货单删除 */
            elseif (isset($_REQUEST['remove_invoice'])) {
                // 删除发货单
                $delivery_id = isset($_REQUEST['delivery_id']) ? $_REQUEST['delivery_id'] : $_REQUEST['checkboxes'];
                $delivery_id = is_array($delivery_id) ? $delivery_id : array($delivery_id);

                foreach ($delivery_id as $value_is) {
                    $value_is = intval(trim($value_is));

                    // 查询：发货单信息
                    $delivery_order = $this->wholesale_delivery_info($value_is);

                    // 如果status不是退货
                    if ($delivery_order['status'] != 1) {
                        /* 处理退货 */
                        $this->delivery_return_goods($delivery_order);
                    }

                    // 如果status是已发货并且发货单号不为空
                    if ($delivery_order['status'] == 0 && $delivery_order['invoice_no'] != '') {
                        /* 更新：删除订单中的发货单号 */
                        $this->del_order_invoice_no($delivery_order['order_id'], $delivery_order['invoice_no']);
                    }

                    // 更新：删除发货单
                    WholesaleDeliveryOrder::where('delivery_id', $value_is)->delete();
                }

                /* 返回 */
                return sys_msg($GLOBALS['_LANG']['tips_delivery_del'], 0, array(array('href' => 'order.php?act=delivery_list', 'text' => $GLOBALS['_LANG']['return_list'])));
            }

            /* ------------------------------------------------------ */
            //-- 同意申请
            /* ------------------------------------------------------ */
            elseif (isset($_POST['agree_apply'])) {
                $require_note = false;
                $action = $GLOBALS['_LANG']['op_confirm'];
                $operation = 'agree_apply';
            }

            /* ------------------------------------------------------ */
            //-- 退款
            /* ------------------------------------------------------ */
            elseif (isset($_POST['refound'])) {
                $require_note = $this->config['order_return_note'] == 1;
                $order = $this->orderManageService->wholesaleOrderInfo($order_id);
                $refound_amount = empty($_REQUEST['refound_amount']) ? 0 : floatval($_REQUEST['refound_amount']);

                if ($order['pay_status'] > 0) {
                    $show_refund1 = true;
                }
                $anonymous = $order['user_id'] == 0;
                $action = $GLOBALS['_LANG']['op_return'];
                $operation = 'refound';

                if ($refound_amount > $order['order_amount']) {
                    $refound_amount = $order['order_amount'];
                }

                $this->smarty->assign('refound_amount', $refound_amount);

                /* 检测订单是否只有一个退货商品的订单 start */
                $is_whole = 0;
                $is_diff = get_wholesale_order_return_rec($order['order_id']);
                if ($is_diff) {
                    //整单退换货
                    $return_count = wholesale_return_order_info_byId($order['order_id'], 0);
                    if ($return_count == 1) {
                        $is_whole = 1;
                    }
                }

                $this->smarty->assign('is_whole', $is_whole);
                /* 检测订单是否只有一个退货商品的订单 end */
            }

            /* ------------------------------------------------------ */
            //-- 收到退换货商品
            /* ------------------------------------------------------ */
            elseif (isset($_POST['receive_goods'])) {
                $require_note = false;
                $action = $GLOBALS['_LANG']['op_confirm'];
                $operation = 'receive_goods';
            }

            /* ------------------------------------------------------ */
            //-- 换出商品
            /* ------------------------------------------------------ */
            elseif (isset($_POST['send_submit'])) {
                $shipping_id = $_POST['shipping_name'];
                $invoice_no = $_POST['invoice_no'];
                $action_note = $_POST['action_note'];

                $require_note = false;
                $action = $GLOBALS['_LANG']['op_confirm'];
                $operation = 'receive_goods';

                WholesaleOrderReturn::where('rec_id', $rec_id)
                    ->update([
                        'out_shipping_name' => $shipping_id
                    ]);
            }

            /* ------------------------------------------------------ */
            //-- 商品分单寄出
            /* ------------------------------------------------------ */
            elseif (isset($_POST['swapped_out'])) {
                $require_note = false;
                $action = $GLOBALS['_LANG']['op_confirm'];
                $operation = 'swapped_out';
            }

            /* ------------------------------------------------------ */
            //-- 商品分单寄出 分单
            /* ------------------------------------------------------ */
            elseif (isset($_POST['swapped_out_single'])) {
                $require_note = false;
                $action = $GLOBALS['_LANG']['op_confirm'];
                $operation = 'swapped_out_single';
            }

            /* ------------------------------------------------------ */
            //-- 完成退换货
            /* ------------------------------------------------------ */
            elseif (isset($_POST['complete'])) {
                $require_note = false;
                $action = $GLOBALS['_LANG']['op_confirm'];
                $operation = 'complete';
            }

            /* ------------------------------------------------------ */
            //-- 拒绝申请
            /* ------------------------------------------------------ */
            elseif (isset($_POST['refuse_apply'])) {
                $require_note = true;
                $action = $GLOBALS['_LANG']['refuse_apply'];
                $operation = 'refuse_apply';
            }

            /* 直接处理还是跳到详细页面 ecmoban模板堂 --zhuo ($require_note && $action_note == '')*/
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
                $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '12_back_apply'));
                /* 显示模板 */
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_operate'] . $action);

                return $this->smarty->display('order_operate.dwt');
            } else {
                /* 直接处理 */
                if (!$batch) {
                    // by　Leah S
                    if ($_REQUEST['ret_id']) {
                        return dsc_header("Location: order.php?act=operate_post&order_id=" . $order_id .
                            "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "&rec_id=" . $rec_id . "&ret_id=" . $ret_id . "\n");
                        exit;
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
        //-- 操作订单状态（处理提交）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'operate_post') {
            /* 检查权限 */
            // admin_priv('order_os_edit');

            /* 取得参数 */
            $order_id = intval(trim($_REQUEST['order_id']));        // 订单id
            $rec_id = empty($_REQUEST['rec_id']) ? 0 : $_REQUEST['rec_id'];     //by　Leah
            $ret_id = empty($_REQUEST['ret_id']) ? 0 : $_REQUEST['ret_id'];  //by Leah
            $return = '';   //by leah
            //by Leah S
            if ($ret_id) {
                $return = 1;
            }
            //by Leah E
            $operation = $_REQUEST['operation']; // 订单操作

            /* 查询订单信息 */
            $order = $this->orderManageService->wholesaleOrderInfo($order_id);

            /* 取得备注信息 */
            $action_note = $_REQUEST['action_note'];

            /* 初始化提示信息 */
            $msg = '';

            /*
            * 退货退款
            */
            if ('return' == $operation) {
                //TODO
                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

                /* 过滤数据 */
                $_REQUEST['refund'] = isset($_REQUEST['refund']) ? $_REQUEST['refund'] : '';
                $_REQUEST['refund_note'] = isset($_REQUEST['refund_note']) ? $_REQUEST['refund_note'] : '';

                /* 手动修改退款金额 start */
                $return_amount = isset($_REQUEST['refound_amount']) && !empty($_REQUEST['refound_amount']) ? floatval($_REQUEST['refound_amount']) : 0; //退款金额
                /* 手动修改退款金额 end */

                //退款金额不得大于可退款金额
                if ($return_amount + $order['return_amount'] > $order['order_amount']) {
                    return sys_msg(lang('suppliers/order.price_input_error'));
                }

                /* 标记订单为“退货”、“已退款” */
                $arr = array(
                    'order_status' => OS_RETURNED,
                    'invoice_no' => '',
                    'return_amount' => $return_amount + $order['return_amount']
                );

                if ($order['order_amount'] > $arr['return_amount']) {
                    $arr['pay_status'] = PS_REFOUND_PART;
                } elseif ($order['order_amount'] = $arr['return_amount']) {
                    $arr['pay_status'] = PS_REFOUND;
                } else {
                    /* 提示信息 */
                    $links[] = array('href' => 'order.php?act=info&order_id=' . $order_id, 'text' => lang('suppliers/order.back_order_info'));
                    return sys_msg(lang('suppliers/order.price_error'), 1, $links);
                }

                $this->update_wholesale_order($order_id, $arr);

                /* 记录log */
                $this->wholesaleService->wholesaleOrderAction($order['order_sn'], OS_RETURNED, $order['shipping_status'], $arr['pay_status'], $action_note, session('supply_name'));

                /* 清除缓存 */
                clear_cache_files();

                /* 操作成功 */
                $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                return sys_msg($GLOBALS['_LANG']['act_ok'], 0, $links);
            } /* 发货单生成确认 */
            elseif ('split' == $operation) {
                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

                if (true) {
                    /* 获取表单提交数据 */
                    array_walk($_REQUEST['delivery'], [$this, "trim_array_walk"]);
                    $delivery = $_REQUEST['delivery'];
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

                    /* 取得订单商品 */
                    $_goods = $this->get_wholesale_order_goods($order_id);
                    $goods_list = $_goods['goods_list'];

                    /* 检查此单发货数量填写是否正确 合并计算相同商品和货品 */
                    if (!empty($send_number) && !empty($goods_list)) {
                        $goods_no_package = array();
                        foreach ($goods_list as $key => $value) {
                            /* 去除 此单发货数量 等于 0 的商品 */
                            if (!isset($value['package_goods_list']) || !is_array($value['package_goods_list'])) {
                                // 如果是货品则键值为商品ID与货品ID的组合
                                $_key = empty($value['product_id']) ? $value['goods_id'] : ($value['goods_id'] . '_' . $value['product_id']);

                                // 统计此单商品总发货数 合并计算相同ID商品或货品的发货数
                                if (empty($goods_no_package[$_key])) {
                                    $goods_no_package[$_key] = $send_number[$value['rec_id']];
                                } else {
                                    $goods_no_package[$_key] += $send_number[$value['rec_id']];
                                }

                                //去除
                                if ($send_number[$value['rec_id']] <= 0) {
                                    unset($send_number[$value['rec_id']], $goods_list[$key]);
                                    continue;
                                }
                            }

                            /* 发货数量与总量不符 */
                            if (!isset($value['package_goods_list']) || !is_array($value['package_goods_list'])) {
                                $sended = $this->wholesale_order_delivery_num($order_id, $value['goods_id'], $value['product_id']);
                                if (($value['goods_number'] - $sended - $send_number[$value['rec_id']]) < 0) {
                                    /* 操作失败 */
                                    $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                                    return sys_msg($GLOBALS['_LANG']['act_ship_num'], 1, $links);
                                }
                            }
                        }
                    }

                    /* 对上一步处理结果进行判断 兼容 上一步判断为假情况的处理 */
                    if (empty($goods_list)) {
                        /* 操作失败 */
                        $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                        return sys_msg($GLOBALS['_LANG']['act_false'], 1, $links);
                    }

                    /* 生成发货单 */
                    /* 获取发货单号和流水号 */
                    $delivery['delivery_sn'] = get_delivery_sn();
                    $delivery_sn = $delivery['delivery_sn'];
                    /* 获取当前操作员 */
                    $delivery['action_user'] = session('supply_name');

                    /* 获取发货单生成时间 */
                    $delivery['update_time'] = GMTIME_UTC;
                    $delivery_time = $delivery['update_time'];

                    $add_time = WholesaleOrderInfo::where('order_sn', $delivery['order_sn'])->value('add_time');
                    $delivery['add_time'] = $add_time ? $add_time : '';

                    /* 设置默认值 */
                    $delivery['status'] = 2; // 正常
                    $delivery['order_id'] = $order_id;

                    $suppliers_id = AdminUser::where('user_id', session('supply_id'))->value('suppliers_id');
                    $suppliers_id = $suppliers_id ? $suppliers_id : 0;

                    $delivery['suppliers_id'] = $suppliers_id;

                    /* 过滤字段项 */
                    $filter_fileds = array(
                        'order_sn', 'add_time', 'user_id', 'how_oos', 'shipping_id', 'shipping_fee',
                        'consignee', 'address', 'country', 'province', 'city', 'district', 'sign_building',
                        'email', 'zipcode', 'tel', 'mobile', 'postscript', 'insure_fee',
                        'agency_id', 'delivery_sn', 'action_user', 'update_time',
                        'suppliers_id', 'status', 'order_id', 'shipping_name'
                    );
                    $_delivery = array();
                    foreach ($filter_fileds as $value) {
                        $_delivery[$value] = $delivery[$value];
                    }

                    /* 发货单入库 */
                    $delivery_id = WholesaleDeliveryOrder::insertGetId($_delivery);

                    if ($delivery_id) {
                        $delivery_goods = array();

                        //发货单商品入库
                        if (!empty($goods_list)) {
                            //分单操作
                            $split_action_note = "";

                            foreach ($goods_list as $value) {
                                // 商品（实货）
                                if (empty($value['extension_code'])) {
                                    $delivery_goods = array('delivery_id' => $delivery_id,
                                        'goods_id' => $value['goods_id'],
                                        'product_id' => $value['product_id'],
                                        'goods_name' => addslashes($value['goods_name']),
                                        'goods_sn' => $value['goods_sn'],
                                        'send_number' => $send_number[$value['rec_id']],
                                        'parent_id' => 0,
                                        'is_real' => $value['is_real'],
                                        'goods_attr' => addslashes($value['goods_attr'])
                                    );
                                    /* 如果是货品 */
                                    if (!empty($value['product_id'])) {
                                        $delivery_goods['product_id'] = $value['product_id'];
                                    }

                                    WholesaleDeliveryGoods::insert($delivery_goods);

                                    //分单操作
                                    $split_action_note .= sprintf($GLOBALS['_LANG']['split_action_note'], $value['goods_sn'], $send_number[$value['rec_id']]) . "<br/>";
                                }
                            }
                        }
                    } else {
                        /* 操作失败 */
                        $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                        return sys_msg($GLOBALS['_LANG']['act_false'], 1, $links);
                    }

                    unset($filter_fileds, $delivery, $_delivery);

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

                        /* 更新订单的非虚拟商品信息 即：商品（实货）（货品）*/
                        $this->update_wholesale_order_goods($order_id, $_sended, $_goods['goods_list']);

                        /* 标记订单为已确认 “发货中” */
                        /* 更新发货时间 */
                        $order_finish = $this->get_order_finish($order_id);
                        $shipping_status = SS_SHIPPED_ING;
                        $arr['shipping_status'] = $shipping_status;
                        $this->update_wholesale_order($order_id, $arr);
                    }

                    /* 分单操作 */
                    $action_note = $split_action_note . $action_note;

                    /* 记录log */
                    $this->wholesaleService->wholesaleOrderAction($order['order_sn'], $order['order_status'], $shipping_status, $order['pay_status'], $action_note, session('supply_name'));

                    /* 清除缓存 */
                    clear_cache_files();

                    /* 操作成功 */
                    $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                    return sys_msg($GLOBALS['_LANG']['act_ok'] . $msg, 0, $links);
                }
            }

            /*------------------------------------------------------ */
            //-- 收到退换货商品
            /*------------------------------------------------------ */
            elseif ('agree_apply' == $operation) {
                $arr = array('agree_apply' => 1); //收到用户退回商品

                WholesaleOrderReturn::where('rec_id', $rec_id)
                    ->update($arr);

                /* 记录log TODO_LOG */
                $this->wholesaleOrderService->wholesaleReturnAction($ret_id, RF_AGREE_APPLY, '', $action_note);
            }

            /*------------------------------------------------------ */
            //-- 收到退换货商品
            /*------------------------------------------------------ */
            elseif ('receive_goods' == $operation) {
                $arr = array('return_status' => 1); //收到用户退回商品

                WholesaleOrderReturn::where('rec_id', $rec_id)
                    ->update($arr);

                $arr['pay_status'] = PS_PAYED;
                $order['pay_status'] = PS_PAYED;

                /* 记录log TODO_LOG */
                $this->wholesaleOrderService->wholesaleReturnAction($ret_id, RF_RECEIVE, '', $action_note);
            }

            /*------------------------------------------------------ */
            //-- 换出商品寄出 ---- 分单
            /*------------------------------------------------------ */
            elseif ('swapped_out_single' == $operation) {
                $arr = array('return_status' => 2); //换出商品寄出

                WholesaleOrderReturn::where('rec_id', $rec_id)
                    ->update($arr);

                $this->wholesaleOrderService->wholesaleReturnAction($ret_id, RF_SWAPPED_OUT_SINGLE, '', $action_note);
            }

            /*------------------------------------------------------ */
            //-- 换出商品寄出
            /*------------------------------------------------------ */
            elseif ('swapped_out' == $operation) {
                $arr = array('return_status' => 3); //换出商品寄出

                WholesaleOrderReturn::where('rec_id', $rec_id)
                    ->update($arr);

                $this->wholesaleOrderService->wholesaleReturnAction($ret_id, RF_SWAPPED_OUT, '', $action_note);
            }

            /*------------------------------------------------------ */
            //-- 拒绝申请
            /*------------------------------------------------------ */
            elseif ('refuse_apply' == $operation) {
                $arr = array('return_status' => 6); //换出商品寄出

                WholesaleOrderReturn::where('rec_id', $rec_id)
                    ->update($arr);

                $this->wholesaleOrderService->wholesaleReturnAction($ret_id, REFUSE_APPLY, '', $action_note);
            }

            /*------------------------------------------------------ */
            //-- 完成退换货
            /*------------------------------------------------------ */
            elseif ('complete' == $operation) {
                $arr = array('return_status' => 4); //完成退换货

                $return_type = WholesaleOrderReturn::where('rec_id', $rec_id)
                    ->value('return_type');
                $return_type = $return_type ? $return_type : 0;

                if ($return_type == 0) {
                    $return_note = FF_MAINTENANCE;
                } elseif ($return_type == 1) {
                    $return_note = FF_REFOUND;
                } elseif ($return_type == 2) {
                    $return_note = FF_EXCHANGE;
                }

                WholesaleOrderReturn::where('rec_id', $rec_id)
                    ->update($arr);

                $this->wholesaleOrderService->wholesaleReturnAction($ret_id, RF_COMPLETE, $return_note, $action_note);
            }

            /*------------------------------------------------------ */
            //-- 退款
            /*------------------------------------------------------ */
            elseif ('refound' == $operation) {
                load_helper(['transaction']);

                //TODO
                /* 定义当前时间 */
                define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

                $is_whole = 0;
                $is_diff = get_wholesale_order_return_rec($order_id);
                if ($is_diff) {
                    //整单退换货
                    $return_count = wholesale_return_order_info_byId($order_id, 0);
                    if ($return_count == 1) {
                        $is_whole = 1;
                    }
                }
                $this->smarty->assign('is_whole', $is_whole);

                /* 过滤数据 */
                $_REQUEST['refund'] = isset($_REQUEST['refund']) ? $_REQUEST['refund'] : ''; // 退款类型
                $_REQUEST['refund_amount'] = isset($_REQUEST['refund_amount']) ? $_REQUEST['refund_amount'] : 0;
                $_REQUEST['action_note'] = isset($_REQUEST['action_note']) ? $_REQUEST['action_note'] : ''; //退款说明
                $_REQUEST['refound_pay_points'] = isset($_REQUEST['refound_pay_points']) ? $_REQUEST['refound_pay_points'] : 0;//退回积分  by kong

                $return_amount = isset($_REQUEST['refound_amount']) && !empty($_REQUEST['refound_amount']) ? floatval($_REQUEST['refound_amount']) : 0; //退款金额
                $is_shipping = isset($_REQUEST['is_shipping']) && !empty($_REQUEST['is_shipping']) ? intval($_REQUEST['is_shipping']) : 0; //是否退运费
                $shippingFee = !empty($is_shipping) ? floatval($_REQUEST['shipping']) : 0; //退款运费金额

                $refound_vcard = isset($_REQUEST['refound_vcard']) && !empty($_REQUEST['refound_vcard']) ? floatval($_REQUEST['refound_vcard']) : 0; //储值卡金额
                $vc_id = isset($_REQUEST['vc_id']) && !empty($_REQUEST['vc_id']) ? intval($_REQUEST['vc_id']) : 0; //储值卡金额

                $return_goods = get_wholesale_return_order_goods1($rec_id); //退换货商品
                $return_info = $this->wholesaleOrderService->wholesaleReturnOrderInfo($ret_id);        //退换货订单

                /* todo 处理退款 */
                if ($order['pay_status'] != PS_UNPAYED) {
                    $order_goods = $this->get_order_goods($order);             //订单商品
                    $refund_type = $_REQUEST['refund'];

                    //判断商品退款是否大于实际商品退款金额
                    $refound_fee = wholesale_order_refound_fee($order_id, $ret_id); //已退金额
                    $paid_amount = $order['order_amount'] - $refound_fee;
                    if ($return_amount > $paid_amount) {
                        $return_amount = $paid_amount;
                    }

                    $refund_amount = $return_amount;
                    $get_order_arr = get_wholesale_order_arr($return_info['return_number'], $return_info['rec_id'], $order_goods['goods_list'], $order);
                    $get_order_arr['return_amount'] = $order['return_amount'] + $refund_amount;

                    $refund_note = request()->input('refund_note', '');
                    $refund_note = addslashes(trim($refund_note));

                    //退款
                    if (!empty($_REQUEST['action_note'])) {
                        $order['should_return'] = $return_info['should_return'];
                        $is_ok = wholesale_order_refound($order, $refund_type, $refund_note, $refund_amount, $operation);

                        if ($is_ok == 2) {
                            /* 提示信息 */
                            $links[] = array('href' => 'order.php?act=return_info&ret_id=' . $ret_id . '&rec_id=' . $return_info['rec_id'], 'text' => lang('suppliers/order.back_return_slip'));
                            return sys_msg(lang('suppliers/order.refund_failed'), 1, $links);
                        }

                        //标记order_return 表
                        $return_status = array(
                            'refound_status' => 1,
                            'agree_apply' => 1,
                            'actual_return' => $refund_amount,
                            'return_shipping_fee' => $shippingFee,
                            'refund_type' => $refund_type,
                            'return_time' => gmtime()
                        );

                        WholesaleOrderReturn::where('ret_id', $ret_id)->update($return_status);

                        $this->update_wholesale_order($order_id, $get_order_arr);
                    }
                }

                /* 记录log */
                $this->wholesaleOrderService->wholesaleReturnAction($ret_id, '', FF_REFOUND, $action_note);
            } elseif ('after_service' == $operation) {
                /* 记录log */
                $this->wholesaleService->wholesaleOrderAction($order['order_sn'], $order['order_status'], $order['shipping_status'], $order['pay_status'], '[' . $GLOBALS['_LANG']['op_after_service'] . '] ' . $action_note, session('supply_name'));
            } else {
                die('invalid params');
            }

            if ($return) {
                $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=return_info&ret_id=' . $ret_id . '&rec_id=' . $rec_id); //by Leah

                return sys_msg($GLOBALS['_LANG']['act_ok'] . $msg, 0, $links);
            } else {
                /* 操作成功 */
                $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
                return sys_msg($GLOBALS['_LANG']['act_ok'] . $msg, 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 修改退换金额
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_refound_amount') {

            $type = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']);
            $refound_amount = empty($_REQUEST['refound_amount']) ? 0 : floatval($_REQUEST['refound_amount']);
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $rec_id = empty($_REQUEST['rec_id']) ? 0 : intval($_REQUEST['rec_id']);
            $ret_id = empty($_REQUEST['ret_d']) ? 0 : intval($_REQUEST['ret_d']);
            $order_shipping_fee = 0;
            $shipping_fee = 0;

            if ($type == 1) {
                $order_return = WholesaleOrderReturn::where('rec_id', $rec_id);
                $order_return = $this->baseRepository->getToArrayFirst($order_return);


                $order = WholesaleOrderInfo::where('order_id', $order_id);
                $order = $this->baseRepository->getToArrayFirst($order);

                $paid_amount = $order['goods_amount'];
                /* 退款 */
                if ($ret_id > 0) {
                    $refound_fee = wholesale_order_refound_fee($order_id, $ret_id);//已退金额
                    if ($refound_fee > 0 && $order_return['should_return'] > $refound_fee) {
                        $paid_amount = $paid_amount - $refound_fee;

                        if ($refound_amount > $paid_amount) {
                            $should_return = $paid_amount;
                        }
                    } else {
                        $should_return = $order_return['should_return'];
                    }
                } else {
                    $should_return = $refound_amount;
                }

                if ($should_return > $paid_amount) {
                    $should_return = $paid_amount;
                }
            }


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
        //-- 获取订单商品信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_goods_info') {

            /* 取得订单商品 */
            $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
            if (empty($order_id)) {
                return make_json_response('', 1, $GLOBALS['_LANG']['error_get_goods_info']);
            }

            $goods_list = array();
            $goods_attr = array();

            $res = WholesaleOrderGoods::where('order_id', $order_id);
            $res = $res->with([
                'getWholesale' => function ($query) {
                    $query->with([
                        'getSuppliers'
                    ]);
                },
                'getWholesaleOrderInfo'
            ]);

            $res = $this->baseRepository->getToArrayGet($res);


            if ($res) {
                foreach ($res as $key => $row) {
                    $wholesale = $row['get_wholesale'];

                    $row['goods_thumb'] = $wholesale['goods_thumb'] ?? '';
                    $row['goods_sn'] = $wholesale['goods_sn'] ?? '';
                    $row['brand_id'] = $wholesale['brand_id'] ?? 0;
                    $row['storage'] = $wholesale['goods_number'] ?? 0;
                    $row['goods_id'] = $wholesale['goods_id'] ?? 0;
                    $row['ru_id'] = $wholesale['get_suppliers']['user_id'] ?? 0;
                    $row['order_sn'] = $wholesale['get_wholesale_order_info']['order_sn'] ?? '';

                    if (empty($prod)) { //当商品没有属性库存时
                        $row['goods_storage'] = $row['storage'];
                    }
                    $row['storage'] = !empty($row['goods_storage']) ? $row['goods_storage'] : 0;
                    $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                    $row['formated_goods_price'] = price_format($row['goods_price']);

                    //图片显示
                    $row['goods_thumb'] = get_image_path($row['goods_thumb']);

                    $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
                    $goods_list[] = $row;
                }
            }

            $attr = array();
            if ($goods_attr) {
                foreach ($goods_attr as $index => $array_val) {

                    $array_val = $this->baseRepository->getExplode($array_val);

                    if ($array_val) {
                        foreach ($array_val as $value) {
                            $arr = explode(':', $value);//以 : 号将属性拆开
                            $attr[$index][] = @array('name' => $arr[0], 'value' => $arr[1]);
                        }
                    }

                }
            }

            $this->smarty->assign('goods_attr', $attr);
            $this->smarty->assign('goods_list', $goods_list);
            $str = $this->smarty->fetch('show_order_goods.dwt');
            $goods[] = array('order_id' => $order_id, 'str' => $str);
            return make_json_result($goods);
        } elseif ($_REQUEST['act'] == 'pay_order') {
            $result = array('error' => 0, 'msg' => '');

            $order_id = !empty($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;

            if ($order_id > 0) {
                $pay_status = WholesaleOrderInfo::where('order_id', $order_id)->value('pay_status');
                $pay_status = $pay_status ? $pay_status : 0;

                if ($pay_status == 2) {
                    /* 已付款则退出 */
                    $result['error'] = 1;
                    $result['msg'] = lang('suppliers/order.no_duplicate_payment');
                } else {
                    load_helper(['payment']);

                    $log_id = PayLog::where('order_id', $order_id)
                        ->where('order_type', PAY_WHOLESALE)
                        ->value('log_id');

                    order_paid($log_id, 2);
                }
            } else {
                /* 如果参数不存在，退出 */
                $result['error'] = 1;
                $result['msg'] = 'invalid parameter';
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 发货单列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_list') {
            /* 检查权限 */
            admin_priv('suppliers_delivery_view');
            $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '09_delivery_order'));

            /* 查询 */
            $result = $this->wholesale_delivery_list();

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_delivery_order']);

            $this->smarty->assign('os_unconfirmed', OS_UNCONFIRMED);
            $this->smarty->assign('cs_await_pay', CS_AWAIT_PAY);
            $this->smarty->assign('cs_await_ship', CS_AWAIT_SHIP);
            $this->smarty->assign('full_page', 1);

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);

            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('delivery_list', $result['delivery']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_update_time', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            /* 显示模板 */

            return $this->smarty->display('delivery_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 搜索、排序、分页
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_delivery_view');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '09_delivery_order'));
            $result = $this->wholesale_delivery_list();

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($result, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('delivery_list', $result['delivery']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            return make_json_result($this->smarty->fetch('delivery_list.dwt'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 发货单详细
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_info') {
            /* 检查权限 */
            admin_priv('suppliers_delivery_view');

            $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '09_delivery_order'));
            $delivery_id = intval(trim($_REQUEST['delivery_id']));

            /* 根据发货单id查询发货单信息 */
            if (!empty($delivery_id)) {
                $delivery_order = $this->wholesale_delivery_info($delivery_id);
            } else {
                die('order does not exist');
            }

            /* 取得用户名 */
            if ($delivery_order['user_id'] > 0) {

                $user = Users::where('user_id', $delivery_order['user_id']);
                $user = $this->baseRepository->getToArrayFirst($user);

                if (!empty($user)) {
                    $delivery_order['user_name'] = $user['user_name'];
                }
            }

            /* 已安装快递列表 */
            $this->smarty->assign('shipping_list', shipping_list());

            /* 是否保价 */
            $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

            /* 取得发货单商品 */
            $goods_list = WholesaleDeliveryGoods::where('delivery_id', $delivery_order['delivery_id']);

            $goods_list = $goods_list->with([
                'getWholesale'
            ]);

            $goods_list = $this->baseRepository->getToArrayGet($goods_list);

            /* 是否存在实体商品 */
            $exist_real_goods = 0;
            if ($goods_list) {
                foreach ($goods_list as $key => $row) {
                    $wholesale = $row['get_wholesale'];

                    $row['brand_id'] = $wholesale['brand_id'] ?? 0;
                    $row['goods_thumb'] = $wholesale['goods_thumb'] ?? '';

                    $brand = get_goods_brand_info($row['brand_id']);
                    $goods_list[$key]['brand_id'] = $brand['brand_id'];
                    $goods_list[$key]['brand_name'] = $brand['brand_name'];

                    //图片显示
                    $row['goods_thumb'] = get_image_path($row['goods_thumb']);
                    $goods_list[$key]['goods_thumb'] = $row['goods_thumb'];

                    if ($row['is_real']) {
                        $exist_real_goods++;
                    }
                }
            }

            /* 取得订单操作记录 */
            $act_list = array();

            $res = WholesaleOrderAction::where('order_id', $delivery_order['order_id'])
                ->where('action_place', 1)
                ->orderByRaw('log_time, action_id desc');
            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $key => $row) {
                    $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                    $row['pay_status'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
                    $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? $GLOBALS['_LANG']['ss_admin'][SS_SHIPPED_ING] : $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                    $row['action_time'] = local_date($this->config['time_format'], $row['log_time']);
                    $act_list[] = $row;
                }
            }

            $this->smarty->assign('action_list', $act_list);

            /* 模板赋值 */
            $this->smarty->assign('delivery_order', $delivery_order);
            $this->smarty->assign('exist_real_goods', $exist_real_goods);
            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('delivery_id', $delivery_id); // 发货单id

            /* 显示模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['delivery_operate'] . $GLOBALS['_LANG']['detail']);
            $this->smarty->assign('action_link', array('href' => 'order.php?act=delivery_list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['09_delivery_order'], 'class' => 'icon-reply'));
            $this->smarty->assign('action_act', ($delivery_order['status'] == 2) ? 'delivery_ship' : 'delivery_cancel_ship');

            return $this->smarty->display('delivery_info.dwt');
            exit; //
        }

        /*------------------------------------------------------ */
        //-- 发货单发货确认
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_ship') {
            /* 检查权限 */
            admin_priv('suppliers_delivery_view');

            /* 定义当前时间 */
            define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

            /* 取得参数 */
            $delivery = array();
            $order_id = intval(trim($_REQUEST['order_id']));        // 订单id
            $delivery_id = intval(trim($_REQUEST['delivery_id']));        // 发货单id
            $delivery['invoice_no'] = isset($_REQUEST['invoice_no']) ? trim($_REQUEST['invoice_no']) : '';
            $delivery['shipping_id'] = isset($_REQUEST['shipping_id']) ? intval($_REQUEST['shipping_id']) : 0;
            $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

            /* 根据发货单id查询发货单信息 */
            if (empty($delivery_id)) {
                die('order does not exist');
            }

            /* 查询订单信息 */
            $order = $this->orderManageService->wholesaleOrderInfo($order_id);

            /* 检查此单发货商品库存缺货情况  ecmoban模板堂 --zhuo start 下单减库存*/
            $delivery_stock_result = WholesaleDeliveryGoods::where('delivery_id', $delivery_id);
            $delivery_stock_result = $delivery_stock_result->with([
                'getWholesale',
                'getWholesaleDeliveryOrder'
            ]);

            $delivery_stock_result = $this->baseRepository->getToArrayGet($delivery_stock_result);

            if ($delivery_stock_result) {
                foreach ($delivery_stock_result as $key => $row) {
                    $wholesale = $row['get_wholesale'];
                    $delivery_stock_result[$key]['storage'] = $wholesale['goods_number'] ?? 0;
                    $delivery_stock_result[$key]['goods_name'] = $row['goods_name'];
                    $delivery_stock_result[$key]['sums'] = $row['send_number'];

                    $delivery_order = $row['get_wholesale_delivery_order'];
                    $delivery_stock_result[$key]['order_id'] = $delivery_order['order_id'] ?? 0;

                    $orderGoods = WholesaleOrderGoods::where('order_id', $delivery_stock_result[$key]['order_id'])
                        ->where('goods_id', $row['goods_id'])
                        ->where('product_id', $row['product_id']);

                    $orderGoods = $this->baseRepository->getToArrayFirst($orderGoods);
                    $delivery_stock_result[$key]['goods_attr_id'] = $orderGoods['goods_attr_id'] ?? '';
                }

                for ($i = 0; $i < count($delivery_stock_result); $i++) {
                    if (($delivery_stock_result[$i]['sums'] > $delivery_stock_result[$i]['storage'] || $delivery_stock_result[$i]['storage'] <= 0) && (($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_SHIP) || ($this->config['use_storage'] == '0' && $delivery_stock_result[$i]['is_real'] == 0))) {
                        /* 操作失败 */
                        $links[] = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
                        return sys_msg(sprintf($GLOBALS['_LANG']['act_good_vacancy'], $delivery_stock_result[$i]['goods_name']), 1, $links);
                        break;
                    }
                }
            }

            /* 发货 */
            /* 如果使用库存，且发货时减库存，则修改库存 */
            if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_SHIP) {
                foreach ($delivery_stock_result as $value) {
                    //（货品）
                    if (!empty($value['product_id'])) {
                        WholesaleProducts::where('product_id', $value['product_id'])
                            ->decrement('product_number', $value['sums']);
                    } else {
                        Wholesale::where('goods_id', $value['goods_id'])
                            ->decrement('goods_number', $value['sums']);
                    }
                }
            }

            /* 修改发货单信息 */
            $invoice_no = str_replace(',', '<br>', $delivery['invoice_no']);
            $invoice_no = trim($invoice_no, '<br>');
            $_delivery['invoice_no'] = $invoice_no;
            $_delivery['shipping_id'] = $delivery['shipping_id'];
            $_delivery['status'] = 0; // 0，为已发货

            $res = WholesaleDeliveryOrder::where('delivery_id', $delivery_id)->update($_delivery);

            if (!$res) {
                /* 操作失败 */
                $links[] = array('text' => $GLOBALS['_LANG']['delivery_sn'] . $GLOBALS['_LANG']['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
                return sys_msg($GLOBALS['_LANG']['act_false'], 1, $links);
            }

            /* 标记订单为已确认 “已发货” */
            /* 更新发货时间 */
            $order_finish = $this->get_all_delivery_finish($order_id);
            $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;

            $arr['shipping_status'] = $shipping_status;
            $arr['shipping_time'] = GMTIME_UTC; // 发货时间
            $arr['invoice_no'] = !empty($invoice_no) ? $invoice_no : $order['invoice_no'];
            $arr['shipping_id'] = $delivery['shipping_id'];
            $this->update_wholesale_order($order_id, $arr);

            /* 发货单发货记录log */
            $this->wholesaleService->wholesaleOrderAction($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, session('supply_name'), 1);

            /* 如果当前订单已经全部发货 */
            if ($order_finish) {
                /* 发送邮件 */
                $cfg = $this->config['send_ship_email'];
                if ($cfg == '1') {
                    $order['invoice_no'] = $invoice_no;
                    $tpl = get_mail_template('deliver_notice');
                    $this->smarty->assign('order', $order);
                    $this->smarty->assign('send_time', local_date($this->config['time_format']));
                    $this->smarty->assign('shop_name', $this->config['shop_name']);
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                    $this->smarty->assign('confirm_url', $this->dsc->url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']); //by wu
                    $this->smarty->assign('send_msg_url', $this->dsc->url() . 'user_message.php?act=message_list&order_id=' . $order['order_id']);
                    $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                    if (!$this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                        $msg = $GLOBALS['_LANG']['send_mail_fail'];
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

                    $smsParams = array(
                        'shop_name' => $shop_name,
                        'shopname' => $shop_name,
                        'user_name' => $user_info['user_name'],
                        'username' => $user_info['user_name'],
                        'consignee' => $order['consignee'],
                        'order_sn' => $order['order_sn'],
                        'ordersn' => $order['order_sn'],
                        'mobile_phone' => $order['mobile'],
                        'mobilephone' => $order['mobile']
                    );

                    $this->commonRepository->smsSend($order['mobile'], $smsParams, 'sms_order_shipped');
                }

                /* 更新商品销量 */
                $this->get_wholesale_sale($order_id);
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 操作成功 */
            $links[] = array('text' => $GLOBALS['_LANG']['09_delivery_order'], 'href' => 'order.php?act=delivery_list');
            $links[] = array('text' => $GLOBALS['_LANG']['delivery_sn'] . $GLOBALS['_LANG']['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
            return sys_msg($GLOBALS['_LANG']['act_ok'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 发货单取消发货
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_cancel_ship') {
            /* 检查权限 */
            admin_priv('suppliers_delivery_view');

            /* 取得参数 */
            $delivery = '';
            $order_id = intval(trim($_REQUEST['order_id']));        // 订单id
            $delivery_id = intval(trim($_REQUEST['delivery_id']));        // 发货单id
            $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

            /* 根据发货单id查询发货单信息 */
            if (!empty($delivery_id)) {
                $delivery_order = $this->wholesale_delivery_info($delivery_id);
            } else {
                die('order does not exist');
            }

            /* 查询订单信息 */
            $order = order_info($order_id);

            /* 取消当前发货单物流单号 */
            $_delivery['invoice_no'] = '';
            $_delivery['shipping_id'] = '';
            $_delivery['status'] = 2;

            $res = WholesaleDeliveryOrder::where('delivery_id', $delivery_id)->update($_delivery);
            if (!$res) {
                /* 操作失败 */
                $links[] = array('text' => $GLOBALS['_LANG']['delivery_sn'] . $GLOBALS['_LANG']['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
                return sys_msg($GLOBALS['_LANG']['act_false'], 1, $links);
                exit;
            }

            /* 修改定单发货单号 */
            $invoice_no_order = explode('<br>', $order['invoice_no']);
            $invoice_no_delivery = explode('<br>', $delivery_order['invoice_no']);
            foreach ($invoice_no_order as $key => $value) {
                $delivery_key = array_search($value, $invoice_no_delivery);
                if ($delivery_key !== false) {
                    unset($invoice_no_order[$key], $invoice_no_delivery[$delivery_key]);
                    if (count($invoice_no_delivery) == 0) {
                        break;
                    }
                }
            }
            $_order['invoice_no'] = implode('<br>', $invoice_no_order);

            /* 更新配送状态 */
            $order_finish = $this->get_all_delivery_finish($order_id);
            $shipping_status = ($order_finish == -1) ? SS_SHIPPED_PART : SS_SHIPPED_ING;
            $arr['shipping_status'] = $shipping_status;
            if ($shipping_status == SS_SHIPPED_ING) {
                $arr['shipping_time'] = ''; // 发货时间
            }
            $arr['invoice_no'] = $_order['invoice_no'];
            $this->update_wholesale_order($order_id, $arr);

            /* 发货单取消发货记录log */
            $this->wholesaleService->wholesaleOrderAction($order['order_sn'], $order['order_status'], $shipping_status, $order['pay_status'], $action_note, session('supply_name'), 1);

            /* 如果使用库存，则增加库存 */
            if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_SHIP) {
                // 检查此单发货商品数量
                $delivery_stock_result = WholesaleDeliveryGoods::where('delivery_id', $delivery_id);
                $delivery_stock_result = $this->baseRepository->getToArrayGet($delivery_stock_result);

                if ($delivery_stock_result) {
                    foreach ($delivery_stock_result as $key => $value) {
                        //（货品）
                        if (!empty($value['product_id'])) {
                            WholesaleProducts::where('product_id', $value['product_id'])
                                ->increment('product_number', $value['sums']);
                        } else {
                            Wholesale::where('goods_id', $value['goods_id'])
                                ->increment('goods_number', $value['sums']);
                        }
                    }
                }
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 操作成功 */
            $links[] = array('text' => $GLOBALS['_LANG']['delivery_sn'] . $GLOBALS['_LANG']['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id);
            return sys_msg($GLOBALS['_LANG']['act_ok'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 退换货订单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'return_list') {
            $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '12_back_apply'));

            /* 检查权限 */
            admin_priv('suppliers_order_back_apply');
            $this->smarty->assign('current', '12_back_apply');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_suppliers_order']);
            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_order_list']);

            $this->smarty->assign('full_page', 1);
            $order_list = return_order_list();

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);


            return $this->smarty->display('return_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 退换货分页
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'return_list_query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_order_back_apply');
            if ($check_auth !== true) {
                return $check_auth;
            }
            /* 模板赋值 */
            $order_list = return_order_list();

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($order_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            return make_json_result($this->smarty->fetch('return_list.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 退货单详情
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'return_info') {
            /* 检查权限 */
            admin_priv('suppliers_order_back_apply');

            $ret_id = intval(trim($_REQUEST['ret_id']));
            $rec_id = intval(trim($_REQUEST['rec_id']));
            $this->smarty->assign('menu_select', array('action' => '02_suppliers_order', 'current' => '12_back_apply'));

            /* 根据发货单id查询发货单信息 */
            if (!empty($ret_id) || !empty($rec_id)) {
                $back_order = $this->wholesaleOrderService->wholesaleReturnOrderInfo($ret_id);
            } else {
                die('order does not exist');
            }

            /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
            $agency_id = AdminUser::where('user_id', session('supply_id'))->value('agency_id');
            $agency_id = $agency_id ? $agency_id : 0;

            if ($agency_id > 0) {
                if ($back_order['agency_id'] != $agency_id) {
                    return sys_msg($GLOBALS['_LANG']['priv_error']);
                }

                /* 取当前办事处信息 */
                $agency_name = Agency::where('agency_id', $agency_id)->value('agency_name');
                $agency_name = $agency_name ? $agency_name : '';

                $back_order['agency_name'] = $agency_name;
            }

            /* 取得用户名 */
            if ($back_order['user_id'] > 0) {

                $user = Users::where('user_id', $back_order['user_id']);
                $user = $this->baseRepository->getToArrayFirst($user);

                if (!empty($user)) {
                    $back_order['user_name'] = $user['user_name'];
                }
            }

            /* 取得区域名 */
            $back_order['region'] = $back_order['address_detail'];

            /* 是否保价 */
            $back_order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;/* 取得发货单商品 */;
            $goods_list = $this->wholesaleOrderService->getWholesaleReturnOrderGoods($rec_id);
            /**
             * 取的退换货订单商品
             */
            $return_list = get_wholesale_return_goods($ret_id);

            $shippinOrderInfo = WholesaleOrderGoods::where('order_id', $back_order['order_id']);
            $shippinOrderInfo = $this->baseRepository->getToArrayFirst($shippinOrderInfo);

            $shippinOrderInfo['ru_id'] = Suppliers::where('suppliers_id', $back_order['suppliers_id'])->value('user_id');
            $shippinOrderInfo['ru_id'] = $shippinOrderInfo['ru_id'] ? $shippinOrderInfo['ru_id'] : 0;

            //快递公司
            /* 取得可用的配送方式列表 */
            $region_id_list = array(
                $back_order['country'], $back_order['province'], $back_order['city'], $back_order['district']
            );

            $shipping_list = available_shipping_list($region_id_list, $shippinOrderInfo);
            $this->smarty->assign('shipping_list', $shipping_list);

            /* 取得退货订单操作记录 */
            $action_list = $this->wholesaleOrderService->getWholesaleReturnAction($ret_id);
            $this->smarty->assign('action_list', $action_list);

            /* 模板赋值 */
            $this->smarty->assign('back_order', $back_order);
            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('return_list', $return_list);

            /* 显示模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['back_operate'] . $GLOBALS['_LANG']['detail']);
            $this->smarty->assign('action_link', array('href' => 'order.php?act=return_list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['12_back_apply'], 'class' => 'icon-reply'));

            return $this->smarty->display('return_order_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 调节金额
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'adjust_fee') {
            $check_auth = check_authz_json('suppliers_order_view');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $adjust_fee = empty($_REQUEST['adjust_fee']) ? 0.00 : floatval($_REQUEST['adjust_fee']);

            $order_info = WholesaleOrderInfo::where('order_id', $order_id)
                ->where('suppliers_id', $adminru['suppliers_id']);
            $order_info = $this->baseRepository->getToArrayFirst($order_info);

            $curr_order_amount = $order_info['goods_amount'] + $adjust_fee;
            if ($curr_order_amount < 0) {
                return make_json_error(lang('suppliers/order.adjust_price_beyond'));
            } else {
                $data = [
                    'adjust_fee' => $adjust_fee,
                    'order_amount' => $curr_order_amount
                ];

                WholesaleOrderInfo::where('order_id', $order_id)
                    ->where('suppliers_id', $adminru['suppliers_id'])
                    ->update($data);

                return make_json_result('');
            }
        }
    }

    /**
     * 获取采购订单列表
     *
     * @return array
     */
    private function wholesale_order_list()
    {
        $adminru = get_admin_ru_id();

        /* 过滤信息 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);

        $filter['composite_status'] = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : -1;

        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
            $filter['order_sn'] = json_str_iconv($filter['order_sn']);
            $filter['consignee'] = json_str_iconv($filter['consignee']);
            $filter['address'] = json_str_iconv($filter['address']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = WholesaleOrderInfo::mainOrderCount()->where('suppliers_id', $adminru['suppliers_id'])->where('is_delete', 0);

        //综合状态
        switch ($filter['composite_status']) {
            case 1:
                $row = $row->where('pay_status', PS_UNPAYED);
                break;

            case 2:
                $row = $row->where('shipping_status', SS_UNSHIPPED)
                    ->where('pay_status', PS_PAYED);
                break;

            case 3:
                $row = $row->where('order_status', OS_RETURNED)
                    ->where('pay_status', PS_PAYED);
                break;
            default:
        }

        if ($filter['order_sn']) {
            $row = $row->where('order_sn', 'like', '%' . mysql_like_quote($filter['order_sn']) . '%');
        }
        if ($filter['consignee']) {
            $row = $row->where('consignee', 'like', '%' . mysql_like_quote($filter['consignee']) . '%');
        }

        if ($filter['keywords']) {
            $row = $row->where(function ($query) use ($filter) {
                $query->whereHas('getWholesaleOrderGoods', function ($query) use ($filter) {
                    $query = $query->where('goods_sn', 'like', '%' . mysql_like_quote($filter['keywords']) . '%');
                    $query->orWhere('goods_name', 'like', '%' . mysql_like_quote($filter['keywords']) . '%');
                });
            });
        }

        $res = $record_count = $row;

        $res = $res->with([
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

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        $res = $res->withCount('getMainOrderId as order_child');

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $start = ($filter['page'] - 1) * $filter['page_size'];
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        /* 格式话数据 */
        if ($res) {
            foreach ($res as $key => $value) {
                $value['total_fee'] = $value['goods_amount'] + $value['tax'] + $value['pay_fee'];

                $res[$key]['total_fee'] = $value['total_fee'];

                $pay_name = Payment::where('pay_id', $value['pay_id'])->value('pay_name');
                $pay_name = $pay_name ? $pay_name : '';

                $res[$key]['pay_name'] = $pay_name;
                $res[$key]['pay_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['pay_time']);

                //查会员名称
                $user_name = Users::where('user_id', $value['user_id'])->value('user_name');
                $user_name = $user_name ? $user_name : '';
                $value['buyer'] = $user_name;
                $res[$key]['buyer'] = !empty($value['buyer']) ? $value['buyer'] : $GLOBALS['_LANG']['anonymous'];

                $res[$key]['formated_order_amount'] = price_format($value['order_amount']);
                $res[$key]['formated_money_paid'] = price_format($value['money_paid']);
                $res[$key]['formated_total_fee'] = price_format($value['total_fee']);
                $res[$key]['short_order_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);

                /* 取得区域名 */
                $province = $value['get_region_province']['region_name'] ?? '';
                $city = $value['get_region_city']['region_name'] ?? '';
                $district = $value['get_region_district']['region_name'] ?? '';
                $street = $value['get_region_street']['region_name'] ?? '';
                $res[$key]['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

                $goods = $this->get_wholesale_order_goods($value['order_id']);
                $res[$key]['goods_list'] = $goods['goods_list'];
            }
        }

        $arr = [
            'orders' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }

    /**
     * 取得批发订单商品
     *
     * @param int $order_id
     * @return array
     */
    private function get_wholesale_order_goods($order_id = 0)
    {
        $goods_list = array();
        $goods_attr = array();

        $res = WholesaleOrderGoods::where('order_id', $order_id);
        $res = $res->with([
            'getWholesale' => function ($query) {
                $query->with([
                    'getSuppliers'
                ]);
            },
            'getWholesaleOrderInfo'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $wholesale = $row['get_wholesale'];
                $row['goods_thumb'] = $wholesale['goods_thumb'] ?? '';
                $row['goods_sn'] = $wholesale['goods_sn'] ?? '';
                $row['brand_id'] = $wholesale['brand_id'] ?? 0;
                $row['storage'] = $wholesale['goods_number'] ?? 0;
                $row['goods_id'] = $wholesale['goods_id'] ?? 0;
                $row['ru_id'] = $wholesale['get_suppliers']['user_id'] ?? 0;
                $row['order_sn'] = $row['get_wholesale_order_info']['order_sn'] ?? '';

                if (empty($prod)) { //当商品没有属性库存时
                    $row['goods_storage'] = $row['storage'];
                }
                $row['storage'] = !empty($row['goods_storage']) ? $row['goods_storage'] : 0;
                $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                $row['formated_goods_price'] = price_format($row['goods_price']);
                $row['goods_id'] = $row['goods_id'];
                //图片显示
                $row['goods_thumb'] = get_image_path($row['goods_thumb']);

                $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
                $goods_list[] = $row;
            }
        }

        $attr = [];
        if ($goods_attr) {
            foreach ($goods_attr as $index => $array_val) {

                $array_val = $this->baseRepository->getExplode($array_val);

                if ($array_val) {
                    foreach ($array_val as $value) {
                        $arr = explode(':', $value);//以 : 号将属性拆开
                        $attr[$index][] = @array('name' => $arr[0], 'value' => $arr[1]);
                    }
                }
            }
        }

        $arr = [
            'goods_list' => $goods_list,
            'attr' => $attr
        ];
        return $arr;
    }

    /**
     * 导出订单
     *
     * @param $result
     * @return string
     */
    private function download_orderlist($result)
    {
        if (empty($result)) {
            return $this->i(lang('suppliers/order.empty_data'));
        }

        $data_name = "";
        $data_cnt = "";
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] < 1) {
            $data_name = lang('suppliers/order.shop_name');
            $data_cnt = lang('suppliers/order.existence');
        }

        $data = $this->i(lang('suppliers/order.order_sn') . $data_name . lang('suppliers/order.title_attr') . "\n");
        $count = count($result);
        for ($i = 0; $i < $count; $i++) {
            $order_sn = $this->i('#' . $result[$i]['order_sn']); //订单号前加'#',避免被四舍五入 by wu
            $order_user = $this->i($result[$i]['buyer']);
            $order_time = $this->i($result[$i]['short_order_time']);
            $consignee = $this->i($result[$i]['consignee']);
            $tel = !empty($result[$i]['mobile']) ? $this->i($result[$i]['mobile']) : $this->i($result[$i]['tel']);
            $address = $this->i($result[$i]['address']);
            $order_amount = $this->i($result[$i]['order_amount']);
            $order_status = $this->i(preg_replace("/\<.+?\>/", "", $GLOBALS['_LANG']['os'][$result[$i]['order_status']])); //去除标签
            $ru_name = !empty($data_cnt) ? i($result[$i]['user_name']) . ',' : ''; //商家名称
            $pay_status = $this->i($GLOBALS['_LANG']['ps'][$result[$i]['pay_status']]);
            $shipping_status = $this->i($GLOBALS['_LANG']['ss'][$result[$i]['shipping_status']]);
            $data .= $order_sn . ',' . $ru_name . $order_user . ',' .
                $order_time . ',' . $consignee . ',' . $tel . ',' .
                $address . ',' .
                $order_amount . ',' . $order_status . ',' .
                $pay_status . ',' . $shipping_status . "\n";
        }
        return $data;
    }

    /**
     * 转字符编码
     *
     * @param $strInput
     * @return string
     */
    private function i($strInput)
    {
        return iconv('utf-8', 'gb2312', $strInput);//页面编码为utf-8时使用，否则导出的中文为乱码
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

        /* 取得订单操作权限 */
        $actions = session('supply_action_list');
        if ($actions == 'all') {
            $priv_list = array('os' => true, 'ss' => true, 'ps' => true, 'edit' => true);
        } else {
            $actions = ',' . $actions . ',';
            $priv_list = array(
                'os' => strpos($actions, ',suppliers_order_view,') !== false,
                'ss' => strpos($actions, ',suppliers_order_view,') !== false,
                'ps' => strpos($actions, ',suppliers_order_view,') !== false,
                'edit' => strpos($actions, ',suppliers_order_view,') !== false
            );
        }

        /* 取得订单支付方式是否货到付款 */
        $payment = $this->paymentService->getPaymentInfo($order['pay_id']);
        $is_cod = $payment['is_cod'] == 1;

        /* 根据状态返回可执行操作 */
        $list = array();

        if (OS_UNCONFIRMED == $os) {
            /* 状态：未完成 */
            if (PS_UNPAYED == $ps) {
                /* 状态：未完成、未付款 */
                if (SS_UNSHIPPED == $ss) {
                    /* 状态：已确认、未付款、未发货（或配货中） */
                    if ($priv_list['os']) {
                        $list['cancel'] = true; // 取消
                        $list['invalid'] = true; // 无效
                    }
                    if ($is_cod) {
                        /* 货到付款 */
                        if ($priv_list['ss']) {
                            $list['split'] = true; // 分单
                        }
                    } else {
                        /* 不是货到付款 */
                        if ($priv_list['ps']) {
                            $list['pay'] = true; // 付款
                        }
                    }
                } /* 状态：已确认、未付款、发货中 */
                elseif (SS_SHIPPED_ING == $ss) {
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

                        if ($priv_list['os']) {
                            $list['return'] = true; // 退货
                        }
                    }
                }
            } else {
                /* 状态：已确认、已付款 */
                if (SS_UNSHIPPED == $ss) {
                    /* 状态：已确认、已付款和付款中、未发货（配货中） => 不是货到付款 */
                    if ($priv_list['ss']) {
                        if (SS_UNSHIPPED == $ss) {
                            $list['prepare'] = true; // 配货
                        }
                    }
                    if ($priv_list['ps']) {
                        $list['unpay'] = true; // 设为未付款
                    }
                } /* 状态：已确认、已付款、发货中 */
                elseif (SS_SHIPPED_ING == $ss) {
                    $list['to_delivery'] = true; // 去发货
                } else {
                    /* 状态：已确认、已付款和付款中、已发货或已收货 */
                    if ($priv_list['ss']) {
                        if (SS_SHIPPED == $ss) {
                            $list['receive'] = true; // 收货确认
                        }
                        if (!$is_cod) {
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
     * 取得订单商品
     * @param array $order 订单数组
     * @return array
     */
    private function get_order_goods($order)
    {
        $goods_list = array();
        $goods_attr = array();

        $res = WholesaleOrderGoods::where('order_id', $order['order_id']);
        $res = $res->with([
            'getWholesale' => function ($query) {
                $query->with([
                    'getSuppliers',
                    'getWholesaleBrand'
                ]);
            },
            'getWholesaleProducts',
            'getWholesaleOrderInfo'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $wholesale = $row['get_wholesale'];
                $row['suppliers_id'] = $wholesale['suppliers_id'] ?? 0;
                $row['goods_thumb'] = $wholesale['goods_thumb'] ?? '';
                $row['goods_sn'] = $wholesale['goods_sn'] ?? '';
                $row['brand_id'] = $wholesale['brand_id'] ?? 0;
                $row['brand_name'] = $wholesale['get_wholesale_brand']['brand_mame'] ?? '';
                $row['storage'] = $wholesale['goods_number'] ?? 0;
                $row['goods_id'] = $wholesale['goods_id'] ?? 0;
                $row['ru_id'] = $wholesale['get_suppliers']['user_id'] ?? 0;
                $row['order_sn'] = $row['get_wholesale_order_info']['order_sn'] ?? '';
                $row['oi_extension_code'] = $row['get_wholesale_order_info']['extension_code'] ?? '';
                $row['product_sn'] = $row['get_wholesale_products']['product_sn'] ?? '';

                // 虚拟商品支持
                if ($row['is_real'] == 0) {
                    /* 取得语言项 */
                    $filename = plugin_path($row['extension_code'] . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php');
                    if (file_exists($filename)) {
                        include_once($filename);
                        if (!empty($GLOBALS['_LANG'][$row['extension_code'] . '_link'])) {
                            $row['goods_name'] = $row['goods_name'] . sprintf($GLOBALS['_LANG'][$row['extension_code'] . '_link'], $row['goods_id'], $order['order_sn']);
                        }
                    }
                }

                if ($row['product_id'] > 0) {
                    $product_number = WholesaleProducts::where('product_id', $row['product_id'])
                        ->where('goods_id', $row['goods_id'])
                        ->value('product_number');
                    $row['storage'] = $product_number ? $product_number : 0;
                } else {
                    $goods_number = Wholesale::where('goods_id', $row['goods_id'])->value('goods_number');
                    $row['storage'] = $goods_number ? $goods_number : 0;
                }

                $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                $row['formated_goods_price'] = price_format($row['goods_price']);

                $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

                //处理货品id
                $row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];

                //图片显示
                $row['goods_thumb'] = get_image_path($row['goods_thumb']);

                $trade_id = $this->orderCommonService->getFindSnapshot($row['order_sn'], $row['goods_id']);
                if ($trade_id) {
                    $row['trade_url'] = $this->dscRepository->dscUrl("trade_snapshot.php?act=trade&tradeId=" . $trade_id . "&snapshot=true");
                }

                //处理商品链接
                $row['url'] = $this->dscRepository->buildUri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

                $goods_list[] = $row;
            }
        }

        $attr = array();
        if ($goods_attr) {
            foreach ($goods_attr as $index => $array_val) {

                $array_val = $this->baseRepository->getExplode($array_val);

                if ($array_val) {
                    foreach ($array_val as $value) {
                        $arr = explode(':', $value);//以 : 号将属性拆开
                        $attr[$index][] = @array('name' => $arr[0], 'value' => $arr[1]);
                    }
                }
            }
        }


        return array('goods_list' => $goods_list, 'attr' => $attr);
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

        $sum = WholesaleOrderGoods::whereRaw('goods_number > send_number')
            ->where('order_id', $order_id)->count();

        if (!$sum) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     * 修改订单
     * @param int $order_id 订单id
     * @param array $order key => value
     * @return  bool
     */
    private function update_wholesale_order($order_id, $order)
    {
        $order_other = $this->baseRepository->getArrayfilterTable($order, 'wholesale_order_info');

        return WholesaleOrderInfo::where('order_id', $order_id)
            ->update($order_other);
    }

    /**
     *更新商品销量
     */
    private function get_wholesale_sale($order_id = 0, $order = array())
    {
        if (empty($order)) {
            $order = WholesaleOrderInfo::where('order_id', $order_id);
            $order = $this->baseRepository->getToArrayFirst($order);
        }

        $is_volume = 0;
        if ($order) {
            if ($GLOBALS['_CFG']['sales_volume_time'] == SALES_PAY && $order['pay_status'] == PS_PAYED) {
                $is_volume = 1;
            } elseif ($GLOBALS['_CFG']['sales_volume_time'] == SALES_SHIP && $order['shipping_status'] == SS_SHIPPED) {
                $is_volume = 1;
            }

            if ($is_volume == 1) {
                $order_res = WholesaleOrderGoods::where('order_id', $order['order_id']);
                $order_res = $this->baseRepository->getToArrayGet($order_res);

                if ($order_res) {
                    foreach ($order_res as $idx => $val) {
                        Wholesale::where('goods_id', $val['goods_id'])
                            ->update([
                                'sales_volume' => $val['goods_number']
                            ]);
                    }
                }
            }
        }
    }

    /**
     * 回调方法
     *
     * @param $array_value
     */
    private function trim_array_walk(&$array_value)
    {
        if (is_array($array_value)) {
            array_walk($array_value, [$this, trim_array_walk]);
        } else {
            $array_value = trim($array_value);
        }
    }

    /**
     * @param $order_id
     * @param $_sended
     * @param array $goods_list
     * @return bool
     */
    private function update_wholesale_order_goods($order_id, $_sended, $goods_list = array())
    {
        if (!is_array($_sended) || empty($order_id)) {
            return false;
        }
        foreach ($_sended as $key => $value) {
            if (!is_array($value)) {
                /* 检查是否为商品（实货）（货品） */
                foreach ($goods_list as $goods) {
                    if ($goods['rec_id'] == $key) {
                        WholesaleOrderGoods::where('rec_id', $key)
                            ->where('order_id', $order_id)
                            ->increment('send_number', $value);
                        break;
                    }
                }
            }
        }

        return true;
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
    private function wholesale_order_delivery_num($order_id = 0, $goods_id = 0, $product_id = 0)
    {
        $sum = WholesaleDeliveryGoods::where('goods_id', $goods_id)
            ->where('extension_code', '<>', 'package_buy');

        if ($product_id > 0) {
            $sum = $sum->where('product_id', $product_id);
        }

        $sum = $sum->whereHas('getWholesaleDeliveryOrder', function ($query) use ($order_id) {
            $query->where('status', 0)
                ->where('order_id', $order_id);
        });

        $sum = $sum->sum('send_number');
        $sum = $sum ? $sum : 0;

        return $sum;
    }

    /**
     *  获取发货单列表信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function wholesale_delivery_list()
    {
        $adminru = get_admin_ru_id();

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

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'update_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = WholesaleDeliveryOrder::where('suppliers_id', $adminru['suppliers_id']);

        if ($filter['order_sn']) {
            $row = $row->where('order_sn', 'like', '%' . mysql_like_quote($filter['order_sn']) . '%');
        }

        if ($filter['consignee']) {
            $row = $row->where('consignee', 'like', '%' . mysql_like_quote($filter['consignee']) . '%');
        }

        if ($filter['delivery_sn']) {
            $row = $row->where('delivery_sn', 'like', '%' . mysql_like_quote($filter['delivery_sn']) . '%');
        }

        if ($filter['goods_id']) {
            $row = $row->whereHas('getWholesaleDeliveryGoods', function ($query) use ($filter) {
                $query->where('goods_id', $filter['goods_id']);
            });
        }

        if ($filter['status'] > -1) {
            $row = $row->where('status', $filter['status']);
        }

        $res = $record_count = $row;

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

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $start = ($filter['page'] - 1) * $filter['page_size'];
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        /* 格式化数据 */
        if ($res) {
            foreach ($res as $key => $value) {
                $res[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
                $res[$key]['update_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['update_time']);
                if ($value['status'] == 1) {
                    $res[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][1];
                } elseif ($value['status'] == 2) {
                    $res[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][2];
                } else {
                    $res[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][0];
                }
            }
        }

        $arr = [
            'delivery' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }

    /**
     * 取得发货单信息
     * @param int $delivery_order 发货单id（如果delivery_order > 0 就按id查，否则按sn查）
     * @param string $delivery_sn 发货单号
     * @return  array   发货单信息（金额都有相应格式化的字段，前缀是formated_）
     */
    private function wholesale_delivery_info($delivery_id, $delivery_sn = '')
    {
        $return_order = array();
        if (empty($delivery_id) || !is_numeric($delivery_id)) {
            return $return_order;
        }

        /* 获取管理员信息 */
        $delivery = WholesaleDeliveryOrder::whereRaw(1);

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
            }
        ]);

        $delivery = $this->baseRepository->getToArrayFirst($delivery);

        if ($delivery) {

            /* 取得区域名 */
            $province = $delivery['get_region_province']['region_name'] ?? '';
            $city = $delivery['get_region_city']['region_name'] ?? '';
            $district = $delivery['get_region_district']['region_name'] ?? '';
            $order['region'] = $province . ' ' . $city . ' ' . $district;

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
            $sum = WholesaleDeliveryOrder::where('order_id', $order_id)
                ->where('status', 2);
            $sum = $sum->count();

            // 全部发货
            if (empty($sum)) {
                $return_res = 1;
            } // 未全部发货
            else {
                /* 订单全部发货中时：当前发货单总数 */
                $_sum = WholesaleDeliveryOrder::where('order_id', $order_id)
                    ->where('status', '<>', 1);
                $_sum = $_sum->count();

                if ($_sum == $sum) {
                    $return_res = -2; // 完全没发货
                } else {
                    $return_res = -1; // 部分发货
                }
            }
        }

        return $return_res;
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
    private function delivery_return_goods($delivery_order)
    {
        /* 查询：取得发货单商品 */
        $goods_list = WholesaleDeliveryGoods::where('delivery_id', $delivery_order['delivery_id']);
        $goods_list = $this->baseRepository->getToArrayGet($goods_list);

        /* 更新： */
        if ($goods_list) {
            foreach ($goods_list as $key => $val) {
                WholesaleOrderGoods::where('order_id', $delivery_order['order_id'])
                    ->where('goods_id', $goods_list[$key]['goods_id'])
                    ->decrement('send_number', $goods_list[$key]['send_number']);
            }
        }

        WholesaleOrderInfo::where('order_id', $delivery_order['order_id'])
            ->update([
                'shipping_status' => 0,
                'order_status' => 1
            ]);
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
        $order_invoice_no = WholesaleOrderInfo::where('order_id', $order_id)->value('invoice_no');
        $order_invoice_no = $order_invoice_no ? $order_invoice_no : '';

        /* 如果为空就结束处理 */
        if (empty($order_invoice_no)) {
            return false;
        }

        /* 去除当前发货单号 */
        $order_array = explode('<br>', $order_invoice_no);
        $delivery_array = $delivery_invoice_no ? explode('<br>', $delivery_invoice_no) : [];

        if ($order_array) {
            foreach ($order_array as $key => $invoice_no) {
                if ($delivery_array && $ii = array_search($invoice_no, $delivery_array)) {
                    unset($order_array[$key], $delivery_array[$ii]);
                }
            }
        }

        $arr['invoice_no'] = implode('<br>', $order_array);
        $this->update_wholesale_order($order_id, $arr);
    }

    /**
     * 获取站点根目录网址
     *
     * @access  private
     * @return  Bool
     */
    private function get_site_root_url()
    {
        return 'http://' . request()->server('HTTP_HOST') . str_replace('/' . ADMIN_PATH . '/shipping.php', '', PHP_SELF);
    }
}
