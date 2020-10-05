<?php

namespace App\Models;

use App\Entities\DeliveryGoods as Base;

/**
 * Class DeliveryGoods
 */
class DeliveryGoods extends Base
{
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    public function getDeliveryOrder()
    {
        return $this->hasOne('App\Models\DeliveryOrder', 'delivery_id', 'delivery_id');
    }
}
