<?php

namespace App\Models;

use App\Entities\AdminMessage as Base;

/**
 * Class AdminMessage
 */
class AdminMessage extends Base
{
    public function getAdminUser()
    {
        return $this->hasOne('App\Models\AdminUser', 'user_id', 'sender_id');
    }
}
