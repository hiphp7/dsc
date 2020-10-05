<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Crons
 */
class Crons extends Model
{
    protected $table = 'crons';

    protected $primaryKey = 'cron_id';

    public $timestamps = false;

    protected $fillable = [
        'cron_code',
        'cron_name',
        'cron_desc',
        'cron_order',
        'cron_config',
        'thistime',
        'nextime',
        'day',
        'week',
        'hour',
        'minute',
        'enable',
        'run_once',
        'allow_ip',
        'alow_files'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCronCode()
    {
        return $this->cron_code;
    }

    /**
     * @return mixed
     */
    public function getCronName()
    {
        return $this->cron_name;
    }

    /**
     * @return mixed
     */
    public function getCronDesc()
    {
        return $this->cron_desc;
    }

    /**
     * @return mixed
     */
    public function getCronOrder()
    {
        return $this->cron_order;
    }

    /**
     * @return mixed
     */
    public function getCronConfig()
    {
        return $this->cron_config;
    }

    /**
     * @return mixed
     */
    public function getThistime()
    {
        return $this->thistime;
    }

    /**
     * @return mixed
     */
    public function getNextime()
    {
        return $this->nextime;
    }

    /**
     * @return mixed
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @return mixed
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * @return mixed
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * @return mixed
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * @return mixed
     */
    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * @return mixed
     */
    public function getRunOnce()
    {
        return $this->run_once;
    }

    /**
     * @return mixed
     */
    public function getAllowIp()
    {
        return $this->allow_ip;
    }

    /**
     * @return mixed
     */
    public function getAlowFiles()
    {
        return $this->alow_files;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCronCode($value)
    {
        $this->cron_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCronName($value)
    {
        $this->cron_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCronDesc($value)
    {
        $this->cron_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCronOrder($value)
    {
        $this->cron_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCronConfig($value)
    {
        $this->cron_config = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setThistime($value)
    {
        $this->thistime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNextime($value)
    {
        $this->nextime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDay($value)
    {
        $this->day = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWeek($value)
    {
        $this->week = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHour($value)
    {
        $this->hour = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMinute($value)
    {
        $this->minute = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEnable($value)
    {
        $this->enable = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRunOnce($value)
    {
        $this->run_once = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAllowIp($value)
    {
        $this->allow_ip = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAlowFiles($value)
    {
        $this->alow_files = $value;
        return $this;
    }
}
