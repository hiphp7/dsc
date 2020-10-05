<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ImService
 */
class ImService extends Model
{
    protected $table = 'im_service';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'nick_name',
        'post_desc',
        'login_time',
        'chat_status',
        'status'
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
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getNickName()
    {
        return $this->nick_name;
    }

    /**
     * @return mixed
     */
    public function getPostDesc()
    {
        return $this->post_desc;
    }

    /**
     * @return mixed
     */
    public function getLoginTime()
    {
        return $this->login_time;
    }

    /**
     * @return mixed
     */
    public function getChatStatus()
    {
        return $this->chat_status;
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
    public function setNickName($value)
    {
        $this->nick_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPostDesc($value)
    {
        $this->post_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLoginTime($value)
    {
        $this->login_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setChatStatus($value)
    {
        $this->chat_status = $value;
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
