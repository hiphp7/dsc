<?php

namespace App\Custom\Distribute\Models;

use App\Models\Users as Base;

/**
 * Class Users
 */
class Users extends Base
{
    /**
     * 关联分销商
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function drpShop()
    {
        return $this->hasOne('App\Models\DrpShop', 'user_id', 'user_id');
    }

    /**
     * 关联分销佣金记录
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function drpLog()
    {
        return $this->hasOne('App\Custom\Distribute\Models\DrpLog', 'user_id', 'user_id');
    }

    /**
     * 关联分销佣金记录
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function drpLogs()
    {
        return $this->hasMany('App\Custom\Distribute\Models\DrpLog', 'user_id', 'user_id');
    }
}
