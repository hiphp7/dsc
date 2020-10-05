<?php

namespace App\Modules\Admin\Controllers;

use App\Services\Other\OauthManageService;
use Illuminate\Support\Str;

/**
 * 第三方登录接口 web
 * Class WebsiteController
 * @package App\Modules\Admin\Controllers
 */
class WebsiteController extends InitController
{
    protected $oauthManageService;

    public function __construct(
        OauthManageService $oauthManageService
    )
    {
        $this->oauthManageService = $oauthManageService;
    }

    public function index()
    {
        admin_priv('website');

        $this->smarty->assign('menu_select', ['action' => '01_system', 'current' => 'website']);

        $act = request()->input('act', '');

        if ($act == 'list') {
            // 插件列表
            $modules = $this->oauthManageService->oauthList('web');

            $website_name = '';
            foreach ($modules as $key => $val) {
                if ($val['type'] == 'wechat') {
                    unset($modules[$key]); // 不显示微信授权登录（微信端）
                }
                $website_name .= $val['name'] . ',';
            }

            $this->smarty->assign('warning', $GLOBALS['_LANG']['warning']);
            $this->smarty->assign('website_name', $website_name); // 取回已有插件
            $this->smarty->assign('action_link', ['href' => 'website.php?act=init', 'text' => $GLOBALS['_LANG']['init']]);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ur_here']);
            $this->smarty->assign('website', $modules);

            return $this->smarty->display('website.dwt');
        } elseif ($act == 'batch') {
            // 批量操作
            $type = request()->input('type', '');

            // 生成的类
            $name = request()->input('checkboxes', []);
            if (empty($name) || !is_array($name)) {
                $link[0] = ['href' => 'website.php?act=list', 'text' => $GLOBALS['_LANG']['webstte_list']];
                return sys_msg($GLOBALS['_LANG']['batch_yes'], 0, $link);
            }

            switch ($type) {
                // 生成调用代码
                case 'create':
                    break;
                case 'uninstall':

                    $modules = $this->oauthManageService->oauthList('web');
                    foreach ($modules as $key => $val) {
                        $modules[$val['type']] = $val;
                    }

                    foreach ($name as $val) {
                        if (isset($modules[$val]) && !empty($modules[$val])) {
                            $install_file = plugin_path('Connect/' . Str::studly($val)) . '/install.php';

                            if (file_exists($install_file)) {
                                @unlink($install_file);
                            }
                        }
                    }

                    break;
            }

            $link[0] = ['href' => 'website.php?act=list', 'text' => $GLOBALS['_LANG']['webstte_list']];
            return sys_msg($GLOBALS['_LANG']['batch_yes'], 0, $link);
        } elseif ($act == 'install' || $act == 'view') {

            // 安装或查看
            $view = $act == 'view';

            $type = request()->input('type', '');

            if (!$type) {
                header('Location: website.php?act=list');
            }

            $info = $this->oauthManageService->getOauthInfoWeb($type);

            if ($view) {
                $config = $this->oauthManageService->getOauthConfigWeb($type);

                if (!empty($config)) {
                    $this->smarty->assign('config', $config);
                }
            }

            // 回调地址
            $info['callback'] = $this->oauthManageService->callbackUrl($type, 'web');
            // 语言合并
            $new_lang = lang('admin/touch_oauth');
            $lang = array_merge($GLOBALS['_LANG'], $new_lang);

            $this->smarty->assign('lang', $lang);
            $this->smarty->assign('info', $info);
            $this->smarty->assign('ur_here', $view ? $GLOBALS['_LANG']['ur_view'] : $GLOBALS['_LANG']['ur_install']);
            $this->smarty->assign('action_link', ['href' => 'website.php?act=list', 'text' => $GLOBALS['_LANG']['webstte_list']]);
            $this->smarty->assign('type', $type);
            $this->smarty->assign('act', $view ? 'update_website' : 'query_install');

            return $this->smarty->display('website_install.dwt');
        } elseif ($act == 'query_install' || $act == 'update_website') {

            // 安装或更新
            $type = request()->input('type', '');

            $query = $act == 'query_install';

            $jntoo = request()->input('jntoo', '');

            $link[0] = ['href' => 'website.php?act=list', 'text' => $GLOBALS['_LANG']['webstte_list']];

            if ($jntoo) {
                $data = "<?php \r\n";
                foreach ($jntoo as $key => $val) {
                    $data .= "\$config['$key'] = '$val';\r\n";
                }

                $res = $this->oauthManageService->updateOauthWeb($type, $data);

                if ($res == true) {
                    return sys_msg(($query ? $GLOBALS['_LANG']['yes_install'] : $GLOBALS['_LANG']['yes_update']), 0, $link);
                }
            }

            return sys_msg(($query ? $GLOBALS['_LANG']['yes_install'] : $GLOBALS['_LANG']['yes_update']), 0, $link);
        } elseif ($act == 'uninstall') {
            // 卸载
            $type = request()->input('type', '');

            $res = $this->oauthManageService->uninstallOauth($type, 'web');

            $link[0] = ['href' => 'website.php?act=list', 'text' => $GLOBALS['_LANG']['webstte_list']];
            if ($res == true) {
                return sys_msg($GLOBALS['_LANG']['yes_uninstall'], 0, $link);
            }

            return sys_msg($GLOBALS['_LANG']['no_uninstall'], 1, $link);
        }
    }
}
