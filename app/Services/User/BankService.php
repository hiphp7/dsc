<?php

namespace App\Services\User;

use App\Models\UserBank;

/**
 * 会员银行卡
 * Class BankService
 * @package App\Services\User
 */
class BankService
{

    /**
     * 添加或者更新会员银行卡
     * @param array $res
     * @return bool
     */
    public function updateBank($res = [])
    {
        if (empty($res)) {
            return false;
        }

        $bank_id = intval($res['id']);
        if ($bank_id > 0) {
            /* 更新指定记录 */
            UserBank::where('id', $bank_id)->where('user_id', $res['user_id'])->update($res);
        } else {
            if (isset($res['id'])) {
                unset($res['id']);
            }
            /* 插入一条新记录 */
            UserBank::insert($res);
        }

        return true;
    }

    /**
     * 会员银行卡详情
     * @param int $user_id
     * @return array
     */
    public function infoBank($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $res = UserBank::where('user_id', $user_id)->first();

        $res = $res ? $res->toArray() : [];

        return $res;
    }
}
