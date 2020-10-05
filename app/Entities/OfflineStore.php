<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OfflineStore
 */
class OfflineStore extends Model
{
    protected $table = 'offline_store';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'stores_user',
        'stores_pwd',
        'stores_name',
        'country',
        'province',
        'city',
        'district',
        'street',
        'stores_address',
        'stores_tel',
        'stores_opening_hours',
        'stores_traffic_line',
        'stores_img',
        'is_confirm',
        'add_time',
        'ec_salt'
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
    public function getStoresName()
    {
        return $this->stores_name;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return mixed
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @return mixed
     */
    public function getStoresAddress()
    {
        return $this->stores_address;
    }

    /**
     * @return mixed
     */
    public function getStoresTel()
    {
        return $this->stores_tel;
    }

    /**
     * @return mixed
     */
    public function getStoresOpeningHours()
    {
        return $this->stores_opening_hours;
    }

    /**
     * @return mixed
     */
    public function getStoresTrafficLine()
    {
        return $this->stores_traffic_line;
    }

    /**
     * @return mixed
     */
    public function getStoresImg()
    {
        return $this->stores_img;
    }

    /**
     * @return mixed
     */
    public function getIsConfirm()
    {
        return $this->is_confirm;
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
    public function setStoresName($value)
    {
        $this->stores_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCountry($value)
    {
        $this->country = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProvince($value)
    {
        $this->province = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCity($value)
    {
        $this->city = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDistrict($value)
    {
        $this->district = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStreet($value)
    {
        $this->street = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoresAddress($value)
    {
        $this->stores_address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoresTel($value)
    {
        $this->stores_tel = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoresOpeningHours($value)
    {
        $this->stores_opening_hours = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoresTrafficLine($value)
    {
        $this->stores_traffic_line = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoresImg($value)
    {
        $this->stores_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsConfirm($value)
    {
        $this->is_confirm = $value;
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
}
