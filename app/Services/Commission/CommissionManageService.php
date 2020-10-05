<?php

namespace App\Services\Commission;

use App\Models\MerchantsServer;
use App\Models\OrderReturn;
use App\Models\SellerCommissionBill;
use App\Models\SellerNegativeBill;
use App\Models\SellerNegativeOrder;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class CommissionManageService
{
    protected $timeRepository;
    protected $baseRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
    }

    /**
     * 账单
     * 当前月的周数列表
     * @param $month Number
     * @return array
     */
    public function getWeeksList($month)
    {
        $weekinfo = [];
        $end_date = $this->timeRepository->getLocalDate('d', $this->timeRepository->getLocalStrtoTime($month . ' +1 month -1 day'));
        for ($i = 1; $i < $end_date; $i = $i + 7) {
            $w = $this->timeRepository->getLocalDate('N', $this->timeRepository->getLocalStrtoTime($month . '-' . $i));
            $weekinfo[] = [$this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime($month . '-' . $i . ' -' . ($w - 1) . ' days')), $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime($month . '-' . $i . ' +' . (7 - $w) . ' days'))];
        }
        return $weekinfo;
    }

    /**
     * 账单
     * 类型：每天
     * 生成账单列表
     *
     * @param int $seller_id
     * @param int $cycle
     * @param int $type
     * @return array
     */
    public function getBillPerDay($seller_id = 0, $cycle = 0, $type = 0)
    {
        $day_array = [];

        if ($type == 1) {
            $bill = $this->getNegativeMinmaxTime($seller_id);
        } else {
            $bill = $this->getBillMinmaxTime($seller_id, $cycle);
        }

        $mintime = 0;
        $maxtime = 0;

        if ($bill) {
            $mintime = isset($bill['min_time']) & !empty($bill['min_time']) ? $bill['min_time'] : $mintime;
            $maxtime = isset($bill['max_time']) & !empty($bill['max_time']) ? $bill['max_time'] : $maxtime;
        }

        if ($mintime && $maxtime) {
            $min_time = $this->timeRepository->getLocalDate("Y-m-d", $mintime);
            $max_time = $this->timeRepository->getLocalDate("Y-m-d", $maxtime);

            $min_time = explode("-", $min_time);
            $max_time = explode("-", $max_time);

            $min_year = intval($min_time[0]);
            $max_year = intval($max_time[0]);
            $min_month = intval($min_time[1]);
            $max_month = intval($max_time[1]);
            $min_day = intval($min_time[2]);
            $max_day = intval($max_time[2]);

            $day_number = 0;
            if ($min_year < $max_year) {
                //开始账单的时间年份比最大的账单结束时间年份要小
                $min_count = 12 - $min_month;
                if ($min_count > 0) {
                    for ($i = $min_month; $i <= 12; $i++) {

                        //获取当月天数
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $min_year);
                        if (!($i == $min_month)) {
                            $day_number += $days;
                        } else {
                            if ($i == $min_month) {
                                $min_day = $days - $min_day;
                                $day_number += $min_day;
                            }
                        }
                    }
                } else {
                    $min_month_day = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $min_month, $min_year);
                    $min_day = $min_month_day - $min_day;
                    $day_number += $min_day;
                }

                for ($i = 1; $i <= $max_month; $i++) {

                    /* 获取当月天数 */
                    $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $max_year);
                    if (!($i == $max_month)) {
                        $day_number += $days;
                    }

                    if ($i == $max_month) {
                        $day_number += $max_day;
                    }
                }
            } else {
                if ($min_month < $max_month) {
                    //开始账单的时间月份比最大的账单结束时间月份要小
                    for ($i = $min_month; $i <= $max_month; $i++) {

                        /* 获取当月天数 */
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $min_year);

                        if (!($i == $min_month || $i == $max_month)) {
                            $day_number += $days;
                        } else {
                            if ($i == $min_month) {
                                $min_day = $days - $min_day;
                                $day_number += $min_day;
                            }

                            if ($i == $max_month) {
                                $day_number += $max_day;
                            }
                        }
                    }
                } else {
                    if ($max_day > $min_day) {
                        $day_number = $max_day - $min_day - 1;
                    }
                }
            }

            if ($day_number > 0) {
                $idx = 0;
                for ($i = 1; $i <= $day_number; $i++) {
                    $bill_day = $this->timeRepository->getLocalDate("Y-m-d", $mintime + 24 * 60 * 60 * $i);
                    $bill_day_start = $bill_day . " 00:00:00";
                    $bill_day_end = $bill_day . " 23:59:59";

                    $day_start = $this->timeRepository->getLocalStrtoTime($bill_day_start);
                    $day_end = $this->timeRepository->getLocalStrtoTime($bill_day_end);

                    if ($type == 1) {
                        $bill_id = $this->getNegativeBillId($seller_id, $day_start, $day_end);
                    } else {
                        $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                    }

                    if (!$bill_id && ($mintime <= $day_start && $maxtime >= $day_end)) {
                        $day_array[$idx]['last_year_start'] = $bill_day_start;
                        $day_array[$idx]['last_year_end'] = $bill_day_end;
                    }

                    $idx++;
                }
            }
        }

        return $day_array;
    }

    /**
     * 账单
     * 类型：每周（七天）
     * 生成账单列表
     *
     * @param int $seller_id
     * @param int $cycle
     * @return array
     */
    public function getBillSevenDay($seller_id = 0, $cycle = 0)
    {
        $bill = $this->getBillMinmaxTime($seller_id, $cycle);

        $mintime = 0;
        $maxtime = 0;

        $day_array = [];
        if ($bill) {
            $mintime = isset($bill['min_time']) & !empty($bill['min_time']) ? $bill['min_time'] : $mintime;
            $maxtime = isset($bill['max_time']) & !empty($bill['max_time']) ? $bill['max_time'] : $maxtime;
        }

        $week_array = [];
        if ($mintime && $maxtime) {
            $min_time = $this->timeRepository->getLocalDate("Y-m-d", $mintime);
            $max_time = $this->timeRepository->getLocalDate("Y-m-d", $maxtime);

            $min_time = explode("-", $min_time);
            $max_time = explode("-", $max_time);

            $min_year = intval($min_time[0]);
            $max_year = intval($max_time[0]);
            $min_month = intval($min_time[1]);
            $max_month = intval($max_time[1]);

            $weeks = [];
            $min_weeks = [];
            $max_weeks = [];
            if ($min_year < $max_year) {
                $min_count = 12 - $min_month;
                for ($i = 0; $i <= $min_count; $i++) {
                    $minmonth = $min_month + $i;
                    $min_weeks[] = $this->getWeeksList($min_year . "-" . $minmonth);
                }

                for ($i = 1; $i <= $max_month; $i++) {
                    $max_weeks[] = $this->getWeeksList($max_year . "-" . $i);
                }

                if ($min_weeks && $max_weeks) {
                    $weeks = array_merge($min_weeks, $max_weeks);
                } elseif ($min_weeks) {
                    $weeks = $min_weeks;
                } elseif ($max_weeks) {
                    $weeks = $max_weeks;
                }
            } else {
                if ($min_month < $max_month) {
                    $m_count = $max_month - $min_month;
                    for ($i = 0; $i <= $m_count; $i++) {
                        $month = $min_month + $i;
                        $weeks[] = $this->getWeeksList($max_year . "-" . $month);
                    }
                }
            }

            if ($weeks) {
                $start_mintime = $mintime;
                $end_mintime = $maxtime + 6 * 24 * 3600;

                foreach ($weeks as $key => $row) {
                    foreach ($row as $keys => $rows) {
                        $start_time = $this->timeRepository->getLocalStrtoTime($rows[0]);
                        $end_time = $this->timeRepository->getLocalStrtoTime($rows[1]);

                        if ($start_mintime <= $start_time && $end_mintime >= $end_time) {
                            $week_array[] = $rows;
                        }
                    }
                }
            }


            $idx = 0;
            if ($week_array) {
                foreach ($week_array as $wkey => $wrow) {
                    $bill_day_start = $wrow[0] . " 00:00:00";
                    $bill_day_end = $wrow[1] . " 23:59:59";

                    $day_start = $this->timeRepository->getLocalStrtoTime($bill_day_start);
                    $day_end = $this->timeRepository->getLocalStrtoTime($bill_day_end);

                    $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                    if (!$bill_id && ($mintime <= $day_start && $maxtime >= $day_end)) {
                        $day_array[$idx]['last_year_start'] = $bill_day_start;
                        $day_array[$idx]['last_year_end'] = $bill_day_end;
                    }

                    $idx++;
                }
            }
        }

        return $day_array;
    }

    /**
     * 账单
     * 类型：半个月（15天）
     * 生成账单列表
     *
     * @param int $seller_id
     * @param int $cycle
     * @return array
     */
    public function getBillHalfMonth($seller_id = 0, $cycle = 0)
    {
        $bill = $this->getBillMinmaxTime($seller_id, $cycle);

        $mintime = 0;
        $maxtime = 0;

        $day_array = [];
        if ($bill) {
            $mintime = isset($bill['min_time']) & !empty($bill['min_time']) ? $bill['min_time'] : $mintime;
            $maxtime = isset($bill['max_time']) & !empty($bill['max_time']) ? $bill['max_time'] : $maxtime;
        }

        if ($mintime && $maxtime) {
            $min_time = $this->timeRepository->getLocalDate("Y-m-d", $mintime);
            $max_time = $this->timeRepository->getLocalDate("Y-m-d", $maxtime);

            $min_time = explode("-", $min_time);
            $max_time = explode("-", $max_time);

            $min_year = intval($min_time[0]);
            $max_year = intval($max_time[0]);
            $min_month = intval($min_time[1]);
            $max_month = intval($max_time[1]);

            $min_month_array = [];
            $max_month_array = [];
            if ($min_year < $max_year) {

                //开始账单的时间年份比最大的账单结束时间年份要小
                $min_count = 12 - $min_month;
                if ($min_count > 0) {
                    for ($i = $min_month; $i <= 12; $i++) {

                        //获取当月天数
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $min_year);
                        $halfMonth = intval($days / 2);

                        if ($i <= 9) {
                            $upper_start_time = $min_year . "-0" . $i . "-01" . " 00:00:00";
                            $upper_end_time = $min_year . "-0" . $i . "-" . $halfMonth . " 23:59:59";

                            $lower_start_time = $min_year . "-0" . $i . "-" . ($halfMonth + 1) . " 00:00:00";
                            $lower_end_time = $min_year . "-0" . $i . "-" . $days . " 23:59:59";
                        } else {
                            $upper_start_time = $min_year . "-" . $i . "-01" . " 00:00:00";
                            $upper_end_time = $min_year . "-" . $i . "-" . $halfMonth . " 23:59:59";

                            $lower_start_time = $min_year . "-" . $i . "-" . ($halfMonth + 1) . " 00:00:00";
                            $lower_end_time = $min_year . "-" . $i . "-" . $days . " 23:59:59";
                        }

                        $min_month_array[] = [
                            'upper' => [
                                'start_time' => $upper_start_time,
                                'end_time' => $upper_end_time
                            ],
                            'lower' => [
                                'start_time' => $lower_start_time,
                                'end_time' => $lower_end_time
                            ]
                        ];
                    }
                } else {
                    $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $min_month, $min_year);
                    $halfMonth = intval($days / 2);

                    $upper_start_time = $min_year . "-12-01" . " 00:00:00";
                    $upper_end_time = $min_year . "-12-" . $halfMonth . " 23:59:59";

                    $lower_start_time = $min_year . "-12-" . ($halfMonth + 1) . " 00:00:00";
                    $lower_end_time = $min_year . "-12-" . $days . " 23:59:59";

                    $min_month_array[] = [
                        'upper' => [
                            'start_time' => $upper_start_time,
                            'end_time' => $upper_end_time
                        ],
                        'lower' => [
                            'start_time' => $lower_start_time,
                            'end_time' => $lower_end_time
                        ]
                    ];
                }

                for ($i = 1; $i <= $max_month; $i++) {

                    /* 获取当月天数 */
                    $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $max_year);
                    $halfMonth = intval($days / 2);

                    if ($i <= 9) {
                        $upper_start_time = $max_year . "-0" . $i . "-01" . " 00:00:00";
                        $upper_end_time = $max_year . "-0" . $i . "-" . $halfMonth . " 23:59:59";

                        $lower_start_time = $max_year . "-0" . $i . "-" . ($halfMonth + 1) . " 00:00:00";
                        $lower_end_time = $max_year . "-0" . $i . "-" . $days . " 23:59:59";
                    } else {
                        $upper_start_time = $max_year . "-" . $i . "-01" . " 00:00:00";
                        $upper_end_time = $max_year . "-" . $i . "-" . $halfMonth . " 23:59:59";

                        $lower_start_time = $max_year . "-" . $i . "-" . ($halfMonth + 1) . " 00:00:00";
                        $lower_end_time = $max_year . "-" . $i . "-" . $days . " 23:59:59";
                    }

                    $max_month_array[] = [
                        'upper' => [
                            'start_time' => $upper_start_time,
                            'end_time' => $upper_end_time
                        ],
                        'lower' => [
                            'start_time' => $lower_start_time,
                            'end_time' => $lower_end_time
                        ]
                    ];
                }

                $month_list = [];
                if ($min_month_array && $max_month_array) {
                    $month_list = array_merge($min_month_array, $max_month_array);
                } elseif ($min_month_array) {
                    $month_list = $min_month_array;
                } elseif ($max_month_array) {
                    $month_list = $max_month_array;
                }

                if ($month_list) {
                    foreach ($month_list as $key => $row) {
                        $upper_day_start = $this->timeRepository->getLocalStrtoTime($row['upper']['start_time']);
                        $upper_day_end = $this->timeRepository->getLocalStrtoTime($row['upper']['end_time']);

                        $lower_day_start = $this->timeRepository->getLocalStrtoTime($row['lower']['start_time']);
                        $lower_day_end = $this->timeRepository->getLocalStrtoTime($row['lower']['end_time']);

                        $upper_id = $this->getBillId($seller_id, $cycle, $upper_day_start, $upper_day_end);
                        if (!$upper_id && ($mintime <= $upper_day_start && $maxtime >= $upper_day_end)) {
                            $upper_array['last_year_start'] = $row['upper']['start_time'];
                            $upper_array['last_year_end'] = $row['upper']['end_time'];

                            array_push($day_array, $upper_array);
                        }

                        $lower_id = $this->getBillId($seller_id, $cycle, $lower_day_start, $lower_day_end);
                        if (!$lower_id && ($mintime <= $lower_day_start && $maxtime >= $lower_day_end)) {
                            $lower_array['last_year_start'] = $row['lower']['start_time'];
                            $lower_array['last_year_end'] = $row['lower']['end_time'];

                            array_push($day_array, $lower_array);
                        }
                    }
                }
            } else {
                if ($min_month < $max_month) {
                    $month_array = [];

                    //开始账单的时间月份比最大的账单结束时间月份要小
                    for ($i = $min_month; $i <= $max_month; $i++) {

                        /* 获取当月天数 */
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $min_year);
                        $halfMonth = intval($days / 2);

                        if ($i <= 9) {
                            $upper_start_time = $min_year . "-0" . $i . "-01" . " 00:00:00";
                            $upper_end_time = $min_year . "-0" . $i . "-" . $halfMonth . " 23:59:59";

                            $lower_start_time = $min_year . "-0" . $i . "-" . ($halfMonth + 1) . " 00:00:00";
                            $lower_end_time = $min_year . "-0" . $i . "-" . $days . " 23:59:59";
                        } else {
                            $upper_start_time = $min_year . "-" . $i . "-01" . " 00:00:00";
                            $upper_end_time = $min_year . "-" . $i . "-" . $halfMonth . " 23:59:59";

                            $lower_start_time = $min_year . "-" . $i . "-" . ($halfMonth + 1) . " 00:00:00";
                            $lower_end_time = $min_year . "-" . $i . "-" . $days . " 23:59:59";
                        }

                        $month_array[] = [
                            'upper' => [
                                'start_time' => $upper_start_time,
                                'end_time' => $upper_end_time
                            ],
                            'lower' => [
                                'start_time' => $lower_start_time,
                                'end_time' => $lower_end_time
                            ]
                        ];
                    }

                    if ($month_array) {
                        foreach ($month_array as $key => $row) {
                            $upper_day_start = $this->timeRepository->getLocalStrtoTime($row['upper']['start_time']);
                            $upper_day_end = $this->timeRepository->getLocalStrtoTime($row['upper']['end_time']);

                            $lower_day_start = $this->timeRepository->getLocalStrtoTime($row['lower']['start_time']);
                            $lower_day_end = $this->timeRepository->getLocalStrtoTime($row['lower']['end_time']);

                            $upper_id = $this->getBillId($seller_id, $cycle, $upper_day_start, $upper_day_end);
                            if (!$upper_id && ($mintime <= $upper_day_start && $maxtime >= $upper_day_end)) {
                                $upper_array['last_year_start'] = $row['upper']['start_time'];
                                $upper_array['last_year_end'] = $row['upper']['end_time'];

                                array_push($day_array, $upper_array);
                            }

                            $lower_id = $this->getBillId($seller_id, $cycle, $lower_day_start, $lower_day_end);
                            if (!$lower_id && ($mintime <= $lower_day_start && $maxtime >= $lower_day_end)) {
                                $lower_array['last_year_start'] = $row['lower']['start_time'];
                                $lower_array['last_year_end'] = $row['lower']['end_time'];

                                array_push($day_array, $lower_array);
                            }
                        }
                    }
                }
            }
        }

        return $day_array;
    }

    /**
     * 账单
     * 类型：按月
     * 生成账单列表
     *
     * @param int $seller_id
     * @param int $cycle
     * @return array
     */
    public function getBillOneMonth($seller_id = 0, $cycle = 0)
    {
        $bill = $this->getBillMinmaxTime($seller_id, $cycle);

        $mintime = 0;
        $maxtime = 0;

        $day_array = [];
        if ($bill) {
            $mintime = isset($bill['min_time']) & !empty($bill['min_time']) ? $bill['min_time'] : $mintime;
            $maxtime = isset($bill['max_time']) & !empty($bill['max_time']) ? $bill['max_time'] : $maxtime;
        }

        if ($mintime && $maxtime) {
            $min_time = $this->timeRepository->getLocalDate("Y-m-d", $mintime);
            $max_time = $this->timeRepository->getLocalDate("Y-m-d", $maxtime);

            $min_time = explode("-", $min_time);
            $max_time = explode("-", $max_time);

            $min_year = intval($min_time[0]);
            $max_year = intval($max_time[0]);
            $min_month = intval($min_time[1]);
            $max_month = intval($max_time[1]);

            if ($min_year < $max_year) {

                //开始账单的时间年份比最大的账单结束时间年份要小
                $iidx = 0;
                $min_array = [];
                $min_count = 12 - $min_month;
                if ($min_count > 0) {
                    for ($i = $min_month; $i <= 12; $i++) {

                        /* 获取当月天数 */
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $min_year);

                        $nowMonth = $i;
                        if ($nowMonth <= 9) {
                            $nowMonth = "0" . $nowMonth;
                        }

                        $last_year_start = $min_year . "-" . $nowMonth . "-01 00:00:00"; //上一个月的第一天
                        $last_year_end = $min_year . "-" . $nowMonth . "-" . $days . " 23:59:59"; //上一个月的最后一天

                        $day_start = $this->timeRepository->getLocalStrtoTime($last_year_start);
                        $day_end = $this->timeRepository->getLocalStrtoTime($last_year_end);

                        $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                        if (!$bill_id && ($mintime <= $day_start && $maxtime >= $day_end)) {
                            $min_array[$iidx]['last_year_start'] = $last_year_start;
                            $min_array[$iidx]['last_year_end'] = $last_year_end;
                        }

                        $iidx++;
                    }
                } else {
                    $last_year_start = $min_year . "-12-01 00:00:00"; //上一个月的第一天
                    $last_year_end = $min_year . "-12-31 23:59:59"; //上一个月的最后一天
                    $day_start = $this->timeRepository->getLocalStrtoTime($last_year_start);
                    $day_end = $this->timeRepository->getLocalStrtoTime($last_year_end);

                    $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                    if (!$bill_id && ($mintime <= $day_start && $maxtime >= $day_end)) {
                        $min_array[$iidx]['last_year_start'] = $last_year_start;
                        $min_array[$iidx]['last_year_end'] = $last_year_end;
                    }
                }

                $aidx = 0;
                $max_array = [];
                for ($i = 1; $i <= $max_month; $i++) {

                    /* 获取当月天数 */
                    $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $max_year);

                    $nowMonth = $i;
                    if ($nowMonth <= 9) {
                        $nowMonth = "0" . $nowMonth;
                    }

                    $last_year_start = $max_year . "-" . $nowMonth . "-01 00:00:00"; //上一个月的第一天
                    $last_year_end = $max_year . "-" . $nowMonth . "-" . $days . " 23:59:59"; //上一个月的最后一天

                    $day_start = $this->timeRepository->getLocalStrtoTime($last_year_start);
                    $day_end = $this->timeRepository->getLocalStrtoTime($last_year_end);

                    $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                    if (!$bill_id && ($mintime <= $day_start && $maxtime >= $day_end)) {
                        $max_array[$aidx]['last_year_start'] = $last_year_start;
                        $max_array[$aidx]['last_year_end'] = $last_year_end;
                    }

                    $aidx++;
                }

                if ($min_array && $max_array) {
                    $day_array = array_merge($min_array, $max_array);
                } elseif ($min_array) {
                    $day_array = $min_array;
                } elseif ($max_array) {
                    $day_array = $max_array;
                }
            } else {
                if ($min_month < $max_month) {
                    $idx = 0;
                    //开始账单的时间月份比最大的账单结束时间月份要小
                    for ($i = $min_month; $i <= $max_month; $i++) {
                        /* 获取当月天数 */
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $min_year);

                        $nowMonth = $i;
                        if ($nowMonth <= 9) {
                            $nowMonth = "0" . $nowMonth;
                        }

                        $last_year_start = $min_year . "-" . $nowMonth . "-01 00:00:00"; //上一个月的第一天
                        $last_year_end = $min_year . "-" . $nowMonth . "-" . $days . " 23:59:59"; //上一个月的最后一天

                        $day_start = $this->timeRepository->getLocalStrtoTime($last_year_start);
                        $day_end = $this->timeRepository->getLocalStrtoTime($last_year_end);

                        $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                        if (!$bill_id && ($mintime <= $day_start && $maxtime >= $day_end)) {
                            $day_array[$idx]['last_year_start'] = $last_year_start;
                            $day_array[$idx]['last_year_end'] = $last_year_end;
                        }

                        $idx++;
                    }
                }
            }
        }

        return $day_array;
    }

    /**
     * 账单
     * 类型：季度
     * 生成账单列表
     *
     * @param int $seller_id
     * @param int $cycle
     * @return array
     */
    public function getBillQuarter($seller_id = 0, $cycle = 0)
    {
        $bill = $this->getBillMinmaxTime($seller_id, $cycle);

        $mintime = 0;
        $maxtime = 0;

        $day_array = [];
        if ($bill) {
            $mintime = isset($bill['min_time']) & !empty($bill['min_time']) ? $bill['min_time'] : $mintime;
            $maxtime = isset($bill['max_time']) & !empty($bill['max_time']) ? $bill['max_time'] : $maxtime;
        }

        if ($mintime && $maxtime) {
            $min_time = $this->timeRepository->getLocalDate("Y-m-d", $mintime);
            $max_time = $this->timeRepository->getLocalDate("Y-m-d", $maxtime);

            $min_time = explode("-", $min_time);
            $max_time = explode("-", $max_time);

            $min_year = intval($min_time[0]);
            $max_year = intval($max_time[0]);
            $min_month = intval($min_time[1]);
            $max_month = intval($max_time[1]);

            if ($min_year < $max_year) {

                //开始账单的时间年份比最大的账单结束时间年份要小
                $iidx = 0;
                $min_array = [];
                $min_count = 12 - $min_month;
                if ($min_count > 0) {
                    for ($i = $min_month; $i <= 12; $i++) {
                        $nowMonth = $i;
                        $month_year = $this->getMonthYear($nowMonth, $min_year);
                        if ($month_year && $month_year['last_year_start'] && $month_year['last_year_end']) {
                            $day_start = $this->timeRepository->getLocalStrtoTime($month_year['last_year_start']);
                            $day_end = $this->timeRepository->getLocalStrtoTime($month_year['last_year_end']);

                            $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                            if (!$bill_id && ($mintime < $day_start && $maxtime > $day_end)) {
                                $min_array[$month_year['quarter']]['last_year_start'] = $month_year['last_year_start'];
                                $min_array[$month_year['quarter']]['last_year_end'] = $month_year['last_year_end'];
                            }
                        }

                        $iidx++;
                    }
                } else {
                    $month_year = $this->getMonthYear(12, $min_year);
                    if ($month_year && $month_year['last_year_start'] && $month_year['last_year_end']) {
                        $day_start = $this->timeRepository->getLocalStrtoTime($month_year['last_year_start']);
                        $day_end = $this->timeRepository->getLocalStrtoTime($month_year['last_year_end']);

                        $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                        if (!$bill_id && ($mintime < $day_start && $maxtime > $day_end)) {
                            $min_array[$month_year['quarter']]['last_year_start'] = $month_year['last_year_start'];
                            $min_array[$month_year['quarter']]['last_year_end'] = $month_year['last_year_end'];
                        }
                    }
                }

                $aidx = 0;
                $max_array = [];
                for ($i = 1; $i <= $max_month; $i++) {
                    $nowMonth = $i;
                    $month_year = $this->getMonthYear($nowMonth, $max_year);
                    if ($month_year && $month_year['last_year_start'] && $month_year['last_year_end']) {
                        $day_start = $this->timeRepository->getLocalStrtoTime($month_year['last_year_start']);
                        $day_end = $this->timeRepository->getLocalStrtoTime($month_year['last_year_end']);

                        $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                        if (!$bill_id && ($mintime < $day_start && $maxtime > $day_end)) {
                            $max_array[$month_year['quarter']]['last_year_start'] = $month_year['last_year_start'];
                            $max_array[$month_year['quarter']]['last_year_end'] = $month_year['last_year_end'];
                        }
                    }

                    $aidx++;
                }

                if ($min_array && $max_array) {
                    $day_array = array_merge($min_array, $max_array);
                } elseif ($min_array) {
                    $day_array = $min_array;
                } elseif ($max_array) {
                    $day_array = $max_array;
                }
            } else {
                if ($min_month < $max_month) {
                    $idx = 0;
                    //开始账单的时间月份比最大的账单结束时间月份要小
                    for ($i = $min_month; $i <= $max_month; $i++) {
                        $nowMonth = $i;
                        $month_year = $this->getMonthYear($nowMonth, $max_year);
                        if ($month_year && $month_year['last_year_start'] && $month_year['last_year_end']) {
                            $day_start = $this->timeRepository->getLocalStrtoTime($month_year['last_year_start']);
                            $day_end = $this->timeRepository->getLocalStrtoTime($month_year['last_year_end']);

                            $bill_id = $this->getBillId($seller_id, $cycle, $day_start, $day_end);
                            if (!$bill_id && ($mintime < $day_start && $maxtime > $day_end)) {
                                $day_array[$month_year['quarter']]['last_year_start'] = $month_year['last_year_start'];
                                $day_array[$month_year['quarter']]['last_year_end'] = $month_year['last_year_end'];
                            }
                        }

                        $idx++;
                    }
                }
            }
        }

        return $day_array;
    }

    /**
     * 获取季度月份范围
     *
     * @param int $nowMonth
     * @param int $nowYear
     * @return array
     */
    public function getMonthYear($nowMonth = 0, $nowYear = 0)
    {
        if ($nowMonth == 1 && $nowMonth <= 3) {
            /* 当前第一季度时间段 */
            $last_year_start = $nowYear . "-01-01 00:00:00"; //当前第一季度开始的第一天
            $last_year_end = $nowYear . "-03-31 23:59:59";   //当前第一季度结束的最后一天

            $quarter = 1;
        } elseif ($nowMonth > 3 && $nowMonth <= 6) {
            /* 当前第二季度时间段 */
            $last_year_start = $nowYear . "-04-01 00:00:00"; //当前第二季度开始的第一天
            $last_year_end = $nowYear . "-06-30 23:59:59";   //当前第二季度结束的最后一天

            $quarter = 2;
        } elseif ($nowMonth > 6 && $nowMonth <= 9) {
            /* 当前第三季度时间段 */
            $last_year_start = $nowYear . "-07-01 00:00:00"; //当前第三季度开始的第一天
            $last_year_end = $nowYear . "-09-30 23:59:59";   //当前第三季度结束的最后一天

            $quarter = 3;
        } elseif ($nowMonth > 9 && $nowMonth <= 12) {
            /* 当前第四季度时间段 */
            $last_year_start = $nowYear . "-10-01 00:00:00"; //当前第四季度开始的第一天
            $last_year_end = $nowYear . "-12-31 23:59:59";   //当前第四季度结束的最后一天

            $quarter = 4;
        }

        $arr = [
            'last_year_start' => $last_year_start,
            'last_year_end' => $last_year_end,
            'quarter' => $quarter
        ];

        return $arr;
    }

    /**
     * 账单
     * 类型：半年（6个月）
     * 生成账单列表
     *
     * @param int $seller_id
     * @param int $cycle
     * @return array
     */
    public function getBillHalfYear($seller_id = 0, $cycle = 0)
    {
        $bill = $this->getBillMinmaxTime($seller_id, $cycle);

        $mintime = 0;
        $maxtime = 0;

        $day_array = [];
        if ($bill) {
            $mintime = isset($bill['min_time']) & !empty($bill['min_time']) ? $bill['min_time'] : $mintime;
            $maxtime = isset($bill['max_time']) & !empty($bill['max_time']) ? $bill['max_time'] : $maxtime;
        }

        if ($mintime && $maxtime) {
            $min_time = $this->timeRepository->getLocalDate("Y-m-d", $mintime);
            $max_time = $this->timeRepository->getLocalDate("Y-m-d", $maxtime);

            $min_time = explode("-", $min_time);
            $max_time = explode("-", $max_time);

            $min_year = intval($min_time[0]);
            $max_year = intval($max_time[0]);

            $year_array = [];
            if ($min_year < $max_year) {
                $year_count = $max_year - $min_year;
                for ($i = 0; $i <= $year_count; $i++) {
                    $year = $min_year + $i;

                    $upper_year_start = $year . "-01-01 00:00:00";
                    $upper_year_end = $year . "-06-30 23:59:59";

                    $upper = [
                        'start_time' => $upper_year_start,
                        'end_time' => $upper_year_end
                    ];

                    $year_array[$i]['upper'] = $upper;

                    $lower_year_start = $year . "-07-01 00:00:00";
                    $lower_year_end = $year . "-12-31 23:59:59";

                    $lower = [
                        'start_time' => $lower_year_start,
                        'end_time' => $lower_year_end
                    ];

                    $year_array[$i]['lower'] = $lower;
                }
            }

            if ($year_array) {
                foreach ($year_array as $key => $row) {
                    $upper_day_start = $this->timeRepository->getLocalStrtoTime($row['upper']['start_time']);
                    $upper_day_end = $this->timeRepository->getLocalStrtoTime($row['upper']['end_time']);

                    $lower_day_start = $this->timeRepository->getLocalStrtoTime($row['lower']['start_time']);
                    $lower_day_end = $this->timeRepository->getLocalStrtoTime($row['lower']['end_time']);

                    $upper_id = $this->getBillId($seller_id, $cycle, $upper_day_start, $upper_day_end);
                    if (!$upper_id && ($mintime <= $upper_day_start && $maxtime >= $upper_day_end)) {
                        $upper_array['last_year_start'] = $row['upper']['start_time'];
                        $upper_array['last_year_end'] = $row['upper']['end_time'];

                        array_push($day_array, $upper_array);
                    }

                    $lower_id = $this->getBillId($seller_id, $cycle, $lower_day_start, $lower_day_end);
                    if (!$lower_id && ($mintime <= $lower_day_start && $maxtime >= $lower_day_end)) {
                        $lower_array['last_year_start'] = $row['lower']['start_time'];
                        $lower_array['last_year_end'] = $row['lower']['end_time'];

                        array_push($day_array, $lower_array);
                    }
                }
            }
        }

        return $day_array;
    }

    /**
     * 账单
     * 类型：按年
     * 生成账单列表
     *
     * @param int $seller_id
     * @param int $cycle
     * @return array
     */
    public function getBillOneYear($seller_id = 0, $cycle = 0)
    {
        $bill = $this->getBillMinmaxTime($seller_id, $cycle);

        $mintime = 0;
        $maxtime = 0;

        $day_array = [];
        if ($bill) {
            $mintime = isset($bill['min_time']) & !empty($bill['min_time']) ? $bill['min_time'] : $mintime;
            $maxtime = isset($bill['max_time']) & !empty($bill['max_time']) ? $bill['max_time'] : $maxtime;
        }

        if ($mintime && $maxtime) {
            $min_time = $this->timeRepository->getLocalDate("Y-m-d", $mintime);
            $max_time = $this->timeRepository->getLocalDate("Y-m-d", $maxtime);

            $min_time = explode("-", $min_time);
            $max_time = explode("-", $max_time);

            $min_year = intval($min_time[0]);
            $max_year = intval($max_time[0]);

            $year_array = [];
            if ($min_year < $max_year) {
                $year_count = $max_year - $min_year;
                for ($i = 0; $i <= $year_count; $i++) {
                    $year = $min_year + $i;

                    $year_start = $year . "-01-01 00:00:00";
                    $year_end = $year . "-12-31 23:59:59";

                    $year_array[$i]['last_year_start'] = $year_start;
                    $year_array[$i]['last_year_end'] = $year_end;
                }
            }

            if ($year_array) {
                foreach ($year_array as $key => $row) {
                    $year_start = $this->timeRepository->getLocalStrtoTime($row['last_year_start']);
                    $year_end = $this->timeRepository->getLocalStrtoTime($row['last_year_end']);

                    $bill_id = $this->getBillId($seller_id, $cycle, $year_start, $year_end);
                    if (!$bill_id && ($mintime < $year_start && $maxtime > $year_end)) {
                        $day_array[$key]['last_year_start'] = $row['last_year_start'];
                        $day_array[$key]['last_year_end'] = $row['last_year_end'];
                    }
                }
            }
        }

        return $day_array;
    }

    /**
     * 账单
     * 类型：按天数
     * 生成账单列表
     *
     * @param int $seller_id
     * @param int $cycle
     * @return array
     */
    public function getBillDaysNumber($seller_id = 0, $cycle = 0)
    {
        $bill = $this->getBillMinmaxTime($seller_id, $cycle);

        $mintime = 0;
        $maxtime = 0;

        $day_array = [];
        if ($bill) {
            $mintime = isset($bill['min_time']) & !empty($bill['min_time']) ? $bill['min_time'] : $mintime;
            $maxtime = isset($bill['max_time']) & !empty($bill['max_time']) ? $bill['max_time'] : $maxtime;
        }

        if ($mintime && $maxtime) {
            $min_time = $this->timeRepository->getLocalDate("Y-m-d", $mintime);
            $max_time = $this->timeRepository->getLocalDate("Y-m-d", $maxtime);

            $min_time = explode("-", $min_time);
            $max_time = explode("-", $max_time);

            $min_year = intval($min_time[0]);
            $max_year = intval($max_time[0]);
            $min_month = intval($min_time[1]);
            $max_month = intval($max_time[1]);
            $min_day = intval($min_time[2]);
            $max_day = intval($max_time[2]);

            $day_number = 0;
            $server_day_number = MerchantsServer::where('user_id', $seller_id)->value('day_number');

            $year_array = [];
            if ($min_year < $max_year) {

                //开始账单的时间年份比最大的账单结束时间年份要小
                $min_count = 12 - $min_month;
                if ($min_count > 0) {
                    for ($i = $min_month; $i <= 12; $i++) {

                        //获取当月天数
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $min_year);
                        if (!($i == $min_month)) {
                            $day_number += $days;
                        } else {
                            if ($i == $min_month) {
                                $minDay = $days - $min_day;
                                $day_number += $minDay;
                            }
                        }
                    }
                } else {
                    $min_month_day = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $min_month, $min_year);
                    $minDay = $min_month_day - $min_day;
                    $day_number += $minDay;
                }

                for ($i = 1; $i <= $max_month; $i++) {

                    /* 获取当月天数 */
                    $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $i, $max_year);
                    if (!($i == $max_month)) {
                        $day_number += $days;
                    }

                    if ($i == $max_month) {
                        $maxday = $max_day;
                        $day_number += $maxday;
                    }
                }

                if ($day_number && $server_day_number && $day_number > $server_day_number) {
                    $number = round($day_number / $server_day_number);

                    for ($i = 0; $i <= $number; $i++) {
                        $year_start = $this->timeRepository->getLocalDate("Y-m-d", $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate("Y-m-d", $bill['min_time'])) + (($i + 1) * ($server_day_number - 1) - ($server_day_number) + 1) * 24 * 60 * 60);
                        $year_end = $this->timeRepository->getLocalDate("Y-m-d", $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate("Y-m-d", $bill['min_time'])) + ($i + 1) * ($server_day_number - 1) * 24 * 60 * 60);

                        $year_start = $year_start . " 00:00:00";
                        $year_end = $year_end . " 23:59:59";

                        $year_array[$i]['last_year_start'] = $year_start;
                        $year_array[$i]['last_year_end'] = $year_end;
                    }
                }
            } else {
                if ($min_month < $max_month) {
                    $m_count = $max_month - $min_month;
                    for ($i = 0; $i <= $m_count; $i++) {
                        $month = $min_month + $i;

                        /* 获取当月天数 */
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $month, $min_year);

                        if (!($month == $min_month || $month == $max_month)) {
                            $day_number += $days;
                        } else {
                            if ($month == $min_month) {
                                $minDay = $days - $min_day;
                                $day_number += $minDay;
                            }

                            if ($month == $max_month) {
                                $maxday = $max_day;
                                $day_number += $maxday;
                            }
                        }
                    }

                    if ($day_number && $server_day_number && $day_number > $server_day_number) {
                        $number = round($day_number / $server_day_number);

                        for ($i = 0; $i <= $number; $i++) {
                            $year_start = $this->timeRepository->getLocalDate("Y-m-d", $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate("Y-m-d", $bill['min_time'])) + (($i + 1) * ($server_day_number - 1) - ($server_day_number) + 1) * 24 * 60 * 60);
                            $year_end = $this->timeRepository->getLocalDate("Y-m-d", $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate("Y-m-d", $bill['min_time'])) + ($i + 1) * ($server_day_number - 1) * 24 * 60 * 60);

                            $year_start = $year_start . " 00:00:00";
                            $year_end = $year_end . " 23:59:59";

                            $year_array[$i]['last_year_start'] = $year_start;
                            $year_array[$i]['last_year_end'] = $year_end;
                        }
                    }
                }
            }

            if ($year_array) {
                foreach ($year_array as $key => $row) {
                    $year_start = $this->timeRepository->getLocalStrtoTime($row['last_year_start']);
                    $year_end = $this->timeRepository->getLocalStrtoTime($row['last_year_end']);

                    $bill_id = $this->getBillId($seller_id, $cycle, $year_start, $year_end);
                    if (!$bill_id && ($mintime < $year_start && $maxtime > $year_end)) {
                        $day_array[$key]['last_year_start'] = $row['last_year_start'];
                        $day_array[$key]['last_year_end'] = $row['last_year_end'];
                    }
                }
            }
        }

        return $day_array;
    }

    /**
     * 账单
     * 获取商家账单最小开始时间
     * 获取商家账单最大结束时间
     *
     * @param int $seller_id
     * @param int $cycle
     * @return mixed
     */
    public function getBillMinmaxTime($seller_id = 0, $cycle = 0)
    {
        $bill = SellerCommissionBill::selectRaw("MIN(start_time) AS min_time, MAX(end_time) AS max_time")
            ->where('seller_id', $seller_id)
            ->where('bill_cycle', $cycle);

        $bill = $this->baseRepository->getToArrayFirst($bill);

        return $bill;
    }

    /**
     * 账单ID
     * 按类型，根据开始时间和结束时间
     *
     * @param $seller_id
     * @param $cycle
     * @param $day_start
     * @param $day_end
     * @return int
     */
    public function getBillId($seller_id, $cycle, $day_start, $day_end)
    {
        $id = SellerCommissionBill::where('start_time', $day_start)
            ->where('end_time', $day_end)
            ->where('bill_cycle', $cycle)
            ->where('seller_id', $seller_id)
            ->value('id');
        $id = $id ? $id : 0;

        return $id;
    }

    /**
     * 负账单ID
     *
     * @param int $seller_id
     * @param int $day_start
     * @param int $day_end
     * @return int
     */
    public function getNegativeBillId($seller_id = 0, $day_start = 0, $day_end = 0)
    {
        $id = SellerNegativeBill::where('start_time', $day_start)
            ->where('end_time', $day_end)
            ->where('seller_id', $seller_id)
            ->value('id');
        $id = $id ? $id : 0;

        return $id;
    }

    /**
     * 账单
     * 获取商家账单最小开始时间
     * 获取商家账单最大结束时间
     *
     * @param int $seller_id
     * @return mixed
     */
    public function getNegativeMinmaxTime($seller_id = 0)
    {
        $bill = SellerNegativeBill::selectRaw('MIN(start_time) AS min_time, MAX(end_time) AS max_time')->where('seller_id', $seller_id);
        $bill = $this->baseRepository->getToArrayFirst($bill);

        return $bill;
    }

    /**
     * 负账单
     *
     * @param $seller_id
     */
    public function negativeBill($seller_id = 0)
    {
        /* 每天出负账单 start */
        $day_array = $this->getBillPerDay($seller_id, 0, 1);

        if (empty($day_array)) {
            $last_year_start = $this->timeRepository->getLocalDate("Y-m-d 00:00:00", $this->timeRepository->getLocalStrtoTime("-1 day"));
            $last_year_end = $this->timeRepository->getLocalDate("Y-m-d 23:59:59", $this->timeRepository->getLocalStrtoTime("-1 day"));
            $day_array[0]['last_year_start'] = $last_year_start;
            $day_array[0]['last_year_end'] = $last_year_end;
        }

        if ($day_array) {
            foreach ($day_array as $keys => $rows) {
                $last_year_start = $this->timeRepository->getLocalStrtoTime($rows['last_year_start']); //时间戳
                $last_year_end = $this->timeRepository->getLocalStrtoTime($rows['last_year_end']); //时间戳

                $bill_count = SellerNegativeBill::where('seller_id', $seller_id)
                    ->where('start_time', '>=', $last_year_start)
                    ->where('end_time', '<=', $last_year_end)
                    ->count();

                if ($bill_count <= 0) {

                    $bill_sn = $this->getBillOrderSn();

                    $other = [
                        'seller_id' => $seller_id,
                        'bill_sn' => $bill_sn,
                        'start_time' => $last_year_start,
                        'end_time' => $last_year_end,
                    ];

                    $negative_id = SellerNegativeBill::insertGetId($other);

                    $negative_order_list = OrderReturn::where('refound_status', 1)
                        ->whereIn('return_type', [1, 3])
                        ->where('negative_id', 0);

                    $negative_order_list = $negative_order_list->whereHas('orderInfo', function ($query) use ($seller_id) {
                        $query->where('ru_id', $seller_id);
                    });

                    $negative_order_list = $negative_order_list->doesntHave('getSellerNegativeOrder');

                    $negative_order_list = $negative_order_list->doesntHave('getSellerBillOrderReturn');

                    $negative_order_list = $this->baseRepository->getToArrayGet($negative_order_list);

                    if ($negative_order_list) {

                        foreach ($negative_order_list as $idx => $val) {

                            $return_amount = $val['actual_return'] - $val['return_shipping_fee'] - $val['return_rate_price'];

                            $other = array(
                                'negative_id' => $negative_id,
                                'order_id' => $val['order_id'],
                                'order_sn' => $val['order_sn'],
                                'ret_id' => $val['ret_id'],
                                'return_sn' => $val['return_sn'],
                                'seller_id' => $seller_id,
                                'return_amount' => $return_amount,
                                'return_shippingfee' => $val['return_shipping_fee'],
                                'return_rate_price' => $val['return_rate_price'],
                                'add_time' => $val['return_time']
                            );

                            SellerNegativeOrder::insert($other);

                            OrderReturn::where('ret_id', $val['ret_id'])->update([
                                'negative_id' => $negative_id
                            ]);
                        }
                    }

                    SellerNegativeOrder::where('add_time', '>=', $last_year_start)
                        ->where('add_time', '<=', $last_year_end)
                        ->where('seller_id', $seller_id)
                        ->where('negative_id', 0)
                        ->where('settle_accounts', 0)
                        ->update([
                            'negative_id' => $negative_id
                        ]);

                    $negative_order = SellerNegativeOrder::selectRaw('SUM(return_amount) AS amount_total, SUM(return_shippingfee) AS shippingfee_total, SUM(return_rate_price) AS rate_total, GROUP_CONCAT(ret_id) AS ret_id')
                        ->where('negative_id', $negative_id)
                        ->where('seller_id', $seller_id);

                    $negative_order = $this->baseRepository->getToArrayFirst($negative_order);

                    if ($negative_order) {

                        $ret_id = $this->baseRepository->getExplode($negative_order['ret_id']);

                        OrderReturn::whereIn('ret_id', $ret_id)
                            ->where('negative_id', 0)
                            ->update([
                                'negative_id' => $negative_id
                            ]);

                        /* 更新负账单金额 */
                        SellerNegativeBill::where('id', $negative_id)
                            ->where('seller_id', $seller_id)
                            ->update([
                                'return_amount' => $negative_order['amount_total'],
                                'return_shippingfee' => $negative_order['shippingfee_total'],
                                'return_rate_price' => $negative_order['rate_total']
                            ]);
                    }
                }
            }
        }
        /* 每天出负账单 end */
    }

    /**
     * 得到新订单号
     *
     * @return string
     */
    public function getBillOrderSn()
    {
        $time = explode(" ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time = explode(".", $time);
        $time = isset($time[1]) ? $time[1] : 0;
        $time = $this->timeRepository->getLocalDate('YmdHis') + $time;

        /* 选择一个随机的方案 */
        mt_srand((double)microtime() * 1000000);
        return $time . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}
