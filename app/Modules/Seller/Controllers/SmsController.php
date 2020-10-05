<?php

namespace App\Modules\Seller\Controllers;

use App\Repositories\Common\CommonRepository;

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

        /******************************************************
         * 短信发送 开始
         ******************************************************/

        $mobile = isset($_POST['mobile']) ? $_POST['mobile'] + 0 : '';
        $mobile_code = isset($_POST['mobile_code']) ? $_POST['mobile_code'] + 0 : '';
        $security_code = isset($_POST['seccode']) ? $_POST['seccode'] + 0 : '';
        $username = !empty($_POST['username']) ? trim($_POST['username']) : '';
        $sms_value = isset($_POST['sms_value']) ? trim($_POST['sms_value']) : '';

        if ($_GET['act'] == 'check') {
            if ($mobile != session('sms_mobile') or $mobile_code != session('sms_mobile_code')) {
                return response()->json(['msg' => $GLOBALS['_LANG']['mobile_verify_code_wrong']]);
            } else {
                return response()->json(['code' => '2']);
            }
        }

        if ($_GET['act'] == 'send') {
            if (empty($mobile)) {
                return response()->json(['msg' => $GLOBALS['_LANG']['mobile_not_null']]);
            }

            $preg = '/^(1[3-9])\d{9}$/'; //简单的方法
            if (!preg_match($preg, $mobile)) {
                return response()->json(['msg' => $GLOBALS['_LANG']['mobile_wrong_reinput']]);
            }

            if (session()->has('sms_security_code') && session('sms_security_code') != $security_code) {
                return response()->json(['msg' => 'you are lost.']);
            }

            if (session()->has('sms_mobile')) {
                if (local_strtotime($this->read_file($mobile)) > (gmtime() - 60)) {
                    return response()->json(['msg' => $GLOBALS['_LANG']['get_verify_too_much']]);
                }
            }

            $mobile_code = $this->random(6, 1);

            $smsParams = [
                'mobile_phone' => $mobile,
                'mobilephone' => $mobile,
                'code' => $mobile_code
            ];

            $send_result = $this->commonRepository->smsSend($mobile, $smsParams, $sms_value);

            if ($send_result === true) {
                $sms_security_code = rand(1000, 9999);
                session([
                    'sms_mobile' => $mobile,
                    'sms_mobile_code' => $mobile_code,
                    'sms_security_code' => $sms_security_code
                ]);

                return response()->json(['code' => 2, 'flag' => htmlspecialchars($_GET['flag']), 'sms_security_code' => $sms_security_code]);
            } else {
                $error = 1;
                if (empty($username)) {
                    $sms_error = $GLOBALS['_LANG']['input_user_name'];
                } else {
                    $sms_error = $send_result;
                }

                return response()->json(['msg' => $sms_error, 'error' => $error]);
            }
        }
    }

    /******************************************************
     * protected function
     ******************************************************/

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
        $this->mkdirs("sms/" . date('Ymd'));
        $filename = "sms/" . date('Ymd') . '/' . $file_name . '.log';
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
        $filename = "sms/" . date('Ymd') . '/' . $file_name . '.log';
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
}
