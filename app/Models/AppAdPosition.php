<?php

namespace App\Models;

use App\Entities\AppAdPosition as Base;

/**
 * Class AppAdPosition
 * @package App\Models
 */
class AppAdPosition extends Base
{
    /**
     * 关联广告列表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function appAds()
    {
        return $this->hasMany('App\Models\AppAd', 'position_id', 'position_id');
    }
}
