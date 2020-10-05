<?php

namespace App\Models;

use App\Entities\Article as Base;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use Illuminate\Support\Carbon;

/**
 * Class Article
 */
class Article extends Base
{
    protected $guarded = [];

    protected $appends = ['id', 'url', 'album', 'amity_time'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function extend()
    {
        return $this->hasOne('App\Models\ArticleExtend', 'article_id', 'article_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comment()
    {
        return $this->hasMany('App\Models\Comment', 'id_value', 'article_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function goods()
    {
        return $this->belongsToMany('App\Models\Goods', 'goods_article', 'article_id', 'goods_id');
    }

    /**
     * 返回文章友好时间
     * @return string
     */
    public function getAmityTimeAttribute()
    {
        $this->attributes['add_time'] = $this->attributes['add_time'] ?? 0;
        $timestamp = strtotime(app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $this->attributes['add_time']));

        return Carbon::createFromTimestamp($timestamp)->diffForHumans();
    }

    /**
     * 返回文章ID
     * @return mixed
     */
    public function getIdAttribute()
    {
        return $this->attributes['article_id'] ?? 0;
    }

    /**
     * 返回文章内容图片
     * @return array
     */
    public function getAlbumAttribute()
    {
        $pattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png|\.bmp|\.jpeg]))[\'|\"].*?[\/]?>/";
        if (isset($this->attributes['content'])) {
            preg_match_all($pattern, $this->attributes['content'], $match);
        }
        $album = [];
        if (isset($match) && count($match[1]) > 0) {
            foreach ($match[1] as $img) {
                if (strtolower(substr($img, 0, 4)) != 'http') {
                    $realPath = mb_substr($img, stripos($img, 'images/'));
                    $album[] = app(DscRepository::class)->getImagePath($realPath);
                } else {
                    $album[] = $img;
                }
            }
        }

        // 超过三图则取前三
        if (count($album) > 3) {
            $album = array_slice($album, 0, 3);
        }
        return $album;
    }

    /**
     * 返回文章url链接
     * @return string
     */
    public function getUrlAttribute()
    {
        $this->attributes['article_id'] = $this->attributes['article_id'] ?? 0;
        return route('article', ['id' => $this->attributes['article_id']]);
    }

    /**
     * 关联文章分类查询
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getArticleCatFirst()
    {
        return $this->hasOne('App\Models\ArticleCat', 'cat_id', 'cat_id');
    }

    /**
     * 关联文章评论
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getComment()
    {
        return $this->hasOne('App\Models\Comment', 'id_value', 'article_id');
    }

    /**
     * 关联文章分类
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getArticleCat()
    {
        return $this->hasOne('App\Models\ArticleCat', 'cat_id', 'cat_id');
    }

    /**
     * 关联文章扩展
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getArticleExtend()
    {
        return $this->hasOne('App\Models\ArticleExtend', 'article_id', 'article_id');
    }

    /**
     * 关联自动管理文章
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getAutoManage()
    {
        return $this->hasOne('App\Models\AutoManage', 'item_id', 'article_id');
    }
}
