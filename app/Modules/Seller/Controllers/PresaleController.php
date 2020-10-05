<?php

namespace App\Modules\Seller\Controllers;


use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\PresaleService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心预售商品管理
 */
class PresaleController extends InitController
{
    protected $presaleService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $commonRepository;
    protected $storeCommonService;

    public function __construct(
        PresaleService $presaleService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->presaleService = $presaleService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('goods');
        load_helper('order');
        load_helper('goods', 'seller');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "bonus");
        /* 检查权限 */
        admin_priv('presale');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));

        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '16_presale']);

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 预售活动列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['presale_list']);
            $this->smarty->assign('action_link', ['href' => 'presale.php?act=add', 'text' => $GLOBALS['_LANG']['add_presale'], 'class' => 'icon-plus']);

            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('presale_cat_link', ['href' => 'presale_cat.php?act=list', 'text' => $GLOBALS['_LANG']['presale_cate_list']]);
            }

            $list = $this->presale_list($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('presale_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            /* 显示商品列表页面 */

            return $this->smarty->display('presale_list.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $list = $this->presale_list($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('presale_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('presale_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑预售活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            /* 初始化/取得预售活动信息 */
            if ($_REQUEST['act'] == 'add') {
                $presale = [
                    'pa_catid' => 0,
                    'act_desc' => '',
                    'start_time' => local_date('Y-m-d H:i:s', gmtime() + 86400),
                    'end_time' => local_date('Y-m-d H:i:s', gmtime() + 4 * 86400),
                    'pay_start_time' => local_date('Y-m-d H:i:s', gmtime() + 4 * 86400 + 1),
                    'pay_end_time' => local_date('Y-m-d H:i:s', gmtime() + 6 * 86400)
                ];
            } else {
                $presale_id = intval($_REQUEST['id']);
                if ($presale_id <= 0) {
                    return 'invalid param';
                }
                $presale = $this->presaleService->presaleInfo($presale_id, 0, 0, "seller");

                if ($presale['ru_id'] != $adminru['ru_id']) {
                    $Loaction = "presale.php?act=list";
                    return dsc_header("Location: $Loaction\n");
                }
            }

            $this->smarty->assign('presale', $presale);

            /* 创建 html editor */
            create_html_editor2('act_desc', 'act_desc', $presale['act_desc']);

            set_default_filter(0, 0, $adminru['ru_id']); //by wu

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_presale']);
            $this->smarty->assign('action_link', $this->list_link($_REQUEST['act'] == 'add'));

            $presale_cat_id = isset($presale['cat_id']) ? intval($presale['cat_id']) : 0;
            $cat_select = presale_cat_list(0, $presale_cat_id, false, 0, true, '', 1);

            /* 缩进分类 */
            if ($cat_select) {
                foreach ($cat_select as $k => $v) {
                    if ($v['level']) {
                        $level = '';
                        for ($i = 0; $i < $v['level']; $i++) {
                            $level .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                        }
                        $cat_select[$k]['name'] = $level . $v['name'];
                    }
                }
            }

            $this->smarty->assign('cat_select', $cat_select);
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            /* 显示模板 */

            return $this->smarty->display('presale_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑预售活动的提交
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert_update') {
            /* 取得预售活动id */
            $presale_id = intval($_POST['act_id']);
            if (isset($_POST['finish']) || isset($_POST['succeed']) || isset($_POST['fail']) || isset($_POST['mail'])) {
                if ($presale_id <= 0) {
                    return sys_msg($GLOBALS['_LANG']['error_presale'], 1);
                }
                $presale = $this->presaleService->presaleInfo($presale_id, 0, 0, "seller");
                if (empty($presale)) {
                    return sys_msg($GLOBALS['_LANG']['error_presale'], 1);
                }
            }

            if (isset($_POST['finish'])) {
                /* 判断订单状态 */
                if ($presale['status'] != GBS_UNDER_WAY) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 结束预售活动，修改结束时间为当前时间 */
                $sql = "UPDATE " . $this->dsc->table('presale_activity') .
                    " SET end_time = '" . gmtime() . "' " .
                    "WHERE act_id = '$presale_id' LIMIT 1";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'presale.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['succeed'])) {
                /* 设置活动成功 */

                /* 判断订单状态 */
                if ($presale['status'] != GBS_FINISHED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 如果有订单，更新订单信息 */
                if ($presale['total_order'] > 0) {
                    /* 查找该预售活动的已确认或未确认订单（已取消的就不管了） */
                    $sql = "SELECT order_id " .
                        "FROM " . $this->dsc->table('order_info') .
                        " WHERE extension_code = 'presale' " .
                        "AND extension_id = '$presale_id' " .
                        "AND (order_status = '" . OS_CONFIRMED . "' or order_status = '" . OS_UNCONFIRMED . "')";
                    $order_id_list = $this->db->getCol($sql);

                    /* 更新订单商品价 */
                    $final_price = $presale['trans_price'];
                    $sql = "UPDATE " . $this->dsc->table('order_goods') .
                        " SET goods_price = '$final_price' " .
                        "WHERE order_id " . db_create_in($order_id_list);
                    $this->db->query($sql);

                    /* 查询订单商品总额 */
                    $sql = "SELECT order_id, SUM(goods_number * goods_price) AS goods_amount " .
                        "FROM " . $this->dsc->table('order_goods') .
                        " WHERE order_id " . db_create_in($order_id_list) .
                        " GROUP BY order_id";
                    $res = $this->db->query($sql);
                    foreach ($res as $row) {
                        $order_id = $row['order_id'];
                        $goods_amount = floatval($row['goods_amount']);

                        /* 取得订单信息 */
                        $order = order_info($order_id);

                        /* 判断订单是否有效：余额支付金额 + 已付款金额 >= 保证金 */
                        if ($order['surplus'] + $order['money_paid'] >= $presale['deposit']) {
                            /* 有效，设为已确认，更新订单 */

                            // 更新商品总额
                            $order['goods_amount'] = $goods_amount;

                            // 如果保价，重新计算保价费用
                            if ($order['insure_fee'] > 0) {
                                $shipping = shipping_info($order['shipping_id']);
                                $order['insure_fee'] = shipping_insure_fee($shipping['shipping_code'], $goods_amount, $shipping['insure']);
                            }

                            // 重算支付费用
                            $order['order_amount'] = $order['goods_amount'] + $order['shipping_fee']
                                + $order['insure_fee'] + $order['pack_fee'] + $order['card_fee']
                                - $order['money_paid'] - $order['surplus'];
                            if ($order['order_amount'] > 0) {
                                $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount']);
                            } else {
                                $order['pay_fee'] = 0;
                            }

                            // 计算应付款金额
                            $order['order_amount'] += $order['pay_fee'];

                            // 计算付款状态
                            if ($order['order_amount'] > 0) {
                                $order['pay_status'] = PS_UNPAYED;
                                $order['pay_time'] = 0;
                            } else {
                                $order['pay_status'] = PS_PAYED;
                                $order['pay_time'] = gmtime();
                            }

                            // 如果需要退款，退到帐户余额
                            if ($order['order_amount'] < 0) {
                                // todo （现在手工退款）
                            }

                            // 订单状态
                            $order['order_status'] = OS_CONFIRMED;
                            $order['confirm_time'] = gmtime();

                            // 更新订单
                            $order = addslashes_deep($order);
                            update_order($order_id, $order);
                        } else {
                            /* 无效，取消订单，退回已付款 */

                            // 修改订单状态为已取消，付款状态为未付款
                            $order['order_status'] = OS_CANCELED;
                            $order['to_buyer'] = $GLOBALS['_LANG']['cancel_order_reason'];
                            $order['pay_status'] = PS_UNPAYED;
                            $order['pay_time'] = 0;

                            /* 如果使用余额或有已付款金额，退回帐户余额 */
                            $money = $order['surplus'] + $order['money_paid'];
                            if ($money > 0) {
                                $order['surplus'] = 0;
                                $order['money_paid'] = 0;
                                $order['order_amount'] = $money;

                                // 退款到帐户余额
                                order_refund($order, 1, $GLOBALS['_LANG']['cancel_order_reason'] . ':' . $order['order_sn']);
                            }

                            /* 更新订单 */
                            $order = addslashes_deep($order);
                            update_order($order['order_id'], $order);
                        }
                    }
                }

                /* 修改预售活动状态为成功 */
                $sql = "UPDATE " . $this->dsc->table('presale_activity') .
                    " SET is_finished = '" . GBS_SUCCEED . "' " .
                    "WHERE act_id = '$presale_id' LIMIT 1";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'presale.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['fail'])) {
                /* 设置活动失败 */

                /* 判断订单状态 */
                if ($presale['status'] != GBS_FINISHED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 如果有有效订单，取消订单 */
                if ($presale['valid_order'] > 0) {
                    /* 查找未确认或已确认的订单 */
                    $sql = "SELECT * " .
                        "FROM " . $this->dsc->table('order_info') .
                        " WHERE extension_code = 'presale' " .
                        "AND extension_id = '$presale_id' " .
                        "AND (order_status = '" . OS_CONFIRMED . "' OR order_status = '" . OS_UNCONFIRMED . "') ";
                    $res = $this->db->query($sql);
                    foreach ($res as $order) {
                        // 修改订单状态为已取消，付款状态为未付款
                        $order['order_status'] = OS_CANCELED;
                        $order['to_buyer'] = $GLOBALS['_LANG']['cancel_order_reason'];
                        $order['pay_status'] = PS_UNPAYED;
                        $order['pay_time'] = 0;

                        /* 如果使用余额或有已付款金额，退回帐户余额 */
                        $money = $order['surplus'] + $order['money_paid'];
                        if ($money > 0) {
                            $order['surplus'] = 0;
                            $order['money_paid'] = 0;
                            $order['order_amount'] = $money;

                            // 退款到帐户余额
                            order_refund($order, 1, $GLOBALS['_LANG']['cancel_order_reason'] . ':' . $order['order_sn'], $money);
                        }

                        /* 更新订单 */
                        $order = addslashes_deep($order);
                        update_order($order['order_id'], $order);
                    }
                }

                /* 修改预售活动状态为失败，记录失败原因（活动说明） */
                $sql = "UPDATE " . $this->dsc->table('presale_activity') .
                    " SET is_finished = '" . GBS_FAIL . "', " .
                    "act_desc = '$_POST[act_desc]' " .
                    "WHERE act_id = '$presale_id' LIMIT 1";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'presale.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['mail'])) {
                /* 发送通知邮件 */

                /* 判断订单状态 */
                if ($presale['status'] != GBS_SUCCEED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 取得邮件模板 */
                $tpl = get_mail_template('presale');

                /* 初始化订单数和成功发送邮件数 */
                $count = 0;
                $send_count = 0;

                /* 取得有效订单 */
                $sql = "SELECT o.consignee, o.add_time, g.goods_number, o.order_sn, " .
                    "o.order_amount, o.order_id, o.email " .
                    "FROM " . $this->dsc->table('order_info') . " AS o, " .
                    $this->dsc->table('order_goods') . " AS g " .
                    "WHERE o.order_id = g.order_id " .
                    "AND o.extension_code = 'presale' " .
                    "AND o.extension_id = '$presale_id' " .
                    "AND o.order_status = '" . OS_CONFIRMED . "'";
                $res = $this->db->query($sql);
                foreach ($res as $order) {
                    if (!empty($order['email'])) {
                        /* 邮件模板赋值 */
                        $this->smarty->assign('consignee', $order['consignee']);
                        $this->smarty->assign('add_time', local_date($GLOBALS['_CFG']['time_format'], $order['add_time']));
                        $this->smarty->assign('goods_name', $presale['goods_name']);
                        $this->smarty->assign('goods_number', $order['goods_number']);
                        $this->smarty->assign('order_sn', $order['order_sn']);
                        $this->smarty->assign('order_amount', price_format($order['order_amount']));
                        $this->smarty->assign('shop_url', $this->dsc->seller_url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']);
                        $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                        $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                        /* 取得模板内容，发邮件 */
                        $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                        if ($this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                            $send_count++;
                        }
                    }

                    $count++;
                }

                /* 提示信息 */
                return sys_msg(sprintf($GLOBALS['_LANG']['mail_result'], $count, $send_count));
            } else {

                /* 保存预售信息 */
                $goods_id = intval($_POST['goods_id']);
                if ($goods_id <= 0) {
                    return sys_msg($GLOBALS['_LANG']['error_goods_null']);
                }

                $info = $this->goods_presale($goods_id);
                if ($info && $info['act_id'] != $presale_id) {
                    return sys_msg($GLOBALS['_LANG']['error_goods_exist']);
                }

                $goods_name = $this->db->getOne("SELECT goods_name FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'");

                $act_name = empty($_POST['act_name']) ? $goods_name : $this->dscRepository->subStr($_POST['act_name'], 0, 255, false);

                $deposit = floatval($_POST['deposit']);

                if ($deposit < 0) {
                    $deposit = 0;
                }

                /* 检查开始时间和结束时间是否合理 */
                $start_time = local_strtotime($_POST['start_time']);
                $end_time = local_strtotime($_POST['end_time']);
                $pay_start_time = local_strtotime($_POST['pay_start_time']);//liu
                $pay_end_time = local_strtotime($_POST['pay_end_time']);//liu

                if ($start_time >= $end_time || $pay_start_time >= $pay_end_time || $end_time > $pay_start_time) {//change liu
                    return sys_msg($GLOBALS['_LANG']['invalid_time']);
                }

                $adminru = get_admin_ru_id(); //ecmoban模板堂 --zhuo

                $presale = [
                    'act_name' => $act_name,
                    'act_desc' => $_POST['act_desc'],
                    'cat_id' => intval($_POST['cat_id']),
                    'goods_id' => $goods_id,
                    'user_id' => $adminru['ru_id'], //ecmoban模板堂 --zhuo
                    'goods_name' => $goods_name,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'pay_start_time' => $pay_start_time,//liu
                    'pay_end_time' => $pay_end_time,//liu
                    'deposit' => $deposit
                ];

                /* 清除缓存 */
                clear_cache_files();

                /* 保存数据 */
                if ($presale_id > 0) {
                    $presale['review_status'] = 1;

                    /* update */
                    $this->db->autoExecute($this->dsc->table('presale_activity'), $presale, 'UPDATE', "act_id = '$presale_id'");

                    /* log */
                    admin_log(addslashes($goods_name) . '[' . $presale_id . ']', 'edit', 'presale');

                    /* todo 更新活动表 */

                    /* 提示信息 */
                    $links = [
                        ['href' => 'presale.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_list']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
                } else {
                    /* insert */
                    $this->db->autoExecute($this->dsc->table('presale_activity'), $presale, 'INSERT');

                    /* log */
                    admin_log(addslashes($goods_name), 'add', 'presale');

                    /* 提示信息 */
                    $links = [
                        ['href' => 'presale.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add']],
                        ['href' => 'presale.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['add_success'], 0, $links);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 批量删除预售活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_drop') {
            if (isset($_POST['checkboxes'])) {
                $del_count = 0; //初始化删除数量
                foreach ($_POST['checkboxes'] as $key => $id) {
                    /* 取得预售活动信息 */
                    $presale = $this->presaleService->presaleInfo($id, 0, 0, "seller");

                    /* 如果预售活动已经有订单，不能删除 */
                    if ($presale['valid_order'] <= 0) {
                        /* 删除预售活动 */
                        $sql = "DELETE FROM " . $this->dsc->table('presale_activity') .
                            " WHERE act_id = '$id' LIMIT 1";
                        $this->db->query($sql, 'SILENT');

                        admin_log(addslashes($presale['goods_name']) . '[' . $id . ']', 'remove', 'presale');
                        $del_count++;
                    }
                }

                /* 如果删除了预售活动，清除缓存 */
                if ($del_count > 0) {
                    clear_cache_files();
                }

                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'presale.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $del_count), 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'presale.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_presale'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $filter = dsc_decode($_GET['JSON']);

            $default_arr = ['goods_id' => 0, 'goods_name' => $GLOBALS['_LANG']['search_goods_creat_option_list'], 'shop_price' => 0];
            $arr[] = $default_arr;
            $arr_presale = get_goods_list($filter);
            foreach ($arr_presale as $k => $v) {
                $arr[$k + 1] = $v;
            }
            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 获取本店价
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'get_price') {
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $goods_id = request()->get('goods_id', 0);
            $shop_price = $this->get_shop_price(intval($goods_id));
            return make_json_result($shop_price);
        }

        /*------------------------------------------------------ */
        //-- 编辑保证金
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_deposit') {
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            $sql = "UPDATE " . $this->dsc->table('presale_activity') .
                " SET deposit = '" . $val . "'" .
                " WHERE act_id = '$id'";
            $this->db->query($sql);

            clear_cache_files();

            return make_json_result(number_format($val, 2));
        }

        /*------------------------------------------------------ */
        //-- 删除预售活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 取得预售活动信息 */
            $presale = $this->presaleService->presaleInfo($id, 0, 0, "seller");

            if ($presale['ru_id'] != $adminru['ru_id']) {
                $url = 'presale.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            /* 如果预售活动已经有订单，不能删除 */
            if ($presale['valid_order'] > 0) {
                return make_json_error($GLOBALS['_LANG']['error_exist_order']);
            }

            /* 删除预售活动 */
            $sql = "DELETE FROM " . $this->dsc->table('presale_activity') . " WHERE act_id = '$id' LIMIT 1";
            $this->db->query($sql);

            admin_log(addslashes($presale['goods_name']) . '[' . $id . ']', 'remove', 'presale');

            clear_cache_files();

            $url = 'presale.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }

    /*
     * 取得预售活动列表
     * @return   array
     */
    private function presale_list($ru_id)
    {
        $result = get_filter();
        $where = "";
        if ($result === false) {
            /* 过滤条件 */
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ga.act_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = (!empty($filter['keyword'])) ? " AND (ga.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%')" : '';
            $where .= "AND ga.user_id = '$ru_id' ";

            $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);

            if ($filter['review_status']) {
                $where .= " AND ga.review_status = '" . $filter['review_status'] . "' ";
            }

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] > -1) {
                if ($ru_id == 0) {
                    if ($filter['store_search'] > 0) {
                        $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                        if ($store_type) {
                            $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                        }

                        if ($filter['store_search'] == 1) {
                            $where .= " AND ga.user_id = '" . $filter['merchant_id'] . "' ";
                        } elseif ($filter['store_search'] == 2) {
                            $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                        } elseif ($filter['store_search'] == 3) {
                            $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                        }

                        if ($filter['store_search'] > 1) {
                            $where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                                " WHERE msi.user_id = ga.user_id $store_where) > 0 ";
                        }
                    } else {
                        $where .= " AND ga.user_id = 0";
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('presale_activity') . " AS ga " .
                " WHERE 1 $where";
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT ga.* " .
                "FROM " . $this->dsc->table('presale_activity') . " AS ga " .
                " WHERE 1 $where " .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . $filter['start'] . ", $filter[page_size]";

            $filter['keyword'] = stripslashes($filter['keyword']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $res = $this->db->query($sql);

        $list = [];
        if ($res) {
            foreach ($res as $row) {
                $stat = $this->presaleService->presaleStat($row['act_id'], $row['deposit']);
                $arr = array_merge($row, $stat);

                $status = $this->presaleService->presaleStatus($arr);

                $arr['start_time'] = local_date($GLOBALS['_CFG']['date_format'], $arr['start_time']);
                $arr['end_time'] = local_date($GLOBALS['_CFG']['date_format'], $arr['end_time']);
                $arr['pay_start_time'] = local_date($GLOBALS['_CFG']['date_format'], $arr['pay_start_time']);
                $arr['pay_end_time'] = local_date($GLOBALS['_CFG']['date_format'], $arr['pay_end_time']);
                $arr['cur_status'] = $GLOBALS['_LANG']['gbs'][$status];

                $arr['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);

                $list[] = $arr;
            }
        }

        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 取得某商品的预售活动
     * @param int $goods_id 商品id
     * @return  array
     */
    private function goods_presale($goods_id)
    {
        $sql = "SELECT * FROM " . $this->dsc->table('presale_activity') .
            " WHERE goods_id = '$goods_id' " .
            " AND start_time <= " . gmtime() .
            " AND end_time >= " . gmtime() . " LIMIT 1";

        return $this->db->getRow($sql);
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true)
    {
        $href = 'presale.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }

        return ['href' => $href, 'text' => $GLOBALS['_LANG']['presale_list'], 'class' => 'icon-reply'];
    }

    /*
    * 获取商品的本店售价，供参考预售商品定金参考比对
    */
    private function get_shop_price($goods_id)
    {
        $sql = " SELECT shop_price FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id' ";
        return $this->db->getOne($sql);
    }
}
