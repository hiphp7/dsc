<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CouponsRegion
 */
class CouponsRegion extends Model
{
    protected $table = 'coupons_region';

    protected $primaryKey = 'cf_id';

    public $timestamps = false;

    protected $fillable = [
        'cou_id',
        'region_list'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCouId()
    {
        return $this->cou_id;
    }

    /**
     * @return mixed
     */
    public function getRegionList()
    {
        return $this->region_list;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouId($value)
    {
        $this->cou_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionList($value)
    {
        $this->region_list = $value;
        return $this;
    }
}
