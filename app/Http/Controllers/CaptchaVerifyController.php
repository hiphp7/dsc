<?php

namespace App\Http\Controllers;

use App\Libraries\CaptchaVerify;

/**
 * 生成验证码
 */
class CaptchaVerifyController extends InitController
{
    protected $config;

    public function __construct()
    {
        $this->config = $this->config();
    }

    public function index()
    {

        $captcha_width = $this->config['captcha_width'];
        $captcha_height = $this->config['captcha_height'];
        $captcha_font_size = $this->config['captcha_font_size'];
        $captcha_length = $this->config['captcha_length'];

        if (isset($_REQUEST['width'])) {
            $captcha_width = $_REQUEST['width'];
        }
        if (isset($_REQUEST['height'])) {
            $captcha_height = $_REQUEST['height'];
        }
        if (isset($_REQUEST['font_size'])) {
            $captcha_font_size = $_REQUEST['font_size'];
        }
        if (isset($_REQUEST['length'])) {
            $captcha_length = $_REQUEST['length'];
        }

        $code_config = [
            'imageW' => $captcha_width,    //验证码图片宽度
            'imageH' => $captcha_height,    //验证码图片高度
            'fontSize' => $captcha_font_size,     //验证码字体大小
            'length' => $captcha_length,      //验证码位数
            'useNoise' => false,  //关闭验证码杂点
        ];

        if (isset($_REQUEST['captcha'])) {
            if ($_REQUEST['captcha'] == 'is_common') {
                $code_config['seKey'] = 'captcha_common'; //验证码通用
            } elseif ($_REQUEST['captcha'] == 'is_login') { //登录
                $code_config['seKey'] = 'captcha_login';
            } elseif ($_REQUEST['captcha'] == 'is_register_email') { //注册-邮箱方式
                $code_config['seKey'] = 'register_email';
            } elseif ($_REQUEST['captcha'] == 'is_register_phone') { //注册-手机方式
                $code_config['seKey'] = 'mobile_phone';
            } elseif ($_REQUEST['captcha'] == 'is_discuss') { //网友讨论圈
                $code_config['seKey'] = 'captcha_discuss';
            } elseif ($_REQUEST['captcha'] == 'is_user_comment') { //晒单
                $code_config['seKey'] = 'user_comment';
            } elseif ($_REQUEST['captcha'] == 'is_get_password') { //忘记密码邮箱找回密码
                $code_config['seKey'] = 'get_password';
            } elseif ($_REQUEST['captcha'] == 'is_get_phone_password') { //手机找回密码
                $code_config['seKey'] = 'get_phone_password';
            } elseif ($_REQUEST['captcha'] == 'get_pwd_question') { //问题找回密码
                $code_config['seKey'] = 'psw_question';
            } elseif ($_REQUEST['captcha'] == 'is_bonus') { //红包
                $code_config['seKey'] = 'bonus';
            } elseif ($_REQUEST['captcha'] == 'is_value_card') { //储值卡
                $code_config['seKey'] = 'value_card';
            } elseif ($_REQUEST['captcha'] == 'is_pay_card') { //充值卡
                $code_config['seKey'] = 'pay_card';
            } elseif ($_REQUEST['captcha'] == 'admin_login') { //后台登陆
                $code_config['seKey'] = 'admin_login';
            } elseif ($_REQUEST['captcha'] == 'change_password_s') { // 用户中心 修改登录密码 second
                $code_config['seKey'] = 'change_password_s';
            } elseif ($_REQUEST['captcha'] == 'change_password_f') { // 用户中心 修改登录密码 second
                $code_config['seKey'] = 'change_password_f';
            }
        }

        $identify = intval(request()->input('identify', ''));

        $img = new CaptchaVerify($code_config);
        $img->codeSet = '0123456789';
        $img->entry($identify);
    }
}
