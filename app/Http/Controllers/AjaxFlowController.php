<?php

namespace App\Http\Controllers;


use App\Models\Cart;
use App\Models\CartCombo;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GroupGoods;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\ShippingDate;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Repositories\Flow\FlowRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Cart\CartGoodsService;
use App\Services\Cart\CarthandleService;
use App\Services\Cart\CartsertService;
use App\Services\Cart\CartService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Flow\FlowActivityService;
use App\Services\Flow\FlowUserService;
use App\Services\Goods\GoodsAttributeImgService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Order\OrderGoodsService;
use App\Services\Package\PackageGoodsService;
use App\Services\User\UserBaitiaoService;

class AjaxFlowController extends InitController
{
    protected $userBaitiaoService;
    protected $cartCommonService;
    protected $goodsService;
    protected $baseRepository;
    protected $commonRepository;
    protected $goodsAttributeImgService;
    protected $dscRepository;
    protected $orderGoodsService;
    protected $goodsCommonService;
    protected $sessionRepository;
    protected $carthandleService;
    protected $config;
    protected $cartService;
    protected $packageGoodsService;
    protected $flowRepository;
    protected $flowUserService;
    protected $goodsWarehouseService;
    protected $goodsAttrService;
    protected $cartGoodsService;
    protected $flowActivityService;
    protected $cartsertService;
    protected $timeRepository;

    public function __construct(
        UserBaitiaoService $userBaitiaoService,
        CartCommonService $cartCommonService,
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        GoodsService $goodsService,
        GoodsAttributeImgService $goodsAttributeImgService,
        DscRepository $dscRepository,
        OrderGoodsService $orderGoodsService,
        GoodsCommonService $goodsCommonService,
        SessionRepository $sessionRepository,
        CarthandleService $carthandleService,
        CartService $cartService,
        PackageGoodsService $packageGoodsService,
        FlowRepository $flowRepository,
        FlowUserService $flowUserService,
        GoodsWarehouseService $goodsWarehouseService,
        GoodsAttrService $goodsAttrService,
        CartGoodsService $cartGoodsService,
        FlowActivityService $flowActivityService,
        CartsertService $cartsertService,
        TimeRepository $timeRepository
    )
    {
        $this->userBaitiaoService = $userBaitiaoService;
        $this->cartCommonService = $cartCommonService;
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->goodsService = $goodsService;
        $this->goodsAttributeImgService = $goodsAttributeImgService;
        $this->dscRepository = $dscRepository;
        $this->orderGoodsService = $orderGoodsService;
        $this->goodsCommonService = $goodsCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->carthandleService = $carthandleService;
        $this->config = $this->dscRepository->dscConfig();
        $this->cartService = $cartService;
        $this->packageGoodsService = $packageGoodsService;
        $this->flowRepository = $flowRepository;
        $this->flowUserService = $flowUserService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->goodsAttrService = $goodsAttrService;
        $this->cartGoodsService = $cartGoodsService;
        $this->flowActivityService = $flowActivityService;
        $this->cartsertService = $cartsertService;
        $this->timeRepository = $timeRepository;
    }

    public function index()
    {
        load_helper('order');

        /* 载入语言文件 */
        $this->dscRepository->helpersLang(['flow', 'user', 'shopping_flow']);

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

        $session_id = $this->sessionRepository->realCartMacIp();
        $user_id = session('user_id', 0);

        $cart_value = $this->cartCommonService->getCartValue();

        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $step = addslashes(trim(request()->input('step', '')));
        $act = addslashes(trim(request()->input('act', '')));

        /*------------------------------------------------------ */
        //-- 添加商品到购物车
        /*------------------------------------------------------ */
        if ($step == 'add_to_cart') {

            $goods = strip_tags(urldecode(request()->input('goods', '')));
            $goods = json_str_iconv($goods);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_id' => '', 'divId' => '', 'confirm_type' => '', 'number' => '', 'store_id' => 0];

            if (empty($goods)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $goods = dsc_decode($goods);

            if (!empty($goods->divId)) {
                $result['divId'] = $goods->divId;
            } else {
                $result['divId'] = '';
            }

            if (isset($goods->stages_qishu)) {
                $no_stages_qishu = 1;
                $flow_stages_qishu = 1;
            } else {
                $no_stages_qishu = 0;
                $flow_stages_qishu = 0;
            }

            session([
                'flow_stages_qishu' => $flow_stages_qishu
            ]);

            $goods->stages_qishu = isset($goods->stages_qishu) ? intval($goods->stages_qishu) : 0;
            $goods->warehouse_id = $warehouse_id;
            $goods->area_id = $area_id;
            $goods->confirm_type = isset($goods->confirm_type) ? intval($goods->confirm_type) : 0;
            $goods->store_id = isset($goods->store_id) ? intval($goods->store_id) : 0;
            $goods->end_time = isset($goods->end_time) ? addslashes($goods->end_time) : 0;
            $goods->store_mobile = isset($goods->store_mobile) ? addslashes($goods->store_mobile) : 0;
            $goods->buynow = isset($goods->buynow) ? intval($goods->buynow) : 0;

            $goods->stages_qishu = isset($goods->stages_qishu) && !empty($goods->stages_qishu) ? intval($goods->stages_qishu) : -1;
            //@author-bylu 检测当前用户白条相关权限(是否授权,是否额度为0,是否逾期);
            if ($goods->stages_qishu > 0) {
                $bt_status = $this->userBaitiaoService->btAuthCheck($goods->stages_qishu);
                switch ($bt_status) {
                    case 1:
                        $result['error'] = 1;
                        $result['message'] = lang('shopping_flow.bt_noll_impower');
                        return response()->json($result);
                        break;

                    case 2:
                        $result['error'] = 1;
                        $result['message'] = lang('shopping_flow.bt_noll_balance');
                        return response()->json($result);
                        break;

                    case 3:
                        return show_message(lang('shopping_flow.bt_forbid_pay'), lang('shopping_flow.bt_go_refund'), 'user_baitiao.php?act=baitiao');
                        break;

                    case 4:
                        $result['error'] = 1;
                        $result['message'] = lang('shopping_flow.bt_forbid_pay');
                        return response()->json($result);
                        break;

                    case 5:
                        return show_message(lang('shopping_flow.bt_overdue'), lang('shopping_flow.bt_go_refund'), 'user_baitiao.php?act=baitiao');
                        break;

                    case 6:
                        $result['error'] = 1;
                        $result['message'] = lang('shopping_flow.bt_overdue');
                        return response()->json($result);
                        break;
                }

            }
            $warehouse_id = intval($goods->warehouse_id);
            $area_id = intval($goods->area_id);

            $confirm_type = isset($goods->confirm_type) ? $goods->confirm_type : 0;

            //门店商品加入购物车是先清除购物车
            if ($goods->store_id > 0) {
                $this->cartCommonService->clearStoreGoods();
            }

            //分期购清除购物车
            if ($goods->stages_qishu > 0 && session('user_id')) {
                Cart::where('stages_qishu', '>', 0)->where('user_id', session('user_id'))->delete();
            }

            $goodsInfo = Goods::where('goods_id', $goods->goods_id);
            $goodsInfo = $this->baseRepository->getToArrayFirst($goodsInfo);

            /* 检查：该地区是否支持配送 ecmoban模板堂 --zhuo */
            if ($this->config['open_area_goods'] == 1) {

                $area_count = $this->goodsService->getHasLinkAreaGods($goods->goods_id, $area_id, $area_city);

                $no_area = 1;
                if ($area_count < 1) {
                    $no_area = 2;
                }

                if ($no_area == 2) {
                    $result['error'] = 1;
                    $result['message'] = lang('flow.shiping_prompt');

                    return response()->json($result);
                } elseif ($goodsInfo['review_status'] <= 2) {
                    $result['error'] = 1;
                    $result['message'] = lang('flow.shelves_goods');

                    return response()->json($result);
                }
            }


            /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
            if (empty($goods->spec) and empty($goods->quick)) {
                $res = GoodsAttr::where('goods_id', $goods->goods_id);

                $res = $res->whereHas('getGoods');

                $res = $res->whereHas('getGoodsAttribute', function ($query) {
                    $query->where('attr_type', '<>', 0);
                });

                $where = [
                    'area_pricetype' => $this->config['area_pricetype'],
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];

                $res = $res->with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'model_attr');
                    },
                    'getGoodsAttribute' => function ($query) {
                        $query->select('attr_id', 'attr_name', 'attr_type', 'sort_order');
                    },
                    'getGoodsWarehouseAttr' => function ($query) use ($warehouse_id) {
                        $query->select('goods_id', 'attr_price')
                            ->where('warehouse_id', $warehouse_id);
                    },
                    'getGoodsWarehouseAreaAttr' => function ($query) use ($where) {
                        $query = $query->select('goods_id', 'attr_price')
                            ->where('area_id', $where['area_id']);

                        if ($where['area_pricetype'] == 1) {
                            $query->where('city_id', $where['area_city']);
                        }
                    }
                ]);

                $res = $res->get();

                $res = $res ? $res->toArray() : [];

                if (!empty($res)) {
                    $spe_arr = [];

                    foreach ($res as $key => $row) {
                        $row = $row['get_goods_attribute'] ? array_merge($row, $row['get_goods_attribute']) : $row;

                        $res[$key] = $row;
                    }

                    $res = $this->baseRepository->getSortBy($res, 'sort_order');

                    foreach ($res as $row) {
                        $attr_price = 0;
                        if ($row['get_goods']) {
                            if ($row['get_goods']['model_attr'] == 1) {
                                $attr_price = $row['get_goods_warehouse_attr']['attr_price'];
                            } elseif ($row['get_goods']['model_attr'] == 2) {
                                $attr_price = $row['get_goods_warehouse_area_attr']['attr_price'];
                            } else {
                                $attr_price = $row['attr_price'];
                            }
                        }

                        $row['attr_price'] = $attr_price;

                        $img_site = [
                            'attr_img_flie' => $row['attr_img_flie'],
                            'attr_img_site' => $row['attr_img_site']
                        ];

                        $attr_info = $this->goodsAttributeImgService->getHasAttrInfo($row['attr_id'], $row['attr_value'], $img_site);
                        $row['img_flie'] = !empty($attr_info['attr_img']) ? $this->dscRepository->getImagePath($attr_info['attr_img']) : '';
                        $row['img_site'] = !empty($attr_info['attr_site']) ? $this->dscRepository->getImagePath($attr_info['attr_site']) : '';

                        $spe_arr[$row['attr_id']]['attr_type'] = $row['attr_type'];
                        $spe_arr[$row['attr_id']]['name'] = $row['attr_name'];
                        $spe_arr[$row['attr_id']]['attr_id'] = $row['attr_id'];
                        $spe_arr[$row['attr_id']]['values'][] = [
                            'img_flie' => $row['img_flie'],
                            'img_site' => $row['img_site'],
                            'label' => $row['attr_value'],
                            'price' => $row['attr_price'],
                            'checked' => $row['attr_checked'],
                            'attr_sort' => $row['attr_sort'],
                            'format_price' => $this->dscRepository->getPriceFormat($row['attr_price'], false),
                            'id' => $row['goods_attr_id']];

                        $spe_arr[$row['attr_id']]['values'] = $this->baseRepository->getSortBy($spe_arr[$row['attr_id']]['values'], 'attr_sort');
                        $spe_arr[$row['attr_id']]['is_checked'] = $this->commonRepository->getAttrValues($spe_arr[$row['attr_id']]['values']);
                    }

                    $spe_array = [];
                    if ($spe_arr) {
                        foreach ($spe_arr as $row) {
                            $spe_array[] = $row;
                        }
                    }

                    if (!empty($goods->confirm_type)) {
                        $result['confirm_type'] = $goods->confirm_type;
                    }
                    if (!empty($goods->number)) {
                        $result['number'] = $goods->number;
                    }

                    $result['error'] = ERR_NEED_SELECT_ATTR;
                    $result['goods_id'] = $goods->goods_id;
                    $result['warehouse_id'] = $warehouse_id;
                    $result['area_id'] = $area_id;
                    $result['parent'] = $goods->parent;
                    $this->smarty->assign('spe_array', $spe_array);

                    $this->smarty->assign('goods_id', $goods->goods_id);
                    $this->smarty->assign('region_id', $warehouse_id);
                    $this->smarty->assign('area_id', $area_id);

                    $start_date = $goodsInfo['xiangou_start_date'];
                    $end_date = $goodsInfo['xiangou_end_date'];

                    $nowTime = $this->timeRepository->getGmTime();
                    if ($nowTime > $start_date && $nowTime < $end_date) {
                        $xiangou = 1;
                    } else {
                        $xiangou = 0;
                    }

                    // 最小起订量
                    if ($goodsInfo['is_minimum'] == 1 && $nowTime > $goodsInfo['minimum_start_date'] && $nowTime < $goodsInfo['minimum_end_date']) {
                        $goodsInfo['is_minimum'] = 1;
                    } else {
                        $goodsInfo['is_minimum'] = 0;
                        $goodsInfo['minimum'] = 0;
                    }

                    $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods->goods_id, $user_id);
                    $this->smarty->assign('xiangou', $xiangou);
                    $this->smarty->assign('orderG_number', $order_goods['goods_number']);
                    $this->smarty->assign('goods', $goodsInfo);

                    $this->smarty->assign('cfg', $this->config);

                    $result['message'] = $this->smarty->fetch("library/goods_attr.lbi");

                    return response()->json($result);
                }
            }

            /* 更新：如果是一步购物，先清空购物车 */
            if ($goods->buynow == 1) {
                session()->flash('one_step_buy', 1);
                $this->cartCommonService->clearCart($user_id, CART_ONESTEP_GOODS);
            }

            /* 检查：商品数量是否合法 */
            if (!is_numeric($goods->number) || intval($goods->number) <= 0) {
                $result['error'] = 1;
                $result['message'] = lang('flow.invalid_number');
            } /* 更新：购物车 */
            else {
                $nowTime = $this->timeRepository->getGmTime();
                $xiangouInfo = $this->goodsCommonService->getPurchasingGoodsInfo($goods->goods_id);
                $start_date = $xiangouInfo['xiangou_start_date'];
                $end_date = $xiangouInfo['xiangou_end_date'];

                if ($xiangouInfo['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
                    $cart_number = Cart::where('goods_id', $goods->goods_id);

                    if (!empty($user_id)) {
                        $cart_number = $cart_number->where('user_id', $user_id);
                    } else {
                        $cart_number = $cart_number->where('session_id', $session_id);
                    }

                    $cart_number = $cart_number->value('goods_number');

                    $orderGoods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods->goods_id, $user_id);

                    if ($orderGoods['goods_number'] >= $xiangouInfo['xiangou_num']) {
                        $result['error'] = 1;
                        $result['message'] = sprintf(lang('flow.purchasing_prompt'), $xiangouInfo['goods_name']);
                        return response()->json($result);
                    } else {
                        if ($xiangouInfo['xiangou_num'] > 0) {
                            if ($cart_number + $orderGoods['goods_number'] + $goods->number > $xiangouInfo['xiangou_num']) {
                                $result['error'] = 1;
                                $result['message'] = lang('flow.purchasing_prompt_two');
                                return response()->json($result);
                            }
                        }
                    }
                }

                // 最小起订量
                if ($goodsInfo['is_minimum'] == 1 && $nowTime > $goodsInfo['minimum_start_date'] && $nowTime < $goodsInfo['minimum_end_date']) {
                    if ($goodsInfo['minimum'] > $goods->number) {
                        $result['error'] = 1;
                        $result['message'] = sprintf(lang('flow.purchasing_minimum'), $goodsInfo['goods_name']);
                        $result['show_info'] = '';
                        return response()->json($result);
                    }
                }

                $act_id = 0;
                if ($result['divId'] && is_numeric($result['divId'])) {
                    $act_id = $result['divId'];
                }
                $add_res = $this->carthandleService->addtoCart($goods->goods_id, $goods->number, $goods->spec, $goods->parent, $warehouse_id, $area_id, $area_city, $goods->stages_qishu, $goods->store_id, $goods->end_time, $goods->store_mobile, $act_id);
                // 更新：添加到购物车
                if ($add_res['error'] == 0) {
                    if (!empty($goods->divId)) {
                        $result['divId'] = $goods->divId;
                    }

                    if ($this->config['cart_confirm'] > 2) {
                        $result['message'] = '';
                    } else {
                        $result['message'] = $this->config['cart_confirm'] == 1 ? lang('common.addto_cart_success_1') : lang('common.addto_cart_success_2');
                    }
                    $result['goods_id'] = $goods->goods_id;
                    $result['content'] = $this->cartsertService->insertCartInfo(4, 0, $goods->store_id);
                    $result['one_step_buy'] = $goods->buynow;

                    if ($goods->stages_qishu > -1 && $no_stages_qishu == 1) {
                        /* 标记购物流程为普通商品 */
                        session([
                            'flow_type' => CART_GENERAL_GOODS
                        ]);
                    }
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

            /*  @author-bylu 如果是点击"分期购"进来的就获取到分期购商品在购物车中的ID start */
            if (!empty($goods->stages_qishu) || $goods->store_id > 0) {

                //判断 有无商品属性传入,如果有商品属性就将商品属性加入条件;
                if (!empty($goods->spec)) {
                    $goods_attr_ids = is_array($goods->spec) ? implode(",", $goods->spec) : $goods->spec;
                } else {
                    $goods_attr_ids = '';
                }

                $cart_value = Cart::where('goods_id', $goods->goods_id)
                    ->where('user_id', $user_id);
                if ($goods_attr_ids) {
                    $cart_value = $cart_value->where('goods_attr_id', $goods_attr_ids);
                }

                if (isset($goods->store_id) && $goods->store_id > 0) {
                    session([
                        'flow_type' => CART_OFFLINE_GOODS
                    ]);
                    $cart_value = $cart_value->where('store_id', $goods->store_id)->where('rec_type', CART_OFFLINE_GOODS);

                    $result['store_id'] = $goods->store_id;
                }

                $cart_value = $cart_value->value('rec_id');
                $result['cart_value'] = !empty($cart_value) ? $cart_value : '';
            }
            /*  @author-bylu  end */

            if ($confirm_type > 0) {
                $result['confirm_type'] = $confirm_type;
            } else {
                $result['confirm_type'] = !empty($this->config['cart_confirm']) ? $this->config['cart_confirm'] : 2;
            }

            /* 定义一键购物商品 */
            if ($goods->buynow == 1 && $no_stages_qishu == 0) {

                if ($result['store_id'] > 0) {
                    $flow_type = CART_OFFLINE_GOODS;
                } else {
                    $flow_type = CART_ONESTEP_GOODS;
                }

                session([
                    'flow_type' => $flow_type
                ]);
            }

            if (!empty($goods->number)) {
                $result['number'] = $goods->number;
            }
            return response()->json($result);
        }

        /*------------------------------------------------------*/
        //-- 检查提交的购物车信息
        /*------------------------------------------------------*/
        elseif ($act == 'check_cart_goods') {
            $result = ['error' => 0, 'message' => ''];

            if (request()->exists('rec_id')) {
                $rec_id = addslashes_deep(request()->input('rec_id', 0));

                $where = [
                    'rec_id' => $rec_id,
                    'type' => 1,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];
                $res = $this->cartGoodsService->getGoodsCartList($where);

                if ($res) {
                    foreach ($res as $key => $val) {
                        if (empty($val['is_invalid']) && $val['is_delete'] == 0) {
                            if ($val['extension_code'] == 'package_buy') {
                                /* 现有库存是否还能凑齐一个礼包 */
                                if ($this->config['use_storage'] == '1' && $this->packageGoodsService->judgePackageStock($val['goods_id'], $val['goods_number'])) {
                                    $result['error'] = 1;
                                    $result['message'] = sprintf(lang('flow.flow_package_buy_insufficient'), $val['goods_name']);
                                    $result['rec_id'] = $key;

                                    return response()->json($result);
                                }
                            } else {
                                $nowTime = $this->timeRepository->getGmTime();
                                $xiangouInfo = $this->goodsCommonService->getPurchasingGoodsInfo($val['goods_id']);
                                $start_date = $xiangouInfo['xiangou_start_date'];
                                $end_date = $xiangouInfo['xiangou_end_date'];

                                if ($xiangouInfo['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
                                    $orderGoods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $val['goods_id'], $user_id);
                                    if ($orderGoods['goods_number'] >= $xiangouInfo['xiangou_num']) {
                                        $result['message'] = sprintf(lang('flow.purchase_Prompt'), $val['goods_name']);

                                        //更新购物车中的商品数量
                                        Cart::where('rec_id', $val['rec_id'])->update(['goods_number' => 0]);

                                        $result['error'] = 1;

                                        return response()->json($result);
                                    } else {
                                        if ($xiangouInfo['xiangou_num'] > 0) {
                                            if ($xiangouInfo['is_xiangou'] == 1 && $orderGoods['goods_number'] + $val['goods_number'] > $xiangouInfo['xiangou_num']) {
                                                $result['message'] = sprintf(lang('flow.purchasing_prompt'), $val['goods_name']);

                                                //更新购物车中的商品数量
                                                $cart_Num = $xiangouInfo['xiangou_num'] - $orderGoods['goods_number'];
                                                Cart::where('rec_id', $val['rec_id'])->update(['goods_number' => $cart_Num]);

                                                $result['error'] = 1;
                                                $result['cart_Num'] = $cart_Num;
                                                $result['rec_id'] = $key;

                                                return response()->json($result);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $result['error'] = 1;
                            $result['message'] = sprintf(lang('flow.goods_lower_frame_notic'), $val['goods_name']);
                            $result['rec_id'] = $key;
                        }
                    }
                }

                return response()->json($result);
            }
        }

        /*------------------------------------------------------*/
        //-- 切换门店，处理点单页面刷新，检查商品库存
        /*------------------------------------------------------*/
        elseif ($step == 'edit_offline_store') {

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            $result['error'] = 0;

            $store_id = intval(request()->input('store_id', 0));

            $store_seller = ($store_id > 0) ? 'store_seller' : '';
            $this->smarty->assign('store_seller', $store_seller);
            $this->smarty->assign('store_id', $store_id);

            //切换门店时  修正购物车门店id
            if ($store_id > 0) {
                if ($cart_value) {
                    $cart_value = $this->baseRepository->getExplode($cart_value);
                    Cart::whereIn('rec_id', $cart_value)->update(['store_id' => $store_id]);
                }
            }

            /* 对商品信息赋值 */
            $cart_goods_list = cart_goods($flow_type, $cart_value, 1, $warehouse_id, $area_id, $area_city, '', $store_id); // 取得商品列表，计算合计

            $cart_goods_list_new = cart_by_favourable($cart_goods_list);
            $this->smarty->assign('goods_list', $cart_goods_list_new);
            $cart_goods = $this->flowRepository->getNewGroupCartGoods($cart_goods_list_new);
            $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
            $this->smarty->assign('cart_goods_number', $cart_goods_number);
            if (empty($cart_goods)) {
                $result['error'] = 1;
                $result['msg'] = lang('flow.cart_or_login_not');
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, '', 0, $cart_value, 0, $cart_goods_list, 0, 0, $store_id);
                $this->smarty->assign('total', $total);

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $web = app(CrossBorderService::class)->webExists();

                    if (!empty($web)) {
                        $arr = [
                            'consignee' => $consignee ?? '',
                            'rec_type' => $flow_type ?? 0,
                            'store_id' => $store_id ?? 0,
                            'cart_value' => $cart_value ?? '',
                            'type' => $type ?? 0,
                            'uc_id' => $order['uc_id'] ?? 0
                        ];
                        $amount = $web->assignNewRatePrice($cart_goods_list, $total['amount'], $arr);
                        $total['amount'] = $amount['amount'];
                        $total['amount_formated'] = $amount['amount_formated'];
                    } else {
                        return response()->json(['error' => 1, 'message' => 'service not exists']);
                    }
                }

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->smarty->assign('is_group_buy', 1);
                } elseif ($flow_type == CART_EXCHANGE_GOODS) {
                    // 积分兑换 qin
                    $this->smarty->assign('is_exchange_goods', 1);
                }

                //有存在虚拟和实体商品 start
                get_goods_flow_type($cart_value);
                //有存在虚拟和实体商品 end

                $result['goods_list'] = $this->smarty->fetch('library/flow_cart_goods.lbi'); //送货清单
                $result['order_total'] = $this->smarty->fetch('library/order_total.lbi');//费用汇总
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 选择上门自取点
        /*------------------------------------------------------ */
        elseif ($step == 'select_picksite') {
            $res = ['error' => 0, 'err_msg' => '', 'content' => ''];

            $picksite_id = intval(request()->input('picksite_id', 0));
            $shipping_date = htmlspecialchars(request()->input('shipping_date', ''));
            $time_range = htmlspecialchars(request()->input('time_range', ''));
            $mark = (int)request()->input('mark', 0);

            if ($mark == 0) {
                session()->put('flow_consignee.point_id', $picksite_id);

            } else {
                if ($shipping_date) {
                    $week = $GLOBALS['_LANG']['unit']['week'] . $this->timeRepository->transitionDate($shipping_date);
                }
                $shipping_dateStr = $this->timeRepository->getLocalDate("m", $this->timeRepository->getLocalStrtoTime($shipping_date)) . "月" . $this->timeRepository->getLocalDate("d", $this->timeRepository->getLocalStrtoTime($shipping_date)) . "日【" . $week . "】" . $time_range;
                session()->put('flow_consignee.shipping_dateStr', $shipping_dateStr);
            }
            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee(session('user_id'));

            /* 对商品信息赋值 */
            $cart_goods_list = cart_goods($flow_type, $cart_value, 1, $warehouse_id, $area_id); // 取得商品列表，计算合计
            $cart_goods_list_new = cart_by_favourable($cart_goods_list);

            $cart_goods = $this->flowRepository->getNewGroupCartGoods($cart_goods_list_new);
            if (empty($cart_goods) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                if (empty($cart_goods)) {
                    $result['error'] = 1;
                    $result['err_msg'] = $GLOBALS['_LANG']['no_goods_in_cart'];
                } elseif (!$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                    $result['error'] = 2;
                    $result['err_msg'] = $GLOBALS['_LANG']['au_buy_after_login'];
                }
            }

            $this->smarty->assign('goods_list', $cart_goods_list_new);
            $this->smarty->assign('shipping_code', 'cac');
            //有存在虚拟和实体商品 start
            get_goods_flow_type($cart_value);
            //有存在虚拟和实体商品 end

            $res['content'] = $this->smarty->fetch('library/flow_cart_goods.lbi');

            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 按区域选择自提点
        /*------------------------------------------------------ */
        elseif ($step == 'getPickSiteList') {
            $district = intval(request()->input('id', 0));
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            if ($district == 0) {
                $where = [
                    'parent_id' => session('flow_consignee.city')
                ];
            } else {
                $where = [
                    'district' => $district
                ];
            }

            $self_point = $this->cartService->getSelfPointCart($where);

            if (empty($self_point)) {
                $result['error'] = 1;
            }

            return response()->json($self_point);
        }

        /*------------------------------------------------------ */
        //-- 上门自取弹框
        /*------------------------------------------------------ */
        elseif ($step == 'pickSite') {
            /* 自提点弹窗口 */
            $res = ['err_msg' => '', 'result' => ''];

            $mark = (int)request()->input('mark', 0);

            if ($mark == 1) {
                $days = [];

                for ($i = 0; $i <= 6; $i++) {
                    $days[$i]['shipping_date'] = $this->timeRepository->getLocalDate("Y-m-d", $this->timeRepository->getLocalStrtoTime(' +' . $i . 'day'));
                    $days[$i]['date_year'] = $days[$i]['shipping_date'];
                    $days[$i]['week'] = $GLOBALS['_LANG']['unit']['week'] . $this->timeRepository->transitionDate($days[$i]['shipping_date']);
                    $days[$i]['date'] = substr($days[$i]['shipping_date'], 5);
                }

                $shipping_date_list = ShippingDate::whereRaw(1);
                $shipping_date_list = $this->baseRepository->getToArrayGet($shipping_date_list);

                $select = [];
                if ($shipping_date_list) {
                    foreach ($shipping_date_list as $key => $val) {
                        $m = 0;
                        for ($s = 0; $s < 7; $s++) {
                            if ($s < $val['select_day']) {
                                $select[$m]['day'] = 0;
                                $select[$m]['date'] = $days[$m]['date'];
                                $select[$m]['week'] = $days[$m]['week'];
                                $select[$m]['shipping_date'] = $days[$m]['shipping_date'];
                            } else {
                                $strtime = $days[$m]['date_year'] . " " . $val['end_date'];
                                $strtime = $this->timeRepository->getLocalStrtoTime($strtime);
                                $select[$m]['day'] = 1;
                                $time = $this->timeRepository->getGmTime();
                                if ($strtime < $time + 8 * 3600) {
                                    $select[$m]['day'] = 0;
                                }
                                $select[$m]['date'] = $days[$m]['date'];
                                $select[$m]['week'] = $days[$m]['week'];
                                $select[$m]['shipping_date'] = $days[$m]['shipping_date'];
                            }
                            $m++;
                        }
                        $shipping_date_list[$key]['select_day'] = $select;
                        $select = [];
                    }
                }

                $this->smarty->assign('years', $this->timeRepository->getLocalDate('Y', $this->timeRepository->getGmTime()));
                $this->smarty->assign('days', $days);
                $this->smarty->assign('shipping_date_list', $shipping_date_list);

                $res['result'] = $this->smarty->fetch('library/picksite_date.lbi');
            } else {
                $district = session('flow_consignee.district');
                $city = session('flow_consignee.city');

                //全部区域
                $district_list = Region::where('parent_id', $city)->get();
                $district_list = $district_list ? $district_list->toArray() : [];

                $where = [
                    'district' => $district
                ];
                $picksite_list = $this->cartService->getSelfPointCart($where);

                $this->smarty->assign('picksite_list', $picksite_list);
                $this->smarty->assign('district_list', $district_list);
                $this->smarty->assign('district', $district);
                $this->smarty->assign('city', $city);

                $res['result'] = $this->smarty->fetch('library/picksite.lbi');
            }

            return response()->json($res);
        }

        /* ------------------------------------------------------ */
        //-- 选定/取消配送的保价
        /* ------------------------------------------------------ */
        elseif ($step == 'select_insure') {
            $result = ['error' => '', 'content' => '', 'need_insure' => 0];

            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee(session('user_id'));

            /* 对商品信息赋值 */
            $cart_goods = cart_goods($flow_type, $cart_value); // 取得商品列表，计算合计

            if (empty($cart_goods) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                $result['error'] = $GLOBALS['_LANG']['no_goods_in_cart'];
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();

                $order['need_insure'] = intval(request()->input('insure', 0));

                /* 保存 session */
                session([
                    'flow_order' => $order
                ]);

                $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
                $this->smarty->assign('cart_goods_number', $cart_goods_number);

                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['street_name'] = get_goods_region_name($consignee['street']);
                $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];
                $this->smarty->assign('consignee', $consignee);

                $cart_goods_list = cart_goods($flow_type, $cart_value, 1); // 取得商品列表，计算合计

                $this->smarty->assign('goods_list', $cart_goods_list);

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee, 0, $cart_value, 0, $cart_goods_list);
                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $web = app(CrossBorderService::class)->webExists();

                    if (!empty($web)) {
                        $arr = [
                            'consignee' => $consignee ?? '',
                            'rec_type' => $flow_type ?? 0,
                            'store_id' => $store_id ?? 0,
                            'cart_value' => $cart_value ?? '',
                            'type' => $type ?? 0,
                            'uc_id' => $order['uc_id'] ?? 0
                        ];
                        $amount = $web->assignNewRatePrice($cart_goods_list, $total['amount'], $arr);
                        $total['amount'] = $amount['amount'];
                        $total['amount_formated'] = $amount['amount_formated'];
                    } else {
                        return response()->json(['error' => 1, 'message' => 'service not exists']);
                    }
                }

                $this->smarty->assign('total', $total);

                /* 取得可以得到的积分和红包 */
                $cartWhere = [
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'include_gift' => false,
                    'rec_type' => $flow_type,
                    'cart_value' => $cart_value
                ];
                $cart_total = $this->cartCommonService->getCartAmount($cartWhere);

                $this->smarty->assign('total_integral', $cart_total - $total['bonus'] - $total['integral_money']);

                $total_bonus = $this->flowActivityService->getTotalBonus();
                $this->smarty->assign('total_bonus', price_format($total_bonus, false));

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->smarty->assign('is_group_buy', 1);
                } elseif ($flow_type == CART_EXCHANGE_GOODS) {
                    // 积分兑换
                    $this->smarty->assign('is_exchange_goods', 1);
                }

                $result['content'] = $this->smarty->fetch('library/order_total.lbi');
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取组合购买商品选择数据列表
        /* ------------------------------------------------------ */
        elseif ($step == 'add_del_cart_combo_list') {
            $group = strip_tags(urldecode(request()->input('group', '')));
            $group = json_str_iconv($group);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_id' => ''];

            if (empty($group)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $group = dsc_decode($group);
            $goodsRow = explode('|', $group->group_rev);
            $goods_id = $goodsRow[0];
            $group_id = str_replace('=', '_', $goodsRow[3]);

            $goodsRow2 = explode('=', $goodsRow[3]);
            $parent_id = $goodsRow2[1];

            $goodSEqual = $group->fitt_goods;

            $res = CartCombo::where('goods_id', $goods_id)->where('group_id', $group_id);

            if (!empty($user_id)) {
                $res = $res->where('user_id', $user_id);
            } else {
                $res = $res->where('session_id', $session_id);
            }

            $res->delete();

            $rec_count = CartCombo::where('parent_id', $parent_id)
                ->where('group_id', $group_id);

            if (!empty($user_id)) {
                $rec_count = $rec_count->where('user_id', $user_id);
            } else {
                $rec_count = $rec_count->where('session_id', $session_id);
            }

            $rec_count = $rec_count->count();

            if ($rec_count < 1) {
                //更新临时购物车（删除主件）
                $res = CartCombo::where('goods_id', $parent_id)
                    ->where('parent_id', 0)
                    ->where('group_id', $group_id);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $res->delete();

                $result['fitt_goods'] = '';
            } else {
                $arr = [];
                foreach ($goodSEqual as $key => $row) {
                    if ($row != $goods_id) {
                        $arr[$key] = $row;
                    }
                }
            }

            $result['fitt_goods'] = $arr;
            $result['add_group'] = $group->add_group;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取组合购买商品选择属性
        /* ------------------------------------------------------ */
        if ($step == 'add_cart_combo_goodsAttr') {
            $group = strip_tags(urldecode(request()->input('group', '')));
            $group = json_str_iconv($group);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_id' => '', 'goods_amount' => 0];

            if (empty($group)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $group = dsc_decode($group);
            $goodsRow = explode('_', $group->group_rev);

            $goodSEqual = $group->fitt_goods;
            $type = $group->type;
            $tImg = $group->tImg;
            $attr_id = $group->attr;
            $number = 1;
            $goods_id = $group->goods_id;
            $fittings_goods = $group->fittings_goods;  //配件主商品ID
            $fittings_attr = $group->fittings_attr;  //配件主商品属性组ID
            $warehouse_id = $goodsRow[4];
            $area_id = $goodsRow[5];
            $group_id = $goodsRow[0] . "_" . $goodsRow[1] . "_" . $goodsRow[2] . "_" . $goodsRow[3];

            $where = [
                'goods_id' => $goods_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goods = $this->goodsService->getGoodsInfo($where);

            if ($goods_id == 0) {
                $result['message'] = $GLOBALS['_LANG']['err_change_attr'];
                $result['error'] = 1;
            } else {
                if ($number == 0) {
                    $result['qty'] = $number = 1;
                } else {
                    $result['qty'] = $number;
                }

                $group_attr = implode('|', $group->attr);
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $group_attr, $warehouse_id, $area_id, $area_city);
                $attr_number = $products ? $products['product_number'] : 0;

                if ($goods['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $goods['goods_id'])->where('warehouse_id', $warehouse_id);
                } elseif ($goods['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $goods['goods_id'])->where('area_id', $area_id);

                    if ($this->config['area_pricetype'] == 1) {
                        $prod = $prod->where('city_id', $area_city);
                    }
                } else {
                    $prod = Products::where('goods_id', $goods['goods_id']);
                }

                $prod = $this->baseRepository->getToArrayFirst($prod);

                if (empty($prod)) { //当商品没有属性库存时
                    $attr_number = $goods['goods_number'];
                }

                $attr_number = !empty($attr_number) ? $attr_number : 0;
                $result['attr_number'] = $attr_number;

                if ($this->config['add_shop_price'] == 1) {
                    $add_tocart = 1;
                } else {
                    $add_tocart = 0;
                }

                $shop_price = $this->goodsCommonService->getFinalPrice($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city, 0, 0, $add_tocart);

                $prod_attr = [];
                if (!empty($prod['goods_attr'])) {
                    $prod_attr = explode('|', $prod['goods_attr']);
                }

                if (count($prod_attr) <= 1) {
                    if (empty($result['attr_number'])) {
                        $result['message'] = $GLOBALS['_LANG']['Stock_goods_null'];
                    }
                } elseif (count($prod_attr) > 1) {
                    if (count($prod_attr) == count($attr_id)) {
                        if (empty($result['attr_number'])) {
                            $result['message'] = $GLOBALS['_LANG']['Stock_goods_null'];
                        }
                    } else {
                        unset($result['attr_number']);
                    }
                }

                if (is_spec($prod_attr) && !empty($prod)) {
                    $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $prod_attr, $warehouse_id, $area_id, $area_city);
                }

                $warehouse_area = [
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city,
                ];

                $spec_price = $this->goodsAttrService->specPrice($attr_id, $goods_id, $warehouse_area);
                $goods_attr = $this->goodsAttrService->getGoodsAttrInfo($attr_id, 'pice', $warehouse_id, $area_id, $area_city);

                $res = CartCombo::where('group_id', $group_id)
                    ->where('goods_id', $goods_id);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $parent = [
                    'goods_attr_id' => implode(',', $attr_id),
                    'product_id' => $product_info['product_id'],
                    'goods_attr' => addslashes($goods_attr)
                ];
                $res->update($parent);

                if ($type == 1) {
                    $goods_price = $shop_price;
                } else {
                    $goods_price = GroupGoods::where('parent_id', $goodsRow[3])
                        ->where('goods_id', $goods_id)
                        ->where('group_id', $goodsRow[2])
                        ->value('goods_price');

                    if ($this->config['add_shop_price'] == 1) {
                        $goods_price = $goods_price + $spec_price;
                    }
                }

                $res = CartCombo::where('group_id', $group_id)
                    ->where('goods_id', $goods_id);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $other = [
                    'goods_price' => $goods_price
                ];

                if (!empty($tImg)) {
                    $other['img_flie'] = $tImg;
                }

                $res->update($other);

                $result['goods_id'] = $goods_id;
                $result['shop_price'] = price_format($shop_price);
                $result['market_price'] = $goods['market_price'];
                $result['result'] = price_format($shop_price * $number);
                $result['groupId'] = $goodsRow[2];

                //商品判断属性是否选完
                $attr_type_list = get_goods_attr_type_list($goods_id, 1);
                if ($attr_type_list == count($attr_id)) {
                    $result['attr_equal'] = 1;
                } else {
                    $result['attr_equal'] = 0;
                }

                $goods_info = get_goods_fittings_info($goodsRow[3], $warehouse_id, $area_id, $area_city, $group_id, 0, $fittings_goods, $fittings_attr);
                $fittings = get_goods_fittings([$goodsRow[3]], $warehouse_id, $area_id, $area_city, $group_id, 1, $goodSEqual);

                $fittings = array_merge($goods_info, $fittings);
                $fittings = array_values($fittings);

                $fittings_interval = get_choose_goods_combo_cart($fittings);

                if ($fittings_interval['return_attr'] < 1) { //配件商品没有属性时
                    $result['amount'] = !empty($fittings_interval['all_price_ori']) ? $fittings_interval['all_price_ori'] : 0;
                    $result['goods_amount'] = !empty($fittings_interval['all_price_ori']) ? price_format($fittings_interval['all_price_ori']) : 0;
                } else {
                    $result['amount'] = !empty($fittings_interval['fittings_price']) ? $fittings_interval['fittings_price'] : 0;
                    $result['goods_amount'] = !empty($fittings_interval['fittings_price']) ? price_format($fittings_interval['fittings_price']) : 0;
                }
                $result['goods_market_amount'] = !empty($fittings_interval['all_market_price']) ? price_format($fittings_interval['all_market_price']) : 0;
                $result['save_amount'] = price_format($fittings_interval['save_price_amount']);
            }

            //判断商品是否有属性，并且已经全选
            $list_select = get_combo_goods_list_select(0, $goodsRow[3], $group_id);
            $result['list_select'] = $list_select;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取组合购买商品选择数据列表
        /* ------------------------------------------------------ */
        elseif ($step == 'add_cart_combo_list') {
            $group = strip_tags(urldecode(request()->input('group', '')));
            $group = json_str_iconv($group);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_id' => ''];

            if (empty($group)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $group = dsc_decode($group);
            $number = $group->number;
            $goods = explode('_', $group->rev);

            $goodSEqual = isset($group->fitt_goods) ? $group->fitt_goods : [];

            $goods_id = $goods[3];
            $warehouse_id = $goods[4];
            $area_id = $goods[5];
            $rev = $goods[0] . "_" . $goods[1] . "_" . $goods[2] . "_" . $goods[3];
            $group = $goods[0] . "_" . $goods[1] . "_" . $goods[2];

            $result['groupId'] = $goods[2];
            $result['number'] = $number;

            if (!empty($number)) {
                $this->smarty->assign('number', $number); //套餐数量
            }
            $result['group'] = $group;
            $result['goods_id'] = $goods_id;
            $result['warehouse_id'] = $warehouse_id;
            $result['area_id'] = $area_id;
            $result['area_city'] = $area_city;
            $this->smarty->assign('group', $group); //组名称
            $this->smarty->assign('warehouse_id', $warehouse_id); //仓库
            $this->smarty->assign('area_id', $area_id); //地区
            $this->smarty->assign('area_city', $area_city); //地区
            $this->smarty->assign('goods_id', $goods_id); //主件商品ID
            //判断商品是否有属性，并且已经全选
            $list_select = get_combo_goods_list_select(0, $goods[3], $rev);

            //获取组合购买商品的总数量
            $combo_goods = get_cart_combo_goods_list(0, $goods[3], $rev);

            $result['group_rev'] = $goods[0] . "_" . $goods[1] . "_" . $goods[2] . "_" . $goods[3] . "_" . $goods[4] . "_" . $goods[5];
            $this->smarty->assign('group_rev', $result['group_rev']); //主件商品ID

            $fittings_top = get_goods_fittings([$goods_id], $warehouse_id, $area_id, $area_city, $goods[2], 2);
            $fittings_top = array_values($fittings_top);
            $this->smarty->assign('fittings_top', $fittings_top); // 配件列表
            $this->smarty->assign('list_select', $list_select); //是否全选

            if ($goodSEqual) { //弹框中点击添加配件商品
                $goods_info = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, $area_city, $rev);
                $fittings = get_goods_fittings([$goods_id], $warehouse_id, $area_id, $area_city, $rev, 1, $goodSEqual);

                $fittings = array_merge($goods_info, $fittings);
                $fittings = array_values($fittings);

                $fittings_interval = get_choose_goods_combo_cart($fittings, $number);

                $result['amount'] = !empty($fittings_interval['fittings_price']) ? $fittings_interval['fittings_price'] : 0;
                if ($list_select == 1) {
                    $result['goods_amount'] = !empty($fittings_interval['fittings_price']) ? price_format($fittings_interval['fittings_price']) : 0;
                    $result['goods_market_amount'] = !empty($fittings_interval['all_market_price']) ? price_format($fittings_interval['all_market_price']) : 0;
                    $result['save_amount'] = price_format($fittings_interval['save_price_amount']);
                } else {
                    $result['goods_amount'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');

                    if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                        $result['save_amount'] = price_format($fittings_interval['save_minPrice']);
                    } else {
                        $result['save_amount'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                    }

                    $result['goods_market_amount'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');
                }

                $result['fittings_minMax'] = $result['goods_amount'];
                $result['market_minMax'] = $result['goods_market_amount'];
                $result['save_minMaxPrice'] = $result['save_amount'];
            }

            /* ------------------------------------------------------ */
            //-- 商品详情页中点击组合购买
            /* ------------------------------------------------------ */
            else {
                if ($combo_goods['combo_number'] > 0) {
                    $goods_info = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, $area_city, $rev);
                    $fittings = get_goods_fittings([$goods_id], $warehouse_id, $area_id, $area_city, $rev, 1);
                } else {
                    $goods_info = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, $area_city, '', 1);
                    $fittings = get_goods_fittings([$goods_id], $warehouse_id, $area_id, $area_city);
                }

                $fittings = array_merge($goods_info, $fittings);
                $fittings = array_values($fittings);

                $fittings_interval = get_choose_goods_combo_cart($fittings);

                if ($combo_goods['combo_number'] > 0) {
                    if ($list_select == 1) {
                        $result['fittings_minMax'] = price_format($fittings_interval['all_price_ori']);
                        $result['market_minMax'] = price_format($fittings_interval['all_market_price']);
                        $result['save_minMaxPrice'] = price_format($fittings_interval['save_price_amount']);
                    } else {
                        if ($fittings_interval['return_attr'] < 1) { //配件商品没有属性时
                            $result['fittings_minMax'] = price_format($fittings_interval['all_price_ori']);
                            $result['save_minMaxPrice'] = price_format($fittings_interval['save_price_amount']);
                        } else {
                            $result['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                            if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                                $result['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                            } else {
                                $result['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                            }
                        }

                        if ($fittings_interval['return_attr'] < 1) { //配件商品没有属性时
                            $result['market_minMax'] = price_format($fittings_interval['all_market_price']);
                        } else {
                            $result['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');
                        }
                    }
                } else {
                    $result['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');

                    if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                        $result['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                    } else {
                        $result['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                    }

                    $result['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');
                }
            }

            $result['list_select'] = $list_select;
            $result['null_money'] = price_format(0);
            $result['collocation_number'] = $fittings_interval['collocation_number'];

            if ($combo_goods['combo_number'] < 1) {
                $fittings = [];
            }

            $result['spe_conut'] = 0;

            if ($fittings) {
                foreach ($fittings as $k => $v) {
                    if ($v['properties']['spe']) {
                        $result['spe_conut']++;
                    }
                }
            }

            $this->smarty->assign('fittings', $fittings); // 配件
            $this->smarty->assign('fittings_minMax', $result['fittings_minMax']); // 搭配区间价
            $this->smarty->assign('market_minMax', $result['market_minMax']); // 参考区间价
            $this->smarty->assign('save_minMaxPrice', $result['save_minMaxPrice']); // 节省区间价
            $this->smarty->assign('collocation_number', $result['collocation_number']); // 已搭配

            $group_number = Goods::where('goods_id', $goods_id)->value('group_number');
            $this->smarty->assign('group_number', $group_number); // 搭配区间数量
            $this->smarty->assign('null_money', price_format(0));

            $this->smarty->assign('goods_id', $goods_id);

            $result['content'] = $this->smarty->fetch("library/goods_fittings_result.lbi");
            $result['content_type'] = $this->smarty->fetch("library/goods_fittings_result_type.lbi");

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 套餐添加到购物车
        /* ------------------------------------------------------ */
        elseif ($step == 'add_to_cart_group') {
            $goods = strip_tags(urldecode(request()->input('goods', '')));
            $goods = json_str_iconv($goods);

            $result = ['error' => 0, 'message' => ''];

            if (empty($goods)) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['system_error'];
                return response()->json($result);
            }

            $goods = dsc_decode($goods);
            $group = $goods->group . "_" . $goods->goods_id; //套餐组

            //批量加入购物车
            $res = CartCombo::where('group_id', $group);

            if (!empty($user_id)) {
                $res = $res->where('user_id', $user_id);
            } else {
                $res = $res->where('session_id', $session_id);
            }

            $count = $res->count();

            if ($count > 0) {
                //清空购物车中的原有数据
                $res = Cart::where('group_id', $group);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $res->delete();

                $selectOther = [
                    'user_id', 'session_id', 'goods_id', 'goods_sn', 'product_id', 'group_id', 'goods_name',
                    'market_price', 'goods_price', 'goods_number', 'goods_attr', 'is_real',
                    'extension_code', 'parent_id', 'rec_type', 'is_gift', 'is_shipping', 'can_handsel',
                    'model_attr', 'goods_attr_id', 'warehouse_id', 'area_id', 'area_city', 'add_time'
                ];
                $res = CartCombo::select($selectOther)
                    ->where('group_id', $group);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $res = $this->baseRepository->getToArrayGet($res);

                //插入新的数据
                if ($res) {
                    Cart::insert($res);
                }

                //查询
                $ru_id = Goods::where('goods_id', $goods->goods_id)->value('user_id');

                //插入更新购物车商品数量
                $res = Cart::where('group_id', $group);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $cartOther = [
                    'goods_number' => $goods->number,
                    'ru_id' => $ru_id
                ];
                $res->update($cartOther);

                //清空套餐临时数据
                $res = CartCombo::where('group_id', $group);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $res->delete();
            } else {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['data_null'];
                return response()->json($result);
            }

            $result['error'] = 0;
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除购物车项目
        /* ------------------------------------------------------ */
        elseif ($step == 'del_in_cart_combo') {
            $goods = strip_tags(urldecode(request()->input('goods', '')));
            $goods = json_str_iconv($goods);

            $goods_id = (int)request()->input('goods_id', 0);

            if (!empty($goods_id) && empty($goods)) {
                if (!is_numeric($goods_id) || intval($goods_id) <= 0) {
                    return redirect("/");
                }
            }

            $result = ['error' => 0, 'message' => ''];

            if (empty($goods)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $goods = dsc_decode($goods);

            //更新临时购物车（删除配件）
            $res = CartCombo::where('goods_id', $goods->goods_id)->where('group_id', $goods->group);

            if (!empty($user_id)) {
                $res = $res->where('user_id', $user_id);
            } else {
                $res = $res->where('session_id', $session_id);
            }

            $res->delete();

            /* 获取配件数量 */
            $rec_count = CartCombo::where('parent_id', $goods->parent)->where('group_id', $goods->group);

            if (!empty($user_id)) {
                $rec_count = $rec_count->where('user_id', $user_id);
            } else {
                $rec_count = $rec_count->where('session_id', $session_id);
            }

            $rec_count = $rec_count->count();

            if ($rec_count < 1) {
                //更新临时购物车（删除主件）
                $res = CartCombo::where('goods_id', $goods->parent)
                    ->where('parent_id', 0)
                    ->where('group_id', $goods->group);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $res->delete();
            }

            $result['error'] = 0;
            $result['group'] = substr($goods->group, 0, strrpos($goods->group, "_"));
            $result['parent'] = $goods->parent;

            $combo_goods = get_cart_combo_goods_list($goods->goods_id, $goods->parent, $goods->group);

            if (empty($combo_goods['shop_price'])) {
                $shop_price = $this->goodsCommonService->getFinalPrice($goods->parent, 1, true, $goods->goods_attr, $goods->warehouse_id, $goods->area_id, $area_city);
                $combo_goods['combo_amount'] = price_format($shop_price, false);
            }

            $result['combo_amount'] = $combo_goods['combo_amount'];
            $result['combo_number'] = $combo_goods['combo_number'];

            //查询组合购买商品区间价格 start
            $parent_id = $goods->parent;
            $warehouse_id = $goods->warehouse_id;
            $area_id = $goods->area_id;
            $rev = $goods->group;

            if ($combo_goods['combo_number'] > 0) {
                $goods_info = get_goods_fittings_info($parent_id, $warehouse_id, $area_id, $area_city, $rev);
                $fittings = get_goods_fittings([$parent_id], $warehouse_id, $area_id, $area_city, $rev, 1);
            } else {
                $goods_info = get_goods_fittings_info($parent_id, $warehouse_id, $area_id, $area_city, '', 1);
                $fittings = get_goods_fittings([$parent_id], $warehouse_id, $area_id, $area_city);
            }

            $fittings = array_merge($goods_info, $fittings);
            $fittings = array_values($fittings);

            $fittings_interval = get_choose_goods_combo_cart($fittings);

            if ($combo_goods['combo_number'] > 0) {
                $result['fittings_minMax'] = price_format($fittings_interval['all_price_ori']);
                $result['market_minMax'] = price_format($fittings_interval['all_market_price']);
                $result['save_minMaxPrice'] = price_format($fittings_interval['save_price_amount']);
            } else {
                $result['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                $result['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');

                if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                    $result['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                } else {
                    $result['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                }
            }

            $goodsGroup = explode('_', $goods->group);
            $result['groupId'] = $goodsGroup[2];
            //查询组合购买商品区间价格 end

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加组合购买商品到购物车
        /* ------------------------------------------------------ */
        elseif ($step == 'add_to_cart_combo') {
            $goods = strip_tags(urldecode(request()->input('goods', '')));
            $goods = json_str_iconv($goods);

            $goods_id = (int)request()->input('goods_id', 0);
            if (!empty($goods_id) && empty($goods)) {
                if (!is_numeric($goods_id) || intval($goods_id) <= 0) {
                    return redirect("/");
                }
            }

            $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_id' => ''];

            if (empty($goods)) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $goods = dsc_decode($goods);

            /* 更新：如果是一步购物，先清空购物车 */
            if (session('one_step_buy') == '1') {
                session()->reflash();
                $this->cartCommonService->clearCart();
            }

            /* 检查：商品数量是否合法 */
            if (!is_numeric($goods->number) || intval($goods->number) <= 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['invalid_number'];
            } /* 更新：购物车 */
            else {
                //ecmoban模板堂 --zhuo start 限购
                $nowTime = $this->timeRepository->getGmTime();
                $xiangouInfo = $this->goodsCommonService->getPurchasingGoodsInfo($goods->goods_id);
                $start_date = $xiangouInfo['xiangou_start_date'];
                $end_date = $xiangouInfo['xiangou_end_date'];

                if ($xiangouInfo['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
                    $cart_number = Cart::where('goods_id', $goods->goods_id);

                    if (!empty($user_id)) {
                        $cart_number = $cart_number->where('user_id', $user_id);
                    } else {
                        $cart_number = $cart_number->where('session_id', $session_id);
                    }

                    $cart_number = $cart_number->value('goods_number');

                    $orderGoods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods->goods_id, $user_id);
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
                //ecmoban模板堂 --zhuo end 限购

                // 更新：添加到购物车
                if (addto_cart_combo($goods->goods_id, $goods->number, $goods->spec, $goods->parent, $goods->group, $goods->warehouse_id, $goods->area_id, $goods->area_city, $goods->goods_attr)) {
                    if ($this->config['cart_confirm'] > 2) {
                        $result['message'] = '';
                    } else {
                        $result['message'] = $this->config['cart_confirm'] == 1 ? $GLOBALS['_LANG']['addto_cart_success_1'] : $GLOBALS['_LANG']['addto_cart_success_2'];
                    }

                    $result['group'] = $goods->group;
                    $result['goods_id'] = stripslashes($goods->goods_id);
                    $result['content'] = "";
                    $result['one_step_buy'] = session('one_step_buy', 0);

                    //返回 原价，配件价，库存信息

                    $warehouse_area['warehouse_id'] = $goods->warehouse_id;
                    $warehouse_area['area_id'] = $goods->area_id;
                    $warehouse_area['area_city'] = $area_city;

                    $combo_goods_info = get_combo_goods_info($goods->goods_id, $goods->number, $goods->spec, $goods->parent, $warehouse_area);
                    $result['fittings_price'] = $combo_goods_info['fittings_price'];
                    $result['spec_price'] = $combo_goods_info['spec_price'];
                    $result['goods_price'] = $combo_goods_info['goods_price'];
                    $result['stock'] = $combo_goods_info['stock'];
                    $result['parent'] = $goods->parent;
                } else {
                    $result['message'] = $this->err->last_message();
                    $result['error'] = $this->err->error_no();
                    $result['group'] = $goods->group;
                    $result['goods_id'] = stripslashes($goods->goods_id);
                    if (is_array($goods->spec)) {
                        $result['product_spec'] = implode(',', $goods->spec);
                    } else {
                        $result['product_spec'] = $goods->spec;
                    }
                }
            }

            $result['warehouse_id'] = $goods->warehouse_id;
            $result['area_id'] = $goods->area_id;
            $result['goods_attr'] = $goods->goods_attr;
            $result['goods_group'] = str_replace("_" . $goods->parent, '', $goods->group);

            $combo_goods = get_cart_combo_goods_list($goods->goods_id, $goods->parent, $goods->group);

            $result['combo_amount'] = $combo_goods['combo_amount'];
            $result['combo_number'] = $combo_goods['combo_number'];

            $result['add_group'] = $goods->add_group;

            //查询组合购买商品区间价格 start
            $parent_id = $goods->parent;
            $warehouse_id = $goods->warehouse_id;
            $area_id = $goods->area_id;
            $rev = $goods->group;
            $fitt_goods = isset($goods->fitt_goods) ? $goods->fitt_goods : [];

            if (!in_array($goods->goods_id, $fitt_goods)) {
                array_unshift($fitt_goods, $goods->goods_id);
            }

            $goods_info = get_goods_fittings_info($parent_id, $warehouse_id, $area_id, $area_city, $rev);
            $fittings = get_goods_fittings([$parent_id], $warehouse_id, $area_id, $area_city, $rev, 1);

            $fittings = array_merge($goods_info, $fittings);
            $fittings = array_values($fittings);

            $fittings_interval = get_choose_goods_combo_cart($fittings);

            $result['fittings_minMax'] = price_format($fittings_interval['all_price_ori']);
            $result['market_minMax'] = price_format($fittings_interval['all_market_price']);
            $result['save_minMaxPrice'] = price_format($fittings_interval['save_price_amount']);

            $goodsGroup = explode('_', $goods->group);
            $result['groupId'] = $goodsGroup[2];
            //查询组合购买商品区间价格 end

            $result['fitt_goods'] = $fitt_goods;

            $result['confirm_type'] = !empty($this->config['cart_confirm']) ? $this->config['cart_confirm'] : 2;
            return response()->json($result);
        }
    }
}
