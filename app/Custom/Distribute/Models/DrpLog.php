<?php

namespace App\Custom\Distribute\Models;

use App\Models\DrpLog as Base;


class DrpLog extends Base
{
    /**
     * 关联订单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联订单商品
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'order_id', 'order_id');
    }

    /**
     * 关联用户
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function users()
    {
        return $this->hasMany('App\Custom\Distribute\Models\Users', 'user_id', 'user_id');
    }
}
