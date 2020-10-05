<?php

namespace App\Models;

use App\Entities\ShippingPoint as Base;

/**
 * Class ShippingPoint
 */
class ShippingPoint extends Base
{
    /**
     * 关联自提地区
     *
     * @access  public
     * @param shipping_area_id
     * @return  array
     */
    public function getAreaRegion()
    {
        return $this->hasOne('App\Models\AreaRegion', 'shipping_area_id', 'shipping_area_id');
    }

    /**
     * 关联自提地区
     *
     * @access  public
     * @param shipping_area_id
     * @return  array
     */
    public function getShippingArea()
    {
        return $this->hasOne('App\Models\ShippingArea', 'shipping_area_id', 'shipping_area_id');
    }

    public function getOrderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'point_id', 'id');
    }

}
