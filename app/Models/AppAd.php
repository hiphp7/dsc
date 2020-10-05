<?php

namespace App\Models;

use App\Entities\AppAd as Base;

/**
 * Class AppAd
 * @package App\Models
 */
class AppAd extends Base
{
    /**
     * 关联广告位
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function appAdPosition()
    {
        return $this->hasOne('App\Models\AppAdPosition', 'position_id', 'position_id');
    }
}
