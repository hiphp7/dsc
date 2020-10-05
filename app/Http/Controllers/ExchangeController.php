<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\GoodsAttr;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\ExchangeService;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryService;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;

/**
 * 积分商城
 */
class ExchangeController extends InitController
{
    protected $areaService;
    protected $exchangeService;
    protected $categoryService;
    protected $goodsService;
    protected $dscRepository;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $goodsWarehouseService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $userCommonService;
    protected $config;
    protected $articleCommonService;
    protected $cartCommonService;
    protected $cartRepository;

    public function __construct(
        AreaService $areaService,
        ExchangeService $exchangeService,
        CategoryService $categoryService,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        GoodsWarehouseService $goodsWarehouseService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        CartCommonService $cartCommonService,
        CartRepository $cartRepository
    )
    {
        $this->areaService = $areaService;
        $this->exchangeService = $exchangeService;
        $this->categoryService = $categoryService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->userCommonService = $userCommonService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->cartRepository = $cartRepository;
    }

    public function index()
    {
        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        /*------------------------------------------------------ */
        //-- act 操作项的初始化
        /*------------------------------------------------------ */
        $act = addslashes(request()->input('act', 'list'));
        $act = $act ? $act : 'list';
        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        $user_id = intval(session('user_id', 0));

        /*------------------------------------------------------ */
        //-- 积分兑换商品列表
        /*------------------------------------------------------ */
        if ($act == 'list') {

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/exchange');
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            //瀑布流 by wu start
            $this->smarty->assign('category_load_type', $this->config['category_load_type']);
            $this->smarty->assign('query_string', preg_replace('/act=\w+&?/', '', request()->server('QUERY_STRING')));
            //瀑布流 by wu end

            /* 初始化分页信息 */
            $page = (int)request()->input('page', 1);

            $size = isset($this->config['page_size']) && intval($this->config['exchange_size']) > 0 ? intval($this->config['exchange_size']) : 10;
            $cat_id = (int)request()->input('cat_id', 0);
            $integral_max = (int)request()->input('integral_max', 0);
            $integral_min = (int)request()->input('integral_min', 0);

            /* 排序、显示方式以及类型 */
            $default_display_type = $this->config['show_order_type'] == '0' ? 'list' : ($this->config['show_order_type'] == '1' ? 'grid' : 'text');
            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'goods_id' : ($this->config['sort_order_type'] == '1' ? 'sales_volume' : 'is_exchange');

            $sort = $default_sort_order_type;
            if (request()->has('sort')) {
                $get_sort = addslashes(request()->input('sort'));
                if (in_array(trim(strtolower($get_sort)), ['goods_id', 'sales_volume', 'exchange_integral', 'is_exchange'])) {
                    $sort = $get_sort;
                }
            }

            $order = $default_sort_order_method;
            if (request()->has('order')) {
                $get_order = addslashes(request()->input('order'));
                if (in_array(trim(strtoupper($get_order)), ['ASC', 'DESC'])) {
                    $order = $get_order;
                }
            }

            $ecsCookie = request()->cookie('ECS');
            $display = isset($ecsCookie['display']) ? $ecsCookie['display'] : $default_display_type;
            if (request()->has('display')) {
                $get_display = addslashes(request()->input('display'));
                if (in_array(trim(strtolower($get_display)), ['list', 'grid', 'text'])) {
                    $display = $get_display;
                }
            }

            $display = in_array($display, ['list', 'grid', 'text']) ? $display : 'text';

            cookie()->queue('ECS[display]', $display, 60 * 24 * 7);

            /* 页面的缓存ID */
            $cache_id = sprintf('%X', crc32($cat_id . '-' . $display . '-' . $sort . '-' . $order . '-' . $page . '-' . $size . '-' . session('user_rank') . '-' .
                $this->config['lang'] . '-' . $integral_max . '-' . $integral_min));

            $content = cache()->remember('exchange_list.dwt.' . $cache_id, $this->config['cache_time'], function () use ($user_id, $display, $order, $sort, $page, $size, $area_city, $area_id, $warehouse_id, $integral_max, $integral_min, $cat_id) {
                /* 如果页面没有被缓存则重新获取页面的内容 */
                $children = $this->categoryService->getCatListChildren($cat_id);

                $cat = Category::catInfo($cat_id)->first();
                $cat = $cat ? $cat->toArray() : [];

                if (!empty($cat)) {
                    $this->smarty->assign('keywords', htmlspecialchars($cat['keywords']));
                    $this->smarty->assign('description', htmlspecialchars($cat['cat_desc']));
                }

                assign_template();

                $position = assign_ur_here('exchange');
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                $categories_pro = get_category_tree_leve_one();
                $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());              // 网店帮助

                /* 调查 */
                $vote = get_vote();
                if (!empty($vote)) {
                    $this->smarty->assign('vote_id', $vote['id']);
                    $this->smarty->assign('vote', $vote['content']);
                }

                $where = [
                    'type' => 'best',
                    'cats' => $children,
                    'min' => $integral_min,
                    'max' => $integral_max,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];

                $best_goods = $this->exchangeService->getExchangeRecommendGoods($where);
                $this->smarty->assign('best_goods', $best_goods); //精品

                $where = [
                    'type' => 'hot',
                    'cats' => $children,
                    'min' => $integral_min,
                    'max' => $integral_max,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];

                $hot_goods = $this->exchangeService->getExchangeRecommendGoods($where);
                $this->smarty->assign('hot_goods', $hot_goods);  //热门

                $count = $this->exchangeService->getExchangeGoodsCount($children, $integral_min, $integral_max);

                $max_page = ($count > 0) ? ceil($count / $size) : 1;
                if ($page > $max_page) {
                    $page = $max_page;
                }
                $goodslist = $this->exchangeService->getExchangeGetGoods($children, $integral_min, $integral_max, $size, $page, $sort, $order);
                if ($display == 'grid') {
                    if (count($goodslist) % 2 != 0) {
                        $goodslist[] = [];
                    }
                }
                $this->smarty->assign('goods_list', $goodslist);
                $this->smarty->assign('category', $cat_id);
                $this->smarty->assign('integral_max', $integral_max);
                $this->smarty->assign('integral_min', $integral_min);

                //瀑布流 by wu start
                if (!$this->config['category_load_type']) {
                    assign_pager('exchange', $cat_id, $count, $size, $sort, $order, $page, '', '', $integral_min, $integral_max, $display); // 分页
                }
                //瀑布流 by wu end

                $exchange_top_banner = '';
                /* 广告位 */
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $exchange_top_banner .= "'activity_top_ad_exchange" . $i . ","; //轮播图
                }
                $this->smarty->assign('activity_top_banner', $exchange_top_banner);
                if ($user_id > 0) {
                    $this->smarty->assign('info', $this->userCommonService->getUserDefault($user_id));
                }
                $this->smarty->assign('cat_id', $cat_id);

                assign_dynamic('exchange_list'); //动态内容

                $this->smarty->assign('category', 9999999999999999999);//by zhuo 凡是程序有这个的都不可去掉，有用

                //获取seo start
                $seo = get_seo_words('change');

                if ($seo) {
                    foreach ($seo as $key => $value) {
                        $seo[$key] = str_replace(['{sitename}', '{key}', '{description}'], [$position['title'], $this->config['shop_keywords'], $this->config['shop_desc']], $value);
                    }
                }

                if (isset($seo['keywords']) && !empty($seo['keywords'])) {
                    $this->smarty->assign('keywords', htmlspecialchars($seo['keywords']));
                } else {
                    $this->smarty->assign('keywords', htmlspecialchars($this->config['shop_keywords']));
                }

                if (isset($seo['description']) && !empty($seo['description'])) {
                    $this->smarty->assign('description', htmlspecialchars($seo['description']));
                } else {
                    $this->smarty->assign('description', htmlspecialchars($this->config['shop_desc']));
                }

                if (isset($seo['title']) && !empty($seo['title'])) {
                    $this->smarty->assign('page_title', htmlspecialchars($seo['title']));
                } else {
                    $this->smarty->assign('page_title', $position['title']);
                }
                //获取seo end

                $this->smarty->assign('shop_name', $this->config['shop_name']);

                $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-typeexchange.xml" : 'feed.php?type=exchange'); // RSS URL

                return $this->smarty->display('exchange_list.dwt');
            });

            return $content;
        }

        /*------------------------------------------------------ */
        //-- 瀑布流 by wu
        /*------------------------------------------------------ */
        if ($act == 'load_more_goods') {

            /* 初始化分页信息 */
            $page = (int)request()->input('page', 1);
            $size = isset($this->config['page_size']) && intval($this->config['exchange_size']) > 0 ? intval($this->config['exchange_size']) : 10;
            $cat_id = (int)request()->input('cat_id', 0);
            $integral_max = (int)request()->input('integral_max', 0);
            $integral_min = (int)request()->input('integral_min', 0);

            /* 排序、显示方式以及类型 */
            $default_display_type = $this->config['show_order_type'] == '0' ? 'list' : ($this->config['show_order_type'] == '1' ? 'grid' : 'text');
            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'goods_id' : ($this->config['sort_order_type'] == '1' ? 'sales_volume' : 'is_exchange');

            $sort = $default_sort_order_type;
            if (request()->has('sort')) {
                $get_sort = addslashes(request()->input('sort'));
                if (in_array(trim(strtolower($get_sort)), ['goods_id', 'sales_volume', 'exchange_integral', 'is_exchange'])) {
                    $sort = $get_sort;
                }
            }

            $order = $default_sort_order_method;
            if (request()->has('order')) {
                $get_order = addslashes(request()->input('order'));
                if (in_array(trim(strtoupper($get_order)), ['ASC', 'DESC'])) {
                    $order = $get_order;
                }
            }

            $ecsCookie = request()->cookie('ECS');
            $display = isset($ecsCookie['display']) ? $ecsCookie['display'] : $default_display_type;
            if (request()->has('display')) {
                $get_display = addslashes(request()->input('display'));
                if (in_array(trim(strtolower($get_display)), ['list', 'grid', 'text'])) {
                    $display = $get_display;
                }
            }


            $display = in_array($display, ['list', 'grid', 'text']) ? $display : 'text';

            cookie()->queue('ECS[display]', $display, 60 * 24 * 7);

            $children = $this->categoryService->getCatListChildren($cat_id);

            $count = $this->exchangeService->getExchangeGoodsCount($children, $integral_min, $integral_max);
            $max_page = ($count > 0) ? ceil($count / $size) : 1;
            if ($page > $max_page) {
                $page = $max_page;
            }
            $goodslist = $this->exchangeService->getExchangeGetGoods($children, $integral_min, $integral_max, $size, $page, $sort, $order);
            if ($display == 'grid') {
                if (count($goodslist) % 2 != 0) {
                    $goodslist[] = [];
                }
            }
            $this->smarty->assign('goods_list', $goodslist);

            $this->smarty->assign('type', 'exchange');
            $result = ['error' => 0, 'message' => '', 'cat_goods' => '', 'best_goods' => ''];
            $result['cat_goods'] = html_entity_decode($this->smarty->fetch('library/more_goods_page.lbi'));
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 积分兑换商品详情
        /*------------------------------------------------------ */
        elseif ($act == 'view') {
            $goods_id = (int)request()->input('id', 0);

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/exchange/detail/' . $goods_id);
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            $cache_id = $goods_id . '-' . session('user_rank') . '-' . $this->config['lang'] . '-exchange';
            $cache_id = sprintf('%X', crc32($cache_id));

            if (!$this->smarty->is_cached('exchange_goods.dwt', $cache_id)) {
                $this->smarty->assign('image_width', $this->config['image_width']);
                $this->smarty->assign('image_height', $this->config['image_height']);
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp()); // 网店帮助
                $this->smarty->assign('id', $goods_id);
                $this->smarty->assign('type', 0);
                $this->smarty->assign('cfg', $GLOBALS['_CFG']);

                $categories_pro = get_category_tree_leve_one();
                $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

                /* 获得商品的信息 */
                $goodsInfo = $this->exchangeService->getExchangeGoodsInfo($goods_id, $warehouse_id, $area_id, $area_city);

                if (empty($goodsInfo)) {
                    /* 如果没有找到任何记录则跳回到首页 */
                    return redirect()->route('exchange');
                } else {
                    $children = $this->categoryService->getCatListChildren($goodsInfo['cat_id']);

                    $goodslist = $this->exchangeService->getExchangeGetGoods($children, 0, 0, 6, 1, 'sales_volume', 'DESC');
                    $this->smarty->assign('look_top', $goodslist);

                    $this->smarty->assign('goods', $goodsInfo);
                    $this->smarty->assign('goods_id', $goodsInfo['goods_id']);

                    /* meta */
                    $this->smarty->assign('keywords', htmlspecialchars($goodsInfo['keywords']));
                    $this->smarty->assign('description', htmlspecialchars($goodsInfo['goods_brief']));

                    assign_template();

                    /* current position */
                    $position = assign_ur_here($goodsInfo['cat_id'], $goodsInfo['goods_name'], [], '', $goodsInfo['user_id']);

                    $this->smarty->assign('ur_here', $position['ur_here']);                  // 当前位置

                    $properties = $this->goodsAttrService->getGoodsProperties($goods_id, $warehouse_id, $area_id, $area_city);  // 获得商品的规格和属性
                    $this->smarty->assign('properties', $properties['pro']);                              // 商品属性
                    $this->smarty->assign('specification', $properties['spe']);                              // 商品规格

                    $this->smarty->assign('pictures', $this->goodsGalleryService->getGoodsGallery($goods_id));                    // 商品相册

                    $where = [
                        'type' => 'best',
                        'cats' => $children,
                        'warehouse_id' => $warehouse_id,
                        'area_id' => $area_id,
                        'area_city' => $area_city
                    ];

                    $best_goods = $this->exchangeService->getExchangeRecommendGoods($where);
                    $this->smarty->assign('best_goods', $best_goods); //精品

                    $where = [
                        'type' => 'hot',
                        'cats' => $children,
                        'warehouse_id' => $warehouse_id,
                        'area_id' => $area_id,
                        'area_city' => $area_city
                    ];

                    $hot_goods = $this->exchangeService->getExchangeRecommendGoods($where);
                    $this->smarty->assign('hot_goods', $hot_goods);  //热门

                    $area = [
                        'region_id' => $warehouse_id,  //仓库ID
                        'province_id' => $this->province_id,
                        'city_id' => $this->city_id,
                        'district_id' => $this->district_id,
                        'street_id' => $this->street_id,
                        'street_list' => $this->street_list,
                        'goods_id' => $goods_id,
                        'user_id' => $user_id,
                        'area_id' => $area_id,
                        'area_city' => $area_city,
                        'merchant_id' => $goodsInfo['user_id'],
                    ];

                    $this->smarty->assign('area', $area);

                    //评分 start
                    $comment_all = $this->commentService->getCommentsPercent($goods_id);

                    if ($goodsInfo['user_id'] > 0) {
                        $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goodsInfo['user_id']); //商家所有商品评分类型汇总
                        $this->smarty->assign('merch_cmt', $merchants_goods_comment);
                    }
                    //评分 end

                    $this->smarty->assign('comment_all', $comment_all);

                    $goods_area = 1;
                    if ($this->config['open_area_goods'] == 1) {

                        $area_count = $this->goodsService->getHasLinkAreaGods($goods_id, $area_id, $area_city);

                        if ($area_count > 0) {
                            $goods_area = 1;
                        } else {
                            $goods_area = 0;
                        }
                    }

                    $this->smarty->assign('goods_area', $goods_area);

                    /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                    $where = [
                        'goods_id' => $goods_id,
                        'warehouse_id' => $warehouse_id,
                        'area_id' => $area_id,
                        'area_city' => $area_city
                    ];
                    $goods_info = $this->goodsService->getGoodsInfo($where); //通过商品ID获取到ru_id;
                    $basic_info = get_shop_info_content($goods_info['user_id']);

                    if ($this->config['customer_service'] == 0) {
                        $goods_user_id = 0;
                    } else {
                        $goods_user_id = $goods_info['user_id'];
                    }

                    $shop_information = $this->merchantCommonService->getShopName($goods_user_id); //通过ru_id获取到店铺信息;

                    //判断当前商家是平台,还是入驻商家 bylu
                    if ($goods_user_id == 0) {
                        //判断平台是否开启了IM在线客服
                        $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                        if ($kf_im_switch) {
                            $shop_information['is_dsc'] = true;
                        } else {
                            $shop_information['is_dsc'] = false;
                        }
                    } else {
                        $shop_information['is_dsc'] = false;
                    }

                    $this->smarty->assign('shop_information', $shop_information);
                    $this->smarty->assign('kf_appkey', $basic_info['kf_appkey'] ?? ''); //应用appkey;
                    $this->smarty->assign('im_user_id', 'dsc' . $user_id); //登入用户ID;
                    /*  @author-bylu  end */

                    if ($basic_info) {
                        $basic_info['province'] = Region::where('region_id', $basic_info['province'])->value('region_name');
                        $basic_info['city'] = Region::where('region_id', $basic_info['city'])->value('region_name');
                    }

                    $chat = $this->dscRepository->chatQq($basic_info);
                    $basic_info['kf_ww'] = $chat['kf_ww'];
                    $basic_info['kf_qq'] = $chat['kf_qq'];

                    $this->smarty->assign('basic_info', $basic_info);

                    // 关联商品
                    $linked_goods = $this->goodsService->getLinkedGoods($goods_id, $warehouse_id, $area_id, $area_city);
                    $this->smarty->assign('related_goods', $linked_goods);

                    // 商品浏览历史
                    $history_goods = get_history_goods($goods_id, $warehouse_id, $area_id, $area_city);
                    $this->smarty->assign('history_goods', $history_goods);

                    assign_dynamic('exchange_goods');

                    //获取seo start
                    $seo = get_seo_words('change_content');

                    if ($seo) {
                        foreach ($seo as $key => $value) {
                            $seo[$key] = str_replace(['{sitename}', '{key}', '{name}', '{description}'], [$this->config['shop_name'], $goodsInfo['goods_style_name'], $goodsInfo['goods_name'], $goodsInfo['goods_style_name']], $value);
                        }
                    }

                    if (isset($seo['keywords']) && !empty($seo['keywords'])) {
                        $this->smarty->assign('keywords', htmlspecialchars($seo['keywords']));
                    } else {
                        $this->smarty->assign('keywords', htmlspecialchars($this->config['shop_keywords']));
                    }

                    if (isset($seo['description']) && !empty($seo['description'])) {
                        $this->smarty->assign('description', htmlspecialchars($seo['description']));
                    } else {
                        $this->smarty->assign('description', htmlspecialchars($this->config['shop_desc']));
                    }

                    if (isset($seo['title']) && !empty($seo['title'])) {
                        $this->smarty->assign('page_title', htmlspecialchars($seo['title']));
                    } else {
                        $this->smarty->assign('page_title', $position['title']);
                    }
                    //获取seo end
                }
            }

            //商品运费
            $region = [1, $this->province_id, $this->city_id, $this->district_id, $this->street_id, $this->street_list];
            $shippingFee = goodsShippingFee($goods_id, $warehouse_id, $area_id, $area_city, $region);
            $this->smarty->assign('shippingFee', $shippingFee);

            $this->smarty->assign('region_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('area_htmlType', 'exchange');

            $this->smarty->assign('integral_scale', price_format($this->config['integral_scale']));

            $this->smarty->assign('category', $goods_id);
            $this->smarty->assign('user_id', $user_id);

            $discuss_list = get_discuss_all_list($goods_id, 0, 1, 10);
            $this->smarty->assign('discuss_list', $discuss_list);

            $this->smarty->assign("user", get_user_info($user_id));

            return $this->smarty->display('exchange_goods.dwt', $cache_id);
        } elseif ($act == 'price') {
            $res = ['err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1];

            //仓库管理的地区ID
            $goods_id = (int)request()->input('id', 0);
            $attr = addslashes(request()->input('attr', ''));

            //仓库管理的地区ID
            $number = (int)request()->input('number', 1);
            $warehouse_id = (int)request()->input('warehouse_id', 0);
            $area_id = (int)request()->input('area_id', 0);
            //仓库管理的地区ID
            $type = addslashes(trim(request()->input('type', '')));

            $where = [
                'goods_id' => $goods_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goodsInfo = $this->goodsService->getGoodsInfo($where);

            if ($goods_id == 0) {
                $res['err_msg'] = $GLOBALS['_LANG']['err_change_attr'];
                $res['err_no'] = 1;
            } else {
                if ($number == 0) {
                    $res['qty'] = $number = 1;
                } else {
                    $res['qty'] = $number;
                }

                //ecmoban模板堂 --zhuo start
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $attr, $warehouse_id, $area_id, $area_city);
                $attr_number = $products ? $products['product_number'] : 0;

                /* 判断是否存在货品信息 */
                if ($goodsInfo['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
                } elseif ($goodsInfo['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);

                    if ($this->config['area_pricetype'] == 1) {
                        $prod = $prod->where('city_id', $area_city);
                    }
                } else {
                    $prod = Products::where('goods_id', $goods_id);
                }

                $prod = $this->baseRepository->getToArrayFirst($prod);

                if ($goodsInfo['goods_type'] == 0) {
                    $attr_number = $goodsInfo['goods_number'];
                } else {
                    if (empty($prod)) { //当商品没有属性库存时
                        $attr_number = $goodsInfo['goods_number'];
                    }

                    if (!empty($prod) && $this->config['add_shop_price'] == 0 && $type == 1) {
                        if (empty($attr_number)) {
                            $attr_number = $goodsInfo['goods_number'];
                        }
                    }
                }

                $attr_number = !empty($attr_number) ? $attr_number : 0;

                $res['attr_number'] = $attr_number;
            }

            if ($this->config['open_area_goods'] == 1) {
                $area_count = $this->goodsService->getHasLinkAreaGods($goods_id, $area_id, $area_city);

                if ($area_count < 1) {
                    $res['err_no'] = 2;
                }
            }

            return response()->json($res);
        } /**
         * ecmoban模板堂 zhuo
         */
        elseif ($act == 'getInfo') {

            $result = ['error' => 0, 'message' => ''];

            $attr_id = (int)request()->input('attr_id', 0);
            $goods_id = (int)request()->input('goods_id', 0);

            $row = GoodsAttr::where('goods_attr_id', $attr_id)->where('goods_id', $goods_id)->first();
            $row = $row ? $row->toArray() : [];

            $result['t_img'] = $row ? $row['attr_gallery_flie'] : '';

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //--  兑换
        /*------------------------------------------------------ */

        elseif ($act == 'buy') {
            /* 查询：判断是否登录 */
            if (!isset($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
                $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'exchange') ? $GLOBALS['_SERVER']['HTTP_REFERER'] : './index.php';
            }

            /* 查询：判断是否登录 */
            if ($user_id <= 0) {
                return show_message($GLOBALS['_LANG']['eg_error_login'], [$GLOBALS['_LANG']['back_up_page']], [$back_act], 'error');
            }

            /* 查询：取得参数：商品id */
            $goods_number = (int)request()->input('number', 0);
            $goods_id = (int)request()->input('goods_id', 0);

            if ($goods_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 查询：取得兑换商品信息 */
            $goods = $this->exchangeService->getExchangeGoodsInfo($goods_id, $warehouse_id, $area_id, $area_city);
            if (empty($goods)) {
                return dsc_header("Location: ./\n");
            }

            /* 查询：检查兑换商品是否是取消 */
            if ($goods['is_exchange'] == 0) {
                return show_message($GLOBALS['_LANG']['eg_error_status'], [$GLOBALS['_LANG']['back_up_page']], [$back_act], 'error');
            }

            $user_info = get_user_info($user_id);
            $user_points = $user_info['payPoints']; // 用户的积分总数

            if ($goods['exchange_integral'] > $user_points) {
                return show_message($GLOBALS['_LANG']['eg_error_integral'], [$GLOBALS['_LANG']['back_up_page']], [$back_act], 'error');
            }

            /* 查询：取得规格 */
            $re = array_keys($_REQUEST);
            $arr = [];
            foreach ($re as $v) {
                if (strpos($v, 'spec_') !== false) {
                    $arr[] = $_REQUEST[$v];
                }
            }
            if ($arr) {
                sort($arr);
                $specs = implode('|', $arr);
            }
            $specs = $specs ?? '';

            /* 查询：如果商品有规格则取规格商品信息 配件除外 */
            if (!empty($specs)) {
                $_specs = explode(',', $specs);

                $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $_specs, $warehouse_id, $area_id, $area_city);
            } else {
                $_specs = [];
            }

            if (empty($product_info)) {
                $product_info = ['product_number' => '', 'product_id' => 0];
            }

            /* 判断是否存在货品信息 */
            if ($goods['model_attr'] == 1) {
                $prod = ProductsWarehouse::where('goods_id', $goods['goods_id'])->where('warehouse_id', $warehouse_id);
            } elseif ($goods['model_attr'] == 2) {
                $prod = ProductsArea::where('goods_id', $goods['goods_id'])->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $prod = $prod->where('city_id', $area_city);
                }
            } else {
                $prod = Products::where('goods_id', $goods['goods_id']);
            }

            $prod = $this->baseRepository->getToArrayFirst($prod);

            //ecmoban模板堂 --zhuo start
            //查询：商品存在规格 是货品 检查该货品库存
            if ($this->config['use_storage'] == 1) {
                $is_product = 0;
                if (is_spec($_specs) && (!empty($prod))) {
                    if (($product_info['product_number'] == 0)) {
                        return show_message($GLOBALS['_LANG']['eg_error_number'], [$GLOBALS['_LANG']['back_up_page']], [$back_act], 'error');
                    }
                } else {
                    $is_product = 1;
                }

                if ($is_product == 1) {
                    /* 查询：检查兑换商品是否有库存 */
                    if ($goods['goods_number'] == 0) {
                        return show_message($GLOBALS['_LANG']['eg_error_number'], [$GLOBALS['_LANG']['back_up_page']], [$back_act], 'error');
                    }
                }
            }
            //ecmoban模板堂 --zhuo end

            /* 查询：查询规格名称和值，不考虑价格 */
            $goods_attr = $this->goodsService->getGoodsAttrList($arr);

            /* 更新：清空购物车中所有团购商品 */
            $this->cartCommonService->clearCart($user_id, CART_EXCHANGE_GOODS);

            //积分兑换 ecmoban模板堂 --zhuo
            $goods['exchange_integral'] = $goods['exchange_integral'] * $this->config['integral_scale'] / 100;

            /* 更新：加入购物车 */
            $cart = [
                'user_id' => $user_id,
                'session_id' => SESS_ID,
                'goods_id' => $goods['goods_id'],
                'product_id' => $product_info['product_id'],
                'goods_sn' => addslashes($goods['goods_sn']),
                'goods_name' => addslashes($goods['goods_name']),
                'market_price' => $goods['marketPrice'],
                'goods_price' => 0,
                'goods_number' => $goods_number,
                'goods_attr' => empty($goods_attr) ? '' : addslashes($goods_attr),
                'goods_attr_id' => $specs,
                'warehouse_id' => $warehouse_id, //ecmoban模板堂 --zhuo 仓库
                'area_id' => $area_id, //ecmoban模板堂 --zhuo 仓库地区
                'area_city' => $area_city,
                'ru_id' => $goods['user_id'] ?? 0,
                'is_real' => $goods['is_real'] ?? 0,
                'extension_code' => empty($goods['extension_code']) ? '' : addslashes($goods['extension_code']),
                'parent_id' => 0,
                'rec_type' => CART_EXCHANGE_GOODS,
                'is_gift' => 0
            ];

            $rec_id = Cart::insertGetId($cart);

            $this->cartRepository->pushCartValue($rec_id);

            /* 记录购物流程类型：团购 */
            session([
                'flow_type' => CART_EXCHANGE_GOODS,
                'extension_code' => 'exchange_goods',
                'extension_id' => $goods_id,
                'direct_shopping' => 4
            ]);

            /* 进入收货人页面 */
            return dsc_header("Location: ./flow.php?step=checkout&direct_shopping=4\n");
        }

        /*------------------------------------------------------ */
        //--  切换收货地址
        /*------------------------------------------------------ */

        elseif ($act == 'in_stock') {
            $res = ['err_msg' => '', 'result' => '', 'qty' => 1];

            $area = $this->areaService->areaCookie();

            $goods_id = (int)request()->input('id', 0);
            $province = (int)request()->input('province', $area['province'] ?? 0);
            $city = (int)request()->input('city', $area['city'] ?? 0);
            $district = (int)request()->input('district', $area['district'] ?? 0);
            $d_null = (int)request()->input('d_null', 0);

            $user_address = get_user_address_region($user_id);
            $user_address = explode(",", $user_address['region_address']);

            $street_info = Region::select('region_id')->where('parent_id', $district)->get();
            $street_info = $street_info ? $street_info->toArray() : [];
            $street_info = $street_info ? collect($street_info)->flatten()->all() : [];

            $street_list = 0;
            $street_id = 0;

            if ($street_info) {
                $street_id = $street_info[0];
                $street_list = implode(",", $street_info);
            }

            //清空
            $time = 60 * 24 * 30;
            cookie()->queue('type_province', 0, $time);
            cookie()->queue('type_city', 0, $time);
            cookie()->queue('type_district', 0, $time);

            $res['d_null'] = $d_null;

            if ($d_null == 0) {
                if (in_array($district, $user_address)) {
                    $res['isRegion'] = 1;
                } else {
                    $res['message'] = $GLOBALS['_LANG']['Distribution_message'];
                    $res['isRegion'] = 88; //原为0
                }
            } else {
                $district = '';
            }

            /* 删除缓存 */
            $this->areaService->getCacheNameForget('area_cookie');
            $this->areaService->getCacheNameForget('area_info');
            $this->areaService->getCacheNameForget('warehouse_id');

            $area_cache_name = $this->areaService->getCacheName('area_cookie');

            $area_cookie_cache = [
                'province' => $province,
                'city_id' => $city,
                'district' => $district,
                'street' => $street_id,
                'street_area' => $street_list
            ];

            cache()->forever($area_cache_name, $area_cookie_cache);

            $res['goods_id'] = $goods_id;

            $flow_warehouse = get_warehouse_goods_region($province);
            cookie()->queue('flow_region', $flow_warehouse['region_id'] ?? 0, 60 * 24 * 30);

            return response()->json($res);
        }
    }
}
