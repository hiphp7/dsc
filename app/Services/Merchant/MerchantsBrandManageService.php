<?php

namespace App\Services\Merchant;

use App\Models\MerchantsShopBrand;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class MerchantsBrandManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }


    /**
     * 获取品牌列表
     *
     * @param $ru_id
     * @return array
     * @throws \Exception
     */
    public function getBrandList($ru_id)
    {
        /* 分页大小 */
        $filter = [];

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'bid' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $brand_name = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';
        $filter['audit_status'] = isset($_REQUEST['audit_status']) ? trim($_REQUEST['audit_status']) : '';

        $res = MerchantsShopBrand::whereRaw(1);
        if ($ru_id > 0) {
            $res = $res->where('user_id', $ru_id);
        }

        if ($filter['store_search'] != 0) {
            if ($ru_id == 0) {
                $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                if ($filter['store_search'] == 1) {
                    $res = $res->where('user_id', $filter['merchant_id']);
                }
                if ($filter['store_search'] > 1) {
                    $res = $res->where(function ($query) use ($filter, $store_type) {
                        $query->whereHas('getMerchantsShopInformation', function ($query) use ($filter, $store_type) {
                            if ($filter['store_search'] == 2) {
                                $query = $query->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                            } elseif ($filter['store_search'] == 3) {
                                $query = $query->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                if ($store_type) {
                                    $query = $query->where('shopNameSuffix', $store_type);
                                }
                            }
                        });
                    });
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end
        if ($filter['audit_status'] == 1 || $filter['audit_status'] == 2) {
            $res = $res->where('audit_status', $filter['audit_status']);
        } elseif ($filter['audit_status'] == 3) {
            $res = $res->where('audit_status', 0);
        }
        /* 记录总数以及页数 */
        if (!empty($brand_name)) {
            if (strtoupper(EC_CHARSET) == 'GBK') {
                $keyword = iconv("UTF-8", "gb2312", $brand_name);
            } else {
                $keyword = $brand_name;
            }
            $res = $res->where('brandName', 'LIKE', '%' . mysql_like_quote($keyword) . '%');
        }
        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 查询记录 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $rows) {
                $site_url = empty($rows['site_url']) ? 'N/A' : '<a href="' . $rows['site_url'] . '" target="_brank">' . $rows['site_url'] . '</a>';
                $rows['site_url'] = $site_url;

                $brand_logo = $this->dscRepository->getImagePath($rows['brandLogo']);
                $rows['brand_logo'] = $brand_logo;
                $rows['brand_id'] = $rows['bid'];
                $rows['brand_name'] = $rows['brandName'];
                $rows['brand_letter'] = $rows['bank_name_letter'];
                $rows['user_name'] = $this->merchantCommonService->getShopName($rows['user_id'], 1);
                $rows['link_brand'] = get_link_brand_list($rows['bid'], 3);

                $arr[] = $rows;
            }
        }

        return ['brand' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
