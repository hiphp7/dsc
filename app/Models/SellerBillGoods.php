<?php

namespace App\Models;

use App\Entities\SellerBillGoods as Base;

/**
 * Class SellerBillGoods
 */
class SellerBillGoods extends Base
{

    /**
     * 关联账单订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getSellerBillOrder()
    {
        return $this->hasOne('App\Models\SellerBillOrder', 'order_id', 'order_id');
    }

    /**
     * 关联订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联订单商品
     *
     * @access  public
     * @param rec_id
     * @return  array
     */
    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'rec_id', 'rec_id');
    }

    /**
     * 关联商品查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
