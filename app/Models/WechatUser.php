<?php

namespace App\Models;

use App\Entities\WechatUser as Base;

/**
 * Class WechatUser
 */
class WechatUser extends Base
{
    /**
     * 关联绑定会员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function connectUser()
    {
        return $this->hasOne('App\Models\ConnectUser', 'open_id', 'unionid');
    }
}
