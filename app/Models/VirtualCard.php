<?php

namespace App\Models;

use App\Entities\VirtualCard as Base;

/**
 * Class VirtualCard
 */
class VirtualCard extends Base
{
    /**
     * 关联订单
     *
     * @access  public
     * @return array
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_sn', 'order_sn');
    }
}
