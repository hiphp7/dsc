<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Log\AccountLogManageService;
use App\Services\User\UserService;

/**
 * 管理中心帐户变动记录
 */
class AccountLogController extends InitController
{
    protected $userService;
    protected $baseRepository;
    protected $accountLogManageService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        UserService $userService,
        AccountLogManageService $accountLogManageService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->userService = $userService;
        $this->accountLogManageService = $accountLogManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        /* ------------------------------------------------------ */
        //-- 办事处列表
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查参数 */
            $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
            if ($user_id <= 0) {
                return sys_msg('invalid param');
            }

            $where = [
                'user_id' => $user_id
            ];
            $user_info = $this->userService->userInfo($where);

            if (empty($user_info)) {
                return sys_msg($GLOBALS['_LANG']['user_not_exist']);
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && isset($user_info['user_name'])) {
                $user_info['user_name'] = $this->dscRepository->stringToStar($user_info['user_name']);
            }

            $this->smarty->assign('user', $user_info);

            if (empty($_REQUEST['account_type']) || !in_array($_REQUEST['account_type'], ['user_money', 'frozen_money', 'rank_points', 'pay_points'])) {
                $account_type = '';
            } else {
                $account_type = $_REQUEST['account_type'];
            }
            $this->smarty->assign('account_type', $account_type);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['account_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_account'], 'href' => 'account_log.php?act=add&user_id=' . $user_id]);
            if ($user_id > 0) {
                $this->smarty->assign('action_link2', ['href' => 'users.php?act=list', 'text' => $GLOBALS['_LANG']['user_list']]);
            }
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign("user_id", $user_id);
            $this->smarty->assign('form_action', 'account_log');
            $account_list = $this->accountLogManageService->getAccountList($user_id, $account_type);
            $this->smarty->assign('account_list', $account_list['account']);
            $this->smarty->assign('filter', $account_list['filter']);
            $this->smarty->assign('record_count', $account_list['record_count']);
            $this->smarty->assign('page_count', $account_list['page_count']);


            return $this->smarty->display('user_list_edit.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 检查参数 */
            $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
            if ($user_id <= 0) {
                return sys_msg('invalid param');
            }

            $where = [
                'user_id' => $user_id
            ];
            $user_info = $this->userService->userInfo($where);

            if (empty($user_info)) {
                return sys_msg($GLOBALS['_LANG']['user_not_exist']);
            }
            $this->smarty->assign('user', $user_info);

            if (empty($_REQUEST['account_type']) || !in_array($_REQUEST['account_type'], ['user_money', 'frozen_money', 'rank_points', 'pay_points'])) {
                $account_type = '';
            } else {
                $account_type = $_REQUEST['account_type'];
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['account_list']);
            $this->smarty->assign('account_type', $account_type);
            $this->smarty->assign("user_id", $user_id);
            $this->smarty->assign('form_action', 'account_log');
            $account_list = $this->accountLogManageService->getAccountList($user_id, $account_type);
            $this->smarty->assign('account_list', $account_list['account']);
            $this->smarty->assign('filter', $account_list['filter']);
            $this->smarty->assign('record_count', $account_list['record_count']);
            $this->smarty->assign('page_count', $account_list['page_count']);

            return make_json_result($this->smarty->fetch('user_list_edit.dwt'), '', ['filter' => $account_list['filter'], 'page_count' => $account_list['page_count']]);
        }

        /* ------------------------------------------------------ */
        //-- 调节帐户
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('account_manage');
            /* 检查参数 */
            $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);

            if ($user_id > 0) {
                $this->smarty->assign('action_link', ['href' => 'users.php?act=list&user_id=' . $user_id, 'text' => $GLOBALS['_LANG']['user_list']]);
            }

            if ($user_id <= 0) {
                return sys_msg('invalid param');
            }

            $where = [
                'user_id' => $user_id
            ];
            $user_info = $this->userService->userInfo($where);

            if (empty($user_info)) {
                return sys_msg($GLOBALS['_LANG']['user_not_exist']);
            }
            $this->smarty->assign('user', $user_info);

            /* 显示模板 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_account']);
            $this->smarty->assign('action_link', ['href' => 'account_log.php?act=list&user_id=' . $user_id, 'text' => $GLOBALS['_LANG']['account_list']]);

            return $this->smarty->display('account_info.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 提交添加、编辑办事处
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('account_manage');

            /* 检查参数 */
            $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);

            /* 提示信息 */
            $links = [
                ['href' => 'account_log.php?act=list&user_id=' . $user_id, 'text' => $GLOBALS['_LANG']['account_list']],
                ['href' => 'account_log.php?act=add&user_id=' . $user_id, 'text' => $GLOBALS['_LANG']['add_account']]
            ];

            $token = trim($_POST['token']);
            if ($token != $GLOBALS['_CFG']['token']) {
                sys_msg($GLOBALS['_LANG']['no_account_change'], 1);
            }

            if ($user_id <= 0) {
                sys_msg('invalid param');
            }

            $where = [
                'user_id' => $user_id
            ];
            $user_info = $this->userService->userInfo($where);

            if (empty($user_info)) {
                sys_msg($GLOBALS['_LANG']['user_not_exist']);
            }

            /* 提交值 */
            $money_status = intval($_POST['money_status']);
            $add_sub_user_money = floatval($_POST['add_sub_user_money']);  // 值：1（增加） 值：-1（减少）
            $add_sub_frozen_money = floatval($_POST['add_sub_frozen_money']); // 值：1（增加） 值：-1（减少）
            $change_desc = $this->dscRepository->subStr($_POST['change_desc'], 255, false);
            $user_money = isset($_POST['user_money']) && !empty($_POST['user_money']) ? $add_sub_user_money * abs(floatval($_POST['user_money'])) : 0;
            $frozen_money = isset($_POST['frozen_money']) && !empty($_POST['frozen_money']) ? $add_sub_frozen_money * abs(floatval($_POST['frozen_money'])) : 0;
            $rank_points = floatval($_POST['add_sub_rank_points']) * abs(floatval($_POST['rank_points']));
            $pay_points = floatval($_POST['add_sub_pay_points']) * abs(floatval($_POST['pay_points']));

            if ($user_money == 0 && $frozen_money == 0 && $rank_points == 0 && $pay_points == 0) {
                sys_msg($GLOBALS['_LANG']['no_account_change']);
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

            if ($user_info) {
                $user_money = get_return_money($user_money, $user_info['user_money']);
                $frozen_money = get_return_money($frozen_money, $user_info['frozen_money']);
                $rank_points = get_return_money($rank_points, $user_info['rank_points']);
                $pay_points = get_return_money($pay_points, $user_info['pay_points']);

                if ($money_status == 1) {
                    if ($frozen_money == 0) {
                        $user_money = 0;
                    }
                }
            }

            /* 保存 */
            log_account_change($user_id, $user_money, $frozen_money, $rank_points, $pay_points, "【" . $GLOBALS['_LANG']['terrace_handle'] . "】" . $change_desc, ACT_ADJUSTING);

            return sys_msg($GLOBALS['_LANG']['log_account_change_ok'], 0, $links);
        }
    }
}
