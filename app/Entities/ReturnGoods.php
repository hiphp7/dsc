<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ReturnGoods
 */
class ReturnGoods extends Model
{
    protected $table = 'return_goods';

    protected $primaryKey = 'rg_id';

    public $timestamps = false;

    protected $fillable = [
        'rec_id',
        'ret_id',
        'goods_id',
        'product_id',
        'product_sn',
        'goods_name',
        'brand_name',
        'goods_sn',
        'is_real',
        'goods_attr',
        'attr_id',
        'return_type',
        'return_number',
        'out_attr',
        'return_attr_id',
        'refound',
        'rate_price'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRecId()
    {
        return $this->rec_id;
    }

    /**
     * @return mixed
     */
    public function getRetId()
    {
        return $this->ret_id;
    }

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
    public function getProductId()
    {
        return $this->product_id;
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
    public function getGoodsName()
    {
        return $this->goods_name;
    }

    /**
     * @return mixed
     */
    public function getBrandName()
    {
        return $this->brand_name;
    }

    /**
     * @return mixed
     */
    public function getGoodsSn()
    {
        return $this->goods_sn;
    }

    /**
     * @return mixed
     */
    public function getIsReal()
    {
        return $this->is_real;
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
    public function getAttrId()
    {
        return $this->attr_id;
    }

    /**
     * @return mixed
     */
    public function getReturnType()
    {
        return $this->return_type;
    }

    /**
     * @return mixed
     */
    public function getReturnNumber()
    {
        return $this->return_number;
    }

    /**
     * @return mixed
     */
    public function getOutAttr()
    {
        return $this->out_attr;
    }

    /**
     * @return mixed
     */
    public function getReturnAttrId()
    {
        return $this->return_attr_id;
    }

    /**
     * @return mixed
     */
    public function getRefound()
    {
        return $this->refound;
    }

    /**
     * @return mixed
     */
    public function getRatePrice()
    {
        return $this->rate_price;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRecId($value)
    {
        $this->rec_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRetId($value)
    {
        $this->ret_id = $value;
        return $this;
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
    public function setProductId($value)
    {
        $this->product_id = $value;
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
    public function setGoodsName($value)
    {
        $this->goods_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandName($value)
    {
        $this->brand_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsSn($value)
    {
        $this->goods_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsReal($value)
    {
        $this->is_real = $value;
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
    public function setAttrId($value)
    {
        $this->attr_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnType($value)
    {
        $this->return_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnNumber($value)
    {
        $this->return_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOutAttr($value)
    {
        $this->out_attr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnAttrId($value)
    {
        $this->return_attr_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRefound($value)
    {
        $this->refound = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRatePrice($value)
    {
        $this->rate_price = $value;
        return $this;
    }
}
