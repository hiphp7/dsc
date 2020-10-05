<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatTemplate
 */
class WechatTemplate extends Model
{
    protected $table = 'wechat_template';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'template_id',
        'code',
        'content',
        'template',
        'title',
        'add_time',
        'status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getWechatId()
    {
        return $this->wechat_id;
    }

    /**
     * @return mixed
     */
    public function getTemplateId()
    {
        return $this->template_id;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
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
    public function setWechatId($value)
    {
        $this->wechat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTemplateId($value)
    {
        $this->template_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCode($value)
    {
        $this->code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTemplate($value)
    {
        $this->template = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitle($value)
    {
        $this->title = $value;
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
