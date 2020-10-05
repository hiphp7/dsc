<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ShippingDate
 */
class ShippingDate extends Model
{
    protected $table = 'shipping_date';

    protected $primaryKey = 'shipping_date_id';

    public $timestamps = false;

    protected $fillable = [
        'start_date',
        'end_date',
        'select_day',
        'select_date'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @return mixed
     */
    public function getSelectDay()
    {
        return $this->select_day;
    }

    /**
     * @return mixed
     */
    public function getSelectDate()
    {
        return $this->select_date;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStartDate($value)
    {
        $this->start_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndDate($value)
    {
        $this->end_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSelectDay($value)
    {
        $this->select_day = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSelectDate($value)
    {
        $this->select_date = $value;
        return $this;
    }
}
