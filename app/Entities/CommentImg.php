<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CommentImg
 */
class CommentImg extends Model
{
    protected $table = 'comment_img';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'order_id',
        'rec_id',
        'goods_id',
        'comment_id',
        'comment_img',
        'img_thumb',
        'cont_desc'
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
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getRecId()
    {
        return $this->rec_id;
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
    public function getCommentId()
    {
        return $this->comment_id;
    }

    /**
     * @return mixed
     */
    public function getCommentImg()
    {
        return $this->comment_img;
    }

    /**
     * @return mixed
     */
    public function getImgThumb()
    {
        return $this->img_thumb;
    }

    /**
     * @return mixed
     */
    public function getContDesc()
    {
        return $this->cont_desc;
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
    public function setRecId($value)
    {
        $this->rec_id = $value;
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
    public function setCommentId($value)
    {
        $this->comment_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommentImg($value)
    {
        $this->comment_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgThumb($value)
    {
        $this->img_thumb = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContDesc($value)
    {
        $this->cont_desc = $value;
        return $this;
    }
}
