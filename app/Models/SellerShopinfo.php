<?php

namespace App\Models;

use App\Entities\SellerShopinfo as Base;

/**
 * Class SellerShopinfo
 */
class SellerShopinfo extends Base
{
    public function MerchantsShopInformation()
    {
        return $this->belongsTo('App\Models\MerchantsShopInformation', 'ru_id', 'user_id');
    }

    /**
     * 关联商品
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasMany('App\Models\Goods', 'user_id', 'ru_id');
    }

    /**
     * 关联入驻商家会员ID
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }

    /**
     * 关联订单商品
     *
     * @access  public
     * @param ru_id
     * @return  array
     */
    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'ru_id', 'ru_id');
    }

    /**
     * 关联配送方式
     *
     * @access  public
     * @param shipping_id
     * @return  array
     */
    public function getShipping()
    {
        return $this->hasOne('App\Models\Shipping', 'shipping_id', 'shipping_id');
    }

    /**
     * 关联商家等级
     *
     * @access  public
     * @param ru_id
     * @return  array
     */
    public function getSellerQrcode()
    {
        return $this->hasOne('App\Models\SellerQrcode', 'ru_id', 'ru_id');
    }

    /**
     * 关联商家入驻流程信息
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getMerchantsStepsFields()
    {
        return $this->hasOne('App\Models\MerchantsStepsFields', 'user_id', 'ru_id');
    }

    /**
     * 关联省份
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionProvince()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'province');
    }

    /**
     * 关联城市
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionCity()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'city');
    }

    /**
     * 关联地区
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getRegionDistrict()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'district');
    }
}
