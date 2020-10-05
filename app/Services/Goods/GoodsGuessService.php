<?php

namespace App\Services\Goods;

use App\Models\Comment;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

class GoodsGuessService
{
    protected $goodsCommonService;
    protected $dscRepository;
    protected $config;
    protected $merchantCommonService;

    public function __construct(
        GoodsCommonService $goodsCommonService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->goodsCommonService = $goodsCommonService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->merchantCommonService = $merchantCommonService;
    }

    /** 猜你你喜欢---从订单商品中获取该分类的其他商品
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getGuessGoods($where = [])
    {
        $order_idArr = $finished_goods = $link_cats = [];
        $start = (($where['page'] > 1) ? ($where['page'] - 1) : 0) * $where['limit'];

        $query = [];

        if (empty($where['history'])) {

            //用户中心
            $order_arr = OrderInfo::select('order_id')->where('user_id', $where['user_id'])->orderBy('order_id', 'desc')->take(5)->get();
            $order_arr = $order_arr ? $order_arr->toArray() : [];

            if ($order_arr) {
                foreach ($order_arr as $key => $val) {
                    $order_idArr[] = $val['order_id'];
                }

                $cat_list = $this->getGuessOderGoodsCat($order_idArr);

                $link_goods = [];
                $link_cats = [];
                foreach ($cat_list as $kk => $vv) {
                    $link_goods[] = $vv['goods_id'];
                    $link_cats[] = $vv['get_goods']['cat_id'];
                }

                $link_cats = array_unique($link_cats);

                $query = $this->getUserOrderGoodsGuess($link_cats, $link_goods, $where['warehouse_id'], $where['area_id'], $where['area_city'], 0, 0, 8);
            }
        } else {

            $ecsCookie = request()->cookie('ECS');

            //商品详情页
            if (!empty($ecsCookie['history'])) {
                $history = !is_array($ecsCookie['history']) ? explode(",", $ecsCookie['history']) : $ecsCookie['history'];
                $history = $history ? addslashes_deep($history) : [];

                $res = Goods::where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('is_delete', 0)
                    ->whereIn('goods_id', $history)
                    ->get();
                $res = $res ? $res->toArray() : [];

                $link_cats = [];
                $link_goods = [];
                if ($res) {
                    foreach ($res as $row) {
                        $link_goods[] = $row['goods_id'];
                        $link_cats[] = $row['cat_id'];
                    }
                }

                //历史商品、分类
                $query = $this->getUserOrderGoodsGuess($link_cats, $link_goods, $where['warehouse_id'], $where['area_id'], $where['area_city'], 0, $start, $where['limit']);
            }
        }

        //默认
        if (empty($query) && (count($query) < $where['limit']) && $where['history'] == 1) {
            //历史商品、分类
            $query = $this->getUserOrderGoodsGuess([], [], $where['warehouse_id'], $where['area_id'], $where['area_city'], 2, $start, $where['limit']);
        }

        $guess_goods = [];
        if ($query) {
            foreach ($query as $row) {
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

                $guess_goods[$row['goods_id']] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $guess_goods[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $guess_goods[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $guess_goods[$row['goods_id']]['sales_volume'] = $row['sales_volume'];
                $guess_goods[$row['goods_id']]['short_name'] = $this->config['goods_name_length'] > 0 ? $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
                $guess_goods[$row['goods_id']]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $guess_goods[$row['goods_id']]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $guess_goods[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $guess_goods[$row['goods_id']]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

                $guess_goods[$row['goods_id']]['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);
                $guess_goods[$row['goods_id']]['shopUrl'] = $this->dscRepository->buildUri('merchants_store', ['urid' => $row['user_id']]);

                //好评率
                $comment_rank = Comment::where('id_value', $row['goods_id'])->avg('comment_rank');
                $comment_rank = $comment_rank ? $comment_rank : 0;

                if ($comment_rank) {
                    $guess_goods[$row['goods_id']]['comment_percent'] = round(($comment_rank / 5) * 100, 1);
                } else {
                    $guess_goods[$row['goods_id']]['comment_percent'] = 100;
                }
            }
        }

        return $guess_goods;
    }

    /**
     * 关联猜你喜欢订单商品分类查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    private function getGuessOderGoodsCat($order_id = [])
    {
        $res = OrderGoods::whereIn('order_id', $order_id)
            ->whereHas('getGoods', function ($query) {
            })
            ->with(['getGoods'])->get();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     * 查询猜你喜欢商品
     *
     * @param array $link_cats
     * @param array $link_goods
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $is_volume
     * @param int $skip
     * @param int $take
     * @return mixed
     */
    private function getUserOrderGoodsGuess($link_cats = [], $link_goods = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $is_volume = 0, $skip = 0, $take = 0)
    {
        $res = Goods::where('is_on_sale', 1)->where('is_alone_sale', 1)->where('is_delete', 0);

        if ($is_volume == 1) {
            $res = $res->where('sales_volume', '>', 0);
        } elseif ($is_volume == 2) {
            $res = $res->where('sales_volume', '>', 0)->where('is_hot', 1);
        }

        if ($link_cats) {
            $res = $res->whereIn('cat_id', $link_cats);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        if ($link_goods) {
            $res = $res->whereNotIn('goods_id', $link_goods);
        }

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
            }
        ]);

        $res = $res->orderBy('sort_order', 'ASC')->orderBy('sales_volume', 'DESC');

        if ($skip > 0) {
            $res = $res->skip($skip);
        }

        if ($take > 0) {
            $res = $res->take($take);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        return $res;
    }
}
