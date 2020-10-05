<?php

namespace App\Models;

use App\Entities\ShippingArea as Base;

/**
 * Class ShippingArea
 */
class ShippingArea extends Base
{
    public function shipping()
    {
        return $this->hasOne(Shipping::class, 'shipping_id', "shipping_id");
    }

    /**
     * 关联配送方式
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getShipping()
    {
        return $this->hasOne('App\Models\Shipping', 'shipping_id', 'shipping_id');
    }
}
