<?php

namespace App\Libraries;

use App\Libraries\Wechat\SDK;

class Wechat extends SDK
{

    /**
     * 微信支付(公众号JSSDK支付)
     *
     * 官方文档：http://mp.weixin.qq.com/wiki/7/aaa137b55fb2e0456bf8dd9148dd613f.html
     * 微信支付：http://pay.weixin.qq.com/wiki/doc/api/index.php?chapter=9_1#
     * 官方示例：http://demo.open.weixin.qq.com/jssdk/sample.zip
     *
     */
    const PAY_PREFIX = 'https://api.mch.weixin.qq.com';
    const PAY_UNIFIEDORDER = '/pay/unifiedorder?'; // 统一下单接口
    const PAY_ORDERQUERY = '/pay/orderquery?'; // 订单查询接口

    const PAY_REFUND = '/secapi/pay/refund?'; // 退款申请接口
    const PAY_REFUNDQUERY = '/pay/refundquery?'; // 退款查询接口

    private $appid;
    private $mch_id;
    private $key;
    private $sub_mch_id; // 子商户号

    private $curl_timeout = 60;

    // 现金红包 api 接口地址
    const REDPACK_SEND_NORMAL = '/mmpaymkttransfers/sendredpack'; // 普通红包
    const REDPACK_SEND_GROUP = '/mmpaymkttransfers/sendgroupredpack'; // 裂变红包
    const REDPACK_QUERY = '/mmpaymkttransfers/gethbinfo';  // 查询红包记录

    // hongbao type
    const TYPE_NORMAL = 'NORMAL';
    const TYPE_GROUP = 'GROUP';

    // 企业付款 接口
    const MCHPAY_TRANS = '/mmpaymkttransfers/promotion/transfers'; // 企业付款到零钱
    const MCHPAY_QUERY = '/mmpaymkttransfers/gettransferinfo'; // 查询企业付款到零钱API
    const MCHPAY_BANK = '/mmpaysptrans/pay_bank'; // 企业付款到银行卡
    const MCHPAY_QUERY_BANK = '/mmpaysptrans/query_bank'; // 查询企业付款到银行卡API
    const MCHPAY_GET_PUBLICKEY = 'https://fraud.mch.weixin.qq.com/risk/getpublickey'; // 获取RSA公钥API

    /**
     * 微信公众号用户标签
     */
    const TAGS_CREATE_URL = '/tags/create?';  // 创建标签
    const TAGS_GET_URL = '/tags/get?';  // 获取公众号已创建的标签
    const TAGS_UPDATE_URL = '/tags/update?';  // 编辑标签
    const TAGS_DELETE_URL = '/tags/delete?'; // 删除标签
    const USER_TAG_URL = '/user/tag/get?';  // 标签下粉丝列表
    const TAGS_MEMBER_BATCHTAGGING_URL = '/tags/members/batchtagging?';  // 批量为用户打标签
    const TAGS_MEMBER_BATCHUNTAGGING_URL = '/tags/members/batchuntagging?'; // 批量为用户取消标签
    const TAGS_GETIDLIST_URL = '/tags/getidlist?'; // 获取用户身上的标签列表

    public function __construct($options)
    {
        $this->appid = isset($options['appid']) ? $options['appid'] : '';
        $this->mch_id = isset($options['mch_id']) ? $options['mch_id'] : '';
        $this->key = isset($options['key']) ? $options['key'] : '';
        $this->sub_mch_id = isset($options['sub_mch_id']) ? $options['sub_mch_id'] : '';
        parent::__construct($options);
        libxml_disable_entity_loader(true);
    }

    /**
     * 公众号支付签名
     * @param array $arr 需要签名的数据
     * @return array|bool 返回签名字串
     */
    public function getPaySign($arr = [])
    {
        if (empty($arr)) {
            return false;
        }
        $arr['appid'] = $this->appid;
        $arr['mch_id'] = $this->mch_id;
        // 存在子商户号 则参与签名
        if (!empty($this->sub_mch_id)) {
            $arr['sub_mch_id'] = $this->sub_mch_id;
        }
        $arr['nonce_str'] = $this->generateNonceStr();
        $paySign = $this->getPaySignature($arr);
        $arr['sign'] = $paySign;
        return $arr;
    }

    /**
     * 公众号支付JSSDK签名
     * @param array $arr 需要签名的数据
     * @return array|bool 返回签名字串
     */
    public function getPayJssdkSign($str)
    {
        if (empty($str)) {
            return false;
        }
        $arr = [];
        $arr['appId'] = $this->appid;
        $arr['timeStamp'] = " " . time();
        $arr['nonceStr'] = $this->generateNonceStr();
        $arr['package'] = "prepay_id=" . $str;
        $arr['signType'] = "MD5";
        $paySign = $this->getPaySignature($arr);
        $arr['paySign'] = $paySign;
        return $arr;
    }

    /**
     * 公众号支付 统一下单接口 （默认交易类型JSAPI）
     * @param array $arr 请求下单参数
     * @param boolean $jsSign false返回下单参数，ture返回H5签名
     * @return boolean|array
     *
     * 字段说明:
     * 商品描述   body
     * 商户订单号 out_trade_no
     * 总金额     total_fee
     * 终端IP     spbill_create_ip
     * 通知地址   notify_url
     * 用户标识   openid
     *
     *   $options = array(
     *            'appid'=>'wxdk1234567890', //填写高级调用功能的app id
     *            'mch_id'=>'xxxxxxxxxxxxxxxxxxx', //微信支付商户号
     *            'key'=>'xxxxxxxxxxxxxxxxxxx' //微信支付API密钥
     *        );
     *     $weObj = new Wechat($options);
     *
     *   $arr=array();
     *   $arr['spbill_create_ip'] = '终端IP';
     *   $arr['out_trade_no'] = '商户订单号';
     *   $arr['total_fee'] = '总金( 135 = 1.35元)';
     *   $arr['notify_url'] = "http://xxxx/PayNotify.php";
     *   $arr['body'] = '商品描述';
     *   $arr['openid'] = '用户标识';
     *   $ret = $weObj->PayUnifiedOrder($arr,true);
     *
     */
    public function PayUnifiedOrder($arr = [], $jsSign = false)
    {
        if (empty($arr)) {
            return false;
        }
        $arr['device_info'] = isset($arr['device_info']) ? $arr['device_info'] : "WEB";
        $arr['fee_type'] = isset($arr['fee_type']) ? $arr['fee_type'] : "CNY";
        $arr['trade_type'] = isset($arr['trade_type']) ? $arr['trade_type'] : "JSAPI";
        $arrdata = $this->getPaySign($arr);
        $xmldata = $this->xml_encode($arrdata);
        $result = $this->http_post(self::PAY_PREFIX . self::PAY_UNIFIEDORDER, $xmldata);
        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") { //下单失败
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                \Log::info('======PayUnifiedOrder FAIL======');
                \Log::info($json);
                return false;
            }
            //生成微信签名
            return $jsSign == false ? $json : $this->getPayJssdkSign($json['prepay_id']);
        }
        return false;
    }

    /**
     * 拼接微信支付签名
     * @param array $arrdata 签名数组
     * @param string $method 签名方法
     * @param string $key 商户key
     * @return boolean|string 签名值
     */
    public function getPaySignature($arrdata, $method = "md5")
    {
        ksort($arrdata);
        $paramstring = "";
        foreach ($arrdata as $key => $value) {
            if (!$value) {
                continue;
            }
            if (strlen($paramstring) == 0) {
                $paramstring .= $key . "=" . $value;
            } else {
                $paramstring .= "&" . $key . "=" . $value;
            }
        }

        $paramstring = $paramstring . "&key=" . $this->key;
        $Sign = $method($paramstring);
        $Sign = strtoupper($Sign);
        return $Sign;
    }

    /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    private function http_post($url, $param, $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = [];
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     *  作用：使用证书，以post方式提交xml到对应的接口url
     *  请确保您的libcurl版本是否支持双向认证，版本高于7.20.1
     */
    public function postXmlSSLCurl($url, $xml, $second = 30, $sslcert = '', $sslkey = '')
    {
        $ch = curl_init();
        //设置curl默认访问为IPv4
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $sslcert);
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $sslkey);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        //返回结果
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
     * 订单查询
     * ======================================
     * 当商户后台、网络、服务器等出现异常，商户系统最终未接收到支付通知；
     */
    public function PayQueryOrder($arr = [])
    {
        if (empty($arr)) {
            return false;
        }

        $arrdata = $this->getPaySign($arr);
        $xmldata = $this->xml_encode($arrdata);
        $result = $this->http_post(self::PAY_PREFIX . self::PAY_ORDERQUERY, $xmldata);
        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 退款申请接口
     * ====================================================
     * 注意：同一笔单的部分退款需要设置相同的订单号和不同的
     * out_refund_no。一笔退款失败后重新提交，要采用原来的
     * out_refund_no。总退款金额不能超过用户实际支付金额(现
     * 金券金额不能退款)。
     */
    public function PayRefund($arr = [], $sslcert = '', $sslkey = '')
    {
        if (empty($arr)) {
            return false;
        }
        $arr['refund_fee_type'] = isset($arr['refund_fee_type']) ? $arr['refund_fee_type'] : "CNY";
        $arrdata = $this->getPaySign($arr);
        $xmldata = $this->xml_encode($arrdata);
        $result = $this->postXmlSSLCurl(self::PAY_PREFIX . self::PAY_REFUND, $xmldata, $this->curl_timeout, $sslcert, $sslkey);
        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") {
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 退款查询
     * ======================================
     * 提交退款申请后，通过调用该接口查询退款状态。退款有一定延时，用零钱支付的退款20分钟内到账，银行卡支付的退款3个工作日后重新查询退款状态。
     */
    public function PayRefundQuery($arr = [])
    {
        if (empty($arr)) {
            return false;
        }

        $arrdata = $this->getPaySign($arr);
        $xmldata = $this->xml_encode($arrdata);
        $result = $this->http_post(self::PAY_PREFIX . self::PAY_REFUNDQUERY, $xmldata);
        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 发送现金红包
     * @param  $type 红包类型：普通红包、裂变红包
     * @return
     */
    public function CreatSendRedpack($arr = [], $type = self::TYPE_NORMAL, $sslcert = '', $sslkey = '')
    {
        if (empty($arr)) {
            return false;
        }

        $arrdata = $this->getRedpackSign($arr);
        $xmldata = $this->xml_encode($arrdata);

        $api = ($type == self::TYPE_NORMAL) ? self::PAY_PREFIX . self::REDPACK_SEND_NORMAL : self::PAY_PREFIX . self::REDPACK_SEND_GROUP;

        $result = $this->postXmlSSLCurl($api, $xmldata, $this->curl_timeout, $sslcert, $sslkey);

        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 现金红包签名
     * @param array $arr 需要签名的数据
     * @return array|bool 返回签名字串
     */
    public function getRedpackSign($arr = [])
    {
        if (empty($arr)) {
            return false;
        }
        $arr['wxappid'] = $this->appid;
        $arr['mch_id'] = $this->mch_id;
        $arr['nonce_str'] = $this->generateNonceStr();
        $arr['sign'] = $this->getPaySignature($arr);
        return $arr;
    }

    /**
     * 查询红包记录
     * @return
     */
    public function QueryRedpack($arr = [], $sslcert = '', $sslkey = '')
    {
        if (empty($arr)) {
            return false;
        }

        $arr['bill_type'] = isset($arr['bill_type']) ? $arr['bill_type'] : "MCHT";

        $arrdata = $this->getRedpackSign($arr);
        $xmldata = $this->xml_encode($arrdata);

        // $result = $this->curl_post_ssl(self::PAY_PREFIX . self::REDPACK_QUERY, $postXml);
        $result = $this->postXmlSSLCurl(self::PAY_PREFIX . self::REDPACK_QUERY, $xmldata, $this->curl_timeout, $sslcert, $sslkey);

        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 企业付款到零钱
     * @param array $arr 请求参数
     * @return boolean|array
     * ======================================
     *   $options = array(
     *            'appid'=>'wxdk1234567890', //填写高级调用功能的app id
     *            'mch_id'=>'xxxxxxxxxxxxxxxxxxx', //微信支付商户号
     *            'key'=>'xxxxxxxxxxxxxxxxxxx' //微信支付API密钥
     *        );
     *   $weObj = new Wechat($options);
     *
     *   $arr = array();
     *   $arr['partner_trade_no'] = '商户订单号';
     *   $arr['openid'] = '用户标识';
     *   $arr['check_name'] = "NO_CHECK"; // NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名
     *   $arr['amount'] = '100'; // 企业付款金额，单位为分
     *   $arr['desc'] = '商品描述'; // 企业付款操作说明信息。必填。
     *   $arr['spbill_create_ip'] = request()->server('REMOTE_ADDR');
     *   $ret = $weObj->MchPay($arr);
     */
    public function MchPay($arr = [], $sslcert = '', $sslkey = '')
    {
        if (empty($arr)) {
            return false;
        }

        $arrdata = $this->getMchPaySign($arr);
        $xmldata = $this->xml_encode($arrdata);

        $result = $this->postXmlSSLCurl(self::PAY_PREFIX . self::MCHPAY_TRANS, $xmldata, $this->curl_timeout, $sslcert, $sslkey);

        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 企业付款到零钱签名
     * @param array $arr 需要签名的数据
     * @return array|bool 返回签名字串
     */
    public function getMchPaySign($arr = [])
    {
        if (empty($arr)) {
            return false;
        }
        $arr['mch_appid'] = $this->appid;
        $arr['mchid'] = $this->mch_id;
        $arr['nonce_str'] = $this->generateNonceStr();
        $arr['sign'] = $this->getPaySignature($arr);
        return $arr;
    }

    /**
     * 查询企业付款零钱api
     * @param $arr = ['partner_trade_no' => $partner_trade_no];
     * ======================================
     *  查询企业付款API只支持查询30天内的订单，30天之前的订单请登录商户平台查询。
     * @return
     */
    public function MchPayQuery($arr = [], $sslcert = '', $sslkey = '')
    {
        if (empty($arr)) {
            return false;
        }

        $arrdata = $this->getPaySign($arr);
        $xmldata = $this->xml_encode($arrdata);

        $result = $this->postXmlSSLCurl(self::PAY_PREFIX . self::MCHPAY_QUERY, $xmldata, $this->curl_timeout, $sslcert, $sslkey);

        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 企业付款到银行卡
     * @param array $arr 请求参数
     * @param string $sslcert 证书
     * @param string $sslkey 证书
     * @param string $rsa_public_key_path RSA公钥路径
     * @return boolean|array
     * ======================================
     *   $options = array(
     *            'appid'=>'wxdk1234567890', //填写高级调用功能的app id
     *            'mch_id'=>'xxxxxxxxxxxxxxxxxxx', //微信支付商户号
     *            'key'=>'xxxxxxxxxxxxxxxxxxx' //微信支付API密钥
     *        );
     *   $weObj = new Wechat($options);
     *
     *   $arr = array();
     *   $arr['partner_trade_no'] = '商户订单号';
     *   $arr['enc_bank_no'] = '收款方银行卡号';
     *   $arr['enc_true_name'] = '收款方用户名';
     *   $arr['bank_code'] = '收款方开户行';
     *   $arr['amount'] = '100'; // 企业付款金额，单位为分
     *   $arr['desc'] = '理财'; // 企业付款到银行卡付款说明,即订单备注
     *   $ret = $weObj->MchPayBank($arr);
     */
    public function MchPayBank($arr = [], $sslcert = '', $sslkey = '', $rsa_public_key_path = '')
    {
        if (empty($arr)) {
            return false;
        }

        if (!file_exists($rsa_public_key_path)) {
            return false;
        }

        $publicKey = file_get_contents($rsa_public_key_path);

        $arr['enc_bank_no'] = self::RSAEncrypt($arr['enc_bank_no'], $publicKey);
        $arr['enc_true_name'] = self::RSAEncrypt($arr['enc_true_name'], $publicKey);

        $arrdata = $this->getMchPayBankSign($arr);
        $xmldata = $this->xml_encode($arrdata);

        $result = $this->postXmlSSLCurl(self::PAY_PREFIX . self::MCHPAY_BANK, $xmldata, $this->curl_timeout, $sslcert, $sslkey);

        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 企业付款到银行卡签名
     * @param array $arr 需要签名的数据
     * @return array|bool 返回签名字串
     */
    public function getMchPayBankSign($arr = [])
    {
        if (empty($arr)) {
            return false;
        }
        $arr['mch_id'] = $this->mch_id;
        $arr['nonce_str'] = $this->generateNonceStr();
        $arr['sign'] = $this->getPaySignature($arr);
        return $arr;
    }

    /**
     * 查询企业付款到银行卡api
     * @param $arr = ['partner_trade_no' => $partner_trade_no];
     * ======================================
     * @return
     */
    public function MchPayQueryBank($arr = [], $sslcert = '', $sslkey = '')
    {
        if (empty($arr)) {
            return false;
        }

        $arrdata = $this->getMchPayBankSign($arr);
        $xmldata = $this->xml_encode($arrdata);

        $result = $this->postXmlSSLCurl(self::PAY_PREFIX . self::MCHPAY_QUERY_BANK, $xmldata, $this->curl_timeout, $sslcert, $sslkey);

        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 获取RSA公钥api
     * @param $arr = ['sign_type' => $sign_type]; // 签名类型，支持HMAC-SHA256和MD5
     * ======================================
     * @return
     */
    public function MchPayGetPublicKey($arr = [], $sslcert = '', $sslkey = '')
    {
        if (empty($arr)) {
            return false;
        }

        $arrdata = $this->getMchPayBankSign($arr);
        $xmldata = $this->xml_encode($arrdata);

        $result = $this->postXmlSSLCurl(self::MCHPAY_GET_PUBLICKEY, $xmldata, $this->curl_timeout, $sslcert, $sslkey);

        if ($result) {
            $json = (array)simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($json['return_code'] != "SUCCESS") { //通信失败
                $this->errCode = $json['return_code'];
                $this->errMsg = $json['return_msg'];
                return false;
            } elseif ($json['result_code'] != "SUCCESS") {
                $this->errCode = $json['err_code'];
                $this->errMsg = $json['err_code_des'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 公钥加密 RSA算法加密
     * @param string $content 加密字符串
     * @param string $publicKey 公钥
     * @return bool|null|string
     */
    public function RSAEncrypt($content = '', $publicKey = '')
    {
        $encrypted = '';
        openssl_public_encrypt($content, $encrypted, openssl_pkey_get_public($publicKey), OPENSSL_PKCS1_OAEP_PADDING);

        //加密成功，返回base64编码的字符串
        return base64_encode($encrypted);
    }

    /**
     * 私钥解密 RSA算法加密
     * @param string $encrypted 公钥加密串
     * @param string $privKey 私钥
     * @return bool|null|string
     */
    public function RSADecrypt($encrypted = '', $privKey = '')
    {
        $res = openssl_get_privatekey($privKey);

        //用base64将内容还原成二进制
        $content = base64_decode($encrypted);
        //把需要解密的内容，按128位拆开解密
        $decrypted = '';
        for ($i = 0; $i < strlen($content) / 128; $i++) {
            $data = substr($content, $i * 128, 128);
            openssl_private_decrypt($data, $decrypt, $res);
            $decrypted .= $decrypt;
        }
        openssl_free_key($res);
        return $decrypted;
    }

    /**
     * 创建标签
     * 一个公众号，最多可以创建100个标签。
     * @param string $name 标签名称
     * @return boolean|array
     */
    public function createTags($name)
    {
        if (!$this->get_access_token() && !$this->checkAuth()) {
            return false;
        }
        $data = [
            'tag' => ['name' => $name]
        ];
        $result = $this->http_post(self::API_URL_PREFIX . self::TAGS_CREATE_URL . 'access_token=' . $this->get_access_token(), self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 获取公众号已创建的标签
     * @return boolean|array
     */
    public function getTags()
    {
        if (!$this->get_access_token() && !$this->checkAuth()) {
            return false;
        }
        $result = $this->http_get(self::API_URL_PREFIX . self::TAGS_GET_URL . 'access_token=' . $this->get_access_token());
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 编辑标签名称
     * @param int $tagid 标签id
     * @param string $name 标签名称
     * @return boolean|array
     */
    public function updateTags($tagid, $name)
    {
        if (!$this->get_access_token() && !$this->checkAuth()) {
            return false;
        }
        $data = [
            'tag' => ['id' => $tagid, 'name' => $name]
        ];
        $result = $this->http_post(self::API_URL_PREFIX . self::TAGS_UPDATE_URL . 'access_token=' . $this->get_access_token(), self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 删除标签
     * 请注意，当某个标签下的粉丝超过10w时，后台不可直接删除标签。此时，开发者可以对该标签下的openid列表，先进行取消标签的操作，直到粉丝数不超过10w后，才可直接删除该标签。
     * @param int $tagid 标签id
     * @return boolean|array
     */
    public function deleteTags($tagid)
    {
        if (!$this->get_access_token() && !$this->checkAuth()) {
            return false;
        }
        $data = [
            'tag' => ['id' => $tagid]
        ];
        $result = $this->http_post(self::API_URL_PREFIX . self::TAGS_DELETE_URL . 'access_token=' . $this->get_access_token(), self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 获取标签下粉丝列表
     * @return boolean|array
     */
    public function getTagUserlist($tagid, $next_openid = '')
    {
        if (!$this->get_access_token() && !$this->checkAuth()) {
            return false;
        }
        $data = [
            'tagid' => $tagid,
            'next_openid' => $next_openid
        ];
        $result = $this->http_get(self::API_URL_PREFIX . self::USER_TAG_URL . 'access_token=' . $this->get_access_token(), self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 获取用户身上的标签列表
     * @param string $openid
     * @return boolean|array 成功则返回用户标签list
     */
    public function getUserTaglist($openid)
    {
        if (!$this->get_access_token() && !$this->checkAuth()) {
            return false;
        }
        $data = [
            'openid' => $openid
        ];
        $result = $this->http_post(self::API_URL_PREFIX . self::TAGS_GETIDLIST_URL . 'access_token=' . $this->get_access_token(), self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            } elseif (isset($json['tagid_list'])) {
                return $json['tagid_list'];
            }
        }
        return false;
    }

    /**
     * 批量为用户打标签
     * @param int $tagid 标签id
     * @param string $openid_list 用户openid数组,一次不能超过50个
     * @return boolean|array
     */
    public function batchtaggingTagsMembers($tagid, $openid_list)
    {
        if (!$this->get_access_token() && !$this->checkAuth()) {
            return false;
        }
        $data = [
            'openid_list' => $openid_list,
            'tagid' => $tagid
        ];
        $result = $this->http_post(self::API_URL_PREFIX . self::TAGS_MEMBER_BATCHTAGGING_URL . 'access_token=' . $this->get_access_token(), self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 批量为用户取消标签
     * @param int $tagid 标签id
     * @param string $openid_list 用户openid数组,一次不能超过50个
     * @return boolean|array
     */
    public function batchuntaggingTagsMembers($tagid, $openid_list)
    {
        if (!$this->get_access_token() && !$this->checkAuth()) {
            return false;
        }
        $data = [
            'openid_list' => $openid_list,
            'tagid' => $tagid
        ];
        $result = $this->http_post(self::API_URL_PREFIX . self::TAGS_MEMBER_BATCHUNTAGGING_URL . 'access_token=' . $this->get_access_token(), self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }


    /**
     * 日志记录
     * @param mixed $log 输入日志
     * @return mixed
     */
    public function log($log)
    {
        $log = is_array($log) ? var_export($log, true) : $log;
        if ($this->debug && function_exists('logResult')) {
            logResult($log);
        }
    }

    /**
     * 设置缓存
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($cachename, $value, $expired)
    {
        return parent::setCache($cachename, $value, $expired);
    }

    /**
     * 获取缓存
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename)
    {
        return parent::getCache($cachename);
    }

    /**
     * 清除缓存
     * @param string $cachename
     * @return boolean
     */
    protected function removeCache($cachename)
    {
        return parent::removeCache($cachename);
    }
}
