<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatRedpackAdvertice
 */
class WechatRedpackAdvertice extends Model
{
    protected $table = 'wechat_redpack_advertice';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'market_id',
        'icon',
        'content',
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
    public function getMarketId()
    {
        return $this->market_id;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
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
    public function setMarketId($value)
    {
        $this->market_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIcon($value)
    {
        $this->icon = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContent($value)
    {
        $this->content = $value;
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
