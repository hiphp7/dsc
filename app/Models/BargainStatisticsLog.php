<?php

namespace App\Models;

use App\Entities\BargainStatisticsLog as Base;

/**
 * Class BargainStatisticsLog
 */
class BargainStatisticsLog extends Base
{
    /**
     * 关联活动
     *
     * @param bargain_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getBargainGoods()
    {
        return $this->hasOne('App\Models\BargainGoods', 'id', 'bargain_id');
    }

    /**
     * 关联会员
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联砍价
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getBargainStatistics()
    {
        return $this->hasMany('App\Models\BargainStatistics', 'bs_id', 'id');
    }


}
