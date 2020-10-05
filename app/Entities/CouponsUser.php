<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CouponsUser
 */
class CouponsUser extends Model
{
    protected $table = 'coupons_user';

    protected $primaryKey = 'uc_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'cou_id',
        'cou_money',
        'is_use',
        'uc_sn',
        'order_id',
        'is_use_time'
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
    public function getCouId()
    {
        return $this->cou_id;
    }

    /**
     * @return mixed
     */
    public function getCouMoney()
    {
        return $this->cou_money;
    }

    /**
     * @return mixed
     */
    public function getIsUse()
    {
        return $this->is_use;
    }

    /**
     * @return mixed
     */
    public function getUcSn()
    {
        return $this->uc_sn;
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
    public function getIsUseTime()
    {
        return $this->is_use_time;
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
    public function setCouId($value)
    {
        $this->cou_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCouMoney($value)
    {
        $this->cou_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsUse($value)
    {
        $this->is_use = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUcSn($value)
    {
        $this->uc_sn = $value;
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
    public function setIsUseTime($value)
    {
        $this->is_use_time = $value;
        return $this;
    }
}
