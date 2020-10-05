<?php

namespace App\Services\Dialog;

use App\Models\Brand;
use App\Models\GoodsTransportExpress;
use App\Models\GoodsTransportExtend;
use App\Models\MerchantsRegionInfo;
use App\Models\Region;
use App\Models\Shipping;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class DialogManageService
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    //查询区域地区列表
    public function ajaxGetAreaList($ra_id = 0, $region_ids = [])
    {
        $res = MerchantsRegionInfo::where('ra_id', $ra_id)
            ->with(['getRegion' => function ($query) {
                $query->select('region_id', 'region_name');
            }]);
        $res = $this->baseRepository->getToArrayGet($res);
        if ($res) {
            foreach ($res as $k => $v) {
                if ($v['get_region']) {
                    $res[$k]['region_name'] = $v['get_region']['region_name'];
                }
            }
        }
        if (!empty($region_ids) && !empty($res)) {
            foreach ($res as $k => $v) {
                if (in_array($v['region_id'], $region_ids)) {
                    $res[$k]['is_checked'] = 1;
                }
            }
        }
        return $res;
    }

    //获取品牌列表
    public function getBrandList($brand_ids)
    {
        $filter['record_count'] = Brand::where('is_show', 1)
            ->whereHas('getBrandExtend', function ($query) {
                $query->where('is_recommend', 1);
            })->count();

        $filter = page_and_size($filter, 4);

        $res = Brand::where('is_show', 1)
            ->whereHas('getBrandExtend', function ($query) {
                $query->where('is_recommend', 1);
            })->orderBy('sort_order', 'ASC')
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $recommend_brands = $this->baseRepository->getToArrayGet($res);

        if ($brand_ids) {
            $brand_ids = explode(',', $brand_ids);
        }
        foreach ($recommend_brands as $key => $val) {
            $val['brand_logo'] = DATA_DIR . '/brandlogo/' . $val['brand_logo'];
            $recommend_brands[$key]['brand_logo'] = $this->dscRepository->getImagePath($val['brand_logo']);
            $recommend_brands[$key]['selected'] = 0;
            if (!empty($brand_ids)) {
                foreach ($brand_ids as $v) {
                    if ($v == $val['brand_id']) {
                        $recommend_brands[$key]['selected'] = 1;
                    }
                }
            }
        }
        $filter['page_arr'] = seller_page($filter, $filter['page'], 14);
        return ['list' => $recommend_brands, 'filter' => $filter];
    }

    /* 地区列表 */
    public function getTransportArea($tid = 0)
    {
        $res = GoodsTransportExtend::whereRaw(1);
        if ($tid == 0) {
            $res = $res->where('admin_id', get_admin_id());
        }
        $res = $res->where('tid', $tid)->orderBy('id', 'DESC');
        $transport_area = $this->baseRepository->getToArrayGet($res);

        foreach ($transport_area as $key => $val) {
            if (!empty($val['top_area_id']) && !empty($val['area_id'])) {
                $area_map = [];
                $top_area_arr = explode(',', $val['top_area_id']);
                foreach ($top_area_arr as $k => $v) {
                    $top_area = get_table_date("region", "region_id='$v'", ['region_name'], 2);

                    $val['area_id'] = $this->baseRepository->getExplode($val['area_id']);
                    $area = Region::select('region_name')->where('parent_id', $v)->whereIn('region_id', $val['area_id']);
                    $area_arr = $this->baseRepository->getToArrayGet($area);

                    $area_map[$k]['top_area'] = $top_area;
                    $area_map[$k]['area_list'] = '';
                    if ($area_arr) {
                        $area_arr = $this->baseRepository->getFlatten($area_arr);
                        $area_list = implode(',', $area_arr);
                        $area_map[$k]['area_list'] = $area_list;
                    }
                }
                $transport_area[$key]['area_map'] = $area_map;
            }
        }
        return $transport_area;
    }

    /* 快递列表 */
    public function getTransportExpress($tid = 0)
    {
        $res = GoodsTransportExpress::whereRaw(1);
        if ($tid == 0) {
            $res = $res->where('admin_id', get_admin_id());
        }

        $res = $res->where('tid', $tid)->orderBy('id', 'DESC');
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

            if ($express_list) {
                $express_list = implode(',', $express_list);
            }
        }
        return $express_list;
    }
}
