<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpLog
 */
class DrpLog extends Model
{
    protected $table = 'drp_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'time',
        'user_id',
        'user_name',
        'money',
        'point',
        'drp_level',
        'is_separate',
        'separate_type',
        'membership_card_id',
        'log_type',
        'drp_account_log_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
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
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @return mixed
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @return mixed
     */
    public function getDrpLevel()
    {
        return $this->drp_level;
    }

    /**
     * @return mixed
     */
    public function getIsSeparate()
    {
        return $this->is_separate;
    }

    /**
     * @return mixed
     */
    public function getSeparateType()
    {
        return $this->separate_type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTime($value)
    {
        $this->time = $value;
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
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMoney($value)
    {
        $this->money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPoint($value)
    {
        $this->point = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDrpLevel($value)
    {
        $this->drp_level = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsSeparate($value)
    {
        $this->is_separate = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSeparateType($value)
    {
        $this->separate_type = $value;
        return $this;
    }
}
