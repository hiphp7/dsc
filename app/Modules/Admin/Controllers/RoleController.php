<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AdminAction;
use App\Models\AdminUser;
use App\Models\Role;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Role\RoleManageService;

/**
 * 角色管理信息以及权限管理程序
 */
class RoleController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;
    protected $roleManageService;
    protected $sessionRepository;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        RoleManageService $roleManageService,
        SessionRepository $sessionRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->roleManageService = $roleManageService;
        $this->sessionRepository = $sessionRepository;
    }

    public function index()
    {

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'login';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 退出登录
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'logout') {
            /* 清除cookie */
            $list = [
                'ECSCP[admin_id]',
                'ECSCP[admin_pass]'
            ];

            $this->sessionRepository->deleteCookie($list);

            session()->flush();

            $_REQUEST['act'] = 'login';
        }

        /*------------------------------------------------------ */
        //-- 登陆界面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'login') {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");

            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0) {
                $this->smarty->assign('gd_version', gd_version());
                $this->smarty->assign('random', mt_rand());
            }

            return $this->smarty->display('login.htm');
        }


        /*------------------------------------------------------ */
        //-- 角色列表页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_list']);
            $this->smarty->assign('action_link', ['href' => 'role.php?act=add', 'text' => $GLOBALS['_LANG']['admin_add_role']]);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('admin_list', $this->roleManageService->getRoleList());

            /* 显示页面 */
            return $this->smarty->display('role_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $this->smarty->assign('admin_list', $this->roleManageService->getRoleList());

            return make_json_result($this->smarty->fetch('role_list.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加角色页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('admin_manage');

            $this->dscRepository->helpersLang('priv_action', 'admin');

            $priv_str = '';

            /* 获取权限的分组数据 */
            $res = AdminAction::where('parent_id', 0);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $rows) {
                $priv_arr[$rows['action_id']] = $rows;
            }

            if ($priv_arr) {
                /* 按权限组查询底级的权限名称 */
                $res = AdminAction::whereIn('parent_id', array_keys($priv_arr));
                $result = $this->baseRepository->getToArrayGet($res);

                foreach ($result as $priv) {
                    $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
                }

                // 将同一组的权限使用 "," 连接起来，供JS全选
                foreach ($priv_arr as $action_id => $action_group) {
                    if (isset($action_group['priv']) && $action_group['priv']) {
                        $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

                        foreach ($action_group['priv'] as $key => $val) {
                            $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                        }
                    }
                }
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_add_role']);
            $this->smarty->assign('action_link', ['href' => 'role.php?act=list', 'text' => $GLOBALS['_LANG']['admin_list_role']]);
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('priv_arr', $priv_arr);

            /* 显示页面 */

            return $this->smarty->display('role_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加角色的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('admin_manage');
            $user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
            $action_code = isset($_POST['action_code']) ? $_POST['action_code'] : [];
            $role_describe = isset($_POST['role_describe']) ? trim($_POST['role_describe']) : '';

            /* 转入权限分配列表 */
            $act_list = @join(",", $action_code);
            $data = [
                'role_name' => $user_name,
                'action_list' => $act_list,
                'role_describe' => $role_describe
            ];
            Role::insert($data);

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['admin_list_role'];
            $link[0]['href'] = 'role.php?act=list';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $user_name . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'add', 'role');
        }

        /*------------------------------------------------------ */
        //-- 编辑角色信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {

            $this->dscRepository->helpersLang('priv_action', 'admin');

            $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 获得该管理员的权限 */
            $priv_str = Role::where('role_id', $id)->value('action_list');
            $priv_str = $priv_str ? $priv_str : '';

            /* 查看是否有权限编辑其他管理员的信息 */
            if (session('admin_id') != $id) {
                admin_priv('admin_manage');
            }

            /* 获取角色信息 */
            $res = Role::where('role_id', $id);
            $user_info = $this->baseRepository->getToArrayFirst($res);

            /* 获取权限的分组数据 */
            $res = AdminAction::where('parent_id', 0);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $rows) {
                $priv_arr[$rows['action_id']] = $rows;
            }

            /* 按权限组查询底级的权限名称 */
            $res = AdminAction::whereIn('parent_id', array_keys($priv_arr));
            $result = $this->baseRepository->getToArrayGet($res);

            foreach ($result as $priv) {
                $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
            }

            // 将同一组的权限使用 "," 连接起来，供JS全选
            foreach ($priv_arr as $action_id => $action_group) {
                // by kong 避免$action_group['priv'] 不存在的时候出现警告
                if (isset($action_group['priv']) && is_array($action_group['priv'])) {
                    $action_group['priv'] = $action_group['priv'];
                } else {
                    $action_group['priv'] = [];
                }
                $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));
                if (!empty($action_group['priv'])) {
                    foreach ($action_group['priv'] as $key => $val) {
                        $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                    }
                }
            }

            /* 模板赋值 */

            $this->smarty->assign('user', $user_info);
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_edit_role']);
            $this->smarty->assign('action_link', ['href' => 'role.php?act=list', 'text' => $GLOBALS['_LANG']['admin_list_role']]);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('user_id', $id);

            return $this->smarty->display('role_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新角色信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 更新管理员的权限 */
            $user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
            $action_code = isset($_POST['action_code']) ? $_POST['action_code'] : [];
            $role_describe = isset($_POST['role_describe']) ? trim($_POST['role_describe']) : '';
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            $act_list = @join(",", $action_code);

            $data = [
                'role_name' => $user_name,
                'action_list' => $act_list,
                'role_describe' => $role_describe
            ];
            Role::where('role_id', $id)->update($data);

            $data = ['action_list' => $act_list];
            AdminUser::where('role_id', $id)->update($data);

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'role.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $user_name . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除一个角色
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('admin_drop');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $remove_num = AdminUser::where('role_id', $id)->count();

            if ($remove_num > 0) {
                return make_json_error($GLOBALS['_LANG']['remove_cannot_user']);
            } else {
                Role::where('role_id', $id)->delete();
                $url = 'role.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            }

            return dsc_header("Location: $url\n");
        }
    }
}
