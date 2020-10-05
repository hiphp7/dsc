<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersLog
 */
class UsersLog extends Model
{
    protected $table = 'users_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'admin_id',
        'change_time',
        'change_type',
        'ip_address',
        'change_city',
        'logon_service'
    ];

    protected $guarded = [];


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
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * @return mixed
     */
    public function getChangeTime()
    {
        return $this->change_time;
    }

    /**
     * @return mixed
     */
    public function getChangeType()
    {
        return $this->change_type;
    }

    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @return mixed
     */
    public function getChangeCity()
    {
        return $this->change_city;
    }

    /**
     * @return mixed
     */
    public function getLogonService()
    {
        return $this->logon_service;
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
    public function setAdminId($value)
    {
        $this->admin_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setChangeTime($value)
    {
        $this->change_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setChangeType($value)
    {
        $this->change_type = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setChangeCity($value)
    {
        $this->change_city = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogonService($value)
    {
        $this->logon_service = $value;
        return $this;
    }
}
