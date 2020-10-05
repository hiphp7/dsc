<?php

namespace App\Exports;

use App\Services\Order\OrderCommonService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Class AdsenseExport
 * @package App\Exports
 */
class AdsenseExport implements FromCollection
{
    /**
     * @var Application|mixed
     */
    protected $orderCommonService;

    /**
     * AdsenseExport constructor.
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
        $ads_stats = $this->orderCommonService->getAdsStats();
        $goods_stats = $this->orderCommonService->getGoodsStats();

        $cellData = [
            [
                $GLOBALS['_LANG']['adsense_name'],
                $GLOBALS['_LANG']['cleck_referer'],
                $GLOBALS['_LANG']['click_count'],
                $GLOBALS['_LANG']['confirm_order'],
                $GLOBALS['_LANG']['gen_order_amount']
            ]
        ];

        $res = array_merge($goods_stats, $ads_stats);

        if ($res) {
            foreach ($res as $key => $row) {
                $key = $key + 1;
                $cellData[$key]['ad_name'] = $row['ad_name'];
                $cellData[$key]['referer'] = $row['referer'];
                $cellData[$key]['clicks'] = $row['clicks'];
                $cellData[$key]['order_confirm'] = $row['order_confirm'];
                $cellData[$key]['order_num'] = $row['order_num'];
            }
        }

        return collect($cellData);
    }
}
