<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BaitiaoPayLog
 */
class BaitiaoPayLog extends Model
{
    protected $table = 'baitiao_pay_log';

    public $timestamps = false;

    protected $fillable = [
        'baitiao_id',
        'log_id',
        'stages_num',
        'stages_price',
        'is_pay',
        'pay_id',
        'pay_code',
        'add_time',
        'pay_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBaitiaoId()
    {
        return $this->baitiao_id;
    }

    /**
     * @return mixed
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * @return mixed
     */
    public function getStagesNum()
    {
        return $this->stages_num;
    }

    /**
     * @return mixed
     */
    public function getStagesPrice()
    {
        return $this->stages_price;
    }

    /**
     * @return mixed
     */
    public function getIsPay()
    {
        return $this->is_pay;
    }

    /**
     * @return mixed
     */
    public function getPayId()
    {
        return $this->pay_id;
    }

    /**
     * @return mixed
     */
    public function getPayCode()
    {
        return $this->pay_code;
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
    public function getPayTime()
    {
        return $this->pay_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBaitiaoId($value)
    {
        $this->baitiao_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogId($value)
    {
        $this->log_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStagesNum($value)
    {
        $this->stages_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStagesPrice($value)
    {
        $this->stages_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsPay($value)
    {
        $this->is_pay = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayId($value)
    {
        $this->pay_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayCode($value)
    {
        $this->pay_code = $value;
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
    public function setPayTime($value)
    {
        $this->pay_time = $value;
        return $this;
    }
}
