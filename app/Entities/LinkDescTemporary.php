<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LinkDescTemporary
 */
class LinkDescTemporary extends Model
{
    protected $table = 'link_desc_temporary';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'ru_id'
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
    public function getRuId()
    {
        return $this->ru_id;
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
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }
}
