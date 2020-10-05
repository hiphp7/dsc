<?php

namespace App\Models;

use App\Entities\HomeTemplates as Base;

/**
 * Class HomeTemplates
 */
class HomeTemplates extends Base
{
    /**
     * 关联支付方式
     *
     * @access  public
     * @param pay_id
     * @return  array
     */
    public function getRegionStore()
    {
        return $this->hasOne('App\Models\RegionStore', 'rs_id', 'rs_id');
    }
}
