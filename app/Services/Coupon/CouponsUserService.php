<?php

namespace App\Services\Coupon;

use App\Models\CouponsUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class CouponsUserService
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
     * 通过 用户优惠券ID 获取该条优惠券详情 bylu
     *
     * @param int $uc_id 用户优惠券ID
     * @param int $seller_id
     * @param int $user_id
     * @return array
     */
    public function getCoupons($uc_id = 0, $seller_id = -1, $user_id = -1)
    {
        if ($user_id == -1) {
            $user_id = session('user_id', 0);
        }

        $time = $this->timeRepository->getGmTime();

        $row = CouponsUser::selectRaw('*, cou_money AS uc_money')
            ->where('uc_id', $uc_id)
            ->where('user_id', $user_id);

        $where = [
            'time' => $time,
            'seller_id' => $seller_id
        ];
        $row = $row->whereHas('getCoupons', function ($query) use ($where) {
            $query->where('cou_end_time', '>', $where['time']);

            if ($where['seller_id'] > -1) {
                $query->where('ru_id', $where['seller_id']);
            }
        });

        $row = $row->with([
            'getCoupons' => function ($query) {
                $query->select('cou_id', 'cou_type', 'cou_money', 'ru_id', 'cou_man');
            }
        ]);

        $row = $this->baseRepository->getToArrayFirst($row);

        $row = isset($row['get_coupons']) ? array_merge($row, $row['get_coupons']) : $row;

        return $row;
    }
}