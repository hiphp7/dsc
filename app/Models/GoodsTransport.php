<?php

namespace App\Models;

use App\Entities\GoodsTransport as Base;

/**
 * Class GoodsTransport
 */
class GoodsTransport extends Base
{
    public function getShippingList()
    {
        return $this->hasMany('App\Models\Shipping', 'shipping_id', 'shipping_id');
    }

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
     * 关联运费模板
     *
     * @access  public
     * @param shipping_id
     * @return  array
     */
    public function getGoodsTransportTpl()
    {
        return $this->belongsTo('App\Models\GoodsTransportTpl', 'tid', 'tid');
    }
}
