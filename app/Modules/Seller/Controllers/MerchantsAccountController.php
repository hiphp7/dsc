<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Image;
use App\Models\UsersReal;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Store\StoreService;
use Illuminate\Support\Str;

/**
 * 管理中心 店铺认证结算管理
 */
class MerchantsAccountController extends InitController
{
    protected $storeService;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        StoreService $storeService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->storeService = $storeService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /* 检查权限 */
        admin_priv('seller_account');

        $act = request()->input('act', 'account');
        $act_type = request()->input('act_type');

        $submit_act = request()->input('submit_act');

        if (!isset($submit_act) && $act != 'checkorder') {
            $tab_menu = $this->get_account_tab_menu($act_type);
            $this->smarty->assign('tab_menu', $tab_menu);
        }

        $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '10_account_manage']);

        /*------------------------------------------------------ */
        //-- 账户管理
        /*------------------------------------------------------ */
        if ($act == 'account_manage') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['17_merchants']);
            $this->smarty->assign('full_page', 1);
            $users_real = get_users_real($adminru['ru_id'], 1);

            if ($users_real) {
                $users_real['front_of_id_card'] = get_image_path($users_real['front_of_id_card']);
                $users_real['reverse_of_id_card'] = get_image_path($users_real['reverse_of_id_card']);
            }

            $this->smarty->assign('real', $users_real);

            /* 显示模板 */
            if ($act_type == 'account') {
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

                return $this->smarty->display('merchants_account.dwt');
            } elseif ($act_type == 'deposit') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_seller_deposit']); // 当前导航

                if (!$users_real) {
                    $link[0] = ['href' => 'merchants_account.php?act=account_manage&act_type=account', 'text' => $GLOBALS['_LANG']['01_seller_account']];
                    return sys_msg($GLOBALS['_LANG']['account_noll'], 2, $link);
                } elseif ($users_real['review_status'] != 1) {
                    $link[0] = ['href' => 'merchants_account.php?act=account_manage&act_type=account', 'text' => $GLOBALS['_LANG']['01_seller_account']];
                    return sys_msg($GLOBALS['_LANG']['label_status'], 2, $link);
                }

                $this->smarty->assign('form_act', "deposit_insert");

                $seller_shopinfo = $this->storeService->getShopInfo($adminru['ru_id']);
                $this->smarty->assign('seller_shopinfo', $seller_shopinfo);

                return $this->smarty->display('merchants_deposit.dwt');
            } elseif ($act_type == 'topup') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_top_up']); // 当前导航
                $this->smarty->assign('form_act', "topup_insert");

                $payment_list = available_payment_list(0); //获取支付方式

                foreach ($payment_list as $key => $payment) {
                    //ecmoban模板堂 --will start
                    //pc端去除ecjia的支付方式
                    if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                        unset($payment_list[$key]);
                        continue;
                    }
                    //ecmoban模板堂 --will end
                }

                $this->smarty->assign("pay", $payment_list);

                $seller_shopinfo = $this->storeService->getShopInfo($adminru['ru_id']);
                $this->smarty->assign('seller_shopinfo', $seller_shopinfo);

                $user_money = $this->db->getOne("SELECT user_money FROM " . $this->dsc->table('users') . " WHERE user_id='" . $adminru['ru_id'] . "'");
                $this->smarty->assign('user_money', $user_money);

                return $this->smarty->display('merchants_topup.dwt');
            } elseif ($act_type == 'detail') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_seller_detail']); // 当前导航
                $list = get_account_log_list($adminru['ru_id'], [2, 3, 4, 5]);
                $log_list = $list['log_list'];

                // 分页
                $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $this->smarty->assign('log_list', $log_list);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);

                return $this->smarty->display('merchants_detail.dwt');
            } elseif ($act_type == 'account_log') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_seller_account_log']); // 当前导航
                $list = get_account_log_list($adminru['ru_id'], [1, 4, 5]);
                $log_list = $list['log_list'];
                //分页
                $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $this->smarty->assign('log_list', $log_list);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);

                return $this->smarty->display('merchants_account_log.dwt');
            } elseif ($act_type == 'frozen_money') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['title_frozen_money']); // 当前导航

                $seller_shopinfo = $this->storeService->getShopInfo($adminru['ru_id']);
                $this->smarty->assign('seller_shopinfo', $seller_shopinfo);

                return $this->smarty->display('merchants_frozen_money.dwt');
            }
            /*------------------------------------------------------ */
            //-- 提交充值信息
            /*------------------------------------------------------ */
            elseif ($act_type == 'topup_pay') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_top_up']); // 当前导航

                load_helper('payment');
                $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['17_merchants']);
                $log_id = isset($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;

                $sql = "SELECT * FROM " . $this->dsc->table('seller_account_log') . " WHERE log_id = '$log_id' LIMIT 1";
                $account_log = $this->db->getRow($sql);

                $sql = "SELECT * FROM " . $this->dsc->table('pay_log') . " WHERE order_id = '$log_id' AND order_type = '" . PAY_TOPUP . "' LIMIT 1";
                $pay_log = $this->db->getRow($sql);

                /* 获取支付信息 */
                $payment_info = payment_info($account_log['pay_id']);

                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);

                //计算支付手续费用
                $payment_info['pay_fee'] = pay_fee($account_log['pay_id'], $account_log['amount'], 0);
                $apply_info['order_amount'] = $account_log['amount'] + $payment_info['pay_fee'];
                $apply_info['order_sn'] = $account_log['apply_sn'];
                $apply_info['user_id'] = $account_log['ru_id'];
                $apply_info['surplus_amount'] = $account_log['amount'];
                $apply_info['log_id'] = $pay_log['log_id'];

                if ($payment_info['pay_code'] == 'balance') {
                    //查询出当前用户的剩余余额;
                    $user_money = $this->db->getOne("SELECT user_money FROM " . $this->dsc->table('users') . " WHERE user_id='" . $account_log['ru_id'] . "'");
                    //如果用户余额足够支付订单;
                    if ($user_money >= $account_log['amount']) {

                        /* 改变商家金额 */
                        $sql = " UPDATE " . $this->dsc->table('seller_shopinfo') . " SET seller_money = seller_money + " . $account_log['amount'] . " WHERE ru_id = '" . $account_log['ru_id'] . "'";
                        $this->db->query($sql);

                        /* 修改申请的支付状态 */
                        $sql = " UPDATE " . $this->dsc->table('seller_account_log') . " SET is_paid = 1, pay_time = '" . gmtime() . "' WHERE log_id = '$log_id'";
                        $this->db->query($sql);

                        load_helper('clips');

                        /* 改变会员金额 */
                        $sql = " UPDATE " . $this->dsc->table('users') . " SET user_money = user_money - " . $account_log['amount'] . " WHERE user_id = '" . $account_log['ru_id'] . "'";
                        $this->db->query($sql);

                        $change_desc = $GLOBALS['_LANG']['label_seller_topup'] . $account_log['apply_sn'];

                        $user_account_log = [
                            'user_id' => $account_log['ru_id'],
                            'user_money' => "-" . $account_log['amount'],
                            'change_desc' => $change_desc,
                            'process_type' => 0,
                            'payment' => $payment_info['pay_name'],
                            'change_time' => gmtime(),
                            'change_type' => 1,
                        ];

                        $this->db->autoExecute($this->dsc->table('account_log'), $user_account_log, 'INSERT');

                        //记录支付log
                        $sql = "UPDATE " . $this->dsc->table('pay_log') . "SET is_paid = 1 WHERE order_id = '$log_id' AND order_type = '" . PAY_TOPUP . "'";
                        $this->db->query($sql);

                        $change_desc = "【" . session('seller_name') . "】" . $GLOBALS['_LANG']['seller_change_desc'];
                        $log = [
                            'user_id' => $account_log['ru_id'],
                            'user_money' => $account_log['amount'],
                            'change_time' => gmtime(),
                            'change_desc' => $change_desc,
                            'change_type' => 1
                        ];
                        $this->db->autoExecute($this->dsc->table('merchants_account_log'), $log, 'INSERT');

                        $link[0] = ['href' => 'merchants_account.php?act=account_manage&act_type=topup', 'text' => $GLOBALS['_LANG']['03_top_up']];
                        return sys_msg($GLOBALS['_LANG']['topup_account_ok'], 0, $link);
                    } else {
                        return sys_msg($GLOBALS['_LANG']['balance_no_enough_select_other_payment']);
                    }
                } else {
                    if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                        /* 调用相应的支付方式文件 */
                        $pay_name = Str::studly($payment_info['pay_code']);
                        $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                        /* 取得在线支付方式的支付按钮 */
                        if (!is_null($pay_obj)) {
                            $payment_info['pay_button'] = $pay_obj->get_code($apply_info, $payment);
                        }
                    }
                }
                $this->smarty->assign('grade_type', 2);
                $this->smarty->assign('apply_id', $log_id);
                $this->smarty->assign('payment', $payment_info);
                $this->smarty->assign('order', $apply_info);
                $this->smarty->assign('amount', $account_log['amount']);
                return $this->smarty->display('seller_done.dwt');
            } elseif ($act_type == 'account_log_list') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_seller_detail']);

                $this->smarty->assign('full_page', 1);
                $list = get_seller_account_log();

                // 分页
                $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
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
        //-- 微信支付改变状态
        /*------------------------------------------------------ */
        elseif ($act == 'checkorder') {
            $is_paid = 0;
            $apply_id = isset($_GET['apply_id']) ? intval($_GET['apply_id']) : 0;
            $sql = "SELECT is_paid FROM " . $this->dsc->table('seller_account_log') . " WHERE log_id = '$apply_id' LIMIT 1";
            $is_paid = $this->db->getOne($sql);
            //已付款
            if ($is_paid == 1) {
                $json = ['code' => 1];
                return response()->json($json);
            } else {
                $json = ['code' => 0];
                return response()->json($json);
            }
        }

        /*------------------------------------------------------ */
        //-- ajax返回列表
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            if ($act_type == 'detail') {
                $list = get_account_log_list($adminru['ru_id'], [2, 3, 4, 5]);
                $fetch = "merchants_detail";
            } elseif ($act_type == 'account_log') {
                $list = get_account_log_list($adminru['ru_id'], [1, 4]);
                $fetch = "merchants_account_log";
            }

            if ($act_type == 'detail' || $act_type == 'account_log') {
                // 分页
                $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);

                $sort_flag = sort_flag($list['filter']);
                $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

                return make_json_result($this->smarty->fetch($fetch . '.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
            } /**
             * 账户明细
             */
            elseif ($act_type == 'account_log_list') {
                $list = get_seller_account_log();

                // 分页
                $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
                $page_count_arr = seller_page($list, $page);
                $this->smarty->assign('page_count_arr', $page_count_arr);

                $list['filter']['act_type'] = 'account_log_list';
                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'account_log_list');

                return make_json_result($this->smarty->fetch('account_log_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
            }
        }

        /*------------------------------------------------------ */
        //-- 插入/更新账户信息
        /*------------------------------------------------------ */
        elseif ($act == 'account_edit') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            $is_insert = isset($_REQUEST['form_act']) ? trim($_REQUEST['form_act']) : '';
            $other['real_name'] = isset($_REQUEST['real_name']) ? addslashes(trim($_REQUEST['real_name'])) : '';
            $other['self_num'] = isset($_REQUEST['self_num']) ? addslashes(trim($_REQUEST['self_num'])) : '';
            $other['bank_name'] = isset($_REQUEST['bank_name']) ? addslashes(trim($_REQUEST['bank_name'])) : '';
            $other['bank_card'] = isset($_REQUEST['bank_card']) ? addslashes(trim($_REQUEST['bank_card'])) : '';
            $other['bank_mobile'] = isset($_REQUEST['mobile_phone']) ? addslashes(trim($_REQUEST['mobile_phone'])) : '';
            $other['mobile_code'] = isset($_REQUEST['mobile_code']) ? intval($_REQUEST['mobile_code']) : '';
            $other['user_type'] = 1;
            $other['user_id'] = $adminru['ru_id'];

            $link[0] = ['href' => 'merchants_account.php?act=account_manage&act_type=account', 'text' => $GLOBALS['_LANG']['01_seller_account']];

            if (isset($_REQUEST['mobile_code']) && session()->has('sms_mobile_code') && session('sms_mobile_code') != $other['mobile_code']) {
                return sys_msg($GLOBALS['_LANG']['mobile_code_error'], 0, $link);
            }

            $front_name = '';
            $reverse_name = '';

            /* 身份证正反面 */
            if ((isset($_FILES['front_of_id_card']['error']) && $_FILES['front_of_id_card']['error'] == 0) || (!isset($_FILES['front_of_id_card']['error']) && isset($_FILES['front_of_id_card']['tmp_name']) && $_FILES['front_of_id_card']['tmp_name'] != 'none')) {
                $front_name = $image->upload_image($_FILES['front_of_id_card'], 'idcard');
                $this->dscRepository->getOssAddFile([$front_name]);
            }

            if (!empty($_FILES['reverse_of_id_card']['size'])) {
                $reverse_name = $image->upload_image($_FILES['reverse_of_id_card'], 'idcard');
                $this->dscRepository->getOssAddFile([$reverse_name]);
            }

            if ($is_insert == 'insert') {
                $other['front_of_id_card'] = $front_name;
                $other['reverse_of_id_card'] = $reverse_name;

                $other['add_time'] = gmtime();
                $this->db->autoExecute($this->dsc->table('users_real'), $other, 'INSERT');
            } else {
                $usersReal = UsersReal::select('front_of_id_card', 'reverse_of_id_card')
                    ->where('user_id', $adminru['ru_id'])
                    ->where('user_type', 1);
                $usersReal = $this->baseRepository->getToArrayFirst($usersReal);

                if ($front_name && $usersReal && empty($usersReal['front_of_id_card'])) {
                    $other['front_of_id_card'] = $front_name;
                }

                if ($reverse_name && $usersReal && empty($usersReal['reverse_of_id_card'])) {
                    $other['reverse_of_id_card'] = $reverse_name;
                }

                $other['review_status'] = 0;//编辑后修改状态为未审核
                $this->db->autoExecute($this->dsc->table('users_real'), $other, 'UPDTAE', "user_id = '" . $adminru['ru_id'] . "' AND user_type = 1");
            }

            //初始化短信验证码
            session()->forget('sms_mobile_code');

            return sys_msg($is_insert ? $GLOBALS['_LANG']['add_account_ok'] : $GLOBALS['_LANG']['edit_account_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 提交提现信息
        /*------------------------------------------------------ */
        elseif ($act == 'deposit_insert') {
            $other['amount'] = isset($_REQUEST['deposit']) ? floatval(trim($_REQUEST['deposit'])) : 0;
            $other['frozen_money'] = $other['amount'];
            $other['seller_note'] = isset($_REQUEST['deposit_note']) ? addslashes(trim($_REQUEST['deposit_note'])) : 0;
            $other['real_id'] = isset($_REQUEST['real_id']) ? intval($_REQUEST['real_id']) : 0;
            $other['add_time'] = gmtime();
            $other['log_type'] = 1; //提现类型
            $other['ru_id'] = $adminru['ru_id']; //商家ID
            $other['deposit_mode'] = isset($_REQUEST['deposit_mode']) ? intval($_REQUEST['deposit_mode']) : 0; //提现方式

            //商家申请提现记录
            $this->db->autoExecute($this->dsc->table('seller_account_log'), $other, 'INSERT');

            //商家资金变动
            log_seller_account_change($other['ru_id'], "-" . $other['amount'], $other['amount']);

            //商家资金明细记录
            merchants_account_log($other['ru_id'], "-" . $other['amount'], $other['frozen_money'], "【" . session('seller_name') . "】" . $GLOBALS['_LANG']['02_seller_deposit']);

            $link[0] = ['href' => 'merchants_account.php?act=account_manage&act_type=account_log', 'text' => $GLOBALS['_LANG']['05_seller_account_log']];
            return sys_msg($GLOBALS['_LANG']['deposit_account_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 提交解冻信息
        /*------------------------------------------------------ */
        elseif ($act == 'unfreeze') {
            $other['frozen_money'] = isset($_REQUEST['frozen_money']) ? floatval(trim($_REQUEST['frozen_money'])) : 0;
            $other['seller_note'] = isset($_REQUEST['topup_note']) ? addslashes(trim($_REQUEST['topup_note'])) : '';
            $other['seller_note'] = "【" . session('seller_name') . "】" . $other['seller_note'];
            $other['add_time'] = gmtime();
            $other['log_type'] = 5; //提现类型
            $other['ru_id'] = $adminru['ru_id']; //商家ID

            $this->db->autoExecute($this->dsc->table('seller_account_log'), $other, 'INSERT');
            log_seller_account_change($other['ru_id'], 0, "-" . $other['frozen_money']);
            merchants_account_log($other['ru_id'], 0, "-" . $other['frozen_money'], "【" . session('seller_name') . "】" . $GLOBALS['_LANG']['apply_for_account']);

            $link[0] = ['href' => 'merchants_account.php?act=account_manage&act_type=account_log', 'text' => $GLOBALS['_LANG']['05_seller_account_log']];
            return sys_msg($GLOBALS['_LANG']['deposit_account_ok'], 0, $link);
        }
        /*------------------------------------------------------ */
        //-- 提交充值信息
        /*------------------------------------------------------ */
        elseif ($act == 'topup_insert') {
            load_helper('clips');
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            $nowTime = gmtime();

            $other['amount'] = isset($_REQUEST['topup_account']) ? floatval(trim($_REQUEST['topup_account'])) : 0;
            $other['seller_note'] = isset($_REQUEST['topup_note']) ? addslashes(trim($_REQUEST['topup_note'])) : 0;
            $other['pay_id'] = isset($_REQUEST['pay_id']) ? intval($_REQUEST['pay_id']) : 0;
            $other['add_time'] = $nowTime;
            $other['log_type'] = 3; //充值类型
            $other['ru_id'] = $adminru['ru_id']; //商家ID

            $certificate_img = isset($_FILES['certificate_img']) ? $_FILES['certificate_img'] : [];

            if ($certificate_img['name']) {
                $other['certificate_img'] = $image->upload_image([], 'seller_account', '', 1, $certificate_img['name'], $certificate_img['type'], $certificate_img['tmp_name'], $certificate_img['error'], $certificate_img['size']);  //图片存放地址 -- data/seller_account
            }

            $other['apply_sn'] = get_order_sn(); //获取新订单号
            $other['pay_time'] = $nowTime;

            $this->db->autoExecute($this->dsc->table('seller_account_log'), $other, 'INSERT');
            $log_id = $this->db->insert_id();

            /* 插入支付日志 */
            insert_pay_log($log_id, $other['amount'], PAY_TOPUP);

            $Loaction = "merchants_account.php?act=account_manage&act_type=topup_pay&log_id=" . $log_id;
            return dsc_header("Location: $Loaction\n");
        }

        /*------------------------------------------------------ */
        //-- 取消充值付款
        /*------------------------------------------------------ */
        elseif ($act == 'del_pay') {
            $log_id = isset($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('seller_account_log') . " WHERE log_id = '$log_id'";
            $this->db->query($sql);

            $Loaction = "merchants_account.php?act=account_manage&act_type=detail";
            return dsc_header("Location: $Loaction\n");
        }
    }

    /**
     * 账户菜单
     */
    private function get_account_tab_menu($act_type = '')
    {
        $account_curr = 0;
        $deposit_curr = 0;
        $topup_curr = 0;
        $detail_curr = 0;
        $account_log_curr = 0;
        $frozen_money_curr = 0;
        $account_log_list_curr = 0;

        $act_type = $act_type ?: 'account';

        $tab_menu = [];
        if ($act_type == 'account') {
            $account_curr = 1;
        } elseif ($act_type == 'deposit') {
            $deposit_curr = 1;
        } elseif ($act_type == 'topup' || $act_type == 'topup_pay') {
            $topup_curr = 1;
        } elseif ($act_type == 'detail') {
            $detail_curr = 1;
        } elseif ($act_type == 'account_log') {
            $account_log_curr = 1;
        } elseif ($act_type == 'frozen_money') {
            $frozen_money_curr = 1;
        } elseif ($act_type == 'account_log_list') {
            $account_log_list_curr = 1;
        }

        $tab_menu[] = ['curr' => $account_curr, 'text' => $GLOBALS['_LANG']['01_seller_account'], 'href' => 'merchants_account.php?act=account_manage&act_type=account'];
        $tab_menu[] = ['curr' => $deposit_curr, 'text' => $GLOBALS['_LANG']['02_seller_deposit'], 'href' => 'merchants_account.php?act=account_manage&act_type=deposit'];
        $tab_menu[] = ['curr' => $topup_curr, 'text' => $GLOBALS['_LANG']['03_top_up'], 'href' => 'merchants_account.php?act=account_manage&act_type=topup'];
        $tab_menu[] = ['curr' => $detail_curr, 'text' => $GLOBALS['_LANG']['04_seller_detail'], 'href' => 'merchants_account.php?act=account_manage&act_type=detail'];
        $tab_menu[] = ['curr' => $account_log_curr, 'text' => $GLOBALS['_LANG']['05_seller_account_log'], 'href' => 'merchants_account.php?act=account_manage&act_type=account_log'];
        $tab_menu[] = ['curr' => $frozen_money_curr, 'text' => $GLOBALS['_LANG']['title_frozen_money'], 'href' => 'merchants_account.php?act=account_manage&act_type=frozen_money'];
        $tab_menu[] = ['curr' => $account_log_list_curr, 'text' => $GLOBALS['_LANG']['fund_details'], 'href' => 'merchants_account.php?act=account_manage&act_type=account_log_list'];
        return $tab_menu;
    }
}
