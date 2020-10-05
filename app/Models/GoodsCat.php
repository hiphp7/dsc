<?php

namespace App\Models;

use App\Entities\GoodsCat as Base;

/**
 * Class GoodsCat
 */
class GoodsCat extends Base
{
    /**
     * 关联商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
