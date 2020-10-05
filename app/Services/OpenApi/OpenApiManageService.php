<?php

namespace App\Services\OpenApi;

use App\Models\OpenApi;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;


class OpenApiManageService
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


    /**
     *  返回bucket列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function openApiList()
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = OpenApi::count();
        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = OpenApi::orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $open_api_list = $this->baseRepository->getToArrayGet($res);

        $count = count($open_api_list);

        for ($i = 0; $i < $count; $i++) {
            $open_api_list[$i]['add_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $open_api_list[$i]['add_time']);
        }

        $arr = ['open_api_list' => $open_api_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /* 接口列表 */
    public function getApiData($api_data = [], $action_code)
    {
        for ($i = 0; $i < count($api_data); $i++) {
            for ($j = 0; $j < count($api_data[$i]['list']); $j++) {
                $api_data[$i]['list'][$j]['is_check'] = 0;

                if ($action_code) {
                    if (in_array($api_data[$i]['list'][$j]['val'], $action_code)) {
                        $api_data[$i]['list'][$j]['is_check'] = 1;
                    }
                }
            }
        }

        return $api_data;
    }
}
