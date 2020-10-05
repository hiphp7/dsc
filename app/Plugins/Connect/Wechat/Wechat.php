<?php

namespace App\Plugins\Connect\Wechat;

use App\Models\TouchAuth;
use EasyWeChat\Factory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class Wechat
 * @package App\Plugins\Connect\Wechat
 */
class Wechat
{
    protected $app = [];
    protected $type = 'wechat';

    public function __construct()
    {
        $config = $this->getConfig();

        $this->app = Factory::officialAccount($config);
    }

    /**
     * 获取授权地址
     * @param null $callback_url
     * @return RedirectResponse
     */
    public function redirect($callback_url = null)
    {
        return $this->app->oauth->scopes(['snsapi_userinfo'])->redirect($callback_url);
    }

    /**
     * 回调用户数据
     * @return array
     */
    public function callback()
    {
        $code = request()->input('code');
        $userinfo = [];
        if ($code) {
            $accessToken = $this->app->oauth->getAccessToken($code);
            $user = $this->app->oauth->user($accessToken);

            $userinfo = $user->getOriginal();
        }

        // unionid 必需
        if (empty($userinfo['unionid'])) {
            return [];
        }

        return $userinfo;
    }

    /**
     * 组装配置
     */
    protected function getConfig()
    {
        $auth_config = TouchAuth::where('type', $this->type)
            ->where('status', 1)
            ->value('auth_config');

        $auth_config = unserialize($auth_config);

        $wechat_config = [];

        if ($auth_config) {
            foreach ($auth_config as $item) {
                $wechat_config[$item['name']] = $item['value'];
            }
        }

        return [
            'app_id' => $wechat_config['app_id'] ?? '', // AppID
            'secret' => $wechat_config['app_secret'] ?? '', // AppSecret
            'oauth' => [
                'scopes' => 'snsapi_userinfo',
                'callback' => dsc_url('/oauth/callback?type=wechat'),
            ],
        ];
    }

    /**
     * 判断是否安装
     * @param $type
     * @return int
     */
    public function status($type)
    {
        // 手机是否安装
        $count = TouchAuth::where('type', $type)->where('status', 1)->count();

        return $count > 0 ? 1 : 0;
    }
}
