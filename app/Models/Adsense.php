<?php

namespace App\Models;

use App\Entities\Adsense as Base;

/**
 * Class Adsense
 */
class Adsense extends Base
{
    /**
     * 关联广告位
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getAd()
    {
        return $this->hasOne('App\Models\Ad', 'ad_id', 'from_ad');
    }
}
