<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderInfo
 */
class OrderInfo extends Model
{
    protected $table = 'order_info';

    protected $primaryKey = 'order_id';

    public $timestamps = false;

    protected $fillable = [
        'main_order_id',
        'order_sn',
        'user_id',
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
        'zipcode',
        'tel',
        'mobile',
        'email',
        'best_time',
        'sign_building',
        'postscript',
        'shipping_id',
        'shipping_name',
        'shipping_code',
        'shipping_type',
        'pay_id',
        'pay_name',
        'how_oos',
        'how_surplus',
        'pack_name',
        'card_name',
        'card_message',
        'inv_payee',
        'inv_content',
        'goods_amount',
        'cost_amount',
        'shipping_fee',
        'insure_fee',
        'pay_fee',
        'pack_fee',
        'card_fee',
        'money_paid',
        'surplus',
        'integral',
        'integral_money',
        'bonus',
        'order_amount',
        'return_amount',
        'from_ad',
        'referer',
        'add_time',
        'confirm_time',
        'pay_time',
        'shipping_time',
        'confirm_take_time',
        'auto_delivery_time',
        'pack_id',
        'card_id',
        'bonus_id',
        'invoice_no',
        'extension_code',
        'extension_id',
        'to_buyer',
        'pay_note',
        'agency_id',
        'inv_type',
        'tax',
        'is_separate',
        'parent_id',
        'discount',
        'discount_all',
        'is_delete',
        'is_settlement',
        'sign_time',
        'is_single',
        'point_id',
        'shipping_dateStr',
        'supplier_id',
        'froms',
        'coupons',
        'uc_id',
        'is_zc_order',
        'zc_goods_id',
        'is_frozen',
        'drp_is_separate',
        'team_id',
        'team_parent_id',
        'team_user_id',
        'team_price',
        'chargeoff_status',
        'invoice_type',
        'vat_id',
        'tax_id',
        'is_update_sale',
        'ru_id',
        'main_count',
        'rel_name',
        'id_num',
        'rate_fee',
        'main_pay'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getMainOrderId()
    {
        return $this->main_order_id;
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
    public function getUserId()
    {
        return $this->user_id;
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
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @return mixed
     */
    public function getTel()
    {
        return $this->tel;
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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getBestTime()
    {
        return $this->best_time;
    }

    /**
     * @return mixed
     */
    public function getSignBuilding()
    {
        return $this->sign_building;
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
    public function getShippingId()
    {
        return $this->shipping_id;
    }

    /**
     * @return mixed
     */
    public function getShippingName()
    {
        return $this->shipping_name;
    }

    /**
     * @return mixed
     */
    public function getShippingCode()
    {
        return $this->shipping_code;
    }

    /**
     * @return mixed
     */
    public function getShippingType()
    {
        return $this->shipping_type;
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
    public function getHowOos()
    {
        return $this->how_oos;
    }

    /**
     * @return mixed
     */
    public function getHowSurplus()
    {
        return $this->how_surplus;
    }

    /**
     * @return mixed
     */
    public function getPackName()
    {
        return $this->pack_name;
    }

    /**
     * @return mixed
     */
    public function getCardName()
    {
        return $this->card_name;
    }

    /**
     * @return mixed
     */
    public function getCardMessage()
    {
        return $this->card_message;
    }

    /**
     * @return mixed
     */
    public function getInvPayee()
    {
        return $this->inv_payee;
    }

    /**
     * @return mixed
     */
    public function getInvContent()
    {
        return $this->inv_content;
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
    public function getCostAmount()
    {
        return $this->cost_amount;
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
    public function getIntegral()
    {
        return $this->integral;
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
    public function getBonus()
    {
        return $this->bonus;
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
    public function getFromAd()
    {
        return $this->from_ad;
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
    public function getAutoDeliveryTime()
    {
        return $this->auto_delivery_time;
    }

    /**
     * @return mixed
     */
    public function getPackId()
    {
        return $this->pack_id;
    }

    /**
     * @return mixed
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * @return mixed
     */
    public function getBonusId()
    {
        return $this->bonus_id;
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
    public function getExtensionCode()
    {
        return $this->extension_code;
    }

    /**
     * @return mixed
     */
    public function getExtensionId()
    {
        return $this->extension_id;
    }

    /**
     * @return mixed
     */
    public function getToBuyer()
    {
        return $this->to_buyer;
    }

    /**
     * @return mixed
     */
    public function getPayNote()
    {
        return $this->pay_note;
    }

    /**
     * @return mixed
     */
    public function getAgencyId()
    {
        return $this->agency_id;
    }

    /**
     * @return mixed
     */
    public function getInvType()
    {
        return $this->inv_type;
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
    public function getIsSeparate()
    {
        return $this->is_separate;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
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
    public function getDiscountAll()
    {
        return $this->discount_all;
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
    public function getIsSettlement()
    {
        return $this->is_settlement;
    }

    /**
     * @return mixed
     */
    public function getSignTime()
    {
        return $this->sign_time;
    }

    /**
     * @return mixed
     */
    public function getIsSingle()
    {
        return $this->is_single;
    }

    /**
     * @return mixed
     */
    public function getPointId()
    {
        return $this->point_id;
    }

    /**
     * @return mixed
     */
    public function getShippingDateStr()
    {
        return $this->shipping_dateStr;
    }

    /**
     * @return mixed
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
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
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * @return mixed
     */
    public function getUcId()
    {
        return $this->uc_id;
    }

    /**
     * @return mixed
     */
    public function getIsZcOrder()
    {
        return $this->is_zc_order;
    }

    /**
     * @return mixed
     */
    public function getZcGoodsId()
    {
        return $this->zc_goods_id;
    }

    /**
     * @return mixed
     */
    public function getIsFrozen()
    {
        return $this->is_frozen;
    }

    /**
     * @return mixed
     */
    public function getDrpIsSeparate()
    {
        return $this->drp_is_separate;
    }

    /**
     * @return mixed
     */
    public function getTeamId()
    {
        return $this->team_id;
    }

    /**
     * @return mixed
     */
    public function getTeamParentId()
    {
        return $this->team_parent_id;
    }

    /**
     * @return mixed
     */
    public function getTeamUserId()
    {
        return $this->team_user_id;
    }

    /**
     * @return mixed
     */
    public function getTeamPrice()
    {
        return $this->team_price;
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
    public function getInvoiceType()
    {
        return $this->invoice_type;
    }

    /**
     * @return mixed
     */
    public function getVatId()
    {
        return $this->vat_id;
    }

    /**
     * @return mixed
     */
    public function getTaxId()
    {
        return $this->tax_id;
    }

    /**
     * @return mixed
     */
    public function getIsUpdateSale()
    {
        return $this->is_update_sale;
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
    public function getMainCount()
    {
        return $this->main_count;
    }

    /**
     * @return mixed
     */
    public function getRelName()
    {
        return $this->rel_name;
    }

    /**
     * @return mixed
     */
    public function getIdNum()
    {
        return $this->id_num;
    }

    /**
     * @return mixed
     */
    public function getRateFee()
    {
        return $this->rate_fee;
    }

    /**
     * @return mixed
     */
    public function getMainPay()
    {
        return $this->main_pay;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMainOrderId($value)
    {
        $this->main_order_id = $value;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
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
    public function setZipcode($value)
    {
        $this->zipcode = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTel($value)
    {
        $this->tel = $value;
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
    public function setEmail($value)
    {
        $this->email = $value;
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
    public function setSignBuilding($value)
    {
        $this->sign_building = $value;
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
    public function setShippingId($value)
    {
        $this->shipping_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingName($value)
    {
        $this->shipping_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingCode($value)
    {
        $this->shipping_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingType($value)
    {
        $this->shipping_type = $value;
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
    public function setHowOos($value)
    {
        $this->how_oos = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHowSurplus($value)
    {
        $this->how_surplus = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPackName($value)
    {
        $this->pack_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardName($value)
    {
        $this->card_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardMessage($value)
    {
        $this->card_message = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInvPayee($value)
    {
        $this->inv_payee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInvContent($value)
    {
        $this->inv_content = $value;
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
    public function setCostAmount($value)
    {
        $this->cost_amount = $value;
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
    public function setIntegral($value)
    {
        $this->integral = $value;
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
    public function setBonus($value)
    {
        $this->bonus = $value;
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
    public function setFromAd($value)
    {
        $this->from_ad = $value;
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
    public function setPackId($value)
    {
        $this->pack_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardId($value)
    {
        $this->card_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBonusId($value)
    {
        $this->bonus_id = $value;
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
    public function setExtensionCode($value)
    {
        $this->extension_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExtensionId($value)
    {
        $this->extension_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setToBuyer($value)
    {
        $this->to_buyer = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayNote($value)
    {
        $this->pay_note = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAgencyId($value)
    {
        $this->agency_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInvType($value)
    {
        $this->inv_type = $value;
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
    public function setIsSeparate($value)
    {
        $this->is_separate = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
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
    public function setDiscountAll($value)
    {
        $this->discount_all = $value;
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
    public function setIsSettlement($value)
    {
        $this->is_settlement = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSignTime($value)
    {
        $this->sign_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsSingle($value)
    {
        $this->is_single = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPointId($value)
    {
        $this->point_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingDateStr($value)
    {
        $this->shipping_dateStr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSupplierId($value)
    {
        $this->supplier_id = $value;
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
    public function setCoupons($value)
    {
        $this->coupons = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUcId($value)
    {
        $this->uc_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsZcOrder($value)
    {
        $this->is_zc_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setZcGoodsId($value)
    {
        $this->zc_goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsFrozen($value)
    {
        $this->is_frozen = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDrpIsSeparate($value)
    {
        $this->drp_is_separate = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTeamId($value)
    {
        $this->team_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTeamParentId($value)
    {
        $this->team_parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTeamUserId($value)
    {
        $this->team_user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTeamPrice($value)
    {
        $this->team_price = $value;
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
    public function setInvoiceType($value)
    {
        $this->invoice_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVatId($value)
    {
        $this->vat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTaxId($value)
    {
        $this->tax_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsUpdateSale($value)
    {
        $this->is_update_sale = $value;
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
    public function setMainCount($value)
    {
        $this->main_count = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRelName($value)
    {
        $this->rel_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIdNum($value)
    {
        $this->id_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRateFee($value)
    {
        $this->rate_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMainPay($value)
    {
        $this->main_pay = $value;
        return $this;
    }
}
