<?php

namespace App\Models;

use App\Entities\WechatWallMsg as Base;

/**
 * Class WechatWallMsg
 */
class WechatWallMsg extends Base
{
    /**
     * 微信墙用户表
     * @param WechatWallUser id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wechatWallUser()
    {
        return $this->hasOne('App\Models\WechatWallUser', 'id', 'user_id');
    }

    /**
     * 活动表
     * @param WechatMarketing id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wechatMarketing()
    {
        return $this->hasOne('App\Models\WechatMarketing', 'id', 'wall_id');
    }
}
