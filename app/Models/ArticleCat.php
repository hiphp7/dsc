<?php

namespace App\Models;

use App\Entities\ArticleCat as Base;

/**
 * Class ArticleCat
 */
class ArticleCat extends Base
{
    protected $appends = ['url'];

    /**
     * article
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function article()
    {
        return $this->belongsTo('App\Models\Article', 'cat_id', 'cat_id');
    }

    /**
     * 获取当前分类树
     * @param int $parent_id
     * @return $this
     */
    public static function getList($parent_id = 0)
    {
        $model = ArticleCat::with('catList')->where('parent_id', $parent_id);

        return $model;
    }

    /**
     * 关联当前分类
     * @return $this
     */
    public function catList()
    {
        return $this->hasMany('App\Models\ArticleCat', 'parent_id')->with('catList');
    }

    /**
     * 关联当前分类父级
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getCatParent()
    {
        return $this->hasOne('App\Models\ArticleCat', 'parent_id', 'cat_id');
    }

    /**
     * 关联文章单条数据
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getArticleFirst()
    {
        return $this->hasOne('App\Models\Article', 'cat_id', 'cat_id');
    }

    /**
     * 关联文章
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getArticle()
    {
        return $this->hasMany('App\Models\Article', 'cat_id', 'cat_id');
    }

    /**
     * 关联文章查询
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getArticleList()
    {
        return $this->hasMany('App\Models\Article', 'cat_id', 'cat_id');
    }

    /**
     * 关联文章分类子分类
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getArticleCatChildrenList()
    {
        return $this->hasMany('App\Models\ArticleCat', 'parent_id', 'cat_id');
    }

    /**
     * url地址
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        if (isset($this->attributes['cat_id']) && $this->attributes['cat_id']) {
            return route('article_cat', ['id' => $this->attributes['cat_id']]);
        } else {
            return false;
        }
    }
}
