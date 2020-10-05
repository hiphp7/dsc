<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StoreUser
 */
class StoreUser extends Model
{
    protected $table = 'store_user';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'store_id',
        'parent_id',
        'stores_user',
        'stores_pwd',
        'tel',
        'email',
        'store_action',
        'add_time',
        'ec_salt',
        'store_user_img'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->store_id;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @return mixed
     */
    public function getStoresUser()
    {
        return $this->stores_user;
    }

    /**
     * @return mixed
     */
    public function getStoresPwd()
    {
        return $this->stores_pwd;
    }

    /**
     * @return mixed
     */
    public function getTel()
    {
        return $this->tel;
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
    public function getStoreAction()
    {
        return $this->store_action;
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
    public function getEcSalt()
    {
        return $this->ec_salt;
    }

    /**
     * @return mixed
     */
    public function getStoreUserImg()
    {
        return $this->store_user_img;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoreId($value)
    {
        $this->store_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoresUser($value)
    {
        $this->stores_user = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoresPwd($value)
    {
        $this->stores_pwd = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTel($value)
    {
        $this->tel = $value;
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
    public function setStoreAction($value)
    {
        $this->store_action = $value;
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
    public function setEcSalt($value)
    {
        $this->ec_salt = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoreUserImg($value)
    {
        $this->store_user_img = $value;
        return $this;
    }
}
