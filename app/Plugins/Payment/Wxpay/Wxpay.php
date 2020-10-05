<?php

namespace App\Plugins\Payment\Wxpay;

use App\Libraries\Wechat;
use App\Models\OrderReturn;
use App\Models\PayLog;
use App\Services\Payment\PaymentService;
use App\Services\User\RefoundService;
use App\Services\Wechat\WechatUserService;
use App\Services\Wechat\WxappService;
use Illuminate\Support\Facades\Log;

/**
 * 微信支付
 *
 * Class Wxpay
 * @package App\Plugins\Payment\Wxpay
 */
class Wxpay
{
    protected $parameters; // cft 参数
    protected $payment; // 配置信息
    protected $config;
    protected $wechatUserService;
    protected $paymentService;
    protected $wxappService;

    public function __construct(
        WechatUserService $wechatUserService,
        PaymentService $paymentService,
        WxappService $wxappService
    )
    {
        $this->wechatUserService = $wechatUserService;
        $this->paymentService = $paymentService;
        $this->wxappService = $wxappService;

        $shopConfig = cache('shop_config');

        if (is_null($shopConfig)) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }
    }

    /**
     * 生成支付代码
     * @param array $order
     * @param array $payment
     * @param int $user_id
     * @return false|string|array
     * @throws \Exception
     */
    public function get_code($order = [], $payment = [], $user_id = 0)
    {
        // 公共参数
        $order_amount = $order['order_amount'] * 100;
        $body = (isset($order['subject']) && !empty($order['subject'])) ? $order['subject'] : $order['order_sn']; // 商品描述

        load_helper('payment');
        // 支付配置参数
        $this->payment = $payment;
        if (empty($this->payment)) {
            return false;
        }

        // App 支付 & 微信小程序支付
        $platform = request()->get('platform');
        if ($platform === 'APP' || $platform === 'MP-WEIXIN') {

            if ($platform === 'APP') {
                $appid = $this->payment['wxpay_app_appid'];
                $notify_url = route('api.payment.notify') . '/wxpay/app'; //  异步通知地址
            } else {
                $appid = $this->wxappService->wxappConfig('wx_appid');
                $notify_url = route('api.payment.notify') . '/wxpay/wxapp'; //  异步通知地址
            }

            $options = [
                'mch_id' => $this->payment['wxpay_mchid'], //微信支付商户号
                'appid' => $appid, //填写高级调用功能的app id
                'key' => $this->payment['wxpay_key'] //微信支付API密钥
            ];

            // 微信支付子商户号
            if (isset($this->payment['wxpay_sub_mch_id']) && !empty($this->payment['wxpay_sub_mch_id'])) {
                $options['sub_mch_id'] = $this->payment['wxpay_sub_mch_id'];
            }
            $weObj = new Wechat($options);

            $out_trade_no = $this->make_trade_no($order['log_id'], $order_amount, $order['order_sn'], 'A');

            $this->setParameter("body", $body); // 商品描述
            $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号
            $this->setParameter("total_fee", $order_amount); // 总金额
            $this->setParameter("spbill_create_ip", request()->getClientIp()); // 客户端IP
            $this->setParameter("notify_url", $notify_url); // 异步通知地址
            $this->setParameter("trade_type", ($platform === 'APP') ? 'APP' : 'JSAPI'); // 交易类型

            // 获取小程序用户openid
            if ($platform === 'MP-WEIXIN') {
                $openid = request()->get('openid');

                if (is_null($openid)) {
                    return false;
                }

                $this->setParameter("openid", $openid);
            }

            $pre_order = $weObj->PayUnifiedOrder($this->parameters);

            if ($platform === 'APP') {
                $client_data = [
                    'appid' => $options['appid'],
                    'partnerid' => $options['mch_id'],
                    'prepayid' => $pre_order['prepay_id'],
                    'package' => 'Sign=WXPay',
                    'noncestr' => md5(time()),
                    'timestamp' => time(),
                ];
                $client_data['sign'] = $weObj->getPaySignature($client_data);
            } else {
                $client_data = [
                    'appId' => $options['appid'],
                    'package' => 'prepay_id=' . $pre_order['prepay_id'],
                    'nonceStr' => md5(time()),
                    'timeStamp' => time(),
                    'signType' => 'MD5'
                ];
                $client_data['paySign'] = $weObj->getPaySignature($client_data);
            }

            return json_encode($client_data);
        }

        // 微信h5支付 or 微信公众号支付
        if (is_mobile_device()) {
            $notify_url = route('api.payment.notify') . '/wxpay'; //  异步通知地址

            $options = [
                'appid' => $this->payment['wxpay_appid'], //填写高级调用功能的app id
                'mch_id' => $this->payment['wxpay_mchid'], //微信支付商户号
                'key' => $this->payment['wxpay_key'] //微信支付API密钥
            ];
            // 微信支付子商户号
            if (isset($this->payment['wxpay_sub_mch_id']) && !empty($this->payment['wxpay_sub_mch_id'])) {
                $options['sub_mch_id'] = $this->payment['wxpay_sub_mch_id'];
            }
            $weObj = new Wechat($options);

            // 判断是否是微信浏览器 调用H5支付 MWEB, 需要商户另外申请
            if (is_wechat_browser() === false) {
                $out_trade_no = $this->make_trade_no($order['log_id'], $order_amount, $order['order_sn'], 'H');

                $scene_info = json_encode(['h5_info' => ['type' => 'Wap', 'wap_url' => dsc_url('/'), 'wap_name' => $this->config['shop_name']]]);

                $this->setParameter("body", $body); // 商品描述
                $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号
                $this->setParameter("total_fee", $order_amount); // 总金额
                $this->setParameter("spbill_create_ip", request()->getClientIp()); // 客户端IP
                $this->setParameter("notify_url", $notify_url); // 异步通知地址
                $this->setParameter("trade_type", "MWEB"); // H5支付的交易类型为MWEB
                $this->setParameter("scene_info", $scene_info); // 场景信息

                $respond = $weObj->PayUnifiedOrder($this->parameters);

                if (isset($respond['mweb_url']) && $respond['result_code'] == 'SUCCESS') {
                    $redirect_url = route('mobile.pay.respond', ['code' => 'wxpay', 'type' => 'wxh5', 'log_id' => $order['log_id']]);

                    return [
                        'paycode' => 'wxpay',
                        'type' => 'wxh5',
                        'mweb_url' => $respond['mweb_url'] . '&redirect_url=' . urlencode($redirect_url),
                    ];
                }
                return false;
            } else {
                // 获取用户openid
                $openid = $this->wechatUserService->get_openid($user_id);
                if (empty($openid)) {
                    return false;
                }

                $out_trade_no = $this->make_trade_no($order['log_id'], $order_amount, $order['order_sn'], 'W');

                $this->setParameter("openid", $openid); // 用户openid
                $this->setParameter("body", $body); // 商品描述
                $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号
                $this->setParameter("total_fee", $order_amount); // 总金额
                $this->setParameter("spbill_create_ip", request()->getClientIp()); // 客户端IP
                $this->setParameter("notify_url", $notify_url); // 异步通知地址
                $this->setParameter("trade_type", "JSAPI"); // 交易类型

                $respond = $weObj->PayUnifiedOrder($this->parameters, true);

                $jsApiParameters = json_encode($respond);

                return [
                    'paycode' => 'wxpay',
                    'payment' => $jsApiParameters,
                    'success_url' => route('mobile.pay.respond') . '?code=wxpay&status=1&log_id=' . $order['log_id'],
                    'cancel_url' => route('mobile.pay.respond') . '?code=wxpay&status=0&log_id=' . $order['log_id'],
                ];
            }
        } else {
            // PC 扫码支付
            $notify_url = notify_url('wxpay'); // route('wxpay_native_callback'); // 原异步通知地址

            $out_trade_no = $this->make_trade_no($order['log_id'], $order_amount, $order['order_sn'], 'P');

            //设置必填参数
            $this->setParameter("body", $body);//商品描述
            $this->setParameter("out_trade_no", $out_trade_no);//商户订单号
            $this->setParameter("total_fee", $order_amount);//总金额
            $this->setParameter("spbill_create_ip", request()->getClientIp()); // 终端IP
            $this->setParameter("notify_url", $notify_url);// 异步通知地址
            $this->setParameter("trade_type", "NATIVE");//交易类型

            // 统一下单
            $result = $this->unifiedorder();

            //商户根据实际情况设置相应的处理流程
            if ($result["return_code"] == "FAIL") {
                logResult($result['return_msg']);
                return '<a href="javascript:;" class="weizf" style="opacity: 0.3;" >微信支付</a>';
            } elseif ($result["result_code"] == "FAIL") {
                logResult($result['err_code'] . $result['err_code_des']);
                return '<a href="javascript:;" class="weizf" style="opacity: 0.3;" >微信支付</a>';
            } elseif ($result["code_url"] != null) {
                //从统一支付接口获取到code_url 生成二维码
                $wxpay_lbi = $this->paymentService->wxpayQrcode($result["code_url"]);

                return '<a href="javascript:;" class="weizf" data-type="wxpay">微信支付</a><div class="wxzf">' . $wxpay_lbi . '</div>';
            }

            return false;
        }
    }

    /**
     * 支付同步回调 (PC)
     *
     * @return bool
     */
    public function respond()
    {
        if (request()->input('status') == 1) {
            $order = [];
            $order['log_id'] = request()->input('log_id');

            return $this->orderQuery($order);
        } else {
            return false;
        }
    }

    /**
     * 支付同步回调 (手机)
     *
     * @return bool
     */
    public function callback()
    {
        return $this->respond();
    }

    /**
     * 支付异步通知 (PC+手机)
     *
     * @param string $refer
     * @param string $type 支付类型 公众号支付、小程序、APP
     * @return string
     */
    public function notify($refer = '', $type = '')
    {
        $postStr = request()->getContent();
        if (!empty($postStr)) {
            load_helper('payment');
            $payment = get_payment('wxpay');
            /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
            if ($payment) {
                $postdata = $this->xmlToArray($postStr);

                //微信端签名
                $wxsign = $postdata['sign'];
                // 验证签名
                $sign = $this->getSign($postdata, $payment['wxpay_key']);
                //验证成功
                if ($wxsign == $sign) {
                    //交易成功
                    if ($postdata['result_code'] == 'SUCCESS') {
                        //获取log_id
                        list($order_sn, $log_id, $endstr) = $this->parse_trade_no($postdata['out_trade_no']);

                        // 修改订单信息(openid，tranid)
                        PayLog::where('log_id', $log_id)->update(['openid' => $postdata['openid'], 'transid' => $postdata['transaction_id']]);

                        // 记录交易信息 new
                        $this->paymentService->updatePayLog($log_id, $postdata);

                        // 改变订单状态
                        $money = $postdata['total_fee']/100;
                        $rs = order_paid($log_id, PS_PAYED, '', '', $money); // 已付款
                        if(isset($rs['status']) && $rs['status'] == 'error') {
                            $returndata['return_code'] = 'FAIL';
                            $returndata['return_msg'] = $rs['message'] ?? 'error';
                            Log::error($returndata['return_msg'], $postdata);
                        } else {
                            // 优化切换支付方式后支付 更新订单支付方式
                            $this->paymentService->updateOrderPayment($order_sn, '', $payment);

                            if (!empty($type) && $type == 'wxapp') {
                                // 小程序模板消息推送
                                $attach = isset($postdata['attach']) ? dsc_decode($postdata['attach'], true) : [];
                                $this->wxappService->messageNotify($order_sn, $attach);
                            }
                        }
                    }
                    $returndata['return_code'] = 'SUCCESS';
                } else {
                    $returndata['return_code'] = 'FAIL';
                    $returndata['return_msg'] = '签名失败';
                }
            } else {
                $returndata['return_code'] = 'FAIL';
                $returndata['return_msg'] = '插件不存在';
            }
        } else {
            $returndata['return_code'] = 'FAIL';
            $returndata['return_msg'] = '无数据返回';
        }
        // 数组转化为xml
        return $this->arrayToXml($returndata);
    }

    /**
     *  查询订单
     *  当商户后台、网络、服务器等出现异常，商户系统最终未接收到支付通知
     *
     * @param array $order
     * @return bool
     */
    public function orderQuery($order = [])
    {
        $payLog = $this->paymentService->getUnPayOrder($order['log_id']);

        if (empty($payLog)) {
            return false;
        }

        // 查询未支付的订单
        if ($payLog['is_paid'] == 0) {
            load_helper('payment');
            $payment = get_payment('wxpay');

            // 小程序订单
            if (file_exists(MOBILE_WXAPP) && isset($payLog['referer']) && $payLog['referer'] == 'wxapp') {
                $wx_appid = $this->wxappService->wxappConfig('wx_appid');
            } else {
                $wx_appid = $payment['wxpay_appid'];
            }

            $options = [
                'appid' => $wx_appid, // 填写高级调用功能的app id
                'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                'key' => $payment['wxpay_key'], //微信支付API密钥
            ];

            $weObj = new Wechat($options);

            // 微信订单号  商户订单号  二选一 ， 微信的订单号，建议优先使用
            if (isset($payLog['pay_trade_data']) && !empty($payLog['pay_trade_data'])) {
                $pay_trade_data = dsc_decode($payLog['pay_trade_data'], true);
                $transaction_id = $pay_trade_data['transaction_id'] ?? '';
            } else {
                $transaction_id = $payLog['transid']; // 兼容方案 后面可去掉
            }

            // 微信订单号 或 商户订单号 二选一
            if (!empty($transaction_id)) {
                $this->setParameter("transaction_id", $transaction_id); // 微信订单号
            } else {

                if (isset($order['order_sn']) && !empty($order['order_sn']) && $payLog['order_type'] == PAY_ORDER) {
                    $referer = $payLog['referer'] ?? '';
                    $flag = $this->payFlag($referer); // 支付标识

                    $order_amount = $payLog['order_amount'] * 100;
                    $out_trade_no = $this->make_trade_no($payLog['log_id'], $order_amount, $order['order_sn'], $flag); // 商户订单号

                    $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号
                }
            }

            $respond = $weObj->PayQueryOrder($this->parameters);
            if ($respond['result_code'] == 'SUCCESS' && $respond['trade_state'] == 'SUCCESS') {
                // 兼容支付失败异常的情况 影响退款
                if (empty($transaction_id)) {
                    PayLog::where('log_id', $order['log_id'])->update(['openid' => $respond['openid'], 'transid' => $respond['transaction_id']]);
                }
                // 改变订单状态
                order_paid($order['log_id'], PS_PAYED);
                return true;
            } else {
                return false;
            }
        } elseif ($payLog['is_paid'] == 1) {
            return true;
        } else {
            return false;
        }
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

            load_helper('payment');
            $payment = get_payment('wxpay');

            // 小程序订单退款
            if (file_exists(MOBILE_WXAPP) && isset($return_order['referer']) && $return_order['referer'] == 'wxapp') {
                $wx_appid = $this->wxappService->wxappConfig('wx_appid');
            } else {
                $wx_appid = $payment['wxpay_appid'];
            }

            $options = [
                'appid' => $wx_appid, //填写高级调用功能的app id
                'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                'key' => $payment['wxpay_key'], //微信支付API密钥
            ];

            $weObj = new Wechat($options);

            // 证书
            $sslcert = storage_path('app/certs/wxpay/' . md5($options['mch_id']) . '_apiclient_cert.pem');
            $sslkey = storage_path('app/certs/wxpay/' . md5($options['mch_id']) . '_apiclient_key.pem');

            if (file_exists($sslcert) && file_exists($sslkey)) {
                $order_amount = $payLog['order_amount'] * 100;

                $out_refund_no = $return_order['return_sn']; // 商户退款单号
                $total_fee = $order_amount;
                $refund_fee = !empty($return_order['should_return']) ? $return_order['should_return'] * 100 : $order_amount;   // 退款金额 默认退全款

                if (file_exists(MOBILE_WXAPP) && isset($return_order['referer']) && $return_order['referer'] == 'wxapp') {
                    // 小程序
                    $refund_notify_url = route('api.payment.notify_refound') . '/wxpay';
                } else {
                    if (is_mobile_device()) {
                        // JSAPI
                        $refund_notify_url = route('api.payment.notify_refound') . '/wxpay';
                    } else {
                        // PC
                        $refund_notify_url = route('notify_refound') . '/wxpay';
                    }
                }

                if (isset($payLog['pay_trade_data']) && !empty($payLog['pay_trade_data'])) {
                    $pay_trade_data = dsc_decode($payLog['pay_trade_data'], true);
                    $transaction_id = $pay_trade_data['transaction_id'] ?? '';
                } else {
                    $transaction_id = $payLog['transid'] ?? ''; // 兼容方案 后面可去掉
                }
                // 微信订单号 或 商户订单号 二选一
                if (!empty($transaction_id)) {
                    $this->setParameter("transaction_id", $transaction_id); // 微信订单号
                } else {
                    if (isset($return_order['order_sn']) && !empty($return_order['order_sn']) && $payLog['order_type'] == PAY_ORDER) {
                        $referer = $return_order['referer'] ?? '';
                        $flag = $this->payFlag($referer); // 支付标识
                        $out_trade_no = $this->make_trade_no($payLog['log_id'], $order_amount, $return_order['order_sn'], $flag); // 商户订单号
                        $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号
                    }
                }
                $this->setParameter("out_refund_no", $out_refund_no);// 商户退款单号
                $this->setParameter("total_fee", $total_fee);//总金额
                $this->setParameter("refund_fee", $refund_fee);//退款金额
                $this->setParameter("notify_url", $refund_notify_url);//退款异步通知地址

                $respond = $weObj->PayRefund($this->parameters, $sslcert, $sslkey);

                // 退款申请接收成功
                if ($respond['result_code'] == 'SUCCESS') {
                    //$out_refund_no = $respond['out_refund_no']; // 商户退款单号
                    return true;
                } else {
                    logResult($weObj->errCode . ':' . $weObj->errMsg);
                    return false;
                }
            }
        }
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
            load_helper('payment');
            $payment = get_payment('wxpay');

            // 小程序订单退款
            if (file_exists(MOBILE_WXAPP) && isset($return_order['referer']) && $return_order['referer'] == 'wxapp') {
                $wx_appid = $this->wxappService->wxappConfig('wx_appid');
            } else {
                $wx_appid = $payment['wxpay_appid'];
            }

            $options = [
                'appid' => $wx_appid, //填写高级调用功能的app id
                'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                'key' => $payment['wxpay_key'], //微信支付API密钥
            ];
            $weObj = new Wechat($options);

            $payLog = $this->paymentService->getPaidOrder($return_order['order_id']);
            if (isset($payLog['pay_trade_data']) && !empty($payLog['pay_trade_data'])) {
                $pay_trade_data = dsc_decode($payLog['pay_trade_data'], true);
                $transaction_id = $pay_trade_data['transaction_id'] ?? '';
            } else {
                $transaction_id = $payLog['transid']; // 兼容方案 后面可去掉
            }

            // 微信订单号 transaction_id， 商户订单号 out_trade_no， 商户退款单号 out_refund_no，微信退款单号 refund_id 四选一
            // $this->setParameter("out_trade_no", $out_trade_no);
            // $this->setParameter("out_refund_no", $order_return_info['return_sn']);// 商户退款单号
            $this->setParameter("transaction_id", $transaction_id);
            // $this->setParameter("refund_id", $refund_id);

            $respond = $weObj->PayRefundQuery($this->parameters);
            // 退款查询
            if ($respond['result_code'] == 'SUCCESS' && $respond['return_code'] == 'SUCCESS') {
                /*
                refund_status_$n $n为下标，从0开始编号。
                退款状态：
                SUCCESS—退款成功
                REFUNDCLOSE—退款关闭。
                PROCESSING—退款处理中
                CHANGE—退款异常，退款到银行发现用户的卡作废或者冻结了
                 */
                $out_refund_no = $respond['out_refund_no']; // 商户退款单号
                $refund_count = $respond['refund_count']; // 退款笔数
                $refund_fee = $respond['refund_fee']; // 退款金额

                return true;
            } else {
                logResult($weObj->errCode . ':' . $weObj->errMsg);
                return false;
            }
        }
        return false;
    }

    /**
     * 退款异步通知
     * @param string $refer 来源
     * @return string
     */
    public function notify_refound($refer = '')
    {
        $postStr = request()->getContent();
        if (!empty($postStr)) {
            load_helper('payment');
            $payment = get_payment('wxpay');
            /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
            if ($payment) {
                $postdata = $this->xmlToArray($postStr);

                if ($postdata['return_code'] == 'SUCCESS') {

                    // 加密信息 解密
                    $decrypt = base64_decode($postdata['req_info'], true); // 对加密串A做base64解码，得到加密串B
                    $key = md5($payment['wxpay_key']); // 对商户key做md5，得到32位小写key*
                    $xml = openssl_decrypt($decrypt, 'aes-256-ecb', $key, OPENSSL_RAW_DATA); // 用key*对加密串B做AES-256-ECB解密
                    $data = $this->xmlToArray($xml);

                    // 退款状态
                    if ($data['refund_status'] == 'SUCCESS') {
                        // 退款成功 更新 退换货订单状态

                        // 获取 order_sn
                        list($order_sn, $log_id, $endstr) = $this->parse_trade_no($postdata['out_trade_no']);

                        app(RefoundService::class)->refund_paid($order_sn, 2, lang('order.return_online'));

                        // 记录退款交易信息
                        $this->paymentService->updateReturnLog('', $order_sn, $data);
                    }
                    $returndata['return_code'] = 'SUCCESS';
                } else {
                    $returndata['return_code'] = 'FAIL';
                    $returndata['return_msg'] = '无数据返回';
                }
            } else {
                $returndata['return_code'] = 'FAIL';
                $returndata['return_msg'] = '插件不存在';
            }
        } else {
            $returndata['return_code'] = 'FAIL';
            $returndata['return_msg'] = '无数据返回';
        }
        // 数组转化为xml
        return $this->arrayToXml($returndata);
    }

    public function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     *  作用：产生随机字符串，不长于32位
     */
    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     *  作用：设置请求参数
     */
    public function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 拼接微信签名
     * @param array $arrdata 签名数组
     * @param string $key 商户key
     * @param string $method 签名方法
     *
     * @return boolean|string 签名值
     */
    protected function getSign($arrdata = [], $key = '', $method = "md5")
    {
        if (isset($arrdata['sign'])) {
            unset($arrdata['sign']);
        }

        foreach ($arrdata as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);

        $buff = "";
        foreach ($Parameters as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $String = '';
        if (strlen($buff) > 0) {
            $String = substr($buff, 0, strlen($buff) - 1);
        }
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $key;
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = $method($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        return strtoupper($String);
    }

    /**
     * 作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30)
    {
        // 初始化curl
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        // 运行curl
        $data = curl_exec($ch);
        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 数组转化为xml
     * @param array $array
     * @return string
     */
    protected function arrayToXml($array)
    {
        $xml = "<xml>";
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";

        return $xml;
    }

    /**
     * 将xml转换为数组
     *
     * @param $xml
     * @return mixed
     */
    protected function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xml_string = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return dsc_decode(json_encode($xml_string), true);
    }


    /**
     * 获取结果
     */
    public function unifiedorder()
    {
        //设置接口链接
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        try {
            //检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new \Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new \Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new \Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new \Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new \Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" && $this->parameters["openid"] == null) {
                throw new \Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
            $this->parameters["appid"] = $this->payment['wxpay_appid'];//公众账号ID
            $this->parameters["mch_id"] = $this->payment['wxpay_mchid'];//商户号
            // 微信支付子商户号
            if (isset($this->payment['wxpay_sub_mch_id']) && !empty($this->payment['wxpay_sub_mch_id'])) {
                $this->parameters['sub_mch_id'] = $this->payment['wxpay_sub_mch_id'];
            }
            $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
            $this->parameters["sign"] = $this->getSign($this->parameters, $this->payment['wxpay_key']);//签名
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        // 数组转xml
        $xml = $this->arrayToXml($this->parameters);

        $response = $this->postXmlCurl($xml, $url, 30);
        // 接收数据xml 转 数组
        return $this->xmlToArray($response);
    }

    /**
     * 生成支付订单号
     * @param int $log_id
     * @param int $order_amount
     * @param string $order_sn
     * @param string $flag
     * @return string
     */
    public function make_trade_no($log_id, $order_amount = 0, $order_sn = '', $flag = '')
    {
        $trade_no = $order_sn . 'O' . $log_id . 'O';

        return str_pad($trade_no, 32, md5(microtime(true)));
    }

    /**
     * 获取交易号信息
     * @param $trade_no
     * @return array
     */
    public function parse_trade_no($trade_no)
    {
        return explode('O', $trade_no);
    }

    /**
     * 返回支付标识
     * @param string $referer
     * @return string
     */
    public function payFlag($referer = '')
    {
        if (file_exists(MOBILE_WXAPP) && $referer == 'wxapp') {
            // 小程序
            $flag = 'A';
        } else {
            if (is_mobile_device()) {
                if (is_wechat_browser() === false) {
                    // H5
                    $flag = 'H';
                } else {
                    // JSAPI
                    $flag = 'W';
                }
            } else {
                // PC
                $flag = 'P';
            }
        }

        return $flag;
    }
}
