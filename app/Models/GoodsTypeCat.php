<?php

namespace App\Models;

use App\Entities\GoodsTypeCat as Base;

/**
 * Class GoodsTypeCat
 */
class GoodsTypeCat extends Base
{
    /**
     * 关联获取父级
     *
     * @access  public
     * @return array
     */
    public function getGoodsTypeCatParent()
    {
        return $this->hasOne('App\Models\GoodsTypeCat', 'cat_id', 'parent_id');
    }
}
