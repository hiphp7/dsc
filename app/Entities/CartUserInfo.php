<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CartUserInfo
 */
class CartUserInfo extends Model
{
    protected $table = 'cart_user_info';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'user_id',
        'shipping_type',
        'shipping_id'
    ];

    protected $guarded = [];


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
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getShippingType()
    {
        return $this->shipping_type;
    }

    /**
     * @return mixed
     */
    public function getShippingId()
    {
        return $this->shipping_id;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingType($value)
    {
        $this->shipping_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingId($value)
    {
        $this->shipping_id = $value;
        return $this;
    }
}
