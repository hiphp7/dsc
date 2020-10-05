<?php

namespace App\Plugins\UserRights\Register\Services;

use App\Models\Coupons;
use App\Models\CouponsUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\UserRights\DiscountService;
use App\Services\UserRights\UserRightsService;


class RegisterRightsService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $userRightsService;
    protected $discountService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        UserRightsService $userRightsService,
        DiscountService $discountService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->userRightsService = $userRightsService;
        $this->discountService = $discountService;
    }

    /**
     * 注册优惠券
     * @param int $type
     * @return array
     */
    public function getRegisterCoupons($type = 1)
    {
        $rangeList = [];

        // 查询注册优惠券列表
        $couponsList = $this->discountService->getCouponsByType($type);
        if (!empty($couponsList)) {
            // 二维转一维数组
            $couponsList = collect($couponsList)->mapWithKeys(function ($item, $key) {
                return [$item['cou_id'] => $item['cou_name']];
            });

            $rangeList = $couponsList->toArray();
        }

        return $rangeList;
    }

    /**
     * 注册送优惠券
     * @param string $code
     * @param int $user_id
     * @return bool
     */
    public function registerSendCoupons($code = '', $user_id = 0)
    {
        // 新手有礼权益
        $userRights = $this->userRightsService->userRightsInfo($code);
        if (empty($userRights)) {
            return false;
        }

        if (isset($userRights['enable']) && isset($userRights['install']) && $userRights['enable'] == 1 && $userRights['install'] == 1) {
            $rights_configure = $userRights['rights_configure'] ?? [];
            if (empty($rights_configure)) {
                return false;
            }

            //获取格林尼治时间戳(用于判断优惠券是否已过期)
            $time = $this->timeRepository->getGmTime();

            // TODO 当前会员 绑定的会员权益卡信息
            $cardRights = [];//$this->userRightsService->membershipCardInfoByUserId($user_id, $userRights['id']);
            $card_rights_configure = $cardRights['0']['rights_configure'] ?? [];

            if (empty($card_rights_configure)) {
                return false;
            }

            // 遍历
            foreach ($rights_configure as $i => $item) {

                // 默认权益 和 会员享有权益 比较
                if (!empty($card_rights_configure) && $item['name'] == $card_rights_configure[$i]['name']) {

                    $card_value = $card_rights_configure[$i]['value'] ?? 0;

                    // 权益配置 第一个值
                    if ($i == 0) {

                        // 取出优惠券类型id 赠送优惠券
                        $cou_id = $card_value;
                        if (!empty($cou_id) && $user_id > 0) {
                            $coupons_info = Coupons::query()->where('cou_id', $cou_id)->where('review_status', 3)->where('cou_end_time', '>', $time)->first();
                            $coupons_info = $coupons_info ? $coupons_info->toArray() : [];

                            if (empty($coupons_info)) {
                                return false;
                            }

                            //获取当前的注册券已被发放的数量(防止发放数量超过设定发放数量)
                            $num = CouponsUser::where('cou_id', $cou_id)->count();
                            if ($coupons_info['cou_total'] <= $num) {
                                return false;
                            }

                            $uc_sn = $time . rand(10, 99); // 生成优惠券编号

                            $other = [
                                'user_id' => $user_id,
                                'cou_id' => $cou_id,
                                'cou_money' => $coupons_info['cou_money'],
                                'uc_sn' => $uc_sn
                            ];
                            CouponsUser::insert($other);
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }

}