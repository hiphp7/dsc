<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AdminUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;

/**
 * 找回管理员密码
 */
class GetPasswordController extends InitController
{
    protected $baseRepository;
    protected $commonRepository;

    public function __construct(
        BaseRepository $baseRepository,
        CommonRepository $commonRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {

        /* 操作项的初始化 */
        if (!request()->server('REQUEST_METHOD')) {
            $method = 'GET';
        } else {
            $method = trim(request()->server('REQUEST_METHOD'));
        }

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
                    $link[0]['text'] = $GLOBALS['_LANG']['back'];
                    $link[0]['href'] = 'privilege.php?act=login';

                    return sys_msg($GLOBALS['_LANG']['code_param_error'], 0, $link);
                } else {
                    $this->smarty->assign('adminid', $adminid);
                    $this->smarty->assign('code', $code);
                    $this->smarty->assign('form_act', 'reset_pwd');
                }
            } elseif (!empty($_GET['act']) && $_GET['act'] == 'forget_pwd') {
                $this->smarty->assign('form_act', 'forget_pwd');
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['get_newpassword']);


            return $this->smarty->display('get_pwd.dwt');
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

                if (!empty($admin_info)) {
                    /* 生成验证的code */
                    $admin_id = $admin_info['user_id'];
                    $code = md5($admin_id . $admin_info['password'] . $admin_info['add_time']);

                    /* 设置重置邮件模板所需要的内容信息 */
                    $template = get_mail_template('send_password');
                    $reset_email = $this->dsc->url() . ADMIN_PATH . '/get_password.php?act=reset_pwd&uid=' . $admin_id . '&code=' . $code;

                    $this->smarty->assign('user_name', $admin_username);
                    $this->smarty->assign('reset_email', $reset_email);
                    $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                    $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                    $this->smarty->assign('sent_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                    $content = $this->smarty->fetch('str:' . $template['template_content']);

                    /* 发送确认重置密码的确认邮件 */
                    if ($this->commonRepository->sendEmail($admin_username, $admin_email, $template['template_subject'], $content, $template['is_html'])) {
                        //提示信息
                        $link[0]['text'] = $GLOBALS['_LANG']['back'];
                        $link[0]['href'] = 'privilege.php?act=login';

                        return sys_msg($GLOBALS['_LANG']['send_success'] . $admin_email, 0, $link);
                    } else {
                        return sys_msg($GLOBALS['_LANG']['send_mail_error'], 1);
                    }
                } else {
                    /* 提示信息 */
                    return sys_msg($GLOBALS['_LANG']['email_username_error'], 1);
                }
            } /* 验证新密码，更新管理员密码 */
            elseif (!empty($_POST['action']) && $_POST['action'] == 'reset_pwd') {
                $new_password = isset($_POST['password']) ? trim($_POST['password']) : '';
                $adminid = isset($_POST['adminid']) ? intval($_POST['adminid']) : 0;
                $code = isset($_POST['code']) ? trim($_POST['code']) : '';

                /* 以用户的原密码，与code的值匹配 */
                $admin_info = AdminUser::where('user_id', $adminid);
                $admin_info = $this->baseRepository->getToArrayFirst($admin_info);

                if (empty($admin_info) || empty($new_password) || empty($code) || $adminid == 0) {
                    return dsc_header("Location: privilege.php?act=login\n");
                }

                if (md5($adminid . $admin_info['password'] . $admin_info['add_time']) != $code) {
                    //此链接不合法
                    $link[0]['text'] = $GLOBALS['_LANG']['back'];
                    $link[0]['href'] = 'privilege.php?act=login';

                    return sys_msg($GLOBALS['_LANG']['code_param_error'], 0, $link);
                }

                //更新管理员的密码
                $ec_salt = rand(1, 9999);
                $result = AdminUser::where('user_id', $adminid)->update([
                    'password' => md5(md5($new_password) . $ec_salt),
                    'ec_salt' => $ec_salt
                ]);

                if ($result > 0) {
                    $link[0]['text'] = $GLOBALS['_LANG']['login_now'];
                    $link[0]['href'] = 'privilege.php?act=login';

                    return sys_msg($GLOBALS['_LANG']['update_pwd_success'], 0, $link);
                } else {
                    return sys_msg($GLOBALS['_LANG']['update_pwd_failed'], 1);
                }
            }
        }
    }
}
