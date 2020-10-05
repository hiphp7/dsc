<?php

namespace App\Models;

use App\Entities\BargainGoods as Base;

/**
 * Class BargainGoods
 */
class BargainGoods extends Base
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

    /**
     * 关联砍价属性价格
     * @param id
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function getBargainTargetPrice()
    {
        return $this->hasMany('App\Models\ActivityGoodsAttr', 'bargain_id', 'id');
    }
}
