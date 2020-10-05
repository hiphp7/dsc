<?php

namespace App\Services\Other;

use App\Models\Adsense;
use App\Models\OrderInfo;
use App\Repositories\Common\BaseRepository;

class AdsenseManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }


    /**
     * 获取广告数据
     *
     * @return array
     */
    public function getAdsStats()
    {
        $res = Adsense::whereRaw(1);
        $res = $res->with([
            'getAd'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        $ads_stats = [];
        if ($res) {
            foreach ($res as $rows) {
                $ad = $rows['get_ad'] ?? [];

                $rows['ad_id'] = $ad['ad_id'] ?? 0;
                $rows['ad_name'] = $ad['ad_name'] ?? '';

                /* 获取当前广告所产生的订单总数 */
                $rows['referer'] = addslashes($rows['referer']);
                $rows['order_num'] = OrderInfo::where('from_ad', $rows['ad_id'])
                    ->where('referer', $rows['referer'])->count();

                /* 当前广告所产生的已完成的有效订单 */
                $rows['order_confirm'] = OrderInfo::where('from_ad', $rows['ad_id'])
                    ->where('referer', $rows['referer'])
                    ->whereIn('order_status', [OS_CONFIRMED, OS_RETURNED_PART, OS_SPLITED])
                    ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                    ->whereIn('pay_status', [PS_PAYED, PS_PAYING])
                    ->count();

                $ads_stats[] = $rows;
            }
        }

        return $ads_stats;
    }

    /**
     * 站外JS投放商品的统计数据
     *
     * @return array
     */
    public function getGoodsStats()
    {
        $goods_res = Adsense::where('from_ad', '-1')
            ->orderBy('referer', 'desc');
        $goods_res = $this->baseRepository->getToArrayGet($goods_res);

        $goods_stats = [];
        if ($goods_res) {
            foreach ($goods_res as $rows2) {
                /* 获取当前广告所产生的订单总数 */
                $rows2['referer'] = addslashes($rows2['referer']);

                $rows2['order_num'] = OrderInfo::where('referer', $rows2['referer'])->count();

                /* 当前广告所产生的已完成的有效订单 */
                $rows2['order_confirm'] = OrderInfo::where('referer', $rows2['referer'])
                    ->whereIn('order_status', [OS_CONFIRMED, OS_RETURNED_PART, OS_SPLITED])
                    ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                    ->whereIn('pay_status', [PS_PAYED, PS_PAYING])
                    ->count();

                $rows2['ad_name'] = $GLOBALS['_LANG']['adsense_js_goods'];
                $goods_stats[] = $rows2;
            }
        }

        return $goods_stats;
    }
}
