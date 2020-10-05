<?php

namespace App\Models;

use App\Entities\WholesaleOrderGoods as Base;

/**
 * Class WholesaleOrderGoods
 */
class WholesaleOrderGoods extends Base
{
    /**
     * 关联订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getWholesaleOrderInfo()
    {
        return $this->hasOne('App\Models\WholesaleOrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联批发商品
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getWholesale()
    {
        return $this->hasOne('App\Models\Wholesale', 'goods_id', 'goods_id');
    }

    /**
     * 关联批发商品货品
     *
     * @access  public
     * @param product_id
     * @return  array
     */
    public function getWholesaleProducts()
    {
        return $this->hasOne('App\Models\WholesaleProducts', 'product_id', 'product_id');
    }
}
