<?php

namespace App\Models;

use App\Entities\FavourableActivity as Base;

/**
 * Class FavourableActivity
 */
class FavourableActivity extends Base
{
    /**
     * 关联商品查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联商铺
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }
}
