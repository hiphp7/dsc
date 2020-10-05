<?php

namespace App\Models;

use App\Entities\GoodsType as Base;

/**
 * Class GoodsType
 */
class GoodsType extends Base
{
    /**
     * 关联属性
     *
     * @access  public
     * @param cat_id
     * @return  array
     */
    public function getGoodsAttribute()
    {
        return $this->hasOne('App\Models\Attribute', 'cat_id', 'cat_id');
    }

    /**
     * 关联属性分类
     *
     * @access  public
     * @param cat_id
     * @return  array
     */
    public function getGoodsTypeCat()
    {
        return $this->hasOne('App\Models\GoodsTypeCat', 'cat_id', 'c_id');
    }

    /**
     * 关联商品
     *
     * @access  public
     * @param goods_type
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_type', 'cat_id');
    }

    /**
     * 关联批发商品
     *
     * @access  public
     * @param goods_type
     * @return  array
     */
    public function getWholesale()
    {
        return $this->hasOne('App\Models\Wholesale', 'goods_type', 'cat_id');
    }
}
