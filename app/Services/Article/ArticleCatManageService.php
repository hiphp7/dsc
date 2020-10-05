<?php

namespace App\Services\Article;

use App\Models\ArticleCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class ArticleCatManageService
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
     * 获取文章分类
     *
     * @return array
     */
    public function getArticleCatList()
    {
        $cat_back = 0;
        $filter['cat_id'] = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

        $row = ArticleCat::whereRaw(1);

        if ($filter['cat_id'] > 0) {
            $row = $row->where('parent_id', $filter['cat_id']);
            $cat_back = 1;
        } else {
            $row = $row->where('parent_id', 0);
        }

        $res = $record_count = $row;

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();
        $filter = page_and_size($filter);

        /* 查询 */
        $res = $res->groupBy('cat_id')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('parent_id', 'ASC');

        $start = ($filter['page'] - 1) * $filter['page_size'];
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $cat) {
                $res[$key]['type_name'] = $GLOBALS['_LANG']['type_name'][$cat['cat_type']];
                $res[$key]['url'] = $this->dscRepository->buildUri('article', ['acid' => $cat['cat_id']], $cat['cat_name']);
                $res[$key]['add_child'] = "articlecat.php?act=add&cat_id=" . $cat['cat_id'] . "";
                $res[$key]['child_url'] = "articlecat.php?act=list&cat_id=" . $cat['cat_id'];
                $res[$key]['cat_type'] = $cat['cat_type'];
            }
        }

        $arr = ['result' => $res, 'filter' => $filter, 'cat_back' => $cat_back, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }
}
