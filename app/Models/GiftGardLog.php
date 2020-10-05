<?php

namespace App\Models;

use App\Entities\GiftGardLog as Base;

/**
 * Class GiftGardLog
 */
class GiftGardLog extends Base
{
    public function getAdminUser()
    {
        return $this->hasOne('App\Models\AdminUser', 'user_id', 'admin_id');
    }
}
