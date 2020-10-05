<?php

namespace App\Services\Log;

use App\Models\AccountLog;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class AccountLogManageService
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

    /**
     * 取得帐户明细
     * @param int $user_id 用户id
     * @param string $account_type 帐户类型：空表示所有帐户，user_money表示可用资金，
     *                  frozen_money表示冻结资金，rank_points表示等级积分，pay_points表示消费积分
     * @return  array
     */
    public function getAccountList($user_id, $account_type = '')
    {
        /* 初始化分页参数 */
        $filter = [
            'user_id' => $user_id,
            'account_type' => $account_type
        ];

        /* 时间筛选 */
        $filter['start_date'] = empty($_REQUEST['start_date']) ? '' : (strpos($_REQUEST['start_date'], '-') > 0 ? $this->timeRepository->getLocalStrtoTime($_REQUEST['start_date']) : $_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? '' : (strpos($_REQUEST['end_date'], '-') > 0 ? $this->timeRepository->getLocalStrtoTime($_REQUEST['end_date']) : $_REQUEST['end_date']);

        $list = AccountLog::where('user_id', $user_id);

        if ($account_type && in_array($account_type, ['user_money', 'frozen_money', 'rank_points', 'pay_points'])) {
            $list = $list->where($account_type, '<>', 0);
        }

        if ($filter['start_date']) {
            $list = $list->where('change_time', '>=', $filter['start_date']);
        }

        if ($filter['end_date']) {
            $list = $list->where('change_time', '<=', $filter['end_date']);
        }

        $res = $record_count = $list;

        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = $record_count->count();
        $filter = page_and_size($filter);

        /* 查询记录 */
        $res = $res->orderBy('log_id', 'desc');

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $row['change_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['change_time']);
                $arr[] = $row;
            }
        }

        return ['account' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
