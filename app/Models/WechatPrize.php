<?php

namespace App\Models;

use App\Entities\WechatPrize as Base;

/**
 * Class WechatPrize
 */
class WechatPrize extends Base
{
    /** 关联微信粉丝表
     * @param openid
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWechatUser()
    {
        return $this->hasOne('App\Models\WechatUser', 'openid', 'openid');
    }
}
