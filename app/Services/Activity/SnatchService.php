<?php

namespace App\Services\Activity;

use App\Libraries\Pager;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\SellerShopinfo;
use App\Models\SnatchLog;
use App\Models\Users;
use App\Repositories\Activity\ActivityRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;

class SnatchService
{
    protected $baseRepository;
    protected $goodsCommonService;
    protected $merchantCommonService;
    protected $config;
    protected $dscRepository;
    protected $timeRepository;
    protected $activityRepository;

    public function __construct(
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        ActivityRepository $activityRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
        $this->activityRepository = $activityRepository;
    }

    /**
     * 获得指定分类下的推荐商品
     *
     * @param array $where
     * @param int $num
     * @return mixed
     */
    public function getExchangeRecommendGoods($where = [], $num = 11)
    {
        $time = $this->timeRepository->getGmTime();

        $res = GoodsActivity::where('review_status', 3);

        //推荐类型，可以是 best, new, hot, promote
        if (isset($where['act_type'])) {
            $res = $res->where('act_type', $where['act_type']);
        }

        if (isset($where['time'])) {
            $res = $res->where('start_time', '<=', $where['time'])
                ->where('end_time', '>=', $where['time']);
        }

        if ($where['type']) {
            switch ($where['type']) {
                case 'best':
                    $res = $res->where('is_best', 1);
                    break;
                case 'new':
                    $res = $res->where('is_new', 1);
                    break;
                case 'hot':
                    $res = $res->where('is_hot', 1);
                    break;
            }
        }

        $where['area_pricetype'] = $this->config['area_pricetype'];

        $user_rank = session('user_rank');
        $res = $res->with([
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
            'getGoods'
        ]);

        $res = $res->withCount([
            'getSnatchLog as price_list_count',
            'getSnatchLog as count' => function ($query) {
                $query->whereHas('getUsers');
            }
        ]);

        $order_type = $this->config['recommend_order'];
        if ($order_type == 0) {
            $res = $res->orderBy("act_id", "desc");
        } else {
            $res = $res->orderByRaw("RAND()");
        }

        $res = $res->take($num);

        $res = $this->baseRepository->getToArrayGet($res);

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

                $row['id'] = $row['goods_id'];
                $row['name'] = $row['goods_name'];
                $row['brief'] = $row['goods_brief'];
                $row['short_name'] = $this->config['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $row['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $row['url'] = $this->dscRepository->buildUri('snatch', ['sid' => $row['act_id']]);

                $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $row['formated_shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $row['formated_shop_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : $row['formated_shop_price'];
                $row['formated_market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);

                $ext_info = unserialize($row['ext_info']);
                $snatch_info = array_merge($row, $ext_info);
                $row['auction'] = $snatch_info;
                $row['status_no'] = $this->activityRepository->getAuctionStatus($snatch_info, $time);
                $row['end_time_date'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $row['end_time']);
                $row['short_style_name'] = $this->goodsCommonService->addStyle($row['short_name'], $row['goods_name_style']);

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 取得用户对当前活动的所出过的价格
     *
     * @param $id
     * @return array
     */
    public function getMyPrice($id)
    {
        $user_id = session('user_id', 0);

        $my_only_price = [];
        $my_price_time = [];
        $pay_points = 0;
        $bid_price = [];
        if (!empty(session('user_id'))) {
            /* 取得用户所有价格 */
            $my_price_time = SnatchLog::where('snatch_id', $id)
                ->where('user_id', $user_id)
                ->orderBy('bid_time')
                ->get();

            $my_price_time = $my_price_time ? $my_price_time->toArray() : [];

            if ($my_price_time) {
                $my_price = collect($my_price_time)->pluck('bid_price');
                $my_price = $my_price->all();

                /* 取得用户唯一价格 */
                $my_only_price = SnatchLog::selectRaw("bid_price , count(*) AS num")->where('snatch_id', $id)->whereIn('bid_price', $my_price)->groupBy('bid_price')->having('num', 1);
                $my_only_price = $this->baseRepository->getToArrayGet($my_only_price);
                $bidprice = $this->baseRepository->getKeyPluck($my_only_price, 'bid_price');

                $price_info = [
                    'bid_price' => $bidprice,
                    'num' => $my_only_price ? count($my_only_price) : 0
                ];

                $my_only_price = $price_info;
            }

            $user_info = Users::select('user_name', 'pay_points')->where('user_id', $user_id)->first();
            $user_info = $user_info ? $user_info->toArray() : [];

            for ($i = 0, $count = count($my_price_time); $i < $count; $i++) {
                $bid_price[] = [
                    'price' => $this->dscRepository->getPriceFormat($my_price_time[$i]['bid_price'], false),
                    'bid_price' => $this->dscRepository->getPriceFormat($my_price_time[$i]['bid_price'], false),
                    'user_name' => $user_info ? $user_info['user_name'] : '',
                    'bid_date' => $this->timeRepository->getLocalDate('Y-m-d H:i:s', $my_price_time[$i]['bid_time']),
                    'is_only' => in_array($my_price_time[$i]['bid_price'], $my_only_price)
                ];
            }

            $pay_points = $user_info ? $user_info['pay_points'] : 0;
            $pay_points = $pay_points . $this->config['integral_name'];
        }

        /* 活动结束时间 */
        $end_time = GoodsActivity::where('act_id', $id)
            ->where('review_status', 3)
            ->where('act_type', GAT_SNATCH)
            ->value('end_time');
        $end_time = $end_time ? $end_time : 0;

        $time = $this->timeRepository->getGmTime();
        $my_price_time = [
            'pay_points' => $pay_points,
            'bid_price' => $bid_price,
            'bid_price_count' => count($bid_price),
            'is_end' => $time > $end_time
        ];

        return $my_price_time;
    }

    /**
     * 取得当前活动的前n个出价
     *
     * @param $id
     * @return mixed
     */
    public function getPriceList($id)
    {
        $res = SnatchLog::where('snatch_id', $id)->orderBy('log_id', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $user_name = Users::where('user_id', $row['user_id'])->value('user_name');

                $row['user_name'] = $user_name ? setAnonymous($user_name) : ''; //处理用户名 by wu
                $row['bid_price'] = $this->dscRepository->getPriceFormat($row['bid_price'], false);
                $row['bid_date'] = $this->timeRepository->getLocalDate("Y-m-d H:i", $row['bid_time']);

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 取的最近的几次活动
     *
     * @param string $keywords
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getSnatchList($keywords = '', $size = 15, $page = 1, $sort = 'goods_id', $order = 'desc', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $now = $this->timeRepository->getGmTime();

        $res = GoodsActivity::select('goods_id', 'act_name as snatch_name', 'act_id as snatch_id', 'ext_info', 'start_time', 'end_time')
            ->where('act_type', GAT_SNATCH)
            ->where('review_status', 3)
            ->where('start_time', '<=', $now);

        if ($keywords) {
            $res = $res->where('act_name', 'like', '%' . $keywords . '%');
        }

        $res = $res->whereHas('getGoods', function ($query) use ($keywords) {
            if ($keywords) {
                $query = $query->where('goods_name', 'like', "%" . $keywords . "%");
            }

            $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);
        });

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
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
            },
            'getGoods'
        ]);

        $res = $res->withCount([
            'getSnatchLog as price_list_count'
        ]);

        if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'load_more_goods') {
            $start = isset($_REQUEST['goods_num']) ? intval($_REQUEST['goods_num']) : 0;
        } else {
            $start = ($page - 1) * $size;
        }

        $res = $res->orderBy($sort, $order);

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $snatch_list = [];
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

                $overtime = $row['end_time'] > $now ? 0 : 1;

                $ext_info = unserialize($row['ext_info']);
                $snatchInfo = array_merge($row, $ext_info);
                $row['start_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['start_time']);
                $row['end_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['end_time']);

                $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $shop_price = ($promote_price > 0) ? $promote_price : $row['shop_price'];

                $snatchInfo['max_price'] = $this->dscRepository->getPriceFormat($snatchInfo['max_price']);
                $snatchInfo['end_time_date'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $snatchInfo['end_time']);

                $snatch_list[] = [
                    'snatch_id' => $row['snatch_id'],
                    'snatch_name' => $row['snatch_name'],
                    'snatch' => $snatchInfo,
                    'start_time' => $row['start_time'],
                    'max_price' => $snatchInfo['max_price'], //
                    'end_time' => $row['end_time'],
                    'current_time' => $this->timeRepository->getLocalDate('Y-m-d H:i:s', $now),
                    'overtime' => $overtime,
                    'formated_market_price' => $this->dscRepository->getPriceFormat($row['market_price']),
                    'formated_shop_price' => $this->dscRepository->getPriceFormat($shop_price),
                    'goods_thumb' => $this->dscRepository->getImagePath($row['goods_thumb']),
                    'price_list_count' => $row['price_list_count'], // 围观次数
                    'url' => $this->dscRepository->buildUri('snatch', ['sid' => $row['snatch_id']])
                ];
            }
        }

        return $snatch_list;
    }

    public function getSnatchCount($keywords = '')
    {
        $now = $this->timeRepository->getGmTime();

        $res = GoodsActivity::where('act_type', GAT_SNATCH)
            ->where('review_status', 3)
            ->where('start_time', $now);

        if ($keywords) {
            $res = $res->where('act_name', 'like', '%' . $keywords . '%');
        }

        $res = $res->whereHas('getGoods', function ($query) use ($keywords) {
            if ($keywords) {
                $query = $query->where('goods_name', 'like', "%" . $keywords . "%");
            }

            $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);
        });

        $count = $res->count();

        return $count;
    }

    /**
     * 取得当前活动信息
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getSnatch($where = [])
    {
        $time = $this->timeRepository->getGmTime();

        $goods = Goods::where('is_delete', 0)
            ->whereHas('getGoodsActivity', function ($query) use ($where) {
                $query->where('act_id', $where['act_id']);
            });

        $where['area_pricetype'] = $this->config['area_pricetype'];

        $user_rank = session('user_rank');
        $goods = $goods->with([
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
            'getGoodsActivity' => function ($query) use ($where) {
                $query->select('goods_id', 'act_name as snatch_name', 'act_id', 'product_id', 'start_time', 'end_time', 'ext_info', 'act_desc as desc', 'act_promise', 'act_ensure')
                    ->where('act_id', $where['act_id']);
            }
        ]);

        $goods = $this->baseRepository->getToArrayFirst($goods);

        if ($goods) {
            $price = [
                'model_price' => isset($goods['model_price']) ? $goods['model_price'] : 0,
                'user_price' => isset($goods['get_member_price']['user_price']) ? $goods['get_member_price']['user_price'] : 0,
                'percentage' => isset($goods['get_member_price']['percentage']) ? $goods['get_member_price']['percentage'] : 0,
                'warehouse_price' => isset($goods['get_warehouse_goods']['warehouse_price']) ? $goods['get_warehouse_goods']['warehouse_price'] : 0,
                'region_price' => isset($goods['get_warehouse_area_goods']['region_price']) ? $goods['get_warehouse_area_goods']['region_price'] : 0,
                'shop_price' => isset($goods['shop_price']) ? $goods['shop_price'] : 0,
                'warehouse_promote_price' => isset($goods['get_warehouse_goods']['warehouse_promote_price']) ? $goods['get_warehouse_goods']['warehouse_promote_price'] : 0,
                'region_promote_price' => isset($goods['get_warehouse_area_goods']['region_promote_price']) ? $goods['get_warehouse_area_goods']['region_promote_price'] : 0,
                'promote_price' => isset($goods['promote_price']) ? $goods['promote_price'] : 0,
                'wg_number' => isset($goods['get_warehouse_goods']['region_number']) ? $goods['get_warehouse_goods']['region_number'] : 0,
                'wag_number' => isset($goods['get_warehouse_area_goods']['region_number']) ? $goods['get_warehouse_area_goods']['region_number'] : 0,
                'goods_number' => isset($goods['goods_number']) ? $goods['goods_number'] : 0
            ];

            $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $goods);

            $goods['shop_price'] = $price['shop_price'];
            $goods['promote_price'] = $price['promote_price'];
            $goods['goods_number'] = $price['goods_number'];

            $goods['goods_desc_old'] = $goods['goods_desc'];

            $goods = $goods['get_goods_activity'] ? array_merge($goods, $goods['get_goods_activity']) : $goods;

            $promote_price = $this->goodsCommonService->getBargainPrice($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
            $goods['formated_market_price'] = $this->dscRepository->getPriceFormat($goods['market_price']);
            $goods['formated_shop_price'] = $this->dscRepository->getPriceFormat($goods['shop_price']);
            $goods['formated_promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
            $goods['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
            $goods['goods_img'] = $this->dscRepository->getImagePath($goods['goods_img']);
            $goods['url'] = $this->dscRepository->buildUri('goods', ['gid' => $goods['goods_id']], $goods['goods_name']);
            $goods['start_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $goods['start_time']);

            $info = unserialize($goods['ext_info']);
            if ($info) {
                foreach ($info as $key => $val) {
                    $goods[$key] = $val;
                }
                $goods['is_end'] = $time > $goods['end_time'];
                $goods['formated_start_price'] = $this->dscRepository->getPriceFormat($goods['start_price']);
                $goods['formated_end_price'] = $this->dscRepository->getPriceFormat($goods['end_price']);
                $goods['formated_max_price'] = $this->dscRepository->getPriceFormat($goods['max_price']);
            }
            /* 将结束日期格式化为格林威治标准时间时间戳 */
            $goods['gmt_end_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $goods['end_time']);
            $goods['end_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $goods['end_time']);
            $goods['snatch_time'] = sprintf($GLOBALS['_LANG']['snatch_start_time'], $goods['start_time'], $goods['end_time']);

            $goods['rz_shopName'] = $this->merchantCommonService->getShopName($goods['user_id'], 1); //店铺名称

            $build_uri = [
                'urid' => $goods['user_id'],
                'append' => $goods['rz_shopName']
            ];

            $domain_url = $this->merchantCommonService->getSellerDomainUrl($goods['user_id'], $build_uri);
            $goods['store_url'] = $domain_url['domain_name'];

            $basic_info = SellerShopinfo::where('ru_id', $goods['user_id']);
            $basic_info = $this->baseRepository->getToArrayFirst($basic_info);

            $goods['shopinfo'] = $basic_info;
            $goods['shopinfo']['brand_thumb'] = str_replace(['../'], '', $basic_info['brand_thumb']);


            if ($basic_info) {
                $goods['province'] = $basic_info['province'];
                $goods['city'] = $basic_info['city'];
                $goods['kf_type'] = $basic_info['kf_type'];
                $goods['[kf_ww'] = $basic_info['kf_ww'];
                $goods['kf_qq'] = $basic_info['kf_qq'];

                $chat = $this->dscRepository->chatQq($basic_info);
                $goods['kf_qq'] = $chat['kf_qq'];
                $goods['kf_ww'] = $chat['kf_ww'];

                $goods['shop_name'] = $goods['rz_shopName'];
            }

            return $goods;
        } else {
            return [];
        }
    }

    /**
     * 获取会员夺宝奇兵的数量
     * $user_id    出价会员ID
     * $type        活动是否结束
     */
    public function getAllSnatch($user_id, $snatch = '')
    {
        $where = [
            'user_id' => $user_id,
            'snatch' => $snatch
        ];

        $snatch_count = GoodsActivity::searchKeyword($snatch)
            ->whereHas("getSnatchLog", function ($query) use ($where) {
                if ($where['snatch']) {
                    $query = $query->searchKeyword($where['snatch']);
                }

                $query->where('user_id', $where['user_id']);
            });

        $snatch_count = $snatch_count->count();

        return $snatch_count;
    }

    /**
     * 获取会员夺宝奇兵列表
     *
     * @param $user_id
     * @param $record_count
     * @param $page
     * @param string $list
     * @param int $size
     * @return array
     */
    public function getSnatchGoodsList($user_id, $record_count, $page, $list = '', $size = 10)
    {
        $time = $this->timeRepository->getGmTime();

        if ($list && is_object($list)) {
            $idTxt = $list->idTxt;
            $keyword = $list->keyword;
            $action = $list->action;
            $type = $list->type;
            $status_keyword = isset($list->status_keyword) ? $list->status_keyword : '';
            $date_keyword = isset($list->date_keyword) ? $list->date_keyword : '';

            $id = '"';
            $id .= $user_id . "=";
            $id .= "idTxt@" . $idTxt . "|";
            $id .= "keyword@" . $keyword . "|";
            $id .= "action@" . $action . "|";
            $id .= "type@" . $type . "|";

            if ($status_keyword) {
                $id .= "status_keyword@" . $status_keyword . "|";
            }

            if ($date_keyword) {
                $id .= "date_keyword@" . $date_keyword;
            }

            $substr = substr($id, -1);
            if ($substr == "|") {
                $id = substr($id, 0, -1);
            }

            $id .= '"';
        } else {
            $id = $user_id;
        }

        $config = ['header' => $GLOBALS['_LANG']['pager_2'], "prev" => "<i><<</i>" . $GLOBALS['_LANG']['page_prev'], "next" => "" . $GLOBALS['_LANG']['page_next'] . "<i>>></i>", "first" => $GLOBALS['_LANG']['page_first'], "last" => $GLOBALS['_LANG']['page_last']];

        $pagerParams = [
            'total' => $record_count,
            'listRows' => $size,
            'id' => $id,
            'page' => $page,
            'funName' => 'user_snatch_gotoPage',
            'pageType' => 1,
            'config_zn' => $config
        ];
        $user_snatch = new Pager($pagerParams);
        $pager = $user_snatch->fpage([0, 4, 5, 6, 9]);

        /* 拍卖活动列表 */
        $where = [
            'user_id' => $user_id,
            'snatch' => $list
        ];

        /* 拍卖活动列表 */
        $res = GoodsActivity::searchKeyword($where['snatch'])
            ->whereHas("getSnatchLog", function ($query) use ($where) {
                if ($where['snatch']) {
                    $query = $query->searchKeyword($where['snatch']);
                }

                $query->where('user_id', $where['user_id']);
            });

        $res = $res->with([
            'getSnatchLog' => function ($query) {
                $query->select('snatch_id', 'bid_time', 'bid_price');
            },
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            },
        ]);

        $res = $res->orderBy('act_id', 'desc');

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];
        $list = [];
        if ($res) {

            foreach ($res as $row) {
                $row = $this->baseRepository->getArrayMerge($row, $row['get_snatch_log']);
                $row = $this->baseRepository->getArrayMerge($row, $row['get_goods']);

                $arr['status_no'] = $this->activityRepository->getAuctionStatus($row, $time);
                $arr['act_id'] = $row['act_id'];
                $arr['act_name'] = $row['act_name'];
                $arr['goods_thumb'] = $row['goods_thumb'];
                $arr['goods_name'] = $row['goods_name'];
                $arr['start_time'] = $row['start_time'];
                $arr['end_time'] = $row['end_time'];
                $arr['bid_time'] = isset($row['bid_time']) ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['bid_time']) : '';
                $arr['bid_price'] = isset($row['bid_price']) ? $this->dscRepository->getPriceFormat($row['bid_price']) : $this->dscRepository->getPriceFormat(0);
                $arr['status'] = $GLOBALS['_LANG']['auction_staues'][$arr['status_no']];
                $list[] = $arr;
            }
        }

        $snatch_list = ['snatch_list' => $list, 'pager' => $pager, 'record_count' => $record_count];
        return $snatch_list;
    }
}
