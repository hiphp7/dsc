<?php

namespace App\Services\CrowdFund;

use App\Models\ZcCategory;
use App\Repositories\Common\BaseRepository;

class CategoryManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * ajax分类列表
     *
     * @param int $parent_id
     * @param int $level
     * @return mixed
     */
    public function getCatLevel($parent_id = 0, $level = 0)
    {
        $res = ZcCategory::where('parent_id', $parent_id)
            ->orderByRaw('sort_order, cat_id asc');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $row) {
                $res[$k]['level'] = $level;
            }
        }

        return $res;
    }
}
