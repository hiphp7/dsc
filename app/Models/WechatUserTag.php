<?php

namespace App\Models;

use App\Entities\WechatUserTag as Base;

/**
 * Class WechatUserTag
 */
class WechatUserTag extends Base
{
    /**
     * 关联微信粉丝表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wechatUser()
    {
        return $this->hasOne('App\Models\WechatUser', 'openid', 'openid');
    }
}
