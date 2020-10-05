<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsShopInformation
 */
class MerchantsShopInformation extends Model
{
    protected $table = 'merchants_shop_information';

    protected $primaryKey = 'shop_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'region_id',
        'shoprz_type',
        'subShoprz_type',
        'shop_expireDateStart',
        'shop_expireDateEnd',
        'shop_permanent',
        'authorizeFile',
        'shop_hypermarketFile',
        'shop_categoryMain',
        'user_shopMain_category',
        'shoprz_brandName',
        'shop_class_keyWords',
        'shopNameSuffix',
        'rz_shopName',
        'hopeLoginName',
        'merchants_message',
        'allow_number',
        'steps_audit',
        'merchants_audit',
        'review_goods',
        'sort_order',
        'store_score',
        'is_street',
        'is_IM',
        'self_run',
        'shop_close',
        'add_time',
        'update_time'
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
    public function getRegionId()
    {
        return $this->region_id;
    }

    /**
     * @return mixed
     */
    public function getShoprzType()
    {
        return $this->shoprz_type;
    }

    /**
     * @return mixed
     */
    public function getSubShoprzType()
    {
        return $this->subShoprz_type;
    }

    /**
     * @return mixed
     */
    public function getShopExpireDateStart()
    {
        return $this->shop_expireDateStart;
    }

    /**
     * @return mixed
     */
    public function getShopExpireDateEnd()
    {
        return $this->shop_expireDateEnd;
    }

    /**
     * @return mixed
     */
    public function getShopPermanent()
    {
        return $this->shop_permanent;
    }

    /**
     * @return mixed
     */
    public function getAuthorizeFile()
    {
        return $this->authorizeFile;
    }

    /**
     * @return mixed
     */
    public function getShopHypermarketFile()
    {
        return $this->shop_hypermarketFile;
    }

    /**
     * @return mixed
     */
    public function getShopCategoryMain()
    {
        return $this->shop_categoryMain;
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
    public function getShoprzBrandName()
    {
        return $this->shoprz_brandName;
    }

    /**
     * @return mixed
     */
    public function getShopClassKeyWords()
    {
        return $this->shop_class_keyWords;
    }

    /**
     * @return mixed
     */
    public function getShopNameSuffix()
    {
        return $this->shopNameSuffix;
    }

    /**
     * @return mixed
     */
    public function getRzShopName()
    {
        return $this->rz_shopName;
    }

    /**
     * @return mixed
     */
    public function getHopeLoginName()
    {
        return $this->hopeLoginName;
    }

    /**
     * @return mixed
     */
    public function getMerchantsMessage()
    {
        return $this->merchants_message;
    }

    /**
     * @return mixed
     */
    public function getAllowNumber()
    {
        return $this->allow_number;
    }

    /**
     * @return mixed
     */
    public function getStepsAudit()
    {
        return $this->steps_audit;
    }

    /**
     * @return mixed
     */
    public function getMerchantsAudit()
    {
        return $this->merchants_audit;
    }

    /**
     * @return mixed
     */
    public function getReviewGoods()
    {
        return $this->review_goods;
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
    public function getStoreScore()
    {
        return $this->store_score;
    }

    /**
     * @return mixed
     */
    public function getIsStreet()
    {
        return $this->is_street;
    }

    /**
     * @return mixed
     */
    public function getIsIM()
    {
        return $this->is_IM;
    }

    /**
     * @return mixed
     */
    public function getSelfRun()
    {
        return $this->self_run;
    }

    /**
     * @return mixed
     */
    public function getShopClose()
    {
        return $this->shop_close;
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
    public function getUpdateTime()
    {
        return $this->update_time;
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
    public function setRegionId($value)
    {
        $this->region_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShoprzType($value)
    {
        $this->shoprz_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSubShoprzType($value)
    {
        $this->subShoprz_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopExpireDateStart($value)
    {
        $this->shop_expireDateStart = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopExpireDateEnd($value)
    {
        $this->shop_expireDateEnd = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopPermanent($value)
    {
        $this->shop_permanent = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAuthorizeFile($value)
    {
        $this->authorizeFile = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopHypermarketFile($value)
    {
        $this->shop_hypermarketFile = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopCategoryMain($value)
    {
        $this->shop_categoryMain = $value;
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
    public function setShoprzBrandName($value)
    {
        $this->shoprz_brandName = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopClassKeyWords($value)
    {
        $this->shop_class_keyWords = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopNameSuffix($value)
    {
        $this->shopNameSuffix = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRzShopName($value)
    {
        $this->rz_shopName = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHopeLoginName($value)
    {
        $this->hopeLoginName = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMerchantsMessage($value)
    {
        $this->merchants_message = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAllowNumber($value)
    {
        $this->allow_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStepsAudit($value)
    {
        $this->steps_audit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMerchantsAudit($value)
    {
        $this->merchants_audit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewGoods($value)
    {
        $this->review_goods = $value;
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
    public function setStoreScore($value)
    {
        $this->store_score = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsStreet($value)
    {
        $this->is_street = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsIM($value)
    {
        $this->is_IM = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSelfRun($value)
    {
        $this->self_run = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopClose($value)
    {
        $this->shop_close = $value;
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
    public function setUpdateTime($value)
    {
        $this->update_time = $value;
        return $this;
    }
}
