<?php

namespace App\Services\Cron;


use App\Models\Crons;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 计划任务
 * Class Comment
 *
 * @package App\Services
 */
class CronService
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

    /**
     * 获取计划任务信息
     * @param string $code
     * @param array $where
     * @return Crons|array
     */
    public function getCron($code = '', $where = [])
    {
        if (empty($code)) {
            return [];
        }

        $res = Crons::where('cron_code', $code);

        if (isset($where['enable'])) {
            $res = $res->where('enable', $where['enable']);
        }

        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    //是否开启下单自动发短信、邮件 start
    public function getSmsOpen()
    {
        $res = $this->getCron('sms', ['enable' => 1]);
        return $res;
    }

    //是否开启商品自动上下架
    public function getManageOpen()
    {
        $res = $this->getCron('manage');
        $crons_enable = $res['enable'] ?? '';
        return $crons_enable;
    }
}
