<?php

namespace App\Services\Goods;

use App\Models\Goods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class GoodsAutoManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $timeRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->timeRepository = $timeRepository;
    }

    public function getAutoGoods($ru_id)
    {
        $res = Goods::where('is_delete', '<>', 1);

        if ($ru_id > 0) {
            $res = $res->where('user_id', $ru_id);
        }

        if (!empty($_POST['goods_name'])) {
            $goods_name = trim($_POST['goods_name']);
            $res = $res->where('goods_name', 'LIKE', '%' . $goods_name . '%');
            $filter['goods_name'] = $goods_name;
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'last_update' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $res = $res->with(['getAutoManage' => function ($query) {
            $query->select('item_id', 'starttime', 'endtime')->where('type', 'goods');
        }]);
        $res = $res->orderBy('goods_id')->orderBy($filter['sort_by'], $filter['sort_order']);
        $res = $res->offset($filter['start'])->limit($filter['page_size']);
        $query = $this->baseRepository->getToArrayGet($res);

        $goodsdb = [];

        if ($query) {
            foreach ($query as $rt) {
                $rt['starttime'] = '';
                $rt['endtime'] = '';
                if (isset($rt['get_auto_manage']) && !empty($rt['get_auto_manage'])) {
                    $rt['starttime'] = $rt['get_auto_manage']['starttime'];
                    $rt['endtime'] = $rt['get_auto_manage']['endtime'];
                }

                if (!empty($rt['starttime'])) {
                    $rt['starttime'] = $this->timeRepository->getLocalDate('Y-m-d', $rt['starttime']);
                }

                if (!empty($rt['endtime'])) {
                    $rt['endtime'] = $this->timeRepository->getLocalDate('Y-m-d', $rt['endtime']);
                }

                $rt['user_name'] = $this->merchantCommonService->getShopName($rt['user_id'], 1);

                $goodsdb[] = $rt;
            }
        }

        $arr = ['goodsdb' => $goodsdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
