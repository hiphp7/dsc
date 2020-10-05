<?php

namespace App\Channels\Sms\Driver;

use App\Libraries\Http;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Class Ihuyi
 * @package App\Channels\Sms\Driver
 * @see http://106.ihuyi.cn/webservice/sms.php?op=Submit
 */
class Ihuyi
{
    /**
     * 短信类配置
     * @var array
     */
    protected $config = [
        'sms_name' => '',
        'sms_password' => '',
    ];

    /**
     * @var objcet 短信对象
     */
    protected $sms_api = "https://106.ihuyi.com/webservice/sms.php?method=Submit";
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
        $sms_name = isset($this->config['sms_ecmoban_user']) ? $this->config['sms_ecmoban_user'] : $this->config['sms_name'];
        $sms_password = isset($this->config['sms_ecmoban_password']) ? $this->config['sms_ecmoban_password'] : $this->config['sms_password'];

        $post_data = [
            'account' => $sms_name,
            'password' => $sms_password,
            'mobile' => $mobile,
            'content' => $this->content
        ];

        $res = Http::doPost($this->sms_api, $post_data);
        $data = $this->xmlToArray($res);

        //开启调试模式 TODO 此处暂时只能发送一次
        if ($data['SubmitResult']['code'] == 2) {
            return true;
        } else {
            $this->errorInfo = $data['SubmitResult']['msg'];
            Log::error($this->errorInfo);
            return false;
        }
    }

    /**
     * @param $xml
     * @return array
     */
    private function xmlToArray($xml)
    {
        $arr = [];

        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $subxml = $matches[2][$i];
                $key = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = $this->xmlToArray($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
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
