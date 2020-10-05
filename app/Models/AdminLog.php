<?php

namespace App\Models;

use App\Entities\AdminLog as Base;

/**
 * Class AdminLog
 */
class AdminLog extends Base
{

    /**
     *  关联管理员
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function adminUser()
    {
        return $this->hasOne('App\Models\AdminUser', 'user_id', 'user_id');
    }
}
