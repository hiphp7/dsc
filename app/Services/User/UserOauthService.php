<?php

namespace App\Services\User;

use App\Models\TouchAuth;

class UserOauthService
{
    /**
     * 查询社会化登录插件
     *
     * @param int $status
     * @param $columns
     * @return array
     */
    public function getOauthList($status = 1, $columns = [])
    {
        // 显示社会化登录插件
        $model = TouchAuth::query()->where('status', $status);

        if (!empty($columns)) {
            $model = $model->select($columns);
        }

        $model = $model->orderBy('sort', 'ASC')
            ->orderBy('id', 'DESC')->get();

        $oauth_list = $model ? $model->toArray() : [];

        return $oauth_list;
    }
}