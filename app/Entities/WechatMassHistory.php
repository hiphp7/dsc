<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatMassHistory
 */
class WechatMassHistory extends Model
{
    protected $table = 'wechat_mass_history';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'media_id',
        'type',
        'status',
        'send_time',
        'msg_id',
        'totalcount',
        'filtercount',
        'sentcount',
        'errorcount'
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
    public function getMediaId()
    {
        return $this->media_id;
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
    public function getStatus()
    {
        return $this->status;
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
    public function getMsgId()
    {
        return $this->msg_id;
    }

    /**
     * @return mixed
     */
    public function getTotalcount()
    {
        return $this->totalcount;
    }

    /**
     * @return mixed
     */
    public function getFiltercount()
    {
        return $this->filtercount;
    }

    /**
     * @return mixed
     */
    public function getSentcount()
    {
        return $this->sentcount;
    }

    /**
     * @return mixed
     */
    public function getErrorcount()
    {
        return $this->errorcount;
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
    public function setMediaId($value)
    {
        $this->media_id = $value;
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
    public function setStatus($value)
    {
        $this->status = $value;
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
    public function setMsgId($value)
    {
        $this->msg_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTotalcount($value)
    {
        $this->totalcount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFiltercount($value)
    {
        $this->filtercount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSentcount($value)
    {
        $this->sentcount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setErrorcount($value)
    {
        $this->errorcount = $value;
        return $this;
    }
}
