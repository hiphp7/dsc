<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DiscussCircle
 */
class DiscussCircle extends Model
{
    protected $table = 'discuss_circle';

    protected $primaryKey = 'dis_id';

    public $timestamps = false;

    protected $fillable = [
        'dis_browse_num',
        'review_status',
        'review_content',
        'like_num',
        'parent_id',
        'quote_id',
        'goods_id',
        'user_id',
        'order_id',
        'dis_type',
        'dis_title',
        'dis_text',
        'add_time',
        'user_name'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getDisBrowseNum()
    {
        return $this->dis_browse_num;
    }

    /**
     * @return mixed
     */
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getReviewContent()
    {
        return $this->review_content;
    }

    /**
     * @return mixed
     */
    public function getLikeNum()
    {
        return $this->like_num;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @return mixed
     */
    public function getQuoteId()
    {
        return $this->quote_id;
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
    public function getUserId()
    {
        return $this->user_id;
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
    public function getDisType()
    {
        return $this->dis_type;
    }

    /**
     * @return mixed
     */
    public function getDisTitle()
    {
        return $this->dis_title;
    }

    /**
     * @return mixed
     */
    public function getDisText()
    {
        return $this->dis_text;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisBrowseNum($value)
    {
        $this->dis_browse_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLikeNum($value)
    {
        $this->like_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQuoteId($value)
    {
        $this->quote_id = $value;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
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
    public function setDisType($value)
    {
        $this->dis_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisTitle($value)
    {
        $this->dis_title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisText($value)
    {
        $this->dis_text = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }
}
