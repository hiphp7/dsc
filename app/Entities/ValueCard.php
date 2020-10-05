<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ValueCard
 */
class ValueCard extends Model
{
    protected $table = 'value_card';

    protected $primaryKey = 'vid';

    public $timestamps = false;

    protected $fillable = [
        'tid',
        'value_card_sn',
        'value_card_password',
        'user_id',
        'vc_value',
        'card_money',
        'bind_time',
        'end_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTid()
    {
        return $this->tid;
    }

    /**
     * @return mixed
     */
    public function getValueCardSn()
    {
        return $this->value_card_sn;
    }

    /**
     * @return mixed
     */
    public function getValueCardPassword()
    {
        return $this->value_card_password;
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
    public function getVcValue()
    {
        return $this->vc_value;
    }

    /**
     * @return mixed
     */
    public function getCardMoney()
    {
        return $this->card_money;
    }

    /**
     * @return mixed
     */
    public function getBindTime()
    {
        return $this->bind_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTid($value)
    {
        $this->tid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValueCardSn($value)
    {
        $this->value_card_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValueCardPassword($value)
    {
        $this->value_card_password = $value;
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
    public function setVcValue($value)
    {
        $this->vc_value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardMoney($value)
    {
        $this->card_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBindTime($value)
    {
        $this->bind_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }
}
