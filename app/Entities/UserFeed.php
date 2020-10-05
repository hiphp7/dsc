<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserFeed
 */
class UserFeed extends Model
{
    protected $table = 'user_feed';

    protected $primaryKey = 'feed_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'value_id',
        'goods_id',
        'feed_type',
        'is_feed'
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
    public function getValueId()
    {
        return $this->value_id;
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
    public function getFeedType()
    {
        return $this->feed_type;
    }

    /**
     * @return mixed
     */
    public function getIsFeed()
    {
        return $this->is_feed;
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
    public function setValueId($value)
    {
        $this->value_id = $value;
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
    public function setFeedType($value)
    {
        $this->feed_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsFeed($value)
    {
        $this->is_feed = $value;
        return $this;
    }
}
