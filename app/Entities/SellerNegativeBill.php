<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerNegativeBill
 */
class SellerNegativeBill extends Model
{
    protected $table = 'seller_negative_bill';

    public $timestamps = false;

    protected $fillable = [
        'bill_sn',
        'commission_bill_sn',
        'commission_bill_id',
        'seller_id',
        'return_amount',
        'return_shippingfee',
        'chargeoff_status',
        'start_time',
        'end_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBillSn()
    {
        return $this->bill_sn;
    }

    /**
     * @return mixed
     */
    public function getCommissionBillSn()
    {
        return $this->commission_bill_sn;
    }

    /**
     * @return mixed
     */
    public function getCommissionBillId()
    {
        return $this->commission_bill_id;
    }

    /**
     * @return mixed
     */
    public function getSellerId()
    {
        return $this->seller_id;
    }

    /**
     * @return mixed
     */
    public function getReturnAmount()
    {
        return $this->return_amount;
    }

    /**
     * @return mixed
     */
    public function getReturnShippingfee()
    {
        return $this->return_shippingfee;
    }

    /**
     * @return mixed
     */
    public function getChargeoffStatus()
    {
        return $this->chargeoff_status;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBillSn($value)
    {
        $this->bill_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommissionBillSn($value)
    {
        $this->commission_bill_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommissionBillId($value)
    {
        $this->commission_bill_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSellerId($value)
    {
        $this->seller_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnAmount($value)
    {
        $this->return_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnShippingfee($value)
    {
        $this->return_shippingfee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setChargeoffStatus($value)
    {
        $this->chargeoff_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStartTime($value)
    {
        $this->start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }
}
