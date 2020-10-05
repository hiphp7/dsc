<?php

namespace App\Modules\Stores\Controllers;

use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\GroupBuyService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderService;

/**
 * 商品管理程序
 */
class OrderController extends InitController
{
    protected $groupBuyService;
    protected $config;
    protected $commonRepository;
    protected $orderService;
    protected $goodsWarehouseService;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $dscRepository;
    protected $orderCommonService;
    protected $goodsAttrService;

    public function __construct(
        GroupBuyService $groupBuyService,
        CommonRepository $commonRepository,
        OrderService $orderService,
        GoodsWarehouseService $goodsWarehouseService,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        OrderCommonService $orderCommonService,
        GoodsAttrService $goodsAttrService
    )
    {
        $this->groupBuyService = $groupBuyService;
        $this->commonRepository = $commonRepository;
        $this->orderService = $orderService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderCommonService = $orderCommonService;
        $this->goodsAttrService = $goodsAttrService;
    }

    public function index()
    {
        load_helper('order');
        load_helper('goods');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if (isset($adminru['ru_id']) && $adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $store_id = session('stores_id');
        $ru_id = $this->db->getOne(" SELECT ru_id FROM " . $this->dsc->table('offline_store') . " WHERE id = '$store_id' ");
        $this->smarty->assign("app", "order");

        //设置logo
        $sql = "SELECT value FROM " . $this->dsc->table('shop_config') . " WHERE code = 'stores_logo'";
        $stores_logo = strstr($this->db->getOne($sql), "images");
        $this->smarty->assign('stores_logo', $stores_logo);

        /*------------------------------------------------------ */
        //-- 商品列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            store_priv('order_manage'); //检查权限
            $list = $this->store_order_list($ru_id, $store_id);
            $this->smarty->assign('order_list', $list['orders']);

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $order_type = isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : '-1';
            $this->smarty->assign('order_type', $order_type);

            $this->smarty->assign('full_page', 1);


            $this->smarty->assign('page_title', $GLOBALS['_LANG']['store_order']);
            return $this->smarty->display('order_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->store_order_list($ru_id, $store_id);
            $this->smarty->assign('order_list', $list['orders']);

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('order_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 处理订单页面
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'deal_store_order') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $operate = empty($_REQUEST['operate']) ? '' : trim($_REQUEST['operate']);

            $store_order_info = get_store_order_info($id);
            $order_id = $store_order_info['order_id'];
            $order_info = order_info($order_id);

            /* 抢单 */
            if ($operate == 'grab_order') {
                $info = ['country' => $order_info['country'],
                    'province' => $order_info['province'],
                    'city' => $order_info['city'],
                    'district' => $order_info['district']
                ];
                $this->smarty->assign('complete_user_address', get_complete_address($info) . ' ' . $order_info['address']);
            }

            /* 发货 */
            if ($operate == 'delivery') {
                $info = ['country' => $order_info['country'],
                    'province' => $order_info['province'],
                    'city' => $order_info['city'],
                    'district' => $order_info['district']
                ];
                $this->smarty->assign('complete_user_address', get_complete_address($info) . ' ' . $order_info['address']);
                $this->smarty->assign('order_goods_list', $this->get_order_goods_list($order_id));
                $this->smarty->assign('store_info', $this->get_store_info($store_id));
            }

            /* 提货 */
            if ($operate == 'pick_goods') {
                $this->smarty->assign('order_goods_list', $this->get_order_goods_list($order_id));
                $this->smarty->assign('store_info', $this->get_store_info($store_id));
            }
            if ($operate == 'achieve') {
                $info = ['country' => $order_info['country'],
                    'province' => $order_info['province'],
                    'city' => $order_info['city'],
                    'district' => $order_info['district']
                ];
                $this->smarty->assign('complete_user_address', get_complete_address($info) . ' ' . $order_info['address']);
                $this->smarty->assign('order_goods_list', $this->get_order_goods_list($order_id));
                $this->smarty->assign('store_info', $this->get_store_info($store_id));
            }

            /* 退回抢单 */
            if ($operate == 'back') {
                //查到这笔订单的抢单门店
                $arr = explode(',', $store_order_info['grab_store_list']);
                //剔除当前门店
                foreach ($arr as $key => $val) {
                    if ($val == $store_id) {
                        unset($arr[$key]);
                    }
                }
                $grab_store_list = implode(",", $arr);


                //更新到数据库
                $sql = "UPDATE " . $GLOBALS['dsc']->table('store_order') . " SET grab_store_list = '$grab_store_list' WHERE id = $id";
                $GLOBALS['db']->query($sql);

                $result['error'] = 1;
                $result['message'] = lang('stores/order.back_order_success');
                return response()->json($result);
            }

            $this->smarty->assign('form_action', $operate);
            $this->smarty->assign('order_info', $order_info);
            $this->smarty->assign('store_id', $store_id);
            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('id', $id);

            $result['content'] = $this->smarty->fetch('deal_store_order.dwt');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 提货
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'pick_goods') {
            store_priv('order_manage'); //检查权限
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $pick_code = empty($_REQUEST['pick_code']) ? '' : intval($_REQUEST['pick_code']);
            $link[] = ['href' => 'order.php?act=list', 'text' => '门店订单列表'];
            $result = ["error" => 0, "message" => "", "content" => ""];

            if ($pick_code) {
                $store_order_info = get_store_order_info($id);
                //判断订单是否已付款
                $sql = " SELECT order_status, pay_status, shipping_status, pay_id, user_id FROM " . $this->dsc->table('order_info') . " WHERE order_id = '$order_id' ";
                $order_info = $this->db->getRow($sql);

                $payment = payment_info($order_info['pay_id']);

                if ($order_info['pay_status'] != 2 && !in_array($payment['pay_code'], ['bank', 'cod'])) {
                    $result['error'] = 0;
                    $result['message'] = $GLOBALS['_LANG']['01_stores_pick_goods'];
                } elseif ($store_order_info && $store_order_info['pick_code'] == $pick_code) {
                    $sql = " UPDATE " . $this->dsc->table('order_info') . " SET order_status = '" . OS_SPLITED . "', pay_status = '" . PS_PAYED . "', shipping_status = '" . SS_RECEIVED . "' " .
                        ", shipping_time = '" . gmtime() . "', confirm_take_time = '" . gmtime() . "' " .
                        " WHERE order_id = '$order_id' ";

                    if ($this->db->query($sql)) {
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['_LANG']['02_stores_pick_goods'];
                    }

                    /* 更新商品销量 */
                    $is_update_sale = is_update_sale($order_id);
                    if ($GLOBALS['_CFG']['sales_volume_time'] == SALES_SHIP && $is_update_sale == 0) {
                        get_goods_sale($order_id);
                    }

                    /* 如果使用库存，且发货时减库存，则修改库存 */

                    if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                        change_order_goods_storage($order_id, true, SDT_PAID, 2, 0, $store_id);
                    }

                    $this->orderCommonService->getUserOrderNumServer($order_info['user_id']);
                } else {
                    $result['error'] = 0;
                    $result['message'] = $GLOBALS['_LANG']['03_stores_pick_goods'];
                }
            } else {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['04_stores_pick_goods'];
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 抢单
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'grab_order') {
            store_priv('order_manage'); //检查权限
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $link[] = ['href' => 'order.php?act=list', 'text' => lang('stores/order.order_list')];

            $store_order_info = get_store_order_info($id);
            if (empty($store_order_info['store_id'])) {
                //判断库存 start
                $has_stock = true;
                $goods_list = get_table_date('order_goods', "order_id='{$store_order_info['order_id']}'", ['goods_id', 'goods_attr_id', 'goods_number'], 1);
                foreach ($goods_list as $key => $val) {
                    if (empty($val['goods_attr_id'])) {
                        $goods_number = get_table_date('store_goods', "store_id='$store_id' AND goods_id='{$val['goods_id']}'", ['goods_number'], 2);
                    } else {
                        $goods_attr = str_replace(',', '|', $val['goods_attr_id']);
                        $goods_number = get_table_date('store_products', "store_id='$store_id' AND goods_id='{$val['goods_id']}' AND goods_attr='{$goods_attr}'", ['product_number'], 2);
                    }
                    if ($goods_number < $val['goods_number']) {
                        $has_stock = false;
                        break;
                    }
                }
                if (!$has_stock) {
                    return make_json_response('', 0, lang('stores/order.stock_insufficient'), ['url' => 'order.php?act=list']);
                }
                //判断库存 end

                $sql = " UPDATE " . $this->dsc->table('store_order') . " SET store_id = '$store_id' WHERE id = '$id' ";
                if ($this->db->query($sql)) {
                    //return sys_msg('碉堡了，您以迅雷不及掩耳盗铃的速度抢到了这个订单', 0, $link);
                    return make_json_response('', 1, lang('stores/order.grab_order'), ['url' => 'order.php?act=list']);
                }
            } else {
                //return sys_msg('哎呀手慢了，订单被人抢走了', 1, $link);
                return make_json_response('', 0, lang('stores/order.not_snatched_order'), ['url' => 'order.php?act=list']);
            }
        }

        /*------------------------------------------------------ */
        //-- 发货
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'delivery') {
            store_priv('order_manage'); //检查权限
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
            $invoice_no = empty($_REQUEST['invoice_no']) ? '' : trim($_REQUEST['invoice_no']);

            /* 定义当前时间 */
            define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳

            if ($invoice_no) {
                $order_id = intval(trim($order_id));

                /* 查询：根据订单id查询订单信息 */
                if (!empty($order_id)) {
                    $order = order_info($order_id);
                } else {
                    return 'order does not exist';
                }

                /* 查询：取得用户名 */
                if ($order['user_id'] > 0) {

                    $user_info = Users::where('user_id', $order['user_id']);
                    $user_info = $this->baseRepository->getToArrayFirst($user_info);

                    if (!empty($user_info)) {
                        $order['user_name'] = $user_info['user_name'];
                    }
                }


                /* 查询：其他处理 */
                $order['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
                $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $GLOBALS['_LANG']['ss'][SS_UNSHIPPED] : $order['invoice_no'];

                /* 查询：是否保价 */
                $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

                /* 查询：取得订单商品 */
                $_goods = $this->get_order_goods(['order_id' => $order['order_id'], 'order_sn' => $order['order_sn']], $store_id);

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
                                    $goods_list[$key]['package_goods_list'][$pg_key]['send'] = $GLOBALS['_LANG']['act_good_vacancy'];
                                    $goods_list[$key]['package_goods_list'][$pg_key]['readonly'] = 'readonly="readonly"';
                                } /* 将已经全部发货的商品设置为只读 */ elseif ($pg_value['send'] <= 0) {
                                    $goods_list[$key]['package_goods_list'][$pg_key]['send'] = $GLOBALS['_LANG']['act_good_delivery'];
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
                                $goods_list[$key]['send'] = $GLOBALS['_LANG']['act_good_vacancy'];
                                $goods_list[$key]['readonly'] = 'readonly="readonly"';
                            } elseif ($goods_list[$key]['send'] <= 0) {
                                $goods_list[$key]['send'] = $GLOBALS['_LANG']['act_good_delivery'];
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
                    $_delivery[$value] = $delivery[$value] ?? '';
                }

                /* 发货单入库 */
                $this->db->autoExecute($this->dsc->table('delivery_order'), $_delivery, 'INSERT', '', 'SILENT');
                $delivery_id = $this->db->insert_id();

                if ($delivery_id) {

                    //发货单商品入库
                    if (!empty($goods_list)) {
                        foreach ($goods_list as $value) {
                            // 商品（实货）（虚货）
                            if (empty($value['extension_code']) || $value['extension_code'] == 'virtual_card') {
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
                                $this->db->autoExecute($this->dsc->table('delivery_goods'), $delivery_goods, 'INSERT', '', 'SILENT');
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
                                    $this->db->autoExecute($this->dsc->table('delivery_goods'), $delivery_pg_goods, 'INSERT', '', 'SILENT');
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
                    return make_json_response('', 0, lang('stores/order.operation_fail'), ['url' => 'order.php?act=list']);
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

                /* 取得用户名 */
                if ($delivery_order['user_id'] > 0) {

                    $user_info = Users::where('user_id', $delivery_order['user_id']);
                    $user_info = $this->baseRepository->getToArrayFirst($user_info);

                    if (!empty($user_info)) {
                        $delivery_order['user_name'] = $user_info['user_name'];
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
                  WHERE delivery_id = '" . $delivery_order['delivery_id'] . "'";
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

                /* 检查此单发货商品库存缺货情况  ecmoban模板堂 --zhuo start 下单减库存 */
                $delivery_stock_sql = "SELECT DG.rec_id AS dg_rec_id, OG.rec_id AS og_rec_id, G.model_attr, G.model_inventory, DG.goods_id, DG.delivery_id, DG.is_real, DG.send_number AS sums, G.goods_number AS storage, G.goods_name, DG.send_number," .
                    " OG.goods_attr_id, OG.warehouse_id, OG.area_id, OG.area_city, OG.ru_id, OG.order_id, OG.product_id FROM " . $this->dsc->table('delivery_goods') . " AS DG, " .
                    $this->dsc->table('goods') . " AS G, " .
                    $this->dsc->table('delivery_order') . " AS D, " .
                    $this->dsc->table('order_goods') . " AS OG " .
                    " WHERE DG.goods_id = G.goods_id AND DG.delivery_id = D.delivery_id AND D.order_id = OG.order_id AND DG.goods_sn = OG.goods_sn AND DG.product_id = OG.product_id AND DG.delivery_id = '$delivery_id' GROUP BY OG.rec_id ";

                $delivery_stock_result = $this->db->getAll($delivery_stock_sql);

                for ($i = 0; $i < count($delivery_stock_result); $i++) {
                    $products = $this->goodsWarehouseService->getWarehouseAttrNumber($delivery_stock_result[$i]['goods_id'], $delivery_stock_result[$i]['goods_attr_id'], $delivery_stock_result[$i]['warehouse_id'], $delivery_stock_result[$i]['area_id'], $delivery_stock_result[$i]['area_city'], $delivery_stock_result[$i]['model_attr'], $store_id);
                    $delivery_stock_result[$i]['storage'] = $products ? $products['product_number'] : 0;

                    if (($delivery_stock_result[$i]['sums'] > $delivery_stock_result[$i]['storage'] || $delivery_stock_result[$i]['storage'] <= 0) && (($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) || ($GLOBALS['_CFG']['use_storage'] == '0' && $delivery_stock_result[$i]['is_real'] == 0))) {
                        /* 操作失败 */
                        return make_json_response('', 0, lang('stores/order.goods_shortage'), ['url' => 'order.php?act=list']);
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
                if (is_array($virtual_goods) && count($virtual_goods) > 0) {
                    foreach ($virtual_goods as $virtual_value) {
                        virtual_card_shipping($virtual_value, $order['order_sn'], $msg, 'split');
                    }
                }

                /* 如果使用库存，且发货时减库存，则修改库存 */

                if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_SHIP) {
                    foreach ($delivery_stock_result as $value) {

                        /* 商品（实货）、超级礼包（实货） ecmoban模板堂 --zhuo */
                        if ($value['is_real'] != 0) {
                            //（货品）

                            if (!empty($value['goods_attr_id'])) {
                                $spec = explode(',', $value['goods_attr_id']);
                                if (is_spec($spec)) {
                                    $product_info = $this->goodsAttrService->getProductsInfo($value['goods_id'], $spec, $value['warehouse_id'], $value['area_id'], $value['area_city'], $store_id);
                                    $minus_stock_sql = "UPDATE " . $this->dsc->table('store_products') . "
                                            SET product_number = product_number - " . $value['sums'] . "
                                            WHERE store_id = '$store_id' AND goods_id ='" . $value['goods_id'] . "' AND product_id = " . $product_info['product_id'];
                                }
                            } else {
                                $minus_stock_sql = "UPDATE " . $this->dsc->table('store_goods') . "
                                            SET goods_number = goods_number - " . $value['sums'] . "
                                            WHERE goods_id = " . $value['goods_id'] . "AND store_id = '$store_id'";
                            }

                            $this->db->query($minus_stock_sql, 'SILENT');

                            //库存日志
                            $logs_other = [
                                'goods_id' => $value['goods_id'],
                                'order_id' => $value['order_id'],
                                'use_storage' => $GLOBALS['_CFG']['stock_dec_time'],
                                'admin_id' => session('store_user_id'),
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
                    $links[] = ['text' => $GLOBALS['_LANG']['delivery_sn'] . $GLOBALS['_LANG']['detail'], 'href' => 'order.php?act=delivery_info&delivery_id=' . $delivery_id];
                    return sys_msg(lang('stores/order.operation_fail'), 1, $links);
                }

                /* 标记订单为已确认 “已发货” */
                /* 更新发货时间 */
                $order_finish = $this->get_all_delivery_finish($order_id);
                $shipping_status = ($order_finish == 1) ? SS_SHIPPED : SS_SHIPPED_PART;
                $arr['shipping_status'] = $shipping_status;
                $arr['shipping_time'] = GMTIME_UTC; // 发货时间
                $arr['invoice_no'] = trim($order['invoice_no'] . '<br>' . $invoice_no, '<br>');
                update_order($order_id, $arr);

                $action_note = session('stores_name') . lang('common.deliver_goods');
                /* 发货单发货记录log */
                order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note, session('stores_name'), 1);

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
                        $this->smarty->assign('confirm_url', $this->dsc->stores_url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']); //by wu
                        $this->smarty->assign('send_msg_url', $this->dsc->stores_url() . 'user_message.php?act=message_list&order_id=' . $order['order_id']);
                        $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                        $this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                    }

                    /* 如果需要，发短信 */
                    if ($GLOBALS['_CFG']['sms_order_shipped'] == '1' && $order['mobile'] != '') {
                        if ($order['ru_id']) {
                            $shop_name = $this->merchantCommonService->getShopName($order['ru_id'], 1);
                        } else {
                            $shop_name = $GLOBALS['_CFG']['shop_name'];
                        }

                        //短信接口参数
                        $smsParams = [
                            'shop_name' => $shop_name,
                            'user_name' => $user_info['user_name'],
                            'consignee' => $order['consignee'],
                            'order_sn' => $order['order_sn'],
                            'mobile_phone' => $order['mobile']
                        ];

                        $this->commonRepository->smsSend($order['mobile'], $smsParams, 'sms_order_shipped');
                    }

                    /* 更新商品销量 */
                    $is_update_sale = is_update_sale($order_id);
                    if ($GLOBALS['_CFG']['sales_volume_time'] == SALES_SHIP && $is_update_sale == 0) {
                        get_goods_sale($order_id);
                    }
                }
                /* 清除缓存 */
                clear_cache_files();

                /* 操作成功 */
                return make_json_response('', 1, lang('stores/order.operation_success'), ['url' => 'order.php?act=list']);
            } else {
                return make_json_response('', 0, lang('stores/order.please_order_id'), ['url' => 'order.php?act=list']);
            }
        }

        /* ------------------------------------------------------ */
        //-- 订单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'operate') {
            $order_id = isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id']) ? trim($_REQUEST['order_id']) : 0;
            $batch = isset($_REQUEST['batch']); // 是否批处理
            /* 批量打印订单 */
            if (isset($_POST['print'])) {
                if (empty($_POST['order_id'])) {
                    return sys_msg($GLOBALS['_LANG']['pls_select_order']);
                }

                if (isset($this->config['tp_api']) && $this->config['tp_api']) {
                    //快递鸟、电子面单 start
                    $url = 'tp_api.php?act=order_print&order_sn=' . $_POST['order_id'];
                    return dsc_header("Location: $url\n");
                    //快递鸟、电子面单 end
                }
            } else {
                return dsc_header("Location: order.php?act=list\n");
            }
        }
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
            FROM " . $GLOBALS['dsc']->table('delivery_goods') . " AS DG, " . $GLOBALS['dsc']->table('delivery_order') . " AS o
            WHERE o.delivery_id = DG.delivery_id
            AND o.status IN (0, 2)
            AND o.order_id = '$order_id'
            AND DG.parent_id = '$package_id'
            AND DG.goods_id = '$goods_id'
            AND DG.extension_code = '$extension_code'";
        $sql .= ($product_id > 0) ? " AND DG.product_id = '$product_id'" : '';

        $send = $GLOBALS['db']->getOne($sql);

        return empty($send) ? 0 : $send;
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
                $return_array[$key]['send'] = $GLOBALS['_LANG']['act_good_delivery'];
                $return_array[$key]['readonly'] = 'readonly="readonly"';
            }

            /* 是否缺货 */
            if ($return_array[$key]['storage'] <= 0 && $GLOBALS['_CFG']['use_storage'] == '1') {
                $return_array[$key]['send'] = $GLOBALS['_LANG']['act_good_vacancy'];
                $return_array[$key]['readonly'] = 'readonly="readonly"';
            }
        }

        return $return_array;
    }

    /**
     * 取得订单商品
     * @param array $order 订单数组
     * @return array
     */
    private function get_order_goods($order, $store_id = 0)
    {
        $goods_list = [];
        $goods_attr = [];

        $sql = "SELECT o.*, g.model_inventory, g.model_attr AS model_attr, g.suppliers_id AS suppliers_id, g.goods_number AS storage, g.goods_thumb, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, p.product_sn, g.bar_code  " .
            "FROM " . $GLOBALS['dsc']->table('order_goods') . " AS o " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('products') . " AS p ON o.product_id = p.product_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " AS g ON o.goods_id = g.goods_id " .
            "LEFT JOIN " . $GLOBALS['dsc']->table('brand') . " AS b ON g.brand_id = b.brand_id " .
            "WHERE o.order_id = '$order[order_id]' ";

        $res = $GLOBALS['db']->query($sql);

        //当前域名协议
        $http = $GLOBALS['dsc']->http();

        foreach ($res as $row) {
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

            if ($row['product_id'] > 0) {
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($row['goods_id'], $row['goods_attr_id'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $row['model_attr'], $store_id);
                $row['storage'] = $products['product_number'];
            } else {
                if ($row['model_inventory'] == 1) {
                    $row['storage'] = $this->get_warehouse_area_goods($row['warehouse_id'], $row['goods_id'], 'warehouse_goods');
                } elseif ($row['model_inventory'] == 2) {
                    $row['storage'] = $this->get_warehouse_area_goods($row['area_id'], $row['goods_id'], 'warehouse_area_goods');
                }
            }

            //图片显示
            $row['goods_thumb'] = get_image_path($row['goods_thumb']);

            $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
            $row['formated_goods_price'] = price_format($row['goods_price']);

            $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

            if ($row['extension_code'] == 'package_buy') {
                $row['storage'] = '';
                $row['brand_name'] = '';
                $row['package_goods_list'] = $this->get_package_goods_list($row['goods_id']);
            }

            //处理货品id
            $row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];

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

        return ['goods_list' => $goods_list, 'attr' => $attr];
    }

    private function get_warehouse_area_goods($warehouse_id, $goods_id, $table)
    {
        $sql = "SELECT region_number FROM " . $GLOBALS['dsc']->table($table) . " WHERE region_id = '$warehouse_id' AND goods_id = '$goods_id'";
        return $GLOBALS['db']->getOne($sql);
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
            FROM " . $GLOBALS['dsc']->table('package_goods') . " AS pg
                LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " AS g ON pg.goods_id = g.goods_id
                LEFT JOIN " . $GLOBALS['dsc']->table('products') . " AS p ON pg.product_id = p.product_id
            WHERE pg.package_id = '$package_id'";
        $resource = $GLOBALS['db']->query($sql);
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
                FROM " . $GLOBALS['dsc']->table('goods_attr') . " AS ga, " . $GLOBALS['dsc']->table('attribute') . " AS a
                WHERE a.attr_id = ga.attr_id
                AND a.attr_type = 1
                AND goods_id IN ($good_product_str) ORDER BY a.sort_order, a.attr_id, g.goods_attr_id";
            $result_goods_attr = $GLOBALS['db']->getAll($sql);

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
    /*------------------------------------------------------ */
    //-- 函数相�    �
    /*------------------------------------------------------ */
    private function store_order_list($ru_id = 0, $store_id = 0)
    {
        /* 过滤查询 */
        $filter = [];

        /* 过滤信息 */
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
        }
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['email'] = empty($_REQUEST['email']) ? '' : trim($_REQUEST['email']);
        $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);
        $filter['tel'] = empty($_REQUEST['tel']) ? '' : trim($_REQUEST['tel']);
        $filter['mobile'] = empty($_REQUEST['mobile']) ? 0 : trim($_REQUEST['mobile']);
        $filter['order_status'] = isset($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : -1;
        $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : -1;
        $filter['pay_status'] = isset($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : -1;
        $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
        $filter['composite_status'] = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : -1;
        $filter['pick_code'] = empty($_REQUEST['pick_code']) ? '' : intval($_REQUEST['pick_code']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        //待抢单的条件筛选
        $filter['order_type'] = isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : -1;
        $where = " WHERE 1 AND (so.store_id = '$store_id' OR (so.store_id = '0' AND so.is_grab_order = '1' AND FIND_IN_SET('" . $store_id . "',grab_store_list))) ";

        //判断订单筛选条件
        $where = " WHERE 1 AND (so.store_id = '$store_id' OR (so.store_id = '0' AND so.is_grab_order = '1' )) ";
        switch ($filter['order_type']) {
            case 0:
                $where .= " AND so.store_id = '0' AND so.is_grab_order = '1' ";
                break;
            case 1:
                $where .= " AND store_id = $store_id AND (shipping_status = 0 OR shipping_status = 3) AND order_status < 2 "; //待发货
                break;
            case 2:
                $where .= " AND store_id = $store_id  AND shipping_status = 3 AND order_status < 2 "; //配货中
                break;
            case 3:
                $where .= " AND store_id = $store_id  AND shipping_status = 1 AND (order_status < 2 OR order_status = 5) "; //已发货
                break;
        }

        /* 初始条件 */

        $where .= " AND (select count(*) from " . $GLOBALS['dsc']->table('order_info') . " as oi1 WHERE oi1.main_order_id = so.order_id) = 0 ";  //主订单下有子订单时，则主订单不显示

        if ($filter['order_sn']) {
            $where .= " AND o.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
        }
        if ($filter['consignee']) {
            $where .= " AND o.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
        }
        if ($filter['tel']) {
            $where .= " AND o.tel = '" . $filter['tel'] . "'";
        }
        if ($filter['mobile']) {
            $where .= " AND o.mobile = '" . $filter['mobile'] . "'";
        }
        if ($filter['pick_code']) {
            $where .= " AND so.pick_code = '" . $filter['pick_code'] . "'";
        }

        if ($ru_id > 0) {
            $where .= " AND o.ru_id = '$ru_id' ";
        } else {
            $where .= " AND o.ru_id = 0 ";
        }

        /* 获得总记录数据 */
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['dsc']->table('store_order') . ' AS so, ' .
            $GLOBALS['dsc']->table('order_info') . ' AS o ' .
            $where . " AND o.order_id = so.order_id ";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获得商品数据 */
        $arr = [];
        $sql = 'SELECT so.*, o.order_id, o.order_sn, o.add_time, o.consignee, o.order_status, o.shipping_status, o.pay_status, so.store_id, ' .
            "(" . $this->orderService->orderAmountField('o.') . ") AS total_fee, o.shipping_fee, o.shipping_name, o.invoice_no, o.mobile " .
            'FROM ' . $GLOBALS['dsc']->table('store_order') . ' AS so, ' .
            $GLOBALS['dsc']->table('order_info') . ' AS o ' .
            $where . " AND o.order_id = so.order_id " .
            'ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'];

        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

        $idx = 0;
        foreach ($res as $rows) {
            $rows['shipping_fee'] = price_format($rows['shipping_fee']);

            $info = [];
            $rows['address'] = isset($rows['address']) ? $rows['address'] : '';
            $rows['complete_user_address'] = get_complete_address($info) . ' ' . $rows['address'];
            $rows['order_goods_list'] = $this->get_order_goods_list($rows['order_id']);
            $rows['rowspan'] = count($rows['order_goods_list']);
            $rows['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);
            $rows['formated_total_fee'] = price_format($rows['total_fee']);
            $rows['take_time'] = $rows['take_time'];
            $rows['mobile'] = $rows['mobile'];

            $arr[$idx] = $rows;
            $idx++;
        }

        return ['orders' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /* 获取订单商品 */
    private function get_order_goods_list($order_id = 0)
    {
        $sql = " SELECT og.order_id, og.goods_id, og.goods_name, og.goods_price, og.goods_number, g.goods_thumb, og.goods_sn, og.goods_attr, og.product_id FROM " . $GLOBALS['dsc']->table('order_goods') . " AS og " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('goods') . " AS g ON g.goods_id = og.goods_id " .
            " WHERE og.order_id = '$order_id' ";
        $order_goods_list = $GLOBALS['db']->getAll($sql);

        foreach ($order_goods_list as $key => $val) {
            $order_goods_list[$key]['formated_goods_price'] = price_format($val['goods_price']);

            //图片显示
            $val['goods_thumb'] = get_image_path($val['goods_thumb']);
            $order_goods_list[$key]['goods_thumb'] = $val['goods_thumb'];
            $order_goods_list[$key]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $val['goods_id']], $val['goods_name']);

            //获得商品货号
            $sql = "SELECT product_sn FROM " . $GLOBALS['dsc']->table('products') . " WHERE product_id = '{$val['product_id']}'";
            $order_goods_list[$key]['product_sn'] = $GLOBALS['db']->getOne($sql);
        }

        return $order_goods_list;
    }

    /* 获取门店信息 */
    private function get_store_info($store_id = 0)
    {
        $sql = " SELECT * FROM " . $GLOBALS['dsc']->table('offline_store') . " WHERE id = '$store_id' ";
        $store_info = $GLOBALS['db']->getRow($sql);

        /* 处理数据 */
        if ($store_info) {
            $info = ['country' => $store_info['country'],
                'province' => $store_info['province'],
                'city' => $store_info['city'],
                'district' => $store_info['district']
            ];
            $store_info['complete_store_address'] = get_complete_address($info) . ' ' . $store_info['stores_address'];
        }

        return $store_info;
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
            FROM ' . $GLOBALS['dsc']->table('order_goods') . '
            WHERE order_id = \'' . $order_id . '\'
            AND goods_number > send_number';

        $sum = $GLOBALS['db']->getOne($sql);
        if (empty($sum)) {
            $return_res = 1;
        }

        return $return_res;
    }

    /**
     * 取得发货单信息
     * @param int $delivery_order 发货单id（如果delivery_order > 0 就按id查，否则按sn查）
     * @param string $delivery_sn 发货单号
     * @return  array   发货单信息（金额都有相应格式化的字段，前缀是formated_）
     */
    private function delivery_order_info($delivery_id, $delivery_sn = '')
    {
        $return_order = [];
        if (empty($delivery_id) || !is_numeric($delivery_id)) {
            return $return_order;
        }

        $where = '';

        $sql = "SELECT * FROM " . $GLOBALS['dsc']->table('delivery_order');
        if ($delivery_id > 0) {
            $sql .= " WHERE delivery_id = '$delivery_id'";
        } else {
            $sql .= " WHERE delivery_sn = '$delivery_sn'";
        }

        $sql .= $where;
        $sql .= " LIMIT 0, 1";
        $delivery = $GLOBALS['db']->getRow($sql);
        if ($delivery) {
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
            $sql = "SELECT COUNT(delivery_id)
                FROM " . $GLOBALS['dsc']->table('delivery_order') . "
                WHERE order_id = '$order_id'
                AND status = 2 ";
            $sum = $GLOBALS['db']->getOne($sql);
            // 全部发货
            if (empty($sum)) {
                $return_res = 1;
            } // 未全部发货
            else {
                /* 订单全部发货中时：当前发货单总数 */
                $sql = "SELECT COUNT(delivery_id)
            FROM " . $GLOBALS['dsc']->table('delivery_order') . "
            WHERE order_id = '$order_id'
            AND status <> 1 ";
                $_sum = $GLOBALS['db']->getOne($sql);
                if ($_sum == $sum) {
                    $return_res = -2; // 完全没发货
                } else {
                    $return_res = -1; // 部分发货
                }
            }
        }

        return $return_res;
    }
}
