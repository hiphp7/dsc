<?php

namespace App\Models;

use App\Entities\WholesaleDeliveryGoods as Base;

/**
 * Class WholesaleDeliveryGoods
 */
class WholesaleDeliveryGoods extends Base
{
    /**
     * 关联批发商品
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWholesale()
    {
        return $this->hasOne('App\Models\Wholesale', 'goods_id', 'goods_id');
    }

    /**
     * 关联批发订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getWholesaleDeliveryOrder()
    {
        return $this->hasOne('App\Models\WholesaleDeliveryOrder', 'delivery_id', 'delivery_id');
    }
}
