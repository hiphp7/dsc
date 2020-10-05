<?php

namespace App\Models;

use App\Entities\WechatQrcode as Base;

/**
 * Class WechatQrcode
 */
class WechatQrcode extends Base
{
    /**
     * 关联推荐分成表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function affiliateLog()
    {
        return $this->hasOne('App\Models\AffiliateLog', 'user_id', 'scene_id');
    }
}
