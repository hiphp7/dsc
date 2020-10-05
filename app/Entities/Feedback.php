<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Feedback
 */
class Feedback extends Model
{
    protected $table = 'feedback';

    protected $primaryKey = 'msg_id';

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'user_id',
        'user_name',
        'user_email',
        'msg_title',
        'msg_type',
        'msg_status',
        'msg_content',
        'msg_time',
        'message_img',
        'order_id',
        'msg_area'
    ];

    protected $guarded = [];


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
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getUserEmail()
    {
        return $this->user_email;
    }

    /**
     * @return mixed
     */
    public function getMsgTitle()
    {
        return $this->msg_title;
    }

    /**
     * @return mixed
     */
    public function getMsgType()
    {
        return $this->msg_type;
    }

    /**
     * @return mixed
     */
    public function getMsgStatus()
    {
        return $this->msg_status;
    }

    /**
     * @return mixed
     */
    public function getMsgContent()
    {
        return $this->msg_content;
    }

    /**
     * @return mixed
     */
    public function getMsgTime()
    {
        return $this->msg_time;
    }

    /**
     * @return mixed
     */
    public function getMessageImg()
    {
        return $this->message_img;
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
    public function getMsgArea()
    {
        return $this->msg_area;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setUserEmail($value)
    {
        $this->user_email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMsgTitle($value)
    {
        $this->msg_title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMsgType($value)
    {
        $this->msg_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMsgStatus($value)
    {
        $this->msg_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMsgContent($value)
    {
        $this->msg_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMsgTime($value)
    {
        $this->msg_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMessageImg($value)
    {
        $this->message_img = $value;
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
    public function setMsgArea($value)
    {
        $this->msg_area = $value;
        return $this;
    }
}
