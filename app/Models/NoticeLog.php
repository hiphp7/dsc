<?php

namespace App\Models;

use App\Entities\NoticeLog as Base;

/**
 * Class NoticeLog
 */
class NoticeLog extends Base
{
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }
}
