<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class SaleOrderExport
 * @package App\Exports
 */
class SaleOrderExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * SaleOrderExport constructor.
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
        $adminru = get_admin_ru_id();

        $goods_order_data = $this->orderCommonService->getSalesOrder($adminru['ru_id'], false);
        $goods_order_data = $goods_order_data['sales_order_data'];

        $cellData = [
            [$GLOBALS['_LANG']['sell_stats']],
            [
                $GLOBALS['_LANG']['order_by'],
                $GLOBALS['_LANG']['goods_name'],
                $GLOBALS['_LANG']['goods_steps_name'],
                $GLOBALS['_LANG']['goods_sn'],
                $GLOBALS['_LANG']['sell_amount'],
                $GLOBALS['_LANG']['sell_sum'],
                $GLOBALS['_LANG']['percent_count']
            ],
        ];

        if ($goods_order_data) {
            $idx = 2;
            foreach ($goods_order_data as $k => $row) {
                $order_by = $k + 1;

                $cellData[$idx]['order_by'] = $order_by;
                $cellData[$idx]['goods_name'] = $row['goods_name'];
                $cellData[$idx]['ru_name'] = $row['ru_name'];
                $cellData[$idx]['goods_sn'] = $row['goods_sn'];
                $cellData[$idx]['goods_num'] = $row['goods_num'];
                $cellData[$idx]['turnover'] = $row['turnover'];
                $cellData[$idx]['wvera_price'] = $row['wvera_price'];

                $idx++;
            }
        }

        return collect($cellData);
    }
}
