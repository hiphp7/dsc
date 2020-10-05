<?php

namespace App\Models;

use App\Entities\MerchantsRegionInfo as Base;

/**
 * Class MerchantsRegionInfo
 */
class MerchantsRegionInfo extends Base
{
    /**
     * 关联地区
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegion()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'region_id');
    }

    /**
     * 关联仓库地区
     *
     * @access  public
     * @param regionId
     * @return  array
     */
    public function getRegionWarehouse()
    {
        return $this->hasOne('App\Models\RegionWarehouse', 'regionId', 'region_id');
    }
}
