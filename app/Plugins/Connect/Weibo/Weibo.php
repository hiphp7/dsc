<?php

namespace App\Plugins\Connect\Weibo;

use App\Models\TouchAuth;
use Illuminate\Support\Str;
use Overtrue\Socialite\SocialiteManager;

/**
 * Class Weibo
 * @package App\Plugins\Connect\Weibo
 */
class Weibo
{
    protected $app = [];
    protected $type = 'weibo';

    public function __construct()
    {
        $config = $this->getConfig();

        $this->app = new SocialiteManager($config);
    }

    /**
     * 获取授权地址
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect($callback_url = null)
    {
        return $this->app->driver($this->type)->redirect($callback_url);
    }

    /**
     * 回调用户数据
     * @return array
     */
    public function callback()
    {
        $user = $this->app->driver($this->type)->user();

        $gender = $user->getOriginal()['gender'];
        if ($gender == 'f') {
            $sex = 1;
        } elseif ($gender == 'm') {
            $sex = 2;
        } else {
            $sex = 0;
        }

        $userinfo = [
            'openid' => $user->getId(),
            'unionid' => $user->getId(),
            'nickname' => $user->getNickname(),
            'sex' => $sex,
            'headimgurl' => $user->getAvatar(),
            'city' => $user->getOriginal()['city'] ?? '',
            'province' => $user->getOriginal()['province'] ?? ''
        ];

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
        $config = [];

        if (is_mobile_device()) {
            // 手机端配置
            $auth_config = TouchAuth::where('type', $this->type)
                ->where('status', 1)
                ->value('auth_config');

            $auth_config = unserialize($auth_config);

            if ($auth_config) {
                foreach ($auth_config as $item) {
                    $config[$item['name']] = $item['value'];
                }
            }
            // 回调地址
            $callback = dsc_url('/oauth/callback?type=' . $this->type);
        } else {
            // PC 配置
            $filepath = plugin_path('Connect/' . Str::studly($this->type) . '/' . 'install.php');

            if (file_exists($filepath)) {
                require_once $filepath;
            }
            // 回调地址
            $callback = route('oauth', ['type' => $this->type]);
        }

        return [
            'weibo' => [
                'client_id' => $config['app_key'] ?? '',
                'client_secret' => $config['app_secret'] ?? '',
                'redirect' => $callback,
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
        if (is_mobile_device()) {
            // 手机是否安装
            $count = TouchAuth::where('type', $type)->where('status', 1)->count();

            return $count > 0 ? 1 : 0;
        } else {
            // PC 是否安装
            $filepath = plugin_path('Connect/' . Str::studly($this->type) . '/' . 'install.php');

            return file_exists($filepath) ? 1 : 0;
        }
    }
}
