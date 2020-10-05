<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Suppliers;
use App\Models\Users;
use App\Models\WholesaleDeliveryOrder;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleProducts;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserAddressService;
use App\Services\Wholesale\OrderManageService;
use App\Services\Wholesale\OrderService as WholesaleOrderService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WholesaleOrderExport;

/**
 * 地区切换程序
 */
class WholesaleOrderController extends InitController
{
    protected $orderManageService;
    protected $config;
    protected $baseRepository;
    protected $wholesaleOrderService;
    protected $goodsWarehouseService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $userAddressService;

    public function __construct(
        OrderManageService $orderManageService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        WholesaleOrderService $wholesaleOrderService,
        GoodsWarehouseService $goodsWarehouseService,
        MerchantCommonService $merchantCommonService,
        UserAddressService $userAddressService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->orderManageService = $orderManageService;
        $this->config = $this->dscRepository->dscConfig();
        $this->baseRepository = $baseRepository;
        $this->wholesaleOrderService = $wholesaleOrderService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->merchantCommonService = $merchantCommonService;
        $this->userAddressService = $userAddressService;
    }

    public function index()
    {
        load_helper(['order', 'goods', 'wholesale', 'suppliers']);

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
        /* ------------------------------------------------------ */
        //--Excel文件下载
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'order_export') {

            /* 订单状态传值 */
            $composite_status = isset($_REQUEST['composite_status']) ? trim($_REQUEST['composite_status']) : -1;

            $time = gmtime();
            $file_name = str_replace(" ", "--", $time . '_wholesale_order');

            return Excel::download(new WholesaleOrderExport, $file_name . '.xlsx');
        }

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        /*------------------------------------------------------ */
        //-- 采购订单列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('suppliers_order_view');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_wholesale_order']);
            $this->smarty->assign('full_page', 1);

            $list = $this->wholesale_order_list();

            $this->smarty->assign('order_list', $list['order_list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return $this->smarty->display('wholesale_order_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_order_view');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $list = $this->wholesale_order_list();

            /* 订单状态传值 */
            $composite_status = isset($_REQUEST['composite_status']) ? trim($_REQUEST['composite_status']) : -1;
            $this->smarty->assign('status', $composite_status);
            $this->smarty->assign('action_link', array('href' => 'order.php?act=order_query', 'text' => $GLOBALS['_LANG']['03_order_query']));
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('order_list', $list['order_list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('wholesale_order_list.dwt'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 订单详情页面
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'info') {
            /* 检查权限 */
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
            if ($order['user_id'] > 0) {

                $user = Users::where('user_id', $order['user_id']);
                $user = $this->baseRepository->getToArrayFirst($user);

                if (!empty($user)) {
                    $order['user_name'] = $user['user_name'];

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $order['user_name'] = $this->dscRepository->stringToStar($order['user_name']);
                    }
                }
            }


            /* 格式化金额 */
            if ($order['order_amount'] < 0) {
                $order['money_refund'] = abs($order['order_amount']);
                $order['formated_money_refund'] = price_format(abs($order['order_amount']));
            }

            /* 其他处理 */
            $order['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
            $order['status'] = $GLOBALS['_LANG']['os'][$order['order_status']];

            /* 取得订单商品总重量 */
            $weight_price = order_weight_price($order['order_id']);
            $order['total_weight'] = $weight_price['formated_weight'];

            $date = array('order_id');

            $order_child = count(get_table_date('wholesale_order_info', "main_order_id='" . $order['order_id'] . "'", $date, 1));
            $order['order_child'] = $order_child;

            /*增值发票 start*/
            if ($order['invoice_type'] == 1) {
                $user_id = $order['user_id'];
                $sql = " SELECT * FROM " . $this->dsc->table('users_vat_invoices_info') . " WHERE user_id = '$user_id' LIMIT 1";
                $res = $this->db->getRow($sql);
                $this->smarty->assign('vat_info', $res);
            }
            /*增值发票 end*/

            /* 参数赋值：订单 */
            $this->smarty->assign('order', $order);
            /* 取得用户信息 */
            if ($order['user_id'] > 0) {
                /* 用户等级 */
                if ($user['user_rank'] > 0) {
                    $where = " WHERE rank_id = '$user[user_rank]' ";
                } else {
                    $where = " WHERE min_points <= " . intval($user['rank_points']) . " ORDER BY min_points DESC ";
                }
                $sql = "SELECT rank_name FROM " . $this->dsc->table('user_rank') . $where;
                $user['rank_name'] = $this->db->getOne($sql);

                // 地址信息
                $sql = "SELECT * FROM " . $this->dsc->table('user_address') . " WHERE user_id = '$order[user_id]'";
                $this->smarty->assign('address_list', $this->db->getAll($sql));
            }

            /* 取得订单商品及货品 */
            $goods_list = array();
            $goods_attr = array();
            $sql = " SELECT o.*, w.goods_number AS storage, o.product_id, o.goods_attr, w.suppliers_id, p.product_sn,
            s.user_id AS ru_id, w.brand_id, w.goods_thumb , w.bar_code 
            FROM " . $this->dsc->table('wholesale_order_goods') . " AS o
			LEFT JOIN " . $this->dsc->table('wholesale_products') . " AS p ON p.product_id = o.product_id
			LEFT JOIN " . $this->dsc->table('wholesale') . " AS w ON w.goods_id = o.goods_id 
			LEFT JOIN " . $this->dsc->table('wholesale_cat') . " AS c ON w.cat_id = c.cat_id
			LEFT JOIN " . $this->dsc->table('suppliers') . " AS s ON s.suppliers_id = w.suppliers_id
            WHERE o.order_id = '$order[order_id]' ";
            $res = $this->db->getAll($sql);

            $goods_list = [];
            if ($res) {
                foreach ($res as $key => $row) {
                    $row['goods_thumb'] = get_image_path($row['goods_thumb']);
                    if ($row['product_id']) {
                        $product_number = WholesaleProducts::where('product_id', $row['product_id'])
                            ->where('goods_id', $row['goods_id'])
                            ->value('product_number');
                        $row['storage'] = $product_number ? $product_number : 0;
                    }
                    $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
                    $row['formated_goods_price'] = price_format($row['goods_price']);


                    $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

                    $goods_list[] = $row;
                }
            }

            $attr = array();
            $arr = array();
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
             * 取得用户收货时间 以快物流信息显示为准，目前先用用户收货时间为准，后期修改TODO by Leah S
             */
            $sql = "SELECT log_time  FROM " . $this->dsc->table('order_action') . " WHERE order_id = '$order[order_id]' ";
            $res_time = local_date($GLOBALS['_CFG']['time_format'], $this->db->getOne($sql));
            $this->smarty->assign('res_time', $res_time);
            /**
             * by Leah E
             */

            //商家店铺信息打印到订单和快递单上
            $sql = "select shop_name,country,province,city,district,shop_address,kf_tel from " . $this->dsc->table('seller_shopinfo') . " where ru_id='" . $order['ru_id'] . "'";
            $store = $this->db->getRow($sql);

            /* 是否打印订单，分别赋值 */
            if (isset($_GET['print'])) {
                $this->smarty->assign('shop_name', $store['shop_name']);
                $this->smarty->assign('shop_url', $this->dsc->url());
                $this->smarty->assign('shop_address', $store['shop_address']);
                $this->smarty->assign('service_phone', $store['kf_tel']);
                $this->smarty->assign('print_time', local_date($GLOBALS['_CFG']['time_format']));
                $this->smarty->assign('action_user', session('admin_name'));

                $this->smarty->template_dir = '../' . DATA_DIR;
                return $this->smarty->display('order_print.html');
            } /* 打印快递单 */
            elseif (isset($_GET['shipping_print'])) {
                //发货地址所在地
                $region_array = array();
                $region = $this->db->getAll("SELECT region_id, region_name FROM " . $this->dsc->table("region")); //打印快递单地区 by wu
                if (!empty($region)) {
                    foreach ($region as $region_data) {
                        $region_array[$region_data['region_id']] = $region_data['region_name'];
                    }
                }
                $this->smarty->assign('shop_name', $store['shop_name']);
                $this->smarty->assign('order_id', $order_id);
                $this->smarty->assign('province', $region_array[$store['province']]);
                $this->smarty->assign('city', $region_array[$store['city']]);
                $this->smarty->assign('district', $region_array[$store['district']]);
                $this->smarty->assign('shop_address', $store['shop_address']);
                $this->smarty->assign('service_phone', $store['kf_tel']);
                $shipping = $this->db->getRow("SELECT * FROM " . $this->dsc->table("shipping_tpl") . " WHERE shipping_id = '" . $order['shipping_id'] . "' and ru_id='" . $order['ru_id'] . "'");
                //打印单模式
                if ($shipping['print_model'] == 2) {
                    /* 可视化 */
                    /* 快递单 */
                    $shipping['print_bg'] = empty($shipping['print_bg']) ? '' : get_site_root_url() . $shipping['print_bg'];

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
                    $sql = "select oi.ru_id from " . $GLOBALS['dsc']->table('order_info') . " as oi " .
                        " where oi.order_id = '" . $order['order_id'] . "'";
                    $ru_id = $GLOBALS['db']->getOne($sql);

                    if ($ru_id > 0) {
                        $sql = "select shoprz_brandName, shopNameSuffix from " . $GLOBALS['dsc']->table('merchants_shop_information') . " where user_id = '$ru_id'";
                        $shop_info = $GLOBALS['db']->getRow($sql);

                        $lable_box['t_shop_name'] = $shop_info['shoprz_brandName'] . $shop_info['shopNameSuffix']; //店铺-名称
                    } else {
                        $lable_box['t_shop_name'] = $GLOBALS['_CFG']['shop_name']; //网店-名称
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
                    if (!empty($GLOBALS['_LANG']['shipping_print'])) {
                        echo $this->smarty->fetch("str:" . $GLOBALS['_LANG']['shipping_print']);
                    } else {
                        echo $GLOBALS['_LANG']['no_print_shipping'];
                    }
                }
            } else {
                /* 模板赋值 */
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['order_info']);
                $this->smarty->assign('action_link', array('href' => 'wholesale_order.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['02_order_list']));

                /* 显示模板 */
                return $this->smarty->display('wholesale_order_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 获取订单商品信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_goods_info') {
            /* 检查权限 */
            admin_priv('suppliers_order_view');

            /* 取得订单商品 */
            $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
            if (empty($order_id)) {
                make_json_response('', 1, $GLOBALS['_LANG']['error_get_goods_info']);
            }
            $goods_list = array();
            $goods_attr = array();
            $sql = "SELECT o.*, w.goods_thumb, w.goods_sn, w.brand_id, w.goods_number AS storage, o.goods_attr, oi.order_sn " .
                "FROM " . $this->dsc->table('wholesale_order_goods') . " AS o " .
                "LEFT JOIN " . $this->dsc->table('wholesale') . " AS w ON w.goods_id = o.goods_id " .
                "LEFT JOIN " . $this->dsc->table('wholesale_order_info') . " AS oi ON oi.order_id = o.order_id " .
                "WHERE o.order_id = '{$order_id}' ";
            $res = $this->db->query($sql);

            foreach ($res as $key => $row) {
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
            $attr = array();
            $arr = array();

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
            $str = $this->smarty->fetch('wholesale_show_order_goods.dwt');
            $goods[] = array('order_id' => $order_id, 'str' => $str);
            return make_json_result($goods);
        }

        /*------------------------------------------------------ */
        //-- 操作订单状态（载入页面）
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'operate') {
            /* 检查权限 */
            admin_priv('suppliers_order_view');

            $order_id = '';
            /* 取得订单id（可能是多个，多个sn）和操作备注（可能没有） */
            if (isset($_REQUEST['order_id'])) {
                $order_id = $_REQUEST['order_id'];
            }
            $order_id_list = explode(',', $order_id);

            $batch = isset($_REQUEST['batch']); // 是否批处理
            $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

            if (isset($_POST['remove'])) {
                foreach ($order_id_list as $id_order) {
                    /* 检查能否操作 */
                    $order = $this->orderManageService->wholesaleOrderInfo($id_order);

                    /* 删除订单 */
                    if ($order['order_status'] == 0 && $order['pay_status'] == 0 && $order['shipping_status'] == 0) {
                        $this->db->query("DELETE FROM " . $this->dsc->table('wholesale_order_info') . " WHERE order_id = '$order[order_id]'");
                        $this->db->query("DELETE FROM " . $this->dsc->table('wholesale_order_goods') . " WHERE order_id = '$order[order_id]'");
                        $this->db->query("DELETE FROM " . $this->dsc->table('wholesale_order_action') . " WHERE order_id = '$order[order_id]'");

                        $this->db->query("DELETE FROM " . $this->dsc->table('wholesale_order_info') . " WHERE order_id = '$order[main_order_id]'");
                        $this->db->query("DELETE FROM " . $this->dsc->table('wholesale_order_goods') . " WHERE order_id = '$order[main_order_id]'");
                        $this->db->query("DELETE FROM " . $this->dsc->table('wholesale_order_action') . " WHERE order_id = '$order[main_order_id]'");

                        /* todo 记录日志 */
                        admin_log($order['order_sn'], 'remove', 'wholesale_order');
                    }
                }
                /* 返回 */
                return sys_msg($GLOBALS['_LANG']['order_removed'] . lang('admin/wholesale_order.delete_fail'), 0, array(array('href' => 'wholesale_order.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['return_list'])));
            } /* 批量打印订单 */
            elseif (isset($_POST['print'])) {
                if (empty($order_id)) {
                    return sys_msg($GLOBALS['_LANG']['pls_select_order']);
                }

                if (isset($this->config['tp_api']) && $this->config['tp_api']) {
                    //快递鸟、电子面单 start
                    $url = 'tp_api.php?act=order_print&order_sn=' . $_POST['order_id'] . '&order_type=wholesale_order';
                    return dsc_header("Location: $url\n");
                    //快递鸟、电子面单 end
                }

                /* 赋值公用信息 */
                $this->smarty->assign('print_time', local_date($GLOBALS['_CFG']['time_format']));
                $this->smarty->assign('action_user', session('admin_name'));

                $html = '';
                $order_sn_list = explode(',', $_POST['order_id']);
                foreach ($order_sn_list as $order_sn) {
                    /* 取得订单信息 */
                    $order = order_info(0, $order_sn);

                    /*判断是否是商家商品  by kong*/
                    if ($order['ru_id'] > 0) {
                        //判断是否是主订单
                        $date = array('order_id');
                        $order_child = count(get_table_date('order_info', "main_order_id = '$order[order_id]'", $date, 1));
                        if ($order_child > 0) {
                            $this->smarty->assign('shop_url', $this->dsc->url());
                            $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                            $this->smarty->assign('shop_address', $GLOBALS['_CFG']['shop_address']);
                            $this->smarty->assign('service_phone', $GLOBALS['_CFG']['service_phone']);
                        } else {
                            $this->smarty->assign('shop_url', $this->dsc->url());
                            $sql = "SELECT domain_name FROM " . $this->dsc->table("seller_domain") . " WHERE ru_id = '" . $order['ru_id'] . "' AND  is_enable = 1";//获取商家域名
                            $domain_name = $this->db->getOne($sql);
                            $this->smarty->assign('domain_name', $domain_name);
                            $this->smarty->assign('shop_name', $this->merchantCommonService->getShopName($order['ru_id'], 1));
                            $seller_shopinfo = $this->db->getRow("SELECT shop_address ,kf_tel FROM" . $this->dsc->table("seller_shopinfo") . " WHERE ru_id = '$order[ru_id]' LIMIT 1");//获取商家地址，电话
                            $this->smarty->assign('shop_address', $seller_shopinfo['shop_address']);
                            $this->smarty->assign('service_phone', $seller_shopinfo['kf_tel']);
                        }
                    } else {
                        $this->smarty->assign('shop_url', $this->dsc->url());
                        $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                        $this->smarty->assign('shop_address', $GLOBALS['_CFG']['shop_address']);
                        $this->smarty->assign('service_phone', $GLOBALS['_CFG']['service_phone']);
                    }
                    // by kong end
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
                    $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('admin_id') . "'";
                    $agency_id = $this->db->getOne($sql);
                    if ($agency_id > 0) {
                        if ($order['agency_id'] != $agency_id) {
                            continue;
                        }
                    }

                    /* 取得用户名 */
                    if ($order['user_id'] > 0) {

                        $user = Users::where('user_id', $order['user_id']);
                        $user = $this->baseRepository->getToArrayFirst($user);

                        if (!empty($user)) {
                            $order['user_name'] = $user['user_name'];
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
                    $goods_list = array();
                    $goods_attr = array();
                    $sql = "SELECT o.*, c.measure_unit, g.goods_unit, g.goods_number AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, g.bar_code " .
                        "FROM " . $this->dsc->table('order_goods') . " AS o " .
                        "LEFT JOIN " . $this->dsc->table('goods') . " AS g ON o.goods_id = g.goods_id " .
                        "LEFT JOIN " . $this->dsc->table('brand') . " AS b ON g.brand_id = b.brand_id " .
                        'LEFT JOIN ' . $GLOBALS['dsc']->table('category') . ' AS c ON g.cat_id = c.cat_id ' .
                        "WHERE o.order_id = '$order[order_id]' ";
                    $res = $this->db->getAll($sql);

                    if ($res) {
                        foreach ($res as $key => $row) {
                            $products = $this->goodsWarehouseService->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $row['warehouse_id'], $row['area_id'], $row['model_attr']);
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
                            $row['measure_unit'] = $row['goods_unit'] ? $row['goods_unit'] : $row['measure_unit'];

                            $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
                            $goods_list[] = $row;
                        }
                    }

                    $attr = array();
                    $arr = array();

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
                    $this->smarty->template_dir = '../' . DATA_DIR;
                    $html .= $this->smarty->fetch('order_print.html') .
                        '<div style="PAGE-BREAK-AFTER:always"></div>';
                }

                echo $html;
                exit;
            }
        } elseif ($_REQUEST['act'] == 'pay_order') {
            $result = array('error' => 0, 'msg' => '');

            $order_id = !empty($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
            if ($order_id > 0) {
                $sql = "SELECT pay_status FROM" . $this->dsc->table('wholesale_order_info') . "WHERE order_id = '$order_id'";
                $pay_status = $this->db->getOne($sql);
                if ($pay_status == 2) {
                    /* 已付款则退出 */
                    $result['error'] = 1;
                    $result['msg'] = lang('admin/wholesale_order.cant_duplicate_payment');
                } else {
                    load_helper(['payment']);

                    $sql = "SELECT log_id FROM" . $this->dsc->table('pay_log') . "WHERE order_id = '$order_id' AND order_type = '" . PAY_WHOLESALE . "'";
                    $log_id = $this->db->getOne($sql);
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

            $seller_order = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

            /* 查询 */
            $result = $this->wholesale_delivery_list();

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_delivery_order']);

            /* 订单内平台、店铺区分 */
            $this->smarty->assign('common_tabs', array('info' => $seller_order, 'url' => 'order.php?act=delivery_list'));
            $this->smarty->assign('os_unconfirmed', OS_UNCONFIRMED);
            $this->smarty->assign('cs_await_pay', CS_AWAIT_PAY);
            $this->smarty->assign('cs_await_ship', CS_AWAIT_SHIP);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('delivery_list', $result['delivery']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_update_time', '<img src="images/sort_desc.gif">');

            /* 显示模板 */

            return $this->smarty->display('wholesale_delivery_list.dwt');
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

            $result = $this->wholesale_delivery_list();

            $this->smarty->assign('delivery_list', $result['delivery']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            return make_json_result($this->smarty->fetch('wholesale_delivery_list.dwt'), '', array('filter' => $result['filter'], 'page_count' => $result['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 发货单详细
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delivery_info') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_delivery_view');
            if ($check_auth !== true) {
                return $check_auth;
            }

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

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $delivery_order['user_name'] = $this->dscRepository->stringToStar($delivery_order['user_name']);
                    }
                }
            }

            /* 是否保价 */
            $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

            /* 取得发货单商品 */
            $goods_sql = "SELECT dg.*, g.goods_thumb, g.brand_id  FROM " . $this->dsc->table('wholesale_delivery_goods') .
                " AS dg LEFT JOIN " . $this->dsc->table('wholesale') . " as g ON dg.goods_id = g.goods_id " .
                " WHERE delivery_id = " . $delivery_order['delivery_id'];
            $goods_list = $GLOBALS['db']->getAll($goods_sql);
            foreach ($goods_list as $key => $row) {
                $brand = get_goods_brand_info($row['brand_id']);
                $goods_list[$key]['brand_name'] = $brand['brand_name'];

                //图片显示
                $row['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
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

            /* 取得订单操作记录 */
            $act_list = array();
            $sql = "SELECT * FROM " . $this->dsc->table('wholesale_order_action') . " WHERE order_id = '" . $delivery_order['order_id'] . "' AND action_place = 1 ORDER BY log_time DESC,action_id DESC";
            $res = $this->db->getAll($sql);

            if ($res) {
                foreach ($res as $key => $row) {
                    $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];
                    $row['pay_status'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
                    $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? $GLOBALS['_LANG']['ss_admin'][SS_SHIPPED_ING] : $GLOBALS['_LANG']['ss'][$row['shipping_status']];
                    $row['action_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['log_time']);
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
            $this->smarty->assign('action_link', array('href' => 'order.php?act=delivery_list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['09_delivery_order']));
            $this->smarty->assign('action_act', ($delivery_order['status'] == 2) ? 'delivery_ship' : 'delivery_cancel_ship');

            return $this->smarty->display('wholesale_delivery_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 退换货订单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'return_list') {

            /* 检查权限 */
            admin_priv('suppliers_order_back_apply');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['12_back_apply']);
            $this->smarty->assign('action_link', array('href' => 'order.php?act=order_query', 'text' => $GLOBALS['_LANG']['03_order_query']));

            $this->smarty->assign('full_page', 1);
            $order_list = wholesale_return_order_list();
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);


            return $this->smarty->display('wholesale_return_list.dwt');
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
            $order_list = wholesale_return_order_list();
            $this->smarty->assign('order_list', $order_list['orders']);
            $this->smarty->assign('filter', $order_list['filter']);
            $this->smarty->assign('record_count', $order_list['record_count']);
            $this->smarty->assign('page_count', $order_list['page_count']);

            return make_json_result($this->smarty->fetch('wholesale_return_list.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
        }

        /* ------------------------------------------------------ */
        //-- 退货单详情
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'return_info') {
            /* 检查权限 */
            admin_priv('suppliers_order_back_apply');

            $ret_id = intval(trim($_REQUEST['ret_id']));
            $rec_id = intval(trim($_REQUEST['rec_id']));

            /* 根据发货单id查询发货单信息 */
            if (!empty($ret_id) || !empty($rec_id)) {
                $back_order = $this->wholesaleOrderService->wholesaleReturnOrderInfo($ret_id);
            } else {
                die('order does not exist');
            }

            /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
            $sql = "SELECT agency_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('admin_id') . "'";
            $agency_id = $this->db->getOne($sql);
            if ($agency_id > 0) {
                if ($back_order['agency_id'] != $agency_id) {
                    return sys_msg($GLOBALS['_LANG']['priv_error']);
                }

                /* 取当前办事处信息 */
                $sql = "SELECT agency_name FROM " . $this->dsc->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";
                $agency_name = $this->db->getOne($sql);
                $back_order['agency_name'] = $agency_name;
            }

            /* 取得用户名 */
            if ($back_order['user_id'] > 0) {

                $user = Users::where('user_id', $back_order['user_id']);
                $user = $this->baseRepository->getToArrayFirst($user);

                if (!empty($user)) {
                    $back_order['user_name'] = $user['user_name'];

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $back_order['user_name'] = $this->dscRepository->stringToStar($back_order['user_name']);
                    }
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
            $this->smarty->assign('action_link', array('href' => 'wholesale_order.php?act=return_list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['10_back_order']));

            return $this->smarty->display('wholesale_return_order_info.dwt');
        }
    }

    /* 获取采购订单列表 */
    private function wholesale_order_list()
    {
        $adminru = get_admin_ru_id();
        $no_main_order = '';

        $result = get_filter();
        if ($result === false) {
            /* 过滤信息 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
            $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
            $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);

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

            $filter['source'] = empty($_REQUEST['source']) ? '' : trim($_REQUEST['source']); //来源起始页

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ? local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
            $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ? local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;

            $where = ' WHERE 1 ';

            if ($filter['keywords']) {
                $where .= " AND (o.order_sn LIKE '%" . $filter['keywords'] . "%'";
                $where .= " OR (iog.goods_name LIKE '%" . $filter['keywords'] . "%' OR iog.goods_sn LIKE '%" . $filter['keywords'] . "%'))";
            }

            if ($adminru['suppliers_id'] > 0) {
                $where .= " AND (SELECT og.suppliers_id FROM " . $GLOBALS['dsc']->table('wholesale_order_goods') . ' as og' . " WHERE og.order_id = o.order_id LIMIT 1) = '" . $adminru['suppliers_id'] . "' ";
            }

            if ($filter['source'] == 'start' || $adminru['ru_id'] > 0 || $filter['keywords']) {
                $no_main_order = " and (select count(*) from " . $GLOBALS['dsc']->table('wholesale_order_info') . " as oi2 where oi2.main_order_id = o.order_id) = 0 ";  //主订单下有子订单时，则主订单不显示
            }

            $leftJoin = '';

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
                $where .= " AND (SELECT u.user_id FROM " . $GLOBALS['dsc']->table('users') . " AS u WHERE u.user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%' LIMIT 1) = o.user_id";
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
                    $where .= $this->orderManageService->wholesaleOrderQuerySql('await_pay', $alias);
                    break;

                case CS_AWAIT_SHIP:
                    $where .= $this->orderManageService->wholesaleOrderQuerySql('await_ship', $alias);
                    break;

                case CS_FINISHED:
                    $where .= $this->orderManageService->wholesaleOrderQuerySql('finished', $alias);
                    break;

                case 104:
                    $where .= $this->orderManageService->wholesaleOrderQuerySql('return_order', $alias);
                    break;

                default:
                    if ($filter['composite_status'] != -1) {
                        $where .= " AND o.order_status = '$filter[composite_status]' ";
                    }
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

            $where_store = '';
            if (empty($filter['start_take_time']) || empty($filter['end_take_time'])) {
                if ($adminru['suppliers_id'] == 0) {
                    $where_store = " AND (SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_goods') . " AS og " . " WHERE o.order_id = og.order_id LIMIT 1) > 0 " .
                        " AND (SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS oi2 WHERE oi2.main_order_id = o.order_id) = 0";
                }
            }

            /* 记录总数 */
            if (!empty($filter['start_take_time']) || !empty($filter['end_take_time'])) {
                $sql = "SELECT o.order_id FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS o " .
                    $leftJoin .
                    "LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_order_action') . " AS oa ON o.order_id = oa.order_id " .
                    $where . $where_store . $no_main_order . " GROUP BY o.order_id";

                $record_count = count($GLOBALS['db']->getAll($sql));
            } elseif (!empty($filter['keywords'])) {
                $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_order_goods') . " AS iog ON iog.order_id = o.order_id ";

                $sql = "SELECT o.order_id FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS o " .
                    $leftJoin .
                    "LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_order_action') . " AS oa ON o.order_id = oa.order_id " .
                    $where . $where_store . $no_main_order . " GROUP BY o.order_id";

                $record_count = count($GLOBALS['db']->getAll($sql));
            } else {
                $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS o " .
                    $leftJoin .
                    $where . $where_store . $no_main_order;
                $record_count = $GLOBALS['db']->getOne($sql);
            }

            $filter['record_count'] = $record_count;
            $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

            if (!empty($filter['keywords']) && empty($filter['user_name'])) {
                $groupBy = " GROUP BY o.order_id ";
            } else {
                $groupBy = " GROUP BY o.order_id ";
                $leftJoin .= " LEFT JOIN " . $GLOBALS['dsc']->table('wholesale_order_action') . " AS oa ON o.order_id = oa.order_id ";
            }

            /* 查询 */
            $sql = "SELECT o.order_id, o.main_order_id, o.order_sn, o.add_time, o.order_status," .
                " o.consignee, o.address, o.email, o.mobile, o.order_amount, o.is_delete,o.pay_id,o.pay_fee,o.pay_time,o.pay_status, " .
                " o.user_id, o.suppliers_id " .
                " FROM " . $GLOBALS['dsc']->table('wholesale_order_info') . " AS o " .
                $leftJoin .
                $where . $where_store . $no_main_order . $groupBy .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

            foreach (array('order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name') as $val) {
                $filter[$val] = stripslashes($filter[$val]);
            }

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $row = $GLOBALS['db']->getAll($sql);

        /* 格式话数据 */
        foreach ($row as $key => $value) {
            $row[$key]['pay_name'] = $GLOBALS['db']->getOne("SELECT pay_name FROM" . $GLOBALS['dsc']->table('payment') . "WHERE pay_id = '" . $value['pay_id'] . "'");
            $row[$key]['pay_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['pay_time']);
            //查会员名称
            $sql = " SELECT user_name FROM " . $GLOBALS['dsc']->table('users') . " WHERE user_id = '" . $value['user_id'] . "'";
            $value['buyer'] = $GLOBALS['db']->getOne($sql, true);
            $row[$key]['buyer'] = !empty($value['buyer']) ? $value['buyer'] : $GLOBALS['_LANG']['anonymous'];

            $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
            $row[$key]['short_order_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);

            /* 取得区域名 */
            $row[$key]['region'] = $this->userAddressService->getUserRegionAddress($value['order_id'], '', 2);

            //ecmoban模板堂 --zhuo start
            $row[$key]['user_name'] = get_table_date('suppliers', "suppliers_id='{$value['suppliers_id']}'", array('suppliers_name'), 2);

            $order_id = $value['order_id'];
            $date = array('order_id');

            $order_child = count(get_table_date('wholesale_order_info', "main_order_id='$order_id'", $date, 1));
            $row[$key]['order_child'] = $order_child;

            $date = array('order_sn');
            $child_list = get_table_date('wholesale_order_info', "main_order_id='$order_id'", $date, 1);
            $row[$key]['child_list'] = $child_list;

            if (!empty($child_list)) {
                $row[$key]['shop_name'] = $GLOBALS["_LANG"]['to_order_sn2'];
            } else {
                $row[$key]['shop_name'] = $row[$key]['user_name'];
            }

            //ecmoban模板堂 --zhuo end
            if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED) {
                /* 如果该订单为无效或取消则显示删除链接 */
                $row[$key]['can_remove'] = 1;
            } else {
                $row[$key]['can_remove'] = 0;
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $row[$key]['mobile'] = $this->dscRepository->stringToStar($value['mobile']);
                $row[$key]['email'] = $this->dscRepository->stringToStar($value['email']);
                $row[$key]['buyer'] = $this->dscRepository->stringToStar($row[$key]['buyer']);
            }
        }

        $arr = array('order_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
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
        $where = 'WHERE 1 ';

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
            $filter['order_referer'] = isset($_REQUEST['order_referer']) ? trim($_REQUEST['order_referer']) : '';

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'do.update_time' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            if ($filter['order_sn']) {
                $where .= " AND do.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
            }
            if ($filter['goods_id']) {
                $where .= " AND (SELECT dg.goods_id FROM " . $GLOBALS['dsc']->table('delivery_goods') . " AS dg WHERE dg.delivery_id = do.delivery_id LIMIT 1) = '" . $filter['goods_id'] . "' ";
            }
            if ($filter['consignee']) {
                $where .= " AND do.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
            }
            if ($filter['status'] >= 0) {
                $where .= " AND do.status = '" . mysql_like_quote($filter['status']) . "'";
            }
            if ($filter['delivery_sn']) {
                $where .= " AND do.delivery_sn LIKE '%" . mysql_like_quote($filter['delivery_sn']) . "%'";
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

            /* 记录总数 */
            $sql = "SELECT COUNT(DISTINCT delivery_id) FROM " . $GLOBALS['dsc']->table('wholesale_delivery_order') . " as do " . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);
            $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

            /* 查询 */
            $sql = "SELECT DISTINCT do.delivery_id, do.delivery_sn, do.order_sn, do.order_id, do.add_time, do.action_user, do.consignee, do.country,
                       do.province, do.city, do.district, do.tel, do.status, do.update_time, do.email, do.suppliers_id, s.suppliers_name
                FROM " . $GLOBALS['dsc']->table("wholesale_delivery_order") . " as do " .
                " LEFT JOIN " . $GLOBALS['dsc']->table('suppliers') . " AS s ON s.suppliers_id = do.suppliers_id " .
                $where .
                " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] . "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];

            $filter = $result['filter'];
        }

        $row = $GLOBALS['db']->getAll($sql);

        /* 格式化数据 */
        if ($row) {
            foreach ($row as $key => $value) {
                $row[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
                $row[$key]['update_time'] = local_date($GLOBALS['_CFG']['time_format'], $value['update_time']);
                if ($value['status'] == 1) {
                    $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][1];
                } elseif ($value['status'] == 2) {
                    $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][2];
                } else {
                    $row[$key]['status_name'] = $GLOBALS['_LANG']['delivery_status'][0];
                }

                $suppliers_info = get_suppliers_info($value['suppliers_id'], array('user_id', 'suppliers_name'));
                $row[$key]['suppliers_name'] = $suppliers_info['suppliers_name'];

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row[$key]['tel'] = $this->dscRepository->stringToStar($value['tel']);
                    $row[$key]['email'] = $this->dscRepository->stringToStar($value['email']);
                }
            }
        }

        $arr = array('delivery' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }

    /**
     * 取得发货单信息
     * @param   int $delivery_order 发货单id（如果delivery_order > 0 就按id查，否则按sn查）
     * @param   string $delivery_sn 发货单号
     * @return  array   发货单信息（金额都有相应格式化的字段，前缀是formated_）
     */
    private function wholesale_delivery_info($delivery_id, $delivery_sn = '')
    {
        $return_order = array();
        if (empty($delivery_id) || !is_numeric($delivery_id)) {
            return $return_order;
        }

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
            $delivery['region'] = $province . ' ' . $city . ' ' . $district;

            /* 格式化金额字段 */
            $delivery['formated_insure_fee'] = price_format($delivery['insure_fee'], false);
            $delivery['formated_shipping_fee'] = price_format($delivery['shipping_fee'], false);

            /* 格式化时间字段 */
            $delivery['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $delivery['add_time']);
            $delivery['formated_update_time'] = local_date($GLOBALS['_CFG']['time_format'], $delivery['update_time']);

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $delivery['mobile'] = $this->dscRepository->stringToStar($delivery['mobile']);
                $delivery['tel'] = $this->dscRepository->stringToStar($delivery['tel']);
                $delivery['email'] = $this->dscRepository->stringToStar($delivery['email']);
            }

            $return_order = $delivery;
        }

        return $return_order;
    }
}
