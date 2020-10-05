<?php

namespace App\Models;

use App\Entities\UserGiftGard as Base;

/**
 * Class UserGiftGard
 */
class UserGiftGard extends Base
{
    public function getGiftGardType()
    {
        return $this->hasOne('App\Models\GiftGardType', 'gift_id', 'gift_id');
    }

    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
