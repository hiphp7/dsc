<?php

namespace App\Models;

use App\Entities\BonusType as Base;

/**
 * Class BonusType
 */
class BonusType extends Base
{
    /**
     * 关联会员红包
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getUserBonus()
    {
        return $this->hasOne('App\Models\UserBonus', 'bonus_type_id', 'type_id');
    }

    /**
     * 关联店铺
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }
}
