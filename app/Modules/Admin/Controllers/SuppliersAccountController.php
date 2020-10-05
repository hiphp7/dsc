<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Repositories\Common\DscRepository;
use App\Services\Store\StoreCommonService;

class SuppliersAccountController extends InitController
{
    protected $dscRepository;
    protected $storeCommonService;

    public function __construct(
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper(['order', 'suppliers']);

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /* 检查权限 */
        admin_priv('suppliers_account');

        if (!isset($_REQUEST['act_type'])) {
            $_REQUEST['act_type'] = 'detail';
        }

        /*------------------------------------------------------ */
        //-- 账户管理
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('suppliers_account');

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $suppliers_id = isset($_REQUEST['suppliers_id']) && !empty($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : 0;

            $action_link = "&suppliers_id=" . $suppliers_id;
            $this->smarty->assign('suppliers_id', $suppliers_id);

            $this->smarty->assign('action_link6', array('text' => $GLOBALS['_LANG']['fund_details'], 'href' => 'suppliers_account.php?act=account_log_list'));
            $this->smarty->assign('action_link2', array('text' => $GLOBALS['_LANG']['presentation_record'], 'href' => 'suppliers_account.php?act=list&act_type=detail&log_type=4' . $action_link));
            $this->smarty->assign('action_link1', array('text' => $GLOBALS['_LANG']['recharge_record'], 'href' => 'suppliers_account.php?act=list&act_type=detail&log_type=3' . $action_link));
            $this->smarty->assign('action_link4', array('text' => $GLOBALS['_LANG']['settlement_record'], 'href' => 'suppliers_account.php?act=list&act_type=detail&log_type=2' . $action_link));
            $this->smarty->assign('action_link5', array('text' => $GLOBALS['_LANG']['thawing_record'], 'href' => 'suppliers_account.php?act=list&act_type=detail&log_type=5' . $action_link));
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['05_seller_account_log'], 'href' => 'suppliers_account.php?act=list&act_type=account_log' . $action_link));
            $this->smarty->assign('action_link3', array('text' => $GLOBALS['_LANG']['suppliers_funds_list'], 'href' => 'suppliers_account.php?act=list&act_type=suppliers_seller_account' . $action_link));
            $this->smarty->assign('full_page', 1);

            if ($_REQUEST['act_type'] == 'detail') {
                $log_type = isset($_REQUEST['log_type']) ? $_REQUEST['log_type'] : 4;
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_seller_detail']);
                $this->smarty->assign('log_type', $log_type);
                $list = get_suppliers_account_log_detail($adminru['suppliers_id'], array($log_type));

                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'detail');

                return $this->smarty->display('suppliers_detail.dwt');
            } elseif ($_REQUEST['act_type'] == 'account_log') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_seller_account_log']);
                $list = get_suppliers_account_log_detail($adminru['suppliers_id'], array(1, 4, 5));

                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'account_log');

                return $this->smarty->display('suppliers_account_log.dwt');
            } elseif ($_REQUEST['act_type'] == 'suppliers_seller_account') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['suppliers_funds_list']);
                $list = $this->get_suppliers_seller_account();
                $list['filter']['act_type'] = $_REQUEST['act_type'];
                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('act_type', 'suppliers_seller_account');

                return $this->smarty->display('suppliers_seller_account.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- ajax返回列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_account');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if ($_REQUEST['act_type'] == 'detail') {
                $log_type = isset($_REQUEST['log_type']) ? $_REQUEST['log_type'] : 4;
                $this->smarty->assign('log_type', $log_type);
                $list = get_suppliers_account_log_detail($adminru['suppliers_id'], array($log_type));
                $fetch = "suppliers_detail";
            } elseif ($_REQUEST['act_type'] == 'account_log') {
                $list = get_suppliers_account_log_detail($adminru['suppliers_id'], array(1, 4, 5));
                $fetch = "suppliers_account_log";
            } elseif ($_REQUEST['act_type'] == 'suppliers_seller_account') {
                $list = $this->get_suppliers_seller_account();
                $list['filter']['act_type'] = $_REQUEST['act_type'];
                $fetch = "suppliers_seller_account";
            }

            if ($_REQUEST['act_type'] == 'detail' || $_REQUEST['act_type'] == 'account_log' || $_REQUEST['act_type'] == 'suppliers_seller_account') {
                $this->smarty->assign('log_list', $list['log_list']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);

                $sort_flag = sort_flag($list['filter']);
                $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

                return make_json_result($this->smarty->fetch($fetch . '.dwt'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
            }
        }

        /*------------------------------------------------------ */
        //-- 查看
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check') {
            /* 检查权限 */
            admin_priv('suppliers_account');

            $this->smarty->assign('action_link2', array('text' => $GLOBALS['_LANG']['04_seller_detail'], 'href' => 'suppliers_account.php?act=list&act_type=detail'));
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['05_seller_account_log'], 'href' => 'suppliers_account.php?act=list&act_type=account_log'));
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['check']);
            $log_id = isset($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;
            $act_type = isset($_REQUEST['act_type']) ? addslashes($_REQUEST['act_type']) : 0;

            $this->smarty->assign('log_id', $log_id);
            $this->smarty->assign('form_action', "update_check");

            $log_info = get_suppliers_account_log_info($log_id);
            $this->smarty->assign('log_info', $log_info);
            $this->smarty->assign('act_type', $act_type);

            if ($log_info) {
                $suppliers_info = array(
                    'suppliers_money' => $log_info['suppliers_money'],
                    'frozen_money' => $log_info['frozen_money'],
                );
            } else {
                $suppliers_info = array();
            }

            $this->smarty->assign('suppliers_info', $suppliers_info);

            $users_real = get_users_real($log_info['ru_id'], 1);
            $this->smarty->assign('real', $users_real);

            return $this->smarty->display('suppliers_log_check.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查看
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_check') {
            /* 检查权限 */
            admin_priv('suppliers_account');

            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            $log = array();
            $log_id = isset($_REQUEST['log_id']) ? intval($_REQUEST['log_id']) : 0;
            $log_reply = isset($_REQUEST['log_reply']) ? addslashes(trim($_REQUEST['log_reply'])) : 0;
            $log_status = isset($_REQUEST['log_status']) ? intval($_REQUEST['log_status']) : 0;
            $certificate_img = isset($_FILES['certificate_img']) ? $_FILES['certificate_img'] : array();
            $msg_type = 0;
            $log_info = get_suppliers_account_log_info($log_id);

            if ($log_status > 0 && $log_status <= 2) {
                if ($log_info['log_type'] == 5) {
                    $log_type = 5;

                    if ($log_status == 1) {
                        /* 改变商家金额 */
                        $sql = " UPDATE " . $this->dsc->table('suppliers') . " SET suppliers_money = suppliers_money + " . $log_info['frozen_money'] . " WHERE suppliers_id = '" . $log_info['suppliers_id'] . "'";
                        $this->db->query($sql);

                        $handler = $GLOBALS['_LANG']['frozen_money_success'];

                        $log = array(
                            'user_id' => $log_info['suppliers_id'],
                            'user_money' => $log_info['frozen_money'],
                            'change_time' => gmtime(),
                            'change_desc' => sprintf($GLOBALS['_LANG']['check_change_desc'], session('admin_name')),
                            'change_type' => 4
                        );
                    } else {
                        $handler = $GLOBALS['_LANG']['frozen_money_failure'];

                        //商家资金变动
                        log_suppliers_account_change($log_info['suppliers_id'], 0, $log_info['frozen_money']);

                        //商家资金明细记录
                        suppliers_account_log($log_info['suppliers_id'], 0, $log_info['frozen_money'], "【" . session('admin_name') . "】" . $GLOBALS['_LANG']['08_refuse_apply_for']);
                    }

                    $href = "suppliers_account.php?act=list&act_type=account_log";
                    $text = $GLOBALS['_LANG']['05_seller_account_log'];

                    /* 改变商家资金记录 */
                    $sql = " UPDATE " . $this->dsc->table('suppliers_account_log_detail') . " SET is_paid = $log_status, admin_note = '$log_reply', log_type = '$log_type' WHERE log_id = '$log_id'";
                    $this->db->query($sql);
                } else {
                    if ($log_info['suppliers_frozen'] < $log_info['amount'] && isset($log_info['payment_info']['pay_code']) && $log_info['payment_info']['pay_code'] != 'bank') {
                        $handler = $GLOBALS['_LANG']['not_sufficient_funds'];
                        $msg_type = 1;
                        $text = $GLOBALS['_LANG']['go_back'];

                        if ($log_info['log_type'] == 3) {
                            $href = "suppliers_account.php?act=check&log_id=" . $log_info['log_id'] . "&act_type=detail";
                        } elseif ($log_info['log_type'] == 1 || $log_info['log_type'] == 4) {
                            $href = "suppliers_account.php?act=check&log_id=" . $log_info['log_id'] . "&act_type=account_log";
                        } else {
                            $href = "suppliers_account.php?act=list&act_type=account_log";
                        }
                    } else {
                        $certificate = '';
                        if ($certificate_img['name']) {
                            $certificate = $image->upload_image($certificate_img, 'suppliers_account');  //图片存放地址 -- data/seller_account
                        }

                        //银行转账
                        if (isset($log_info['payment_info']['pay_code']) && $log_info['payment_info']['pay_code'] == 'bank') {

                            /* 改变商家金额 */
                            $sql = " UPDATE " . $this->dsc->table('suppliers') . " SET suppliers_money = suppliers_money + " . $log_info['amount'] . " WHERE suppliers_id = '" . $log_info['suppliers_id'] . "'";
                            $this->db->query($sql);

                            $log_type = 3;
                            $handler = $GLOBALS['_LANG']['topup_account_ok'];
                            $href = "suppliers_account.php?act=check&log_id=" . $log_id . "&act_type=detail";
                            $text = $GLOBALS['_LANG']['04_seller_detail'];
                            $log = array(
                                'user_id' => $log_info['suppliers_id'],
                                'user_money' => $log_info['amount'],
                                'change_time' => gmtime(),
                                'change_desc' => sprintf($GLOBALS['_LANG']['07_seller_top_up'], session('admin_name')),
                                'change_type' => 1
                            );
                        } else {

                            /* 转账至前台会员余额账户 start */
                            if ($log_info['deposit_mode'] == 1) {
                                /* 改变会员金额 */
                                $sql = " UPDATE " . $this->dsc->table('users') . " SET user_money = user_money + " . $log_info['amount'] . " WHERE user_id = '" . $log_info['ru_id'] . "'";
                                $this->db->query($sql);
                            }
                            /* 转账至前台会员余额账户 end */

                            /* 改变商家金额 */
                            $sql = " UPDATE " . $this->dsc->table('suppliers') . " SET frozen_money = frozen_money - " . $log_info['amount'] . " WHERE suppliers_id = '" . $log_info['suppliers_id'] . "'";
                            $this->db->query($sql);

                            $change_desc = sprintf($GLOBALS['_LANG']['06_seller_deposit'], session('admin_name'));

                            $log_type = 4;
                            $handler = $GLOBALS['_LANG']['deposit_account_ok'];
                            $href = "suppliers_account.php?act=list&act_type=account_log";
                            $text = $GLOBALS['_LANG']['05_seller_account_log'];

                            /* 转账至前台会员余额账户 start */
                            if ($log_info['deposit_mode'] == 1) {
                                $user_account_log = array(
                                    'user_id' => $log_info['ru_id'],
                                    'user_money' => "+" . $log_info['amount'],
                                    'change_desc' => $change_desc,
                                    'process_type' => 0,
                                    'payment' => '',
                                    'change_time' => gmtime(),
                                    'change_type' => 2,
                                );

                                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('account_log'), $user_account_log, 'INSERT');
                            }
                            /* 转账至前台会员余额账户 end */

                            $log = array(
                                'user_id' => $log_info['suppliers_id'],
                                'change_time' => gmtime(),
                                'change_desc' => $change_desc
                            );

                            $log['frozen_money'] = "-" . $log_info['amount'];
                        }

                        $update_set = '';
                        if ($certificate) {
                            $update_set .= "certificate_img = '$certificate', ";
                        }

                        /* 改变会员金额 */
                        $sql = " UPDATE " . $this->dsc->table('suppliers_account_log_detail') . " SET is_paid = '$log_status', $update_set admin_note = '$log_reply', log_type = '$log_type' WHERE log_id = '$log_id'";
                        $this->db->query($sql);
                    }
                }
            } else {
                $handler = $GLOBALS['_LANG']['handler_failure'];
                $msg_type = 1;
                $text = $GLOBALS['_LANG']['go_back'];
                if ($log_info['payment_info']['pay_name'] == lang('admin/suppliers_account.remittance')) {
                    $href = "suppliers_account.php?act=list";
                } else {
                    $href = "suppliers_account.php?act=list&act_type=account_log";
                }
            }

            if (!empty($log)) {
                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('suppliers_account_log'), $log, 'INSERT');
            }

            $link[0] = array('href' => $href, 'text' => $text);
            return sys_msg($handler, $msg_type, $link);
        }

        /*------------------------------------------------------ */
        //-- 调节商家账户
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_seller') {
            /* 检查权限 */
            admin_priv('suppliers_account');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['adjust_merchant_account']);
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['suppliers_funds_list'], 'href' => 'suppliers_account.php?act=list&act_type=suppliers_seller_account'));

            $suppliers_id = isset($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : 0;

            $sql = "SELECT  sal.suppliers_money, sal.frozen_money, sal.suppliers_id, sal.suppliers_name FROM " . $GLOBALS['dsc']->table('suppliers') . " AS sal " .
                "WHERE sal.suppliers_id = '$suppliers_id' LIMIT 1 ";
            $suppliers_info = $this->db->getRow($sql);

            $suppliers_info['formated_suppliers_money'] = price_format($suppliers_info['suppliers_money'], false);
            $suppliers_info['formated_frozen_money'] = price_format($suppliers_info['frozen_money'], false);
            $this->smarty->assign("suppliers_info", $suppliers_info);

            $sc_rand = rand(1000, 9999);
            $sc_guid = sc_guid();

            $seller_account_cookie = MD5($sc_guid . "-" . $sc_rand);
            cookie()->queue('seller_account_cookie', $seller_account_cookie, gmtime() + 3600 * 24 * 30);

            $this->smarty->assign('sc_guid', $sc_guid);
            $this->smarty->assign('sc_rand', $sc_rand);

            return $this->smarty->display("suppliers_account_info.dwt");
        }

        /*------------------------------------------------------ */
        //-- 调节商家账户
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 检查权限 */
            admin_priv('suppliers_account');

            /* 检查参数 */
            $suppliers_id = empty($_REQUEST['suppliers_id']) ? 0 : intval($_REQUEST['suppliers_id']);

            if ($suppliers_id <= 0) {
                return sys_msg('invalid param');
            }

            /* 提示信息 */
            $links = array(
                array('href' => 'suppliers_account.php?act=account_log_list&suppliers_id=' . $suppliers_id, 'text' => $GLOBALS['_LANG']['account_list']),
                array('href' => 'suppliers_account.php?act=edit_seller&suppliers_id=' . $suppliers_id, 'text' => $GLOBALS['_LANG']['add_account'])
            );

            /* 防止重复提交 start */
            $sc_rand = isset($_POST['sc_rand']) && !empty($_POST['sc_rand']) ? trim($_POST['sc_rand']) : '';
            $sc_guid = isset($_POST['sc_guid']) && !empty($_POST['sc_guid']) ? trim($_POST['sc_guid']) : '';

            $seller_account_cookie = MD5($sc_guid . "-" . $sc_rand);

            if (!empty($sc_guid) && !empty($sc_rand) && request()->hasCookie('seller_account_cookie')) {
                if (!empty(request()->cookie('seller_account_cookie'))) {
                    if (!(request()->cookie('seller_account_cookie') == $seller_account_cookie)) {
                        return sys_msg($GLOBALS['_LANG']['repeat_submit'], 0, $links);
                    }
                } else {
                    return sys_msg($GLOBALS['_LANG']['log_account_change_no'], 0, $links);
                }

                $sql = "SELECT suppliers_id, suppliers_money, frozen_money FROM" . $this->dsc->table("suppliers") . " WHERE suppliers_id = '$suppliers_id' LIMIT 1";
                $suppliers_info = $this->db->getRow($sql);

                if (!$suppliers_info) {
                    return sys_msg($GLOBALS['_LANG']['user_not_exist']);
                }

                $money_status = intval($_POST['money_status']);
                $add_sub_user_money = floatval($_POST['add_sub_user_money']);  // 值：1（增加） 值：-1（减少）
                $add_sub_frozen_money = floatval($_POST['add_sub_frozen_money']); // 值：1（增加） 值：-1（减少）
                $change_desc = $this->dscRepository->subStr($_POST['change_desc'], 255, false);
                $user_money = isset($_POST['user_money']) && !empty($_POST['user_money']) ? $add_sub_user_money * abs(floatval($_POST['user_money'])) : 0;
                $frozen_money = isset($_POST['frozen_money']) && !empty($_POST['frozen_money']) ? $add_sub_frozen_money * abs(floatval($_POST['frozen_money'])) : 0;
                if ($money_status == 0 && abs($user_money) > $suppliers_info['suppliers_money'] && $add_sub_user_money < 0) {
                    return sys_msg(lang('admin/suppliers_account.amount_beyond'));
                }

                if ($money_status == 1 && abs($frozen_money) > $suppliers_info['frozen_money'] && $add_sub_user_money > 0) {
                    return sys_msg(lang('admin/suppliers_account.freezing_amount_beyond'));
                }

                if ($user_money == 0 && $frozen_money == 0) {
                    return sys_msg($GLOBALS['_LANG']['no_account_change']);
                }


                if ($money_status == 1) {
                    if ($frozen_money > 0) {
                        $user_money = '-' . $frozen_money;
                    } else {
                        if (!empty($frozen_money) && !(strpos($frozen_money, "-") === false)) {
                            $user_money = substr($frozen_money, 1);
                        }
                    }
                }

                if ($suppliers_info) {
                    $user_money = get_return_money($user_money, $suppliers_info['suppliers_money']);
                    $frozen_money = get_return_money($frozen_money, $suppliers_info['frozen_money']);

                    if ($money_status == 1) {
                        if ($frozen_money == 0) {
                            $user_money = 0;
                        }
                    }
                }

                //更新商家资金
                log_suppliers_account_change($suppliers_id, $user_money, $frozen_money);

                /* 记录明细 */
                $change_desc = sprintf($GLOBALS['_LANG']['seller_change_money'], session('admin_name')) . $change_desc;
                suppliers_account_log($suppliers_id, $user_money, $frozen_money, $change_desc, 3);

                //防止重复提交
                cookie()->queue('seller_account_cookie', '', gmtime() + 3600 * 24 * 30);
            }
            /* 防止重复提交 end */

            return sys_msg($GLOBALS['_LANG']['suppliers_funds_list'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 日志列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'account_log_list') {
            /* 检查权限 */
            admin_priv('suppliers_account');

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_seller_detail']);
            $this->smarty->assign('action_link6', array('text' => $GLOBALS['_LANG']['fund_details'], 'href' => 'suppliers_account.php?act=account_log_list'));
            $this->smarty->assign('action_link2', array('text' => $GLOBALS['_LANG']['presentation_record'], 'href' => 'suppliers_account.php?act=list&act_type=detail&log_type=4'));
            $this->smarty->assign('action_link1', array('text' => $GLOBALS['_LANG']['recharge_record'], 'href' => 'suppliers_account.php?act=list&act_type=detail&log_type=3'));
            $this->smarty->assign('action_link4', array('text' => $GLOBALS['_LANG']['settlement_record'], 'href' => 'suppliers_account.php?act=list&act_type=detail&log_type=2'));
            $this->smarty->assign('action_link5', array('text' => $GLOBALS['_LANG']['thawing_record'], 'href' => 'suppliers_account.php?act=list&act_type=detail&log_type=5'));
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['05_seller_account_log'], 'href' => 'suppliers_account.php?act=list&act_type=account_log'));
            $this->smarty->assign('action_link3', array('text' => $GLOBALS['_LANG']['suppliers_funds_list'], 'href' => 'suppliers_account.php?act=list&act_type=suppliers_seller_account'));

            $this->smarty->assign('full_page', 1);
            $list = get_suppliers_account_log();
            $this->smarty->assign('log_list', $list['log_list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('act_type', 'account_log_list');

            return $this->smarty->display('suppliers_account_log_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- Ajax日志列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'account_query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_account');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $list = get_suppliers_account_log();
            $this->smarty->assign('log_list', $list['log_list']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('act_type', 'account_log_list');

            return make_json_result($this->smarty->fetch('suppliers_account_log_list.dwt'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
        }
    }

    /**
     * 商家资金列表
     */
    private function get_suppliers_seller_account()
    {
        $result = get_filter();
        if ($result === false) {
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sal.suppliers_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            $ex_where = ' WHERE sal.review_status = 3 ';

            if ($filter['keywords']) {
                $sql = "SELECT suppliers_id FROM" . $GLOBALS['dsc']->table('suppliers') . " WHERE (suppliers_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' OR suppliers_desc LIKE '%" . mysql_like_quote($filter['keywords']) . "%') ";
                $user_id = $GLOBALS['db']->getAll($sql);
                if ($user_id) {
                    $user_id = implode(',', arr_foreach($user_id));
                    $ex_where .= ' AND sal.suppliers_id in (' . $user_id . ') ';
                }
            }

            $sql = "SELECT count(*) FROM " . $GLOBALS['dsc']->table('suppliers') . " AS sal " .
                " $ex_where";

            $filter['record_count'] = $GLOBALS['db']->getOne($sql);
            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = "SELECT  sal.suppliers_money,sal.frozen_money,sal.suppliers_id,sal.suppliers_name FROM " . $GLOBALS['dsc']->table('suppliers') . " AS sal " .
                " $ex_where " .
                " ORDER BY " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $res = $GLOBALS['db']->getAll($sql);

        $arr = array('log_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }
}
