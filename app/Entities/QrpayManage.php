<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class QrpayManage
 */
class QrpayManage extends Model
{
    protected $table = 'qrpay_manage';

    public $timestamps = false;

    protected $fillable = [
        'qrpay_name',
        'type',
        'amount',
        'discount_id',
        'tag_id',
        'qrpay_status',
        'ru_id',
        'qrpay_code',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getQrpayName()
    {
        return $this->qrpay_name;
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
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return mixed
     */
    public function getDiscountId()
    {
        return $this->discount_id;
    }

    /**
     * @return mixed
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * @return mixed
     */
    public function getQrpayStatus()
    {
        return $this->qrpay_status;
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
    public function getQrpayCode()
    {
        return $this->qrpay_code;
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
    public function setQrpayName($value)
    {
        $this->qrpay_name = $value;
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
    public function setAmount($value)
    {
        $this->amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDiscountId($value)
    {
        $this->discount_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTagId($value)
    {
        $this->tag_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQrpayStatus($value)
    {
        $this->qrpay_status = $value;
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
    public function setQrpayCode($value)
    {
        $this->qrpay_code = $value;
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
