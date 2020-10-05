<?php

namespace App\Services\Magazine;

use App\Models\MailTemplates;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;


class MagazineListManageService
{
    protected $baseRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
    }

    public function getMagazine()
    {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'template_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = MailTemplates::where('type', 'magazine')->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $res = MailTemplates::where('type', 'magazine')
            ->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $magazinedb = $this->baseRepository->getToArrayGet($res);

        if ($magazinedb) {
            foreach ($magazinedb as $k => $v) {
                $magazinedb[$k]['last_modify'] = $this->timeRepository->getLocalDate('Y-m-d', $v['last_modify']);
                $magazinedb[$k]['last_send'] = $this->timeRepository->getLocalDate('Y-m-d', $v['last_send']);
            }
        }

        $arr = ['magazinedb' => $magazinedb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
