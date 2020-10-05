<?php

namespace App\Services\Visual;

use App\Models\Article;
use App\Models\ArticleCat;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CollectStore;
use App\Models\GalleryAlbum;
use App\Models\Goods;
use App\Models\MerchantsCategory;
use App\Models\MerchantsShopInformation;
use App\Models\PicAlbum;
use App\Models\SeckillGoods;
use App\Models\SeckillTimeBucket;
use App\Models\TouchPageView;
use App\Models\TouchTopic;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryGoodsService;
use App\Services\Goods\GoodsCommonService;
use App\Services\User\UserCommonService;
use Illuminate\Support\Facades\Storage;

/**
 * 可视化
 * Class VisualService
 * @package App\Services\Visual
 */
class VisualService
{
    protected $config;
    protected $timeRepository;
    protected $categoryGoodsService;
    protected $dscRepository;
    protected $goodsCommonService;
    protected $baseRepository;
    protected $userCommonService;

    public function __construct(
        TimeRepository $timeRepository,
        CategoryGoodsService $categoryGoodsService,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        BaseRepository $baseRepository,
        UserCommonService $userCommonService
    )
    {
        $files = [
            'common',
            'ecmoban'
        ];
        load_helper($files);
        $this->timeRepository = $timeRepository;
        $this->categoryGoodsService = $categoryGoodsService;
        $this->dscRepository = $dscRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->baseRepository = $baseRepository;
        $this->userCommonService = $userCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 编辑控制台
     */
    public function Index()
    {
        $init_data = [];
        $init_data['app'] = isset($this->config['wap_app']) ? $this->config['wap_app'] : '';
        return $init_data;
    }

    /**
     * 保存模块配置
     * param $type  页面类型
     * param $id   页面ID
     * param $ru_id   商家ID
     * return int $id   返回默认页面ID
     */
    public function Default($ru_id = 0, $type = 'index')
    {
        if ($ru_id > 0) {
            $id = TouchPageView::where('ru_id', $ru_id)
                ->where('type', $type)
                ->value('id');
        } else {
            $id = TouchPageView::where('ru_id', 0)
                ->where('type', 'index')
                ->where('default', 1)
                ->value('id');
        }
        return $id;
    }

    /**
     * 头部APP广告位
     */
    public function AppNav()
    {
        $app = $this->config['wap_index_pro'] ? 1 : 0;
        return $app;
    }

    /**
     * 公告
     * @param int $cat_id
     * @param int $num
     * @return array
     */
    public function Article($cat_id = 0, $num = 10)
    {
        $article_msg = Article::where('is_open', 1);

        if ($cat_id > 0) {
            $list = $this->article_tree($cat_id);

            $res = [];
            if ($list) {
                foreach ($list as $k => $val) {
                    $res[$k] = isset($val['cat_id']) ? $val['cat_id'] : $val;
                }
                if ($res) {
                    array_unshift($res, $cat_id);
                    $cat_id = implode(',', $res);
                    $article_msg = $article_msg->whereIn('cat_id', $cat_id);
                } else {
                    $article_msg = $article_msg->where('cat_id', $cat_id);
                }
            } else {
                $article_msg = $article_msg->where('cat_id', $cat_id);
            }
        }

        $article_msg = $article_msg->orderBy('article_id', 'DESC')
            ->limit($num)
            ->get();

        $article_msg = $article_msg ? $article_msg->toArray() : [];

        if (!empty($article_msg)) {
            foreach ($article_msg as $key => $value) {
                $article_msg[$key]['title'] = $value['title'];
                $article_msg[$key]['url'] = dsc_url('/#/articleDetail/' . $value['article_id']);
                $article_msg[$key]['app_page'] = config('route.article.detail') . $value['article_id'];
                $article_msg[$key]['date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $value['add_time']);
            }
        }

        return $article_msg;
    }

    /**
     * 商品列表模块(默认)
     *
     * @param int $user_id
     * @param int $cat_id
     * @param string $type
     * @param int $ru_id
     * @param int $number
     * @param int $brand
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array|string
     */
    public function Product($user_id = 0, $cat_id = 0, $type = '', $ru_id = 0, $number = 10, $brand = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        if ($cat_id == 0) {
            $children = 0;
        } else {
            $children = $this->get_children($cat_id);
        }

        $where_ext = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'intro' => $type,
            'ru_id' => $ru_id,
        ];

        $sort = 'click_count';
        $product = $this->categoryGoodsService->getMobileCategoryGoodsList($user_id, '', $children, $brand, '', '', '', $where_ext, 0, $number, 1, $sort);

        $product = $product ? $product : [];

        return $product;
    }

    /**
     * 已选则商品列表模块
     *
     * @param $goods_id
     * @param int $ru_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $uid 手机端登录会员ID
     * @return array
     */
    public function Checked($goods_id, $ru_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $uid = 0)
    {
        $goods = [];
        if (!empty($goods_id)) {

            $goods_id = $this->baseRepository->getExplode($goods_id);

            $res = Goods::whereIn('goods_id', $goods_id)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0)
                ->where('is_alone_sale', 1)
                ->where('is_show', 1);

            if ($this->config['review_goods']) {
                $res = $res->where('review_status', '>', 2);
            }

            if ($ru_id > 0) {
                $res = $res->where('user_id', $ru_id);
            }

            /* 关联地区 */
            $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

            if ($uid > 0) {
                $rank = $this->userCommonService->getUserRankByUid($uid);
                $user_rank = $rank['rank_id'];
                $discount = isset($rank['discount']) ? $rank['discount'] : 100;
            } else {
                $user_rank = 1;
                $discount = 100;
            }

            $where = [
                'warehouse_id' => $warehouse_id ?? 0,
                'area_id' => $area_id ?? 0,
                'area_city' => $area_city ?? 0,
                'area_pricetype' => $this->config['area_pricetype']
            ];

            $res = $res->with([
                'getMemberPrice' => function ($query) use ($user_rank) {
                    $query->where('user_rank', $user_rank);
                },
                'getWarehouseGoods' => function ($query) use ($where) {
                    if (isset($where['warehouse_id']) && $where['warehouse_id']) {
                        $query->where('region_id', $where['warehouse_id']);
                    }
                },
                'getWarehouseAreaGoods' => function ($query) use ($where) {
                    $query = $query->where('region_id', $where['area_id']);

                    if ($where['area_pricetype'] == 1) {
                        $query->where('city_id', $where['area_city']);
                    }
                }
            ]);

            $res = $this->baseRepository->getToArrayGet($res);

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

                    $price = $this->goodsCommonService->getGoodsPrice($price, $discount / 100, $row);

                    $row['shop_price'] = $price['shop_price'];
                    $row['promote_price'] = $price['promote_price'];
                    $row['goods_number'] = $price['goods_number'];

                    if ($row['promote_price'] > 0) {
                        $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    } else {
                        $promote_price = 0;
                    }

                    $goods[$key]['promote_price'] = $row['promote_price'];
                    $goods[$key]['shop_price'] = $row['shop_price'];

                    if ($promote_price > 0) {
                        $goods[$key]['shop_price_formated'] = $this->dscRepository->getPriceFormat($promote_price);
                    } else {
                        $goods[$key]['shop_price_formated'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                    }
                    $goods[$key]['market_price'] = $row['market_price'] ?? '';
                    $goods[$key]['market_price_formated'] = $this->dscRepository->getPriceFormat($row['market_price']);

                    $goods[$key]['goods_number'] = $row['goods_number'];
                    $goods[$key]['goods_id'] = $row['goods_id'];
                    $goods[$key]['title'] = $row['goods_name'];
                    $goods[$key]['sale'] = $row['sales_volume'];
                    $goods[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                    $goods[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                    $goods[$key]['url'] = dsc_url('/#/goods/' . $row['goods_id']);
                    $goods[$key]['app_page'] = config('route.goods.detail') . $row['goods_id'];
                }
            }
        }

        return $goods;
    }

    /**
     * 秒杀模块
     *
     * @param int $number
     * @return array|mixed
     * @throws \Exception
     */
    public function Seckill($number = 10)
    {
        $time = $this->timeRepository->getGmTime();

        $now = $time + 28800;

        $sec = SeckillTimeBucket::whereHas('getSeckillGoods', function ($query) use ($now) {
            $query->whereHas('getSeckill', function ($query) use ($now) {
                $query->where('is_putaway', 1)
                    ->where('review_status', 3)
                    ->where('begin_time', '<=', $now)
                    ->where('acti_time', '>', $now);
            });
        });

        $sec = $sec->orderBy('begin_time');

        $sec = $sec->get();

        $sec = $sec ? $sec->toArray() : [];

        if (empty($sec)) {
            return [];
        }

        foreach ($sec as $key => $val) {
            if ($val) {
                $sec[$key]['begin_time'] = $this->timeRepository->getLocalStrtoTime($val['begin_time']) + 28800;
                $sec[$key]['end_time'] = $this->timeRepository->getLocalStrtoTime($val['end_time']) + 28800;
                if ($now > $sec[$key]['begin_time'] && $now < $sec[$key]['end_time']) {
                    $arr['id'] = $val['id'];
                    $arr['begin_time'] = $sec[$key]['begin_time'];
                    $arr['end_time'] = $sec[$key]['end_time'];
                    $arr['type'] = 1; // 当前活动
                } elseif ($now < $sec[$key]['begin_time']) {
                    $all[$key]['id'] = $val['id'];
                    $all[$key]['begin_time'] = $sec[$key]['begin_time'];
                    $all[$key]['end_time'] = $sec[$key]['end_time'];
                    $all[$key]['type'] = 0; // 过期活动
                }
            }
        }

        $allsec = [];
        if (!empty($all)) {
            $allsec = array_values($all);
        }
        if (empty($arr['type'])) {
            $arr = [];
            $len = count($allsec);
            for ($i = 0; $i < $len; $i++) {
                if ($i == 0) {
                    $arr = $allsec[$i];
                    continue;
                }
                if ($allsec[$i]['begin_time'] < $arr['begin_time']) {
                    $arr = $allsec[$i];
                }
            }
        }

        if (empty($arr['id'])) {
            return [];
        }

        $goods_cache_name = 'visual_service_seckill_goods_' . $arr['id'];
        $sec_goods = cache($goods_cache_name);
        $sec_goods = !is_null($sec_goods) ? $sec_goods : [];

        if (empty($sec_goods)) {
            $sec_goods = SeckillGoods::where('tb_id', $arr['id']);

            $sec_goods = $sec_goods->whereHas('getSeckill', function ($query) use ($now) {
                $query->where('is_putaway', 1)
                    ->where('review_status', 3)
                    ->where('begin_time', '<=', $now)
                    ->where('acti_time', '>', $now);
            });

            $sec_goods = $sec_goods->with([
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'goods_name', 'market_price', 'goods_thumb');
                }
            ]);

            $sec_goods = $sec_goods->orderBy('goods_id', 'DESC');

            $sec_goods = $sec_goods->take($number);

            $sec_goods = $sec_goods->get();

            $sec_goods = $sec_goods ? $sec_goods->toArray() : [];

            cache()->forever($goods_cache_name, $sec_goods);
        }

        if ($sec_goods) {
            foreach ($sec_goods as $key => $value) {
                $arr['goods'][$key]['id'] = $value['id'];
                $arr['goods'][$key]['goods_id'] = $value['goods_id'];
                $arr['goods'][$key]['price'] = $value['sec_price'];
                $arr['goods'][$key]['price_formated'] = $this->dscRepository->getPriceFormat($value['sec_price']);
                $arr['goods'][$key]['shop_price'] = $arr['goods'][$key]['price'];
                $arr['goods'][$key]['shop_price_formated'] = $arr['goods'][$key]['price_formated'];
                $arr['goods'][$key]['stock'] = $value['sec_num'];

                /* 商品 */
                $goods = $value['get_goods'] ?? [];
                $goods['market_price'] = $goods['market_price'] ?? 0;
                $goods['goods_name'] = $goods['goods_name'] ?? '';
                $goods['goods_thumb'] = $goods['goods_thumb'] ?? '';

                $arr['goods'][$key]['market_price'] = $goods['market_price'] ?? 0;
                $arr['goods'][$key]['market_price_formated'] = $this->dscRepository->getPriceFormat($arr['goods'][$key]['market_price']);
                $arr['goods'][$key]['title'] = $goods['goods_name'];
                $arr['goods'][$key]['goods_thumb'] = empty($goods['goods_thumb']) ? '' : $this->dscRepository->getImagePath($goods['goods_thumb']);
                $arr['goods'][$key]['url'] = url('/#/seckill/detail') . '?' . http_build_query(['seckill_id' => $value['id'], 'tomorrow' => 0], '', '&');
                $arr['goods'][$key]['app_page'] = config('route.seckill.detail') . $value['id'] . '&tomorrow=0';
            }
        }

        return $arr;
    }

    /**
     * 店铺街
     *
     * @param int $childrenNumber
     * @param int $number
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function Store($childrenNumber = 100, $number = 10)
    {
        $cache_name = 'visual_service_store_' . $childrenNumber . '_' . $number;
        $store = cache($cache_name);
        $store = !is_null($store) ? $store : false;

        if ($store === false) {
            $store = MerchantsShopInformation::where('is_street', 1)
                ->where('shop_close', 1);

            $where = [
                'review_goods' => $this->config['review_goods'],
                'childrenNumber' => $childrenNumber
            ];

            $store = $store->with([
                'getGoods' => function ($query) use ($where) {

                    $query = $query->select('goods_id', 'user_id', 'goods_name', 'goods_thumb');

                    $query = $query->where('is_on_sale', 1)
                        ->where('is_alone_sale', 1)
                        ->where('is_delete', 0);

                    if ($where['review_goods'] == 1) {
                        $query = $query->where('review_status', '>', 2);
                    }

                    if ($where['childrenNumber'] > 0) {
                        $query->take($where['childrenNumber']);
                    }
                },
                'getSellerShopinfo'
            ]);

            $store = $store->orderBy('sort_order');

            $store = $store->take($number);

            $store = $store->get();

            $store = $store ? $store->toArray() : [];

            if ($store) {
                foreach ($store as $key => $value) {

                    $shopinfo = $value['get_seller_shopinfo'] ?? [];
                    $value['logo_thumb'] = $shopinfo['logo_thumb'] ?? '';
                    $value['street_thumb'] = $shopinfo['street_thumb'] ?? '';

                    $goods = $value['get_goods'] ?? [];

                    if ($goods) {
                        foreach ($goods as $a => $val) {
                            $goods[$a]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                        }
                    }

                    $store[$key]['goods'] = $goods;
                    $store[$key]['total'] = count($goods);
                    $store[$key]['logo_thumb'] = $this->dscRepository->getImagePath(str_replace('../', '', $value['logo_thumb']));
                    $store[$key]['street_thumb'] = $this->dscRepository->getImagePath($value['street_thumb']);

                    unset($value['get_seller_shopinfo']);
                    unset($value['get_goods']);
                }
            }

            cache()->forever($cache_name, $store);
        }

        return $store;
    }

    /**
     * 店铺街详情
     */
    public function StoreIn($ru_id = 0, $uid = 0)
    {
        $res = MerchantsShopInformation::from('merchants_shop_information as ms')
            ->select('ms.shop_id', 'ms.user_id', 'ms.rz_shopName', 'ss.logo_thumb', 'ss.street_thumb')
            ->leftjoin('seller_shopinfo as ss', 'ms.user_id', 'ss.ru_id')
            ->where('ms.user_id', $ru_id)
            ->get();
        $store = $res ? $res->toArray() : [];

        foreach ($store as $key => $value) {
            $store[$key]['total'] = $this->GoodsInfo($value['user_id']);
            $store[$key]['new'] = $this->GoodsInfo($value['user_id'], 1, 0);
            $store[$key]['promote'] = $this->GoodsInfo($value['user_id'], 0, 1);

            $store[$key]['logo_thumb'] = $this->dscRepository->getImagePath(str_replace('../', '', $value['logo_thumb']));
            $store[$key]['street_thumb'] = $this->dscRepository->getImagePath($value['street_thumb']);

            $follow = CollectStore::select('user_id')
                ->where('ru_id', $value['user_id'])
                ->where('user_id', $uid)
                ->count();

            $store[$key]['count_gaze'] = empty($follow) ? 0 : 1;

            $like_num = CollectStore::select('ru_id')
                ->where('ru_id', $value['user_id'])
                ->count();

            $store[$key]['like_num'] = empty($like_num) ? 0 : $like_num;
        }

        return $store;
    }

    public function GoodsInfo($user_id, $store_new = 0, $is_promote = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $res = Goods::select('goods_id')
            ->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('goods_number', '>', 0)
            ->where('user_id', $user_id);

        if ($store_new > 0) {
            $res = $res->where('store_new', 1);
        }

        if ($is_promote > 0) {
            $res = $res->where('is_promote', 1)
                ->where('promote_start_date', '<', $time)
                ->where('promote_end_date', '>', $time);
        }

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $res->count();

        return $res;
    }

    /**
     * 店铺街详情底部
     */
    public function StoreDown($ru_id = 0)
    {
        $res = MerchantsShopInformation::from('merchants_shop_information as ms')
            ->select('ms.shop_id', 'ms.user_id', 'ms.is_IM', 'ms.rz_shopName', 'ss.kf_qq', 'ss.kf_ww', 'ss.meiqia')
            ->leftjoin('seller_shopinfo as ss', 'ms.user_id', 'ss.ru_id')
            ->where('ms.user_id', $ru_id)
            ->get();
        $shop = $res ? $res->toArray() : [];

        $store = [];
        foreach ($shop as $key => $value) {
            $store[$key]['shop_id'] = $value['shop_id'];
            $store[$key]['user_id'] = $value['user_id'];
            $store[$key]['rz_shopName'] = $value['rz_shopName'];
            $store[$key]['shop_category'] = $this->store_category(0, $value['user_id']);
        }

        return $store;
    }

    /**
     * 关注店铺
     */
    public function AddCollect($ru_id = 0, $uid = 0)
    {
        $time = $this->timeRepository->getGmTime();

        if (!empty($ru_id) && $uid > 0) {
            $status = CollectStore::select('user_id', 'rec_id')
                ->where('ru_id', $ru_id)
                ->where('user_id', $uid)
                ->first();
            $status = $status ? $status->toArray() : [];
            if ($status) {
                CollectStore::where('rec_id', $status['rec_id'])->delete();

                $res = [
                    'count_gaze' => 0
                ];
                return $res;
            } else {
                $data = [
                    'user_id' => $uid,
                    'ru_id' => $ru_id,
                    'is_attention' => 1,
                    'add_time' => $time
                ];

                CollectStore::insertGetId($data);

                $res = [
                    'count_gaze' => 1
                ];
                return $res;
            }
        }
    }

    /**
     * 显示页面
     *
     * @param int $id
     * @param string $type
     * @param int $default
     * @param int $ru_id
     * @param int $number
     * @param int $page_id
     * @return array
     * @throws \Exception
     */
    public function View($id = 0, $type = 'index', $default = 0, $ru_id = 0, $number = 10, $page_id = 0)
    {

        $ru_id = is_null($ru_id) ? 0 : $ru_id;

        $model = TouchPageView::whereRaw('1');

        if ($id) {
            $res = $model->select('data')->where('id', $id)->first();
        } elseif ($default < 2) {
            if ($number == 0) {
                $res = $model->select('id', 'type', 'title', 'pic', 'thumb_pic', 'default')->where('default', $default)->where('ru_id', $ru_id)->where('page_id', $page_id)->orderBy('update_at', 'DESC')->get();
            } elseif ($number > 0) {
                $res = $model->select('id', 'type', 'title', 'pic', 'thumb_pic', 'default')->where('default', $default)->where('ru_id', $ru_id)->where('page_id', $page_id)->orderBy('update_at', 'DESC')->limit($number)->get();
            }
            $res = $res ? $res->toArray() : [];
            if ($res) {
                foreach ($res as $k => $v) {
                    $res[$k]['thumb_pic'] = (isset($v['thumb_pic']) && !empty($v['thumb_pic'])) ? $this->dscRepository->getImagePath($v['thumb_pic']) : '';
                }
            }

            return $res;
        } elseif ($default == 3) {
            // 左侧默认首页、自定义页
            if ($number == 0) {
                $res = $model->select('id', 'type', 'title', 'pic', 'thumb_pic', 'default')->where('ru_id', $ru_id)->orderBy('update_at', 'DESC')->get();
            } elseif ($number > 0) {
                $res = $model->select('id', 'type', 'title', 'pic', 'thumb_pic', 'default')->where('ru_id', $ru_id)->orderBy('update_at', 'DESC')->limit($number)->get();
            }
            $res = $res ? $res->toArray() : [];
            if ($res) {
                foreach ($res as $k => $v) {
                    $res[$k]['thumb_pic'] = (isset($v['thumb_pic']) && !empty($v['thumb_pic'])) ? $this->dscRepository->getImagePath($v['thumb_pic']) : '';
                }
            } else {
                TouchPageView::insert([
                    'ru_id' => $ru_id,
                    'type' => 'store',
                    'page_id' => 0,
                    'title' => lang('merchants_store.Shop_home'),
                    'data' => '',
                    'default' => 1,
                    'review_status' => 1,
                    'is_show' => 1,
                ]);
            }

            return $res;
        } else {
            $res = $model->select('data')->where('ru_id', $ru_id)->where('type', $type)->orderBy('update_at', 'DESC')->get();
        }

        $data = $res ? $res->toArray() : [];
        if (isset($data['data']) && $data['data']) {
            $data['data'] = $this->pageDataReplace($data['data']);
        }

        return $data;
    }

    /**
     * 可视化内容替换
     * @param string $content 可视化数据
     * @param bool $absolute
     * @return string
     */
    public function pageDataReplace($content = '', $absolute = false)
    {
        if ($absolute == true) {
            /**
             * 图片路径 绝对路径转相对路径 （用于保存数据库）
             */
            $label = [
                '/storage\//' => '',
                '/\"img\"\:\"(http|https)\:\/\/(.*?)\/(.*?)\"/' => '"img":"../$3"',
                '/\"productImg\"\:\"(http|https)\:\/\/(.*?)\/(.*?)\"/' => '"productImg":"../$3"',
                '/\"titleImg\"\:\"(http|https)\:\/\/(.*?)\/(.*?)\"/' => '"titleImg":"../$3"',
            ];
        } else {
            if ($this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $url = $bucket_info['endpoint'] ?? '';
            } else {
                $url = Storage::url('/');
            }

            $label = [
                /**
                 * 图片路径 相对路径转绝对路径 （用于显示）
                 */
                '/\"img\"\:\"..\/(.*?)\"/' => '"img":"' . $url . '$1"',
                '/\"productImg\"\:\"..\/(.*?)\"/' => '"productImg":"' . $url . '$1"',
                '/\"titleImg\"\:\"..\/(.*?)\"/' => '"titleImg":"' . $url . '$1"',
            ];
        }

        foreach ($label as $key => $value) {
            $content = preg_replace($key, $value, $content);
        }
        return $content;
    }

    /**
     * 文章分类
     * @param int $tree_id
     * @return array
     */
    public function article_tree($tree_id = 0)
    {
        $res = ArticleCat::select('cat_id', 'cat_name')
            ->where('parent_id', $tree_id)
            ->orderBy('sort_order', 'ASC')
            ->get();
        $res = $res ? $res->toArray() : [];

        $three_arr = [];
        foreach ($res as $k => $row) {
            $three_arr[$k]['cat_id'] = $row['cat_id'];
            $three_arr[$k]['cat_name'] = $row['cat_name'];
            $three_arr[$k]['haschild'] = 0;

            if (isset($row['cat_id'])) {
                $child_tree = $this->article_tree($row['cat_id']);
                if ($child_tree) {
                    $three_arr[$k]['tree'] = $child_tree;
                    $three_arr[$k]['haschild'] = 1;
                }
            }
        }

        return $three_arr;
    }

    /**
     * 获得指定分类下所有底层分类的ID
     *
     * @access  public
     * @param integer $cat_id 指定的分类ID
     * @return  string
     */
    public function get_children($cat_id = 0)
    {
        $cat_keys = $this->get_array_keys_cat($cat_id);

        if ($cat_id > 0) {
            $cat_keys = collect($cat_keys)->concat([$cat_id])->all();// 追加当前分类id
        }

        return $cat_keys;
    }

    /**
     * 分类id数组 多维转一维
     * @param int $cat_id
     * @return array|bool
     */
    protected function get_array_keys_cat($cat_id = 0)
    {
        // 商品分类所有子分类id 多维数组
        $category_tree = $this->category_tree($cat_id);

        $chid_arr = [];
        if ($category_tree) {
            foreach ($category_tree as $k => $v) {
                $chid_arr[$k]['cat_id'] = $v['cat_id'];
            }
        }

        $one_dimensional_array = isset($chid_arr) ? $this->arr_foreach($chid_arr) : [];

        return $one_dimensional_array;
    }

    //多维数组转为一维数组
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
     * 商品分类
     * @param int $cat_id
     * @param int $level 是否显示子分类
     * @return array
     */
    public function cat_list($cat_id = 0, $level = 0)
    {
        $model = Category::select('cat_id', 'cat_name', 'cat_alias_name', 'parent_id')
            ->where('parent_id', $cat_id)
            ->where('is_show', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('cat_id', 'DESC');

        $total = $model->count();

        $res = $model->get();

        $res = $res ? $res->toArray() : [];

        $three_arr = [];
        foreach ($res as $k => $row) {
            $three_arr[$k]['cat_id'] = $row['cat_id'];
            $three_arr[$k]['cat_name'] = !empty($row['cat_alias_name']) ? $row['cat_alias_name'] : $row['cat_name'];
            $three_arr[$k]['url'] = dsc_url('/#/list/' . $row['cat_id']);
            $three_arr[$k]['app_page'] = config('route.goods.list') . $row['cat_id'];
            $three_arr[$k]['parent_id'] = $row['parent_id'];
            $three_arr[$k]['haschild'] = 0;

            $three_arr[$k]['level'] = $level;
            //$three_arr[$k]['select'] = str_repeat('&nbsp;', $three_arr[$k]['level'] * 4);

            if (isset($row['cat_id']) && $level > 0) {
                $child_tree = $this->cat_list($row['cat_id'], $level + 1);
                if ($child_tree && !empty($child_tree['category'])) {
                    $three_arr[$k]['child_tree'] = $child_tree['category'];
                    $three_arr[$k]['total'] = count($child_tree);
                    $three_arr[$k]['haschild'] = 1;
                }
            }
        }

        return ['category' => $three_arr, 'total' => $total];
    }

    /**
     * 文章分类
     * @param int $cat_id 文章分类id
     * @param int $level 是否显示子分类
     * @return array
     */
    public function article_list($cat_id = 0, $level = 0)
    {
        $model = ArticleCat::where('parent_id', $cat_id);

        $model = $model->select('cat_id', 'cat_name', 'parent_id')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('cat_id', 'DESC');

        $total = $model->count();

        $article = $model->get();
        $article = $article ? $article->toArray() : [];

        $three_arr = [];
        foreach ($article as $k => $row) {
            $three_arr[$k]['cat_id'] = $row['cat_id'];
            $three_arr[$k]['cat_name'] = $row['cat_name'];
            $three_arr[$k]['url'] = dsc_url('/#/article') . '?cat_id=' . $row['cat_id'];
            $three_arr[$k]['parent_id'] = $row['parent_id'];
            $three_arr[$k]['haschild'] = 0;

            $three_arr[$k]['level'] = $level;
            //$three_arr[$k]['select'] = str_repeat('&nbsp;', $three_arr[$k]['level'] * 4);

            if (isset($row['cat_id']) && $level > 0) {
                $child_tree = $this->article_list($row['cat_id'], $level + 1);
                if ($child_tree && !empty($child_tree['article'])) {
                    $three_arr[$k]['child_tree'] = $child_tree['article'];
                    $three_arr[$k]['total'] = count($child_tree);
                    $three_arr[$k]['haschild'] = 1;
                }
            }
        }

        return ['article' => $three_arr, 'total' => $total];
    }

    //店铺分类导航
    public function store_category($cat_id = 0, $ru_id = 0, $level = 0)
    {
        $res = MerchantsCategory::select('cat_id', 'cat_name', 'parent_id', 'user_id')
            ->where('user_id', $ru_id)
            ->where('is_show', 1)
            ->where('parent_id', $cat_id)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('cat_id', 'DESC')
            ->get();
        $res = $res ? $res->toArray() : [];

        $three_arr = [];
        foreach ($res as $k => $row) {
            $three_arr[$k]['cat_id'] = $row['cat_id'];
            $three_arr[$k]['cat_name'] = $row['cat_name'];
            $three_arr[$k]['parent_id'] = $row['parent_id'];
            $three_arr[$k]['url'] = dsc_url('/#/ShopGoodsList') . '?' . http_build_query(['cat_id' => $row['cat_id'], 'ru_id' => $ru_id], '', '&');
            $three_arr[$k]['opennew'] = 0;

            $three_arr[$k]['haschild'] = 0;
            $three_arr[$k]['level'] = $level;

            if (isset($row['cat_id']) && $level > 0) {
                $child_tree = $this->store_category($row['cat_id'], $ru_id, $level + 1);
                if ($child_tree) {
                    $three_arr[$k]['child'] = $child_tree;
                    $three_arr[$k]['haschild'] = 1;
                }
            }
        }

        return $three_arr;
    }


    /**
     * 更新页面
     *
     * @param $id
     * @param array $data
     * @param string $pic
     * @return bool
     */
    public function save_page($id, $data = [], $pic = '')
    {
        if ($id) {
            $time = $this->timeRepository->getGmTime();

            $model = TouchPageView::where('id', $id);

            $res = $model->first();
            $res = $res ? $res->toArray() : [];

            if ($res) {
                // 保存图片数据路径为相对路径
                if ($data) {
                    $data = $this->pageDataReplace($data, true);
                }
                $keep = [
                    'data' => !empty($data) ? $data : $res['data'],
                    'pic' => !empty($pic) ? $pic : $res['pic'],
                    'update_at' => $time
                ];

                $model->update($keep);

                $cache_id = md5(serialize($id));
                cache()->forget('visual.view' . $cache_id);

                return true;
            }


        }
        return false;
    }

    /**
     * 删除页面
     * @param int $id
     * @return bool
     */
    public function del_page($id = 0)
    {
        if ($id) {
            return TouchPageView::where('id', $id)->delete();
        }
        return false;
    }

    /**
     *  品牌列表
     * @return array
     */
    public function brand_list($num = 100)
    {
        $brand = Brand::where(['is_show' => 1])
            ->limit($num)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('brand_id', 'DESC')
            ->get();

        if ($brand) {
            foreach ($brand as $k => $val) {
                $brand[$k]['brand_logo'] = $this->dscRepository->getImagePath($val['brand_logo']);
                $brand[$k]['index_img'] = $this->dscRepository->getImagePath($val['index_img']);
            }
        }

        return $brand ? $brand->toArray() : [];
    }

    /**
     * 创建相册
     *
     * @param int $ru_id
     * @param string $album_mame
     */
    public function make_gallery_action($ru_id = 0, $album_mame = '')
    {
        $time = $this->timeRepository->getGmTime();

        $data = [
            'ru_id' => $ru_id,
            'album_mame' => $album_mame,
            'sort_order' => 50,
            'add_time' => $time,
        ];
        return GalleryAlbum::create($data);
    }

    /**
     * 图库列表
     *
     * @param int $ru_id
     * @param int $album_id
     * @param string $thumb
     * @param int $pageSize
     * @return array
     */
    public function picture_list($ru_id = 0, $album_id = 0, $thumb = '', $pageSize = 15)
    {
        $model = PicAlbum::where(['ru_id' => $ru_id, 'album_id' => $album_id]);

        $list = $model->orderBy('pic_id', 'desc')
            ->paginate($pageSize);

        $res = [];
        foreach ($list as $key => $vo) {
            $res[$key]['id'] = $vo['pic_id'];
            $res[$key]['desc'] = $vo['pic_name'];
            $res[$key]['img'] = $this->dscRepository->getImagePath($vo['pic_file']);
            $res[$key]['isSelect'] = false;
        }

        $total = $model->count();

        return ['res' => $res, 'total' => $total];
    }

    /**
     * 相册或图片
     *
     * @param string $type
     * @param int $ru_id
     * @param int $album_id
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     */
    public function get_thumb($type = '', $ru_id = 0, $album_id = 0, $pageSize = 10, $currentPage = 1)
    {
        $data = [];
        if ($type == 'thumb') {
            // 左侧相册列表
            $model = GalleryAlbum::where(['ru_id' => $ru_id, 'parent_album_id' => 0]);

            $total = $model->count();

            $gallery = $model->orderBy('add_time', 'DESC')
                ->get();
            $gallery = $gallery ? $gallery->toArray() : [];

            if ($gallery) {
                foreach ($gallery as $key => $value) {
                    $gallery[$key] = [
                        'album_id' => $value['album_id'],
                        'name' => $value['album_mame']
                    ];
                    $tree = GalleryAlbum::select('album_id', 'album_mame')
                        ->where(['parent_album_id' => $value['album_id']])
                        ->orderBy('add_time', 'DESC')
                        ->get();
                    $tree = $tree ? $tree->toArray() : [];
                    $gallery[$key]['tree'] = $tree;
                }
            }

            $data = ['thumb' => $gallery, 'total' => $total];
        } elseif ($type == 'img') {
            // 图片列表
            $current = ($currentPage == 1) ? 0 : ($currentPage - 1) * $pageSize;

            $model = PicAlbum::where(['album_id' => $album_id, 'ru_id' => $ru_id]);

            $total = $model->count();

            $pic = $model->select('pic_id', 'pic_name', 'pic_file')
                ->orderBy('add_time', 'DESC')
                ->offset($current)
                ->limit($pageSize)
                ->get();
            $pic = $pic ? $pic->toArray() : [];

            if ($pic) {
                foreach ($pic as $key => $value) {
                    $pic[$key]['pic_file'] = $this->dscRepository->getImagePath($value['pic_file']);
                }
            }

            $data = ['img' => $pic, 'total' => $total];
        }

        return $data;
    }

    /**
     * 可选导航链接 平台
     * @param string $type
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     */
    public function get_url($type = '', $pageSize = 10, $currentPage = 1)
    {
        $url = [];
        $current = ($currentPage == 1) ? 0 : ($currentPage - 1) * $pageSize;

        if ($type == 'function') {
            $url = [
                ['cat_id' => '1', 'cat_name' => lang('common.category'), 'parent_id' => 0, 'url' => dsc_url('/#/catalog'), 'app_page' => config('route.catalog.index')],
                ['cat_id' => '2', 'cat_name' => lang('common.cat_list'), 'parent_id' => 0, 'url' => dsc_url('/#/cart'), 'app_page' => config('route.cart.index')],
                ['cat_id' => '3', 'cat_name' => lang('common.user_center'), 'parent_id' => 0, 'url' => dsc_url('/#/user'), 'app_page' => config('route.user.index')],
                ['cat_id' => '4', 'cat_name' => lang('common.store_street'), 'parent_id' => 0, 'url' => dsc_url('/#/shop'), 'app_page' => config('route.shop.index')],
                ['cat_id' => '5', 'cat_name' => lang('common.brand_street'), 'parent_id' => 0, 'url' => dsc_url('/#/brand'), 'app_page' => config('route.brand.index')],
                ['cat_id' => '6', 'cat_name' => lang('common.mini_sns'), 'parent_id' => 0, 'url' => dsc_url('/#/discover'), 'app_page' => config('route.discover.index')],
            ];

            return ['url' => $url, 'total' => count($url), 'page' => $currentPage];
        } elseif ($type == 'activity') {
            $url = [
                ['cat_id' => 1, 'cat_name' => lang('common.rec_txt.2'), 'parent_id' => 0, 'url' => dsc_url('/#/groupbuy'), 'app_page' => config('route.groupbuy.index')],
                ['cat_id' => 2, 'cat_name' => lang('common.rec_txt.5'), 'parent_id' => 0, 'url' => dsc_url('/#/exchange'), 'app_page' => config('route.exchange.index')],
                //['cat_id' => 3, 'cat_name' => lang('common.rec_txt.8'), 'parent_id' => 0, 'url' => url('mobile/#/crowdfunding'), 'url' => config('route.crowdfunding.index')], // 暂时先注释 功能完善后可显示
                ['cat_id' => 4, 'cat_name' => lang('common.rec_txt.7'), 'parent_id' => 0, 'url' => dsc_url('/#/topic'), 'app_page' => config('route.topic.index')],
                ['cat_id' => 5, 'cat_name' => lang('common.rec_txt.9'), 'parent_id' => 0, 'url' => dsc_url('/#/activity'), 'app_page' => config('route.activity.index')],
                ['cat_id' => 6, 'cat_name' => lang('common.rec_txt.3'), 'parent_id' => 0, 'url' => dsc_url('/#/auction'), 'app_page' => config('route.auction.index')],
                ['cat_id' => 7, 'cat_name' => lang('common.rec_txt.10'), 'parent_id' => 0, 'url' => dsc_url('/#/seckill'), 'app_page' => config('route.seckill.index')],
                ['cat_id' => 8, 'cat_name' => lang('common.rec_txt.11'), 'parent_id' => 0, 'url' => dsc_url('/#/package'), 'app_page' => config('route.package.index')]
            ];
            // 如果有拼团模块则显示
            if (file_exists(MOBILE_TEAM)) {
                $team = [['cat_id' => 9, 'cat_name' => lang('common.rec_txt.12'), 'parent_id' => 0, 'url' => dsc_url('/#/team'), 'app_page' => config('route.team.index')]];
                $url = collect($url)->merge($team)->all();
            }

            // 如果有砍价模块则显示
            if (file_exists(MOBILE_BARGAIN)) {
                $team = [['cat_id' => 10, 'cat_name' => lang('common.rec_txt.13'), 'parent_id' => 0, 'url' => dsc_url('/#/bargain'), 'app_page' => config('route.bargain.index')]];
                $url = collect($url)->merge($team)->all();
            }

            return ['url' => $url, 'total' => count($url), 'page' => $currentPage];
        } elseif ($type == 'category') {
            // 分类显示子分类
            $list = $this->cat_list(0, 1);
            // 分类分页
            $url = collect($list['category'])->slice($current, $pageSize)->values()->all();

            return ['url' => $url, 'total' => $list['total'], 'page' => $currentPage];
        } elseif ($type == 'article') {
            // 文章显示子分类
            $list = $this->article_list(0, 1);
            // 文章分页
            $url = collect($list['article'])->slice($current, $pageSize)->values()->all();

            return ['url' => $url, 'total' => $list['total'], 'page' => $currentPage];
        } elseif ($type == 'topic') {
            $time = $this->timeRepository->getGmTime();
            $model = TouchTopic::where('review_status', 3)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>', $time);

            $total = $model->count();

            $list = $model->offset($current)
                ->limit($pageSize)
                ->get();
            $list = $list ? $list->toArray() : [];

            $url = [];
            if ($list) {
                foreach ($list as $key => $value) {
                    $url[$key] = [
                        'cat_id' => $value['topic_id'],
                        'cat_name' => $value['name'],
                        'parent_id' => 0,
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'topic_img' => $this->dscRepository->getImagePath($value['topic_img']),
                        'url' => dsc_url('/#/topic/detail/' . $value['cat_id']),
                        'app_page' => config('route.topic.detail') . $value['cat_id'],
                    ];
                }
            }

            return ['url' => $url, 'total' => $total, 'page' => $currentPage];
        }

        return $url;
    }

    /**
     * 可选导航链接 - 商家
     *
     * @param string $type
     * @param int $ru_id
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     */
    public function get_seller_url($type = '', $ru_id = 0, $pageSize = 10, $currentPage = 1)
    {
        $url = [];
        $current = ($currentPage == 1) ? 0 : ($currentPage - 1) * $pageSize;

        if ($type == 'category') {
            $model = MerchantsCategory::select('cat_id', 'cat_name', 'parent_id')
                ->where(['parent_id' => 0, 'is_show' => 1, 'user_id' => $ru_id]);

            $total = $model->count();

            $url = $model->offset($current)
                ->limit($pageSize)
                ->get();
            $url = $url ? $url->toArray() : [];

            foreach ($url as $key => $value) {
                $url[$key]['url'] = dsc_url('/#/ShopGoodsList') . '?' . http_build_query(['ru_id' => $ru_id, 'cat_id' => $value['cat_id']], '', '&');
            }

            return ['url' => $url, 'total' => $total, 'page' => $currentPage];
        }

        return $url;
    }

    /**
     * 添加自定义页面 专题页
     *
     * @param int $id
     * @param string $type
     * @param int $ru_id
     * @param int $page_id
     * @param string $description
     * @param string $title
     * @param array $data
     * @return array
     */
    public function add_topic_page($id = 0, $type = 'topic', $ru_id = 0, $page_id = 0, $title = '', $description = '', $data = [])
    {
        $time = $this->timeRepository->getGmTime();
        if ($id) {
            // 编辑
            $view = TouchPageView::select('id', 'title', 'description', 'thumb_pic')
                ->where(['id' => $id, 'type' => $type])
                ->first();
            $view = $view ? $view->toArray() : [];

            if ($view) {
                $upload_file = isset($data['file']) && !empty($data['file']) ? $data['file'] : ''; // 上传图片
                $pic = (isset($view['thumb_pic']) && !empty($view['thumb_pic'])) ? $view['thumb_pic'] : $upload_file;
                $keep = [
                    'ru_id' => $ru_id,
                    'title' => !empty($title) ? $title : (isset($view['title']) ? $view['title'] : ''),
                    'thumb_pic' => $pic,
                    'description' => !empty($description) ? $description : (isset($view['description']) ? $view['description'] : ''),
                    'update_at' => $time
                ];
                TouchPageView::where(['id' => $id, 'type' => $type])->update($keep);

                $page = TouchPageView::where(['id' => $id])->first();
                $page = $page ? $page->toArray() : [];

                $page['thumb_pic'] = (isset($page['thumb_pic']) && !empty($page['thumb_pic'])) ? $this->dscRepository->getImagePath($page['thumb_pic']) : '';

                return ['status' => 0, 'pic_url' => $page['thumb_pic'], 'page' => $page, 'msg' => 'save success'];
            } else {
                return ['status' => 1, 'msg' => 'add error'];
            }
        } else {
            // 添加
            $num = 0;
            if ($page_id > 0) {
                $num = TouchPageView::select('id', 'page_id', 'title', 'description', 'thumb_pic')->where(['page_id' => $page_id])->count();
            }
            if ($num < 1) {
                $keep = [
                    'ru_id' => $ru_id,
                    'type' => $type,
                    'title' => !empty($title) ? $title : '',
                    'page_id' => $page_id,
                    'thumb_pic' => !empty($data['file']) ? $data['file'] : '',
                    'description' => !empty($description) ? $description : '',
                    'create_at' => $time
                ];
                $new_id = TouchPageView::insertGetId($keep);

                $page = TouchPageView::where('id', $new_id)->first();
                $page = $page ? $page->toArray() : [];

                $page['thumb_pic'] = (isset($page['thumb_pic']) && !empty($page['thumb_pic'])) ? $this->dscRepository->getImagePath($page['thumb_pic']) : '';

                return ['status' => 0, 'pic_url' => $page['thumb_pic'], 'page' => $page, 'msg' => 'add success'];
            } else {
                $page = TouchPageView::where(['page_id' => $page_id])->first();
                $page = $page ? $page->toArray() : [];
                if ($page) {
                    return ['status' => 1, 'msg' => 'page exist', 'page' => $page];
                }
            }
        }
    }

    /**
     * 搜索商品
     * @param int $ru_id
     * @param string $keywords
     * @param int $cat_id
     * @param int $brand_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     */
    public function search_goods($ru_id = 0, $keywords = '', $cat_id = 0, $brand_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $pageSize = 10, $currentPage = 1)
    {
        $current = ($currentPage == 1) ? 0 : ($currentPage - 1) * intval($pageSize);

        $model = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        if ($this->config['review_goods']) {
            $model = $model->where('review_status', '>', 2);
        }

        if ($ru_id) {
            $model = $model->where('user_id', $ru_id);
        }
        if ($cat_id) {
            $model = $model->where('cat_id', $cat_id);
        }
        if ($brand_id) {
            $model = $model->where('brand_id', $brand_id);
        }
        if ($keywords) {
            $model = $model->where(function ($query) use ($keywords) {
                $query->where('goods_name', 'like', '%' . $keywords . '%')
                    ->orWhere('goods_sn', 'like', '%' . $keywords . '%')
                    ->orWhere('keywords', 'like', '%' . $keywords . '%');
            });
        }

        $user_rank = 1;

        $model = $model->whereHas('getSellerShopInfo', function ($query) {
            $query->where('shop_close', 1);
        });

        $where = [
            'area_pricetype' => $this->config['area_pricetype'],
            'area_id' => $area_id,
            'area_city' => $area_city
        ];

        $model = $model->with([
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
            'getShopInfo' => function ($query) {
                $query->select('user_id', 'self_run');
            },
        ]);

        $total = $model->count();

        $goods = $model->select('goods_id', 'user_id', 'goods_name', 'goods_name_style', 'comments_number', 'sales_volume', 'market_price', 'is_new', 'is_best', 'is_hot', 'promote_start_date', 'promote_end_date', 'is_promote', 'shop_price', 'goods_brief', 'goods_thumb', 'goods_img')
            ->offset($current)
            ->limit($pageSize)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('goods_id', 'DESC')
            ->get();
        $goods = $goods ? $goods->toArray() : [];

        foreach ($goods as $key => $val) {
            $goods[$key] = collect($val)->merge($val['get_shop_info'])->except('get_shop_info')->all();

            if (isset($val['promote_price']) && $val['promote_price'] > 0) {
                $promote_price = $this->goodsCommonService->getBargainPrice($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $goods[$key]['promote_price'] = $val['promote_price'] ?? 0;
            $goods[$key]['shop_price'] = $val['shop_price'] ?? 0;
            $goods[$key]['market_price'] = $val['market_price'] ?? 0;

            if ($promote_price > 0) {
                $goods[$key]['shop_price_formated'] = $this->dscRepository->getPriceFormat($promote_price);
            } else {
                $goods[$key]['shop_price_formated'] = $this->dscRepository->getPriceFormat($val['shop_price']);
            }
            $goods[$key]['market_price_formated'] = $this->dscRepository->getPriceFormat($val['market_price']);

            $goods[$key]['goods_img'] = $this->dscRepository->getImagePath($val['goods_img']);
            $goods[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
            $goods[$key]['goods_img'] = $this->dscRepository->getImagePath($val['goods_img']);
        }

        return ['goods' => $goods, 'total' => $total];
    }

    /**
     * 翻译POST数据类型
     * @param array $data
     * @return array
     */
    public function transform($data = [])
    {
        if (!empty($data)) {
            foreach ($data as $key => $vo) {
                if (is_array($vo)) {
                    $data[$key] = $this->transform($vo);
                } else {
                    if ($vo === 'true') {
                        $data[$key] = true;
                    }
                    if ($vo === 'false' || $key === 'setting') {
                        $data[$key] = false;
                    }
                }
            }
            return $data;
        }
    }

    /**
     * 递归显示所有子分类
     * @param int $cat_id
     * @param int $level
     * @return array
     */
    protected function category_tree($cat_id = 0, $level = 0)
    {
        $res = Category::select('cat_id', 'cat_name', 'parent_id')
            ->where('parent_id', $cat_id)
            ->where('is_show', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('cat_id', 'DESC')
            ->get();
        $res = $res ? $res->toArray() : [];

        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        static $list = [];
        foreach ($res as $key => $value) {
            //第一次遍历,找到父节点为根节点的节点 也就是parent_id=0的节点
            if ($value['parent_id'] == $cat_id) {
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['level'] = $level;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($res[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                $this->category_tree($value['cat_id'], $level + 1);
            }
        }

        return $list;
    }
}
