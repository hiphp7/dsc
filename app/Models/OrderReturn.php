<?php

namespace App\Models;

use App\Entities\OrderReturn as Base;

/**
 * Class OrderReturn
 */
class OrderReturn extends Base
{

    /**
     * 关联商品
     *
     * @param goods_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联退换货商品
     *
     * @access  public
     * @param ret_id
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getReturnGoods()
    {
        return $this->hasOne('App\Models\ReturnGoods', 'ret_id', 'ret_id');
    }

    /**
     * 关联订单商品
     *
     * @access  public
     * @param rec_id
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'rec_id', 'rec_id');
    }

    /**
     * 关联订单商品
     *
     * @access  public
     * @param order_id
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderIdGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'order_id', 'order_id');
    }

    /**
     * 关联退换货订单扩展表信息
     *
     * @access  public
     * @param ret_id
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderReturnExtend()
    {
        return $this->hasOne('App\Models\OrderReturnExtend', 'ret_id', 'ret_id');
    }

    public function getRegionCountry()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'country');
    }

    /**
     * 关联省份
     *
     * @access  public
     * @param user_id
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionProvince()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'province');
    }

    /**
     * 关联城市
     *
     * @access  public
     * @param user_id
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionCity()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'city');
    }

    /**
     * 关联城镇
     *
     * @access  public
     * @param user_id
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionDistrict()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'district');
    }

    /**
     * 关联乡村/街道
     *
     * @access  public
     * @param user_id
     * @return  \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionStreet()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'street');
    }

    /**
     * 关联订单
     * @param order_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    public function getSellerNegativeOrder()
    {
        return $this->hasOne('App\Models\SellerNegativeOrder', 'ret_id', 'ret_id');
    }

    public function getSellerBillOrderReturn()
    {
        return $this->hasOne('App\Models\SellerBillOrderReturn', 'ret_id', 'ret_id');
    }
}
