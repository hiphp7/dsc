<?php

namespace App\Models;

use App\Entities\AreaRegion as Base;

/**
 * Class AreaRegion
 */
class AreaRegion extends Base
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
     * 关联地区
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getShippingPoint()
    {
        return $this->hasOne('App\Models\ShippingPoint', 'shipping_area_id', 'shipping_area_id');
    }
}
