<?php

namespace App\Models;

use App\Entities\PayCard as Base;

/**
 * Class PayCard
 */
class PayCard extends Base
{
    /**
     * 关联充值卡类型
     *
     * @access  public
     * @param type_id
     * @return  array
     */
    public function getPayCardType()
    {
        return $this->hasOne('App\Models\PayCardType', 'type_id', 'c_id');
    }

    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }
}
