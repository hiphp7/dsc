<?php

namespace App\Exports;

use App\Repositories\Common\TimeRepository;
use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class StatsOrderExport
 * @package App\Exports
 */
class StatsOrderExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * @var Application|mixed
     */
    protected $timeRepository;

    /**
     * StatsOrderExport constructor.
     */
    public function __construct()
    {
        $this->orderCommonService = app(OrderCommonService::class);
        $this->timeRepository = app(TimeRepository::class);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $start_date = empty($_REQUEST['start_date']) ? $this->timeRepository->getLocalStrtoTime('-20 day') : intval($_REQUEST['start_date']);
        $end_date = empty($_REQUEST['end_date']) ? $this->timeRepository->getGmTime() : intval($_REQUEST['end_date']);

        $pay_arr = [];
        $ship_arr = [];

        /* 支付方式 */
        $pay_item1 = $this->orderCommonService->getPayTypeStats($start_date, $end_date);
        $pay_item2 = $this->orderCommonService->getPayTypeStats($start_date, $end_date);
        if ($pay_item1) {
            $pay_arr = $this->orderCommonService->getToArray($pay_item1, $pay_item2, 'pay_id', 'pay_arr', 'pay_name');
        }

        $pay_list = [];
        if ($pay_arr) {
            foreach ($pay_arr as $val) {
                $pay_list[] = $val['pay_name'];
            }
        }

        $pay_count_list = [];
        if ($pay_arr) {
            foreach ($pay_arr as $val) {
                $pay_count_list[] = count($val['pay_arr']);
            }
        }

        $ship_res1 = $this->orderCommonService->getShippingTypeStats($start_date, $end_date);
        $ship_res2 = $this->orderCommonService->getShippingTypeStats($start_date, $end_date);
        if ($ship_res1) {
            $ship_arr = $this->orderCommonService->getToArray($ship_res1, $ship_res2, 'shipping_id', 'ship_arr', 'ship_name');
        }

        $ship_list = [];
        if ($ship_arr) {
            foreach ($ship_arr as $val) {
                $ship_list[] = $val['ship_name'];
            }
        }

        $ship_count_list = [];
        if ($ship_arr) {
            foreach ($ship_arr as $val) {
                $ship_count_list[] = count($val['ship_arr']);
            }
        }

        $order_info = $this->orderCommonService->getOrderInfoStats($start_date, $end_date);

        $cellData = [
            [
                $GLOBALS['_LANG']['order_circs']
            ],
            [
                $GLOBALS['_LANG']['confirmed'],
                $GLOBALS['_LANG']['succeed'],
                $GLOBALS['_LANG']['unconfirmed'],
                $GLOBALS['_LANG']['invalid']
            ],
            [
                $order_info['confirmed_num'],
                $order_info['succeed_num'],
                $order_info['unconfirmed_num'],
                $order_info['invalid_num']
            ],
            [
                $GLOBALS['_LANG']['pay_method']
            ],
            $pay_list,
            $pay_count_list,
            [
                $GLOBALS['_LANG']['shipping_method']
            ],
            $ship_list,
            $ship_count_list
        ];

        return collect($cellData);
    }
}
