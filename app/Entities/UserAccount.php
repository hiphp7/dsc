<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAccount
 */
class UserAccount extends Model
{
    protected $table = 'user_account';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'admin_user',
        'amount',
        'deposit_fee',
        'add_time',
        'paid_time',
        'admin_note',
        'user_note',
        'process_type',
        'payment',
        'pay_id',
        'is_paid',
        'complaint_details',
        'complaint_imges',
        'complaint_time'
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
    public function getAdminUser()
    {
        return $this->admin_user;
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
    public function getDepositFee()
    {
        return $this->deposit_fee;
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
    public function getPaidTime()
    {
        return $this->paid_time;
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
    public function getUserNote()
    {
        return $this->user_note;
    }

    /**
     * @return mixed
     */
    public function getProcessType()
    {
        return $this->process_type;
    }

    /**
     * @return mixed
     */
    public function getPayment()
    {
        return $this->payment;
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
    public function getIsPaid()
    {
        return $this->is_paid;
    }

    /**
     * @return mixed
     */
    public function getComplaintDetails()
    {
        return $this->complaint_details;
    }

    /**
     * @return mixed
     */
    public function getComplaintImges()
    {
        return $this->complaint_imges;
    }

    /**
     * @return mixed
     */
    public function getComplaintTime()
    {
        return $this->complaint_time;
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
    public function setAdminUser($value)
    {
        $this->admin_user = $value;
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
    public function setDepositFee($value)
    {
        $this->deposit_fee = $value;
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
    public function setPaidTime($value)
    {
        $this->paid_time = $value;
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
    public function setUserNote($value)
    {
        $this->user_note = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProcessType($value)
    {
        $this->process_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayment($value)
    {
        $this->payment = $value;
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
    public function setIsPaid($value)
    {
        $this->is_paid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setComplaintDetails($value)
    {
        $this->complaint_details = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setComplaintImges($value)
    {
        $this->complaint_imges = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setComplaintTime($value)
    {
        $this->complaint_time = $value;
        return $this;
    }
}
