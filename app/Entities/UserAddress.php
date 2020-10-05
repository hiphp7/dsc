<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAddress
 */
class UserAddress extends Model
{
    protected $table = 'user_address';

    protected $primaryKey = 'address_id';

    public $timestamps = false;

    protected $fillable = [
        'address_name',
        'user_id',
        'consignee',
        'email',
        'country',
        'province',
        'city',
        'district',
        'street',
        'address',
        'zipcode',
        'tel',
        'mobile',
        'sign_building',
        'best_time',
        'audit',
        'update_time',
        'rel_name',
        'id_num',
        'real_status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getAddressName()
    {
        return $this->address_name;
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
    public function getConsignee()
    {
        return $this->consignee;
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
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getZipcode()
    {
        return $this->zipcode;
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
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @return mixed
     */
    public function getSignBuilding()
    {
        return $this->sign_building;
    }

    /**
     * @return mixed
     */
    public function getBestTime()
    {
        return $this->best_time;
    }

    /**
     * @return mixed
     */
    public function getAudit()
    {
        return $this->audit;
    }

    /**
     * @return mixed
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * @return mixed
     */
    public function getRelName()
    {
        return $this->rel_name;
    }

    /**
     * @return mixed
     */
    public function getIdNum()
    {
        return $this->id_num;
    }

    /**
     * @return mixed
     */
    public function getRealStatus()
    {
        return $this->real_status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddressName($value)
    {
        $this->address_name = $value;
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
    public function setConsignee($value)
    {
        $this->consignee = $value;
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
    public function setAddress($value)
    {
        $this->address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setZipcode($value)
    {
        $this->zipcode = $value;
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
    public function setMobile($value)
    {
        $this->mobile = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSignBuilding($value)
    {
        $this->sign_building = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBestTime($value)
    {
        $this->best_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAudit($value)
    {
        $this->audit = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setRelName($value)
    {
        $this->rel_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIdNum($value)
    {
        $this->id_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRealStatus($value)
    {
        $this->real_status = $value;
        return $this;
    }
}
