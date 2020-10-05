<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Repositories\Common\CommonRepository;
use App\Rules\PhoneNumber;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class SmsController
 * @package App\Api\Fourth\Controllers
 */
class SmsController extends Controller
{
    /**
     * @var CommonRepository
     */
    protected $commonRepository;

    /**
     * SmsController constructor.
     * @param CommonRepository $commonRepository
     * @throws Exception
     */
    public function __construct(
        CommonRepository $commonRepository
    )
    {
        $this->commonRepository = $commonRepository;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request)
    {
        // 数据验证
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', new PhoneNumber()]
        ]);

        // 返回错误
        if ($validator->fails()) {
            $error = $validator->errors()->first();

            return $this->failed($error);
        }

        // 准备数据
        $client_id = $request->get('client');
        $captcha = $request->get('captcha');
        $mobile = $request->get('mobile');

        // 校验图片验证码
        if (Cache::get($client_id) != $captcha) {
            return $this->failed(lang('user.bind_captcha_error'));
        }

        // 限制发送速率
        if (Cache::get($mobile . '_send_time') > (time() - 60)) {
            return $this->failed(lang('user.send_wait'));
        }

        // 获取验证码
        $sms_code = rand(100000, 999999);

        // 设置短信模板数据
        $message = ['code' => $sms_code];

        // 发送短信
        $res = $this->send($mobile, $message, 'sms_code');
        if ($res) {
            $result = [
                "status" => "success",
                "result" => [
                    "msg" => lang('user.send_success'),
                ]
            ];
        } else {
            $result = ["status" => "fail"];
        }

        // 校验发送
        if ($result['status'] === 'success') {
            Cache::put($client_id . $mobile, $sms_code, Carbon::now()->addMinutes(10));
            Cache::put($mobile . '_send_time', time(), Carbon::now()->addMinutes(10));

            return $this->succeed($result);
        } else {
            return $this->failed(lang('user.send_fail'));
        }
    }

    /**
     * 短信验证码校验
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function verify(Request $request)
    {
        if ($this->verifySMS($request)) {
            return $this->succeed('ok');
        } else {
            return $this->failed(lang('user.bind_mobile_code_error'));
        }
    }

    /**
     * 发送短信
     * @param string $mobile 接收手机号码
     * @param string $content 发送短信的内容数据
     * @param string $send_time 发送内容模板时机标记
     * @return bool
     * @throws Exception
     */
    protected function send($mobile = '', $content = '', $send_time = '')
    {
        return $this->commonRepository->smsSend($mobile, $content, $send_time);
    }
}
