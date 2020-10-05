<?php

namespace App\Models;

use App\Entities\GoodsArticle as Base;

/**
 * Class GoodsArticle
 */
class GoodsArticle extends Base
{

    /**
     * 关联文章
     *
     * @access  public
     * @param article_id
     * @return  array
     */
    public function getArticle()
    {
        return $this->hasMany('App\Models\Article', 'article_id', 'article_id');
    }

    /**
     * 关联文章
     *
     * @access  public
     * @param article_id
     * @return  array
     */
    public function getArticleInfo()
    {
        return $this->hasOne('App\Models\Article', 'article_id', 'article_id');
    }

    /**
     * 关联商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne("App\Models\Goods", 'goods_id', 'goods_id');
    }

    /**
     * 关联商品会员价格
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getMemberPrice()
    {
        return $this->hasMany('App\Models\MemberPrice', 'goods_id', 'goods_id');
    }

    /**
     * 关联仓库商品信息查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseGoods()
    {
        return $this->hasOne('App\Models\WarehouseGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联仓库地区商品查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseAreaGoods()
    {
        return $this->hasOne('App\Models\WarehouseAreaGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联地区显示商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getLinkAreaGoods()
    {
        return $this->hasOne('App\Models\LinkAreaGoods', 'goods_id', 'goods_id');
    }
}
