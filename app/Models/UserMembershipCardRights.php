<?php

namespace App\Models;

use App\Entities\UserMembershipCardRights as Base;

/**
 * Class UserMembershipCardRights
 */
class UserMembershipCardRights extends Base
{

    /**
     * 关联会员权益
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userMembershipRights()
    {
        return $this->hasOne('App\Models\UserMembershipRights', 'id', 'rights_id');
    }

    /**
     * 关联会员权益列表
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function userMembershipRightsList()
    {
        return $this->hasMany('App\Models\UserMembershipRights', 'id', 'rights_id');
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
