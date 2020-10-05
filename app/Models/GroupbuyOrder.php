<?php

namespace App\Models;

use App\Entities\GroupbuyOrder as Base;

/**
 * Class GroupbuyOrder
 */
class GroupbuyOrder extends Base
{

    /**
     * 关联会员
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联活动
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGroupbuyGoods()
    {
        return $this->hasOne('App\Models\GroupbuyGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联团长
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGroupbuyLeader()
    {
        return $this->hasOne('App\Models\GroupbuyLeader', 'leader_id', 'leader_id');
    }

    public function getRegionCountry()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'country');
    }

    /**
     * 关联省份
     * @return  array
     */
    public function getRegionProvince()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'province');
    }

    /**
     * 关联城市
     * @return  array
     */
    public function getRegionCity()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'city');
    }

    /**
     * 关联城镇
     * @return  array
     */
    public function getRegionDistrict()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'district');
    }

    /**
     * 关联乡村/街道
     * @return  array
     */
    public function getRegionStreet()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'street');
    }


}
