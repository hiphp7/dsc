<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StoreOrder
 */
class StoreOrder extends Model
{
    protected $table = 'store_order';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'store_id',
        'ru_id',
        'is_grab_order',
        'grab_store_list',
        'pick_code',
        'take_time'
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
    public function getStoreId()
    {
        return $this->store_id;
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
    public function getIsGrabOrder()
    {
        return $this->is_grab_order;
    }

    /**
     * @return mixed
     */
    public function getGrabStoreList()
    {
        return $this->grab_store_list;
    }

    /**
     * @return mixed
     */
    public function getPickCode()
    {
        return $this->pick_code;
    }

    /**
     * @return mixed
     */
    public function getTakeTime()
    {
        return $this->take_time;
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
    public function setStoreId($value)
    {
        $this->store_id = $value;
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
    public function setIsGrabOrder($value)
    {
        $this->is_grab_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGrabStoreList($value)
    {
        $this->grab_store_list = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPickCode($value)
    {
        $this->pick_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTakeTime($value)
    {
        $this->take_time = $value;
        return $this;
    }
}
