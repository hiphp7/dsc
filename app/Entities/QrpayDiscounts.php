<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class QrpayDiscounts
 */
class QrpayDiscounts extends Model
{
    protected $table = 'qrpay_discounts';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'min_amount',
        'discount_amount',
        'max_discount_amount',
        'status',
        'add_time'
    ];

    protected $guarded = [];


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
    public function getMinAmount()
    {
        return $this->min_amount;
    }

    /**
     * @return mixed
     */
    public function getDiscountAmount()
    {
        return $this->discount_amount;
    }

    /**
     * @return mixed
     */
    public function getMaxDiscountAmount()
    {
        return $this->max_discount_amount;
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
    public function getAddTime()
    {
        return $this->add_time;
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
    public function setMinAmount($value)
    {
        $this->min_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDiscountAmount($value)
    {
        $this->discount_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMaxDiscountAmount($value)
    {
        $this->max_discount_amount = $value;
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
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }
}
