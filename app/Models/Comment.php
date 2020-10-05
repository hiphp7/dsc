<?php

namespace App\Models;

use App\Entities\Comment as Base;

/**
 * Class Comment
 */
class Comment extends Base
{
    public function user()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联评论图片
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getCommentImg()
    {
        return $this->hasMany('App\Models\CommentImg', 'comment_id', 'comment_id');
    }

    /**
     * 关联订单商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'rec_id', 'rec_id');
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
