<?php

namespace App\Models;

use App\Entities\CouponsUser as Base;

/**
 * Class CouponsUser
 */
class CouponsUser extends Base
{
    /**
     * 关联优惠券
     *
     * @access  public
     * @param cou_id
     * @return  array
     */
    public function getCoupons()
    {
        return $this->hasOne('App\Models\Coupons', 'cou_id', 'cou_id');
    }

    /**
     * 关联订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }
}
