<?php

namespace App\Models;

use App\Entities\TeamGoods as Base;

/**
 * Class TeamGoods
 */
class TeamGoods extends Base
{
    /**
     * 关联商品
     * @param goods_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
