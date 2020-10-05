<?php

namespace App\Models;

use App\Entities\GoodsGallery as Base;

/**
 * Class GoodsGallery
 */
class GoodsGallery extends Base
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
