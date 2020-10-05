<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class UserOrderExport
 * @package App\Exports
 */
class UserOrderExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * UserOrderExport constructor.
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
        $user_orderinfo = $this->orderCommonService->getUserOrderInfo(false);

        $cellData = [
            [
                $GLOBALS['_LANG']['order_by'],
                $GLOBALS['_LANG']['user_name'],
                $GLOBALS['_LANG']['order_num'],
                $GLOBALS['_LANG']['turnover']
            ],
        ];

        if (isset($user_orderinfo['user_orderinfo']) && $user_orderinfo['user_orderinfo']) {
            $idx = 1;
            foreach ($user_orderinfo['user_orderinfo'] as $k => $row) {
                $cellData[$idx]['order_by'] = $idx;
                $cellData[$idx]['user_name'] = $row['user_name'];
                $cellData[$idx]['nnumber'] = $row['order_num'];
                $cellData[$idx]['amount'] = $row['turnover'];

                $idx++;
            }
        }

        return collect($cellData);
    }
}
