<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatCustomMessage
 */
class WechatCustomMessage extends Model
{
    protected $table = 'wechat_custom_message';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'uid',
        'msg',
        'send_time',
        'is_wechat_admin',
        'msgtype'
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
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return mixed
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * @return mixed
     */
    public function getSendTime()
    {
        return $this->send_time;
    }

    /**
     * @return mixed
     */
    public function getIsWechatAdmin()
    {
        return $this->is_wechat_admin;
    }

    /**
     * @return mixed
     */
    public function getMsgtype()
    {
        return $this->msgtype;
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
    public function setUid($value)
    {
        $this->uid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMsg($value)
    {
        $this->msg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendTime($value)
    {
        $this->send_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsWechatAdmin($value)
    {
        $this->is_wechat_admin = $value;
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
}
