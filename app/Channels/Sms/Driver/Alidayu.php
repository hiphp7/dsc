<?php

namespace App\Channels\Sms\Driver;

use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Class Alidayu
 * @package App\Channels\Sms\Driver
 */
class Alidayu
{
    /**
     * 短信类配置
     * @var array
     */
    protected $config = [
        'ali_appkey' => '',
        'ali_secretkey' => '',
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
     * @param $title
     * @param $content
     * @return $this
     */
    public function setSms($title, $content, $data)
    {
        $msg = SmsTemplate::where('send_time', $title)->first();
        $msg = $msg ? $msg->toArray() : [];

        foreach ($content as $key => $vo) {
            settype($content[$key], 'string');
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
            'sms_type' => 'normal',
            'sms_free_sign_name' => $msg['set_sign'],
            'sms_template_code' => $msg['temp_id'],
            'sms_param' => json_encode($content)
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
        app(\App\Plugins\Aliyunyu\Alidayu::class)->index();

        $c = new \TopClient;
        $c->appkey = $this->config['ali_appkey'];
        $c->secretKey = $this->config['ali_secretkey'];
        $c->format = 'json';
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req->setSmsType($this->content['sms_type']);
        $req->setSmsFreeSignName($this->content['sms_free_sign_name']);
        $req->setSmsParam($this->content['sms_param']);
        $req->setRecNum($mobile);
        $req->setSmsTemplateCode($this->content['sms_template_code']);
        $resp = $c->execute($req);
        if ($resp->code == 0) {
            return true;
        } elseif ($resp->sub_msg) {
            $this->errorInfo = $resp->sub_msg;
            Log::error($this->errorInfo);
        } else {
            $this->errorInfo = $resp->msg;
            Log::error($this->errorInfo);
        }

        return false;
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
