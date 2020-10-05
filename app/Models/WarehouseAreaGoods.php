<?php

namespace App\Models;

use App\Entities\WarehouseAreaGoods as Base;

/**
 * Class WarehouseAreaGoods
 */
class WarehouseAreaGoods extends Base
{
    /**
     * 关联仓库地区
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
     * 关联仓库地区商品
     *
     * @access  public
     * @param city_id
     * @return  array
     */
    public function getRegionWarehouseCity()
    {
        return $this->hasOne('App\Models\WarehouseAreaGoods', 'city_id', 'region_id');
    }
}
