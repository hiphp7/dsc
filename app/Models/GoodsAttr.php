<?php

namespace App\Models;

use App\Entities\GoodsAttr as Base;

/**
 * Class GoodsAttr
 */
class GoodsAttr extends Base
{
    /**
     * 关联商品
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联商品
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getGoodsList()
    {
        return $this->hasMany('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联商品
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getGoodsAttribute()
    {
        return $this->hasOne('App\Models\Attribute', 'attr_id', 'attr_id');
    }

    /**
     * 关联仓库商品属性
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getGoodsWarehouseAttr()
    {
        return $this->hasOne('App\Models\WarehouseAttr', 'goods_attr_id', 'goods_attr_id');
    }

    /**
     * 关联仓库商品属性
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getGoodsWarehouseAreaAttr()
    {
        return $this->hasOne('App\Models\WarehouseAreaAttr', 'goods_attr_id', 'goods_attr_id');
    }

    public function getGoodsType()
    {
        return $this->hasOne('App\Models\GoodsType', 'cat_id', 'goods_type');
    }
}
