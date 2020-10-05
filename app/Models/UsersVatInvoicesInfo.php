<?php

namespace App\Models;

use App\Entities\UsersVatInvoicesInfo as Base;

/**
 * Class UsersVatInvoicesInfo
 */
class UsersVatInvoicesInfo extends Base
{
    /**
     * 关联省份
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionProvince()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'province');
    }

    /**
     * 关联城市
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionCity()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'city');
    }

    /**
     * 关联地区
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionDistrict()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'district');
    }

    /**
     * 关联街道
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionStreet()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'street');
    }
}
