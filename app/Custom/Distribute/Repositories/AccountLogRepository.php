<?php

namespace App\Custom\Distribute\Repositories;

use App\Models\AccountLog;
use App\Models\MerchantsAccountLog;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\TimeRepository;
use Illuminate\Support\Facades\DB;

/**
 * 记录账户变动
 * Class AccountLogRepository
 * @package App\Custom\Distribute\Repositories
 */
class AccountLogRepository
{
    protected $timeRepository;

    public function __construct(
        TimeRepository $timeRepository
    )
    {
        $this->timeRepository = $timeRepository;
    }

    /**
     * 记录帐户变动
     * @param $user_id
     * @param int $user_money 可用余额变动
     * @param int $frozen_money 冻结余额变动
     * @param int $rank_points 等级积分变动
     * @param int $pay_points 消费积分变动
     * @param string $change_desc 变动说明
     * @param int $change_type 变动类型
     * @param int $deposit_fee
     */
    public function log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER, $deposit_fee = 0)
    {
        if ($user_id > 0) {
            /* 插入帐户变动记录 */
            $account_log = [
                'user_id' => $user_id,
                'user_money' => $user_money,
                'frozen_money' => $frozen_money,
                'rank_points' => $rank_points,
                'pay_points' => $pay_points,
                'change_time' => $this->timeRepository->getGmTime(),
                'change_desc' => $change_desc,
                'change_type' => $change_type,
                'deposit_fee' => $deposit_fee
            ];

            $log_id = AccountLog::insertGetId($account_log);

            /* 更新用户信息 */
            $user_money = $user_money + $deposit_fee;
            $update_log = [
                'frozen_money' => DB::raw("frozen_money  + ('$frozen_money')"),
                'pay_points' => DB::raw("pay_points  + ('$pay_points')"),
                'rank_points' => DB::raw("rank_points  + ('$rank_points')")
            ];

            Users::where('user_id', $user_id)->increment('user_money', $user_money, $update_log);

            return $log_id;
        }
    }

    /**
     * 商家帐户变动
     * @param $ru_id
     * @param int $seller_money
     * @param int $frozen_money
     */
    public function log_seller_account_change($ru_id, $seller_money = 0, $frozen_money = 0)
    {
        /* 更新商家账户信息 */
        if ($ru_id > 0 && $seller_money > 0) {

            $other = $frozen_money > 0 ? ['frozen_money' => DB::raw("frozen_money  + ('$frozen_money')")] : [];

            SellerShopinfo::where('ru_id', $ru_id)->increment('seller_money', $seller_money, $other);
        }
    }

    /**
     * 商家帐户变动记录
     * @param $ru_id
     * @param int $user_money
     * @param int $frozen_money
     * @param $change_desc
     * @param int $change_type
     */
    public function merchants_account_log($ru_id, $user_money = 0, $frozen_money = 0, $change_desc, $change_type = 1)
    {
        if ($ru_id > 0) {
            $other = [
                'user_id' => $ru_id,
                'user_money' => $user_money,
                'frozen_money' => $frozen_money,
                'change_time' => $this->timeRepository->getGmTime(),
                'change_desc' => $change_desc,
                'change_type' => $change_type
            ];
            MerchantsAccountLog::insert($other);
        }
    }


}
