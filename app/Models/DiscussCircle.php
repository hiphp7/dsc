<?php

namespace App\Models;

use App\Entities\DiscussCircle as Base;

/**
 * Class DiscussCircle
 */
class DiscussCircle extends Base
{
    /**
     * 关联会员
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
