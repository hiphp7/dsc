<?php

namespace App\Models;

use App\Entities\UserRankRights as Base;

/**
 * Class UserRank
 */
class UserRankRights extends Base
{
    /**
     * 关联会员
     *
     * @access  public
     * @param user_rank
     * @return  array
     */
    public function getUserRank()
    {
        return $this->hasOne('App\Models\UserRank', 'rank_id', 'user_rank_id');
    }

    /**
     * 关联会员权益
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userMembershipRights()
    {
        return $this->hasOne('App\Models\UserMembershipRights', 'id', 'rights_id');
    }

}
