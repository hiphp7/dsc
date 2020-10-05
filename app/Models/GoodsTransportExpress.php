<?php

namespace App\Models;

use App\Entities\GoodsTransportExpress as Base;

/**
 * Class GoodsTransportExpress
 */
class GoodsTransportExpress extends Base
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
        return $this->belongsTo('App\Models\Shipping', 'shipping_id', 'shipping_id');
    }

    /**
     * 关联配送方式
     *
     * @access  public
     * @param tid
     * @return  array
     */
    public function getGoodsTransportExtend()
    {
        return $this->belongsTo('App\Models\GoodsTransportExtend', 'tid', 'tid');
    }
}
