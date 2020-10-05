<?php

use App\Models\DrpShop;
use App\Models\DrpUserCredit;
use App\Models\OrderGoods;


/**
 * 分销商等级
 */
function drp_rank_info($user_id = 0)
{
    $rank_info = [];
    //检测分销商是否是特殊等级,直接获取等级信息
//    $drp_shop = DrpShop::where(['user_id' => $user_id])->first();
    $drp_shop = DrpShop::with([
        'userMembershipCard' => function ($query) {
            $query->select('id', 'name');
        },
    ])->where(['user_id' => $user_id])->first();
    $credit_id = $drp_shop['credit_id'];
    if ($credit_id > 0) {
        $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
            ->where(['id' => $credit_id])
            ->first();
    } else {
        //统计分销商所属订单金额
        $goods_price = OrderGoods::from('order_goods as o')
            ->leftjoin('drp_log as a', 'o.order_id', '=', 'a.order_id')
            ->where('a.is_separate', 1)
            ->where('a.separate_type', '!=', '-1')
            ->where('a.user_id', $user_id)
            ->sum('money');

        $goods_price = $goods_price ? $goods_price : 0;

        $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
            ->where('min_money', '<=', $goods_price)
            ->where('max_money', '>', $goods_price)
            ->orderBy('min_money', 'ASC')
            ->first();

        $rank_info = $rank_info ? $rank_info->toArray() : [];
    }
    if (isset($drp_shop['user_membership_card']['name'])) {
        $rank_info['credit_name'] = $drp_shop['user_membership_card']['name'];
    }

    return $rank_info;
}

/**
 * 验证字段值是否重复
 * @param string $field 字段名
 * @param string $value 值
 * @param integer $id
 * @param string $where 条件表达式
 * @return boolean
 */
function is_only($field = '', $value, $id = 0)
{
    $model = DrpUserCredit::where($field, $value);
    if ($id) {
        $model = $model->where('id', '<>', $id);
    }
    $model = $model->first();

    return $model ? false : true;
}


