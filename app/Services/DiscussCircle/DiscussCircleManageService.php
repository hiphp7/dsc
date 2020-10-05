<?php

namespace App\Services\DiscussCircle;

use App\Models\DiscussCircle;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class DiscussCircleManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        TimeRepository $timeRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获取讨论列表
     *
     * @param $ru_id
     * @return array
     * @throws \Exception
     */
    public function getDiscussList($ru_id)
    {
        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        // 筛选 未审核
        $filter['review_status'] = empty($_REQUEST['review_status']) ? '' : trim($_REQUEST['review_status']);

        $res = DiscussCircle::whereRaw(1);
        if (!empty($filter['keywords'])) {
            $res = $res->where('dis_title', 'like', '%' . $filter['keywords'] . '%');
        }
        if ($filter['review_status']) {
            $res = $res->where('review_status', $filter['review_status']);
        }

        $keywords = $filter['keywords'];

        $filter['record_count'] = $res->whereHas('getGoods', function ($query) use ($ru_id, $keywords) {
            if ($keywords) {
                $query->orWhere('goods_name', 'LIKE', '%' . $keywords . '%');
            }
            if ($ru_id > 0) {
                $query->where('user_id', $ru_id);
            }
        })->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取评论数据 */

        $res = $res->with(['getGoods' => function ($query) use ($ru_id, $keywords) {
            if ($keywords) {
                $query->orWhere('goods_name', 'LIKE', '%' . $keywords . '%');
            }
            if ($ru_id > 0) {
                $query->where('user_id', $ru_id);
            }
            $query->select('goods_id', 'goods_name', 'user_id');
        }]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                if (isset($row['get_goods']) && !empty($row['get_goods'])) {
                    $row['goods_name'] = $row['get_goods']['goods_name'];
                    $row['ru_id'] = $row['get_goods']['user_id'];
                }
                switch ($row['review_status']) {
                    case 1:
                        $row['lang_review_status'] = $GLOBALS['_LANG']['not_audited'];
                        break;

                    case 2:
                        $row['lang_review_status'] = $GLOBALS['_LANG']['audited_not_adopt'];
                        break;

                    case 3:

                        $row['lang_review_status'] = $GLOBALS['_LANG']['audited_yes_adopt'];
                        break;
                }

                $row['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['add_time']);
                $row['shop_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1);
                $row['user_name'] = Users::where('user_id', $row['user_id'])->value('user_name');

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row['user_name'] = $this->dscRepository->stringToStar($row['user_name']);
                }

                $arr[] = $row;
            }
        }

        $filter['keywords'] = stripslashes($filter['keywords']);
        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获取讨论列表
     * @access  public
     * @return  array
     */
    public function getDiscussUserReplyList()
    {
        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $dis_id = empty($_REQUEST['dis_id']) ? 0 : trim($_REQUEST['dis_id']);
        $id = empty($_REQUEST['id']) ? 0 : trim($_REQUEST['id']);
        $filter['dis_id'] = $dis_id > 0 ? $dis_id : $id;

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $res = DiscussCircle::whereRaw(1);
        if (!empty($filter['keywords'])) {
            $res = $res->where('dis_title', 'LIKE', '%' . $filter['keywords'] . '%');
        }

        $filter['record_count'] = $res->where('parent_id', $filter['dis_id'])->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取评论数据 */
        $arr = [];
        $res = $res->where('parent_id', $filter['dis_id'])
            ->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $row) {
            $row['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['add_time']);

            $quote_id = $row['quote_id'];
            $res = Users::whereHas('getDiscussCircle', function ($query) use ($quote_id) {
                $query->where('dis_id', $quote_id);
            });
            $row['quote_name'] = $res->value('user_name');
            $arr[] = $row;
        }

        $filter['keywords'] = stripslashes($filter['keywords']);
        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count'], 'dis_id' => $filter['dis_id']];

        return $arr;
    }
}
