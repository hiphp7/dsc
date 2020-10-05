<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WholesalePurchaseGoods
 */
class WholesalePurchaseGoods extends Model
{
    protected $table = 'wholesale_purchase_goods';

    protected $primaryKey = 'goods_id';

    public $timestamps = false;

    protected $fillable = [
        'purchase_id',
        'cat_id',
        'goods_name',
        'goods_number',
        'goods_price',
        'goods_img',
        'remarks'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPurchaseId()
    {
        return $this->purchase_id;
    }

    /**
     * @return mixed
     */
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsName()
    {
        return $this->goods_name;
    }

    /**
     * @return mixed
     */
    public function getGoodsNumber()
    {
        return $this->goods_number;
    }

    /**
     * @return mixed
     */
    public function getGoodsPrice()
    {
        return $this->goods_price;
    }

    /**
     * @return mixed
     */
    public function getGoodsImg()
    {
        return $this->goods_img;
    }

    /**
     * @return mixed
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPurchaseId($value)
    {
        $this->purchase_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatId($value)
    {
        $this->cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsName($value)
    {
        $this->goods_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsNumber($value)
    {
        $this->goods_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsPrice($value)
    {
        $this->goods_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsImg($value)
    {
        $this->goods_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRemarks($value)
    {
        $this->remarks = $value;
        return $this;
    }
}
