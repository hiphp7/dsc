<?php

namespace App\Models;

use App\Entities\RsRegion as Base;

/**
 * Class RsRegion
 */
class RsRegion extends Base
{
    /**
     * 关联商家
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'region_id', 'region_id');
    }

    /**
     * 关联商品店铺
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getMerchantsShopInformationList()
    {
        return $this->hasMany('App\Models\MerchantsShopInformation', 'region_id', 'region_id');
    }

    public function getRegionStore()
    {
        return $this->hasOne('App\Models\RegionStore', 'rs_id', 'rs_id');
    }
}
