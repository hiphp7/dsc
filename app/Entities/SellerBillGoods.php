<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerBillGoods
 */
class SellerBillGoods extends Model
{
    protected $table = 'seller_bill_goods';

    public $timestamps = false;

    protected $fillable = [
        'rec_id',
        'order_id',
        'goods_id',
        'cat_id',
        'proportion',
        'goods_price',
        'dis_amount',
        'goods_number',
        'goods_attr',
        'drp_money',
        'commission_rate'
    ];

    protected $guarded = [];


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
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @return mixed
     */
    public function getProportion()
    {
        return $this->proportion;
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
    public function getDisAmount()
    {
        return $this->dis_amount;
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
    public function getGoodsAttr()
    {
        return $this->goods_attr;
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
    public function getCommissionRate()
    {
        return $this->commission_rate;
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
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatId($value)
    {
        $this->cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProportion($value)
    {
        $this->proportion = $value;
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
    public function setDisAmount($value)
    {
        $this->dis_amount = $value;
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
    public function setGoodsAttr($value)
    {
        $this->goods_attr = $value;
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
    public function setCommissionRate($value)
    {
        $this->commission_rate = $value;
        return $this;
    }
}
