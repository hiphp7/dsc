<?php

namespace App\Models;

use App\Entities\UserBonus as Base;

/**
 * Class UserBonus
 */
class UserBonus extends Base
{
    /**
     * 关联红包类型
     * @param type_id
     * @param bonus_type_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getBonusType()
    {
        return $this->hasOne('App\Models\BonusType', 'type_id', 'bonus_type_id');
    }

    /**
     * 关联红包类型列表
     *
     * @param type_id
     * @param bonus_type_id
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getBonusTypeList()
    {
        return $this->hasMany('App\Models\BonusType', 'type_id', 'bonus_type_id');
    }

    /**
     * 关联会员
     * @param user_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联订单
     * @param order_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }
}
