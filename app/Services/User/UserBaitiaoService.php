<?php

namespace App\Services\User;


use App\Models\Baitiao;
use App\Models\BaitiaoLog;
use App\Models\BaitiaoPayLog;
use App\Models\Stages;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use Illuminate\Support\Str;

class UserBaitiaoService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $config;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 白条记录
     *
     * @param int $user_id
     * @param int $size
     * @param int $start
     * @return array
     */
    public function getBaitiaoLogList($user_id = 0, $size = 0, $start = 0)
    {
        $res = BaitiaoLog::selectRaw("*, stages_one_price * stages_total AS order_amount")
            ->where('user_id', $user_id);

        if ($size > 0) {
            if ($start > 0) {
                $res = $res->skip($start);
            }

            if ($size > 0) {
                $res = $res->take($size);
            }
        }

        $res = $res->with([
            'getOrder' => function ($query) {
                $query->select('order_id', 'order_sn', 'pay_id');
            }
        ]);

        $res = $res->orderBy('log_id', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        $bt_log = [];
        if ($res) {
            foreach ($res as $row) {
                $row['order_sn'] = $row['get_order']['order_sn'] ?? '';
                $row['pay_id'] = $row['get_order']['pay_id'] ?? 0;

                /* 查询更新支付状态 start */
                if ($row['stages_total'] && $row['is_repay'] == 0) {
                    for ($i = 1; $i <= $row['stages_total']; $i++) {
                        $pay_log_other = [
                            'baitiao_id' => $row['baitiao_id'],
                            'log_id' => $row['log_id'],
                            'stages_num' => $i,
                            'is_pay' => 0,
                        ];
                        $log_info = $this->getBaitiaoPayLogInfo($pay_log_other);

                        if ($log_info && $log_info['pay_id']) {
                            $payment = [
                                'pay_id' => $log_info['pay_id'],
                                'pay_code' => $log_info['pay_code']
                            ];

                            /* 调用相应的支付方式文件 */
                            if ($payment && strpos($payment['pay_code'], 'pay_') === false) {
                                $code = Str::studly($payment['pay_code']);
                                $pay_obj = app('\\App\\Plugins\\Payment\\' . $code . '\\' . $code);

                                if (!is_null($pay_obj)) {
                                    $is_callable = [$pay_obj, 'orderQuery'];

                                    /* 判断类对象方法是否存在 */
                                    if (is_callable($is_callable)) {
                                        $order_other = [
                                            'order_sn' => $row['order_sn'],
                                            'log_id' => $log_info['id'],
                                            'order_amount' => $row['order_amount'],
                                        ];

                                        $pay_obj->orderQuery($order_other);

                                        $baitiao_info = BaitiaoLog::where('log_id', $row['log_id']);
                                        $baitiao_info = $this->baseRepository->getToArrayFirst($baitiao_info);

                                        if ($baitiao_info) {
                                            $row['repay_date'] = $baitiao_info['repay_date'];
                                            $row['yes_num'] = $baitiao_info['yes_num'];
                                            $row['repayed_date'] = $baitiao_info['repayed_date'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                /* 查询更新支付状态 end */

                $row['stages_num'] = $row['yes_num'] + 1;
                $row['use_date'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['use_date']);

                //如果是白条分期订单,重新计算还款日期 bylu;
                if ($row['is_stages'] == 1) {
                    $repay_date = unserialize($row['repay_date']);
                    $stages_num = $row['yes_num'] + 1;
                    $row['repay_date'] = $repay_date[$stages_num] ?? $repay_date[$row['yes_num']]; //这里要+1,因为还款日期数组,是1起始,而还款期数是0起始;
                } else {
                    $row['repay_date'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['repay_date']);
                }
                if ($row['repayed_date']) {
                    $row['repayed_date'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['repayed_date']);
                }

                if ($row['pay_num'] == 0 && $row['stages_total'] > 0 && $row['is_stages'] == 1) {
                    for ($i = 1; $i <= $row['stages_total']; $i++) {
                        $count = BaitiaoPayLog::where('log_id', $row['log_id'])
                            ->where('baitiao_id', $row['baitiao_id'])
                            ->where('stages_num', $i)
                            ->count();

                        if (!$count) {
                            $pay_log_other = [
                                'log_id' => $row['log_id'],
                                'baitiao_id' => $row['baitiao_id'],
                                'stages_num' => $i,
                                'stages_price' => $row['stages_one_price'],
                                'add_time' => $this->timeRepository->getGmTime()
                            ];
                            BaitiaoPayLog::insert($pay_log_other);
                        }
                    }

                    $bt_pay_count = BaitiaoPayLog::where('log_id', $row['log_id'])->count();
                    if ($row['stages_total'] == $bt_pay_count) {
                        $baitiao_log_other['pay_num'] = 1;
                        BaitiaoLog::where('log_id', $row['log_id'])->update($baitiao_log_other);
                    }
                }

                $bt_log[] = $row;
            }
        }

        return $bt_log;
    }

    /**
     * 白条信息
     *
     * @param array $other
     * @return array
     */
    public function getBaitiaoPayLogInfo($other = [])
    {
        $row = [];
        if ($other) {
            $row = BaitiaoPayLog::whereRaw(1);

            if (isset($other['id'])) {
                $row = $row->where('id', $other['id']);
            }

            if (isset($other['baitiao_id']) && !empty($other['baitiao_id'])) {
                $row = $row->where('baitiao_id', $other['baitiao_id']);
            }

            if (isset($other['log_id']) && !empty($other['log_id'])) {
                $row = $row->where('log_id', $other['log_id']);
            }

            if (isset($other['stages_num'])) {
                $row = $row->where('stages_num', $other['stages_num']);
            }

            if (isset($other['is_pay'])) {
                $row = $row->where('is_pay', $other['is_pay']);
            }

            $row = $this->baseRepository->getToArrayFirst($row);
        }

        return $row;
    }

    /**
     * 白条信息
     *
     * @param array $other
     * @return array
     */
    public function getBaitiaoInfo($other = [])
    {
        if (empty($other)) {
            return [];
        }

        $row = Baitiao::whereRaw(1);

        if (isset($other['baitiao_id']) && !empty($other['baitiao_id'])) {
            $row = $row->where('baitiao_id', $other['baitiao_id']);
        }

        if (isset($other['user_id']) && !empty($other['user_id'])) {
            $row = $row->where('user_id', $other['user_id']);
        }

        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * 白条信息
     *
     * @param array $other
     * @return array
     */
    public function getBaitiaoLogInfo($other = [])
    {
        $row = [];
        if ($other) {
            $row = BaitiaoLog::whereRaw(1);

            if (isset($other['log_id']) && !empty($other['log_id'])) {
                $row = $row->where('log_id', $other['log_id']);
            }

            if (isset($other['order_id']) && !empty($other['order_id'])) {
                $row = $row->where('order_id', $other['order_id']);
            }

            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row) {
                if ($row['is_stages'] == 1) {
                    $repay_date = unserialize($row['repay_date']);
                    $row['repay_date'] = $repay_date[$row['yes_num'] + 1] ?? $repay_date[$row['yes_num']]; //这里要+1,因为还款日期数组,是1起始,而还款期数是0起始;
                } else {
                    $row['repay_date'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['repay_date']);
                }

                $row['format_stages_one_price'] = $this->dscRepository->getPriceFormat($row['stages_one_price'], false);
            }
        }

        return $row;
    }

    /**
     * 会员白条余额 【白条总金额 $amount 白条可用余额 $balance】
     *
     * @param int $user_id
     * @return array
     */
    public function getBaitiaoBalance($user_id = 0)
    {
        $arr = [
            'amount' => 0,
            'balance' => 0,
            'numbers' => 0,
            'stay_pay' => 0,
            'already_amount' => 0,
            'total_amount' => 0,
            'bt_info' => []
        ];

        $total_amount = 0;
        $already_amount = 0;

        $bt_other = [
            'user_id' => $user_id
        ];
        $bt_info = $this->getBaitiaoInfo($bt_other);

        if ($bt_info) {
            $baitiao_log = BaitiaoLog::selectRaw("SUM(stages_one_price * (stages_total - yes_num)) AS total_amount, SUM(stages_one_price * yes_num) AS already_amount, count(log_id) AS numbers")
                ->where('user_id', $user_id)->where('is_repay', 0)->where('is_refund', 0);

            $baitiao_log = $this->baseRepository->getToArrayFirst($baitiao_log);

            if ($baitiao_log) {
                $remain_amount = floatval($bt_info['amount']) - floatval($baitiao_log['total_amount']);
                $arr = [
                    'amount' => $bt_info['amount'],
                    'balance' => $remain_amount,
                    'numbers' => $baitiao_log['numbers'],
                    'stay_pay' => $baitiao_log['total_amount'],
                    'already_amount' => $baitiao_log['already_amount'],
                    'bt_info' => $bt_info
                ];

                $total_amount = $baitiao_log['total_amount'];
                $already_amount = $baitiao_log['already_amount'];
            }
        }

        $arr['format_stay_pay'] = $this->dscRepository->getPriceFormat($total_amount, false);
        $arr['format_already_amount'] = $this->dscRepository->getPriceFormat($already_amount, false);

        return $arr;
    }

    /**
     * 白条支付记录列表
     *
     * @param array $log_id
     * @param int $size
     * @param int $start
     * @return array
     */
    public function getBaitiaoPayLogList($log_id = [], $size = 0, $start = 0)
    {
        if (empty($log_id)) {
            return [];
        }

        $res = BaitiaoPayLog::whereIn('log_id', $log_id);

        $res = $res->with([
            'getBaitiaoLog' => function ($query) {
                $query = $query->select('log_id', 'order_id', 'is_refund');

                $query->with([
                    'getOrder' => function ($query) {
                        $query->where('main_count', 0);
                    }
                ]);
            }
        ]);

        $res = $res->orderBy('id', 'desc');

        if ($size > 0) {
            if ($start > 0) {
                $res = $res->skip($start);
            }
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $log = [];
        if ($res) {
            foreach ($res as $row) {
                $row['add_time'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['add_time']);
                $row['pay_time'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['pay_time']);

                $order = [];
                if ($row['get_baitiao_log'] && isset($row['get_baitiao_log']['get_order']) && !empty($row['get_baitiao_log']['get_order'])) {
                    $order = $row['get_baitiao_log']['get_order'];
                }

                $row['order_id'] = isset($order['order_id']) ? $order['order_id'] : 0;
                $row['order_sn'] = isset($order['order_sn']) ? $order['order_sn'] : '';
                $row['pay_id'] = isset($order['pay_id']) ? $order['pay_id'] : 0;
                $row['is_refund'] = isset($row['get_baitiao_log']['is_refund']) ? $row['get_baitiao_log']['is_refund'] : 0;
                $log[] = $row;
            }
        }

        return $log;
    }

    /**
     * 订单白条信息
     *
     * @param array $other
     * @return array
     */
    public function getStagesInfo($other = [])
    {
        if (empty($other)) {
            return [];
        }

        $row = Stages::whereRaw(1);

        if (isset($other['stages_id']) && !empty($other['stages_id'])) {
            $row = $row->where('stages_id', $other['stages_id']);
        }

        if (isset($other['order_sn']) && !empty($other['order_sn'])) {
            $row = $row->where('order_sn', $other['order_sn']);
        }

        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * 检测当前用户白条相关权限(是否授权,是否额度为0,是否逾期)
     *
     * @param null $stages_qishu 分期期数
     * @param bool $is_jiesuan 是否购物车结算入口
     * @return int
     */
    public function btAuthCheck($stages_qishu = null, $is_jiesuan = false)
    {
        $user_id = session('user_id', 0);

        $bt_info = Baitiao::where('user_id', $user_id);

        $bt_info = $bt_info->with([
            'getBaitiaoLogList' => function ($query) {
                $query = $query->where('is_repay', 0)
                    ->where('is_refund', 0);

                $query->whereHas('getOrder', function ($query) {
                    $query->where('main_count', 0);
                });
            }
        ]);

        $bt_info = $this->baseRepository->getToArrayFirst($bt_info);

        //判断当前用户是否有白条授权(未被授权不能下单,白条额度为0不能下单)
        if (!empty($stages_qishu) && $stages_qishu > -1) {

            if (empty($bt_info)) {
                return 1;
            }

            if ($bt_info['amount'] <= 0) {
                return 2;
            }
        }

        //判断是否可以下单(白条逾期就不能下单)
        if (!empty($bt_info)) {
            $over_date = $this->timeRepository->getGmTime() - (($bt_info['over_repay_trem']) * 24 * 3600);

            $list = [];

            $pay_baitiao_amount = 0;
            foreach ($bt_info['get_baitiao_log_list'] as $key => $row) {
                $list[$key] = $row;

                $list[$key]['baitiao_id'] = $bt_info['baitiao_id'];
                $list[$key]['user_id'] = $bt_info['user_id'];
                $list[$key]['amount'] = $bt_info['amount'];
                $list[$key]['repay_term'] = $bt_info['repay_term'];
                $list[$key]['over_repay_trem'] = $bt_info['over_repay_trem'];
                $list[$key]['add_time'] = $bt_info['add_time'];

                $pay_baitiao_amount += $row['stages_one_price'];
            }

            if ($pay_baitiao_amount > $bt_info['amount']) {
                return 2;
            }

            foreach ($list as $k => $val) {

                //如果是白条分期订单,重新计算最后还款日期 bylu;
                if ($val['is_stages'] == 1) {
                    $repay_date = unserialize($val['repay_date']);//数组;
                    if (count($repay_date) > 1) {
                        $repay_date = isset($repay_date[$val['yes_num'] + 1]) ? $this->timeRepository->getLocalStrtoTime($repay_date[$val['yes_num'] + 1]) : 0;//当前期预定还款时间;
                    } else {
                        $repay_date = $this->timeRepository->getLocalStrtoTime($repay_date[1]);//当前期预定还款时间;
                    }

                    $over_date = $this->timeRepository->getGmTime();//当前时间;

                    if ($repay_date > 0 && $over_date >= $repay_date) {
                        if ($is_jiesuan) {
                            return 3;
                        } else {
                            return 4;
                        }
                    }
                } else {
                    if ($over_date >= $val['repay_date']) {
                        if ($is_jiesuan) {
                            return 5;
                        } else {
                            return 6;
                        }
                    }
                }
            }
        }

        return 0;
    }

    /**
     * 所有待还款白条的总额和条数
     *
     * @param int $user_id
     * @return array
     */
    public function getRepayBt($user_id = 0)
    {
        $repay_bt = BaitiaoLog::selectRaw("SUM(stages_one_price * (stages_total - yes_num)) AS total_amount, COUNT(log_id) AS numbers, SUM(stages_one_price * yes_num) AS already_amount")
            ->where('user_id', $user_id)
            ->where('is_repay', 0)
            ->where('is_refund', 0)
            ->first();
        $repay_bt = $repay_bt ? $repay_bt->toArray() : [];

        return $repay_bt;
    }
}