<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AffiliateLog
 */
class AffiliateLog extends Model
{
    protected $table = 'affiliate_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'time',
        'user_id',
        'user_name',
        'money',
        'point',
        'separate_type'
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
    public function setSeparateType($value)
    {
        $this->separate_type = $value;
        return $this;
    }
}
