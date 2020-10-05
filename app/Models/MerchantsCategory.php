<?php

namespace App\Models;

use App\Entities\MerchantsCategory as Base;
use App\Repositories\Common\DscRepository;

/**
 * Class MerchantsCategory
 */
class MerchantsCategory extends Base
{
    protected $appends = ['url'];

    /**
     * 获取当前分类子分类树
     *
     * @param int $parent_id
     * @return MerchantsCategory|\Illuminate\Database\Eloquent\Builder
     */
    public static function getList($parent_id = 0)
    {
        $model = MerchantsCategory::with([
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function catList()
    {
        return $this->hasMany('App\Models\MerchantsCategory', 'parent_id')->with('catList');
    }

    /**
     * url地址
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        $this->attributes['user_id'] = isset($this->attributes['user_id']) ? $this->attributes['user_id'] : 0;

        if (isset($this->attributes['cat_id']) && $this->attributes['cat_id']) {
            $other = [
                'cid' => $this->attributes['cat_id']
            ];
            $url = app(DscRepository::class)->sellerUrl($this->attributes['user_id'], $other);
            return $url;
        } else {
            return false;
        }
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
     * 获取父级分类信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getParentCatInfo()
    {
        return $this->hasOne('App\Models\MerchantsCategory', 'cat_id', 'parent_id');
    }
}
