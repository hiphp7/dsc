<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductsArea
 */
class ProductsArea extends Model
{
    protected $table = 'products_area';

    protected $primaryKey = 'product_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'goods_attr',
        'product_sn',
        'bar_code',
        'product_number',
        'product_price',
        'product_promote_price',
        'product_market_price',
        'product_warn_number',
        'area_id',
        'city_id',
        'admin_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsAttr()
    {
        return $this->goods_attr;
    }

    /**
     * @return mixed
     */
    public function getProductSn()
    {
        return $this->product_sn;
    }

    /**
     * @return mixed
     */
    public function getBarCode()
    {
        return $this->bar_code;
    }

    /**
     * @return mixed
     */
    public function getProductNumber()
    {
        return $this->product_number;
    }

    /**
     * @return mixed
     */
    public function getProductPrice()
    {
        return $this->product_price;
    }

    /**
     * @return mixed
     */
    public function getProductPromotePrice()
    {
        return $this->product_promote_price;
    }

    /**
     * @return mixed
     */
    public function getProductMarketPrice()
    {
        return $this->product_market_price;
    }

    /**
     * @return mixed
     */
    public function getProductWarnNumber()
    {
        return $this->product_warn_number;
    }

    /**
     * @return mixed
     */
    public function getAreaId()
    {
        return $this->area_id;
    }

    /**
     * @return mixed
     */
    public function getCityId()
    {
        return $this->city_id;
    }

    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsAttr($value)
    {
        $this->goods_attr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductSn($value)
    {
        $this->product_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBarCode($value)
    {
        $this->bar_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductNumber($value)
    {
        $this->product_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductPrice($value)
    {
        $this->product_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductPromotePrice($value)
    {
        $this->product_promote_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductMarketPrice($value)
    {
        $this->product_market_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductWarnNumber($value)
    {
        $this->product_warn_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAreaId($value)
    {
        $this->area_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCityId($value)
    {
        $this->city_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdminId($value)
    {
        $this->admin_id = $value;
        return $this;
    }
}
