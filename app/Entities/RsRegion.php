<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RsRegion
 */
class RsRegion extends Model
{
    protected $table = 'rs_region';

    public $timestamps = false;

    protected $fillable = [
        'rs_id',
        'region_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRsId()
    {
        return $this->rs_id;
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
    public function setRsId($value)
    {
        $this->rs_id = $value;
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
