<?php

namespace App\Http\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Brand\BrandGoodsService;
use App\Services\Brand\BrandService;

/**
 * 品牌列表
 */
class BrandController extends InitController
{
    protected $brandService;
    protected $dscRepository;
    protected $config;
    protected $articleCommonService;
    protected $brandGoodsService;

    public function __construct(
        BrandService $brandService,
        DscRepository $dscRepository,
        ArticleCommonService $articleCommonService,
        BrandGoodsService $brandGoodsService
    )
    {
        $this->brandService = $brandService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->brandGoodsService = $brandGoodsService;
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

        $this->smarty->assign('open_area_goods', $this->config['open_area_goods']);

        /* 获得请求的平台品牌ID */
        $brand_id = intval(request()->input('id', 0));

        if (!request()->exists('id')) {
            $brand_id = intval(request()->input('brand', 0));
        }

        if ($brand_id) {
            $Loaction = dsc_url('/#/brandDetail/' . $brand_id);
        } else {
            $Loaction = dsc_url('/#/brand');
        }

        /* 跳转H5 start */
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        /* 获得请求的商家品牌ID */
        $mbid = intval(request()->input('mbid', 0));
        $act = addslashes(trim(request()->input('act', 'default')));
        $act = $act ? $act : 'default';

        if (empty($brand_id)) {

            if ($act == 'default') {
                $cat_id = intval(request()->input('cat_id', 0));

                $brand_list = $this->brandService->getBrands($cat_id, 'brand', 0, 1);

                $this->smarty->assign('brand_list', $brand_list);

                $brand_index_ad = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $brand_index_ad .= "'brand_index_ad" . $i . ",";
                }
                $this->smarty->assign('brand_index_ad', $brand_index_ad);
            } elseif ($act == 'filter_category') {
                $result = ['error' => 0, 'content' => ''];

                $cat_id = intval(request()->input('cat_id', 0));

                $brand_list = $this->brandService->getBrands($cat_id, 'brand', 0, 1);
                $this->smarty->assign('brand_list', $brand_list);
                $result['content'] = $this->smarty->fetch('library/brand_list.lbi');
                return response()->json($result);
            } elseif ($act == 'load_more_brand') {
                $result = ['error' => 0, 'content' => ''];

                $cat_id = intval(request()->input('cat_id', 0));

                $have_num = intval(request()->input('have_num', 0));
                $load_num = intval(request()->input('load_num', 8));

                $page = ceil($have_num / $load_num) + 1;

                $brand_list = $this->brandService->getBrands($cat_id, 'brand', 0, $page);
                $this->smarty->assign('brand_list', $brand_list);
                $result['content'] = $this->smarty->fetch('library/brand_list.lbi');
                return response()->json($result);
            }
            $step = request()->input('step', '');
            $cat_key = intval(request()->input('cat_key', 0));

            if ($step == 'load_brands' && !empty($cat_key)) {
                $result = ['error' => 0, 'content' => '']; // zhangyh_100322

                $rome_key = intval(request()->input('rome_key', 0));
                $rome_key = intval($rome_key) + 1;

                $brand_cat = read_static_cache('cat_brand_cache');

                if (!empty($brand_cat) && is_array($brand_cat)) {
                    foreach ($brand_cat[$cat_key]['cat_id'] as $k => $v) {
                        $brands = $this->brandService->getBrands($v['id']);
                        if ($brands) {
                            $brand_list[$k] = $brands;
                        } else {
                            unset($brand_cat[$cat_key]['cat_id'][$k]);
                        }
                    }
                    $this->smarty->assign('one_brand_cat', $brand_cat[$cat_key]);
                    $this->smarty->assign('cat_key', $cat_key);
                    $this->smarty->assign('brand_list', $brand_list);

                    if (count($brand_cat[$cat_key]['cat_id']) > 0) {
                        /*                 * 小图 start* */
                        $brand_cat_ad = '';
                        for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                            $brand_cat_ad .= "'brand_cat_ad" . $i . ","; //首页楼层轮播图
                        }
                        //用于页面的罗马数字效果，罗马数字数组
                        $rome_number = [1 => 'Ⅰ', 2 => 'Ⅱ', 3 => 'Ⅲ', 4 => 'Ⅳ', 5 => 'Ⅴ', 6 => 'Ⅵ', 7 => 'Ⅶ', 8 => 'Ⅷ', 9 => 'Ⅸ', 10 => 'Ⅹ', 11 => 'Ⅺ', 12 => 'Ⅻ', 13 => 'XIII', 14 => 'XIV', 15 => 'XV', 16 => 'XVI', 17 => 'XVII', 18 => 'XVIII', 19 => 'XIX', 20 => 'XX'];
                        $this->smarty->assign('rome_number', $rome_number[$rome_key]);
                        $arr = ["ad_arr" => $brand_cat_ad, "id" => $cat_key];
                        $brand_cat_ad = insert_get_adv_child($arr);
                        $this->smarty->assign('brand_cat_ad', $brand_cat_ad);
                        $result['content'] = html_entity_decode($this->smarty->fetch('library/load_brands.lbi'));
                    }
                }

                return response()->json($result);
            }

            /* 缓存编号 */

                assign_template();
                $position = assign_ur_here('', $GLOBALS['_LANG']['all_brand']);
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

                //获取seo start
                $seo = get_seo_words('brand_list');

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

                return $this->smarty->display('brand.dwt');

        }

        /* 初始化分页信息 */
        $page = intval(request()->input('page', 1));

        $size = !empty($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10;
        $cate = intval(request()->input('cat', 0));

        if (!request()->exists('cat')) {
            $cate = intval(request()->input('category', 0));
        }

        $price_min = floatval(request()->input('price_min'));
        $price_max = floatval(request()->input('price_max'));

        /* 排序、显示方式以及类型 */
        $default_display_type = $this->config['show_order_type'] == '0' ? 'list' : ($this->config['show_order_type'] == '1' ? 'grid' : 'text');
        $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'goods_id' : ($this->config['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

        $sort = $default_sort_order_type;
        if (request()->exists('sort')) {
            $get_sort = request()->input('sort');
            if (in_array(trim(strtolower($get_sort)), ['goods_id', 'shop_price', 'last_update', 'sales_volume', 'comments_number'])) {
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
        $ecsCookie = request()->cookie('ECS');

        $display = isset($ecsCookie['display']) ? $ecsCookie['display'] : $default_display_type;
        if (request()->exists('display')) {
            $get_display = request()->input('display');
            if (in_array(trim(strtolower($get_display)), ['list', 'grid', 'text'])) {
                $display = $get_display;
            }
        }
        $display = in_array($display, ['list', 'grid', 'text']) ? $display : 'text';

        cookie()->queue('ECS[display]', $display, 60 * 24 * 7);

        $goods_num = intval(request()->input('goods_num', 0));
        $best_num = intval(request()->input('best_num', 0));

        if ($act == 'load_more_goods') {
            $goods_floor = floor($goods_num / 4 * 8 / 5 - $best_num);

            if ($goods_floor < 0) {
                $best_size = $best_num;
            } else {
                $best_size = $goods_floor + 2;
            }
        } else {
            $best_num = 0;
            $best_size = 6;
        }

        $ship = intval(request()->input('ship', 0));
        $self = intval(request()->input('self', 0));
        $have = intval(request()->input('have', 0));

        $this->smarty->assign('price_min', $price_min);
        $this->smarty->assign('price_max', $price_max);

        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        /* 页面的缓存ID */
        $cache_id = sprintf('%X', crc32($mbid . '-' . $brand_id . '-' . $cate . '-' . $price_min . '-' . $price_max . '-' . $best_size . '-' . $best_num . '-' .
            $warehouse_id . '-' . $area_id . '-' . $area_city . '-' . $goods_num . '-' . $size . '-' . $page . '-' . $sort . '-' . $order . '-' . $act . '-' . $ship . '-' . $have . '-' . $self . '-' . $display . '-' . session('user_rank') . '-' . $this->config['lang']));

        $where_ext = [
            'self' => $self,
            'have' => $have,
            'ship' => $ship
        ];

        /* 缓存编号 */
        $content = cache()->remember('brand_list.dwt.' . $cache_id, $this->config['cache_time'], function () use ($mbid, $brand_id, $cate, $where_ext, $price_min, $price_max, $best_size, $best_num, $warehouse_id, $area_id, $area_city, $goods_num, $size, $page, $sort, $order, $act, $ship, $self, $display) {

            if ($mbid) {
                $mact = 'merchants_brands';
                $brand_info = $this->brandService->getBrandInfo($mbid, $mact);
            } else {
                $brand_info = $this->brandService->getBrandInfo($brand_id);
            }

            if (empty($brand_info)) {
                return dsc_header("Location: ./\n");
            }

            $this->smarty->assign('data_dir', DATA_DIR);

            $brand_info['brand_desc'] = $brand_info['brand_desc'] ?? '';
            $this->smarty->assign('keywords', htmlspecialchars($brand_info['brand_desc']));
            $this->smarty->assign('description', htmlspecialchars($brand_info['brand_desc']));

            /* 赋值固定内容 */
            assign_template();

            $position = assign_ur_here($cate, $brand_info['brand_name']);

            $this->smarty->assign('ur_here', $position['ur_here']); // 当前位置
            $this->smarty->assign('brand_id', $brand_id);
            $this->smarty->assign('mbid', $mbid);
            $this->smarty->assign('category', $cate);

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());              // 网店帮助
            $this->smarty->assign('show_marketprice', $this->config['show_marketprice']);
            $this->smarty->assign('brand_cat_list', $this->brandService->getBrandRelatedCat($brand_id)); // 相关分类
            $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-b$brand_id.xml" : 'feed.php?brand=' . $brand_id);

            /* 调查 */
            $vote = get_vote();
            if (!empty($vote)) {
                $this->smarty->assign('vote_id', $vote['id']);
                $this->smarty->assign('vote', $vote['content']);
            }

            $where = [
                'brand_id' => $brand_id,
                'cats' => $cate,
                'where_ext' => $where_ext,
                'min' => $price_min,
                'max' => $price_max,
                'size' => $best_size,
                'start' => $best_num,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];

            $where['type'] = 'best';
            $best_goods = $this->brandGoodsService->getBrandRecommendGoods($where);
            $this->smarty->assign('best_goods', $best_goods);

            $where['type'] = 'hot';
            $hot_goods = $this->brandGoodsService->getBrandRecommendGoods($where);
            $this->smarty->assign('hot_goods', $hot_goods);

            $this->smarty->assign('brand', $brand_info);

            $count = $this->brandGoodsService->getGoodsCountByBrand($brand_id, $cate, $price_min, $price_max, $where_ext, $warehouse_id, $area_id, $area_city);
            $goodslist = $this->brandGoodsService->getBrandGoodsList($brand_id, $cate, $price_min, $price_max, $where_ext, $warehouse_id, $area_id, $area_city, $goods_num, $size, $page, $sort, $order);

            if ($display == 'grid') {
                if (count($goodslist) % 2 != 0) {
                    $goodslist[] = [];
                }
            }

            $this->smarty->assign('goods_list', $goodslist);
            $this->smarty->assign('script_name', 'brand');

            /**小图 start by wang头部广告**/
            $brand_list_left_ad = '';
            $brand_list_right_ad = '';
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $brand_list_left_ad .= "'brand_list_left_ad" . $i . ","; //品牌商品页面头部左侧广告
                $brand_list_right_ad .= "'brand_list_right_ad" . $i . ","; //品牌商品页面头部右侧广告
            }

            $this->smarty->assign('brand_list_left_ad', $brand_list_left_ad);
            $this->smarty->assign('brand_list_right_ad', $brand_list_right_ad);

            $this->smarty->assign('region_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            //获取seo start
            $seo = get_seo_words('brand_list');

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

            assign_pager('brand', $cate, $count, $size, $sort, $order, $page, '', $brand_id, $price_min, $price_max, $display, '', '', '', 0, '', '', $act, $ship, $self, $mbid); // 分页
            assign_dynamic('brand'); // 动态内容

            return $this->smarty->display('brand_list.dwt');
        });

        return $content;
    }
}
