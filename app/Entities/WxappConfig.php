<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WxappConfig
 */
class WxappConfig extends Model
{
    protected $table = 'wxapp_config';

    public $timestamps = false;

    protected $fillable = [
        'wx_appname',
        'wx_appid',
        'wx_appsecret',
        'wx_mch_id',
        'wx_mch_key',
        'token_secret',
        'add_time',
        'status',
        'wxcert'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getWxAppname()
    {
        return $this->wx_appname;
    }

    /**
     * @return mixed
     */
    public function getWxAppid()
    {
        return $this->wx_appid;
    }

    /**
     * @return mixed
     */
    public function getWxAppsecret()
    {
        return $this->wx_appsecret;
    }

    /**
     * @return mixed
     */
    public function getWxMchId()
    {
        return $this->wx_mch_id;
    }

    /**
     * @return mixed
     */
    public function getWxMchKey()
    {
        return $this->wx_mch_key;
    }

    /**
     * @return mixed
     */
    public function getTokenSecret()
    {
        return $this->token_secret;
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getWxcert()
    {
        return $this->wxcert;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxAppname($value)
    {
        $this->wx_appname = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxAppid($value)
    {
        $this->wx_appid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxAppsecret($value)
    {
        $this->wx_appsecret = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxMchId($value)
    {
        $this->wx_mch_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxMchKey($value)
    {
        $this->wx_mch_key = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTokenSecret($value)
    {
        $this->token_secret = $value;
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
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWxcert($value)
    {
        $this->wxcert = $value;
        return $this;
    }
}
