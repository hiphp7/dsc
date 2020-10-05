<?php

namespace App\Models;

use App\Entities\Goods as Base;

/**
 * Class Goods
 */
class Goods extends Base
{
    public function getGoodsExtend()
    {
        return $this->hasOne('App\Models\GoodsExtend', 'goods_id', 'goods_id');
    }

    public function getWholesaleCart()
    {
        return $this->hasOne('App\Models\WholesaleCart', 'goods_id', 'goods_id');
    }

    public function getWholesale()
    {
        return $this->hasOne('App\Models\Wholesale', 'goods_id', 'goods_id');
    }

    public function getMemberPrice()
    {
        return $this->hasOne('App\Models\MemberPrice', 'goods_id', 'goods_id');
    }

    public function getWarehouseGoods()
    {
        return $this->hasOne('App\Models\WarehouseGoods', 'goods_id', 'goods_id');
    }

    public function getWarehouseAreaGoods()
    {
        return $this->hasOne('App\Models\WarehouseAreaGoods', 'goods_id', 'goods_id');
    }

    public function getLinkAreaGoods()
    {
        return $this->hasOne('App\Models\LinkAreaGoods', 'goods_id', 'goods_id');
    }

    public function getGoodsCat()
    {
        return $this->hasOne('App\Models\GoodsCat', 'goods_id', 'goods_id');
    }

    public function getBrand()
    {
        return $this->hasOne('App\Models\Brand', 'brand_id', 'brand_id');
    }

    public function getShopInfo()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }

    public function getSellerShopInfo()
    {
        return $this->hasOne('App\Models\SellerShopinfo', 'ru_id', 'user_id');
    }

    public function getPresaleActivity()
    {
        return $this->hasOne('App\Models\PresaleActivity', 'goods_id', 'goods_id');
    }

    public function getGoodsActivity()
    {
        return $this->hasOne('App\Models\GoodsActivity', 'goods_id', 'goods_id');
    }

    public function getSeckillGoods()
    {
        return $this->hasOne('App\Models\SeckillGoods', 'goods_id', 'goods_id');
    }

    public function getGoodsCategory()
    {
        return $this->hasOne('App\Models\Category', 'cat_id', 'cat_id');
    }

    public function getGoodsAttribute()
    {
        return $this->hasOne('App\Models\Attribute', 'cat_id', 'goods_type');
    }

    public function getGoodsAttr()
    {
        return $this->hasOne('App\Models\GoodsAttr', 'goods_id', 'goods_id');
    }

    public function getGoodsAttrList()
    {
        return $this->hasMany('App\Models\GoodsAttr', 'goods_id', 'goods_id')->with([
            'getGoodsAttribute' => function ($query) {
                $query->orderByRaw('sort_order, attr_id');
            }
        ]);
    }

    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'goods_id', 'goods_id');
    }

    public function getOrderGoodsList()
    {
        return $this->hasMany('App\Models\OrderGoods', 'goods_id', 'goods_id');
    }

    public function getExchangeGoods()
    {
        return $this->hasOne('App\Models\ExchangeGoods', 'goods_id', 'goods_id');
    }

    public function getCategory()
    {
        return $this->hasOne('App\Models\Category', 'cat_id', 'cat_id');
    }

    public function getPackageGoods()
    {
        return $this->hasOne('App\Models\PackageGoods', 'goods_id', 'goods_id');
    }

    public function getCartCombo()
    {
        return $this->hasOne('App\Models\CartCombo', 'goods_id', 'goods_id');
    }

    public function getBonusType()
    {
        return $this->hasOne('App\Models\BonusType', 'type_id', 'bonus_type_id');
    }

    public function scopeBonusTypeInfo($query)
    {
        return $query->with(['getBonusType']);
    }

    public function getCollectGoods()
    {
        return $this->hasOne('App\Models\CollectGoods', 'goods_id', 'goods_id');
    }

    public function getWholesaleVolumePrice()
    {
        return $this->hasOne('App\Models\WholesaleVolumePrice', 'goods_id', 'goods_id');
    }

    public function getAutoManage()
    {
        return $this->hasOne('App\Models\AutoManage', 'item_id', 'goods_id');
    }

    public function getOrderReturn()
    {
        return $this->hasOne('App\Models\OrderReturn', 'goods_id', 'goods_id');
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

    public function getComment()
    {
        return $this->hasOne('App\Models\Comment', 'id_value', 'goods_id');
    }

    public function getGoodsConsumption()
    {
        return $this->hasMany('App\Models\GoodsConsumption', 'goods_id', 'goods_id');
    }
}
