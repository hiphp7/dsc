<?php

namespace App\Models;

use App\Entities\BackOrder as Base;

/**
 * Class BackOrder
 */
class BackOrder extends Base
{
    /**
     * 关联订单
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联订单商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'order_id', 'order_id');
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
}
