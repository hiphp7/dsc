<?php

namespace App\Models;

use App\Entities\SellerTemplateApply as Base;

/**
 * Class SellerTemplateApply
 */
class SellerTemplateApply extends Base
{
    /**
     * 关联支付方式
     *
     * @access  public
     * @param pay_id
     * @return  array
     */
    public function getPayment()
    {
        return $this->hasOne('App\Models\Payment', 'pay_id', 'pay_id');
    }
}
