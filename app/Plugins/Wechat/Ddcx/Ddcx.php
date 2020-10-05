<?php

namespace App\Plugins\Wechat\Ddcx;

use App\Http\Controllers\Wechat\PluginController;
use App\Models\Goods;

/**
 * Class Ddcx 订单查询
 * @package App\Plugins\Wechat\Ddcx
 */
class Ddcx extends PluginController
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
     *
     * @param string $fromusername
     * @param array $info
     * @return array
     */
    public function returnData($fromusername = '', $info = [])
    {
        $articles = ['type' => 'text', 'content' => lang('wechat.order_empty')];
        $users = $this->wechatUserService->get_wechat_user_id($fromusername);
        if (empty($users) || empty($users['mobile_phone'])) {
            $articles = ['type' => 'text', 'content' => lang('wechat.ddcx_mobile_phone_empty')];
            return $articles;
        }
        if (!empty($users)) {
            // 用户最新一条订单信息
            $order = $this->wechatPluginService->userOrderInfo($users['user_id']);
            if ($order) {
                // 订单商品
                $order_goods = $order['goods'] ?? [];

                $goods = '';
                $order['goods_thumb'] = '';
                if (!empty($order_goods)) {
                    foreach ($order_goods as $key => $val) {
                        if ($key == 0) {
                            $attr = !empty($val['goods_attr']) ? "(" . $val['goods_attr'] . ")" : '';
                            $goods .= $val['goods_name'] . $attr . '(' . $val['goods_number'] . ')';
                            // 订单商品缩略图
                            $order['goods_thumb'] = Goods::where('goods_id', $val['goods_id'])->value('goods_thumb');
                        }
                    }
                }
                // 订单综合状态
                $os = lang('order.os');
                $ps = lang('order.ps');
                $ss = lang('order.ss');

                $order['order_status_format'] = $os[$order['order_status']];
                $order['pay_status_format'] = $ps[$order['pay_status']];
                $order['shipping_status_format'] = $ss[$order['shipping_status']];

                $articles = [];
                $articles['type'] = 'news';
                $articles['content'][0]['Title'] = lang('wechat.ddcx_order_sn') . $order['order_sn'];
                $articles['content'][0]['Description'] = lang('wechat.ddcx_goods') . $goods . "\r\n" . lang('wechat.ddcx_total_fee') . $order['total_fee'] . "\r\n" . lang('wechat.wechat_order_status') . $order['order_status_format'] . '-' . $order['pay_status_format'] . '-' . $order['shipping_status_format'] . "\r\n" . lang('wechat.wechat_shipping_name') . $order['shipping_name'] . "\r\n" . lang('wechat.wechat_invoice_no') . $order['invoice_no'];
                $articles['content'][0]['PicUrl'] = $this->wechatPluginService->getPicUrl($order['goods_thumb']);
                $articles['content'][0]['Url'] = dsc_url('/#/user/orderDetail/' . $order['order_id']);
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
