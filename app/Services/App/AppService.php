<?php

namespace App\Services\App;

use App\Models\AppAd;
use App\Models\AppAdPosition;
use App\Repositories\Common\DscRepository;

/**
 * Class AppService
 * @package App\Services\App
 */
class AppService
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    /**
     * 通过 type 获取广告位信息
     * @param string $position_type
     * @return int
     */
    public function adPositionInfoByType($position_type = '')
    {
        $position_id = AppAdPosition::where('position_type', $position_type)->value('position_id');

        return $position_id ?? 0;
    }

    /**
     * app广告列表
     * @param int $position_id
     * @param array $offset
     * @return array
     */
    public function adList($position_id = 0, $offset = [])
    {
        $model = AppAd::where('position_id', $position_id)
            ->where('enabled', 1);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $model = $model->orderBy('sort_order', 'ASC')
            ->orderBy('ad_id', 'DESC')
            ->get();

        $list = $model ? $model->toArray() : [];

        if (!empty($list)) {
            foreach ($list as $k => $value) {
                if ($value['media_type'] == 0) {
                    $list[$k]['ad_code'] = empty($value['ad_code']) ? '' : $this->dscRepository->getImagePath($value['ad_code']);
                }
            }

            $list = collect($list)->values()->all();
        }

        return ['list' => $list, 'total' => $total];
    }
}
