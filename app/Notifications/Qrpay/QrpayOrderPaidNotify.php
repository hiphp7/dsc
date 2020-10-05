<?php

namespace App\Notifications\Qrpay;

use App\Services\Qrpay\QrpayService;
use Payment\Notify\PayNotifyInterface;

/**
 * 付款码异步通知
 * Class QrpayOrderPaidNotify
 * @package App\Notifications\Qrpay
 */
class QrpayOrderPaidNotify implements PayNotifyInterface
{
    protected $qrpayService;

    /**
     * OrderPaidNotify constructor.
     * @param QrpayService $qrpayService
     */
    public function __construct(
        QrpayService $qrpayService
    )
    {
        $this->qrpayService = $qrpayService;
    }

    public function notifyProcess(array $data)
    {
        /**
         * 改变支付状态
         *
         */
        // logResult('notify_data:');
        // logResult($data);
        $out_trade_no = explode('Q', $data['order_no']);
        $log_id = $out_trade_no['1']; // 订单号log_id
        $this->qrpayService->qrpay_order_paid($log_id, 1);

        // 保存交易信息
        $this->qrpayService->update_trade_data($log_id, $data);

        // 自动结算 商家账户
        $this->qrpayService->insert_seller_account_log($log_id);

        return true;
    }
}
