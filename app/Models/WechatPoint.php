<?php

namespace App\Models;

use App\Entities\WechatPoint as Base;

/**
 * Class WechatPoint
 */
class WechatPoint extends Base
{
    /** 关联微信粉丝表
     * @param openid
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWechatUser()
    {
        return $this->hasOne('App\Models\WechatUser', 'openid', 'openid');
    }

    /**
     * 账户记录
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getAccountLog()
    {
        return $this->hasOne('App\Models\AccountLog', 'log_id', 'log_id');
    }

}
