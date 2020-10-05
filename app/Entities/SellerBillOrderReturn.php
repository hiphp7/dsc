<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerBillOrderReturn
 */
class SellerBillOrderReturn extends Model
{
    protected $table = 'seller_bill_order_return';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'ret_id'
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
    public function getRetId()
    {
        return $this->ret_id;
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
    public function setRetId($value)
    {
        $this->ret_id = $value;
        return $this;
    }
}
