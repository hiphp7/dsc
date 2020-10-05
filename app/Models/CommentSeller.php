<?php

namespace App\Models;

use App\Entities\CommentSeller as Base;

/**
 * Class CommentSeller
 */
class CommentSeller extends Base
{
    /**
     * 关联会员
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联商家
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getSellerShopinfo()
    {
        return $this->hasOne('App\Models\SellerShopinfo', 'ru_id', 'ru_id');
    }

    /**
     * 关联订单商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderInfo()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联店铺
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'ru_id', 'ru_id');
    }
}
