<?php

namespace App\Models;

use App\Entities\OrderInfoMembershipCard as Base;

/**
 * Class OrderInfoMembershipCard
 */
class OrderInfoMembershipCard extends Base
{

    /**
     * 关联订单
     *
     * @param order_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

}
