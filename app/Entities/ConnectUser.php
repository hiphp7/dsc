<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ConnectUser
 */
class ConnectUser extends Model
{
    protected $table = 'connect_user';

    public $timestamps = false;

    protected $fillable = [
        'connect_code',
        'user_id',
        'is_admin',
        'open_id',
        'refresh_token',
        'access_token',
        'profile',
        'create_at',
        'expires_in',
        'expires_at',
        'user_type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getConnectCode()
    {
        return $this->connect_code;
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
    public function getIsAdmin()
    {
        return $this->is_admin;
    }

    /**
     * @return mixed
     */
    public function getOpenId()
    {
        return $this->open_id;
    }

    /**
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * @return mixed
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @return mixed
     */
    public function getCreateAt()
    {
        return $this->create_at;
    }

    /**
     * @return mixed
     */
    public function getExpiresIn()
    {
        return $this->expires_in;
    }

    /**
     * @return mixed
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * @return mixed
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConnectCode($value)
    {
        $this->connect_code = $value;
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
    public function setIsAdmin($value)
    {
        $this->is_admin = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOpenId($value)
    {
        $this->open_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRefreshToken($value)
    {
        $this->refresh_token = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAccessToken($value)
    {
        $this->access_token = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProfile($value)
    {
        $this->profile = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreateAt($value)
    {
        $this->create_at = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpiresIn($value)
    {
        $this->expires_in = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpiresAt($value)
    {
        $this->expires_at = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserType($value)
    {
        $this->user_type = $value;
        return $this;
    }
}
