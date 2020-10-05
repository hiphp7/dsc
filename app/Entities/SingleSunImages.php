<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SingleSunImages
 */
class SingleSunImages extends Model
{
    protected $table = 'single_sun_images';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'order_id',
        'goods_id',
        'img_file',
        'img_thumb',
        'cont_desc',
        'comment_id',
        'img_type'
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
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getImgFile()
    {
        return $this->img_file;
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
     * @return mixed
     */
    public function getCommentId()
    {
        return $this->comment_id;
    }

    /**
     * @return mixed
     */
    public function getImgType()
    {
        return $this->img_type;
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
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgFile($value)
    {
        $this->img_file = $value;
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
    public function setImgType($value)
    {
        $this->img_type = $value;
        return $this;
    }
}
