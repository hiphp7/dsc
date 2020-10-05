<?php

namespace App\Models;

use App\Entities\MerchantsAccountLog as Base;

/**
 * Class MerchantsAccountLog
 */
class MerchantsAccountLog extends Base
{
    /**
     * 关联入驻商
     *
     * @access  public
     * @return array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }
}
