<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\Exchange;
use App\Models\AdminAction;
use App\Models\AdminUser;
use App\Models\Agency;
use App\Models\Suppliers;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\CommonManageService;

/**
 * 记录管理员操作日志
 */
class PrivilegeSuppliersController extends InitController
{
    protected $baseRepository;
    protected $commonRepository;
    protected $commonManageService;
    protected $dscRepository;
    protected $sessionRepository;

    public function __construct(
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        CommonManageService $commonManageService,
        DscRepository $dscRepository,
        SessionRepository $sessionRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->commonManageService = $commonManageService;
        $this->dscRepository = $dscRepository;
        $this->sessionRepository = $sessionRepository;
    }

    public function index()
    {

        /* 初始化 $exc 对象 */
        $exc = new Exchange($this->dsc->table("admin_user"), $this->db, 'user_id', 'user_name');
        $this->smarty->assign('menus', session('menus', ''));
        $this->smarty->assign('action_type', "privilege");
        $adminru = get_admin_ru_id();

        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('seller', 0);

        $php_self = $this->commonManageService->getPhpSelf(1);
        $this->smarty->assign('php_self', $php_self);

        $this->smarty->assign('menu_select', array('action' => '10_priv_admin', 'current' => '02_admin_seller'));

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('suppliers_child_manage');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', lang('suppliers/privilege_suppliers.administrators_list'));
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            /*判断是否供应商,是显示添加管理员按钮*/
            if ($adminru['suppliers_id'] > 0) {
                $this->smarty->assign('action_link', array('href' => 'privilege_suppliers.php?act=add', 'text' => $GLOBALS['_LANG']['admin_add'], 'class' => 'icon-plus'));
            }

            $this->smarty->assign('suppliers_id', $adminru['suppliers_id']);
            $this->smarty->assign('full_page', 1);

            $admin_list = $this->get_admin_userlist($adminru['suppliers_id']);

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($admin_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('admin_list', $admin_list['list']);
            $this->smarty->assign('filter', $admin_list['filter']);
            $this->smarty->assign('record_count', $admin_list['record_count']);
            $this->smarty->assign('page_count', $admin_list['page_count']);

            /* 显示页面 */

            $this->smarty->assign('current', 'privilege_seller');
            return $this->smarty->display('privilege_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $check_auth = check_authz_json('suppliers_child_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $admin_list = $this->get_admin_userlist($adminru['suppliers_id']);

            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($admin_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('admin_list', $admin_list['list']);
            $this->smarty->assign('filter', $admin_list['filter']);
            $this->smarty->assign('record_count', $admin_list['record_count']);
            $this->smarty->assign('page_count', $admin_list['page_count']);
            $this->smarty->assign('current', 'privilege_seller');
            return make_json_result($this->smarty->fetch('privilege_list.dwt'), '', array('filter' => $admin_list['filter'], 'page_count' => $admin_list['page_count']));
        }

        /*------------------------------------------------------ */
        //-- 添加管理员页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('suppliers_child_manage');

            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            $this->smarty->assign('ur_here', lang('suppliers/privilege_suppliers.administrators_add'));
            $this->smarty->assign('action_link', array('href' => 'privilege_suppliers.php?act=list', 'text' => $GLOBALS['_LANG']['02_admin_seller'], 'class' => 'icon-reply'));
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');

            /* 显示页面 */

            $this->smarty->assign('current', 'privilege_seller');
            return $this->smarty->display('privilege_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加管理员的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 检查权限 */
            admin_priv('suppliers_child_manage');

            /* 判断管理员是否已经存在 */
            if (!empty($_POST['user_name'])) {
                $object = AdminUser::whereRaw(1);

                $where = [
                    'user_name' => stripslashes($_POST['user_name'])
                ];
                $is_only = $this->commonRepository->getManageIsOnly($object, $where);

                if ($is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($_POST['user_name'])), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($_POST['email'])) {
                $is_only = $exc->is_only('email', stripslashes($_POST['email']));

                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['email_exist'], stripslashes($_POST['email'])), 1);
                }
            }

            /* 获取添加日期及密码 */
            $add_time = gmtime();

            $password = md5($_POST['password']);
            $action_list = '';
            $suppliers_id = 0;
            $ru_id = 0;
            if (session('supply_id') > 0) {
                $res = AdminUser::where('user_id', session('supply_id'));
                $res = $this->baseRepository->getToArrayFirst($res);

                $action_list = $res['action_list'] ?? '';
                $suppliers_id = $res['suppliers_id'] ?? 0;
                $ru_id = $res['ru_id'];
            }

            $row = AdminUser::where('action_list', 'all');
            $row = $this->baseRepository->getToArrayFirst($row);

            $user_name = isset($_POST['user_name']) && !empty($_POST['user_name']) ? addslashes(trim($_POST['user_name'])) : '';
            $email = isset($_POST['email']) && !empty($_POST['email']) ? addslashes(trim($_POST['email'])) : '';

            $new_id = AdminUser::insertGetId([
                'user_name' => $user_name,
                'email' => $email,
                'password' => $password,
                'add_time' => $add_time,
                'nav_list' => $row['nav_list'],
                'action_list' => $action_list,
                'suppliers_id' => $suppliers_id,
                'parent_id' => session('supply_id'),
                'ru_id' => $ru_id
            ]);

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['go_allot_priv'];
            $link[0]['href'] = 'privilege_suppliers.php?act=allot&id=' . $new_id . '&user=' . $user_name . '';

            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'privilege_suppliers.php?act=add';

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'add', 'privilege');

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑管理员信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 不能编辑demo这个管理员 */
            if (session('supply_name') == 'demo') {
                $link[] = array('text' => $GLOBALS['_LANG']['back_list'], 'href' => 'privilege_suppliers.php?act=list');
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            /* 查看是否有权限编辑其他管理员的信息 */
            if (session('supply_id') != $_REQUEST['id']) {
                admin_priv('suppliers_child_manage');
            }

            /* 获取管理员信息 */
            $user_info = AdminUser::where('user_id', $id);
            $user_info = $this->baseRepository->getToArrayFirst($user_info);

            /* 取得该管理员负责的办事处名称 */
            if ($user_info && $user_info['agency_id'] > 0) {
                $agency_name = Agency::where('agency_id', $user_info['agency_id'])->value('agency_name');
                $user_info['agency_name'] = $agency_name ? $agency_name : '';
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_edit']);
            $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['02_admin_seller'], 'href' => 'privilege_suppliers.php?act=list', 'class' => 'icon-reply'));
            $this->smarty->assign('user', $user_info);

            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');


            return $this->smarty->display('privilege_info.dwt');
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

            if ($_REQUEST['act'] == 'update') {
                /* 查看是否有权限编辑其他管理员的信息 */
                if (session('supply_id') != $_REQUEST['id']) {
                    admin_priv('suppliers_child_manage');
                }
                $g_link = 'privilege_suppliers.php?act=list';
            } else {
                $admin_id = session('supply_id');
                $g_link = 'privilege_suppliers.php?act=modif';
            }

            /* 判断管理员是否已经存在 */
            if (!empty($admin_name)) {
                $object = AdminUser::whereRaw(1);

                $where = [
                    'user_name' => $admin_name,
                    'id' => [
                        'filed' => [
                            'user_id' => $admin_id
                        ],
                        'condition' => '<>'
                    ]
                ];

                $is_only = $this->commonRepository->getManageIsOnly($object, $where);
                if ($is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($admin_name)), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($admin_email)) {
                $object = AdminUser::whereRaw(1);

                $where = [
                    'email' => $admin_email,
                    'id' => [
                        'filed' => [
                            'user_id' => $admin_id
                        ],
                        'condition' => '<>'
                    ]
                ];

                $is_only = $this->commonRepository->getManageIsOnly($object, $where);

                if ($is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['email_exist'], stripslashes($admin_email)), 1);
                }
            }

            //如果要修改密码
            $pwd_modified = false;

            if (!empty($_POST['new_password'])) {
                /* 查询旧密码并与输入的旧密码比较是否相同 */
                $adminUser = AdminUser::where('user_id', $admin_id);
                $adminUser = $this->baseRepository->getToArrayFirst($adminUser);

                $old_password = isset($adminUser['password']) && $adminUser['password'] ? $adminUser['password'] : '';
                $old_ec_salt = isset($adminUser['ec_salt']) && $adminUser['ec_salt'] ? $adminUser['ec_salt'] : '';

                if (empty($old_ec_salt)) {
                    $old_ec_password = md5($_POST['old_password']);
                } else {
                    $old_ec_password = md5(md5($_POST['old_password']) . $old_ec_salt);
                }

                if ($old_password <> $old_ec_password) {
                    $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                    return sys_msg($GLOBALS['_LANG']['pwd_error'], 0, $link);
                }

                /* 比较新密码和确认密码是否相同 */
                if ($_POST['new_password'] <> $_POST['pwd_confirm']) {
                    $link[] = array('text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)');
                    return sys_msg($GLOBALS['_LANG']['js_languages']['password_error'], 0, $link);
                } else {
                    $pwd_modified = true;
                }
            }

            $other = [
                'user_name' => $admin_name,
                'email' => $admin_email,
            ];

            if ($_REQUEST['act'] != 'update' && !empty($_POST['nav_list'])) {
                $other['nav_list'] = implode(",", $_POST['nav_list']);
            }

            //更新管理员信息
            if ($pwd_modified) {
                AdminUser::where('user_id', $admin_id)->update(['login_status' => '']); // 更新改密码字段
                if (!empty($_POST['new_password'])) {
                    $other['password'] = md5(md5(trim($_POST['new_password'])) . $ec_salt);
                }

                $other['ec_salt'] = $ec_salt;
            }

            AdminUser::where('user_id', $admin_id)
                ->update($other);

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'edit', 'privilege');

            /* 如果修改了密码，则需要将session中该管理员的数据清空 */
            if ($pwd_modified && $_REQUEST['act'] == 'update_self') {
                $this->sessionRepository->delete_spec_admin_session(session('supply_id'));
                $msg = $GLOBALS['_LANG']['edit_password_succeed'];
            } else {
                $msg = $GLOBALS['_LANG']['edit_profile_succeed'];
            }

            /* 提示信息 */
            $link[] = array('text' => strpos($g_link, 'list') ? $GLOBALS['_LANG']['back_admin_list'] : $GLOBALS['_LANG']['modif_info'], 'href' => $g_link);
            return sys_msg("$msg<script>parent.document.getElementById('header-frame').contentWindow.document.location.reload();</script>", 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 为管理员分配权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'allot') {
            /* 检查权限 */
            admin_priv('suppliers_child_manage');

            $this->dscRepository->helpersLang('priv_action', 'admin');

            $user_id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);

            /* 获得该管理员的权限 */
            $priv_str = AdminUser::where('user_id', $user_id)->value('action_list');
            $priv_str = $priv_str ? $priv_str : '';

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str == 'all') {
                $link[] = array('text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege_suppliers.php?act=list');
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            $user_parent = AdminUser::where('user_id', $user_id);
            $user_parent = $this->baseRepository->getToArrayFirst($user_parent);

            if ($user_parent) {
                $parent_priv = AdminUser::where('user_id', $user_parent['parent_id']);
                $parent_priv = $this->baseRepository->getToArrayFirst($parent_priv);
                $parent_priv = $parent_priv && $parent_priv['action_list'] ? explode(',', $parent_priv['action_list']) : [];
            }

            $priv_str = $parent_priv ? $user_parent['action_list'] : '';

            /* 获取权限的分组数据 */
            $res = AdminAction::where('parent_id', 0);
            $res = $this->baseRepository->getToArrayGet($res);

            $priv_arr = [];
            if ($res) {
                foreach ($res as $key => $rows) {
                    $priv_arr[$rows['action_id']] = $rows;
                }
            }

            if ($priv_arr) {
                /* 按权限组查询底级的权限名称 */
                $result = AdminAction::whereIn('parent_id', array_keys($priv_arr))
                    ->whereIn('action_code', array_values($parent_priv));
                $result = $this->baseRepository->getToArrayGet($result);

                if ($result) {
                    foreach ($result as $key => $priv) {
                        $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
                    }
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
            $this->smarty->assign('action_link', array('href' => 'privilege_suppliers.php?act=list', 'text' => $GLOBALS['_LANG']['02_admin_seller'], 'class' => 'icon-reply'));
            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('form_act', 'update_allot');
            $this->smarty->assign('user_id', $_GET['id']);

            /* 显示页面 */

            $this->smarty->assign('current', 'privilege_seller');
            return $this->smarty->display('privilege_allot.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员的权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_allot') {
            /* 检查权限 */
            admin_priv('suppliers_child_manage');

            $user_id = request()->get('target_price', 0);
            $action_code = request()->get('action_code', []);
            /* 取得当前管理员用户名 */
            $admin_name = AdminUser::where('user_id', $user_id)->value('user_name');
            $admin_name = $admin_name ? $admin_name : '';

            /* 更新管理员的权限 */
            $act_list = $action_code ? implode(",", $action_code) : '';

            AdminUser::where('user_id', $user_id)
                ->update([
                    'action_list' => $act_list,
                    'role_id' => ''
                ]);

            /* 动态更新管理员的SESSION */
            if (session('supply_id') == $user_id) {
                session([
                    'action_list' => $act_list
                ]);
            }

            /* 记录管理员操作 */
            admin_log(addslashes($admin_name), 'edit', 'privilege');

            /* 提示信息 */
            $link[] = array('text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege_suppliers.php?act=list');
            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $admin_name . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除一个管理员
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('suppliers_child_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;

            /* 获得管理员用户名 */
            $admin_name = AdminUser::where('user_id', $id)->value('user_name');
            $admin_name = $admin_name ? $admin_name : '';

            /* ID为1的不允许删除 */
            if ($id == 1) {
                make_json_error($GLOBALS['_LANG']['remove_cannot']);
            }

            /* 管理员不能删除自己 */
            if ($id == session('supply_id')) {
                make_json_error($GLOBALS['_LANG']['remove_self_cannot']);
            }

            $res = AdminUser::where('user_id', $id)->delete();

            if ($res) {
                $this->sessionRepository->delete_spec_admin_session($id); // 删除session中该管理员的记录

                admin_log(addslashes($admin_name), 'remove', 'privilege');
                clear_cache_files();
            }

            $url = 'privilege_suppliers.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }

    /**
     * 获取管理员列表
     *
     * @param int $suppliers_id
     * @return array
     */
    private function get_admin_userlist($suppliers_id = 0)
    {
        /* 过滤信息 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['parent_id'] = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
        $filter['suppliers_id'] = $suppliers_id;
        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        $row = AdminUser::whereRaw(1);

        if ($filter['keywords']) {
            $row = $row->where('user_name', 'like', '%' . mysql_like_quote($filter['keywords']) . '%');
        }

        if ($filter['parent_id']) {
            $row = $row->where('parent_id', session('supply_id'));
        } else {
            $row = $row->where('parent_id', '>', 0);
        }

        if ($filter['suppliers_id'] > 0) {
            $row = $row->where('suppliers_id', $filter['suppliers_id']);
        }

        $res = $record_count = $row;

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        $res = $res->orderBy('user_id', 'desc');

        $start = ($filter['page'] - 1) * $filter['page_size'];
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $suppliers_name = Suppliers::where('suppliers_id', $val['suppliers_id'])->value('suppliers_name');
                $suppliers_name = $suppliers_name ? $suppliers_name : '';

                $res[$key]['suppliers_name'] = $suppliers_name;
                $res[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
                $res[$key]['last_login'] = local_date($GLOBALS['_CFG']['time_format'], $val['last_login']);
            }
        }

        $arr = [
            'list' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }
}
