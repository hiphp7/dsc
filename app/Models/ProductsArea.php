<?php

namespace App\Models;

use App\Entities\ProductsArea as Base;

/**
 * Class ProductsArea
 */
class ProductsArea extends Base
{
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
