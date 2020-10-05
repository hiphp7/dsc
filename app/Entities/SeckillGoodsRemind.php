<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SeckillGoodsRemind
 */
class SeckillGoodsRemind extends Model
{
    protected $table = 'seckill_goods_remind';

    protected $primaryKey = 'r_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'sec_goods_id',
        'add_time'
    ];

    protected $guarded = [];


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
    public function getSecGoodsId()
    {
        return $this->sec_goods_id;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
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
    public function setSecGoodsId($value)
    {
        $this->sec_goods_id = $value;
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
}
