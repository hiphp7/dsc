<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatReply
 */
class WechatReply extends Model
{
    protected $table = 'wechat_reply';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'type',
        'content',
        'media_id',
        'rule_name',
        'add_time',
        'reply_type'
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
    public function getType()
    {
        return $this->type;
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
    public function getMediaId()
    {
        return $this->media_id;
    }

    /**
     * @return mixed
     */
    public function getRuleName()
    {
        return $this->rule_name;
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
    public function getReplyType()
    {
        return $this->reply_type;
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
    public function setType($value)
    {
        $this->type = $value;
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
    public function setMediaId($value)
    {
        $this->media_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuleName($value)
    {
        $this->rule_name = $value;
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
    public function setReplyType($value)
    {
        $this->reply_type = $value;
        return $this;
    }
}
