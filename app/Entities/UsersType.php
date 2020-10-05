<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersType
 */
class UsersType extends Model
{
    protected $table = 'users_type';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'enterprise_personal',
        'companyname',
        'contactname',
        'companyaddress',
        'industry',
        'surname',
        'givenname',
        'agreement',
        'country',
        'province',
        'city',
        'district'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getEnterprisePersonal()
    {
        return $this->enterprise_personal;
    }

    /**
     * @return mixed
     */
    public function getCompanyname()
    {
        return $this->companyname;
    }

    /**
     * @return mixed
     */
    public function getContactname()
    {
        return $this->contactname;
    }

    /**
     * @return mixed
     */
    public function getCompanyaddress()
    {
        return $this->companyaddress;
    }

    /**
     * @return mixed
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * @return mixed
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * @return mixed
     */
    public function getGivenname()
    {
        return $this->givenname;
    }

    /**
     * @return mixed
     */
    public function getAgreement()
    {
        return $this->agreement;
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
     * @param $value
     * @return $this
     */
    public function setEnterprisePersonal($value)
    {
        $this->enterprise_personal = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyname($value)
    {
        $this->companyname = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactname($value)
    {
        $this->contactname = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyaddress($value)
    {
        $this->companyaddress = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIndustry($value)
    {
        $this->industry = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSurname($value)
    {
        $this->surname = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGivenname($value)
    {
        $this->givenname = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAgreement($value)
    {
        $this->agreement = $value;
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
}
