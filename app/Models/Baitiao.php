<?php

namespace App\Models;

use App\Entities\Baitiao as Base;

/**
 * Class Baitiao
 */
class Baitiao extends Base
{
    /**
     * 关联白条日志
     *
     * @access  public
     * @param baitiao_id
     * @return  array
     */
    public function getBaitiaoLog()
    {
        return $this->hasOne('App\Models\BaitiaoLog', 'baitiao_id', 'baitiao_id');
    }

    /**
     * 关联白条列表日志
     *
     * @access  public
     * @param baitiao_id
     * @return  array
     */
    public function getBaitiaoLogList()
    {
        return $this->hasMany('App\Models\BaitiaoLog', 'baitiao_id', 'baitiao_id');
    }
}
