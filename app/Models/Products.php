<?php

namespace App\Models;

use App\Entities\Products as Base;

/**
 * Class Products
 */
class Products extends Base
{
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
