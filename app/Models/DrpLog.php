<?php

namespace App\Models;

use App\Entities\DrpLog as Base;

/**
 * Class DrpLog
 */
class DrpLog extends Base
{
    /**
     * 关联订单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联会员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUser()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联付费购买记录订单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getDrpAccountLog()
    {
        return $this->hasOne('App\Custom\Distribute\Models\DrpAccountLog', 'id', 'drp_account_log_id');
    }

}
