<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Sessions
 */
class Sessions extends Model
{
    protected $table = 'sessions';

    protected $primaryKey = 'sesskey';

    public $timestamps = false;

    protected $fillable = [
        'expiry',
        'userid',
        'adminid',
        'ip',
        'user_name',
        'user_rank',
        'discount',
        'email',
        'data'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * @return mixed
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * @return mixed
     */
    public function getAdminid()
    {
        return $this->adminid;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getUserRank()
    {
        return $this->user_rank;
    }

    /**
     * @return mixed
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpiry($value)
    {
        $this->expiry = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserid($value)
    {
        $this->userid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdminid($value)
    {
        $this->adminid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIp($value)
    {
        $this->ip = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserRank($value)
    {
        $this->user_rank = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDiscount($value)
    {
        $this->discount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEmail($value)
    {
        $this->email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setData($value)
    {
        $this->data = $value;
        return $this;
    }
}
