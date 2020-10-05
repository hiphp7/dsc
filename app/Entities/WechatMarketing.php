<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatMarketing
 */
class WechatMarketing extends Model
{
    protected $table = 'wechat_marketing';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'marketing_type',
        'name',
        'keywords',
        'command',
        'description',
        'starttime',
        'endtime',
        'addtime',
        'logo',
        'background',
        'config',
        'support',
        'status',
        'qrcode',
        'url'
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
    public function getMarketingType()
    {
        return $this->marketing_type;
    }

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
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getStarttime()
    {
        return $this->starttime;
    }

    /**
     * @return mixed
     */
    public function getEndtime()
    {
        return $this->endtime;
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
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @return mixed
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return mixed
     */
    public function getSupport()
    {
        return $this->support;
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
    public function getQrcode()
    {
        return $this->qrcode;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
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
    public function setMarketingType($value)
    {
        $this->marketing_type = $value;
        return $this;
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
    public function setKeywords($value)
    {
        $this->keywords = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommand($value)
    {
        $this->command = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStarttime($value)
    {
        $this->starttime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndtime($value)
    {
        $this->endtime = $value;
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
    public function setLogo($value)
    {
        $this->logo = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBackground($value)
    {
        $this->background = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConfig($value)
    {
        $this->config = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSupport($value)
    {
        $this->support = $value;
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
    public function setQrcode($value)
    {
        $this->qrcode = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUrl($value)
    {
        $this->url = $value;
        return $this;
    }
}
