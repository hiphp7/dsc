<?php

namespace App\Services\Wholesale;

use App\Models\WholesalePurchase;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class WholesalePurchaseManageService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获取求购数据列表
     *
     * @return array
     */
    public function purchaseList()
    {
        /* 过滤查询 */
        $filter = array();

        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'purchase_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = WholesalePurchase::query();

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $row = $row->where('subject', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
        }

        $res = $record_count = $row;

        /* 获得总记录数据 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        /* 获得广告数据 */
        $res = $res->with([
            'getUsers'
        ]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $res = $res->skip($filter['start']);

        $res = $res->take($filter['page_size']);

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $key => $rows) {
                /* 格式化日期 */
                $rows['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $rows['add_time']);
                $rows['end_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $rows['end_time']);
                $rows['user_name'] = $rows['get_users']['user_name'] ?? '';

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $rows['user_name'] = $this->dscRepository->stringToStar($rows['user_name']);
                }

                $arr[] = $rows;
            }
        }

        return array('purchase_list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    }
}