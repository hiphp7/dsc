<?php

namespace App\Http\Controllers;

use App\Libraries\CaptchaVerify;
use App\Models\Users;
use App\Repositories\Common\CommonRepository;

/**
 * 编辑器
 */
class SmsController extends InitController
{
    protected $commonRepository;

    public function __construct(
        CommonRepository $commonRepository
    )
    {
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {
        $user_id = session('user_id', 0);

        $mobile = request()->input('mobile', '');
        $mobile = $mobile ? (int)$mobile : '';
        $mobile_code = request()->input('mobile_code', '');
        $mobile_code = $mobile_code ? (int)$mobile_code : '';
        $security_code = request()->input('seccode', '');
        $security_code = $security_code ? (int)$security_code : '';

        $username = addslashes(trim(request()->input('username', '')));
        $send_time = addslashes(trim(request()->input('sms_value', 'sms_signin')));

        $flag = htmlspecialchars(request()->input('flag', ''));
        $act = addslashes(trim(request()->input('act', '')));

        if ($act == 'check') {
            if ((session()->has('sms_mobile') && $mobile != session('sms_mobile')) || (session()->has('sms_mobile_code') && $mobile_code != session('sms_mobile_code'))) {
                return response()->json([
                    'msg' => lang('sms.json_msg.identify_error')
                ]);
            } else {
                return response()->json([
                    'code' => '2'
                ]);
            }
        }

        if ($act == 'send') {
            $send_result = false;
            if (!empty($username)) {
                if ($GLOBALS['_CFG']['sms_type'] == 1) {
                    $is_null = $this->get_send_sms_keyval($GLOBALS['_CFG']['ali_appkey'], $GLOBALS['_CFG']['ali_secretkey']);
                } elseif ($GLOBALS['_CFG']['sms_type'] == 2) {
                    $is_null = $this->get_send_sms_keyval($GLOBALS['_CFG']['access_key_id'], $GLOBALS['_CFG']['access_key_secret']);
                } elseif ($GLOBALS['_CFG']['sms_type'] == 3) {
                    $is_null = $this->get_send_sms_keyval($GLOBALS['_CFG']['dsc_appkey'], $GLOBALS['_CFG']['dsc_appsecret']);
                } else {
                    $is_null = $this->get_send_sms_keyval($GLOBALS['_CFG']['sms_ecmoban_user'], $GLOBALS['_CFG']['sms_ecmoban_password']);
                }

                if ($is_null) {
                    return response()->json([
                        'msg' => lang('sms.json_msg.sms_allocation_error')
                    ]);
                }

                if (empty($mobile)) {
                    return response()->json([
                        'msg' => lang('sms.json_msg.phone_not_null')
                    ]);
                }

                $preg = '/^(1[3-9])\d{9}$/'; //简单的方法
                if (!preg_match($preg, $mobile)) {
                    return response()->json([
                        'msg' => lang('sms.json_msg.phone_not_true')
                    ]);
                }

                if (($flag == 'register' && (intval($GLOBALS['_CFG']['captcha']) &
                            CAPTCHA_REGISTER) && gd_version() > 0) || request()->exists('captcha') || ($flag == 'change_password_f' && (intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_SAFETY) && gd_version() > 0)) {
                    $captcha = addslashes(trim(request()->input('captcha', '')));
                    $seKey = addslashes(trim(request()->input('sekey', 'mobile_phone')));

                    if (empty($captcha)) {
                        return response()->json([
                            'msg' => lang('sms.json_msg.identify_not_null')
                        ]);
                    }

                    $captcha_code = app(CaptchaVerify::class)->check($captcha, $seKey, '', 'ajax');
                    if (!$captcha_code) {
                        return response()->json([
                            'msg' => lang('sms.json_msg.identify_has_error')
                        ]);
                    }
                } else {
                    if (!session()->has('sms_security_code') || session('sms_security_code') != $security_code) {
                        return response()->json([
                            'msg' => 'you are lost.'
                        ]);
                    }
                }

                if (session()->has('sms_mobile') && session('sms_mobile')) {
                    if (local_strtotime($this->read_file($mobile)) > (gmtime() - 60)) {
                        return response()->json([
                            'msg' => lang('sms.json_msg.code_not_noeminute')
                        ]);
                    }
                }

                $row = Users::where('mobile_phone', $mobile);

                if ($user_id > 0) {
                    $row = $row->where('user_id', '<>', $user_id);
                }

                $count = $row->count();

                if (request()->exists('flag')) {
                    if ($flag == 'register' || $flag == 'change_mobile') {
                        //手机注册
                        if ($count > 0) {
                            return response()->json([
                                'msg' => lang('sms.json_msg.phone_ishas')
                            ]);
                        }
                    } elseif ($flag == 'forget') {
                        //找回密码
                        if ($count == 0) {
                            return response()->json([
                                'msg' => lang('sms.json_msg.phone_not_has')
                            ]);
                        }
                    }
                }

                $mobile_code = $this->random(6, 1);

                $smsParams = [
                    'mobile_phone' => $mobile,
                    'mobilephone' => $mobile,
                    'code' => $mobile_code,
                    'product' => $username
                ];

                $send_result = $this->commonRepository->smsSend($mobile, $smsParams, $send_time);
            }

            if ($send_result === true) {
                $sms_security_code = rand(1000, 9999);
                session([
                    'sms_mobile' => $mobile,
                    'sms_mobile_code' => $mobile_code,
                    'sms_security_code' => $sms_security_code
                ]);

                return response()->json([
                    'code' => 2,
                    'flag' => $flag,
                    'sms_security_code' => $sms_security_code
                ]);
            } else {
                $error = 1;
                if (empty($mobile)) {
                    $sms_error = lang('sms.json_msg.phone_please_write');
                } else {
                    $sms_error = $send_result;
                }

                return response()->json([
                    'msg' => $sms_error,
                    'error' => $error
                ]);
            }
        }
    }

    /* 随机数 */
    private function random($length = 6, $numeric = 0)
    {
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    private function write_file($file_name, $content)
    {
        $path = storage_path('logs/');
        $this->mkdirs($path . date('Ymd'));
        $filename = $path . date('Ymd') . '/' . $file_name . '.log';
        $Ts = fopen($filename, "a+");
        fputs($Ts, "\r\n" . $content);
        fclose($Ts);
    }

    private function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) {
            return true;
        }
        if (!$this->mkdirs(dirname($dir), $mode)) {
            return false;
        }
        return @mkdir($dir, $mode);
    }

    private function read_file($file_name)
    {
        $content = '';
        $path = storage_path('logs/');
        $filename = $path . date('Ymd') . '/' . $file_name . '.log';
        if (function_exists('file_get_contents')) {
            @$content = file_get_contents($filename);
        } else {
            if (@$fp = fopen($filename, 'r')) {
                @$content = fread($fp, filesize($filename));
                @fclose($fp);
            }
        }
        $content = explode("\r\n", $content);
        return end($content);
    }

    private function get_send_sms_keyval($key, $val)
    {
        if (empty($key) || empty($val)) {
            $is_null = 1;
        } else {
            $is_null = 0;
        }

        return $is_null;
    }
}
