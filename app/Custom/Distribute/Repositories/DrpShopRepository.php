<?php

namespace App\Custom\Distribute\Repositories;

use App\Models\DrpShop;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;


class DrpShopRepository
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
     * 分销商信息
     * @param int $user_id
     * @param array $columns
     * @return array
     */
    public function drp_shop_info($user_id = 0, $columns = [])
    {
        if (empty($user_id)) {
            return [];
        }

        $model = DrpShop::where('user_id', $user_id);

        if (!empty($columns)) {
            $model = $model->select($columns);
        }

        $model = $model->first();

        $result = $model ? $model->toArray() : [];

        return $result;
    }

    /**
     * 分销商数量
     * @param string $time_type
     * @return mixed
     */
    public function drp_shop_count($time_type = '')
    {
        $model = DrpShop::where('audit', 1);

        if (!empty($time_type) && $time_type == 'D') {
            // 当前月份时间戳
            $t = $this->thisTime($time_type);
            $today_start = $t['start_time'];
            $today_end = $t['end_time'];

            $model = $model->whereBetween('create_time', [$today_start, $today_end]);
        }

        if (!empty($time_type) && $time_type == 'M') {
            // 当前月份时间戳
            $m = $this->thisTime($time_type);
            $month_start = $m['start_time'];
            $month_end = $m['end_time'];

            $model = $model->whereBetween('create_time', [$month_start, $month_end]);
        }


        $count = $model->count();

        return $count;
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


}