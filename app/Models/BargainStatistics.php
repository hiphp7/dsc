<?php

namespace App\Models;

use App\Entities\BargainStatistics as Base;

/**
 * Class BargainStatistics
 */
class BargainStatistics extends Base
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

    /**
     * 关联参与砍价活动
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getBargainStatisticsLog()
    {
        return $this->hasOne('App\Models\BargainStatisticsLog', 'id', 'bs_id');
    }

}
