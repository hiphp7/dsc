<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Baitiao
 */
class Baitiao extends Model
{
    protected $table = 'baitiao';

    protected $primaryKey = 'baitiao_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'amount',
        'repay_term',
        'over_repay_trem',
        'add_time'
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
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return mixed
     */
    public function getRepayTerm()
    {
        return $this->repay_term;
    }

    /**
     * @return mixed
     */
    public function getOverRepayTrem()
    {
        return $this->over_repay_trem;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
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
    public function setAmount($value)
    {
        $this->amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRepayTerm($value)
    {
        $this->repay_term = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOverRepayTrem($value)
    {
        $this->over_repay_trem = $value;
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
}
