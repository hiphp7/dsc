<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ExchangeGoods
 */
class ExchangeGoods extends Model
{
    protected $table = 'exchange_goods';

    protected $primaryKey = 'eid';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'review_status',
        'review_content',
        'user_id',
        'exchange_integral',
        'market_integral',
        'is_exchange',
        'is_hot',
        'is_best'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getReviewContent()
    {
        return $this->review_content;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getExchangeIntegral()
    {
        return $this->exchange_integral;
    }

    /**
     * @return mixed
     */
    public function getMarketIntegral()
    {
        return $this->market_integral;
    }

    /**
     * @return mixed
     */
    public function getIsExchange()
    {
        return $this->is_exchange;
    }

    /**
     * @return mixed
     */
    public function getIsHot()
    {
        return $this->is_hot;
    }

    /**
     * @return mixed
     */
    public function getIsBest()
    {
        return $this->is_best;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExchangeIntegral($value)
    {
        $this->exchange_integral = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMarketIntegral($value)
    {
        $this->market_integral = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsExchange($value)
    {
        $this->is_exchange = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsHot($value)
    {
        $this->is_hot = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsBest($value)
    {
        $this->is_best = $value;
        return $this;
    }
}
