<?php

namespace App\Models;

use App\Entities\MerchantsGrade as Base;

/**
 * Class MerchantsGrade
 */
class MerchantsGrade extends Base
{
    /**
     * 关联店铺等级信息
     *
     * @param grade_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getSellerGrade()
    {
        return $this->hasOne('App\Models\SellerGrade', 'id', 'grade_id');
    }
}
