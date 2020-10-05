<?php

namespace App\Plugins\Cron\Ipdel;

use App\Models\Stats;

$cron_lang = __DIR__ . '/Languages/' . config('shop.lang') . '.php';

if (file_exists($cron_lang)) {
    require_once($cron_lang);
}

$debug = config('app.debug'); // true 开启日志 false 关闭日志

$time = $this->timeRepository->getGmTime();

$cron['ipdel_day'] = isset($cron['ipdel_day']) && empty($cron['ipdel_day']) && $cron['ipdel_day'] ? $cron['ipdel_day'] : 7;

$deltime = $time - $cron['ipdel_day'] * 3600 * 24;

Stats::where('access_time', '<', $deltime)->delete();
