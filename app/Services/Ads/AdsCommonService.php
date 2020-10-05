<?php

namespace App\Services\Ads;

use App\Models\TouchAd;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class AdsCommonService
{
    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 首页弹出广告位
     * @return mixed
     */
    public function getPopupAds()
    {
        $time = $this->timeRepository->getGmTime();
        $ads = TouchAd::where('ad_name', '首页红包广告')->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->where('enabled', 1);
        $ads = $this->baseRepository->getToArrayFirst($ads);

        $popup_enabled = '';
        $ad_link = '';
        $open = 0;
        if ($ads) {
            $popup_enabled = isset($ads['ad_code']) ? $this->dscRepository->getImagePath('data/afficheimg/' . $ads['ad_code']) : '';
            $ad_link = $ads['ad_link'] ?? '';
            $open = 1;
        }

        $result['ad_link'] = $ad_link;
        $result['popup_ads'] = $popup_enabled;
        $result['open'] = $open;

        return $result;
    }
}