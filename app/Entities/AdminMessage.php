<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AdminMessage
 */
class AdminMessage extends Model
{
    protected $table = 'admin_message';

    protected $primaryKey = 'message_id';

    public $timestamps = false;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'sent_time',
        'read_time',
        'readed',
        'deleted',
        'title',
        'message'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getSenderId()
    {
        return $this->sender_id;
    }

    /**
     * @return mixed
     */
    public function getReceiverId()
    {
        return $this->receiver_id;
    }

    /**
     * @return mixed
     */
    public function getSentTime()
    {
        return $this->sent_time;
    }

    /**
     * @return mixed
     */
    public function getReadTime()
    {
        return $this->read_time;
    }

    /**
     * @return mixed
     */
    public function getReaded()
    {
        return $this->readed;
    }

    /**
     * @return mixed
     */
    public function getDeleted()
    {
        return $this->deleted;
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
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSenderId($value)
    {
        $this->sender_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReceiverId($value)
    {
        $this->receiver_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSentTime($value)
    {
        $this->sent_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReadTime($value)
    {
        $this->read_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReaded($value)
    {
        $this->readed = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDeleted($value)
    {
        $this->deleted = $value;
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
    public function setMessage($value)
    {
        $this->message = $value;
        return $this;
    }
}
