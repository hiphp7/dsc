<?php

namespace App\Models;

use App\Entities\GoodsTransportExtend as Base;

/**
 * Class GoodsTransportExtend
 */
class GoodsTransportExtend extends Base
{
    /**
     * 关联配送方式
     *
     * @access  public
     * @param shipping_id
     * @return  array
     */
    public function getGoodsTransportExpress()
    {
        return $this->belongsTo('App\Models\GoodsTransportExpress', 'tid', 'tid');
    }
}
