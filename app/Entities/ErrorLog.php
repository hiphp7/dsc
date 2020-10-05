<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ErrorLog
 */
class ErrorLog extends Model
{
    protected $table = 'error_log';

    public $timestamps = false;

    protected $fillable = [
        'info',
        'file',
        'time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInfo($value)
    {
        $this->info = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFile($value)
    {
        $this->file = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTime($value)
    {
        $this->time = $value;
        return $this;
    }
}
