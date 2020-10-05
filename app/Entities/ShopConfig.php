<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ShopConfig
 */
class ShopConfig extends Model
{
    protected $table = 'shop_config';

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'code',
        'type',
        'store_range',
        'store_dir',
        'value',
        'sort_order',
        'shop_group'
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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getStoreRange()
    {
        return $this->store_range;
    }

    /**
     * @return mixed
     */
    public function getStoreDir()
    {
        return $this->store_dir;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getShopGroup()
    {
        return $this->shop_group;
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
    public function setCode($value)
    {
        $this->code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoreRange($value)
    {
        $this->store_range = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoreDir($value)
    {
        $this->store_dir = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopGroup($value)
    {
        $this->shop_group = $value;
        return $this;
    }
}
