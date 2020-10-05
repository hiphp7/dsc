<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LinkDescGoodsid
 */
class LinkDescGoodsid extends Model
{
    protected $table = 'link_desc_goodsid';

    public $timestamps = false;

    protected $fillable = [
        'd_id',
        'goods_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getDId()
    {
        return $this->d_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDId($value)
    {
        $this->d_id = $value;
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
}
