<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class KdniaoCustomerAccount
 */
class KdniaoCustomerAccount extends Model
{
    protected $table = 'kdniao_customer_account';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'shipping_id',
        'shipper_code',
        'station_code',
        'station_name',
        'apply_id',
        'company',
        'name',
        'tel',
        'mobile',
        'province_name',
        'province_code',
        'city_name',
        'city_code',
        'exp_area_name',
        'exp_area_code',
        'address',
        'province',
        'city',
        'district'
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
    public function getShippingId()
    {
        return $this->shipping_id;
    }

    /**
     * @return mixed
     */
    public function getShipperCode()
    {
        return $this->shipper_code;
    }

    /**
     * @return mixed
     */
    public function getStationCode()
    {
        return $this->station_code;
    }

    /**
     * @return mixed
     */
    public function getStationName()
    {
        return $this->station_name;
    }

    /**
     * @return mixed
     */
    public function getApplyId()
    {
        return $this->apply_id;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
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
    public function getProvinceName()
    {
        return $this->province_name;
    }

    /**
     * @return mixed
     */
    public function getProvinceCode()
    {
        return $this->province_code;
    }

    /**
     * @return mixed
     */
    public function getCityName()
    {
        return $this->city_name;
    }

    /**
     * @return mixed
     */
    public function getCityCode()
    {
        return $this->city_code;
    }

    /**
     * @return mixed
     */
    public function getExpAreaName()
    {
        return $this->exp_area_name;
    }

    /**
     * @return mixed
     */
    public function getExpAreaCode()
    {
        return $this->exp_area_code;
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
    public function setShippingId($value)
    {
        $this->shipping_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShipperCode($value)
    {
        $this->shipper_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStationCode($value)
    {
        $this->station_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStationName($value)
    {
        $this->station_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setApplyId($value)
    {
        $this->apply_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompany($value)
    {
        $this->company = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
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
    public function setProvinceName($value)
    {
        $this->province_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProvinceCode($value)
    {
        $this->province_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCityName($value)
    {
        $this->city_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCityCode($value)
    {
        $this->city_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpAreaName($value)
    {
        $this->exp_area_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpAreaCode($value)
    {
        $this->exp_area_code = $value;
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
}
