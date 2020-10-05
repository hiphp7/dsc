<?php

namespace App\Channels\Sms\Driver;

use App\Models\SmsTemplate;
use App\Repositories\Common\TimeRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Huawei
 * @package App\Channels\Sms\Driver
 * @see https://support.huaweicloud.com/api-msgsms/sms_05_0001.html
 */
class Huawei
{
    const ENDPOINT_HOST = 'https://api.rtc.huaweicloud.com:10443';

    const ENDPOINT_URI = '/sms/batchSendSms/v1';

    const SUCCESS_CODE = '000000';

    /**
     * 短信类配置
     * @var array
     */
    protected $config = [
        'app_key' => '',
        'app_secret' => '',
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

        if (isset($data['temp_content']) && !empty($data['temp_content'])) {
            $msg['temp_content'] = $data['temp_content'];
        } else {
            $msg['temp_content'] = $msg['temp_content'] ?? '';
        }

        // 替换消息变量
        preg_match_all('/\$\{(.*?)\}/', $msg['temp_content'], $matches);
        foreach ($matches[1] as $vo) {
            $msg['temp_content'] = str_replace('${' . $vo . '}', '["' . $content[$vo] . '"]', $msg['temp_content']);
        }
        preg_match_all('/\[(.*?)\]/', $msg['temp_content'], $matches);
        $templateParas = '[' . implode(',', $matches[1]) . ']';

        // 组装数据
        $this->content = [
            'sender' => $msg['sender'],
            'signature' => $msg['set_sign'],
            'templateId' => $msg['temp_id'],
            'statusCallback' => $msg['call_back'] ?? '',
            'templateParas' => $templateParas
        ];

        return $this;
    }

    /**
     * 发送短信
     *
     * @param int $mobile
     * @return bool
     */
    public function sendSms($mobile = 0)
    {
        $app_key = isset($this->config['huawei_sms_key']) ? $this->config['huawei_sms_key'] : $this->config['app_key'];
        $app_secret = isset($this->config['huawei_sms_secret']) ? $this->config['huawei_sms_secret'] : $this->config['app_secret'];

        $endpoint = $this->getEndpoint();
        $headers = $this->getHeaders($app_key, $app_secret);

        $messageData = $this->content['templateParas'];

        $params = [
            'from' => $this->content['sender'],
            'to' => $mobile,
            'templateId' => $this->content['templateId'],
            'templateParas' => $messageData,
            'signature' => $this->content['signature'],
            'statusCallback' => $this->content['statusCallback'],
        ];

        $client = new Client();

        $result = ['code' => self::SUCCESS_CODE];

        try {
            $result = $client->request('post', $endpoint, [
                'headers' => $headers,
                'form_params' => $params,
                //为防止因HTTPS证书认证失败造成API调用失败，需要先忽略证书信任问题
                'verify' => false,
            ]);

            $result = $this->unwrapResponse($result);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $result = $this->unwrapResponse($e->getResponse());
            }
        }

        if ($result['code'] == self::SUCCESS_CODE) {
            return true;
        } else {
            $code = $result['code'] ?? '';
            $status = $result['result'][0]['status'] ?? '';

            $this->errorInfo = $this->getMessage($status, $code);
            Log::error($this->errorInfo);
        }

        return false;
    }

    /**
     * 构造 Endpoint.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return self::ENDPOINT_HOST . self::ENDPOINT_URI;
    }

    /**
     * 获取请求 Headers 参数.
     *
     * @param string $appKey
     * @param string $appSecret
     *
     * @return array
     */
    protected function getHeaders($appKey, $appSecret)
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
            'X-WSSE' => $this->buildWsseHeader($appKey, $appSecret),
        ];
    }

    /**
     * 构造X-WSSE参数值
     *
     * @param string $appKey
     * @param string $appSecret
     *
     * @return string
     */
    protected function buildWsseHeader($appKey, $appSecret)
    {
        $timestamp = app(TimeRepository::class)->getLocalDate('Y-m-d\TH:i:s\Z');
        $nonce = uniqid();
        $passwordDigest = base64_encode(hash('sha256', ($nonce . $timestamp . $appSecret)));

        return sprintf(
            'UsernameToken Username="%s",PasswordDigest="%s",Nonce="%s",Created="%s"',
            $appKey,
            $passwordDigest,
            $nonce,
            $timestamp
        );
    }

    /**
     * 转换返回结果
     *
     * @param ResponseInterface $response
     * @return mixed|string
     */
    protected function unwrapResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $contents = $response->getBody()->getContents();

        if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
            return dsc_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            return dsc_decode(json_encode(simplexml_load_string($contents)), true);
        }

        return $contents;
    }

    /**
     * 错误信息列表
     * @param string $status
     * @param string $code
     * @return string
     */
    public function getMessage($status = '', $code = '')
    {
        $message = [
            'E200015' => '待发送短信数量太大',
            'E200028' => '模板变量校验失败',
            'E200029' => '模板类型校验失败',
            'E200030' => '模板未激活',
            'E200031' => '协议校验失败',
            'E200033' => '模板类型不正确'
        ];

        if (!isset($message[$status])) {
            $codeMsg = [
                'E000000' => '系统异常',
                'E000027' => '非法请求',
                'E000102' => 'app_key无效',
                'E000103' => 'app_key不可用',
                'E000104' => 'app_secret无效',
                'E000106' => 'app_key没有调用本API的权限',
                'E000109' => '用户状态未激活',
                'E000110' => '时间超出限制',
                'E000112' => '用户状态已冻结'
            ];

            return $codeMsg[$code] ?? '短信发送失败';
        }

        return $message[$status] ?? '短信发送失败';
    }

    /**
     * 返回错误信息
     * @return string
     */
    public function getError()
    {
        return $this->errorInfo;
    }
}
