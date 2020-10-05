<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsConsumption
 */
class GoodsConsumption extends Model
{
    protected $table = 'goods_consumption';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'cfull',
        'creduce'
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
    public function getCfull()
    {
        return $this->cfull;
    }

    /**
     * @return mixed
     */
    public function getCreduce()
    {
        return $this->creduce;
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
    public function setCfull($value)
    {
        $this->cfull = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreduce($value)
    {
        $this->creduce = $value;
        return $this;
    }
}
