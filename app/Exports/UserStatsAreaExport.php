<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class UserStatsAreaExport
 * @package App\Exports
 */
class UserStatsAreaExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * UserStatsAreaExport constructor.
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
        $order_list = $this->orderCommonService->userAreaStats();

        $thead = [
            $GLOBALS['_LANG']['province_alt'],
            $GLOBALS['_LANG']['city'],
            $GLOBALS['_LANG']['area_alt'],
            $GLOBALS['_LANG']['user_sale_stats'][0],
            $GLOBALS['_LANG']['amount'],
            $GLOBALS['_LANG']['user_sale_stats'][1]
        ];

        $cellData = [
            $thead,
        ];

        if ($order_list && $order_list['orders']) {
            $idx = 1;
            foreach ($order_list['orders'] as $k => $row) {
                $cellData[$idx]['province_name'] = $row['province_name'];
                $cellData[$idx]['city_name'] = $row['city_name'];
                $cellData[$idx]['district_name'] = $row['district_name'];
                $cellData[$idx]['user_num'] = $row['user_num'];
                $cellData[$idx]['total_fee'] = $row['total_fee'];
                $cellData[$idx]['total_num'] = $row['total_num'];

                $idx++;
            }
        }

        return collect($cellData);
    }
}
