<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BargainStatistics
 */
class BargainStatistics extends Model
{
    protected $table = 'bargain_statistics';

    public $timestamps = false;

    protected $fillable = [
        'bs_id',
        'user_id',
        'subtract_price',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBsId()
    {
        return $this->bs_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getSubtractPrice()
    {
        return $this->subtract_price;
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
    public function setBsId($value)
    {
        $this->bs_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSubtractPrice($value)
    {
        $this->subtract_price = $value;
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
