<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ShippingArea
 */
class ShippingArea extends Model
{
    protected $table = 'shipping_area';

    protected $primaryKey = 'shipping_area_id';

    public $timestamps = false;

    protected $fillable = [
        'shipping_area_name',
        'shipping_id',
        'configure',
        'ru_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getShippingAreaName()
    {
        return $this->shipping_area_name;
    }

    /**
     * @return mixed
     */
    public function getShippingId()
    {
        return $this->shipping_id;
    }

    /**
     * @return mixed
     */
    public function getConfigure()
    {
        return $this->configure;
    }

    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingAreaName($value)
    {
        $this->shipping_area_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingId($value)
    {
        $this->shipping_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConfigure($value)
    {
        $this->configure = $value;
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
}
