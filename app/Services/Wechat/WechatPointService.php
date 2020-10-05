<?php

namespace App\Services\Wechat;

use App\Models\AccountLog;
use App\Models\Coupons;
use App\Models\CouponsUser;
use App\Models\Users;
use App\Models\WechatPoint;
use App\Repositories\Common\TimeRepository;

class WechatPointService
{
    protected $timeRepository;
    protected $wechatUserService;

    /**
     * 构造函数
     */
    public function __construct(
        TimeRepository $timeRepository,
        WechatUserService $wechatUserService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->wechatUserService = $wechatUserService;
    }

    /**
     * 微信会员积分赠送处理
     *
     * @param string $fromusername
     * @param array $info
     * @param int $rank_points
     * @param int $pay_points
     * @return bool
     * @throws \Exception
     */
    public function do_point($fromusername = '', $info = [], $rank_points = 0, $pay_points = 0)
    {
        $users = $this->wechatUserService->get_wechat_user_id($fromusername);
        if ($users) {

            $time = $this->timeRepository->getGmTime();

            // 积分记录
            $data['user_id'] = $users['user_id'];
            $data['user_money'] = 0;
            $data['frozen_money'] = 0;
            $data['rank_points'] = intval($rank_points);
            $data['pay_points'] = intval($pay_points);
            $data['change_time'] = $time;
            $data['change_desc'] = $info['name'] . lang('wechat.give_integral');
            $data['change_type'] = ACT_OTHER;

            // 同一时间 同一用户不能重复插入
            $where = [
                'user_id' => $data['user_id'],
                'change_time' => $data['change_time'],
                'change_type' => ACT_OTHER,
            ];
            $account_log_num = AccountLog::where($where)->count();
            if ($account_log_num == 0) {
                $ac_log_id = AccountLog::insertGetId($data);

                // 从表记录
                $data1['log_id'] = $ac_log_id;
                $data1['openid'] = $fromusername;
                $data1['keywords'] = $info['command'];
                $data1['createtime'] = $time;

                $where1 = [
                    'openid' => $data1['openid'],
                    'keywords' => $data1['keywords'],
                    'createtime' => $data1['createtime'],
                ];
                $wechat_point_num = WechatPoint::where($where1)->count();
                if ($wechat_point_num == 0) {
                    $we_point_id = WechatPoint::insertGetId($data1);

                    // 增加等级积分
                    if ($rank_points > 0) {
                        Users::where('user_id', $users['user_id'])->increment('rank_points', $rank_points);
                    }
                    // 增加消费积分
                    if ($pay_points > 0) {
                        Users::where('user_id', $users['user_id'])->increment('pay_points', $pay_points);
                    }

                    return $we_point_id;
                }
            }
        }

        return false;
    }

    /**
     * 微信会员 积分扣除处理(消费积分)
     *
     * @param string $fromusername
     * @param array $info
     * @param int $point_value
     * @return bool
     */
    public function takeout_point($fromusername = '', $info = [], $point_value = 0)
    {
        $users = $this->wechatUserService->get_wechat_user_id($fromusername);
        if ($users) {

            $time = $this->timeRepository->getGmTime();

            // 扣除处理
            $user_pay_points = Users::where(['user_id' => $users['user_id']])->value('pay_points');
            // 判断用户消费积分 大于扣除消费积分
            if (intval($user_pay_points) >= intval($point_value)) {

                // 积分记录
                $data['user_id'] = $users['user_id'];
                $data['user_money'] = 0;
                $data['frozen_money'] = 0;
                $data['rank_points'] = 0;
                $data['pay_points'] = $point_value;
                $data['change_time'] = $time;
                $data['change_desc'] = $info['name'] . '积分扣除';
                $data['change_type'] = ACT_OTHER;

                // 同一时间 同一用户不能重复插入
                $where = [
                    'user_id' => $data['user_id'],
                    'change_time' => $data['change_time'],
                    'change_type' => ACT_OTHER,
                ];

                $account_log_num = AccountLog::where($where)->count();
                if ($account_log_num == 0) {
                    $ac_log_id = AccountLog::insertGetId($data);

                    // 从表记录
                    $data1['log_id'] = $ac_log_id;
                    $data1['openid'] = $fromusername;
                    $data1['keywords'] = $info['command'];
                    $data1['createtime'] = $time;

                    $where1 = [
                        'openid' => $data1['openid'],
                        'keywords' => $data1['keywords'],
                        'createtime' => $data1['createtime'],
                    ];
                    $wechat_point_num = WechatPoint::where($where1)->count();
                    if ($wechat_point_num == 0) {
                        $we_log_id = WechatPoint::insertGetId($data1);

                        // 扣除消费积分
                        if ($point_value > 0) {
                            Users::where('user_id', $users['user_id'])->where('pay_points', '>=', $point_value)->decrement('pay_points', intval($point_value));
                        }
                    }

                    return true;
                } else {
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * 中奖概率计算
     *
     * @param $proArr
     * @return int|string
     */
    public function get_rand($proArr = [])
    {
        $result = '';
        // 概率数组的总概率精度
        $proSum = array_sum($proArr);
        // 概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $result;
    }

    /**
     * 积分赠送记录数量
     * 统计当前时间减去时间间隔得到的历史时间之后赠送的次数
     * @param string $openid
     * @param string $command
     * @param int $point_interval
     * @return mixed
     */
    public function wechatPointCount($openid = '', $command = '', $point_interval = 0)
    {
        $time = $this->timeRepository->getGmTime();

        $time = $time - $point_interval;

        $num = WechatPoint::where('openid', $openid)
            ->where('keywords', $command)
            ->where('createtime', '>', $time)
            ->orderBy('createtime', 'DESC')
            ->count();

        return $num;
    }

    /**
     * 签到记录列表
     * @param string $command
     * @param int $wechat_id
     * @param string $openid
     * @param string $time_type
     * @param array $offset
     * @return array
     */
    public function wechatPointList($command = '', $wechat_id = 0, $openid = '', $time_type = '', $offset = [])
    {
        $model = WechatPoint::where('keywords', $command);

        if (!empty($openid)) {
            $model = $model->where('openid', $openid);
        }

        if (!empty($time_type) && $time_type == 'M') {
            // 当前月份时间戳
            $m = $this->thisTime($time_type);
            $month_start = $m['start_time'];
            $month_end = $m['end_time'];

            $model = $model->where('createtime', '>', $month_start)
                ->where('createtime', '<', $month_end)
                ->orderBy('createtime', 'ASC');
        }

        $total = $model->count();

        // 分页
        if (!empty($offset)) {
            if (isset($offset['start']) && !empty($offset['start'])) {
                $model = $model->offset($offset['start']);
            }
            if (isset($offset['limit']) && !empty($offset['limit'])) {
                $model = $model->limit($offset['limit']);
            }
        }

        $model = $model->get();

        $list = $model ? $model->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 今日是否签到
     * @param string $openid
     * @param string $command
     * @param int $wechat_id
     * @return bool
     */
    public function todayPointTimes($openid = '', $command = '', $wechat_id = 0)
    {
        // 签到判断 最后一次签到时间 如果有
        $condition['openid'] = $openid;
        $condition['keywords'] = $command;
        //$condition['wechat_id'] = $wechat_id;
        $result = WechatPoint::select('createtime')->where($condition)
            ->orderBy('id', 'DESC')
            ->first();
        $result = $result ? $result->toArray() : [];

        $time = $this->timeRepository->getGmTime();
        $nowtime_format = $this->timeRepository->getLocalDate('Y-m-d', $time);
        $createtime = empty($result['createtime']) ? '' : $this->timeRepository->getLocalDate('Y-m-d', $result['createtime']);

        // $result 为空 说明今天未签到 || 若存在 并且格式化时间day != 当前时间day 也说明未签到
        if (empty($result) || empty($createtime) || (!empty($createtime) && $createtime != $nowtime_format)) {
            // 今日可签到
            return true;
        } else {
            // 今日已签到 请明天再来
            return false;
        }
    }

    /**
     * 当前月份或当天开始、结束时间
     * @param string $ymd
     * @return array
     */
    public function thisTime($ymd = '')
    {
        $time = $this->timeRepository->getGmTime();

        $thismonth = $this->timeRepository->getLocalDate('m');
        $thisyear = $this->timeRepository->getLocalDate('Y');

        if ($ymd == 'M') {
            //取当前月开始、结束时间戳
            $startDay = $thisyear . '-' . $thismonth . '-1';
            $endDay = $thisyear . '-' . $thismonth . '-' . $this->timeRepository->getLocalDate('t', $this->timeRepository->getLocalStrtoTime($startDay));

            $b_time = $this->timeRepository->getLocalStrtoTime($startDay);//当前月的月初时间戳
            $e_time = $this->timeRepository->getLocalStrtoTime($endDay);//当前月的月末时间戳

            return ['start_time' => $b_time, 'end_time' => $e_time];
        }

        if ($ymd == 'D') {
            //取当天开始、结束时间戳
            $startDay = $this->timeRepository->getLocalDate('Y-m-d');
            $endDay = $this->timeRepository->getLocalDate('Y-m-d', $time + 3600 * 24);

            $b_time = $this->timeRepository->getLocalStrtoTime($startDay);//当天开始时间戳
            $e_time = $this->timeRepository->getLocalStrtoTime($endDay);//当天结束时间戳

            return ['start_time' => $b_time, 'end_time' => $e_time];
        }

        // 当前时间戳
        return ['now' => $time];
    }

    /**
     * 返回连续天数
     * @param array $day_list
     * @return int
     */
    public function continue_day($day_list = [])
    {
        // $day_list = ['2018-04-10', '2018-04-08', '2018-04-06', '2018-04-05', '2018-04-04'];

        if (empty($day_list)) {
            return 0;
        }

        $continue_day = 1;//连续天数

        $count = count($day_list);
        if ($count >= 1) {
            for ($i = 1; $i <= $count; $i++) {
                if ((abs((strtotime(date('Y-m-d')) - strtotime($day_list[$i - 1])) / 86400)) == $i) {
                    $continue_day = $i + 1;
                } else {
                    break;
                }
            }
        }

        return $continue_day;    //输出连续几天
    }

    /**
     * 赠送优惠券
     * @param string $openid
     * @param int $config_coupons_id
     * @return mixed
     */
    public function sendCoupons($openid = '', $config_coupons_id = 0)
    {
        $users = $this->wechatUserService->get_wechat_user_id($openid);
        $user_id = $users['user_id'] ?? 0;
        if ($user_id > 0) {
            $time = $this->timeRepository->getGmTime();

            //会员等级
            $user_rank = Users::where('user_id', $user_id)->value('user_rank');

            $coupons = Coupons::where('cou_id', $config_coupons_id)->where('review_status', 3)->where('cou_end_time', '>', $time)->first();
            $coupons = $coupons ? $coupons->toArray() : [];

            $cou_rank = $coupons['cou_ok_user'] ?? '';  //可以使用优惠券的rank
            $ranks = explode(",", $cou_rank);

            if (!empty($coupons) && $ranks) {
                if (in_array($user_rank, $ranks)) {
                    $num = CouponsUser::where('user_id', $user_id)->where('cou_id', $config_coupons_id)->count('cou_id');

                    // 判断是否已经领取了,并且还没有使用(根据创建优惠券时设定的每人可以领取的总张数为准,防止超额领取)
                    if (!empty($coupons['cou_user_num']) && $coupons['cou_user_num'] > $num) {
                        // 领取优惠券
                        $data = [
                            'user_id' => $user_id,
                            'cou_id' => $config_coupons_id,
                            'cou_money' => $coupons['cou_money'],
                            'uc_sn' => $time
                        ];
                        $insertGetId = CouponsUser::insertGetId($data);

                        return $insertGetId;
                    }
                }
            }
        }

        return false;
    }

}
