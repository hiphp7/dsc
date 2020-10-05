<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class VolumePrice
 */
class VolumePrice extends Model
{
    protected $table = 'volume_price';

    public $timestamps = false;

    protected $fillable = [
        'price_type',
        'goods_id',
        'volume_number',
        'volume_price'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPriceType()
    {
        return $this->price_type;
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
    public function getVolumeNumber()
    {
        return $this->volume_number;
    }

    /**
     * @return mixed
     */
    public function getVolumePrice()
    {
        return $this->volume_price;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPriceType($value)
    {
        $this->price_type = $value;
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
    public function setVolumeNumber($value)
    {
        $this->volume_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVolumePrice($value)
    {
        $this->volume_price = $value;
        return $this;
    }
}
