<?php

namespace App\Models;

use App\Entities\SellerCommissionBill as Base;

/**
 * Class SellerCommissionBill
 */
class SellerCommissionBill extends Base
{
    /**
     * 关联佣金比例
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getMerchantsServer()
    {
        return $this->hasOne('App\Models\MerchantsServer', 'user_id', 'seller_id');
    }
}
