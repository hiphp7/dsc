<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ZcProgress
 */
class ZcProgress extends Model
{
    protected $table = 'zc_progress';

    public $timestamps = false;

    protected $fillable = [
        'pid',
        'progress',
        'add_time',
        'img'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return mixed
     */
    public function getProgress()
    {
        return $this->progress;
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
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPid($value)
    {
        $this->pid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProgress($value)
    {
        $this->progress = $value;
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
    public function setImg($value)
    {
        $this->img = $value;
        return $this;
    }
}
