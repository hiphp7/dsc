<?php

namespace App\Models;

use App\Entities\ValueCardRecord as Base;

/**
 * Class ValueCardRecord
 */
class ValueCardRecord extends Base
{
    /**
     * 关联储值卡
     *
     * @access  public
     * @param id
     * @return  array
     */
    public function getValueCardType()
    {
        return $this->hasOne('App\Models\ValueCardType', 'id', 'tid');
    }

    /**
     * 关联订单
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
