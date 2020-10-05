<?php

namespace App\Plugins\UserRights\Register;

use App\Http\Controllers\PluginController;
use App\Plugins\UserRights\Register\Services\RegisterRightsService;


class Register extends PluginController
{
    // 插件名称
    public $plugin_name = '';
    public $code = '';

    // 配置
    protected $cfg = [];

    /**
     * service
     * @var \Illuminate\Contracts\Foundation\Application|mixed
     */
    protected $registerRightsService;

    public function __construct()
    {
        parent::__construct();

        $this->registerRightsService = app(RegisterRightsService::class);
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPluginInfo($value)
    {
        $this->cfg = $value;
        return $this;
    }

    /**
     * 查询信息
     * @return array
     */
    public function getPluginInfo()
    {
        $cfg = $this->cfg;

        if (!empty($cfg)) {
            // 查询注册优惠券列表
            $range = $this->registerRightsService->getRegisterCoupons(1);

            $rights_configure = $cfg['rights_configure'] ?? [];

            if (!empty($rights_configure)) {
                foreach ($rights_configure as $k => $value) {
                    if ($value['name'] == 'cou_type' && $value['type'] == 'select') {
                        $rights_configure[$k]['range'] = $range;
                    }
                }
            }

            $cfg['rights_configure'] = $rights_configure;
        }

        $this->cfg = $cfg;

        return $this->cfg;
    }

    /**
     * 安装
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function install()
    {
        $cfg = $this->getPluginInfo();

        $this->plugin_assign('cfg', $cfg);
        $this->assign('page_title', $this->plugin_name);
        return $this->plugin_display('install');
    }

    /**
     * 执行方法 注册送优惠券
     * @param int $user_id
     * @return bool
     */
    public function actionRegisterSendCoupons($user_id = 0)
    {
        return $this->registerRightsService->registerSendCoupons($this->code, $user_id);
    }


    public function index()
    {

    }
}