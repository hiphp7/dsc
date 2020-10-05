<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpTransferLog
 */
class DrpTransferLog extends Model
{
    protected $table = 'drp_transfer_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'money',
        'add_time',
        'check_status',
        'deposit_type',
        'deposit_status',
        'deposit_fee',
        'bank_info',
        'trade_no',
        'deposit_data',
        'finish_status'
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
    public function getMoney()
    {
        return $this->money;
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
    public function getCheckStatus()
    {
        return $this->check_status;
    }

    /**
     * @return mixed
     */
    public function getDepositType()
    {
        return $this->deposit_type;
    }

    /**
     * @return mixed
     */
    public function getDepositStatus()
    {
        return $this->deposit_status;
    }

    /**
     * @return mixed
     */
    public function getDepositFee()
    {
        return $this->deposit_fee;
    }

    /**
     * @return mixed
     */
    public function getBankInfo()
    {
        return $this->bank_info;
    }

    /**
     * @return mixed
     */
    public function getTradeNo()
    {
        return $this->trade_no;
    }

    /**
     * @return mixed
     */
    public function getDepositData()
    {
        return $this->deposit_data;
    }

    /**
     * @return mixed
     */
    public function getFinishStatus()
    {
        return $this->finish_status;
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
    public function setMoney($value)
    {
        $this->money = $value;
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
    public function setCheckStatus($value)
    {
        $this->check_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDepositType($value)
    {
        $this->deposit_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDepositStatus($value)
    {
        $this->deposit_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDepositFee($value)
    {
        $this->deposit_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankInfo($value)
    {
        $this->bank_info = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTradeNo($value)
    {
        $this->trade_no = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDepositData($value)
    {
        $this->deposit_data = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFinishStatus($value)
    {
        $this->finish_status = $value;
        return $this;
    }
}
