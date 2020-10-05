<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class VirtualCard
 */
class VirtualCard extends Model
{
    protected $table = 'virtual_card';

    protected $primaryKey = 'card_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'card_sn',
        'card_password',
        'add_date',
        'end_date',
        'is_saled',
        'order_sn',
        'crc32'
    ];

    protected $guarded = [];


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
    public function getCardSn()
    {
        return $this->card_sn;
    }

    /**
     * @return mixed
     */
    public function getCardPassword()
    {
        return $this->card_password;
    }

    /**
     * @return mixed
     */
    public function getAddDate()
    {
        return $this->add_date;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @return mixed
     */
    public function getIsSaled()
    {
        return $this->is_saled;
    }

    /**
     * @return mixed
     */
    public function getOrderSn()
    {
        return $this->order_sn;
    }

    /**
     * @return mixed
     */
    public function getCrc32()
    {
        return $this->crc32;
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
    public function setCardSn($value)
    {
        $this->card_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardPassword($value)
    {
        $this->card_password = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddDate($value)
    {
        $this->add_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndDate($value)
    {
        $this->end_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsSaled($value)
    {
        $this->is_saled = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderSn($value)
    {
        $this->order_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCrc32($value)
    {
        $this->crc32 = $value;
        return $this;
    }
}
