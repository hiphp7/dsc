<?php

namespace App\Models;

use App\Entities\Attribute as Base;

/**
 * Class Attribute
 */
class Attribute extends Base
{
    /**
     * 关联属性类型
     *
     * @access  public
     * @param cat_id
     * @return  array
     */
    public function goodsType()
    {
        return $this->hasOne('App\Models\GoodsType', 'cat_id', 'cat_id');
    }


    /**
     * 关联商品属性
     *
     * @access  public
     * @param attr_id
     * @return  array
     */
    public function getGoodsAttr()
    {
        return $this->hasOne('App\Models\GoodsAttr', 'attr_id', 'attr_id');
    }

    /**
     * 关联商品属性列表
     *
     * @access  public
     * @param attr_id
     * @return  array
     */
    public function getGoodsAttrList()
    {
        return $this->hasMany('App\Models\GoodsAttr', 'attr_id', 'attr_id');
    }

    /**
     * 关联批发商品属性
     *
     * @access  public
     * @param attr_id
     * @return  array
     */
    public function getWholesaleGoodsAttr()
    {
        return $this->hasOne('App\Models\WholesaleGoodsAttr', 'attr_id', 'attr_id');
    }

    /**
     * 关联批发商品属性列表
     *
     * @access  public
     * @param attr_id
     * @return  array
     */
    public function getWholesaleGoodsAttrList()
    {
        return $this->hasMany('App\Models\WholesaleGoodsAttr', 'attr_id', 'attr_id');
    }
}
