<?php

namespace App\Models;

use App\Entities\WholesaleCart as Base;

/**
 * Class WholesaleCart
 */
class WholesaleCart extends Base
{
    /**
     * 关联商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联批发
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWholesale()
    {
        return $this->hasOne('App\Models\Wholesale', 'goods_id', 'goods_id');
    }

    /**
     * 关联session表
     *
     * @access  public
     * @param sesskey
     * @return  array
     */
    public function getSessions()
    {
        return $this->hasOne('App\Models\Sessions', 'sesskey', 'session_id');
    }
}
