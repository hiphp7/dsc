<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderReturn
 */
class OrderReturn extends Model
{
    protected $table = 'order_return';

    protected $primaryKey = 'ret_id';

    public $timestamps = false;

    protected $fillable = [
        'return_sn',
        'goods_id',
        'user_id',
        'rec_id',
        'order_id',
        'order_sn',
        'credentials',
        'maintain',
        'back',
        'goods_attr',
        'exchange',
        'return_type',
        'attr_val',
        'cause_id',
        'apply_time',
        'return_time',
        'should_return',
        'actual_return',
        'return_shipping_fee',
        'return_brief',
        'remark',
        'country',
        'province',
        'city',
        'district',
        'street',
        'addressee',
        'phone',
        'address',
        'zipcode',
        'is_check',
        'return_status',
        'refound_status',
        'back_shipping_name',
        'back_other_shipping',
        'back_invoice_no',
        'out_shipping_name',
        'out_invoice_no',
        'agree_apply',
        'chargeoff_status',
        'activation_number',
        'refund_type',
        'return_trade_data',
        'return_rate_price',
        'goods_bonus'
    ];

    protected $guarded = [];


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
    public function getGoodsId()
    {
        return $this->goods_id;
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
    public function getRecId()
    {
        return $this->rec_id;
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
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @return mixed
     */
    public function getMaintain()
    {
        return $this->maintain;
    }

    /**
     * @return mixed
     */
    public function getBack()
    {
        return $this->back;
    }

    /**
     * @return mixed
     */
    public function getGoodsAttr()
    {
        return $this->goods_attr;
    }

    /**
     * @return mixed
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @return mixed
     */
    public function getReturnType()
    {
        return $this->return_type;
    }

    /**
     * @return mixed
     */
    public function getAttrVal()
    {
        return $this->attr_val;
    }

    /**
     * @return mixed
     */
    public function getCauseId()
    {
        return $this->cause_id;
    }

    /**
     * @return mixed
     */
    public function getApplyTime()
    {
        return $this->apply_time;
    }

    /**
     * @return mixed
     */
    public function getReturnTime()
    {
        return $this->return_time;
    }

    /**
     * @return mixed
     */
    public function getShouldReturn()
    {
        return $this->should_return;
    }

    /**
     * @return mixed
     */
    public function getActualReturn()
    {
        return $this->actual_return;
    }

    /**
     * @return mixed
     */
    public function getReturnShippingFee()
    {
        return $this->return_shipping_fee;
    }

    /**
     * @return mixed
     */
    public function getReturnBrief()
    {
        return $this->return_brief;
    }

    /**
     * @return mixed
     */
    public function getRemark()
    {
        return $this->remark;
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
    public function getAddressee()
    {
        return $this->addressee;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
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
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @return mixed
     */
    public function getIsCheck()
    {
        return $this->is_check;
    }

    /**
     * @return mixed
     */
    public function getReturnStatus()
    {
        return $this->return_status;
    }

    /**
     * @return mixed
     */
    public function getRefoundStatus()
    {
        return $this->refound_status;
    }

    /**
     * @return mixed
     */
    public function getBackShippingName()
    {
        return $this->back_shipping_name;
    }

    /**
     * @return mixed
     */
    public function getBackOtherShipping()
    {
        return $this->back_other_shipping;
    }

    /**
     * @return mixed
     */
    public function getBackInvoiceNo()
    {
        return $this->back_invoice_no;
    }

    /**
     * @return mixed
     */
    public function getOutShippingName()
    {
        return $this->out_shipping_name;
    }

    /**
     * @return mixed
     */
    public function getOutInvoiceNo()
    {
        return $this->out_invoice_no;
    }

    /**
     * @return mixed
     */
    public function getAgreeApply()
    {
        return $this->agree_apply;
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
    public function getActivationNumber()
    {
        return $this->activation_number;
    }

    /**
     * @return mixed
     */
    public function getRefundType()
    {
        return $this->refund_type;
    }

    /**
     * @return mixed
     */
    public function getReturnTradeData()
    {
        return $this->return_trade_data;
    }

    /**
     * @return mixed
     */
    public function getReturnRatePrice()
    {
        return $this->return_rate_price;
    }

    /**
     * @return mixed
     */
    public function getGoodsBonus()
    {
        return $this->goods_bonus;
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
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
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
    public function setRecId($value)
    {
        $this->rec_id = $value;
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
    public function setCredentials($value)
    {
        $this->credentials = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMaintain($value)
    {
        $this->maintain = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBack($value)
    {
        $this->back = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsAttr($value)
    {
        $this->goods_attr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExchange($value)
    {
        $this->exchange = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnType($value)
    {
        $this->return_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrVal($value)
    {
        $this->attr_val = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCauseId($value)
    {
        $this->cause_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setApplyTime($value)
    {
        $this->apply_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnTime($value)
    {
        $this->return_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShouldReturn($value)
    {
        $this->should_return = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActualReturn($value)
    {
        $this->actual_return = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnShippingFee($value)
    {
        $this->return_shipping_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnBrief($value)
    {
        $this->return_brief = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRemark($value)
    {
        $this->remark = $value;
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
    public function setAddressee($value)
    {
        $this->addressee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPhone($value)
    {
        $this->phone = $value;
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
    public function setZipcode($value)
    {
        $this->zipcode = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsCheck($value)
    {
        $this->is_check = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnStatus($value)
    {
        $this->return_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRefoundStatus($value)
    {
        $this->refound_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBackShippingName($value)
    {
        $this->back_shipping_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBackOtherShipping($value)
    {
        $this->back_other_shipping = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBackInvoiceNo($value)
    {
        $this->back_invoice_no = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOutShippingName($value)
    {
        $this->out_shipping_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOutInvoiceNo($value)
    {
        $this->out_invoice_no = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAgreeApply($value)
    {
        $this->agree_apply = $value;
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
    public function setActivationNumber($value)
    {
        $this->activation_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRefundType($value)
    {
        $this->refund_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnTradeData($value)
    {
        $this->return_trade_data = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnRatePrice($value)
    {
        $this->return_rate_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsBonus($value)
    {
        $this->goods_bonus = $value;
        return $this;
    }
}
