<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatQrcode
 */
class WechatQrcode extends Model
{
    protected $table = 'wechat_qrcode';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'type',
        'expire_seconds',
        'scene_id',
        'username',
        'function',
        'ticket',
        'qrcode_url',
        'endtime',
        'scan_num',
        'status',
        'sort'
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
    public function getExpireSeconds()
    {
        return $this->expire_seconds;
    }

    /**
     * @return mixed
     */
    public function getSceneId()
    {
        return $this->scene_id;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @return mixed
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * @return mixed
     */
    public function getQrcodeUrl()
    {
        return $this->qrcode_url;
    }

    /**
     * @return mixed
     */
    public function getEndtime()
    {
        return $this->endtime;
    }

    /**
     * @return mixed
     */
    public function getScanNum()
    {
        return $this->scan_num;
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
    public function getSort()
    {
        return $this->sort;
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
    public function setExpireSeconds($value)
    {
        $this->expire_seconds = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSceneId($value)
    {
        $this->scene_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUsername($value)
    {
        $this->username = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFunction($value)
    {
        $this->function = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTicket($value)
    {
        $this->ticket = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQrcodeUrl($value)
    {
        $this->qrcode_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndtime($value)
    {
        $this->endtime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setScanNum($value)
    {
        $this->scan_num = $value;
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
    public function setSort($value)
    {
        $this->sort = $value;
        return $this;
    }
}
