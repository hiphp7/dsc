<?php

namespace App\Modules\Admin\Controllers;

use App\Models\ShopConfig;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\PluginManageService;
use App\Services\Sms\SmsManageService;
use Illuminate\Http\Request;

/**
 * 短信插件管理
 * Class SmsController
 * @package App\Modules\Admin\Controllers
 */
class SmsController extends BaseController
{
    protected $timeRepository;
    protected $dscRepository;
    protected $smsManageService;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        SmsManageService $smsManageService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->smsManageService = $smsManageService;
    }

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 全部短信列表
     * @param Request $request
     * @param PluginManageService $pluginManageService
     * @return mixed
     */
    public function index(Request $request, PluginManageService $pluginManageService)
    {
        // 数据库
        $list = $this->smsManageService->smsList();

        $code_arr = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $code_arr[$value['code']] = $value;
            }
        }

        // 所有列表
        $new_plugins = [];
        $plugins = $pluginManageService->readPlugins('Sms');
        if (!empty($plugins)) {
            foreach ($plugins as $k => $v) {

                $plugins[$k]['name'] = $GLOBALS['_LANG'][$v['code']];
                $plugins[$k]['description'] = $GLOBALS['_LANG'][$v['description']];

                // 数据库中存在，用数据库的数据
                if (isset($code_arr[$v['code']])) {
                    $new_plugins[] = array_merge($plugins[$k], $code_arr[$v['code']]);
                } else {
                    $new_plugins[] = $plugins[$k];
                }
            }
        }

        if (!empty($new_plugins)) {
            // 按sort排序
            $collection = collect($new_plugins)->sortBy('sort')->values();

            $new_plugins = $collection->toArray();
        }

        if (empty($list)) {
            // 同步旧短信配置
            return $this->syncOldSmsConfig($request, $pluginManageService);
        }

        $this->assign('plugins', $new_plugins);
        return $this->display();
    }

    /**
     * 安装、编辑短信
     * @param Request $request
     * @param PluginManageService $pluginManageService
     * @return mixed
     */
    public function edit(Request $request, PluginManageService $pluginManageService)
    {
        if ($request->isMethod('POST')) {

            $code = $request->input('code', '');
            $handler = $request->input('handler', ''); // edit\install

            $data = $request->input('data', []); // 基本配置

            if (empty($code) || empty($handler)) {
                return $this->message(lang('admin/sms.code_empty'), route('admin.sms.index'));
            }

            // 保存配置
            $cfg_value = $request->input('cfg_value', []);
            $cfg_label = $request->input('cfg_label', []);
            $cfg_name = $request->input('cfg_name', []);
            $cfg_type = $request->input('cfg_type', []);
            $cfg_range = $request->input('cfg_range', []);

            $sms_configure = [];

            if (!empty($cfg_value) && is_array($cfg_value)) {
                for ($i = 0; $i < count($cfg_value); $i++) {

                    // 判断 cfg_value[1] 是否修改,若没修改取原值存入config
                    if (stripos($cfg_value[$i], '*') !== false) {
                        $old_sms_config = $this->smsManageService->getSmsConfigure($code);
                        $cfg_value[$i] = $old_sms_config[$i]['value'];
                    }

                    $sms_configure[] = [
                        'name' => trim($cfg_name[$i]),
                        'type' => trim($cfg_type[$i]),
                        'value' => trim($cfg_value[$i]),
                        'label' => $cfg_label[$i] ?? '',
                    ];
                }
            }

            $data['sms_configure'] = empty($sms_configure) ? '' : \Opis\Closure\serialize($sms_configure);
            $data['enable'] = 1;

            // 设置默认
            $data['default'] = 1;
            $this->smsManageService->setDefault($code);

            if (!empty($handler) && $handler == 'edit') {
                // 编辑
                $res = $this->smsManageService->update($code, $data);
                if ($res) {
                    return $this->message(lang('admin/common.enabled') . lang('admin/common.success'), route('admin.sms.index'));
                }
            } elseif (!empty($handler) && $handler == 'install') {
                // 安装
                $res = $this->smsManageService->create($code, $data);
                if ($res) {
                    return $this->message(lang('admin/common.install') . lang('admin/common.success'), route('admin.sms.index'));
                }
            }

            return $this->message(lang('admin/common.handler') . lang('admin/common.fail'), route('admin.sms.index'));
        }

        // 操作
        $code = $request->input('code', '');
        $handler = $request->input('handler', 'install'); // edit\install

        if (empty($code)) {
            return $this->message(lang('admin/sms.code_empty'), route('admin.sms.index'));
        }

        // 获取配置信息
        $info = [];
        if (!empty($handler) && $handler == 'edit') {
            // 获取配置信息
            $info = $this->smsManageService->smsInfo($code);
        }

        // 插件实例
        $obj = $pluginManageService->pluginInstance($code, 'Sms');

        if (!is_null($obj)) {
            // 插件配置
            $cfg = $pluginManageService->getPluginConfig($code, 'Sms', $info);

            if (isset($cfg['sms_configure']) && !empty($cfg['sms_configure'])) {
                // 循环配置插件中所有属性
                foreach ($cfg['sms_configure'] as $k => $val) {
                    // 配置项 encrypt 以*加密处理
                    if (isset($val['encrypt']) && $val['encrypt'] == true) {
                        $cfg['sms_configure'][$k]['value'] = string_to_star($val['value']);
                    }
                }
            }

            $obj->setPluginInfo($cfg);

            return $obj->install();
        }
    }

    /**
     * 卸载插件
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uninstall(Request $request)
    {
        $code = $request->input('code', '');

        if (empty($code)) {
            return response()->json(['error' => 1, 'msg' => lang('admin/sms.code_empty')]);
        }

        // 卸载
        $res = $this->smsManageService->uninstall($code);
        if ($res) {
            return response()->json(['error' => 0, 'msg' => lang('admin/common.uninstall') . lang('admin/common.success')]);
        }

        return response()->json(['error' => 1, 'msg' => lang('admin/common.uninstall') . lang('admin/common.fail')]);
    }

    /**
     * 同步兼容旧短信配置
     * @param Request $request
     * @param PluginManageService $pluginManageService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncOldSmsConfig(Request $request, PluginManageService $pluginManageService)
    {
        $oldSmsConfig = $this->smsManageService->oldSmsConfig();

        // 原选择的短信
        $install_code = $oldSmsConfig['code'] ?? '';

        if (!empty($oldSmsConfig['list'])) {
            foreach ($oldSmsConfig['list'] as $code => $item) {

                // 插件实例
                $obj = $pluginManageService->pluginInstance($code, 'Sms');

                $cfg = [];
                if (!is_null($obj)) {
                    // 插件配置
                    $cfg = $pluginManageService->getPluginConfig($code, 'Sms');
                }

                $data = [
                    'name' => $cfg['name'] ?? '',
                    'description' => $cfg['description'] ?? '',
                    'website' => $cfg['website'] ?? '',
                    'sort' => $cfg['sort'] ?? '',
                    'enable' => 1,
                ];

                $sms_configure = [];
                if (!empty($cfg['sms_configure'])) {
                    foreach ($cfg['sms_configure'] as $value) {
                        $value['value'] = $item[$value['name']] ?? '';

                        $sms_configure[] = $value;
                    }
                }

                $data['sms_configure'] = empty($sms_configure) ? '' : \Opis\Closure\serialize($sms_configure);

                if ($install_code && $install_code == $code) {
                    // 安装原选择的短信 并设置为默认
                    $data['default'] = 1;
                }

                $res = $this->smsManageService->create($code, $data);

                if ($res) {
                    // 隐藏原短信配置项
                    $code_arr = collect($item)->mapWithKeys(function ($item, $key) {
                        $arr[$key] = $key;
                        return $arr;
                    })->values()->toArray();

                    ShopConfig::query()->where('type', '<>', 'hidden')->where(function ($query) use ($code_arr) {
                        $query->whereIn('code', $code_arr)->orWhere('code', 'sms_type');
                    })->update(['type' => 'hidden']);
                }
            }
        }

        return redirect()->route('admin.sms.index');
    }
}
