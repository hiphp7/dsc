<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatUser
 */
class WechatUser extends Model
{
    protected $table = 'wechat_user';

    protected $primaryKey = 'uid';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'subscribe',
        'openid',
        'nickname',
        'sex',
        'city',
        'country',
        'province',
        'language',
        'headimgurl',
        'subscribe_time',
        'remark',
        'privilege',
        'unionid',
        'groupid',
        'ect_uid',
        'bein_kefu',
        'parent_id',
        'drp_parent_id',
        'from',
        'subscribe_scene',
        'qr_scene',
        'qr_scene_str'
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
    public function getSubscribe()
    {
        return $this->subscribe;
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
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return mixed
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
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
    public function getSubscribeTime()
    {
        return $this->subscribe_time;
    }

    /**
     * @return mixed
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @return mixed
     */
    public function getPrivilege()
    {
        return $this->privilege;
    }

    /**
     * @return mixed
     */
    public function getUnionid()
    {
        return $this->unionid;
    }

    /**
     * @return mixed
     */
    public function getGroupid()
    {
        return $this->groupid;
    }

    /**
     * @return mixed
     */
    public function getEctUid()
    {
        return $this->ect_uid;
    }

    /**
     * @return mixed
     */
    public function getBeinKefu()
    {
        return $this->bein_kefu;
    }

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
    public function getDrpParentId()
    {
        return $this->drp_parent_id;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return mixed
     */
    public function getSubscribeScene()
    {
        return $this->subscribe_scene;
    }

    /**
     * @return mixed
     */
    public function getQrScene()
    {
        return $this->qr_scene;
    }

    /**
     * @return mixed
     */
    public function getQrSceneStr()
    {
        return $this->qr_scene_str;
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
    public function setSubscribe($value)
    {
        $this->subscribe = $value;
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
    public function setCity($value)
    {
        $this->city = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCountry($value)
    {
        $this->country = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProvince($value)
    {
        $this->province = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLanguage($value)
    {
        $this->language = $value;
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
    public function setSubscribeTime($value)
    {
        $this->subscribe_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRemark($value)
    {
        $this->remark = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPrivilege($value)
    {
        $this->privilege = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUnionid($value)
    {
        $this->unionid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGroupid($value)
    {
        $this->groupid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEctUid($value)
    {
        $this->ect_uid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBeinKefu($value)
    {
        $this->bein_kefu = $value;
        return $this;
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
    public function setDrpParentId($value)
    {
        $this->drp_parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFrom($value)
    {
        $this->from = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSubscribeScene($value)
    {
        $this->subscribe_scene = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQrScene($value)
    {
        $this->qr_scene = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQrSceneStr($value)
    {
        $this->qr_scene_str = $value;
        return $this;
    }
}
