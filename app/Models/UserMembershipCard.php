<?php

namespace App\Models;

use App\Entities\UserMembershipCard as Base;

/**
 * Class UserMembershipCard
 */
class UserMembershipCard extends Base
{

    /**
     * 关联会员权益
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function userMembershipCardRightsList()
    {
        return $this->hasMany('App\Models\UserMembershipCardRights', 'membership_card_id', 'id');
    }


    public function userMembershipCardRights()
    {
        return $this->hasMany('App\Models\UserMembershipCardRights', 'membership_card_id', 'id');
    }

}
