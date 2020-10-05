<?php

namespace App\Models;

use App\Entities\BookingGoods as Base;

/**
 * Class BookingGoods
 */
class BookingGoods extends Base
{
    /**
     * 关联商品
     *
     * @access  public
     * @param main_order_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }
}
