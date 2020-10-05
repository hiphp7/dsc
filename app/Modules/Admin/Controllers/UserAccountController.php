<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;

/**
 * 会员帐目管理(包括预付款，余额)
 */
class UserAccountController extends InitController
{
    protected $commonRepository;
    protected $dscRepository;

    public function __construct(
        CommonRepository $commonRepository,
        DscRepository $dscRepository
    )
    {
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 会员余额记录列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限判断 */
            admin_priv('surplus_manage');

            /* 指定会员的ID为查询条件 */
            $user_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $process_type = isset($_REQUEST['process_type']) && !empty($_REQUEST['process_type']) ? intval($_REQUEST['process_type']) : 0;
            $is_paid = !empty($_REQUEST['is_paid']) ? intval($_REQUEST['is_paid']) : 0;

            /* 获得支付方式列表 */
            $payment = [];
            $sql = "SELECT pay_id, pay_name FROM " . $this->dsc->table('payment') .
                " WHERE enabled = 1 AND pay_code != 'cod' AND pay_code != 'balance' ORDER BY pay_id";
            $res = $this->db->query($sql);
            foreach ($res as $row) {
                $payment[$row['pay_name']] = $row['pay_name'];
            }

            /* 模板赋值 */
            $this->smarty->assign('process_type_' . $process_type, 'selected="selected"');

            if (isset($_REQUEST['is_paid'])) {
                $this->smarty->assign('is_paid_' . $is_paid, 'selected="selected"');
            }
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_user_account']);
            $this->smarty->assign('id', $user_id);
            $this->smarty->assign('payment_list', $payment);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['surplus_add'], 'href' => 'user_account.php?act=add&process_type=' . $process_type]);

            $list = $this->account_list();

            $this->smarty->assign('list', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_add_time', '<img src="' . __TPL__ . '/images/sort_desc.gif">');


            return $this->smarty->display('user_account_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑会员余额页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('surplus_manage'); //权限判断

            $process_type = isset($_REQUEST['process_type']) && !empty($_REQUEST['process_type']) ? intval($_REQUEST['process_type']) : 0;

            $ur_here = ($_REQUEST['act'] == 'add') ? $GLOBALS['_LANG']['surplus_add'] : $GLOBALS['_LANG']['surplus_edit'];
            $form_act = ($_REQUEST['act'] == 'add') ? 'insert' : 'update';
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            /* 获得支付方式列表, 不包括“货到付款” */
            $user_account = [];
            $payment = [];
            $sql = "SELECT pay_id, pay_name FROM " . $this->dsc->table('payment') .
                " WHERE enabled = 1 AND pay_code != 'cod' ORDER BY pay_id";
            $res = $this->db->query($sql);

            $idx = 0;
            foreach ($res as $row) {
                $row['pay_name'] = strip_tags($row['pay_name']);
                $payment[$idx]['pay_id'] = $row['pay_id'];
                $payment[$idx]['pay_name'] = $row['pay_name'];

                $idx++;
            }

            if ($_REQUEST['act'] == 'edit') {
                /* 取得余额信息 */
                $user_account = $this->db->getRow("SELECT * FROM " . $this->dsc->table('user_account') . " WHERE id = '$id'");

                // 如果是负数，去掉前面的符号
                $user_account['amount'] = str_replace('-', '', $user_account['amount']);

                /* 取得会员名称 */
                $sql = "SELECT user_name FROM " . $this->dsc->table('users') . " WHERE user_id = '$user_account[user_id]'";
                $user_name = $this->db->getOne($sql);
            } else {
                $user_name = '';
            }

            if ($user_account && $user_account['pay_id'] == 0 && $user_account['payment']) {
                $sql = 'SELECT pay_id FROM ' . $this->dsc->table('payment') .
                    " WHERE pay_name = '" . $user_account['payment'] . "' AND enabled = 1";
                $pay_id = $this->db->getOne($sql, true);

                $user_account['pay_id'] = $pay_id;
            }

            if ($_REQUEST['act'] == 'add') {
                $user_account['process_type'] = $process_type;
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $user_name = $this->dscRepository->stringToStar($user_name);
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $ur_here);
            $this->smarty->assign('form_act', $form_act);
            $this->smarty->assign('payment_list', $payment);
            $this->smarty->assign('action', $_REQUEST['act']);
            $this->smarty->assign('user_surplus', $user_account);
            $this->smarty->assign('user_name', $user_name);

            if ($_REQUEST['act'] == 'add') {
                $href = 'user_account.php?act=list';
            } else {
                $href = 'user_account.php?act=list&' . list_link_postfix();
            }
            $this->smarty->assign('action_link', ['href' => $href, 'text' => $GLOBALS['_LANG']['09_user_account']]);


            return $this->smarty->display('user_account_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑会员余额的处理部分
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('surplus_manage');

            /* 初始化变量 */
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $is_paid = !empty($_POST['is_paid']) ? intval($_POST['is_paid']) : 0;
            $amount = !empty($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $process_type = !empty($_POST['process_type']) ? intval($_POST['process_type']) : 0;
            $user_name = !empty($_POST['user_id']) ? addslashes(trim($_POST['user_id'])) : '';
            $admin_note = !empty($_POST['admin_note']) ? trim($_POST['admin_note']) : '';
            $user_note = !empty($_POST['user_note']) ? trim($_POST['user_note']) : '';
            $pay_id = !empty($_POST['pay_id']) ? trim($_POST['pay_id']) : '';

            $user_id = $this->db->getOne("SELECT user_id FROM " . $this->dsc->table('users') . " WHERE user_name = '$user_name'");

            /* 此会员是否存在 */
            if ($user_id == 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                return sys_msg($GLOBALS['_LANG']['username_not_exist'], 0, $link);
            }

            /* 退款，检查余额是否足够 */
            if ($process_type == 1) {
                $user_surplus = $this->get_user_surplus($user_id);

                /* 如果扣除的余额多于此会员拥有的余额，提示 */
                if ($amount > $user_surplus['user_money']) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['surplus_amount_error'], 0, $link);
                }
            }

            $sql = 'SELECT pay_name FROM ' . $this->dsc->table('payment') .
                " WHERE pay_id = '$pay_id'";
            $payment = $this->db->getOne($sql, true);


            if ($_REQUEST['act'] == 'insert') {
                /* 入库的操作 */
                if ($process_type == 1) {
                    $amount = (-1) * $amount;
                }

                $other = [
                    'user_id' => $user_id,
                    'admin_user' => session('admin_name'),
                    'amount' => $amount,
                    'add_time' => gmtime(),
                    'paid_time' => gmtime(),
                    'admin_note' => $admin_note,
                    'user_note' => $user_note,
                    'process_type' => $process_type,
                    'payment' => $payment,
                    'pay_id' => $pay_id,
                    'is_paid' => $is_paid
                ];

                $this->db->autoExecute($this->dsc->table('user_account'), $other, 'INSERT');
                $id = $this->db->insert_id();
                if ($process_type == 1) {
                    // 余额变动
                    $change_desc = $amount > 0 ? $GLOBALS['_LANG']['surplus_type_0'] : $GLOBALS['_LANG']['surplus_type_1'];
                    $change_type = $amount > 0 ? ACT_SAVING : ACT_DRAWING;
                    log_account_change($user_id, $amount, -$amount, 0, 0, $change_desc, $change_type);
                }
            } else {

                /* 更新数据表 */
                $sql = "UPDATE " . $this->dsc->table('user_account') . " SET " .
                    "admin_note   = '$admin_note', " .
                    "user_note    = '$user_note', " .
                    "payment      = '$payment' " .
                    "WHERE id      = '$id'";
                $this->db->query($sql);
            }

            // 更新会员余额数量
            if ($is_paid == 1) {
                $change_desc = $amount > 0 ? $GLOBALS['_LANG']['surplus_type_0'] : $GLOBALS['_LANG']['surplus_type_1'];
                $change_type = $amount > 0 ? ACT_SAVING : ACT_DRAWING;
                log_account_change($user_id, $amount, 0, 0, 0, $change_desc, $change_type);
            }

            //如果是预付款并且未确认，向pay_log插入一条记录
            if ($process_type == 0 && $is_paid == 0) {
                load_helper('order');

                /* 取支付方式信息 */
                $payment_info = [];
                $payment_info = $this->db->getRow('SELECT * FROM ' . $this->dsc->table('payment') .
                    " WHERE pay_name = '$payment' AND enabled = '1'");
                //计算支付手续费用
                $pay_fee = pay_fee($payment_info['pay_id'], $amount, 0);
                $total_fee = $pay_fee + $amount;

                /* 插入 pay_log */
                $sql = 'INSERT INTO ' . $this->dsc->table('pay_log') . " (order_id, order_amount, order_type, is_paid)" .
                    " VALUES ('$id', '$total_fee', '" . PAY_SURPLUS . "', 0)";
                $this->db->query($sql);
            }

            /* 记录管理员操作 */
            if ($_REQUEST['act'] == 'update') {
                admin_log($user_name, 'edit', 'user_surplus');
            } else {
                admin_log($user_name, 'add', 'user_surplus');
            }

            /* 提示信息 */
            if ($_REQUEST['act'] == 'insert') {
                $href = 'user_account.php?act=list';
            } else {
                $href = 'user_account.php?act=list&' . list_link_postfix();
            }
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = $href;

            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'user_account.php?act=add';

            return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 审核会员余额页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check') {
            /* 检查权限 */
            admin_priv('surplus_manage');

            /* 初始化 */
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            /* 如果参数不合法，返回 */
            if ($id == 0) {
                return dsc_header("Location: user_account.php?act=list\n");
            }

            /* 查询当前的预付款信息 */
            $account = $this->db->getRow("SELECT * FROM " . $this->dsc->table('user_account') . " WHERE id = '$id'");
            $account['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $account['add_time']);

            $account['complaint_imges'] = $account['complaint_imges'] ? get_image_path($account['complaint_imges']) : '';

            if ($account['process_type'] == 1 || $account['payment'] == lang('admin/user_account.remittance')) {
                $sql = "SELECT real_name, bank_card, self_num, bank_name  FROM " . $this->dsc->table('users_real') . " WHERE user_id = '$account[user_id]'";
                $account['real'] = $this->db->getRow($sql);

                $account['processType'] = 1;
            }

            //by wang获得用户账目的扩展信息
            $account['fields'] = $this->db->getRow("SELECT * FROM " . $this->dsc->table('user_account_fields') . " WHERE user_id = '$account[user_id]' and account_id='$id'");

            //余额类型:预付款，退款申请，购买商品，取消订单
            if ($account['process_type'] == 0) {
                $process_type = $GLOBALS['_LANG']['surplus_type_0'];
            } elseif ($account['process_type'] == 1) {
                $process_type = $GLOBALS['_LANG']['surplus_type_1'];
            } elseif ($account['process_type'] == 2) {
                $process_type = $GLOBALS['_LANG']['surplus_type_2'];
            } else {
                $process_type = $GLOBALS['_LANG']['surplus_type_3'];
            }

            $sql = "SELECT user_name, mobile_phone FROM " . $this->dsc->table('users') . " WHERE user_id = '" . $account['user_id'] . "' LIMIT 1";
            $user_info = $this->db->getRow($sql);

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && $user_info) {
                $user_info['user_name'] = $this->dscRepository->stringToStar($user_info['user_name']);
                $user_info['mobile_phone'] = $this->dscRepository->stringToStar($user_info['mobile_phone']);
            }

            $sql = "SELECT * FROM " . $this->dsc->table('users_real') . " WHERE user_id = '" . $account['user_id'] . "' LIMIT 1";
            $users_real = $this->db->getRow($sql);

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && $users_real) {
                $users_real['bank_mobile'] = $this->dscRepository->stringToStar($users_real['bank_mobile']);
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['check']);
            $account['user_note'] = htmlspecialchars($account['user_note']);
            $this->smarty->assign('surplus', $account);
            $this->smarty->assign('users_real', $users_real);
            $this->smarty->assign('process_type', $process_type);
            $this->smarty->assign('user_info', $user_info);
            $this->smarty->assign('id', $id);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['09_user_account'],
                'href' => 'user_account.php?act=list&' . list_link_postfix()]);

            /* 页面显示 */

            return $this->smarty->display('user_account_check.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新会员余额的状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'action') {
            /* 检查权限 */
            admin_priv('surplus_manage');

            /* 初始化 */
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $is_paid = isset($_POST['is_paid']) ? intval($_POST['is_paid']) : 0;
            $admin_note = isset($_POST['admin_note']) ? trim($_POST['admin_note']) : '';

            /* 如果参数不合法，返回 */
            if ($id == 0 || empty($admin_note)) {
                return dsc_header("Location: user_account.php?act=list\n");
            }

            /* 查询当前的预付款信息 */
            $account = [];
            $account = $this->db->getRow("SELECT * FROM " . $this->dsc->table('user_account') . " WHERE id = '$id'");
            $amount = $account['amount'];

            $user_surplus = $this->get_user_surplus($account['user_id']);

            //如果状态为未确认
            if ($account['is_paid'] == 0) {
                //获取申请会员信息
                $user_info = [];
                if (intval($account['user_id']) > 0) {
                    $sql = "SELECT mobile_phone, email,user_name FROM" . $this->dsc->table('users') . " WHERE user_id = '" . $account['user_id'] . "' LIMIT 1";
                    $user_info = $this->db->getRow($sql);
                }

                //短信接口参数
                $smsParams = [
                    'user_name' => $user_info['user_name'],
                    'username' => $user_info['user_name'],
                    'user_money' => $user_surplus['user_money'] + $amount,
                    'usermoney' => $user_surplus['user_money'] + $amount,
                    'op_time' => local_date('Y-m-d H:i:s', gmtime()),
                    'optime' => local_date('Y-m-d H:i:s', gmtime()),
                    'add_time' => local_date('Y-m-d H:i:s', $account['add_time']),
                    'addtime' => local_date('Y-m-d H:i:s', $account['add_time']),
                    'examine' => "通过",
                    'fmt_amount' => $amount,
                    'fmtamount' => $amount,
                    'mobile_phone' => $user_info['mobile_phone'] ? $user_info['mobile_phone'] : '',
                    'mobilephone' => $user_info['mobile_phone'] ? $user_info['mobile_phone'] : ''
                ];
                //如果是退款申请, 并且已完成,更新此条记录,扣除相应的余额
                if ($is_paid == 1 && $account['process_type'] == 1) {
                    $fmt_amount = str_replace('-', '', $amount);

                    //如果扣除的余额多于此会员拥有的余额，提示
                    if ($fmt_amount > $user_surplus['frozen_money']) {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                        return sys_msg($GLOBALS['_LANG']['surplus_frozen_error'], 0, $link);
                    }

                    $this->update_user_account($id, $amount, $admin_note, $is_paid);

                    //更新会员余额数量
                    log_account_change($account['user_id'], 0, $amount + $account['deposit_fee'], 0, 0, "【" . $GLOBALS['_LANG']['surplus_type_1'] . "-" . $GLOBALS['_LANG']['offline_transfer'] . "】" . $admin_note, ACT_DRAWING);

                    $order_note = !empty($account['admin_note']) ? explode("：", $account['admin_note']) : [];

                    if ($order_note && isset($order_note[1]) && $order_note[1]) {
                        load_helper('order');

                        $order = order_info(0, $order_note[1]);

                        if ($order['ru_id']) {
                            $adminru = get_admin_ru_id();

                            $change_desc = $GLOBALS['_LANG']['operator'] . $adminru['user_name'] . $GLOBALS['_LANG']['operator_two'];
                            $log = [
                                'user_id' => $order['ru_id'],
                                'user_money' => $amount,
                                'change_time' => gmtime(),
                                'change_desc' => $change_desc,
                                'change_type' => 2
                            ];

                            $this->db->autoExecute($this->dsc->table('merchants_account_log'), $log, 'INSERT');

                            $sql = "UPDATE " . $this->dsc->table('seller_shopinfo') . " SET seller_money = seller_money + '" . $log['user_money'] . "' WHERE ru_id = '" . $order['ru_id'] . "'";
                            $this->db->query($sql);
                        }
                    }

                    /* 如果需要，发短信 */
                    $smsParams['user_money'] = $user_surplus['user_money'] + $amount;
                    $smsParams['usermoney'] = $user_surplus['user_money'] + $amount;
                    $smsParams['process_type'] = $GLOBALS['_LANG']['surplus_type_1'];
                    $smsParams['processtype'] = $GLOBALS['_LANG']['surplus_type_1'];

                    if (isset($GLOBALS['_CFG']['user_account_code']) && $GLOBALS['_CFG']['user_account_code'] == '1' && $user_info['mobile_phone'] != '') {
                        $this->commonRepository->smsSend($user_info['mobile_phone'], $smsParams, 'user_account_code', false);
                    }

                    if ($user_info['email'] != '') {
                        $tpl = get_mail_template('user_account_code');

                        $this->smarty->assign('smsParams', $smsParams);
                        $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                        $this->commonRepository->sendEmail($GLOBALS['_CFG']['shop_name'], $user_info['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                    }
                } elseif ($is_paid == 1 && $account['process_type'] == 0) {
                    //如果是预付款，并且已完成, 更新此条记录，增加相应的余额
                    $this->update_user_account($id, $amount, $admin_note, $is_paid);

                    //更新会员余额数量
                    log_account_change($account['user_id'], $amount, 0, 0, 0, "【" . $GLOBALS['_LANG']['user_name'] . $GLOBALS['_LANG']['surplus_type_0'] . "】" . $admin_note, ACT_SAVING);

                    /* 如果需要，发短信 */
                    //获取完成后的会员余额
                    $smsParams['user_money'] = $user_surplus['user_money'] + $amount;
                    $smsParams['usermoney'] = $user_surplus['user_money'] + $amount;
                    $smsParams['process_type'] = $GLOBALS['_LANG']['surplus_type_0'];
                    $smsParams['processtype'] = $GLOBALS['_LANG']['surplus_type_0'];

                    if (isset($GLOBALS['_CFG']['user_account_code']) && $GLOBALS['_CFG']['user_account_code'] == '1' && $user_info['mobile_phone'] != '') { //添加条件 by wu
                        $this->commonRepository->smsSend($user_info['mobile_phone'], $smsParams, 'user_account_code', false);
                    }

                    if ($user_info['email'] != '') {
                        $tpl = get_mail_template('user_account_code');

                        $this->smarty->assign('smsParams', $smsParams);
                        $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                        $this->commonRepository->sendEmail($GLOBALS['_CFG']['shop_name'], $user_info['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                    }
                } elseif ($is_paid == 0 || $is_paid == 2) {
                    if ($is_paid == 2) {
                        $set = "is_paid = 2";
                    } else {
                        $set = "is_paid = 0";
                    }

                    /* 否则更新信息 */
                    $sql = "UPDATE " . $this->dsc->table('user_account') . " SET " .
                        "admin_user    = '" . session('admin_name') . "', " .
                        "admin_note    = '$admin_note', " .
                        $set .
                        " WHERE id = '$id'";
                    $this->db->query($sql);
                }

                /* 记录管理员日志 */
                admin_log('(' . addslashes($GLOBALS['_LANG']['check']) . ')' . $admin_note, 'edit', 'user_surplus');

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'user_account.php?act=list&' . list_link_postfix();

                return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                return sys_msg($GLOBALS['_LANG']['attradd_failed'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- ajax帐户信息列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->account_list();
            $this->smarty->assign('list', $list['list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $this->smarty->assign('sort_add_time', '<img src="' . __TPL__ . '/images/sort_' . strtolower($list['filter']['sort_order']) . '.gif">');

            return make_json_result($this->smarty->fetch('user_account_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- ajax删除一条信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('surplus_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = @intval($_REQUEST['id']);
            $sql = "SELECT u.user_name FROM " . $this->dsc->table('users') . " AS u, " .
                $this->dsc->table('user_account') . " AS ua " .
                " WHERE u.user_id = ua.user_id AND ua.id = '$id' ";
            $user_name = $this->db->getOne($sql);
            $sql = "DELETE FROM " . $this->dsc->table('user_account') . " WHERE id = '$id'";
            if ($this->db->query($sql, 'SILENT')) {
                admin_log(addslashes($user_name), 'remove', 'user_surplus');
                $url = 'user_account.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
                return dsc_header("Location: $url\n");
            } else {
                return make_json_error($this->db->error());
            }
        }
        /*------------------------------------------------------ */
        //-- 会员批量审核
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('surplus_manage');
            $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : '';
            $admin_note = "";
            if ($checkboxes) {
                foreach ($checkboxes as $id) {
                    /* 初始化 */
                    $is_paid = 1;

                    /* 查询当前的预付款信息 */
                    $account = [];
                    $account = $this->db->getRow("SELECT * FROM " . $this->dsc->table('user_account') . " WHERE id = '$id'");

                    $user_surplus = $this->get_user_surplus($account['user_id']);

                    $amount = $account['amount'];
                    //如果状态为未确认
                    if ($account['is_paid'] == 0) {
                        //获取申请会员信息
                        $sql = "SELECT mobile_phone, email,user_name FROM" . $this->dsc->table('users') . " WHERE user_id = '" . $account['user_id'] . "'";
                        $user_info = $this->db->getRow($sql);

                        //短信接口参数
                        $smsParams = [
                            'user_name' => $user_info['user_name'],
                            'username' => $user_info['user_name'],
                            'user_money' => $user_surplus['user_money'],
                            'usermoney' => $user_surplus['user_money'],
                            'op_time' => local_date('Y-m-d H:i:s', gmtime()),
                            'optime' => local_date('Y-m-d H:i:s', gmtime()),
                            'add_time' => local_date('Y-m-d H:i:s', $account['add_time']),
                            'addtime' => local_date('Y-m-d H:i:s', $account['add_time']),
                            'examine' => $GLOBALS['_LANG']['through'],
                            'fmt_amount' => $amount,
                            'fmtamount' => $amount,
                            'mobile_phone' => $user_info['mobile_phone'] ? $user_info['mobile_phone'] : '',
                            'mobilephone' => $user_info['mobile_phone'] ? $user_info['mobile_phone'] : ''
                        ];
                        //如果是退款申请, 并且已完成,更新此条记录,扣除相应的余额
                        if ($account['process_type'] == '1') {
                            $fmt_amount = str_replace('-', '', $amount);

                            //如果扣除的余额多于此会员拥有的余额，提示
                            if ($fmt_amount > $user_surplus['frozen_money']) {
                                continue;
                            }

                            $this->update_user_account($id, $amount, $admin_note, $is_paid);

                            //更新会员余额数量
                            log_account_change($account['user_id'], 0, $amount + $account['deposit_fee'], 0, 0, $GLOBALS['_LANG']['surplus_type_1'], ACT_DRAWING);
                            /* 如果需要，发短信 */
                            //获取完成后的会员余额
                            $smsParams['user_money'] = $user_surplus['user_money'];
                            $smsParams['usermoney'] = $user_surplus['user_money'];
                            $smsParams['process_type'] = $GLOBALS['_LANG']['surplus_type_1'];
                            $smsParams['processtype'] = $GLOBALS['_LANG']['surplus_type_1'];

                            if (isset($GLOBALS['_CFG']['user_account_code']) && $GLOBALS['_CFG']['user_account_code'] == '1' && $user_info['mobile_phone'] != '') {
                                $this->commonRepository->smsSend($user_info['mobile_phone'], $smsParams, 'user_account_code', false);
                            }
                            if ($user_info['email'] != '') {
                                $tpl = get_mail_template('user_account_code');

                                $this->smarty->assign('smsParams', $smsParams);
                                $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                                $this->commonRepository->sendEmail($GLOBALS['_CFG']['shop_name'], $user_info['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                            }
                        } elseif ($account['process_type'] == '0') {
                            //如果是预付款，并且已完成, 更新此条记录，增加相应的余额
                            $this->update_user_account($id, $amount, $admin_note, $is_paid);

                            //更新会员余额数量
                            log_account_change($account['user_id'], $amount, 0, 0, 0, $GLOBALS['_LANG']['surplus_type_0'], ACT_SAVING);

                            /* 如果需要，发短信 */
                            //获取完成后的会员余额
                            $smsParams['user_money'] = $user_surplus['user_money'];
                            $smsParams['usermoney'] = $user_surplus['user_money'];
                            $smsParams['process_type'] = $GLOBALS['_LANG']['surplus_type_0'];
                            $smsParams['processtype'] = $GLOBALS['_LANG']['surplus_type_0'];

                            if (isset($GLOBALS['_CFG']['user_account_code']) && $GLOBALS['_CFG']['user_account_code'] == '1' && $user_info['mobile_phone'] != '') {
                                $this->commonRepository->smsSend($user_info['mobile_phone'], $smsParams, 'user_account_code');
                            }

                            if ($user_info['email'] != '') {
                                $tpl = get_mail_template('user_account_code');

                                $this->smarty->assign('smsParams', $smsParams);
                                $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                                $this->commonRepository->sendEmail($GLOBALS['_CFG']['shop_name'], $user_info['email'], $tpl['template_subject'], $content, $tpl['is_html']);
                            }
                        }

                        /* 记录管理员日志 */
                        admin_log('(' . addslashes($GLOBALS['_LANG']['check']) . ')' . $admin_note, 'edit', 'user_surplus');
                    }
                }

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'user_account.php?act=list&' . list_link_postfix();

                return sys_msg($GLOBALS['_LANG']['attradd_succed'], 0, $link);
            } else {
                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'user_account.php?act=list&' . list_link_postfix();

                return sys_msg($GLOBALS['_LANG']['please_take_handle'], 0, $link);
            }
        }
    }
    /*------------------------------------------------------ */
    //-- 会员余额函数部分
    /*------------------------------------------------------ */
    /**
     * 查询会员余额的数量
     * @access  public
     * @param int $user_id 会员ID
     * @return  int
     */
    private function get_user_surplus($user_id)
    {
        $sql = "SELECT user_money, frozen_money FROM " . $this->dsc->table('users') .
            " WHERE user_id = '$user_id'";
        $res = $this->db->getRow($sql);

        if (!$res) {
            $res['user_money'] = 0;
            $res['frozen_money'] = 0;
        }

        return $res;
    }


    /**
     * 更新会员账目明细
     *
     * @access  public
     * @param array $id 帐目ID
     * @param array $admin_note 管理员描述
     * @param array $amount 操作的金额
     * @param array $is_paid 是否已完成
     *
     * @return  int
     */
    private function update_user_account($id, $amount, $admin_note, $is_paid)
    {
        $sql = "UPDATE " . $this->dsc->table('user_account') . " SET " .
            "admin_user  = '" . session('admin_name') . "', " .
            "amount      = '$amount', " .
            "paid_time   = '" . gmtime() . "', " .
            "admin_note  = '$admin_note', " .
            "is_paid     = '$is_paid' WHERE id = '$id'";
        return $this->db->query($sql);
    }

    /**
     * 账户列表
     *
     * @return array
     */
    private function account_list()
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤列表 */
            $filter['user_id'] = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['process_type'] = isset($_REQUEST['process_type']) ? intval($_REQUEST['process_type']) : 0;
            $filter['payment'] = empty($_REQUEST['payment']) ? '' : trim($_REQUEST['payment']);
            $filter['is_paid'] = isset($_REQUEST['is_paid']) ? intval($_REQUEST['is_paid']) : -1;
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : local_strtotime($_REQUEST['start_date']);
            $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (local_strtotime($_REQUEST['end_date']) + 86400);
            $filter['add_start_date'] = empty($_REQUEST['add_start_date']) ? '' : (strpos($_REQUEST['add_start_date'], '-') > 0 ? local_strtotime($_REQUEST['add_start_date']) : $_REQUEST['add_start_date']);
            $filter['add_end_date'] = empty($_REQUEST['add_end_date']) ? '' : (strpos($_REQUEST['add_end_date'], '-') > 0 ? local_strtotime($_REQUEST['add_end_date']) : $_REQUEST['add_end_date']);

            $where = " WHERE 1 ";
            if ($filter['user_id'] > 0) {
                $where .= " AND ua.user_id = '$filter[user_id]' ";
            }
            if ($filter['process_type'] != -1) {
                $where .= " AND ua.process_type = '$filter[process_type]' ";
            } else {
                $where .= " AND ua.process_type " . db_create_in([SURPLUS_SAVE, SURPLUS_RETURN]);
            }
            if ($filter['payment']) {
                $where .= " AND ua.payment = '$filter[payment]' ";
            }
            if ($filter['is_paid'] != -1) {
                $where .= " AND ua.is_paid = '$filter[is_paid]' ";
            }

            if ($filter['add_start_date']) {
                $where .= " AND ua.add_time >= '$filter[add_start_date]'";
            }
            if ($filter['add_end_date']) {
                $where .= " AND ua.add_time <= '$filter[add_end_date]'";
            }

            $leftJoin = '';
            if ($filter['keywords']) {
                $where .= " AND ((u.user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%')" .
                    " OR (u.email LIKE '%" . mysql_like_quote($filter['keywords']) . "%') " .
                    " OR (u.mobile_phone LIKE '%" . mysql_like_quote($filter['keywords']) . "%')) ";
                $leftJoin = "LEFT JOIN" . $GLOBALS['dsc']->table('users') . ' AS u ON ua.user_id = u.user_id';
            }
            /* 　时间过滤　 */
            if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
                $where .= "AND paid_time >= " . $filter['start_date'] . " AND paid_time < '" . $filter['end_date'] . "'";
            }

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('user_account') . " AS ua " . $leftJoin . $where;
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询数据 */
            $sql = 'SELECT ua.*, u.user_name FROM ' .
                $this->dsc->table('user_account') . ' AS ua LEFT JOIN ' .
                $this->dsc->table('users') . ' AS u ON ua.user_id = u.user_id' .
                $where . "ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] . " LIMIT " . $filter['start'] . ", " . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $list = $this->db->getAll($sql);
        foreach ($list as $key => $value) {
            $list[$key]['surplus_amount'] = price_format(abs($value['amount']), false);
            $list[$key]['add_date'] = local_date($GLOBALS['_CFG']['time_format'], $value['add_time']);
            $list[$key]['process_type_name'] = $GLOBALS['_LANG']['surplus_type_' . $value['process_type']];

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $list[$key]['user_name'] = $this->dscRepository->stringToStar($value['user_name']);
            }
        }
        $arr = ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
