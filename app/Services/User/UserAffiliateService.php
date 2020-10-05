<?php

namespace App\Services\User;

use App\Models\Users;

class UserAffiliateService
{
    /**
     * 获取推荐
     *
     * @param int $parent_id
     * @return int
     */
    public function getAffiliate($parent_id = 0)
    {
        if ($parent_id > 0) {
            $user_id = Users::where('user_id', $parent_id)->value('user_id');
            return $user_id ?? 0;
        }

        return 0;
    }
}
