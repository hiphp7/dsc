<?php

namespace App\Models;

use App\Entities\DrpAffiliateLog as Base;

/**
 * Class DrpAffiliateLog
 */
class DrpAffiliateLog extends Base
{
    /**
     * 关联订单
     * @param order_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联会员
     * @param user_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }
}
