<?php

namespace App\Models;

use App\Entities\BaitiaoPayLog as Base;

/**
 * Class BaitiaoPayLog
 */
class BaitiaoPayLog extends Base
{
    /**
     * 关联白条记录
     *
     * @access  public
     * @param log_id
     * @return  array
     */
    public function getBaitiaoLog()
    {
        return $this->hasOne('App\Models\BaitiaoLog', 'log_id', 'log_id');
    }
}
