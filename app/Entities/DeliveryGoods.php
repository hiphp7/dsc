<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DeliveryGoods
 */
class DeliveryGoods extends Model
{
    protected $table = 'delivery_goods';

    protected $primaryKey = 'rec_id';

    public $timestamps = false;

    protected $fillable = [
        'delivery_id',
        'goods_id',
        'product_id',
        'product_sn',
        'goods_name',
        'brand_name',
        'goods_sn',
        'is_real',
        'extension_code',
        'parent_id',
        'send_number',
        'goods_attr'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getDeliveryId()
    {
        return $this->delivery_id;
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
    public function getExtensionCode()
    {
        return $this->extension_code;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @return mixed
     */
    public function getSendNumber()
    {
        return $this->send_number;
    }

    /**
     * @return mixed
     */
    public function getGoodsAttr()
    {
        return $this->goods_attr;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDeliveryId($value)
    {
        $this->delivery_id = $value;
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
    public function setExtensionCode($value)
    {
        $this->extension_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendNumber($value)
    {
        $this->send_number = $value;
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
}
