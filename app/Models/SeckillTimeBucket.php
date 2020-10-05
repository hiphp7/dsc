<?php

namespace App\Models;

use App\Entities\SeckillTimeBucket as Base;

/**
 * Class SeckillTimeBucket
 */
class SeckillTimeBucket extends Base
{
    /**
     * 关联商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getSeckillGoods()
    {
        return $this->hasOne('App\Models\SeckillGoods', 'tb_id', 'id');
    }
}
