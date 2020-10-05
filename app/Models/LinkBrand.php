<?php

namespace App\Models;

use App\Entities\LinkBrand as Base;

/**
 * Class LinkBrand
 */
class LinkBrand extends Base
{
    /**
     * 关联品牌
     *
     * @access  public
     * @param bid
     * @return  array
     */
    public function getBrand()
    {
        return $this->hasOne('App\Models\Brand', 'brand_id', 'brand_id');
    }

    /**
     * 关联品牌
     *
     * @access  public
     * @param bid
     * @return  array
     */
    public function getMerchantsShopBrand()
    {
        return $this->hasOne('App\Models\MerchantsShopBrand', 'bid', 'bid');
    }
}
