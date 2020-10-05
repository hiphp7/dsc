<?php

namespace App\Plugins\Payment\Alipay;

use App\Models\OrderReturn;
use App\Notifications\Order\OrderPaidNotify;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Log;
use Payment\Client\Charge;
use Payment\Client\Notify;
use Payment\Client\Query;
use Payment\Client\Refund;
use Payment\Common\PayException;
use Payment\Config;

class Alipay
{
    protected $paymentService;

    public function __construct(
        PaymentService $paymentService
    )
    {
        $this->paymentService = $paymentService;
    }

    /**
     * 生成支付代码
     *
     * @param $order
     * @return mixed|string
     */
    public function get_code($order)
    {
        // 订单信息
        $payData = [
            'body' => $order['order_sn'],
            'subject' => (isset($order['subject']) && !empty($order['subject'])) ? $order['subject'] : $order['order_sn'],
            'order_no' => $this->make_trade_no($order['log_id'], $order['order_amount']),
            'timeout_express' => time() + 3600 * 24,// 表示必须 24h 内付款
            'amount' => $order['order_amount'],// 单位为元 ,最小为0.01
            'return_param' => (string)$order['log_id'],// 一定不要传入汉字，只能是 字母 数字组合
            'client_ip' => request()->getClientIp(),// 客户地址
            'goods_type' => 1,
            'store_id' => '',
        ];

        // App 支付
        $platform = request()->get('platform');
        if ($platform === 'APP') {
            $channel = Config::ALI_CHANNEL_APP;
            $refer = 'app';
        } else {
            $channel = is_mobile_device() ? Config::ALI_CHANNEL_WAP : Config::ALI_CHANNEL_WEB;
            // 来源
            $refer = is_mobile_device() ? 'mobile' : '';
        }

        try {
            $payUrl = Charge::run($channel, $this->getConfig($refer), $payData);
        } catch (PayException $e) {
            // 异常处理
            Log::error($e->getMessage());
        }

        /* 生成支付按钮 */
        if (isset($payUrl)) {
            if ($refer == 'app' || (isset($order['merge']) && $order['merge'] == 1)) {
                return $payUrl;
            } elseif (is_mobile_device()) {
                return '<a type="button" class="box-flex btn btn-submit min-two-btn" onclick="javascript:_AP.pay(\'' . $payUrl . '\')">支付宝支付</a>';
            } else {
                return '<div style="text-align: center"><input type="button" onclick="window.open(\'' . $payUrl . '\')" value="支付宝支付" /></div>';
            }
        }

        Log::error('Alipay Generate Abnormal');
        return '';
    }

    /**
     * 支付同步通知 (PC)
     * @return mixed
     */
    public function respond()
    {
        try {

            $log_id = $this->parse_trade_no(request()->get('out_trade_no'));
            $order = [];
            $order['log_id'] = $log_id;

            return $this->orderQuery($order);
        } catch (PayException $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    /**
     * 支付同步通知 (手机)
     * @return mixed
     */
    public function callback()
    {
        return $this->respond();
    }

    /**
     * 支付异步通知 (PC+手机)
     *
     * @param string $refer 来源
     * @param string $type 支付类型
     * @return mixed
     */
    public function notify($refer = '', $type = '')
    {
        try {
            $callback = app(OrderPaidNotify::class);

            $ret = Notify::run(Config::ALI_CHARGE, $this->getConfig($refer), $callback);// 处理回调，内部进行了签名检查
            return $ret;
        } catch (PayException $e) {
            Log::error($e->getMessage());
            exit('fail');
        }
    }

    /**
     * 退款异步通知 (PC+手机)
     * alipay 退款的异步通知是依据支付接口的触发条件来触发的，异步通知也是发送到支付接口传入的异步地址上
     *
     * @param string $refer 来源
     * @return string
     */
    public function notify_refound($refer = '')
    {
        // 这里如果退款触发了异步信息，退款的异步信息中会有 refund_fee 退款总金额参数，如果有这个参数就可以确定这一笔退款成功了
        return $this->notify($refer);
    }

    /**
     * 订单查询
     * @param array $order
     * @return mixed
     */
    public function orderQuery($order = [])
    {
        $payLog = $this->paymentService->getUnPayOrder($order['log_id']);

        if (empty($payLog)) {
            return false;
        }

        // 查询未支付的订单
        if ($payLog['is_paid'] == 0) {

            $data = [
                'out_trade_no' => $this->make_trade_no($payLog['log_id'], $payLog['order_amount']),
            ];

            $refer = '';
            if (isset($payLog['referer']) && $payLog['referer'] == 'H5') {
                $refer = 'mobile';
            }

            try {
                $ret = Query::run(Config::ALI_CHARGE, $this->getConfig($refer), $data);

                if ($ret) {
                    if (isset($ret['response']) && $ret['response']['trade_state'] === Config::TRADE_STATUS_SUCC) {
                        order_paid($payLog['log_id'], PS_PAYED);
                        return true;
                    }
                    return false;
                }
                return false;
            } catch (PayException $e) {
                logResult($e->getMessage());
            }
        } elseif ($payLog['is_paid'] == 1) {
            return true;
        }

        return false;
    }

    /**
     * 退款申请接口
     * array(
     *     'order_id' => '1',
     *     'order_sn' => '2017061609464501623',
     *     'return_sn' => '2018112218244971853'
     *     'should_return' => '11',
     *     'pay_id' => '',
     *     'pay_status' => 2
     * )
     *
     * @param array $return_order
     * @return bool
     */
    public function refund($return_order = [])
    {
        // 查询已支付的订单
        $payLog = $this->paymentService->getPaidOrder($return_order['order_id']);

        if (empty($payLog)) {
            return false;
        }

        // 已付款的订单 才可申请退款
        if ($payLog['is_paid'] == 1 && $return_order['pay_status'] == PS_PAYED) {
            $refund_fee = !empty($return_order['should_return']) ? $return_order['should_return'] : $payLog['order_amount'];   // 退款金额 默认退全款

            $data = [
                'refund_fee' => $refund_fee,
                'reason' => $return_order['return_brief'] ?? '在线退款申请',
                'refund_no' => $return_order['return_sn'],
            ];

            // 支付宝交易号 trade_no ， 与 商户订单号 out_trade_no 必须二选一
            if (isset($payLog['pay_trade_data']) && !empty($payLog['pay_trade_data'])) {
                $pay_trade_data = dsc_decode($payLog['pay_trade_data'], true);
                $trade_no = $pay_trade_data['transaction_id'] ?? '';

                $data['out_trade_no'] = '';
                $data['trade_no'] = $trade_no;
            } else {
                // 商户订单号
                $out_trade_no = $this->make_trade_no($payLog['log_id'], $payLog['order_amount']);
                $data['out_trade_no'] = $out_trade_no;
                $data['trade_no'] = '';
            }

            $refer = '';
            if (isset($payLog['referer']) && $payLog['referer'] == 'H5') {
                $refer = 'mobile';
            }

            try {
                $ret = Refund::run(Config::ALI_REFUND, $this->getConfig($refer), $data);

                if ($ret) {
                    // 退款成功
                    if (isset($ret['is_success']) && $ret['is_success'] == 'T' && isset($ret['response']['refund_fee']) && isset($ret['response']['refund_time'])) {
                        return true;
                    }
                }
                return false;
            } catch (PayException $e) {
                logResult($e->getMessage());
                return false;
            }
        }
        return false;
    }

    /**
     * 查询退款接口
     * @param $return_order
     * @return bool
     */
    public function refundQuery($return_order = [])
    {
        // 查询退换货表已申请、未退款的退换货订单
        $order_return_info = OrderReturn::select('return_sn', 'order_sn', 'return_status', 'refound_status', 'agree_apply')
            ->where(['order_id' => $return_order['order_id']])
            ->where('refound_status', '<>', 1)
            ->where(function ($query) {
                $query->whereIn('return_type', [1, 3]);
            })
            ->first();
        $order_return_info = $order_return_info ? $order_return_info->toArray() : [];

        if ($order_return_info && $order_return_info['agree_apply'] == 1) {
            $payLog = $this->paymentService->getPaidOrder($return_order['order_id']);
            // 商户订单号
            $out_trade_no = $this->make_trade_no($payLog['log_id'], $payLog['order_amount']);
            $data = [
                'out_trade_no' => $out_trade_no,
                'trade_no' => '', // 支付宝交易号， 与 out_trade_no 必须二选一
                'refund_no' => $order_return_info['return_sn'],
            ];

            $refer = '';
            if (isset($payLog['referer']) && $payLog['referer'] == 'H5') {
                $refer = 'mobile';
            }

            try {
                $ret = Query::run(Config::ALI_REFUND, $this->getConfig($refer), $data);

                if ($ret) {
                    // 退款成功
                    if (isset($ret['is_success']) && $ret['is_success'] == 'T' && isset($ret['response']['refund_fee']) && isset($ret['response']['refund_time'])) {
                        return true;
                    }
                }
                return false;
            } catch (PayException $e) {
                logResult($e->errorMessage());
                return false;
            }
        }
        return false;
    }

    /**
     * 获取配置参数
     * @return array
     */
    private function getConfig($refer = '')
    {
        load_helper('payment');
        $payment = get_payment('alipay');

        if (empty($payment)) {
            return [];
        }

        $use_sandbox = isset($payment['use_sandbox']) ? (bool)$payment['use_sandbox'] : false;

        $config = [
            'use_sandbox' => $use_sandbox,
            'partner' => $payment['alipay_partner'] ?? '',
            'app_id' => $payment['app_id'] ?? '',
            'sign_type' => $payment['sign_type'] ?? 'RSA2',
            // 可以填写文件路径，或者密钥字符串  当前字符串是 rsa2 的支付宝公钥(开放平台获取)
            'ali_public_key' => $payment['ali_public_key'] ?? '',
            // 可以填写文件路径，或者密钥字符串  我的沙箱模式，rsa与rsa2的私钥相同，为了方便测试
            'rsa_private_key' => $payment['rsa_private_key'] ?? '',
            'notify_url' => route('notify') . '/alipay', // 支付异步通知地址
            'return_url' => return_url('alipay'), // 支付同步通知地址
            'return_raw' => false,
        ];
        // 手机通知地址
        if ($refer == 'mobile' || $refer == 'app') {
            $config['notify_url'] = route('api.payment.notify') . '/alipay'; // 支付异步通知地址
            $config['return_url'] = route('mobile.pay.respond', ['code' => 'alipay']); // 支付同步通知地址
        }

        return $config;
    }

    /**
     * 生成支付订单号
     * @param $log_id
     * @param $order_amount
     * @return string
     */
    public function make_trade_no($log_id, $order_amount)
    {
        $trade_no = '6';
        $trade_no .= str_pad($log_id, 15, 0, STR_PAD_LEFT);
        $trade_no .= str_pad($order_amount * 100, 16, 0, STR_PAD_LEFT);

        return $trade_no;
    }

    /**
     * 获取log_id
     * @param $trade_no
     * @return int
     */
    public function parse_trade_no($trade_no)
    {
        $log_id = substr($trade_no, 1, 15);

        return intval($log_id);
    }
}
