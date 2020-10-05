<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerBillOrder
 */
class SellerBillOrder extends Model
{
    protected $table = 'seller_bill_order';

    public $timestamps = false;

    protected $fillable = [
        'bill_id',
        'user_id',
        'seller_id',
        'order_id',
        'order_sn',
        'order_status',
        'shipping_status',
        'pay_status',
        'order_amount',
        'return_amount',
        'return_shippingfee',
        'goods_amount',
        'tax',
        'shipping_fee',
        'insure_fee',
        'pay_fee',
        'pack_fee',
        'card_fee',
        'bonus',
        'integral_money',
        'coupons',
        'discount',
        'value_card',
        'money_paid',
        'surplus',
        'drp_money',
        'confirm_take_time',
        'chargeoff_status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBillId()
    {
        return $this->bill_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
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
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * @return mixed
     */
    public function getShippingStatus()
    {
        return $this->shipping_status;
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
    public function getOrderAmount()
    {
        return $this->order_amount;
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
    public function getGoodsAmount()
    {
        return $this->goods_amount;
    }

    /**
     * @return mixed
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @return mixed
     */
    public function getShippingFee()
    {
        return $this->shipping_fee;
    }

    /**
     * @return mixed
     */
    public function getInsureFee()
    {
        return $this->insure_fee;
    }

    /**
     * @return mixed
     */
    public function getPayFee()
    {
        return $this->pay_fee;
    }

    /**
     * @return mixed
     */
    public function getPackFee()
    {
        return $this->pack_fee;
    }

    /**
     * @return mixed
     */
    public function getCardFee()
    {
        return $this->card_fee;
    }

    /**
     * @return mixed
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * @return mixed
     */
    public function getIntegralMoney()
    {
        return $this->integral_money;
    }

    /**
     * @return mixed
     */
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * @return mixed
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @return mixed
     */
    public function getValueCard()
    {
        return $this->value_card;
    }

    /**
     * @return mixed
     */
    public function getMoneyPaid()
    {
        return $this->money_paid;
    }

    /**
     * @return mixed
     */
    public function getSurplus()
    {
        return $this->surplus;
    }

    /**
     * @return mixed
     */
    public function getDrpMoney()
    {
        return $this->drp_money;
    }

    /**
     * @return mixed
     */
    public function getConfirmTakeTime()
    {
        return $this->confirm_take_time;
    }

    /**
     * @return mixed
     */
    public function getChargeoffStatus()
    {
        return $this->chargeoff_status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBillId($value)
    {
        $this->bill_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
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
    public function setOrderStatus($value)
    {
        $this->order_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingStatus($value)
    {
        $this->shipping_status = $value;
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
    public function setOrderAmount($value)
    {
        $this->order_amount = $value;
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
    public function setGoodsAmount($value)
    {
        $this->goods_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTax($value)
    {
        $this->tax = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingFee($value)
    {
        $this->shipping_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInsureFee($value)
    {
        $this->insure_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayFee($value)
    {
        $this->pay_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPackFee($value)
    {
        $this->pack_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardFee($value)
    {
        $this->card_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBonus($value)
    {
        $this->bonus = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIntegralMoney($value)
    {
        $this->integral_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCoupons($value)
    {
        $this->coupons = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDiscount($value)
    {
        $this->discount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValueCard($value)
    {
        $this->value_card = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMoneyPaid($value)
    {
        $this->money_paid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSurplus($value)
    {
        $this->surplus = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDrpMoney($value)
    {
        $this->drp_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConfirmTakeTime($value)
    {
        $this->confirm_take_time = $value;
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
}
