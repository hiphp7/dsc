<?php

namespace App\Models;

use App\Entities\PresaleActivity as Base;

/**
 * Class PresaleActivity
 */
class PresaleActivity extends Base
{
    /**
     * 关联商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联商品列表
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoodsList()
    {
        return $this->hasMany('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联预售商品分类
     *
     * @access  public
     * @param cat_id
     * @return  array
     */
    public function getPresaleCat()
    {
        return $this->hasMany('App\Models\PresaleCat', 'cat_id', 'cat_id');
    }

    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }
}
