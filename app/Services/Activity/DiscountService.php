<?php

namespace App\Services\Activity;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Models\UserRank;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;

/**
 * 活动 ->【优惠】
 *
 * 【赠品】【折扣】【满减】
 */
class DiscountService
{
    public $user_rank_list = [];    //会员等级列表
    protected $timeRepository;
    protected $config;
    protected $baseRepository;
    protected $dscRepository;
    protected $goodsCommonService;
    protected $merchantCommonService;
    protected $userCommonService;

    public function __construct(
        TimeRepository $timeRepository,
        UserCommonService $userCommonService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->userCommonService = $userCommonService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获取优惠活动列表
     *
     * @return array
     * @throws \Exception
     */
    public function activityList()
    {
        //当前时间
        $time = $this->timeRepository->getGmTime();

        $activity_list = FavourableActivity::where("review_status", 3)
            ->where('end_time', '>', $time)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('end_time', 'DESC')
            ->get();

        $activity_list = $activity_list ? $activity_list->toArray() : [];

        $list = [];
        $timeFormat = $this->config['time_format'];

        if ($activity_list) {
            foreach ($activity_list as $row) {
                $row['activity_thumb'] = $this->dscRepository->getImagePath($row['activity_thumb']);

                if ($row['start_time'] > $time) {
                    $row['status'] = 0; //未开始
                } elseif ($row['start_time'] < $time && $row['end_time'] > $time) {
                    $row['status'] = 1; //进行中
                } elseif ($row['end_time'] < $time) {
                    $row['status'] = 2; //已结束
                }
                $row['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['start_time']);
                $row['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['end_time']);

                //优惠方式
                $row['actType'] = $row['act_type'];

                switch ($row['act_type']) {
                    case 0:
                        $row['act_type'] = lang('discount.act_type.0');
                        $row['activity_name'] = sprintf(lang('discount.type_activity_name.0'), $row['min_amount']);
                        break;
                    case 1:
                        $row['act_type'] = lang('discount.act_type.1');
                        $row['act_type_ext'] .= lang('discount.money_unit');
                        $row['activity_name'] = sprintf(lang('discount.type_activity_name.1'), $row['min_amount'], $row['act_type_ext']);
                        break;
                    case 2:
                        $row['act_type'] = lang('discount.act_type.2');
                        $row['activity_name'] = sprintf(lang('discount.type_activity_name.2'), $row['min_amount'], $row['act_type_ext'] / 10);
                        break;
                }

                $list[] = $row;
            }

            $list = array_values($list);
        }

        return $list;
    }

    /**
     * 查询符合条件的优惠活动
     *
     * @param int $user_id
     * @param int $ru_id
     * @return array
     * @throws \Exception
     */
    public function activityListAll($user_id = 0, $ru_id = 0)
    {
        $list = [];
        if ($user_id > 0) {
            $user_rank = $this->userCommonService->getUserRankByUid($user_id);

            if (!$user_rank) {
                $user_rank['rank_id'] = 0;
            }
            $user_rank = ',' . $user_rank['rank_id'] . ',';

            //当前时间
            $gmtime = $this->timeRepository->getGmTime();

            $activity = FavourableActivity::where('start_time', '<=', $gmtime)
                ->where('end_time', '>=', $gmtime)
                ->where('review_status', 3);

            if ($ru_id > 0) {
                $activity->where(function ($query) use ($ru_id) {
                    $query->where('user_id', $ru_id)->orWhere('userFav_type', '=', 1);
                });
            } else {
                $activity->where('user_id', $ru_id);
            }

            $list = $activity->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
                ->get();

            $list = $list ? $list->toArray() : [];
        }

        return $list;
    }

    /**
     * 同一商家所有优惠活动包含的所有优惠范围
     *
     * @param int $user_id
     * @param int $ru_id
     * @param int $act_range
     * @return array
     * @throws \Exception
     */
    public function activityRangeExt($user_id = 0, $ru_id = 0, $act_range = 0)
    {
        //当前时间
        $gmtime = $this->timeRepository->getGmTime();
        $user_rank = $this->userCommonService->getUserRankByUid($user_id);
        if (!$user_rank) {
            $user_rank['rank_id'] = 0;
        }
        $user_rank = ',' . $user_rank['rank_id'] . ',';
        $activity = FavourableActivity::select('*');
        if ($ru_id > 0) {
            $activity->Where('user_id', '=', $ru_id);
            $activity->orWhere('userFav_type', '=', 1);
        } else {
            $activity->where("user_id", $ru_id);
        }
        $list = $activity->where("review_status", 3)
            ->where("start_time", '<=', $gmtime)
            ->where("end_time", '>=', $gmtime)
            ->where("act_range", $act_range)
            ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
            ->get();

        $list = $list ? $list->toArray() : [];

        if ($list === null) {
            return [];
        }

        $arr = [];
        foreach ($list as $key => $row) {
            $id_list = explode(',', $row['act_range_ext']);
            $arr = array_merge($arr, $id_list);
        }

        return array_unique($arr);
    }

    /**
     * 优惠活动 - 活动详情
     *
     * @param int $act_id
     * @return array
     * @throws \Exception
     */
    public function activityDetail($act_id = 0)
    {
        //当前时间
        $time = $this->timeRepository->getGmTime();
        $timeFormat = $this->config['time_format'];
        $list = FavourableActivity::where("review_status", 3)
            ->where("act_id", $act_id)
            ->first();

        if (is_null($list)) {
            return [];
        }

        $list = $list ? $list->toArray() : [];

        /* 取得用户等级 */
        $user_rank_list = [];
        $user_rank_list[0] = lang('discount.not_user');
        $res = UserRank::get();
        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $row) {
                $user_rank_list[$row['rank_id']] = $row['rank_name'];
            }
        }

        //享受优惠会员等级
        $user_rank = explode(',', $list['user_rank']);
        $list['user_rank'] = [];
        foreach ($user_rank as $val) {
            if (isset($user_rank_list[$val])) {
                $list['user_rank'][] = $user_rank_list[$val];
            }
        }

        if ($list['start_time'] > $time) {
            $list['status'] = 0; //未开始
        } elseif ($list['start_time'] < $time && $list['end_time'] > $time) {
            $list['status'] = 1; //进行中
        } elseif ($list['end_time'] < $time) {
            $list['status'] = 2; //已结束
        }

        $list['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $list['start_time']);
        $list['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $list['end_time']);
        $list['activity_thumb'] = $this->dscRepository->getImagePath($list['activity_thumb']);
        $list['min_amount'] = $this->dscRepository->getPriceFormat($list['min_amount'], false);
        $list['max_amount'] = $this->dscRepository->getPriceFormat($list['max_amount'], false);

        //优惠方式
        $list['actType'] = $list['act_type'];

        //范围类型
        $list['actRange'] = $list['act_range'];

        //优惠范围类型、内容
        if ($list['act_range'] != FAR_ALL && !empty($list['act_range_ext'])) {
            if ($list['act_range'] == FAR_CATEGORY) {
                $list['act_range'] = lang('discount.act_range.1');
            } elseif ($list['act_range'] == FAR_BRAND) {
                $list['act_range'] = lang('discount.act_range.2');
            } else {
                $list['act_range'] = lang('discount.act_range.3');
            }
        } else {
            $list['act_range'] = lang('discount.act_range.0');
        }

        switch ($list['act_type']) {
            case 0:
                $list['act_type'] = lang('discount.act_type.0');
                $list['gift'] = unserialize($list['gift']);
                if (is_array($list['gift'])) {
                    foreach ($list['gift'] as $k => $v) {
                        $list['gift'][$k]['act_id'] = $list['act_id'];
                        $list['gift'][$k]['ru_id'] = $list['user_id'];
                        $goods_thumb = Goods::where('goods_id', $v['id'])->value('goods_thumb');
                        $list['gift'][$k]['thumb'] = $this->dscRepository->getImagePath($goods_thumb);
                        $list['gift'][$k]['price'] = $v['price'];
                        $list['gift'][$k]['price_formated'] = $this->dscRepository->getPriceFormat($v['price']);
                        $list['gift'][$k]['url'] = dsc_url('/#/goods/' . $v['id']);
                        $list['gift'][$k]['app_page'] = config('route.goods.detail') . $v['id'];
                    }
                }
                $list['activity_name'] = sprintf(lang('discount.type_activity_name.0'), $list['min_amount']);

                break;
            case 1:
                $list['act_type'] = lang('discount.act_type.1');
                $list['act_type_ext'] .= lang('discount.money_unit');
                $list['activity_name'] = sprintf(lang('discount.type_activity_name.1'), $list['min_amount'], $list['act_type_ext']);
                $list['gift'] = [];

                break;
            case 2:
                $list['act_type'] = lang('discount.act_type.2');
                $list['activity_name'] = sprintf(lang('discount.type_activity_name.2'), $list['min_amount'], $list['act_type_ext'] / 10);
                $list['gift'] = [];
                break;
        }

        return $list;
    }

    /**
     * 优惠活动 - 活动信息
     * @param int $act_id
     * @return array
     */
    public function activityInfo($act_id = 0)
    {
        $list = FavourableActivity::where("review_status", 3)
            ->where("act_id", $act_id)
            ->first();

        if (is_null($list)) {
            return [];
        }

        $list = $list ? $list->toArray() : [];
        if ($list) {
            $list['gift'] = isset($list['gift']) && !empty($list['gift']) ? unserialize($list['gift']) : '';
        }

        return $list;
    }

    /**
     * 优惠活动 - 活动商品
     * @param array $filter
     * @param int $sort
     * @param string $order
     * @param int $page
     * @param int $size
     * @return array
     */
    public function activityGoods($filter = ['goods_ids' => '', 'cat_ids' => '', 'brand_ids' => '', 'user_id' => 0], $sort = 0, $order = 'desc', $page = 1, $size = 10, $uid)
    {
        $begin = ($page - 1) * $size;

        $goods = Goods::from('goods as g')
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
            ->where('g.is_delete', 0);

        // 分类
        if (isset($filter['cat_ids']) && !empty($filter['cat_ids'])) {
            $cat_id = explode(',', $filter['cat_ids']);
            $goods->whereIn('g.cat_id', $cat_id);
        }
        //品牌
        if (isset($filter['brand_ids']) && !empty($filter['brand_ids'])) {
            $goods->leftjoin('brand as b', 'b.brand_id', '=', 'g.brand_id');
            $brand_id = explode(',', $filter['brand_ids']);
            $goods->whereIn('g.brand_id', $brand_id);
        }
        // 商品
        if (isset($filter['goods_ids']) && !empty($filter['goods_ids'])) {
            $goods_id = explode(',', $filter['goods_ids']);
            $goods->whereIn('g.goods_id', $goods_id);
        }

        if ($this->config['review_goods']) {
            $goods = $goods->where('g.review_status', '>', 2);
        }

        // 商家
        if (isset($filter['user_id'])) {
            $goods->where('g.user_id', $filter['user_id']);
        }

        switch ($sort) {
            case '0':// 0综合
                $goods = $goods->orderBy('g.goods_id', $order);
                break;
            case '1'://价格
                $goods = $goods->orderBy('g.shop_price', $order);
                break;
            case '2'://销量
                $goods = $goods->orderBy('g.sales_volume', $order);
                break;
        }

        $goods_list = $goods->offset($begin)
            ->limit($size)
            ->get();

        if (is_null($goods_list)) {
            return [];
        }

        $goods_list = $goods_list ? $goods_list->toArray() : [];

        //计算当前商品的会员等级折扣
        $user_rank = $this->userCommonService->getUserRankByUid($uid);
        $discount = $user_rank['discount'] ?? 100;

        $list = [];
        foreach ($goods_list as $key => $row) {
            $list[$key]['goods_id'] = $row['goods_id'];
            $list[$key]['user_id'] = $row['user_id'];
            $list[$key]['self_run'] = $row['user_id'];
            $list[$key]['goods_name'] = $row['goods_name'];
            $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $list[$key]['shop_price'] = $row['shop_price'] * ($discount / 100);//显示会员等级价格
            $list[$key]['market_price'] = $row['market_price'];
            $list[$key]['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['shop_price'] * ($discount / 100));//显示会员等级价格
            $list[$key]['market_price_format'] = $this->dscRepository->getPriceFormat($row['market_price']);
            $list[$key]['goods_number'] = $row['goods_number'];
            $list[$key]['sales_volume'] = $row['sales_volume'];
            $list[$key]['url'] = dsc_url('/#/goods/' . $row['goods_id']);
            $list[$key]['app_page'] = config('route.goods.detail') . $row['goods_id'];
        }

        return $list;
    }

    /**
     * 根据活动id查询活动详情
     *
     * @param int $user_id
     * @param int $act_id
     * @return array
     * @throws \Exception
     */
    public function getActivityInfo($user_id = 0, $act_id = 0)
    {
        //当前时间
        $gmtime = $this->timeRepository->getGmTime();
        //会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($user_id);
        if (!$user_rank) {
            $user_rank['rank_id'] = 0;
        }
        $user_rank = ',' . $user_rank['rank_id'] . ',';

        $activity = FavourableActivity::where("review_status", 3)
            ->where("act_id", $act_id)
            ->where("start_time", '<=', $gmtime)
            ->where("end_time", '>=', $gmtime)
            ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
            ->first();

        $activity = $activity ? $activity->toArray() : [];

        return $activity;
    }

    /**
     * 同一商家所有优惠活动包含的所有优惠范围
     *
     * @param int $user_id
     * @param int $act_id
     * @return array
     * @throws \Exception
     */
    public function getActivityType($user_id = 0, $act_id = 0)
    {
        // 活动信息
        $activity = $this->getActivityInfo($user_id, $act_id);

        $row = [];
        if ($activity) {
            $row['actType'] = $activity['act_type'];
            switch ($activity['act_type']) {
                case 0:
                    $row['act_type'] = lang('discount.act_type_short.0');
                    $row['act_name'] = sprintf(lang('discount.type_activity_name_short.0'), $activity['min_amount']);
                    break;
                case 1:
                    $row['act_type'] = lang('discount.act_type_short.1');
                    $row['act_name'] = sprintf(lang('discount.type_activity_name_short.1'), $activity['min_amount'], $activity['act_type_ext']);
                    break;
                case 2:
                    $row['act_type'] = lang('discount.act_type_short.2');
                    $row['act_name'] = sprintf(lang('discount.type_activity_name_short.2'), $activity['min_amount']);
                    break;
                default:
                    break;
            }
        }

        return $row;
    }

    /**
     * 查询活动中 已加入购物车的商品
     *
     * @param int $user_id
     * @param int $act_id
     * @param int $rec_type
     * @return array
     * @throws \Exception
     */
    public function cartFavourableGoods($user_id = 0, $act_id = 0, $rec_type = CART_GENERAL_GOODS)
    {
        // 活动信息
        $favourable = $this->getActivityInfo($user_id, $act_id);

        /* 查询优惠范围内商品总额的sql */
        $res = Cart::where('user_id', $user_id)
            ->where('rec_type', $rec_type)
            ->where('is_gift', 0);

        $id_list = [];
        $list = [];
        if ($favourable) {
            /* 根据优惠范围修正sql */
            if ($favourable['act_range'] == FAR_ALL) {
            } elseif ($favourable['act_range'] == FAR_CATEGORY) {
                /* 取得优惠范围分类的所有下级分类 */
                if ($favourable['act_range_ext']) {
                    $cat_list = explode(',', $favourable['act_range_ext']);
                    foreach ($cat_list as $id) {
                        $cat_list = $this->arr_foreach($this->catList($id));
                        $id_list = array_merge($id_list, $cat_list);
                        array_unshift($id_list, $id);
                    }

                    if ($id_list) {
                        $id_list = array_unique($id_list);
                        $res = $res->whereHas('getGoods', function ($query) use ($id_list) {
                            $query->whereIn('cat_id', $id_list);
                        });
                    }
                }
            } elseif ($favourable['act_range'] == FAR_BRAND) {
                $id_list = $this->baseRepository->getExplode($favourable['act_range_ext']);
                $id_list = array_unique($id_list);
                $res = $res->whereHas('getGoods', function ($query) use ($id_list) {
                    $query->whereIn('brand_id', $id_list);
                });

            } elseif ($favourable['act_range'] == FAR_GOODS) {
                $id_list = $this->baseRepository->getExplode($favourable['act_range_ext']);
                $id_list = array_unique($id_list);
                $res = $res->whereIn('goods_id', $id_list);
            }
            $res = $res->with([
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'goods_thumb', 'goods_name');
                }
            ]);

            $res = $this->baseRepository->getToArrayGet($res);

            $list = [];
            if ($res) {
                foreach ($res as $key => $row) {
                    $row = $this->baseRepository->getArrayMerge($row, $row['get_goods']);
                    $list[$key]['rec_id'] = $row['rec_id'];
                    $list[$key]['goods_id'] = $row['goods_id'];
                    $list[$key]['goods_name'] = $row['goods_name'];
                    $list[$key]['shop_price'] = $row['goods_price'];
                    $list[$key]['shop_price_format'] = $this->dscRepository->getPriceFormat($row['goods_price']);
                    $list[$key]['goods_number'] = $row['goods_number'];
                    $list[$key]['url'] = dsc_url('/#/goods/' . $row['goods_id']);
                    $list[$key]['app_page'] = config('route.goods.detail') . $row['goods_id'];
                }
            }
        }

        return $list;
    }

    /**
     * 获取商品分类树
     *
     * @param int $cat_id
     * @return array
     */
    public function catList($cat_id = 0)
    {
        $res = Category::select('cat_id', 'cat_name', 'touch_icon', 'parent_id', 'cat_alias_name', 'is_show')
            ->where('parent_id', $cat_id)
            ->where('is_show', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('cat_id', 'ASC')
            ->get();

        $res = $res ? $res->toArray() : [];

        if (empty($res)) {
            return [];
        }

        $arr = [];

        foreach ($res as $key => $row) {
            $arr[$row['cat_id']]['cat_id'] = $row['cat_id'];
            if (isset($row['cat_id'])) {
                $child_tree = $this->catList($row['cat_id']);
                if ($child_tree) {
                    $arr[$row['cat_id']]['child_tree'] = $child_tree;
                }
            }
        }

        return $arr;
    }

    /**
     * 多维数组转为一维数组
     */
    public function arr_foreach($arr)
    {
        $tmp = [];
        if (!is_array($arr)) {
            return false;
        }
        foreach ($arr as $val) {
            if (is_array($val)) {
                $tmp = array_merge($tmp, $this->arr_foreach($val));
            } else {
                $tmp[] = $val;
            }
        }
        return $tmp;
    }

    /**
     * 获取优惠活动列表
     * @return array
     */
    public function getFavourableActivity()
    {
        $time = $this->timeRepository->getGmTime();

        /* 取得用户等级 */
        $user_rank_list = $this->user_rank_list;

        $res = FavourableActivity::where('review_status', 3)
            ->where('end_time', '>', $time)
            ->orderBy('sort_order')
            ->orderBy('end_time', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        if ($res) {
            foreach ($res as $row) {
                $row['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['start_time']);
                $row['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['end_time']);
                $row['activity_thumb'] = $this->dscRepository->getImagePath($row['activity_thumb']);

                //享受优惠会员等级
                $user_rank = explode(',', $row['user_rank']);
                $row['user_rank'] = [];
                foreach ($user_rank as $val) {
                    if (isset($user_rank_list[$val])) {
                        $row['user_rank'][] = $user_rank_list[$val];
                    }
                }

                if ($row['userFav_type']) {
                    $row['shop_name'] = $GLOBALS['_LANG']['His_general']; //商家名称
                } else {
                    $row['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称;

                    $build_uri = [
                        'urid' => $row['user_id'],
                        'append' => $row['shop_name']
                    ];

                    $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);

                    $row['shop_url'] = $domain_url['domain_name'];
                }

                $row['act_range_type'] = $row['act_range']; //优惠范围
                //优惠范围类型、内容
                if ($row['act_range'] != FAR_ALL && !empty($row['act_range_ext'])) {
                    $act_range_ext = !is_array($row['act_range_ext']) ? explode(",", $row['act_range_ext']) : $row['act_range_ext'];

                    if ($row['act_range'] == FAR_CATEGORY) {
                        $row['act_range'] = $GLOBALS['_LANG']['far_category'];
                        $row['program'] = 'category.php?id=';

                        $act_range_ext = Category::select('cat_id AS id', 'cat_name AS name')->whereIn('cat_id', $act_range_ext)->get();

                        $act_range_ext = $act_range_ext ? $act_range_ext->toArray() : [];
                    } elseif ($row['act_range'] == FAR_BRAND) {
                        $row['act_range'] = $GLOBALS['_LANG']['far_brand'];
                        $row['program'] = 'brand.php?id=';

                        $act_range_ext = Brand::select('brand_id AS id', 'brand_name AS name')->whereIn('brand_id', $act_range_ext)->get();

                        $act_range_ext = $act_range_ext ? $act_range_ext->toArray() : [];
                    } else {
                        $row['act_range'] = $GLOBALS['_LANG']['far_goods'];
                        $row['program'] = 'goods.php?id=';

                        $act_range_ext = Goods::select('goods_id AS id', 'goods_name AS name')->whereIn('goods_id', $act_range_ext)->get();

                        $act_range_ext = $act_range_ext ? $act_range_ext->toArray() : [];
                    }

                    $row['act_range_ext'] = $act_range_ext;
                } else {
                    $row['act_range'] = $GLOBALS['_LANG']['far_all'];
                }

                //优惠方式
                $row['actType'] = $row['act_type']; //优惠方式

                switch ($row['act_type']) {
                    case 0:
                        $row['act_type'] = $GLOBALS['_LANG']['fat_goods'];
                        $row['gift'] = unserialize($row['gift']);
                        if (is_array($row['gift'])) {
                            foreach ($row['gift'] as $k => $v) {
                                $goods_thumb = Goods::where('goods_id', $v['id'])->value('goods_thumb');

                                $row['gift'][$k]['thumb'] = $this->dscRepository->getImagePath($goods_thumb);
                            }
                        }
                        break;
                    case 1:
                        $row['act_type'] = $GLOBALS['_LANG']['fat_price'];
                        $row['act_type_ext'] .= $GLOBALS['_LANG']['unit_yuan'];
                        $row['gift'] = [];
                        break;
                    case 2:
                        $row['act_type'] = $GLOBALS['_LANG']['fat_discount'];
                        $row['act_type_ext'] .= "%";
                        $row['gift'] = [];
                        break;
                }

                $list[$row['actType']]['activity_name'] = $row['act_type'];
                $list[$row['actType']]['activity_list'][] = $row;
            }

            ksort($list);
        }

        return $list;
    }

    /**
     * 获取优惠活动商品列表
     * @param $act_range_ext
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getActRangeExt($act_range_ext, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $res = [];
        /* 查询分类商品数据 */
        if ($act_range_ext) {
            $act_range_ext = !is_array($act_range_ext) ? explode(",", $act_range_ext) : $act_range_ext;

            $res = Goods::where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0)
                ->whereIn('goods_id', $act_range_ext);

            if ($this->config['review_goods']) {
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
                    $query = $query->where('region_id', $where['area_id']);

                    if ($where['area_pricetype'] == 1) {
                        $query->where('city_id', $where['area_city']);
                    }
                },
                'getBrand'
            ]);

            $res = $res->orderByRaw('sort_order, last_update desc');

            $res = $this->baseRepository->getToArrayGet($res);
        }

        $arr = [];
        if ($res) {
            foreach ($res as $key => $val) {
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

                $price = $this->goodsCommonService->getGoodsPrice($price, session('discount'), $val);


                $val['shop_price'] = $price['shop_price'];
                $val['promote_price'] = $price['promote_price'];
                $val['goods_number'] = $price['goods_number'];

                $arr[$key] = $val;

                if ($val['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $arr[$key]['price'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                $arr[$key]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
            }
        }

        return $arr;
    }
}
