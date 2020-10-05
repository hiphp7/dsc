<?php

namespace App\Custom\Distribute\Repositories;

use App\Models\CouponsUser;
use App\Models\Goods;
use App\Models\OrderReturn;
use App\Models\SellerBillOrder;
use App\Models\UserBonus;
use App\Models\ValueCard;
use App\Models\ValueCardRecord;
use App\Repositories\Common\TimeRepository;

/**
 * Class OrderRefundRepository
 * @package App\Custom\Distribute\Repositories
 */
class OrderRefundRepository
{
    protected $timeRepository;

    public function __construct(
        TimeRepository $timeRepository
    )
    {
        $this->timeRepository = $timeRepository;
    }

    /**
     * 查询订单退换货已退金额、已退运费
     * @param int $order_id
     * @param int $ret_id
     * @return array
     */
    public function orderReturnFee($order_id = 0, $ret_id = 0)
    {
        $res = OrderReturn::selectRaw('SUM(actual_return) AS actual_return, SUM(return_shipping_fee) AS return_shipping_fee')
            ->where('order_id', $order_id)
            ->whereIn('refund_type', [1, 3])
            ->where('refound_status', 1);

        if ($ret_id > 0) {
            $res = $res->where('ret_id', '<>', $ret_id);
        }

        $res = $res->first();

        $fee = $res ? $res->toArray() : [];

        return $fee;
    }

    /**
     * 退款 - 更新商品销量
     * @param int $goods_id
     * @param int $return_number
     */
    public function updateGoodsSale($goods_id = 0, $return_number = 0)
    {
        Goods::where('goods_id', $goods_id)->decrement('sales_volume', $return_number);
    }

    /**
     * 退款 - 更新账单
     * @param int $order_id
     * @param int $refund_amount
     * @param int $refund_shipping_fee
     */
    public function updateSellerBillOrder($order_id = 0, $refund_amount = 0, $refund_shipping_fee = 0)
    {
        $other = $refund_shipping_fee > 0 ? ['return_shippingfee' => $refund_shipping_fee] : [];
        SellerBillOrder::where('order_id', $order_id)->increment('return_amount', $refund_amount, $other);
    }

    /**
     * 退款 - 如果有 退还红包
     * @param int $order_id
     */
    public function return_bonus($order_id = 0)
    {
        UserBonus::where('order_id', $order_id)->update(['used_time' => '', 'order_id' => '']);
    }

    /**
     * 退款 - 如果有 退还优惠券
     * @param int $order_id
     */
    public function return_coupons($order_id = 0)
    {
        // 判断当前订单是否满足了返券要求
        $other = [
            'order_id' => 0,
            'is_use_time' => 0,
            'is_use' => 0
        ];
        CouponsUser::where('order_id', $order_id)->update($other);
    }

    /**
     * 退款 - 如果有 退还储值卡
     * @param int $order_id
     * @return int|mixed
     */
    public function return_card_money($order_id = 0)
    {
        $row = ValueCardRecord::where('order_id', $order_id)->first();
        $row = $row ? $row->toArray() : [];

        if ($row) {
            /* 更新储值卡金额 */
            ValueCard::where('vid', $row['vc_id'])->increment('card_money', $row['use_val']);

            /* 更新订单使用储值卡金额 */
            //ValueCardRecord::where('vc_id', $row['vc_id'])->where('order_id', $order_id)->decrement('use_val', $row['use_val']);

            /* 更新储值卡金额使用日志 */
            $time = $this->timeRepository->getGmTime();
            $log = [
                'vc_id' => $row['vc_id'],
                'order_id' => $order_id,
                'use_val' => $row['use_val'],
                'vc_dis' => 1,
                'add_val' => $row['use_val'],
                'record_time' => $time
            ];

            ValueCardRecord::insert($log);

            return $row['use_val'];
        }

        return 0;
    }

    /**
     * 退换货订单数量
     * @param int $order_id
     * @return int
     */
    public function orderReturnCount($order_id = 0)
    {
        if (empty($order_id)) {
            return 0;
        }

        $count = OrderReturn::where('order_id', $order_id)->count();

        return $count;
    }

    /**
     * 退货单商品（单品退换货记录）
     * @param int $rec_id
     * @return int
     */
    public function order_return_ret($rec_id = 0)
    {
        if (empty($rec_id)) {
            return 0;
        }

        $ret_id = OrderReturn::where('rec_id', $rec_id)->value('ret_id');

        return $ret_id;
    }

}
