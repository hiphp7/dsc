<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerAccountLog
 */
class SellerAccountLog extends Model
{
    protected $table = 'seller_account_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'real_id',
        'ru_id',
        'order_id',
        'amount',
        'frozen_money',
        'certificate_img',
        'deposit_mode',
        'log_type',
        'apply_sn',
        'pay_id',
        'pay_time',
        'admin_note',
        'add_time',
        'seller_note',
        'is_paid',
        'percent_value'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * @return mixed
     */
    public function getRealId()
    {
        return $this->real_id;
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
    public function getOrderId()
    {
        return $this->order_id;
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
    public function getFrozenMoney()
    {
        return $this->frozen_money;
    }

    /**
     * @return mixed
     */
    public function getCertificateImg()
    {
        return $this->certificate_img;
    }

    /**
     * @return mixed
     */
    public function getDepositMode()
    {
        return $this->deposit_mode;
    }

    /**
     * @return mixed
     */
    public function getLogType()
    {
        return $this->log_type;
    }

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
    public function getPayId()
    {
        return $this->pay_id;
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
    public function getAdminNote()
    {
        return $this->admin_note;
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
    public function getSellerNote()
    {
        return $this->seller_note;
    }

    /**
     * @return mixed
     */
    public function getIsPaid()
    {
        return $this->is_paid;
    }

    /**
     * @return mixed
     */
    public function getPercentValue()
    {
        return $this->percent_value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdminId($value)
    {
        $this->admin_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRealId($value)
    {
        $this->real_id = $value;
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
    public function setOrderId($value)
    {
        $this->order_id = $value;
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
    public function setFrozenMoney($value)
    {
        $this->frozen_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCertificateImg($value)
    {
        $this->certificate_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDepositMode($value)
    {
        $this->deposit_mode = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogType($value)
    {
        $this->log_type = $value;
        return $this;
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
    public function setPayId($value)
    {
        $this->pay_id = $value;
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
    public function setAdminNote($value)
    {
        $this->admin_note = $value;
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
    public function setSellerNote($value)
    {
        $this->seller_note = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsPaid($value)
    {
        $this->is_paid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPercentValue($value)
    {
        $this->percent_value = $value;
        return $this;
    }
}
