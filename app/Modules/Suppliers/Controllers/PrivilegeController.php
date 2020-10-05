<?php

namespace App\Modules\Suppliers\Controllers;

use App\Libraries\CaptchaVerify;
use App\Models\AdminUser;
use App\Models\Plugins;
use App\Models\Role;
use App\Models\ShopConfig;
use App\Models\WholesaleCart;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\CommonManageService;
use Illuminate\Support\Str;

/**
 * 记录管理员操作日志
 *
 * Class PrivilegeController
 * @package App\Modules\Suppliers\Controllers
 */
class PrivilegeController extends InitController
{
    protected $baseRepository;
    protected $commonRepository;
    protected $commonManageService;
    protected $dscRepository;
    protected $sessionRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        CommonManageService $commonManageService,
        SessionRepository $sessionRepository
    )
    {
        // 验证密码路由限制1分钟3次
        if(!empty($_REQUEST['type']) && $_REQUEST['type'] == 'password'){
            $this->middleware('throttle:3');
        }
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->commonManageService = $commonManageService;
        $this->dscRepository = $dscRepository;
        $this->sessionRepository = $sessionRepository;
    }

    public function index()
    {
        $this->smarty->assign('menus', session('menus', ''));
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'login';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $adminru = get_admin_ru_id();

        if ($adminru && $adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('seller', 1);
        $php_self = $this->commonManageService->getPhpSelf(1);
        $this->smarty->assign('php_self', $php_self);

        /* ------------------------------------------------------ */
        //-- 退出登录
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'logout') {
            /* 清除cookie */
            $cookieList = [
                'ECSCP[supply_id]',
                'ECSCP[supply_pass]'
            ];

            $this->sessionRepository->destroy_cookie($cookieList);

            /* 清除session */
            $sessionList = [
                'supply_id',
                'supply_name',
                'supply_action_list',
                'supply_last_check',
                'login_hash'
            ];
            $this->sessionRepository->destroy_session($sessionList);

            $_REQUEST['act'] = 'login';
        }

        /* ------------------------------------------------------ */
        //-- 登陆界面
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'login') {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");

            $seller_login_logo = ShopConfig::where('code', 'seller_login_logo')->value('value');
            $seller_login_logo = $seller_login_logo ? strstr($seller_login_logo, "images") : '';
            $this->smarty->assign('seller_login_logo', $seller_login_logo);

            if (isset($_REQUEST['step'])) {
                if ($_REQUEST['step'] == 'captcha') {
                    $captcha_width = isset($GLOBALS['_CFG']['captcha_width']) ? $GLOBALS['_CFG']['captcha_width'] : 120;
                    $captcha_height = isset($GLOBALS['_CFG']['captcha_height']) ? $GLOBALS['_CFG']['captcha_height'] : 36;
                    $captcha_font_size = isset($GLOBALS['_CFG']['captcha_font_size']) ? $GLOBALS['_CFG']['captcha_font_size'] : 18;
                    $captcha_length = isset($GLOBALS['_CFG']['captcha_length']) ? $GLOBALS['_CFG']['captcha_length'] : 4;

                    $code_config = [
                        'imageW' => $captcha_width, //验证码图片宽度
                        'imageH' => $captcha_height, //验证码图片高度
                        'fontSize' => $captcha_font_size, //验证码字体大小
                        'length' => $captcha_length, //验证码位数
                        'useNoise' => false, //关闭验证码杂点
                    ];

                    $code_config['seKey'] = 'admin_login';
                    $img = new CaptchaVerify($code_config);
                    return $img->entry();
                }
            }

            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0) {
                $this->smarty->assign('gd_version', gd_version());
                $this->smarty->assign('random', mt_rand());
            }

            return $this->smarty->display('login.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 验证登陆信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'signin') {
            $_POST = get_request_filter($_POST, 1);

            $_POST['username'] = isset($_POST['username']) && !empty($_POST['username']) ? addslashes($_POST['username']) : '';
            $_POST['password'] = isset($_POST['password']) && !empty($_POST['password']) ? addslashes($_POST['password']) : '';
            $_POST['username'] = !empty($_POST['username']) ? str_replace(["=", " "], '', $_POST['username']) : '';
            $_POST['username'] = !empty($_POST['username']) ? $_POST['username'] : addslashes($_POST['username']);
            $username = $_POST['username'];
            $password = $_POST['password'];

            /* 检查验证码是否正确 */
            if (gd_version() > 0 && intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_ADMIN) {

                /* 检查验证码是否正确 */
                $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

                $verify = app(CaptchaVerify::class);
                if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'captcha') {
                    $captcha_code = $verify->check($captcha, 'admin_login', '', 'ajax');
                    if (!$captcha_code) {
                        return 'false';
                    } else {
                        return 'true';
                    }
                } else {
                    $captcha_code = $verify->check($captcha, 'admin_login');
                    if (!$captcha_code) {
                        return sys_msg($GLOBALS['_LANG']['captcha_error'], 1);
                    }
                }
            }

            /* 检查密码是否正确(验证码正确后才验证密码) */
            if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'password') {
                $ec_salt = AdminUser::where('user_name', $username)->value('ec_salt');
                if ($ec_salt) {

                    //限制供应商登录后台
                    $rs = AdminUser::where('user_name', $username)
                        ->where('password', md5(md5($password) . $ec_salt))
                        ->where('ru_id', '<>', 0)
                        ->where('suppliers_id', '>', 0)
                        ->count();
                } else {

                    //限制供应商登录后台
                    $rs = AdminUser::where('user_name', $username)
                        ->where('password', md5($password))
                        ->where('ru_id', '<>', 0)
                        ->where('suppliers_id', '>', 0)
                        ->count();
                }

                if ($rs) {
                    die('true');
                } else {
                    die('false');
                }
            }

            $ec_salt = AdminUser::where('user_name', $_POST['username'])->value('ec_salt');
            $ec_salt = $ec_salt ? $ec_salt : '';

            if (!empty($ec_salt)) {
                /* 检查密码是否正确 */
                $row = AdminUser::where('user_name', $username)
                    ->where('password', md5(md5($password) . $ec_salt));
            } else {
                /* 检查密码是否正确 */
                $row = AdminUser::where('user_name', $username)
                    ->where('password', md5($password));
            }

            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row) {
                // 检查是否为供货商的管理员 所属供货商是否有效
                if (!empty($row['suppliers_id'])) {
                    $supplier_is_check = suppliers_list_info(' is_check = 1 AND suppliers_id = ' . $row['suppliers_id']);
                    if (empty($supplier_is_check)) {
                        return sys_msg($GLOBALS['_LANG']['login_disable'], 1);
                    }
                }
                if ($row['ru_id'] == 0 || $row['suppliers_id'] == 0) {
                    return sys_msg(lang('suppliers/privilege.no_jurisdiction'), 1);
                }

                // 登录成功
                set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_login']);

                $this->commonManageService->updateLoginStatus(session('login_hash')); // 插入登录状态

                session([
                    'suppliers_id' => $row['suppliers_id']
                ]);

                if (empty($row['ec_salt'])) {
                    $ec_salt = rand(1, 9999);
                    $new_possword = md5(md5($_POST['password']) . $ec_salt);

                    AdminUser::where('user_id', session('supply_id'))
                        ->update([
                            'ec_salt' => $ec_salt,
                            'password' => $new_possword
                        ]);
                }

                if ($row['action_list'] == 'all' && empty($row['last_login'])) {
                    session([
                        'shop_guide' => true
                    ]);
                }

                // 更新最后登录时间和IP
                AdminUser::where('user_id', session('supply_id'))
                    ->update([
                        'last_login' => gmtime(),
                        'last_ip' => $this->dscRepository->dscIp()
                    ]);

                admin_log("", '', 'supply_login'); //记录登陆日志

                // 清除购物车中过期的数据
                $this->clear_cart();

                session([
                    'verify_time' => true
                ]);

                return redirect()->route('supplier.home');
            } else {
                return sys_msg($GLOBALS['_LANG']['login_faild'], 1);
            }
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

            if ($admin_name == lang('common.buyer') || $admin_name == lang('suppliers/privilege.seller')) {
                /* 提示信息 */
                $link[] = array('text' => lang('suppliers/privilege.invalid_name'), 'href' => "privilege.php?act=modif");
                return sys_msg(lang('suppliers/privilege.edit_fail'), 0, $link);
            }

            if ($_REQUEST['act'] == 'update') {
                /* 查看是否有权限编辑其他管理员的信息 */
                if (session('supply_id') != $_REQUEST['id']) {
                    admin_priv('admin_manage');
                }
                $g_link = 'privilege.php?act=list';
            } else {
                $admin_id = session('supply_id');
                $g_link = 'privilege.php?act=modif';
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

                $old_password = issert($adminUser['password']) && $adminUser['password'] ? $adminUser['password'] : '';
                $old_ec_salt = issert($adminUser['ec_salt']) && $adminUser['ec_salt'] ? $adminUser['ec_salt'] : '';

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

            if (isset($_POST['select_role']) && !empty($_POST['select_role'])) {
                $select_role = addslashes($_POST['select_role']);
                $row = Role::where('role_id', $select_role);
                $row = $this->baseRepository->getToArrayFirst($row);

                $other['action_list'] = $row['action_list'];
                $other['role_id'] = $select_role;
            }

            if ($_REQUEST['act'] != 'update' && !empty($_POST['nav_list'])) {
                $other['nav_list'] = implode(",", $_POST['nav_list']);
            }

            //更新管理员信息
            if ($pwd_modified) {
                if (!empty($_POST['new_password'])) {
                    $other['password'] = md5(md5(trim($_POST['new_password'])) . $ec_salt);
                }

                $other['ec_salt'] = $ec_salt;
            }

            AdminUser::where('user_id', $admin_id)
                ->update($other);

            /* 记录管理员操作 */
            admin_log($admin_name, 'edit', 'privilege');

            /* 如果修改了密码，则需要将session中该管理员的数据清空 */
            if ($pwd_modified) {

                AdminUser::where('user_id', $admin_id)->update(['login_status' => '']); // 更新改密码字段
                
                /* 清除cookie */
                $cookieList = [
                    'ECSCP[supply_id]',
                    'ECSCP[supply_pass]'
                ];

                $this->sessionRepository->destroy_cookie($cookieList);

                /* 清除session */
                $sessionList = [
                    'supply_id',
                    'supply_name',
                    'supply_action_list',
                    'supply_last_check'
                ];
                $this->sessionRepository->destroy_session($sessionList);

                $g_link = "privilege.php?act=login";

                if (config('session.driver') === 'database') {
                    $this->sessionRepository->delete_spec_admin_session(session('supply_id'));
                }

                $msg = $GLOBALS['_LANG']['edit_password_succeed'];
            } else {
                $msg = $GLOBALS['_LANG']['edit_profile_succeed'];
            }

            /* 提示信息 */
            $link[] = array('text' => strpos($g_link, 'list') ? $GLOBALS['_LANG']['back_admin_list'] : $GLOBALS['_LANG']['modif_info'], 'href' => $g_link);
            return sys_msg("$msg<script>parent.document.getElementById('header-frame').contentWindow.document.location.reload();</script>", 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑个人资料
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'modif') {
            /* 检查权限 */
            admin_priv('suppliers_privilege');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            $this->smarty->assign('menu_select', array('action' => '10_priv_admin', 'current' => 'privilege_seller'));

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : session('supply_id');

            /* 不能编辑demo这个管理员 */
            if (session('supply_name') == 'demo') {
                $link[] = array('text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege.php?act=list');
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            load_helper(['menu', 'priv'], 'suppliers');

            $modules = $GLOBALS['modules'];
            $purview = $GLOBALS['purview'];

            /* 包含插件菜单语言项 */
            $rs = Plugins::whereRaw(1);
            $rs = $this->baseRepository->getToArrayGet($rs);

            if ($rs) {
                foreach ($rs as $row) {
                    /* 取得语言项 */
                    if (file_exists(app_path('Plugins/' . Str::studly($row['code']) . '/Languages/common_' . $GLOBALS['_CFG']['lang'] . '.php'))) {
                        include_once(app_path('Plugins/' . Str::studly($row['code']) . '/Languages/common_' . $GLOBALS['_CFG']['lang'] . '.php'));
                    }

                    /* 插件的菜单项 */
                    if (file_exists(app_path('Plugins/' . Str::studly($row['code']) . '/Languages/inc_menu.php'))) {
                        include_once(app_path('Plugins/' . Str::studly($row['code']) . '/Languages/inc_menu.php'));
                    }
                }
            }

            foreach ($modules as $key => $value) {
                ksort($modules[$key]);
            }
            ksort($modules);

            foreach ($modules as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        if (isset($purview[$k]) && is_array($purview[$k])) {
                            $boole = false;
                            foreach ($purview[$k] as $action) {
                                $boole = $boole || admin_priv($action, '', false);
                            }
                            if (!$boole) {
                                unset($modules[$key][$k]);
                            }
                        } elseif (isset($purview[$k]) && !admin_priv($purview[$k], '', false)) {
                            unset($modules[$key][$k]);
                        }
                    }
                }
            }

            /* 获得当前管理员数据信息 */
            $user_info = AdminUser::where('user_id', session('supply_id'));
            $user_info = $this->baseRepository->getToArrayFirst($user_info);

            /* 获取导航条 */
            $nav_arr = (trim($user_info['nav_list']) == '') ? array() : explode(",", $user_info['nav_list']);
            $nav_lst = array();
            if ($nav_arr) {
                foreach ($nav_arr as $val) {
                    $arr = explode('|', $val);
                    $nav_lst[$arr[1]] = $arr[0];
                }
            }

            /* 模板赋值 */
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['modif_info']);

            if ($user_info['suppliers_id'] == 0) {
                $this->smarty->assign('action_link', array('text' => $GLOBALS['_LANG']['01_admin_list'], 'href' => 'privilege.php?act=list'));
            }

            $this->smarty->assign('user', $user_info);
            $this->smarty->assign('menus', $modules);
            $this->smarty->assign('nav_arr', $nav_lst);

            $this->smarty->assign('form_act', 'update_self');
            $this->smarty->assign('action', 'modif');

            /* 获得该管理员的权限 */
            $priv_str = AdminUser::where('user_id', $id);
            $priv_str = $this->baseRepository->getToArrayFirst($priv_str);

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str == 'all') {
                $this->smarty->assign('priv_str', 1);
            }

            /* 显示页面 */
            return $this->smarty->display('privilege_info.dwt');
        }
    }

    /**
     * 清除购物车中过期的数据
     */
    private function clear_cart()
    {
        /* 取得有效的session */
        $valid_sess = WholesaleCart::whereHas('getSessions');
        $valid_sess = $this->baseRepository->getToArrayGet($valid_sess);
        $valid_sess = $this->baseRepository->getKeyPluck($valid_sess, 'session_id');

        if ($valid_sess) {
            // 删除cart中无效的数据
            WholesaleCart::whereNotIn('session_id', $valid_sess)->delete();
        }
    }
}
