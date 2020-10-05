<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class GuestStatsExport
 * @package App\Exports
 */
class GuestStatsExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * GuestStatsExport constructor.
     */
    public function __construct()
    {
        $this->orderCommonService = app(OrderCommonService::class);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $user_num = $this->orderCommonService->GuestStatsUserCount();
        $have_order_usernum = $this->orderCommonService->GuestStatsUserOrderCount();
        $user_all_order = $this->orderCommonService->GuestStatsUserOrderAll();
        $user_all_order['order_num'] = $user_all_order['order_num'] ?? 0;
        $user_all_order['turnover'] = $user_all_order['turnover'] ?? 0;
        $guest_all_order = $this->orderCommonService->GguestAllOrder();

        $user_turnover = isset($user_all_order['turnover']) ? strip_tags(price_format($user_all_order['turnover'])) : 0;
        $ave_user_ordernum = $user_num > 0 ? sprintf("%0.2f", $user_all_order['order_num'] / $user_num) : 0;
        $ave_user_turnover = $user_num > 0 ? strip_tags(price_format($user_all_order['turnover'] / $user_num)) : 0;
        $order_num = isset($guest_all_order['order_num']) && $guest_all_order['order_num'] > 0 ? strip_tags(price_format($guest_all_order['turnover'] / $guest_all_order['order_num'])) : 0;
        $guest_turnover = isset($guest_all_order['turnover']) ? strip_tags(price_format($guest_all_order['turnover'])) : 0;

        $cellData = [

            /* 生成会员购买率 */
            [
                $GLOBALS['_LANG']['percent_buy_member']
            ],
            [
                $GLOBALS['_LANG']['member_count'],
                $GLOBALS['_LANG']['order_member_count'],
                $GLOBALS['_LANG']['member_order_count'],
                $GLOBALS['_LANG']['percent_buy_member']
            ],

            [
                $user_num,
                $have_order_usernum,
                $user_all_order['order_num'],
                sprintf("%0.2f", ($user_num > 0 ? $have_order_usernum / $user_num : 0) * 100)
            ],

            /* 每会员平均订单数及购物额 */
            [
                $GLOBALS['_LANG']['order_turnover_peruser']
            ],
            [
                $GLOBALS['_LANG']['member_sum'],
                $GLOBALS['_LANG']['average_member_order'],
                $GLOBALS['_LANG']['member_order_sum']
            ],
            [
                $user_turnover,
                $ave_user_ordernum,
                $ave_user_turnover
            ],

            /* 每会员平均订单数及购物额 */
            [
                $GLOBALS['_LANG']['order_turnover_percus']
            ],
            [
                $GLOBALS['_LANG']['guest_member_orderamount'],
                $GLOBALS['_LANG']['guest_member_ordercount'],
                $GLOBALS['_LANG']['guest_order_sum']
            ],
            [
                $guest_turnover,
                $guest_all_order['order_num'] ?? 0,
                $order_num
            ]
        ];

        return collect($cellData);
    }
}
