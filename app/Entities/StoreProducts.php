<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StoreProducts
 */
class StoreProducts extends Model
{
    protected $table = 'store_products';

    protected $primaryKey = 'product_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'goods_attr',
        'product_sn',
        'product_number',
        'ru_id',
        'store_id'
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
    public function getProductNumber()
    {
        return $this->product_number;
    }

    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->store_id;
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
    public function setProductNumber($value)
    {
        $this->product_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoreId($value)
    {
        $this->store_id = $value;
        return $this;
    }
}
