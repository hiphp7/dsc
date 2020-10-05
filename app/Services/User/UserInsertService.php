<?php

namespace App\Services\User;

use App\Models\Brand;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class UserInsertService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得推荐品牌信息
     *
     * @param array $arr
     * @param string $brand_id
     * @return mixed
     */
    public function insertRecommendBrands($arr = [], $brand_id = '')
    {
        $arr['num'] = isset($arr['num']) && !empty($arr['num']) ? intval($arr['num']) : 0;

        $recommend_brands = Brand::where('is_show', 1)
            ->whereHas('getBrandExtend', function ($query) {
                $query->where('is_recommend', 1);
            });

        if (!empty($brand_id)) {
            $brand_id = !is_array($brand_id) ? explode(",", $brand_id) : $brand_id;

            $recommend_brands = $recommend_brands->whereIn('brand_id', $brand_id);
        }

        $uid = session('user_id', 0);
        $recommend_brands = $recommend_brands->withCount([
            'getCollectBrand as collect_count' => function ($query) use ($uid) {
                $query->where('user_id', $uid);
            }
        ]);

        $recommend_brands = $recommend_brands->orderBy('sort_order');

        if ($arr['num'] > 0) {
            $recommend_brands = $recommend_brands->take($arr['num']);
        }

        $recommend_brands = $this->baseRepository->getToArrayGet($recommend_brands);

        if ($recommend_brands) {
            foreach ($recommend_brands as $key => $row) {
                $recommend_brands[$key]['brand_logo'] = empty($row['brand_logo']) ? str_replace(['../'], '', $this->config['no_brand']) : $row['brand_logo'];

                if ($row['site_url'] && strlen($row['site_url']) > 8) {
                    $recommend_brands[$key]['url'] = $row['site_url'];
                } else {
                    $recommend_brands[$key]['url'] = $this->dscRepository->buildUri('brandn', ['bid' => $row['brand_id']], $row['brand_name']);
                }

                $recommend_brands[$key]['is_collect'] = $row['collect_count'];

                $recommend_brands[$key]['brand_logo'] = $this->dscRepository->getImagePath(DATA_DIR . '/brandlogo/' . $row['brand_logo']);
            }
        }

        $need_cache = $GLOBALS['smarty']->caching;
        $need_compile = $GLOBALS['smarty']->force_compile;

        $GLOBALS['smarty']->caching = false;
        $GLOBALS['smarty']->force_compile = true;

        $GLOBALS['smarty']->assign('recommend_brands', $recommend_brands);
        $val = $GLOBALS['smarty']->fetch('library/index_brand_street.lbi');

        $GLOBALS['smarty']->caching = $need_cache;
        $GLOBALS['smarty']->force_compile = $need_compile;

        return $val;
    }
}