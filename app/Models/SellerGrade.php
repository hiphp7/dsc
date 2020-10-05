<?php

namespace App\Models;

use App\Entities\SellerGrade as Base;

/**
 * Class SellerGrade
 */
class SellerGrade extends Base
{
    /**
     * 关联店铺等级信息
     *
     * @access  public
     * @param grade_id
     * @return  array
     */
    public function getMerchantsGrade()
    {
        return $this->hasOne('App\Models\MerchantsGrade', 'grade_id', 'id');
    }
}
