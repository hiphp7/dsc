<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AdminLog
 */
class AdminLog extends Model
{
    protected $table = 'admin_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'log_time',
        'user_id',
        'log_info',
        'ip_address'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getLogTime()
    {
        return $this->log_time;
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
    public function getLogInfo()
    {
        return $this->log_info;
    }

    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogTime($value)
    {
        $this->log_time = $value;
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
    public function setLogInfo($value)
    {
        $this->log_info = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIpAddress($value)
    {
        $this->ip_address = $value;
        return $this;
    }
}
