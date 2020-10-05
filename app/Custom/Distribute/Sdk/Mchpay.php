<?php

namespace App\Custom\Distribute\Sdk;

use App\Libraries\Wechat;
use Illuminate\Support\Facades\Log;


/**
 * Class Mchpay
 * @package App\Custom\Distribute\Sdk
 */
class Mchpay
{
    protected $wxpayObj;
    protected $payment;

    // 证书
    protected $sslcert;
    protected $sslkey;

    public function __construct()
    {
        load_helper('payment');
        $payment = get_payment('wxpay');

        if (empty($payment)) {
            return false;
        }

        $this->wxpayObj = new Wechat($payment);
        $this->payment = $payment;

        // 证书
        $this->sslcert = storage_path('app/certs/wxpay/') . md5($payment['wxpay_mchid']) . "_apiclient_cert.pem";
        $this->sslkey = storage_path('app/certs/wxpay/') . md5($payment['wxpay_mchid']) . "_apiclient_key.pem";
    }

    /**
     * 微信企业付款至零钱
     * @param array $param
     * @return bool
     */
    public function MchPay($param = [])
    {
        if (empty($param)) {
            return false;
        }

        if (file_exists($this->sslcert) && file_exists($this->sslkey)) {

            $arr = [];
            $arr['partner_trade_no'] = $param['partner_trade_no'];
            $arr['openid'] = $param['openid']; // 用户标识
            $arr['check_name'] = "NO_CHECK"; // NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名
            $arr['amount'] = $param['money']; // 企业付款金额，单位为分
            $arr['desc'] = $param['desc']; // 企业付款操作说明信息。必填。
            $arr['spbill_create_ip'] = request()->getClientIp();

            $ret = $this->wxpayObj->MchPay($arr, $this->sslcert, $this->sslkey);

            if (isset($ret) && $ret['return_code'] == 'SUCCESS' && $ret['result_code'] == 'SUCCESS') {

                return $ret;
            }

            if (config('app.debug')) {
                Log::info('======MchPay======');
                Log::info($this->wxpayObj->errCode . ' ' . $this->wxpayObj->errMsg);
            }
        }

        return false;
    }

    /**
     * 查询企业付款
     * @param array $param
     * @return bool
     */
    public function MchPayQuery($param = [])
    {
        if (empty($param)) {
            return false;
        }

        if (file_exists($this->sslcert) && file_exists($this->sslkey)) {

            $arr = [
                'partner_trade_no' => $param['partner_trade_no']
            ];

            // 查询API
            $respond = $this->wxpayObj->MchPayQuery($arr, $this->sslcert, $this->sslkey);
            if (isset($respond) && $respond['return_code'] == 'SUCCESS' && $respond['result_code'] == 'SUCCESS') {

                // 代付订单状态：
                // PROCESSING（处理中，如有明确失败，则返回额外失败原因；否则没有错误原因）
                // SUCCESS（付款成功）
                // FAILED（付款失败,需要替换付款单号重新发起付款）
                // BANK_FAIL（银行退票，订单状态由付款成功流转至退票,退票时付款金额和手续费会自动退还）
                return $respond;
            }

            if (config('app.debug')) {
                Log::info('======MchPay======');
                Log::info($this->wxpayObj->errCode . ' ' . $this->wxpayObj->errMsg);
            }
        }

        return false;
    }

    /**
     * 微信企业付款至银行卡
     * @param array $param
     * @param string $rsa_public_key_path
     * @return bool
     */
    public function MchPayBank($param = [], $rsa_public_key_path = '')
    {
        if (empty($param)) {
            return false;
        }

        if (file_exists($this->sslcert) && file_exists($this->sslkey)) {

            // 付款至银行卡
            $arr = [];
            $arr['partner_trade_no'] = $param['partner_trade_no'];
            $arr['enc_bank_no'] = $param['enc_bank_no'];
            $arr['enc_true_name'] = $param['enc_true_name'];
            $arr['bank_code'] = $param['bank_code'];
            $arr['amount'] = $param['money']; // 企业付款金额，单位为分
            $arr['desc'] = $param['desc']; // 企业付款操作说明信息。必填。

            $ret = $this->wxpayObj->MchPayBank($arr, $this->sslcert, $this->sslkey, $rsa_public_key_path);

            if (isset($ret) && $ret['return_code'] == 'SUCCESS' && $ret['result_code'] == 'SUCCESS') {

                return $ret;
            }

            if (config('app.debug')) {
                Log::info('======MchPay======');
                Log::info($this->wxpayObj->errCode . ' ' . $this->wxpayObj->errMsg);
            }
        }

        return false;
    }

    /**
     * 获取RSA公钥API
     * @param string $sign_type
     * @return array|bool
     */
    public function MchPayGetPublicKey($sign_type = 'MD5')
    {
        $arr = [
            'sign_type' => $sign_type,
        ];

        if (file_exists($this->sslcert) && file_exists($this->sslkey)) {
            // 获取RSA公钥API
            $rsa = $this->wxpayObj->MchPayGetPublicKey($arr, $this->sslcert, $this->sslkey);
            if (isset($rsa) && $rsa['return_code'] == 'SUCCESS' && $rsa['result_code'] == 'SUCCESS') {

                if (config('app.debug')) {
                    Log::info('rsa');
                    Log::info($rsa);
                }

                return $rsa;
            }
        }

        return false;
    }

    /**
     * 查询企业付款到银行卡
     * @param array $param
     * @return bool
     */
    public function MchPayBankQuery($param = [])
    {
        if (empty($order)) {
            return false;
        }

        if (file_exists($this->sslcert) && file_exists($this->sslkey)) {

            $arr = [
                'partner_trade_no' => $param['partner_trade_no']
            ];

            // 查询API
            $respond = $this->wxpayObj->MchPayQueryBank($arr, $this->sslcert, $this->sslkey);
            if (isset($respond) && $respond['return_code'] == 'SUCCESS' && $respond['result_code'] == 'SUCCESS') {

                // 代付订单状态：
                // PROCESSING（处理中，如有明确失败，则返回额外失败原因；否则没有错误原因）
                // SUCCESS（付款成功）
                // FAILED（付款失败,需要替换付款单号重新发起付款）
                // BANK_FAIL（银行退票，订单状态由付款成功流转至退票,退票时付款金额和手续费会自动退还）
                return $respond;
            }

            if (config('app.debug')) {
                Log::info('======MchPay======');
                Log::info($this->wxpayObj->errCode . ' ' . $this->wxpayObj->errMsg);
            }
        }

        return false;
    }

}
