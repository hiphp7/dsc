<?php

namespace App\Models;

use App\Entities\SellerBillOrder as Base;

/**
 * Class SellerBillOrder
 */
class SellerBillOrder extends Base
{
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    public function getSellerCommissionBill()
    {
        return $this->hasOne('App\Models\SellerCommissionBill', 'id', 'bill_id');
    }

    public function getSellerBillGoods()
    {
        return $this->hasOne('App\Models\SellerBillGoods', 'order_id', 'order_id');
    }
}
