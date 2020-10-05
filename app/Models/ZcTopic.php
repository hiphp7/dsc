<?php

namespace App\Models;

use App\Entities\ZcTopic as Base;

/**
 * Class ZcTopic
 */
class ZcTopic extends Base
{
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    public function getZcProject()
    {
        return $this->hasOne('App\Models\ZcProject', 'id', 'pid');
    }
}
