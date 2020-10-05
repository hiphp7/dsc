<?php

namespace App\Models;

use App\Entities\GoodsInventoryLogs as Base;

/**
 * Class GoodsInventoryLogs
 */
class GoodsInventoryLogs extends Base
{
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    public function getOrderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }
}
