<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class NoticeLog
 */
class NoticeLog extends Model
{
    protected $table = 'notice_log';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'email',
        'send_ok',
        'send_type',
        'send_time'
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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getSendOk()
    {
        return $this->send_ok;
    }

    /**
     * @return mixed
     */
    public function getSendType()
    {
        return $this->send_type;
    }

    /**
     * @return mixed
     */
    public function getSendTime()
    {
        return $this->send_time;
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
    public function setEmail($value)
    {
        $this->email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendOk($value)
    {
        $this->send_ok = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendType($value)
    {
        $this->send_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendTime($value)
    {
        $this->send_time = $value;
        return $this;
    }
}
