<?php

namespace App\Services\Package;

use App\Models\GoodsActivity;
use App\Models\PackageGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;

class PackageManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $commonManageService;
    protected $admin_id = 0;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        CommonManageService $commonManageService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->commonManageService = $commonManageService;
        $this->dscRepository = $dscRepository;
        $this->admin_id = $this->commonManageService->getAdminId();
    }


    /**
     * 获取活动列表
     *
     * @param $ru_id
     * @return array
     * @throws \Exception
     */
    public function getPackageList($ru_id)
    {
        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        $adminru = get_admin_ru_id();
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        $res = GoodsActivity::whereRaw(1);
        if (!empty($filter['keywords'])) {
            $res = $res->where('act_name', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%');
        }

        if ($ru_id > 0) {
            $res = $res->where('user_id', $ru_id);
        }

        if ($filter['review_status']) {
            $res = $res->where('review_status', $filter['review_status']);
        }

        //卖场
        $res = $this->dscRepository->getWhereRsid($res, 'user_id', $filter['rs_id']);

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] > -1) {
            if ($ru_id == 0) {
                if ($filter['store_search'] > 0) {
                    $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                    if ($filter['store_search'] == 1) {
                        $res = $res->where('user_id', $filter['merchant_id']);
                    }

                    if ($filter['store_search'] > 1) {
                        $res = $res->where(function ($query) use ($filter, $store_type) {
                            $query->whereHas('getMerchantsShopInformation', function ($query) use ($filter, $store_type) {
                                if ($filter['store_search'] == 2) {
                                    $query->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                } elseif ($filter['store_search'] == 3) {
                                    $query = $query->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                    if ($store_type) {
                                        $query->where('shopNameSuffix', $store_type);
                                    }
                                }
                            });
                        });

                    }
                } else {
                    $res = $res->where('user_id', 0);
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        //区分商家和自营
        if (!empty($filter['seller_list'])) {
            $res = $res->where('user_id', '>', 0);
        } else {
            $res = $res->where('user_id', 0);
        }

        $filter['record_count'] = $res->where('act_type', GAT_PACKAGE)->count();

        $filter = page_and_size($filter);

        /* 获活动数据 */

        $res = $res->where('act_type', GAT_PACKAGE)
            ->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $row = $this->baseRepository->getToArrayGet($res);

        foreach ($row as $key => $val) {
            $row[$key]['package_name'] = $val['act_name'];
            $row[$key]['start_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $val['start_time']);
            $row[$key]['end_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $val['end_time']);
            $info = unserialize($row[$key]['ext_info']);
            unset($row[$key]['ext_info']);
            if ($info) {
                foreach ($info as $info_key => $info_val) {
                    $row[$key][$info_key] = $info_val;
                }
            }

            $row[$key]['ru_name'] = $this->merchantCommonService->getShopName($val['user_id'], 1);
        }

        $arr = ['packages' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 保存某礼包的商品
     * @param int $package_id
     * @return  void
     */
    public function handlePackagepGoods($package_id)
    {
        $data = ['package_id' => $package_id];
        PackageGoods::where('admin_id', $this->admin_id)
            ->where('package_id', 0)
            ->update($data);
    }
}
