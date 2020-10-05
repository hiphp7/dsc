<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsChangeLog
 */
class GoodsChangeLog extends Model
{
    protected $table = 'goods_change_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'shop_price',
        'shipping_fee',
        'promote_price',
        'member_price',
        'volume_price',
        'give_integral',
        'rank_integral',
        'goods_weight',
        'is_on_sale',
        'user_id',
        'handle_time',
        'old_record'
    ];

    protected $guarded = [];


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
    public function getShopPrice()
    {
        return $this->shop_price;
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
    public function getPromotePrice()
    {
        return $this->promote_price;
    }

    /**
     * @return mixed
     */
    public function getMemberPrice()
    {
        return $this->member_price;
    }

    /**
     * @return mixed
     */
    public function getVolumePrice()
    {
        return $this->volume_price;
    }

    /**
     * @return mixed
     */
    public function getGiveIntegral()
    {
        return $this->give_integral;
    }

    /**
     * @return mixed
     */
    public function getRankIntegral()
    {
        return $this->rank_integral;
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
    public function getIsOnSale()
    {
        return $this->is_on_sale;
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
    public function getHandleTime()
    {
        return $this->handle_time;
    }

    /**
     * @return mixed
     */
    public function getOldRecord()
    {
        return $this->old_record;
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
    public function setShopPrice($value)
    {
        $this->shop_price = $value;
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
    public function setPromotePrice($value)
    {
        $this->promote_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMemberPrice($value)
    {
        $this->member_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVolumePrice($value)
    {
        $this->volume_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiveIntegral($value)
    {
        $this->give_integral = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRankIntegral($value)
    {
        $this->rank_integral = $value;
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
    public function setIsOnSale($value)
    {
        $this->is_on_sale = $value;
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
    public function setHandleTime($value)
    {
        $this->handle_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOldRecord($value)
    {
        $this->old_record = $value;
        return $this;
    }
}
