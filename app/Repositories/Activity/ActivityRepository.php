<?php

namespace App\Repositories\Activity;


class ActivityRepository
{
    /**
     * 计算拍卖活动状态（注意参数一定是原始信息）
     *
     * @param $auction
     * @param int $time
     * @return int
     */
    public function getAuctionStatus($auction, $time = 0)
    {
        //默认时间为当前时间的时间戳
        if ($time == 0) {
            $time = gmtime();
        }

        if (isset($auction['is_finished'])) {
            if ($auction['is_finished'] == 0) {
                if ($time < $auction['start_time']) {
                    return PRE_START; // 未开始
                } elseif ($time > $auction['end_time']) {
                    return FINISHED; // 已结束，未处理
                } else {
                    return UNDER_WAY; // 进行中
                }
            } elseif ($auction['is_finished'] == 1) {
                return FINISHED; // 已结束，未处理
            } else {
                return SETTLED; // 已结束，已处理
            }
        } else {
            return SETTLED; // 已结束，已处理
        }
    }
}