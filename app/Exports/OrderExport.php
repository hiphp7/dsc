<?php

namespace App\Exports;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class OrderExport
 * @package App\Exports
 */
class OrderExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * @var Application|mixed
     */
    protected $baseRepository;

    /**
     * @var Application|mixed
     */
    protected $timeRepository;

    /**
     * @var Application|mixed
     */
    protected $dscRepository;

    /**
     * OrderExport constructor.
     */
    public function __construct()
    {
        $this->orderCommonService = app(OrderCommonService::class);
        $this->baseRepository = app(BaseRepository::class);
        $this->timeRepository = app(TimeRepository::class);
        $this->dscRepository = app(DscRepository::class);
    }

    /**
     * @return Collection
     */
    public function collection()
    {

        $this->dscRepository->helpersLang(['order'], 'admin');

        $adminru = get_admin_ru_id();

        $start_date = empty($_REQUEST['start_date']) ? $this->timeRepository->getLocalStrtoTime('-20 day') : intval($_REQUEST['start_date']);
        $end_date = empty($_REQUEST['end_date']) ? $this->timeRepository->getGmTime() : intval($_REQUEST['end_date']);

        /**
         * 订单统计信息
         */
        $order_info = $this->orderCommonService->getStatsOrderInfo($start_date, $end_date, $adminru);

        /**
         * 支付方式
         */
        $pay_item = $this->orderCommonService->getPayType($start_date, $end_date, $adminru);

        $pay_name = [];
        if ($pay_item) {
            $pay_list = $this->orderCommonService->getToArray($pay_item, $pay_item, 'pay_id', 'pay_arr', 'pay_name');
            $pay_name = $this->baseRepository->getKeyPluck($pay_list, 'pay_name');
            $pay_count = count($pay_item);

            if ($pay_list) {
                $pay_name = $this->baseRepository->getArrayMerge($pay_name, [$pay_count]);
            }
        }

        /**
         * 配送方式
         */
        $ship_res = $this->orderCommonService->getShippingType($start_date, $end_date, $adminru);

        $ship_name = [];
        if ($ship_res) {
            $ship_list = $this->orderCommonService->getToArray($ship_res, $ship_res, 'shipping_id', 'ship_arr', 'ship_name');
            $ship_name = $this->baseRepository->getKeyPluck($ship_list, 'ship_name');
            $shiping_count = count($ship_res);

            if ($ship_list) {
                $ship_name = $this->baseRepository->getArrayMerge($ship_name, [$shiping_count]);
            }
        }

        $lang = [
            $GLOBALS['_LANG']['confirmed'],
            $GLOBALS['_LANG']['succeed'],
            $GLOBALS['_LANG']['unconfirmed'],
            $GLOBALS['_LANG']['invalid']
        ];

        $order_info['confirmed_num'] = isset($order_info['confirmed_num']) && $order_info['confirmed_num'] ? $order_info['confirmed_num'] : '(空)';
        $order_info['succeed_num'] = isset($order_info['succeed_num']) && $order_info['succeed_num'] ? $order_info['succeed_num'] : '(空)';
        $order_info['unconfirmed_num'] = isset($order_info['unconfirmed_num']) && $order_info['unconfirmed_num'] ? $order_info['unconfirmed_num'] : '(空)';
        $order_info['invalid_num'] = isset($order_info['invalid_num']) && $order_info['invalid_num'] ? $order_info['invalid_num'] : '(空)';

        $pay_name = $pay_name ? $pay_name : ['(空)', '(空)'];
        $ship_name = $ship_name ? $ship_name : ['(空)', '(空)'];

        $order = [
            $order_info['confirmed_num'],
            $order_info['succeed_num'],
            $order_info['unconfirmed_num'],
            $order_info['invalid_num']
        ];

        $cellData = [
            [$GLOBALS['_LANG']['order_circs']],
            $lang,
            $order,
            [$GLOBALS['_LANG']['pay_method']],
            $pay_name,
            [$GLOBALS['_LANG']['shipping_method']],
            $ship_name
        ];

        return collect($cellData);
    }
}
