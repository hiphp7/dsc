<?php

namespace App\Models;

use App\Entities\ValueCard as Base;

/**
 * Class ValueCard
 */
class ValueCard extends Base
{
    /**
     * 关联储值卡
     *
     * @access  public
     * @param id
     * @return  array
     */
    public function getValueCardType()
    {
        return $this->hasOne('App\Models\ValueCardType', 'id', 'tid');
    }
}
