<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsConshipping
 */
class GoodsConshipping extends Model
{
    protected $table = 'goods_conshipping';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'sfull',
        'sreduce'
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
    public function getSfull()
    {
        return $this->sfull;
    }

    /**
     * @return mixed
     */
    public function getSreduce()
    {
        return $this->sreduce;
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
    public function setSfull($value)
    {
        $this->sfull = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSreduce($value)
    {
        $this->sreduce = $value;
        return $this;
    }
}
