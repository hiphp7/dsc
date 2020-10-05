<?php

namespace App\Models;

use App\Entities\Coupons as Base;

/**
 * Class Coupons
 */
class Coupons extends Base
{
    /**
     * 关联会员优惠券
     *
     * @access  public
     * @param cou_id
     * @return  array
     */
    public function getCouponsUser()
    {
        return $this->hasOne('App\Models\CouponsUser', 'cou_id', 'cou_id');
    }

    /**
     * 关联会员优惠券列表
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getCouponsUserList()
    {
        return $this->hasMany('App\Models\CouponsUser', 'cou_id', 'cou_id');
    }
}
