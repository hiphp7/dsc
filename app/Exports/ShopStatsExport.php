<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class ShopStatsExport
 * @package App\Exports
 */
class ShopStatsExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * ShopStatsExport constructor.
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
        $order_list = $this->orderCommonService->shopSaleStats();

        $thead = [
            $GLOBALS['_LANG']['record_id'],
            $GLOBALS['_LANG']['steps_shop_name'],
            $GLOBALS['_LANG']['sale_stats'][0],
            $GLOBALS['_LANG']['sale_stats'][1],
            $GLOBALS['_LANG']['sale_stats'][2],
            $GLOBALS['_LANG']['sale_stats'][3],
            $GLOBALS['_LANG']['sale_stats'][4],
            $GLOBALS['_LANG']['sale_stats'][5],
            $GLOBALS['_LANG']['sale_stats'][6]
        ];

        $cellData = [
            $thead,
        ];

        if ($order_list && $order_list['orders']) {
            $idx = 1;
            foreach ($order_list['orders'] as $k => $row) {
                $k = $k + 1;

                $cellData[$idx]['record_id'] = $k;
                $cellData[$idx]['user_name'] = $row['user_name'];
                $cellData[$idx]['total_user_num'] = $row['total_user_num'];
                $cellData[$idx]['total_order_num'] = $row['total_order_num'];
                $cellData[$idx]['total_fee'] = $row['total_fee'];
                $cellData[$idx]['total_valid_num'] = $row['total_valid_num'];
                $cellData[$idx]['valid_fee'] = $row['valid_fee'];
                $cellData[$idx]['total_return_num'] = $row['total_return_num'];
                $cellData[$idx]['return_amount'] = $row['return_amount'];

                $idx++;
            }
        }

        return collect($cellData);
    }
}
