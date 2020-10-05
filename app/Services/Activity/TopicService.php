<?php

namespace App\Services\Activity;

use App\Models\Goods;
use App\Models\Topic;
use App\Models\TouchPageView;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsCommonService;
use App\Services\User\UserCommonService;

/**
 * 专题
 *
 * Class TopicService
 * @package App\Services\Activity
 */
class TopicService
{
    protected $config;
    protected $timeRepository;
    protected $userCommonService;
    protected $dscRepository;
    protected $goodsCommonService;

    public function __construct(
        TimeRepository $timeRepository,
        UserCommonService $userCommonService,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->userCommonService = $userCommonService;
        $this->dscRepository = $dscRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 专题列表
     *
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getTopicList($page = 1, $size = 10)
    {
        $time = $this->timeRepository->getGmTime();

        $begin = ($page - 1) * $size;

        $topic = TouchPageView::select('id', 'title', 'page_id', 'description', 'thumb_pic')
            ->where('type', 'topic');

        $topic = $topic->orderBy('update_at', 'desc')
            ->offset($begin)
            ->limit($size)
            ->get();

        $topic = $topic ? $topic->toArray() : [];

        $arr = [];
        if ($topic) {
            foreach ($topic as $k => $v) {
                if ($v['page_id'] > 0) {
                    $res = Topic::select('topic_id')
                        ->where('topic_id', $v['page_id'])
                        ->where('start_time', '<=', $time)
                        ->where('end_time', '>=', $time)
                        ->first();
                    $pctopic = $res ? $res->toArray() : [];
                    if ($pctopic) {
                        $arr[$k]['topic_img'] = $this->dscRepository->getImagePath($v['thumb_pic']);
                        $arr[$k]['topic_id'] = $v['id'];
                        $arr[$k]['title'] = $v['title'];
                    }
                } else {
                    $arr[$k]['topic_img'] = $this->dscRepository->getImagePath($v['thumb_pic']);
                    $arr[$k]['topic_id'] = $v['id'];
                    $arr[$k]['title'] = $v['title'];
                }
            }
        }

        $arr = collect($arr)->values()->all();

        return $arr;
    }

    /**
     * 专题详情加商品列表
     *
     * @param $uid
     * @param int $topic_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getDetail($uid, $topic_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $nowtime = $this->timeRepository->getGmTime();

        $topic = Topic::where('topic_id', $topic_id)
            ->where('start_time', '<=', $nowtime)
            ->where('end_time', '>=', $nowtime)
            ->first();

        $topic = $topic ? $topic->toArray() : [];

        if ($topic) {
            $topic['topic_img'] = $this->dscRepository->getImagePath($topic['topic_img']);

            $topic_data = [];
            if (isset($topic['data']) && !empty($topic['data'])) {
                $topic['data'] = addcslashes($topic['data'], "'");
                $topic_data = unserialize($topic["data"]);
            }
            $topic_data = $topic_data ? collect($topic_data)->toArray() : [];

            $goods_id = [];
            if ($topic_data) {
                foreach ($topic_data as $key => $value) {
                    foreach ($value as $k => $val) {
                        $opt = explode('|', $val);
                        $topic_data[$key][$k] = $opt[1];
                        $goods_id[] = $opt[1];
                    }
                }
            }

            if ($goods_id) {

                $where = [
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city,
                    'area_pricetype' => $this->config['area_pricetype']
                ];

                $model = Goods::where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('is_delete', 0)
                    ->whereIn('goods_id', $goods_id);

                if ($this->config['review_goods']) {
                    $model = $model->where('review_status', '>', 2);
                }

                $rank = $this->userCommonService->getUserRankByUid($uid);
                if ($rank) {
                    $user_rank = $rank['rank_id'];
                    $discount = $rank['discount'];
                } else {
                    $user_rank = 1;
                    $discount = 1;
                }

                $model = $model->with([
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
                    }
                ]);

                $goods_list = $model->orderBy('sort_order', 'ASC')
                    ->orderBy('goods_id', 'DESC')
                    ->get();

                $goods_list = $goods_list ? $goods_list->toArray() : [];

                $sort_goods_arr = [];
                if ($goods_list) {
                    foreach ($goods_list as $key => $val) {
                        $price = [
                            'model_price' => isset($val['model_price']) ? $val['model_price'] : 0,
                            'user_price' => isset($val['get_member_price']['user_price']) ? $val['get_member_price']['user_price'] : 0,
                            'percentage' => isset($val['get_member_price']['percentage']) ? $val['get_member_price']['percentage'] : 0,
                            'warehouse_price' => isset($val['get_warehouse_goods']['warehouse_price']) ? $val['get_warehouse_goods']['warehouse_price'] : 0,
                            'region_price' => isset($val['get_warehouse_area_goods']['region_price']) ? $val['get_warehouse_area_goods']['region_price'] : 0,
                            'shop_price' => isset($val['shop_price']) ? $val['shop_price'] : 0,
                            'warehouse_promote_price' => isset($val['get_warehouse_goods']['warehouse_promote_price']) ? $val['get_warehouse_goods']['warehouse_promote_price'] : 0,
                            'region_promote_price' => isset($val['get_warehouse_area_goods']['region_promote_price']) ? $val['get_warehouse_area_goods']['region_promote_price'] : 0,
                            'promote_price' => isset($val['promote_price']) ? $val['promote_price'] : 0,
                            'wg_number' => isset($val['get_warehouse_goods']['region_number']) ? $val['get_warehouse_goods']['region_number'] : 0,
                            'wag_number' => isset($val['get_warehouse_area_goods']['region_number']) ? $val['get_warehouse_area_goods']['region_number'] : 0,
                            'goods_number' => isset($val['goods_number']) ? $val['goods_number'] : 0
                        ];

                        $price = $this->goodsCommonService->getGoodsPrice($price, $discount, $val);

                        $val['shop_price'] = $price['shop_price'] ?? '';
                        $val['promote_price'] = $price['promote_price'] ?? '';
                        $val['goods_number'] = $price['goods_number'] ?? '';

                        if ($val['promote_price'] > 0) {
                            $promote_price = $this->goodsCommonService->getBargainPrice($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']);
                        } else {
                            $promote_price = 0;
                        }
                        $goods_list[$key]['market_price'] = $val['market_price'] ?? '';
                        $goods_list[$key]['rank_price'] = $val['shop_price'] ?? '';
                        $goods_list[$key]['shop_price'] = $val['shop_price'] ?? '';

                        $goods_list[$key]['market_price_formated'] = $this->dscRepository->getPriceFormat($val['market_price']);
                        $goods_list[$key]['rank_price_formated'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                        if ($promote_price > 0) {
                            $goods_list[$key]['shop_price_formated'] = $this->dscRepository->getPriceFormat($promote_price);
                        } else {
                            $goods_list[$key]['shop_price_formated'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                        }

                        $goods_list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                    }

                    if ($topic_data && $goods_list) {
                        foreach ($topic_data as $key => $value) {
                            foreach ($goods_list as $goods) {
                                if (in_array($goods['goods_id'], $value)) {
                                    $key = $key == 'default' ? lang('common.all_goods') : $key;
                                    $sort_goods_arr[$key][] = $goods;
                                }
                            }
                        }
                    }
                }

                $topic['goods_list'] = $sort_goods_arr;
            }
        }

        return $topic;
    }
}
