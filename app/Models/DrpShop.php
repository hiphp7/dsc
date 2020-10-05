<?php

namespace App\Models;

use App\Entities\DrpShop as Base;

/**
 * Class DrpShop
 */
class DrpShop extends Base
{
    /**
     * 关联分销记录
     * @param user_id
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getDrpLogList()
    {
        return $this->hasMany('App\Models\DrpLog', 'user_id', 'user_id');
    }

    /**
     * 关联会员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联下级分销商
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getChildUsers()
    {
        return $this->hasMany('App\Models\Users', 'drp_parent_id', 'user_id');
    }

    /**
     * 关联分销等级
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function drpUserCredit()
    {

        return $this->hasOne('App\Models\DrpUserCredit', 'id', 'credit_id');
    }

    /**
     * 关联会员权益卡
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userMembershipCard()
    {

        return $this->hasOne('App\Models\UserMembershipCard', 'id', 'membership_card_id');
    }
}
