<?php

namespace App\Modules\Admin\Controllers;

use App\Models\ShopConfig;
use App\Repositories\Common\BaseRepository;

/**
 * 验证码管理
 */
class CaptchaManageController extends InitController
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        /* 检查权限 */
        admin_priv('shop_config');

        /*------------------------------------------------------ */
        //-- 验证码设置
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'main') {
            if (gd_version() == 0) {
                return sys_msg($GLOBALS['_LANG']['captcha_note'], 1);
            }


            $captcha = intval($GLOBALS['_CFG']['captcha']);

            $captcha_check = [];
            if ($captcha & CAPTCHA_REGISTER) {
                $captcha_check['register'] = 'checked="checked"';
            }
            if ($captcha & CAPTCHA_LOGIN) {
                $captcha_check['login'] = 'checked="checked"';
            }
            if ($captcha & CAPTCHA_COMMENT) {
                $captcha_check['comment'] = 'checked="checked"';
            }
            if ($captcha & CAPTCHA_ADMIN) {
                $captcha_check['admin'] = 'checked="checked"';
            }
            if ($captcha & CAPTCHA_MESSAGE) {
                $captcha_check['message'] = 'checked="checked"';
            }
            if ($captcha & CAPTCHA_LOGIN_FAIL) {
                $captcha_check['login_fail_yes'] = 'checked="checked"';
            } else {
                $captcha_check['login_fail_no'] = 'checked="checked"';
            }
            if ($captcha & CAPTCHA_SAFETY) {
                $captcha_check['safety'] = 'checked="checked"';
            }

            //验证码数组 start
            $code_config = [
                'captcha_width' => $GLOBALS['_CFG']['captcha_width'],    //验证码图片宽度
                'captcha_height' => $GLOBALS['_CFG']['captcha_height'],    //验证码图片高度
                'captcha_font_size' => $GLOBALS['_CFG']['captcha_font_size'],     //验证码字体大小
                'captcha_length' => $GLOBALS['_CFG']['captcha_length']      //验证码位数
            ];

            $this->smarty->assign('code_config', $code_config);

            $codeConfig = [
                'width' => 126,    //验证码图片宽度
                'height' => 41,    //验证码图片高度
                'font_size' => 18,     //验证码字体大小
                'length' => 4      //验证码位数
            ];
            $this->smarty->assign('codeConfig', $codeConfig);
            //验证码数组 end

            $this->smarty->assign('captcha', $captcha_check);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['captcha_manage']);
            return $this->smarty->display('captcha_manage.dwt');
        }

        /*------------------------------------------------------ */
        //-- 保存设置
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'save_config') {
            $captcha = 0;
            $captcha = empty($_POST['captcha_register']) ? $captcha : $captcha | CAPTCHA_REGISTER;
            $captcha = empty($_POST['captcha_login']) ? $captcha : $captcha | CAPTCHA_LOGIN;
            $captcha = empty($_POST['captcha_comment']) ? $captcha : $captcha | CAPTCHA_COMMENT;
            $captcha = empty($_POST['captcha_tag']) ? $captcha : $captcha | CAPTCHA_TAG;
            $captcha = empty($_POST['captcha_admin']) ? $captcha : $captcha | CAPTCHA_ADMIN;
            $captcha = empty($_POST['captcha_login_fail']) ? $captcha : $captcha | CAPTCHA_LOGIN_FAIL;
            $captcha = empty($_POST['captcha_message']) ? $captcha : $captcha | CAPTCHA_MESSAGE;
            $captcha = empty($_POST['captcha_safety']) ? $captcha : $captcha | CAPTCHA_SAFETY;

            $captcha_width = empty($_POST['captcha_width']) ? 126 : intval($_POST['captcha_width']);
            $captcha_height = empty($_POST['captcha_height']) ? 41 : intval($_POST['captcha_height']);
            $captcha_font_size = empty($_POST['captcha_font_size']) ? 18 : intval($_POST['captcha_font_size']);
            $captcha_length = !empty($_POST['captcha_length']) ? intval($_POST['captcha_length']) : 4;

            $arr = [
                'captcha' => $captcha,
                'captcha_width' => $captcha_width,
                'captcha_height' => $captcha_height,
                'captcha_font_size' => $captcha_font_size,
                'captcha_length' => $captcha_length
            ];

            foreach ($arr as $key => $val) {
                ShopConfig::where('code', $key)
                    ->update([
                        'value' => $val
                    ]);
            }

            /* 清除系统设置 */
            $list = [
                'shop_config'
            ];

            $this->baseRepository->getCacheForgetlist($list);

            return sys_msg($GLOBALS['_LANG']['save_ok'], 0, [['href' => 'captcha_manage.php?act=main', 'text' => $GLOBALS['_LANG']['captcha_manage']]]);
        }
    }
}
