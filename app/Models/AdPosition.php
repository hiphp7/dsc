<?php

namespace App\Models;

use App\Entities\AdPosition as Base;

/**
 * Class AdPosition
 */
class AdPosition extends Base
{
    /**
     * 关联广告
     *
     * @access  public
     * @return array
     */
    public function getAd()
    {
        return $this->hasMany('App\Models\Ad', 'position_id', 'position_id');
    }

    /**
     * 关联广告列表
     *
     * @access  public
     * @return array
     */
    public function getAdList()
    {
        return $this->hasMany('App\Models\Ad', 'position_id', 'position_id');
    }

    /**
     * 关联店铺
     *
     * @access  public
     * @return array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasMany('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }
}
