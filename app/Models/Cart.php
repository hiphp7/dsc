<?php

namespace App\Models;

use App\Entities\Cart as Base;

/**
 * Class Cart
 */
class Cart extends Base
{
    public function goods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', "goods_id");
    }

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
     * 关联商品会员价格
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
     * 关联配件组件商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGroupGoods()
    {
        return $this->hasOne('App\Models\GroupGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联超值礼包商品
     *
     * @access  public
     * @param package_id
     * @return  array
     */
    public function getPackageGoods()
    {
        return $this->hasMany('App\Models\GoodsActivity', 'package_id', 'goods_id');
    }

    /**
     * 关联促销商品
     *
     * @access  public
     * @param act_id
     * @return  array
     */
    public function getGoodsActivity()
    {
        return $this->hasOne('App\Models\GoodsActivity', 'act_id', 'goods_id');
    }

    /**
     * 关联活动积分商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getExchangeGoods()
    {
        return $this->hasOne('App\Models\ExchangeGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联仓库商品
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
     * 关联地区商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseAreaGoods()
    {
        return $this->hasOne('App\Models\WarehouseAreaGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联仓库商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseGoodsList()
    {
        return $this->hasMany('App\Models\WarehouseGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联地区商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseAreaGoodsList()
    {
        return $this->hasMany('App\Models\WarehouseAreaGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联门店
     *
     * @access  public
     * @param id
     * @return  array
     */
    public function getOfflineStore()
    {
        return $this->hasOne('App\Models\OfflineStore', 'id', 'store_id');
    }

    /**
     * 关联门店地址信息
     *
     * @access  public
     * @param id
     * @return  array
     */
    public function getOfflineStoreArea()
    {
        return $this->hasOne('App\Models\OfflineStore', 'id', 'store_id')->with([
            'getRegionProvince',
            'getRegionCity',
            'getRegionDistrict',
        ]);
    }

    /**
     * 关联门店商品
     *
     * @access  public
     * @param store_id
     * @return  array
     */
    public function getStoreGoods()
    {
        return $this->hasOne('App\Models\StoreGoods', 'store_id', 'store_id');
    }

    /**
     * 关联购车配件商品
     *
     * @access  public
     * @param parent_id
     * @return  array
     */
    public function getCartParentGoods()
    {
        return $this->hasOne('App\Models\Cart', 'parent_id', 'goods_id');
    }

    /**
     * 关联session表
     *
     * @access  public
     * @param sesskey
     * @return  array
     */
    public function getSessions()
    {
        return $this->hasOne('App\Models\Sessions', 'sesskey', 'session_id');
    }

    public function getRegionWarehouse()
    {
        return $this->hasOne('App\Models\RegionWarehouse', 'region_id', 'warehouse_id');
    }

    public function getProductsWarehouse()
    {
        return $this->hasOne('App\Models\ProductsWarehouse', 'goods_id', 'goods_id');
    }

    public function getProductsArea()
    {
        return $this->hasOne('App\Models\ProductsArea', 'goods_id', 'goods_id');
    }

    public function getProducts()
    {
        return $this->hasOne('App\Models\Products', 'goods_id', 'goods_id');
    }

    public function getProductsWarehouseList()
    {
        return $this->hasMany('App\Models\ProductsWarehouse', 'goods_id', 'goods_id');
    }

    public function getProductsAreaList()
    {
        return $this->hasMany('App\Models\ProductsArea', 'goods_id', 'goods_id');
    }

    public function getProductsList()
    {
        return $this->hasMany('App\Models\Products', 'goods_id', 'goods_id');
    }

    public function getPresaleActivity()
    {
        return $this->hasOne('App\Models\PresaleActivity', 'goods_id', 'goods_id');
    }

    public function getCollectGoods()
    {
        return $this->hasOne('App\Models\CollectGoods', 'goods_id', 'goods_id');
    }
}
