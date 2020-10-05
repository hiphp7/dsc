<?php

namespace App\Services\NoticeLogs;

use App\Models\NoticeLog;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;


class NoticeLogsManageService
{
    protected $baseRepository;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        CommissionService $commissionService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->timeRepository = $timeRepository;
    }


    /* 获取管理员操作记录 */
    public function getNoticeLogs($ru_id)
    {
        $filter = [];
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

        //查询条件
        $res = NoticeLog::whereRaw(1);

        //区分商家和自营
        $seller_list = $filter['seller_list'];
        $res = $res->whereHas('getGoods', function ($query) use ($ru_id, $seller_list) {
            if ($ru_id > 0) {
                $query = $query->where('user_id', $ru_id);
            }
            if (!empty($seller_list)) {
                $query = $query->where('user_id', '>', 0);
            } else {
                $query = $query->where('user_id', 0);
            }
        });
        /* 获得总记录数据 */
        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 获取管理员日志记录 */
        $list = [];
        $res = $res->with(['getGoods' => function ($query) {
            $query->select('goods_id', 'user_id', 'goods_name');
        }]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $rows) {
                $rows['user_id'] = '';
                $rows['goods_name'] = '';
                if (isset($rows['get_goods']) && !empty($rows['get_goods'])) {
                    $rows['user_id'] = $rows['get_goods']['user_id'];
                    $rows['goods_name'] = $rows['get_goods']['goods_name'];
                }

                $rows['send_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $rows['send_time']);
                $rows['shop_name'] = $this->merchantCommonService->getShopName($rows['user_id'], 1);

                $list[] = $rows;
            }
        }

        return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
