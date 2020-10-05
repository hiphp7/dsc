<?php

namespace App\Models;

use App\Entities\WholesaleProducts as Base;

/**
 * Class WholesaleProducts
 */
class WholesaleProducts extends Base
{
    /**
     * 批发商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWholesale()
    {
        return $this->hasOne('App\Models\Wholesale', 'goods_id', 'goods_id');
    }
}
