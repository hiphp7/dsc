<?php

namespace App\Services\Index;

use App\Models\OrderInfo;
use App\Models\Sessions;
use App\Models\SessionsData;
use App\Models\Stats;
use App\Repositories\Common\BaseRepository;


class IndexManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 计算订单数量
     *
     * @param int $ru_id
     * @return mixed
     */
    public function getOrderCount($ru_id = 0)
    {
        $res = OrderInfo::whereRaw(1);
        if ($ru_id > 0) {
            //主订单下有子订单时，则主订单不显示
            $res = $res->where('ru_id', $ru_id)->where('main_count', 0);
        }
        $count = $res->count();

        return $count;
    }

    /**
     * @param int $type
     */
    public function clearSessions($type = 0)
    {
        if ($type == 0) {
            Sessions::truncate();
            SessionsData::truncate();
            Stats::truncate();
        } elseif ($type == 1) {
            Sessions::truncate();
            SessionsData::truncate();
        } else {
            Stats::truncate();
        }

        return;
    }
}
