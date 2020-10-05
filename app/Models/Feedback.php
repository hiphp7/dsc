<?php

namespace App\Models;

use App\Entities\Feedback as Base;

/**
 * Class Feedback
 */
class Feedback extends Base
{
    /**
     * 关联订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联自身
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getFeedback()
    {
        return $this->hasOne('App\Models\Feedback', 'parent_id', 'msg_id');
    }
}
