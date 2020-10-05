<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ZcProject
 */
class ZcProject extends Model
{
    protected $table = 'zc_project';

    public $timestamps = false;

    protected $fillable = [
        'cat_id',
        'title',
        'init_id',
        'start_time',
        'end_time',
        'amount',
        'join_money',
        'join_num',
        'focus_num',
        'prais_num',
        'title_img',
        'details',
        'describe',
        'risk_instruction',
        'img',
        'is_best'
    ];

    protected $guarded = [];


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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getInitId()
    {
        return $this->init_id;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return mixed
     */
    public function getJoinMoney()
    {
        return $this->join_money;
    }

    /**
     * @return mixed
     */
    public function getJoinNum()
    {
        return $this->join_num;
    }

    /**
     * @return mixed
     */
    public function getFocusNum()
    {
        return $this->focus_num;
    }

    /**
     * @return mixed
     */
    public function getPraisNum()
    {
        return $this->prais_num;
    }

    /**
     * @return mixed
     */
    public function getTitleImg()
    {
        return $this->title_img;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return mixed
     */
    public function getDescribe()
    {
        return $this->describe;
    }

    /**
     * @return mixed
     */
    public function getRiskInstruction()
    {
        return $this->risk_instruction;
    }

    /**
     * @return mixed
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @return mixed
     */
    public function getIsBest()
    {
        return $this->is_best;
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
    public function setTitle($value)
    {
        $this->title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInitId($value)
    {
        $this->init_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStartTime($value)
    {
        $this->start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAmount($value)
    {
        $this->amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setJoinMoney($value)
    {
        $this->join_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setJoinNum($value)
    {
        $this->join_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFocusNum($value)
    {
        $this->focus_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPraisNum($value)
    {
        $this->prais_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitleImg($value)
    {
        $this->title_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDetails($value)
    {
        $this->details = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDescribe($value)
    {
        $this->describe = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRiskInstruction($value)
    {
        $this->risk_instruction = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImg($value)
    {
        $this->img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsBest($value)
    {
        $this->is_best = $value;
        return $this;
    }
}
