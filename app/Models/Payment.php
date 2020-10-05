<?php

namespace App\Models;

use App\Entities\Payment as Base;

/**
 * Class Payment
 */
class Payment extends Base
{
    /**
     * 关联订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'pay_id', 'pay_id');
    }
}
