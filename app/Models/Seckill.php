<?php

namespace App\Models;

use App\Entities\Seckill as Base;

/**
 * Class Seckill
 */
class Seckill extends Base
{
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }
}
