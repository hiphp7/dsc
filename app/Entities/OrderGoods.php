<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderGoods
 */
class OrderGoods extends Model
{
    protected $table = 'order_goods';

    protected $primaryKey = 'rec_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'user_id',
        'cart_recid',
        'goods_id',
        'goods_name',
        'goods_sn',
        'product_id',
        'goods_number',
        'market_price',
        'goods_price',
        'goods_attr',
        'send_number',
        'is_real',
        'extension_code',
        'parent_id',
        'is_gift',
        'model_attr',
        'goods_attr_id',
        'ru_id',
        'shopping_fee',
        'warehouse_id',
        'area_id',
        'area_city',
        'is_single',
        'freight',
        'tid',
        'shipping_fee',
        'drp_money',
        'is_distribution',
        'commission_rate',
        'stages_qishu',
        'product_sn',
        'is_reality',
        'is_return',
        'is_fast',
        'rate_price',
        'goods_bonus',
        'buy_drp_show'
    ];

    protected $guarded = [];


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
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getCartRecid()
    {
        return $this->cart_recid;
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
    public function getGoodsName()
    {
        return $this->goods_name;
    }

    /**
     * @return mixed
     */
    public function getGoodsSn()
    {
        return $this->goods_sn;
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsNumber()
    {
        return $this->goods_number;
    }

    /**
     * @return mixed
     */
    public function getMarketPrice()
    {
        return $this->market_price;
    }

    /**
     * @return mixed
     */
    public function getGoodsPrice()
    {
        return $this->goods_price;
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
    public function getSendNumber()
    {
        return $this->send_number;
    }

    /**
     * @return mixed
     */
    public function getIsReal()
    {
        return $this->is_real;
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
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @return mixed
     */
    public function getIsGift()
    {
        return $this->is_gift;
    }

    /**
     * @return mixed
     */
    public function getModelAttr()
    {
        return $this->model_attr;
    }

    /**
     * @return mixed
     */
    public function getGoodsAttrId()
    {
        return $this->goods_attr_id;
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
    public function getShoppingFee()
    {
        return $this->shopping_fee;
    }

    /**
     * @return mixed
     */
    public function getWarehouseId()
    {
        return $this->warehouse_id;
    }

    /**
     * @return mixed
     */
    public function getAreaId()
    {
        return $this->area_id;
    }

    /**
     * @return mixed
     */
    public function getAreaCity()
    {
        return $this->area_city;
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
    public function getFreight()
    {
        return $this->freight;
    }

    /**
     * @return mixed
     */
    public function getTid()
    {
        return $this->tid;
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
    public function getDrpMoney()
    {
        return $this->drp_money;
    }

    /**
     * @return mixed
     */
    public function getIsDistribution()
    {
        return $this->is_distribution;
    }

    /**
     * @return mixed
     */
    public function getCommissionRate()
    {
        return $this->commission_rate;
    }

    /**
     * @return mixed
     */
    public function getStagesQishu()
    {
        return $this->stages_qishu;
    }

    /**
     * @return mixed
     */
    public function getProductSn()
    {
        return $this->product_sn;
    }

    /**
     * @return mixed
     */
    public function getIsReality()
    {
        return $this->is_reality;
    }

    /**
     * @return mixed
     */
    public function getIsReturn()
    {
        return $this->is_return;
    }

    /**
     * @return mixed
     */
    public function getIsFast()
    {
        return $this->is_fast;
    }

    /**
     * @return mixed
     */
    public function getRatePrice()
    {
        return $this->rate_price;
    }

    /**
     * @return mixed
     */
    public function getGoodsBonus()
    {
        return $this->goods_bonus;
    }

    /**
     * @return mixed
     */
    public function getBuyDrpShow()
    {
        return $this->buy_drp_show;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCartRecid($value)
    {
        $this->cart_recid = $value;
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
    public function setGoodsName($value)
    {
        $this->goods_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsSn($value)
    {
        $this->goods_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductId($value)
    {
        $this->product_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsNumber($value)
    {
        $this->goods_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMarketPrice($value)
    {
        $this->market_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsPrice($value)
    {
        $this->goods_price = $value;
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
    public function setSendNumber($value)
    {
        $this->send_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsReal($value)
    {
        $this->is_real = $value;
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
    public function setParentId($value)
    {
        $this->parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsGift($value)
    {
        $this->is_gift = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setModelAttr($value)
    {
        $this->model_attr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsAttrId($value)
    {
        $this->goods_attr_id = $value;
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
    public function setShoppingFee($value)
    {
        $this->shopping_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWarehouseId($value)
    {
        $this->warehouse_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAreaId($value)
    {
        $this->area_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAreaCity($value)
    {
        $this->area_city = $value;
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
    public function setFreight($value)
    {
        $this->freight = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTid($value)
    {
        $this->tid = $value;
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
    public function setDrpMoney($value)
    {
        $this->drp_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDistribution($value)
    {
        $this->is_distribution = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommissionRate($value)
    {
        $this->commission_rate = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStagesQishu($value)
    {
        $this->stages_qishu = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductSn($value)
    {
        $this->product_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsReality($value)
    {
        $this->is_reality = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsReturn($value)
    {
        $this->is_return = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsFast($value)
    {
        $this->is_fast = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRatePrice($value)
    {
        $this->rate_price = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setBuyDrpShow($value)
    {
        $this->buy_drp_show = $value;
        return $this;
    }
}
