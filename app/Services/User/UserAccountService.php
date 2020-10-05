<?php

namespace App\Services\User;


use App\Models\AccountLog;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;
use Illuminate\Support\Facades\DB;

class UserAccountService
{
    protected $baseRepository;
    protected $userRankService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        UserRankService $userRankService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->userRankService = $userRankService;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 记录帐户变动
     *
     * @param int $user_id 用户id
     * @param int $user_money 可用余额变动
     * @param int $frozen_money 冻结余额变动
     * @param int $rank_points 等级积分变动
     * @param int $pay_points 消费积分变动
     * @param string $change_desc 变动说明
     * @param int $change_type 变动类型：参见常量文件
     * @param int $order_type
     * @param int $deposit_fee
     * @throws \Exception
     */
    public function logAccountChange($user_id = 0, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER, $order_type = 0, $deposit_fee = 0)
    {
        $is_go = true;
        $is_user_money = 0;
        $is_pay_points = 0;

        //控制只有后台执行，前台不操作以下程序
        if ($change_desc && $order_type) {
            $change_desc_arr = $change_desc ? explode(" ", $change_desc) : [];

            if (count($change_desc_arr) >= 2) {
                $order_sn = !empty($change_desc_arr[1]) ? $change_desc_arr[1] : '';

                if (!empty($order_sn)) {
                    $order_res = OrderInfo::select(['order_id', 'main_order_id'])->where('order_sn', $order_sn);
                    $order_res = $this->baseRepository->getToArrayFirst($order_res);
                } else {
                    $order_res = [];
                }

                if (empty($order_res)) {
                    $is_go = false;
                }

                if ($order_res) {
                    if ($order_res['main_order_id'] > 0) {  //操作无效或取消订单时，先查询该订单是否有主订单

                        $ordor_main = OrderInfo::select('order_sn')->where('order_id', $order_res['main_order_id']);
                        $ordor_main = $this->baseRepository->getToArrayFirst($ordor_main);

                        if ($ordor_main) {
                            $order_surplus_desc = sprintf(lang('user.return_order_surplus'), $ordor_main['order_sn']);
                            $order_integral_desc = sprintf(lang('user.return_order_integral'), $ordor_main['order_sn']);
                        } else {
                            $order_surplus_desc = '';
                            $order_integral_desc = '';
                        }

                        //查询该订单的主订单是否已操作过无效或取消订单
                        $change_desc = [$order_surplus_desc, $order_integral_desc];

                        $log_res = [];
                        if ($change_desc) {
                            $log_res = AccountLog::select('log_id')->whereIn('change_desc', $change_desc);
                            $log_res = $this->baseRepository->getToArrayGet($log_res);
                        }

                        if ($log_res) {
                            $is_go = false;
                        }
                    } else {
                        if ($order_res && $order_res['order_id'] > 0) {
                            $main_order_res = OrderInfo::select('order_id', 'order_sn')->where('main_order_id', $order_res['order_id']);
                            $main_order_res = $this->baseRepository->getToArrayGet($main_order_res);

                            if ($main_order_res > 0) {
                                foreach ($main_order_res as $key => $row) {
                                    $order_surplus_desc = sprintf(lang('user.return_order_surplus'), $row['order_sn']);
                                    $order_integral_desc = sprintf(lang('user.return_order_integral'), $row['order_sn']);

                                    $main_change_desc = [$order_surplus_desc, $order_integral_desc];
                                    $parent_account_log = AccountLog::select(['user_money', 'pay_points'])->whereIn('change_desc', $main_change_desc);
                                    $parent_account_log = $this->baseRepository->getToArrayGet($parent_account_log);

                                    if ($parent_account_log) {
                                        if ($user_money) {
                                            $is_user_money += $parent_account_log[0]['user_money'];
                                        }

                                        if ($pay_points) {
                                            $is_pay_points += $parent_account_log[1]['pay_points'];
                                        }
                                    }
                                }
                            }
                        }

                        if ($user_money) {
                            $user_money -= $is_user_money;
                        }

                        if ($pay_points) {
                            $pay_points -= $is_pay_points;
                        }
                    }
                }
            }
        } /**
         * 判断是否是支付订单操作
         * 【订单号不能为空】
         *
         */
        elseif ($change_desc) {
            if (strpos($change_desc, '：') !== false) {
                $change_desc_arr = explode("：", $change_desc);
            } else {
                $change_desc_arr = explode(" ", $change_desc);
            }

            if (count($change_desc_arr) >= 2) {
                if (!empty($change_desc_arr[0]) && ($change_desc_arr[0] == '支付订单' || $change_desc_arr[0] == '追加使用余额支付订单')) {
                    if (!empty($change_desc_arr[1])) {
                        $change_desc_arr[1] = trim($change_desc_arr[1]);
                    }

                    $order_sn = !empty($change_desc_arr[1]) ? $change_desc_arr[1] : '';

                    if ($order_sn) {
                        $order_res = OrderInfo::where('order_sn', $order_sn);
                        $order_res = $this->baseRepository->getToArrayFirst($order_res);
                    } else {
                        $order_res = [];
                    }

                    if (empty($order_res)) {
                        $is_go = false;
                    }
                }
            }
        }

        if ($is_go && ($user_money || $frozen_money || $rank_points || $pay_points)) {
            if (is_array($change_desc)) {
                $change_desc = implode('<br/>', $change_desc);
            }

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

            AccountLog::insert($account_log);

            /* 更新用户信息 */
            $user_money = $user_money + $deposit_fee;

            $update_log = [
                'frozen_money' => DB::raw("frozen_money  + ('$frozen_money')"),
                'pay_points' => DB::raw("pay_points  + ('$pay_points')"),
                'rank_points' => DB::raw("rank_points  + ('$rank_points')")
            ];

            $this->baseRepository->getDbRaw($update_log);

            Users::where('user_id', $user_id)->increment('user_money', $user_money, $update_log);

            if (!$this->userRankService->judgeUserSpecialRank($user_id)) {

                /* 更新会员当前等级 start */
                $user_rank_points = Users::where('user_id', $user_id)->value('rank_points');
                $user_rank_points = $user_rank_points ? $user_rank_points : 0;

                $rank_row = [];
                if ($user_rank_points >= 0) {
                    //1.4.3 会员等级修改（成长值只有下限）
                    $rank_row = $this->userRankService->getUserRankByPoint($user_rank_points);
                }

                if ($rank_row) {
                    $rank_row['discount'] = $rank_row['discount'] / 100.00;
                } else {
                    $rank_row['discount'] = 1;
                    $rank_row['rank_id'] = 0;
                }
                /* 更新会员当前等级 end */

                Users::where('user_id', $user_id)->update(['user_rank' => $rank_row['rank_id']]);

//                if (config('session.driver') === 'database') {
//                    //等级和折扣变动user_rank，discount
//                    Sessions::where('userid', $user_id)->where('adminid', 0)->update(['user_rank' => $rank_row['rank_id']]);
//                }
                $userRank = [
                    'user_rank' => $rank_row['rank_id'],
                    'discount' => $rank_row['discount']
                ];
                session($userRank);
            }
        }
    }
}
