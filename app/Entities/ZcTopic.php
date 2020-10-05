<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ZcTopic
 */
class ZcTopic extends Model
{
    protected $table = 'zc_topic';

    protected $primaryKey = 'topic_id';

    public $timestamps = false;

    protected $fillable = [
        'parent_topic_id',
        'reply_topic_id',
        'topic_status',
        'topic_content',
        'user_id',
        'pid',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getParentTopicId()
    {
        return $this->parent_topic_id;
    }

    /**
     * @return mixed
     */
    public function getReplyTopicId()
    {
        return $this->reply_topic_id;
    }

    /**
     * @return mixed
     */
    public function getTopicStatus()
    {
        return $this->topic_status;
    }

    /**
     * @return mixed
     */
    public function getTopicContent()
    {
        return $this->topic_content;
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
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentTopicId($value)
    {
        $this->parent_topic_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReplyTopicId($value)
    {
        $this->reply_topic_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTopicStatus($value)
    {
        $this->topic_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTopicContent($value)
    {
        $this->topic_content = $value;
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
    public function setPid($value)
    {
        $this->pid = $value;
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
}
