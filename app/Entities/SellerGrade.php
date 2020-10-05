<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerGrade
 */
class SellerGrade extends Model
{
    protected $table = 'seller_grade';

    public $timestamps = false;

    protected $fillable = [
        'grade_name',
        'goods_sun',
        'seller_temp',
        'favorable_rate',
        'give_integral',
        'rank_integral',
        'pay_integral',
        'white_bar',
        'grade_introduce',
        'entry_criteria',
        'grade_img',
        'is_open',
        'is_default'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getGradeName()
    {
        return $this->grade_name;
    }

    /**
     * @return mixed
     */
    public function getGoodsSun()
    {
        return $this->goods_sun;
    }

    /**
     * @return mixed
     */
    public function getSellerTemp()
    {
        return $this->seller_temp;
    }

    /**
     * @return mixed
     */
    public function getFavorableRate()
    {
        return $this->favorable_rate;
    }

    /**
     * @return mixed
     */
    public function getGiveIntegral()
    {
        return $this->give_integral;
    }

    /**
     * @return mixed
     */
    public function getRankIntegral()
    {
        return $this->rank_integral;
    }

    /**
     * @return mixed
     */
    public function getPayIntegral()
    {
        return $this->pay_integral;
    }

    /**
     * @return mixed
     */
    public function getWhiteBar()
    {
        return $this->white_bar;
    }

    /**
     * @return mixed
     */
    public function getGradeIntroduce()
    {
        return $this->grade_introduce;
    }

    /**
     * @return mixed
     */
    public function getEntryCriteria()
    {
        return $this->entry_criteria;
    }

    /**
     * @return mixed
     */
    public function getGradeImg()
    {
        return $this->grade_img;
    }

    /**
     * @return mixed
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * @return mixed
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGradeName($value)
    {
        $this->grade_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsSun($value)
    {
        $this->goods_sun = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSellerTemp($value)
    {
        $this->seller_temp = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFavorableRate($value)
    {
        $this->favorable_rate = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiveIntegral($value)
    {
        $this->give_integral = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRankIntegral($value)
    {
        $this->rank_integral = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayIntegral($value)
    {
        $this->pay_integral = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWhiteBar($value)
    {
        $this->white_bar = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGradeIntroduce($value)
    {
        $this->grade_introduce = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEntryCriteria($value)
    {
        $this->entry_criteria = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGradeImg($value)
    {
        $this->grade_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsOpen($value)
    {
        $this->is_open = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDefault($value)
    {
        $this->is_default = $value;
        return $this;
    }
}
