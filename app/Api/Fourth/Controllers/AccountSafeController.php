<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\User\AccountSafeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * 账户安全中心
 * Class AccountSafeController
 * @package App\Api\Fourth\Controllers
 */
class AccountSafeController extends Controller
{
    protected $accountSafeService;
    protected $config;

    public function __construct(
        AccountSafeService $accountSafeService
    )
    {
        $this->accountSafeService = $accountSafeService;

        /* 商城配置信息 */
        $shopConfig = cache('shop_config');
        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
    }

    /**
     * 账户安全首页
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    public function index(Request $request)
    {
        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        // 是否验证邮箱
        $result['is_validated'] = $this->accountSafeService->userValidateStatus($user_id);

        // 是否启用支付密码
        $result['users_paypwd'] = $this->accountSafeService->userPaypwdCount($user_id);

        // 是否是授权登录用户 如果是 则不显示修改密码
        $result['is_connect_user'] = $this->accountSafeService->isConnectUser($user_id);

        return $this->succeed($result);
    }

    /**
     * 启用支付密码
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function addPaypwd(Request $request)
    {
        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = ['code' => 0, 'msg' => ''];

        $result['notice'] = lang('user.Enable_pay_password_desc');

        // 判断是否启用支付密码
        $result['users_paypwd'] = $this->accountSafeService->userPaypwd($user_id);

        // 验证类型 开启短信 默认短信
        $result['validate_type'] = $validate_type = $this->accountSafeService->validateType();

        $result['user_info'] = $user_info = $this->accountSafeService->users($user_id);
        $email_is_validated = !empty($user_info['is_validated']) ? $user_info['is_validated'] : 0;
        $mobile_is_validated = !empty($user_info['mobile_phone']) ? 1 : 0;

        // 未开启短信且邮箱未验证 todo
//        if ($validate_type == 'email' && $email_is_validated == 0) {
//            // 邮箱未验证
//            return $this->succeed(['code' => 1, 'msg' => lang('user.email_no_validate')]);
//        }
        // 开启短信优先验证手机
        if ($validate_type == 'phone' && $mobile_is_validated == 0) {
            // 手机未验证
            return $this->succeed(['code' => 1, 'msg' => lang('user.mobile_no_validate')]);
        }

        return $this->succeed($result);
    }

    /**
     * 修改支付密码
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function editPaypwd(Request $request)
    {
        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $paypwd_id = $request->input('paypwd_id', 0);
        $pay_paypwd = $request->input('pay_paypwd', ''); // 支付密码

        $pay_online = $request->input('pay_online', 0);
        $user_surplus = $request->input('user_surplus', 0);
        $user_point = $request->input('user_point', 0);
        $baitiao = $request->input('baitiao', 0);
        $gift_card = $request->input('gift_card', 0);

        $validate_type = $request->input('validate_type', 'phone'); // 验证类型 phone 手机 或 邮箱 email

        // 开启短信验证手机
        $sms_signin = $this->config['sms_signin'];
        if ($validate_type == 'phone') {

            // 验证短信验证码
            if (!$this->verifySMS($request)) {
                return $this->failed(lang('user.bind_mobile_code_error'));
            }

        }

        if (empty($pay_paypwd)) {
            // 支付密码不能为空
            return $this->succeed(['code' => 1, 'msg' => lang('user.input_pay_password')]);
        }

        // 支付密码长度限制6位数字
        if (strlen($pay_paypwd) != 6) {
            return $this->succeed(['code' => 1, 'msg' => lang('flow.paypwd_length_limit')]);
        }

        $data = [
            'user_id' => $user_id,
            'pay_online' => $pay_online,
            'user_surplus' => $user_surplus,
            'user_point' => $user_point,
            'baitiao' => $baitiao,
            'gift_card' => $gift_card
        ];
        // 加密
        $ec_salt = rand(1, 9999);
        $new_password = md5(md5($pay_paypwd) . $ec_salt);

        $data['pay_password'] = $new_password;
        $data['ec_salt'] = $ec_salt;
        if ($paypwd_id) {
//            // 更新支付密码，验证原支付密码
//            $old_pay_paypwd = $request->input('old_pay_paypwd', '');
//            if (empty($old_pay_paypwd)) {
//                // 原支付密码不能为空
//                return $this->succeed(['code' => 1, 'msg' => lang('user.old_pay_password_empty')]);
//            }
//            $users_paypwd = $this->accountSafeService->getUsersPaypwd($paypwd_id);
//
//            $new_password = md5(md5($old_pay_paypwd) . $users_paypwd['ec_salt']);
//            if ($new_password != $users_paypwd['pay_password']) {
//                // 原支付密码不正确
//                return $this->succeed(['code' => 1, 'msg' => lang('user.old_pay_password_error')]);
//            }

            $this->accountSafeService->updateUsersPaypwd($user_id, $paypwd_id, $data);

            return $this->succeed(['code' => 0, 'msg' => lang('user.edit_pay_password_success')]);

        } else {
            // 启用支付密码
            $this->accountSafeService->addUsersPaypwd($data);

            return $this->succeed(['code' => 0, 'msg' => lang('user.Enable_pay_password_notice')]);
        }
    }

}
