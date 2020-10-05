<?php

namespace App\Plugins\Connect\Weixin;

use EasyWeChat\Factory;
use Illuminate\Support\Str;

/**
 * Class Weixin
 * @package App\Plugins\Connect\Weixin
 */
class Weixin
{
    protected $app = [];
    protected $type = 'weixin';

    public function __construct()
    {
        $config = $this->getConfig();

        $this->app = Factory::officialAccount($config);
    }

    /**
     * 获取授权地址
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect($callback_url = null)
    {
        return $this->app->oauth->scopes(['snsapi_login'])->redirect($callback_url);
    }

    /**
     * @return array
     */
    public function callback()
    {
        $user = $this->app->oauth->user();

        $userinfo = $user->getOriginal();

        // unionid 必需
        if (empty($userinfo['unionid'])) {
            return [];
        }

        // 获取已授权用户 原始API返回的结果
        return $userinfo;
    }

    /**
     * 组装配置
     */
    protected function getConfig()
    {
        $config = [];

        $filepath = plugin_path('Connect/' . Str::studly($this->type) . '/' . 'install.php');

        if (file_exists($filepath)) {
            require_once $filepath;
        }

        return [
            'app_id' => $config['app_id'] ?? '', // AppID
            'secret' => $config['app_secret'] ?? '', // AppSecret
            'oauth' => [
                'scopes' => 'snsapi_login',
                'callback' => route('oauth', ['type' => $this->type]),
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
        // PC 是否安装
        $filepath = plugin_path('Connect/' . Str::studly($type) . '/' . 'install.php');

        return file_exists($filepath) ? 1 : 0;
    }
}
