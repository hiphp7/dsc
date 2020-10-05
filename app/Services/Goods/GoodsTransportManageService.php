<?php

namespace App\Services\Goods;

use App\Models\GoodsTransport;
use App\Models\GoodsTransportExpress;
use App\Models\GoodsTransportExtend;
use App\Models\Region;
use App\Models\Shipping;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;

class GoodsTransportManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $commonManageService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        CommonManageService $commonManageService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->commonManageService = $commonManageService;
        $this->timeRepository = $timeRepository;
    }

    /* 快递列表 */

    public function getTransportExpress($tid = 0)
    {
        $res = GoodsTransportExpress::where('tid', $tid);
        if ($tid == 0) {
            $admin_id = $this->commonManageService->getAdminId();
            $res = $res->where('admin_id', $admin_id);
        }
        $res = $res->orderBy('id', 'DESC');
        $transport_express = $this->baseRepository->getToArrayGet($res);

        foreach ($transport_express as $key => $val) {
            $transport_express[$key]['express_list'] = $this->getExpressList($val['shipping_id']);
        }
        return $transport_express;
    }

    /* 获取快递 */

    public function getExpressList($shipping_id = '')
    {
        $express_list = '';
        if (!empty($shipping_id)) {
            $shipping_id = $this->baseRepository->getExplode($shipping_id);
            $res = Shipping::select('shipping_name')->whereIn('shipping_id', $shipping_id);
            $express_list = $this->baseRepository->getToArrayGet($res);
            $express_list = $this->baseRepository->getFlatten($express_list);
            $express_list = implode(',', $express_list);
        }
        return $express_list;
    }

    /* 获取地区 */

    public function getAreaList($area_id = '')
    {
        $area_list = '';
        if (!empty($area_id)) {
            $area_id = $this->baseRepository->getExplode($area_id);
            $res = Region::select('region_name')->whereIn('region_id', $area_id);
            $area_list = $this->baseRepository->getToArrayGet($res);
            $area_list = $this->baseRepository->getFlatten($area_list);
            $area_list = implode(',', $area_list);
        }
        return $area_list;
    }

    /* 地区列表 */

    public function getTransportArea($tid = 0)
    {
        $res = GoodsTransportExtend::where('tid', $tid);
        if ($tid == 0) {
            $admin_id = $this->commonManageService->getAdminId();
            $res = $res->where('admin_id', $admin_id);
        }

        $res = $res->orderBy('id', 'DESC');
        $transport_area = $this->baseRepository->getToArrayGet($res);

        foreach ($transport_area as $key => $val) {
            if (!empty($val['top_area_id']) && !empty($val['area_id'])) {
                $area_map = [];
                $top_area_arr = explode(',', $val['top_area_id']);
                foreach ($top_area_arr as $k => $v) {

                    $top_area = Region::where('region_id', $v)->value('region_name');
                    $top_area = $top_area ? $top_area : '';

                    $area_id_attr = $this->baseRepository->getExplode($val['area_id']);
                    $res = Region::select('region_name')->where('parent_id', $v)->whereIn('region_id', $area_id_attr);
                    $area_arr = $this->baseRepository->getToArrayGet($res);
                    $area_arr = $this->baseRepository->getFlatten($area_arr);

                    $area_list = $area_arr ? implode(',', $area_arr) : '';

                    $area_map[$k]['top_area'] = $top_area;
                    $area_map[$k]['area_list'] = $area_list;
                }
                $transport_area[$key]['area_map'] = $area_map;
            }
        }
        return $transport_area;
    }

    /* 模板列表 */
    public function getTransportList($ru_id = 0)
    {
        $res = GoodsTransport::whereRaw(1);
        /* 初始化分页参数 */
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        if (!empty($filter['keyword'])) {
            $res = $res->where('title', 'LIKE', '%' . mysql_like_quote($filter['keyword']) . '%');
        }
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['keyword'] = stripslashes($filter['keyword']);
        //区分商家和自营
        if (!empty($filter['seller_list'])) {
            $res = $res->where('ru_id', '>', 0);
        } else {
            $res = $res->where('ru_id', 0);
        }

        /* 查询记录总数，计算分页数 */
        $filter['record_count'] = $res->count();
        $filter = page_and_size($filter);

        /* 查询记录 */
        $res = $res->orderBy('tid', 'DESC')->offset($filter['start'])->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $row['update_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['update_time']);
                $row['shop_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1);
                $arr[] = $row;
            }
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
