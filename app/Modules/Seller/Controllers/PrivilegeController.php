<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\CaptchaVerify;
use App\Libraries\Exchange;
use App\Models\AdminUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;
use Illuminate\Support\Str;

/**
 * 管理员信息以及权限管理程序
 */
class PrivilegeController extends InitController
{
    protected $baseRepository;
    protected $commonManageService;
    protected $commonRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $sessionRepository;
    protected $storeCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        CommonManageService $commonManageService,
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        SessionRepository $sessionRepository,
        StoreCommonService $storeCommonService
    )
    {
        // 验证密码路由限制1分钟3次
        if (!empty($_REQUEST['type']) && $_REQUEST['type'] == 'password') {
            $this->middleware('throttle:3');
        }
        $this->baseRepository = $baseRepository;
        $this->commonManageService = $commonManageService;
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->sessionRepository = $sessionRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        $menus = session()->has('menus') ? session('menus') : '';
        $this->smarty->assign('menus', $menus);
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'login';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* 初始化 $exc 对象 */
        $exc = new Exchange($this->dsc->table("admin_user"), $this->db, 'user_id', 'user_name');

        $adminru = get_admin_ru_id();

        //ecmoban模板堂 --zhuo start
        if (isset($adminru['ru_id']) && $adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('seller', 1);
        $php_self = $this->commonManageService->getPhpSelf(1);
        $this->smarty->assign('php_self', $php_self);
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 退出登录
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'logout') {
            /* 清除cookie */
            $cookieList = [
                'ECSCP[seller_id]',
                'ECSCP[seller_pass]'
            ];

            $this->sessionRepository->destroy_cookie($cookieList);

            /* 清除session */
            $sessionList = [
                'seller_id',
                'seller_name',
                'seller_action_list',
                'seller_last_check',
                'login_hash'
            ];
            $this->sessionRepository->destroy_session($sessionList);

            return dsc_header("Location: privilege.php?act=login\n");
        }

        /*------------------------------------------------------ */
        //-- 登陆界面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'login') {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");

            $sql = "SELECT value FROM " . $this->dsc->table('shop_config') . " WHERE code = 'seller_login_logo'";
            $seller_login_logo = strstr($this->db->getOne($sql), "images");
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

        /*------------------------------------------------------ */
        //-- 验证登陆信息
        /*------------------------------------------------------ */
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

            $ec_salt = AdminUser::where('user_name', $username)->value('ec_salt');
            $ec_salt = $ec_salt ? $ec_salt : 0;

            /* 检查密码是否正确(验证码正确后才验证密码) */
            if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'password') {
                if (!empty($ec_salt)) {
                    $count = AdminUser::where('user_name', $username)
                        ->where('password', md5(md5($password) . $ec_salt))
                        ->where('ru_id', '<>', 0)
                        ->where('suppliers_id', 0)
                        ->count();
                } else {
                    $count = AdminUser::where('user_name', $username)
                        ->where('password', md5($password))
                        ->where('ru_id', '<>', 0)
                        ->where('suppliers_id', 0)
                        ->count();
                }

                if ($count) {
                    return 'true';
                } else {
                    return 'false';
                }
            }

            if (!empty($ec_salt)) {
                /* 检查密码是否正确 */
                $row = AdminUser::where('user_name', $username)
                    ->where('password', md5(md5($password) . $ec_salt))
                    ->where('ru_id', '>', 0);
            } else {
                /* 检查密码是否正确 */
                $row = AdminUser::where('user_name', $username)
                    ->where('password', md5($password))
                    ->where('ru_id', '>', 0);
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
                if ($row['ru_id'] == 0) {
                    return sys_msg("商家后台，平台禁止入内", 1);
                }
                // 登录成功
                set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_login']);

                $this->commonManageService->updateLoginStatus(session('seller_login_hash')); // 插入登录状态

                session([
                    'suppliers_id' => $row['suppliers_id']
                ]);

                if (empty($row['ec_salt'])) {
                    $ec_salt = rand(1, 9999);
                    $new_possword = md5(md5($_POST['password']) . $ec_salt);
                    $this->db->query("UPDATE " . $this->dsc->table('admin_user') .
                        " SET ec_salt='" . $ec_salt . "', password='" . $new_possword . "'" .
                        " WHERE user_id='" . session('seller_id') . "'");
                }

                if ($row['action_list'] == 'all' && empty($row['last_login'])) {
                    session([
                        'shop_guide' => true
                    ]);
                }

                // 更新最后登录时间和IP
                $this->db->query("UPDATE " . $this->dsc->table('admin_user') .
                    " SET last_login='" . gmtime() . "', last_ip='" . $this->dscRepository->dscIp() . "'" .
                    " WHERE user_id='" . session('seller_id') . "'");

                admin_log("", '', 'admin_login');//记录登陆日志

                // 清除购物车中过期的数据
                session([
                    'verify_time' => true
                ]);

                return dsc_header("Location: index.php\n");
            } else {
                return sys_msg($GLOBALS['_LANG']['login_faild'], 1);
            }
        }

        /*------------------------------------------------------ */
        //-- 管理员列表页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_admin_list']);
            $this->smarty->assign('action_link', ['href' => 'privilege.php?act=add', 'text' => $GLOBALS['_LANG']['admin_add']]);
            $this->smarty->assign('full_page', 1);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $admin_list = $this->get_admin_userlist($adminru['ru_id']);

            $this->smarty->assign('admin_list', $admin_list['list']);
            $this->smarty->assign('filter', $admin_list['filter']);
            $this->smarty->assign('record_count', $admin_list['record_count']);
            $this->smarty->assign('page_count', $admin_list['page_count']);
            /* 显示页面 */

            return $this->smarty->display('privilege_list.htm');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $admin_list = $this->get_admin_userlist($adminru['ru_id']);

            $this->smarty->assign('admin_list', $admin_list['list']);
            $this->smarty->assign('filter', $admin_list['filter']);
            $this->smarty->assign('record_count', $admin_list['record_count']);
            $this->smarty->assign('page_count', $admin_list['page_count']);
            return make_json_result($this->smarty->fetch('privilege_list.htm'), '', ['filter' => $admin_list['filter'], 'page_count' => $admin_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加管理员页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('admin_manage');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_add']);
            $this->smarty->assign('action_link', ['href' => 'privilege.php?act=list', 'text' => $GLOBALS['_LANG']['01_admin_list']]);
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');
            $this->smarty->assign('select_role', $this->get_role_list());

            /* 显示页面 */

            return $this->smarty->display('privilege_info.htm');
        }

        /*------------------------------------------------------ */
        //-- 添加管理员的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('admin_manage');

            $_POST['user_name'] = trim($_POST['user_name']);

            if ($_POST['user_name'] == $GLOBALS['_LANG']['buyer'] || $_POST['user_name'] == $GLOBALS['_LANG']['saler']) {
                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['invalid_name_cant_use'], 'href' => "privilege.php?act=modif"];
                return sys_msg($GLOBALS['_LANG']['add_faile'], 0, $link);
            }

            /* 判断管理员是否已经存在 */
            if (!empty($_POST['user_name'])) {
                $is_only = $exc->is_only('user_name', stripslashes($_POST['user_name']));

                if (!$is_only) {
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
            $role_id = '';
            $action_list = '';
            if (!empty($_POST['select_role'])) {
                $sql = "SELECT action_list FROM " . $this->dsc->table('role') . " WHERE role_id = '" . $_POST['select_role'] . "'";
                $row = $this->db->getRow($sql);
                $action_list = $row['action_list'];
                $role_id = $_POST['select_role'];
            }

            $sql = "SELECT nav_list FROM " . $this->dsc->table('admin_user') . " WHERE action_list = 'all'";
            $row = $this->db->getRow($sql);


            $sql = "INSERT INTO " . $this->dsc->table('admin_user') . " (user_name, email, password, add_time, nav_list, action_list, role_id) " .
                "VALUES ('" . trim($_POST['user_name']) . "', '" . trim($_POST['email']) . "', '$password', '$add_time', '$row[nav_list]', '$action_list', '$role_id')";

            $this->db->query($sql);
            /* 转入权限分配列表 */
            $new_id = $this->db->insert_id();

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['go_allot_priv'];
            $link[0]['href'] = 'privilege.php?act=allot&id=' . $new_id . '&user=' . $_POST['user_name'] . '';

            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'privilege.php?act=add';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'add', 'privilege');
        }

        /*------------------------------------------------------ */
        //-- 编辑管理员信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 不能编辑demo这个管理员 */
            if (session('seller_name') == 'demo') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'privilege.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            /* 查看是否有权限编辑其他管理员的信息 */
            if (session('seller_id') != $id) {
                admin_priv('admin_manage');
            }

            /* 获取管理员信息 */
            $sql = "SELECT user_id, user_name, email, password, agency_id, role_id FROM " . $this->dsc->table('admin_user') .
                " WHERE user_id = '$id'";
            $user_info = $this->db->getRow($sql);


            /* 取得该管理员负责的办事处名称 */
            if ($user_info['agency_id'] > 0) {
                $sql = "SELECT agency_name FROM " . $this->dsc->table('agency') . " WHERE agency_id = '$user_info[agency_id]'";
                $user_info['agency_name'] = $this->db->getOne($sql);
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_admin_list'], 'href' => 'privilege.php?act=list']);
            $this->smarty->assign('user', $user_info);

            /* 获得该管理员的权限 */
            $priv_str = $this->db->getOne("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '$id'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str != 'all') {
                $this->smarty->assign('select_role', $this->get_role_list());
            }
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');


            return $this->smarty->display('privilege_info.htm');
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
            $password = !empty($_POST['new_password']) ? ", password = '" . md5(md5(trim($_POST['new_password'])) . $ec_salt) . "'" : '';

            if ($admin_name == $GLOBALS['_LANG']['buyer'] || $admin_name == $GLOBALS['_LANG']['saler']) {
                /* 提示信息 */
                $link[] = ['text' => $GLOBALS['_LANG']['invalid_name_cant_use'], 'href' => "privilege.php?act=modif"];
                return sys_msg($GLOBALS['_LANG']['edit_fail'], 0, $link);
            }

            if ($_REQUEST['act'] == 'update') {
                /* 查看是否有权限编辑其他管理员的信息 */
                if (session('seller_id') != $_REQUEST['id']) {
                    admin_priv('admin_manage');
                }
                $g_link = 'privilege.php?act=list';
                $nav_list = '';
            } else {
                $nav_list = !empty($_POST['nav_list']) ? ", nav_list = '" . @join(",", $_POST['nav_list']) . "'" : '';
                $admin_id = session('seller_id');
                $g_link = 'privilege.php?act=modif';
            }
            /* 判断管理员是否已经存在 */
            if (!empty($admin_name)) {
                $is_only = $exc->num('user_name', $admin_name, $admin_id);
                if ($is_only == 1) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($admin_name)), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($admin_email)) {
                $is_only = $exc->num('email', $admin_email, $admin_id);

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

            $role_id = '';
            $action_list = '';
            if (!empty($_POST['select_role'])) {
                $sql = "SELECT action_list FROM " . $this->dsc->table('role') . " WHERE role_id = '" . $_POST['select_role'] . "'";
                $row = $this->db->getRow($sql);
                $action_list = ', action_list = \'' . $row['action_list'] . '\'';
                $role_id = ', role_id = ' . $_POST['select_role'] . ' ';
            }

            //给商家发短信，邮件
            $sql = "SELECT ru_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '$admin_id'";
            $ru_id = $this->db->getOne($sql);

            if ($ru_id && $GLOBALS['_CFG']['sms_seller_signin'] == '1') {
                //商家名称
                $shop_name = $this->merchantCommonService->getShopName($ru_id, 1);

                $sql = " SELECT mobile, seller_email FROM " . $this->dsc->table('seller_shopinfo') . " WHERE ru_id = '$ru_id' LIMIT 1";
                $shopinfo = $this->db->getRow($sql);

                if (!empty($shopinfo['mobile'])) {
                    //短信接口
                    $smsParams = [
                        'seller_name' => htmlspecialchars($admin_name),
                        'seller_password' => htmlspecialchars(trim($_POST['new_password'])),
                        'current_admin_name' => session('seller_name'),
                        'edit_time' => local_date($GLOBALS['_CFG']['time_format'], gmtime()),
                        'shop_name' => $GLOBALS['_CFG']['shop_name'],
                        'seller_name' => $shop_name,
                        'mobile_phone' => $shopinfo['mobile']
                    ];

                    $this->commonRepository->smsSend($shopinfo['mobile'], $smsParams, 'sms_seller_signin');
                }

                /* 发送邮件 */
                $template = get_mail_template('seller_signin');
                if ($adminru['ru_id'] == 0 && $template['template_content'] != '') {
                    if ($shopinfo['seller_email'] && ($admin_name != '' || $_POST['new_password'] != '') && $shop_name != '') {
                        $this->smarty->assign('shop_name', $shop_name);
                        $this->smarty->assign('seller_name', $admin_name);
                        $this->smarty->assign('seller_psw', trim($_POST['new_password']));
                        $this->smarty->assign('site_name', $GLOBALS['_CFG']['shop_name']);
                        $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                        $content = $this->smarty->fetch('str:' . $template['template_content']);

                        $this->commonRepository->sendEmail($admin_name, $shopinfo['seller_email'], $template['template_subject'], $content, $template['is_html']);
                    }
                }
            }

            //更新管理员信息
            if ($pwd_modified) {
                $sql = "UPDATE " . $this->dsc->table('admin_user') . " SET " .
                    "user_name = '$admin_name', " .
                    "email = '$admin_email', " .
                    "ec_salt = '$ec_salt' " .
                    $action_list .
                    $role_id .
                    $password .
                    $nav_list .
                    "WHERE user_id = '$admin_id'";
            } else {
                $sql = "UPDATE " . $this->dsc->table('admin_user') . " SET " .
                    "user_name = '$admin_name', " .
                    "email = '$admin_email' " .
                    $action_list .
                    $role_id .
                    $nav_list .
                    "WHERE user_id = '$admin_id'";
            }

            $this->db->query($sql);

            $seller_shopinfo = [
                'seller_email' => $admin_email
            ];

            $this->db->autoExecute($this->dsc->table('seller_shopinfo'), $seller_shopinfo, 'UPDATE', "ru_id = '" . $adminru['ru_id'] . "'");

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'edit', 'privilege');

            /* 如果修改了密码，则需要将session中该管理员的数据清空 */
            if ($pwd_modified && $_REQUEST['act'] == 'update_self') {

                AdminUser::where('user_id', $admin_id)->update(['login_status' => '']); // 更新改密码字段

                /* 清除cookie */
                $cookieList = [
                    'ECSCP[seller_id]',
                    'ECSCP[seller_pass]'
                ];

                $this->sessionRepository->destroy_cookie($cookieList);

                /* 清除session */
                $sessionList = [
                    'seller_id',
                    'seller_name',
                    'seller_action_list',
                    'seller_last_check'
                ];
                $this->sessionRepository->destroy_session($sessionList);

                $g_link = "privilege.php?act=login";

                $msg = $GLOBALS['_LANG']['edit_password_succeed'];
            } else {
                $msg = $GLOBALS['_LANG']['edit_profile_succeed'];
            }

            /* 提示信息 */
            $link[] = ['text' => strpos($g_link, 'list') ? $GLOBALS['_LANG']['back_admin_list'] : $GLOBALS['_LANG']['return_login'], 'href' => $g_link];
            return sys_msg("$msg<script>parent.document.getElementById('header-frame').contentWindow.document.location.reload();</script>", 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑个人资料
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'modif') {
            /* 检查权限 */
            admin_priv('privilege_seller');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['modif_info']);
            $this->smarty->assign('menu_select', ['action' => '10_priv_admin', 'current' => 'privilege_seller']);
            $this->smarty->assign('action_url', 'privilege.php');

            /* 不能编辑demo这个管理员 */
            if (session('seller_name') == 'demo') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            load_helper(['menu', 'priv'], 'seller');

            $modules = $GLOBALS['modules'];
            $purview = $GLOBALS['purview'];

            /* 包含插件菜单语言项 */
            $sql = "SELECT code FROM " . $this->dsc->table('plugins');
            $rs = $this->db->query($sql);

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

            if ($modules) {
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
            }

            /* 获得当前管理员数据信息 */
            $sql = "SELECT user_id, user_name, email, nav_list, ru_id " .
                "FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('seller_id') . "'";
            $user_info = $this->db->getRow($sql);

            /* 获取导航条 */
            $nav_arr = (trim($user_info['nav_list']) == '') ? [] : explode(",", $user_info['nav_list']);
            $nav_lst = [];
            foreach ($nav_arr as $val) {
                $arr = explode('|', $val);
                $nav_lst[$arr[1]] = $arr[0];
            }

            /* 模板赋值 */
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['modif_info']);

            if ($user_info['ru_id'] == 0) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_admin_list'], 'href' => 'privilege.php?act=list']);
            }

            $this->smarty->assign('user', $user_info);
            $this->smarty->assign('menus', $modules);
            $this->smarty->assign('nav_arr', $nav_lst);

            $this->smarty->assign('form_act', 'update_self');
            $this->smarty->assign('action', 'modif');

            /* 获得该管理员的权限 ecmoban模板堂 --zhuo*/
            $seller_id = isset($_GET['id']) ? intval($_GET['id']) : session('seller_id');
            $priv_str = $this->db->getOne("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '$seller_id'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str == 'all') {
                $this->smarty->assign('priv_str', 1);
            }

            /* 显示页面 */

            return $this->smarty->display('privilege_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 为管理员分配权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'allot') {

            $this->dscRepository->helpersLang('priv_action', 'seller');

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            admin_priv('allot_priv');
            if (session('seller_id') == $id) {
                admin_priv('all');
            }

            /* 获得该管理员的权限 */
            $priv_str = $this->db->getOne("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '$id'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str == 'all') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            /* 获取权限的分组数据 */
            $sql_query = "SELECT action_id, parent_id, action_code,relevance FROM " . $this->dsc->table('admin_action') .
                " WHERE parent_id = 0";
            $res = $this->db->query($sql_query);
            foreach ($res as $rows) {
                $priv_arr[$rows['action_id']] = $rows;
            }

            if ($priv_arr) {
                /* 按权限组查询底级的权限名称 */
                $sql = "SELECT action_id, parent_id, action_code,relevance FROM " . $this->dsc->table('admin_action') .
                    " WHERE parent_id " . db_create_in(array_keys($priv_arr));
                $result = $this->db->query($sql);
                foreach ($result as $priv) {
                    $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
                }


                // 将同一组的权限使用 "," 连接起来，供JS全选 ecmoban模板堂 --zhuo
                foreach ($priv_arr as $action_id => $action_group) {
                    if ($action_group['priv']) {
                        $priv = @array_keys($action_group['priv']);
                        $priv_arr[$action_id]['priv_list'] = join(',', $priv);
                        if (!empty($action_group['priv'])) {
                            foreach ($action_group['priv'] as $key => $val) {

                                if (!empty(trim($priv_str)) && !empty($val['action_code'])) {
                                    $true = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                                } else {
                                    $true = 0;
                                }

                                $priv_arr[$action_id]['priv'][$key]['cando'] = $true;
                            }
                        }
                    }
                }
            }

            /* 赋值 */
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['allot_priv'] . ' [ ' . $_GET['user'] . ' ] ');
            $this->smarty->assign('action_link', ['href' => 'privilege.php?act=list', 'text' => $GLOBALS['_LANG']['01_admin_list']]);
            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('form_act', 'update_allot');
            $this->smarty->assign('user_id', $id);

            /* 显示页面 */

            return $this->smarty->display('privilege_allot.htm');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员的权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_allot') {
            admin_priv('admin_manage');

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 取得当前管理员用户名 */
            $admin_name = $this->db->getOne("SELECT user_name FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '$id'");

            /* 更新管理员的权限 */
            $act_list = @join(",", $_POST['action_code']);
            $sql = "UPDATE " . $this->dsc->table('admin_user') . " SET action_list = '$act_list', role_id = '' " .
                "WHERE user_id = '$id'";

            $this->db->query($sql);
            /* 动态更新管理员的SESSION */
            if (session('seller_id') == $id) {
                session([
                    'action_list' => $act_list
                ]);
            }

            /* 记录管理员操作 */
            admin_log(addslashes($admin_name), 'edit', 'privilege');

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $admin_name . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除一个管理员
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('admin_drop');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 获得管理员用户名 */
            $admin_name = $this->db->getOne('SELECT user_name FROM ' . $this->dsc->table('admin_user') . " WHERE user_id = '$id'");

            /* ID为1的不允许删除 */
            if ($id == 1) {
                return make_json_error($GLOBALS['_LANG']['remove_cannot']);
            }

            /* 管理员不能删除自己 */
            if ($id == session('seller_id')) {
                return make_json_error($GLOBALS['_LANG']['remove_self_cannot']);
            }

            if ($exc->drop($id)) {

                if (config('session.driver') === 'database') {
                    $this->sessionRepository->delete_spec_admin_session($id); // 删除session中该管理员的记录
                }

                admin_log(addslashes($admin_name), 'remove', 'privilege');
                clear_cache_files();
            }

            $url = 'privilege.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 验证用户名 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_user_name') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $user_name = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);

            if ($user_name) {
                $sql = " SELECT user_id FROM " . $this->dsc->table('admin_user') . " WHERE user_name = '$user_name' LIMIT 1";
                if ($this->db->getOne($sql)) {
                    $result['error'] = 1;
                }
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 验证密码 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_user_password') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $user_name = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
            $user_password = empty($_REQUEST['user_password']) ? '' : trim($_REQUEST['user_password']);

            $sql = "SELECT `ec_salt` FROM " . $this->dsc->table('admin_user') . " WHERE user_name = '" . $user_name . "'";
            $ec_salt = $this->db->getOne($sql);

            if (!empty($ec_salt)) {
                /* 检查密码是否正确 */
                $sql = "SELECT user_id,ru_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt" .
                    " FROM " . $this->dsc->table('admin_user') .
                    " WHERE user_name = '" . $user_name . "' AND password = '" . md5(md5($user_password) . $ec_salt) . "'";
            } else {
                /* 检查密码是否正确 */
                $sql = "SELECT user_id,ru_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt" .
                    " FROM " . $this->dsc->table('admin_user') .
                    " WHERE user_name = '" . $user_name . "' AND password = '" . md5($user_password) . "'";
            }

            $row = $this->db->getRow($sql);

            if ($row) {
                $result['error'] = 1;
            }

            return response()->json($result);
        }
    }

    /* 获取管理员列表 */
    private function get_admin_userlist($ru_id)
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤信息 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);

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

            $where = '';
            if ($filter['keywords']) {
                $where .= " AND (user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%')";
            }

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] != 0) {
                if ($ru_id == 0) {
                    $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                    if ($store_type) {
                        $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                    }

                    if ($filter['store_search'] == 1) {
                        $where .= " AND au.ru_id = '" . $filter['merchant_id'] . "' ";
                    } elseif ($filter['store_search'] == 2) {
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    } elseif ($filter['store_search'] == 3) {
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if ($filter['store_search'] > 1) {
                        $where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                            " WHERE msi.user_id = au.ru_id $store_where) > 0 ";
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            /* 记录总数 */
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('admin_user') . " AS au " . " WHERE 1 AND parent_id = 0 $where";
            $record_count = $this->db->getOne($sql);

            $filter['record_count'] = $record_count;
            $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

            $sql = 'SELECT user_id, user_name, au.ru_id, email, add_time, last_login ' .
                'FROM ' . $this->dsc->table('admin_user') . ' AS au ' .
                'WHERE 1 AND parent_id = 0 ' . $where . ' ORDER BY user_id DESC ' .
                "LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $list = $this->db->getAll($sql);

        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['ru_name'] = $this->merchantCommonService->getShopName($val['ru_id'], 1);
                $list[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
                $list[$key]['last_login'] = local_date($GLOBALS['_CFG']['time_format'], $val['last_login']);
            }
        }

        $arr = ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /* 清除购物车中过期的数据 */
    private function clear_cart()
    {
        /* 取得有效的session */
        $sql = "SELECT DISTINCT session_id " .
            "FROM " . $this->dsc->table('cart') . " AS c, " .
            $this->dsc->table('sessions') . " AS s " .
            "WHERE c.session_id = s.sesskey ";
        $valid_sess = $this->db->getCol($sql);

        // 删除cart中无效的数据
        $sql = "DELETE FROM " . $this->dsc->table('cart') .
            " WHERE session_id NOT " . db_create_in($valid_sess);
        $this->db->query($sql);
        // 删除cart_combo中无效的数据 by mike
        $sql = "DELETE FROM " . $this->dsc->table('cart_combo') .
            " WHERE session_id NOT " . db_create_in($valid_sess);
        $this->db->query($sql);
    }

    /* 获取角色列表 */
    private function get_role_list()
    {
        $list = [];
        $sql = 'SELECT role_id, role_name, action_list ' .
            'FROM ' . $this->dsc->table('role');
        $list = $this->db->getAll($sql);
        return $list;
    }
}
