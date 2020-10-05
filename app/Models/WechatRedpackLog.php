<?php

namespace App\Models;

use App\Entities\WechatRedpackLog as Base;

/**
 * Class WechatRedpackLog
 */
class WechatRedpackLog extends Base
{
    /**
     * 关联微信粉丝
     * @param openid
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWechatUser()
    {
        return $this->hasOne('App\Models\WechatUser', 'openid', 'openid');
    }
}
