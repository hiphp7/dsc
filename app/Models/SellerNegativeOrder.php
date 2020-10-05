<?php

namespace App\Models;

use App\Entities\SellerNegativeOrder as Base;

/**
 * Class SellerNegativeOrder
 */
class SellerNegativeOrder extends Base
{
    public function getSellerNegativeBill()
    {
        return $this->hasOne('App\Models\SellerNegativeBill', 'id', 'negative_id');
    }
}
