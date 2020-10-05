<?php

namespace App\Models;

use App\Entities\Shipping as Base;

/**
 * Class Shipping
 */
class Shipping extends Base
{
    /**
     * 关联运费模板
     *
     * @access  public
     * @param shipping_id
     * @return  array
     */
    public function getGoodsTransportTpl()
    {
        return $this->hasOne('App\Models\GoodsTransportTpl', 'shipping_id', 'shipping_id');
    }


    /**
     * 关联批发订单
     *
     * @access  public
     * @param pay_id
     * @return  array
     */
    public function getWholesaleOrderInfo()
    {
        return $this->hasOne('App\Models\WholesaleOrderInfo', 'shipping_id', 'shipping_id');
    }

    /**
     * 关联订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'shipping_id', 'shipping_id');
    }
}
