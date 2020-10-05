<?php

namespace App\Services\User;


use App\Models\UserBonus;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class UserBonusService
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
     * 查询会员的红包金额
     *
     * @param int $user_id
     * @return array
     */
    public function getUserBonus($user_id = 0)
    {

        $day = $this->timeRepository->getLocalGetDate();
        $cur_date = $this->timeRepository->getLocalMktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $row = UserBonus::selectRaw('bonus_type_id, COUNT(*) AS bonus_count')
            ->where('user_id', $user_id)
            ->where('order_id', 0);

        $row = $row->whereHas('getBonusType', function ($query) use ($cur_date) {
            $query->where('use_start_date', '<', $cur_date)
                ->where('use_end_date', '>', $cur_date);
        });

        $row = $row->with([
            'getBonusTypeList'
        ]);

        $row = $this->baseRepository->getToArrayFirst($row);

        $bonus_value = 0;
        if ($row) {
            foreach ($row['get_bonus_type_list'] as $key => $val) {
                $bonus_value += $val['type_money'];
            }
        }

        $row['bonus_value'] = $bonus_value;

        return $row;
    }
}