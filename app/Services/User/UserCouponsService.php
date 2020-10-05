<?php

namespace App\Services\User;

use App\Models\CouponsUser;
use App\Services\Activity\CouponsService;

class UserCouponsService
{
    protected $couponsService;

    public function __construct(
        CouponsService $couponsService
    )
    {
        $this->couponsService = $couponsService;
    }

    /**
     * 注册送优惠券
     *
     * @param int $user_id
     */
    public function registerCoupons($user_id = 0)
    {

        //获取注册类型的优惠券信息;
        $res = $this->couponsService->getCouponsTypeInfoNoPage(1);

        if (!empty($res)) {
            foreach ($res as $k => $v) {
                //获取当前的注册券已被发放的数量(防止发放数量超过设定发放数量)
                $num = CouponsUser::where('cou_id', $v['cou_id'])->count();

                if ($v['cou_total'] <= $num) {
                    continue;
                }

                //注册送注册券
                $other = [
                    'user_id' => $user_id,
                    'cou_id' => $v['cou_id'],
                    'cou_money' => $v['cou_money'],
                    'uc_sn' => $v['uc_sn']
                ];

                CouponsUser::insert($other);
            }
        }
    }
}