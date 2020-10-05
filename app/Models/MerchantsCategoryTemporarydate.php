<?php

namespace App\Models;

use App\Entities\MerchantsCategoryTemporarydate as Base;

/**
 * Class MerchantsCategoryTemporarydate
 */
class MerchantsCategoryTemporarydate extends Base
{
    /**
     * 关联分类
     *
     * @access  public
     * @param cat_id
     * @return  array
     */
    public function getCategory()
    {
        return $this->hasOne("App\Models\Category", 'cat_id', 'cat_id');
    }
}
