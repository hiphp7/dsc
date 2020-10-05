<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Seckill
 */
class Seckill extends Model
{
    protected $table = 'seckill';

    protected $primaryKey = 'sec_id';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'acti_title',
        'begin_time',
        'is_putaway',
        'acti_time',
        'add_time',
        'review_status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getActiTitle()
    {
        return $this->acti_title;
    }

    /**
     * @return mixed
     */
    public function getBeginTime()
    {
        return $this->begin_time;
    }

    /**
     * @return mixed
     */
    public function getIsPutaway()
    {
        return $this->is_putaway;
    }

    /**
     * @return mixed
     */
    public function getActiTime()
    {
        return $this->acti_time;
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
    public function getReviewStatus()
    {
        return $this->review_status;
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

    /**
     * @param $value
     * @return $this
     */
    public function setActiTitle($value)
    {
        $this->acti_title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBeginTime($value)
    {
        $this->begin_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsPutaway($value)
    {
        $this->is_putaway = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActiTime($value)
    {
        $this->acti_time = $value;
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
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }
}
