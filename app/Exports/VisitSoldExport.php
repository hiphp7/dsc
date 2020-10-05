<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class VisitSoldExport
 * @package App\Exports
 */
class VisitSoldExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * VisitSoldExport constructor.
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
        $cat_id = (!empty($_REQUEST['cat_id'])) ? intval($_REQUEST['cat_id']) : 0;
        $brand_id = (!empty($_REQUEST['brand_id'])) ? intval($_REQUEST['brand_id']) : 0;
        $show_num = (!empty($_REQUEST['show_num'])) ? intval($_REQUEST['show_num']) : 15;

        /* 获取访问购买的比例数据 */
        $click_sold_info = $this->orderCommonService->clickSoldInfo($adminru['ru_id'], $cat_id, $brand_id, $show_num);

        $cellData = [
            [
                $GLOBALS['_LANG']['order_by'],
                $GLOBALS['_LANG']['goods_name'],
                $GLOBALS['_LANG']['ru_name'],
                $GLOBALS['_LANG']['click_count'],
                $GLOBALS['_LANG']['sold_times'],
                $GLOBALS['_LANG']['scale']
            ]
        ];

        if ($click_sold_info) {
            $idx = 1;
            foreach ($click_sold_info as $k => $row) {
                $cellData[$idx]['order_by'] = $k;
                $cellData[$idx]['goods_name'] = $row['goods_name'];
                $cellData[$idx]['ru_name'] = $row['ru_name'];
                $cellData[$idx]['click_count'] = $row['click_count'];
                $cellData[$idx]['sold_times'] = $row['sold_times'];
                $cellData[$idx]['scale'] = $row['scale'];

                $idx++;
            }
        }

        return collect($cellData);
    }
}
