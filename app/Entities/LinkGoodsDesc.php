<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LinkGoodsDesc
 */
class LinkGoodsDesc extends Model
{
    protected $table = 'link_goods_desc';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'ru_id',
        'desc_name',
        'goods_desc',
        'review_status',
        'review_content'
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
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getDescName()
    {
        return $this->desc_name;
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
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDescName($value)
    {
        $this->desc_name = $value;
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
}
