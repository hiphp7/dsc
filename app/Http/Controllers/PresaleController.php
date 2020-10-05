<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\PresaleService;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryService;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Erp\JigonManageService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;


/**
 * 预售商品
 */
class PresaleController extends InitController
{
    protected $areaService;
    protected $presaleService;
    protected $goodsService;
    protected $dscRepository;
    protected $categoryService;
    protected $jigonManageService;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $goodsCommonService;
    protected $goodsWarehouseService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $sessionRepository;
    protected $config;
    protected $articleCommonService;
    protected $cartCommonService;
    protected $cartRepository;

    public function __construct(
        AreaService $areaService,
        PresaleService $presaleService,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        CategoryService $categoryService,
        JigonManageService $jigonManageService,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        GoodsCommonService $goodsCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        SessionRepository $sessionRepository,
        ArticleCommonService $articleCommonService,
        CartCommonService $cartCommonService,
        CartRepository $cartRepository
    )
    {
        $this->areaService = $areaService;
        $this->presaleService = $presaleService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->categoryService = $categoryService;
        $this->jigonManageService = $jigonManageService;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->sessionRepository = $sessionRepository;
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

        get_request_filter();
        $_POST = get_request_filter($_POST, 1);

        $user_id = session('user_id', 0);

        //分类导航页
        $this->smarty->assign('pre_nav_list', $this->presaleService->getPreNav());
        /*------------------------------------------------------ */
        //-- act 操作项的初始化
        /*------------------------------------------------------ */
        $act = addslashes(request()->input('act', 'index'));
        $act = $act ? $act : 'index';

        if (!empty($act) && $act == 'price') {
            $goods_id = (int)request()->input('id', 0);

            $res = ['err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1];

            $attr_id = request()->input('attr', '');
            $attr_id = $attr_id ? explode(',', $attr_id) : [];

            $number = (int)request()->input('number', 1);

            //加载类型
            $type = (int)request()->input('type', 0);

            $get_goods_attr = request()->input('goods_attr', '');
            $goods_attr = $get_goods_attr ? explode(',', $get_goods_attr) : [];

            $attr_ajax = $this->presaleService->getGoodsAttrAjax($goods_id, $goods_attr, $attr_id);

            $where = [
                'goods_id' => $goods_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goods = $this->goodsService->getGoodsInfo($where);

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
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $get_goods_attr, $warehouse_id, $area_id, $area_city);
                $attr_number = $products ? $products['product_number'] : 0;
                $product_promote_price = isset($products['product_promote_price']) ? $products['product_promote_price'] : 0;

                /* 判断是否存在货品信息 */
                if ($goods['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
                } elseif ($goods['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);

                    if ($this->config['area_pricetype'] == 1) {
                        $prod = $prod->where('city_id', $area_city);
                    }
                } else {
                    $prod = Products::where('goods_id', $goods_id);
                }

                $prod = $this->baseRepository->getToArrayFirst($prod);

                //贡云商品 获取库存
                if ($goods['cloud_id'] > 0 && isset($products['product_id'])) {
                    $attr_number = $this->jigonManageService->jigonGoodsNumber(['product_id' => $products['product_id']]);
                } else {
                    if ($goods['goods_type'] == 0) {
                        $attr_number = $goods['goods_number'];
                    } else {
                        if (empty($prod)) { //当商品没有属性库存时
                            $attr_number = $goods['goods_number'];
                        }
                    }
                }

                if (empty($prod)) { //当商品没有属性库存时
                    $res['bar_code'] = $goods['bar_code'];
                } else {
                    $res['bar_code'] = $products['bar_code'];
                }

                if ($goods['cloud_id'] > 0) {
                    $attr_number = !empty($attr_number) ? $attr_number : 0;
                } else {
                    $attr_number = 999; //预售商品，不受库存限制
                }

                $res['attr_number'] = $attr_number;
                //ecmoban模板堂 --zhuo end

                $res['show_goods'] = 0;
                if ($goods_attr && $this->config['add_shop_price'] == 0) {
                    if (count($goods_attr) == count($attr_ajax['attr_id'])) {
                        $res['show_goods'] = 1;
                    }
                }

                $shop_price = $this->goodsCommonService->getFinalPrice($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city);
                $res['shop_price'] = price_format($shop_price);

                //属性价格
                $spec_price = $this->goodsCommonService->getFinalPrice($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city, 1, 0, 0, $res['show_goods'], $product_promote_price);

                if ($this->config['add_shop_price'] == 0) {
                    if ($attr_id) {
                        $res['result'] = price_format($spec_price);
                    } else {
                        $res['result'] = price_format($shop_price);
                    }
                } else {
                    $res['result'] = price_format($shop_price);
                }

                $res['spec_price'] = price_format($spec_price);
                $res['original_shop_price'] = $shop_price;
                $res['original_spec_price'] = $spec_price;
                $res['marketPrice_amount'] = price_format($goods['marketPrice'] + $spec_price);

                if ($this->config['add_shop_price'] == 0) {
                    $goods['marketPrice'] = isset($products['product_market_price']) && !empty($products['product_market_price']) ? $products['product_market_price'] : $goods['marketPrice'];
                    $res['result_market'] = price_format($goods['marketPrice']); // * $number
                } else {
                    $res['result_market'] = price_format($goods['marketPrice'] + $spec_price); // * $number
                }
            }

            if ($this->config['open_area_goods'] == 1) {
                $area_count = $this->goodsService->getHasLinkAreaGods($goods_id, $area_id, $area_city);

                if ($area_count < 1) {
                    $res['err_no'] = 2;
                }
            }

            $presale = $this->presaleService->getPresaleTime($goods_id);
            $res['act_id'] = isset($presale['act_id']) ? $presale['act_id'] : 0;
            $res['type'] = $type;
            $res['presale'] = $presale;

            return response()->json($res);
        } elseif ($act == 'in_stock') {
            $res = ['err_msg' => '', 'result' => '', 'qty' => 1];

            $area = $this->areaService->areaCookie();

            $act_id = (int)request()->input('act_id', 0);
            $goods_id = (int)request()->input('id', 0);
            $province = (int)request()->input('province', $area['province'] ?? 0);
            $city = (int)request()->input('city', $area['city'] ?? 0);
            $district = (int)request()->input('district', $area['district'] ?? 0);

            $d_null = (int)request()->input('d_null', 0);
            $user_id = (int)request()->input('user_id', 0);

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
            $time =60 * 24 * 30;
            cookie()->queue('type_province', 0, $time);
            cookie()->queue('type_city', 0, $time);
            cookie()->queue('type_city', 0, $time);

            $res['d_null'] = $d_null;

            if ($d_null == 0) {
                if (in_array($district, $user_address)) {
                    $res['isRegion'] = 1;
                } else {
                    $res['message'] = $GLOBALS['_LANG']['region_message'];
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
            $res['act_id'] = $act_id;

            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 预售 --> 首页
        /*------------------------------------------------------ */
        if ($act == 'index') {

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/presale/');
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            /* 缓存编号 */
            $cache_id = sprintf('%X', crc32(session('user_rank', 0) . '-' . $this->config['lang']));

            if (!$this->smarty->is_cached('presale_index.dwt', $cache_id)) {

                // 调用数据
                $pre_goods = $this->presaleService->getPreCat();
                $this->smarty->assign('pre_cat_goods', $pre_goods);

                $categories_pro = get_category_tree_leve_one();
                $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

                assign_template();
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
                $position = assign_ur_here();
                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                /* 小图 start */
                $presale_banner = "";
                $presale_banner_small = "";
                $presale_banner_small_left = "";
                $presale_banner_small_right = "";
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $presale_banner .= "'presale_banner" . $i . ","; //预售轮播banner
                    $presale_banner_small .= "'presale_banner_small" . $i . ","; //预售小轮播
                    $presale_banner_small_left .= "'presale_banner_small_left" . $i . ","; //预售小轮播 左侧
                    $presale_banner_small_right .= "'presale_banner_small_right" . $i . ","; //预售小轮播 右侧
                }


                $this->smarty->assign('pager', ['act' => 'index']);
                $this->smarty->assign('presale_banner', $presale_banner);
                $this->smarty->assign('presale_banner_small', $presale_banner_small);
                $this->smarty->assign('presale_banner_small_left', $presale_banner_small_left);
                $this->smarty->assign('presale_banner_small_right', $presale_banner_small_right);

                /* 小图 end */
            }

            /* 显示模板 */
            return $this->smarty->display('presale_index.dwt', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 预售 --> 新品发布
        /*------------------------------------------------------ */
        elseif ($act == 'new') {

            // 筛选条件
            $cat_id = (int)request()->input('cat_id', 0);
            // 状态1即将开始，2预约中，3已结束
            $status = (int)request()->input('status', 0);

            $cache_id = sprintf('%X', crc32($cat_id . '-' . $status . '-' . $this->config['lang']));

            if (!$this->smarty->is_cached('presale_new.dwt', $cache_id)) {
                $children = $this->presaleService->getPresaleCatListChildren($cat_id);

                $pager = ['cat_id' => $cat_id, 'act' => 'new', 'status' => $status];
                $this->smarty->assign('pager', $pager);

                $pre_status['status_cat'] = $this->presaleService->getPresaleUrl("new", 0, 0, "新品发布");
                $pre_status['status_all'] = $this->presaleService->getPresaleUrl("new", $cat_id, 0, "新品发布");
                $pre_status['status_one'] = $this->presaleService->getPresaleUrl("new", $cat_id, 1, "新品发布");
                $pre_status['status_two'] = $this->presaleService->getPresaleUrl("new", $cat_id, 2, "新品发布");
                $pre_status['status_three'] = $this->presaleService->getPresaleUrl("new", $cat_id, 3, "新品发布");
                $this->smarty->assign('pre_status', $pre_status);

                //所有分类
                $pre_category = $this->presaleService->getPreCategory('new', $status);
                $this->smarty->assign('pre_category', $pre_category);

                $date_result = $this->presaleService->getNewGoodsList($children, $status);
                $this->smarty->assign('date_result', $date_result);

                assign_template();
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
                $position = assign_ur_here();
                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                /** 小图 start**/
                $presale_banner_new = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $presale_banner_new .= "'presale_banner_new" . $i . ","; //预售轮播banner
                }

                $this->smarty->assign('presale_banner_new', $presale_banner_new);
            }

            /* 显示模板 */
            return $this->smarty->display('presale_new.dwt', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 预售 --> 抢订
        /*------------------------------------------------------ */
        elseif ($act == 'advance') {
            //筛选条件
            $price_min = (int)request()->input('price_min', 0);
            $price_max = (int)request()->input('price_max', 0);

            $start_time = addslashes(trim(request()->input('start_time', '')));
            $end_time = addslashes(trim(request()->input('end_time', '')));

            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'act_id' : ($this->config['sort_order_type'] == '1' ? 'shop_price' : 'start_time');

            $sort = request()->input('sort', '');
            $sort = in_array(trim(strtolower($sort)), ['shop_price', 'start_time', 'act_id']) ? trim($sort) : $default_sort_order_type;
            $order = request()->input('order', '');
            $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;
            $cat_id = (int)request()->input('cat_id', 0);
            // 状态1即将开始，2预约中，3已结束
            $status = (int)request()->input('status', 0);

            $cache_id = sprintf('%X', crc32($cat_id . '-' . $price_min . '-' . $price_max . '-' . $start_time . '-' . $start_time . '-' . $end_time . '-' . $default_sort_order_method . '-' . $default_sort_order_type . '-' . $sort . '-' . $order . '-' . $status . '-' . $this->config['lang']));

            if (!$this->smarty->is_cached('presale_advance.dwt', $cache_id)) {
                $children = $this->presaleService->getPresaleCatListChildren($cat_id);

                // 调用数据
                $goods = $this->presaleService->getPreGoods($children, $price_min, $price_max, $start_time, $end_time, $sort, $status, $order);

                $pre_category = $this->presaleService->getPreCategory("advance", $status);
                $this->smarty->assign('pre_category', $pre_category);

                $pager = ['cat_id' => $cat_id, 'act' => 'advance', 'price_min' => $price_min, 'price_max' => $price_max, 'sort' => $sort, 'order' => $order, 'status' => $status];
                $this->smarty->assign('pager', $pager);
                $this->smarty->assign("goods", $goods);

                assign_template();
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
                $position = assign_ur_here();
                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                /*                 * 小图 start* */
                $presale_banner_advance = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $presale_banner_advance .= "'presale_banner_advance" . $i . ","; //预售轮播banner
                }

                $this->smarty->assign('presale_banner_advance', $presale_banner_advance);
            }

            /* 显示模板 */
            return $this->smarty->display('presale_advance.dwt', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 预售 --> 抢订
        /*------------------------------------------------------ */
        elseif ($act == 'category') {
            //筛选条件
            $price_min = (int)request()->input('price_min', 0);
            $price_max = (int)request()->input('price_max', 0);
            $start_time = addslashes(trim(request()->input('start_time', '')));
            $end_time = addslashes(trim(request()->input('end_time', '')));

            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'act_id' : ($this->config['sort_order_type'] == '1' ? 'shop_price' : 'start_time');

            $sort = request()->input('sort', '');
            $sort = in_array(trim(strtolower($sort)), ['shop_price', 'start_time', 'act_id']) ? trim($sort) : $default_sort_order_type;
            $order = request()->input('order', '');
            $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;

            $cat_id = (int)request()->input('cat_id', 0);
            // 状态1即将开始，2预约中，3已结束
            $status = (int)request()->input('status', 0);

            $cache_id = sprintf('%X', crc32($cat_id . '-' . $price_min . '-' . $price_max . '-' . $start_time . '-' . $start_time . '-' . $end_time . '-' . $default_sort_order_method . '-' . $default_sort_order_type . '-' . $sort . '-' . $order . '-' . $status . '-' . $this->config['lang']));

            if (!$this->smarty->is_cached('presale_category.dwt', $cache_id)) {
                $children = $this->presaleService->getPresaleCatListChildren($cat_id);

                // 调用数据
                $goods = $this->presaleService->getPreGoods($children, $price_min, $price_max, $start_time, $end_time, $sort, $status, $order);

                //所有分类
                $pre_category = $this->presaleService->getPreCategory('category', $status);
                $this->smarty->assign('pre_category', $pre_category);

                $pager = ['cat_id' => $cat_id, 'act' => 'category', 'price_min' => $price_min, 'price_max' => $price_max, 'sort' => $sort, 'order' => $order, 'status' => $status];
                $this->smarty->assign('pager', $pager);
                $this->smarty->assign("goods", $goods);

                assign_template();
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
                $position = assign_ur_here();
                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                /* * 小图 start* */
                $presale_banner_category = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $presale_banner_category .= "'presale_banner_category" . $i . ","; //预售轮播banner
                }

                $this->smarty->assign('presale_banner_category', $presale_banner_category);
            }

            /* 显示模板 */
            return $this->smarty->display('presale_category.dwt', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 预售 --> 商品详情
        /*------------------------------------------------------ */
        elseif ($act == 'view') {
            /* 取得参数：预售活动id */
            $presale_id = (int)request()->input('id', 0);
            if ($presale_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/presale/detail/' . $presale_id);
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            /* 取得预售活动信息 */
            $presale = $this->presaleService->presaleInfo($presale_id, 0, $user_id);

            if (empty($presale)) {
                return show_message($GLOBALS['_LANG']['now_not_snatch']);
            }

            assign_template();

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            /* 缓存id：语言，预售活动id，状态，（如果是进行中）当前数量和是否登录 */
            $cache_id = $this->config['lang'] . '-presale-' . $presale_id . '-' . $presale['status'] . time();
            if ($presale['status'] == GBS_UNDER_WAY) {
                $cache_id = $cache_id . '-' . $presale['valid_goods'];
            }
            $cache_id = sprintf('%X', crc32($cache_id));

            /* 如果没有缓存，生成缓存 */
            if (!$this->smarty->is_cached('presale_goods.dwt', $cache_id)) {

                //ecmoban模板堂 --zhuo start 限购
                $start_date = $presale['xiangou_start_date'];
                $end_date = $presale['xiangou_end_date'];

                $nowTime = gmtime();
                if ($nowTime > $start_date && $nowTime < $end_date) {
                    $xiangou = 1;
                } else {
                    $xiangou = 0;
                }

                $this->smarty->assign('xiangou', $xiangou);
                $this->smarty->assign('orderG_number', $presale['total_goods']); //购买的商品数量
                //ecmoban模板堂 --zhuo end 限购

                $now = gmtime();
                $presale['gmt_end_date'] = local_strtotime($presale['end_time']);
                $presale['gmt_start_date'] = local_strtotime($presale['start_time']);
                if ($presale['gmt_start_date'] >= $now) {
                    $presale['no_start'] = 1;
                }
                if ($presale['gmt_end_date'] <= $now) {
                    $presale['already_over'] = 1;
                }

                $this->smarty->assign('presale', $presale);

                /* 取得预售商品信息 */
                $goods_id = $presale['goods_id'];

                $where = [
                    'goods_id' => $goods_id,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];
                $goods = $this->goodsService->getGoodsInfo($where);

                if (empty($goods)) {
                    return dsc_header("Location: ./\n");
                }

                $this->smarty->assign('goods', $goods);

                $this->smarty->assign('id', $goods_id);
                $this->smarty->assign('type', 0);

                //评分 start
                $comment_all = $this->commentService->getCommentsPercent($goods_id);
                $this->smarty->assign('comment_all', $comment_all);
                if ($goods['user_id'] > 0) {
                    $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goods['user_id']); //商家所有商品评分类型汇总
                    $this->smarty->assign('merch_cmt', $merchants_goods_comment);
                }
                //评分 end

                //ecmoban模板堂 --zhuo start
                $shop_info = get_merchants_shop_info($goods['user_id']);
                $shop_info = $shop_info ? $shop_info : [];

                $shop_info['license_comp_adress'] = $shop_info ? $shop_info['license_comp_adress'] : '';
                $adress = get_license_comp_adress($shop_info['license_comp_adress']);

                $this->smarty->assign('shop_info', $shop_info);
                $this->smarty->assign('adress', $adress);

                $this->smarty->assign('goods_id', $goods_id); //商品ID

                $warehouse_list = get_warehouse_list_goods();
                $this->smarty->assign('warehouse_list', $warehouse_list); //仓库列

                $warehouse_name = get_warehouse_name_id($warehouse_id);

                $this->smarty->assign('warehouse_name', $warehouse_name); //仓库名称
                $this->smarty->assign('region_id', $warehouse_id); //商品仓库region_id

                $this->smarty->assign('user_id', $user_id);

                $this->smarty->assign('shop_price_type', $goods['model_price']); //商品价格运营模式 0代表统一价格（默认） 1、代表仓库价格 2、代表地区价格
                $this->smarty->assign('area_id', $area_id); //地区ID
                $this->smarty->assign('area_city', $area_city); //市级地区ID
                //ecmoban模板堂 --zhuo start 仓库

                //预约人数
                $pre_num = $this->presaleService->getPreNum($goods_id);
                $this->smarty->assign('pre_num', $pre_num);

                /* 取得商品的规格 */
                $properties = $this->goodsAttrService->getGoodsProperties($goods_id, $warehouse_id, $area_id, $area_city);
                $this->smarty->assign('properties', $properties['pro']);    //商品属性
                $this->smarty->assign('specification', $properties['spe']); // 商品规格

                $this->smarty->assign('area_htmlType', 'presale');

                $province_row = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $this->province_id)->first();
                $province_row = $province_row ? $province_row->toArray() : [];
                $city_row = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $this->city_id)->first();
                $city_row = $city_row ? $city_row->toArray() : [];
                $district_row = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $this->district_id)->first();
                $district_row = $district_row ? $district_row->toArray() : [];

                $this->smarty->assign('province_row', $province_row);
                $this->smarty->assign('city_row', $city_row);
                $this->smarty->assign('district_row', $district_row);

                //模板赋值
                $this->smarty->assign('cfg', $GLOBALS['_CFG']);
                $position = assign_ur_here($presale['pa_catid'], $presale['goods_name'], [], '', $presale['user_id']);

                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

                /**
                 * Start
                 *
                 * 商品推荐
                 * 【'best' ：精品, 'new' ：新品, 'hot'：热销】
                 */

                $where = [
                    'seller_id' => $goods['user_id'],
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city,
                    'rec_type' => 1,
                    'presale' => 'presale'
                ];

                /* 最新商品 */
                $where['type'] = 'new';
                $new_goods = $this->goodsService->getRecommendGoods($where);

                /* 推荐商品 */
                $where['type'] = 'best';
                $best_goods = $this->goodsService->getRecommendGoods($where);

                /* 热卖商品 */
                $where['type'] = 'hot';
                $hot_goods = $this->goodsService->getRecommendGoods($where);

                $this->smarty->assign('new_goods', $new_goods);
                $this->smarty->assign('best_goods', $best_goods);
                $this->smarty->assign('hot_goods', $hot_goods);
                /* End */

                $this->smarty->assign('pictures', $this->goodsGalleryService->getGoodsGallery($goods_id)); // 商品相册

                $all_count = get_discuss_type_count($goods_id); //帖子总数
                $this->smarty->assign('all_count', $all_count);

                //相关分类
                $goods_related_cat = $this->presaleService->getGoodsRelatedCat($presale['pa_catid']);
                $this->smarty->assign('goods_related_cat', $goods_related_cat);
            }

            //关联商品
            $linked_goods = $this->goodsService->getLinkedGoods($goods_id, $warehouse_id, $area_id, $area_city);
            $this->smarty->assign('related_goods', $linked_goods);

            //　详情部分 评分 start
            $comment_all = $this->commentService->getCommentsPercent($goods_id);
            $this->smarty->assign('comment_all', $comment_all);

            /**
             * 店铺分类
             */
            if ($goods['user_id']) {
                $goods_store_cat = $this->categoryService->getChildTreePro(0, 0, 'merchants_category', 0, $goods['user_id']);

                if ($goods_store_cat) {
                    $goods_store_cat = array_values($goods_store_cat);
                }

                $this->smarty->assign('goods_store_cat', $goods_store_cat);
            }

            $discuss_list = get_discuss_all_list($goods_id, 0, 1, 10);
            $this->smarty->assign('discuss_list', $discuss_list);

            //更新商品点击次数
            Goods::where('goods_id', $presale['goods_id'])->increment('click_count', 1);

            $this->smarty->assign('act_id', $presale_id);
            $this->smarty->assign('now_time', gmtime());           // 当前系统时间

            $this->smarty->assign('area_htmlType', 'presale');

            $basic_info = get_shop_info_content($goods['user_id']);

            if ($basic_info) {
                $basic_info['province'] = Region::where('region_id', $basic_info['province'])->value('region_name');
                $basic_info['city'] = Region::where('region_id', $basic_info['city'])->value('region_name');
            }

            /*  @author-bylu 判断当前商家是否允许"在线客服" start */
            $shop_information = $this->merchantCommonService->getShopName($goods['user_id']);//通过ru_id获取到店铺信息;

            //判断当前商家是平台,还是入驻商家 bylu
            if ($presale['user_id'] == 0) {

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

            $this->smarty->assign('basic_info', $basic_info);

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
                'merchant_id' => $goods['user_id'],
            ];

            $this->smarty->assign('area', $area);

            return $this->smarty->display('presale_goods.dwt', $cache_id);
        }

        /*------------------------------------------------------ */
        //-- 预售商品 --> 购买
        /*------------------------------------------------------ */
        elseif ($act == 'buy') {

            /* 查询：取得规格 */
            $goods_attr = addslashes(request()->input('goods_attr_id', ''));

            /* 查询：判断是否登录 */
            if ($user_id <= 0) {
                return show_message($GLOBALS['_LANG']['gb_error_login'], '', '', 'error');
            }

            /* 查询：取得参数：预售活动id */
            $presale_id = (int)request()->input('presale_id', 0);
            if ($presale_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 查询：取得数量 */
            $number = (int)request()->input('number', 1);
            $number = $number < 1 ? 1 : $number;

            /* 查询：取得预售活动信息 */
            $presale = $this->presaleService->presaleInfo($presale_id, $number, $user_id);

            if (empty($presale)) {
                return dsc_header("Location: ./\n");
            }

            /* 查询：检查预售活动是否是进行中 */
            if ($presale['status'] != GBS_UNDER_WAY) {
                return show_message($GLOBALS['_LANG']['presale_error_status'], '', '', 'error');
            }

            /* 查询：取得规格 */
            $specs = htmlspecialchars(trim(request()->input('goods_spec', '')));

            /* 查询：取得预售商品信息 */
            $where = [
                'goods_id' => $presale['goods_id'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goodsInfo = $this->goodsService->getGoodsInfo($where);

            if (empty($goodsInfo)) {
                return dsc_header("Location: ./\n");
            }

            $prod = [];
            $products = [];
            if ($goods_attr) {
                if ($goodsInfo['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('warehouse_id', $warehouse_id);
                } elseif ($goodsInfo['model_attr'] == 2) {
                    $prod = ProductsArea::where('area_id', $area_id);

                    if ($this->config['area_pricetype'] == 1) {
                        $prod = $prod->where('city_id', $area_city);
                    }
                } else {
                    $prod = Products::whereRaw(1);
                }

                $prod = $prod->where('goods_id', $goodsInfo['goods_id']);
                $prod = $this->baseRepository->getToArrayFirst($prod);

                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goodsInfo['goods_id'], $goods_attr, $warehouse_id, $area_id, $area_city);
                $product_number = isset($products['product_number']) ? $products['product_number'] : 0;
            }

            if ($prod) {
                $goods_number = $product_number;
            } else {
                $goods_number = $goodsInfo['goods_number'];
            }

            if ($goods_attr && $goodsInfo['cloud_id']) {
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

            if ($goods_number <= 0) {
                return show_message($GLOBALS['_LANG']['buy_error'], '', '', 'error');
            }

            /* 查询：如果商品有规格则取规格商品信息 配件除外 */
            if ($specs) {
                $_specs = explode(',', $specs);
                $product_info = $this->goodsAttrService->getProductsInfo($goodsInfo['goods_id'], $_specs, $warehouse_id, $area_id, $area_city);
            }

            empty($product_info) ? $product_info = ['product_number' => 0, 'product_id' => 0] : '';

            /* 查询：查询规格名称和值，不考虑价格 */
            $goods_attr = $this->goodsService->getGoodsAttrList($specs);

            /* 更新：清空购物车中所有预售商品 */
            $this->cartCommonService->clearCart($user_id, CART_PRESALE_GOODS);

            if (!empty($user_id)) {
                $sess = "";
            } else {
                $sess = $this->sessionRepository->realCartMacIp();
            }

            //ecmoban模板堂 --zhuo start 限购
            $nowTime = gmtime();
            $start_date = $goodsInfo['xiangou_start_date'];
            $end_date = $goodsInfo['xiangou_end_date'];

            if ($goodsInfo['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
                if ($presale['total_goods'] >= $goodsInfo['xiangou_num']) {
                    $message = $presale['goods_name'] . " 商品您已购买达到上限";
                    return show_message($message, $GLOBALS['_LANG']['back_to_presale'], 'presale.php?id=' . $presale['act_id'] . '&act=view');
                } else {
                    if ($goodsInfo['xiangou_num'] > 0) {
                        if ($goodsInfo['is_xiangou'] == 1 && $presale['total_goods'] + $number > $goodsInfo['xiangou_num']) {
                            //可购买数量
                            $number = $goodsInfo['xiangou_num'] - $presale['total_goods'];
                        }
                    }
                }
            }
            //ecmoban模板堂 --zhuo end 限购

            /* 查询：处理规格属性 */
            if ($goods_attr) {
                $goods_attr_id = $goods_attr;
                $attr_list = [];
                $res = GoodsAttr::select('attr_id', 'attr_value');
                $res = $res->with([
                    'getGoodsAttribute' => function ($query) use ($goods_attr_id) {
                        $query->select('attr_id', 'attr_name')
                            ->whereIn('goods_attr_id', $goods_attr_id);
                    }
                ]);

                $res = $res->orderBy('goods_attr_id')->get();
                $res = $res ? $res->toArray() : [];

                if ($res) {
                    foreach ($res as $v) {
                        $attr_list[] = $v['get_goods_attribute']['attr_name'] . ':' . $v['attr_value'];
                    }
                }
                $goods_attr = join(chr(13) . chr(10), $attr_list);
            } else {
                $goods_attr = '';
                $goods_attr_id = '';
            }

            /* 更新：加入购物车 */
            $cart = [
                'user_id' => $user_id,
                'session_id' => $sess,
                'goods_id' => $presale['goods_id'],
                'product_id' => $product_info['product_id'],
                'goods_sn' => addslashes($goodsInfo['goods_sn']),
                'goods_name' => addslashes($goodsInfo['goods_name']),
                'market_price' => $goodsInfo['market_price'],
                'goods_price' => $this->goodsCommonService->getFinalPrice($presale['goods_id']),
                'goods_number' => $number,
                'goods_attr' => addslashes($goods_attr),
                'goods_attr_id' => $goods_attr_id,
                //ecmoban模板堂 --zhuo start
                'ru_id' => $goodsInfo['user_id'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                //ecmoban模板堂 --zhuo end
                'is_real' => $goodsInfo['is_real'],
                'extension_code' => 'presale',
                'parent_id' => 0,
                'rec_type' => CART_PRESALE_GOODS,
                'is_gift' => 0
            ];

            $rec_id = Cart::insertGetId($cart);

            $this->cartRepository->pushCartValue($rec_id);

            /* 更新：记录购物流程类型：预售 */
            session([
                'flow_type' => CART_PRESALE_GOODS,
                'extension_code' => 'presale',
                'extension_id' => $presale['act_id'],
                'browse_trace' => "presale" /* 进入收货人页面 */
            ]);

            return dsc_header("Location: ./flow.php?step=checkout\n");
        }
    }
}
