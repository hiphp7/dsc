<?php

namespace App\Models;

use App\Entities\OfflineStore as Base;

/**
 * Class OfflineStore
 */
class OfflineStore extends Base
{

    /**
     * 关联门店商品
     *
     * @access  public
     * @param store_id
     * @return  array
     */
    public function getStoreGoods()
    {
        return $this->hasOne('App\Models\StoreGoods', 'store_id', 'id');
    }

    /**
     * 关联门店商品货品
     *
     * @access  public
     * @param store_id
     * @return  array
     */
    public function getStoreProducts()
    {
        return $this->hasOne('App\Models\StoreProducts', 'store_id', 'id');
    }

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

    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }
}
