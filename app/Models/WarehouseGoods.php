<?php

namespace App\Models;

use App\Entities\WarehouseGoods as Base;

/**
 * Class WarehouseGoods
 */
class WarehouseGoods extends Base
{
    /**
     * 关联仓库
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionWarehouse()
    {
        return $this->hasOne('App\Models\RegionWarehouse', 'region_id', 'region_id');
    }

    /**
     * 关联仓库地区
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionWarehouseCity()
    {
        return $this->hasOne('App\Models\RegionWarehouse', 'region_id', 'city_id');
    }
}
