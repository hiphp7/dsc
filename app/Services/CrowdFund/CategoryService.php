<?php

namespace App\Services\CrowdFund;

use App\Models\ZcCategory;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class CategoryService
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
     * 多维数组转一维数组【分类】
     *
     * @param int $parent_id
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getZcCatListChildren($parent_id = 0)
    {
        //顶级分类页分类显示
        $cat_list = cache('get_zccat_list_children' . $parent_id);
        $cat_list = !is_null($cat_list) ? $cat_list : false;

        //将数据写入缓存文件
        if ($cat_list === false) {
            $cat_list = ZcCategory::getList($parent_id)
                ->where('is_show', 1)
                ->orderBy('sort_order')
                ->orderBy('cat_id');

            $cat_list = $this->baseRepository->getToArrayGet($cat_list);

            if ($cat_list) {
                $cat_list = $this->dscRepository->getCatVal($cat_list);
                $cat_list = $this->baseRepository->getFlatten($cat_list);

                $cat_list = !empty($parent_id) ? collect($cat_list)->prepend($parent_id)->all() : $cat_list;
            } else {
                $cat_list = [$parent_id];
            }

            $cat_list = collect($cat_list)->values()->all();

            cache()->forever('get_zccat_list_children' . $parent_id, $cat_list);
        }

        return $cat_list;
    }
}
