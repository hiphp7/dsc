<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsShopBrand
 */
class MerchantsShopBrand extends Model
{
    protected $table = 'merchants_shop_brand';

    protected $primaryKey = 'bid';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'bank_name_letter',
        'brandName',
        'brandFirstChar',
        'brandLogo',
        'brandType',
        'brand_operateType',
        'brandEndTime',
        'brandEndTime_permanent',
        'site_url',
        'brand_desc',
        'sort_order',
        'is_show',
        'is_delete',
        'major_business',
        'audit_status',
        'add_time'
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
    public function getBankNameLetter()
    {
        return $this->bank_name_letter;
    }

    /**
     * @return mixed
     */
    public function getBrandName()
    {
        return $this->brandName;
    }

    /**
     * @return mixed
     */
    public function getBrandFirstChar()
    {
        return $this->brandFirstChar;
    }

    /**
     * @return mixed
     */
    public function getBrandLogo()
    {
        return $this->brandLogo;
    }

    /**
     * @return mixed
     */
    public function getBrandType()
    {
        return $this->brandType;
    }

    /**
     * @return mixed
     */
    public function getBrandOperateType()
    {
        return $this->brand_operateType;
    }

    /**
     * @return mixed
     */
    public function getBrandEndTime()
    {
        return $this->brandEndTime;
    }

    /**
     * @return mixed
     */
    public function getBrandEndTimePermanent()
    {
        return $this->brandEndTime_permanent;
    }

    /**
     * @return mixed
     */
    public function getSiteUrl()
    {
        return $this->site_url;
    }

    /**
     * @return mixed
     */
    public function getBrandDesc()
    {
        return $this->brand_desc;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * @return mixed
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * @return mixed
     */
    public function getMajorBusiness()
    {
        return $this->major_business;
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
    public function setBankNameLetter($value)
    {
        $this->bank_name_letter = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandName($value)
    {
        $this->brandName = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandFirstChar($value)
    {
        $this->brandFirstChar = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandLogo($value)
    {
        $this->brandLogo = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandType($value)
    {
        $this->brandType = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandOperateType($value)
    {
        $this->brand_operateType = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandEndTime($value)
    {
        $this->brandEndTime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandEndTimePermanent($value)
    {
        $this->brandEndTime_permanent = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSiteUrl($value)
    {
        $this->site_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandDesc($value)
    {
        $this->brand_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsShow($value)
    {
        $this->is_show = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDelete($value)
    {
        $this->is_delete = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMajorBusiness($value)
    {
        $this->major_business = $value;
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
}
