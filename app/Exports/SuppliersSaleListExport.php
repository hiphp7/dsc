<?php

namespace App\Exports;

use App\Services\Wholesale\CommonManageService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class SuppliersSaleListExport
 * @package App\Exports
 */
class SuppliersSaleListExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $commonManageService;

    /**
     * SuppliersSaleListExport constructor.
     */
    public function __construct()
    {
        $this->commonManageService = app(CommonManageService::class);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        /* 获取访问购买的比例数据 */
        $get_sale_list = $this->commonManageService->getSaleList(false);

        $cellData = [
            [
                $GLOBALS['_LANG']['suppliers_name'],
                $GLOBALS['_LANG']['goods_sn'],
                $GLOBALS['_LANG']['goods_name'],
                $GLOBALS['_LANG']['order_sn'],
                $GLOBALS['_LANG']['amount'],
                $GLOBALS['_LANG']['sell_price'],
                $GLOBALS['_LANG']['all_price'],
                $GLOBALS['_LANG']['sell_date']
            ]

        ];

        if ($get_sale_list['sale_list_data']) {
            $idx = 1;
            foreach ($get_sale_list['sale_list_data'] as $k => $row) {
                $cellData[$idx]['shop_name'] = $row['shop_name'];
                $cellData[$idx]['goods_sn'] = $row['goods_sn'];
                $cellData[$idx]['goods_name'] = $row['goods_name'];
                $cellData[$idx]['order_sn'] = $row['order_sn'];
                $cellData[$idx]['goods_num'] = $row['goods_number'];
                $cellData[$idx]['sales_price'] = $row['sales_price'];
                $cellData[$idx]['total_fee'] = $row['total_fee'];
                $cellData[$idx]['sales_time'] = $row['sales_time'];

                $idx++;
            }
        }

        return collect($cellData);
    }
}
