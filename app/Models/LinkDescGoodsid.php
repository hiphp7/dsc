<?php

namespace App\Models;

use App\Entities\LinkDescGoodsid as Base;

/**
 * Class LinkDescGoodsid
 */
class LinkDescGoodsid extends Base
{
    /**
     * 关联店铺等级信息
     *
     * @access  public
     * @param grade_id
     * @return  array
     */
    public function getLinkGoodsDesc()
    {
        return $this->hasOne('App\Models\LinkGoodsDesc', 'id', 'd_id');
    }
}
