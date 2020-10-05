<?php

namespace App\Services\Merchant;

use App\Models\MerchantsPercent;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderService;


class MerchantsPercentManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        CommissionService $commissionService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获取供应商列表信息
     *
     * @return array
     */
    public function percentList()
    {
        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'percent_id' : trim($_REQUEST['sort_by']);  //ecmoban模板堂 --zhuo
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        $filter['record_count'] = MerchantsPercent::count();
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        $res = MerchantsPercent::orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset(($filter['page'] - 1) * $filter['page_size'])
            ->limit($filter['page_size']);
        $percent_list = $this->baseRepository->getToArrayGet($res);

        if ($percent_list) {
            foreach ($percent_list as $key => $val) {
                $percent_list[$key]['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['date_format'], $val['add_time']);
            }
        }

        $arr = ['result' => $percent_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
