<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatPoint
 */
class WechatPoint extends Model
{
    protected $table = 'wechat_point';

    public $timestamps = false;

    protected $fillable = [
        'log_id',
        'wechat_id',
        'openid',
        'keywords',
        'createtime'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * @return mixed
     */
    public function getWechatId()
    {
        return $this->wechat_id;
    }

    /**
     * @return mixed
     */
    public function getOpenid()
    {
        return $this->openid;
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
    public function getCreatetime()
    {
        return $this->createtime;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogId($value)
    {
        $this->log_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWechatId($value)
    {
        $this->wechat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOpenid($value)
    {
        $this->openid = $value;
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
    public function setCreatetime($value)
    {
        $this->createtime = $value;
        return $this;
    }
}
