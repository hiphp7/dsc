<?php

namespace App\Services\Cron;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 后台计划任务
 * Class Comment
 *
 * @package App\Services
 */
class CronManageService
{
    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function get_next_time($cron)
    {
        $timestamp = $this->timeRepository->getGmTime();
        $y = $this->timeRepository->getLocalDate('Y', $timestamp);
        $mo = $this->timeRepository->getLocalDate('n', $timestamp);
        $d = $this->timeRepository->getLocalDate('j', $timestamp);
        $w = $this->timeRepository->getLocalDate('w', $timestamp);
        $h = $this->timeRepository->getLocalDate('G', $timestamp);
        $sh = $sm = 0;
        $sy = $y;
        if ($cron['day']) {
            $sd = $cron['day'];
            $smo = $mo;
        } else {
            $sd = $d;
            $smo = $mo;
            if ($cron['week'] !== '') {
                $sd += $cron['week'] - $w;
            }
        }
        if ($cron['hour']) {
            $sh = $cron['hour'];
        }
        $next = $this->timeRepository->getLocalStrtoTime("$sy-$smo-$sd $sh:$sm:0");

        return $next;
    }

    public function get_minute($cron_minute)
    {
        $cron_minute = explode(',', $cron_minute);
        $cron_minute = array_unique($cron_minute);
        foreach ($cron_minute as $key => $val) {
            if ($val) {
                $val = intval($val);
                $val < 0 && $val = 0;
                $val > 59 && $val = 59;
                $cron_minute[$key] = $val;
            }
        }
        return trim(implode(',', $cron_minute));
    }

    public function get_dwh()
    {
        $days = $week = $hours = [];
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }
        for ($i = 1; $i < 8; $i++) {
            $week[$i] = $GLOBALS['_LANG']['week'][$i];
        }
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        return [$days, $week, $hours];
    }
}
