<?php

namespace App\Models;

use App\Entities\Brand as Base;

/**
 * Class Brand
 */
class Brand extends Base
{
    /**
     * 关联品牌扩展信息
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getBrandExtend()
    {
        return $this->hasOne('App\Models\BrandExtend', 'brand_id', 'brand_id');
    }

    /**
     * 关联商品
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'brand_id', 'brand_id');
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
        return $this->hasMany('App\Models\Goods', 'brand_id', 'brand_id');
    }

    public function getCollectBrand()
    {
        return $this->hasOne('App\Models\CollectBrand', 'brand_id', 'brand_id');
    }
}
