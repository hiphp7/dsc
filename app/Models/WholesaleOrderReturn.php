<?php

namespace App\Models;

use App\Entities\WholesaleOrderReturn as Base;

/**
 * Class WholesaleOrderReturn
 */
class WholesaleOrderReturn extends Base
{
    /**
     * 关联供应链商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWholesale()
    {
        return $this->hasOne('App\Models\Wholesale', 'goods_id', 'goods_id');
    }

    /**
     * 关联退换货商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWholesaleReturnGoods()
    {
        return $this->hasOne('App\Models\WholesaleReturnGoods', 'rec_id', 'rec_id');
    }

    /**
     * 关联订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWholesaleOrderInfo()
    {
        return $this->hasOne('App\Models\WholesaleOrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联退换货订单扩展信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWholesaleOrderReturnExtend()
    {
        return $this->hasOne('App\Models\WholesaleOrderReturnExtend', 'ret_id', 'ret_id');
    }

    /**
     * 关联省份
     *
     * @access  public
     * @param user_id
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
     * @param user_id
     * @return  array
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
     * @return  array
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
     * @return  array
     */
    public function getRegionStreet()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'street');
    }
}
