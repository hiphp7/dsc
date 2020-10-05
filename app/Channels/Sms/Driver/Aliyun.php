<?php

namespace App\Channels\Sms\Driver;

use App\Libraries\Http;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Class Aliyun
 * @package App\Channels\Sms\Driver
 * @see https://help.aliyun.com/document_detail/101414.html
 */
class Aliyun
{
    const ENDPOINT_URL = 'http://dysmsapi.aliyuncs.com';
    const ENDPOINT_METHOD = 'SendSms';
    const ENDPOINT_VERSION = '2017-05-25';
    const ENDPOINT_FORMAT = 'JSON';
    const ENDPOINT_REGION_ID = 'cn-hangzhou';
    const ENDPOINT_SIGNATURE_METHOD = 'HMAC-SHA1';
    const ENDPOINT_SIGNATURE_VERSION = '1.0';

    /**
     * 短信类配置
     * @var array
     */
    protected $config = [
        'access_key_id' => '',
        'access_key_secret' => '',
    ];

    /**
     * @var objcet 短信对象
     */
    protected $content = [];
    protected $errorInfo = '';

    /**
     * 构建函数
     * @param array $config 短信配置
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 设置短信信息
     * @param string $title
     * @param array $content
     * @param array $data
     * @return $this
     */
    public function setSms($title = '', $content = [], $data = [])
    {
        $msg = SmsTemplate::where('send_time', $title)->first();
        $msg = $msg ? $msg->toArray() : [];

        if ($data && (isset($data['set_sign']) && isset($data['temp_id'])) && (!empty($data['set_sign']) && !empty($data['temp_id']))) {
            preg_match_all('/\$\{(.*?)\}/', $data['temp_content'], $matches);
            $temp_content = $matches[1] ?? [];

            foreach ($content as $key => $vo) {
                if ($temp_content && in_array($key, $temp_content)) {
                    settype($content[$key], 'string');
                } else {
                    unset($content[$key]);
                }
            }
        } else {
            foreach ($content as $key => $vo) {
                settype($content[$key], 'string');
            }
        }

        if (isset($data['set_sign']) && !empty($data['set_sign'])) {
            $msg['set_sign'] = $data['set_sign'];
        } else {
            $msg['set_sign'] = $msg['set_sign'] ?? '';
        }

        if (isset($data['temp_id']) && !empty($data['temp_id'])) {
            $msg['temp_id'] = $data['temp_id'];
        } else {
            $msg['temp_id'] = $msg['temp_id'] ?? '';
        }

        // 组装数据
        $this->content = [
            'SignName' => $msg['set_sign'],
            'TemplateCode' => $msg['temp_id'],
            'TemplateParam' => json_encode($content)
        ];

        return $this;
    }

    /**
     * 发送短信
     * @param string $to 收件人
     * @return boolean
     */
    public function sendSms($mobile)
    {
        $params = [
            'RegionId' => self::ENDPOINT_REGION_ID,
            'AccessKeyId' => $this->config['access_key_id'],
            'Format' => self::ENDPOINT_FORMAT,
            'SignatureMethod' => self::ENDPOINT_SIGNATURE_METHOD,
            'SignatureVersion' => self::ENDPOINT_SIGNATURE_VERSION,
            'SignatureNonce' => uniqid(),
            'Timestamp' => $this->getTimestamp(),
            'Action' => self::ENDPOINT_METHOD,
            'Version' => self::ENDPOINT_VERSION,
            'PhoneNumbers' => strval($mobile),
            'SignName' => $this->content['SignName'],
            'TemplateCode' => $this->content['TemplateCode'],
            'TemplateParam' => $this->content['TemplateParam'],
        ];

        $params['Signature'] = $this->generateSign($params);

        $response = Http::doPost(self::ENDPOINT_URL, $params);

        $resp = dsc_decode($response, true);
        if ($resp['Code'] == 'OK') {
            return true;
        } else {
            $this->errorInfo = $resp['Message'];
            Log::error($this->errorInfo);
        }
        return false;
    }

    /**
     * Generate Sign.
     *
     * @param array $params
     *
     * @return string
     */
    protected function generateSign($params)
    {
        ksort($params);
        $accessKeySecret = $this->config['access_key_secret'];
        $stringToSign = 'POST&%2F&' . urlencode(http_build_query($params, null, '&', PHP_QUERY_RFC3986));

        return base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
    }

    /**
     * @return false|string
     */
    protected function getTimestamp()
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('GMT');
        $timestamp = date('Y-m-d\TH:i:s\Z');
        date_default_timezone_set($timezone);

        return $timestamp;
    }

    /**
     * 返回错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorInfo;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        unset($this->sms);
    }
}
