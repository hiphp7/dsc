<?php

namespace App\Services\User;

use App\Models\ConnectUser;
use App\Models\Users;
use App\Repositories\Common\TimeRepository;

/**
 * 社会化登录用户
 * Class ConnectUser
 * @package App\Services
 */
class ConnectUserService
{
    /**
     * @var TimeRepository
     */
    protected $timeRepository;

    /**
     * ConnectUserService constructor.
     * @param TimeRepository $timeRepository
     */
    public function __construct(TimeRepository $timeRepository)
    {
        $this->timeRepository = $timeRepository;
    }

    /**
     * 根据 user_id 获取用户绑定信息
     * @param int $user_id
     * @param string $type
     * @return array|bool
     */
    public function getUserInfo($user_id = 0, $type = '')
    {
        if (empty($user_id) || empty($type)) {
            return false;
        }

        $type = ($type == 'weixin') ? 'wechat' : $type;// 统一PC与H5参数
        $map = [
            'user_id' => $user_id,
            'type' => $type,
        ];

        $model = Users::query()->whereHas('getConnectUser', function ($query) use ($map) {
            $query->where('user_id', $map['user_id'])
                ->where('connect_code', 'sns_' . $map['type']);
        });

        $model = $model->with([
            'getConnectUser' => function ($query) use ($map) {
                $query->where('user_id', $map['user_id'])
                    ->where('connect_code', 'sns_' . $map['type']);
            }
        ]);

        $result = $model->first();

        $result = $result ? $result->toArray() : [];

        $user = [];
        if ($result) {
            $user = collect($result)->merge($result['get_connect_user'])->except('get_connect_user')->all();
        }

        return $user;
    }

    /**
     * 查询社会化登录用户信息
     * @param string $union_id
     * @param string $type
     * @return array|bool
     */
    public function getConnectUserinfo($union_id = '', $type = '')
    {
        if (empty($union_id) || empty($type)) {
            return false;
        }

        $type = ($type == 'weixin') ? 'wechat' : $type;

        $map = [
            'open_id' => $union_id,
            'type' => $type,
        ];

        $model = Users::query()->whereHas('getConnectUser', function ($query) use ($map) {
            $query->where('open_id', $map['open_id'])
                ->where('connect_code', 'sns_' . $map['type']);
        });

        $model = $model->with([
            'getConnectUser' => function ($query) use ($map) {
                $query->select('user_id', 'id')
                    ->where('open_id', $map['open_id'])
                    ->where('connect_code', 'sns_' . $map['type']);
            }
        ]);

        $result = $model->first();

        $result = $result ? $result->toArray() : [];

        $user = [];
        if ($result) {
            $user = collect($result)->merge($result['get_connect_user'])->except('get_connect_user')->all();
        }

        return $user;
    }

    /**
     * 更新社会化登录用户信息
     * @param array $user
     * @param string $type : qq、weibo、wechat
     * @return bool
     */
    public function updateConnectUser($user = [], $type = '')
    {
        if (empty($user) || empty($type)) {
            return false;
        }

        if ($user && $type) {
            $type = ($type == 'weixin') ? 'wechat' : $type;// 统一PC与H5参数
            // 组合数据
            $profile = [
                'nickname' => $user['nickname'],
                'sex' => $user['sex'] ?? '',
                'province' => $user['province'] ?? '',
                'city' => $user['city'] ?? '',
                'country' => $user['country'] ?? '',
                'headimgurl' => $user['headimgurl'] ?? '',
            ];
            $data = [
                'connect_code' => 'sns_' . $type,
                'user_id' => $user['user_id'],
                'open_id' => $user['unionid'],
                'profile' => serialize($profile)
            ];
            if ($user['user_id'] > 0 && $user['unionid']) {
                $time = $this->timeRepository->getGmTime();

                // 查询
                $connect_userinfo = $this->getConnectUserinfo($user['unionid'], $type);
                if (empty($connect_userinfo)) {
                    // 新增记录
                    $data['create_at'] = $time;
                    ConnectUser::create($data);
                } else {
                    // 更新记录
                    ConnectUser::where(['open_id' => $user['unionid'], 'connect_code' => 'sns_' . $type])->update($data);
                }

                return true;
            }
        }
    }

    /**
     * 授权登录用户列表
     * @param int $user_id
     * @return bool|array
     */
    public function connectUserList($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        $model = ConnectUser::where('user_id', $user_id)->where('connect_code', '<>', '');

        $model = $model->whereHas('getUsers');

        $result = $model->get();

        $result = $result ? $result->toArray() : [];

        return $result;
    }

    /**
     * 查询是否绑定
     * @param int $id
     * @param int $user_id
     * @return array|bool
     */
    public function connectUserById($id = 0, $user_id = 0)
    {
        if (empty($id) || empty($user_id)) {
            return false;
        }

        $model = ConnectUser::where('id', $id)->where('connect_code', '<>', '');

        $model = $model->whereHas('getUsers', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        });

        $model = $model->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'mobile_phone');
            }
        ]);

        $result = $model->first();

        $result = $result ? $result->toArray() : [];

        if (!empty($result)) {
            $result = collect($result)->merge($result['get_users'])->except('get_users')->all();
        }

        return $result;
    }

    /**
     * 删除授权登录记录
     * @param string $union_id
     * @param int $user_id
     * @return bool
     */
    public function connectUserDelete($union_id = '', $user_id = 0)
    {
        if (empty($union_id) || empty($user_id)) {
            return false;
        }

        return ConnectUser::where('open_id', $union_id)->where('user_id', $user_id)->delete();
    }

    /**
     * 查询users用户是否被其他人绑定
     * @param string $union_id
     * @param string $type
     * @return int
     */
    public function checkConnectUserId($union_id = '', $type = '')
    {
        if (empty($union_id) || empty($type)) {
            return 0;
        }

        return ConnectUser::where('open_id', $union_id)->where('connect_code', 'sns_' . $type)->value('user_id');
    }
}
