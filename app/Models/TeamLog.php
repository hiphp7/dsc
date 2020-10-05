<?php

namespace App\Models;

use App\Entities\TeamLog as Base;

/**
 * Class TeamLog
 */
class TeamLog extends Base
{
    /**
     * 关联拼团订单
     * @param team_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'team_id', 'team_id');
    }

    /**
     * 关联拼团商品
     * @param id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getTeamGoods()
    {
        return $this->hasOne('App\Models\TeamGoods', 'id', 't_id');
    }

    /**
     * 关联商品
     * @param goods_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
