<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BargainStatisticsLog
 */
class BargainStatisticsLog extends Model
{
    protected $table = 'bargain_statistics_log';

    public $timestamps = false;

    protected $fillable = [
        'bargain_id',
        'goods_attr_id',
        'user_id',
        'final_price',
        'add_time',
        'count_num',
        'status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBargainId()
    {
        return $this->bargain_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsAttrId()
    {
        return $this->goods_attr_id;
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
    public function getFinalPrice()
    {
        return $this->final_price;
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
    public function getCountNum()
    {
        return $this->count_num;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBargainId($value)
    {
        $this->bargain_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsAttrId($value)
    {
        $this->goods_attr_id = $value;
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
    public function setFinalPrice($value)
    {
        $this->final_price = $value;
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
    public function setCountNum($value)
    {
        $this->count_num = $value;
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
}
