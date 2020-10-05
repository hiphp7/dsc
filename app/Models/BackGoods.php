<?php

namespace App\Models;

use App\Entities\BackGoods as Base;

/**
 * Class BackGoods
 */
class BackGoods extends Base
{
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
