<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Attribute
 */
class Attribute extends Model
{
    protected $table = 'attribute';

    protected $primaryKey = 'attr_id';

    public $timestamps = false;

    protected $fillable = [
        'cat_id',
        'attr_name',
        'attr_cat_type',
        'attr_input_type',
        'attr_type',
        'attr_values',
        'color_values',
        'attr_index',
        'sort_order',
        'is_linked',
        'attr_group',
        'attr_input_category',
        'cloud_attr_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @return mixed
     */
    public function getAttrName()
    {
        return $this->attr_name;
    }

    /**
     * @return mixed
     */
    public function getAttrCatType()
    {
        return $this->attr_cat_type;
    }

    /**
     * @return mixed
     */
    public function getAttrInputType()
    {
        return $this->attr_input_type;
    }

    /**
     * @return mixed
     */
    public function getAttrType()
    {
        return $this->attr_type;
    }

    /**
     * @return mixed
     */
    public function getAttrValues()
    {
        return $this->attr_values;
    }

    /**
     * @return mixed
     */
    public function getColorValues()
    {
        return $this->color_values;
    }

    /**
     * @return mixed
     */
    public function getAttrIndex()
    {
        return $this->attr_index;
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
    public function getIsLinked()
    {
        return $this->is_linked;
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
    public function getAttrInputCategory()
    {
        return $this->attr_input_category;
    }

    /**
     * @return mixed
     */
    public function getCloudAttrId()
    {
        return $this->cloud_attr_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatId($value)
    {
        $this->cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrName($value)
    {
        $this->attr_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrCatType($value)
    {
        $this->attr_cat_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrInputType($value)
    {
        $this->attr_input_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrType($value)
    {
        $this->attr_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrValues($value)
    {
        $this->attr_values = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setColorValues($value)
    {
        $this->color_values = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrIndex($value)
    {
        $this->attr_index = $value;
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
    public function setIsLinked($value)
    {
        $this->is_linked = $value;
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
    public function setAttrInputCategory($value)
    {
        $this->attr_input_category = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCloudAttrId($value)
    {
        $this->cloud_attr_id = $value;
        return $this;
    }
}
