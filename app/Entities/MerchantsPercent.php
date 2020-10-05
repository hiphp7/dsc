<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsPercent
 */
class MerchantsPercent extends Model
{
    protected $table = 'merchants_percent';

    protected $primaryKey = 'percent_id';

    public $timestamps = false;

    protected $fillable = [
        'percent_value',
        'sort_order',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPercentValue()
    {
        return $this->percent_value;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPercentValue($value)
    {
        $this->percent_value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
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
}
