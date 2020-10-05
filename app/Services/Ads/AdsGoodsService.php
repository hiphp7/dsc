<?php

namespace App\Services\Ads;

use App\Models\Goods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsCommonService;

class AdsGoodsService
{
    protected $dscRepository;
    protected $config;
    protected $baseRepository;
    protected $goodsCommonService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
    }

    /**
     * 广告位促销商品
     *
     * @param string $goods_name
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getGoodsAdPromote($goods_name = '', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $cache_name = "get_goods_ad_promote_" . $goods_name . '_' . $warehouse_id . '_' . $area_id . '_' . $area_city;
        $row = cache($cache_name);

        if (is_null($row)) {
            $goods_name = !empty($goods_name) ? addslashes($goods_name) : '';

            $row = Goods::where('goods_name', $goods_name)
                ->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            if ($this->config['review_goods']) {
                $row = $row->where('review_status', '>', 2);
            }

            $row = $this->dscRepository->getAreaLinkGoods($row, $area_id, $area_city);

            $where = [
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                'area_pricetype' => $this->config['area_pricetype']
            ];

            $user_rank = session('user_rank');
            $row = $row->with([
                'getMemberPrice' => function ($query) use ($user_rank) {
                    $query->where('user_rank', $user_rank);
                },
                'getWarehouseGoods' => function ($query) use ($where) {
                    $query->where('region_id', $where['warehouse_id']);
                },
                'getWarehouseAreaGoods' => function ($query) use ($where) {
                    $query = $query->where('region_id', $where['area_id']);

                    if ($where['area_pricetype'] == 1) {
                        $query->where('city_id', $where['area_city']);
                    }
                },
                'getBrand'
            ]);

            $row = $row->orderBy('sort_order', 'ASC')->orderBy('last_update', 'desc');
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row) {
                $price = [
                    'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                    'user_price' => isset($row['get_member_price']['user_price']) ? $row['get_member_price']['user_price'] : 0,
                    'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                    'warehouse_price' => isset($row['get_warehouse_goods']['warehouse_price']) ? $row['get_warehouse_goods']['warehouse_price'] : 0,
                    'region_price' => isset($row['get_warehouse_area_goods']['region_price']) ? $row['get_warehouse_area_goods']['region_price'] : 0,
                    'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                    'warehouse_promote_price' => isset($row['get_warehouse_goods']['warehouse_promote_price']) ? $row['get_warehouse_goods']['warehouse_promote_price'] : 0,
                    'region_promote_price' => isset($row['get_warehouse_area_goods']['region_promote_price']) ? $row['get_warehouse_area_goods']['region_promote_price'] : 0,
                    'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                    'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                    'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                    'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
                ];

                $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    $row['promote_price'] = $promote_price > 0 ? $this->dscRepository->getPriceFormat($promote_price) : '';
                } else {
                    $row['promote_price'] = '';
                }

                $row['brand_name'] = $row['get_brand'] ? $row['get_brand']['brand_name'] : '';
                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);

                $row['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $row['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $row['url'] = route('goods', ['id' => $row['goods_id']]);
            }

            cache()->forever($cache_name, $row);
        }

        return $row;
    }
}
