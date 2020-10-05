<?php

namespace App\Models;

use App\Entities\GoodsTransportTpl as Base;

/**
 * Class GoodsTransportTpl
 */
class GoodsTransportTpl extends Base
{
    /**
     * 关联配送方式
     *
     * @access  public
     * @param shipping_id
     * @return  array
     */
    public function getShipping()
    {
        return $this->hasOne('App\Models\Shipping', 'shipping_id', 'shipping_id');
    }
}
