<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SeckillGoods
 */
class SeckillGoods extends Model
{
    protected $table = 'seckill_goods';

    public $timestamps = false;

    protected $fillable = [
        'sec_id',
        'tb_id',
        'goods_id',
        'sec_price',
        'sec_num',
        'sec_limit'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getSecId()
    {
        return $this->sec_id;
    }

    /**
     * @return mixed
     */
    public function getTbId()
    {
        return $this->tb_id;
    }

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
    public function getSecPrice()
    {
        return $this->sec_price;
    }

    /**
     * @return mixed
     */
    public function getSecNum()
    {
        return $this->sec_num;
    }

    /**
     * @return mixed
     */
    public function getSecLimit()
    {
        return $this->sec_limit;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSecId($value)
    {
        $this->sec_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTbId($value)
    {
        $this->tb_id = $value;
        return $this;
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
    public function setSecPrice($value)
    {
        $this->sec_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSecNum($value)
    {
        $this->sec_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSecLimit($value)
    {
        $this->sec_limit = $value;
        return $this;
    }
}
