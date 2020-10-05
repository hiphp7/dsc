<?php

namespace App\Models;

use App\Entities\Ad as Base;

/**
 * Class Ad
 */
class Ad extends Base
{
    /**
     * 关联广告位
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getAdPosition()
    {
        return $this->hasOne('App\Models\AdPosition', 'position_id', 'position_id');
    }

    /**
     * 关联手机广告位
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getTouchAdPosition()
    {
        return $this->hasOne('App\Models\TouchAdPosition', 'position_id', 'position_id');
    }

    /**
     * 关联订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'from_ad', 'ad_id');
    }

    /**
     * 关联广告
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getAdsense()
    {
        return $this->hasOne('App\Models\Adsense', 'from_ad', 'ad_id');
    }
}
