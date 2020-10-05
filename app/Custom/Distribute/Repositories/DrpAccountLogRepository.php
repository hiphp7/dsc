<?php

namespace App\Custom\Distribute\Repositories;

use App\Custom\Distribute\Models\DrpAccountLog;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 分销商账户变动记录
 * Class DrpAccountLogRepository
 * @package App\Custom\Distribute\Repositories
 */
class DrpAccountLogRepository
{
    protected $timeRepository;
    protected $baseRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
    }


    public function accountLogList($user_id = 0, $offset = [], $condition = [])
    {
        $model = DrpAccountLog::where('user_id', $user_id);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('add_time', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }


    public function accountLogInfo($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        $model = DrpAccountLog::where('id', $id);

        $result = $model->first();

        $result = $result ? $result->toArray() : [];

        return $result;
    }

    /**
     * 更新记录
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function accountLogUpdate($id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data = $this->baseRepository->getArrayfilterTable($data, 'drp_account_log');

        return DrpAccountLog::where('id', $id)->update($data);
    }

    /**
     * 新增记录
     * @param array $data
     * @return bool
     */
    public function accountLogCreate($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data = $this->baseRepository->getArrayfilterTable($data, 'drp_account_log');

        return DrpAccountLog::insertGetId($data);
    }

    /**
     * 删除记录
     * @param int $id
     * @return bool
     */
    public function accountLogDelete($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return DrpAccountLog::where('id', $id)->delete();
    }

    /**
     * 查询
     * @param int $user_id
     * @param int $membership_card_id
     * @param string $receive_type
     * @return array|bool
     */
    public function accountLogInfoByUser($user_id = 0, $membership_card_id = 0, $receive_type = '')
    {
        if (empty($user_id)) {
            return false;
        }

        // receive_type =  integral 消费积分兑换
        $model = DrpAccountLog::where('user_id', $user_id)->where('membership_card_id', $membership_card_id)->where('receive_type', $receive_type);

        $result = $model->orderBy('id', 'DESC')->first();

        $result = $result ? $result->toArray() : [];

        return $result;
    }

    /**
     * 插入帐户变动记录
     * @param int $user_id
     * @param array $account_log
     * @return bool
     */
    public function insert_drp_account_log($user_id = 0, $account_log = [])
    {
        if ($user_id > 0) {
            $account_log['user_id'] = $user_id;
            $account_log['add_time'] = $this->timeRepository->getGmTime();
            $account_log['account_type'] = $account_log['account_type'] ?? ACT_OTHER;

            return $this->accountLogCreate($account_log);
        }

        return false;
    }

    /**
     * 修改记录
     * @param int $log_id
     * @param int $user_id
     * @param array $account_log
     * @return bool
     */
    public function update_drp_account_log($log_id = 0, $user_id = 0, $account_log = [])
    {
        if (empty($log_id) || empty($user_id) || empty($account_log)) {
            return false;
        }

        $account_log = $this->baseRepository->getArrayfilterTable($account_log, 'drp_account_log');

        return DrpAccountLog::where('log_id', $log_id)->where('user_id', $user_id)->update($account_log);
    }

    /**
     * 查询信息
     * @param int $log_id
     * @param int $user_id
     * @return array|bool
     */
    public function get_drp_account_log($log_id = 0, $user_id = 0)
    {
        if (empty($log_id) || empty($user_id)) {
            return false;
        }

        $model = DrpAccountLog::where('user_id', $user_id)->where('log_id', $log_id)->where('is_paid', 1)->first();

        $info = $model ? $model->toArray() : [];

        return $info;
    }

}