<?php

namespace App\Models;

use App\Entities\OrderGoods as Base;

/**
 * Class OrderGoods
 */
class OrderGoods extends Base
{
    /**
     * 关联商品订单
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联商品查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    public function getProducts()
    {
        return $this->hasOne('App\Models\Products', 'product_id', 'product_id');
    }

    /**
     * 关联预售商品活动
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getPresaleActivity()
    {
        return $this->hasOne('App\Models\PresaleActivity', 'goods_id', 'goods_id');
    }

    /**
     * 关联商品评论
     *
     * @access  public
     * @param id_value
     * @return  array
     */
    public function getGoodsComment()
    {
        return $this->hasOne('App\Models\Comment', 'id_value', 'goods_id');
    }

    /**
     * 关联订单商品评论
     *
     * @access  public
     * @param id_value
     * @return  array
     */
    public function getOrderGoodsComment()
    {
        return $this->hasOne('App\Models\Comment', 'rec_id', 'rec_id');
    }

    /**
     * 关联订单商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getOrderGoodsSeller()
    {
        return $this->hasOne('App\Models\OrderGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联订单商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getOrderGoodsSellerList()
    {
        return $this->hasMany('App\Models\OrderGoods', 'goods_id', 'goods_id');
    }

    /**
     * 获取主订单商品商家
     *
     * @access  public
     * @param getOrderGoods
     * @return  Number
     */
    public function scopeSellerCount()
    {
        return $this->whereHas('getOrderGoodsSeller', function ($query) {
            $query->select("count(*) as count")->Having('count', 0);
        });
    }

    /**
     * 关联会员订单商品评论数量
     *
     * @access  public
     * @param getMainOrderId
     * @param  $user_id 会员ID
     * @param  $rec_id 订单商品ID
     * @param  $sign 类型值（0, 1, 2 ）
     * @return  Number
     */
    public function scopeGoodsCommentCount($query, $user_id = 0, $order_id = 0, $sign = 0)
    {
        $where = [
            'user_id' => $user_id,
            'order_id' => $order_id,
            'sign' => $sign
        ];
        return $query->whereHas('getOrderGoodsComment', function ($query) use ($where) {
            if ($where['sign'] > 0) {
                $query = $query->whereHas('getCommentImg', function ($query) use ($where) {
                    $query = $query->selectRaw("count(*) AS count");

                    if ($where['sign'] == 1) {
                        $query->Having('count', 0);
                    } else {
                        $query->Having('count', '>', 0);
                    }
                });
            }

            $query = $query->selectRaw('count(*) as count')
                ->where('comment_type', 0)
                ->where('parent_id', 0)
                ->where('user_id', $where['user_id']);

            if ($where['order_id'] > 0) {
                $query = $query->where('order_id', $where['order_id']);
            } else {
                $query = $query->where('order_id', '>', 0);
            }

            if ($where['sign'] > 0) {
                $query->Having('count', '>', 0);
            } else {
                $query->Having('count', 0);
            }
        });
    }

    /**
     * 关联会员等级价查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getMemberPrice()
    {
        return $this->hasOne('App\Models\MemberPrice', 'goods_id', 'goods_id');
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

    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }

    public function getOrderReturn()
    {
        return $this->hasOne('App\Models\OrderReturn', 'rec_id', 'rec_id');
    }
}
