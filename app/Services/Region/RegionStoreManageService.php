<?php

namespace App\Services\Region;

use App\Models\AdminUser;
use App\Models\RegionStore;
use App\Models\RsRegion;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class RegionStoreManageService
{

    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
    }


    /* 获取卖场列表 */
    public function regionStoreList()
    {
        /* 过滤查询 */
        $filter = [];

        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'rs_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $res = RegionStore::whereRaw(1);
        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $res = $res->where(function ($query) use ($filter) {
                $query->where('rs_name', 'LIKE', '%' . mysql_like_quote($filter['keyword']) . '%');
            });
        }

        /* 获得总记录数据 */
        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 获得数据 */
        $arr = [];
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $rows) {
            //地区
            $region_id = get_table_date('rs_region', "rs_id='$rows[rs_id]'", ['region_id'], 2);
            if ($region_id) {
                $rows['region_name'] = get_table_date('region', "region_id='$region_id'", ['region_name'], 2);
            }

            //管理员
            $rows['user_name'] = get_table_date('admin_user', "rs_id='$rows[rs_id]'", ['user_name'], 2);

            $arr[] = $rows;
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /* 获取卖场信息 */
    public function getRegionStoreInfo($rs_id = 0)
    {
        $region_store = get_table_date('region_store', "rs_id='$rs_id'", ['*']);
        if ($region_store) {
            //区域
            $region_id = RsRegion::where('rs_id', $rs_id)->value('region_id');
            $region_id = $region_id ? $region_id : 0;

            //管理员
            $user_id = AdminUser::where('rs_id', $rs_id)->value('user_id');
            $user_id = $user_id ? $user_id : 0;

            //整合数据
            $region_store['region_id'] = $region_id;
            $region_store['user_id'] = $user_id;
        }

        return $region_store;
    }

    /* 获取管理员列表 */
    public function getRegionAdmin()
    {
        $super_admin_id = get_table_date('admin_user', "action_list='all'", ['user_id'], 2);

        $res = AdminUser::where('action_list', '<>', 'all')
            ->where('ru_id', 0)
            ->where('parent_id', $super_admin_id)
            ->orderBy('user_id', 'DESC');
        $region_admin = $this->baseRepository->getToArrayGet($res);

        return $region_admin;
    }
}
