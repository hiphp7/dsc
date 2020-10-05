<?php

namespace App\Models;

use App\Entities\ImDialog as Base;

/**
 * Class ImDialog
 */
class ImDialog extends Base
{
    /**
     * 关联用户
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUser()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'customer_id');
    }

    public function getImService()
    {
        return $this->hasOne('App\Models\ImService', 'id', 'services_id');
    }
}
