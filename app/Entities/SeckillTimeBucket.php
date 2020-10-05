<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SeckillTimeBucket
 */
class SeckillTimeBucket extends Model
{
    protected $table = 'seckill_time_bucket';

    public $timestamps = false;

    protected $fillable = [
        'begin_time',
        'end_time',
        'title'
    ];

    protected $guarded = [];


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
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
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
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitle($value)
    {
        $this->title = $value;
        return $this;
    }
}
