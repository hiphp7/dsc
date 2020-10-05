<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Users;
use App\Services\User\ConnectUserService;
use App\Services\User\UserOauthService;
use App\Services\Wechat\WechatUserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Class OAuthController
 * @package App\Api\Fourth\Controllers
 */
class OAuthController extends Controller
{
    /**
     * @var ConnectUserService
     */
    protected $connectUserService;

    /**
     * @var WechatUserService
     */
    protected $wechatUserService;

    /**
     * @var UserOauthService
     */
    protected $userOauthService;

    /**
     * OAuthController constructor.
     * @param ConnectUserService $connectUserService
     * @param WechatUserService $wechatUserService
     * @param UserOauthService $userOauthService
     */
    public function __construct(
        ConnectUserService $connectUserService,
        WechatUserService $wechatUserService,
        UserOauthService $userOauthService
    )
    {
        $this->connectUserService = $connectUserService;
        $this->wechatUserService = $wechatUserService;
        $this->userOauthService = $userOauthService;
    }

    /**
     * 生成链接
     * @param Request $request
     * @return JsonResponse|RedirectResponse|Redirector
     */
    public function code(Request $request)
    {
        $app = $this->getProvider($request);

        if (is_null($app)) {
            return $this->responseNotFound();
        }

        // 检测是否安装
        if ($app->status($request->get('type')) == 0) {
            if ($request->has('target_url')) {
                $url = $request->get('target_url');
            } else {
                $url = route('mobile');
            }
            return redirect($url);
        }

        $target_url = $request->get('target_url');
        // 解密
        $target_url = is_null($target_url) ? null : base64_decode($target_url);

        // 记录回调目标URL
        Cache::put('target_url', $target_url, Carbon::now()->addHours(1));

        return $app->redirect();
    }

    /**
     * 授权回调
     * @param Request $request
     * @return JsonResponse|RedirectResponse|Redirector
     * @throws ValidationException
     */
    public function callback(Request $request)
    {
        $this->validate($request, [
            'type' => 'required', // 授权登录类型
        ]);

        $app = $this->getProvider($request);

        $oauthUser = $app->callback();

        if (empty($oauthUser)) {
            if ($request->has('target_url')) {
                $url = $request->get('target_url');
            } else {
                $url = route('mobile');
            }
            return redirect($url);
        }

        $type = $request->get('type');

        $cache_id = $type . md5($oauthUser['unionid']);
        Cache::put($cache_id, $oauthUser, Carbon::now()->addHours(1));

        // 会员中心授权绑定
        $get_user_id = $request->get('user_id', 0);
        if ($get_user_id > 0 && $this->authorization() == $get_user_id) {
            $is_bind = $this->userBind($oauthUser, $get_user_id, $type);
            if ($is_bind == false) {
                if ($request->has('target_url')) {
                    $url = $request->get('target_url');
                } else {
                    $url = route('mobile');
                }
                return redirect($url);
            }
        }

        $result = $this->connectUserService->getConnectUserinfo($oauthUser['unionid'], $type);

        if (!empty($result)) {
            // 授权登录
            $user_id = $result['user_id'];

            $jwt = $this->JWTEncode(['user_id' => $user_id]);

            if ($get_user_id == 0 && $type) {
                // 更新社会化登录信息
                $oauthUser['user_id'] = $user_id;
                $this->connectUserService->updateConnectUser($oauthUser, $type);
                // 更新微信粉丝信息
                if (is_wechat_browser() && $type == 'wechat') {
                    $this->wechatUserService->update_wechat_user($oauthUser, 1);
                }
            }

            $res = ['login' => 1, 'token' => $jwt];
        } else {
            // 验证绑定手机号
            if (is_wechat_browser() && $type == 'wechat') {
                // 处理推荐人参数
                $parent_id = $request->get('parent_id', 0);
                //$parent_id = !empty($parent_id) ? base64_decode($parent_id) : 0;// 解密值
                $oauthUser['parent_id'] = $parent_id;
                $oauthUser['drp_parent_id'] = $parent_id;
                // 更新微信粉丝信息
                $this->wechatUserService->update_wechat_user($oauthUser, 1);
            }

            $res = ['login' => 0, 'unionid' => $oauthUser['unionid'], 'type' => $type];
        }

        // 处理微信插件的授权跳转
        $target_url = Cache::get('target_url');
        if (is_null($target_url)) {
            $url = dsc_url('/#/user');
            $res['url'] = base64_encode($url);
        } else {
            $res['url'] = base64_encode($target_url); // 加密
        }

        return $this->succeed($res);
    }

    /**
     * 校验授权用户信息
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAuth(Request $request)
    {
        // 授权类型如：weixin/weibo/qq；详见：https://uniapp.dcloud.io/api/plugins/provider
        // 非platform平台类型，platform类型如下：H5/App/MP-WEIXIN等
        $type = $request->get('type', 'weixin');
        $userInfo = $request->get('userInfo');

        // 定义服务类型映射
        $provider = [
            'weixin' => 'wechat',
        ];

        // 覆写类型
        if (isset($provider[$type])) {
            $type = $provider[$type];
        }

        $union_id = '';
        if ($type == 'wechat' && isset($userInfo['unionId'])) {
            $union_id = $userInfo['unionId'];
            $userInfo['nickname'] = $userInfo['nickName'];
            $userInfo['headimgurl'] = $userInfo['avatarUrl'];
            $userInfo['sex'] = $userInfo['gender'];
        }

        $user = $this->connectUserService->getConnectUserinfo($union_id, $type);

        if (empty($user)) {
            // 用户未注册
            $cache_id = $type . md5($union_id);
            Cache::put($cache_id, $userInfo, Carbon::now()->addHours(1));

            return $this->succeed(['registered' => 0, 'message' => '用户未注册']);
        } else {
            // 用户已注册返回JWT token
            $jwt = $this->JWTEncode(['user_id' => $user['user_id']]);

            return $this->succeed(['registered' => 1, 'token' => $jwt]);
        }
    }

    /**
     * @param $request
     * @return mixed
     */
    protected function getProvider($request)
    {
        $type = Str::studly($request->get('type'));

        $provider = 'App\\Plugins\\Connect\\' . $type . '\\' . $type;

        if (!class_exists($provider)) {
            return null;
        }

        return new $provider();
    }

    /**
     * 会员中心授权管理绑定帐号(自动)
     * @param array $oauthUser
     * @param int $user_id
     * @param string $type
     * @return bool
     */
    protected function userBind($oauthUser = [], $user_id = 0, $type = '')
    {
        if (empty($oauthUser) || empty($type)) {
            return false;
        }

        // 查询users用户是否存在
        $users = Users::where('user_id', $user_id)->first();
        $users = $users ? $users->toArray() : [];

        if (!empty($users) && !empty($oauthUser['unionid'])) {
            // 查询users用户是否被其他人绑定
            $connect_user_id = $this->connectUserService->checkConnectUserId($oauthUser['unionid'], $type);
            if ($connect_user_id > 0 && $connect_user_id != $users['user_id']) {
                return false;
            }

            // 更新社会化登录信息
            $oauthUser['user_id'] = $user_id;
            $this->connectUserService->updateConnectUser($oauthUser, $type);
            // 更新微信粉丝信息
            if (is_wechat_browser() && $type == 'wechat') {
                $this->wechatUserService->update_wechat_user($oauthUser, 1); // 1 不更新ect_uid
            }

            // 重新登录
            return true;
        } else {
            return false;
        }
    }

    /**
     * 用户绑定登录信息列表
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function bindList(Request $request)
    {
        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $connect_user = $this->connectUserService->connectUserList($user_id);

        // 显示已经安装的社会化登录插件
        $columns = ['id', 'type', 'status', 'sort'];
        $oauth_list = $this->userOauthService->getOauthList(1, $columns);

        $list = [];
        if (!empty($oauth_list)) {
            foreach ($oauth_list as $key => $vo) {
                $list[$vo['type']]['type'] = $vo['type'];
                $list[$vo['type']]['install'] = $vo['status'];
                // PC微信扫码
                if ($vo['type'] == 'weixin') {
                    $vo['type'] = 'wechat';
                }
                if ($vo['type'] == 'wechat' && !is_wechat_browser()) {
                    unset($list[$vo['type']]); // 过滤微信登录
                }
            }
        }

        if (!empty($connect_user)) {
            foreach ($connect_user as $key => $value) {
                $type = substr($value['connect_code'], 4);
                $list[$type]['user_id'] = $value['user_id'];
                $list[$type]['id'] = $value['id'];
                // 已绑定
                if ($value['user_id'] == $user_id) {
                    $list[$type]['is_bind'] = 1;
                }
            }

            $list = collect($list)->values()->all();
        }

        return $this->succeed($list);
    }

    /**
     * 授权解绑
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function unbind(Request $request)
    {
        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $id = $request->input('id', 0);

        if (!empty($id)) {
            // 查询是否绑定 并且需填写验证手机号
            $users = $this->connectUserService->connectUserById($id, $user_id);
            if (!empty($users)) {
                if (!empty($users['mobile_phone'])) {
                    // 删除绑定记录
                    $this->connectUserService->connectUserDelete($users['open_id'], $user_id);

                    // 删除授权登录缓存
                    $cache_id = md5($users['open_id']);
                    if (Cache::has($cache_id)) {
                        Cache::forget($cache_id);
                    }
                    Cache::forget('target_url');

                    return $this->succeed(['code' => 0, 'msg' => lang('user.Un_bind') . lang('admin/common.success')]);
                } else {
                    return $this->succeed(['code' => 1, 'msg' => lang('user.Real_name_authentication_Mobile_two')]);
                }
            } else {
                return $this->succeed(['code' => 1, 'msg' => lang('user.not_Bound')]);
            }
        }

        return $this->succeed(['code' => 1, 'msg' => lang('admin/common.fail')]);
    }
}
