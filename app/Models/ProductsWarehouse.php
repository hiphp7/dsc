<?php

namespace App\Models;

use App\Entities\ProductsWarehouse as Base;

/**
 * Class ProductsWarehouse
 */
class ProductsWarehouse extends Base
{
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
