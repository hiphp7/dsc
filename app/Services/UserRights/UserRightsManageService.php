<?php

namespace App\Services\UserRights;

use App\Models\UserMembershipCardRights;
use App\Models\UserMembershipRights;
use App\Models\UserRankRights;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\User\UserRankService;


class UserRightsManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $discountService;
    protected $userRightsCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        DiscountService $discountService,
        UserRightsCommonService $userRightsCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->discountService = $discountService;
        $this->userRightsCommonService = $userRightsCommonService;
    }

    /**
     * 新增
     * @param string $code
     * @param array $data
     * @return bool
     */
    public function createUserRights($code = '', $data = [])
    {
        if (empty($code) || empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'user_membership_rights');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        $count = $this->userRightsCount($code);
        if (empty($count)) {

            $data['code'] = $code;
            $data['add_time'] = $this->timeRepository->getGmTime();
            return UserMembershipRights::create($data);
        }

        return false;
    }

    /**
     * 编辑
     * @param string $code
     * @param array $data
     * @return bool
     */
    public function updateUserRights($code = '', $data = [])
    {
        if (empty($code) || empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'user_membership_rights');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        $data['update_time'] = $this->timeRepository->getGmTime();

        $res = UserMembershipRights::where('code', $code)->update($data);
        if ($res) {
            //会员特价权益编辑后更新默认等级价格
            if ($code == 'discount') {
                $data['rights_configure'] = unserialize($data['rights_configure']);
                $discount = $data['enable'] ? ($data['rights_configure'][0]['value'] ?? 100) : 100;

                $info = $this->userRightsInfo($code);
                app(UserRankService::class)->updateDefaultRightDiscount($info['id'], $discount);
            }

        }
        return $res;
    }

    /**
     * 查询是否存在
     * @param string $code
     * @return mixed
     */
    public function userRightsCount($code = '')
    {
        if (empty($code)) {
            return false;
        }

        $count = UserMembershipRights::query()->where('code', $code)->count();

        return $count;
    }

    /**
     * 查询信息
     * @param string $code
     * @return array|mixed
     */
    public function userRightsInfo($code = '')
    {
        if (empty($code)) {
            return false;
        }

        $info = $this->userRightsCommonService->userRightsInfo($code);

        return $info;
    }

    /**
     * 列表
     * @return array
     */
    public function userRightsList()
    {
        $model = UserMembershipRights::query();

        $model = $model->orderBy('id', 'DESC')
            ->get();

        $list = $model ? $model->toArray() : [];

        if (!empty($list)) {
            foreach ($list as $k => $value) {
                $list[$k]['install'] = 1;
                $list[$k]['rights_configure'] = empty($value['rights_configure']) ? '' : unserialize($value['rights_configure']);
                $list[$k]['icon'] = empty($value['icon']) ? '' : ((stripos($value['icon'], 'assets') !== false) ? asset($value['icon']) : $this->dscRepository->getImagePath($value['icon']));
            }
        }

        return $list;
    }

    /**
     * 卸载删除
     * @param string $code
     * @return bool
     */
    public function uninstallUserRights($code = '')
    {
        if (empty($code)) {
            return false;
        }

        $model = UserMembershipRights::where('code', $code);

        if ($model) {
            $right_id = $model->value('id');

            $res = $model->delete();

            if ($res) {
                // 删除已绑定权益关系
                UserMembershipCardRights::where('rights_id', $right_id)->delete();
                UserRankRights::where('rights_id', $right_id)->delete();
            }

            return $res;
        }

        return false;
    }
}
