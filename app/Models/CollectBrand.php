<?php

namespace App\Models;

use App\Entities\CollectBrand as Base;

/**
 * Class CollectBrand
 */
class CollectBrand extends Base
{
    /**
     * 关联品牌
     *
     * @access  public
     * @return  array
     */
    public function getBrand()
    {
        return $this->hasOne('App\Models\Brand', 'brand_id', 'brand_id');
    }
}
