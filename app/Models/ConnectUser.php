<?php

namespace App\Models;

use App\Entities\ConnectUser as Base;

/**
 * Class ConnectUser
 */
class ConnectUser extends Base
{
    /**
     * 关联会员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }
}
