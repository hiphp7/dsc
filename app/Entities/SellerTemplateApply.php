<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerTemplateApply
 */
class SellerTemplateApply extends Model
{
    protected $table = 'seller_template_apply';

    protected $primaryKey = 'apply_id';

    public $timestamps = false;

    protected $fillable = [
        'apply_sn',
        'ru_id',
        'temp_id',
        'temp_code',
        'pay_status',
        'apply_status',
        'total_amount',
        'pay_fee',
        'add_time',
        'pay_time',
        'pay_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getApplySn()
    {
        return $this->apply_sn;
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
    public function getTempId()
    {
        return $this->temp_id;
    }

    /**
     * @return mixed
     */
    public function getTempCode()
    {
        return $this->temp_code;
    }

    /**
     * @return mixed
     */
    public function getPayStatus()
    {
        return $this->pay_status;
    }

    /**
     * @return mixed
     */
    public function getApplyStatus()
    {
        return $this->apply_status;
    }

    /**
     * @return mixed
     */
    public function getTotalAmount()
    {
        return $this->total_amount;
    }

    /**
     * @return mixed
     */
    public function getPayFee()
    {
        return $this->pay_fee;
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
     * @return mixed
     */
    public function getPayId()
    {
        return $this->pay_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setApplySn($value)
    {
        $this->apply_sn = $value;
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
    public function setTempId($value)
    {
        $this->temp_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTempCode($value)
    {
        $this->temp_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayStatus($value)
    {
        $this->pay_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setApplyStatus($value)
    {
        $this->apply_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTotalAmount($value)
    {
        $this->total_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayFee($value)
    {
        $this->pay_fee = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setPayId($value)
    {
        $this->pay_id = $value;
        return $this;
    }
}
