<?php

namespace App\Channels\Sms\Driver;

use App\Libraries\Http;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Class Dscsms
 * @package App\Channels\Sms\Driver
 * @see http://wiki.sc.com/index.php?title=Sms/send
 */
class Dscsms
{
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
    protected $sms_api = "https://cloud.ecjia.com/sites/api/?url=sms/send";
    protected $content = null;
    protected $errorInfo = null;

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
            $msg['temp_content'] = str_replace('${' . $vo . '}', $content[$vo], $msg['temp_content']);
        }
        $this->content = $msg['temp_content'];

        return $this;
    }

    /**
     * 发送短信
     * @param string $mobile 收件人
     * @return boolean
     */
    public function sendSms($mobile)
    {
        $app_key = isset($this->config['dsc_appkey']) ? $this->config['dsc_appkey'] : $this->config['app_key'];
        $app_secret = isset($this->config['dsc_appsecret']) ? $this->config['dsc_appsecret'] : $this->config['app_secret'];

        $post_data = [
            'app_key' => $app_key,
            'app_secret' => $app_secret,
            'mobile' => $mobile,
            'content' => $this->content
        ];

        $res = Http::doPost($this->sms_api, $post_data);
        $data = dsc_decode($res, true);

        //开启调试模式 TODO 此处暂时只能发送一次
        if ($data['status']['succeed']) {
            return true;
        } else {
            $this->errorInfo = $data['status']['error_desc'];
            Log::error(var_export($data, true));
            return false;
        }
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
