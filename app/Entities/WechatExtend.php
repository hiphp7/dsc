<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatExtend
 */
class WechatExtend extends Model
{
    protected $table = 'wechat_extend';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'name',
        'keywords',
        'command',
        'config',
        'type',
        'enable',
        'author',
        'website'
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
    public function getConfig()
    {
        return $this->config;
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
    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return mixed
     */
    public function getWebsite()
    {
        return $this->website;
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
    public function setConfig($value)
    {
        $this->config = $value;
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
    public function setEnable($value)
    {
        $this->enable = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAuthor($value)
    {
        $this->author = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWebsite($value)
    {
        $this->website = $value;
        return $this;
    }
}
