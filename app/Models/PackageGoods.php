<?php

namespace App\Models;

use App\Entities\PackageGoods as Base;

/**
 * Class PackageGoods
 */
class PackageGoods extends Base
{
    /**
     * 关联商品货品
     *
     * @access  public
     * @param product_id
     * @return  array
     */
    public function getProducts()
    {
        return $this->hasOne('App\Models\Products', 'product_id', 'product_id');
    }

    /**
     * 关联仓库商品货品
     *
     * @access  public
     * @param product_id
     * @return  array
     */
    public function getProductsWarehouse()
    {
        return $this->hasOne('App\Models\ProductsWarehouse', 'product_id', 'product_id');
    }

    /**
     * 关联地区商品货品
     *
     * @access  public
     * @param product_id
     * @return  array
     */
    public function getProductsArea()
    {
        return $this->hasOne('App\Models\ProductsArea', 'product_id', 'product_id');
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
     * 关联商品数量
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function scopeGoodsNumber($query, $package_num = 0)
    {
        $res = $query->select('goods_id', 'goods_number AS package_number')
            ->with([
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'goods_number');
                }
            ]);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        $return = 0;
        if ($res) {
            if ($res['get_goods'] && $res['package_number'] * $package_num < $res['get_goods']['goods_number']) {
                $return = 1;
            }
        }

        return $return;
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
     * 关联仓库商品数量
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function scopeWarehouseGoodsNumber($query, $package_num = 0)
    {
        $res = $query->select('goods_id', 'goods_number AS package_number')
            ->with([
                'getWarehouseGoods' => function ($query) {
                    $query->select('goods_id', 'region_number');
                }
            ]);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        $return = 0;
        if ($res) {
            if ($res['get_warehouse_goods'] && $res['package_number'] * $package_num < $res['get_warehouse_goods']['region_number']) {
                $return = 1;
            }
        }

        return $return;
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
     * 关联商品属性
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoodsAttrList()
    {
        return $this->hasMany('App\Models\GoodsAttr', 'goods_id', 'goods_id');
    }
}
