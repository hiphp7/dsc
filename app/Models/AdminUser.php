<?php

namespace App\Models;

use App\Entities\AdminUser as Base;

/**
 * Class AdminUser
 */
class AdminUser extends Base
{
    public function service()
    {
        return $this->hasOne('App\Models\ImService', 'user_id', 'user_id');
    }

    public function getRole()
    {
        return $this->hasOne('App\Models\Role', 'role_id', 'role_id');
    }

    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }

    public function getSellerShopinfo()
    {
        return $this->hasOne('App\Models\SellerShopinfo', 'ru_id', 'ru_id');
    }
}
