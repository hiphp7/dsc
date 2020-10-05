<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatUserTag
 */
class WechatUserTag extends Model
{
    protected $table = 'wechat_user_tag';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'tag_id',
        'openid'
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
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * @return mixed
     */
    public function getOpenid()
    {
        return $this->openid;
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
    public function setTagId($value)
    {
        $this->tag_id = $value;
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
}
