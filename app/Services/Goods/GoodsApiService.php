<?php

namespace App\Services\Goods;

use App\Models\UserRank;
use App\Models\Users;
use App\Services\User\UserRankService;

class GoodsApiService
{
    /**
     * 获取当前用户等级对应的折扣系数
     *
     * @param int $uid
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function UserRankDiscount($uid = 0)
    {
        $cache_name = "user_rank_discount_" . $uid;
        $data = cache($cache_name);

        if (is_null($data)) {
            $rank = Users::select('user_rank')->where('user_id', $uid)->first();
            $rank = $rank ? $rank->toArray() : [];
            $data = [];
            if ($uid) {
                if ($rank['user_rank'] > 0) {
                    //用户是特殊会员等级
                    $user_rank_info = UserRank::whereHas('getUsers', function ($query) use ($uid) {
                        $query->where('user_id', $uid);
                    });

                    $user_rank_info = $user_rank_info->first();

                    $data = $user_rank_info ? $user_rank_info->toArray() : [];

                    $data['discount'] = isset($data['discount']) ? $data['discount'] * 0.01 : 1;

                } else {
                    //用户是一般会员
                    $user_rank_info = Users::select('rank_points', 'rank_points')->where(['user_id' => $uid])->first();
                    $user_rank_info = $user_rank_info ? $user_rank_info->toArray() : [];

                    if ($user_rank_info) {
                        //1.4.3 会员等级修改（成长值只有下限）
                        $user_rank = app(UserRankService::class)->getUserRankByPoint($user_rank_info['rank_points']);

                        $data['rank_id'] = $user_rank['rank_id'] ?? 0;
                        $data['rank_name'] = $user_rank['rank_name'] ?? '';
                        $data['discount'] = isset($user_rank['discount']) ? $user_rank['discount'] * 0.01 : 1;
                    }
                }
            }

            cache()->forever($cache_name, $data);
        }

        return $data;
    }
}
