<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WarehouseGoods
 */
class WarehouseGoods extends Model
{
    protected $table = 'warehouse_goods';

    protected $primaryKey = 'w_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'goods_id',
        'region_id',
        'region_sn',
        'region_number',
        'warehouse_price',
        'warehouse_promote_price',
        'add_time',
        'last_update',
        'give_integral',
        'rank_integral',
        'pay_integral'
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
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getRegionId()
    {
        return $this->region_id;
    }

    /**
     * @return mixed
     */
    public function getRegionSn()
    {
        return $this->region_sn;
    }

    /**
     * @return mixed
     */
    public function getRegionNumber()
    {
        return $this->region_number;
    }

    /**
     * @return mixed
     */
    public function getWarehousePrice()
    {
        return $this->warehouse_price;
    }

    /**
     * @return mixed
     */
    public function getWarehousePromotePrice()
    {
        return $this->warehouse_promote_price;
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
    public function getLastUpdate()
    {
        return $this->last_update;
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
    public function getPayIntegral()
    {
        return $this->pay_integral;
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
    public function setRegionId($value)
    {
        $this->region_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionSn($value)
    {
        $this->region_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionNumber($value)
    {
        $this->region_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWarehousePrice($value)
    {
        $this->warehouse_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWarehousePromotePrice($value)
    {
        $this->warehouse_promote_price = $value;
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
    public function setLastUpdate($value)
    {
        $this->last_update = $value;
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
    public function setPayIntegral($value)
    {
        $this->pay_integral = $value;
        return $this;
    }
}
