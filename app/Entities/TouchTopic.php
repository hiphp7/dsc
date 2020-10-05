<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TouchTopic
 */
class TouchTopic extends Model
{
    protected $table = 'touch_topic';

    protected $primaryKey = 'topic_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'intro',
        'start_time',
        'end_time',
        'data',
        'template',
        'css',
        'topic_img',
        'title_pic',
        'base_style',
        'htmls',
        'keywords',
        'description',
        'review_status',
        'review_content'
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getIntro()
    {
        return $this->intro;
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
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return mixed
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @return mixed
     */
    public function getTopicImg()
    {
        return $this->topic_img;
    }

    /**
     * @return mixed
     */
    public function getTitlePic()
    {
        return $this->title_pic;
    }

    /**
     * @return mixed
     */
    public function getBaseStyle()
    {
        return $this->base_style;
    }

    /**
     * @return mixed
     */
    public function getHtmls()
    {
        return $this->htmls;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
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
    public function setIntro($value)
    {
        $this->intro = $value;
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
    public function setData($value)
    {
        $this->data = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTemplate($value)
    {
        $this->template = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCss($value)
    {
        $this->css = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTopicImg($value)
    {
        $this->topic_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitlePic($value)
    {
        $this->title_pic = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBaseStyle($value)
    {
        $this->base_style = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHtmls($value)
    {
        $this->htmls = $value;
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
