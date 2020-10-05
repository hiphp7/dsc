<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PresaleActivity
 */
class PresaleActivity extends Model
{
    protected $table = 'presale_activity';

    protected $primaryKey = 'act_id';

    public $timestamps = false;

    protected $fillable = [
        'act_name',
        'cat_id',
        'user_id',
        'goods_id',
        'goods_name',
        'act_desc',
        'deposit',
        'start_time',
        'end_time',
        'pay_start_time',
        'pay_end_time',
        'is_finished',
        'review_status',
        'review_content',
        'pre_num'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getActName()
    {
        return $this->act_name;
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
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsName()
    {
        return $this->goods_name;
    }

    /**
     * @return mixed
     */
    public function getActDesc()
    {
        return $this->act_desc;
    }

    /**
     * @return mixed
     */
    public function getDeposit()
    {
        return $this->deposit;
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
    public function getPayStartTime()
    {
        return $this->pay_start_time;
    }

    /**
     * @return mixed
     */
    public function getPayEndTime()
    {
        return $this->pay_end_time;
    }

    /**
     * @return mixed
     */
    public function getIsFinished()
    {
        return $this->is_finished;
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
    public function getPreNum()
    {
        return $this->pre_num;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActName($value)
    {
        $this->act_name = $value;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsName($value)
    {
        $this->goods_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActDesc($value)
    {
        $this->act_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDeposit($value)
    {
        $this->deposit = $value;
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
    public function setPayStartTime($value)
    {
        $this->pay_start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayEndTime($value)
    {
        $this->pay_end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsFinished($value)
    {
        $this->is_finished = $value;
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
    public function setPreNum($value)
    {
        $this->pre_num = $value;
        return $this;
    }
}
