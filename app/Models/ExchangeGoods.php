<?php

namespace App\Models;

use App\Entities\ExchangeGoods as Base;

/**
 * Class ExchangeGoods
 */
class ExchangeGoods extends Base
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

    /**
     * 关联店铺
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }
}
