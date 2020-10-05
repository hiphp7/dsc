<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AdminAction;
use App\Models\AdminUser;
use App\Models\Agency;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Privilege\PrivilegeManageService;

/**
 * DSCMALL 管理员信息以及权限管理程序
 */
class PrivilegeSellerController extends InitController
{
    protected $commonManageService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $baseRepository;
    protected $privilegeManageService;
    protected $sessionRepository;

    public function __construct(
        CommonManageService $commonManageService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        PrivilegeManageService $privilegeManageService,
        SessionRepository $sessionRepository
    )
    {
        $this->commonManageService = $commonManageService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->privilegeManageService = $privilegeManageService;
        $this->sessionRepository = $sessionRepository;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('seller', 0);

        $php_self = $this->commonManageService->getPhpSelf(1);
        $this->smarty->assign('php_self', $php_self);

        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_admin_seller']);

            /*判断是否是商家,是显示添加管理员按钮*/
            if ($adminru['ru_id'] > 0) {
                $this->smarty->assign('action_link', ['href' => 'privilege_seller.php?act=add', 'text' => $GLOBALS['_LANG']['admin_add']]);
            }

            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $this->smarty->assign('full_page', 1);

            $admin_list = $this->privilegeManageService->getAdminUserlist($adminru['ru_id']);

            $this->smarty->assign('admin_list', $admin_list['list']);
            $this->smarty->assign('filter', $admin_list['filter']);
            $this->smarty->assign('record_count', $admin_list['record_count']);
            $this->smarty->assign('page_count', $admin_list['page_count']);

            /* 显示页面 */

            return $this->smarty->display('seller_privilege_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $admin_list = $this->privilegeManageService->getAdminUserlist($adminru['ru_id']);

            $this->smarty->assign('admin_list', $admin_list['list']);
            $this->smarty->assign('filter', $admin_list['filter']);
            $this->smarty->assign('record_count', $admin_list['record_count']);
            $this->smarty->assign('page_count', $admin_list['page_count']);
            return make_json_result($this->smarty->fetch('privilege_list.dwt'), '', ['filter' => $admin_list['filter'], 'page_count' => $admin_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加管理员页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('seller_manage');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_add']);
            $this->smarty->assign('action_link', ['href' => 'privilege_seller.php?act=list', 'text' => $GLOBALS['_LANG']['02_admin_seller']]);
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');

            /* 显示页面 */

            return $this->smarty->display('seller_privilege_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加管理员的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('seller_manage');

            /* 判断管理员是否已经存在 */
            if (!empty($_POST['user_name'])) {
                $is_only = AdminUser::where('user_name', stripslashes($_POST['user_name']))->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($_POST['user_name'])), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($_POST['email'])) {
                $is_only = AdminUser::where('email', stripslashes($_POST['email']))->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['email_exist'], stripslashes($_POST['email'])), 1);
                }
            }

            /* 获取添加日期及密码 */
            $add_time = gmtime();

            $password = md5($_POST['password']);
            $action_list = '';
            $ru_id = '';
            if (session('admin_id') > 0) {

                $res = AdminUser::where('user_id', session('admin_id'));
                $res = $this->baseRepository->getToArrayFirst($res);

                $action_list = $res['action_list'] ?? '';
                $ru_id = $res['ru_id'] ?? 0;
            }
            $res = AdminUser::where('action_list', 'all');
            $row = $this->baseRepository->getToArrayFirst($res);

            /* 转入权限分配列表 */
            $data = [
                'user_name' => trim($_POST['user_name']),
                'email' => trim($_POST['email']),
                'password' => $password,
                'add_time' => $add_time,
                'nav_list' => $row['nav_list'],
                'action_list' => $action_list,
                'ru_id' => $ru_id,
                'parent_id' => session('admin_id')
            ];
            $new_id = AdminUser::insertGetId($data);

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['go_allot_priv'];
            $link[0]['href'] = 'privilege_seller.php?act=allot&id=' . $new_id . '&user=' . $_POST['user_name'] . '';

            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'privilege_seller.php?act=add';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'add', 'privilege');
        }

        /*------------------------------------------------------ */
        //-- 编辑管理员信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 不能编辑demo这个管理员 */
            if (session('admin_name') == 'demo') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'privilege_seller.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            /* 查看是否有权限编辑其他管理员的信息 */
            if (session('admin_id') != $_REQUEST['id']) {
                admin_priv('seller_manage');
            }

            /* 获取管理员信息 */
            $res = AdminUser::where('user_id', $_REQUEST['id']);
            $user_info = $this->baseRepository->getToArrayFirst($res);

            /* 取得该管理员负责的办事处名称 */
            if ($user_info['agency_id'] > 0) {
                $user_info['agency_name'] = Agency::where('agency_id', $user_info['agency_id'])
                    ->value('agency_name');
                $user_info['agency_name'] = $user_info['agency_name'] ? $user_info['agency_name'] : '';
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_admin_seller'], 'href' => 'privilege_seller.php?act=list']);
            $this->smarty->assign('user', $user_info);

            /* 获得该管理员的权限 */
            $priv_str = AdminUser::where('user_id', $_GET['id'])->value('action_list');
            $priv_str = $priv_str ? $priv_str : '';

            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');


            return $this->smarty->display('seller_privilege_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update' || $_REQUEST['act'] == 'update_self') {

            /* 变量初始化 */
            $admin_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $admin_name = !empty($_REQUEST['user_name']) ? trim($_REQUEST['user_name']) : '';
            $admin_email = !empty($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
            $ec_salt = rand(1, 9999);
            $password = !empty($_POST['new_password']) ? md5(md5($_POST['new_password']) . $ec_salt) : '';
            if ($_REQUEST['act'] == 'update') {
                /* 查看是否有权限编辑其他管理员的信息 */
                if (session('admin_id') != $_REQUEST['id']) {
                    admin_priv('seller_manage');
                }
                $g_link = 'privilege_seller.php?act=list';
                $nav_list = '';
            } else {
                $nav_list = !empty($_POST['nav_list']) ? @join(",", $_POST['nav_list']) : '';
                $admin_id = session('admin_id');
                $g_link = 'privilege_seller.php?act=modif';
            }
            /* 判断管理员是否已经存在 */
            if (!empty($admin_name)) {
                $is_only = AdminUser::where('user_name', $admin_name)
                    ->where('user_id', '<>', $admin_id)
                    ->count();
                if ($is_only == 1) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($admin_name)), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($admin_email)) {
                $is_only = AdminUser::where('email', $admin_email)
                    ->where('user_id', '<>', $admin_id)
                    ->count();
                if ($is_only == 1) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['email_exist'], stripslashes($admin_email)), 1);
                }
            }

            //如果要修改密码
            $pwd_modified = false;

            if (!empty($_POST['new_password'])) {

                /* 比较新密码和确认密码是否相同 */
                if ($_POST['new_password'] <> $_POST['pwd_confirm']) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['password_error'], 0, $link);
                } else {
                    $pwd_modified = true;
                }
            }


            //更新管理员信息
            if ($pwd_modified) {
                $data = [
                    'user_name' => $admin_name,
                    'email' => $admin_email,
                    'ec_salt' => $ec_salt,
                    'password' => $password,
                    'nav_list' => $nav_list
                ];
                AdminUser::where('user_id', $admin_id)->update($data);
            } else {
                $data = [
                    'user_name' => $admin_name,
                    'email' => $admin_email,
                    'nav_list' => $nav_list
                ];
                AdminUser::where('user_id', $admin_id)->update($data);

            }

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'edit', 'privilege');

            /* 如果修改了密码，则需要将session中该管理员的数据清空 */
            if ($pwd_modified && $_REQUEST['act'] == 'update_self') {
                $this->sessionRepository->delete_spec_admin_session(session('admin_id'));
                $msg = $GLOBALS['_LANG']['edit_password_succeed'];
            } else {
                $msg = $GLOBALS['_LANG']['edit_profile_succeed'];
            }

            /* 提示信息 */
            $link[] = ['text' => strpos($g_link, 'list') ? $GLOBALS['_LANG']['back_admin_list'] : $GLOBALS['_LANG']['modif_info'], 'href' => $g_link];
            return sys_msg("$msg<script>parent.document.getElementById('header-frame').contentWindow.document.location.reload();</script>", 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 为管理员分配权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'allot') {
            admin_priv('seller_allot');

            $this->dscRepository->helpersLang('priv_action', 'admin');

            $user_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

            /* 获得该管理员的权限 */
            $priv_str = AdminUser::where('user_id', $_GET['id'])->value('action_list');
            $priv_str = $priv_str ? $priv_str : '';

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str == 'all') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege_seller.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            $res = AdminUser::where('user_id', $user_id);
            $user_parent = $this->baseRepository->getToArrayFirst($res);

            $res = AdminUser::where('user_id', $user_parent['parent_id']);
            $parent_priv = $this->baseRepository->getToArrayFirst($res);
            $parent_priv = explode(',', $parent_priv['action_list'] ?? '');

            $priv_str = $user_parent['action_list'];

            /* 获取权限的分组数据 */
            $res = AdminAction::where('parent_id', 0);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $rows) {

                // 微信通
                if (!file_exists(MOBILE_WECHAT) && $rows['action_code'] == 'wechat') {
                    continue;
                }

                // 微分销
                if (!file_exists(MOBILE_DRP) && $rows['action_code'] == 'drp') {
                    continue;
                }

                // 微信小程序
                if (!file_exists(MOBILE_WXAPP) && $rows['action_code'] == 'wxapp') {
                    continue;
                }

                // 拼团
                if (!file_exists(MOBILE_TEAM) && $rows['action_code'] == 'team') {
                    continue;
                }

                // 砍价
                if (!file_exists(MOBILE_BARGAIN) && $rows['action_code'] == 'bargain_manage') {
                    continue;
                }

                $priv_arr[$rows['action_id']] = $rows;
            }

            if ($priv_arr) {
                /* 按权限组查询底级的权限名称 */
                $res = AdminAction::whereIn('parent_id', array_keys($priv_arr))
                    ->whereIn('action_code', array_values($parent_priv));
                $result = $this->baseRepository->getToArrayGet($res);

                foreach ($result as $priv) {
                    $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
                }

                // 将同一组的权限使用 "," 连接起来，供JS全选 ecmoban模板堂 --zhuo
                foreach ($priv_arr as $action_id => $action_group) {
                    if (isset($action_group['priv']) && $action_group['priv']) {
                        $priv = @array_keys($action_group['priv']);
                        $priv_arr[$action_id]['priv_list'] = join(',', $priv);

                        foreach ($action_group['priv'] as $key => $val) {
                            $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                        }
                    }
                }
            }

            /* 赋值 */
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['allot_priv'] . ' [ ' . $_GET['user'] . ' ] ');
            $this->smarty->assign('action_link', ['href' => 'privilege_seller.php?act=list', 'text' => $GLOBALS['_LANG']['02_admin_seller']]);
            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('form_act', 'update_allot');
            $this->smarty->assign('user_id', $_GET['id']);

            /* 显示页面 */
            return $this->smarty->display('seller_privilege_allot.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员的权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_allot') {
            admin_priv('seller_allot');

            /* 取得当前管理员用户名 */
            $admin_name = AdminUser::where('user_id', $_POST['id'])->value('user_name');
            $admin_name = $admin_name ? $admin_name : '';

            /* 更新管理员的权限 */
            $act_list = @join(",", $_POST['action_code']);
            $data = [
                'action_list' => $act_list,
                'role_id' => ''
            ];
            AdminUser::where('user_id', $_POST['id'])->update($data);
            /* 动态更新管理员的SESSION */
            if (session('admin_id') == $_POST['id']) {
                session([
                    'action_list' => $act_list
                ]);
            }

            /* 记录管理员操作 */
            admin_log(addslashes($admin_name), 'edit', 'privilege');

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege_seller.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $admin_name . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除一个管理员
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('seller_drop');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 获得管理员用户名 */
            $admin_name = AdminUser::where('user_id', $id)->value('user_name');
            $admin_name = $admin_name ? $admin_name : '';

            /* ID为1的不允许删除 */
            if ($id == 1) {
                return make_json_error($GLOBALS['_LANG']['remove_cannot']);
            }

            /* 管理员不能删除自己 */
            if ($id == session('admin_id')) {
                return make_json_error($GLOBALS['_LANG']['remove_self_cannot']);
            }

            $res = AdminUser::where('user_id', $id)->delete();
            if ($res > 0) {
                $this->sessionRepository->delete_spec_admin_session($id); // 删除session中该管理员的记录

                admin_log(addslashes($admin_name), 'remove', 'privilege');
                clear_cache_files();
            }

            $url = 'privilege_seller.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }
}
