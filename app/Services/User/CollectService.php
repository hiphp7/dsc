<?php

namespace App\Services\User;

use App\Models\CollectGoods;
use App\Models\CollectStore;
use App\Models\Coupons;
use App\Models\SellerShopinfo;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\CouponsService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 收藏
 * Class Collect
 * @package App\Services
 */
class CollectService
{
    protected $config;
    protected $goodsService;
    protected $timeRepository;
    protected $areaService;
    protected $dscRepository;
    protected $userRankService;
    protected $merchantCommonService;
    protected $goodsCommonService;
    protected $couponsService;

    public function __construct(
        TimeRepository $timeRepository,
        CouponsService $couponsService,
        AreaService $areaService,
        DscRepository $dscRepository,
        UserRankService $userRankService,
        MerchantCommonService $merchantCommonService,
        GoodsCommonService $goodsCommonService
    )
    {
        $this->couponsService = $couponsService;
        $this->timeRepository = $timeRepository;
        $this->areaService = $areaService;
        $this->dscRepository = $dscRepository;
        $this->userRankService = $userRankService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsCommonService = $goodsCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }


    /**
     * 收藏店铺列表
     *
     * @param int $user_id
     * @param int $page
     * @param int $size
     * @param int $region_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getUserShopList($user_id = 0, $page = 1, $size = 10, $region_id = 0, $area_id = 0, $area_city = 0)
    {
        $begin = $page > 0 ? ($page - 1) * $size : 0;
        $res = CollectStore::from('collect_store as c')
            ->select('m.shoprz_brandName', 'm.shopNameSuffix', 'm.shop_id', 's.shop_logo', 's.logo_thumb', 'c.rec_id', 'c.ru_id', 'c.add_time', 's.kf_type', 's.kf_ww', 's.kf_qq', 'brand_thumb')
            ->leftjoin('seller_shopinfo as s', 'c.ru_id', '=', 's.ru_id')
            ->leftjoin('merchants_shop_information as m', 's.ru_id', '=', 'm.user_id')
            ->where("c.user_id", $user_id)
            ->orderBy('m.shop_id', 'desc')
            ->offset($begin)
            ->limit($size)
            ->get();

        $res = $res ? $res->toArray() : [];

        $store_list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $store_list[$key]['collect_number'] = CollectStore::where('ru_id', $row['ru_id'])->where('user_id', $user_id)->count();
                $store_list[$key]['rec_id'] = $row['rec_id'];
                $store_list[$key]['shoprz_brandName'] = $row['shoprz_brandName'];
                $store_list[$key]['shopNameSuffix'] = $row['shopNameSuffix'];
                //取消关注链接
                $store_list[$key]['cancel_collect_shop'] = route('api.collect.collectshop', ['rec_id' => $row['rec_id']]);
                $store_list[$key]['shop_id'] = $row['ru_id'];
                $store_list[$key]['store_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1); //店铺名称
                $store_list[$key]['shop_bg_logo'] = $this->dscRepository->getImagePath(str_replace('../', '', $row['shop_logo']));
                $store_list[$key]['shop_logo'] = $this->dscRepository->getImagePath(str_replace('../', '', $row['logo_thumb']));
                $store_list[$key]['count_store'] = CollectStore::where('ru_id', $row['ru_id'])->count();
                $store_list[$key]['add_time'] = $this->timeRepository->getLocalDate("Y-m-d", $row['add_time']);
                $store_list[$key]['kf_type'] = $row['kf_type'];
                $store_list[$key]['kf_ww'] = $row['kf_ww'];
                $store_list[$key]['kf_qq'] = $row['kf_qq'];
                $store_list[$key]['ru_id'] = $row['ru_id'];
                $store_list[$key]['brand_thumb'] = $this->dscRepository->getImagePath($row['brand_thumb']);
                $store_list[$key]['url'] = route('api.store.storedetail', ['id' => $row['ru_id']]);
            }
        }

        return $store_list;
    }

    /**
     * 收藏/移除收藏 店铺
     *
     * @param $shop_id
     * @param $user_id
     * @return int
     */
    public function collectShop($shop_id, $user_id)
    {
        if ($shop_id && $user_id) {
            $res = CollectStore::select('user_id', 'rec_id')
                ->where('ru_id', $shop_id)
                ->where('user_id', $user_id)
                ->count();
            //未收藏便增加 已收藏便删除
            if ($res > 0) {
                CollectStore::where('ru_id', $shop_id)
                    ->where('user_id', $user_id)
                    ->delete();
                //已取消收藏
                return 1;
            } else {
                $time = $this->timeRepository->getGmTime();
                CollectStore::insert([
                    'user_id' => $user_id,
                    'ru_id' => $shop_id,
                    'add_time' => $time,
                    'is_attention' => 1
                ]);
                $cou_id = Coupons::where('cou_type', VOUCHER_SHOP_CONLLENT)->where('ru_id', $shop_id)->value('cou_id');

                if (isset($cou_id) && !empty($cou_id)) {
                    $this->couponsService->Couponsreceive($cou_id, $user_id);
                }
                //已收藏
                return 2;
            }
        }
    }


    /**
     * 关注/移除关注 商品
     *
     * @param $goods_id
     * @param $user_id
     * @return int
     */
    public function collectGoods($goods_id = 0, $user_id)
    {
        if ($goods_id && $user_id) {
            $res = collectGoods::select('user_id', 'rec_id')
                ->where('goods_id', $goods_id)
                ->where('user_id', $user_id)
                ->count();
            //未关注便增加 已关注便删除
            if ($res > 0) {
                collectGoods::where('goods_id', $goods_id)
                    ->where('user_id', $user_id)
                    ->delete();
                //已取消关注
                return 1;
            } else {
                $time = $this->timeRepository->getGmTime();
                collectGoods::insert([
                    'user_id' => $user_id,
                    'goods_id' => $goods_id,
                    'add_time' => $time,
                    'is_attention' => 1
                ]);
                //已关注
                return 2;
            }
        } else {
            return 3;//无效参数
        }
    }

    /**
     * 关注商品列表
     *
     * @param int $user_id
     * @param int $page
     * @param int $size
     * @param int $province_id
     * @param int $city_id
     * @return array
     */
    public function getUserGoodsList($user_id = 0, $page = 1, $size = 10, $province_id = 0, $city_id = 0)
    {
        //用户的等级和折扣
        $user_info = $this->userRankService->getUserRankInfo($user_id);
        $user_rank = $user_info['user_rank'];
        $user_discount = $user_info['discount'];

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 地区ID
         */
        $areaOther = [
            'province_id' => $province_id,
            'city_id' => $city_id,
        ];
        $areaInfo = $this->areaService->getAreaInfo($areaOther);

        $warehouse_id = $areaInfo['area']['warehouse_id'];
        $area_id = $areaInfo['area']['area_id'];
        $area_city = $areaInfo['area']['city_id'];

        $res = CollectGoods::select('rec_id', 'goods_id', 'is_attention', 'add_time', 'user_id')
            ->where('user_id', $user_id);

        $where = [
            'area_pricetype' => $this->config['area_pricetype'],
            'open_area_goods' => $this->config['open_area_goods'],
            'review_goods' => $this->config['review_goods'],
            'area_id' => $area_id,
            'area_city' => $area_city
        ];
        $res = $res->whereHas('getGoods', function ($query) use ($where) {
            $query = $query->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            if ($where['review_goods'] == 1) {
                $query = $query->where('review_status', '>', 2);
            }

            $this->dscRepository->getAreaLinkGoods($query, $where['area_id'], $where['area_city']);
        });

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

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $goods_list = [];
        if ($res) {
            foreach ($res as $key => $row) {
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

                $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount, $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                if ($row && $row['get_goods']) {
                    $row = array_merge($row, $row['get_goods']);
                } elseif (empty($row) && $row['get_goods']) {
                    $row = $row['get_goods'];
                }

                //$goods_list[$key] = $row;
                $goods_list[$key]['goods_number'] = $row['goods_number'];

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $goods_list[$key]['on_sale'] = $row['is_on_sale'];
                //商品未审核，展示状态已下架
                if ($row['review_status'] <= 2) {
                    $goods_list[$key]['on_sale'] = 0;
                }
                $goods_list[$key]['rec_id'] = $row['rec_id'];
                $goods_list[$key]['is_attention'] = $row['is_attention'];
                $goods_list[$key]['goods_id'] = $row['goods_id'];

                $shop_info = $this->merchantCommonService->getShopName($row['user_id'], 3);

                $goods_list[$key]['shop_name'] = $shop_info['shop_name'];

                //IM or 客服
                if ($this->config['customer_service'] == 0) {
                    $ru_id = 0;
                    $shop_information = $this->merchantCommonService->getShopName($ru_id); //通过ru_id获取到店铺信息;
                } else {
                    $ru_id = $row['user_id'];
                    $shop_information = $shop_info['shop_information']; //通过ru_id获取到店铺信息;
                }

                $goods_list[$key]['is_IM'] = isset($shop_information['is_IM']) ? $shop_information['is_IM'] : 0; //平台是否允许商家使用"在线客服";

                if ($ru_id == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                    if ($kf_im_switch) {
                        $goods_list[$key]['is_dsc'] = true;
                    } else {
                        $goods_list[$key]['is_dsc'] = false;
                    }
                } else {
                    $goods_list[$key]['is_dsc'] = false;
                }

                $goods_list[$key]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
                $goods_list[$key]['goods_name'] = $row['goods_name'];
                $goods_list[$key]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $goods_list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $goods_list[$key]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $store_list[$key]['cancel_collect_goods'] = route('api.collect.collectgoods', ['rec_id' => $row['rec_id']]);
                $goods_list[$key]['url'] = dsc_url('/#/goods/' . $row['goods_id']);
                $goods_list[$key]['app_page'] = config('route.goods.detail') . $row['goods_id'];
                $goods_list[$key]['shop_url'] = dsc_url('/#/shopHome/' . $row['user_id']);
                $goods_list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            }
        }

        return $goods_list;
    }
}
