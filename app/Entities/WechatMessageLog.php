<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatMessageLog
 */
class WechatMessageLog extends Model
{
    protected $table = 'wechat_message_log';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'fromusername',
        'createtime',
        'keywords',
        'msgtype',
        'msgid',
        'is_send'
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
    public function getFromusername()
    {
        return $this->fromusername;
    }

    /**
     * @return mixed
     */
    public function getCreatetime()
    {
        return $this->createtime;
    }

    /**
     * @return mixed
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return mixed
     */
    public function getMsgtype()
    {
        return $this->msgtype;
    }

    /**
     * @return mixed
     */
    public function getMsgid()
    {
        return $this->msgid;
    }

    /**
     * @return mixed
     */
    public function getIsSend()
    {
        return $this->is_send;
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
    public function setFromusername($value)
    {
        $this->fromusername = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreatetime($value)
    {
        $this->createtime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKeywords($value)
    {
        $this->keywords = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMsgtype($value)
    {
        $this->msgtype = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMsgid($value)
    {
        $this->msgid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsSend($value)
    {
        $this->is_send = $value;
        return $this;
    }
}
