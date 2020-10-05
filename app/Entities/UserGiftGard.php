<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserGiftGard
 */
class UserGiftGard extends Model
{
    protected $table = 'user_gift_gard';

    protected $primaryKey = 'gift_gard_id';

    public $timestamps = false;

    protected $fillable = [
        'gift_sn',
        'gift_password',
        'user_id',
        'goods_id',
        'user_time',
        'express_no',
        'gift_id',
        'address',
        'consignee_name',
        'mobile',
        'status',
        'config_goods_id',
        'is_delete',
        'shipping_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getGiftSn()
    {
        return $this->gift_sn;
    }

    /**
     * @return mixed
     */
    public function getGiftPassword()
    {
        return $this->gift_password;
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
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getUserTime()
    {
        return $this->user_time;
    }

    /**
     * @return mixed
     */
    public function getExpressNo()
    {
        return $this->express_no;
    }

    /**
     * @return mixed
     */
    public function getGiftId()
    {
        return $this->gift_id;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getConsigneeName()
    {
        return $this->consignee_name;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
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
    public function getConfigGoodsId()
    {
        return $this->config_goods_id;
    }

    /**
     * @return mixed
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * @return mixed
     */
    public function getShippingTime()
    {
        return $this->shipping_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftSn($value)
    {
        $this->gift_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftPassword($value)
    {
        $this->gift_password = $value;
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
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserTime($value)
    {
        $this->user_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpressNo($value)
    {
        $this->express_no = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftId($value)
    {
        $this->gift_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddress($value)
    {
        $this->address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConsigneeName($value)
    {
        $this->consignee_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMobile($value)
    {
        $this->mobile = $value;
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
    public function setConfigGoodsId($value)
    {
        $this->config_goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDelete($value)
    {
        $this->is_delete = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingTime($value)
    {
        $this->shipping_time = $value;
        return $this;
    }
}
