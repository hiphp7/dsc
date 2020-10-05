<?php

namespace App\Models;

use App\Entities\ImService as Base;

/**
 * Class ImService
 */
class ImService extends Base
{
    public function AdminUser()
    {
        return $this->belongsTo('App\Models\AdminUser', 'user_id', 'user_id');
    }

    public function getAdminUser()
    {
        return $this->hasOne('App\Models\AdminUser', 'user_id', 'user_id');
    }
}
