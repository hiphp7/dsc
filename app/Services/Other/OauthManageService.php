<?php

namespace App\Services\Other;

use App\Models\TouchAuth;
use App\Repositories\Common\DscRepository;
use Illuminate\Support\Str;

/**
 * 授权登录后台管理
 * Class OauthManageService
 * @package App\Services\Other
 */
class OauthManageService
{
    protected $dscRepository;
    protected $config;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 授权登录列表
     * @param string $device 设备 web、h5
     * @return array
     */
    public function oauthList($device = 'web')
    {
        $modules = $this->dscRepository->readModules(plugin_path('Connect'));

        if (empty($modules)) {
            return [];
        }

        if ($device == 'web') {
            foreach ($modules as $key => $value) {

                $type = Str::studly($value['type']);

                $this->dscRepository->helpersLang($value['type'], 'Connect/' . $type . '/Languages/' . $this->config['lang'], 1);

                /* 检查该插件是否已经安装 */
                $install_file = plugin_path('Connect/' . $type . '/install.php');

                $modules[$key]['name'] = $GLOBALS['_LANG'][$value['type']];
                $modules[$key]['desc'] = $GLOBALS['_LANG'][$value['desc']];

                if (file_exists($install_file)) {
                    /* 插件已经安装了 */
                    $modules[$key]['install'] = 1;
                } else {
                    $modules[$key]['install'] = 0;
                }
            }
        }

        if ($device == 'h5') {
            foreach ($modules as $key => $value) {
                $modules[$key]['install'] = TouchAuth::where('type', $value['type'])->count();
                if ($value['name'] == 'Weixin') {
                    unset($modules[$key]); // 过滤微信扫码登录
                }
            }
        }

        return $modules;
    }

    /**
     * h5授权信息
     * @param string $type
     * @return array
     */
    public function getOauthInfo($type = '')
    {
        $info = TouchAuth::where('type', $type)->first();

        $info = $info ? $info->toArray() : [];

        return $info;
    }

    /**
     * h5配置
     * @param string $type
     * @return array
     */
    public function getOauthConfig($type = '')
    {
        $info = $this->getOauthInfo($type);

        $config = [];
        if (!empty($info)) {
            $auth_config = empty($info['auth_config']) ? [] : unserialize($info['auth_config']);

            $config = [
                'status' => $info['status'] ?? '',
                'sort' => $info['sort'] ?? ''
            ];
            if (!empty($auth_config)) {
                foreach ($auth_config as $key => $value) {
                    $config[$value['name']] = $value['value'];
                }
            }
        }

        return $config;
    }

    /**
     * 获得h5原授权信息 用于编辑
     * @param string $type
     * @return array
     */
    public function getOldOauthInfo($type = '')
    {
        $info = $this->getOauthInfo($type);

        $config = [];

        if (!empty($info)) {
            $auth_config = empty($info['auth_config']) ? [] : unserialize($info['auth_config']);

            if (!empty($auth_config)) {
                foreach ($auth_config as $key => $value) {
                    $config[$key] = $value['value'];
                }
            }
        }

        return $config;
    }

    /**
     * 安装h5
     * @param array $data
     * @return bool
     */
    public function createOauth($data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 插入配置信息
        return TouchAuth::create($data);
    }

    /**
     * 更新h5
     * @param array $data
     * @return bool
     */
    public function updateOauth($data = [])
    {
        if (empty($data)) {
            return false;
        }

        return TouchAuth::where('type', $data['type'])->update($data);
    }

    /**
     * web配置
     * @param string $type
     * @return array
     */
    public function getOauthInfoWeb($type = '')
    {
        $config = [];

        $file_path = plugin_path('Connect/' . Str::studly($type) . '/config.php');
        if (file_exists($file_path)) {
            $config = require_once($file_path);
        }

        return $config;
    }

    /**
     * web安装信息
     * @param string $type
     * @return array
     */
    public function getOauthConfigWeb($type = '')
    {
        $config = [];

        $file_path = plugin_path('Connect/' . Str::studly($type) . '/install.php');
        if (file_exists($file_path)) {
            require_once($file_path);
        }

        return $config;
    }

    /**
     * 安装或更新 web
     * @param string $type
     * @param string $data
     * @return bool
     */
    public function updateOauthWeb($type = '', $data = '')
    {
        if (empty($data)) {
            return false;
        }

        $install_file = plugin_path('Connect/' . Str::studly($type) . '/install.php');

        file_put_contents($install_file, $data);

        return true;
    }

    /**
     * 卸载
     * @param string $type
     * @param string $device 设备 web、h5
     * @return bool
     */
    public function uninstallOauth($type = '', $device = '')
    {
        if (empty($type)) {
            return false;
        }

        if ($device == 'web') {
            $install_file = plugin_path('Connect/' . Str::studly($type)) . '/install.php';

            if (file_exists($install_file)) {
                @unlink($install_file);
                return true;
            }
        }

        if ($device == 'h5') {
            $res = TouchAuth::where('type', $type)->delete();

            return $res;
        }

        return false;
    }

    /**
     * 返回回调地址
     * @param string $type qq、wechat、weibo
     * @param string $device 设备 web、h5
     * @return string
     */
    public function callbackUrl($type = '', $device = '')
    {
        if (empty($type) || empty($device)) {
            return '';
        }

        $type = $type == 'weixin' ? 'wechat' : $type;

        $result = [
            'qq' => [
                'web' => url('oauth'),
                'h5' => url('mobile/oauth/callback')
            ],
            'wechat' => [
                'web' => url('oauth'),
                'h5' => ''
            ],
            'weibo' => [
                'web' => url('oauth'),
                'h5' => url('mobile')
            ],
        ];

        return $result[$type][$device];
    }
}
