<?php

namespace App\Services\Article;

use App\Models\GoodsArticle;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsCommonService;
use App\Services\User\UserCommonService;

class ArticleGoodsService
{
    protected $dscRepository;
    protected $userCommonService;
    protected $baseRepository;
    protected $goodsCommonService;
    protected $config;

    public function __construct(
        DscRepository $dscRepository,
        UserCommonService $userCommonService,
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->userCommonService = $userCommonService;
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得文章关联的商品
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getArticleRelatedGoods($where = [])
    {
        $res = GoodsArticle::where('article_id', $where['article_id']);

        $res = $res->whereHas('getGoods', function ($query) use ($where) {
            if (isset($where['review_goods']) && $where['review_goods'] == 1) {
                $query = $query->where('review_status', '>', 2);
            }

            $query = $this->dscRepository->getAreaLinkGoods($query, $where['area_id'], $where['area_city']);

            $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);
        });

        if (isset($where['uid']) && $where['uid'] > 0) {
            $rank = $this->userCommonService->getUserRankByUid($where['uid']);
            $user_rank = $rank['rank_id'];
            $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $user_rank = session('user_rank', 1);
            $user_discount = session()->has('discount') ? session('discount') / 100 : 100;
        }

        $res = $res->with([
            'getGoods',
            'getWarehouseGoods' => function ($query) use ($where) {
                $query->where('region_id', $where['warehouse_id']);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query->where('region_id', $where['area_id']);

                if (isset($where['area_pricetype']) && $where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            }
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {

                $row = $row['get_goods'] ? $this->baseRepository->getArrayMerge($row, $row['get_goods']) : $row;
                $row = $row['get_warehouse_goods'] ? $this->baseRepository->getArrayMerge($row, $row['get_warehouse_goods']) : $row;
                $row = $row['get_warehouse_area_goods'] ? $this->baseRepository->getArrayMerge($row, $row['get_warehouse_area_goods']) : $row;
                $row = $row['get_member_price'] ? $this->baseRepository->getArrayMerge($row, $row['get_member_price']) : $row;

                $row = $this->baseRepository->getArrayExcept($row, ['get_goods', 'get_warehouse_goods', 'get_warehouse_area_goods', 'get_member_price']);

                $price = [
                    'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                    'user_price' => isset($row['user_price']) ? $row['user_price'] : 0,
                    'percentage' => isset($row['percentage']) ? $row['percentage'] : 0,
                    'warehouse_price' => isset($row['warehouse_price']) ? $row['warehouse_price'] : 0,
                    'region_price' => isset($row['region_price']) ? $row['region_price'] : 0,
                    'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                    'warehouse_promote_price' => isset($row['warehouse_promote_price']) ? $row['warehouse_promote_price'] : 0,
                    'region_promote_price' => isset($row['region_promote_price']) ? $row['region_promote_price'] : 0,
                    'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                    'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                    'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                    'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
                ];

                $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount / 100, $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                $row['short_name'] = $this->config['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $row['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $row['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $row['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

                if ($row['promote_price'] > 0) {
                    $row['promote_price'] = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    $row['formated_promote_price'] = $this->dscRepository->getPriceFormat($row['promote_price']);
                } else {
                    $row['promote_price'] = 0;
                }
                $arr[] = $row;
            }
        }

        return $arr;
    }
}