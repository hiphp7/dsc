<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Region
 */
class Region extends Model
{
    protected $table = 'region';

    protected $primaryKey = 'region_id';

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'region_name',
        'region_type',
        'agency_id'
    ];

    protected $guarded = [];


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
