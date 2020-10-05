<?php

namespace App\Models;

use App\Entities\Wholesale as Base;

/**
 * Class Wholesale
 */
class Wholesale extends Base
{
    /**
     * 关联商品表
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
     * 批发商品扩展分类
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWholesaleExtend()
    {
        return $this->hasOne('App\Models\WholesaleExtend', 'goods_id', 'goods_id');
    }

    /**
     * 关联订单商品表
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getOrderGoodsList()
    {
        return $this->hasMany('App\Models\OrderGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联商品表
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWholesaleVolumePrice()
    {
        return $this->hasOne('App\Models\WholesaleVolumePrice', 'goods_id', 'goods_id');
    }

    /**
     * 关联商品表列表
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWholesaleVolumePriceList()
    {
        return $this->hasMany('App\Models\WholesaleVolumePrice', 'goods_id', 'goods_id');
    }

    /**
     * 关联品牌
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getWholesaleBrand()
    {
        return $this->hasOne('App\Models\Brand', 'brand_id', 'brand_id');
    }


    /**
     * 关联采购分类表
     *
     * @access  public
     * @param cat_id
     * @return  array
     */
    public function getWholesaleCat()
    {
        return $this->hasOne('App\Models\WholesaleCat', 'cat_id', 'cat_id');
    }

    /**
     * 关联供货商
     *
     * @access  public
     * @param suppliers_id
     * @return  array
     */
    public function getSuppliers()
    {
        return $this->hasOne('App\Models\Suppliers', 'suppliers_id', 'suppliers_id');
    }
}
