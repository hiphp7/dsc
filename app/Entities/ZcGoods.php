<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ZcGoods
 */
class ZcGoods extends Model
{
    protected $table = 'zc_goods';

    public $timestamps = false;

    protected $fillable = [
        'pid',
        'limit',
        'backer_num',
        'price',
        'shipping_fee',
        'content',
        'img',
        'return_time',
        'backer_list'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return mixed
     */
    public function getBackerNum()
    {
        return $this->backer_num;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getShippingFee()
    {
        return $this->shipping_fee;
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
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @return mixed
     */
    public function getReturnTime()
    {
        return $this->return_time;
    }

    /**
     * @return mixed
     */
    public function getBackerList()
    {
        return $this->backer_list;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPid($value)
    {
        $this->pid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLimit($value)
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBackerNum($value)
    {
        $this->backer_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPrice($value)
    {
        $this->price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingFee($value)
    {
        $this->shipping_fee = $value;
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
    public function setImg($value)
    {
        $this->img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnTime($value)
    {
        $this->return_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBackerList($value)
    {
        $this->backer_list = $value;
        return $this;
    }
}
