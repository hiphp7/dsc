<?php

namespace App\Services\Goods;


use App\Models\GoodsActivity;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class GoodsActivityService
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 查询商品活动
     * @param int $act_id
     * @param int $act_type
     * @return array
     */
    public function getGoodsActivity($act_id = 0, $act_type = GAT_PACKAGE)
    {
        $activity = GoodsActivity::where('act_id', $act_id)
            ->where('act_type', $act_type);
        $activity = $this->baseRepository->getToArrayFirst($activity);

        if ($activity) {
            $activity['activity_thumb'] = !empty($activity['activity_thumb']) ? $this->dscRepository->getImagePath($activity['activity_thumb']) : $this->dscRepository->dscUrl('themes/ecmoban_dsc2017/images/17184624079016pa.jpg');
        }

        return $activity;
    }
}
