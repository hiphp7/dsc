<?php

namespace App\Services\EntryCriteria;

use App\Models\EntryCriteria;
use App\Repositories\Common\BaseRepository;

class EntryCriteriaManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function getCriteriaCatLevel($parent_id = 0)
    {
        $res = EntryCriteria::where('parent_id', $parent_id);
        $res = $res->withCount('getEntryCriteriaCount');
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            $level = 0;
            foreach ($res as $k => $row) {
                $res[$k]['has_children'] = isset($row['get_entry_criteria_count_count']) ? $row['get_entry_criteria_count_count'] : 0;
                $res[$k]['level'] = $level;
            }
        }

        return $res;
    }
}
