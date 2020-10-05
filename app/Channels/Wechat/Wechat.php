<?php

namespace App\Channels\Wechat;

use App\Libraries\Wechat as Weixin;
use App\Models\WechatTemplate;
use App\Models\WechatTemplateLog;
use App\Models\WechatUser;

class Wechat
{
    /**
     * 微信通配置
     * @var array
     */
    protected $config = [
        'token' => '',
        'appid' => '',
        'appsecret' => '',
    ];

    /**
     * @var objcet 微信对象
     */
    protected $wechat;
    // protected $content = null;

    /**
     * 构建函数
     * @param array $config 配置
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 设置并记录模板消息
     * @param array $content 模板消息内容
     */
    public function setData($to, $title, $content, $data)
    {
        // 查询openid
        $openid = $this->get_openid($to);

        $template = WechatTemplate::where('code', $title)->where('status', 1)->first();
        $template = $template ? $template->toArray() : [];

        if ($openid && $template && $template['title']) {
            $content['first'] = !empty($content['first']) ? $content['first'] : ['value' => $template['title'], 'color' => '#173177'];
            $content['remark'] = !empty($template['content']) ? ['value' => $template['content'], 'color' => '#FF0000'] : $content['remark'];
            $rs['code'] = $title;
            $rs['openid'] = $openid;
            $rs['data'] = serialize($content);
            $rs['url'] = $data['url'];
            $rs['wechat_id'] = $data['wechat_id'];

            WechatTemplateLog::insert($rs);
        }
        return $this;
    }

    /**
     * 执行发送
     * @param $to user_id
     * @return boolen
     */
    public function send($to = 0, $title = '')
    {
        // 查询openid
        $openid = $this->get_openid($to);

        $result = WechatTemplateLog::where('status', 0)
            ->where('openid', $openid)
            ->where('code', $title)
            ->orderBy('id', 'desc');

        $result = $result->with([
            'wechatTemplate'
        ]);

        $result = $result ? $result->toArray() : [];

        if ($result) {
            $data['touser'] = $result['openid'];
            $data['template_id'] = $result['wechat_template']['template_id'] ?? 0;
            $data['url'] = $result['url'];
            $data['topcolor'] = '#FF0000';
            $data['data'] = unserialize($result['data']);
            $weObj = new Weixin($this->config);
            $rs = $weObj->sendTemplateMessage($data);
            if (empty($rs)) {
                // logResult($weObj->errMsg);
                return false;
            }

            WechatTemplateLog::where('code', $result['code'])
                ->where('openid', $result['openid'])
                ->where('wechat_id', $result['wechat_id'])
                ->update([
                    'msgid' => $rs['msgid']
                ]);

            return true;
        }
        return false;
    }

    /**
     * 获取openid
     * @param $to user_id
     * @return $openid
     */
    private function get_openid($to = 0)
    {
        $openid = 0;
        if (request()->hasCookie('wechat_ru_id')) {
            $openid = request()->hasCookie('seller_openid') ? request()->cookie('seller_openid') : session('seller_openid');
        } else {
            if ($to) {
                $openid = WechatUser::whereHas('wechatTemplate', function ($query) use ($to) {
                    $query->where('user_id', $to);
                })->value('openid');

                $openid = $openid ? $openid : 0;
            }
        }

        return $openid;
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
        unset($this->wechat);
    }
}
