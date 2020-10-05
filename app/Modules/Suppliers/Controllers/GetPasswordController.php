<?php

namespace App\Modules\Suppliers\Controllers;

use App\Models\AdminUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;

/**
 * 记录管理员操作日志
 */
class GetPasswordController extends InitController
{
    protected $config;
    protected $baseRepository;
    protected $commonRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    public function index()
    {
        $seller_login_logo = strstr($this->config['seller_login_logo'], "images");
        $this->smarty->assign('seller_login_logo', $seller_login_logo);

        /* 操作项的初始化 */
        if (!request()->server('REQUEST_METHOD')) {
            $method = 'GET';
        } else {
            $method = trim(request()->server('REQUEST_METHOD'));
        }

        $adminru = get_admin_ru_id();

        /*------------------------------------------------------ */
        //-- 填写管理员帐号和email页面
        /*------------------------------------------------------ */
        if ($method == 'GET') {
            //验证从邮件地址过来的链接
            if (!empty($_GET['act']) && $_GET['act'] == 'reset_pwd') {
                $code = !empty($_GET['code']) ? trim($_GET['code']) : '';
                $adminid = !empty($_GET['uid']) ? intval($_GET['uid']) : 0;

                /* 以用户的原密码，与code的值匹配 */
                $admin_info = AdminUser::where('user_id', $adminid);
                $admin_info = $this->baseRepository->getToArrayFirst($admin_info);

                if (empty($admin_info) || $adminid == 0 || empty($code)) {
                    return dsc_header("Location: privilege.php?act=login\n");
                }

                if (md5($adminid . $admin_info['password'] . $admin_info['add_time']) != $code) {
                    //此链接不合法
                    $link[0]['text'] = lang('common.back');
                    $link[0]['href'] = 'privilege.php?act=login';

                    return sys_msg(lang('admin/get_password.code_param_error'), 0, $link);
                } else {
                    $this->smarty->assign('adminid', $adminid);
                    $this->smarty->assign('code', $code);
                    $this->smarty->assign('form_act', 'reset_pwd');
                }
            } elseif (!empty($_GET['act']) && $_GET['act'] == 'forget_pwd') {
                $this->smarty->assign('form_act', 'forget_pwd');

                $_lang = array_merge($GLOBALS['_LANG'], lang('admin/get_password'));
                $this->smarty->assign('lang', $_lang);
            }

            $this->smarty->assign('ur_here', lang('admin/get_password.get_newpassword'));

            return $this->smarty->display('login.dwt');
        }

        /*------------------------------------------------------ */
        //-- 验证管理员帐号和email, 发送邮件
        /*------------------------------------------------------ */
        else {
            /* 发送找回密码确认邮件 */
            if (!empty($_POST['action']) && $_POST['action'] == 'get_pwd') {
                $admin_username = !empty($_POST['user_name']) ? trim($_POST['user_name']) : '';
                $admin_email = !empty($_POST['email']) ? trim($_POST['email']) : '';

                if (empty($admin_username) || empty($admin_email)) {
                    return dsc_header("Location: privilege.php?act=login\n");
                }

                /* 管理员用户名和邮件地址是否匹配，并取得原密码 */
                $admin_info = AdminUser::where('user_name', $admin_username)
                    ->where('email', $admin_email);
                $admin_info = $this->baseRepository->getToArrayFirst($admin_info);

                if (empty($admin_info)) {
                    return sys_msg($GLOBALS['_LANG']['user_not_isset'], 1);
                }

                $suppliers_info = get_suppliers_info($admin_info['suppliers_id'], array('user_id', 'email'));

                $seller_email = !empty($suppliers_info['email']) ? $suppliers_info['email'] : '';

                if ($admin_info) {
                    $admin_info['seller_email'] = $seller_email;
                }

                if (!empty($admin_info)) {
                    /* 生成验证的code */
                    $admin_id = $admin_info['user_id'];
                    $code = md5($admin_id . $admin_info['password'] . $admin_info['add_time']);

                    /* 设置重置邮件模板所需要的内容信息 */
                    $template = get_mail_template('send_password');

                    $reset_email = $this->dscRepository->dscUrl(SUPPLLY_PATH . '/get_password.php?act=reset_pwd&uid=' . $admin_id . '&code=' . $code);

                    $this->smarty->assign('user_name', $admin_username);
                    $this->smarty->assign('reset_email', $reset_email);
                    $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                    $content = $this->smarty->fetch('str:' . $template['template_content']);

                    /* 发送确认重置密码的确认邮件 */
                    if ($this->commonRepository->sendEmail($admin_username, $admin_email, $template['template_subject'], $content, $template['is_html'])) {
                        //提示信息
                        $link[0]['text'] = lang('common.back');
                        $link[0]['href'] = 'privilege.php?act=login';

                        return sys_msg(lang('admin/get_password.send_success') . $admin_email, 0, $link);
                    } else {
                        return sys_msg(lang('admin/get_password.send_mail_error'), 1);
                    }
                } else {
                    /* 提示信息 */
                    return sys_msg(lang('admin/get_password.email_username_error'), 1);
                }
            } /* 验证新密码，更新管理员密码 */
            elseif (!empty($_POST['action']) && $_POST['action'] == 'reset_pwd') {
                $new_password = isset($_POST['password']) ? trim($_POST['password']) : '';
                $adminid = isset($_POST['adminid']) ? intval($_POST['adminid']) : 0;
                $code = isset($_POST['code']) ? trim($_POST['code']) : '';

                if (empty($new_password) || empty($code) || $adminid == 0) {
                    return dsc_header("Location: privilege.php?act=login\n");
                }

                /* 以用户的原密码，与code的值匹配 */
                $admin_info = AdminUser::where('user_id', $adminid);
                $admin_info = $this->baseRepository->getToArrayFirst($admin_info);

                if (md5($adminid . $admin_info['password'] . $admin_info['add_time']) != $code) {
                    //此链接不合法
                    $link[0]['text'] = lang('common.back');
                    $link[0]['href'] = 'privilege.php?act=login';

                    return sys_msg(lang('admin/get_password.code_param_error'), 0, $link);
                }

                //更新管理员的密码
                $ec_salt = rand(1, 9999);
                AdminUser::where('user_id', $adminid)
                    ->update([
                        'password' => md5(md5($new_password) . $ec_salt),
                        'ec_salt' => $ec_salt
                    ]);

                $link[0]['text'] = lang('common.login_now');
                $link[0]['href'] = 'privilege.php?act=login';

                return sys_msg(lang('admin/get_password.update_pwd_success'), 0, $link);
            }
        }
    }
}
