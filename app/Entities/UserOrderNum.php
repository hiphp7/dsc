<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserOrderNum
 */
class UserOrderNum extends Model
{
    protected $table = 'user_order_num';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'order_all_num',
        'order_nopay',
        'order_nogoods',
        'order_isfinished',
        'order_isdelete',
        'order_team_num',
        'order_not_comment',
        'order_return_count'
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
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getOrderAllNum()
    {
        return $this->order_all_num;
    }

    /**
     * @return mixed
     */
    public function getOrderNopay()
    {
        return $this->order_nopay;
    }

    /**
     * @return mixed
     */
    public function getOrderNogoods()
    {
        return $this->order_nogoods;
    }

    /**
     * @return mixed
     */
    public function getOrderIsfinished()
    {
        return $this->order_isfinished;
    }

    /**
     * @return mixed
     */
    public function getOrderIsdelete()
    {
        return $this->order_isdelete;
    }

    /**
     * @return mixed
     */
    public function getOrderTeamNum()
    {
        return $this->order_team_num;
    }

    /**
     * @return mixed
     */
    public function getOrderNotComment()
    {
        return $this->order_not_comment;
    }

    /**
     * @return mixed
     */
    public function getOrderReturnCount()
    {
        return $this->order_return_count;
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
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderAllNum($value)
    {
        $this->order_all_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderNopay($value)
    {
        $this->order_nopay = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderNogoods($value)
    {
        $this->order_nogoods = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderIsfinished($value)
    {
        $this->order_isfinished = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderIsdelete($value)
    {
        $this->order_isdelete = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderTeamNum($value)
    {
        $this->order_team_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderNotComment($value)
    {
        $this->order_not_comment = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderReturnCount($value)
    {
        $this->order_return_count = $value;
        return $this;
    }
}
