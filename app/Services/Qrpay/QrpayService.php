<?php

namespace App\Services\Qrpay;

use App\Models\MerchantsAccountLog;
use App\Models\Payment;
use App\Models\QrpayDiscounts;
use App\Models\QrpayLog;
use App\Models\QrpayManage;
use App\Models\SellerAccountLog;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use Payment\Client\Charge;
use Payment\Common\PayException;
use Payment\Config;

/**
 * Class QrpayService
 * @package App\Services\Qrpay
 */
class QrpayService
{
    protected $timeRepository;
    protected $config;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        load_helper(['order']);

        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 取得某支付方式信息
     *
     * @param string $code 支付方式代码
     * @return array
     */
    public function get_payment_info($code = '')
    {
        $payment = Payment::where(['pay_code' => $code, 'enabled' => 1])
            ->first();

        $payment = $payment ? $payment->toArray() : [];

        if ($payment) {
            $config_list = unserialize($payment['pay_config']);

            foreach ($config_list as $config) {
                $payment[$config['name']] = $config['value'];
            }
        }

        return $payment;
    }

    /**
     * 查询收款码信息
     *
     * @param int $id
     * @return array
     */
    public function get_qrpay_info($id = 0)
    {
        if ($id) {
            $res = QrpayManage::where('id', $id)
                ->first();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                $res['qrpay_code'] = $this->dscRepository->getImagePath($res['qrpay_code']);
                $res['add_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $res['add_time']);
            }

            return $res;
        }

        return [];
    }

    /**
     *  处理收款码优惠满减金额
     * @mark 每满$dis['min_amount'] 减 $dis['discount_amount']， 最高优惠$dis['max_discount_amount']元
     * @param int $qrpay_id
     * @param int $pay_amount
     * @return int
     */
    public function do_discount_fee($qrpay_id = 0, $pay_amount = 0)
    {
        $discount_fee = 0;
        $res = QrpayManage::where(['id' => $qrpay_id])
            ->first();
        $res = $res ? $res->toArray() : [];

        if (!empty($res) && $res['discount_id'] > 0) {
            $dis = QrpayDiscounts::where(['id' => $res['discount_id'], 'status' => 1])
                ->first();
            $dis = $dis ? $dis->toArray() : [];

            if (!empty($dis)) {
                if ($pay_amount > 0 && $pay_amount >= $dis['min_amount']) {
                    $per = intval($pay_amount / $dis['min_amount']);
                    $discount_fee = $dis['discount_amount'] * $per;
                    // 每笔最高优惠，未设置优惠金额(0.00) 无上限
                    if (!empty(floatval($dis['max_discount_amount']))) {
                        $discount_fee = $discount_fee > $dis['max_discount_amount'] ? $dis['max_discount_amount'] : $discount_fee;
                    }
                    $discount_fee = number_format($discount_fee, 2, '.', '');
                }
            }
        }
        return $discount_fee;
    }

    /**
     * 查询收款码优惠名称
     *
     * @param int $id
     * @return string
     * @throws \Exception
     */
    public function get_discounts_name($id = 0)
    {
        $res = QrpayDiscounts::select('min_amount', 'discount_amount', 'max_discount_amount')
            ->where(['status' => 1, 'id' => $id])
            ->first();
        $res = $res ? $res->toArray() : [];

        return (!empty($res) && $res['min_amount'] > 0) ? sprintf(lang('discount.activity_full_reduction'), $res['min_amount'], $res['discount_amount']) : '';
    }

    /**
     * 更新收款码支付状态
     *
     * @param $log_id
     * @param int $pay_status 0 未支付 1 已支付
     */
    public function qrpay_order_paid($log_id, $pay_status = 0)
    {
        /* 取得支付编号 */
        $log_id = intval($log_id);
        if ($log_id > 0) {
            $pay_log = QrpayLog::where(['id' => $log_id, 'pay_status' => 0])
                ->first();
            $pay_log = $pay_log ? $pay_log->toArray() : [];

            if ($pay_log) {
                QrpayLog::where(['id' => $log_id])->update(['pay_status' => $pay_status]);
            }
        }
    }

    /**
     * 更新保存交易信息 可用于对账查询
     * @param  $log_id
     * @param  $data
     */
    public function update_trade_data($log_id, $data = [])
    {
        if ($data) {
            $datas = [
                'trade_no' => $data['transaction_id'] ?? '',
                'notify_data' => serialize($data), // 保存序列化交易数据
            ];

            QrpayLog::where(['id' => $log_id])->update($datas);
        }
    }

    /**
     * 结算商家收款码余额
     * @param int $order_id
     * @return
     */
    public function insert_seller_account_log($order_id = 0)
    {
        // 已支付 未结算 商家收款记录
        $res = QrpayLog::where(['id' => $order_id, 'is_settlement' => 0, 'pay_status' => 1])->first();
        $res = $res ? $res->toArray() : [];

        if (!empty($res)) {
            if ($res['ru_id'] > 0) {
                $nowTime = $this->timeRepository->getGmTime();

                $other['admin_id'] = 0;
                $other['ru_id'] = $res['ru_id'];
                $other['order_id'] = 0;
                $other['amount'] = $res['pay_amount']; //结算金额
                $other['add_time'] = $nowTime; //结算时间
                $other['log_type'] = 2; // 2 结算 3 充值
                $other['is_paid'] = 1;
                $other['pay_id'] = Payment::where(['pay_code' => $res['payment_code']])->value('pay_id');
                $other['apply_sn'] = '【' . lang('order.receipt_code_order') . '】' . $res['pay_order_sn'];

                // 更新 已结算
                QrpayLog::where(['id' => $order_id, 'ru_id' => $res['ru_id']])->update(['is_settlement' => 1]);

                // 插入日志
                SellerAccountLog::create($other);

                // 更新商家账户余额
                SellerShopinfo::where(['ru_id' => $res['ru_id']])->increment('seller_money', $res['pay_amount']);

                $change_desc = lang('order.seller_automatic_settlement');
                $user_account_log = [
                    'user_id' => $res['ru_id'],
                    'user_money' => $res['pay_amount'],
                    'change_time' => $nowTime,
                    'change_desc' => $change_desc,
                    'change_type' => 2
                ];
                MerchantsAccountLog::create($user_account_log);
            } else {
                // 更新 已结算 平台默认
                QrpayLog::where(['id' => $order_id])->update(['is_settlement' => 1]);
            }

            return true;
        }

        return false;
    }

    /**
     * 下单并调起支付
     * @param int $uid
     * @param string $openid
     * @param string $pay_code
     * @param int $pay_amount
     * @param array $qrpay_info
     * @return array|mixed|string
     */
    public function done($uid = 0, $openid = '', $pay_code = '', $pay_amount = 0, $qrpay_info = [])
    {
        // 下单流程
        $order = [];
        $order['pay_order_sn'] = get_order_sn();

        //计算此收款码满减优惠后的金额
        if ($pay_amount > 0) {
            $discount_fee = $this->do_discount_fee($qrpay_info['id'], $pay_amount);
            $pay_amount = $pay_amount - $discount_fee;
            $pay_amount = number_format($pay_amount, 2, '.', '');
        }
        $order['pay_amount'] = $pay_amount;

        $order['pay_user_id'] = $uid ?? 0;
        $order['openid'] = $openid ?? '';
        $order['add_time'] = $this->timeRepository->getGmTime();
        $order['qrpay_id'] = $qrpay_info['id'];
        $order['pay_desc'] = (isset($discount_fee) && $discount_fee > 0) ? $this->get_discounts_name($qrpay_info['discount_id']) : ''; // 备注
        $order['ru_id'] = !empty($qrpay_info['ru_id']) ? $qrpay_info['ru_id'] : 0; // 商家id
        $order['payment_code'] = $pay_code; // 支付方式

        $insert = false; // 是否插入订单
        if ($pay_code == 'wxpay') {
            if (isset($openid) && !empty($openid)) {
                $insert = true;
            }
        } else {
            $insert = true;
        }

        if ($insert == true) {
            /* 插入收款记录表 */
            $error_no = 0;
            do {
                $order['pay_order_sn'] = get_order_sn(); //获取新订单号
                $new_order = $this->baseRepository->getArrayfilterTable($order, 'qrpay_log');
                try {
                    $new_order_id = QrpayLog::insertGetId($new_order);
                } catch (\Exception $e) {
                    $error_no = (stripos($e->getMessage(), '1062 Duplicate entry') !== false) ? 1062 : $e->getCode();
                }

                if ($error_no > 0 && $error_no != 1062) {
                    die($e->getMessage());
                }
            } while ($error_no == 1062); //如果是订单号重复则重新提交数据

            $order['id'] = $new_order_id ?? 0;
        }

        /* 取得支付信息，生成支付代码 */
        $payment = $this->get_payment_info($pay_code);

        if (!empty($order['id']) && $payment && $pay_amount > 0) {
            // 业务请求参数
            $payData = $this->getPayData($pay_code, $order);
            try {
                $trade_type = $pay_code == 'wxpay' ? Config::WX_CHANNEL_PUB : Config::ALI_CHANNEL_WAP;
                $ret = Charge::run($trade_type, $this->getConfig($pay_code, $qrpay_info['id']), $payData);
                // 微信转json数据
                $ret = $pay_code == 'wxpay' ? json_encode($ret, JSON_UNESCAPED_UNICODE) : $ret;
            } catch (PayException $e) {
                // 异常处理
                exit($e->getMessage());
            }
        }

        return $ret ?? [];
    }

    /**
     * 业务请求参数
     *
     * @param string $pay_code
     * @param array $order
     * @return array
     */
    public function getPayData($pay_code = '', $order = [])
    {
        $payData = [];
        if ($pay_code == 'alipay') {
            $payData = [
                'body' => $order['pay_order_sn'],
                'subject' => !empty($order['pay_desc']) ? "【" . $order['pay_desc'] . "】" . $order['pay_order_sn'] : $order['pay_order_sn'],
                'order_no' => $order['pay_order_sn'] . 'Q' . $order['id'],
                'timeout_express' => time() + 3600 * 24,// 表示必须 24h 内付款
                'amount' => $order['pay_amount'],// 单位为元 ,最小为0.01
                'return_param' => 'qr' . $order['id'],// 一定不要传入汉字，只能是 字母 数字组合
                'client_ip' => request()->getClientIp(),// 客户地址
                'goods_type' => 1,
                'store_id' => '',
            ];
        }

        if ($pay_code == 'wxpay') {
            $payData = [
                'body' => $order['pay_order_sn'],
                'subject' => !empty($order['pay_desc']) ? "【" . $order['pay_desc'] . "】" . $order['pay_order_sn'] : $order['pay_order_sn'],
                'order_no' => $order['pay_order_sn'] . 'Q' . $order['id'],
                'timeout_express' => time() + 3600 * 24,// 表示必须 24h 内付款
                'amount' => $order['pay_amount'],// 单位为元 接口已转换为分
                'return_param' => 'qr' . $order['id'],// 一定不要传入汉字，只能是 字母 数字组合
                'client_ip' => request()->getClientIp(),// 客户地址
                'openid' => $order['openid'],
            ];
        }

        return $payData;
    }

    /**
     * 获取配置
     *
     * @param string $pay_code
     * @return array
     */
    public function getConfig($pay_code = '', $qrpay_id = 0)
    {
        $payment = $this->get_payment_info($pay_code);
        $config = [];
        if ($pay_code == 'alipay') {
            $config = [
                'use_sandbox' => isset($payment['use_sandbox']) ? (bool)$payment['use_sandbox'] : false,
                'partner' => $payment['alipay_partner'],
                'app_id' => $payment['app_id'],
                'sign_type' => $payment['sign_type'],
                // 可以填写文件路径，或者密钥字符串  当前字符串是 rsa2 的支付宝公钥(开放平台获取)
                'ali_public_key' => $payment['ali_public_key'],
                // 可以填写文件路径，或者密钥字符串  我的沙箱模式，rsa与rsa2的私钥相同，为了方便测试
                'rsa_private_key' => $payment['rsa_private_key'],
                'notify_url' => route('api.qrpay.notify') . '/' . $pay_code . '/' . $qrpay_id,
                'return_url' => route('mobile.pay.respond', ['type' => 'qrpay', 'id' => $qrpay_id]),
                'return_raw' => false,
            ];
        }

        if ($pay_code == 'wxpay') {
            $config = [
                'use_sandbox' => isset($payment['use_sandbox']) ? (bool)$payment['use_sandbox'] : false,
                'app_id' => $payment['wxpay_appid'], // 公众账号ID
                'mch_id' => $payment['wxpay_mchid'], // 商户id
                'md5_key' => $payment['wxpay_key'], // md5 秘钥
                // 'app_cert_pem' => ROOT_PATH . 'storage/app/certs/apiclient_cert.pem',
                // 'app_key_pem' => ROOT_PATH . 'storage/app/certs/apiclient_key.pem',
                'sign_type' => 'MD5', // MD5  HMAC-SHA256
                'fee_type' => 'CNY', // 货币类型  当前仅支持该字段
                'notify_url' => route('api.qrpay.notify') . '/' . $pay_code . '/' . $qrpay_id,
                // 'redirect_url' => url('qrpay/index/callback', 0, 0, true), // h5支付跳转地址
                'return_raw' => false,
            ];
        }

        return $config;
    }
}
