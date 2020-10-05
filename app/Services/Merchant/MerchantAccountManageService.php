<?php

namespace App\Services\Merchant;

use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;


class MerchantAccountManageService
{
    protected $baseRepository;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,

        MerchantCommonService $merchantCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 商家资金列表
     */
    public function getMerchantsSellerAccount()
    {
        $adminru = get_admin_ru_id();

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ru_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);

        $res = SellerShopinfo::whereRaw(1);
        if ($filter['keywords']) {

            $keywords = $filter['keywords'];
            $u_res = Users::select('user_id')
                ->where(function ($query) use ($keywords) {
                    $query->where('user_name', 'LIKE', '%' . mysql_like_quote($keywords) . '%')
                        ->orWhere('nick_name', 'LIKE', '%' . mysql_like_quote($keywords) . '%');
                });
            $user_id = $this->baseRepository->getToArrayGet($u_res);
            $user_id = $this->baseRepository->getFlatten($user_id);
            if ($user_id) {
                $res = $res->whereIn('ru_id', $user_id);
            }
        }
        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';


        $ru_id = $adminru['ru_id'];
        $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

        $res = $res->whereHas('getMerchantsShopInformation', function ($query) use ($filter, $store_type, $ru_id) {
            $query = $query->where('merchants_audit', 1);

            if ($filter['store_search'] != 0) {
                if ($ru_id == 0) {
                    if ($filter['store_search'] == 1) {
                        $query = $query->where('user_id', $filter['merchant_id']);
                    } elseif ($filter['store_search'] == 2) {
                        $query = $query->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                    } elseif ($filter['store_search'] == 3) {
                        $query = $query->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                        if ($store_type) {
                            $query = $query->where('shopNameSuffix', $store_type);
                        }
                    }

                    if ($filter['store_search'] > 1) {
                        $query = $query->where('user_id', '>', 0);
                    }
                }
            }
        });

        //管理员查询的权限 -- 店铺查询 end
        $filter['record_count'] = $res->count();
        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['shop_name'] = $this->merchantCommonService->getShopName($res[$i]['ru_id'], 1);
        }

        $arr = ['log_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
