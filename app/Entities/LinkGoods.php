<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LinkGoods
 */
class LinkGoods extends Model
{
    protected $table = 'link_goods';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'link_goods_id',
        'is_double',
        'admin_id'
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
    public function getLinkGoodsId()
    {
        return $this->link_goods_id;
    }

    /**
     * @return mixed
     */
    public function getIsDouble()
    {
        return $this->is_double;
    }

    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
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
    public function setLinkGoodsId($value)
    {
        $this->link_goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDouble($value)
    {
        $this->is_double = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdminId($value)
    {
        $this->admin_id = $value;
        return $this;
    }
}
