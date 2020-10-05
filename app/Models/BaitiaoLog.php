<?php

namespace App\Models;

use App\Entities\BaitiaoLog as Base;

/**
 * Class BaitiaoLog
 */
class BaitiaoLog extends Base
{
    /**
     * 关联白条订单
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
