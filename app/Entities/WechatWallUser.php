<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatWallUser
 */
class WechatWallUser extends Model
{
    protected $table = 'wechat_wall_user';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'wall_id',
        'nickname',
        'sex',
        'headimg',
        'status',
        'addtime',
        'checktime',
        'openid',
        'wechatname',
        'headimgurl',
        'sign_number'
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
    public function getWallId()
    {
        return $this->wall_id;
    }

    /**
     * @return mixed
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @return mixed
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @return mixed
     */
    public function getHeadimg()
    {
        return $this->headimg;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getAddtime()
    {
        return $this->addtime;
    }

    /**
     * @return mixed
     */
    public function getChecktime()
    {
        return $this->checktime;
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
    public function getWechatname()
    {
        return $this->wechatname;
    }

    /**
     * @return mixed
     */
    public function getHeadimgurl()
    {
        return $this->headimgurl;
    }

    /**
     * @return mixed
     */
    public function getSignNumber()
    {
        return $this->sign_number;
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
    public function setWallId($value)
    {
        $this->wall_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNickname($value)
    {
        $this->nickname = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSex($value)
    {
        $this->sex = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHeadimg($value)
    {
        $this->headimg = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setAddtime($value)
    {
        $this->addtime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setChecktime($value)
    {
        $this->checktime = $value;
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
    public function setWechatname($value)
    {
        $this->wechatname = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHeadimgurl($value)
    {
        $this->headimgurl = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSignNumber($value)
    {
        $this->sign_number = $value;
        return $this;
    }
}
