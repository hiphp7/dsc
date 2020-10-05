<?php

namespace App\Services\Wholesale;

use App\Models\WholesaleCat;
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
     * 批发分类列表
     *
     * @param int $parent_id
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getCategoryList($parent_id = 0)
    {
        $cat_list = cache('wholesale_cat_list_' . $parent_id);
        $cat_list = !is_null($cat_list) ? $cat_list : false;

        if ($cat_list === false) {
            $cat_list = WholesaleCat::getList($parent_id)
                ->where('is_show', 1)
                ->orderBy('sort_order')
                ->orderBy('cat_id');

            $cat_list = $this->baseRepository->getToArrayGet($cat_list);

            if ($cat_list) {
                foreach ($cat_list as $key => $row) {
                    $cat_list[$key]['id'] = $row['cat_id'];
                    $cat_list[$key]['cat_alias_name'] = $row['cat_alias_name'];
                    $cat_list[$key]['url'] = $this->dscRepository->buildUri('wholesale_cat', ['cid' => $row['cat_id']], $row['cat_name']);
                    $cat_list[$key]['name'] = $row['cat_name'];
                }
            }

            cache()->forever('wholesale_cat_list_' . $parent_id, $cat_list);
        }

        return $cat_list;
    }

    /**
     * 多维数组转一维数组【分类】
     *
     * @param int $parent_id
     * @return array|\Illuminate\Support\Collection|mixed|string
     * @throws \Exception
     */
    public function getWholesaleCatListChildren($parent_id = 0)
    {

        //顶级分类页分类显示
        $cat_list = read_static_cache('get_wholesale_cat_list_children' . $parent_id);

        //将数据写入缓存文件
        if ($cat_list === false) {
            $cat_list = WholesaleCat::getList($parent_id)
                ->where('is_show', 1)
                ->orderBy('sort_order')
                ->orderBy('cat_id')
                ->get();

            $cat_list = $cat_list ? $cat_list->toArray() : [];

            if ($cat_list) {
                $cat_list = $this->dscRepository->getCatVal($cat_list);

                $cat_list = collect($cat_list)->flatten();
                $cat_list = $cat_list->all();

                $cat_list = !empty($parent_id) ? collect($cat_list)->prepend($parent_id)->all() : $cat_list;
            } else {
                $cat_list = [$parent_id];
            }

            write_static_cache('get_wholesale_cat_list_children' . $parent_id, $cat_list);
        }

        return $cat_list;
    }
}
