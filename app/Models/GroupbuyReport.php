<?php

namespace App\Models;

use App\Entities\GroupbuyReport as Base;

/**
 * Class GroupbuyReport
 */
class GroupbuyReport extends Base
{

    /**
     * 关联订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getGroupbuyOrder()
    {
        return $this->hasOne('App\Models\GroupbuyOrder', 'order_id', 'order_id');
    }

    /**
     * 关联商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGroupbuyGoods()
    {
        return $this->hasOne('App\Models\GroupbuyGoods', 'goods_id', 'report_object_id');
    }

    /**
     * 关联团长
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGroupbuyLeader()
    {
        return $this->hasOne('App\Models\GroupbuyLeader', 'leader_id', 'report_object_id');
    }

    /**
     * 关联举报类型
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGroupbuyReportType()
    {
        return $this->hasOne('App\Models\GroupbuyReportType', 'type_id', 'type_id');
    }


}
