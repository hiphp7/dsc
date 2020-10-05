<?php

namespace App\Services\Other;

use App\Models\CollectGoods;
use App\Models\Goods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class AttentionListManageService
{
    protected $baseRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
    }

    public function getAttenTion()
    {
        $filter['goods_name'] = isset($_REQUEST['goods_name']) && !empty($_REQUEST['goods_name']) ? trim($_REQUEST['goods_name']) : '';
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'last_update' : trim($_REQUEST['sort_by']);

        $row = Goods::where('is_delete', 0);

        if (!empty($filter['goods_name'])) {
            $row = $row->where('goods_name', 'like', '%' . $filter['goods_name'] . '%');
        }

        $row = $row->whereHas('getCollectGoods', function ($query) {
            $query->where('is_attention', 1);
        });

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k]['last_update'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['last_update']);
            }
        }

        $arr = ['goodsdb' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }

    /**
     * 会员关注商品的数量
     *
     * @param int $goods_id
     * @return array
     */
    public function getUserCollectGoodsCount($goods_id = 0, $date = 0)
    {
        $count = CollectGoods::where('is_attention', 1);

        if ($goods_id) {
            $count = $count->where('goods_id', $goods_id);
        }

        $count = $count->whereHas('getUsers');

        $where = [
            'date' => $date
        ];
        $count = $count->whereHas('getGoods', function ($query) use ($where) {
            $query->where('is_delete', 0);

            if ($where['date'] > 0) {
                $query->where('last_update', '>=', $where['date']);
            }
        });

        $count = $count->count();

        return $count;
    }

    /**
     * 会员关注商品的列表
     *
     * @param int $goods_id
     * @return array
     */
    public function getUserCollectGoodsList($goods_id = 0, $date = 0, $start = 0, $size = 10)
    {
        $row = CollectGoods::where('is_attention', 1);

        if ($goods_id) {
            $row = $row->where('goods_id', $goods_id);
        }

        $row = $row->whereHas('getUsers');

        $where = [
            'date' => $date
        ];

        $row = $row->whereHas('getGoods', function ($query) use ($where) {
            $query = $query->where('is_delete', 0);

            if ($where['date'] > 0) {
                $query->where('last_update', '>=', $where['date']);
            }
        });

        $row = $row->with([
            'getUsers',
            'getGoods'
        ]);

        if ($start > 0) {
            $row = $row->skip($start);
        }

        if ($size > 0) {
            $row = $row->take($size);
        }

        $row = $this->baseRepository->getToArrayGet($row);

        return $row;
    }
}
