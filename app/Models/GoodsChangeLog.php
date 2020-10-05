<?php

namespace App\Models;

use App\Entities\GoodsChangeLog as Base;

/**
 * Class GoodsChangeLog
 */
class GoodsChangeLog extends Base
{
    /**
     * 关联商品
     *
     * @access  public
     * @return array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联管理员
     *
     * @access  public
     * @return array
     */
    public function getAdminUser()
    {
        return $this->hasOne('App\Models\AdminUser', 'user_id', 'user_id');
    }
}
