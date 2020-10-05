<?php

namespace App\Models;

use App\Entities\WholesaleDeliveryOrder as Base;

/**
 * Class WholesaleDeliveryOrder
 */
class WholesaleDeliveryOrder extends Base
{
    /**
     * 关联批发订单商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWholesaleDeliveryGoods()
    {
        return $this->hasOne('App\Models\WholesaleDeliveryGoods', 'order_id', 'order_id');
    }

    /**
     * 关联国家
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionCountry()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'country');
    }

    /**
     * 关联省份
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionProvince()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'province');
    }

    /**
     * 关联城市
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionCity()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'city');
    }

    /**
     * 关联城镇
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionDistrict()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'district');
    }
}
