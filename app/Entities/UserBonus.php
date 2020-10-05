<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserBonus
 */
class UserBonus extends Model
{
    protected $table = 'user_bonus';

    protected $primaryKey = 'bonus_id';

    public $timestamps = false;

    protected $fillable = [
        'bonus_type_id',
        'bonus_sn',
        'bonus_password',
        'user_id',
        'used_time',
        'order_id',
        'emailed',
        'bind_time',
        'start_time',
        'end_time',
        'date_type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBonusTypeId()
    {
        return $this->bonus_type_id;
    }

    /**
     * @return mixed
     */
    public function getBonusSn()
    {
        return $this->bonus_sn;
    }

    /**
     * @return mixed
     */
    public function getBonusPassword()
    {
        return $this->bonus_password;
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
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getEmailed()
    {
        return $this->emailed;
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
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getDateType()
    {
        return $this->date_type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBonusTypeId($value)
    {
        $this->bonus_type_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBonusSn($value)
    {
        $this->bonus_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBonusPassword($value)
    {
        $this->bonus_password = $value;
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
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEmailed($value)
    {
        $this->emailed = $value;
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
    public function setStartTime($value)
    {
        $this->start_time = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setDateType($value)
    {
        $this->date_type = $value;
        return $this;
    }
}
