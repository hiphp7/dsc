<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TouchPageView
 */
class TouchPageView extends Model
{
    protected $table = 'touch_page_view';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'type',
        'page_id',
        'title',
        'keywords',
        'description',
        'data',
        'pic',
        'thumb_pic',
        'create_at',
        'update_at',
        'default',
        'review_status',
        'is_show'
    ];

    protected $guarded = [];


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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getPageId()
    {
        return $this->page_id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * @return mixed
     */
    public function getThumbPic()
    {
        return $this->thumb_pic;
    }

    /**
     * @return mixed
     */
    public function getCreateAt()
    {
        return $this->create_at;
    }

    /**
     * @return mixed
     */
    public function getUpdateAt()
    {
        return $this->update_at;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
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
    public function getIsShow()
    {
        return $this->is_show;
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
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPageId($value)
    {
        $this->page_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitle($value)
    {
        $this->title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKeywords($value)
    {
        $this->keywords = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setData($value)
    {
        $this->data = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPic($value)
    {
        $this->pic = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setThumbPic($value)
    {
        $this->thumb_pic = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreateAt($value)
    {
        $this->create_at = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUpdateAt($value)
    {
        $this->update_at = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDefault($value)
    {
        $this->default = $value;
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
    public function setIsShow($value)
    {
        $this->is_show = $value;
        return $this;
    }
}
