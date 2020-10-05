<?php

namespace App\Models;

use App\Entities\LinkAreaGoods as Base;

/**
 * Class LinkAreaGoods
 */
class LinkAreaGoods extends Base
{
    /**
     * 关联仓库地区
     *
     * @access  public
     * @return array
     */
    public function getRegionWarehouse()
    {
        return $this->hasOne('App\Models\RegionWarehouse', 'region_id', 'region_id');
    }
}
