<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersVatInvoicesInfo
 */
class UsersVatInvoicesInfo extends Model
{
    protected $table = 'users_vat_invoices_info';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'company_name',
        'company_address',
        'tax_id',
        'company_telephone',
        'bank_of_deposit',
        'bank_account',
        'consignee_name',
        'consignee_mobile_phone',
        'consignee_address',
        'audit_status',
        'add_time',
        'country',
        'province',
        'city',
        'district'
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
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * @return mixed
     */
    public function getCompanyAddress()
    {
        return $this->company_address;
    }

    /**
     * @return mixed
     */
    public function getTaxId()
    {
        return $this->tax_id;
    }

    /**
     * @return mixed
     */
    public function getCompanyTelephone()
    {
        return $this->company_telephone;
    }

    /**
     * @return mixed
     */
    public function getBankOfDeposit()
    {
        return $this->bank_of_deposit;
    }

    /**
     * @return mixed
     */
    public function getBankAccount()
    {
        return $this->bank_account;
    }

    /**
     * @return mixed
     */
    public function getConsigneeName()
    {
        return $this->consignee_name;
    }

    /**
     * @return mixed
     */
    public function getConsigneeMobilePhone()
    {
        return $this->consignee_mobile_phone;
    }

    /**
     * @return mixed
     */
    public function getConsigneeAddress()
    {
        return $this->consignee_address;
    }

    /**
     * @return mixed
     */
    public function getAuditStatus()
    {
        return $this->audit_status;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyName($value)
    {
        $this->company_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyAddress($value)
    {
        $this->company_address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTaxId($value)
    {
        $this->tax_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyTelephone($value)
    {
        $this->company_telephone = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankOfDeposit($value)
    {
        $this->bank_of_deposit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankAccount($value)
    {
        $this->bank_account = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConsigneeName($value)
    {
        $this->consignee_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConsigneeMobilePhone($value)
    {
        $this->consignee_mobile_phone = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConsigneeAddress($value)
    {
        $this->consignee_address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAuditStatus($value)
    {
        $this->audit_status = $value;
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
