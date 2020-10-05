<?php

namespace App\Plugins\UserRights\DrpGoods;

use App\Http\Controllers\PluginController;
use App\Plugins\UserRights\DrpGoods\Services\DrpGoodsRightsService;


class DrpGoods extends PluginController
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
    protected $drpGoodsRightsService;

    public function __construct()
    {
        parent::__construct();

        $this->drpGoodsRightsService = app(DrpGoodsRightsService::class);
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
     * 执行方法 订单商品分成
     * @return mixed
     */
    public function actionOrderDrpLog()
    {
        // 原 addDrpLog 方法
        return $this->drpGoodsRightsService->orderDrpLog($this->code);
    }

    public function index()
    {

    }
}
