<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsRegionInfo
 */
class MerchantsRegionInfo extends Model
{
    protected $table = 'merchants_region_info';

    public $timestamps = false;

    protected $fillable = [
        'ra_id',
        'region_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRaId()
    {
        return $this->ra_id;
    }

    /**
     * @return mixed
     */
    public function getRegionId()
    {
        return $this->region_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRaId($value)
    {
        $this->ra_id = $value;
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
}
