<?php

namespace App\Notifications\Order;

use App\Services\Payment\PaymentService;
use App\Services\User\RefoundService;
use Payment\Config;
use Payment\Notify\PayNotifyInterface;

/**
 * 支付异步通知
 *
 * 客户端需要继承该接口，并实现这个方法，在其中实现对应的业务逻辑
 * Class OrderPaidNotify
 */
class OrderPaidNotify implements PayNotifyInterface
{
    protected $refoundService;
    protected $paymentService;

    /**
     * OrderPaidNotify constructor.
     * @param RefoundService $refoundService
     * @param PaymentService $paymentService
     */
    public function __construct(
        RefoundService $refoundService,
        PaymentService $paymentService
    )
    {
        $this->refoundService = $refoundService;
        $this->paymentService = $paymentService;
    }

    public function notifyProcess(array $data)
    {
        // 初始值
        $log_id = 0;
        $order_sn = '';
        $pay_code = '';

        $channel = $data['channel'];
        if ($channel === Config::ALI_CHARGE) {
            $pay_code = 'alipay';
            // 支付宝支付 alipay 退款的异步通知是依据支付接口的触发条件来触发的，异步通知也是发送到支付接口传入的异步地址上

            $order_sn = $data['subject'];

            $payment = $this->paymentService->getPayment($pay_code);
            $log_id = $payment->parse_trade_no($data['order_no']); // 商户订单号 out_trade_no

            $refund_fee = $data['refund_fee'] ?? 0; // 退款金额
            $trade_refund_time = $data['trade_refund_time'] ?? 0; // 交易退款时间
            /**
             * 改变退换货订单状态
             * 这里如果退款触发了异步信息，退款的异步信息中会有 refund_fee 退款总金额参数，如果有这个参数就可以确定这一笔退款成功了
             */
            if ($refund_fee && $trade_refund_time) {
                $this->refoundService->refund_paid($order_sn, 2, $order_sn);

                // 记录退款交易信息
                $this->paymentService->updateReturnLog('', $order_sn, $data);
            }
        } elseif ($channel === Config::WX_CHARGE) {
            // 微信支付
            $pay_code = 'wxpay';

            $order_sn = $data['order_no'];
        }

        /**
         * 改变订单状态
         */
        order_paid($log_id, PS_PAYED, $order_sn);

        // 记录交易信息
        $this->paymentService->updatePayLog($log_id, $data);

        // 优化切换支付方式后支付 更新订单支付方式
        $this->paymentService->updateOrderPayment($order_sn, $pay_code);

        return true;
    }
}
