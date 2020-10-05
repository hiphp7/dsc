<?php

namespace App\Services\Order;


class OrderStatusService
{
    /**
     * 订单状态
     *
     * @param $status
     * @return mixed
     * @throws \Exception
     */
    public function orderStatus($status = 0)
    {
        $array = [
            OS_UNCONFIRMED => lang('order.os.' . OS_UNCONFIRMED),
            OS_CONFIRMED => lang('order.os.' . OS_CONFIRMED),
            OS_CANCELED => lang('order.os.' . OS_CANCELED),
            OS_INVALID => lang('order.os.' . OS_INVALID),
            OS_RETURNED => lang('order.os.' . OS_RETURNED),
            OS_SPLITED => lang('order.os.' . OS_SPLITED),
            OS_SPLITING_PART => lang('order.os.' . OS_SPLITING_PART),
            OS_RETURNED_PART => lang('order.os.' . OS_RETURNED_PART),
            OS_ONLY_REFOUND => lang('order.os.' . OS_ONLY_REFOUND)
        ];
        return $array[$status];
    }

    /**
     * 支付状态
     *
     * @param $status
     * @return mixed
     * @throws \Exception
     */
    public function payStatus($status = 0)
    {
        $array = [
            PS_UNPAYED => lang('order.ps.' . PS_UNPAYED),
            PS_PAYING => lang('order.ps.' . PS_PAYING),
            PS_PAYED => lang('order.ps.' . PS_PAYED),
            PS_PAYED_PART => lang('order.ps.' . PS_PAYED_PART),
            PS_REFOUND => lang('order.ps.' . PS_REFOUND),
            PS_REFOUND_PART => lang('order.ps.' . PS_REFOUND_PART),
            PS_MAIN_PAYED_PART => lang('order.ps.' . PS_MAIN_PAYED_PART)
        ];

        return $array[$status];
    }

    /**
     * 配送状态
     *
     * @param int $status
     * @return mixed
     * @throws \Exception
     */
    public function shipStatus($status = 0)
    {
        $array = [
            SS_UNSHIPPED => lang('order.ss.' . SS_UNSHIPPED),
            SS_SHIPPED => lang('order.ss.' . SS_SHIPPED),
            SS_RECEIVED => lang('order.ss.' . SS_RECEIVED),
            SS_PREPARING => lang('order.ss.' . SS_PREPARING),
            SS_SHIPPED_PART => lang('order.ss.' . SS_SHIPPED_PART),
            SS_SHIPPED_ING => lang('order.ss.' . SS_SHIPPED_ING),
            OS_SHIPPED_PART => lang('order.ss.' . OS_SHIPPED_PART),
            SS_PART_RECEIVED => lang('order.ss.' . SS_PART_RECEIVED)
        ];

        return $array[$status];
    }
}
