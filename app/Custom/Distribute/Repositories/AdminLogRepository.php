<?php

namespace App\Custom\Distribute\Repositories;

use App\Models\AdminLog;
use App\Repositories\Common\TimeRepository;

/**
 * 管理员记录日志
 * Class AdminLogRepository
 * @package App\Custom\Distribute\Repositories
 */
class AdminLogRepository
{
    protected $timeRepository;

    public function __construct(
        TimeRepository $timeRepository
    )
    {
        $this->timeRepository = $timeRepository;
    }

    /**
     * 记录管理员的操作内容
     * @param string $sn 数据的唯一值
     * @param string $action 操作的类型
     * @param string $content 操作的内容
     * @param int $admin_id 管理员id
     * @return  void
     */
    function admin_log($sn = '', $action = '', $content = '', $admin_id = 0)
    {
        $log_info = $action . $content;
        if ($sn) {
            $log_info .= ': ' . addslashes($sn);
        }

        AdminLog::insert([
            'log_time' => $this->timeRepository->getGmTime(),
            'user_id' => $admin_id,
            'log_info' => $log_info,
            'ip_address' => request()->getClientIp()
        ]);
    }


}