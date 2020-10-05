<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsRegionArea
 */
class MerchantsRegionArea extends Model
{
    protected $table = 'merchants_region_area';

    protected $primaryKey = 'ra_id';

    public $timestamps = false;

    protected $fillable = [
        'ra_name',
        'ra_sort',
        'add_time',
        'up_titme'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRaName()
    {
        return $this->ra_name;
    }

    /**
     * @return mixed
     */
    public function getRaSort()
    {
        return $this->ra_sort;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getUpTitme()
    {
        return $this->up_titme;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRaName($value)
    {
        $this->ra_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRaSort($value)
    {
        $this->ra_sort = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUpTitme($value)
    {
        $this->up_titme = $value;
        return $this;
    }
}
