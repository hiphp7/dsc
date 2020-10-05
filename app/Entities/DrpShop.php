<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpShop
 */
class DrpShop extends Model
{
    protected $table = 'drp_shop';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'shop_name',
        'real_name',
        'mobile',
        'qq',
        'shop_img',
        'shop_portrait',
        'cat_id',
        'create_time',
        'isbuy',
        'audit',
        'status',
        'shop_money',
        'shop_points',
        'type',
        'credit_id',
        'frozen_money',
        'apply_channel',
        'open_time',
        'expiry_time',
        'membership_card_id',
        'expiry_type',
        'apply_time',
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
    public function getShopName()
    {
        return $this->shop_name;
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
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @return mixed
     */
    public function getQq()
    {
        return $this->qq;
    }

    /**
     * @return mixed
     */
    public function getShopImg()
    {
        return $this->shop_img;
    }

    /**
     * @return mixed
     */
    public function getShopPortrait()
    {
        return $this->shop_portrait;
    }

    /**
     * @return mixed
     */
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @return mixed
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * @return mixed
     */
    public function getIsbuy()
    {
        return $this->isbuy;
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getShopMoney()
    {
        return $this->shop_money;
    }

    /**
     * @return mixed
     */
    public function getShopPoints()
    {
        return $this->shop_points;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getCreditId()
    {
        return $this->credit_id;
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
    public function getApplyChannel()
    {
        return $this->apply_channel;
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
    public function setShopName($value)
    {
        $this->shop_name = $value;
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
    public function setMobile($value)
    {
        $this->mobile = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQq($value)
    {
        $this->qq = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopImg($value)
    {
        $this->shop_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopPortrait($value)
    {
        $this->shop_portrait = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatId($value)
    {
        $this->cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreateTime($value)
    {
        $this->create_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsbuy($value)
    {
        $this->isbuy = $value;
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
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopMoney($value)
    {
        $this->shop_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopPoints($value)
    {
        $this->shop_points = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreditId($value)
    {
        $this->credit_id = $value;
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
    public function setApplyChannel($value)
    {
        $this->apply_channel = $value;
        return $this;
    }
}
