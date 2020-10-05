<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Wechat
 */
class Wechat extends Model
{
    protected $table = 'wechat';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'orgid',
        'weixin',
        'token',
        'appid',
        'appsecret',
        'encodingaeskey',
        'type',
        'oauth_status',
        'secret_key',
        'oauth_redirecturi',
        'oauth_count',
        'time',
        'sort',
        'status',
        'default_wx',
        'ru_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getOrgid()
    {
        return $this->orgid;
    }

    /**
     * @return mixed
     */
    public function getWeixin()
    {
        return $this->weixin;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getAppid()
    {
        return $this->appid;
    }

    /**
     * @return mixed
     */
    public function getAppsecret()
    {
        return $this->appsecret;
    }

    /**
     * @return mixed
     */
    public function getEncodingaeskey()
    {
        return $this->encodingaeskey;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getOauthStatus()
    {
        return $this->oauth_status;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->secret_key;
    }

    /**
     * @return mixed
     */
    public function getOauthRedirecturi()
    {
        return $this->oauth_redirecturi;
    }

    /**
     * @return mixed
     */
    public function getOauthCount()
    {
        return $this->oauth_count;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return mixed
     */
    public function getSort()
    {
        return $this->sort;
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
    public function getDefaultWx()
    {
        return $this->default_wx;
    }

    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrgid($value)
    {
        $this->orgid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWeixin($value)
    {
        $this->weixin = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setToken($value)
    {
        $this->token = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAppid($value)
    {
        $this->appid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAppsecret($value)
    {
        $this->appsecret = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEncodingaeskey($value)
    {
        $this->encodingaeskey = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOauthStatus($value)
    {
        $this->oauth_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSecretKey($value)
    {
        $this->secret_key = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOauthRedirecturi($value)
    {
        $this->oauth_redirecturi = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOauthCount($value)
    {
        $this->oauth_count = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTime($value)
    {
        $this->time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSort($value)
    {
        $this->sort = $value;
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
    public function setDefaultWx($value)
    {
        $this->default_wx = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }
}
