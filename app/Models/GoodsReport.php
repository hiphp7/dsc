<?php

namespace App\Models;

use App\Entities\GoodsReport as Base;

/**
 * Class GoodsReport
 */
class GoodsReport extends Base
{
    /**
     * 关联商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }
}
