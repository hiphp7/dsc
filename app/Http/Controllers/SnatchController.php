<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\GoodsActivity;
use App\Models\OrderGoods;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Models\SnatchLog;
use App\Models\Users;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\SnatchService;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartCommonService;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 夺宝奇兵前台页面
 */
class SnatchController extends InitController
{
    protected $snatchService;
    protected $goodsService;
    protected $goodsAttrService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $sessionRepository;
    protected $config;
    protected $articleCommonService;
    protected $dscRepository;
    protected $cartCommonService;
    protected $cartRepository;
    protected $goodsProdutsService;

    public function __construct(
        SnatchService $snatchService,
        GoodsService $goodsService,
        GoodsAttrService $goodsAttrService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        SessionRepository $sessionRepository,
        ArticleCommonService $articleCommonService,
        DscRepository $dscRepository,
        CartCommonService $cartCommonService,
        CartRepository $cartRepository,
        GoodsProdutsService $goodsProdutsService
    )
    {
        $this->snatchService = $snatchService;
        $this->goodsService = $goodsService;
        $this->goodsAttrService = $goodsAttrService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->dscRepository = $dscRepository;
        $this->cartCommonService = $cartCommonService;
        $this->cartRepository = $cartRepository;
        $this->goodsProdutsService = $goodsProdutsService;
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

        assign_ur_here();
        $this->smarty->assign('now_time', gmtime());           // 当前系统时间

        /*------------------------------------------------------ */
        //-- 如果用没有指定活动id，将页面重定向到即将结束的活动
        /*------------------------------------------------------ */

        $id = (int)request()->input('id', 0);
        $user_id = session('user_id', 0);

        $this->smarty->assign('user_id', $user_id);

        $template = "snatch_list";
        $act = addslashes(request()->input('act', ''));
        if (empty($act) && !$id) {
            $template = "snatch_index";
            $act = 'list';
        } elseif (empty($act) && $id) {
            $act = 'main';
        }

        if ($act == 'list') {

            //瀑布流 by wu start
            $this->smarty->assign('category_load_type', $this->config['category_load_type']);
            $this->smarty->assign('query_string', preg_replace('/act=\w+&?/', '', request()->server('QUERY_STRING')));
            //瀑布流 by wu end

            /* 初始化分页信息 */
            // 取得当前页
            $page = (int)request()->input('page', 1);
            $size = isset($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10; // 取得每页记录数
            $size = 15;
            $keywords = htmlspecialchars(trim(request()->input('keywords', '')));

            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = "snatch_id";
            $sort = addslashes(request()->input('sort', ''));
            $sort = in_array(trim(strtolower($sort)), ['snatch_id', 'end_time', 'start_time']) ? trim($sort) : $default_sort_order_type;
            $order = addslashes(request()->input('order', ''));
            $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;

            assign_template();
            assign_dynamic('snatch');
            $position = assign_ur_here(1, $GLOBALS['_LANG']['snatch']);
            $this->smarty->assign('page_title', $position['title']);
            $this->smarty->assign('ur_here', $position['ur_here']);

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
            $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-typesnatch.xml" : 'feed.php?type=snatch'); // RSS URL

            $snatch_list = $this->snatchService->getSnatchList($keywords, $size, $page, $sort, $order, $warehouse_id, $area_id, $area_city);
            $this->smarty->assign('snatch_list', $snatch_list);     //所有有效的夺宝奇兵列表

            $count = $this->snatchService->getSnatchCount($keywords);

            //瀑布流 by wu start
            if (!$this->config['category_load_type']) {
                /* 设置分页链接 */
                $pager = get_pager('snatch.php', ['act' => 'list', 'keywords' => $keywords, 'sort' => $sort, 'order' => $order], $count, $page, $size);
                $this->smarty->assign('pager', $pager);
            }
            //瀑布流 by wu end	snatch

            /* 广告位 */
            $activity_top_banner = '';
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $activity_top_banner .= "'activity_top_ad_snatch" . $i . ","; //轮播图
            }
            $this->smarty->assign('activity_top_banner', $activity_top_banner);

            //获取已拍商品数量
            $res = OrderGoods::selectRaw("SUM(goods_number) AS goods_number")
                ->whereHas('getOrder', function ($query) {
                    $query = $query->where('main_count', 0);
                    $query->where('extension_code', 'snatch')->where('pay_status', PS_PAYED);
                });

            $res = $res->first();

            $res = $res ? $res->toArray() : [];

            $snatch_goods_num = $res ? $res['goods_number'] : 0;

            $this->smarty->assign('snatch_goods_num', $snatch_goods_num);

            $where = [
                'act_type' => GAT_SNATCH,
                'time' => gmtime(),
                'type' => 'hot',
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $this->smarty->assign('hot_goods', $this->snatchService->getExchangeRecommendGoods($where));  //热门

            return $this->smarty->display($template . '.dwt');
        } /* 瀑布流 by wu */
        elseif ($act == 'load_more_goods') {

            /* 初始化分页信息 */

            // 取得当前页
            $page = (int)request()->input('page', 1);
            $size = isset($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10; // 取得每页记录数
            $size = 15;
            $keywords = htmlspecialchars(trim(request()->input('keywords', '')));
            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = "snatch_id";

            $sort = addslashes(request()->input('sort', ''));
            $sort = in_array(trim(strtolower($sort)), ['snatch_id', 'end_time', 'start_time']) ? trim($sort) : $default_sort_order_type;
            $order = addslashes(request()->input('order', ''));
            $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;

            $snatch_list = $this->snatchService->getSnatchList($keywords, $size, $page, $sort, $order, $warehouse_id, $area_id, $area_city);
            $this->smarty->assign('snatch_list', $snatch_list);     //所有有效的夺宝奇兵列表

            $this->smarty->assign('type', 'snatch');
            $result = ['error' => 0, 'message' => '', 'cat_goods' => '', 'best_goods' => ''];
            $result['cat_goods'] = html_entity_decode($this->smarty->fetch('library/more_goods_page.lbi'));
            return response()->json($result);
        } /* 显示页面部分 */
        elseif ($act == 'main') {
            $where = [
                'act_id' => $id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];

            $goods = $this->snatchService->getSnatch($where);

            if ($goods) {
                $position = assign_ur_here($goods['cat_id'], $goods['snatch_name'], [], '', $goods['user_id']);
                $myprice = $this->snatchService->getMyPrice($id);
                if ($goods['is_end']) {
                    //如果活动已经结束,获取活动结果
                    $this->smarty->assign('result', get_snatch_result($id));
                }

                $this->smarty->assign('id', $id);
                $this->smarty->assign('snatch_goods', $goods); // 竞价商品
                $this->smarty->assign('goods', $goods); // 竞价商品
                $this->smarty->assign('myprice', $myprice);
                if (isset($goods['product_id']) && $goods['product_id'] > 0) {
                    $goods_specifications = $this->goodsService->getSpecificationsList($goods['goods_id']);

                    $good_products = $this->goodsProdutsService->getGoodProducts($goods['goods_id'], $goods['product_id']);

                    $_good_products = explode('|', $good_products[0]['goods_attr']);
                    $products_info = '';
                    foreach ($_good_products as $value) {
                        $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
                    }
                    $this->smarty->assign('products_info', $products_info);
                    unset($goods_specifications, $good_products, $_good_products, $products_info);
                }
            } else {
                return show_message($GLOBALS['_LANG']['now_not_snatch']);
            }

            /* 调查 */
            $vote = get_vote();
            if (!empty($vote)) {
                $this->smarty->assign('vote_id', $vote['id']);
                $this->smarty->assign('vote', $vote['content']);
            }

            assign_template();
            assign_dynamic('snatch');
            $this->smarty->assign('page_title', $position['title']);
            $this->smarty->assign('ur_here', $position['ur_here']);

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
            $this->smarty->assign('price_list', $this->snatchService->getPriceList($id));
            $this->smarty->assign('price_list_count', count($this->snatchService->getPriceList($id)));
            $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-typesnatch.xml" : 'feed.php?type=snatch'); // RSS URL

            $this->smarty->assign('pictures', $this->goodsGalleryService->getGoodsGallery($goods['goods_id']));                    // 商品相册

            //评分 start
            $comment_all = $this->commentService->getCommentsPercent($goods['goods_id']);

            if ($goods['user_id'] > 0) {
                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goods['user_id']); //商家所有商品评分类型汇总
                $this->smarty->assign('merch_cmt', $merchants_goods_comment);
            }
            //评分 end

            $this->smarty->assign('comment_all', $comment_all);

            $shop_information = $this->merchantCommonService->getShopName($goods['user_id']);//通过ru_id获取到店铺信息;
            $basic_info = $shop_information;

            if ($basic_info) {
                $basic_info['province'] = Region::where('region_id', $goods['province'])->value('region_name');
                $basic_info['province'] = $basic_info['province'] ? $basic_info['province'] : '';

                $basic_info['city'] = Region::where('region_id', $goods['city'])->value('region_name');
                $basic_info['city'] = $basic_info['city'] ? $basic_info['city'] : '';
                $basic_info['kf_type'] = $goods['kf_type'];
                $basic_info['shop_name'] = $goods['shop_name'];

                $chat = $this->dscRepository->chatQq($basic_info);
                $basic_info['kf_qq'] = $chat['kf_qq'];
                $basic_info['kf_ww'] = $chat['kf_ww'];
            }

            /*  @author-bylu 判断当前商家是否允许"在线客服" start */

            //判断当前商家是平台,还是入驻商家 bylu
            if ($goods['user_id'] == 0) {
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
            /*  @author-bylu  end */

            $properties = $this->goodsAttrService->getGoodsProperties($goods['goods_id'], $warehouse_id, $area_id, $area_city);  // 获得商品的规格和属性

            $this->smarty->assign('cfg', $GLOBALS['_CFG']);
            $this->smarty->assign('properties', $properties['pro']);                              // 商品规格
            $this->smarty->assign('specification', $properties['spe']);                              // 商品属性
            $this->smarty->assign('goods_id', $goods['goods_id']);                              // 商品ID
            $this->smarty->assign('region_id', $warehouse_id);                              // 商品ID
            $this->smarty->assign('area_id', $area_id);                              // 商品ID

            $this->smarty->assign('basic_info', $basic_info);

            $where = [
                'act_type' => GAT_SNATCH,
                'time' => gmtime(),
                'type' => 'hot',
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $this->smarty->assign('hot_goods', $this->snatchService->getExchangeRecommendGoods($where));  //热门

            return $this->smarty->display('snatch.dwt');
        }

        /* 最新出价列表 */
        if ($act == 'new_price_list') {
            $result = ['error' => 0, 'content' => ''];

            $myprice = $this->snatchService->getMyPrice($id);

            $this->smarty->assign('price_list', $myprice['bid_price']);
            $this->smarty->assign('price_list_count', count($myprice['bid_price']));
            $result['content'] = $this->smarty->fetch('library/snatch_price.lbi');

            $result['id'] = $id;
            return response()->json($result);
        }

        /* 用户出价处理 */
        if ($act == 'bid') {
            $result = ['error' => 0, 'content' => ''];

            $price = floatval(request()->input('price', 0));
            $price = round($price, 2);

            $warehouse_id = (int)request()->input('region_id', 0);
            $area_id = (int)request()->input('area_id', 0);

            /* 测试是否登陆 */
            if (empty(session('user_id'))) {
                $result['error'] = 1;
                $result['prompt'] = 1;
                $result['content'] = $GLOBALS['_LANG']['not_login'];
                $result['back_url'] = "snatch.php?id=" . $id;
                return response()->json($result);
            }

            /* 获取活动基本信息用于校验 */
            $row = GoodsActivity::select('act_name as snatch_name', 'end_time', 'ext_info')->where('act_id', $id)->where('review_status', 3)->first();
            $row = $row ? $row->toArray() : [];

            if ($row) {
                $info = unserialize($row['ext_info']);
                if ($info) {
                    foreach ($info as $key => $val) {
                        $row[$key] = $val;
                    }
                }
            }

            if (empty($row)) {
                $result['error'] = 1;
                $result['content'] = $this->db->error();
                return response()->json($result);
            }

            if ($row['end_time'] < gmtime()) {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['snatch_is_end'];
                return response()->json($result);
            }

            /* 检查出价是否合理 */
            if ($price < $row['start_price'] || $price > $row['end_price']) {
                $result['error'] = 1;

                $result['content'] = sprintf($GLOBALS['_LANG']['not_in_range'], $row['start_price'], $row['end_price']);
                return response()->json($result);
            }

            /* 检查用户是否已经出同一价格 */
            $count = SnatchLog::where('snatch_id', $id)->where('user_id', session('user_id'))->where('bid_price', $price)->count();
            if ($count > 0) {
                $result['error'] = 1;
                $result['content'] = sprintf($GLOBALS['_LANG']['also_bid'], price_format($price, false));
                return response()->json($result);
            }

            /* 检查用户积分是否足够 */
            $pay_points = Users::where('user_id', session('user_id', 0))->value('pay_points');
            if ($row['cost_points'] > $pay_points) {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['lack_pay_points'];
                return response()->json($result);
            }

            log_account_change(session('user_id', 0), 0, 0, 0, 0 - $row['cost_points'], sprintf($GLOBALS['_LANG']['snatch_log'], $row['snatch_name'])); //扣除用户积分

            $other = [
                'snatch_id' => $id,
                'user_id' => $user_id,
                'bid_price' => $price,
                'bid_time' => gmtime()
            ];
            SnatchLog::insert($other);

            $where = [
                'act_id' => $id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $snatch_goods = $this->snatchService->getSnatch($where);
            $this->smarty->assign('snatch_goods', $snatch_goods); // 竞价商品

            if ($snatch_goods['is_end']) {
                //如果活动已经结束,获取活动结果
                $this->smarty->assign('result', get_snatch_result($id));
            }

            $this->smarty->assign('price_list', $this->snatchService->getPriceList($id));
            $this->smarty->assign('price_list_count', count($this->snatchService->getPriceList($id)));

            $this->smarty->assign('myprice', $this->snatchService->getMyPrice($id));
            $this->smarty->assign('id', $id);
            $result['content'] = $this->smarty->fetch('library/snatch.lbi');
            $result['content_price'] = $this->smarty->fetch('library/snatch_price.lbi');

            $result['id'] = $id;
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 购买商品
        /*------------------------------------------------------ */
        if ($act == 'buy') {
            if (empty($id)) {
                return dsc_header("Location: ./\n");
            }

            if (empty(session('user_id'))) {
                return show_message($GLOBALS['_LANG']['not_login']);
            }

            $where = [
                'act_id' => $id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $snatchInfo = $this->snatchService->getSnatch($where);


            if (empty($snatchInfo)) {
                return dsc_header("Location: ./\n");
            }

            /* 未结束，不能购买 */
            if (empty($snatchInfo['is_end'])) {
                $page = $this->dscRepository->buildUri('snatch', ['sid' => $id]);
                return dsc_header("Location: $page\n");
            }

            $result = get_snatch_result($id);

            if (session('user_id') != $result['user_id']) {
                return show_message($GLOBALS['_LANG']['not_for_you']);
            }

            //检查是否已经购买过
            if ($result['order_count'] > 0) {
                return show_message($GLOBALS['_LANG']['order_placed']);
            }

            /* 处理规格属性 */
            $goods_attr = '';
            $goods_attr_id = '';
            if ($snatchInfo['product_id'] > 0) {
                $product_info = $this->goodsProdutsService->getGoodProducts($snatchInfo['goods_id'], $snatchInfo['product_id']);

                $goods_attr_id = str_replace('|', ',', $product_info[0]['goods_attr']);

                /* 查询：查询规格名称和值，不考虑价格 */
                $goods_attr = $this->goodsService->getGoodsAttrList($goods_attr_id);
            } else {
                $snatchInfo['product_id'] = 0;
            }

            /* 清空购物车中所有商品 */
            $this->cartCommonService->clearCart($user_id, CART_SNATCH_GOODS);

            $session_id = $this->sessionRepository->realCartMacIp();
            $sess = empty(session('user_id')) ? $session_id : '';

            /* 加入购物车 */
            $cart = [
                'user_id' => session('user_id', 0),
                'session_id' => $sess,
                'goods_id' => $snatchInfo['goods_id'],
                'product_id' => $snatchInfo['product_id'],
                'goods_sn' => addslashes($snatchInfo['goods_sn']),
                'goods_name' => addslashes($snatchInfo['goods_name']),
                'market_price' => $snatchInfo['market_price'],
                'goods_price' => $result['buy_price'],
                'goods_number' => 1,
                'goods_attr' => $goods_attr,
                'goods_attr_id' => $goods_attr_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                'is_real' => $snatchInfo['is_real'],
                'ru_id' => $snatchInfo['user_id'],
                'extension_code' => addslashes($snatchInfo['extension_code']),
                'parent_id' => 0,
                'rec_type' => CART_SNATCH_GOODS,
                'is_gift' => 0
            ];

            $rec_id = Cart::insertGetId($cart);

            $this->cartRepository->pushCartValue($rec_id);

            /* 记录购物流程类型：夺宝奇兵 */
            session([
                'flow_type' => CART_SNATCH_GOODS,
                'extension_code' => 'snatch',
                'extension_id' => $id,
                'direct_shopping' => 3
            ]);

            /* 进入收货人页面 */
            return dsc_header("Location: ./flow.php?step=checkout&direct_shopping=3\n");
        }
    }
}
