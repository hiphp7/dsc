<?php

namespace App\Services\User;

use App\Models\ConnectUser;
use App\Models\Users;
use App\Models\UsersPaypwd;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 会员账户安全
 * Class AccountSafeService
 * @package App\Services\User
 */
class AccountSafeService
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
     * 用户信息
     * @param int $user_id
     * @return array
     */
    public function users($user_id = 0)
    {
        $user = Users::where('user_id', $user_id)->first();

        return $user ? $user->toArray() : [];
    }

    /**
     * 是否验证邮箱
     * @param int $user_id
     * @return int
     */
    public function userValidateStatus($user_id = 0)
    {
        $user = $this->users($user_id);

        return $user['is_validated'] ?? 0;
    }

    /**
     * 是否启用支付密码
     * @param int $user_id
     * @return int
     */
    public function userPaypwdCount($user_id = 0)
    {
        $users_paypwd = UsersPaypwd::where('user_id', $user_id)->count();

        return $users_paypwd > 0 ? 1 : 0;
    }

    /**
     * 判断是否授权登录用户
     * @param int $user_id
     * @return boolean |int
     */
    public function isConnectUser($user_id = 0)
    {
        $is_connect_user = ConnectUser::where('user_id', $user_id)->count();

        return $is_connect_user > 0 ? 1 : 0;
    }

    /**
     * 返回验证类型
     * @return string
     */
    public function validateType()
    {
        if ($this->config['sms_signin'] == 0) {
            $type = 'email';
        } else {
            $type = 'phone';
        }

        return $type;
    }

    /**
     * 查询启用支付密码
     * @param int $user_id
     * @return int
     */
    public function userPaypwd($user_id = 0)
    {
        $result = UsersPaypwd::where('user_id', $user_id)->first();

        return $result ? $result->toArray() : [];
    }

    /**
     * 添加支付密码
     * @param array $data
     * @return bool
     */
    public function addUsersPaypwd($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data = $this->baseRepository->getArrayfilterTable($data, 'users_paypwd');

        $count = $this->userPaypwd($data['user_id']);

        if (empty($count)) {
            return UsersPaypwd::insert($data);
        }

        return false;
    }

    /**
     * 更新
     * @param int $user_id
     * @param int $paypwd_id
     * @param array $data
     * @return bool
     */
    public function updateUsersPaypwd($user_id = 0, $paypwd_id = 0, $data = [])
    {
        if (empty($paypwd_id) || empty($data)) {
            return false;
        }

        $data = $this->baseRepository->getArrayfilterTable($data, 'users_paypwd');

        return UsersPaypwd::where('user_id', $user_id)->where('paypwd_id', $paypwd_id)->update($data);
    }

    /**
     * 查询支付密码
     * @param int $paypwd_id
     * @return array
     */
    public function getUsersPaypwd($paypwd_id = 0)
    {
        $result = UsersPaypwd::where('paypwd_id', $paypwd_id)->first();

        return $result ? $result->toArray() : [];
    }

}
