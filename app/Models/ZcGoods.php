<?php

namespace App\Models;

use App\Entities\ZcGoods as Base;

/**
 * Class ZcGoods
 */
class ZcGoods extends Base
{

    /**
     * 关联众筹商品项目
     *
     * @access  public
     * @param id
     * @return  array
     */
    public function getZcProject()
    {
        return $this->hasOne('App\Models\ZcProject', 'id', 'pid');
    }

    /**
     * 关联众筹商品订单
     *
     * @access  public
     * @param zc_goods_id
     * @return  array
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'zc_goods_id', 'id');
    }

    /**
     * 关联众筹商品订单列表
     *
     * @access  public
     * @param zc_goods_id
     * @return  array
     */
    public function getOrderList()
    {
        return $this->hasMany('App\Models\OrderInfo', 'zc_goods_id', 'id');
    }
}
