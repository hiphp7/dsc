<?php

namespace App\Services\Cron;

use App\Libraries\Template;
use App\Models\Crons;
use App\Models\ErrorLog;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use Illuminate\Support\Str;

/**
 * 计划任务 artisan
 * Class Comment
 *
 * @package App\Services
 */
class CronArtisanService
{
    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得需要执行的计划任务数据
     * @param string $timestamp
     * @param string $refer
     * @param string $cron_code
     */
    public function cronList($timestamp = '', $refer = '', $cron_code = '')
    {
        $error_log = [];

        // 兼容加载
        if (!isset($GLOBALS['smarty'])) {
            load_helper(['function', 'base']); // 执行 artisan 发送邮件时 需要加载 base 函数 dsc_iconv
            // 兼容发送邮件
            defined('__ROOT__') or define('__ROOT__', url('/') . '/');
            defined('__PUBLIC__') or define('__PUBLIC__', asset('/assets'));
            defined('__TPL__') or define('__TPL__', asset('/themes/' . $this->config['template']));
            defined('__STORAGE__') or define('__STORAGE__', asset('/') . "storage");
            $GLOBALS['smarty'] = new Template();
        }

        $timestamp = empty($timestamp) ? $this->timeRepository->getGmTime() : $timestamp;

        $crondb = $this->get_cron_info($timestamp, $cron_code); // 获得需要执行的计划任务数据

        if ($crondb) {
            foreach ($crondb as $key => $cron_val) {
                $code = Str::studly($cron_val['cron_code']);
                $path = plugin_path('Cron/' . $code . '/' . $code . '.php');

                if (file_exists($path)) {
                    if (!empty($cron_val['allow_ip'])) { // 设置了允许ip
                        $allow_ip = explode(',', $cron_val['allow_ip']);
                        $server_ip = request()->getClientIp();
                        if (!in_array($server_ip, $allow_ip)) {
                            continue;
                        }
                    }
                    if (!empty($cron_val['minute'])) { // 设置了允许分钟段
                        $m = explode(',', $cron_val['minute']);
                        $m_now = intval($this->timeRepository->getLocalDate('i', $timestamp));
                        if (!in_array($m_now, $m)) {
                            continue;
                        }
                    }
                    if (isset($refer) && !empty($refer) && !empty($cron_val['alow_files'])) { // 设置允许调用文件
                        $f_info = parse_url($refer);
                        $f_now = basename($f_info['path']);
                        $f = explode(' ', $cron_val['alow_files']);
                        if (!in_array($f_now, $f)) {
                            continue;
                        }
                    }
                    if (!empty($cron_val['cron_config'])) {
                        foreach ($cron_val['cron_config'] as $k => $v) {
                            $cron[$v['name']] = $v['value'];
                        }
                    }
                    include_once($path);
                } else {
                    $error_log[] = $this->make_error_arr(plugin_path('Cron/' . $code . '/' . $code . '.php') . ' not found!', __FILE__, $timestamp);
                }

                $close = $cron_val['run_once'] ? 0 : 1;
                $next_time = $this->get_next_time($cron_val['cron'], $timestamp);

                Crons::where('cron_id', $cron_val['cron_id'])->update([
                    'thistime' => $timestamp,
                    'nextime' => $next_time,
                    'enable' => $close
                ]);
            }
        }

        $this->write_error_arr($error_log);
    }

    /**
     * 获得需要执行的计划任务数据
     * @param string $timestamp
     * @param string $cron_code
     * @return array
     */
    protected function get_cron_info($timestamp = '', $cron_code = '')
    {
        $crons = Crons::where('enable', 1)->where('nextime', '<', $timestamp);

        if (!empty($cron_code)) {
            $crons = $crons->where('cron_code', $cron_code);
        }

        $crons = $crons->get();

        $crons = $crons ? $crons->toArray() : [];

        $crondb = [];

        if ($crons) {
            foreach ($crons as $rt) {
                $rt['cron'] = array('day' => $rt['day'], 'week' => $rt['week'], 'm' => $rt['minute'], 'hour' => $rt['hour']);
                $rt['cron_config'] = unserialize($rt['cron_config']);
                $rt['minute'] = trim($rt['minute']);
                $rt['allow_ip'] = trim($rt['allow_ip']);
                $crondb[] = $rt;
            }
        }

        return $crondb;
    }

    /**
     * @param $cron
     * @param string $timestamp
     * @return int|string
     */
    protected function get_next_time($cron, $timestamp = '')
    {
        $y = $this->timeRepository->getLocalDate('Y', $timestamp);
        $mo = $this->timeRepository->getLocalDate('n', $timestamp);
        $d = $this->timeRepository->getLocalDate('j', $timestamp);
        $w = $this->timeRepository->getLocalDate('w', $timestamp);
        $h = $this->timeRepository->getLocalDate('G', $timestamp);
        $sh = $sm = 0;
        $sy = $y;
        if ($cron['day']) {
            $sd = $cron['day'];
            $smo = $mo + 1;
        } else {
            $sd = $d;
            $smo = $mo;
            if ($cron['week'] != '') {
                $sd += $cron['week'] - $w + 7;
            }
        }
        if ($cron['hour']) {
            $sh = $cron['hour'];
            if (empty($cron['day']) && $cron['week'] == '') {
                $sd++;
            }
        }
        //$next = gmmktime($sh,$sm,0,$smo,$sd,$sy);
        $next = $this->timeRepository->getLocalStrtoTime("$sy-$smo-$sd $sh:$sm:0");
        if ($next < $timestamp) {
            if ($cron['m']) {
                return $timestamp + 60 - intval($this->timeRepository->getLocalDate('s', $timestamp));
            } else {
                return $timestamp;
            }
        } else {
            return $next;
        }
    }

    /**
     * @param $msg
     * @param $file
     * @param $timestamp
     * @return array
     */
    protected function make_error_arr($msg, $file, $timestamp)
    {
        return ['info' => $msg, 'file' => $file, 'time' => $timestamp];
    }

    /**
     * @param $err_arr
     */
    protected function write_error_arr($err_arr = [])
    {
        if (!empty($err_arr)) {
            foreach ($err_arr as $key => $val) {
                $data = [
                    'info' => $val['info'],
                    'file' => $val['file'],
                    'time' => $val['time']
                ];
                ErrorLog::create($data);
            }
        }
    }
}
