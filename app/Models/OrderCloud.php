<?php

namespace App\Models;

use App\Entities\OrderCloud as Base;

/**
 * Class Ad
 */
class OrderCloud extends Base
{
    /**
     * 关联订单商品
     *
     * @access  public
     * @param rec_id
     * @return  array
     */
    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'rec_id', 'rec_id');
    }
}
