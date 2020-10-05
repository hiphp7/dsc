<?php

namespace App\Services\Message;

use App\Models\ZcInitiator;
use App\Models\ZcProject;
use App\Models\ZcRankLogo;
use App\Models\ZcTopic;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\CrowdFund\CategoryService;

class ZcManageService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $timeRepository;
    protected $config;
    protected $categoryService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        CategoryService $categoryService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->categoryService = $categoryService;
    }

    /**
     * 获得发起人列表
     *
     * @return array
     */
    public function zcTopicList()
    {
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['parent_id'] = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);

        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'topic_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = ZcTopic::query();

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $row = $row->where('topic_content', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
        }

        /* 父子话题 */
        if (!empty($filter['parent_id'])) {
            $row = $row->where('parent_topic_id', $filter['parent_id']);
        } else {
            $row = $row->where('parent_topic_id', 0);
        }

        $res = $record_count = $row;

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->with([
            'getUsers',
            'getZcProject'
        ]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $res = $res->skip($filter['start']);

        $res = $res->take($filter['page_size']);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {

                $res[$key]['user_name'] = $val['get_users']['user_name'] ?? '';
                $res[$key]['nick_name'] = $val['get_users']['nick_name'] ?? '';
                $res[$key]['title'] = $val['get_zc_project']['title'] ?? '';

                if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                    $res[$key]['user_name'] = $this->dscRepository->stringToStar($res[$key]['user_name']);
                    $res[$key]['nick_name'] = $this->dscRepository->stringToStar($res[$key]['nick_name']);
                }

                $res[$key]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['add_time']);
            }
        }

        return ['topic_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 获得商品列表
     *
     * @return array
     * @throws \Exception
     */
    public function zcProjectList()
    {
        $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);

        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = ZcProject::query();

        if ($filter['cat_id'] > 0) {
            $children = $this->categoryService->getZcCatListChildren($filter['cat_id']);
            $row = $row->whereIn('cat_id', $children);
        }

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $row = $row->where('title', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
        }

        $res = $record_count = $row;

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);


        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $res = $res->skip($filter['start']);

        $res = $res->take($filter['page_size']);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            $time = $this->timeRepository->getGmTime();
            foreach ($res as $k => $val) {
                $res[$k]['start_time'] = $this->timeRepository->getLocalDate('Y-m-d', $val['start_time']);
                $res[$k]['end_time'] = $this->timeRepository->getLocalDate('Y-m-d', $val['end_time']);

                $res[$k]['title_img'] = $this->dscRepository->getImagePath($val['title_img']);

                if ($time > $val['end_time']) {
                    $status = $GLOBALS['_LANG']['zc_out'];
                    if ($val['join_money'] > $val['amount']) {
                        $res[$k]['result'] = 1;
                    } else {
                        $res[$k]['result'] = 2;
                    }
                } elseif ($time < $val['start_time']) {
                    $status = $GLOBALS['_LANG']['zc_soon'];
                } else {
                    $status = $GLOBALS['_LANG']['zc_in'];
                }
                $res[$k]['zc_status'] = $status;
            }
        }

        return ['zc_projects' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 获得发起人列表
     *
     * @access  public
     * @params  integer $isdelete
     * @params  integer $real_goods
     * @params  integer $conditions
     * @return  array
     */
    public function zcInitiatorList()
    {
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);

        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = ZcInitiator::query();

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $row = $row->where('name', 'like', '%' . mysql_like_quote($filter['keyword']) . '%');
        }

        $res = $record_count = $row;

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $res = $res->skip($filter['start']);

        $res = $res->take($filter['page_size']);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $v) {//处理等级标识
                $res[$k]['img'] = $this->dscRepository->getImagePath($v['img']);
                $logo = explode(',', $v['rank']);
                if ($logo) {
                    foreach ($logo as $val) {
                        $res[$k]['logo'][] = $this->getRankLogo($val);
                        foreach ($res[$k]['logo'] as $logo_key => $logo_val) {
                            $res[$k]['logo'][$logo_key]['img'] = $this->dscRepository->getImagePath($logo_val['img']);
                        }
                    }
                }
            }
        }

        return ['zc_initiator' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 取得等级身份标识
     *
     * @param $id
     * @return mixed
     */
    public function getRankLogo($id)
    {
        $row = ZcRankLogo::where('id', $id);
        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * 获得等级标识列表
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function zcRankLogoList()
    {
        $row = ZcRankLogo::query();
        $row = $this->baseRepository->getToArrayGet($row);

        if ($row) {
            foreach ($row as $key => $val) {
                $row[$key]['img'] = $this->dscRepository->getImagePath($val['img']);
            }
        }

        return $row;
    }
}