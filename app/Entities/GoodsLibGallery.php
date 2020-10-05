<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsLibGallery
 */
class GoodsLibGallery extends Model
{
    protected $table = 'goods_lib_gallery';

    protected $primaryKey = 'img_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'img_url',
        'img_desc',
        'thumb_url',
        'img_original',
        'single_id'
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
    public function getImgUrl()
    {
        return $this->img_url;
    }

    /**
     * @return mixed
     */
    public function getImgDesc()
    {
        return $this->img_desc;
    }

    /**
     * @return mixed
     */
    public function getThumbUrl()
    {
        return $this->thumb_url;
    }

    /**
     * @return mixed
     */
    public function getImgOriginal()
    {
        return $this->img_original;
    }

    /**
     * @return mixed
     */
    public function getSingleId()
    {
        return $this->single_id;
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
    public function setImgUrl($value)
    {
        $this->img_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgDesc($value)
    {
        $this->img_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setThumbUrl($value)
    {
        $this->thumb_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgOriginal($value)
    {
        $this->img_original = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSingleId($value)
    {
        $this->single_id = $value;
        return $this;
    }
}
