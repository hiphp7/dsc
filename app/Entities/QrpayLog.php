<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class QrpayLog
 */
class QrpayLog extends Model
{
    protected $table = 'qrpay_log';

    public $timestamps = false;

    protected $fillable = [
        'pay_order_sn',
        'pay_amount',
        'qrpay_id',
        'ru_id',
        'pay_user_id',
        'openid',
        'payment_code',
        'trade_no',
        'notify_data',
        'pay_status',
        'is_settlement',
        'pay_desc',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPayOrderSn()
    {
        return $this->pay_order_sn;
    }

    /**
     * @return mixed
     */
    public function getPayAmount()
    {
        return $this->pay_amount;
    }

    /**
     * @return mixed
     */
    public function getQrpayId()
    {
        return $this->qrpay_id;
    }

    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getPayUserId()
    {
        return $this->pay_user_id;
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
    public function getPaymentCode()
    {
        return $this->payment_code;
    }

    /**
     * @return mixed
     */
    public function getTradeNo()
    {
        return $this->trade_no;
    }

    /**
     * @return mixed
     */
    public function getNotifyData()
    {
        return $this->notify_data;
    }

    /**
     * @return mixed
     */
    public function getPayStatus()
    {
        return $this->pay_status;
    }

    /**
     * @return mixed
     */
    public function getIsSettlement()
    {
        return $this->is_settlement;
    }

    /**
     * @return mixed
     */
    public function getPayDesc()
    {
        return $this->pay_desc;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayOrderSn($value)
    {
        $this->pay_order_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayAmount($value)
    {
        $this->pay_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQrpayId($value)
    {
        $this->qrpay_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayUserId($value)
    {
        $this->pay_user_id = $value;
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
    public function setPaymentCode($value)
    {
        $this->payment_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTradeNo($value)
    {
        $this->trade_no = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setPayStatus($value)
    {
        $this->pay_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsSettlement($value)
    {
        $this->is_settlement = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayDesc($value)
    {
        $this->pay_desc = $value;
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
}
