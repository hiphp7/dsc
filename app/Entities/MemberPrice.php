<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MemberPrice
 */
class MemberPrice extends Model
{
    protected $table = 'member_price';

    protected $primaryKey = 'price_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'user_rank',
        'user_price'
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
    public function getUserRank()
    {
        return $this->user_rank;
    }

    /**
     * @return mixed
     */
    public function getUserPrice()
    {
        return $this->user_price;
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
    public function setUserRank($value)
    {
        $this->user_rank = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserPrice($value)
    {
        $this->user_price = $value;
        return $this;
    }
}
