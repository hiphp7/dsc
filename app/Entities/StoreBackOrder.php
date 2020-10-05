<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StoreBackOrder
 */
class StoreBackOrder extends Model
{
    protected $table = 'store_back_order';

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'order_id'
    ];

    protected $guarded = [];


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
    public function getOrderId()
    {
        return $this->order_id;
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
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }
}
