<?php

namespace App\Models;

use App\Entities\Region as Base;

/**
 * Class Region
 */
class Region extends Base
{
    /**
     * 关联仓库
     *
     * @access  public
     * @param regionId
     * @return  array
     */
    public function getRegionWarehouse()
    {
        return $this->hasOne('App\Models\RegionWarehouse', 'regionId', 'region_id');
    }

    /**
     * 关联地区父级
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionParent()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'parent_id');
    }

    /**
     * 关联地区子级
     *
     * @access  public
     * @param parent_id
     * @return  array
     */
    public function getRegionChild()
    {
        return $this->hasMany('App\Models\Region', 'parent_id', 'region_id');
    }
}
