<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PayCard
 */
class PayCard extends Model
{
    protected $table = 'pay_card';

    public $timestamps = false;

    protected $fillable = [
        'card_number',
        'card_psd',
        'user_id',
        'used_time',
        'status',
        'c_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCardNumber()
    {
        return $this->card_number;
    }

    /**
     * @return mixed
     */
    public function getCardPsd()
    {
        return $this->card_psd;
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
    public function getUsedTime()
    {
        return $this->used_time;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getCId()
    {
        return $this->c_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardNumber($value)
    {
        $this->card_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardPsd($value)
    {
        $this->card_psd = $value;
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
    public function setUsedTime($value)
    {
        $this->used_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCId($value)
    {
        $this->c_id = $value;
        return $this;
    }
}
