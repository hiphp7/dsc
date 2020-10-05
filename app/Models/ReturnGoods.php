<?php

namespace App\Models;

use App\Entities\ReturnGoods as Base;

/**
 * Class ReturnGoods
 */
class ReturnGoods extends Base
{
    /**
     * 关联退换货订单
     *
     * @access  public
     * @param ret_id
     * @return  array
     */
    public function getOrderReturn()
    {
        return $this->hasOne('App\Models\OrderReturn', 'ret_id', 'ret_id');
    }

    /**
     * 关联退换货订单扩展表
     *
     * @access  public
     * @param ret_id
     * @return  array
     */
    public function getOrderReturnExtend()
    {
        return $this->hasOne('App\Models\OrderReturnExtend', 'ret_id', 'ret_id');
    }

    /**
     * 关联商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联属性商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoodsAttr()
    {
        return $this->hasOne('App\Models\GoodsAttr', 'goods_id', 'goods_id');
    }

    public function getSellerNegativeOrder()
    {
        return $this->hasOne('App\Models\SellerNegativeOrder', 'ret_id', 'ret_id');
    }
}
