<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ImMessage
 */
class ImMessage extends Model
{
    protected $table = 'im_message';

    public $timestamps = false;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'dialog_id',
        'message',
        'add_time',
        'user_type',
        'status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getFromUserId()
    {
        return $this->from_user_id;
    }

    /**
     * @return mixed
     */
    public function getToUserId()
    {
        return $this->to_user_id;
    }

    /**
     * @return mixed
     */
    public function getDialogId()
    {
        return $this->dialog_id;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFromUserId($value)
    {
        $this->from_user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setToUserId($value)
    {
        $this->to_user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDialogId($value)
    {
        $this->dialog_id = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserType($value)
    {
        $this->user_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }
}
