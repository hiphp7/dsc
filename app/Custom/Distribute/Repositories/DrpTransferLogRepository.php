<?php

namespace App\Custom\Distribute\Repositories;

use App\Custom\Distribute\Models\DrpTransferLog;
use App\Custom\Distribute\Services\DrpCommonService;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 分销商提现申请记录
 * Class DrpTransferLogRepository
 * @package App\Custom\Distribute\Repositories
 */
class DrpTransferLogRepository
{
    protected $timeRepository;
    protected $baseRepository;
    protected $orderRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        OrderRepository $orderRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * 分销商提现申请列表
     * @param string $keywords
     * @param array $offset
     * @param array $condition
     * @return array
     */
    public function transferLogList($keywords = '', $offset = [], $condition = [])
    {
        $model = DrpTransferLog::where('deposit_type', '>', 0);

        if (!empty($keywords)) {
            // 搜索分销商
            $model = $model->whereHas('drpShop', function ($query) use ($keywords) {
                $query->where('shop_name', 'like', '%' . $keywords . '%')
                    ->orWhere('real_name', 'like', '%' . $keywords . '%');
            });
        }

        // 按申请时间筛选
        if (!empty($condition)) {
            if (!empty($condition['starttime']) && !empty($condition['endtime'])) {
                $model = $model->whereBetween('add_time', [$condition['starttime'], $condition['endtime']]);
            }
        }

        $model = $model->with([
            'drpShop' => function ($query) {
                $query->where('audit', 1)->select('user_id', 'shop_name');
            }
        ]);

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

    /**
     * 审核记录
     * @param int $id
     * @param int $status
     * @return bool
     */
    public function transferLogCheck($id = 0, $status = 0)
    {
        if (empty($id)) {
            return false;
        }

        return DrpTransferLog::where('id', $id)->update(['check_status' => $status]);
    }

    /**
     * 提现申请记录
     * @param int $id
     * @return array|bool
     */
    public function transferLogInfo($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        $model = DrpTransferLog::where('id', $id);

        $model = $model->with([
            'drpShop' => function ($query) {
                $query->where('audit', 1)->select('user_id', 'shop_name');
            }
        ]);

        $result = $model->first();

        $result = $result ? $result->toArray() : [];

        $result = collect($result)->merge($result['drp_shop'])->except('drp_shop')->all();

        return $result;
    }

    /**
     * 更新记录
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function transferLogUpdate($id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data = $this->baseRepository->getArrayfilterTable($data, 'drp_transfer_log');

        $res = DrpTransferLog::where('id', $id)->update($data);

        if ($res) {
            //提现成功后进行分销商升级处理
            if ($data['finish_status'] == 1) {
                app(DrpCommonService::class)->drp_transfer_upgrade($id);
            }
        }

        return $res;
    }

    /**
     * 新增记录
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function transferLogCreate($user_id = 0, $data = [])
    {
        if (empty($user_id) || empty($data)) {
            return false;
        }

        $data['add_time'] = $this->timeRepository->getGmTime();

        $count = DrpTransferLog::where('user_id', $user_id)->where('trade_no', $data['trade_no'])->count();
        if (empty($count)) {

            $data = $this->baseRepository->getArrayfilterTable($data, 'drp_transfer_log');

            return DrpTransferLog::insertGetId($data);
        }

        return false;
    }

    /**
     * 删除记录
     * @param int $id
     * @return bool
     */
    public function transferLogDelete($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return DrpTransferLog::where('id', $id)->delete();
    }

    /**
     * 分销商提现申请记录（前台）
     * @param int $user_id
     * @param int $deposit_status
     * @param int $page
     * @param int $size
     * @return array
     */
    public function transferLogListForUser($user_id = 0, $deposit_status = -1, $page = 1, $size = 10)
    {
        $model = DrpTransferLog::where('user_id', $user_id)->where('deposit_type', '>', 0);

        // 0 未提现 1 已提现
        if ($deposit_status >= 0) {
            $model = $model->where('deposit_status', $deposit_status);
        }

        $model = $model->with([
            'drpShop' => function ($query) {
                $query->where('audit', 1)->select('user_id', 'shop_name');
            }
        ]);

        $total = $model->count();

        $offset = [
            'start' => ($page - 1) * $size,
            'limit' => $size,
        ];
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

}
