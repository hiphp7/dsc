<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BookingGoods
 */
class BookingGoods extends Model
{
    protected $table = 'booking_goods';

    protected $primaryKey = 'rec_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'link_man',
        'tel',
        'goods_id',
        'goods_desc',
        'goods_number',
        'booking_time',
        'is_dispose',
        'dispose_user',
        'dispose_time',
        'dispose_note'
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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getLinkMan()
    {
        return $this->link_man;
    }

    /**
     * @return mixed
     */
    public function getTel()
    {
        return $this->tel;
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
    public function getGoodsDesc()
    {
        return $this->goods_desc;
    }

    /**
     * @return mixed
     */
    public function getGoodsNumber()
    {
        return $this->goods_number;
    }

    /**
     * @return mixed
     */
    public function getBookingTime()
    {
        return $this->booking_time;
    }

    /**
     * @return mixed
     */
    public function getIsDispose()
    {
        return $this->is_dispose;
    }

    /**
     * @return mixed
     */
    public function getDisposeUser()
    {
        return $this->dispose_user;
    }

    /**
     * @return mixed
     */
    public function getDisposeTime()
    {
        return $this->dispose_time;
    }

    /**
     * @return mixed
     */
    public function getDisposeNote()
    {
        return $this->dispose_note;
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
    public function setEmail($value)
    {
        $this->email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkMan($value)
    {
        $this->link_man = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTel($value)
    {
        $this->tel = $value;
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
    public function setGoodsDesc($value)
    {
        $this->goods_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsNumber($value)
    {
        $this->goods_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBookingTime($value)
    {
        $this->booking_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDispose($value)
    {
        $this->is_dispose = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisposeUser($value)
    {
        $this->dispose_user = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisposeTime($value)
    {
        $this->dispose_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisposeNote($value)
    {
        $this->dispose_note = $value;
        return $this;
    }
}
