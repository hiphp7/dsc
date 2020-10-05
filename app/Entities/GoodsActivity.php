<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsActivity
 */
class GoodsActivity extends Model
{
    protected $table = 'goods_activity';

    protected $primaryKey = 'act_id';

    public $timestamps = false;

    protected $fillable = [
        'act_name',
        'user_id',
        'act_desc',
        'activity_thumb',
        'act_promise',
        'act_ensure',
        'act_type',
        'goods_id',
        'product_id',
        'goods_name',
        'start_time',
        'end_time',
        'is_finished',
        'ext_info',
        'is_hot',
        'review_status',
        'review_content',
        'is_new'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getActName()
    {
        return $this->act_name;
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
    public function getActDesc()
    {
        return $this->act_desc;
    }

    /**
     * @return mixed
     */
    public function getActivityThumb()
    {
        return $this->activity_thumb;
    }

    /**
     * @return mixed
     */
    public function getActPromise()
    {
        return $this->act_promise;
    }

    /**
     * @return mixed
     */
    public function getActEnsure()
    {
        return $this->act_ensure;
    }

    /**
     * @return mixed
     */
    public function getActType()
    {
        return $this->act_type;
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
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsName()
    {
        return $this->goods_name;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getIsFinished()
    {
        return $this->is_finished;
    }

    /**
     * @return mixed
     */
    public function getExtInfo()
    {
        return $this->ext_info;
    }

    /**
     * @return mixed
     */
    public function getIsHot()
    {
        return $this->is_hot;
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
    public function getIsNew()
    {
        return $this->is_new;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActName($value)
    {
        $this->act_name = $value;
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
    public function setActDesc($value)
    {
        $this->act_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActivityThumb($value)
    {
        $this->activity_thumb = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActPromise($value)
    {
        $this->act_promise = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActEnsure($value)
    {
        $this->act_ensure = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActType($value)
    {
        $this->act_type = $value;
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
    public function setProductId($value)
    {
        $this->product_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsName($value)
    {
        $this->goods_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStartTime($value)
    {
        $this->start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsFinished($value)
    {
        $this->is_finished = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExtInfo($value)
    {
        $this->ext_info = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsHot($value)
    {
        $this->is_hot = $value;
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
    public function setIsNew($value)
    {
        $this->is_new = $value;
        return $this;
    }
}
