<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Suppliers
 */
class Suppliers extends Model
{
    protected $table = 'suppliers';

    protected $primaryKey = 'suppliers_id';

    public $timestamps = false;

    protected $fillable = [
        'suppliers_name',
        'suppliers_desc',
        'is_check',
        'user_id',
        'real_name',
        'self_num',
        'company_name',
        'company_address',
        'front_of_id_card',
        'reverse_of_id_card',
        'suppliers_logo',
        'license_fileImg',
        'organization_fileImg',
        'linked_bank_fileImg',
        'region_id',
        'user_shopMain_category',
        'mobile_phone',
        'email',
        'add_time',
        'review_status',
        'suppliers_money',
        'frozen_money',
        'suppliers_percent',
        'kf_qq',
        'review_content'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getSuppliersName()
    {
        return $this->suppliers_name;
    }

    /**
     * @return mixed
     */
    public function getSuppliersDesc()
    {
        return $this->suppliers_desc;
    }

    /**
     * @return mixed
     */
    public function getIsCheck()
    {
        return $this->is_check;
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
    public function getRealName()
    {
        return $this->real_name;
    }

    /**
     * @return mixed
     */
    public function getSelfNum()
    {
        return $this->self_num;
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
    public function getFrontOfIdCard()
    {
        return $this->front_of_id_card;
    }

    /**
     * @return mixed
     */
    public function getReverseOfIdCard()
    {
        return $this->reverse_of_id_card;
    }

    /**
     * @return mixed
     */
    public function getSuppliersLogo()
    {
        return $this->suppliers_logo;
    }

    /**
     * @return mixed
     */
    public function getLicenseFileImg()
    {
        return $this->license_fileImg;
    }

    /**
     * @return mixed
     */
    public function getOrganizationFileImg()
    {
        return $this->organization_fileImg;
    }

    /**
     * @return mixed
     */
    public function getLinkedBankFileImg()
    {
        return $this->linked_bank_fileImg;
    }

    /**
     * @return mixed
     */
    public function getRegionId()
    {
        return $this->region_id;
    }

    /**
     * @return mixed
     */
    public function getUserShopMainCategory()
    {
        return $this->user_shopMain_category;
    }

    /**
     * @return mixed
     */
    public function getMobilePhone()
    {
        return $this->mobile_phone;
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
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getSuppliersMoney()
    {
        return $this->suppliers_money;
    }

    /**
     * @return mixed
     */
    public function getFrozenMoney()
    {
        return $this->frozen_money;
    }

    /**
     * @return mixed
     */
    public function getSuppliersPercent()
    {
        return $this->suppliers_percent;
    }

    /**
     * @return mixed
     */
    public function getKfQq()
    {
        return $this->kf_qq;
    }

    /**
     * @return mixed
     */
    public function getReviewContent()
    {
        return $this->review_content;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersName($value)
    {
        $this->suppliers_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersDesc($value)
    {
        $this->suppliers_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsCheck($value)
    {
        $this->is_check = $value;
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
    public function setRealName($value)
    {
        $this->real_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSelfNum($value)
    {
        $this->self_num = $value;
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
    public function setFrontOfIdCard($value)
    {
        $this->front_of_id_card = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReverseOfIdCard($value)
    {
        $this->reverse_of_id_card = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersLogo($value)
    {
        $this->suppliers_logo = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLicenseFileImg($value)
    {
        $this->license_fileImg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrganizationFileImg($value)
    {
        $this->organization_fileImg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkedBankFileImg($value)
    {
        $this->linked_bank_fileImg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionId($value)
    {
        $this->region_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserShopMainCategory($value)
    {
        $this->user_shopMain_category = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMobilePhone($value)
    {
        $this->mobile_phone = $value;
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
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersMoney($value)
    {
        $this->suppliers_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFrozenMoney($value)
    {
        $this->frozen_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersPercent($value)
    {
        $this->suppliers_percent = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKfQq($value)
    {
        $this->kf_qq = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }
}
