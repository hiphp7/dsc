<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\WechatUser;
use App\Repositories\Common\CommonRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Friend\FriendLinkService;
use App\Services\Navigator\NavigatorService;
use App\Services\User\ConnectUserService;
use App\Services\User\UserCommonService;
use App\Services\Wechat\WechatUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class OauthController
 * @package App\Http\Controllers
 */
class OauthController extends InitController
{
    protected $config;
    protected $connectUserService;
    protected $friendLinkService;
    protected $wechatUserService;
    protected $userCommonService;
    protected $cartCommonService;
    protected $navigatorService;
    protected $commonRepository;

    public function __construct(
        ConnectUserService $connectUserService,
        FriendLinkService $friendLinkService,
        WechatUserService $wechatUserService,
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService,
        NavigatorService $navigatorService,
        CommonRepository $commonRepository
    )
    {
        $this->config = $this->config();
        $this->connectUserService = $connectUserService;
        $this->wechatUserService = $wechatUserService;
        $this->friendLinkService = $friendLinkService;
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->navigatorService = $navigatorService;
        $this->commonRepository = $commonRepository;
    }

    /**
     * 构造函数
     */
    protected function initialize()
    {
        parent::initialize();
        load_helper(['passport', 'mobile']);

        L(lang('user'));
        L(lang('common'));
        $this->assign('lang', L());

        $this->common_info('user');
    }

    /**
     * 授权登录
     * @param Request $request
     * @return array|void|string
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'type' => 'required', // 授权登录类型
        ]);

        $type = $request->input('type');
        $back_url = $request->input('back_url', '');
        $back_url = strip_tags(html_out($back_url));
        // 会员中心授权管理绑定
        $user_id = $request->input('user_id', 0);

        // 处理url
        $back_url = empty($back_url) ? route('user') : $back_url;
        if ($user_id > 0) {
            $url = route('oauth', ['type' => $type, 'user_id' => $user_id, 'back_url' => $back_url]);
        } else {
            $url = route('oauth', ['type' => $type, 'back_url' => $back_url]);
        }

        $app = $this->getProvider($type);
        if (is_null($app)) {
            return [];
        }
        // 检测是否安装
        if ($app->status($type) == 0) {
            return show_message(lang('user.auth_not_exit'), lang('user.back_up_page'), route('user'), 'error');
        }

        // 授权回调
        $code = $request->get('code', '');
        if (isset($code) && $code != '') {
            if ($res = $app->callback()) {
                // unionid 必需
                if (!$res['unionid']) {
                    return show_message(lang('user.msg_authoriza_error'), lang('user.back_up_page'), route('user'), 'error');
                }
                $back_url = strip_tags(html_out($back_url));

                // 处理推荐u参数(会员推荐、分销商推荐)
                $up_uid = $this->commonRepository->getUserAffiliate();  // 获得推荐uid参数
                $up_drpid = $this->commonRepository->getDrpAffiliate();  // 获得分销商uid参数
                $res['parent_id'] = $up_uid ?? 0; // 同步推荐分成关系
                $res['drp_parent_id'] = $up_drpid ?? 0;//同步分销关系

                session(['unionid' => $res['unionid']]);
                session(['oauth_info' => $res]);
                $openid = $res['openid'] ?? '';
                session(['openid' => $openid]);

                // 会员中心授权管理绑定
                if ($user_id > 0 && session('user_id') == $user_id && !empty($res['unionid'])) {
                    $back_url = empty($back_url) ? route('user', ['act' => 'account_bind']) : $back_url;
                    if ($this->UserBind($res, $user_id, $type) === true) {
                        return redirect($back_url);
                    } else {
                        return show_message(lang('user.msg_account_bound'), lang('user.msg_rebound'), $back_url, 'error');
                    }
                } else {
                    // 查询是否新用户
                    $userinfo = $this->connectUserService->getConnectUserinfo($res['unionid'], $type);

                    // 已经绑定过的 授权自动登录
                    if ($userinfo) {
                        // 已注册用户更新手机号
                        if ($userinfo && empty($userinfo['mobile_phone'])) {
                            return redirect()->route('oauth.bind_register', ['type' => $type]);
                        }
                        $this->doLogin($userinfo['user_name']);

                        // 更新社会化登录用户信息
                        $res['user_id'] = !empty($userinfo['user_id']) ? $userinfo['user_id'] : session('user_id');
                        $this->connectUserService->updateConnectUser($res, $type);
                        // 更新微信授权用户信息
                        if (file_exists(MOBILE_WECHAT) && $type == 'weixin') {
                            $res['openid'] = session('openid');
                            $this->wechatUserService->update_wechat_user($res, 1); // 1 不更新ect_uid
                        }

                        return redirect()->route('user');
                    } else {
                        if (!empty(session('unionid')) && session()->has('unionid') || $res['unionid']) {
                            // 注册并验证手机号
                            return redirect()->route('oauth.bind_register', ['type' => $type, 'back_url' => $back_url]);
                        } else {
                            return show_message(lang('user.msg_author_register_error'), lang('user.back_up_page'), route('user'), 'error');
                        }
                    }
                }
            } else {
                return show_message(lang('user.msg_authoriza_error'), lang('user.back_up_page'), route('user'), 'error');
            }
        }

        // 授权开始
        return $app->redirect($url);
    }

    /**
     * 微信绑定手机号注册
     * @return
     */
    public function bind_register()
    {
        if (request()->isMethod('post')) {
            $mobile = request()->input('mobile', '');
            $sms_code = request()->input('mobile_code', '');
            $captcha_value = request()->input('captcha', '');
            $type = request()->input('type', '');
            $back_url = request()->input('back_url', '');
            $back_url = empty($back_url) ? route('user') : $back_url;
            $back_url = strip_tags(html_out($back_url));

            // 验证手机号不能为空
            if (empty($mobile)) {
                return response()->json(['status' => 'n', 'info' => lang('user.bind_mobile_null')]);
            }
            // 验证手机号格式
            if (is_mobile($mobile) == false) {
                return response()->json(['status' => 'n', 'info' => lang('user.bind_mobile_error')]);
            }

            // 验证短信验证码
            if (!session()->has('sms_mobile') || !session()->has('sms_mobile_code')) {
                return response()->json(['status' => 'n', 'info' => lang('user.bind_mobile_code_error')]);
            }
            if ($mobile != session('sms_mobile') || $sms_code != session('sms_mobile_code')) {
                return response()->json(['status' => 'n', 'info' => lang('user.bind_mobile_code_error')]);
            }

            $res = session()->get('oauth_info');
            $res['mobile_phone'] = $mobile;

            $type = ($type == 'weixin') ? 'wechat' : $type;// 统一PC与H5参数
            $userinfo = $this->connectUserService->getConnectUserinfo($res['unionid'], $type);
            if (!empty($userinfo)) {
                if (empty($userinfo['mobile_phone'])) {
                    // 更新会员手机号
                    $user_data = [
                        'mobile_phone' => $res['mobile_phone'],
                    ];
                    Users::where(['user_id' => $userinfo['user_id']])->update($user_data);
                }
                // 登录
                $this->doLogin($userinfo['user_name']);
                return response()->json(['status' => 'y', 'info' => lang('user.oauth_success'), 'url' => $back_url]);
            } else {
                // 验证此手机号是否已经绑定 第三方登录 含微信、QQ
                $model = Users::where(function ($query) use ($mobile) {
                    $query->orWhere('mobile_phone', $mobile)
                        ->orWhere('user_name', $mobile);
                });
                $model = $model->whereHas('getConnectUser', function ($query) use ($type) {
                    $query->where('connect_code', 'sns_' . $type);
                });

                $user_connect = $model->first();
                $user_connect = $user_connect ? $user_connect->toArray() : [];

                if (!empty($user_connect)) {
                    return response()->json(['status' => 'n', 'info' => lang('user.mobile_isbinded'), 'url' => $back_url]);
                }
                // 验证会员或手机号是否已注册
                $users = Users::select('user_id', 'user_name', 'mobile_phone')
                    ->where(function ($query) use ($mobile) {
                        $query->orWhere('mobile_phone', $mobile)
                            ->orWhere('user_name', $mobile);
                    })
                    ->first();
                $users = $users ? $users->toArray() : [];
                if (!empty($users)) {
                    if (session()->has('sms_mobile') && session()->has('sms_mobile_code') && $mobile == session('sms_mobile') && $sms_code == session('sms_mobile_code')) {
                        // 更新社会化登录用户信息
                        $res['user_id'] = $users['user_id'];
                        $this->connectUserService->updateConnectUser($res, $type);
                        // 查询是否绑定
                        $userinfo = $this->connectUserService->getConnectUserinfo($res['unionid'], $type);
                        if (!empty($userinfo)) {
                            // 登录
                            $this->doLogin($userinfo['user_name']);
                            return response()->json(['status' => 'y', 'info' => lang('user.oauth_success'), 'url' => $back_url]);
                        }
                    } else {
                        return response()->json(['status' => 'n', 'info' => lang('user.please_change_mobile')]);
                    }
                }
                // 注册
                $result = $this->doRegister($res, $type);
                if ($result == true) {
                    return response()->json(['status' => 'y', 'info' => lang('user.oauth_success'), 'url' => $back_url]);
                } else {
                    return response()->json(['status' => 'n', 'info' => lang('user.oauth_fail'), 'url' => $back_url]);
                }
            }
        }

        $type = request()->input('type', '');
        $back_url = request()->input('back_url', '');
        $back_url = empty($back_url) ? route('user') : $back_url;
        $back_url = strip_tags(html_out($back_url));

        $oauth_info = session()->get('oauth_info');
        if (empty($oauth_info)) {
            return show_message(lang('user.oauth_fail'), lang('user.back_up_page'), route('user'), 'error');
        }

        $sms_security_code = rand(1000, 9999);

        session([
            'sms_security_code' => $sms_security_code
        ]);

        $this->assign('sms_security_code', $sms_security_code);

        $this->assign('oauth_info', $oauth_info);
        $this->assign('type', $type);
        $this->assign('back_url', $back_url);
        $this->assign('sms_signin', $this->config['sms_signin']);
        $this->assign('page_title', lang('user.bind_mobile'));
        return $this->display('oauth_bindregister');
    }

    /**
     * 设置成登录状态
     * @param  $username
     */
    protected function doLogin($username)
    {
        $GLOBALS['user']->set_session($username);
        $GLOBALS['user']->set_cookie($username);
        $this->userCommonService->updateUserInfo();
        $this->cartCommonService->recalculatePriceCart();
    }

    /**
     * 授权注册
     * @param array $res
     * @param string $type
     * @return bool
     */
    protected function doRegister($res = [], $type = '')
    {
        $username = $res['mobile_phone']; //get_wechat_username($res['unionid'], $type);
        $password = session()->has('sms_mobile_code') ? session('sms_mobile_code') : mt_rand(100000, 999999); // 默认短信验证码为密码
        $email = $username . '@qq.com';
        $extends = [
            'nick_name' => !empty($res['nickname']) ? $res['nickname'] : '',
            'sex' => !empty($res['sex']) ? $res['sex'] : 0,
            'user_picture' => !empty($res['headimgurl']) ? $res['headimgurl'] : '',
            'mobile_phone' => !empty($res['mobile_phone']) ? $res['mobile_phone'] : '',
        ];
        // 微信通粉丝 保存的推荐参数信息
        if (file_exists(MOBILE_WECHAT)) {
            $wechat_user = WechatUser::select('drp_parent_id', 'parent_id')->where(['unionid' => $res['unionid']])->first();
            $wechat_user = $wechat_user ? $wechat_user->toArray() : [];
            if (!empty($wechat_user)) {
                if (file_exists(MOBILE_DRP)) {
                    $res['drp_parent_id'] = $wechat_user['drp_parent_id'] > 0 ? $wechat_user['drp_parent_id'] : 0;
                }
                $res['parent_id'] = $wechat_user['parent_id'] > 0 ? $wechat_user['parent_id'] : 0;
            }
        }
        // 普通用户
        if (file_exists(MOBILE_DRP) && isset($res['drp_parent_id']) && $res['drp_parent_id'] > 0) {
            $extends['drp_parent_id'] = $res['drp_parent_id'] > 0 ? $res['drp_parent_id'] : 0;
        }
        if (isset($res['parent_id']) && $res['parent_id'] > 0) {
            $extends['parent_id'] = $res['parent_id'] > 0 ? $res['parent_id'] : 0;
        }

        // 查询是否绑定
        $type = ($type == 'weixin') ? 'wechat' : $type;// 统一PC与H5参数
        $userinfo = $this->connectUserService->getConnectUserinfo($res['unionid'], $type);
        if (empty($userinfo)) {
            if (register($username, $password, $email, $extends) !== false) {
                // 获取新的session
                $new_user = [
                    'user_id' => session('user_id', 0),
                    'user_rank' => session('user_rank', 0),
                    'discount' => session('discount', 1)
                ];
                session($new_user);
                // 更新社会化登录用户信息
                $res['user_id'] = session('user_id');
                $this->connectUserService->updateConnectUser($res, $type);

                // 更新用户头像与昵称信息
                $this->userCommonService->updateUsers($res);

                // 更新微信用户信息
                if (file_exists(MOBILE_WECHAT) && $type == 'wechat') {
                    $res['openid'] = session('openid');
                    $res['from'] = 2; // 2 微信扫码授权注册
                    $this->wechatUserService->update_wechat_user($res);
                }

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 会员中心授权管理绑定帐号(自动)
     *
     * @param array $oauthUser
     * @param int $user_id
     * @param string $type
     * @return bool
     */
    protected function UserBind($oauthUser = [], $user_id = 0, $type = '')
    {
        if (empty($oauthUser) || empty($type)) {
            return false;
        }

        // 查询users用户是否存在
        $users = Users::select('user_id', 'user_name')->where('user_id', $user_id)->first();
        $users = $users ? $users->toArray() : [];
        if ($users && !empty($oauthUser['unionid'])) {

            $type = ($type == 'weixin') ? 'wechat' : $type;// 统一PC与H5参数

            // 查询users用户是否被其他人绑定
            $connect_user_id = $this->connectUserService->checkConnectUserId($oauthUser['unionid'], $type);
            if ($connect_user_id > 0 && $connect_user_id != $users['user_id']) {
                return false;
            }

            // 更新社会化登录用户信息
            $oauthUser['user_id'] = $user_id;
            $this->connectUserService->updateConnectUser($oauthUser, $type);
            // 更新微信粉丝信息
            if (file_exists(MOBILE_WECHAT) && $type == 'wechat') {
                $this->wechatUserService->update_wechat_user($oauthUser, 1); // 1 不更新ect_uid
            }

            // 重新登录
            $this->doLogin($users['user_name']);
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $request
     * @return mixed
     */
    protected function getProvider($type)
    {
        $type = Str::studly($type);

        $provider = 'App\\Plugins\\Connect\\' . $type . '\\' . $type;

        if (!class_exists($provider)) {
            return null;
        }

        return new $provider();
    }

    /**
     * 底部共用信息
     * @param string $filename 对应语言包文件名
     */
    protected function common_info($filename)
    {
        $this->assign('dwt_shop_name', $this->config['shop_name']);
        $this->assign('user_id', session('user_id'));

        // js提示语言文件
        $file_languages = (isset($GLOBALS['_LANG']['js_languages'][$filename]) && is_array($GLOBALS['_LANG']['js_languages'][$filename])) ? $GLOBALS['_LANG']['js_languages'][$filename] : [];
        $merge_js_languages = array_merge($GLOBALS['_LANG']['js_languages']['common'], $file_languages);
        $json_languages = json_encode($merge_js_languages);
        $this->assign('json_languages', $json_languages);

        //自定义导航栏
        $navigator_list = $this->navigatorService->getNavigator();
        $this->assign('navigator_list', $navigator_list);

        // ICP 备案信息
        $icp_number = $this->config['icp_number'];
        $this->assign('icp_number', $icp_number);

        // 友情连接
        $links = $this->friendLinkService->getIndexGetLinks();
        $this->assign('img_links', $links['img']);
        $this->assign('txt_links', $links['txt']);
    }
}
