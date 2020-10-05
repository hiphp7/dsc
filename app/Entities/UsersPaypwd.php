<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersPaypwd
 */
class UsersPaypwd extends Model
{
    protected $table = 'users_paypwd';

    protected $primaryKey = 'paypwd_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ec_salt',
        'pay_password',
        'pay_online',
        'user_surplus',
        'user_point',
        'baitiao',
        'gift_card'
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
    public function getEcSalt()
    {
        return $this->ec_salt;
    }

    /**
     * @return mixed
     */
    public function getPayPassword()
    {
        return $this->pay_password;
    }

    /**
     * @return mixed
     */
    public function getPayOnline()
    {
        return $this->pay_online;
    }

    /**
     * @return mixed
     */
    public function getUserSurplus()
    {
        return $this->user_surplus;
    }

    /**
     * @return mixed
     */
    public function getUserPoint()
    {
        return $this->user_point;
    }

    /**
     * @return mixed
     */
    public function getBaitiao()
    {
        return $this->baitiao;
    }

    /**
     * @return mixed
     */
    public function getGiftCard()
    {
        return $this->gift_card;
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
    public function setEcSalt($value)
    {
        $this->ec_salt = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayPassword($value)
    {
        $this->pay_password = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayOnline($value)
    {
        $this->pay_online = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserSurplus($value)
    {
        $this->user_surplus = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserPoint($value)
    {
        $this->user_point = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBaitiao($value)
    {
        $this->baitiao = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftCard($value)
    {
        $this->gift_card = $value;
        return $this;
    }
}
