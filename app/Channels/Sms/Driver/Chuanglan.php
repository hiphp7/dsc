<?php

namespace App\Channels\Sms\Driver;

use App\Libraries\Http;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Class Chuanglan
 * @package App\Channels\Sms\Driver
 * @see https://zz.253.com/v5.html#/api_doc
 */
class Chuanglan
{
    const API_SEND_URL = '/msg/send/json'; //创蓝发送短信接口URL
    const API_VARIABLE_URL = '/msg/variable/json';//创蓝变量短信接口URL
    const API_BALANCE_QUERY_URL = '/msg/balance/json';//创蓝短信余额查询接口URL

    /**
     * 短信类配置
     * @var array
     */
    protected $config = [
        'chuanglan_account' => '', // 创蓝API账号
        'chuanglan_password' => '', // 创蓝API密码
        'chuanglan_api_url' => '', // 登陆管理后台获取完整域名
        'chuanglan_signa' => '', // 运营商签名符号
    ];

    /**
     * @var null 短信对象
     */
    protected $content = null;
    protected $params = []; //最多不能超过1000个参数组
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
            $this->params[] = $content[$vo];
            $msg['temp_content'] = str_replace('${' . $vo . '}', '{$var}', $msg['temp_content']);
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
        $post_data = [
            'account' => $this->config['chuanglan_account'],
            'password' => $this->config['chuanglan_password'],
            'msg' => '【' . $this->config['chuanglan_signa'] . '】' . $this->content,
            'params' => $mobile . ',' . implode(',', $this->params),
            'report' => 'true'
        ];
        $post_data = json_encode($post_data);

        $response = Http::doPost($this->config['chuanglan_api_url'] . self::API_VARIABLE_URL, $post_data, 5, 'Content-Type: application/json; charset=utf-8');

        $data = dsc_decode($response, true);

        //开启调试模式 TODO 此处暂时只能发送一次
        if (isset($data['code']) && $data['code'] == 0) {
            return true;
        } else {
            $this->errorInfo = $this->getMessage($data['code']);
            Log::error($this->errorInfo);
            return false;
        }
    }

    /**
     * 查询额度
     * @return bool|\mix|string
     */
    public function queryBalance()
    {
        // 查询参数
        $postArr = [
            'account' => $this->config['chuanglan_account'],
            'password' => $this->config['chuanglan_password'],
        ];
        $post_data = json_encode($postArr);

        $response = Http::doPost($this->config['chuanglan_api_url'] . self::API_BALANCE_QUERY_URL, $post_data, 5, 'Content-Type: application/json; charset=utf-8');

        $data = dsc_decode($response, true);

        if (isset($data['balance'])) {
            // echo '余额	' . $data['balance'].'	条' ;
            return $data;
        } else {
            $this->errorInfo = $data['errorMsg'];
            Log::error($this->errorInfo);
            return false;
        }
    }

    /**
     * 错误信息列表
     *
     * @param string $code
     * @return mixed|string
     */
    public function getMessage($code = '')
    {
        $message = [
            '101' => '无此用户',
            '102' => '密码错',
            '103' => '提交过快',
            '104' => '系统忙',
            '105' => '敏感短信（短信内容包含敏感词）',
            '106' => '消息长度错（>536 或<=0）',
            '107' => '包含错误的手机号码',
            '108' => '手机号码个数错',
            '109' => '无发送额度（该用户可用短信数已使用完）',
            '110' => '不在发送时间内',
            '113' => '扩展码格式错（非数字或者长度不对）',
            '114' => '可用参数组个数错误',
            '116' => '签名不合法或未带签名',
            '117' => 'IP 地址认证错,请求调用的 IP 地址不是系统登记的 IP 地址',
            '118' => '用户没有相应的发送权限（账号被禁止发送）',
            '119' => '用户已过期',
            '120' => '违反防盗用策略(日发送限制)',
            '123' => '发送类型错误',
            '124' => '白模板匹配错误',
            '125' => '匹配驳回模板，提交失败',
            '127' => '定时发送时间格式错误',
            '128' => '内容编码失败',
            '129' => 'JSON 格式错误',
            '130' => '请求参数错误（缺少必填参数）',
        ];

        return $message[$code] ?? '短信发送失败';
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
