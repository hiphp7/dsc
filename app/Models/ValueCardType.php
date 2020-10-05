<?php

namespace App\Models;

use App\Entities\ValueCardType as Base;

/**
 * Class ValueCardType
 */
class ValueCardType extends Base
{
    /**
     * 关联商品虚拟卡
     *
     * @access  public
     * @param tid
     * @return  array
     */
    public function getValueCard()
    {
        return $this->hasOne('App\Models\ValueCard', 'tid', 'id');
    }
}
