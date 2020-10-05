<?php

namespace App\Models;

use App\Entities\OrderInvoice as Base;

/**
 * Class OrderInvoice
 */
class OrderInvoice extends Base
{
    /**
     * 关联订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'inv_payee', 'inv_payee');
    }
}
