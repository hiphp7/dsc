<?php

namespace App\Modules\Seller\Controllers;

use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\GroupBuyService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心团购商品管理
 */
class GroupBuyController extends InitController
{
    protected $groupBuyService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $commonRepository;
    protected $storeCommonService;

    public function __construct(
        GroupBuyService $groupBuyService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->groupBuyService = $groupBuyService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('goods');
        load_helper('order');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "bonus");
        /* 检查权限 */
        admin_priv('group_by');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));

        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '08_group_buy']);

        /*------------------------------------------------------ */
        //-- 团购活动列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['group_buy_list']);
            $this->smarty->assign('action_link', ['href' => 'group_buy.php?act=add', 'text' => $GLOBALS['_LANG']['add_group_buy'], 'class' => 'icon-plus']);

            $list = $this->group_buy_list($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('group_buy_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 显示商品列表页面 */

            return $this->smarty->display('group_buy_list.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $list = $this->group_buy_list($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('group_buy_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('group_buy_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑团购活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            set_default_filter(0, 0, $adminru['ru_id']);
            /* 初始化/取得团购活动信息 */
            if ($_REQUEST['act'] == 'add') {
                $group_buy = [
                    'act_id' => 0,
                    'start_time' => date('Y-m-d H:i:s', time() + 86400),
                    'end_time' => date('Y-m-d H:i:s', time() + 4 * 86400),
                    'price_ladder' => [['amount' => 0, 'price' => 0]]
                ];
            } else {
                $group_buy_id = intval($_REQUEST['id']);
                if ($group_buy_id <= 0) {
                    return 'invalid param';
                }

                $where = [
                    'group_buy_id' => $group_buy_id,
                    'path' => 'seller'
                ];
                $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

                if ($group_buy['user_id'] != $adminru['ru_id']) {
                    $Loaction = "group_buy.php?act=list";
                    return dsc_header("Location: $Loaction\n");
                }
            }
            $this->smarty->assign('group_buy', $group_buy);

            //分类列表 by wu
            $select_category_html = '';
            $select_category_html .= insert_select_category(0, 0, 0, 'category', 1);
            $this->smarty->assign('select_category_html', $select_category_html);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_group_buy']);
            $this->smarty->assign('action_link', $this->list_link($_REQUEST['act'] == 'add'));
            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            /* 显示模板 */

            return $this->smarty->display('group_buy_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑团购活动的提交
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert_update') {
            /* 取得团购活动id */
            $group_buy_id = intval($_POST['act_id']);
            if (isset($_POST['finish']) || isset($_POST['succeed']) || isset($_POST['fail']) || isset($_POST['mail'])) {
                if ($group_buy_id <= 0) {
                    return sys_msg($GLOBALS['_LANG']['error_group_buy'], 1);
                }

                $where = [
                    'group_buy_id' => $group_buy_id,
                    'path' => 'seller'
                ];
                $group_buy = $this->groupBuyService->getGroupBuyInfo($where);
                if (empty($group_buy)) {
                    return sys_msg($GLOBALS['_LANG']['error_group_buy'], 1);
                }
            }

            if (isset($_POST['finish'])) {
                /* 判断订单状态 */
                if ($group_buy['status'] != GBS_UNDER_WAY) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 结束团购活动，修改结束时间为当前时间 */
                $sql = "UPDATE " . $this->dsc->table('goods_activity') .
                    " SET end_time = '" . gmtime() . "' " .
                    "WHERE act_id = '$group_buy_id' LIMIT 1";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'group_buy.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['succeed'])) {
                /* 设置活动成功 */

                /* 判断订单状态 */
                if ($group_buy['status'] != GBS_FINISHED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 如果有订单，更新订单信息 */
                if ($group_buy['total_order'] > 0) {
                    /* 查找该团购活动的已确认或未确认订单（已取消的就不管了） */
                    $sql = "SELECT order_id " .
                        "FROM " . $this->dsc->table('order_info') .
                        " WHERE extension_code = 'group_buy' " .
                        "AND extension_id = '$group_buy_id' " .
                        "AND (order_status = '" . OS_CONFIRMED . "' or order_status = '" . OS_UNCONFIRMED . "')";
                    $order_id_list = $this->db->getCol($sql);

                    /* 更新订单商品价 */
                    $final_price = $group_buy['trans_price'];
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
                        if ($order['surplus'] + $order['money_paid'] >= $group_buy['deposit']) {
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

                /* 修改团购活动状态为成功 */
                $sql = "UPDATE " . $this->dsc->table('goods_activity') .
                    " SET is_finished = '" . GBS_SUCCEED . "' " .
                    "WHERE act_id = '$group_buy_id' LIMIT 1";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'group_buy.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['fail'])) {
                /* 设置活动失败 */

                /* 判断订单状态 */
                if ($group_buy['status'] != GBS_FINISHED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 如果有有效订单，取消订单 */
                if ($group_buy['valid_order'] > 0) {
                    /* 查找未确认或已确认的订单 */
                    $sql = "SELECT * " .
                        "FROM " . $this->dsc->table('order_info') .
                        " WHERE extension_code = 'group_buy' " .
                        "AND extension_id = '$group_buy_id' " .
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

                /* 修改团购活动状态为失败，记录失败原因（活动说明） */
                $sql = "UPDATE " . $this->dsc->table('goods_activity') .
                    " SET is_finished = '" . GBS_FAIL . "', " .
                    "act_desc = '$_POST[act_desc]' " .
                    "WHERE act_id = '$group_buy_id' LIMIT 1";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'group_buy.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['mail'])) {
                /* 发送通知邮件 */

                /* 判断订单状态 */
                if ($group_buy['status'] != GBS_SUCCEED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 取得邮件模板 */
                $tpl = get_mail_template('group_buy');

                /* 初始化订单数和成功发送邮件数 */
                $count = 0;
                $send_count = 0;

                /* 取得有效订单 */
                $sql = "SELECT o.consignee, o.add_time, g.goods_number, o.order_sn, " .
                    "o.order_amount, o.order_id, o.email " .
                    "FROM " . $this->dsc->table('order_info') . " AS o, " .
                    $this->dsc->table('order_goods') . " AS g " .
                    "WHERE o.order_id = g.order_id " .
                    "AND o.extension_code = 'group_buy' " .
                    "AND o.extension_id = '$group_buy_id' " .
                    "AND o.order_status = '" . OS_CONFIRMED . "'";
                $res = $this->db->query($sql);
                foreach ($res as $order) {
                    if (!empty($order['email'])) {
                        /* 邮件模板赋值 */
                        $this->smarty->assign('consignee', $order['consignee']);
                        $this->smarty->assign('add_time', local_date($GLOBALS['_CFG']['time_format'], $order['add_time']));
                        $this->smarty->assign('goods_name', $group_buy['goods_name']);
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
                /* 保存团购信息 */
                $goods_id = intval($_POST['goods_id']);
                if ($goods_id <= 0) {
                    return sys_msg($GLOBALS['_LANG']['error_goods_null']);
                }
                $info = $this->goods_group_buy($goods_id);
                if ($info && $info['act_id'] != $group_buy_id) {
                    return sys_msg($GLOBALS['_LANG']['error_goods_exist']);
                }

                $goods_name = $this->db->getOne("SELECT goods_name FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'");

                $act_name = empty($_POST['act_name']) ? $goods_name : $this->dscRepository->subStr($_POST['act_name'], 0, 255, false);

                $deposit = floatval($_POST['deposit']);
                if ($deposit < 0) {
                    $deposit = 0;
                }

                $restrict_amount = intval($_POST['restrict_amount']);
                if ($restrict_amount < 0) {
                    $restrict_amount = 0;
                }

                $gift_integral = intval($_POST['gift_integral']);
                if ($gift_integral < 0) {
                    $gift_integral = 0;
                }

                $price_ladder = [];
                $count = count($_POST['ladder_amount']);
                for ($i = $count - 1; $i >= 0; $i--) {
                    /* 如果数量小于等于0，不要 */
                    $amount = intval($_POST['ladder_amount'][$i]);
                    if ($amount <= 0) {
                        continue;
                    }

                    /* 如果价格小于等于0，不要 */
                    $price = round(floatval($_POST['ladder_price'][$i]), 2);
                    if ($price <= 0) {
                        continue;
                    }

                    /* 加入价格阶梯 */
                    $price_ladder[$amount] = ['amount' => $amount, 'price' => $price];
                }
                if (count($price_ladder) < 1) {
                    return sys_msg($GLOBALS['_LANG']['error_price_ladder']);
                }

                /* 限购数量不能小于价格阶梯中的最大数量 */
                $amount_list = array_keys($price_ladder);
                if ($restrict_amount > 0 && max($amount_list) > $restrict_amount) {
                    return sys_msg($GLOBALS['_LANG']['error_restrict_amount']);
                }

                ksort($price_ladder);
                $price_ladder = array_values($price_ladder);

                /* 检查开始时间和结束时间是否合理 */
                $start_time = local_strtotime($_POST['start_time']);
                $end_time = local_strtotime($_POST['end_time']);
                if ($start_time >= $end_time) {
                    return sys_msg($GLOBALS['_LANG']['invalid_time']);
                }
                $is_hot = isset($_REQUEST['is_hot']) ? $_REQUEST['is_hot'] : 0;
                $is_new = isset($_REQUEST['is_new']) ? $_REQUEST['is_new'] : 0;
                $group_buy = [
                    'act_name' => $act_name,
                    'act_desc' => $_POST['act_desc'] ?? '',
                    'act_type' => GAT_GROUP_BUY,
                    'goods_id' => $goods_id,
                    'user_id' => $adminru['ru_id'], //ecmoban模板堂 --zhuo
                    'goods_name' => $goods_name,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'is_hot' => $is_hot,
                    'is_new' => $is_new,
                    'ext_info' => serialize([
                        'price_ladder' => $price_ladder,
                        'restrict_amount' => $restrict_amount,
                        'gift_integral' => $gift_integral,
                        'deposit' => $deposit
                    ])
                ];

                /* 清除缓存 */
                clear_cache_files();

                /* 保存数据 */
                if ($group_buy_id > 0) {
                    $group_buy['review_status'] = 1;

                    /* update */
                    $this->db->autoExecute($this->dsc->table('goods_activity'), $group_buy, 'UPDATE', "act_id = '$group_buy_id' AND act_type = " . GAT_GROUP_BUY);

                    /* log */
                    admin_log(addslashes($goods_name) . '[' . $group_buy_id . ']', 'edit', 'group_buy');

                    /* todo 更新活动表 */

                    /* 提示信息 */
                    $links = [
                        ['href' => 'group_buy.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_list']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
                } else {
                    /* insert */
                    $this->db->autoExecute($this->dsc->table('goods_activity'), $group_buy, 'INSERT');

                    /* log */
                    admin_log(addslashes($goods_name), 'add', 'group_buy');

                    /* 提示信息 */
                    $links = [
                        ['href' => 'group_buy.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add']],
                        ['href' => 'group_buy.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['add_success'], 0, $links);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 批量删除团购活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_drop') {
            if (isset($_POST['checkboxes'])) {
                $del_count = 0; //初始化删除数量
                foreach ($_POST['checkboxes'] as $key => $id) {
                    /* 取得团购活动信息 */

                    $where = [
                        'group_buy_id' => $id,
                        'path' => 'seller'
                    ];
                    $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

                    /* 如果团购活动已经有订单，不能删除 */
                    if (isset($group_buy['valid_order']) && $group_buy['valid_order'] > 0) {
                        continue;
                    }

                    /* 删除团购活动 */
                    $sql = "DELETE FROM " . $this->dsc->table('goods_activity') .
                        " WHERE act_id = '$id' LIMIT 1";
                    $this->db->query($sql, 'SILENT');

                    if (isset($group_buy['goods_name'])) {
                        admin_log(addslashes($group_buy['goods_name']) . '[' . $id . ']', 'remove', 'group_buy');
                    }

                    $del_count++;
                }

                /* 如果删除了团购活动，清除缓存 */
                if ($del_count > 0) {
                    clear_cache_files();
                }

                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'group_buy.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $del_count), 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'group_buy.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_group_buy'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索单条商品信息
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'group_goods') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $filter = dsc_decode($_GET['JSON']);
            $arr = get_admin_goods_info($filter->goods_id);

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $filter = dsc_decode($_GET['JSON']);
            $arr = get_goods_list($filter);

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 编辑保证金
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_deposit') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            $sql = "SELECT ext_info FROM " . $this->dsc->table('goods_activity') .
                " WHERE act_id = '$id' AND act_type = '" . GAT_GROUP_BUY . "'";
            $ext_info = unserialize($this->db->getOne($sql));
            $ext_info['deposit'] = $val;

            $sql = "UPDATE " . $this->dsc->table('goods_activity') .
                " SET ext_info = '" . serialize($ext_info) . "'" .
                " WHERE act_id = '$id'";
            $this->db->query($sql);

            clear_cache_files();

            return make_json_result(number_format($val, 2));
        }

        /*------------------------------------------------------ */
        //-- 编辑保证金
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_restrict_amount') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $sql = "SELECT ext_info FROM " . $this->dsc->table('goods_activity') .
                " WHERE act_id = '$id' AND act_type = '" . GAT_GROUP_BUY . "'";
            $ext_info = unserialize($this->db->getOne($sql));
            $ext_info['restrict_amount'] = $val;

            $sql = "UPDATE " . $this->dsc->table('goods_activity') .
                " SET ext_info = '" . serialize($ext_info) . "'" .
                " WHERE act_id = '$id'";
            $this->db->query($sql);

            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 删除团购活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 取得团购活动信息 */

            $where = [
                'group_buy_id' => $id,
                'path' => 'seller'
            ];
            $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

            if ($group_buy['user_id'] != $adminru['ru_id']) {
                $url = 'group_buy.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            }

            /* 如果团购活动已经有订单，不能删除 */
            if (isset($group_buy['valid_order']) && $group_buy['valid_order'] > 0) {
                return make_json_error($GLOBALS['_LANG']['error_exist_order']);
            }

            /* 删除团购活动 */
            $sql = "DELETE FROM " . $this->dsc->table('goods_activity') . " WHERE act_id = '$id' LIMIT 1";
            $this->db->query($sql);

            if (isset($group_buy['goods_name'])) {
                admin_log(addslashes($group_buy['goods_name']) . '[' . $id . ']', 'remove', 'group_buy');
            }

            clear_cache_files();

            $url = 'group_buy.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }

    /*
     * 取得团购活动列表
     * @return   array
     */
    private function group_buy_list($ru_id)
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ga.act_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);

            $where = (!empty($filter['keyword'])) ? " AND (ga.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%')" : '';

            //ecmoban模板堂 --zhuo start
            if ($ru_id > 0) {
                $where .= " and ga.user_id = '$ru_id'";
            }
            //ecmoban模板堂 --zhuo end

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

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('goods_activity') . " AS ga " .
                " WHERE ga.act_type = '" . GAT_GROUP_BUY . "' $where";
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT ga.* " .
                "FROM " . $this->dsc->table('goods_activity') . " AS ga " .
                " WHERE ga.act_type = '" . GAT_GROUP_BUY . "' $where " .
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
        foreach ($res as $row) {
            $ext_info = unserialize($row['ext_info']);
            $stat = $this->groupBuyService->getGroupBuyStat($row['act_id'], $ext_info['deposit']);
            $arr = array_merge($row, $stat, $ext_info);

            /* 处理价格阶梯 */
            $price_ladder = $arr['price_ladder'];
            if (!is_array($price_ladder) || empty($price_ladder)) {
                $price_ladder = [['amount' => 0, 'price' => 0]];
            } else {
                foreach ($price_ladder as $key => $amount_price) {
                    $price_ladder[$key]['formated_price'] = price_format($amount_price['price']);
                }
            }

            /* 计算当前价 */
            $cur_price = $price_ladder[0]['price'];    // 初始化
            $cur_amount = $stat['valid_goods'];         // 当前数量
            foreach ($price_ladder as $amount_price) {
                if ($cur_amount >= $amount_price['amount']) {
                    $cur_price = $amount_price['price'];
                } else {
                    break;
                }
            }

            $arr['cur_price'] = $cur_price;

            $status = $this->groupBuyService->getGroupBuyStatus($arr);

            $arr['start_time'] = local_date($GLOBALS['_CFG']['date_format'], $arr['start_time']);
            $arr['end_time'] = local_date($GLOBALS['_CFG']['date_format'], $arr['end_time']);
            $arr['cur_status'] = $GLOBALS['_LANG']['gbs'][$status];

            $arr['user_name'] = $this->merchantCommonService->getShopName($arr['user_id'], 1); //ecmoban模板堂 --zhuo

            $list[] = $arr;
        }
        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 取得某商品的团购活动
     * @param int $goods_id 商品id
     * @return  array
     */
    private function goods_group_buy($goods_id)
    {
        $sql = "SELECT * FROM " . $this->dsc->table('goods_activity') .
            " WHERE goods_id = '$goods_id' " .
            " AND act_type = '" . GAT_GROUP_BUY . "'" .
            " AND start_time <= " . gmtime() .
            " AND end_time >= " . gmtime();

        return $this->db->getRow($sql);
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true)
    {
        $href = 'group_buy.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }

        return ['href' => $href, 'text' => $GLOBALS['_LANG']['group_buy_list'], 'class' => 'icon-reply'];
    }
}
