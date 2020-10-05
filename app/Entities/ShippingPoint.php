<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ShippingPoint
 */
class ShippingPoint extends Model
{
    protected $table = 'shipping_point';

    public $timestamps = false;

    protected $fillable = [
        'shipping_area_id',
        'name',
        'user_name',
        'mobile',
        'address',
        'img_url',
        'anchor',
        'line'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getShippingAreaId()
    {
        return $this->shipping_area_id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getImgUrl()
    {
        return $this->img_url;
    }

    /**
     * @return mixed
     */
    public function getAnchor()
    {
        return $this->anchor;
    }

    /**
     * @return mixed
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingAreaId($value)
    {
        $this->shipping_area_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMobile($value)
    {
        $this->mobile = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddress($value)
    {
        $this->address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgUrl($value)
    {
        $this->img_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAnchor($value)
    {
        $this->anchor = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLine($value)
    {
        $this->line = $value;
        return $this;
    }
}
