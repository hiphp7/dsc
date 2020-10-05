<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class UserStatsRankExport
 * @package App\Exports
 */
class UserStatsRankExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * UserStatsRankExport constructor.
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
        $order_list = $this->orderCommonService->userSaleStats();

        $thead = [
            $GLOBALS['_LANG']['record_id'],
            $GLOBALS['_LANG']['user_name'],
            $GLOBALS['_LANG']['user_sale_stats'][2],
            $GLOBALS['_LANG']['user_sale_stats'][3],
            $GLOBALS['_LANG']['user_sale_stats'][4],
            $GLOBALS['_LANG']['user_sale_stats'][5],
            $GLOBALS['_LANG']['user_sale_stats'][6],
            $GLOBALS['_LANG']['user_sale_stats'][7]
        ];

        $cellData = [
            $thead,
        ];

        if ($order_list && $order_list['orders']) {
            $idx = 1;
            foreach ($order_list['orders'] as $k => $row) {
                $cellData[$idx]['user_id'] = $row['user_id'];
                $cellData[$idx]['user_name'] = $row['user_name'];
                $cellData[$idx]['total_num'] = $row['total_num'] ? $row['total_num'] : 0;
                $cellData[$idx]['total_fee'] = $row['total_fee'] ? $row['total_fee'] : 0;
                $cellData[$idx]['valid_num'] = $row['valid_num'] ? $row['valid_num'] : 0;
                $cellData[$idx]['valid_fee'] = $row['valid_fee'] ? $row['valid_fee'] : 0;
                $cellData[$idx]['return_num'] = $row['return_num'] ? $row['return_num'] : 0;
                $cellData[$idx]['valireturn_feed_fee'] = $row['return_fee'] ? $row['return_fee'] : 0;

                $idx++;
            }
        }

        return collect($cellData);
    }
}
