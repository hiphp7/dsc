<?php

namespace App\Models;

use App\Entities\UserRank as Base;

/**
 * Class UserRank
 */
class UserRank extends Base
{
    /**
     * 关联会员
     *
     * @access  public
     * @param user_rank
     * @return  array
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_rank', 'rank_id');
    }

    /**
     * 关联会员等级价
     *
     * @access  public
     * @param user_rank
     * @return  array
     */
    public function getMemberPrice()
    {
        return $this->hasOne('App\Models\MemberPrice', 'user_rank', 'rank_id');
    }


    /**
     * 关联会员等级权益
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userRankRights()
    {
        return $this->hasMany('App\Models\UserRankRights', 'user_rank_id', 'rank_id');
    }


}
