<?php

namespace App\Http\Controllers;

use App\Libraries\QRcode;
use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\CollectStore;
use App\Models\Goods as GoodsModel;
use App\Models\GoodsAttr;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\GroupBuyService;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryService;
use App\Services\Comment\CommentService;
use App\Services\Erp\JigonManageService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;

/**
 * 团购商品前台文件
 */
class GroupBuyController extends InitController
{
    protected $groupBuyService;
    protected $categoryService;
    protected $goodsService;
    protected $dscRepository;
    protected $jigonManageService;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $goodsWarehouseService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $sessionRepository;
    protected $config;
    protected $articleCommonService;
    protected $orderGoodsService;
    protected $cartCommonService;
    protected $cartRepository;

    public function __construct(
        GroupBuyService $groupBuyService,
        CategoryService $categoryService,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        JigonManageService $jigonManageService,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        GoodsWarehouseService $goodsWarehouseService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        SessionRepository $sessionRepository,
        ArticleCommonService $articleCommonService,
        OrderGoodsService $orderGoodsService,
        CartCommonService $cartCommonService,
        CartRepository $cartRepository
    )
    {
        $this->groupBuyService = $groupBuyService;
        $this->categoryService = $categoryService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->jigonManageService = $jigonManageService;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->orderGoodsService = $orderGoodsService;
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
        $keywords = htmlspecialchars(trim(request()->input('keywords', '')));

        if (request()->exists('keywords')) {
            clear_all_files();
        }

        $group_buy_id = (int)request()->input('id', 0);

        if ($group_buy_id) {
            $Loaction = dsc_url('/#/groupbuy/detail/' . $group_buy_id);
        } else {
            $Loaction = dsc_url('/#/groupbuy');
        }

        /* 跳转H5 start */
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        $user_id = session('user_id', 0);

        /*------------------------------------------------------ */
        //-- act 操作项的初始化
        /*------------------------------------------------------ */
        $template = "group_buy_list";
        $act = addslashes(request()->input('act', ''));
        if (empty($act)) {
            $template = "group_buy";
            $act = 'list';
        }

        /*------------------------------------------------------ */
        //-- 团购商品 --> 团购活动商品列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            $sort = addslashes(trim(request()->input('sort', '')));

            //瀑布流 by wu start
            $this->smarty->assign('category_load_type', $this->config['category_load_type']);
            $this->smarty->assign('query_string', preg_replace('/act=\w+&?/', '', request()->server('QUERY_STRING')));
            //瀑布流 by wu end

            $goods_num = (int)request()->input('goods_num', 0);

            $cat_id = (int)request()->input('cat_id', 0);
            /* 初始化分页信息 */

            $size = isset($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10; /* 取得每页记录数 */

            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';

            if ($sort == 'comments_number') {
                $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'start_time' : ($this->config['sort_order_type'] == '1' ? 'shop_price' : 'last_update');
            } else {
                $default_sort_order_type = 'act_id';
            }

            $sort = in_array(trim(strtolower($sort)), ['act_id', 'start_time', 'sales_volume', 'comments_number']) ? trim($sort) : $default_sort_order_type;

            $order = request()->input('order', '');
            $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;

            $children = [];
            $count = 0;
            if ($template == 'group_buy_list') {
                /* 取得团购活动总数 */
                $children = $this->categoryService->getCatListChildren($cat_id);
                $count = $this->groupBuyService->getGroupBuyCount($children, $keywords);
            }

            /* 取得当前页 */
            $page = (int)request()->input('page', 1);
            if ($count > 0 && $template == 'group_buy_list') {
                /* 计算总页数 */
                $page_count = ceil($count / $size);
                $page = $page > $page_count ? $page_count : $page;
            }

            /* 缓存id：语言 - 每页记录数 - 当前页 */
            $cache_id = $cat_id . '-' . $goods_num . '-' . $size . '-' . $page . '-' . $sort . '-' . $order . '-' . $keywords . '-' . session('user_rank', 0) . '_' . $this->config['lang'];
            $cache_id = sprintf('%X', crc32($cache_id));

            $content = cache()->remember($template . '.dwt.' . $cache_id, $this->config['cache_time'], function () use ($cat_id, $children, $count, $template, $keywords, $goods_num, $size, $page, $sort, $order) {
                if ($count > 0 && $template == 'group_buy_list') {
                    /* 取得当前页的团购活动 */
                    $gb_list = $this->groupBuyService->getGroupBuyList($children, $keywords, '', $goods_num, $size, $page, $sort, $order);
                    $this->smarty->assign('gb_list', $gb_list);

                    //瀑布流 by wu start
                    if (!$this->config['category_load_type']) {
                        /* 设置分页链接 */
                        $pager = get_pager('group_buy.php', ['act' => 'list', 'keywords' => $keywords, 'sort' => $sort, 'order' => $order], $count, $page, $size);
                        $this->smarty->assign('pager', $pager);
                    }
                    //瀑布流 by wu end
                }

                /* 模板赋值 */
                $this->smarty->assign('cfg', $GLOBALS['_CFG']);
                assign_template();
                $position = assign_ur_here(0, $GLOBALS['_LANG']['group_buy']);
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                //获取seo start
                $seo = get_seo_words('group');

                if ($seo) {
                    foreach ($seo as $key => $value) {
                        $seo[$key] = str_replace(['{sitename}', '{key}', '{description}'], [$this->config['shop_name'], $this->config['shop_keywords'], $this->config['shop_desc']], $value);
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

                if ($template == 'group_buy_list') {
                    $categories_pro = get_category_tree_leve_one();
                    $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版
                } else {
                    $category_list = $this->categoryService->getCatList();
                    $this->smarty->assign('category_list', $category_list);
                }

                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
                $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-typegroup_buy.xml" : 'feed.php?type=group_buy'); // RSS URL

                if ($template == 'group_buy') {
                    /* 广告位 */
                    $group_top_banner = '';
                    for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                        $group_top_banner .= "'activity_top_ad_group_buy" . $i . ","; //轮播图
                    }
                    $this->smarty->assign('activity_top_banner', $group_top_banner);
                    /* 取得正在进行的团购活动 */
                    $new_list = $this->groupBuyService->getGroupBuyList($children, $keywords, "new", 0, 5);
                    $this->smarty->assign('new_list', $new_list);
                    $hot_list = $this->groupBuyService->getGroupBuyList($children, $keywords, "hot", 0, 10);
                    $this->smarty->assign('hot_list', $hot_list);
                }
                $this->smarty->assign('cat_id', $cat_id);
                assign_dynamic('group_buy_list');

                /* 显示模板 */
                return $this->smarty->display($template . '.dwt');
            });

            return $content;
        }

        /*------------------------------------------------------ */
        //-- 瀑布流 by wu
        /*------------------------------------------------------ */
        elseif ($act == 'load_more_goods') {
            $cat_id = (int)request()->input('cat_id', 0);
            /* 初始化分页信息 */
            $page = (int)request()->input('page', 1);
            $size = isset($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10; /* 取得每页记录数 */
            $size = 20;

            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';

            $sort = request()->input('sort', '');
            if ($sort == 'comments_number') {
                $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'goods_id' : ($this->config['sort_order_type'] == '1' ? 'shop_price' : 'last_update');
            } else {
                $default_sort_order_type = 'act_id';
            }

            $sort = in_array(trim(strtolower($sort)), ['act_id', 'goods_id', 'sales_volume', 'comments_number']) ? trim($sort) : $default_sort_order_type;
            $order = request()->input('order', '');
            $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;

            $children = $this->categoryService->getCatListChildren($cat_id);

            /* 取得团购活动总数 */
            $count = $this->groupBuyService->getGroupBuyCount($children, $keywords);

            if ($count > 0) {
                /* 取得当前页的团购活动 */
                $gb_list = $this->groupBuyService->getGroupBuyList($children, $keywords, '', 0, $size, $page, $sort, $order);
                $this->smarty->assign('gb_list', $gb_list);

                $this->smarty->assign('type', 'group_buy');
                $result = ['error' => 0, 'message' => '', 'cat_goods' => '', 'best_goods' => ''];
                $result['cat_goods'] = html_entity_decode($this->smarty->fetch('library/more_goods_page.lbi'));
                return response()->json($result);
            }
        }

        /*------------------------------------------------------ */
        //-- 团购商品 --> 属性图片
        /*------------------------------------------------------ */
        elseif ($act == 'getInfo') {

            $result = ['error' => 0, 'message' => ''];

            $attr_id = (int)request()->input('attr_id', 0);
            $goods_id = (int)request()->input('goods_id', 0);

            $attr_gallery_flie = GoodsAttr::where('goods_attr_id', $attr_id)->where('goods_id', $goods_id)->value('attr_gallery_flie');

            $result['t_img'] = $attr_gallery_flie;

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 团购商品 --> 商品详情
        /*------------------------------------------------------ */
        elseif ($act == 'view') {
            /* 取得参数：团购活动id */
            $group_buy_id = (int)request()->input('id', 0);
            if ($group_buy_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 取得团购活动信息 */
            $where = [
                'group_buy_id' => $group_buy_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'user_id' => $user_id
            ];
            $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

            //获取商品时候收藏
            $group_buy['is_collect'] = '';
            if (session('user_id') > 0) {
                $group_buy['is_collect'] = CollectGoods::where('goods_id', $group_buy['goods_id'])->where('user_id', $user_id)->value('rec_id');
            }

            //是否收藏店铺
            $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $group_buy['user_id'])->value('rec_id');
            if ($rec_id > 0) {
                $group_buy['error'] = '1';
            } else {
                $group_buy['error'] = '2';
            }

            if (empty($group_buy)) {
                return dsc_header("Location: ./\n");
            }

            $first_month_day = local_mktime(0, 0, 0, date('m'), 1, date('Y')); //本月第一天
            $last_month_day = local_mktime(0, 0, 0, date('m'), date('t'), date('Y')) + 24 * 60 * 60 - 1; //本月最后一天

            $group_list = $this->groupBuyService->getMonthDayStartEndGoods($group_buy_id, $first_month_day, $last_month_day);
            $this->smarty->assign('group_list', $group_list);

            $province_id = $this->province_id;
            $city_id = $this->city_id;
            $district_id = $this->district_id;

            /* 缓存id：语言，团购活动id，状态，（如果是进行中）当前数量和是否登录 */
            $cache_id = $this->config['lang'] . '-' . $group_buy_id . '-' . $warehouse_id . '-' . $area_id . '-' . $area_city . '-' . $province_id . '-' . $city_id . '-' . $district_id . '-' . session('user_rank') . '-' . $group_buy['status'] . gmtime();
            if ($group_buy['status'] == GBS_UNDER_WAY) {
                $cache_id = $cache_id . '-' . $group_buy['valid_goods'] . '-' . session('user_id', 0) . '-' . session('user_rank', 0) . '_' . $this->config['lang'];
            }

            $cache_id = sprintf('%X', crc32($cache_id));
            $content = cache()->remember('group_buy_goods.dwt.' . $cache_id, $this->config['cache_time'], function () use ($group_buy_id, $group_buy, $user_id, $warehouse_id, $area_id, $area_city, $province_id, $city_id, $district_id) {

                $merchant_group = $this->groupBuyService->getMerchantGroupGoods($group_buy_id, $group_buy['user_id']);
                $this->smarty->assign('merchant_group_goods', $merchant_group);

                $this->smarty->assign('look_top', $this->groupBuyService->getTopGroupGoods('click_count', $user_id));
                $this->smarty->assign('buy_top', $this->groupBuyService->getTopGroupGoods('sales_volume', $user_id));

                $this->smarty->assign('comment_percent', comment_percent($group_buy['goods_id']));

                $group_buy['gmt_end_date'] = $group_buy['end_date'];
                $this->smarty->assign('group_buy', $group_buy);

                /* 取得团购商品信息 */
                $goods_id = $group_buy['goods_id'];
                $goods_info = $group_buy['goods'];

                $area = [
                    'region_id' => $warehouse_id, //仓库ID
                    'province_id' => $province_id,
                    'city_id' => $city_id,
                    'district_id' => $district_id,
                    'goods_id' => $goods_id,
                    'user_id' => $user_id,
                    'area_id' => $area_id,
                    'merchant_id' => $goods_info['user_id'],
                    'area_city' => $area_city,
                ];

                $this->smarty->assign('area', $area);

                /* 读评论信息 */
                $this->smarty->assign('id', $goods_id);
                $this->smarty->assign('type', 0);

                if (empty($goods_info)) {
                    return dsc_header("Location: ./\n");
                }

                $this->smarty->assign('gb_goods', $goods_info);
                $properties = $this->goodsAttrService->getGoodsProperties($goods_id, $warehouse_id, $area_id, $area_city);  // 获得商品的规格和属性
                $this->smarty->assign('properties', $properties['pro']);                              // 商品属性
                $this->smarty->assign('specification', $properties['spe']);                              // 商品规格

                //模板赋值
                $this->smarty->assign('cfg', $GLOBALS['_CFG']);
                assign_template();

                $linked_goods = $this->goodsService->getLinkedGoods($goods_id, $warehouse_id, $area_id, $area_city);
                $position = assign_ur_here($goods_info['cat_id'], $group_buy['goods_name'], [], '', $group_buy['user_id']);

                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
                $this->smarty->assign('price_ladder', $group_buy['price_ladder']);

                //获取最小起订量
                $min_amount = 0;
                if ($group_buy['price_ladder']) {
                    $ladder_arr = array_column($group_buy['price_ladder'], 'amount');

                    if (!empty($ladder_arr)) {
                        $min_amount = min($ladder_arr);
                    }
                }
                $this->smarty->assign('min_amount', $min_amount);

                $this->smarty->assign('related_goods', $linked_goods);                                   // 关联商品
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

                $this->smarty->assign('area_htmlType', 'group_buy');

                assign_dynamic('group_buy_goods');

                //评分 start
                $comment_all = $this->commentService->getCommentsPercent($goods_id);
                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goods_info['user_id']); //商家所有商品评分类型汇总
                //评分 end

                $this->smarty->assign('comment_all', $comment_all);
                $this->smarty->assign('merch_cmt', $merchants_goods_comment);

                if ($this->config['customer_service'] == 0) {
                    $goods_info['user_id'] = 0;
                }

                $basic_info = get_shop_info_content($goods_info['user_id']);

                if ($basic_info) {
                    $basic_info['province'] = Region::where('region_id', $basic_info['province'])->value('region_name');
                    $basic_info['city'] = Region::where('region_id', $basic_info['city'])->value('region_name');
                }

                $this->smarty->assign('basic_info', $basic_info);
                $this->smarty->assign('category', $goods_id);

                /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                if ($this->config['customer_service'] == 0) {
                    $goods_info['user_id'] = 0;
                }

                $shop_information = $this->merchantCommonService->getShopName($goods_info['user_id']);//通过ru_id获取到店铺信息;

                //判断当前商家是平台,还是入驻商家 bylu
                if ($goods_info['user_id'] == 0) {
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
                $shop_information['goods_id'] = $goods_id;
                $this->smarty->assign('shop_information', $shop_information);
                /*  @author-bylu  end */

                //商品运费by wu start
                $region = [1, $this->province_id, $this->city_id, $this->district_id, $this->street_id];
                $shippingFee = goodsShippingFee($goods_id, $warehouse_id, $area_id, $area_city, $region);
                $this->smarty->assign('shippingFee', $shippingFee);
                //商品运费by wu end


                $this->smarty->assign('goods', $group_buy);
                $this->smarty->assign('pictures', $this->goodsGalleryService->getGoodsGallery($goods_id));                    // 商品相册
                $this->smarty->assign('now_time', gmtime());           // 当前系统时间

                $linked_goods = $this->goodsService->getLinkedGoods($goods_id, $warehouse_id, $area_id, $area_city);
                $this->smarty->assign('related_goods', $linked_goods);

                $history_goods = get_history_goods($goods_id, $warehouse_id, $area_id, $area_city);
                $this->smarty->assign('history_goods', $history_goods);

                $this->smarty->assign('region_id', $warehouse_id);
                $this->smarty->assign('area_id', $area_id);

                $start_date = $group_buy['xiangou_start_date'];
                $end_date = $group_buy['xiangou_end_date'];
                $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods_id, session('user_id'), 'group_buy', '', $group_buy['act_id']);
                $this->smarty->assign('orderG_number', $order_goods['goods_number']);

                //@author guan start
                if ($this->config['two_code']) {
                    $group_buy_path = storage_public(IMAGE_DIR . "/group_wenxin/");

                    /* 生成目录 */
                    if (!file_exists($group_buy_path)) {
                        make_dir($group_buy_path);
                    }

                    $logo = empty($this->config['two_code_logo']) ? $goods_info['goods_img'] : str_replace('../', '', $this->config['two_code_logo']);

                    if ($this->config['open_oss'] == 1) {
                        $logo = $logo ? $this->dscRepository->getImagePath($logo) : '';
                    } else {
                        $logo = $logo && (strpos($logo, 'http') === false) ? storage_public($logo) : $logo;
                    }

                    $size = '200x200';
                    $url = url('/') . '/';
                    $two_code_links = trim($this->config['two_code_links']);
                    $two_code_links = empty($two_code_links) ? $url : $two_code_links;
                    $data = $two_code_links . 'group_buy.php?act=view&id=' . $group_buy_id;
                    $errorCorrectionLevel = 'H'; // 纠错级别：L、M、Q、H
                    $matrixPointSize = 4; // 点的大小：1到10
                    $image = IMAGE_DIR . "/group_wenxin/weixin_code_" . $goods_info['goods_id'] . ".png";
                    $filename = storage_public($image);

                    if (!file_exists($filename)) {
                        QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize);
                        $QR = imagecreatefrompng($filename);

                        $linkExists = $this->dscRepository->remoteLinkExists($logo);

                        if ($linkExists) {
                            $logo = imagecreatefromstring(file_get_contents($logo));

                            $QR_width = imagesx($QR);
                            $QR_height = imagesy($QR);

                            $logo_width = imagesx($logo);
                            $logo_height = imagesy($logo);

                            // Scale logo to fit in the QR Code
                            $logo_qr_width = $QR_width / 5;
                            $scale = $logo_width / $logo_qr_width;
                            $logo_qr_height = $logo_height / $scale;
                            $from_width = ($QR_width - $logo_qr_width) / 2;
                            //echo $from_width;
                            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
                        }

                        imagepng($QR, $filename);
                        imagedestroy($QR);
                    }

                    $this->dscRepository->getOssAddFile([$image]);

                    $this->smarty->assign('weixin_img_url', $this->dscRepository->getImagePath($image));
                    $this->smarty->assign('weixin_img_text', trim($this->config['two_code_mouse']));
                    $this->smarty->assign('two_code', trim($this->config['two_code']));
                }
                //@author guan end

                //获取seo start
                $seo = get_seo_words('group_content');

                if ($seo) {
                    foreach ($seo as $key => $value) {
                        $seo[$key] = str_replace(['{name}', '{description}'], [$group_buy['act_name'], $group_buy['act_desc']], $value);
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

                $this->smarty->assign('user_id', session('user_id'));
                return $this->smarty->display('group_buy_goods.dwt');
            });

            //更新商品点击次数
            GoodsModel::where('goods_id', $group_buy['goods_id'])->increment('click_count', 1);

            return $content;

        } elseif ($act == 'price') {
            $res = ['err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1];

            //仓库管理的地区ID
            $goods_id = (int)request()->input('id', 0);
            $attr_id = request()->input('attr', '');

            $number = (int)request()->input('number', 1);
            $warehouse_id = (int)request()->input('warehouse_id', 0);
            //仓库管理的地区ID
            $area_id = (int)request()->input('id', 0);
            //加载类型
            $type = (int)request()->input('type', 0);

            $where = [
                'goods_id' => $goods_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goods_info = $this->goodsService->getGoodsInfo($where);

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
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $attr_id, $warehouse_id, $area_id, $area_city);
                $attr_number = $products ? $products['product_number'] : 0;

                if ($goods_info['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $goods_info['goods_id'])->where('warehouse_id', $this->warehouse_id);
                } elseif ($goods_info['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $goods_info['goods_id'])->where('area_id', $this->area_id);

                    if ($this->config['area_pricetype'] == 1) {
                        $prod = $prod->where('city_id', $area_city);
                    }
                } else {
                    $prod = Products::where('goods_id', $goods_info['goods_id']);
                }

                $prod = $this->baseRepository->getToArrayFirst($prod);

                //贡云商品 获取库存
                if ($goods_info['cloud_id'] > 0 && isset($products['product_id'])) {
                    $attr_number = $this->jigonManageService->jigonGoodsNumber(['product_id' => $products['product_id']]);
                } else {
                    if ($goods_info['goods_type'] == 0) {
                        $attr_number = $goods_info['goods_number'];
                    } else {
                        if (empty($prod)) { //当商品没有属性库存时
                            $attr_number = $goods_info['goods_number'];
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

            $res['type'] = $type;

            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 团购商品 --> 购买
        /*------------------------------------------------------ */

        elseif ($act == 'buy') {
            $goods_attr_id = addslashes(request()->input('goods_attr_id', ''));

            /* 查询：判断是否登录 */
            if (session('user_id') <= 0) {
                return show_message($GLOBALS['_LANG']['gb_error_login'], '', '', 'error');
            }

            $warehouse_id = (int)request()->input('warehouse_id', 0);
            //仓库管理的地区ID
            $area_id = (int)request()->input('area_id', 0);

            /* 查询：取得参数：团购活动id */
            $group_buy_id = (int)request()->input('group_buy_id', 0);
            if ($group_buy_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 查询：取得数量 */
            $number = (int)request()->input('number', 1);
            $number = $number < 1 ? 1 : $number;

            /* 查询：取得团购活动信息 */
            $where = [
                'group_buy_id' => $group_buy_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                'current_num' => $number,
                'user_id' => $user_id
            ];
            $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

            if (empty($group_buy)) {
                return dsc_header("Location: ./\n");
            }

            /* 查询：检查团购活动是否是进行中 */
            if ($group_buy['status'] != GBS_UNDER_WAY) {
                return show_message($GLOBALS['_LANG']['gb_error_status'], '', '', 'error');
            }

            /* 查询：取得团购商品信息 */
            $goods_info = $group_buy['goods'];

            if (empty($goods_info)) {
                return dsc_header("Location: ./\n");
            }

            $prod = [];
            $products = [];
            if ($goods_attr_id) {
                if ($goods_info['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('warehouse_id', $warehouse_id);
                } elseif ($goods_info['model_attr'] == 2) {
                    $prod = ProductsArea::where('area_id', $area_id);

                    if ($this->config['area_pricetype'] == 1) {
                        $prod = $prod->where('city_id', $area_city);
                    }
                } else {
                    $prod = Products::whereRaw(1);
                }

                $prod = $prod->where('goods_id', $goods_info['goods_id']);
                $prod = $this->baseRepository->getToArrayFirst($prod);

                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_info['goods_id'], $goods_attr_id, $warehouse_id, $area_id, $area_city);
                $product_number = isset($products['product_number']) ? $products['product_number'] : 0;
            }

            if ($prod) {
                $goods_number = $product_number;
            } else {
                $goods_number = $goods_info['goods_number'];
            }

            if ($goods_attr_id && $goods_info['cloud_id']) {
                $productIds = Products::select('cloud_product_id')->where('product_id', $products['product_id']);
                $productIds = $this->baseRepository->getToArrayGet($productIds);
                $productIds = $this->baseRepository->getKeyPluck($productIds, 'cloud_product_id');

                $cloud = new Cloud();
                $is_callable = [$cloud, 'queryInventoryNum'];

                /* 判断类对象方法是否存在 */
                if (is_callable($is_callable)) {
                    $cloud_prod = $cloud->queryInventoryNum($productIds);

                    $cloud_prod = dsc_decode($cloud_prod, true);

                    if ($cloud_prod['code'] == 10000) {
                        $cloud_product = $cloud_prod['data'];
                        if ($cloud_product) {
                            foreach ($cloud_product as $k => $v) {
                                if (in_array($v['productId'], $productIds)) {
                                    if ($v['hasTax'] == 1) {
                                        $goods_number = $v['taxNum'];
                                    } else {
                                        $goods_number = $v['noTaxNum'];
                                    }

                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $url = $this->dscRepository->buildUri('group_buy', ['gbid' => $group_buy_id]);

            if ($goods_number <= 0) {
                return show_message($GLOBALS['_LANG']['buy_error'], $GLOBALS['_LANG']['go_back'], $url);
            }

            $start_date = $group_buy['xiangou_start_date'];
            $end_date = $group_buy['xiangou_end_date'];
            $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $group_buy['goods_id'], session('user_id'), 'group_buy', '', $group_buy['act_id']);
            $restrict_amount = $number + $order_goods['goods_number'];

            /* 查询：判断数量是否足够 */
            if ($group_buy['restrict_amount'] > 0 && $restrict_amount > $group_buy['restrict_amount']) {
                return show_message($GLOBALS['_LANG']['gb_error_restrict_amount'], '', '', 'error');
            } elseif ($group_buy['restrict_amount'] > 0 && ($number > ($group_buy['restrict_amount'] - $group_buy['valid_goods']))) {
                return show_message($GLOBALS['_LANG']['gb_error_goods_lacking'], '', '', 'error');
            }

            /* 查询：取得规格 */
            $specs = htmlspecialchars(trim(request()->input('goods_spec', '')));
            /* 查询：如果商品有规格则取规格商品信息 配件除外 */
            if ($specs) {
                $_specs = explode(',', $specs);
                $product_info = $this->goodsAttrService->getProductsInfo($goods_info['goods_id'], $_specs, $warehouse_id, $area_id, $area_city);
            }

            empty($product_info) ? $product_info = ['product_number' => 0, 'product_id' => 0] : '';

            if ($goods_info['model_attr'] == 1) {
                $prod = ProductsWarehouse::where('goods_id', $goods_info['goods_id'])->where('warehouse_id', $this->warehouse_id)->first();
                $prod = $prod ? $prod->toArray() : [];
            } elseif ($goods_info['model_attr'] == 2) {
                $prod = ProductsArea::where('goods_id', $goods_info['goods_id'])->where('area_id', $this->area_id)->first();
                $prod = $prod ? $prod->toArray() : [];
            } else {
                $prod = Products::where('goods_id', $goods_info['goods_id'])->first();
                $prod = $prod ? $prod->toArray() : [];
            }

            /* 检查：库存 */
            if ($this->config['use_storage'] == 1) {
                /* 查询：判断是否是属性商品 */
                if ($prod) {
                    //购买商品的数量和商品当前属性库存对比
                    if ($number > $prod['product_number']) {
                        return show_message($GLOBALS['_LANG']['gb_error_goods_lacking'], '', '', 'error');
                    }
                } else {
                    /* 查询：判断数量是否足够 */
                    if ($number > $goods_info['goods_number']) {
                        return show_message($GLOBALS['_LANG']['gb_error_goods_lacking'], '', '', 'error');
                    }
                }
            }

            /* 查询：查询规格名称和值，不考虑价格 */
            $goods_attr = $this->goodsService->getGoodsAttrList($specs);

            /* 更新：清空购物车中所有团购商品 */
            $this->cartCommonService->clearCart($user_id, CART_GROUP_BUY_GOODS);

            $session_id = $this->sessionRepository->realCartMacIp();
            $sess = empty(session('user_id')) ? $session_id : '';

            /* 更新：加入购物车 */
            $goods_price = $group_buy['deposit'] > 0 ? $group_buy['deposit'] : $group_buy['cur_price'];
            $cart = [
                'user_id' => session('user_id'),
                'session_id' => $sess,
                'goods_id' => $group_buy['goods_id'],
                'product_id' => $product_info['product_id'],
                'goods_sn' => addslashes($goods_info['goods_sn']),
                'goods_name' => addslashes($goods_info['goods_name']),
                'market_price' => $goods_info['market_price'],
                'goods_price' => $goods_price,
                'goods_number' => $number,
                'goods_attr' => addslashes($goods_attr),
                'goods_attr_id' => $goods_attr_id,
                //ecmoban模板堂 --zhuo start
                'ru_id' => $goods_info['user_id'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                //ecmoban模板堂 --zhuo end
                'is_real' => $goods_info['is_real'],
                'extension_code' => addslashes($goods_info['extension_code']),
                'parent_id' => 0,
                'rec_type' => CART_GROUP_BUY_GOODS,
                'is_gift' => 0
            ];

            $rec_id = Cart::insertGetId($cart);

            $this->cartRepository->pushCartValue($rec_id);

            /* 更新：记录购物流程类型：团购 */
            session([
                'flow_type' => CART_GROUP_BUY_GOODS,
                'extension_code' => 'group_buy',
                'extension_id' => $group_buy_id,
                'browse_trace' => "group_buy"  /* 进入收货人页面 */
            ]);

            return dsc_header("Location: ./flow.php?step=checkout&direct_shopping=5\n");
        }

        /* ------------------------------------------------------ */
        //-- 判断会员邮箱是否验证
        /* ------------------------------------------------------ */
        if (!empty($act) && $act == 'checked_certification') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $row = Users::select('email', 'is_validated')->where('user_id', $user_id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if (empty($row)) {
                $result['error'] = 1;
            } elseif (empty($row['email']) || empty($row['is_validated'])) {
                $result['error'] = 1;
            }

            return response()->json($result);
        }
    }
}
