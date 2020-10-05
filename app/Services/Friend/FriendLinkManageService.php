<?php

namespace App\Services\Friend;

use App\Models\FriendLink;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 *
 * Class FriendLinkManageService
 * @package App\Services\Friend
 */
class FriendLinkManageService
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

    /* 获取友情链接数据列表 */
    public function getLinksList()
    {
        $filter = [];
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'link_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        /* 获得总记录数据 */
        $filter['record_count'] = FriendLink::count();

        $filter = page_and_size($filter);

        /* 获取数据 */
        $res = FriendLink::orderBy($filter['sort_by'], $filter['sort_order'])->offset($filter['start'])->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        if ($res) {
            foreach ($res as $rows) {
                if (empty($rows['link_logo'])) {
                    $rows['link_logo'] = '';
                } else {
                    if ((strpos($rows['link_logo'], 'http://') === false) && (strpos($rows['link_logo'], 'https://') === false)) {
                        $rows['link_logo'] = $this->dscRepository->getImagePath($rows['link_logo']);
                    }
                }

                $list[] = $rows;
            }
        }

        return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
