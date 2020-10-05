<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RegionWarehouse
 */
class RegionWarehouse extends Model
{
    protected $table = 'region_warehouse';

    protected $primaryKey = 'region_id';

    public $timestamps = false;

    protected $fillable = [
        'regionId',
        'parent_id',
        'region_name',
        'region_code',
        'region_type',
        'agency_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRegionId()
    {
        return $this->regionId;
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
    public function getRegionName()
    {
        return $this->region_name;
    }

    /**
     * @return mixed
     */
    public function getRegionCode()
    {
        return $this->region_code;
    }

    /**
     * @return mixed
     */
    public function getRegionType()
    {
        return $this->region_type;
    }

    /**
     * @return mixed
     */
    public function getAgencyId()
    {
        return $this->agency_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionId($value)
    {
        $this->regionId = $value;
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
    public function setRegionName($value)
    {
        $this->region_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionCode($value)
    {
        $this->region_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionType($value)
    {
        $this->region_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAgencyId($value)
    {
        $this->agency_id = $value;
        return $this;
    }
}
