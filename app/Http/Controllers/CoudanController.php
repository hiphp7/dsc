<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\FavourableActivity;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\AddonItemService;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CarthandleService;
use App\Services\Cart\CartService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGuessService;
use App\Services\Goods\GoodsService;
use App\Services\Order\OrderGoodsService;

class CoudanController extends InitController
{
    protected $addonItemService;
    protected $goodsService;
    protected $cartService;
    protected $baseRepository;
    protected $sessionRepository;
    protected $config;
    protected $articleCommonService;
    protected $goodsCommonService;
    protected $orderGoodsService;
    protected $carthandleService;
    protected $dscRepository;
    protected $goodsGuessService;

    public function __construct(
        AddonItemService $addonItemService,
        GoodsService $goodsService,
        CartService $cartService,
        BaseRepository $baseRepository,
        SessionRepository $sessionRepository,
        ArticleCommonService $articleCommonService,
        GoodsCommonService $goodsCommonService,
        OrderGoodsService $orderGoodsService,
        CarthandleService $carthandleService,
        DscRepository $dscRepository,
        GoodsGuessService $goodsGuessService
    )
    {
        $this->addonItemService = $addonItemService;
        $this->goodsService = $goodsService;
        $this->cartService = $cartService;
        $this->baseRepository = $baseRepository;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->goodsCommonService = $goodsCommonService;
        $this->orderGoodsService = $orderGoodsService;
        $this->carthandleService = $carthandleService;
        $this->dscRepository = $dscRepository;
        $this->goodsGuessService = $goodsGuessService;
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

        load_helper('order');

        $active_id = (int)request()->input('id', 0);
        $user_id = session('user_id', 0);

        $act = addslashes(request()->input('act', 'index'));
        $act = $act ? $act : 'index';
        /**
         * 公共文件
         * 初始化
         */

        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

        /* ------------------------------------------------------ */
        //-- 凑单页面
        /* ------------------------------------------------------ */
        if ($act == 'index') {

            // 获取页面url中的查询参数
            $sort = 'goods_id';
            if (request()->has('sort')) {
                $get_sort = addslashes(trim(request()->input('sort')));
                if (in_array(trim(strtolower($get_sort)), ['goods_id', 'sales_volume', 'shop_price'])) {
                    $sort = $get_sort;
                }
            }
            $order = 'DESC';
            if (request()->has('order')) {
                $get_order = addslashes(trim(request()->input('order')));
                if (in_array(trim(strtolower($get_order)), ['ASC', 'DESC'])) {
                    $order = $get_order;
                }
            }

            $count = $this->addonItemService->getFavourableGoodsCount(session('user_rank', 0), $active_id, $warehouse_id, $area_id, $area_city);
            /* 取得每页记录数 */
            $size = 30;

            /* 计算总页数 */
            $page_count = ceil($count / $size);

            /* 取得当前页 */
            $page = (int)request()->input('page', 1);

            $page = $page > $page_count && !empty($page_count) ? $page_count : $page;

            //模板缓存
            $cache_id = sprintf('%X', crc32($active_id . '-' . $area_city . '-' . $size . '-' . $page . '-' . $count . '-' . $order . '-' . $sort . '-' . $warehouse_id . '-' . $area_id . '-' . $area_city . '-' . session('user_rank', 0) . '_' . $this->config['lang']));
            $content = cache()->remember('coudan.dwt.' . $cache_id, $this->config['cache_time'], function () use ($active_id, $user_id, $warehouse_id, $area_id, $area_city, $size, $page, $count, $order, $sort) {

                $position = assign_ur_here(0, $GLOBALS['_LANG']['shopping_list']);
                $this->smarty->assign('page_title', $position['title']);    // 页面标题

                assign_template();
                $categories_pro = get_category_tree_leve_one();
                $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

                /**
                 * Start
                 *
                 * 猜你喜欢商品
                 */
                $where = [
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city,
                    'user_id' => $user_id,
                    'history' => 1,
                    'page' => 1,
                    'limit' => 7
                ];
                $guess_goods = $this->goodsGuessService->getGuessGoods($where);

                $this->smarty->assign('guess_goods', $guess_goods);
                /* End */

                // 判断活动是否存在
                $active_num = FavourableActivity::where('review_status', 3)->where('act_id', $active_id)->count();
                if ($active_num == 0) {
                    return show_message($GLOBALS['_LANG']['activity_error']);
                }
                $this->smarty->assign('active_id', $active_id);

                $pager = get_pager('coudan.php', ['id' => $active_id, 'sort' => $sort, 'order' => $order], $count, $page, $size);
                $this->smarty->assign('pager', $pager);

                // 查询活动包含的所有商品
                $favourable_goods_list = $this->addonItemService->getFavourableGoodsList(session('user_rank', 0), $active_id, $sort, $order, $size, $page, $warehouse_id, $area_id, $area_city);

                $this->smarty->assign('favourable_goods', $favourable_goods_list);

                //凑单活动类型 满减-满换-打折
                $this->smarty->assign('act_type_txt', $this->addonItemService->getActType(session('user_rank', 0), $active_id));

                $this->smarty->assign('favourable_id', $active_id);
                // 查询活动中 已加入购物车的商品
                $cart_fav_goods = $this->addonItemService->getCartFavourableGoods(session('user_rank', 0), $active_id, $warehouse_id, $area_id, $area_city);
                $this->smarty->assign('cart_favourable_goods', $cart_fav_goods);

                $cart_fav_num = 0;
                $cart_fav_total = 0;
                if ($cart_fav_goods) {
                    foreach ($cart_fav_goods as $key => $row) {
                        $cart_fav_num += $row['goods_number'];
                        $cart_fav_total += $row['shop_price'] * $row['goods_number'];
                    }
                }

                // 同一优惠活动添加到购物车的商品数量
                $this->smarty->assign('cart_fav_num', $cart_fav_num);
                $this->smarty->assign('cart_fav_total', price_format($cart_fav_total));

                $this->smarty->assign('region_id', $warehouse_id);
                $this->smarty->assign('area_id', $area_id);
                $this->smarty->assign('area_city', $area_city);

                return $this->smarty->display('coudan.dwt');
            });

            return $content;
        }

        /* ------------------------------------------------------ */
        //-- 凑单列表页面添加购物车
        /* ------------------------------------------------------ */
        elseif ($act == 'ajax_update_cart') {
            $goods = strip_tags(urldecode(request()->input('goods', '')));
            $goods = json_str_iconv($goods);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_id' => ''];

            if (empty($goods)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $goods = dsc_decode($goods);

            $goods->goods_id = intval($goods->goods_id);

            if ($goods->goods_id < 1) {
                return redirect("/");
            }

            /* 检查：该地区是否支持配送 ecmoban模板堂 --zhuo */
            if ($this->config['open_area_goods'] == 1) {
                $where = [
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'goods_id' => $goods->goods_id,
                    'area_city' => $area_city
                ];
                $goodsInfo = $this->goodsService->getGoodsInfo($where);

                $area_count = $this->goodsService->getHasLinkAreaGods($goods->goods_id, $area_id, $area_city);

                $no_area = 1;
                if ($area_count < 1) {
                    $no_area = 2;
                }

                if ($no_area == 2) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['shiping_prompt'];

                    return response()->json($result);
                } elseif ($goodsInfo['review_status'] <= 2) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['shelves_goods'];

                    return response()->json($result);
                }
            }

            $spe_array = $this->cartService->cartGoodsAttr($goods->goods_id, $warehouse_id, $area_id, $area_city);

            /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
            if (empty($goods->spec) && empty($goods->quick) && !empty($spe_array)) {

                $goods_attr_id = '';
                $spe_array = array_values($spe_array);
                foreach ($spe_array as $key => $attr) {
                    if ($key == 0) {
                        $goods_attr_id .= $attr['values'][0]['id'];
                    } else {
                        $goods_attr_id .= ',' . $attr['values'][0]['id'];
                    }
                }

                $result['error'] = ERR_NEED_SELECT_ATTR;
                $result['goods_id'] = $goods->goods_id;
                $result['warehouse_id'] = $warehouse_id;
                $result['area_id'] = $area_id;
                $result['parent'] = $goods->parent ?? 0;
                $result['area_city'] = $area_city;
                $this->smarty->assign('spe_array', $spe_array);

                $shop_price = $this->goodsCommonService->getFinalPrice($goods->goods_id, $goods->number, true, $goods_attr_id, $warehouse_id, $area_id, $area_city, 0, 0, 0);
                $result['result'] = price_format($shop_price);
                $this->smarty->assign('shop_price', $result['result']);

                $result['message'] = $this->smarty->fetch("library/goods_attr.lbi");

                $result['active_id'] = $goods->active_id;

                return response()->json($result);
            }

            /* 检查：商品数量是否合法 */
            if (!is_numeric($goods->number) || intval($goods->number) <= 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_number'];
            } /* 更新：购物车 */
            else {
                // 限购
                $xiangouInfo = $this->goodsCommonService->getPurchasingGoodsInfo($goods->goods_id);
                if ($xiangouInfo['is_xiangou'] == 1) {
                    $res = Cart::where('goods_id', $goods->goods_id);

                    if (!empty($user_id)) {
                        $res = $res->where('user_id', $user_id);
                    } else {
                        $session_id = $this->sessionRepository->realCartMacIp();
                        $res = $res->where('session_id', $session_id);
                    }

                    //获取购物车数量
                    $cart_number = $res->value('goods_number');

                    $start_date = $xiangouInfo['xiangou_start_date'];
                    $end_date = $xiangouInfo['xiangou_end_date'];
                    $orderGoods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods->goods_id, $user_id);

                    $nowTime = gmtime();
                    if ($nowTime > $start_date && $nowTime < $end_date) {
                        if ($orderGoods['goods_number'] >= $xiangouInfo['xiangou_num']) {
                            $result['error'] = 1;
                            $result['message'] = $GLOBALS['_LANG']['purchasing_prompt'];
                            return response()->json($result);
                        } else {
                            if ($xiangouInfo['xiangou_num'] > 0) {
                                if ($cart_number + $orderGoods['goods_number'] + $goods->number > $xiangouInfo['xiangou_num']) {
                                    $result['error'] = 1;
                                    $result['message'] = $GLOBALS['_LANG']['purchasing_prompt_two'];
                                    return response()->json($result);
                                }
                            }
                        }
                    }
                }
                //ecmoban模板堂 --zhuo end 限购

                $goods->active_id = intval($goods->active_id);

                // 更新：添加到购物车
                $add_res = $this->carthandleService->addtoCart($goods->goods_id, $goods->number, $goods->spec, 0, $warehouse_id, $area_id, $area_city, '-1', 0, '', '', $goods->active_id);
                if ($add_res['error'] == 0) {
                    if ($this->config['cart_confirm'] > 2) {
                        $result['message'] = '';
                    } else {
                        $result['message'] = $this->config['cart_confirm'] == 1 ? $GLOBALS['_LANG']['addto_cart_success_1'] : $GLOBALS['_LANG']['addto_cart_success_2'];
                    }

                    //凑单活动类型 满减-满换-打折
                    $this->smarty->assign('act_type_txt', $this->addonItemService->getActType(session('user_rank', 0), $goods->active_id));
                    $this->smarty->assign('favourable_id', $goods->active_id);

                    $cart_fav_goods = $this->addonItemService->getCartFavourableGoods(session('user_rank', 0), $goods->active_id, $warehouse_id, $area_id, $area_city);
                    $this->smarty->assign('cart_favourable_goods', $cart_fav_goods);

                    $cart_fav_num = 0;
                    $cart_fav_total = 0;

                    if ($cart_fav_goods) {
                        foreach ($cart_fav_goods as $key => $row) {
                            $cart_fav_num += $row['goods_number'];
                            $cart_fav_total += $row['shop_price'] * $row['goods_number'];
                        }
                    }

                    $this->smarty->assign('cart_fav_num', $cart_fav_num);
                    $this->smarty->assign('cart_fav_total', price_format($cart_fav_total));

                    $result['content'] = $this->smarty->fetch("library/coudan_top_list.lbi");

                    $result['one_step_buy'] = session('one_step_buy', 0);
                } else {
                    $result['message'] = $add_res['message'];
                    $result['error'] = $add_res['error'];
                    $result['goods_id'] = stripslashes($goods->goods_id);
                    if (is_array($goods->spec)) {
                        $result['product_spec'] = implode(',', $goods->spec);
                    } else {
                        $result['product_spec'] = $goods->spec;
                    }
                }
            }

            $result['confirm_type'] = !empty($this->config['cart_confirm']) ? $this->config['cart_confirm'] : 2;
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除购物车里面的优惠活动凑单商品
        /* ------------------------------------------------------ */
        elseif ($act == 'delete_cart_fav_goods') {
            $result = ['error' => 0, 'content' => '', 'message' => ''];
            $rec_id = (int)request()->input('rec_id', 0);
            $active_id = (int)request()->input('favourable_id', 0);

            if ($rec_id == 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['cart_no_goods'];
                return response()->json($result);
            }

            // 删除购物车商品
            Cart::where('rec_id', $rec_id)->delete();

            // 当优惠活动商品不满足最低金额时-删除赠品
            $favourable = favourable_info($active_id);
            $favourable_available = favourable_available($favourable);

            if (!$favourable_available) {
                $res = Cart::where('is_gift', '<>', 0);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', intval(session('user_id')));
                } else {
                    $session_id = $this->sessionRepository->realCartMacIp();
                    $res = $res->where('session_id', $session_id);
                }

                $res->delete();
            }

            //凑单活动类型 满减-满换-打折
            $this->smarty->assign('act_type_txt', $this->addonItemService->getActType(session('user_rank', 0), $active_id));

            $this->smarty->assign('favourable_id', $active_id);

            $cart_fav_goods = $this->addonItemService->getCartFavourableGoods(session('user_rank', 0), $active_id, $warehouse_id, $area_id, $area_city);

            $this->smarty->assign('cart_favourable_goods', $cart_fav_goods);

            $cart_fav_num = 0;
            $cart_fav_total = 0;

            if ($cart_fav_goods) {
                foreach ($cart_fav_goods as $key => $row) {
                    $cart_fav_num += $row['goods_number'];
                    $cart_fav_total += $row['shop_price'] * $row['goods_number'];
                }
            }

            $this->smarty->assign('cart_fav_num', $cart_fav_num);
            $this->smarty->assign('cart_fav_total', price_format($cart_fav_total));


            $result['content'] = $this->smarty->fetch("library/coudan_top_list.lbi");
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除购物车里面的优惠活动凑单商品
        /* ------------------------------------------------------ */
        elseif ($act == 'cart_info') {

            $result = ['error' => 0, 'content' => '', 'message' => ''];

            $cart = Cart::selectRaw("SUM(goods_price * goods_number) as total, SUM(goods_number) as number")
                ->where('user_id', $user_id)
                ->where('act_id', $active_id)
                ->first();
            $cart = $cart ? $cart->toArray() : [];

            $result['total'] = $cart['total'] ?? 0;
            $result['total_format'] = $this->dscRepository->getPriceFormat($result['total']);
            $result['number'] = $cart['number'] ?? 0;

            return response()->json($result);
        }
    }
}
