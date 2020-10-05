<?php

namespace App\Http\Controllers;

use App\Models\PayLog;
use App\Models\Payment;
use App\Models\Region;
use App\Models\Suppliers;
use App\Models\UserAddress;
use App\Models\Users;
use App\Models\UsersPaypwd;
use App\Models\Wholesale;
use App\Models\WholesaleCart;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleProducts;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\AreaService;
use App\Services\Common\CommonService;
use App\Services\Flow\FlowUserService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Payment\PaymentService;
use App\Services\User\UserAddressService;
use App\Services\Wholesale\CartService;
use App\Services\Wholesale\CategoryService;
use App\Services\Wholesale\GoodsService;
use App\Services\Wholesale\OrderService;
use App\Services\Wholesale\WholesaleService;
use Illuminate\Support\Str;

/**
 * 调查程序
 */
class WholesaleFlowController extends InitController
{
    protected $areaService;
    protected $categoryService;
    protected $cartService;
    protected $wholesaleService;
    protected $goodsService;
    protected $baseRepository;
    protected $orderService;
    protected $paymentService;
    protected $timeRepository;
    protected $commonRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $sessionRepository;
    protected $articleCommonService;
    protected $userAddressService;
    protected $flowUserService;
    protected $commonService;

    public function __construct(
        AreaService $areaService,
        CategoryService $categoryService,
        CartService $cartService,
        WholesaleService $wholesaleService,
        GoodsService $goodsService,
        OrderService $orderService,
        BaseRepository $baseRepository,
        PaymentService $paymentService,
        TimeRepository $timeRepository,
        CommonRepository $commonRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        SessionRepository $sessionRepository,
        ArticleCommonService $articleCommonService,
        UserAddressService $userAddressService,
        FlowUserService $flowUserService,
        CommonService $commonService
    )
    {
        $this->areaService = $areaService;
        $this->categoryService = $categoryService;
        $this->cartService = $cartService;
        $this->wholesaleService = $wholesaleService;
        $this->goodsService = $goodsService;
        $this->orderService = $orderService;
        $this->baseRepository = $baseRepository;
        $this->paymentService = $paymentService;
        $this->timeRepository = $timeRepository;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->articleCommonService = $articleCommonService;
        $this->userAddressService = $userAddressService;
        $this->flowUserService = $flowUserService;
        $this->commonService = $commonService;
    }

    public function index()
    {
        load_helper(['order', 'wholesale', 'suppliers']);

        $this->dscRepository->helpersLang(['user', 'shopping_flow']);

        $user_id = session('user_id', 0);

        $cart_value = $this->cartService->getWholesaleCartValue();

        //访问权限
        $wholesaleUse = $this->commonService->judgeWholesaleUse($user_id);

        if ($wholesaleUse['return']) {
            if ($user_id) {
                return show_message($GLOBALS['_LANG']['not_seller_user']);
            } else {
                return show_message($GLOBALS['_LANG']['not_login_user']);
            }
        }

        /* 跳转H5 start */
        $Loaction = 'mobile#/supplier/cart';
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
        $warehouse_id = $this->warehouse_id;
        $area_id = $this->area_id;
        $area_city = $this->area_city;
        /* End */

        $this->smarty->assign('keywords', htmlspecialchars($GLOBALS['_CFG']['shop_keywords']));
        $this->smarty->assign('description', htmlspecialchars($GLOBALS['_CFG']['shop_desc']));
        $this->smarty->assign('goods_flow_type', 101);

        /*------------------------------------------------------ */
        //-- INPUT
        /*------------------------------------------------------ */

        $step = addslashes(trim(request()->input('step', 'cart')));
        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        assign_template();
        $position = assign_ur_here(0, $GLOBALS['_LANG']['shopping_flow']);
        $this->smarty->assign('page_title', $position['title']);    // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
        $this->smarty->assign('lang', $GLOBALS['_LANG']);
        $this->smarty->assign('show_marketprice', $GLOBALS['_CFG']['show_marketprice']);

        $cat_list = $this->categoryService->getCategoryList();
        $this->smarty->assign('cat_list', $cat_list);
        $this->smarty->assign('data_dir', DATA_DIR);       // 数据目录

        $this->smarty->assign('user_id', $user_id);

        /*------------------------------------------------------ */
        //-- 添加商品到购物车
        /*------------------------------------------------------ */
        if ($step == 'add_to_cart') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            //处理数据
            $goods_id = (int)request()->input('goods_id', 0);

            //判断商品是否设置属性
            $goods_type = Wholesale::where('goods_id', $goods_id)->value('goods_type');
            $goods_type = $goods_type ? $goods_type : 0;

            $properties = $this->goodsService->getWholesaleGoodsProperties($goods_id);

            if ($properties && $goods_type > 0 && $properties['spe']) {
                $attr_array = request()->input('attr_array', []);
                $num_array = request()->input('num_array', []);
                $total_number = array_sum($num_array);
            } else {
                $goods_number = (int)request()->input('goods_number', 0);
                $total_number = $goods_number;
            }

            if ($user_id < 1) {
                //提示登陆
                $result['error'] = 2;
                $result['content'] = $GLOBALS['_LANG']['overdue_login'];
                return response()->json($result);
            }

            //计算价格
            $price_info = $this->goodsService->calculateGoodsPrice($goods_id, $total_number);

            //商品信息
            $goods_info = Wholesale::where('goods_id', $goods_id);
            $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

            if (!empty($user_id)) {
                $sess = "";
            } else {
                $sess = $this->sessionRepository->realCartMacIp();
            }

            //通用数据
            $common_data = array();
            $common_data['user_id'] = $user_id;
            $common_data['session_id'] = $sess;
            $common_data['goods_id'] = $goods_id;
            $common_data['goods_sn'] = $goods_info['goods_sn'];
            $common_data['goods_name'] = $goods_info['goods_name'];
            $common_data['goods_price'] = $price_info['unit_price'];
            $common_data['goods_number'] = 0;
            $common_data['goods_attr_id'] = '';
            $common_data['suppliers_id'] = $goods_info['suppliers_id'];
            $common_data['add_time'] = gmtime();

            //加入购物车
            if ($properties && $goods_type > 0 && $properties['spe']) {
                foreach ($attr_array as $key => $val) {

                    //货品信息
                    $attr = explode(',', $val);

                    //处理数据
                    $data = $common_data;
                    $data['goods_attr'] = $data['goods_attr'] ?? '';

                    $gooda_attr = $this->goodsService->getGoodsAttrArray($goods_id, $val);
                    foreach ($gooda_attr as $v) {
                        $data['goods_attr'] .= $v['attr_name'] . ":" . $v['attr_value'] . "\n";
                    }
                    $data['goods_attr_id'] = $val;
                    $data['goods_number'] = $num_array[$key];

                    //货品数据
                    $product_info = $this->goodsService->getWholesaleProductInfo($goods_id, $attr);
                    $data['product_id'] = $product_info['product_id'] ?? 0;

                    //判断是更新还是插入
                    $rec_id = $this->cartService->getFlowAddToCartRecId($goods_id, $attr, $user_id);

                    if (!empty($rec_id)) {
                        $goods_number = $data['goods_number'];
                        unset($data['goods_number']);

                        WholesaleCart::where('rec_id', $rec_id)->increment('goods_number', $goods_number, $data);
                    } else {
                        WholesaleCart::insert($data);
                    }
                }
            } else {
                $data = $common_data;
                $data['goods_number'] = $goods_number;

                //判断是更新还是插入
                $rec_id = $this->cartService->getFlowAddToCartRecId($goods_id, '', $user_id);

                if (!empty($rec_id)) {
                    $goods_number = $data['goods_number'];
                    unset($data['goods_number']);

                    WholesaleCart::where('rec_id', $rec_id)->increment('goods_number', $goods_number, $data);
                } else {
                    WholesaleCart::insert($data);
                }
            }

            //重新计算价格并更新价格
            $this->cartService->calculateCartGoodsPrice($goods_id);
            $result['content'] = insert_wholesale_cart_info();

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 更新记录数量
        /*------------------------------------------------------ */
        elseif ($step == 'update_rec_num') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $rec_id = (int)request()->input('rec_id', 0);
            $rec_num = (int)request()->input('rec_num', 0);

            //查询库存
            $cart_info = WholesaleCart::where('rec_id', $rec_id);
            $cart_info = $this->baseRepository->getToArrayFirst($cart_info);

            if (empty($cart_info)) {
                return response()->json($result);
            }

            if ($cart_info['product_id']) {
                $goods_number = WholesaleProducts::where('goods_id', $cart_info['goods_id'])
                    ->where('product_id', $cart_info['product_id'])->value('product_number');
            } else {
                $goods_number = Wholesale::where('goods_id', $cart_info['goods_id'])->value('goods_number');
            }

            $goods_number = $goods_number ? $goods_number : 0;
            $result['goods_number'] = $goods_number;

            if ($goods_number < $rec_num) {
                $result['error'] = 1;
                $result['message'] = "该商品库存只有{$goods_number}个";
                $rec_num = $goods_number;
            }

            WholesaleCart::where('rec_id', $rec_id)->update(['goods_number' => $rec_num]);

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 更新购物车
        /*------------------------------------------------------ */
        elseif ($step == 'ajax_update_cart') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $rec_ids = request()->input('rec_ids', []);
            $rec_ids = $rec_ids ? implode(',', $rec_ids) : '';

            // 修改选中状态
            $this->cartService->checked(0, $rec_ids);

            // 更新记录购物车rec_id
            $this->cartService->pushWholesaleCartValue($rec_ids);

            $cart_value = $this->cartService->getWholesaleCartValue(1, $user_id);

            //商品信息
            $is_checked = 1;
            $cart_goods = $this->cartService->wholesaleCartGoods(0, $cart_value, $is_checked);

            $goods_list = [];
            if ($cart_goods) {
                foreach ($cart_goods as $key => $val) {
                    foreach ($val['goods_list'] as $k => $g) {

                        //处理阶梯价格
                        $this->smarty->assign('goods', $g);
                        $g['volume_price_lbi'] = $this->smarty->fetch('library/wholesale_cart_volume_price.lbi');

                        //商品数据
                        $goods_list[$g['goods_id']] = $g;
                    }
                }
            }

            $result['goods_list'] = $goods_list;

            //订单信息
            $cart_info = $this->cartService->wholesaleCartInfo(0, $cart_value, $is_checked);
            $result['cart_info'] = $cart_info;

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 清除购物车
        /*------------------------------------------------------ */
        elseif ($step == 'clear') {
            $this->cartService->clearWholesaleCart();

            return redirect()->route('home');
        }

        /*------------------------------------------------------ */
        //-- 结算页面 切换收货地址
        /*------------------------------------------------------ */
        elseif ($step == 'edit_consignee_checked') {
            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            $result = array('msg' => '', 'result' => '', 'qty' => 1);
            $result['error'] = 0;

            $address_id = (int)request()->input('address_id', 0);
            $store_id = (int)request()->input('store_id', 0);
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
                cookie()->queue('area_region', $flow_warehouse['region_id'] ?? 0, gmtime() + 3600 * 24 * 30);
                cookie()->queue('flow_region', $flow_warehouse['region_id'] ?? 0, gmtime() + 3600 * 24 * 30);
            }

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('store_id', $store_id);

            $consignee['province_name'] = get_goods_region_name($consignee['province']);
            $consignee['city_name'] = get_goods_region_name($consignee['city']);
            $consignee['district_name'] = get_goods_region_name($consignee['district']);
            $consignee['street_name'] = get_goods_region_name($consignee['street']);
            $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];
            $this->smarty->assign('consignee', $consignee);

            $user_address = $this->userAddressService->getUserAddressList($user_id);

            if (!$user_address && $consignee) {
                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['street_name'] = get_goods_region_name($consignee['street']);
                $consignee['region'] = $consignee['province_name'] . "&nbsp;" . $consignee['city_name'] . "&nbsp;" . $consignee['district_name'] . "&nbsp;" . $consignee['street_name'];

                $user_address = array($consignee);
            }

            $this->smarty->assign('user_address', $user_address);

            $result['content'] = $this->smarty->fetch("library/wholesale_consignee_flow.lbi");

            /* 对商品信息赋值 */
            $cart_goods = $this->cartService->wholesaleCartGoods(0, $cart_value);
            $this->smarty->assign('goods_list', $cart_goods);

            $cart_goods = $this->get_new_group_cart_goods($cart_goods);

            if (empty($cart_goods) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                if (empty($cart_goods)) {
                    $result['error'] = 1;
                    $result['msg'] = $GLOBALS['_LANG']['cart_or_login_not'];
                } elseif (!$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                    $result['error'] = 2;
                    $result['msg'] = $GLOBALS['_LANG']['address_Prompt'];
                }
            } else {

                /* 取得购物流程设置 */
                $this->smarty->assign('config', $GLOBALS['_CFG']);

                /* 计算订单的费用 */
                $total = $this->cartService->wholesaleCartInfo(0, $cart_value);
                $this->smarty->assign('total', $total);

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->smarty->assign('is_group_buy', 1);
                } elseif ($flow_type == CART_EXCHANGE_GOODS) {
                    // 积分兑换 qin
                    $this->smarty->assign('is_exchange_goods', 1);
                }

                $result['goods_list'] = $this->smarty->fetch('library/wholesale_flow_cart_goods.lbi');//送货清单
                $result['order_total'] = $this->smarty->fetch('library/wholesale_order_total.lbi');//费用汇总
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 结算页面收货地址编辑
        /*------------------------------------------------------ */
        elseif ($step == 'edit_Consignee') {
            $result = array('message' => '', 'result' => '', 'qty' => 1);
            $address_id = (int)request()->input('address_id', 0);

            if ($address_id == 0) {
                $consignee['country'] = 1;
                $consignee['province'] = 0;
                $consignee['city'] = 0;
            }

            $consignee = $this->userAddressService->getUpdateFlowConsignee($address_id, $user_id);
            $this->smarty->assign('consignee', $consignee);

            $consignee['country'] = $consignee['country'] ?? 0;
            $consignee['province'] = $consignee['province'] ?? 0;
            $consignee['city'] = $consignee['city'] ?? 0;
            $consignee['district'] = $consignee['district'] ?? 0;

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());

            $this->smarty->assign('please_select', '请选择');

            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
            $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('street_list', $street_list);

            if ($user_id <= 0) {
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
            $result = array('message' => '', 'result' => '', 'error' => 0);
            $csg = dsc_decode(json_str_iconv(request()->input('csg', '')));

            $consignee = array(
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
                'best_time' => empty($csg->best_time) ? '' : compile_str($csg->best_time),
            );

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
                cookie()->queue('area_region', $flow_warehouse['region_id'] ?? 0, gmtime() + 3600 * 24 * 30);
                cookie()->queue('flow_region', $flow_warehouse['region_id'] ?? 0, gmtime() + 3600 * 24 * 30);
            }

            if ($result['error'] == 0) {
                if ($user_id > 0) {
                    $district_count = Region::where('parent_id', $consignee['city'])->count();
                    $street_count = Region::where('parent_id', $consignee['district'])->count();

                    load_helper(['transaction']);

                    //验证传入数据
                    if ($consignee['district'] == '' && $district_count && $csg->goods_flow_type == 101) {
                        $result['error'] = 4;
                        $result['message'] = $GLOBALS['_LANG']['district_null'];
                    } elseif ($consignee['street'] == '' && $street_count && $csg->goods_flow_type == 101) {
                        $result['error'] = 4;
                        $result['message'] = $GLOBALS['_LANG']['street_null'];
                    }
                    if ($result['error'] == 0) {
                        $row = UserAddress::where('consignee', $consignee['consignee'])
                            ->where('country', $consignee['country'])
                            ->where('province', $consignee['province'])
                            ->where('city', $consignee['city'])
                            ->where('district', $consignee['district'])
                            ->where('user_id', $user_id);

                        if ($consignee['address_id'] > 0) {
                            $row = $row->where('address_id', '<>', $consignee['address_id']);
                        }

                        $row = $row->count();

                        if ($row > 0) {
                            $result['error'] = 4;
                            $result['message'] = $GLOBALS['_LANG']['Distribution_exists'];
                        } else {
                            $result['error'] = 0;

                            /* 如果用户已经登录，则保存收货人信息 */
                            $consignee['user_id'] = $user_id;
                            $this->userAddressService->saveConsignee($consignee, true);

                            $user_address_id = Users::where('user_id', $user_id)->value('address_id');
                            $user_address_id = $user_address_id ? $user_address_id : 0;

                            if ($user_address_id > 0) {
                                $consignee['address_id'] = $user_address_id;
                            }

                            if ($consignee['address_id'] > 0) {
                                Users::where('user_id', $consignee['user_id'])->update([
                                    'address_id' => $consignee['address_id']
                                ]);

                                session([
                                    'flow_consignee' => $consignee
                                ]);

                                $result['message'] = $GLOBALS['_LANG']['edit_success_two'];
                            } else {
                                $result['message'] = $GLOBALS['_LANG']['add_success_two'];
                            }
                        }
                    }

                    $this->smarty->assign('warehouse_id', $warehouse_id);
                    $this->smarty->assign('area_id', $area_id);

                    $user_address = $this->userAddressService->getUserAddressList($user_id);
                    $this->smarty->assign('user_address', $user_address);
                    $consignee['province_name'] = get_goods_region_name($consignee['province']);
                    $consignee['city_name'] = get_goods_region_name($consignee['city']);
                    $consignee['district_name'] = get_goods_region_name($consignee['district']);
                    $consignee['street_name'] = get_goods_region_name($consignee['street']);
                    $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];

                    $this->smarty->assign('consignee', $consignee);

                    $result['content'] = $this->smarty->fetch("library/wholesale_consignee_flow.lbi");


                    /* 对商品信息赋值 */
                    $cart_goods = $this->cartService->wholesaleCartGoods(0, $cart_value);
                    $this->smarty->assign('goods_list', $cart_goods);

                    /* 计算订单的费用 */
                    $total = $this->cartService->wholesaleCartInfo(0, $cart_value);
                    $this->smarty->assign('total', $total);

                    $result['goods_list'] = $this->smarty->fetch('library/wholesale_flow_cart_goods.lbi'); //送货清单
                    $result['order_total'] = $this->smarty->fetch('library/wholesale_order_total.lbi'); //费用汇总
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
            $result = array('message' => '', 'result' => '', 'error' => 0, 'qty' => 1);

            $address_id = (int)request()->input('address_id', 0);
            $type = (int)request()->input('type', 0);

            UserAddress::where('user_id', $user_id)
                ->where('address_id', $address_id)
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
                cookie()->queue('area_region', $flow_warehouse['region_id'] ?? 0, gmtime() + 3600 * 24 * 30);
                cookie()->queue('flow_region', $flow_warehouse['region_id'] ?? 0, gmtime() + 3600 * 24 * 30);
            }

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            $user_address = $this->userAddressService->getUserAddressList($user_id);
            $this->smarty->assign('user_address', $user_address);

            if (!$user_address) {
                $consignee = array(
                    'province' => 0,
                    'city' => 0
                );
                // 取得国家列表、商店所在国家、商店所在国家的省列表
                $this->smarty->assign('country_list', $this->areaService->getRegionsLog());
                $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

                $province_list = $this->areaService->getRegionsLog(1, 1);
                $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
                $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);

                $this->smarty->assign('province_list', $province_list);
                $this->smarty->assign('city_list', $city_list);
                $this->smarty->assign('district_list', $district_list);
                $this->smarty->assign('consignee', $consignee);

                $result['error'] = 2;
                if ($type == 1) {
                    $result['content'] = $this->smarty->fetch("library/wholesale_consignee_flow.lbi");
                } else {
                    $result['content'] = $this->smarty->fetch("library/consignee_new.lbi");
                }
            } else {
                $result['content'] = $this->smarty->fetch("library/wholesale_consignee_flow.lbi");
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
                $consignee = [
                    'country' => 0,
                    'province' => 0,
                    'city' => 0,
                    'district' => 0,
                    'street' => 0,
                    'province_name' => '',
                    'city_name' => '',
                    'district_name' => '',
                    'street_name' => '',
                    'address' => ''
                ];
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
            $cart_goods = $this->cartService->wholesaleCartGoods(0, $cart_value);
            $this->smarty->assign('goods_list', $cart_goods);

            /* 计算订单的费用 */
            $total = $this->cartService->wholesaleCartInfo(0, $cart_value);
            $this->smarty->assign('total', $total);

            $result['goods_list'] = $this->smarty->fetch('library/wholesale_flow_cart_goods.lbi'); //送货清单
            $result['order_total'] = $this->smarty->fetch('library/wholesale_order_total.lbi'); //费用汇总

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 改变支付方式
        /*------------------------------------------------------ */
        elseif ($step == 'select_payment') {
            $result = array('error' => 0, 'massage' => '', 'content' => '', 'need_insure' => 0, 'payment' => 1);

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));
            $store_id = (int)request()->input('store_id', 0);

            $store_seller = addslashes(request()->input('store_seller', ''));
            $store_seller = ($store_id > 0) ? 'store_seller' : $store_seller;

            $this->smarty->assign('store_id', $store_id);
            $this->smarty->assign('store_seller', $store_seller);

            $warehouse_id = (int)request()->input('warehouse_id', 0);
            $area_id = (int)request()->input('area_id', 0);

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            /* 获得收货人信息 */
            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 对商品信息赋值 */
            $cart_goods = $this->cartService->wholesaleCartGoods(0, $cart_value);
            $this->smarty->assign('goods_list', $cart_goods);

            $cart_goods = $this->get_new_group_cart_goods($cart_goods);

            if (empty($cart_goods) || !$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                if (empty($cart_goods)) {
                    $result['error'] = 1;
                } elseif (!$this->flowUserService->checkConsigneeInfo($consignee, $flow_type)) {
                    $result['error'] = 2;
                }
            } else {
                /* 取得购物流程设置 */
                $this->smarty->assign('config', $GLOBALS['_CFG']);

                /* 取得订单信息 */
                $order = flow_order_info();
                $order['surplus'] = 0;

                $order['pay_id'] = (int)request()->input('payment', 0);

                $where = [
                    'pay_id' => $order['pay_id']
                ];
                $payment_info = $this->paymentService->getPaymentInfo($where);
                $result['pay_code'] = $payment_info['pay_code'];

                /* 保存 session */
                session([
                    'flow_order' => $order
                ]);

                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['street_name'] = get_goods_region_name($consignee['street']);
                $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];
                $this->smarty->assign('consignee', $consignee);

                /* 计算订单的费用 */
                $total = $this->cartService->wholesaleCartInfo(0, $cart_value);
                $this->smarty->assign('total', $total);

                /* 团购标志 */
                if ($flow_type == CART_GROUP_BUY_GOODS) {
                    $this->smarty->assign('is_group_buy', 1);
                } elseif ($flow_type == CART_EXCHANGE_GOODS) {
                    // 积分兑换 qin
                    $this->smarty->assign('is_exchange_goods', 1);
                }

                $result['content'] = $this->smarty->fetch('library/wholesale_order_total.lbi');
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 购物车商品结算
        /*------------------------------------------------------ */
        elseif ($step == 'checkout') {
            /*------------------------------------------------------ */
            //-- 订单确认
            /*------------------------------------------------------ */

            /**
             * 初始化红包、优惠券、储值卡
             */
            session()->forget('flow_order.bonus_id');
            session()->forget('flow_order.uc_id');
            session()->forget('flow_order.vc_id');

            /* 取得购物类型 */
            $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

            //配送方式--自提点标识
            session([
                'merchants_shipping' => []
            ]);

            //ecmoban模板堂 --zhuo
            $direct_shopping = request()->input('direct_shopping', 0);

            $cart_value = [];
            if (empty($cart_value)) {
                // 获取购物车选中的
                $cart_value = $this->cartService->getWholesaleCartValue(1, $user_id);
                $done_cart_value = implode(",", $cart_value);
            } else {
                if (count(explode(",", $cart_value)) == 1) {
                    $cart_value = intval($cart_value);
                }
            }

            $this->cartService->pushWholesaleCartValue($cart_value);

            $this->smarty->assign('cart_value', $done_cart_value);

            /* 对购物车选中商品信息赋值 */
            $cart_goods = $this->cartService->wholesaleCartGoods(0, $cart_value, 1);
            if (!$cart_goods) {
                return redirect()->route('wholesale');
            }
            $this->smarty->assign('goods_list', $cart_goods);

            /* 团购标志 */
            if ($flow_type == CART_GROUP_BUY_GOODS) {
                $this->smarty->assign('is_group_buy', 1);
            } elseif ($flow_type == CART_EXCHANGE_GOODS) {
                /* 积分兑换商品 */
                $this->smarty->assign('is_exchange_goods', 1);
            } elseif ($flow_type == CART_PRESALE_GOODS) {
                /* 预售商品 */
                $this->smarty->assign('is_presale_goods', 1);
            } else {
                //正常购物流程  清空其他购物流程情况
                session()->put('flow_order.extension_code', '');
            }

            /* 检查购物车中是否有商品 */
            $count = $this->cartService->getHasCartGoods($user_id, $flow_type);

            if ($count == 0) {
                return show_message($GLOBALS['_LANG']['no_goods_in_cart'], '', '', 'warning');
            }

            /*
             * 检查用户是否已经登录
             * 如果用户已经登录了则检查是否有默认的收货地址
             * 如果没有登录则跳转到登录和注册页面
             */
            if (empty($direct_shopping) && $user_id == 0) {
                /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
                return redirect()->route('user');
            }

            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 初始化地区ID */
            $consignee['country'] = !isset($consignee['country']) && empty($consignee['country']) ? 0 : intval($consignee['country']);
            $consignee['province'] = !isset($consignee['province']) && empty($consignee['province']) ? 0 : intval($consignee['province']);
            $consignee['city'] = !isset($consignee['city']) && empty($consignee['city']) ? 0 : intval($consignee['city']);
            $consignee['district'] = !isset($consignee['district']) && empty($consignee['district']) ? 0 : intval($consignee['district']);
            $consignee['street'] = !isset($consignee['street']) && empty($consignee['street']) ? 0 : intval($consignee['street']);
            $consignee['address'] = !isset($consignee['address']) && empty($consignee['address']) ? '' : $consignee['address'];

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
                    cookie()->queue('area_region', $flow_warehouse['region_id'] ?? 0, gmtime() + 3600 * 24 * 30);
                    cookie()->queue('flow_region', $flow_warehouse['region_id'] ?? 0, gmtime() + 3600 * 24 * 30);
                }
            }

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            $user_address = $this->userAddressService->getUserAddressList($user_id);

            if ($direct_shopping != 1 && !empty($user_id)) {
                $browse_trace = "wholesale_flow.php";
            } else {
                $browse_trace = "wholesale_flow.php?step=checkout";
            }

            session([
                'browse_trace' => $browse_trace
            ]);

            if (!$user_address && $consignee) {
                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['street_name'] = get_goods_region_name($consignee['street']);
                $consignee['region'] = $consignee['province_name'] . "&nbsp;" . $consignee['city_name'] . "&nbsp;" . $consignee['district_name'] . "&nbsp;" . $consignee['street_name'];

                $user_address = array($consignee);
            }

            $this->smarty->assign('user_address', $user_address);
            $this->smarty->assign('user_id', $user_id);

            session([
                'flow_consignee' => $consignee
            ]);

            $consignee['province_name'] = get_goods_region_name($consignee['province']);
            $consignee['city_name'] = get_goods_region_name($consignee['city']);
            $consignee['district_name'] = get_goods_region_name($consignee['district']);
            $consignee['street_name'] = get_goods_region_name($consignee['street']);
            $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['street_name'] . $consignee['address'];
            $this->smarty->assign('consignee', $consignee);

            /* 对是否允许修改购物车赋值 */
            if ($flow_type != CART_GENERAL_GOODS || session('one_step_buy') == '1') {
                $this->smarty->assign('allow_edit_cart', 0);
            } else {
                $this->smarty->assign('allow_edit_cart', 1);
            }

            /*
             * 取得购物流程设置
             */
            $this->smarty->assign('config', $GLOBALS['_CFG']);
            /*
             * 取得订单信息
             */
            $order = flow_order_info();
            $this->smarty->assign('order', $order);

            /* 如果能开发票，取得发票内容列表 */
            if ((!isset($GLOBALS['_CFG']['can_invoice']) || $GLOBALS['_CFG']['can_invoice'] == '1')
                && isset($GLOBALS['_CFG']['invoice_content'])
                && trim($GLOBALS['_CFG']['invoice_content']) != '' && $flow_type != CART_EXCHANGE_GOODS
            ) {
                $inv_content_list = explode("\n", str_replace("\r", '', $GLOBALS['_CFG']['invoice_content']));
                $this->smarty->assign('inv_content', $inv_content_list[0]);
                //默认发票计算
                $order['need_inv'] = 1;
                $order['inv_type'] = '';
                $order['inv_payee'] = '个人';
                $order['inv_content'] = $inv_content_list[0];
            }

            /* 计算折扣 */
            if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS) {
                $discount = compute_discount(3, $cart_value);
                $this->smarty->assign('discount', $discount['discount']);
                $favour_name = empty($discount['name']) ? '' : join(',', $discount['name']);
                $this->smarty->assign('your_discount', sprintf($GLOBALS['_LANG']['your_discount'], $favour_name, price_format($discount['discount'])));
            }

            if (!$user_address) {
                $consignee = array(
                    'province' => 0,
                    'city' => 0
                );
                // 取得国家列表、商店所在国家、商店所在国家的省列表
                $this->smarty->assign('country_list', get_regions());
                $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

                $province_list = $this->areaService->getRegionsLog(1, 1);
                $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
                $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);

                $this->smarty->assign('province_list', $province_list);
                $this->smarty->assign('city_list', $city_list);
                $this->smarty->assign('district_list', $district_list);
                $this->smarty->assign('consignee', $consignee);
            }

            /*
             * 计算订单的费用
             */
            $total = $this->cartService->wholesaleCartInfo(0, $cart_value);
            $this->smarty->assign('total', $total);

            /* 取得支付列表 */
            if ($order['shipping_id'] == 0) {
                $cod_fee = 0;
            } else {
                $shipping = shipping_info($order['shipping_id']);
                $cod = $shipping['support_cod'];

                if ($cod) {
                    /* 如果是团购，且保证金大于0，不能使用货到付款 */
                    if ($flow_type == CART_GROUP_BUY_GOODS) {
                        $group_buy_id = session('extension_id');
                        if ($group_buy_id <= 0) {
                            return show_message('error group_buy_id');
                        }
                        $group_buy = group_buy_info($group_buy_id);
                        if (empty($group_buy)) {
                            return show_message('group buy not exists: ' . $group_buy_id);
                        }

                        if ($group_buy['deposit'] > 0) {
                            $cod_fee = 0;

                            /* 赋值保证金 */
                            $this->smarty->assign('gb_deposit', $group_buy['deposit']);
                        }
                    }

                    if ($cod) {
                        $shipping_area_info = shipping_info($order['shipping_id']);
                        $cod_fee = isset($shipping_area_info['pay_fee']) ? $shipping_area_info['pay_fee'] : 0;
                    }
                } else {
                    $cod_fee = 0;
                }
            }

            // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
            $payment_list = available_payment_list(1, $cod_fee);

            if (isset($payment_list)) {
                foreach ($payment_list as $key => $payment) {
                    //ecmoban模板堂 --will start
                    //pc端去除ecjia的支付方式
                    if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                        unset($payment_list[$key]);
                        continue;
                    }
                    //ecmoban模板堂 --will end

                    if ($payment['is_cod'] == '1') {
                        $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
                    }
                    /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
                    if ($payment['pay_code'] == 'yeepayszx' && $total['amount'] > 300) {
                        unset($payment_list[$key]);
                    }

                    if ($payment['pay_code'] == 'alipay_wap') {
                        unset($payment_list[$key]);
                    }

                    /* 如果有余额支付 */
                    if ($payment['pay_code'] == 'balance') {
                        /* 如果未登录，不显示 */
                        if ($user_id == 0) {
                            unset($payment_list[$key]);
                        } else {
                            if (session('flow_order.pay_id') == $payment['pay_id']) {
                                $this->smarty->assign('disable_surplus', 1);
                            }
                        }
                    }
                }
            }

            //过滤掉在线支付的方法(余额支付,支付宝等等),因为订单结算页只允许显示一个在线支付按钮
            foreach ($payment_list as $k => $v) {
                if ($v['is_online'] == 1) {
                    unset($payment_list[$k]);
                }
            }

            $this->smarty->assign('payment_list', $payment_list);

            $user_info = Users::where('user_id', $user_id);
            $user_info = $this->baseRepository->getToArrayFirst($user_info);

            $user_info['user_money'] = $user_info['user_money'] ?? 0;

            // 如果开启在线支付
            $pay_online = UsersPaypwd::where('user_id', $user_id);
            $pay_online = $this->baseRepository->getToArrayFirst($pay_online);

            if ($pay_online) {
                if ($pay_online['pay_online'] || ($pay_online['user_surplus'] && $user_info['user_money'] > 0)) { //安装了"在线支付",才显示余额支付输入框 bylu;
                    $this->smarty->assign('open_pay_password', 1);
                    $this->smarty->assign('pay_pwd_error', 1);
                }
            }

            $enabled = Payment::where('pay_code', 'balance')->value('enabled');

            /* 如果使用余额，取得用户余额 */
            if ((!isset($GLOBALS['_CFG']['use_surplus']) || $GLOBALS['_CFG']['use_surplus'] == '1')
                && $user_id > 0
                && $user_info['user_money'] > 0
            ) {
                if ($enabled) { // 安装了"余额支付",才显示余额支付输入框 bylu;
                    // 能使用余额
                    $this->smarty->assign('allow_use_surplus', 1);
                    $this->smarty->assign('your_surplus', $user_info['user_money']);
                }
            }

            /* 储值卡 begin */
            /* 储值卡 end */

            /* 如果使用缺货处理，取得缺货处理列表 */
            if (!isset($GLOBALS['_CFG']['use_how_oos']) || $GLOBALS['_CFG']['use_how_oos'] == '1') {
                if (is_array($GLOBALS['_LANG']['oos']) && !empty($GLOBALS['_LANG']['oos'])) {
                    $this->smarty->assign('how_oos_list', $GLOBALS['_LANG']['oos']);
                }
            }

            $this->smarty->assign('flow_type', $flow_type);

            /* 保存 session */
            session([
                'flow_order' => $order
            ]);
        }

        /*------------------------------------------------------ */
        //-- 提交购物车商品
        /*------------------------------------------------------ */
        elseif ($step == 'done') {
            load_helper(['clips', 'suppliers']);

            //处理数据
            $rec_ids = addslashes(trim(request()->input('done_cart_value', '')));
            $rec_ids = $this->baseRepository->getExplode($rec_ids);

            $pay_id = (int)request()->input('payment', 0);
            $consignee = $this->flowUserService->getConsignee($user_id);
            /* 收货人信息 */
            foreach ($consignee as $key => $value) {
                $_REQUEST[$key] = addslashes($value);
            }

            //检查下架商品
            if ($rec_ids) {
                $goods_ids = WholesaleCart::whereIn('rec_id', $rec_ids);
                $goods_ids = $this->baseRepository->getToArrayGet($goods_ids);
                $goods_ids = $this->baseRepository->getKeyPluck($goods_ids, 'goods_id');

                $goods_ids = array_unique($goods_ids);
                foreach ($goods_ids as $key => $value) {
                    $enabled = get_table_date('wholesale', "goods_id='$value'", array('enabled'), 2);
                    if (empty($enabled)) {
                        return show_message(lang('wholesale_flow.order_has_soldout_goods'), lang('wholesale_flow.back_cart'), 'wholesale_flow.php?step=cart', 'info');
                    }
                }
            } else {
                return redirect()->route('wholesale_flow');
            }

            //公共数据

            $common_data['consignee'] = addslashes(trim(request()->input('consignee', '')));
            $common_data['mobile'] = addslashes(trim(request()->input('mobile', '')));
            $common_data['email'] = addslashes(trim(request()->input('email', '')));
            $common_data['tel'] = addslashes(trim(request()->input('tel', '')));
            $common_data['country'] = (int)request()->input('country', 0);
            $common_data['province'] = (int)request()->input('province', 0);
            $common_data['city'] = (int)request()->input('city', 0);
            $common_data['district'] = (int)request()->input('district', 0);
            $common_data['street'] = (int)request()->input('street', 0);
            $common_data['address'] = addslashes(trim(request()->input('address', '')));
            $common_data['zipcode'] = addslashes(trim(request()->input('zipcode', '')));
            $common_data['best_time'] = addslashes(trim(request()->input('best_time', '')));
            $common_data['sign_building'] = addslashes(trim(request()->input('sign_building', '')));
            $common_data['inv_type'] = (int)request()->input('inv_type', 0);
            $common_data['pay_id'] = $pay_id;
            $common_data['postscript'] = addslashes(trim(request()->input('postscript', '')));
            $common_data['inv_payee'] = addslashes(trim(request()->input('inv_payee', '')));
            $common_data['inv_content'] = addslashes(trim(request()->input('inv_content', '')));
            $common_data['invoice_type'] = (int)request()->input('invoice_type', 0);
            $common_data['tax_id'] = addslashes(trim(request()->input('tax_id', '')));;

            if ($common_data['pay_id'] == 0) {
                return show_message(lang('wholesale_flow.please_select_payment'), lang('wholesale_flow.back_cart'), 'wholesale_flow.php?step=cart', 'info');
            }

            //内部数据
            $main_order = $common_data;
            $main_order['order_sn'] = get_order_sn(); //获取订单号
            $main_order['main_order_id'] = 0; //主订单
            $main_order['add_time'] = gmtime();
            $main_order['goods_amount'] = 0;
            $main_order['order_amount'] = 0;
            $main_order['user_id'] = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;

            /* 获取表字段 插入主订单 */
            $other_order = $this->baseRepository->getArrayfilterTable($main_order, 'wholesale_order_info');

            $main_order_id = WholesaleOrderInfo::insertGetId($other_order);

            //开始分单 start
            $suppliers_id = [];
            if ($rec_ids) {
                $suppliers_id = WholesaleCart::whereIn('rec_id', $rec_ids)->where('user_id', $main_order['user_id']);
                $suppliers_id = $this->baseRepository->getToArrayGet($suppliers_id);
                $suppliers_id = $this->baseRepository->getKeyPluck($suppliers_id, 'suppliers_id');
                $suppliers_id = array_unique($suppliers_id);
            }

            if ($suppliers_id) {
                foreach ($suppliers_id as $key => $val) {
                    //内部数据
                    $child_order = $common_data;
                    $child_order['order_sn'] = get_order_sn(); //获取订单号
                    $child_order['main_order_id'] = $main_order_id; //主订单
                    $child_order['user_id'] = $main_order['user_id'];
                    $child_order['add_time'] = gmtime();
                    $child_order['goods_amount'] = 0;
                    $child_order['order_amount'] = 0;
                    $child_order['suppliers_id'] = $val;

                    /* 获取表字段 */
                    $other_order = $this->baseRepository->getArrayfilterTable($child_order, 'wholesale_order_info');

                    //插入子订单
                    $child_order_id = WholesaleOrderInfo::insertGetId($other_order);

                    //购物车商品数据
                    $cart_goods = WholesaleCart::where('suppliers_id', $val)
                        ->whereIn('rec_id', $rec_ids)
                        ->where('user_id', $main_order['user_id']);
                    $cart_goods = $this->baseRepository->getToArrayGet($cart_goods);

                    if ($cart_goods) {
                        foreach ($cart_goods as $k => $v) {
                            unset($v['rec_id']);

                            //插入订单商品表
                            $v['order_id'] = $child_order_id;

                            /* 获取表字段 */
                            $other_v = $this->baseRepository->getArrayfilterTable($v, 'wholesale_order_goods');

                            WholesaleOrderGoods::insert($other_v);

                            //统计子订单金额
                            $child_order['goods_amount'] += $v['goods_price'] * $v['goods_number'];
                            $child_order['order_amount'] = $child_order['goods_amount'];
                        }
                    }

                    //更新子订单数据

                    /* 获取表字段 */
                    $other_order = $this->baseRepository->getArrayfilterTable($child_order, 'wholesale_order_info');

                    WholesaleOrderInfo::where('order_id', $child_order_id)->update($other_order);

                    insert_pay_log($child_order_id, $child_order['order_amount'], PAY_WHOLESALE);//更新子订单支付日志

                    //统计主订单金额
                    $main_order['goods_amount'] += $child_order['goods_amount'];
                    $main_order['order_amount'] = $main_order['goods_amount'];

                    $suppliers = Suppliers::where('suppliers_id', $val);
                    $suppliers = $this->baseRepository->getToArrayFirst($suppliers);

                    if ($suppliers && $GLOBALS['_CFG']['sms_order_placed'] == '1' && $suppliers['mobile_phone'] != '') {
                        $shop_name = $this->merchantCommonService->getShopName($suppliers['user_id'], 1);
                        $order_region = $this->wholesaleService->getFlowWholesaleUserRegion($child_order_id);

                        //普通订单->短信接口参数
                        $smsParams = array(
                            'shop_name' => $shop_name,
                            'shopname' => $shop_name,
                            'order_sn' => $child_order['order_sn'],
                            'ordersn' => $child_order['order_sn'],
                            'consignee' => $child_order['consignee'],
                            'order_region' => $order_region,
                            'orderregion' => $order_region,
                            'address' => $child_order['address'],
                            'order_mobile' => $child_order['mobile'],
                            'ordermobile' => $child_order['mobile'],
                            'mobile_phone' => $suppliers['mobile_phone'],
                            'mobilephone' => $suppliers['mobile_phone']
                        );

                        $this->commonRepository->smsSend($suppliers['mobile_phone'], $smsParams, 'sms_order_placed');
                    }
                }
            }

            /* 获取表字段 */
            $other_order = $this->baseRepository->getArrayfilterTable($main_order, 'wholesale_order_info');

            WholesaleOrderInfo::where('order_id', $main_order_id)->update($other_order);

            $order_amount = WholesaleOrderInfo::where('order_id', $main_order_id)->value('order_amount');
            $order_amount = $order_amount ? $order_amount : 0;

            insert_pay_log($main_order_id, $order_amount, PAY_WHOLESALE);//更新主订单支付日志
            //开始分单 end

            //如果使用库存，且下订单时减库存，则减少库存
            if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                suppliers_change_order_goods_storage($main_order_id, true, SDT_PLACE);
            }

            //插入数据完成后删除购物车订单
            WholesaleCart::whereIn('rec_id', $rec_ids)
                ->where('user_id', $main_order['user_id'])
                ->delete();

            return dsc_header("Location: wholesale_flow.php?step=order_pay&order_id=" . $main_order_id . "\n");
        }

        /*------------------------------------------------------ */
        //-- 微信支付改变状态
        /*------------------------------------------------------ */
        elseif ($step == 'checkorder') {
            $order_id = (int)request()->input('order_id', 0);

            $order_info = WholesaleOrderInfo::where('order_id', $order_id);
            $order_info = $this->baseRepository->getToArrayFirst($order_info);

            $pay = Payment::where('pay_id', $order_info['pay_id']);
            $pay = $this->baseRepository->getToArrayFirst($pay);

            //已付款
            if ($order_info && $order_info['pay_status'] == PS_PAYED) {
                $json = array('code' => 1, 'pay_name' => $pay['pay_name'], 'pay_code' => $pay['pay_code']);

                return json_encode($json);
            } else {
                $json = array('code' => 0, 'pay_name' => $pay['pay_name'], 'pay_code' => $pay['pay_code']);

                return json_encode($json);
            }
        } elseif ($step == 'order_pay') {
            load_helper(['clips', 'payment', 'suppliers']);

            $order_id = (int)request()->input('order_id', 0);

            $order_info = WholesaleOrderInfo::where('order_id', $order_id)
                ->where('user_id', $user_id);
            $order_info = $this->baseRepository->getToArrayFirst($order_info);

            //获取支付方式信息
            $where = [
                'pay_id' => $order_info['pay_id']
            ];
            $payment_info = $this->paymentService->getPaymentInfo($where);

            $payment_info['pay_name'] = addslashes($payment_info['pay_name']);
            $payment_info['pay_code'] = addslashes($payment_info['pay_code']);
            $pay_fee = pay_fee($order_info['pay_id'], $order_info['order_amount'], 0); //获取手续费
            //数组处理
            $order['order_amount'] = $order_info['order_amount'] + $pay_fee;
            $order['pay_name'] = $payment_info['pay_name'];
            $order['pay_fee'] = $pay_fee;
            $order['user_id'] = $order_info['user_id'];

            $log_id = PayLog::where('order_id', $order_id)
                ->where('order_type', PAY_WHOLESALE)
                ->value('log_id');
            $order['log_id'] = $log_id;
            $order['order_sn'] = $order_info['order_sn'];

            //子订单数量
            $child_order_info = WholesaleOrderInfo::where('main_order_id', $order_id);
            $child_order_info = $this->baseRepository->getToArrayGet($child_order_info);

            $child_num = count($child_order_info);
            if ($order_info['pay_status'] != 2) {
                if ($payment_info['pay_code'] == 'balance') {
                    //查询出当前用户的剩余余额;
                    $user_money = Users::where('user_id', $user_id)->value('user_money');
                    $user_money = $user_money ? $user_money : 0;

                    //如果用户余额足够支付订单;
                    if ($user_money > $order['order_amount']) {
                        $time = $this->timeRepository->getGmTime();

                        /* 修改申请的支付状态 */
                        WholesaleOrderInfo::where('order_id', $order_id)->update([
                            'pay_status' => PS_PAYED,
                            'pay_time' => $time
                        ]);

                        /* 修改此次支付操作的状态为已付款 */
                        PayLog::where('order_id', $order_id)
                            ->where('order_type', PAY_WHOLESALE)
                            ->update([
                                'is_paid' => 1
                            ]);

                        log_account_change($order['user_id'], $order['order_amount'] * (-1), 0, 0, 0, sprintf($GLOBALS['_LANG']['pay_who_order'], $order_info['order_sn']));

                        //修改子订单状态为已付款
                        if ($child_num > 0) {
                            $order_res = WholesaleOrderInfo::where('main_order_id', $order_id);
                            $order_res = $this->baseRepository->getToArrayGet($order_res);

                            if ($order_res) {
                                foreach ($order_res as $row) {
                                    /* 修改此次支付操作子订单的状态为已付款 */
                                    PayLog::where('order_id', $row['order_id'])
                                        ->where('order_type', PAY_WHOLESALE)
                                        ->update([
                                            'is_paid' => 1
                                        ]);

                                    $child_pay_fee = order_pay_fee($row['pay_id'], $row['order_amount']); //获取支付费用
                                    //修改子订单支付状态
                                    WholesaleOrderInfo::where('order_id', $row['order_id'])
                                        ->update([
                                            'pay_status' => 2,
                                            'pay_time' => $time,
                                            'pay_fee' => $child_pay_fee
                                        ]);

                                    $suppliers = Suppliers::where('suppliers_id', $row['suppliers_id']);
                                    $suppliers = $this->baseRepository->getToArrayFirst($suppliers);

                                    if ($suppliers && $GLOBALS['_CFG']['sms_order_payed'] == '1' && $suppliers['mobile_phone'] != '') {
                                        $shop_name = $this->merchantCommonService->getShopName($suppliers['user_id'], 1);
                                        $order_region = $this->wholesaleService->getFlowWholesaleUserRegion($row['order_id']);

                                        //普通订单->短信接口参数
                                        $smsParams = array(
                                            'shop_name' => $shop_name,
                                            'shopname' => $shop_name,
                                            'order_sn' => $row['order_sn'],
                                            'ordersn' => $row['order_sn'],
                                            'consignee' => $row['consignee'],
                                            'order_region' => $order_region,
                                            'orderregion' => $order_region,
                                            'address' => $row['address'],
                                            'order_mobile' => $row['mobile'],
                                            'ordermobile' => $row['mobile'],
                                            'mobile_phone' => $suppliers['mobile_phone'],
                                            'mobilephone' => $suppliers['mobile_phone']
                                        );

                                        $this->commonRepository->smsSend($suppliers['mobile_phone'], $smsParams, 'sms_order_payed');
                                    }
                                }
                            }
                        }
                        //如果使用库存，且下订单付款时减库存，则减少库存
                        if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PAID) {
                            suppliers_change_order_goods_storage($order_id, true, SDT_PAID);
                        }

                        $this->smarty->assign('is_pay', 1);
                    } else {
                        return show_message(lang('wholesale_flow.not_sufficient_funds'), lang('wholesale_flow.back_cart'), 'wholesale_flow.php?step=cart', 'info');
                    }
                } else {
                    $pay_online = '';
                    if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                        /* 取得在线支付方式的支付按钮 */
                        $pay_name = Str::studly($payment_info['pay_code']);
                        $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                        if (!is_null($pay_obj)) {
                            $pay_online = $pay_obj->get_code($order, unserialize_config($payment_info['pay_config']));
                        }
                    }

                    $payment_info['pay_button'] = $pay_online;
                }
            } else {
                $this->smarty->assign('is_pay', 1);
            }

            //获取支付方式
            // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
            $payment_list = available_payment_list(1);

            if (isset($payment_list)) {
                foreach ($payment_list as $key => $payment) {
                    //pc端去除ecjia的支付方式
                    if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                        unset($payment_list[$key]);
                        continue;
                    }

                    if ($payment['is_cod'] == '1') {
                        $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
                    }
                    /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
                    if ($payment['pay_code'] == 'yeepayszx') {
                        unset($payment_list[$key]);
                    }

                    if ($payment['pay_code'] == 'alipay_wap') {
                        unset($payment_list[$key]);
                    }

                    /* 如果有余额支付 */
                    if ($payment['pay_code'] == 'balance') {
                        /* 如果未登录，不显示 */
                        if ($user_id == 0) {
                            unset($payment_list[$key]);
                        }
                    }
                    //过滤在现在线支付
                    if ($payment['pay_code'] == 'onlinepay' || $payment['pay_code'] == 'chunsejinrong') {
                        unset($payment_list[$key]);
                    }
                }
            }

            $arr = last_shipping_and_payment();//获取默认的支付方式
            $this->smarty->assign('pay_id', $arr['pay_id']);
            $this->smarty->assign('payment_list', $payment_list);
            $this->smarty->assign('order_id', $order_id);
            $this->smarty->assign('order', $order);
            $this->smarty->assign('payment', $payment_info);
            $this->smarty->assign('child_order_info', $child_order_info);
            $this->smarty->assign('child_num', $child_num);
            $this->smarty->assign('main_order', $order_info);
            $this->smarty->assign('step', $step);
            return $this->smarty->display('wholesale_flow.dwt');
        }

        /*------------------------------------------------------ */
        //-- 删除购物车商品
        /*------------------------------------------------------ */
        elseif ($step == 'remove') {
            $result = array('error' => 0, 'message' => '', 'content' => '');

            $goods_id = (int)request()->input('goods_id', 0);
            if (!empty($goods_id)) {
                $res = WholesaleCart::where('goods_id', $goods_id);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $session_id = $this->sessionRepository->realCartMacIp();
                    $res = $res->where('session_id', $session_id);
                }

                $res->delete();
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 批量删除购物车商品
        /*------------------------------------------------------ */
        elseif ($step == 'batch_remove') {
            $result = array('error' => 0, 'message' => '', 'content' => '');
            $goods_id = (int)request()->input('goods_id', 0);

            if (!empty($goods_id)) {
                $goods_id = $this->baseRepository->getExplode($goods_id);
                $res = WholesaleCart::whereIn('goods_id', $goods_id);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $session_id = $this->sessionRepository->realCartMacIp();
                    $res = $res->where('session_id', $session_id);
                }

                $res->delete();
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 改变支付方式
        /*------------------------------------------------------ */
        elseif ($step == 'change_payment') {
            $result = array('error' => 0, 'message' => '', 'content' => '');
            $order_id = (int)request()->input('order_id', 0);
            $pay_id = (int)request()->input('pay_id', 0);

            if ($order_id && $pay_id) {
                WholesaleOrderInfo::where('order_id', $order_id)
                    ->update([
                        'pay_id' => $pay_id
                    ]);
            } else {
                $result['error'] = 1;
                $result['content'] = lang('wholesale_flow.order_abnormal');
            }

            return response()->json($result);
        } else {

            // 购物车页面
            $goods_id = request()->input('goods_id', 0);
            $rec_ids = addslashes(request()->input('rec_ids', 0));

            $goods_data = $this->cartService->wholesaleCartGoods($goods_id, $rec_ids);
            $this->smarty->assign('goods_data', $goods_data);
            $cart_info = $this->cartService->wholesaleCartInfo($goods_id, $rec_ids);
            $this->smarty->assign('cart_info', $cart_info);
        }

        $history_goods = get_history_goods(0, $warehouse_id, $area_id);
        $this->smarty->assign('history_goods', $history_goods);
        $this->smarty->assign('historyGoods_count', count($history_goods));

        /*
         * 总额
         */
        $total = $this->cartService->wholesaleCartInfo(0, $cart_value);

        /*
         * 当前会员账户金额
         */
        $user_info = Users::where('user_id', $user_id);
        $user_info = $this->baseRepository->getToArrayFirst($user_info);
        $user_info['user_money'] = $user_info['user_money'] ?? 0;

        $total['total_price'] = floatval($total['total_price']);
        $user_info['user_money'] = floatval($user_info['user_money']);
        //获取支付方式
        // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
        $payment_list = available_payment_list(1);

        if (isset($payment_list)) {
            foreach ($payment_list as $key => $payment) {
                //pc端去除ecjia的支付方式
                if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                    unset($payment_list[$key]);
                    continue;
                }

                if ($payment['is_cod'] == '1') {
                    $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
                }
                /* 如果有易宝神州行支付 如果订单金额大于300 则不显示 */
                if ($payment['pay_code'] == 'yeepayszx') {
                    unset($payment_list[$key]);
                }

                if ($payment['pay_code'] == 'alipay_wap') {
                    unset($payment_list[$key]);
                }
                if ($payment['pay_code'] == 'alipay_bank') {
                    unset($payment_list[$key]);
                }
                /* 如果有余额支付 */
                if ($payment['pay_code'] == 'balance') {
                    /* 如果未登录，不显示 */
                    if ($user_id == 0 || $user_info['user_money'] < $total['total_price']) {
                        unset($payment_list[$key]);
                    }
                }
                //过滤在现在线支付
                if ($payment['pay_code'] == 'onlinepay' || $payment['pay_code'] == 'chunsejinrong') {
                    unset($payment_list[$key]);
                }
            }
        }


        $arr = last_shipping_and_payment();//获取默认的支付方式
        $this->smarty->assign('pay_id', $arr['pay_id']);
        $this->smarty->assign('payment_list', $payment_list);

        $this->smarty->assign('currency_format', $GLOBALS['_CFG']['currency_format']);
        $this->smarty->assign('integral_scale', price_format($GLOBALS['_CFG']['integral_scale']));
        $this->smarty->assign('step', $step);
        assign_dynamic('shopping_flow');

        return $this->smarty->display('wholesale_flow.dwt');
    }

    /*------------------------------------------------------ */
    //-- PRIVATE FUNCTION
    /*------------------------------------------------------ */

    /**
     * 重新组合购物流程商品数组
     */
    private function get_new_group_cart_goods($cart_goods_list_new)
    {
        $car_goods = array();
        foreach ($cart_goods_list_new as $key => $goods) {
            foreach ($goods['goods_list'] as $k => $list) {
                $car_goods[] = $list;
            }
        }

        return $car_goods;
    }
}
