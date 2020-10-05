<?php

namespace App\Services\User;

use App\Libraries\Pager;
use App\Models\CollectGoods;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;

class UserCollectGoodsService
{
    protected $dscRepository;
    protected $config;
    protected $baseRepository;
    protected $goodsCommonService;
    protected $timeRepository;
    protected $merchantCommonService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 获取指定用户的收藏商品列表
     *
     * @param int $user_id
     * @param int $record_count
     * @param int $page
     * @param string $pageFunc
     * @param int $size
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getCollectionGoods($user_id = 0, $record_count = 0, $page = 1, $pageFunc = '', $size = 10, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {

        $pagerParams = [
            'total' => $record_count,
            'listRows' => $size,
            'page' => $page,
            'funName' => $pageFunc,
            'pageType' => 1
        ];

        $collection = new Pager($pagerParams);
        $pager = $collection->fpage([0, 4, 5, 6, 9]);

        $res = CollectGoods::select('rec_id', 'goods_id', 'is_attention', 'add_time')
            ->where('user_id', $user_id);

        $where = [
            'open_area_goods' => $this->config['open_area_goods'],
            'review_goods' => $this->config['review_goods'],
            'area_id' => $area_id,
            'area_city' => $area_city
        ];
        $res = $res->whereHas('getGoods', function ($query) use ($where) {
            $query = $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            $query = $this->dscRepository->getAreaLinkGoods($query, $where['area_id'], $where['area_city']);

            if ($where['review_goods'] == 1) {
                $query->where('review_status', '>', 2);
            }
        });

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getGoods',
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $res->orderBy('rec_id', 'desc');

        $start = ($page - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $goods_list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $row = $this->baseRepository->getArrayMerge($row, $row['get_goods']);

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

                $goods_list[$row['goods_id']] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $goods_list[$row['goods_id']]['rec_id'] = $row['rec_id'];
                $goods_list[$row['goods_id']]['is_attention'] = $row['is_attention'];
                $goods_list[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $goods_list[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $goods_list[$row['goods_id']]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods_list[$row['goods_id']]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $goods_list[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $goods_list[$row['goods_id']]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $goods_list[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $goods_list[$row['goods_id']]['add_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $row['add_time']);

                $goods_list[$row['goods_id']]['zconments'] = app(CommentService::class)->goodsZconments($row['goods_id']);
            }
        }

        $arr = ['goods_list' => $goods_list, 'record_count' => $record_count, 'pager' => $pager, 'size' => $size];

        return $arr;
    }

    /**
     * 获取指定用户的收藏商品列表
     *
     * @param $user_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     * @throws \Exception
     */
    public function getDefaultCollectionGoods($user_id, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {

        $res = CollectGoods::select('rec_id', 'goods_id', 'is_attention', 'add_time')
            ->where('user_id', $user_id);

        $where = [
            'open_area_goods' => $this->config['open_area_goods'],
            'review_goods' => $this->config['review_goods'],
            'area_id' => $area_id,
            'area_city' => $area_city
        ];
        $res = $res->whereHas('getGoods', function ($query) use ($where) {
            $query = $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            $query = $this->dscRepository->getAreaLinkGoods($query, $where['area_id'], $where['area_city']);

            if ($where['review_goods'] == 1) {
                $query->where('review_status', '>', 2);
            }
        });

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getGoods',
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $res->orderBy('rec_id', 'desc');

        $res = $res->take(5);

        $res = $this->baseRepository->getToArrayGet($res);

        $goods_list = [];
        if ($res) {
            foreach ($res as $row) {
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

                $row = $this->baseRepository->getArrayMerge($row, $row['get_goods']);

                $goods_list[$row['goods_id']] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $goods_list[$row['goods_id']]['rec_id'] = $row['rec_id'];
                $goods_list[$row['goods_id']]['is_attention'] = $row['is_attention'];
                $goods_list[$row['goods_id']]['goods_id'] = $row['goods_id'];

                $shop_info = $this->merchantCommonService->getShopName($row['user_id'], 3);
                $goods_list[$row['goods_id']]['shop_name'] = $shop_info['shop_name'];

                //IM or 客服
                if ($this->config['customer_service'] == 0) {
                    $ru_id = 0;
                    $shop_information = $this->merchantCommonService->getShopName($ru_id); //通过ru_id获取到店铺信息;
                } else {
                    $ru_id = $row['user_id'];
                    $shop_information = $shop_info['shop_information']; //通过ru_id获取到店铺信息;
                }

                $goods_list[$row['goods_id']]['is_IM'] = isset($shop_information['is_IM']) ? $shop_information['is_IM'] : 0; //平台是否允许商家使用"在线客服";

                if ($ru_id == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                    if ($kf_im_switch) {
                        $goods_list[$row['goods_id']]['is_dsc'] = true;
                    } else {
                        $goods_list[$row['goods_id']]['is_dsc'] = false;
                    }
                } else {
                    $goods_list[$row['goods_id']]['is_dsc'] = false;
                }

                $goods_list[$row['goods_id']]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
                $goods_list[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $goods_list[$row['goods_id']]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods_list[$row['goods_id']]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $goods_list[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $goods_list[$row['goods_id']]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                $goods_list[$row['goods_id']]['shop_url'] = $this->dscRepository->buildUri('merchants_store', ['urid' => $row['user_id']]);
                $goods_list[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            }
        }

        return $goods_list;
    }
}