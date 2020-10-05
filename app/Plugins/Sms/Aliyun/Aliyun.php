<?php

namespace App\Plugins\Sms\Aliyun;

use App\Http\Controllers\PluginController;


class Aliyun extends PluginController
{
    // 插件名称
    public $plugin_name = '';
    public $code = '';

    // 配置
    protected $cfg = [];


    public function __construct()
    {
        parent::__construct();
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
     * 执行方法
     * @return mixed
     */
    public function actionSend()
    {
        return 'send';
    }

    public function index()
    {

    }
}
