<?php

namespace App\Models;

use App\Entities\WarehouseFreight as Base;

/**
 * Class WarehouseFreight
 */
class WarehouseFreight extends Base
{
    /**
     * 关联配送方式
     *
     * @access  public
     * @param shipping_id
     * @return  array
     */
    public function getShipping()
    {
        return $this->hasOne('App\Models\Shipping', 'shipping_id', 'shipping_id');
    }

    /**
     * 关联仓库region_id
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionWarehouseRegId()
    {
        return $this->hasOne('App\Models\RegionWarehouse', 'region_id', 'warehouse_id');
    }

    /**
     * 关联仓库regionId
     *
     * @access  public
     * @param regionId
     * @return  array
     */
    public function getRegionWarehousereg()
    {
        return $this->hasOne('App\Models\RegionWarehouse', 'regionId', 'region_id');
    }
}
