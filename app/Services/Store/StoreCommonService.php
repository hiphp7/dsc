<?php

namespace App\Services\Store;


use App\Models\MerchantsShopInformation;
use App\Models\OfflineStore;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantCommonService;

class StoreCommonService
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
     * 店铺列表
     *
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getCommonStoreList()
    {
        $cache_name = "get_common_store_list";
        $arr = cache($cache_name);

        if (is_null($arr)) {
            $res = MerchantsShopInformation::where('merchants_audit', 1);
            $res = $this->baseRepository->getToArrayGet($res);

            $arr = [];
            if ($res) {
                foreach ($res as $key => $row) {
                    $arr[$key]['shop_id'] = $row['shop_id'];
                    $arr[$key]['ru_id'] = $row['user_id'];
                    $arr[$key]['store_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称
                }
            }

            cache()->forever($cache_name, $arr);
        }

        return $arr;
    }

    /**
     * 判断是否支持门店自提，返回门店id
     *
     * @param int $goods_id
     * @return mixed
     */
    public function judgeStoreGoods($goods_id = 0)
    {
        $store_goods = OfflineStore::select('id')->where('is_confirm', 1)
            ->whereHas('getStoreGoods', function ($query) use ($goods_id) {
                $query->where('goods_id', $goods_id);
            });

        $store_goods = $this->baseRepository->getToArrayGet($store_goods);
        $store_goods = $this->baseRepository->getKeyPluck($store_goods, 'id');
        $store_goods = $this->baseRepository->getArrayUnique($store_goods);

        $store_products = OfflineStore::select('id')->where('is_confirm', 1)
            ->whereHas('getStoreProducts', function ($query) use ($goods_id) {
                $query->where('goods_id', $goods_id);
            });

        $store_products = $this->baseRepository->getToArrayGet($store_products);
        $store_products = $this->baseRepository->getKeyPluck($store_products, 'id');
        $store_products = $this->baseRepository->getArrayUnique($store_products);

        $store_ids = $this->baseRepository->getArrayMerge($store_goods, $store_products);
        $store_ids = $this->baseRepository->getArrayUnique($store_ids);

        return $store_ids;
    }
}
