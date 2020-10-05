<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TradeSnapshot
 */
class TradeSnapshot extends Model
{
    protected $table = 'trade_snapshot';

    protected $primaryKey = 'trade_id';

    public $timestamps = false;

    protected $fillable = [
        'order_sn',
        'user_id',
        'goods_id',
        'goods_name',
        'goods_sn',
        'shop_price',
        'goods_number',
        'shipping_fee',
        'rz_shopName',
        'goods_weight',
        'add_time',
        'goods_attr',
        'goods_attr_id',
        'ru_id',
        'goods_desc',
        'goods_img',
        'snapshot_time'
    ];

    protected $guarded = [];


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
    public function getShopPrice()
    {
        return $this->shop_price;
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
    public function getShippingFee()
    {
        return $this->shipping_fee;
    }

    /**
     * @return mixed
     */
    public function getRzShopName()
    {
        return $this->rz_shopName;
    }

    /**
     * @return mixed
     */
    public function getGoodsWeight()
    {
        return $this->goods_weight;
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
    public function getGoodsAttr()
    {
        return $this->goods_attr;
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
    public function getGoodsDesc()
    {
        return $this->goods_desc;
    }

    /**
     * @return mixed
     */
    public function getGoodsImg()
    {
        return $this->goods_img;
    }

    /**
     * @return mixed
     */
    public function getSnapshotTime()
    {
        return $this->snapshot_time;
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
    public function setShopPrice($value)
    {
        $this->shop_price = $value;
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
    public function setShippingFee($value)
    {
        $this->shipping_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRzShopName($value)
    {
        $this->rz_shopName = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsWeight($value)
    {
        $this->goods_weight = $value;
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
    public function setGoodsAttr($value)
    {
        $this->goods_attr = $value;
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
    public function setGoodsDesc($value)
    {
        $this->goods_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsImg($value)
    {
        $this->goods_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSnapshotTime($value)
    {
        $this->snapshot_time = $value;
        return $this;
    }
}
