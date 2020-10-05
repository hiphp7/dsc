<?php

namespace App\Services\Ads;

use App\Models\Ad;
use App\Models\AdPosition;
use App\Models\TouchAd;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 广告
 *
 * Class AdsService
 * @package App\Services\Ads
 */
class AdsService
{
    protected $timeRepository;
    protected $baseRepository;
    protected $config;
    protected $dscRepository;
    protected $adsGoodsService;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        AdsGoodsService $adsGoodsService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->adsGoodsService = $adsGoodsService;
    }

    /**
     * 获取广告位置信息
     *
     * @param array $where
     * @return array
     */
    public function getAdPositionInfo($where = [])
    {
        if (empty($where)) {
            return [];
        }

        $row = AdPosition::whereRaw(1);

        $row = $row->whereHas('getAd', function ($query) use ($where) {
            if (isset($where['ad_name'])) {
                $query = $query->where('ad_name', $where['ad_name']);
            }

            if (isset($where['media_type'])) {
                $query = $query->where(function ($query) use ($where) {
                    if (is_array($where['media_type'])) {
                        foreach ($where['media_type'] as $v) {
                            $query = $query->where('media_type', $v);
                        }
                    } else {
                        $query->where('media_type', $where['media_type']);
                    }
                });
            }

            if (isset($where['time']) && $where['time'] == 1) {
                $query = $query->whereRaw("UNIX_TIMESTAMP()>ad.start_time and UNIX_TIMESTAMP()<ad.end_time");
            }

            if (isset($where['enabled'])) {
                $query->where('enabled', $where['enabled']);
            }
        });

        $row = $row->with(['getAd']);

        $row = $this->baseRepository->getToArrayFirst($row);

        $row = isset($row['get_ad']) && $row['get_ad'] ? array_merge($row, $row['get_ad']) : $row;

        return $row;
    }

    /**
     * 调用指定的广告位的广告 原手机端广告位
     *
     * @param int $position_id 广告位id
     * @param int $num 数量
     * @param string $order 排序
     * @return array
     */
    public function getTouchAds($position_id = 0, $num = 5, $order = '')
    {
        $time = $this->timeRepository->getGmTime();

        $res = TouchAd::where('position_id', $position_id)
            ->where('enabled', 1)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time);

        $res = $res->with('getTouchAdPosition');

        if ($order == 'rand') {
            $res = $res->orderByRaw("RAND()");
        } else {
            $res = $res->orderBy('ad_id', 'DESC');
        }

        $res = $res->limit($num);

        $res = $this->baseRepository->getToArrayGet($res);

        $ads = [];

        if ($res) {
            foreach ($res as $key => $row) {
                $row = collect($row)->merge($row['get_touch_ad_position'])->except('get_touch_ad_position')->all();

                if ($row['position_id'] != $position_id) {
                    continue;
                }
                switch ($row['media_type']) {
                    case 0:
                        // 图片广告
                        if ((strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false)) {
                            $row['ad_code'] = $this->dscRepository->getImagePath('data/afficheimg/' . $row['ad_code']);
                        }

                        $row['link'] = 'affiche.php?ad_id=' . $row['ad_id'] . '&uri=' . urlencode($row['ad_link']);
                        $ads[] = $row;
                        break;
                    case 1:
                        // Flash
                        if ((strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false)) {
                            $row['ad_code'] = $this->dscRepository->getImagePath('data/afficheimg/' . $row['ad_code']);
                        }

                        $row['link'] = 'affiche.php?ad_id=' . $row['ad_id'] . '&uri=' . urlencode($row['ad_link']);
                        $ads[] = $row;
                        break;
                    case 2:
                        // CODE
                        $ads[] = $row['ad_code'];
                        break;
                    case 3:
                        // TEXT
                        $row['ad_code'] = htmlspecialchars($row['ad_code']);

                        $row['link'] = 'affiche.php?ad_id=' . $row['ad_id'] . '&uri=' . urlencode($row['ad_link']);
                        $ads[] = $row;
                        break;
                }
            }

            $ads = collect($ads)->values()->all();
        }

        return $ads;
    }

    /**
     * 指定广告信息
     *
     * @param string $cat_n_child
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     * @throws \Exception
     */
    public function getAdPostiChild($cat_n_child = '', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $time = $this->timeRepository->getGmTime();
        if ($cat_n_child == 'sy') {
            $cat_n_child = '';
        }

        $res = [];
        if ($cat_n_child) {
            $theme = $this->config['template'];
            $cat_n_child = str_replace(["'"], '', $cat_n_child);

            $cat_n_child = $this->baseRepository->getExplode($cat_n_child);

            $res = Ad::whereIn('ad_name', $cat_n_child)
                ->where('media_type', 0)
                ->where('start_time', '<', $time)
                ->where('end_time', '>', $time)
                ->where('enabled', 1)
                ->where('media_type', 0);

            $res = $res->whereHas('getAdPosition', function ($query) use ($theme) {
                $query->where('theme', $theme);
            });

            $res = $res->with([
                'getAdPosition' => function ($query) use ($theme) {
                    $query->where('theme', $theme);
                }
            ]);

            $res = $res->orderBy('ad_name');
            $res = $res->orderBy('ad_id');
            $res = $this->baseRepository->getToArrayGet($res);
        }

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $key = $key + 1;

                $arr[$key]['position_id'] = $row['get_ad_position']['position_id'];
                $arr[$key]['position_name'] = $row['get_ad_position']['position_name'];
                $arr[$key]['ad_name'] = $row['ad_name'];

                //出来广告图片链接
                if ($row['ad_code']) {
                    if (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) {
                        $arr[$key]['ad_code'] = $this->dscRepository->getImagePath(DATA_DIR . '/afficheimg/' . $row['ad_code']);
                    } else {
                        $src = $row['ad_code'];
                        $src = str_replace('../', '', $src);
                        $arr[$key]['ad_code'] = $this->dscRepository->getImagePath($src);
                    }
                }

                if ($row['ad_bg_code']) {
                    if (strpos($row['ad_bg_code'], 'http://') === false && strpos($row['ad_bg_code'], 'https://') === false) {
                        $arr[$key]['ad_bg_code'] = $this->dscRepository->getImagePath(DATA_DIR . '/afficheimg/' . $row['ad_bg_code']);
                    } else {
                        $src = $row['ad_bg_code'];
                        $src = str_replace('../', '', $src);
                        $arr[$key]['ad_bg_code'] = $this->dscRepository->getImagePath($src);
                    }
                }

                $arr[$key]['ad_link'] = $row["ad_link"];
                $arr[$key]['link_man'] = $row["link_man"];
                $arr[$key]['ad_width'] = $row['get_ad_position']['ad_width'];
                $arr[$key]['ad_height'] = $row['get_ad_position']['ad_height'];
                $arr[$key]['link_color'] = $row['link_color'];
                $arr[$key]['b_title'] = $row['b_title'];
                $arr[$key]['s_title'] = $row['s_title'];
                $arr[$key]['start_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['start_time']);
                $arr[$key]['end_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['end_time']);
                $arr[$key]['ad_type'] = $row['ad_type'];
                $arr[$key]['goods_name'] = $row['goods_name'];

                if ($row['goods_name'] && $row['ad_type']) {
                    $arr[$key]['goods_info'] = $this->adsGoodsService->getGoodsAdPromote($row['goods_name'], $warehouse_id, $area_id, $area_city);

                    if ((strpos($row['ad_link'], 'http://') !== false || strpos($row['ad_link'], 'https://') !== false)) {
                        $row['ad_link'] = '';
                    }

                    if (empty($row['ad_link'])) {
                        $arr[$key]['ad_link'] = $arr[$key]['goods_info']['url'];
                    }
                } else {
                    if ($row["ad_link"]) {
                        if ((strpos($row['ad_link'], 'http://') === false && strpos($row['ad_link'], 'https://') === false)) {
                            $arr[$key]["ad_link"] = 'affiche.php?ad_id=' . $row['ad_id'] . '&amp;uri=' . urlencode($row["ad_link"]);
                        } else {
                            $arr[$key]["ad_link"] = $row["ad_link"];
                        }
                    }
                }
            }
        }

        return $arr;
    }
}
