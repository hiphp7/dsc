<?php

namespace App\Services\Goods;

use App\Models\AdminUser;
use App\Models\Goods;
use App\Models\GoodsReport;
use App\Models\GoodsReportImg;
use App\Models\GoodsReportTitle;
use App\Models\GoodsReportType;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class GoodsReportManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }


    /**
     * 投诉列表
     * @return  array
     */
    public function getGoodsReport()
    {

        $res = GoodsReport::whereRaw(1);
        /* 初始化分页参数 */
        $filter = [];
        $filter['handle_type'] = !empty($_REQUEST['handle_type']) ? $_REQUEST['handle_type'] : '-1';
        $filter['keywords'] = !empty($_REQUEST['keywords']) ? trim($_REQUEST['keywords']) : '';

        if ($filter['keywords']) {
            $keywords = $filter['keywords'];
            $res = $res->where(function ($query) use ($keywords) {
                $query->whereHas('getUsers', function ($query) use ($keywords) {
                    $query = $query->where('user_name', 'LIKE', '%' . $keywords . '%')
                        ->orWhere('nick_name', 'LIKE', '%' . $keywords . '%')
                        ->orWhere('goods_name', 'LIKE', '%' . $keywords . '%');
                });
            });
        }
        if ($filter['handle_type'] != '-1') {
            if ($filter['handle_type'] == 6) {
                $res = $res->where('report_state', 0);
            } else {
                $res = $res->where('report_state', '>', 0);
            }
        }
        /* 查询记录总数，计算分页数 */

        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 查询记录 */
        $res = $res->with(['getUsers' => function ($query) {
            $query->select('user_id', 'user_name');
        }]);
        $res = $res->orderBy('add_time', 'DESC')->offset($filter['start'])->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);
        $arr = [];
        foreach ($res as $rows) {
            $rows['user_name'] = '';
            if (isset($rows['get_users']) && !empty($rows['get_users'])) {
                $rows['user_name'] = $rows['get_users']['user_name'];

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $rows['user_name'] = $this->dscRepository->stringToStar($rows['user_name']);
                }
            }
            $rows['goods_image'] = $this->dscRepository->getImagePath($rows['goods_image']);
            $rows['admin_name'] = AdminUser::where('user_id', $rows['admin_id'])->value('user_name');
            $rows['admin_name'] = $rows['admin_name'] ? $rows['admin_name'] : '';
            if ($rows['title_id'] > 0) {
                $rows['title_name'] = GoodsReportTitle::where('title_id', $rows['title_id'])->value('title_name');
                $rows['title_name'] = $rows['title_name'] ? $rows['title_name'] : '';

            }
            if ($rows['type_id'] > 0) {
                $rows['type_name'] = GoodsReportType::where('type_id', $rows['type_id'])->value('type_name');
                $rows['type_name'] = $rows['type_name'] ? $rows['type_name'] : '';
            }
            if ($rows['add_time'] > 0) {
                $rows['add_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $rows['add_time']);
            }
            if ($rows['handle_time'] > 0) {
                $rows['handle_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $rows['handle_time']);
            }
            $rows['url'] = $this->dscRepository->buildUri('goods', ['gid' => $rows['goods_id']], $rows['goods_name']);
            $user_id = Goods::where('goods_id', $rows['goods_id'])->value('user_id');
            $user_id = $user_id ? $user_id : 0;
            $rows['shop_name'] = $this->merchantCommonService->getShopName($user_id, 1);

            //获取举报图片列表
            $img_list = GoodsReportImg::where('report_id', $rows['report_id'])->orderBy('img_id', 'DESC');
            $img_list = $this->baseRepository->getToArrayGet($img_list);
            if (!empty($img_list)) {
                foreach ($img_list as $k => $v) {
                    $img_list[$k]['img_file'] = $this->dscRepository->getImagePath($v['img_file']);
                }
            }
            $rows['img_list'] = $img_list;
            $arr[] = $rows;
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    public function getGoodsReportTypeList()
    {
        /* 初始化分页参数 */
        $filter = [];
        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = GoodsReportType::count();
        $filter = page_and_size($filter);

        /* 查询记录 */
        $res = GoodsReportType::orderBy('type_id', 'DESC')
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $list = $this->baseRepository->getToArrayGet($res);

        return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    public function getGoodsReportTitleList()
    {
        /* 初始化分页参数 */
        $filter = [];
        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = GoodsReportTitle::count();
        $filter = page_and_size($filter);

        /* 查询记录 */

        $res = GoodsReportTitle::orderBy('type_id', 'DESC')->offset($filter['start'])->limit($filter['page_size']);
        $list = $this->baseRepository->getToArrayGet($res);

        if ($list) {
            foreach ($list as $k => $v) {
                if ($v['type_id'] > 0) {
                    $list[$k]['type_name'] = GoodsReportType::where('type_id', $v['type_id'])->value('type_name');
                    $list[$k]['type_name'] = $list[$k]['type_name'] ? $list[$k]['type_name'] : '';
                }
            }
        }
        return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
