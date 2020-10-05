<?php

namespace App\Http\Controllers;

use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsGuessService;
use App\Services\Goods\GoodsService;

class AjaxGoodsController extends InitController
{
    protected $categoryService;
    protected $goodsService;
    protected $goodsGuessService;

    public function __construct(
        CategoryService $categoryService,
        GoodsService $goodsService,
        GoodsGuessService $goodsGuessService
    )
    {
        $this->categoryService = $categoryService;
        $this->goodsService = $goodsService;
        $this->goodsGuessService = $goodsGuessService;
    }

    public function index()
    {
        if ($this->checkReferer() === false) {
            return response()->json(['error' => 1, 'message' => 'referer error']);
        }

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

        $user_id = session('user_id', 0);

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        //jquery Ajax跨域
        $is_jsonp = intval(request()->input('is_jsonp', 0));
        $act = addslashes(trim(request()->input('act', '')));

        /*------------------------------------------------------ */
        //-- 普通商品详情，看了又看
        /*------------------------------------------------------ */
        if ($act == 'see_more_goods') {

            $cat_id = intval(request()->input('cat_id', 0));

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));
            $area_city = intval(request()->input('area_city', 0));

            $cache_name = 'see_more_goods_' . $cat_id . "_" . $warehouse_id . "_" . $area_id;
            $see_more_goods = cache($cache_name);
            $see_more_goods = !is_null($see_more_goods) ? $see_more_goods : false;

            if ($see_more_goods === false) {
                $top_cat = $this->categoryService->getTopparentCat($cat_id);
                $top_cat['cat_id'] = $top_cat['cat_id'] ?? 0;

                $see_more_goods = $this->goodsService->getFilterGoodsList(['cat_ids' => $top_cat['cat_id']], 'goods', $warehouse_id, $area_id, $area_city, 5, 1, "click_count", "DESC");

                cache()->forever($cache_name, $see_more_goods);
            }

            $this->smarty->assign('see_more_goods', $see_more_goods);

            $result['content'] = $this->smarty->fetch('library/see_more_goods.lbi');
        }

        /*------------------------------------------------------ */
        //-- 猜你喜欢--换一组ajax处理
        /*------------------------------------------------------ */
        elseif ($act == 'guess_goods') {
            $result = ['err_msg' => '', 'result' => ''];
            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));
            $area_city = intval(request()->input('area_city', 0));
            $page = intval(request()->input('page', 1));

            if ($page > 3) {
                $page = 1;
            }

            $need_cache = $this->smarty->caching;
            $need_compile = $this->smarty->force_compile;
            $this->smarty->caching = false;
            $this->smarty->force_compile = true;

            /**
             * Start
             *
             * 猜你喜欢商品
             */
            $where = [
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'user_id' => $user_id,
                'history' => 1,
                'page' => $page,
                'area_city' => $area_city,
                'limit' => 7
            ];
            $guess_goods = $this->goodsGuessService->getGuessGoods($where);

            $this->smarty->assign('guess_goods', $guess_goods);
            /* End */

            $result['page'] = $page;
            $result['result'] = $this->smarty->fetch('library/guess_goods_love.lbi');

            $this->smarty->caching = $need_cache;
            $this->smarty->force_compile = $need_compile;
        }

        /*------------------------------------------------------ */
        //-- 商品详情，猜你喜欢
        /*------------------------------------------------------ */
        elseif ($act == 'guess_goods_love') {
            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));
            $area_city = intval(request()->input('area_city', 0));

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

            $result['content'] = $this->smarty->fetch('library/guess_goods_love.lbi');
        }

        if ($is_jsonp) {
            $jsoncallback = trim(request()->input('jsoncallback', ''));
            return $jsoncallback . "(" . response()->json($result) . ")";
        } else {
            return response()->json($result);
        }
    }
}
