<?php

namespace App\Models;

use App\Entities\CollectStore as Base;

/**
 * Class CollectStore
 */
class CollectStore extends Base
{
    /**
     * 关联商家
     *
     * @access  public
     * @return  array
     */
    public function getSellerShopinfo()
    {
        return $this->hasOne('App\Models\SellerShopinfo', 'ru_id', 'ru_id');
    }
}
