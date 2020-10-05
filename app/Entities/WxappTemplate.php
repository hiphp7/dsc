<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WxappTemplate
 */
class WxappTemplate extends Model
{
    protected $table = 'wxapp_template';

    public $timestamps = false;

    protected $fillable = [
        'wx_wechat_id',
        'wx_template_id',
        'wx_code',
        'wx_content',
        'wx_template',
        'wx_keyword_id',
        'wx_title',
        'add_time',
        'status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getWxWechatId()
    {
        return $this->wx_wechat_id;
    }

    /**
     * @return mixed
     */
    public function getWxTemplateId()
    {
        return $this->wx_template_id;
    }

    /**
     * @return mixed
     */
    public function getWxCode()
    {
        return $this->wx_code;
    }

    /**
     * @return mixed
     */
    public function getWxContent()
    {
        return $this->wx_content;
    }

    /**
     * @return mixed
     */
    public function getWxTemplate()
    {
        return $this->wx_template;
    }

    /**
     * @return mixed
     */
    public function getWxKeywordId()
    {
        return $this->wx_keyword_id;
    }

    /**
     * @return mixed
     */
    public function getWxTitle()
    {
        return $this->wx_title;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxWechatId($value)
    {
        $this->wx_wechat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxTemplateId($value)
    {
        $this->wx_template_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxCode($value)
    {
        $this->wx_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxContent($value)
    {
        $this->wx_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxTemplate($value)
    {
        $this->wx_template = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxKeywordId($value)
    {
        $this->wx_keyword_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxTitle($value)
    {
        $this->wx_title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }
}
