<?php

namespace App\Models;

use App\Entities\SaleNotice as Base;

/**
 * Class SaleNotice
 */
class SaleNotice extends Base
{
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
