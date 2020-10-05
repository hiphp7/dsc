<?php

namespace App\Http\Controllers;

use App\Models\RegionWarehouse;
use App\Models\UserAddress;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Flow\FlowRepository;
use App\Services\Activity\CouponsService;
use App\Services\Cart\CartCommonService;
use App\Services\Cart\CartService;
use App\Services\Common\AreaService;
use App\Services\Coupon\CouponsUserService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Flow\FlowUserService;
use App\Services\User\UserAddressService;

class AjaxFlowAddressController extends InitController
{
    protected $dscRepository;
    protected $cartService;
    protected $sessionRepository;
    protected $userAddressService;
    protected $areaService;
    protected $flowRepository;
    protected $couponsService;
    protected $flowUserService;
    protected $cartCommonService;
    protected $couponsUserService;

    public function __construct(
        DscRepository $dscRepository,
        CartService $cartService,
        SessionRepository $sessionRepository,
        UserAddressService $userAddressService,
        AreaService $areaService,
        FlowRepository $flowRepository,
        CouponsService $couponsService,
        FlowUserService $flowUserService,
        CartCommonService $cartCommonService,
        CouponsUserService $couponsUserService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->cartService = $cartService;
        $this->sessionRepository = $sessionRepository;
        $this->userAddressService = $userAddressService;
        $this->areaService = $areaService;
        $this->flowRepository = $flowRepository;
        $this->couponsService = $couponsService;
        $this->flowUserService = $flowUserService;
        $this->cartCommonService = $cartCommonService;
        $this->couponsUserService = $couponsUserService;
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
        /*------------------------------------------------------ */
        //-- 结算页面收货地址编辑
        /*------------------------------------------------------ */
        if ($step == 'edit_Consignee') {
            $address_id = intval(request()->input('address_id', 0));

            if ($address_id == 0) {
                $consignee['country'] = 1;
                $consignee['province'] = 0;
                $consignee['city'] = 0;
                $consignee['district'] = 0;
            } else {
                $consignee = $this->userAddressService->getUpdateFlowConsignee($address_id, $user_id);
            }

            $this->smarty->assign('consignee', $consignee);

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());

            $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
            $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('street_list', $street_list);

            //有存在虚拟和实体商品 start
            get_goods_flow_type($cart_value);
            //有存在虚拟和实体商品 end

            if (session('user_id') <= 0) {
                $result['error'] = 2;
                $result['message'] = $GLOBALS['_LANG']['lang_crowd_not_login'];
            } else {
                $result['error'] = 0;
                $result['content'] = $this->smarty->fetch("library/consignee_new.lbi");
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加收货地址
        /*------------------------------------------------------ */
        elseif ($step == 'insert_Consignee') {
            $time = gmtime();
            $result = ['message' => '', 'result' => '', 'error' => 0];
            $uc_id = intval(request()->input('uc_id', 0));

            $csg = json_str_iconv(request()->input('csg', ''));
            $csg = dsc_decode($csg);

            $shipping_id = strip_tags(urldecode(request()->input('shipping_id', '')));
            $tmp_shipping_id_arr = dsc_decode($shipping_id, true);

            $consignee = [
                'address_id' => empty($csg->address_id) ? 0 : intval($csg->address_id),
                'consignee' => empty($csg->consignee) ? '' : compile_str(trim($csg->consignee)),
                'country' => empty($csg->country) ? 0 : intval($csg->country),
                'province' => empty($csg->province) ? 0 : intval($csg->province),
                'city' => empty($csg->city) ? 0 : intval($csg->city),
                'district' => empty($csg->district) ? 0 : intval($csg->district),
                'street' => empty($csg->street) ? 0 : intval($csg->street),
                'email' => empty($csg->email) ? '' : compile_str($csg->email),
                'address' => empty($csg->address) ? '' : compile_str($csg->address),
                'zipcode' => empty($csg->zipcode) ? '' : compile_str(make_semiangle(trim($csg->zipcode))),
                'tel' => empty($csg->tel) ? '' : compile_str(make_semiangle(trim($csg->tel))),
                'mobile' => empty($csg->mobile) ? '' : compile_str(make_semiangle(trim($csg->mobile))),
                'sign_building' => empty($csg->sign_building) ? '' : compile_str($csg->sign_building),
                'update_time' => $time,
                'best_time' => empty($csg->best_time) ? '' : compile_str($csg->best_time)
            ];

            $goods_flow_type = $csg->goods_flow_type ?? 0;

            if ($consignee) {
                /* 删除缓存 */
                $this->areaService->getCacheNameForget('area_cookie');
                $this->areaService->getCacheNameForget('area_info');
                $this->areaService->getCacheNameForget('warehouse_id');

                $area_cache_name = $this->areaService->getCacheName('area_cookie');

                $area_cookie_cache = [
                    'province' => $consignee['province'],
                    'city_id' => $consignee['city'],
                    'district' => $consignee['district'],
                    'street' => $consignee['street'],
                    'street_area' => ''
                ];

                cache()->forever($area_cache_name, $area_cookie_cache);

                $flow_warehouse = get_warehouse_goods_region($consignee['province']);
                $flow_warehouse['region_id'] = $flow_warehouse['region_id'] ?? 0;
                cookie()->queue('area_region', $flow_warehouse['region_id'], 60 * 24 * 30);
                cookie()->queue('flow_region', $flow_warehouse['region_id'], 60 * 24 * 30);
            }

            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            if ($result['error'] == 0) {
                if ($user_id > 0) {
                    $where = [
                        'city' => $consignee['city']
                    ];
                    $district_count = $this->areaService->getRegionParentCount($where);

                    $where = [
                        'district' => $consignee['district']
                    ];
                    $street_count = $this->areaService->getRegionParentCount($where);

                    //验证传入数据
                    if ($consignee['district'] == '' && $district_count && $goods_flow_type == 101) {
                        $result['error'] = 4;
                        $result['message'] = $GLOBALS['_LANG']['district_null'];
                    } elseif ($consignee['street'] == '' && $street_count && $goods_flow_type == 101) {
                        $result['error'] = 4;
                        $result['message'] = $GLOBALS['_LANG']['street_null'];
                    }
                    if ($result['error'] == 0) {
                        $row = $this->userAddressService->getUserAddressCount($user_id, $consignee);

                        if ($row > 0) {
                            $result['error'] = 4;
                            $result['message'] = $GLOBALS['_LANG']['Distribution_exists'];
                        } else {
                            $result['error'] = 0;

                            if ($user_id > 0) {
                                /* 如果用户已经登录，则保存收货人信息 */
                                $consignee['user_id'] = $user_id;
                                $this->userAddressService->saveConsignee($consignee, true);
                            }

                            $user_address_id = Users::where('user_id', $user_id)->count('address_id');

                            if ($user_address_id > 0) {
                                $consignee['address_id'] = $user_address_id;
                            }

                            if ($consignee['address_id'] > 0) {
                                Users::where('user_id', $consignee['user_id'])->update(['address_id' => $consignee['address_id']]);

                                session([
                                    'flow_consignee' => $consignee
                                ]);

                                $result['message'] = $GLOBALS['_LANG']['edit_success_two'];
                            } else {
                                $result['message'] = $GLOBALS['_LANG']['add_success_two'];
                            }
                        }
                    }

                    $user_address = $this->userAddressService->getUserAddressList($user_id);
                    $this->smarty->assign('user_address', $user_address);
                    $consignee['province_name'] = get_goods_region_name($consignee['province']);
                    $consignee['city_name'] = get_goods_region_name($consignee['city']);
                    $consignee['district_name'] = get_goods_region_name($consignee['district']);
                    $consignee['street_name'] = get_goods_region_name($consignee['street']);
                    $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];

                    $this->smarty->assign('consignee', $consignee);

                    $result['content'] = $this->smarty->fetch("library/consignee_flow.lbi");

                    $warehouse_id = get_province_id_warehouse($consignee['province']);

                    $area_info = RegionWarehouse::select('region_id', 'regionId', 'region_name', 'parent_id')->where('regionId', $consignee['province'])->first();
                    $area_info = $area_info ? $area_info->toArray() : [];
                    $area_info['region_id'] = $area_info['region_id'] ?? 0;

                    $this->smarty->assign('warehouse_id', $warehouse_id);
                    $this->smarty->assign('area_id', $area_info['region_id']);

                    /* 对商品信息赋值 */
                    $cart_goods_list = cart_goods($flow_type, $cart_value, 1, $warehouse_id, $area_info['region_id']); // 取得商品列表，计算合计
                    $cart_goods_list_new = cart_by_favourable($cart_goods_list);

                    $cart_goods = $this->flowRepository->getNewGroupCartGoods($cart_goods_list_new);

                    //有存在虚拟和实体商品 start
                    get_goods_flow_type($cart_value);
                    //有存在虚拟和实体商品 end

                    $this->smarty->assign('goods_list', $cart_goods_list_new);
                    $result['goods_list'] = $this->smarty->fetch('library/flow_cart_goods.lbi'); //送货清单

                    /* 取得订单信息 */
                    $order = flow_order_info();
                    $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
                    $this->smarty->assign('cart_goods_number', $cart_goods_number);

                    //切换配送方式 by kong
                    $cart_goods_list = get_flowdone_goods_list($cart_goods_list, $tmp_shipping_id_arr);

                    /* 优惠券 免邮 start */
                    $not_freightfree = 0;
                    if ($uc_id > 0) {
                        $coupons_info = $this->couponsUserService->getCoupons($uc_id);
                        if (!empty($coupons_info) && $cart_goods_list) {
                            if ($coupons_info['cou_type'] == VOUCHER_SHIPPING) {
                                $goods_amount = 0;
                                foreach ($cart_goods_list as $key => $row) {
                                    if ($row['ru_id'] == $coupons_info['ru_id']) {
                                        foreach ($row['goods_list'] as $gkey => $grow) {
                                            $goods_amount += $grow['goods_price'] * $grow['goods_number'];
                                        }
                                    }
                                }

                                if ($goods_amount >= $coupons_info['cou_man'] || $coupons_info['cou_man'] == 0) {
                                    $cou_region = get_coupons_region($coupons_info['cou_id']);
                                    $cou_region = !empty($cou_region) ? explode(",", $cou_region) : [];

                                    /* 是否含有不支持免邮的地区 */
                                    if ($cou_region && in_array($consignee['province'], $cou_region)) {
                                        $not_freightfree = 1;
                                    }
                                }
                            }
                        }
                    }
                    /* 优惠券 免邮 start */

                    $result['not_freightfree'] = $not_freightfree;

                    $type = [
                        'type' => 0,
                        'shipping_list' => $tmp_shipping_id_arr,
                        'step' => $step
                    ];

                    /* 计算订单的费用 */
                    $total = order_fee($order, $cart_goods, $consignee, $type, $cart_value, 0, $cart_goods_list);

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

                    //更新优惠券
                    if ($this->config['use_coupons'] == 1 && $flow_type == CART_GENERAL_GOODS) {
                        $cart_ru_id = $cart_value ? $this->cartService->getCartSellerlist($cart_value) : '';

                        // 取得用户可用优惠券
                        $user_coupons = $this->couponsService->getUserCouponsList(session('user_id'), true, $total['goods_price'], $cart_goods, true, $cart_ru_id, '', $consignee['province']);

                        $coupons_list = $this->couponsService->getUserCouponsList(session('user_id'), true, '', false, true, $cart_ru_id, 'cart'); //获得当前登陆用户所有的优惠券

                        //获取不能使用的优惠券数组
                        if (!empty($coupons_list)) {
                            foreach ($coupons_list as $k => $v) {
                                $coupons_list[$k]['cou_type_name'] = $v['cou_type'];
                                $coupons_list[$k]['cou_end_time'] = local_date('Y-m-d', $v['cou_end_time']);
                                $coupons_list[$k]['cou_type'] = $v['cou_type'] == VOUCHER_ALL ? $GLOBALS['_LANG']['lang_goods_coupons']['all_pay'] : ($v['cou_type'] == VOUCHER_USER ? $GLOBALS['_LANG']['lang_goods_coupons']['user_pay'] : ($v['cou_type'] == VOUCHER_SHOPING ? $GLOBALS['_LANG']['lang_goods_coupons']['goods_pay'] : ($v['cou_type'] == VOUCHER_LOGIN ? $GLOBALS['_LANG']['lang_goods_coupons']['reg_pay'] : $GLOBALS['_LANG']['lang_goods_coupons']['not_pay'])));

                                if ($v['spec_cat']) {
                                    $coupons_list[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_cate'];
                                } elseif ($v['cou_goods']) {
                                    $coupons_list[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_goods'];
                                } else {
                                    $coupons_list[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_all'];
                                }

                                if (!empty($user_coupons)) {
                                    foreach ($user_coupons as $uk => $ur) {
                                        if ($v['cou_id'] == $ur['cou_id']) {
                                            unset($coupons_list[$k]);
                                            continue;
                                        }
                                    }
                                }
                            }
                            $result['is_coupons_list'] = 1;
                        }

                        //没有满足条件的优惠券数组
                        $this->smarty->assign('coupons_list', $coupons_list);

                        if ($user_coupons) {
                            foreach ($user_coupons as $k => $v) {
                                $user_coupons[$k]['cou_type_name'] = $v['cou_type'];
                                $user_coupons[$k]['cou_end_time'] = local_date('Y-m-d', $v['cou_end_time']);
                                $user_coupons[$k]['cou_type'] = $v['cou_type'] == VOUCHER_ALL ? $GLOBALS['_LANG']['lang_goods_coupons']['all_pay'] : ($v['cou_type'] == VOUCHER_USER ? $GLOBALS['_LANG']['lang_goods_coupons']['user_pay'] : ($v['cou_type'] == VOUCHER_SHOPING ? $GLOBALS['_LANG']['lang_goods_coupons']['goods_pay'] : ($v['cou_type'] == VOUCHER_LOGIN ? $GLOBALS['_LANG']['lang_goods_coupons']['reg_pay'] : ($v['cou_type'] == VOUCHER_SHIPPING ? $GLOBALS['_LANG']['lang_goods_coupons']['free_pay'] : $GLOBALS['_LANG']['lang_goods_coupons']['not_pay']))));
                                $user_coupons[$k]['cou_goods_name'] = $v['cou_goods'] ? $GLOBALS['_LANG']['lang_goods_coupons']['is_goods'] : $GLOBALS['_LANG']['lang_goods_coupons']['is_all'];
                                if ($v['spec_cat']) {
                                    $user_coupons[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_cate'];
                                } elseif ($v['cou_goods']) {
                                    $user_coupons[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_goods'];
                                } else {
                                    $user_coupons[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_all'];
                                }
                            }
                        }

                        if (!empty($user_coupons)) {
                            $result['is_user_coupons'] = 1;
                        }
                        //优惠券列表
                        $this->smarty->assign('user_coupons', $user_coupons);
                        $result['order_coupoms_list'] = $this->smarty->fetch('library/order_coupoms_list.lbi'); //优惠券列表
                    }

                    $result['order_total'] = $this->smarty->fetch('library/order_total.lbi'); //费用汇总
                } else {
                    $result['error'] = 2;
                    $result['message'] = $GLOBALS['_LANG']['lang_crowd_not_login'];
                }
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 删除收货地址
        /*------------------------------------------------------ */
        elseif ($step == 'delete_Consignee') {

            $result['error'] = 0;

            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            $address_id = intval(request()->input('address_id', 0));

            $type = intval(request()->input('type', 0));
            $store_id = intval(request()->input('store_id', 0));

            UserAddress::where('address_id', $address_id)
                ->where('user_id', $user_id)
                ->delete();

            $consignee = session('flow_consignee');
            $this->smarty->assign('consignee', $consignee);

            if ($consignee) {
                /* 删除缓存 */
                $this->areaService->getCacheNameForget('area_cookie');
                $this->areaService->getCacheNameForget('area_info');
                $this->areaService->getCacheNameForget('warehouse_id');

                $area_cache_name = $this->areaService->getCacheName('area_cookie');

                $area_cookie_cache = [
                    'province' => $consignee['province'],
                    'city_id' => $consignee['city'],
                    'district' => $consignee['district'],
                    'street' => $consignee['street'],
                    'street_area' => ''
                ];

                cache()->forever($area_cache_name, $area_cookie_cache);

                $flow_warehouse = get_warehouse_goods_region($consignee['province']);
                cookie()->queue('area_region', $flow_warehouse['region_id'] ?? 0, 60 * 24 * 30);
                cookie()->queue('flow_region', $flow_warehouse['region_id'] ?? 0, 60 * 24 * 30);
            }

            $warehouse_id = get_province_id_warehouse($consignee['province']);
            $area_info = RegionWarehouse::select('region_id', 'regionId', 'region_name', 'parent_id')->where('regionId', $consignee['province'])->first();
            $area_info = $area_info ? $area_info->toArray() : [];

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_info['region_id']);

            $user_address = $this->userAddressService->getUserAddressList($user_id);
            $this->smarty->assign('user_address', $user_address);

            //有存在虚拟和实体商品 start
            get_goods_flow_type($cart_value);
            //有存在虚拟和实体商品 end

            if (!$user_address) {
                $consignee = [
                    'province' => 0,
                    'city' => 0
                ];

                // 取得国家列表、商店所在国家、商店所在国家的省列表
                $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

                $country_list = $this->areaService->getRegionsLog();
                $province_list = $this->areaService->getRegionsLog(1, 1);
                $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
                $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);

                $this->smarty->assign('country_list', $country_list);
                $this->smarty->assign('province_list', $province_list);
                $this->smarty->assign('city_list', $city_list);
                $this->smarty->assign('district_list', $district_list);
                $this->smarty->assign('consignee', $consignee);

                $result['error'] = 2;
                if ($type == 1) {
                    $result['content'] = $this->smarty->fetch("library/consignee_flow.lbi");
                } else {
                    $result['content'] = $this->smarty->fetch("library/consignee_new.lbi");
                }
            } else {
                $result['content'] = $this->smarty->fetch("library/consignee_flow.lbi");
            }

            // 获取用户收货地址
            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 初始化地区ID */
            $consignee['country'] = !isset($consignee['country']) && empty($consignee['country']) ? 0 : intval($consignee['country']);
            $consignee['province'] = !isset($consignee['province']) && empty($consignee['province']) ? 0 : intval($consignee['province']);
            $consignee['city'] = !isset($consignee['city']) && empty($consignee['city']) ? 0 : intval($consignee['city']);
            $consignee['district'] = !isset($consignee['district']) && empty($consignee['district']) ? 0 : intval($consignee['district']);
            $consignee['street'] = !isset($consignee['street']) && empty($consignee['street']) ? 0 : intval($consignee['street']);
            $consignee['address'] = !isset($consignee['address']) && empty($consignee['address']) ? '' : intval($consignee['address']);

            if (empty($consignee)) {
                $consignee = ['country' => 0, 'province' => 0, 'city' => 0, 'district' => 0, 'street' => 0, 'province_name' => '', 'city_name' => '', 'district_name' => '', 'street_name' => '', 'address' => ''];
            }

            $consignee['province_name'] = get_goods_region_name($consignee['province']);
            $consignee['city_name'] = get_goods_region_name($consignee['city']);
            $consignee['district_name'] = get_goods_region_name($consignee['district']);
            $consignee['street_name'] = get_goods_region_name($consignee['street']);
            $consignee['region'] = $consignee['province_name'] . "&nbsp;" . $consignee['city_name'] . "&nbsp;" . $consignee['district_name'] . "&nbsp;" . $consignee['street_name'];
            $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];

            session([
                'flow_consignee' => $consignee
            ]);

            $this->smarty->assign('consignee', $consignee);

            /* 对商品信息赋值 */
            $cart_goods_list = cart_goods($flow_type, $cart_value, 1, $warehouse_id, $area_id, $area_city, $consignee, $store_id); // 取得商品列表，计算合计
            $cart_goods_list_new = cart_by_favourable($cart_goods_list);
            $this->smarty->assign('goods_list', $cart_goods_list_new);

            $cart_goods = $this->flowRepository->getNewGroupCartGoods($cart_goods_list_new);

            $result['goods_list'] = $this->smarty->fetch('library/flow_cart_goods.lbi'); //送货清单

            /* 取得订单信息 */
            $order = flow_order_info();

            $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
            $this->smarty->assign('cart_goods_number', $cart_goods_number);
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
            $result['order_total'] = $this->smarty->fetch('library/order_total.lbi'); //费用汇总

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 结算页面 切换收货地址
        /*------------------------------------------------------ */
        elseif ($step == 'edit_consignee_checked') {

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            $result['error'] = 0;

            $shipping_id = strip_tags(urldecode(request()->input('shipping_id', '')));
            $tmp_shipping_id_arr = dsc_decode($shipping_id, true);

            $uc_id = intval(request()->input('uc_id', 0));
            $address_id = intval(request()->input('address_id', 0));
            $store_id = intval(request()->input('store_id', 0));
            $store_seller = ($store_id > 0) ? 'store_seller' : '';
            $this->smarty->assign('store_seller', $store_seller);

            //默认快递
            session([
                'merchants_shipping' => []
            ]);

            $consignee = $this->userAddressService->getUpdateFlowConsignee($address_id, $user_id);

            /* 初始化地区ID */
            $consignee['country'] = !isset($consignee['country']) && empty($consignee['country']) ? 0 : intval($consignee['country']);
            $consignee['province'] = !isset($consignee['province']) && empty($consignee['province']) ? 0 : intval($consignee['province']);
            $consignee['city'] = !isset($consignee['city']) && empty($consignee['city']) ? 0 : intval($consignee['city']);
            $consignee['district'] = !isset($consignee['district']) && empty($consignee['district']) ? 0 : intval($consignee['district']);
            $consignee['street'] = !isset($consignee['street']) && empty($consignee['street']) ? 0 : intval($consignee['street']);

            session([
                'flow_consignee' => $consignee
            ]);

            if ($consignee) {
                /* 删除缓存 */
                $this->areaService->getCacheNameForget('area_cookie');
                $this->areaService->getCacheNameForget('area_info');
                $this->areaService->getCacheNameForget('warehouse_id');

                $area_cache_name = $this->areaService->getCacheName('area_cookie');

                $area_cookie_cache = [
                    'province' => $consignee['province'],
                    'city_id' => $consignee['city'],
                    'district' => $consignee['district'],
                    'street' => $consignee['street'],
                    'street_area' => ''
                ];

                cache()->forever($area_cache_name, $area_cookie_cache);

                $flow_warehouse = get_warehouse_goods_region($consignee['province']);
                if ($flow_warehouse) {
                    cookie()->queue('area_region', $flow_warehouse['region_id'], 60 * 24 * 30);
                    cookie()->queue('flow_region', $flow_warehouse['region_id'], 60 * 24 * 30);
                }
            } else {
                $consignee['province'] = 0;
                $consignee['city'] = 0;
                $consignee['district'] = 0;
                $consignee['street'] = 0;
            }

            $warehouse_id = get_province_id_warehouse($consignee['province']);
            $area_info = RegionWarehouse::select('region_id', 'regionId', 'region_name', 'parent_id')->whereIn('regionId', [$consignee['province'], $consignee['city']])->get();
            $area_info = $area_info ? $area_info->toArray() : [];

            $area_id = $area_info[0]['region_id'] ?? 0;
            $area_city = $area_info[1]['region_id'] ?? 0;

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('store_id', $store_id);

            $consignee['province_name'] = get_goods_region_name($consignee['province']);
            $consignee['city_name'] = get_goods_region_name($consignee['city']);
            $consignee['district_name'] = get_goods_region_name($consignee['district']);
            $consignee['street_name'] = get_goods_region_name($consignee['street']);
            $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];
            $this->smarty->assign('consignee', $consignee);

            $cart_goods_number = $this->cartCommonService->getBuyCartGoodsNumber($flow_type, $cart_value);
            $this->smarty->assign('cart_goods_number', $cart_goods_number);

            $user_address = $this->userAddressService->getUserAddressList($user_id);

            if (!$user_address && $consignee) {
                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['street_name'] = get_goods_region_name($consignee['street']);
                $consignee['region'] = $consignee['province_name'] . "&nbsp;" . $consignee['city_name'] . "&nbsp;" . $consignee['district_name'] . "&nbsp;" . $consignee['street_name'];

                $user_address = [$consignee];
            }

            $this->smarty->assign('user_address', $user_address);

            /* 对商品信息赋值 */
            $cart_goods_list = cart_goods($flow_type, $cart_value, 1, $warehouse_id, $area_id, $area_city, $consignee, $store_id); // 取得商品列表，计算合计

            $cart_goods_list_new = cart_by_favourable($cart_goods_list);
            $this->smarty->assign('goods_list', $cart_goods_list_new);

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $web = app(CrossBorderService::class)->webExists();

                if (!empty($web)) {
                    $new_cart_goods_list['goodslist'] = $cart_goods_list;
                    $is_kj = $web->assignIsKj($new_cart_goods_list);
                    if (!is_numeric($is_kj)) {
                        return $is_kj;
                    }
                }
            }

            $result['content'] = $this->smarty->fetch("library/consignee_flow.lbi");

            $cart_goods = $this->flowRepository->getNewGroupCartGoods($cart_goods_list_new);

            $check_consignee = $this->flowUserService->checkConsigneeInfo($consignee, $flow_type);

            if (empty($cart_goods) || !$check_consignee) {
                if (empty($cart_goods)) {
                    $result['error'] = 1;
                    $result['msg'] = $GLOBALS['_LANG']['cart_or_login_not'];
                } elseif (!$check_consignee) {
                    $result['error'] = 2;
                    $result['msg'] = $GLOBALS['_LANG']['address_Prompt'];
                }
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $GLOBALS['_CFG']);
                /* 取得订单信息 */
                $order = flow_order_info();

                //切换配送方式 by kong
                $cart_goods_list = get_flowdone_goods_list($cart_goods_list, $tmp_shipping_id_arr);

                $coupons_info = $this->couponsUserService->getCoupons($uc_id);

                /* 优惠券 免邮 start */
                $not_freightfree = 0;
                if (!empty($coupons_info) && $cart_goods_list) {
                    if ($coupons_info['cou_type'] == VOUCHER_SHIPPING) {
                        $goods_amount = 0;
                        foreach ($cart_goods_list as $key => $row) {
                            if ($row['ru_id'] == $coupons_info['ru_id']) {
                                foreach ($row['goods_list'] as $gkey => $grow) {
                                    $goods_amount += $grow['goods_price'] * $grow['goods_number'];
                                }
                            }
                        }

                        if ($goods_amount >= $coupons_info['cou_man'] || $coupons_info['cou_man'] == 0) {
                            $cou_region = get_coupons_region($coupons_info['cou_id']);
                            $cou_region = !empty($cou_region) ? explode(",", $cou_region) : [];

                            /* 是否含有不支持免邮的地区 */
                            if ($cou_region && in_array($consignee['province'], $cou_region)) {
                                $not_freightfree = 1;
                            }
                        }
                    }
                }
                /* 优惠券 免邮 start */

                $result['not_freightfree'] = $not_freightfree;

                /* 计算订单的费用 */
                $total = order_fee($order, $cart_goods, $consignee, 0, $cart_value, 0, $cart_goods_list, 0, 0, $store_id);
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

                //更新优惠券
                if ($this->config['use_coupons'] == 1 && $flow_type == CART_GENERAL_GOODS) {
                    $cart_ru_id = $cart_value ? $this->cartService->getCartSellerlist($cart_value) : '';

                    // 取得用户可用优惠券
                    $user_coupons = $this->couponsService->getUserCouponsList(session('user_id'), true, $total['goods_price'], $cart_goods, true, $cart_ru_id, '', $consignee['province']);

                    $coupons_list = $this->couponsService->getUserCouponsList(session('user_id'), true, '', false, true, $cart_ru_id, 'cart'); //获得当前登陆用户所有的优惠券
                    //获取不能使用的优惠券数组
                    if (!empty($coupons_list)) {
                        foreach ($coupons_list as $k => $v) {
                            $coupons_list[$k]['cou_type_name'] = $v['cou_type'];
                            $coupons_list[$k]['cou_end_time'] = local_date('Y-m-d', $v['cou_end_time']);
                            $coupons_list[$k]['cou_type'] = $v['cou_type'] == VOUCHER_ALL ? $GLOBALS['_LANG']['lang_goods_coupons']['all_pay'] : ($v['cou_type'] == VOUCHER_USER ? $GLOBALS['_LANG']['lang_goods_coupons']['user_pay'] : ($v['cou_type'] == VOUCHER_SHOPING ? $GLOBALS['_LANG']['lang_goods_coupons']['goods_pay'] : ($v['cou_type'] == VOUCHER_LOGIN ? $GLOBALS['_LANG']['lang_goods_coupons']['reg_pay'] : $GLOBALS['_LANG']['lang_goods_coupons']['not_pay'])));

                            if ($v['spec_cat']) {
                                $coupons_list[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_cate'];
                            } elseif ($v['cou_goods']) {
                                $coupons_list[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_goods'];
                            } else {
                                $coupons_list[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_all'];
                            }

                            if (!empty($user_coupons)) {
                                foreach ($user_coupons as $uk => $ur) {
                                    if ($v['cou_id'] == $ur['cou_id']) {
                                        unset($coupons_list[$k]);
                                        continue;
                                    }
                                }
                            }
                        }
                        $result['is_coupons_list'] = 1;
                    }
                    //没有满足条件的优惠券数组
                    $this->smarty->assign('coupons_list', $coupons_list);

                    if ($user_coupons) {
                        if ($user_coupons) {
                            foreach ($user_coupons as $k => $v) {
                                $user_coupons[$k]['cou_type_name'] = $v['cou_type'];
                                $user_coupons[$k]['cou_end_time'] = local_date('Y-m-d', $v['cou_end_time']);
                                $user_coupons[$k]['cou_type'] = $v['cou_type'] == VOUCHER_ALL ? $GLOBALS['_LANG']['lang_goods_coupons']['all_pay'] : ($v['cou_type'] == VOUCHER_USER ? $GLOBALS['_LANG']['lang_goods_coupons']['user_pay'] : ($v['cou_type'] == VOUCHER_SHOPING ? $GLOBALS['_LANG']['lang_goods_coupons']['goods_pay'] : ($v['cou_type'] == VOUCHER_LOGIN ? $GLOBALS['_LANG']['lang_goods_coupons']['reg_pay'] : ($v['cou_type'] == VOUCHER_SHIPPING ? $GLOBALS['_LANG']['lang_goods_coupons']['free_pay'] : $GLOBALS['_LANG']['lang_goods_coupons']['not_pay']))));
                                $user_coupons[$k]['cou_goods_name'] = $v['cou_goods'] ? $GLOBALS['_LANG']['lang_goods_coupons']['is_goods'] : $GLOBALS['_LANG']['lang_goods_coupons']['is_all'];
                                if ($v['spec_cat']) {
                                    $user_coupons[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_cate'];
                                } elseif ($v['cou_goods']) {
                                    $user_coupons[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_goods'];
                                } else {
                                    $user_coupons[$k]['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_all'];
                                }
                            }
                        }
                    }

                    if (!empty($user_coupons)) {
                        $result['is_user_coupons'] = 1;
                    }

                    //优惠券列表
                    $this->smarty->assign('user_coupons', $user_coupons);
                    $result['order_coupoms_list'] = $this->smarty->fetch('library/order_coupoms_list.lbi');//优惠券列表
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

                $result['goods_list'] = $this->smarty->fetch('library/flow_cart_goods.lbi');//送货清单
                $result['order_total'] = $this->smarty->fetch('library/order_total.lbi');//费用汇总
            }

            return response()->json($result);
        }
    }
}
