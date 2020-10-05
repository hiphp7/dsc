<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Api\Fourth\Transformers\UserTransformer;
use App\Services\Cart\CartCommonService;
use App\Services\User\AuthService;
use App\Services\User\UserCommonService;
use App\Services\User\UserLoginRegisterService;
use App\Services\User\UserOauthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class PassportController
 * @package App\Api\Fourth\Controllers
 */
class PassportController extends Controller
{
    protected $userTransformer;
    protected $authService;
    protected $userCommonService;
    protected $config;
    protected $userOauthService;
    protected $cartCommonService;
    protected $userLoginRegisterService;

    public function __construct(
        UserTransformer $userTransformer,
        AuthService $authService,
        UserCommonService $userCommonService,
        UserOauthService $userOauthService,
        CartCommonService $cartCommonService,
        UserLoginRegisterService $userLoginRegisterService
    )
    {
        $this->userTransformer = $userTransformer;
        $this->authService = $authService;
        $this->userCommonService = $userCommonService;
        $this->userOauthService = $userOauthService;
        $this->cartCommonService = $cartCommonService;
        $this->userLoginRegisterService = $userLoginRegisterService;

        /* 商城配置信息 */
        $shopConfig = cache('shop_config');
        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
    }

    /**
     * 电子邮箱规则验证
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request)
    {

        if ($this->config['shop_reg_closed'] == 1) {
            $message = current($GLOBALS['err']->last_message());
            return $this->failed($message);
        }

        $this->validate($request, [
            'username' => 'required', // 用户名
            'email' => 'required|email', // 电子邮箱
            'password' => 'required', // 密码
            'code' => 'required', // 图片验证码
        ]);

        $username = $request->get('username');
        $email = $request->get('email');
        $password = $request->get('password');

        // 推荐参数
        $parent_id = $request->get('parent_id');

        if (file_exists(MOBILE_DRP)) {
            $other['drp_parent_id'] = $parent_id;
        }
        $other['parent_id'] = $parent_id;

        if ($this->userLoginRegisterService->register($username, $password, $email, $other)) {
            return $this->login($request);
        } else {
            $message = current($GLOBALS['err']->last_message());
            return $this->failed($message);
        }
    }

    /**
     * 返回用户登录成功之后的token
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function login(Request $request)
    {
        $username = $request->get('username');
        $password = $request->get('password');

        $user = $this->userCommonService->getUserByName($username);

        if (is_null($user)) {
            return $this->failed(lang('user.user_not_exist'));
        }

        $password = empty($user->ec_salt) ? md5($password) : md5(md5($password) . $user->ec_salt);

        if ($password != $user->password) {
            return $this->failed(lang('user.user_pass_error'));
        }

        $jwt = $this->JWTEncode(['user_id' => $user->user_id]);

        // 登录成功更新用户信息
        $this->userCommonService->updateUserInfo($user->user_id, 'mobile');

        // 重新计算购物车信息
        $this->cartCommonService->recalculatePriceMobileCart($user->user_id);

        return $this->succeed($jwt);
    }

    /**
     * 手机号码快捷登录
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function fastLogin(Request $request)
    {
        // ECJia App 快捷登录
        if ($request->has('ecjiahash')) {
            $user = $this->ecjiaLogin($request);

            if ($user === false) {
                return $this->failed(lang('user.ecjia_auth_login_fail'));
            } else {
                $jwt = $this->JWTEncode(['user_id' => collect($user)->get('user_id')]);
                return $this->succeed($jwt);
            }
        }

        // 手机号码快捷登录
        if (!$this->verifySMS($request)) {
            return $this->failed(lang('user.bind_mobile_code_error'));
        }

        $result = $this->authService->loginOrRegister($request);

        if ($result['error']) {
            return $this->failed($result['message']);
        } else {
            $jwt = $this->JWTEncode(['user_id' => $result['user_id']]);
            return $this->succeed($jwt);
        }
    }

    /**
     * 重置密码
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function reset(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|between:6,20',
        ], [
            'required' => lang('user.user_pass_empty'),
            'between' => lang('user.user_pass_limit_6_20')
        ]);

        // 验证短信验证码
        if (!$this->verifySMS($request)) {
            return $this->failed(lang('user.bind_mobile_code_error'));
        }

        // 查找用户信息
        $mobile = $request->get('mobile');
        $user = $this->userCommonService->getUserByName($mobile);

        if (is_null($user)) {
            return $this->failed(lang('user.user_not_exist'));
        }

        // 重置用户新密码
        $GLOBALS['user']->edit_user([
            'user_id' => collect($user)->get('user_id'),
            'username' => collect($user)->get('user_name'),
            'password' => $request->get('password')
        ], 1);

        $res = $this->userTransformer->transform($user);

        return $this->succeed($res);
    }

    /**
     * 社会化登录列表
     * @return JsonResponse
     */
    public function oauth_list()
    {
        // 获取已经安装的
        $columns = ['id', 'type'];
        $list = $this->userOauthService->getOauthList(1, $columns);

        if (!empty($list)) {
            foreach ($list as $key => $vo) {
                if ($vo['type'] == 'wechat' && !is_wechat_browser()) {
                    unset($list[$key]);
                }
            }
            // 重新索引
            $list = collect($list)->values()->all();
        }

        return $this->succeed($list);
    }

    /**
     * 获取注册配置
     * @return JsonResponse
     */
    public function loginConfig()
    {
        $res['shop_reg_closed'] = $this->config['shop_reg_closed'];

        return $this->succeed($res);
    }
}
