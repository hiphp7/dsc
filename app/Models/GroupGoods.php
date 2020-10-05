<?php

namespace App\Models;

use App\Entities\GroupGoods as Base;

/**
 * Class GroupGoods
 */
class GroupGoods extends Base
{
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
     * 关联商品配件
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getCartCombo()
    {
        return $this->hasOne('App\Models\CartCombo', 'goods_id', 'goods_id');
    }

    /**
     * 关联会员等级价查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getMemberPrice()
    {
        return $this->hasOne('App\Models\MemberPrice', 'goods_id', 'goods_id');
    }

    /**
     * 关联仓库商品信息查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseGoods()
    {
        return $this->hasOne('App\Models\WarehouseGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联仓库地区商品查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseAreaGoods()
    {
        return $this->hasOne('App\Models\WarehouseAreaGoods', 'goods_id', 'goods_id');
    }
}
