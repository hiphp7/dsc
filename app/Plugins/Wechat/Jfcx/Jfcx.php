<?php

namespace App\Plugins\Wechat\Jfcx;

use App\Http\Controllers\Wechat\PluginController;

/**
 * Class Jfcx 积分查询
 * @package App\Plugins\Wechat\Jfcx
 */
class Jfcx extends PluginController
{
    // 插件名称
    protected $plugin_name = '';
    // 配置
    protected $cfg = [];

    /**
     * 构造方法
     *
     * @param  $cfg
     */
    public function __construct($cfg = [])
    {
        parent::__construct();
        $this->plugin_name = strtolower(basename(__FILE__, '.php'));
        $this->cfg = $cfg;

        $this->plugin_assign('config', $this->cfg);
    }

    /**
     * 安装
     */
    public function install()
    {
        return $this->plugin_display('install', $this->_data);
    }

    /**
     * 获取数据
     */
    public function returnData($fromusername = '', $info = [])
    {
        $articles = ['type' => 'text', 'content' => lang('wechat.jfcx_empty')];
        $users = $this->wechatUserService->get_wechat_user_id($fromusername);
        if (empty($users) || empty($users['mobile_phone'])) {
            $articles = ['type' => 'text', 'content' => lang('wechat.jfcx_mobile_phone_empty')];
            return $articles;
        }
        if (!empty($users)) {
            $data = $this->wechatPluginService->userInfo($users['user_id']);

            if (!empty($data)) {
                $articles['content'] = lang('wechat.user_money') . $data['user_money_format'] . "\r\n" . lang('wechat.rank_points') . $data['rank_points'] . "\r\n" . lang('wechat.pay_points') . $data['pay_points'];
                // 积分赠送
                $this->wechatPluginService->updatePoint($fromusername, $info);
            }
        }
        return $articles;
    }

    /**
     * 行为操作
     */
    public function executeAction()
    {
    }
}
