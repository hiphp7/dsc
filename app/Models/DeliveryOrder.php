<?php

namespace App\Models;

use App\Entities\DeliveryOrder as Base;

/**
 * Class DeliveryOrder
 */
class DeliveryOrder extends Base
{
    public function getDeliveryGoods()
    {
        return $this->hasMany('App\Models\DeliveryGoods', 'delivery_id', 'delivery_id');
    }

    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'order_id', 'order_id');
    }

    public function getOrderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
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

    /**
     * 关联乡村/街道
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionStreet()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'street');
    }

    /**
     * 关联配送方式
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getShipping()
    {
        return $this->hasOne('App\Models\Shipping', 'shipping_id', 'shipping_id');
    }
}
