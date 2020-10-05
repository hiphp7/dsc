<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsStepsFields
 */
class MerchantsStepsFields extends Model
{
    protected $table = 'merchants_steps_fields';

    protected $primaryKey = 'fid';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'agreement',
        'steps_site',
        'site_process',
        'contactName',
        'contactPhone',
        'contactEmail',
        'organization_code',
        'organization_fileImg',
        'companyName',
        'business_license_id',
        'legal_person',
        'personalNo',
        'legal_person_fileImg',
        'license_comp_adress',
        'license_adress',
        'establish_date',
        'business_term',
        'shopTime_term',
        'busines_scope',
        'license_fileImg',
        'company_located',
        'company_adress',
        'company_contactTel',
        'company_tentactr',
        'company_phone',
        'taxpayer_id',
        'taxs_type',
        'taxs_num',
        'tax_fileImg',
        'status_tax_fileImg',
        'company_name',
        'account_number',
        'bank_name',
        'linked_bank_number',
        'linked_bank_address',
        'linked_bank_fileImg',
        'company_type',
        'company_website',
        'company_sale',
        'shop_seller_have_experience',
        'shop_website',
        'shop_employee_num',
        'shop_sale_num',
        'shop_average_price',
        'shop_warehouse_condition',
        'shop_warehouse_address',
        'shop_delicery_company',
        'shop_erp_type',
        'shop_operating_company',
        'shop_buy_ecmoban_store',
        'shop_buy_delivery',
        'preVendorId',
        'preVendorId_fileImg',
        'shop_vertical',
        'registered_capital',
        'contactXinbie',
        'is_distribution',
        'source',
        'country'
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
    public function getAgreement()
    {
        return $this->agreement;
    }

    /**
     * @return mixed
     */
    public function getStepsSite()
    {
        return $this->steps_site;
    }

    /**
     * @return mixed
     */
    public function getSiteProcess()
    {
        return $this->site_process;
    }

    /**
     * @return mixed
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * @return mixed
     */
    public function getContactPhone()
    {
        return $this->contactPhone;
    }

    /**
     * @return mixed
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * @return mixed
     */
    public function getOrganizationCode()
    {
        return $this->organization_code;
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
    public function getBusinessLicenseId()
    {
        return $this->business_license_id;
    }

    /**
     * @return mixed
     */
    public function getLegalPerson()
    {
        return $this->legal_person;
    }

    /**
     * @return mixed
     */
    public function getPersonalNo()
    {
        return $this->personalNo;
    }

    /**
     * @return mixed
     */
    public function getLegalPersonFileImg()
    {
        return $this->legal_person_fileImg;
    }

    /**
     * @return mixed
     */
    public function getLicenseCompAdress()
    {
        return $this->license_comp_adress;
    }

    /**
     * @return mixed
     */
    public function getLicenseAdress()
    {
        return $this->license_adress;
    }

    /**
     * @return mixed
     */
    public function getEstablishDate()
    {
        return $this->establish_date;
    }

    /**
     * @return mixed
     */
    public function getBusinessTerm()
    {
        return $this->business_term;
    }

    /**
     * @return mixed
     */
    public function getShopTimeTerm()
    {
        return $this->shopTime_term;
    }

    /**
     * @return mixed
     */
    public function getBusinesScope()
    {
        return $this->busines_scope;
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
    public function getCompanyLocated()
    {
        return $this->company_located;
    }

    /**
     * @return mixed
     */
    public function getCompanyAdress()
    {
        return $this->company_adress;
    }

    /**
     * @return mixed
     */
    public function getCompanyContactTel()
    {
        return $this->company_contactTel;
    }

    /**
     * @return mixed
     */
    public function getCompanyTentactr()
    {
        return $this->company_tentactr;
    }

    /**
     * @return mixed
     */
    public function getCompanyPhone()
    {
        return $this->company_phone;
    }

    /**
     * @return mixed
     */
    public function getTaxpayerId()
    {
        return $this->taxpayer_id;
    }

    /**
     * @return mixed
     */
    public function getTaxsType()
    {
        return $this->taxs_type;
    }

    /**
     * @return mixed
     */
    public function getTaxsNum()
    {
        return $this->taxs_num;
    }

    /**
     * @return mixed
     */
    public function getTaxFileImg()
    {
        return $this->tax_fileImg;
    }

    /**
     * @return mixed
     */
    public function getStatusTaxFileImg()
    {
        return $this->status_tax_fileImg;
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
    public function getAccountNumber()
    {
        return $this->account_number;
    }

    /**
     * @return mixed
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * @return mixed
     */
    public function getLinkedBankNumber()
    {
        return $this->linked_bank_number;
    }

    /**
     * @return mixed
     */
    public function getLinkedBankAddress()
    {
        return $this->linked_bank_address;
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
    public function getCompanyType()
    {
        return $this->company_type;
    }

    /**
     * @return mixed
     */
    public function getCompanyWebsite()
    {
        return $this->company_website;
    }

    /**
     * @return mixed
     */
    public function getCompanySale()
    {
        return $this->company_sale;
    }

    /**
     * @return mixed
     */
    public function getShopSellerHaveExperience()
    {
        return $this->shop_seller_have_experience;
    }

    /**
     * @return mixed
     */
    public function getShopWebsite()
    {
        return $this->shop_website;
    }

    /**
     * @return mixed
     */
    public function getShopEmployeeNum()
    {
        return $this->shop_employee_num;
    }

    /**
     * @return mixed
     */
    public function getShopSaleNum()
    {
        return $this->shop_sale_num;
    }

    /**
     * @return mixed
     */
    public function getShopAveragePrice()
    {
        return $this->shop_average_price;
    }

    /**
     * @return mixed
     */
    public function getShopWarehouseCondition()
    {
        return $this->shop_warehouse_condition;
    }

    /**
     * @return mixed
     */
    public function getShopWarehouseAddress()
    {
        return $this->shop_warehouse_address;
    }

    /**
     * @return mixed
     */
    public function getShopDeliceryCompany()
    {
        return $this->shop_delicery_company;
    }

    /**
     * @return mixed
     */
    public function getShopErpType()
    {
        return $this->shop_erp_type;
    }

    /**
     * @return mixed
     */
    public function getShopOperatingCompany()
    {
        return $this->shop_operating_company;
    }

    /**
     * @return mixed
     */
    public function getShopBuyEcmobanStore()
    {
        return $this->shop_buy_ecmoban_store;
    }

    /**
     * @return mixed
     */
    public function getShopBuyDelivery()
    {
        return $this->shop_buy_delivery;
    }

    /**
     * @return mixed
     */
    public function getPreVendorId()
    {
        return $this->preVendorId;
    }

    /**
     * @return mixed
     */
    public function getPreVendorIdFileImg()
    {
        return $this->preVendorId_fileImg;
    }

    /**
     * @return mixed
     */
    public function getShopVertical()
    {
        return $this->shop_vertical;
    }

    /**
     * @return mixed
     */
    public function getRegisteredCapital()
    {
        return $this->registered_capital;
    }

    /**
     * @return mixed
     */
    public function getContactXinbie()
    {
        return $this->contactXinbie;
    }

    /**
     * @return mixed
     */
    public function getIsDistribution()
    {
        return $this->is_distribution;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
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
    public function setAgreement($value)
    {
        $this->agreement = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStepsSite($value)
    {
        $this->steps_site = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSiteProcess($value)
    {
        $this->site_process = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactName($value)
    {
        $this->contactName = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactPhone($value)
    {
        $this->contactPhone = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactEmail($value)
    {
        $this->contactEmail = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrganizationCode($value)
    {
        $this->organization_code = $value;
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
    public function setCompanyName($value)
    {
        $this->companyName = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBusinessLicenseId($value)
    {
        $this->business_license_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLegalPerson($value)
    {
        $this->legal_person = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPersonalNo($value)
    {
        $this->personalNo = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLegalPersonFileImg($value)
    {
        $this->legal_person_fileImg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLicenseCompAdress($value)
    {
        $this->license_comp_adress = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLicenseAdress($value)
    {
        $this->license_adress = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEstablishDate($value)
    {
        $this->establish_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBusinessTerm($value)
    {
        $this->business_term = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopTimeTerm($value)
    {
        $this->shopTime_term = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBusinesScope($value)
    {
        $this->busines_scope = $value;
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
    public function setCompanyLocated($value)
    {
        $this->company_located = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyAdress($value)
    {
        $this->company_adress = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyContactTel($value)
    {
        $this->company_contactTel = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyTentactr($value)
    {
        $this->company_tentactr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyPhone($value)
    {
        $this->company_phone = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTaxpayerId($value)
    {
        $this->taxpayer_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTaxsType($value)
    {
        $this->taxs_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTaxsNum($value)
    {
        $this->taxs_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTaxFileImg($value)
    {
        $this->tax_fileImg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStatusTaxFileImg($value)
    {
        $this->status_tax_fileImg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAccountNumber($value)
    {
        $this->account_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankName($value)
    {
        $this->bank_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkedBankNumber($value)
    {
        $this->linked_bank_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkedBankAddress($value)
    {
        $this->linked_bank_address = $value;
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
    public function setCompanyType($value)
    {
        $this->company_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanyWebsite($value)
    {
        $this->company_website = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompanySale($value)
    {
        $this->company_sale = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopSellerHaveExperience($value)
    {
        $this->shop_seller_have_experience = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopWebsite($value)
    {
        $this->shop_website = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopEmployeeNum($value)
    {
        $this->shop_employee_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopSaleNum($value)
    {
        $this->shop_sale_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopAveragePrice($value)
    {
        $this->shop_average_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopWarehouseCondition($value)
    {
        $this->shop_warehouse_condition = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopWarehouseAddress($value)
    {
        $this->shop_warehouse_address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopDeliceryCompany($value)
    {
        $this->shop_delicery_company = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopErpType($value)
    {
        $this->shop_erp_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopOperatingCompany($value)
    {
        $this->shop_operating_company = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopBuyEcmobanStore($value)
    {
        $this->shop_buy_ecmoban_store = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopBuyDelivery($value)
    {
        $this->shop_buy_delivery = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPreVendorId($value)
    {
        $this->preVendorId = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPreVendorIdFileImg($value)
    {
        $this->preVendorId_fileImg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopVertical($value)
    {
        $this->shop_vertical = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegisteredCapital($value)
    {
        $this->registered_capital = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactXinbie($value)
    {
        $this->contactXinbie = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDistribution($value)
    {
        $this->is_distribution = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSource($value)
    {
        $this->source = $value;
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
}