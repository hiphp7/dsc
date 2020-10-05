<?php

namespace App\Services\UserRights;

use App\Models\Coupons;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;


class DiscountService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
    }


    /**
     * 通过类型获取优惠券信息
     * @param string $cou_type 空为所有 1 注册赠券，2，购物赠券 3，全场赠券 4，会员赠券， 5 免邮券
     * @param array $offset 分页
     * @return array
     */
    public function getCouponsByType($cou_type = '', $offset = ['start' => 0, 'limit' => 100])
    {
        // 审核通过且在有效期内
        $time = $this->timeRepository->getGmTime();

        $model = Coupons::query()->where('review_status', 3)
            ->where('cou_end_time', '>', $time);

        if (!empty($cou_type)) {
            $model = $model->where('cou_type', $cou_type);
        }

        $model = $model->select('cou_id', 'cou_name');

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $list = $this->baseRepository->getToArrayGet($model);

        return $list;
    }

    /**
     * 红包
     * @param string $type
     * @return array
     */
    public function getBonusByType($type = '')
    {
        $result = [];
        return $result;
    }

}