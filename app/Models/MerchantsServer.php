<?php

namespace App\Models;

use App\Entities\MerchantsServer as Base;

/**
 * Class MerchantsServer
 */
class MerchantsServer extends Base
{
    /**
     * 关联店铺比例
     *
     * @access  public
     * @param percent_id
     * @return  array
     */
    public function getMerchantsPercent()
    {
        return $this->hasOne('App\Models\MerchantsPercent', 'percent_id', 'suppliers_percent');
    }
}
