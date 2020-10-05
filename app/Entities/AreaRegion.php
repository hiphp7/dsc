<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AreaRegion
 */
class AreaRegion extends Model
{
    protected $table = 'area_region';

    public $timestamps = false;

    protected $fillable = [
        'shipping_area_id',
        'region_id',
        'ru_id'
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
    public function getRegionId()
    {
        return $this->region_id;
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
    public function setShippingAreaId($value)
    {
        $this->shipping_area_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionId($value)
    {
        $this->region_id = $value;
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
