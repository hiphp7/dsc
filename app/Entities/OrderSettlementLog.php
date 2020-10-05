<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderSettlementLog
 */
class OrderSettlementLog extends Model
{
    protected $table = 'order_settlement_log';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'ru_id',
        'is_settlement',
        'actual_amount',
        'type',
        'add_time',
        'update_time'
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
    public function getRuId()
    {
        return $this->ru_id;
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
    public function getActualAmount()
    {
        return $this->actual_amount;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
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
    public function getUpdateTime()
    {
        return $this->update_time;
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
    public function setRuId($value)
    {
        $this->ru_id = $value;
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
    public function setActualAmount($value)
    {
        $this->actual_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
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
    public function setUpdateTime($value)
    {
        $this->update_time = $value;
        return $this;
    }
}
