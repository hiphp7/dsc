<?php

namespace App\Models;

use App\Entities\Category as Base;

/**
 * Class Category
 */
class Category extends Base
{
    protected $appends = ['url'];

    /**
     * 获取当前分类子分类树
     *
     * @access  public
     * @return array
     */
    public static function getList($parent_id = 0)
    {
        $model = Category::with([
            'catList' => function ($query) {
                $query = $query->where('is_show', 1);
                $query->orderBy('sort_order')->orderBy('cat_id');
            }
        ])
            ->where('parent_id', $parent_id);

        return $model;
    }

    /**
     * 关联当前分类
     *
     * @access  public
     * @return array
     */
    public function catList()
    {
        return $this->hasMany('App\Models\Category', 'parent_id')->with('catList');
    }

    /**
     * 递归显示父级ID
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function catParentList()
    {
        return $this->hasMany('App\Models\Category', 'cat_id', 'parent_id')->select('cat_id', 'parent_id')->with([
            'catParentList' => function ($query) {
                $query->select('cat_id', 'parent_id');
            }
        ]);
    }

    /**
     * 关联分类信息
     *
     * @access  public
     * @return array
     */
    public function scopeCatInfo($query, $cat_id)
    {
        return $query->where('cat_id', $cat_id);
    }

    /**
     * url地址
     * @return string
     */
    public function getUrlAttribute()
    {
        $this->attributes['cat_id'] = $this->attributes['cat_id'] ?? 0;
        return route('category', ['id' => $this->attributes['cat_id']]);
    }

    /**
     * 分类下单个商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'cat_id', 'cat_id');
    }

    /**
     * 分类下全部商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function goods()
    {
        return $this->hasMany('App\Models\Goods', 'cat_id', 'cat_id');
    }

    /**
     * 关联仓库商品信息查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoodsList()
    {
        return $this->hasMany('App\Models\Goods', 'cat_id', 'cat_id');
    }

    public function getMerchantsCategory()
    {
        return $this->hasOne('App\Models\MerchantsCategory', 'cat_id', 'cat_id');
    }
}
