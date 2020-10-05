<?php

namespace App\Models;

use App\Entities\SellerAccountLog as Base;

/**
 * Class SellerAccountLog
 */
class SellerAccountLog extends Base
{
    /**
     * 关联账单订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getSellerBillOrder()
    {
        return $this->hasOne('App\Models\SellerBillOrder', 'order_id', 'order_id');
    }

    /**
     * 关联订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联入驻商家
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }
}
