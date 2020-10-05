<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TeamCategory
 */
class GroupbuyCategory extends Model
{
    protected $table = 'groupbuy_category';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'parent_id',
        'cat_img',
        'sort_order',
        'status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

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
    public function getCatImg()
    {
        return $this->cat_img;
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
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
    public function setCatImg($value)
    {
        $this->cat_img = $value;
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
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }
}
