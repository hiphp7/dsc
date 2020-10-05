<?php

namespace App\Models;

use App\Entities\WholesaleCat as Base;

/**
 * Class WholesaleCat
 */
class WholesaleCat extends Base
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
        $model = WholesaleCat::with([
            'catList' => function ($query) {
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
        return $this->hasMany('App\Models\WholesaleCat', 'parent_id')->with('catList');
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
        return route('wholesale_cat', ['id' => $this->attributes['cat_id']]);
    }
}
