<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ArticleCat
 */
class ArticleCat extends Model
{
    protected $table = 'article_cat';

    protected $primaryKey = 'cat_id';

    public $timestamps = false;

    protected $fillable = [
        'cat_name',
        'cat_type',
        'keywords',
        'cat_desc',
        'sort_order',
        'show_in_nav',
        'parent_id'
    ];

    protected $guarded = [];


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
    public function getCatType()
    {
        return $this->cat_type;
    }

    /**
     * @return mixed
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return mixed
     */
    public function getCatDesc()
    {
        return $this->cat_desc;
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
    public function getShowInNav()
    {
        return $this->show_in_nav;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
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
    public function setCatType($value)
    {
        $this->cat_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKeywords($value)
    {
        $this->keywords = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatDesc($value)
    {
        $this->cat_desc = $value;
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
    public function setShowInNav($value)
    {
        $this->show_in_nav = $value;
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
}
