<?php

namespace App\Models;

use App\Entities\UserAddress as Base;

/**
 * Class UserAddress
 */
class UserAddress extends Base
{
    /**
     * 关联会员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联省份
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionProvince()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'province');
    }

    /**
     * 关联城市
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionCity()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'city');
    }

    /**
     * 关联城镇
     *
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionDistrict()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'district');
    }

    /**
     * 关联乡村/街道
     *
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionStreet()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'street');
    }
}
