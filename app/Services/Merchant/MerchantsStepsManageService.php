<?php

namespace App\Services\Merchant;

use App\Models\MerchantsStepsProcess;
use App\Models\MerchantsStepsTitle;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderService;


class MerchantsStepsManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        CommissionService $commissionService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
    }

    /**
     *  返回申请流程列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function stepsProcessList()
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'process_steps, steps_sort' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $res = MerchantsStepsProcess::whereRaw(1);
        if ($filter['keywords']) {
            $res = $res->where('process_title', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%');
        }

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderByRaw('process_steps ' . $filter['sort_order']);

        $res = $res->offset($filter['start'])->limit($filter['page_size']);
        $process_list = $this->baseRepository->getToArrayGet($res);

        $count = count($process_list);
        for ($i = 0; $i < $count; $i++) {
            $process_list[$i]['process_steps'] = $process_list[$i]['process_steps'];
            $process_list[$i]['process_left'] = isset($process_list[$i]['process_left']) ? $process_list[$i]['process_left'] : '';
            $process_list[$i]['process_right'] = isset($process_list[$i]['process_right']) ? $process_list[$i]['process_right'] : '';
        }

        $arr = ['process_list' => $process_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 返回申请流程列表数据
     *
     * @param $id
     * @return array
     */
    public function stepsProcessTitleList($id)
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);

        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'tid' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $res = MerchantsStepsTitle::where('fields_steps', $id);
        if ($filter['keywords']) {
            $res = $res->where('fields_titles', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%');
        }

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);

        $title_list = $this->baseRepository->getToArrayGet($res);

        $count = count($title_list);
        for ($i = 0; $i < $count; $i++) {
            $title_list[$i]['fields_steps'] = MerchantsStepsProcess::where('id', $title_list[$i]['fields_steps'])->value('process_title');
            $title_list[$i]['fields_steps'] = $title_list[$i]['fields_steps'] ? $title_list[$i]['fields_steps'] : '';
        }

        $arr = ['title_list' => $title_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
