<?php

namespace App\Services\User;

use App\Models\UsersReal;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 会员实名认证
 * Class VerifyService
 * @package App\Services\User
 */
class VerifyService
{
    protected $timeRepository;
    protected $config;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 添加或者更新会员实名认证
     * @param array $info
     * @return bool
     */
    public function updateVerify($info = [])
    {
        if (empty($info)) {
            return false;
        }

        $info['add_time'] = $this->timeRepository->getGmTime();
        $info['review_status'] = 0;
        $info['review_content'] = 0;
        $info['user_type'] = 0;

        $info = $this->baseRepository->getArrayfilterTable($info, 'users_real');

        $real_id = intval($info['real_id']);
        if ($real_id > 0) {
            // 更新指定记录
            UsersReal::where('real_id', $real_id)->where('user_id', $info['user_id'])->update($info);
        } else {
            if (isset($info['real_id'])) {
                unset($info['real_id']);
            }
            // 插入一条新记录
            UsersReal::insert($info);
        }
        return true;
    }

    /**
     * 会员实名认证详情
     * @param int $user_id
     * @return array
     */
    public function infoVerify($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $res = UsersReal::where('user_id', $user_id)->first();
        $res = $res ? $res->toArray() : [];

        if (!empty($res)) {
            $res['front_of_id_card'] = $this->dscRepository->getImagePath($res['front_of_id_card']);
            $res['reverse_of_id_card'] = $this->dscRepository->getImagePath($res['reverse_of_id_card']);
            $res['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $res['add_time']);
        }

        return $res;
    }
}
