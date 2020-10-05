<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsCategoryTemporarydate
 */
class MerchantsCategoryTemporarydate extends Model
{
    protected $table = 'merchants_category_temporarydate';

    protected $primaryKey = 'ct_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'cat_id',
        'parent_id',
        'cat_name',
        'parent_name',
        'is_add'
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
    public function getCatId()
    {
        return $this->cat_id;
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
    public function getCatName()
    {
        return $this->cat_name;
    }

    /**
     * @return mixed
     */
    public function getParentName()
    {
        return $this->parent_name;
    }

    /**
     * @return mixed
     */
    public function getIsAdd()
    {
        return $this->is_add;
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
    public function setCatId($value)
    {
        $this->cat_id = $value;
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
    public function setCatName($value)
    {
        $this->cat_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentName($value)
    {
        $this->parent_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsAdd($value)
    {
        $this->is_add = $value;
        return $this;
    }
}
