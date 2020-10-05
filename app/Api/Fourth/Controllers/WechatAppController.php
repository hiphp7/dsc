<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\WxappConfig;
use App\Services\User\AuthService;
use EasyWeChat\Factory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Class WechatAppController
 * @package App\Api\Fourth\Controllers
 */
class WechatAppController extends Controller
{
    /**
     * 微信小程序对象
     * @var
     */
    protected $handler;

    /**
     * @var
     */
    protected $authService;

    /**
     * @return JsonResponse
     * @throws Exception
     */
    protected function initialize()
    {
        parent::initialize();

        $config = $this->config();

        if (is_null($config)) {
            return $this->failed(lang('wechat.wxapp_auth_failed'));
        }

        $this->handler = Factory::miniProgram($config);

        $this->authService = app(AuthService::class);
    }

    /**
     * 获取用户身份信息(openId, sessionKey, unionId)
     * @param Request $request
     * @return JsonResponse
     */
    public function session(Request $request)
    {
        $code = $request->get('code', '');

        $session = $this->handler->auth->session($code);

        return $this->succeed($session);
    }

    /**
     * 获取微信手机号码或微信UnionId
     * @param Request $request
     * @return JsonResponse
     */
    public function decrypt(Request $request)
    {
        $session = $request->get('sessionKey', '');
        $iv = $request->get('iv', '');
        $encryptData = $request->get('encryptData', '');

        $decryptedData = $this->handler->encryptor->decryptData($session, $iv, $encryptData);

        return $this->succeed($decryptedData);
    }

    /**
     * 用户快捷登录注册
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        $type = $request->get('type', 'wechat');
        $phoneNumber = $request->get('phoneNumber', '');
        $userInfo = $request->get('userInfo', '');
        $scopeSession = $request->get('scopeSessions', '');

        $platform = $request->get('platform', 'MP-WEIXIN'); // 授权登录来源

        // 组装 Request 数据
        $request->offsetSet('type', $type);
        $request->offsetSet('mobile', $phoneNumber);
        $request->offsetSet('openid', $scopeSession['openid']);
        $request->offsetSet('unionid', $scopeSession['unionid']);
        $request->offsetSet('platform', $platform);

        $userInfo['nickname'] = $userInfo['nickName'];
        $userInfo['openid'] = $scopeSession['openid'];
        $userInfo['unionid'] = $scopeSession['unionid'];
        $userInfo['headimgurl'] = $userInfo['avatarUrl'];

        Cache::put($type . md5($scopeSession['unionid']), $userInfo, Carbon::now()->addHours(1));

        $result = $this->authService->loginOrRegister($request);

        if ($result['error']) {
            return $this->failed($result['message']);
        } else {
            $jwt = $this->JWTEncode(['user_id' => $result['user_id']]);
            return $this->succeed($jwt);
        }
    }

    /**
     * 小程序配置
     * @return array
     */
    protected function config()
    {
        $config = WxappConfig::where('status', 1)->first();

        if (is_null($config)) {
            return null;
        }

        return [
            'app_id' => $config->wx_appid,
            'secret' => $config->wx_appsecret,
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => storage_path('logs/wechat_app.log'),
            ],
        ];
    }
}
