<?php

namespace App\Models;

use App\Entities\MerchantsShopBrand as Base;

/**
 * Class MerchantsShopBrand
 */
class MerchantsShopBrand extends Base
{
    /**
     * 关联品牌
     *
     * @access  public
     * @param bid
     * @return  array
     */
    public function getLinkBrand()
    {
        return $this->hasOne('App\Models\LinkBrand', 'bid', 'bid');
    }

    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }
}
