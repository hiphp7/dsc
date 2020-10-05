<?php

namespace App\Plugins\Wechat\Wlcx;

use App\Http\Controllers\Wechat\PluginController;

/**
 * 物流查询类
 *
 * @author wanglu
 *
 */
class Wlcx extends PluginController
{
    // 插件名称
    protected $plugin_name = '';
    // 配置
    protected $cfg = [];

    /**
     * 构造方法
     *
     * @param array $cfg
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
    public function returnData($fromusername, $info)
    {
        $articles = ['type' => 'text', 'content' => lang('wechat.wlcx_empty')];
        $users = $this->wechatUserService->get_wechat_user_id($fromusername);
        if (empty($users) || empty($users['mobile_phone'])) {
            $articles = ['type' => 'text', 'content' => lang('wechat.wlcx_mobile_phone_empty')];
            return $articles;
        }
        if (!empty($users)) {
            // 用户最新一条订单信息
            $order = $this->wechatPluginService->userOrderInfo($users['user_id']);

            if (!empty($order) && $order['shipping_status'] > 0) {
                //已发货
                if (!empty($order['shipping_name']) && !empty($order['shipping_code'])) {
                    // 配送状态
                    $ss = lang('order.ss');
                    $order['shipping_status_format'] = $ss[$order['shipping_status']];

                    $articles = [];
                    $articles['type'] = 'news';
                    $articles['content'][0]['Title'] = lang('wechat.wechat_invoice_no') . $order['invoice_no'];
                    $articles['content'][0]['Description'] = lang('wechat.wechat_shipping_name') . $order['shipping_name'] . "\r\n" . lang('wechat.wechat_shipping_status') . $order['shipping_status_format'];

                    $shipping = $this->wechatPluginService->shippingInstance($order['shipping_code']);
                    if (!is_null($shipping)) {
                        $url = route('tracker', ['type' => $shipping->get_code_name(), 'postid' => $order['invoice_no']]);
                    }
                    $articles['content'][0]['PicUrl'] = $this->wechatPluginService->getPicUrl();
                    $articles['content'][0]['Url'] = $url ?? '';
                }
            }
            // 积分赠送
            $this->wechatPluginService->updatePoint($fromusername, $info);
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
