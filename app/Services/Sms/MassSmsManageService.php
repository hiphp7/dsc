<?php

namespace App\Services\Sms;

use App\Models\MassSmsLog;
use App\Models\MassSmsTemplate;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;


class MassSmsManageService
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

    //获取短信模板列表
    public function massSmsList()
    {
        /* 过滤查询 */
        $filter = [];

        //ecmoban模板堂 --zhuo start
        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        //ecmoban模板堂 --zhuo end

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $res = MassSmsTemplate::whereRaw(1);

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $keyword = $filter['keyword'];
            $res = $res->where(function ($query) use ($keyword) {
                $query->where('temp_content', 'LIKE', '%' . mysql_like_quote($keyword) . '%');
            });
        }

        /* 获得总记录数据 */
        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 获得广告数据 */
        $arr = [];
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        $idx = 0;
        if ($res) {
            foreach ($res as $rows) {
                /* 格式化日期 */
                $rows['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $rows['add_time']);

                /* 统计数量 */
                $rows['wait_count'] = MassSmsLog::where('template_id', $rows['id'])->where('send_status', 0)->count();
                $rows['success_count'] = MassSmsLog::where('template_id', $rows['id'])->where('send_status', 1)->count();
                $rows['failure_count'] = MassSmsLog::where('template_id', $rows['id'])->where('send_status', 2)->count();

                $arr[$idx] = $rows;
                $idx++;
            }
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    //获取短信记录列表
    public function massSmsLog()
    {
        /* 过滤查询 */
        $filter = [];

        //ecmoban模板堂 --zhuo start
        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        //ecmoban模板堂 --zhuo end

        $filter['template_id'] = empty($_REQUEST['template_id']) ? 0 : trim($_REQUEST['template_id']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $res = MassSmsLog::whereRaw(1);

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $keyword = $filter['keyword'];
            $res = $res->where(function ($query) use ($keyword) {
                $query->where('temp_content', 'LIKE', '%' . mysql_like_quote($keyword) . '%');
            });

        }

        /* 模板id */
        if (!empty($filter['template_id'])) {
            $res = $res->where('template_id', $filter['template_id']);
        }

        /* 获得总记录数据 */
        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 获得广告数据 */
        $arr = [];

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $rows) {
                /* 格式化日期 */
                $rows['last_send'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $rows['last_send']);

                /* 会员信息 */
                $user_info = get_table_date("users", "user_id='{$rows['user_id']}'", ['user_name', 'mobile_phone']);
                $rows['user_name'] = $user_info['user_name'];
                $rows['mobile_phone'] = $user_info['mobile_phone'];

                $arr[$key] = $rows;
            }
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
