<?php

namespace App\Message;

use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Message;
use Overtrue\EasySms\Strategies\OrderStrategy;

class OrderPaidMessage extends Message
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $strategy = OrderStrategy::class; // 定义本短信的网关使用策略，覆盖全局配置中的 `default.strategy`

    /**
     * @var array
     */
    protected $gateways = ['huyi']; // 定义本短信的适用平台，覆盖全局配置中的 `default.gateways`

    /**
     * OrderPaidMessage constructor.
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * 定义直接使用内容发送平台的内容
     * @param GatewayInterface|null $gateway
     * @return string
     */
    public function getContent(GatewayInterface $gateway = null)
    {
        return sprintf('您的订单:%s, 已经完成付款', $this->order->no);
    }

    /**
     * 定义使用模板发送方式平台所需要的模板 ID
     * @param GatewayInterface|null $gateway
     * @return string
     */
    public function getTemplate(GatewayInterface $gateway = null)
    {
        return 'SMS_003';
    }

    /**
     * 模板参数
     * @param GatewayInterface|null $gateway
     * @return array
     */
    public function getData(GatewayInterface $gateway = null)
    {
        return [
            'order_no' => $this->order->no
        ];
    }
}
