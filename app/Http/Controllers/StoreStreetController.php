<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Store\StoreStreetService;

/**
 * 首页文件
 */
class StoreStreetController extends InitController
{
    protected $storeStreetService;
    protected $dscRepository;
    protected $config;
    protected $articleCommonService;

    public function __construct(
        StoreStreetService $storeStreetService,
        DscRepository $dscRepository,
        ArticleCommonService $articleCommonService
    )
    {
        $this->storeStreetService = $storeStreetService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {
        /* 跳转H5 start */
        $Loaction = 'mobile/#/shop';
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

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

        $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = "shop_id";
        $sort = request()->input('sort', '');
        $sort = in_array(trim(strtolower($sort)), ['shop_id', 'store_score', 'sales_volume']) ? trim($sort) : $default_sort_order_type;
        $order = request()->input('order', '');
        $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;

        $page = (int)request()->input('page', 1);
        $size = 16;

        //分类树
        if (request()->exists('act')) {
            $act = addslashes(request()->input('act', ''));
            if ($act == 'search_city' || $act == 'search_district') {
                $area = strip_tags(urldecode(request()->input('area')));
                $area = json_str_iconv($area);

                $result = ['error' => 0, 'message' => '', 'content' => ''];

                if (empty($area)) {
                    $result['error'] = 1;
                    return response()->json($result);
                }

                $area = dsc_decode($area);

                if ($act == 'search_city') {
                    $result['region_type'] = 2;
                } else {
                    $result['region_type'] = 3;
                }

                $this->smarty->assign('region_type', $result['region_type']);

                if ($area->region_type == 1) {
                    $result['store_province'] = $area->region_id;
                } elseif ($area->region_type == 2) {
                    $region = Region::where('region_id', $area->region_id)->first();
                    $region = $region ? $region->toArray() : [];

                    $store_province = Region::where('region_id', $region['parent_id'])->first();
                    $store_province = $store_province ? $store_province->toArray() : [];

                    $result['store_province'] = $store_province['region_id'];
                    $result['store_city'] = $area->region_id;
                } elseif ($area->region_type == 3) {
                    $region = Region::where('region_id', $area->region_id)->first();
                    $region = $region ? $region->toArray() : [];

                    $store_city = Region::where('region_id', $region['parent_id'])->first();
                    $store_city = $store_city ? $store_city->toArray() : [];

                    $result['store_city'] = $store_city['region_id'];
                    $result['store_district'] = $area->region_id;

                    $result['store_province'] = Region::where('region_id', $store_city['parent_id'])->value('region_id');
                }

                $region_list = get_current_region_list($area->region_id, $result['region_type']);
                $this->smarty->assign('region_list', $region_list);

                if (!$region_list) {
                    $result['error'] = 2;
                }

                $result['store_user'] = $area->store_user; //商家

                $id = '';
                if ($result['store_province']) {
                    $id .= "store_province-" . $result['store_province'] . "|";
                }

                if (isset($result['store_city'])) {
                    $id .= "store_city-" . $result['store_city'] . "|";
                }

                if (isset($result['store_district'])) {
                    $id .= "store_district-" . $result['store_district'] . "|";
                }

                if ($result['store_user']) {
                    $id .= "store_user-" . $result['store_user'];
                }

                $substr = substr($id, -1);
                if ($substr == "|") {
                    $id = substr($id, 0, -1);
                }

                $result['id'] = $id;

                $result['content'] = $this->smarty->fetch("library/street_region_list.lbi");

                return response()->json($result);
            } elseif ($act == 'search_cat') {
                $area = strip_tags(urldecode(request()->input('area')));
                $area = json_str_iconv($area);

                $result = ['error' => 0, 'message' => '', 'content' => ''];

                $area = dsc_decode($area);

                $cat_id = $area->region_id;
                $region_type = $area->region_type;

                $store_province = $area->store_province;
                $store_city = $area->store_city;
                $store_district = $area->store_district;

                if (empty($area)) {
                    $result['error'] = 1;
                    return response()->json($result);
                }

                $store_user = $this->storeStreetService->getCatStoreList($cat_id);
                if ($store_user) {
                    $su_id = "store_user-" . $store_user;
                } else {
                    $su_id = '';
                }

                $id = '';
                if ($store_province) {
                    $id .= "store_province-" . $store_province . "|";
                }

                if ($store_city) {
                    $id .= "store_city-" . $store_city . "|";
                }

                if ($store_district) {
                    $id .= "store_district-" . $store_district . "|";
                }

                if ($store_province || $store_city || $store_district) {
                    $id .= $su_id;
                } else {
                    $id = $su_id;
                }

                $result['id'] = $id;

                $result['store_user'] = $store_user;
                $result['store_province'] = $store_province;
                $result['store_city'] = $store_city;
                $result['store_district'] = $store_district;

                return response()->json($result);
            }
        }

        $this->smarty->assign('category', 9999999999999999999);

        /*------------------------------------------------------ */
        //-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内        容
        /*------------------------------------------------------ */

        /* 缓存编号 */
        $cache_id = sprintf('%X', crc32($area_city . '-' . $area_id . '-' . $warehouse_id . '-' . $order . '-' . $sort . '-' . $page . '-' . $size . '-' . session('user_rank', 0) . '-' . $this->config['lang']));
        $content = cache()->remember('store_street.dwt.' . $cache_id, $this->config['cache_time'], function () use ($area_city, $area_id, $warehouse_id, $order, $sort, $page, $size) {
            assign_template();

            $position = assign_ur_here(0, $GLOBALS['_LANG']['store_street']);
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
            $this->smarty->assign('user_id', session('user_id'));
            $this->smarty->assign('page_title', $position['title']);    // 页面标题

            /* meta information */
            $this->smarty->assign('keywords', htmlspecialchars($this->config['shop_keywords']));
            $this->smarty->assign('description', htmlspecialchars($this->config['shop_desc']));
            $this->smarty->assign('flash_theme', $this->config['flash_theme']);  // Flash轮播图片模板
            $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $keywords = htmlspecialchars(stripcslashes(request()->input('keywords', '')));

            $count = $this->storeStreetService->getStoreShopCount($keywords);

            $store_shop_list = $this->storeStreetService->getStoreShopList(0, $keywords, $count, $size, $page, $sort, $order, $warehouse_id, $area_id, $area_city, 0, 0, 0, '', 1);

            $shop_list = $store_shop_list['shop_list'];
            $this->smarty->assign('store_shop_list', $shop_list);
            $this->smarty->assign('pager', $store_shop_list['pager']);

            $this->smarty->assign('count', $count);
            $this->smarty->assign('size', $size);

            $province_list = get_current_region_list();
            $this->smarty->assign('province_list', $province_list);

            $store_best_list = $this->storeStreetService->getShopGoodsCountList(0, $warehouse_id, $area_id, $area_city, 1, 'store_best');
            $this->smarty->assign('store_best_list', $store_best_list);

            /* 广告位 */
            $store_street_ad = '';
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $store_street_ad .= "'store_street_ad" . $i . ","; //轮播图
            }
            $this->smarty->assign('store_street_ad', $store_street_ad);

            /* 页面中的动态内容 */
            assign_dynamic('store_street');

            return $this->smarty->display('store_street.dwt');
        });

        return $content;
    }
}
