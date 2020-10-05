<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CommentSeller
 */
class CommentSeller extends Model
{
    protected $table = 'comment_seller';

    protected $primaryKey = 'sid';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ru_id',
        'order_id',
        'desc_rank',
        'service_rank',
        'delivery_rank',
        'sender_rank',
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
    public function getRuId()
    {
        return $this->ru_id;
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
    public function getDescRank()
    {
        return $this->desc_rank;
    }

    /**
     * @return mixed
     */
    public function getServiceRank()
    {
        return $this->service_rank;
    }

    /**
     * @return mixed
     */
    public function getDeliveryRank()
    {
        return $this->delivery_rank;
    }

    /**
     * @return mixed
     */
    public function getSenderRank()
    {
        return $this->sender_rank;
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
    public function setRuId($value)
    {
        $this->ru_id = $value;
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
    public function setDescRank($value)
    {
        $this->desc_rank = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setServiceRank($value)
    {
        $this->service_rank = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDeliveryRank($value)
    {
        $this->delivery_rank = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSenderRank($value)
    {
        $this->sender_rank = $value;
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
