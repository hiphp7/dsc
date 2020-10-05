<?php

namespace App\Services\Drp;

use App\Models\DrpShop;
use App\Models\DrpUserCredit;
use App\Models\OrderGoods;
use App\Repositories\Common\BaseRepository;


class DrpUserCreditService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 分销商列表
     * @param int $limit
     * @return array
     */
    public function getList($limit = 3)
    {
        $model = DrpUserCredit::orderBy('min_money', 'ASC')->limit($limit)->get();

        $list = $model ? $model->toArray() : [];

        return $list;
    }

    /**
     * 分销商等级
     * @param int $user_id
     * @return array
     */
    public function drpRankInfo($user_id = 0)
    {
        //检测分销商是否是特殊等级,直接获取等级信息
        $model = DrpShop::where('user_id', $user_id);

        $model = $model->with([
            'drpUserCredit'
        ]);

        $rank_info = $model->first();

        $rank_info = $rank_info ? $rank_info->toArray() : [];

        $rank_info['credit_name'] = $rank_info['drp_user_credit']['credit_name'] ?? '';

        if (empty($rank_info['credit_id'])) {

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

            return $rank_info;
        }

        return $rank_info;
    }


    /**
     * 查询等级信息
     * @param int $id
     * @return array
     */
    public function getInfo($id = 0)
    {
        $model = DrpUserCredit::where('id', $id)->first();

        $info = $model ? $model->toArray() : [];

        return $info;
    }

    /**
     * 更新分销商等级信息
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateCredit($id = 0, $data = [])
    {
        if (empty($id) || empty($data)) {
            return false;
        }

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        return DrpUserCredit::where('id', $id)->update($data);
    }

}
