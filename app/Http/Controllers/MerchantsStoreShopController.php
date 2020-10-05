<?php

namespace App\Http\Controllers;

use App\Models\CollectStore;
use App\Models\MerchantsShopInformation;
use App\Models\SellerShopinfo;
use App\Services\Article\ArticleCommonService;
use App\Services\Comment\CommentService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreStreetService;

/**
 * 首页文件
 */
class MerchantsStoreShopController extends InitController
{
    protected $StoreStreetService;
    protected $merchantCommonService;
    protected $commentService;
    protected $config;
    protected $articleCommonService;

    public function __construct(
        StoreStreetService $StoreStreetService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        ArticleCommonService $articleCommonService
    )
    {
        $this->StoreStreetService = $StoreStreetService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
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

        $brand = (int)request()->input('brand', 0);
        $price_max = (int)request()->input('price_max', 0);
        $price_min = (int)request()->input('price_min', 0);
        $filter_attr_str = htmlspecialchars(trim(request()->input('filter_attr', 0)));
        $filter_attr_str = trim(urldecode($filter_attr_str));
        $filter_attr_str = preg_match('/^[\d\.]+$/', $filter_attr_str) ? $filter_attr_str : '';

        $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'goods_id' : ($this->config['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

        $sort = request()->input('sort', '');
        $sort = in_array(trim(strtolower($sort)), ['goods_id', 'shop_price', 'last_update']) ? trim($sort) : $default_sort_order_type;
        $order = request()->input('order', '');
        $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;
        $page = (int)request()->input('page', 1);
        $size = 24;

        $merchant_id = (int)request()->input('id', 1);
        $user_id = session('user_id', 0);

        //商家不存则跳转回首页
        $shop_id = MerchantsShopInformation::where('user_id', $merchant_id)->value('shop_id');
        if ($merchant_id == 0 || $shop_id < 1) {
            header("Location: index.php\n");
        }

        /*------------------------------------------------------ */
        //-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内        容
        /*------------------------------------------------------ */
        /* 缓存编号 */
        $cache_id = sprintf('%X', crc32(session('user_rank') . '-' . $sort . '-' . $order . '-' . $page . '-' . $size . '-' . $merchant_id . '-' . $this->config['lang']));
        $shop_info = $this->merchantCommonService->getShopName($merchant_id, 3); //店铺名称
        if (!$this->smarty->is_cached('merchants_shop.dwt', $cache_id)) {
            assign_template('', [], $merchant_id);

            $position = assign_ur_here(0, $shop_info['shop_name']);
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $goods_list = $this->StoreStreetService->getShopGoodsCmtList($merchant_id, $warehouse_id, $area_id, $area_city, $price_min, $price_max, $page, $size, $sort, $order);
            $this->smarty->assign('goods_list', $goods_list);

            $count = $this->StoreStreetService->getShopGoodsCmtCount($merchant_id, $area_id, $area_city, $price_min, $price_max, $sort);
            $this->smarty->assign('count', $count);

            if ($merchant_id > 0) {
                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($merchant_id); //商家所有商品评分类型汇总
            }

            $this->smarty->assign('merch_cmt', $merchants_goods_comment);

            $build_uri = [
                'urid' => $merchant_id,
                'append' => $shop_info['shop_name']
            ];

            $domain_url = $this->merchantCommonService->getSellerDomainUrl($merchant_id, $build_uri);
            $shop_info['store_url'] = $domain_url['domain_name'];
            $this->smarty->assign('shop_info', $shop_info);

            $brand_list = get_shop_brand_list($merchant_id); //商家品牌
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
            $address = get_shop_address_info($merchant_id); //商家所在位置

            $this->smarty->assign('brand_list', $brand_list);  // 店铺信息
            $this->smarty->assign('address', $address);  // 店铺信息

            $basic_info = SellerShopinfo::where('ru_id', $merchant_id)->first();
            $basic_info = $basic_info ? $basic_info->toArray() : [];

            $basic_info['logo_thumb'] = $basic_info ? get_image_path(str_replace('../', '', $basic_info['logo_thumb'])) : '';

            $this->smarty->assign('basic_info', $basic_info);  //店铺详细信息

            $this->smarty->assign('merchant_id', $merchant_id);
            $this->smarty->assign('script_name', 'merchants_store_shop');

            $store_best_list = $this->StoreStreetService->getShopGoodsCountList(0, $warehouse_id, $area_id, $area_city, 1, 'store_best', 0, 7);
            $this->smarty->assign('store_best_list', $store_best_list);

            if ($user_id > 0) {
                $collect_store = CollectStore::where('user_id', $user_id)->where('ru_id', $merchant_id)->value('rec_id');
                $this->smarty->assign('collect_store', $collect_store);
            }

            assign_pager('merchants_store_shop', 0, $count, $size, $sort, $order, $page, '', $brand, $price_min, $price_max, 'list', $filter_attr_str, '', '', $merchant_id); // 分页

            /* 页面中的动态内容 */
            assign_dynamic('merchants_store_shop');
        }

        return $this->smarty->display('merchants_shop.dwt', $cache_id);
    }
}
