<?php

namespace App\Services\Common;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use Illuminate\Support\Str;


class PluginManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 插件列表
     *
     * @param string $directory 插件目录
     * @return array
     */
    public function readPlugins($directory = '/')
    {
        if (empty($directory)) {
            return [];
        }

        $directory = Str::studly($directory);
        $plugins = glob(plugin_path($directory . '/*/config.php'));

        $cfg = [];
        foreach ($plugins as $i => $file) {
            $cfg[] = require_once($file);
        }

        return $cfg;
    }

    /**
     * 返回插件实例
     *
     * @param string $plugin_name
     * @param string $directory 插件目录
     * @return \Illuminate\Foundation\Application|mixed|null
     */
    public function pluginInstance($plugin_name = '', $directory = '/')
    {
        if (empty($plugin_name)) {
            return null;
        }

        $obj = null;

        $plugin = Str::studly($plugin_name);
        $class = '\\App\Plugins\\' . $directory . '\\' . $plugin . '\\' . $plugin;

        if (class_exists($class)) {
            // 插件对象
            $obj = new $class();
        }

        return $obj;
    }

    /**
     * 获取插件配置信息
     *
     * @param string $plugin_name
     * @param string $directory 插件目录
     * @param array $info
     * @return array|mixed
     */
    public function getPluginConfig($plugin_name = '', $directory = '/', $info = [])
    {
        if (empty($plugin_name)) {
            return [];
        }

        $plugin = Str::studly($plugin_name);
        $config_file = plugin_path($directory . '/' . $plugin . '/config.php');
        $data = require($config_file);

        if ($directory && $directory == 'Sms') {
            return $this->transformSmsConfig($data, $info);
        }

        if ($directory && $directory == 'UserRights') {
            return $this->transformUserRightsConfig($data, $info);
        }

        return $data;
    }

    /**
     * 初始化短信插件配置
     * @param array $data
     * @param array $info
     * @return array
     */
    protected function transformSmsConfig($data = [], $info = [])
    {
        if (empty($data)) {
            return [];
        }

        if (!empty($info)) {
            // 编辑配置信息
            if (!empty($info['sms_configure']) && is_array($info['sms_configure'])) {
                /* 取出已经设置属性的code */
                $code_list = [];
                foreach ($info['sms_configure'] as $key => $value) {
                    $code_list[$value['name']] = $value['value'];

                    $code_list[$value['name'] . '_range'] = $value['range'] ?? null;
                }

                $info['sms_configure'] = [];

                if (isset($data['sms_configure']) && $data['sms_configure']) {
                    foreach ($data['sms_configure'] as $key => $value) {

                        $info['sms_configure'][$key]['desc'] = $GLOBALS['_LANG'][$value['name'] . '_desc'] ?? '';
                        $info['sms_configure'][$key]['label'] = $GLOBALS['_LANG'][$value['name']] ?? '';
                        $info['sms_configure'][$key]['name'] = $value['name'];
                        $info['sms_configure'][$key]['type'] = $value['type'];
                        // 是否加密处理
                        $info['sms_configure'][$key]['encrypt'] = $value['encrypt'] ?? false;

                        if (isset($code_list[$value['name']])) {
                            $info['sms_configure'][$key]['value'] = $code_list[$value['name']];
                        } else {
                            $info['sms_configure'][$key]['value'] = $GLOBALS['_LANG'][$value['name'] . '_value'] ?? $value['value'];
                        }

                        if (isset($code_list[$value['name'] . '_range'])) {
                            if ($info['sms_configure'][$key]['type'] == 'select' || $info['sms_configure'][$key]['type'] == 'radiobox') {
                                $info['sms_configure'][$key]['range'] = $code_list[$value['name'] . '_range'] ?? [];
                            }
                        } else {
                            if ($info['sms_configure'][$key]['type'] == 'select' || $info['sms_configure'][$key]['type'] == 'radiobox') {
                                $info['sms_configure'][$key]['range'] = $GLOBALS['_LANG'][$info['sms_configure'][$key]['name'] . '_range'] ?? [];
                            }
                        }
                    }
                }
            }

            $data = $info;

            $data['handler'] = 'edit';

        } else {
            // 安装
            $data['name'] = $GLOBALS['_LANG'][$data['code']];
            $data['description'] = $GLOBALS['_LANG'][$data['description']];

            // 取得默认配置信息
            if (isset($data['sms_configure']) && $data['sms_configure']) {
                foreach ($data['sms_configure'] as $key => $value) {

                    $data['sms_configure'][$key]['desc'] = $GLOBALS['_LANG'][$value['name'] . '_desc'] ?? '';
                    $data['sms_configure'][$key]['label'] = $GLOBALS['_LANG'][$value['name']] ?? '';
                    $data['sms_configure'][$key]['name'] = $value['name'];
                    $data['sms_configure'][$key]['type'] = $value['type'];
                    // 是否加密处理
                    $data['sms_configure'][$key]['encrypt'] = $value['encrypt'] ?? false;

                    $data['sms_configure'][$key]['value'] = $GLOBALS['_LANG'][$value['name'] . '_value'] ?? $value['value'];

                    if ($data['sms_configure'][$key]['type'] == 'select' || $data['sms_configure'][$key]['type'] == 'radiobox') {
                        $data['sms_configure'][$key]['range'] = $GLOBALS['_LANG'][$data['sms_configure'][$key]['name'] . '_range'] ?? [];
                    }
                }
            }

            $data['handler'] = 'install';
        }

        return $data;
    }

    /**
     * 初始化会员权益插件配置
     * @param array $data
     * @param array $info
     * @return array
     */
    protected function transformUserRightsConfig($data = [], $info = [])
    {
        if (empty($data)) {
            return [];
        }

        if (!empty($info)) {
            // 编辑 取得编辑权益配置信息
            if (!empty($info['rights_configure']) && is_array($info['rights_configure'])) {
                /* 取出已经设置属性的code */
                $code_list = [];
                foreach ($info['rights_configure'] as $key => $value) {
                    $code_list[$value['name']] = $value['value'];

                    $code_list[$value['name'] . '_range'] = $value['range'] ?? null;
                }

                $info['rights_configure'] = [];

                if (isset($data['rights_configure']) && $data['rights_configure']) {
                    foreach ($data['rights_configure'] as $key => $value) {

                        $info['rights_configure'][$key]['desc'] = $GLOBALS['_LANG'][$value['name'] . '_desc'] ?? '';
                        $info['rights_configure'][$key]['label'] = $GLOBALS['_LANG'][$value['name']] ?? '';
                        $info['rights_configure'][$key]['name'] = $value['name'];
                        $info['rights_configure'][$key]['type'] = $value['type'];

                        if (isset($code_list[$value['name']])) {
                            $info['rights_configure'][$key]['value'] = $code_list[$value['name']];
                        } else {
                            $info['rights_configure'][$key]['value'] = $GLOBALS['_LANG'][$value['name'] . '_value'] ?? $value['value'];
                        }

                        if (isset($code_list[$value['name'] . '_range'])) {
                            if ($info['rights_configure'][$key]['type'] == 'select' || $info['rights_configure'][$key]['type'] == 'radiobox') {
                                $info['rights_configure'][$key]['range'] = $code_list[$value['name'] . '_range'] ?? [];
                            }
                        } else {
                            if ($info['rights_configure'][$key]['type'] == 'select' || $info['rights_configure'][$key]['type'] == 'radiobox') {
                                $info['rights_configure'][$key]['range'] = $GLOBALS['_LANG'][$info['rights_configure'][$key]['name'] . '_range'] ?? [];
                            }
                        }
                    }
                }
            }

            $data = $info;

            $data['handler'] = 'edit';

        } else {
            // 安装
            $data['name'] = $GLOBALS['_LANG'][$data['code']];
            $data['description'] = $GLOBALS['_LANG'][$data['description']];

            // 取得默认权益配置信息
            if (isset($data['rights_configure']) && $data['rights_configure']) {
                foreach ($data['rights_configure'] as $key => $value) {

                    $data['rights_configure'][$key]['desc'] = $GLOBALS['_LANG'][$value['name'] . '_desc'] ?? '';
                    $data['rights_configure'][$key]['label'] = $GLOBALS['_LANG'][$value['name']] ?? '';
                    $data['rights_configure'][$key]['name'] = $value['name'];
                    $data['rights_configure'][$key]['type'] = $value['type'];

                    $data['rights_configure'][$key]['value'] = $GLOBALS['_LANG'][$value['name'] . '_value'] ?? $value['value'];

                    if ($data['rights_configure'][$key]['type'] == 'select' || $data['rights_configure'][$key]['type'] == 'radiobox') {
                        $data['rights_configure'][$key]['range'] = $GLOBALS['_LANG'][$data['rights_configure'][$key]['name'] . '_range'] ?? [];
                    }
                }
            }

            $data['handler'] = 'install';
        }

        if (!empty($data)) {
            $data['icon'] = (stripos($data['icon'], 'assets') !== false) ? asset($data['icon']) : $this->dscRepository->getImagePath($data['icon']);
            $data['trigger_point_format'] = empty($data['trigger_point']) ? '' : lang('admin/users.trigger_point_' . $data['trigger_point']);
        }

        return $data;
    }

}
