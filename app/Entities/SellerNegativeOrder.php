<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerNegativeOrder
 */
class SellerNegativeOrder extends Model
{
    protected $table = 'seller_negative_order';

    public $timestamps = false;

    protected $fillable = [
        'negative_id',
        'order_id',
        'order_sn',
        'ret_id',
        'return_sn',
        'seller_id',
        'return_amount',
        'return_shippingfee',
        'settle_accounts',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getNegativeId()
    {
        return $this->negative_id;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getOrderSn()
    {
        return $this->order_sn;
    }

    /**
     * @return mixed
     */
    public function getRetId()
    {
        return $this->ret_id;
    }

    /**
     * @return mixed
     */
    public function getReturnSn()
    {
        return $this->return_sn;
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
    public function getSettleAccounts()
    {
        return $this->settle_accounts;
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
    public function setNegativeId($value)
    {
        $this->negative_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderSn($value)
    {
        $this->order_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRetId($value)
    {
        $this->ret_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnSn($value)
    {
        $this->return_sn = $value;
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
    public function setSettleAccounts($value)
    {
        $this->settle_accounts = $value;
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
