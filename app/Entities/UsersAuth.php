<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersAuth
 */
class UsersAuth extends Model
{
    protected $table = 'users_auth';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'identity_type',
        'identifier',
        'credential',
        'verified',
        'add_time',
        'update_time'
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
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getIdentityType()
    {
        return $this->identity_type;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return mixed
     */
    public function getCredential()
    {
        return $this->credential;
    }

    /**
     * @return mixed
     */
    public function getVerified()
    {
        return $this->verified;
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
    public function getUpdateTime()
    {
        return $this->update_time;
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
    public function setIdentityType($value)
    {
        $this->identity_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIdentifier($value)
    {
        $this->identifier = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCredential($value)
    {
        $this->credential = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVerified($value)
    {
        $this->verified = $value;
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
    public function setUpdateTime($value)
    {
        $this->update_time = $value;
        return $this;
    }
}
