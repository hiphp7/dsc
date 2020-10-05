<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BonusType
 */
class BonusType extends Model
{
    protected $table = 'bonus_type';

    protected $primaryKey = 'type_id';

    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'user_id',
        'type_money',
        'send_type',
        'usebonus_type',
        'min_amount',
        'max_amount',
        'send_start_date',
        'send_end_date',
        'use_start_date',
        'use_end_date',
        'min_goods_amount',
        'review_status',
        'review_content',
        'valid_period',
        'date_type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return $this->type_name;
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
    public function getTypeMoney()
    {
        return $this->type_money;
    }

    /**
     * @return mixed
     */
    public function getSendType()
    {
        return $this->send_type;
    }

    /**
     * @return mixed
     */
    public function getUsebonusType()
    {
        return $this->usebonus_type;
    }

    /**
     * @return mixed
     */
    public function getMinAmount()
    {
        return $this->min_amount;
    }

    /**
     * @return mixed
     */
    public function getMaxAmount()
    {
        return $this->max_amount;
    }

    /**
     * @return mixed
     */
    public function getSendStartDate()
    {
        return $this->send_start_date;
    }

    /**
     * @return mixed
     */
    public function getSendEndDate()
    {
        return $this->send_end_date;
    }

    /**
     * @return mixed
     */
    public function getUseStartDate()
    {
        return $this->use_start_date;
    }

    /**
     * @return mixed
     */
    public function getUseEndDate()
    {
        return $this->use_end_date;
    }

    /**
     * @return mixed
     */
    public function getMinGoodsAmount()
    {
        return $this->min_goods_amount;
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
    public function getReviewContent()
    {
        return $this->review_content;
    }

    /**
     * @return mixed
     */
    public function getValidPeriod()
    {
        return $this->valid_period;
    }

    /**
     * @return mixed
     */
    public function getDateType()
    {
        return $this->date_type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTypeName($value)
    {
        $this->type_name = $value;
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
    public function setTypeMoney($value)
    {
        $this->type_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendType($value)
    {
        $this->send_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUsebonusType($value)
    {
        $this->usebonus_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMinAmount($value)
    {
        $this->min_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMaxAmount($value)
    {
        $this->max_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendStartDate($value)
    {
        $this->send_start_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendEndDate($value)
    {
        $this->send_end_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseStartDate($value)
    {
        $this->use_start_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseEndDate($value)
    {
        $this->use_end_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMinGoodsAmount($value)
    {
        $this->min_goods_amount = $value;
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
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValidPeriod($value)
    {
        $this->valid_period = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDateType($value)
    {
        $this->date_type = $value;
        return $this;
    }
}
