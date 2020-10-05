<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatPrize
 */
class WechatPrize extends Model
{
    protected $table = 'wechat_prize';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'openid',
        'prize_name',
        'issue_status',
        'winner',
        'dateline',
        'prize_type',
        'activity_type',
        'market_id'
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
    public function getPrizeName()
    {
        return $this->prize_name;
    }

    /**
     * @return mixed
     */
    public function getIssueStatus()
    {
        return $this->issue_status;
    }

    /**
     * @return mixed
     */
    public function getWinner()
    {
        return $this->winner;
    }

    /**
     * @return mixed
     */
    public function getDateline()
    {
        return $this->dateline;
    }

    /**
     * @return mixed
     */
    public function getPrizeType()
    {
        return $this->prize_type;
    }

    /**
     * @return mixed
     */
    public function getActivityType()
    {
        return $this->activity_type;
    }

    /**
     * @return mixed
     */
    public function getMarketId()
    {
        return $this->market_id;
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
    public function setPrizeName($value)
    {
        $this->prize_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIssueStatus($value)
    {
        $this->issue_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinner($value)
    {
        $this->winner = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDateline($value)
    {
        $this->dateline = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPrizeType($value)
    {
        $this->prize_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActivityType($value)
    {
        $this->activity_type = $value;
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
}
