<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsType
 */
class GoodsType extends Model
{
    protected $table = 'goods_type';

    protected $primaryKey = 'cat_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'suppliers_id',
        'cat_name',
        'enabled',
        'attr_group',
        'c_id'
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
    public function getSuppliersId()
    {
        return $this->suppliers_id;
    }

    /**
     * @return mixed
     */
    public function getCatName()
    {
        return $this->cat_name;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return mixed
     */
    public function getAttrGroup()
    {
        return $this->attr_group;
    }

    /**
     * @return mixed
     */
    public function getCId()
    {
        return $this->c_id;
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
    public function setSuppliersId($value)
    {
        $this->suppliers_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatName($value)
    {
        $this->cat_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEnabled($value)
    {
        $this->enabled = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrGroup($value)
    {
        $this->attr_group = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCId($value)
    {
        $this->c_id = $value;
        return $this;
    }
}
