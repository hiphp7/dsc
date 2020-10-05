<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WholesaleCat
 */
class WholesaleCat extends Model
{
    protected $table = 'wholesale_cat';

    protected $primaryKey = 'cat_id';

    public $timestamps = false;

    protected $fillable = [
        'cat_name',
        'keywords',
        'cat_desc',
        'show_in_nav',
        'style',
        'is_show',
        'style_icon',
        'cat_icon',
        'pinyin_keyword',
        'cat_alias_name',
        'parent_id',
        'sort_order'
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
    public function getShowInNav()
    {
        return $this->show_in_nav;
    }

    /**
     * @return mixed
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @return mixed
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * @return mixed
     */
    public function getStyleIcon()
    {
        return $this->style_icon;
    }

    /**
     * @return mixed
     */
    public function getCatIcon()
    {
        return $this->cat_icon;
    }

    /**
     * @return mixed
     */
    public function getPinyinKeyword()
    {
        return $this->pinyin_keyword;
    }

    /**
     * @return mixed
     */
    public function getCatAliasName()
    {
        return $this->cat_alias_name;
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
    public function getSortOrder()
    {
        return $this->sort_order;
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
    public function setShowInNav($value)
    {
        $this->show_in_nav = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStyle($value)
    {
        $this->style = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsShow($value)
    {
        $this->is_show = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStyleIcon($value)
    {
        $this->style_icon = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatIcon($value)
    {
        $this->cat_icon = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPinyinKeyword($value)
    {
        $this->pinyin_keyword = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatAliasName($value)
    {
        $this->cat_alias_name = $value;
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
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }
}
