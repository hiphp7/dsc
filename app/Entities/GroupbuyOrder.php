<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GroupbuyOrder
 */
class GroupbuyOrder extends Model
{
    protected $table = 'groupbuy_order';

    protected $primaryKey = 'order_id';

    public $timestamps = false;

    protected $fillable = [
        'order_sn',
        'user_id',
        'ru_id',
        'order_status',
        'shipping_status',
        'pay_status',
        'consignee',
        'country',
        'province',
        'city',
        'district',
        'street',
        'address',
        'mobile',
        'best_time',
        'postscript',
        'pay_id',
        'pay_name',
        'goods_amount',
        'goods_num',
        'money_paid',
        'surplus',
        'order_amount',
        'referer',
        'add_time',
        'confirm_time',
        'pay_time',
        'shipping_time',
        'confirm_take_time',
        'auto_delivery_time',
        'invoice_no',
        'is_delete',
        'froms',
        'coupon_id',
        'coupon_money',
        'virtual_card_id',
        'virtual_card_money',
        'chargeoff_status',
        'return_amount',
        'leader_id',
        'act_id',
        'leader_commission',
        'return_order',
        'pick_up_barcode',
        'pick_up_time',
        'pick_up_status',
        'receive_status',
        'receive_time'
    ];

    protected $guarded = [];


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
    public function getRuId()
    {
        return $this->ru_id;
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
    public function getConsignee()
    {
        return $this->consignee;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return mixed
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @return mixed
     */
    public function getBestBime()
    {
        return $this->best_time;
    }

    /**
     * @return mixed
     */
    public function getPostscript()
    {
        return $this->postscript;
    }

    /**
     * @return mixed
     */
    public function getPayId()
    {
        return $this->pay_id;
    }

    /**
     * @return mixed
     */
    public function getPayName()
    {
        return $this->pay_name;
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
    public function getGoodsNum()
    {
        return $this->goods_num;
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
    public function getOrderAmount()
    {
        return $this->order_amount;
    }

    /**
     * @return mixed
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getConfirmTime()
    {
        return $this->confirm_time;
    }

    /**
     * @return mixed
     */
    public function getPayTime()
    {
        return $this->pay_time;
    }

    /**
     * @return mixed
     */
    public function getShippingTime()
    {
        return $this->shipping_time;
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
    public function getDutoDeliveryTime()
    {
        return $this->auto_delivery_time;
    }

    /**
     * @return mixed
     */
    public function getInvoiceNo()
    {
        return $this->invoice_no;
    }

    /**
     * @return mixed
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * @return mixed
     */
    public function getFroms()
    {
        return $this->froms;
    }

    /**
     * @return mixed
     */
    public function getCouponId()
    {
        return $this->coupon_id;
    }

    /**
     * @return mixed
     */
    public function getCouponMoney()
    {
        return $this->coupon_money;
    }

    /**
     * @return mixed
     */
    public function getVirtualCardId()
    {
        return $this->virtual_card_id;
    }

    /**
     * @return mixed
     */
    public function getVirtualCardMoney()
    {
        return $this->virtual_card_money;
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
    public function getReturnAmount()
    {
        return $this->return_amount;
    }

    /**
     * @return mixed
     */
    public function getLeaderId()
    {
        return $this->leader_id;
    }

    /**
     * @return mixed
     */
    public function getActId()
    {
        return $this->act_id;
    }

    /**
     * @return mixed
     */
    public function getLeaderCommission()
    {
        return $this->leader_commission;
    }

    /**
     * @return mixed
     */
    public function getReturnOrder()
    {
        return $this->return_order;
    }

    /**
     * @return mixed
     */
    public function getPickUpBarcode()
    {
        return $this->pick_up_barcode;
    }

    /**
     * @return mixed
     */
    public function getPickUpTime()
    {
        return $this->pick_up_time;
    }

    /**
     * @return mixed
     */
    public function getPickUpStatus()
    {
        return $this->pick_up_status;
    }

    /**
     * @return mixed
     */
    public function getReceiveStatus()
    {
        return $this->receive_status;
    }

    /**
     * @return mixed
     */
    public function getreceiveTime()
    {
        return $this->receive_time;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
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
    public function setConsignee($value)
    {
        $this->consignee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCountry($value)
    {
        $this->country = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProvince($value)
    {
        $this->province = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCity($value)
    {
        $this->city = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDistrict($value)
    {
        $this->district = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStreet($value)
    {
        $this->street = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddress($value)
    {
        $this->address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMobile($value)
    {
        $this->mobile = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBestTime($value)
    {
        $this->best_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPostscript($value)
    {
        $this->postscript = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayId($value)
    {
        $this->pay_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayName($value)
    {
        $this->pay_name = $value;
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
    public function setGoodsNum($value)
    {
        $this->goods_num = $value;
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
    public function setOrderAmount($value)
    {
        $this->order_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReferer($value)
    {
        $this->referer = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setConfirmTime($value)
    {
        $this->confirm_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayTime($value)
    {
        $this->pay_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingTime($value)
    {
        $this->shipping_time = $value;
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
    public function setAutoDeliveryTime($value)
    {
        $this->auto_delivery_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInvoiceNo($value)
    {
        $this->invoice_no = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDelete($value)
    {
        $this->is_delete = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFroms($value)
    {
        $this->froms = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouponId($value)
    {
        $this->coupon_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouponMoney($value)
    {
        $this->coupon_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVirtualCardId($value)
    {
        $this->virtual_card_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVirtualCardMoney($value)
    {
        $this->virtual_card_money = $value;
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
    public function setReturnAmount($value)
    {
        $this->return_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLeaderId($value)
    {
        $this->leader_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActId($value)
    {
        $this->act_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLeaderCommission($value)
    {
        $this->leader_commission = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnOrder($value)
    {
        $this->return_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPickUpBarcode($value)
    {
        $this->pick_up_barcode = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPickUpTime($value)
    {
        $this->pick_up_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPickUpStatus($value)
    {
        $this->pick_up_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReceiveStatus($value)
    {
        $this->receive_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReceiveTime($value)
    {
        $this->receive_time = $value;
        return $this;
    }


}
