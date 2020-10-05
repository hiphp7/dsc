<?php

namespace App\Http\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Store\StoreService;

class AjaxShopController extends InitController
{
    protected $storeService;
    protected $goodsWarehouseService;
    protected $dscRepository;

    public function __construct(
        StoreService $storeService,
        GoodsWarehouseService $goodsWarehouseService,
        DscRepository $dscRepository
    )
    {
        $this->storeService = $storeService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        $this->dscRepository->helpersLang('flow');

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
        //-- 异步获取门店列表
        /*------------------------------------------------------ */
        if ($act == "get_store_list") {

            /*接收数据*/
            $goods_id = intval(request()->input('goods_id', 0));

            $cart_value = addslashes_deep(request()->input('cart_value', ''));
            $province = intval(request()->input('province', 0));
            $city = intval(request()->input('city', 0));

            $district = intval(request()->input('district', 0));
            $type = request()->input('type', '');
            $spec_arr = addslashes_deep(request()->input('spec_arr', ''));

            $where = [
                'goods_id' => $goods_id,
                'cart_value' => $cart_value,
                'province' => $province,
                'city' => $city,
                'district' => $district
            ];

            $seller_store = $this->storeService->getStoreList($where);

            $is_spec = explode(',', $spec_arr);
            $html = '';
            $result['error'] = 0;
            if (!empty($seller_store)) {
                foreach ($seller_store as $k => $v) {
                    if (is_spec($is_spec) == true) {
                        $products = $this->goodsWarehouseService->getWarehouseAttrNumber($v['goods_id'], $spec_arr, $warehouse_id, $area_id, $area_city, '', $v['id']);//获取属性库存
                        $v['goods_number'] = $products['product_number'];
                    }

                    $v['goods_number'] = $v['goods_number'] ?? 0;

                    if ($v['goods_number'] > 0 || $cart_value) {
                        if ($type == 'flow') {
                            $html .= '<div class="option" data-value="' . $v['id'] . '" onClick="edit_offline_store(this);">' . $v['stores_name'] . '</div>';
                        } else {
                            $addtocart = "addToCart(" . $goods_id . ",0,0,'',''," . $v['id'] . ")";
                            $html .= '<li><div class="td s_title"><i></i>' . $v['stores_name'] . '</div><div class="td s_address">' . $GLOBALS['_LANG']['address'] . '[' . $v['province'] . '&nbsp;' . $v['city'] . '&nbsp;' . $v['district'] . ']&nbsp;' . $v['stores_address'] . '</div><div class="td handle"><a  href="javascript:bool=2;' . $addtocart . '" >' . $GLOBALS['_LANG']['Since_lift_new'] . '</a></div></li>';
                        }
                    }
                }
                $result['error'] = 1;
            }

            if ($type == 'flow') {
                $result['content'] = '<div class="option" data-value="" onClick="edit_offline_store(this);">' . $GLOBALS['_LANG']['Please_store'] . '</div>' . $html . '</div>';
            } elseif ($type == 'store_select_shop') {
                $this->smarty->assign('area_position_list', $seller_store);
                $result['content'] = $this->smarty->fetch('library/store_select_shop.lbi');
            } else {
                $result['content'] = $html;
            }
        }

        /*------------------------------------------------------ */
        //-- 门店列表
        /*------------------------------------------------------ */
        elseif ($act == 'all_stores_list') {

            /*接收数据*/
            $goods_id = intval(request()->input('goods_id', 0));
            $spec_arr = addslashes_deep(request()->input('spec_arr', ''));

            $where = [
                'goods_id' => $goods_id
            ];

            /*获取该商品有货门店*/
            $seller_store = $this->storeService->getStoreList($where);

            $is_spec = explode(',', $spec_arr);
            $html = '';
            $result['error'] = 0;
            if (!empty($seller_store)) {
                foreach ($seller_store as $k => $v) {
                    if (is_spec($is_spec) == true) {
                        $products = $this->goodsWarehouseService->getWarehouseAttrNumber($v['goods_id'], $spec_arr, $warehouse_id, $area_id, $area_city, '', $v['id']);//获取属性库存
                        $v['goods_number'] = $products['product_number'];
                    }
                    if ($v['goods_number'] > 0) {
                        $addtocart = "addToCart(" . $goods_id . ",0,0,'',''," . $v['id'] . ")";
                        $html .= '<li><div class="td s_title"><i></i>' . $v['stores_name'] . '</div><div class="td s_address">' . $GLOBALS['_LANG']['address'] . '[' . $v['province'] . '&nbsp;' . $v['city'] . '&nbsp;' . $v['district'] . ']&nbsp;' . $v['stores_address'] . '</div><div class="td handle"><a  href="javascript:bool=2;' . $addtocart . '" >' . $GLOBALS['_LANG']['Since_lift_new'] . '</a></div></li>';
                    }
                }
                $result['error'] = 1;
            }

            $result['content'] = $html;
        }

        if ($is_jsonp) {
            $jsoncallback = trim(request()->input('jsoncallback', ''));
            return $jsoncallback . "(" . response()->json($result) . ")";
        } else {
            return response()->json($result);
        }
    }
}
