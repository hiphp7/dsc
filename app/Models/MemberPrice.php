<?php

namespace App\Models;

use App\Entities\MemberPrice as Base;

/**
 * Class MemberPrice
 */
class MemberPrice extends Base
{
    /**
     *  关联会员等级
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUserRank()
    {
        return $this->hasOne('App\Models\UserRank', 'rank_id', 'user_rank');
    }
}
