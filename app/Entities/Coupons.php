<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Coupons
 */
class Coupons extends Model
{
    protected $table = 'coupons';

    protected $primaryKey = 'cou_id';

    public $timestamps = false;

    protected $fillable = [
        'cou_name',
        'cou_total',
        'cou_man',
        'cou_money',
        'cou_user_num',
        'cou_goods',
        'spec_cat',
        'cou_start_time',
        'cou_end_time',
        'cou_type',
        'cou_get_man',
        'cou_ok_user',
        'cou_ok_goods',
        'cou_ok_cat',
        'cou_intro',
        'cou_add_time',
        'ru_id',
        'cou_order',
        'cou_title',
        'review_status',
        'review_content'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCouName()
    {
        return $this->cou_name;
    }

    /**
     * @return mixed
     */
    public function getCouTotal()
    {
        return $this->cou_total;
    }

    /**
     * @return mixed
     */
    public function getCouMan()
    {
        return $this->cou_man;
    }

    /**
     * @return mixed
     */
    public function getCouMoney()
    {
        return $this->cou_money;
    }

    /**
     * @return mixed
     */
    public function getCouUserNum()
    {
        return $this->cou_user_num;
    }

    /**
     * @return mixed
     */
    public function getCouGoods()
    {
        return $this->cou_goods;
    }

    /**
     * @return mixed
     */
    public function getSpecCat()
    {
        return $this->spec_cat;
    }

    /**
     * @return mixed
     */
    public function getCouStartTime()
    {
        return $this->cou_start_time;
    }

    /**
     * @return mixed
     */
    public function getCouEndTime()
    {
        return $this->cou_end_time;
    }

    /**
     * @return mixed
     */
    public function getCouType()
    {
        return $this->cou_type;
    }

    /**
     * @return mixed
     */
    public function getCouGetMan()
    {
        return $this->cou_get_man;
    }

    /**
     * @return mixed
     */
    public function getCouOkUser()
    {
        return $this->cou_ok_user;
    }

    /**
     * @return mixed
     */
    public function getCouOkGoods()
    {
        return $this->cou_ok_goods;
    }

    /**
     * @return mixed
     */
    public function getCouOkCat()
    {
        return $this->cou_ok_cat;
    }

    /**
     * @return mixed
     */
    public function getCouIntro()
    {
        return $this->cou_intro;
    }

    /**
     * @return mixed
     */
    public function getCouAddTime()
    {
        return $this->cou_add_time;
    }

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
    public function getCouOrder()
    {
        return $this->cou_order;
    }

    /**
     * @return mixed
     */
    public function getCouTitle()
    {
        return $this->cou_title;
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
     * @param $value
     * @return $this
     */
    public function setCouName($value)
    {
        $this->cou_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouTotal($value)
    {
        $this->cou_total = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouMan($value)
    {
        $this->cou_man = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouMoney($value)
    {
        $this->cou_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouUserNum($value)
    {
        $this->cou_user_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouGoods($value)
    {
        $this->cou_goods = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSpecCat($value)
    {
        $this->spec_cat = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouStartTime($value)
    {
        $this->cou_start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouEndTime($value)
    {
        $this->cou_end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouType($value)
    {
        $this->cou_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouGetMan($value)
    {
        $this->cou_get_man = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouOkUser($value)
    {
        $this->cou_ok_user = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouOkGoods($value)
    {
        $this->cou_ok_goods = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouOkCat($value)
    {
        $this->cou_ok_cat = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouIntro($value)
    {
        $this->cou_intro = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouAddTime($value)
    {
        $this->cou_add_time = $value;
        return $this;
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
    public function setCouOrder($value)
    {
        $this->cou_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouTitle($value)
    {
        $this->cou_title = $value;
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
}
