<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CommentBaseline
 */
class CommentBaseline extends Model
{
    protected $table = 'comment_baseline';

    public $timestamps = false;

    protected $fillable = [
        'goods',
        'service',
        'shipping'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getGoods()
    {
        return $this->goods;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return mixed
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoods($value)
    {
        $this->goods = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setService($value)
    {
        $this->service = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShipping($value)
    {
        $this->shipping = $value;
        return $this;
    }
}
