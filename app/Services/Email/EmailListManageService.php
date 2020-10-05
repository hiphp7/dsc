<?php

namespace App\Services\Email;


use App\Models\EmailList;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * 分销
 * Class DrpService
 * @package App\Services\Email
 */
class EmailListManageService
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function getEmailList()
    {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'stat' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = EmailList::count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */

        $res = EmailList::orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])->limit($filter['page_size']);

        $emaildb = $this->baseRepository->getToArrayGet($res);

        if ($emaildb) {
            foreach ($emaildb as $key => $val) {
                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $emaildb[$key]['email'] = $this->dscRepository->stringToStar($val['email']);
                }
            }
        }

        $arr = ['emaildb' => $emaildb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
