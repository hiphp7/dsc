<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatShareCount
 */
class WechatShareCount extends Model
{
    protected $table = 'wechat_share_count';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'openid',
        'share_type',
        'link',
        'share_time'
    ];

    protected $guarded = [];


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
    public function getShareType()
    {
        return $this->share_type;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return mixed
     */
    public function getShareTime()
    {
        return $this->share_time;
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
    public function setShareType($value)
    {
        $this->share_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLink($value)
    {
        $this->link = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShareTime($value)
    {
        $this->share_time = $value;
        return $this;
    }
}
