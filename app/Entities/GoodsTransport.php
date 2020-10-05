<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsTransport
 */
class GoodsTransport extends Model
{
    protected $table = 'goods_transport';

    protected $primaryKey = 'tid';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'type',
        'freight_type',
        'title',
        'shipping_title',
        'free_money',
        'update_time'
    ];

    protected $guarded = [];


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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getFreightType()
    {
        return $this->freight_type;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getShippingTitle()
    {
        return $this->shipping_title;
    }

    /**
     * @return mixed
     */
    public function getFreeMoney()
    {
        return $this->free_money;
    }

    /**
     * @return mixed
     */
    public function getUpdateTime()
    {
        return $this->update_time;
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
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFreightType($value)
    {
        $this->freight_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitle($value)
    {
        $this->title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingTitle($value)
    {
        $this->shipping_title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFreeMoney($value)
    {
        $this->free_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUpdateTime($value)
    {
        $this->update_time = $value;
        return $this;
    }
}
