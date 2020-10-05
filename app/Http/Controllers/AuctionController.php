<?php

namespace App\Http\Controllers;

use App\Models\AuctionLog;
use App\Models\Cart;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\AuctionService;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryService;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserService;

/**
 * 拍卖前台文件
 */
class AuctionController extends InitController
{
    protected $auctionService;
    protected $categoryService;
    protected $goodsService;
    protected $userService;
    protected $dscRepository;
    protected $goodsAttrService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $sessionRepository;
    protected $config;
    protected $articleCommonService;
    protected $cartCommonService;
    protected $cartRepository;
    protected $goodsProdutsService;

    public function __construct(
        AuctionService $auctionService,
        CategoryService $categoryService,
        GoodsService $goodsService,
        UserService $userService,
        DscRepository $dscRepository,
        GoodsAttrService $goodsAttrService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        SessionRepository $sessionRepository,
        ArticleCommonService $articleCommonService,
        CartCommonService $cartCommonService,
        CartRepository $cartRepository,
        GoodsProdutsService $goodsProdutsService
    )
    {
        $this->auctionService = $auctionService;
        $this->categoryService = $categoryService;
        $this->goodsService = $goodsService;
        $this->userService = $userService;
        $this->dscRepository = $dscRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->cartRepository = $cartRepository;
        $this->goodsProdutsService = $goodsProdutsService;
    }

    public function index()
    {
        load_helper('order');

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
        $user_id = session('user_id', 0);
        $this->smarty->assign('now_time', gmtime());           // 当前系统时间

        $act = addslashes(trim(request()->input('act', 'list')));
        $act = $act ? $act : 'list';

        /*------------------------------------------------------ */
        //-- 拍卖活动列表
        /*------------------------------------------------------ */
        if ($act == 'list') {

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/auction');
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

            // 取得当前页
            $page = intval(request()->input('page', 1));

            $size = isset($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10; // 取得每页记录数
            $size = 15;

            $cat_id = intval(request()->input('cat_id', 0));
            $integral_max = intval(request()->input('integral_max', 0));
            $integral_min = intval(request()->input('integral_min', 0));
            $keywords = htmlspecialchars(request()->input('keywords', ''));

            $cat_top_id = intval(request()->input('cat_top_id', 0));
            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'act_id' : ($this->config['sort_order_type'] == '1' ? 'start_time' : 'end_time');

            $sort = $default_sort_order_type;
            if (request()->exists('sort')) {
                $get_sort = request()->input('sort');
                if (in_array(trim(strtolower($get_sort)), ['act_id', 'start_time', 'end_time'])) {
                    $sort = $get_sort;
                }
            }

            $order = $default_sort_order_method;
            if (request()->exists('order')) {
                $get_order = request()->input('order');
                if (in_array(trim(strtoupper($get_order)), ['ASC', 'DESC'])) {
                    $order = $get_order;
                }
            }

            //模板缓存
            $cache_id = $cat_id . '-' . $integral_min . '-' . $integral_max . '-' . $cat_top_id . '-' . $keywords . '-' . $sort . '-' . $order . '-' . $size . '-' . $page . '-' . session('user_rank', 0) . '_' . $this->config['lang'];
            $cache_id = sprintf('%X', crc32($cache_id));

            $content = cache()->remember('auction_list.dwt.' . $cache_id, $this->config['cache_time'], function () use ($cat_id, $integral_min, $integral_max, $cat_top_id, $keywords, $sort, $order, $size, $page) {

                $top_children = [];
                if ($cat_top_id > 0) {
                    $top_children = $this->categoryService->getCatListChildren($cat_top_id);
                }

                /* 取得拍卖活动总数 */
                $count = $this->auctionService->getAuctionCount($keywords, $top_children);

                if ($count > 0) {
                    /* 取得当前页的拍卖活动 */
                    $auction_list = $this->auctionService->getAuctionList($keywords, $sort, $order, $size, $page, $top_children);
                    $this->smarty->assign('auction_list', $auction_list);

                    $cat_top_list = $this->auctionService->getTopCat();
                    $this->smarty->assign('cat_top_list', $cat_top_list);

                    //瀑布流 by wu start
                    if (!$this->config['category_load_type']) {
                        /* 设置分页链接 */
                        $pager = get_pager('auction.php', ['act' => 'list', 'keywords' => $keywords, 'sort' => $sort, 'order' => $order], $count, $page, $size);
                        $this->smarty->assign('pager', $pager);
                    }
                    //瀑布流 by wu end
                }

                /* 模板赋值 */
                $this->smarty->assign('cfg', $GLOBALS['_CFG']);
                assign_template();
                $position = assign_ur_here();
                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
                $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-typeauction.xml" : 'feed.php?type=auction'); // RSS URL

                $children = $this->categoryService->getCatListChildren($cat_id);

                $hot_goods = $this->auctionService->getAuctionRecommendGoods('hot', $children, $integral_min, $integral_max);
                $this->smarty->assign('hot_goods', $hot_goods);  //热门

                $this->smarty->assign('category', 9999999999999999999);

                $this->smarty->assign('cat_top_id', $cat_top_id);
                /* 广告位 */
                $activity_top_banner = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $activity_top_banner .= "'activity_top_ad_auction" . $i . ","; //轮播图
                }
                $this->smarty->assign('activity_top_banner', $activity_top_banner);

                assign_dynamic('auction_list');

                /* 显示模板 */
                return $this->smarty->display('auction_list.dwt');
            });

            return $content;
        }

        /*------------------------------------------------------ */
        //-- 瀑布流 by wu
        /*------------------------------------------------------ */
        if ($act == 'load_more_goods') {

            /* 初始化分页信息 */
            // 取得当前页
            $page = intval(request()->input('page', 1));

            $size = isset($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10; // 取得每页记录数
            $size = 15;
            $keywords = htmlspecialchars(trim(request()->input('keywords', '')));
            $cat_top_id = intval(request()->input('cat_top_id', 0));

            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'act_id' : ($this->config['sort_order_type'] == '1' ? 'start_time' : 'end_time');

            $sort = $default_sort_order_type;
            if (request()->exists('sort')) {
                $get_sort = request()->input('sort');
                if (in_array(trim(strtolower($get_sort)), ['act_id', 'start_time', 'end_time'])) {
                    $sort = $get_sort;
                }
            }

            $order = $default_sort_order_method;
            if (request()->exists('order')) {
                $get_order = request()->input('order');
                if (in_array(trim(strtoupper($get_order)), ['ASC', 'DESC'])) {
                    $order = $get_order;
                }
            }

            $top_children = [];
            if ($cat_top_id > 0) {
                $top_children = $this->categoryService->getCatListChildren($cat_top_id);
            }

            /* 取得当前页的拍卖活动 */
            $auction_list = $this->auctionService->getAuctionList($keywords, $sort, $order, $size, $page, $top_children);
            $this->smarty->assign('auction_list', $auction_list);

            $this->smarty->assign('type', 'auction');

            $this->smarty->assign('cat_top_list', $this->auctionService->getTopCat());
            $this->smarty->assign('cat_top_id', $cat_top_id);

            $result = ['error' => 0, 'message' => '', 'cat_goods' => '', 'best_goods' => ''];
            $result['cat_goods'] = html_entity_decode($this->smarty->fetch('library/more_goods_page.lbi'));
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 拍卖商品 --> 商品详情
        /*------------------------------------------------------ */
        elseif ($act == 'view') {
            $cat_id = intval(request()->input('cat_id', 0));

            $integral_max = intval(request()->input('integral_max', 0));
            $integral_min = intval(request()->input('integral_min', 0));

            /* 取得参数：拍卖活动id */
            $id = intval(request()->input('id', 0));

            /* 取得拍卖活动信息 */
            $auctionInfo = $this->auctionService->getAuctionInfo($id);

            if (!$id || !$auctionInfo) {
                return redirect(route('auction'));
            }

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/auction/detail/' . $id);
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            $children = $this->categoryService->getCatListChildren($cat_id);
            $this->smarty->assign('hot_goods', $this->auctionService->getAuctionRecommendGoods('hot', $children, $integral_min, $integral_max));  //热门
            $this->smarty->assign('user_id', $user_id);

            $auctionInfo['is_winner'] = 0;
            /* 缓存id：语言，拍卖活动id，状态，如果是进行中，还要最后出价的时间（如果有的话） */
            $cache_id = $this->config['lang'] . '-' . $id . '-' . $warehouse_id . '_' . $area_id . '_' . $area_city . '-' . $auctionInfo['status_no'];
            if ($auctionInfo['status_no'] == UNDER_WAY) {
                if (isset($auctionInfo['last_bid'])) {
                    $cache_id = $cache_id . '-' . $auctionInfo['last_bid']['bid_time'];
                }
            } elseif ($auctionInfo['last_bid']) {
                if ($auctionInfo['status_no'] == FINISHED && $auctionInfo['last_bid']['bid_user'] == session('user_id') && $auctionInfo['order_count'] == 0) {
                    $auctionInfo['is_winner'] = 1;
                }
                $cache_id = $cache_id . '-' . $auctionInfo['last_bid']['bid_time'] . '-1';
            }

            //模板缓存
            $cache_id = sprintf('%X', crc32($cache_id . '-' . session('user_rank', 0) . '_' . $this->config['lang']));
            $content = cache()->remember('auction.dwt.' . $cache_id, $this->config['cache_time'], function () use ($auctionInfo, $id, $user_id, $warehouse_id, $area_id, $area_city) {

                /* 获取用户信息 */
                $where = [
                    'user_id' => $user_id
                ];
                $userInfo = $this->userService->userInfo($where);
                $this->smarty->assign("user", $userInfo);

                //取货品信息
                if ($auctionInfo['product_id'] > 0) {
                    $goods_specifications = $this->goodsService->getSpecificationsList($auctionInfo['goods_id']);

                    $good_products = $this->goodsProdutsService->getGoodProducts($auctionInfo['goods_id'], $auctionInfo['product_id']);

                    $_good_products = explode('|', $good_products[0]['goods_attr']);
                    $products_info = '';
                    foreach ($_good_products as $value) {
                        $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
                    }
                    $this->smarty->assign('products_info', $products_info);
                    unset($goods_specifications, $good_products, $_good_products, $products_info);
                }

                $auctionInfo['gmt_end_time'] = local_strtotime($auctionInfo['end_time']);

                $this->smarty->assign('auction', $auctionInfo);

                /* 取得拍卖商品信息 */
                $where = [
                    'goods_id' => $auctionInfo['goods_id'],
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];
                $goodsInfo = $this->goodsService->getGoodsInfo($where);

                if (empty($goodsInfo)) {
                    //跳转首页
                    return redirect("/");
                }

                $goodsInfo['url'] = $this->dscRepository->buildUri('goods', ['gid' => $auctionInfo['goods_id']], $goodsInfo['goods_name']);
                $this->smarty->assign('auction_goods', $goodsInfo);
                $this->smarty->assign('goods', $goodsInfo);

                /* 出价记录 */
                $auction_log = auction_log($id);
                $this->smarty->assign('auction_log', $auction_log);

                $auction_count = auction_log($id, 1);
                $this->smarty->assign('auction_count', $auction_count);

                //模板赋值
                $this->smarty->assign('cfg', $GLOBALS['_CFG']);
                assign_template();

                $position = assign_ur_here(0, $goodsInfo['goods_name']);
                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
                $this->smarty->assign('pictures', $this->goodsGalleryService->getGoodsGallery($auctionInfo['goods_id']));                    // 商品相册

                //评分 start
                $comment_all = $this->commentService->getCommentsPercent($auctionInfo['goods_id']);

                if ($goodsInfo['user_id'] > 0) {
                    $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goodsInfo['user_id']); //商家所有商品评分类型汇总
                    $this->smarty->assign('comment_all', $comment_all);
                    $this->smarty->assign('merch_cmt', $merchants_goods_comment);
                }
                //评分 end

                $properties = $this->goodsAttrService->getGoodsProperties($auctionInfo['goods_id'], $warehouse_id, $area_id, $area_city);  // 获得商品的规格和属性
                $this->smarty->assign('properties', $properties['pro']);                              // 商品规格
                $this->smarty->assign('specification', $properties['spe']);                              // 商品属性

                assign_dynamic('auction');

                $this->smarty->assign('goods_id', $auctionInfo['goods_id']);                              // 商品ID
                $this->smarty->assign('region_id', $warehouse_id);                              // 商品ID
                $this->smarty->assign('area_id', $area_id);                              // 商品ID

                $basic_info = get_shop_info_content($goodsInfo['user_id']);

                $basic_info['province'] = Region::where('region_id', $basic_info['province'])->value('region_name');
                $basic_info['province'] = Region::where('region_id', $basic_info['city'])->value('region_name');

                /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                $shop_information = $this->merchantCommonService->getShopName($goodsInfo['user_id']);//通过ru_id获取到店铺信息;

                if ($shop_information) {
                    //判断当前商家是平台,还是入驻商家 bylu
                    if ($goodsInfo['user_id'] == 0) {
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
                }

                /*  @author-bylu  end */

                $this->smarty->assign('basic_info', $basic_info);

                $this->smarty->assign('category', 9999999999999999999);

                //更新商品点击次数
                Goods::where('goods_id', $auctionInfo['goods_id'])->increment('click_count', 1);

                return $this->smarty->display('auction.dwt');
            });

            return $content;
        }

        /*------------------------------------------------------ */
        //-- 拍卖商品 --> 出价
        /*------------------------------------------------------ */
        elseif ($act == 'bid') {
            load_helper('order');

            /* 取得参数：拍卖活动id */
            $id = intval(request()->input('id', 0));
            if ($id <= 0) {
                return redirect("/");
            }

            /* 取得拍卖活动信息 */
            $auctionInfo = $this->auctionService->getAuctionInfo($id);
            if (empty($auctionInfo)) {
                return redirect("/");
            }

            /* 活动是否正在进行 */
            if ($auctionInfo['status_no'] != UNDER_WAY) {
                return show_message($GLOBALS['_LANG']['au_not_under_way'], '', '', 'error');
            }

            /* 是否登录 */
            if ($user_id <= 0) {
                return show_message($GLOBALS['_LANG']['au_bid_after_login']);
            }

            $where = [
                'user_id' => $user_id
            ];
            $userInfo = $this->userService->userInfo($where);

            /* 取得出价 */
            $bid_price = round(floatval(request()->input('buy-price', 0)), 2);

            if ($bid_price <= 0) {
                return show_message($GLOBALS['_LANG']['au_bid_price_error'], '', '', 'error');
            }

            /* 如果有一口价且出价大于等于一口价，则按一口价算 */
            $is_ok = false; // 出价是否ok
            if ($auctionInfo['end_price'] > 0) {
                if ($bid_price >= $auctionInfo['end_price']) {
                    $bid_price = $auctionInfo['end_price'];
                    $is_ok = true;
                }
            }

            /* 出价是否有效：区分第一次和非第一次 */
            if (!$is_ok) {
                if ($auctionInfo['bid_user_count'] == 0) {
                    /* 第一次要大于等于起拍价 */
                    $min_price = $auctionInfo['start_price'];
                } else {
                    /* 非第一次出价要大于等于最高价加上加价幅度，但不能超过一口价 */
                    $min_price = $auctionInfo['last_bid']['bid_price'] + $auctionInfo['amplitude'];
                    if ($auctionInfo['end_price'] > 0) {
                        $min_price = min($min_price, $auctionInfo['end_price']);
                    }
                }

                if ($bid_price < $min_price) {
                    return show_message(sprintf($GLOBALS['_LANG']['au_your_lowest_price'], price_format($min_price, false)), '', '', 'error');
                }
            }

            /* 检查联系两次拍卖人是否相同 */
            if (isset($auctionInfo['last_bid']['bid_user']) && $auctionInfo['last_bid']['bid_user'] == $user_id && $bid_price != $auctionInfo['end_price']) {
                return show_message($GLOBALS['_LANG']['au_bid_repeat_user'], '', '', 'error');
            }

            /* 是否需要保证金 */
            if ($auctionInfo['deposit'] > 0) {
                /* 可用资金够吗 */
                if ($userInfo['user_money'] < $auctionInfo['deposit']) {
                    return show_message($GLOBALS['_LANG']['au_user_money_short'], '', '', 'error');
                }

                /* 如果不是第一个出价，解冻上一个用户的保证金 */
                if ($auctionInfo['bid_user_count'] > 0) {
                    log_account_change(
                        $auctionInfo['last_bid']['bid_user'],
                        $auctionInfo['deposit'],
                        (-1) * $auctionInfo['deposit'],
                        0,
                        0,
                        sprintf($GLOBALS['_LANG']['au_unfreeze_deposit'], $auctionInfo['act_name'])
                    );
                }

                /* 冻结当前用户的保证金 */
                log_account_change(
                    $user_id,
                    (-1) * $auctionInfo['deposit'],
                    $auctionInfo['deposit'],
                    0,
                    0,
                    sprintf($GLOBALS['_LANG']['au_freeze_deposit'], $auctionInfo['act_name'])
                );
            }

            /* 插入出价记录 */
            $auction_log = [
                'act_id' => $id,
                'bid_user' => $user_id,
                'bid_price' => $bid_price,
                'bid_time' => gmtime()
            ];

            AuctionLog::insert($auction_log);

            /* 出价是否等于一口价 */
            if ($bid_price == $auctionInfo['end_price']) {
                /* 结束拍卖活动 */
                GoodsActivity::where('act_id', $id)->update(['is_finished' => 1]);
            }

            /* 跳转到活动详情页 */
            return dsc_header("Location: auction.php?act=view&id=$id\n");
        }

        /*------------------------------------------------------ */
        //-- 拍卖商品 --> 购买
        /*------------------------------------------------------ */
        elseif ($act == 'buy') {
            /* 查询：取得参数：拍卖活动id */
            $id = intval(request()->input('id', 0));
            if ($id <= 0) {
                return redirect("/");
            }

            /* 查询：取得拍卖活动信息 */
            $auctionInfo = $this->auctionService->getAuctionInfo($id);
            if (empty($auctionInfo)) {
                return redirect("/");
            }

            /* 查询：活动是否已结束 */
            if ($auctionInfo['status_no'] != FINISHED) {
                return show_message($GLOBALS['_LANG']['au_not_finished'], '', '', 'error');
            }

            /* 查询：有人出价吗 */
            if ($auctionInfo['bid_user_count'] <= 0) {
                return show_message($GLOBALS['_LANG']['au_no_bid'], '', '', 'error');
            }

            /* 查询：是否已经有订单 */
            if ($auctionInfo['order_count'] > 0) {
                return show_message($GLOBALS['_LANG']['au_order_placed']);
            }

            /* 查询：是否登录 */
            if ($user_id <= 0) {
                return show_message($GLOBALS['_LANG']['au_buy_after_login']);
            }

            /* 查询：最后出价的是该用户吗 */
            if ($auctionInfo['last_bid']['bid_user'] != $user_id) {
                return show_message($GLOBALS['_LANG']['au_final_bid_not_you'], '', '', 'error');
            }

            /* 查询：取得商品信息 */
            $where = [
                'goods_id' => $auctionInfo['goods_id'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goodsInfo = $this->goodsService->getGoodsInfo($where);

            /* 查询：处理规格属性 */
            $goods_attr = '';
            $goods_attr_id = '';
            if ($auctionInfo['product_id'] > 0) {
                $product_info = $this->goodsProdutsService->getGoodProducts($auctionInfo['goods_id'], $auctionInfo['product_id']);

                $goods_attr_id = str_replace('|', ',', $product_info[0]['goods_attr']);

                /* 查询：查询规格名称和值，不考虑价格 */
                $goods_attr = $this->goodsService->getGoodsAttrList($goods_attr_id);
            } else {
                $auctionInfo['product_id'] = 0;
            }

            if (!empty(session('user_id'))) {
                $sess = "";
            } else {
                $sess = $this->sessionRepository->realCartMacIp();
            }

            /* 清空购物车中所有拍卖商品 */
            $this->cartCommonService->clearCart($user_id, CART_AUCTION_GOODS);

            /* 加入购物车 */
            $cart = [
                'user_id' => $user_id,
                'session_id' => $sess,
                'goods_id' => $auctionInfo['goods_id'],
                'goods_sn' => addslashes($goodsInfo['goods_sn']),
                'goods_name' => addslashes($goodsInfo['goods_name']),
                'market_price' => $goodsInfo['market_price'],
                'goods_price' => $auctionInfo['last_bid']['bid_price'],
                'goods_number' => 1,
                'goods_attr' => addslashes($goods_attr),
                'goods_attr_id' => $goods_attr_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                'is_real' => $goodsInfo['is_real'],
                'ru_id' => $goodsInfo['user_id'],
                'extension_code' => addslashes($goodsInfo['extension_code']),
                'parent_id' => 0,
                'rec_type' => CART_AUCTION_GOODS,
                'is_gift' => 0
            ];

            $rec_id = Cart::insertGetId($cart);

            $this->cartRepository->pushCartValue($rec_id);

            /* 记录购物流程类型：团购 */
            session([
                'flow_type' => CART_AUCTION_GOODS,
                'extension_code' => 'auction',
                'extension_id' => $id,
                'direct_shopping' => 2
            ]);

            /* 进入收货人页面 */
            return dsc_header("Location: ./flow.php?step=checkout&direct_shopping=2\n");
        }
    }
}
