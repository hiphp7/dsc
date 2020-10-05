<?php

namespace App\Models;

use App\Entities\UsersLog as Base;

/**
 * Class UsersLog
 */
class UsersLog extends Base
{
    /**
     * 关联管理员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getAdminUser()
    {
        return $this->hasOne('App\Models\AdminUser', 'user_id', 'admin_id');
    }
}
