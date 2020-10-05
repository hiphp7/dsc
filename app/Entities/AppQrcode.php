<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AppQrcode
 */
class AppQrcode extends Model
{
    protected $table = 'app_qrcode';

    public $timestamps = false;

    protected $fillable = [
        'login_code',
        'user_id',
        'user_name',
        'token',
        'sid',
        'is_ok',
        'login_time',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getLoginCode()
    {
        return $this->login_code;
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
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getSid()
    {
        return $this->sid;
    }

    /**
     * @return mixed
     */
    public function getIsOk()
    {
        return $this->is_ok;
    }

    /**
     * @return mixed
     */
    public function getLoginTime()
    {
        return $this->login_time;
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
    public function setLoginCode($value)
    {
        $this->login_code = $value;
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
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setToken($value)
    {
        $this->token = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSid($value)
    {
        $this->sid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsOk($value)
    {
        $this->is_ok = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLoginTime($value)
    {
        $this->login_time = $value;
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
