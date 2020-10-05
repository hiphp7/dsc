<?php

namespace App\Models;

use App\Entities\SuppliersAccountLogDetail as Base;

/**
 * Class SuppliersAccountLogDetail
 */
class SuppliersAccountLogDetail extends Base
{
    /**
     * 关联订单资金日志详情
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getWholesaleOrderInfo()
    {
        return $this->hasOne('App\Models\WholesaleOrderInfo', 'order_id', 'order_id');
    }
}
