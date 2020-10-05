<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FavourableActivity
 */
class FavourableActivity extends Model
{
    protected $table = 'favourable_activity';

    protected $primaryKey = 'act_id';

    public $timestamps = false;

    protected $fillable = [
        'act_name',
        'start_time',
        'end_time',
        'user_rank',
        'act_range',
        'act_range_ext',
        'min_amount',
        'max_amount',
        'act_type',
        'act_type_ext',
        'activity_thumb',
        'gift',
        'sort_order',
        'user_id',
        'rs_id',
        'userFav_type',
        'userFav_type_ext',
        'review_status',
        'review_content',
        'user_range_ext',
        'is_user_brand'
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
    public function getUserRank()
    {
        return $this->user_rank;
    }

    /**
     * @return mixed
     */
    public function getActRange()
    {
        return $this->act_range;
    }

    /**
     * @return mixed
     */
    public function getActRangeExt()
    {
        return $this->act_range_ext;
    }

    /**
     * @return mixed
     */
    public function getMinAmount()
    {
        return $this->min_amount;
    }

    /**
     * @return mixed
     */
    public function getMaxAmount()
    {
        return $this->max_amount;
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
    public function getActTypeExt()
    {
        return $this->act_type_ext;
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
    public function getGift()
    {
        return $this->gift;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
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
    public function getRsId()
    {
        return $this->rs_id;
    }

    /**
     * @return mixed
     */
    public function getUserFavType()
    {
        return $this->userFav_type;
    }

    /**
     * @return mixed
     */
    public function getUserFavTypeExt()
    {
        return $this->userFav_type_ext;
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
    public function getUserRangeExt()
    {
        return $this->user_range_ext;
    }

    /**
     * @return mixed
     */
    public function getIsUserBrand()
    {
        return $this->is_user_brand;
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
    public function setUserRank($value)
    {
        $this->user_rank = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActRange($value)
    {
        $this->act_range = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActRangeExt($value)
    {
        $this->act_range_ext = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMinAmount($value)
    {
        $this->min_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMaxAmount($value)
    {
        $this->max_amount = $value;
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
    public function setActTypeExt($value)
    {
        $this->act_type_ext = $value;
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
    public function setGift($value)
    {
        $this->gift = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
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
    public function setRsId($value)
    {
        $this->rs_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserFavType($value)
    {
        $this->userFav_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserFavTypeExt($value)
    {
        $this->userFav_type_ext = $value;
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
    public function setUserRangeExt($value)
    {
        $this->user_range_ext = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsUserBrand($value)
    {
        $this->is_user_brand = $value;
        return $this;
    }
}
