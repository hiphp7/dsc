<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\Image;
use App\Models\AccountLog;
use App\Models\PayLog;
use App\Models\Suppliers;
use App\Models\SuppliersAccountLog;
use App\Models\SuppliersAccountLogDetail;
use App\Models\Users;
use App\Models\UsersReal;
use App\Repositories\Common\BaseRepository;
use Illuminate\Support\Str;

/**
 * 记录管理员操作日志
 */
class SuppliersAccountController extends InitController
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        load_helper(['order']);

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /* 检查权限 */
        admin_priv('suppliers_account');

        if (!isset($_REQUEST['submit_act'])) {
            if (!isset($_REQUEST['act_type'])) {
                $Loaction = "suppliers_account.php?act=account_manage&act_type=account";
                return dsc_header("Location: $Loaction\n");
            }

            $tab_menu = $this->get_account_tab_menu();
            $this->smarty->assign('tab_menu', $tab_menu);
        }

        $this->smarty->assign('menu_select', array('action' => '03_suppliers', 'current' => '10_account_manage'));//页面位置标记

        /*------------------------------------------------------ */
        //-- 账户管理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'account_manage') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['03_suppliers']);
            $this->smarty->assign('full_page', 1);
            $suppliers_info = get_suppliers_info($adminru['suppliers_id'], array('suppliers_money', 'frozen_money', 'user_id'));
            $users_real = get_users_real($suppliers_info['user_id'], 1);
            $this->smarty->assign('real', $users_real);

            /* 显示模板 */
            if ($_REQUEST['act_type'] == 'account') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_seller_account']); // 当前导航
                /* 短信发送设置 */
                if (intval($GLOBALS['_CFG']['sms_code']) > 0) {
                    $sms_security_code = rand(1000, 9999);

                    session([
                        'sms_security_code' => $sms_security_code
                    ]);

                    $this->smarty->assign('sms_security_code', $sms_security_code);
                    $this->smarty->assign('enabled_sms_signin', 1);
                }

                if (!$users_real) {
                    $this->smarty->assign('form_act', "insert");
                } else {
                    $this->smarty->assign('form_act', "update");
                }

                return $this->smarty->display('suppliers_account.dwt');
            } elseif ($_REQUEST['act_type'] == 'deposit') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_seller_deposit']); // 当前导航

                if (!$users_real) {
                    $link[0] = array('href' => 'suppliers_account.php?act=account_manage&act_type=account', 'text' => $GLOBALS['_LANG']['01_seller_account']);
                    return sys_msg($GLOBALS['_LANG']['account_noll'], 2, $link);
                } elseif ($users_real['review_status'] != 1) {
                    $link[0] = array('href' => 'suppliers_account.php?act=account_manage&act_type=account', 'text' => $GLOBALS['_LANG']['01_seller_account']);
                    return sys_msg($GLOBALS['_LANG']['label_status'], 2, $link);
                }

                $this->smarty->assign('form_act', "deposit_insert");
                $this->smarty->assign('suppliers_info', $suppliers_info);

                return $this->smarty->display('suppliers_deposit.dwt');
            } elseif ($_REQUEST['act_type'] == 'topup') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_top_up']); // 当前导航
                $this->smarty->assign('form_act', "topup_insert");

                $payment_list = available_payment_list(0); //获取支付方式

                if ($payment_list) {
                    foreach ($payment_list as $key => $payment) {
                        //pc端去除ecjia的支付方式
                        if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                            unset($payment_list[$key]);
                            continue;
                        }
                    }
                }

                $this->smarty->assign("pay", $payment_list);
                $this->smarty->assign('suppliers_info', $suppliers_info);

                $user_money = Users::where('user_id', $suppliers_info['user_id'])->value('user_money');
                $user_money = $user_money ? $user_money : 0;

                $this->smarty->assign('user_money', $user_money);

                return $this->smarty->display('suppliers_topup.dwt');
            } elseif ($_REQUEST['act_type'] == 'detail') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_seller_detail']); // 当前导航
                $list = get_suppliers_account_log_detail($adminru['suppliers_id'], array(2, 3, 4, 5));
                $log_list = $list['log_list'];

                $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $this->smarty->assign('log_list', $log_list);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);

                return $this->smarty->display('suppliers_detail.dwt');
            } elseif ($_REQUEST['act_type'] == 'account_log') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_seller_account_log']); // 当前导航
                $list = get_suppliers_account_log_detail($adminru['suppliers_id'], array(1, 4, 5));
                $log_list = $list['log_list'];

                $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $this->smarty->assign('log_list', $log_list);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);

                return $this->smarty->display('suppliers_account_log.dwt');
            } elseif ($_REQUEST['act_type'] == 'frozen_money') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['title_frozen_money']); // 当前导航
                $this->smarty->assign('suppliers_info', $suppliers_info);

                return $this->smarty->display('suppliers_frozen_money.dwt');
            }

            /*------------------------------------------------------ */
            //-- 提交充值信息
            /*------------------------------------------------------ */
            elseif ($_REQUEST['act_type'] == 'topup_pay') {
                load_helper(['payment']);

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_top_up']); // 当前导航
                $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['03_suppliers']);
                $log_id = isset($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;

                $account_log = SuppliersAccountLogDetail::where('log_id', $log_id);
                $account_log = $this->baseRepository->getToArrayFirst($account_log);

                //获取会员id
                $suppliers_info = get_suppliers_info($account_log['suppliers_id'], array('user_id'));
                $account_log['user_id'] = $suppliers_info['user_id'];

                $pay_log = PayLog::where('order_id', $log_id)
                    ->where('order_type', PAY_TOPUP);
                $pay_log = $this->baseRepository->getToArrayFirst($pay_log);

                /* 获取支付信息 */
                $payment_info = payment_info($account_log['pay_id']);

                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);

                //计算支付手续费用
                $payment_info['pay_fee'] = pay_fee($account_log['pay_id'], $account_log['amount'], 0);
                $apply_info['order_amount'] = $account_log['amount'] + $payment_info['pay_fee'];
                $apply_info['order_sn'] = $account_log['apply_sn'];
                $apply_info['user_id'] = $account_log['user_id'];
                $apply_info['surplus_amount'] = $account_log['amount'];
                $apply_info['log_id'] = $pay_log['log_id'];

                if ($payment_info['pay_code'] == 'balance') {
                    //查询出当前用户的剩余余额;
                    $user_money = Users::where('user_id', $account_log['user_id'])->value('user_money');
                    $user_money = $user_money ? $user_money : 0;

                    //如果用户余额足够支付订单;
                    if ($user_money >= $account_log['amount']) {

                        /* 改变商家金额 */
                        Suppliers::where('suppliers_id', $account_log['suppliers_id'])
                            ->increment('suppliers_money', $account_log['amount']);

                        /* 修改申请的支付状态 */
                        SuppliersAccountLogDetail::where('log_id', $log_id)
                            ->update([
                                'is_paid' => 1,
                                'pay_time' => gmtime()
                            ]);

                        load_helper(['clips']);

                        /* 改变会员金额 */
                        Users::where('user_id', $account_log['user_id'])
                            ->decrement('user_money', $account_log['amount']);

                        $change_desc = $GLOBALS['_LANG']['label_seller_topup'] . $account_log['apply_sn'];

                        $user_account_log = array(
                            'user_id' => $account_log['user_id'],
                            'user_money' => "-" . $account_log['amount'],
                            'change_desc' => $change_desc,
                            'change_time' => gmtime(),
                            'change_type' => 1,
                        );

                        AccountLog::insert($user_account_log);

                        //记录支付log
                        PayLog::where('order_id', $log_id)
                            ->where('order_type', PAY_TOPUP)
                            ->update([
                                'is_paid' => 1
                            ]);

                        $change_desc = "【" . session('supply_name') . "】" . $GLOBALS['_LANG']['seller_change_desc'];
                        $log = array(
                            'user_id' => $account_log['suppliers_id'],
                            'user_money' => $account_log['amount'],
                            'change_time' => gmtime(),
                            'change_desc' => $change_desc,
                            'change_type' => 1
                        );
                        SuppliersAccountLog::insert($log);

                        $link[0] = array('href' => 'suppliers_account.php?act=account_manage&act_type=topup', 'text' => $GLOBALS['_LANG']['topup_account_ok']);
                        return sys_msg($GLOBALS['_LANG']['deposit_account_ok'], 0, $link);
                    } else {
                        return sys_msg(lang('suppliers/suppliers_account.running_low'));
                    }
                } else {
                    /* 取得在线支付方式的支付按钮 */
                    $pay_name = Str::studly($payment_info['pay_code']);
                    $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                    if (!is_null($pay_obj)) {
                        $payment_info['pay_button'] = $pay_obj->get_code($apply_info, $payment);
                    }
                }

                $this->smarty->assign('payment', $payment_info);
                $this->smarty->assign('order', $apply_info);
                $this->smarty->assign('amount', $account_log['amount']);
                return $this->smarty->display('suppliers_done.dwt');
            } elseif ($_REQUEST['act_type'] = 'account_log_list') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_seller_detail']);

                $this->smarty->assign('full_page', 1);
                $list = get_suppliers_account_log();

                $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $list['filter']['act_type'] = 'account_log_list';
                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'account_log_list');


                return $this->smarty->display('account_log_list.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- ajax返回列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            if ($_REQUEST['act_type'] == 'detail') {
                $list = get_suppliers_account_log_detail($adminru['suppliers_id'], array(2, 3, 4, 5));
                $fetch = "suppliers_detail";
            } elseif ($_REQUEST['act_type'] == 'account_log') {
                $list = get_suppliers_account_log_detail($adminru['suppliers_id'], array(1, 4, 5));
                $fetch = "suppliers_account_log";
            }

            if ($_REQUEST['act_type'] == 'detail' || $_REQUEST['act_type'] == 'account_log') {
                $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);

                $sort_flag = sort_flag($list['filter']);
                $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

                return make_json_result($this->smarty->fetch($fetch . '.dwt'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
            }

            /*------------------------------------------------------ */
            //-- 账户明细
            /*------------------------------------------------------ */
            elseif ($_REQUEST['act_type'] == 'account_log_list') {
                $list = get_suppliers_account_log();

                $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $list['filter']['act_type'] = 'account_log_list';
                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'account_log_list');


                return make_json_result($this->smarty->fetch('account_log_list.dwt'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
            }
        }

        /*------------------------------------------------------ */
        //-- 插入/更新账户信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'account_edit') {
            $suppliers_info = get_suppliers_info($adminru['suppliers_id'], array('user_id'));

            $is_insert = isset($_REQUEST['form_act']) ? trim($_REQUEST['form_act']) : '';
            $other['real_name'] = isset($_REQUEST['real_name']) ? addslashes(trim($_REQUEST['real_name'])) : '';
            $other['self_num'] = isset($_REQUEST['self_num']) ? addslashes(trim($_REQUEST['self_num'])) : '';
            $other['bank_name'] = isset($_REQUEST['bank_name']) ? addslashes(trim($_REQUEST['bank_name'])) : '';
            $other['bank_card'] = isset($_REQUEST['bank_card']) ? addslashes(trim($_REQUEST['bank_card'])) : '';
            $other['bank_mobile'] = isset($_REQUEST['mobile_phone']) ? addslashes(trim($_REQUEST['mobile_phone'])) : '';
            $other['mobile_code'] = isset($_REQUEST['mobile_code']) ? intval($_REQUEST['mobile_code']) : '';
            $other['user_type'] = 1;
            $other['user_id'] = $suppliers_info['user_id'];

            $link[0] = array('href' => 'suppliers_account.php?act=account_manage&act_type=account', 'text' => $GLOBALS['_LANG']['01_seller_account']);

            if (session()->has('sms_mobile_code') && session('sms_mobile_code') != $other['mobile_code']) {
                return sys_msg($GLOBALS['_LANG']['mobile_code_error'], 0, $link);
            }

            /* 获取表字段 */
            $other = $this->baseRepository->getArrayfilterTable($other, 'users_real');

            if ($is_insert == 'insert') {
                $other['add_time'] = gmtime();
                UsersReal::insert($other);
            } else {
                $other['review_status'] = 0;//编辑后修改状态为未审核
                UsersReal::where('user_id', $suppliers_info['user_id'])
                    ->where('user_type', 1)
                    ->update($other);
            }

            //初始化短信验证码
            session()->forget('sms_mobile_code');

            return sys_msg($is_insert ? $GLOBALS['_LANG']['add_account_ok'] : $GLOBALS['_LANG']['edit_account_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 提交提现信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'deposit_insert') {
            $other['amount'] = isset($_REQUEST['deposit']) ? floatval(trim($_REQUEST['deposit'])) : 0;
            $other['frozen_money'] = $other['amount'];
            $other['seller_note'] = isset($_REQUEST['deposit_note']) ? addslashes(trim($_REQUEST['deposit_note'])) : 0;
            $other['real_id'] = isset($_REQUEST['real_id']) ? intval($_REQUEST['real_id']) : 0;
            $other['add_time'] = gmtime();
            $other['log_type'] = 1; //提现类型
            $other['suppliers_id'] = $adminru['suppliers_id']; //商家ID
            $other['deposit_mode'] = isset($_REQUEST['deposit_mode']) ? intval($_REQUEST['deposit_mode']) : 0; //提现方式

            //商家申请提现记录
            SuppliersAccountLogDetail::insert($other);

            //商家资金变动
            log_suppliers_account_change($other['suppliers_id'], "-" . $other['amount'], $other['amount']);

            //商家资金明细记录
            suppliers_account_log($other['suppliers_id'], "-" . $other['amount'], $other['frozen_money'], "【" . session('supply_name') . "】" . $GLOBALS['_LANG']['02_seller_deposit']);

            $link[0] = array('href' => 'suppliers_account.php?act=account_manage&act_type=account_log', 'text' => $GLOBALS['_LANG']['05_seller_account_log']);
            return sys_msg($GLOBALS['_LANG']['deposit_account_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 提交解冻信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'unfreeze') {
            $other['frozen_money'] = isset($_REQUEST['frozen_money']) ? floatval(trim($_REQUEST['frozen_money'])) : 0;
            $other['seller_note'] = isset($_REQUEST['topup_note']) ? addslashes(trim($_REQUEST['topup_note'])) : '';
            $other['seller_note'] = "【" . session('supply_name') . "】" . $other['seller_note'];
            $other['add_time'] = gmtime();
            $other['log_type'] = 5; //提现类型
            $other['suppliers_id'] = $adminru['suppliers_id']; //商家ID

            SuppliersAccountLogDetail::insert($other);

            log_suppliers_account_change($other['suppliers_id'], 0, "-" . $other['frozen_money']);
            suppliers_account_log($other['suppliers_id'], 0, "-" . $other['frozen_money'], "【" . session('supply_name') . "】" . $GLOBALS['_LANG']['apply_for_account']);

            $link[0] = array('href' => 'suppliers_account.php?act=account_manage&act_type=account_log', 'text' => $GLOBALS['_LANG']['05_seller_account_log']);
            return sys_msg($GLOBALS['_LANG']['deposit_account_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 提交充值信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'topup_insert') {
            load_helper(['clips']);

            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            $nowTime = gmtime();

            $other['amount'] = isset($_REQUEST['topup_account']) ? floatval(trim($_REQUEST['topup_account'])) : 0;
            $other['seller_note'] = isset($_REQUEST['topup_note']) ? addslashes(trim($_REQUEST['topup_note'])) : 0;
            $other['pay_id'] = isset($_REQUEST['pay_id']) ? intval($_REQUEST['pay_id']) : 0;
            $other['add_time'] = $nowTime;
            $other['log_type'] = 3; //充值类型
            $other['suppliers_id'] = $adminru['suppliers_id']; //商家ID

            $certificate_img = isset($_FILES['certificate_img']) ? $_FILES['certificate_img'] : array();

            if ($certificate_img['name']) {
                $other['certificate_img'] = $image->upload_image('', 'suppliers_account', '', 1, $certificate_img['name'], $certificate_img['type'], $certificate_img['tmp_name'], $certificate_img['error'], $certificate_img['size']);  //图片存放地址 -- data/suppliers_account
            }

            $other['apply_sn'] = get_order_sn(); //获取新订单号
            $other['pay_time'] = $nowTime;

            $log_id = SuppliersAccountLogDetail::insertGetId($other);

            /* 插入支付日志 */
            insert_pay_log($log_id, $other['amount'], PAY_TOPUP);

            $Loaction = "suppliers_account.php?act=account_manage&act_type=topup_pay&log_id=" . $log_id;
            return dsc_header("Location: $Loaction\n");
        }

        /*------------------------------------------------------ */
        //-- 取消充值付款
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_pay') {
            $log_id = isset($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;

            SuppliersAccountLogDetail::where('log_id', $log_id)->delete();

            $Loaction = "suppliers_account.php?act=account_manage&act_type=detail";
            return dsc_header("Location: $Loaction\n");
        }
    }

    /**
     * 账户菜单
     */
    private function get_account_tab_menu()
    {
        $account_curr = 0;
        $deposit_curr = 0;
        $topup_curr = 0;
        $detail_curr = 0;
        $account_log_curr = 0;
        $frozen_money_curr = 0;
        $account_log_list_curr = 0;

        $tab_menu = array();
        if ($_REQUEST['act_type'] == 'account') {
            $account_curr = 1;
        } elseif ($_REQUEST['act_type'] == 'deposit') {
            $deposit_curr = 1;
        } elseif ($_REQUEST['act_type'] == 'topup' || $_REQUEST['act_type'] == 'topup_pay') {
            $topup_curr = 1;
        } elseif ($_REQUEST['act_type'] == 'detail') {
            $detail_curr = 1;
        } elseif ($_REQUEST['act_type'] == 'account_log') {
            $account_log_curr = 1;
        } elseif ($_REQUEST['act_type'] == 'frozen_money') {
            $frozen_money_curr = 1;
        } elseif ($_REQUEST['act_type'] == 'account_log_list') {
            $account_log_list_curr = 1;
        }

        $tab_menu[] = array('curr' => $account_curr, 'text' => $GLOBALS['_LANG']['01_seller_account'], 'href' => 'suppliers_account.php?act=account_manage&act_type=account');
        $tab_menu[] = array('curr' => $deposit_curr, 'text' => $GLOBALS['_LANG']['02_seller_deposit'], 'href' => 'suppliers_account.php?act=account_manage&act_type=deposit');
        $tab_menu[] = array('curr' => $topup_curr, 'text' => $GLOBALS['_LANG']['03_top_up'], 'href' => 'suppliers_account.php?act=account_manage&act_type=topup');
        $tab_menu[] = array('curr' => $detail_curr, 'text' => $GLOBALS['_LANG']['04_seller_detail'], 'href' => 'suppliers_account.php?act=account_manage&act_type=detail');
        $tab_menu[] = array('curr' => $account_log_curr, 'text' => $GLOBALS['_LANG']['05_seller_account_log'], 'href' => 'suppliers_account.php?act=account_manage&act_type=account_log');
        $tab_menu[] = array('curr' => $frozen_money_curr, 'text' => $GLOBALS['_LANG']['title_frozen_money'], 'href' => 'suppliers_account.php?act=account_manage&act_type=frozen_money');
        $tab_menu[] = array('curr' => $account_log_list_curr, 'text' => $GLOBALS['_LANG']['fund_details'], 'href' => 'suppliers_account.php?act=account_manage&act_type=account_log_list');

        return $tab_menu;
    }
}
