<?php

namespace App\Models;

use App\Entities\WholesalePurchase as Base;

/**
 * Class WholesalePurchase
 */
class WholesalePurchase extends Base
{
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }
}
