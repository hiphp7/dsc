<?php

namespace App\Models;

use App\Entities\StoreGoods as Base;

/**
 * Class StoreGoods
 */
class StoreGoods extends Base
{
    /**
     * 关联门店
     *
     * @access  public
     * @param id
     * @return  array
     */
    public function getOfflineStore()
    {
        return $this->hasOne('App\Models\OfflineStore', 'id', 'store_id');
    }
}
