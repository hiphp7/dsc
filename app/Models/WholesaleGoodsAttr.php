<?php

namespace App\Models;

use App\Entities\WholesaleGoodsAttr as Base;

/**
 * Class WholesaleGoodsAttr
 */
class WholesaleGoodsAttr extends Base
{
    /**
     * 关联批发属性
     *
     * @access  public
     * @param attr_id
     * @return  array
     */
    public function getGoodsAttribute()
    {
        return $this->hasOne('App\Models\Attribute', 'attr_id', 'attr_id');
    }
}
