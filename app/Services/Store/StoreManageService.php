<?php

namespace App\Services\Store;

use App\Models\StoreUser;
use App\Repositories\Common\TimeRepository;

/**
 * 门店后台管理 service
 * Class StoreManageService
 * @package App\Services\Wechat
 */
class StoreManageService
{
    protected $timeRepository;

    /**
     * StoreManageService constructor.
     * @param TimeRepository $timeRepository
     */
    public function __construct(
        TimeRepository $timeRepository
    )
    {
        $this->timeRepository = $timeRepository;
    }

    /**
     * 查询门店会员
     * @param string $user_name
     * @param string $password
     * @return array
     */
    public function storeUser($user_name = '', $password = '')
    {
        if (empty($user_name)) {
            return [];
        }
        $ec_salt = StoreUser::where('stores_user', $user_name)->value('ec_salt');

        if (!empty($ec_salt)) {
            $stores_pwd = md5(md5($password) . $ec_salt);
        } else {
            $stores_pwd = md5($password);
        }

        $row = StoreUser::where('stores_user', $user_name)->where('stores_pwd', $stores_pwd)->first();

        return $row ? $row->toArray() : [];
    }

    /**
     * 更新门店会员信息
     * @param $store_user_id
     * @param array $updata
     * @return bool
     */
    public function updateStoreUser($store_user_id, $updata = [])
    {
        if (empty($updata)) {
            return false;
        }

        $up = StoreUser::where('id', $store_user_id)->update($updata);

        return $up;
    }
}
