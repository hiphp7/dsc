<?php

namespace App\Models;

use App\Entities\ZcCategory as Base;

/**
 * Class ZcCategory
 */
class ZcCategory extends Base
{
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
     * 关联分类信息
     *
     * @access  public
     * @return array
     */
    public function scopeCatInfo($query, $cat_id)
    {
        return $query->where('cat_id', $cat_id);
    }
}
