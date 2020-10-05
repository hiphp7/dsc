<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Payment
 */
class Payment extends Model
{
    protected $table = 'payment';

    protected $primaryKey = 'pay_id';

    public $timestamps = false;

    protected $fillable = [
        'pay_code',
        'pay_name',
        'pay_fee',
        'pay_desc',
        'pay_order',
        'pay_config',
        'enabled',
        'is_cod',
        'is_online'
    ];

    protected $guarded = [];


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
    public function getPayName()
    {
        return $this->pay_name;
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
    public function getPayDesc()
    {
        return $this->pay_desc;
    }

    /**
     * @return mixed
     */
    public function getPayOrder()
    {
        return $this->pay_order;
    }

    /**
     * @return mixed
     */
    public function getPayConfig()
    {
        return $this->pay_config;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return mixed
     */
    public function getIsCod()
    {
        return $this->is_cod;
    }

    /**
     * @return mixed
     */
    public function getIsOnline()
    {
        return $this->is_online;
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
    public function setPayName($value)
    {
        $this->pay_name = $value;
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
    public function setPayDesc($value)
    {
        $this->pay_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayOrder($value)
    {
        $this->pay_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayConfig($value)
    {
        $this->pay_config = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEnabled($value)
    {
        $this->enabled = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsCod($value)
    {
        $this->is_cod = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsOnline($value)
    {
        $this->is_online = $value;
        return $this;
    }
}
