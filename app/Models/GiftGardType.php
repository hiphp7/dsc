<?php

namespace App\Models;

use App\Entities\GiftGardType as Base;

/**
 * Class GiftGardType
 */
class GiftGardType extends Base
{
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }
}
