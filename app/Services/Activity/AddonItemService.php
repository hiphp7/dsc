<?php

namespace App\Services\Activity;

use App\Models\Cart;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsCommonService;

/**
 * 活动 ->【凑单】
 */
class AddonItemService
{
    protected $categoryService;
    protected $baseRepository;
    protected $config;
    protected $goodsCommonService;
    protected $sessionRepository;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        CategoryService $categoryService,
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        SessionRepository $sessionRepository,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
    }

    /**
     * 取得某用户等级当前时间可以享受的优惠活动
     *
     * @param $user_rank
     * @param $favourable_id
     * @param string $sort
     * @param string $order
     * @param int $size
     * @param int $page
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getFavourableGoodsList($user_rank, $favourable_id, $sort = '', $order = '', $size = 15, $page = 1, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        /* 当前用户可享受的优惠活动 */
        $user_rank = ',' . $user_rank . ',';
        $now = $this->timeRepository->getGmTime();

        $favourable = FavourableActivity::where('review_status', 3)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('act_id', $favourable_id)
            ->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");

        $favourable = $favourable->first();

        $favourable = $favourable ? $favourable->toArray() : [];

        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        if ($favourable['userFav_type'] == 0) {
            $res = $res->where('user_id', $favourable['user_id']);
        }

        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_ALL) {
            if ($this->config['region_store_enabled']) {
                /* 设置的使用范围 卖场优惠活动 liu */
                $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id']);
                if ($mer_ids) {
                    $mer_ids = !is_array($mer_ids) ? explode(",", $mer_ids) : $mer_ids;
                    $res = $res->whereIn('user_id', $mer_ids);
                }
            }
        }
        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_CATEGORY) {
            // 按分类
            $id_list = [];
            $cat_list = explode(',', $favourable['act_range_ext']);
            foreach ($cat_list as $id) {

                /**
                 * 当前分类下的所有子分类
                 * 返回一维数组
                 */
                $cat_keys = $this->categoryService->getCatListChildren(intval($id));

                $id_list = array_merge($id_list, $cat_keys);
            }
            $res = $res->whereIn('cat_id', $id_list);
        }
        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_BRAND) {
            // 按品牌
            $id_list = explode(',', $favourable['act_range_ext']);

            $res = $res->whereIn('brand_id', $id_list);
        }

        $ext = false;
        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_GOODS) {
            $ext = true;
            if ($this->config['region_store_enabled']) {
                /* 设置的使用范围 msj卖场优惠活动 liu */
                $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id']);
                if ($mer_ids) {
                    $res = $res->whereIn('user_id', $mer_ids);
                }
                if ($favourable['userFav_type_ext']) {
                    $ext = false;
                }
            }

            // 按商品分类
            $id_list = explode(',', $favourable['act_range_ext']);

            $res = $res->whereIn('goods_id', $id_list);
        }

        if (isset($favourable['userFav_type']) && $favourable['userFav_type'] == 0 && $ext) {
            $res = $res->where('user_id', $favourable['user_id']);
        }

        if ($this->config['review_goods'] == 1) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

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
                $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $res->orderBy($sort, $order);

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        $key = 0;

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

                $arr[$key] = $row;

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $arr[$key]['goods_id'] = $row['goods_id'];
                $arr[$key]['goods_name'] = $row['goods_name'];
                $arr[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$key]['format_shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $arr[$key]['format_promote_price'] = ($promote_price > 0) ? $this->dscRepository->getImagePath($promote_price) : '';
                $arr[$key]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

                $key++;
            }
        }

        return $arr;
    }

    /**
     * 优惠活动
     *
     * @param $user_rank
     * @param $favourable_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return mixed
     */
    public function getFavourableGoodsCount($user_rank, $favourable_id, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        /* 当前用户可享受的优惠活动 */
        $user_rank = ',' . $user_rank . ',';
        $now = $this->timeRepository->getGmTime();

        $favourable = FavourableActivity::where('review_status', 3)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('act_id', $favourable_id)
            ->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");

        $favourable = $favourable->first();

        $favourable = $favourable ? $favourable->toArray() : [];

        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        if ($favourable['userFav_type'] == 0) {
            $res = $res->where('user_id', $favourable['user_id']);
        }

        $ext = true;
        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_ALL) {
            if ($this->config['region_store_enabled']) {
                /* 设置的使用范围 卖场优惠活动 liu */
                $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id']);
                if ($mer_ids) {
                    $mer_ids = !is_array($mer_ids) ? explode(",", $mer_ids) : $mer_ids;
                    $res = $res->whereIn('user_id', $mer_ids);
                }
            }
        }

        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_CATEGORY) {
            // 按分类
            $id_list = [];
            $cat_list = explode(',', $favourable['act_range_ext']);
            foreach ($cat_list as $id) {

                /**
                 * 当前分类下的所有子分类
                 * 返回一维数组
                 */

                $cat_keys = $this->categoryService->getCatListChildren(intval($id));

                $id_list = array_merge($id_list, $cat_keys);
            }

            $res = $res->whereIn('cat_id', $id_list);
        }
        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_BRAND) {
            // 按品牌
            $id_list = explode(',', $favourable['act_range_ext']);

            $res = $res->whereIn('brand_id', $id_list);
        }
        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_GOODS) {
            if ($this->config['region_store_enabled']) {
                if ($favourable['userFav_type_ext']) {
                    $ext = false;
                }
            }

            // 按商品分类
            $id_list = explode(',', $favourable['act_range_ext']);

            $res = $res->whereIn('goods_id', $id_list);
        }

        if (isset($favourable['userFav_type']) && $favourable['userFav_type'] == 0 && $ext) {
            $res = $res->where('user_id', $favourable['user_id']);
        }

        if ($this->config['review_goods'] == 1) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        return $res->count();
    }

    /**
     * 取得当前活动 已经加入购物车的商品
     *
     * @param int $user_rank
     * @param $favourable_id
     * @param int $warehouse_id
     * @param int $area_id
     * @return array
     */
    public function getCartFavourableGoods($user_rank = 0, $favourable_id, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $user_rank = ',' . $user_rank . ',';
        $now = $this->timeRepository->getGmTime();

        $favourable = FavourableActivity::where('review_status', 3)
            ->where('act_id', $favourable_id)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");

        $favourable = $this->baseRepository->getToArrayFirst($favourable);

        /* 查询优惠范围内购物车的商品 */
        $res = Cart::select('rec_id', 'goods_number', 'goods_id', 'goods_price')
            ->where('rec_type', CART_GENERAL_GOODS)
            ->where('is_gift', 0);

        if (!empty(session('user_id'))) {
            $res = $res->where('user_id', session('user_id'));
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = $res->where('session_id', $session_id);
        }

        $mer_ids = [];
        $id_list = [];
        /* 根据优惠范围修正sql */
        if (isset($favourable['act_range']) && $favourable['act_range'] == FAR_ALL) {
            if ($this->config['region_store_enabled']) {
                /* 设置的使用范围 卖场优惠活动 liu */
                $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id']);
                if ($mer_ids) {
                    $mer_ids = !is_array($mer_ids) ? explode(",", $mer_ids) : $mer_ids;
                }
            }
        } elseif (isset($favourable['act_range']) && $favourable['act_range'] == FAR_CATEGORY) {
            /* 取得优惠范围分类的所有下级分类 */
            $cat_list = explode(',', $favourable['act_range_ext']);
            foreach ($cat_list as $id) {

                /**
                 * 当前分类下的所有子分类
                 * 返回一维数组
                 */
                $cat_keys = $this->categoryService->getCatListChildren(intval($id));

                $id_list = array_merge($id_list, $cat_keys);
            }
        } elseif (isset($favourable['act_range']) && $favourable['act_range'] == FAR_BRAND) {
            $id_list = explode(',', $favourable['act_range_ext']);
        } else {
            $id_list = isset($favourable['act_range_ext']) ? explode(',', $favourable['act_range_ext']) : [];
        }

        $goodsWhere = [
            'region_store_enabled' => $this->config['region_store_enabled'],
            'mer_ids' => $mer_ids,
            'user_id' => $favourable['user_id'] ?? 0,
            'far_all' => FAR_ALL,
            'categoty' => FAR_CATEGORY,
            'brand' => FAR_BRAND,
            'goods' => FAR_GOODS,
            'id_list' => $id_list,
            'favourable' => $favourable,
            'open_area_goods' => $this->config['open_area_goods'],
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];

        $res = $res->whereHas('getGoods', function ($query) use ($goodsWhere) {
            $ext = true;

            $query = $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            if (isset($goodsWhere['favourable']['act_range']) && $goodsWhere['favourable']['act_range'] == $goodsWhere['far_all']) {
                if ($goodsWhere['region_store_enabled'] == 0) {
                    $query = $query->whereIn('user_id', $goodsWhere['mer_ids']);
                }
            } elseif (isset($goodsWhere['favourable']['act_range']) && $goodsWhere['favourable']['act_range'] == $goodsWhere['categoty']) {
                if ($goodsWhere['id_list']) {
                    $query = $query->whereIn('cat_id', $goodsWhere['id_list']);
                }
            } elseif (isset($goodsWhere['favourable']['act_range']) && $goodsWhere['favourable']['act_range'] == $goodsWhere['brand']) {
                if ($goodsWhere['id_list']) {
                    $query = $query->whereIn('brand_id', $goodsWhere['id_list']);
                }
            } else {
                if ($goodsWhere['region_store_enabled']) {
                    if ($goodsWhere['favourable']['userFav_type_ext']) {
                        $ext = false;
                    }
                }

                if ($goodsWhere['id_list']) {
                    $query = $query->whereIn('goods_id', $goodsWhere['id_list']);
                }
            }

            if (isset($goodsWhere['favourable']['userFav_type']) && $goodsWhere['favourable']['userFav_type'] == 0 && $ext) {
                $query = $query->where('user_id', $goodsWhere['user_id']);
            }

            $this->dscRepository->getAreaLinkGoods($query, $goodsWhere['area_id'], $goodsWhere['area_city']);
        });

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'goods_name');
            }
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        /* 优惠范围内的商品总额 */
        $cart_favourable_goods = [];

        if ($res) {
            foreach ($res as $key => $row) {
                $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;

                $cart_favourable_goods[$key]['rec_id'] = $row['rec_id'];
                $cart_favourable_goods[$key]['goods_id'] = $row['goods_id'];
                $cart_favourable_goods[$key]['goods_name'] = $row['goods_name'];
                $cart_favourable_goods[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $cart_favourable_goods[$key]['shop_price'] = number_format($row['goods_price'], 2, '.', '');
                $cart_favourable_goods[$key]['goods_number'] = $row['goods_number'];
                $cart_favourable_goods[$key]['goods_url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            }
        }

        return $cart_favourable_goods;
    }

    // 获取优惠活动类型 满赠-满减-打折
    public function getActType($user_rank, $favourable_id)
    {
        $user_rank = ',' . $user_rank . ',';
        $now = $this->timeRepository->getGmTime();

        $selected = FavourableActivity::where('review_status', 3)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('act_id', $favourable_id)
            ->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");

        $selected = $selected->first();

        $selected = $selected ? $selected->toArray() : [];

        $act_type_txt = '';
        if ($selected) {
            switch ($selected['act_type']) {
                case 0:
                    $act_type_txt = lang('coudan.With_a_gift') . lang('coudan.man') . $selected['min_amount'] . lang('coudan.change_purchase_gift');
                    break;
                case 1:
                    $act_type_txt = lang('coudan.Full_reduction') . lang('coudan.man') . $selected['min_amount'] . lang('coudan.reduction_gift') . $selected['act_type_ext'] . lang('coudan.yuan');
                    break;
                case 2:
                    $act_type_txt = lang('coudan.discount') . lang('coudan.man') . $selected['min_amount'] . lang('coudan.discount_gift');
                    break;

                default:
                    break;
            }
        }

        return $act_type_txt;
    }
}
