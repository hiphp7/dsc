<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ActivityGoodsAttr
 */
class ActivityGoodsAttr extends Model
{
    protected $table = 'activity_goods_attr';

    public $timestamps = false;

    protected $fillable = [
        'bargain_id',
        'goods_id',
        'product_id',
        'target_price',
        'type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBargainId()
    {
        return $this->bargain_id;
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
    public function getTargetPrice()
    {
        return $this->target_price;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBargainId($value)
    {
        $this->bargain_id = $value;
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
    public function setTargetPrice($value)
    {
        $this->target_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }
}
