<?php

namespace App\Services\Region;


use App\Models\MerchantsRegionArea;
use App\Models\MerchantsRegionInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;


class RegionAreaManageService
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

    /**
     *  返回地区区域列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function regionAreaList()
    {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ra_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = MerchantsRegionArea::count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = MerchantsRegionArea::orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $region_list = $this->baseRepository->getToArrayGet($res);

        $count = count($region_list);
        if ($region_list) {
            for ($i = 0; $i < $count; $i++) {
                $region_list[$i]['add_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $region_list[$i]['add_time']);
                $area = $this->getAreaList($region_list[$i]['ra_id']);
                $region_list[$i]['area_list'] = $area['region_name'];
            }
        }

        $arr = ['region_list' => $region_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }


    //批量添加地区
    public function getAreaAddBacth($ra_id = 0, $area_list)
    {
        MerchantsRegionInfo::where('ra_id', $ra_id)->delete();

        $other = [];
        if (count($area_list) > 0) {
            for ($i = 0; $i < count($area_list); $i++) {
                $other['ra_id'] = $ra_id;
                $other['region_id'] = $area_list[$i];
                MerchantsRegionInfo::insert($other);
            }
        }
    }

    //查询区域地区列表
    public function getAreaList($ra_id = 0)
    {
        $res = MerchantsRegionInfo::where('ra_id', $ra_id);
        $res = $res->with(['getRegion']);
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [
            'region_name' => ''
        ];
        foreach ($res as $key => $row) {
            $row['region_id'] = $row['get_region']['region_id'] ?? 0;
            $row['region_name'] = $row['get_region']['region_name'] ?? '';

            $arr[$key] = $row;
            $arr['region_name'] .= $row['region_name'] . ',';

            $res[$key] = $row;
        }

        $arr['region_name'] = substr($arr['region_name'], 0, -1);

        return $arr;
    }
}
