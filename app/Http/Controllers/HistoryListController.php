<?php

namespace App\Http\Controllers;

use App\Services\Article\ArticleCommonService;
use App\Services\Category\CategoryGoodsService;

/**
 * 浏览列表插件
 */
class HistoryListController extends InitController
{
    protected $categoryGoodsService;
    protected $articleCommonService;

    public function __construct(
        CategoryGoodsService $categoryGoodsService,
        ArticleCommonService $articleCommonService
    )
    {
        $this->categoryGoodsService = $categoryGoodsService;
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {

        /* 初始化分页信息 */
        $page = (int)request()->input('page', 1);
        $size = isset($GLOBALS['_CFG']['page_size']) && intval($GLOBALS['_CFG']['page_size']) > 0 ? intval($GLOBALS['_CFG']['page_size']) : 10;

        $ship = (int)request()->input('ship', 0);
        //by wang
        $self = (int)request()->input('self', 0);

        /* 排序、显示方式以及类型 */
        $default_sort_order_method = $GLOBALS['_CFG']['sort_order_method'] == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = $GLOBALS['_CFG']['sort_order_type'] == '0' ? 'goods_id' : ($GLOBALS['_CFG']['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

        $sort = request()->input('sort', '');
        $sort = in_array(trim(strtolower($sort)), ['goods_id', 'shop_price', 'last_update', 'sales_volume']) ? trim($sort) : $default_sort_order_type;
        $order = request()->input('order', '');
        $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;

        $act = addslashes(request()->input('act', ''));
        $goods_id = (int)request()->input('goods_id', 0);

        assign_template('c', 0);

        $position = assign_ur_here(0, $GLOBALS['_LANG']['view_history']);
        $this->smarty->assign('page_title', $position['title']);    // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

        $categories_pro = get_category_tree_leve_one();
        $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());              // 网店帮助
        $this->smarty->assign('show_marketprice', $GLOBALS['_CFG']['show_marketprice']);

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

        $count = cate_history_count();

        $max_page = ($count > 0) ? ceil($count / $size) : 1;
        if ($page > $max_page) {
            $page = $max_page;
        }

        if ($act == 'delHistory') {
            $res = ['err_msg' => '', 'result' => '', 'qty' => 1];

            $ecsCookie = request()->cookie('ECS');

            $goods_history = explode(',', $ecsCookie['history'] ?? '');
            $list_history = explode(',', $ecsCookie['list_history'] ?? '');

            $one_history = $this->get_setcookie_goods($goods_history, $goods_id);
            $two_history = $this->get_setcookie_goods($list_history, $goods_id);

            cookie()->queue('ECS[history]', implode(',', $one_history), 60 * 24 * 30);
            cookie()->queue('ECS[list_history]', implode(',', $two_history), 60 * 24 * 30);

            return response()->json($res);
        }

        $goodslist = cate_history($size, $page, $sort, $order, $warehouse_id, $area_id, $area_city, $ship, $self);

        //瀑布流加载分类商品 by wu start
        $this->smarty->assign('category_load_type', $GLOBALS['_CFG']['category_load_type']);
        $this->smarty->assign('query_string', request()->server('QUERY_STRING'));
        $this->smarty->assign('script_name', 'history_list');
        $this->smarty->assign('category', 0);

        $best_goods = $this->categoryGoodsService->getCategoryRecommendGoods('', 'best', 0, $warehouse_id, $area_id, $area_city);
        $this->smarty->assign('best_goods', $best_goods);

        $this->smarty->assign('region_id', $warehouse_id);
        $this->smarty->assign('area_id', $area_id);

        $this->smarty->assign('goods_list', $goodslist); // 分类游览历史记录 ecmoban模板堂 --zhuo
        $this->smarty->assign('dwt_filename', 'history_list');

        assign_pager('history_list', 0, $count, $size, $sort, $order, $page, '', '', '', '', '', '', '', '', '', '', '', '', $ship, $self); // 分页

        return $this->smarty->display('history_list.dwt');
    }

    private function get_setcookie_goods($list_history, $goods_id)
    {
        for ($i = 0; $i <= count($list_history); $i++) {
            if ($list_history[$i] == $goods_id) {
                unset($list_history[$i]);
            }
        }

        return $list_history;
    }
}
