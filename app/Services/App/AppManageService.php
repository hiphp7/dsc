<?php

namespace App\Services\App;

use App\Models\AppAd;
use App\Models\AppAdPosition;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * APP 后台管理
 * Class AppManageService
 * @package App\Services\App
 */
class AppManageService
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
     * 添加或更新广告位
     * @param null $data
     * @return bool
     */
    public function updateAdPostion($data = null)
    {
        if (is_null($data)) {
            return false;
        }

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        $data = $this->baseRepository->getArrayfilterTable($data, 'app_ad_position');

        $position_id = $data['position_id'] ?? 0;
        if (!empty($position_id)) {
            $res = AppAdPosition::where('position_id', $position_id)->update($data);
        } else {
            $res = AppAdPosition::create($data);
        }

        return $res;
    }

    /**
     * 广告位列表
     * @param int $position_id
     * @param string $keywords
     * @param array $offset
     * @return array
     */
    public function adPositionList($position_id = 0, $keywords = '', $offset = [])
    {
        $model = AppAdPosition::whereRaw(1);

        if (!empty($position_id)) {
            $model = $model->where('position_id', $position_id);
        }

        if (!empty($keywords)) {
            $model = $model->where('position_name', 'like', '%' . $keywords . '%');
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $model = $model->orderBy('position_id', 'DESC')
            ->get();

        $list = $model ? $model->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 广告位信息
     * @param int $position_id
     * @return array
     */
    public function adPositionInfo($position_id = 0)
    {
        $res = AppAdPosition::where('position_id', $position_id)->first();

        return $res ? $res->toArray() : [];
    }

    /**
     * 检查广告位下是否有广告
     * @param int $position_id
     * @return bool
     */
    public function checkAd($position_id = 0)
    {
        $model = AppAdPosition::where('position_id', $position_id);

        $model = $model->whereHas('appAds');

        $count = $model->count();

        return $count > 0 ? true : false;
    }

    /**
     * 删除广告位
     * @param int $position_id
     * @return bool
     */
    public function deleteAdPosition($position_id = 0)
    {
        if (empty($position_id)) {
            return false;
        }

        $res = AppAdPosition::where('position_id', $position_id)->delete();

        return $res;
    }

    /**
     * 添加或更新广告
     * @param null $data
     * @return bool
     */
    public function updateAd($data = null)
    {
        if (is_null($data)) {
            return false;
        }

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        $data = $this->baseRepository->getArrayfilterTable($data, 'app_ad');

        $ad_id = $data['ad_id'] ?? 0;
        if (!empty($ad_id)) {
            $res = AppAd::where('ad_id', $ad_id)->update($data);
        } else {
            $res = AppAd::create($data);
        }

        return $res;
    }

    /**
     * app广告列表
     * @param int $position_id
     * @param string $keywords
     * @param array $offset
     * @return array
     */
    public function adList($position_id = 0, $keywords = '', $offset = [])
    {
        $model = AppAd::whereRaw(1);

        if (!empty($position_id)) {
            $model = $model->where('position_id', $position_id);
        }

        if (!empty($keywords)) {
            $model = $model->where('ad_name', 'like', '%' . $keywords . '%');
        }

        $model = $model->with([
            'appAdPosition'
        ]);

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
                    // 远程图片地址
                    if (strtolower(substr($value['ad_code'], 0, 4)) == 'http') {
                        $list[$k]['url_src'] = $value['ad_code'];
                        $value['ad_code'] = '';
                    }

                    $list[$k]['ad_code'] = empty($value['ad_code']) ? '' : $this->dscRepository->getImagePath($value['ad_code']);
                }

                if (isset($value['app_ad_position']) && !empty(isset($value['app_ad_position']))) {
                    $list[$k]['position_name'] = $value['app_ad_position']['position_name'];
                }
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 广告信息
     * @param int $ad_id
     * @return array
     */
    public function adInfo($ad_id = 0)
    {
        $model = AppAd::where('ad_id', $ad_id);

        $model = $model->with([
            'appAdPosition'
        ]);

        $model = $model->first();

        $res = $model ? $model->toArray() : [];

        if (!empty($res)) {
            if ($res['media_type'] == 0) {
                // 远程图片地址
                if (strtolower(substr($res['ad_code'], 0, 4)) == 'http') {
                    $res['url_src'] = $res['ad_code'];
                    $res['ad_code'] = '';
                }

                $res['ad_code'] = empty($res['ad_code']) ? '' : $this->dscRepository->getImagePath($res['ad_code']);
            }
        }

        return $res;
    }

    /**
     * 修改广告状态
     * @param int $ad_id
     * @param int $status
     * @return bool
     */
    public function updateAdStatus($ad_id = 0, $status = 0)
    {
        if (empty($ad_id)) {
            return false;
        }

        $model = AppAd::where('ad_id', $ad_id)->first();

        $model->enabled = $status;

        $model->save();

        return true;
    }

    /**
     * 删除广告
     * @param int $ad_id
     * @return bool
     */
    public function deleteAd($ad_id = 0)
    {
        if (empty($ad_id)) {
            return false;
        }

        $res = AppAd::where('ad_id', $ad_id)->delete();

        return $res;
    }
}
