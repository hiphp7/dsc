<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatRedpackLog
 */
class WechatRedpackLog extends Model
{
    protected $table = 'wechat_redpack_log';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'market_id',
        'hb_type',
        'openid',
        'hassub',
        'money',
        'time',
        'mch_billno',
        'mch_id',
        'wxappid',
        'bill_type',
        'notify_data'
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
    public function getMarketId()
    {
        return $this->market_id;
    }

    /**
     * @return mixed
     */
    public function getHbType()
    {
        return $this->hb_type;
    }

    /**
     * @return mixed
     */
    public function getOpenid()
    {
        return $this->openid;
    }

    /**
     * @return mixed
     */
    public function getHassub()
    {
        return $this->hassub;
    }

    /**
     * @return mixed
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return mixed
     */
    public function getMchBillno()
    {
        return $this->mch_billno;
    }

    /**
     * @return mixed
     */
    public function getMchId()
    {
        return $this->mch_id;
    }

    /**
     * @return mixed
     */
    public function getWxappid()
    {
        return $this->wxappid;
    }

    /**
     * @return mixed
     */
    public function getBillType()
    {
        return $this->bill_type;
    }

    /**
     * @return mixed
     */
    public function getNotifyData()
    {
        return $this->notify_data;
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
    public function setMarketId($value)
    {
        $this->market_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHbType($value)
    {
        $this->hb_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOpenid($value)
    {
        $this->openid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHassub($value)
    {
        $this->hassub = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMoney($value)
    {
        $this->money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTime($value)
    {
        $this->time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMchBillno($value)
    {
        $this->mch_billno = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMchId($value)
    {
        $this->mch_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxappid($value)
    {
        $this->wxappid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBillType($value)
    {
        $this->bill_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNotifyData($value)
    {
        $this->notify_data = $value;
        return $this;
    }
}
