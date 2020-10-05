<?php

namespace App\Custom\Distribute\Repositories;


use App\Custom\Distribute\Models\DrpLog;
use App\Custom\Distribute\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;


class DrpLogRepository
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


    /**
     * 下级分销商分成佣金列表
     * @param int $user_id
     * @param array $offset
     * @return array
     */
    public function drp_invite_list($user_id = 0, $offset = [])
    {
        $model = Users::where('drp_parent_id', $user_id);

        $model = $model->whereHas('drpShop', function ($query) {
            $query->where('audit', 1)->where('status', 1);
        });

        $model = $model->with([
            'drpShop' => function ($query) {
                $query->where('audit', 1)->where('status', 1)->select('user_id', 'shop_name', 'create_time');
            },
            'drpLog' => function ($query) {
                $query = $query->where('is_separate', 1)->select('user_id', 'order_id', 'money', 'time');
                $query = $query->whereHas('orderGoods', function ($query) {
                    $query->where('buy_drp_show', 1);
                });
                $query = $query->with([
                    'orderInfo' => function ($query) {
                        $query->select('order_id', 'order_sn', 'add_time');
                    }
                ]);
            }
        ]);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->select('user_id', 'user_name', 'nick_name', 'mobile_phone', 'drp_parent_id', 'user_picture')->orderBy('user_id', 'DESC')
            ->get();

        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 下级分销商分成佣金总金额
     * @param int $user_id
     * @return mixed
     */
    public function drp_invite_count($user_id = 0)
    {
        $model = DrpLog::where('is_separate', 1);

        $model = $model->whereHas('users', function ($query) use ($user_id) {
            $query = $query->whereHas('drpShop', function ($query) {
                $query->where('audit', 1)->where('status', 1);
            });
            $query->where('drp_parent_id', $user_id);
        });

        $total_child_shop_money = $model->sum('money');

        return $total_child_shop_money;
    }

}