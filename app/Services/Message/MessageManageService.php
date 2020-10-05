<?php

namespace App\Services\Message;

use App\Models\AdminMessage;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionService;
use App\Services\Common\CommonManageService;
use App\Services\Order\OrderService;


class MessageManageService
{
    protected $baseRepository;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;
    protected $commonManageService;
    protected $admin_id = 0;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        CommissionService $commissionService,
        DscRepository $dscRepository,
        CommonManageService $commonManageService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
        $this->commonManageService = $commonManageService;
        $this->timeRepository = $timeRepository;

        /* 后台管理员ID */
        $this->admin_id = $this->commonManageService->getAdminId();
    }

    /**
     * 获取管理员留言列表
     *
     * @return array
     */
    public function getMessageList()
    {
        /* 查询条件 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sent_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['msg_type'] = empty($_REQUEST['msg_type']) ? 0 : intval($_REQUEST['msg_type']);

        /* 查询条件 */
        $res = AdminMessage::whereRaw(1);
        switch ($filter['msg_type']) {
            case 1:
                $res = $res->where('receiver_id', $this->admin_id);
                break;
            case 2:
                $res = $res->where('sender_id', $this->admin_id)
                    ->where('deleted', 0);
                break;
            case 3:
                $res = $res->where('readed', 0)
                    ->where('receiver_id', $this->admin_id)
                    ->where('deleted', 0);
                break;
            case 4:
                $res = $res->where('readed', 1)
                    ->where('receiver_id', $this->admin_id)
                    ->where('deleted', 0);
                break;
            default:
                $res = $res->where(function ($query) {
                    $query->where('sender_id', $this->admin_id)
                        ->orWhere('receiver_id', $this->admin_id)
                        ->where('deleted', 0);
                });
        }

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->with(['getAdminUser' => function ($query) {
            $query->select('user_id', 'user_name');
        }]);
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $row = $this->baseRepository->getToArrayGet($res);

        if ($row) {
            foreach ($row as $key => $val) {
                $row[$key]['user_name'] = '';
                if (isset($val['get_admin_user']) && !empty($val['get_admin_user'])) {
                    $row[$key]['user_name'] = $val['get_admin_user']['user_name'];
                }

                $row[$key]['sent_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $val['sent_time']);
                $row[$key]['read_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $val['read_time']);
            }
        }

        $arr = ['item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
