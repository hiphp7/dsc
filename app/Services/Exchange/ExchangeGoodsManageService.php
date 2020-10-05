<?php

namespace App\Services\Exchange;

use App\Models\ExchangeGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 积分管理
 * Class JigonService
 * @package App\Services\Erp
 */
class ExchangeGoodsManageService
{
    protected $merchantCommonService;
    protected $dscRepository;
    protected $baseRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /* 获得商品列表 */
    public function getExchangeGoodslist($ru_id)
    {
        $filter = [];
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'eid' : trim($_REQUEST['sort_by']);
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
        $res = ExchangeGoods::whereRaw(1);
        if ($filter['review_status']) {
            $res = $res->where('review_status', $filter['review_status']);
        }

        if ($ru_id > 0) {
            $res = $res->where('user_id', $ru_id);
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
                        $store_search = $filter['store_search'] ?? '';
                        $store_keyword = $filter['store_keyword'] ?? '';

                        $res = $res->where(function ($query) use ($store_search, $store_keyword, $store_type) {
                            $query->whereHas('getMerchantsShopInformation', function ($query) use ($store_search, $store_keyword, $store_type) {
                                if ($store_search == 2) {
                                    $query = $query->where('rz_shopName', 'LIKE', '%' . $store_keyword . '%');
                                }
                                if ($store_search == 3) {
                                    if ($store_type) {
                                        $query = $query->where('shopNameSuffix', $store_type);
                                    }
                                    $query = $query->where('shoprz_brandName', 'LIKE', '%' . $store_keyword . '%');
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
            if ($ru_id == 0) {
                $res = $res->where('user_id', 0);
            }

        }
        /* 文章总数 */
        $keyword = $filter['keyword'];
        $res->whereHas('getGoods', function ($g_query) use ($keyword) {
            if ($keyword) {
                $g_query->where('goods_name', 'LIKE', '%' . mysql_like_quote($keyword) . '%');
            }
        });
        $res->with(['getGoods' => function ($g_query) use ($keyword) {
            $g_query->select('goods_id', 'goods_name');
        }]);
        $filter['record_count'] = $res->count();


        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $arr = [];
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])->offset($filter['start'])->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $rows) {
                $rows['goods_name'] = '';
                if (isset($rows['get_goods']) && !empty($rows['get_goods'])) {
                    $rows['goods_name'] = $rows['get_goods']['goods_name'];
                }
                $rows['user_name'] = $this->merchantCommonService->getShopName($rows['user_id'], 1); //ecmoban模板堂 --zhuo
                $arr[] = $rows;
            }
        }
        return ['arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
