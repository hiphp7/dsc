<?php

namespace App\Services\User;

use App\Services\Cart\CartCommonService;
use App\Services\Wechat\WechatService;
use App\Services\Wechat\WechatUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    protected $wechatService;
    protected $wechatUserService;
    protected $connectUserService;
    protected $userCommonService;
    protected $cartCommonService;
    protected $userLoginRegisterService;

    public function __construct(
        WechatService $wechatService,
        WechatUserService $wechatUserService,
        ConnectUserService $connectUserService,
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService,
        UserLoginRegisterService $userLoginRegisterService
    )
    {
        $this->wechatService = $wechatService;
        $this->wechatUserService = $wechatUserService;
        $this->connectUserService = $connectUserService;
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->userLoginRegisterService = $userLoginRegisterService;
    }

    /**
     * 登录|退出
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function loginOrRegister(Request $request)
    {
        $mobile = $request->get('mobile');
        $user = $this->userCommonService->getUserByName($mobile);

        // 统一普通注册和手机号快捷注册
        if ($request->has('pwd')) {
            $password = $request->get('pwd');
        } else {
            $password = $request->get('code', rand(100000, 999999));
        }

        $request->offsetSet('username', $mobile);
        $request->offsetSet('password', $password);
        $request->offsetSet('email', '');
        $request->offsetSet('mobile_phone', $mobile);

        $type = $request->get('type', '');
        $unionid = $request->get('unionid', '');

        // 定义服务类型映射
        $provider = [
            'weixin' => 'wechat',
        ];

        // 覆写类型
        if (isset($provider[$type])) {
            $type = $provider[$type];
        }

        if (!empty($type)) {
            $platform = $request->get('platform', ''); // 授权注册来源:  MP-WEIXIN 小程序

            // 获得 OAuth 授权信息
            $oauthUser = [];
            if ($unionid) {
                $cache_id = $type . md5($unionid);
                $oauthUser = Cache::get($cache_id);
            }

            if (empty($oauthUser)) {
                return ['error' => true, 'message' => 'Please bind the open platform'];
            }
        }

        if (is_null($user)) {
            // 自动注册并登录
            $other['mobile_phone'] = $mobile;
            $parent_id = $request->get('parent_id', 0);
            //$parent_id = !empty($parent_id) ? base64_decode($parent_id) : 0; // 解密值
            if (file_exists(MOBILE_DRP)) {
                $other['drp_parent_id'] = $parent_id;
            }
            $other['parent_id'] = $parent_id;
            // 微信通粉丝 保存的推荐参数信息
            if (file_exists(MOBILE_WECHAT) && $unionid) {
                $wechat_user = $this->wechatUserService->get_parent($unionid);
                if (!empty($wechat_user)) {
                    if (file_exists(MOBILE_DRP)) {
                        $drp_parent_id = $wechat_user->drp_parent_id > 0 ? $wechat_user->drp_parent_id : 0;
                    }
                    $parent_id = $wechat_user->parent_id > 0 ? $wechat_user->parent_id : 0;
                }
            }
            // 分销用户
            if (file_exists(MOBILE_DRP)) {
                $other['drp_parent_id'] = (isset($drp_parent_id) && $drp_parent_id > 0) ? $drp_parent_id : $other['drp_parent_id'];
            }
            // 普通用户
            $other['parent_id'] = (isset($parent_id) && $parent_id > 0) ? $parent_id : $other['parent_id'];

            if (!empty($type) && !empty($oauthUser)) {
                $other['nick_name'] = $oauthUser['nickname'] ?? ''; // 取授权昵称
                $other['user_picture'] = $oauthUser['headimgurl'] ?? ''; // 授权头像
                $other['sex'] = $oauthUser['sex'] ?? 0;
            }
            // 优先使用微信用户昵称与头像信息 (小程序)
            if ($request->has('userInfo')) {
                $userInfo = $request->get('userInfo');
                $other['nick_name'] = $userInfo['nickName'];
                $other['user_picture'] = $userInfo['avatarUrl'];
                $other['sex'] = $userInfo['gender'];
            }

            if ($this->userLoginRegisterService->register($request->get('username'), $request->get('password'), $request->get('email'), $other)) {
                $user = $this->userCommonService->getUserByName($mobile);

                // 更新 OAuth 授权信息
                if (!empty($type) && !empty($oauthUser)) {
                    $oauthUser['user_id'] = $user->user_id;
                    $oauthUser['unionid'] = $unionid;

                    // 更新社会化登录信息
                    $this->connectUserService->updateConnectUser($oauthUser, $type);
                    if (is_wechat_browser() && $type == 'wechat') {
                        // 更新微信粉丝信息
                        $oauthUser['from'] = (isset($platform) && $platform == 'MP-WEIXIN') ? 3 : 1;
                        $this->wechatUserService->update_wechat_user($oauthUser);

                        // 关注送红包
                        $this->wechatService->sendBonus($oauthUser['openid']);
                    }
                }

                return ['error' => false, 'user_id' => $user->user_id]; // $this->succeed($jwt);
            } else {
                $error = $this->userLoginRegisterService->getError();
                $message = current($error->last_message());
                return ['error' => true, 'message' => $message]; // $this->failed($message);
            }
        } else {
            // 不启用自动注册的时候，如果已存在账户信息，返回error
            if ($request->get('allow_login', 1) == 0) {
                return ['error' => true, 'message' => lang('user.mobile_isbinded')];
            }

            // 校验手机账号是否已绑定其他账号
            $connect = $this->connectUserService->getUserinfo($user->user_id, $type);
            if (isset($connect['open_id']) && $connect['open_id'] != $unionid) {
                return ['error' => true, 'message' => lang('user.mobile_isbinded')]; // $this->failed('该手机号码已绑定');
            }

            // 更新 OAuth 授权信息
            if (!empty($type) && !empty($oauthUser)) {
                $oauthUser['user_id'] = $user->user_id;
                $oauthUser['unionid'] = $unionid;

                // 更新社会化登录信息
                $this->connectUserService->updateConnectUser($oauthUser, $type);
                if (is_wechat_browser() && $type == 'wechat') {
                    // 更新微信粉丝信息
                    $this->wechatUserService->update_wechat_user($oauthUser);
                }
            }

            // 更新用户昵称与头像信息 (小程序)
            if ($request->has('userInfo')) {
                $userInfo = $request->get('userInfo');
                $this->userCommonService->updateUsers([
                    'user_id' => $user->user_id,
                    'nickname' => $userInfo['nickName'],
                    'headimgurl' => $userInfo['avatarUrl'],
                    'sex' => $userInfo['gender'],
                ]);
            }

            // 登录成功更新用户信息
            $this->userCommonService->updateUserInfo($user->user_id, 'mobile');
            // 重新计算购物车信息
            $this->cartCommonService->recalculatePriceMobileCart($user->user_id);

            return ['error' => false, 'user_id' => $user->user_id]; // $this->succeed($jwt);
        }
    }
}
