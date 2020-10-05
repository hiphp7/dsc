<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GroupGoods
 */
class GroupGoods extends Model
{
    protected $table = 'group_goods';

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'goods_id',
        'goods_price',
        'admin_id',
        'group_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
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
    public function getGoodsPrice()
    {
        return $this->goods_price;
    }

    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
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
    public function setGoodsPrice($value)
    {
        $this->goods_price = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setGroupId($value)
    {
        $this->group_id = $value;
        return $this;
    }
}
