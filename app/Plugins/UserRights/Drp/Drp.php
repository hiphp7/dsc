<?php

namespace App\Plugins\UserRights\Drp;

use App\Http\Controllers\PluginController;
use App\Plugins\UserRights\Drp\Services\DrpRightsService;


class Drp extends PluginController
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
    protected $drpRightsService;

    public function __construct()
    {
        parent::__construct();

        $this->drpRightsService = app(DrpRightsService::class);
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
     * 执行方法 付费购买分成
     * @param int $user_id
     * @param int $order_id
     * @param int $parent_id
     * @param int $money
     * @return mixed
     */
    public function actionBuyDrpLog($user_id = 0, $order_id = 0, $parent_id = 0, $money = 0)
    {
        return $this->drpRightsService->buyDrpLog($this->code, $user_id, $order_id, $parent_id, $money);
    }

    /**
     * 执行方法 指定商品分成
     * @param int $user_id
     * @param int $order_id
     * @param int $parent_id
     * @param int $money
     * @return mixed
     */
    public function actionGoodsDrpLog($user_id = 0, $order_id = 0, $parent_id = 0, $money = 0)
    {
        return $this->drpRightsService->goodsDrpLog($this->code, $user_id, $order_id, $parent_id, $money);
    }

    public function index()
    {

    }
}
