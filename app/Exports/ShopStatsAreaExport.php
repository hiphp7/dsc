<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class ShopStatsAreaExport
 * @package App\Exports
 */
class ShopStatsAreaExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $commonService;

    /**
     * ShopStatsAreaExport constructor.
     */
    public function __construct()
    {
        $this->commonService = app(OrderCommonService::class);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $order_list = $this->orderCommonService->shopAreaStats();

        $thead = [
            $GLOBALS['_LANG']['province_alt'],
            $GLOBALS['_LANG']['city'],
            $GLOBALS['_LANG']['area_alt'],
            $GLOBALS['_LANG']['shop_number']
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
                $cellData[$idx]['store_num'] = $row['store_num'];

                $idx++;
            }
        }

        return collect($cellData);
    }
}
