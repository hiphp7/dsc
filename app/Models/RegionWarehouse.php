<?php

namespace App\Models;

use App\Entities\RegionWarehouse as Base;

/**
 * Class RegionWarehouse
 */
class RegionWarehouse extends Base
{
    /**
     * 关联同表父级信息
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getRegionWarehouse()
    {
        return $this->belongsTo('App\Models\RegionWarehouse', 'region_id', 'parent_id');
    }

    /**
     * 关联仓库地区商品
     *
     * @access  public
     * @param region_id
     * @return  array
     */
    public function getWarehouseAreaGoods()
    {
        return $this->hasOne('App\Models\WarehouseAreaGoods', 'region_id', 'region_id');
    }

    /**
     * 关联仓库地区商品
     *
     * @access  public
     * @param city_id
     * @return  array
     */
    public function getWarehouseAreaGoodsCity()
    {
        return $this->hasOne('App\Models\WarehouseAreaGoods', 'city_id', 'region_id');
    }

    /**
     * 关联仓库地区商品属性
     *
     * @access  public
     * @param area_id
     * @return  array
     */
    public function getWarehouseAreaAttr()
    {
        return $this->hasOne('App\Models\WarehouseAreaAttr', 'area_id', 'region_id');
    }
}
