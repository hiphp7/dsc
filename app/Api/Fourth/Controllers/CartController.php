<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Cart;
use App\Models\Goods;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\DiscountService;
use App\Services\Cart\CartCommonService;
use App\Services\Cart\CartMobileService;
use App\Services\Coupon\CouponService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsMobileService;
use App\Services\User\UserCommonService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class CartController
 * @package App\Api\Fourth\Controllers
 */
class CartController extends Controller
{
    protected $config;
    protected $cartMobileService;
    protected $discountService;
    protected $couponService;
    protected $goodsCommonService;
    protected $dscRepository;
    protected $userCommonService;
    protected $cartCommonService;

    public function __construct(
        CartMobileService $cartMobileService,
        DiscountService $discountService,
        CouponService $couponService,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService
    )
    {
        $this->cartMobileService = $cartMobileService;
        $this->discountService = $discountService;
        $this->couponService = $couponService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->goodsCommonService = $goodsCommonService;
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
    }

    /**
     * 加入购物车
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function add(Request $request)
    {
        $this->validate($request, [
            'goods_id' => 'required|integer',
            'num' => 'required|integer',
        ]);

        $goods_id = $request->input('goods_id', 0);
        $num = $request->input('num', 1);
        $spec = $request->input('spec');
        $parent_id = $request->input('parent_id', 0);
        $store_id = $request->input('store_id', 0);
        $take_time = $request->input('take_time', '');
        $store_mobile = $request->input('store_mobile', 0);
        $rec_type = $request->input('rec_type', 0);

        if (empty($goods_id) || empty($num)) {
            return $this->failed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
        }

        $data = $this->cartMobileService->addToCartMobile($this->uid, $goods_id, $num, $spec, $parent_id, $this->warehouse_id, $this->area_id, $this->area_city, $store_id, $take_time, $store_mobile, $rec_type);

        return $this->succeed($data);
    }

    /**
     * 加入购物车超值礼包
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addPackage(Request $request)
    {
        $this->validate($request, [
            'package_id' => 'required|integer',
        ]);

        $package_id = $request->input('package_id');
        $number = $request->input('number', 1);

        $result = $this->cartMobileService->addPackageToCartMobile($this->uid, $package_id, $number, $this->warehouse_id, $this->area_id, $this->area_city);

        return $this->succeed($result);
    }

    /**
     * 添加优惠活动（赠品）到购物车
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addGiftCart(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'act_id' => 'required|integer',
            'ru_id' => 'required|integer',
        ]);

        $uid = $this->authorization();   //返回用户ID

        //验证通过
        $args = array_merge($request->all(), ['uid' => $uid]);

        $result = $this->cartMobileService->addGiftCart($args);

        return $this->succeed($result);
    }

    /**
     * 加入购物车
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addEspecially(Request $request)
    {
        $this->validate($request, [
            'goods_id' => 'required|integer',
            'num' => 'required|integer',
        ]);

        $goods_id = $request->input('goods_id');
        $num = $request->input('num', 1);
        $spec = $request->input('spec');
        $store_id = $request->input('store_id', 0);
        $rec_type = $request->input('rec_type', 0);

        $data = $this->cartMobileService->addEspeciallyToCartMobile($this->uid, $goods_id, $num, $spec, $this->warehouse_id, $this->area_id, $this->area_city, $store_id, $rec_type);

        return $this->succeed($data);
    }

    /**
     * 添加套餐组合商品（配件）到购物车（临时）
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addToCartCombo(Request $request)
    {
        $this->validate($request, [
            'goods_id' => 'required|integer',
            'number' => 'required|integer',
        ]);

        $goods_id = $request->input('goods_id', 0); // 当前商品id
        $number = $request->input('number', 1);
        $spec = $request->input('spec');  // 当前商品属性
        $parent_attr = $request->input('parent_attr'); // 主件商品属性
        $parent = $request->input('parent', 0); // 主件商品id
        $group_id = $request->input('group_id', '');
        $add_group = $request->input('add_group', '');
        $fitt_goods = $request->input('fitt_goods', '');

        $spec = !empty($spec) ? explode(',', $spec) : [];
        $args = [
            'goods_id' => $goods_id,
            'number' => $number,
            'spec' => $spec,
            'parent_attr' => $parent_attr,
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'parent' => $parent,
            'group_id' => $group_id,
            'add_group' => $add_group,
        ];

        $result = $this->cartMobileService->addToCartCombo($this->uid, $args);

        if ($result['error'] == 0) {
            $result['group_id'] = $group_id;
            $result['goods_id'] = stripslashes($goods_id);
            $result['content'] = "";
            $result['one_step_buy'] = session('one_step_buy', 0);
            //返回 原价，配件价，库存信息
            $warehouse_area['warehouse_id'] = $this->warehouse_id;
            $warehouse_area['area_id'] = $this->area_id;
            $warehouse_area['area_city'] = $this->area_city;
            $combo_goods_info = get_combo_goods_info($goods_id, $number, $spec, $parent, $warehouse_area);
            $result['fittings_price'] = $combo_goods_info['fittings_price'];
            $result['spec_price'] = $combo_goods_info['spec_price'];
            $result['goods_price'] = $combo_goods_info['goods_price'];
            $result['stock'] = $combo_goods_info['stock'];
            $result['parent'] = $parent;
        } else {
            $result['goods_id'] = stripslashes($goods_id);
            if (is_array($spec)) {
                $result['product_spec'] = implode(',', $spec);
            } else {
                $result['product_spec'] = $spec;
            }
        }

        //查询组合购买商品区间价格 start
        $combo_goods = get_cart_combo_goods_list($goods_id, $parent, $group_id, $this->uid);

        if (!empty($combo_goods)) {
            $result['combo_amount'] = $combo_goods['combo_amount'];
            $result['combo_number'] = $combo_goods['combo_number'];
        }

        $fitt_goods = isset($fitt_goods) ? $fitt_goods : [];

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($this->uid);
        if ($user_rank) {
            $user_rank['discount'] = $user_rank['discount'] / 100;
        } else {
            $user_rank['rank_id'] = 1;
            $user_rank['discount'] = 1;
        }

        $fittings = get_goods_fittings([$parent], $this->warehouse_id, $this->area_id, $this->area_city, $group_id, 1, [], $this->uid, $user_rank);

        if ($fittings) {
            $goods_info = get_goods_fittings_info($parent, $this->warehouse_id, $this->area_id, $this->area_city, $group_id, 0, '', [], $this->uid, $user_rank);

            $fittings = array_merge($goods_info, $fittings);
            $fittings = array_values($fittings);

            $fittings_interval = get_choose_goods_combo_cart($fittings);

            if ($combo_goods['combo_number'] > 0) {
                //配件商品没有属性时
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
        }

        $goodsGroup = explode('_', $group_id);
        $result['groupId'] = $goodsGroup[2];

        $result['fitt_goods'] = $fitt_goods;

        $result['confirm_type'] = !empty($this->config['cart_confirm']) ? $this->config['cart_confirm'] : 2;

        $result['warehouse_id'] = $this->warehouse_id;
        $result['area_id'] = $this->area_id;
        $result['area_city'] = $this->area_city;
        $result['goods_group'] = str_replace("_" . $parent, '', $group_id);

        $result['add_group'] = $add_group;

        return $this->succeed($result);
    }

    /**
     * 删除套餐组合购物车商品（配件）（临时）
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function delInCartCombo(Request $request)
    {
        $this->validate($request, [
            'goods_id' => 'required|integer',
        ]);

        $goods_id = $request->input('goods_id'); // 当前商品id
        $parent = $request->input('parent', 0); // 主件商品id
        $group_id = $request->input('group_id', '');
        $goods_attr = $request->input('goods_attr'); // 主件商品属性

        //更新临时购物车（删除配件）
        $this->cartMobileService->deleteGroupGoods($this->uid, $goods_id, $group_id);

        //统计购物车配件数量
        $rec_count = $this->cartMobileService->countGroupGoods($this->uid, $parent, $group_id);
        if ($rec_count < 1) {
            //更新临时购物车（删除主件）
            $this->cartMobileService->deleteParentGoods($this->uid, $parent, $group_id);
        }

        //查询组合购买商品区间价格 start
        $combo_goods = get_cart_combo_goods_list($goods_id, $parent, $group_id, $this->uid);

        if (empty($combo_goods['shop_price'])) {
            // 最终价格
            $goods_attr = !empty($goods_attr) ? explode(',', $goods_attr) : [];
            $shop_price = app(GoodsMobileService::class)->getFinalPrice($this->uid, $parent, 1, true, $goods_attr, $this->warehouse_id, $this->area_id, $this->area_city);
            $combo_goods['combo_amount'] = price_format($shop_price, false);
        }

        $result['combo_amount'] = $combo_goods['combo_amount'];
        $result['combo_number'] = $combo_goods['combo_number'];

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($this->uid);
        if ($user_rank) {
            $user_rank['discount'] = $user_rank['discount'] / 100;
        } else {
            $user_rank['rank_id'] = 1;
            $user_rank['discount'] = 1;
        }

        //查询组合购买商品区间价格 start
        if ($combo_goods['combo_number'] > 0) {
            $goods_info = get_goods_fittings_info($parent, $this->warehouse_id, $this->area_id, $this->area_city, $group_id, 0, '', [], $this->uid, $user_rank);
            $fittings = get_goods_fittings([$parent], $this->warehouse_id, $this->area_id, $this->area_city, $group_id, 1, [], $this->uid, $user_rank);
        } else {
            $goods_info = get_goods_fittings_info($parent, $this->warehouse_id, $this->area_id, $this->area_city, '', 1, '', [], $this->uid, $user_rank);
            $fittings = get_goods_fittings([$parent], $this->warehouse_id, $this->area_id, $this->area_city, '', 0, [], $this->uid, $user_rank);
        }

        $fittings = array_merge($goods_info, $fittings);
        $fittings = array_values($fittings);

        $fittings_interval = get_choose_goods_combo_cart($fittings);

        if ($combo_goods['combo_number'] > 0) {
            //配件商品没有属性时
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

        $goodsGroup = explode('_', $group_id);
        $result['groupId'] = $goodsGroup[2];
        //查询组合购买商品区间价格 end

        $result['error'] = 0;
        $result['group'] = substr($group_id, 0, strrpos($group_id, "_"));
        $result['parent'] = $parent;

        return $this->succeed($result);
    }

    /**
     * 套餐组合商品（配件）加入购物车 cart 最终
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addToCartGroup(Request $request)
    {
        $this->validate($request, [
            'goods_id' => 'required|integer',
        ]);

        $group_name = $request->input('group_name', '');
        $goods_id = $request->input('goods_id'); // 当前商品id

        $group_id = $group_name . "_" . $goods_id; // group_id

        // 检测是否存在组合套餐
        $rec_count = $this->cartMobileService->countGroupGoods($this->uid, 0, $group_id);

        if ($rec_count) {

            //清空购物车中的原有数据
            $this->cartMobileService->deleteCartGroupGoods($this->uid, $group_id);

            // 插入新的数据
            // 查询临时购物车中的组合套餐
            $cart_lisr = $this->cartMobileService->selectGroupGoods($this->uid, $group_id);
            if ($cart_lisr) {
                foreach ($cart_lisr as $key => $val) {
                    $cart = [];
                    $cart['user_id'] = $val['user_id'];
                    $cart['session_id'] = $val['session_id'];
                    $cart['goods_id'] = $val['goods_id'];
                    $cart['goods_sn'] = $val['goods_sn'];
                    $cart['product_id'] = $val['product_id'];
                    $cart['group_id'] = $val['group_id'];
                    $cart['goods_name'] = $val['goods_name'];
                    $cart['market_price'] = $val['market_price'];
                    $cart['goods_price'] = $val['goods_price'];
                    $cart['goods_number'] = $val['goods_number'];
                    $cart['goods_attr'] = $val['goods_attr'];
                    $cart['is_real'] = $val['is_real'];
                    $cart['extension_code'] = !is_null($val['extension_code']) ? $val['extension_code'] : '';
                    $cart['parent_id'] = $val['parent_id'];
                    $cart['rec_type'] = $val['rec_type'];
                    $cart['is_gift'] = $val['is_gift'];
                    $cart['is_shipping'] = $val['is_shipping'];
                    $cart['can_handsel'] = $val['can_handsel'];
                    $cart['model_attr'] = $val['model_attr'];
                    $cart['goods_attr_id'] = $val['goods_attr_id'];
                    $cart['ru_id'] = $val['ru_id'];
                    $cart['warehouse_id'] = $val['warehouse_id'];
                    $cart['area_id'] = $val['area_id'];
                    $cart['area_city'] = $val['area_city'];
                    $cart['add_time'] = $val['add_time'];

                    Cart::insert($cart);
                }
            }

            //清空套餐临时数据
            $this->cartMobileService->deleteParentGoods($this->uid, 0, $group_id);

            $result['error'] = 1;
            $result['msg'] = lang('cart.add_to_cart');
            return $this->succeed($result);
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('cart.data_null');
            return $this->succeed($result);
        }
    }

    /**
     * 购物车商品列表
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function goodsList(Request $request)
    {
        $arr = [
            'warehouse_id' => $request->input('warehouse_id', 0),
            'area_id' => $request->input('area_id', 0),
            'checked' => $request->input('checked', ''),
            'district_id' => $request->input('district_id', 0),//1.4.2增加，判断门店自提
        ];
        $arr['uid'] = $this->authorization();

        $data = $this->cartMobileService->getGoodsCartListMobile($arr);

        // 对同一商家商品按照活动分组
        $data = $this->cartMobileService->getCartByFavourable($data);

        return $this->succeed($data);
    }

    /**
     * 删除商品
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function deleteCart(Request $request)
    {
        $this->validate($request, [
            'rec_id' => 'required|integer',
        ]);

        $arr = [
            'rec_id' => $request->input('rec_id', 0),
        ];
        $arr['uid'] = $this->uid;

        $data = $this->cartMobileService->deleteCartGoods($arr);

        return $this->succeed($data);
    }

    /**
     * 清空商品
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function clearCart(Request $request)
    {
        $rec_id = $request->input('rec_id', []);

        $data = $this->cartCommonService->clearCart($this->uid, CART_GENERAL_GOODS, $rec_id);
        if ($data > 0) {
            $result['error'] = 0;
            $result['msg'] = lang('cart.delect_cart_success');
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('cart.delect_cart_error');
        }

        return $this->succeed($result);
    }

    /**
     * 购物车总价
     * @param Request $request
     * @return JsonResponse
     */
    public function amount(Request $request)
    {
        $rec_id = $request->input('rec_id', 0);

        $cartWhere = [
            'user_id' => $this->uid,
            'cart_value' => $rec_id
        ];
        $data = $this->cartCommonService->getCartAmount($cartWhere);

        return $this->succeed($data);
    }

    /**
     * 购物车选择商品
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function checked(Request $request)
    {
        $rec_id = $request->input('rec_id', []);  // 所有的选中的购物车rec_id

        // 修改选中状态
        $this->cartMobileService->checked($this->uid, $rec_id);
        $arr = [
            'warehouse_id' => $request->input('warehouse_id'),
            'area_id' => $request->input('area_id'),
            'checked' => $request->input('checked', ''),
            'uid' => $this->uid,
        ];

        $data = $this->cartMobileService->getGoodsCartListMobile($arr);
        // 对同一商家商品按照活动分组
        $cart_fav_box = $this->cartMobileService->getCartByFavourable($data);
        $result['cart_fav_box'] = $cart_fav_box;

        $cart_goods = $this->cartMobileService->getCartGoods($this->uid, CART_GENERAL_GOODS, $rec_id);

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($this->uid);
        /* 计算折扣 */
        $discount = compute_discount(3, $rec_id, 0, 0, $this->uid, $user_rank['rank_id']);
        $fav_amount = $discount['discount'];

        $goods_amount = get_cart_check_goods($cart_goods, $rec_id);

        $save_total_amount = price_format($fav_amount + $goods_amount['save_amount']);
        $result['save_total_amount'] = $save_total_amount;

        //商品阶梯优惠
        $result['dis_amount'] = $goods_amount['save_amount'];


        if ($goods_amount['subtotal_amount'] > 0) {
            if ($goods_amount['subtotal_amount'] >= $fav_amount) {
                $goods_amount['subtotal_amount'] = $goods_amount['subtotal_amount'] - $fav_amount;
            } else {
                $goods_amount['subtotal_amount'] = 0;
            }
        } else {
            $goods_amount['subtotal_amount'] = 0;
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $cbec = app(CrossBorderService::class)->cbecExists();
            if (!empty($cbec)) {
                $result['rate_price'] = $cbec->totalRateMobile($cart_goods);
                $result['rate_formated'] = price_format($result['rate_price']);
                $goods_amount['subtotal_amount'] += $result['rate_price'];
            }
            $result['cross_border'] = true;
        }
        $result['goods_amount'] = $goods_amount['subtotal_amount'];
        $result['goods_amount_formated'] = price_format($goods_amount['subtotal_amount'], false);
        $result['cart_number'] = $goods_amount['subtotal_number'];
        $result['discount'] = $fav_amount + $result['dis_amount'];
        $result['discount_formated'] = price_format($result['discount'], false);


        return $this->succeed($result);
    }

    /**
     * 检查购物车选中商品 更新购物车活动 car_id
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function cartValue(Request $request)
    {
        $rec_id = $request->input('rec_id', []);  // 所有的选中的购物车 rec_id

        $result = ['error' => 0, 'cart_number' => 0, 'goods_amount' => 0, 'rec_id' => $rec_id];

        //商品列表
        $cart_goods = $this->cartMobileService->getCartGoods($this->uid, CART_GENERAL_GOODS, $rec_id);

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($this->uid);
        $user_rank['rank_id'] = $user_rank['rank_id'] ?? 0;

        /* 计算折扣 */
        $discount = compute_discount(3, $rec_id, 0, 0, $this->uid, $user_rank['rank_id']);
        $fav_amount = $discount['discount'];

        $goods_amount = get_cart_check_goods($cart_goods, $rec_id);

        $save_total_amount = price_format($fav_amount + $goods_amount['save_amount']);
        $result['save_total_amount'] = $save_total_amount;

        //商品阶梯优惠
        $result['dis_amount'] = $goods_amount['save_amount'];

        if ($goods_amount['subtotal_amount'] > 0) {
            if ($goods_amount['subtotal_amount'] >= $fav_amount) {
                $goods_amount['subtotal_amount'] = $goods_amount['subtotal_amount'] - $fav_amount;
            } else {
                $goods_amount['subtotal_amount'] = 0;
            }
        } else {
            $goods_amount['subtotal_amount'] = 0;
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $result['cross_border'] = true;

            if ($rec_id) {
                $cbec = app(CrossBorderService::class)->cbecExists();
                if (!empty($cbec)) {
                    $result['rate_price'] = $cbec->totalRateMobile($cart_goods);
                    $result['rate_formated'] = price_format($result['rate_price']);
                    $goods_amount['subtotal_amount'] += $result['rate_price'];
                }
            }
        }

        $result['goods_amount'] = $goods_amount['subtotal_amount'];
        $result['goods_amount_formated'] = price_format($goods_amount['subtotal_amount'], false);
        $result['cart_number'] = $goods_amount['subtotal_number'];
        $result['discount'] = $fav_amount + $result['dis_amount'];
        $result['discount_formated'] = price_format($result['discount'], false);

        return $this->succeed($result);
    }

    /**
     * 购物车更改数量
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'rec_id' => 'required|integer',
            'num' => 'required|integer',
        ]);

        $rec_id = $request->input('rec_id', 0);// 当前点击购物车的id
        $num = $request->input('num', 1);

        if (empty($rec_id)) {
            return $this->failed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
        }

        // 购物商品信息
        $cart_info = $this->cartMobileService->getCartInfo($this->uid, $rec_id);
        $result['error'] = 0;
        //查询：系统启用了库存，检查输入的商品数量是否有效
        if ($this->config['use_storage'] > 0 && $cart_info['extension_code'] != 'package_buy') {
            /* 检查商品单品限购 start */
            $xiangou = $this->cartMobileService->xiangou_checked($cart_info['goods_id'], $num, $this->uid, 1);
            if ($xiangou['error'] == 1) {
                $result['error'] = '1';
                $result['number'] = $xiangou['cart_number'];
                $result['msg'] = sprintf(lang('cart.xiangou_num_beyond'), $xiangou['cart_number']);
                return $this->succeed($result);
            } elseif ($xiangou['error'] == 2) {
                $result['error'] = '1';
                $result['number'] = $xiangou['cart_number'];
                $result['msg'] = sprintf(lang('cart.xiangou_num_beyond_cumulative'), $cart_info['xiangou_num']);
                return $this->succeed($result);
            }
            /* 检查商品单品限购 end */

            // 最小起订量
            if ($cart_info['is_minimum'] == 1) {
                if ($cart_info['minimum'] > $num) {
                    $result['error'] = '1';
                    $result['number'] = $cart_info['minimum'];
                    $result['msg'] = sprintf(lang('cart.is_minimum_number'), $cart_info['minimum']);
                    return $result;
                }
            }

            if (!empty($cart_info['product_id'])) {// 货品
                // 获取货品库存
                $prod = $this->cartMobileService->getProductNumber($cart_info['goods_id'], $cart_info['product_id'], $cart_info['model_attr']);
                $product_number = $prod['product_number'];
                if ($product_number < $num) {
                    $result['error'] = 1;
                    $result['number'] = $product_number;
                    $result['msg'] = sprintf(lang('cart.stock_insufficiency'), $cart_info['goods_name'], $product_number, $product_number);
                    return $this->succeed($result);
                }
            } else {// 普通商品
                if ($cart_info['goods_number'] < $num) {
                    $result['error'] = 1;
                    $result['number'] = $cart_info['goods_number'];
                    $result['msg'] = sprintf(lang('cart.stock_insufficiency'), $cart_info['goods_name'], $cart_info['goods_number'], $cart_info['goods_number']);
                    return $this->succeed($result);
                }
            }
        }

        /* 查询：检查该项是否为基本件 以及是否存在配件 */
        /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id，goods_number为1 */
        $offers_accessories_res = $this->cartMobileService->getCartGroupList($this->uid, $rec_id);

        //订货数量大于0
        if ($num > 0 && isset($cart_info['group_number'])) {
            if ($cart_info['group_number'] > 0 && $num > $cart_info['group_number'] && !empty($cart_info['group_id'])) {
                $result['error'] = 1;
                $result['msg'] = sprintf(lang('cart.group_stock_insufficiency'), $cart_info['goods_name'], $cart_info['goods_number'], $cart_info['goods_number']);
                return $this->succeed($result);
            }
            //主配件更新数量时，子配件也跟着加数量
            if ($offers_accessories_res) {
                for ($i = 0; $i < count($offers_accessories_res); $i++) {
                    $this->cartMobileService->update($num, $offers_accessories_res[$i]['rec_id'], $this->uid);
                }
            }
        }

        // 更新购物车商品数量
        $this->cartMobileService->update($num, $rec_id, $this->uid);

        // 更新购物车优惠活动 start
        $fav = [];
        $is_fav = 0;
        if ($cart_info['act_id'] == 0) {
            if ($cart_info['extension_code'] != 'package_buy' && $cart_info['is_gift'] == 0) {
                $fav = $this->cartMobileService->getFavourable($cart_info['user_id'], $cart_info['goods_id'], $cart_info['ru_id'], 0, true);
                if ($fav) {
                    $is_fav = 1;
                }
            }
        }
        if ($is_fav == 1) {
            $this->cartMobileService->update_cart_goods_fav($cart_info['user_id'], $cart_info['rec_id'], $fav['act_id']);
        }
        // 更新购物车优惠活动 end

        $cart_goods = $this->cartMobileService->getCartGoods($this->uid, CART_GENERAL_GOODS);

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($this->uid);
        /* 计算折扣 */
        $discount = compute_discount(3, '', 0, 0, $this->uid, $user_rank['rank_id']);

        $fav_amount = $discount['discount'];

        $goods_amount = get_cart_check_goods($cart_goods, $rec_id);

        $save_total_amount = price_format($fav_amount + $goods_amount['save_amount']);
        $result['save_total_amount'] = $save_total_amount;

        //商品阶梯优惠
        $result['dis_amount'] = $goods_amount['save_amount'];

        if ($goods_amount['subtotal_amount'] > 0) {
            if ($goods_amount['subtotal_amount'] >= $fav_amount) {
                $goods_amount['subtotal_amount'] = $goods_amount['subtotal_amount'] - $fav_amount;
            } else {
                $goods_amount['subtotal_amount'] = 0;
            }
        } else {
            $goods_amount['subtotal_amount'] = 0;
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $cbec = app(CrossBorderService::class)->cbecExists();
            if (!empty($cbec)) {
                $result['rate_price'] = $cbec->totalRateMobile($cart_goods);
                $result['rate_formated'] = price_format($result['rate_price']);
                $goods_amount['subtotal_amount'] += $result['rate_price'];
            }
            $result['cross_border'] = true;
        }

        $result['goods_amount'] = $goods_amount['subtotal_amount'];
        $result['goods_amount_formated'] = price_format($goods_amount['subtotal_amount'], false);
        $result['cart_number'] = $goods_amount['subtotal_number'];
        $result['discount'] = $fav_amount + $result['dis_amount'];

        $attr_id = empty($cart_info['goods_attr_id']) ? [] : explode(',', $cart_info['goods_attr_id']);
        $result['goods_price'] = $this->goodsCommonService->getFinalPrice($cart_info['goods_id'], $num, true, $attr_id, $cart_info['warehouse_id'], $cart_info['area_id'], $cart_info['area_city']);
        $result['goods_price_formated'] = price_format($result['goods_price'], false);
        $result['discount_formated'] = price_format($result['discount'], false);

        // 套餐组合
        $result['group'] = [];
        if ($cart_goods) {
            foreach ($cart_goods as $goods) {
                if ($goods['rec_id'] == $rec_id) {
                    $result['rec_goods'] = $goods['goods_id'];
                    break;
                }
            }
            foreach ($cart_goods as $goods) {
                if (isset($result['rec_goods']) && $goods['parent_id'] > 0 && $result['rec_goods'] == $goods['parent_id']) {
                    if ($goods['rec_id'] != $rec_id) {
                        $result['group'][$goods['rec_id']]['rec_group'] = $goods['group_id'] . "_" . $goods['rec_id'];
                        $result['group'][$goods['rec_id']]['rec_group_number'] = $goods['goods_number'];
                        $result['group'][$goods['rec_id']]['rec_group_talId'] = $goods['group_id'] . "_" . $goods['rec_id'] . "_subtotal";
                        $result['group'][$goods['rec_id']]['rec_group_subtotal'] = price_format($goods['subtotal'], false);
                    }
                }
            }
        }

        if ($result['group']) {
            $result['group'] = array_values($result['group']);
        }

        // 优惠活动
        if ($cart_info['act_id']) {
            $sel_flag = $request->input('sel_flag', 'cart_sel_flag');  // 标志flag

            // 获取活动购物车id
            $cat_id = $this->cartMobileService->getCartRecId($this->uid, $cart_info['act_id'], $cart_info['ru_id']);

            $act_sel = ['act_sel_id' => $cat_id, 'act_sel' => $sel_flag, 'from' => 'mobile'];
            $is_gift = Cart::where('user_id', $this->uid)->where('is_gift', $cart_info['act_id'])->count();
            // 当优惠活动商品不满足最低金额时-删除赠品
            $favourable = favourable_info($cart_info['act_id']);
            $favourable_available = favourable_available($favourable, $act_sel, -1, $this->uid, $user_rank['rank_id']);
            // 取消商品 删除活动对应的赠品
            $is_delete_gift = 0;
            if ($is_gift && !$favourable_available) {
                Cart::where('user_id', $this->uid)->where('is_gift', $cart_info['act_id'])->delete();
                $is_delete_gift = 1;
            }

            // 局部更新优惠活动
            $cart_fav_box = cart_favourable_box($cart_info['act_id'], $act_sel, $this->uid, $user_rank['rank_id'], $this->warehouse_id, $this->area_id, $this->area_city);

            if (isset($cart_fav_box['act_goods_list']) && !empty($cart_fav_box['act_goods_list'])) {
                $cart_fav_box['act_goods_list'] = collect($cart_fav_box['act_goods_list'])->values()->all();
            } else {
                $cart_fav_box['act_goods_list'] = [];
            }

            if (isset($cart_fav_box['act_cart_gift']) && !empty($cart_fav_box['act_cart_gift'])) {
                $cart_fav_box['act_cart_gift'] = collect($cart_fav_box['act_cart_gift'])->values()->all();
            } else {
                $cart_fav_box['act_cart_gift'] = [];
            }

            $result['cart_fav_box'] = $cart_fav_box;
            $result['is_gift'] = $is_gift;
            $result['is_delete_gift'] = $is_delete_gift;
        }

        return $this->succeed($result);
    }

    /**
     * 优惠活动（赠品）列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function giftList(Request $request)
    {
        $this->validate($request, [
            'act_id' => 'required|integer',
        ]);

        $act_id = $request->input('act_id');     // 活动id

        $favourable = $this->discountService->activityDetail($act_id);

        if (isset($favourable['gift']) && $favourable['gift']) {
            foreach ($favourable['gift'] as $key => $value) {

                $goodsCount = Goods::where('goods_id', $value['id'])->count();

                $cart_gift_num = $this->cartMobileService->goodsNumInCartGift($this->uid, $value['id']);//赠品在购物车数量
                if ($goodsCount > 0) {
                    $favourable['gift'][$key]['is_checked'] = $cart_gift_num ? true : false;
                } else {
                    unset($favourable['gift'][$key]);
                }
            }
        } else {
            $favourable['gift'] = [];
        }

        $result['giftlist'] = $favourable['gift'];
        $result['act_type_ext'] = $favourable['act_type_ext'];

        return $this->succeed($result);
    }

    /**
     * 购物车选择促销活动
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getFavourable(Request $request)
    {
        $goods_id = $request->input('goods_id', 0); // 商品id
        $ru_id = $request->input('ru_id', 0);    // 商家id
        $act_id = $request->input('act_id', 0);     // 当前活动id
        $rec_id = $request->input('rec_id', 0);     //当前购物车的id

        if (empty($rec_id)) {
            return $this->failed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
        }

        $user_id = $this->authorization();
        $favourable = $this->cartMobileService->getFavourable($user_id, $goods_id, $ru_id, $act_id);
        if ($favourable) {
            foreach ($favourable as $key => $val) {
                $favourable[$key]['rec_id'] = $rec_id;
            }
        }
        $result = [
            'error' => 0,
            'goods_id' => $goods_id,
            'rec_id' => $rec_id,
            'favourable' => $favourable
        ];

        return $this->succeed($result);
    }

    /**
     * 购物车切换可选促销
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function changefav(Request $request)
    {
        $result['error'] = 0;

        $act_id = $request->input('act_id', 0);     // 活动id
        $rec_id = $request->input('rec_id', 0);     // 当前购物车的id

        if (empty($rec_id)) {
            return $this->failed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
        }

        $user_id = $this->authorization();

        // 删除活动对应的赠品
        $old_act_id = Cart::where('user_id', $user_id)->where('rec_id', $rec_id)->value('act_id');
        if ($old_act_id) {
            Cart::where('user_id', $user_id)->where('is_gift', $old_act_id)->delete();
        }

        // 更新购物车优惠活动
        $this->cartMobileService->update_cart_goods_fav($user_id, $rec_id, $act_id);

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($user_id);
        if ($act_id) {
            $sel_flag = $request->input('sel_flag', 'cart_sel_flag');  // 标志flag
            $act_sel = ['act_sel_id' => $rec_id, 'act_sel' => $sel_flag, 'from' => 'mobile'];
            $is_gift = Cart::where('user_id', $user_id)->where('is_gift', $act_id)->count();
            // 取消商品 删除活动对应的赠品
            $is_delete_gift = 0;
            if ($is_gift) {
                Cart::where('user_id', $user_id)->where('is_gift', $act_id)->delete();
                $is_delete_gift = 1;
            }

            // 局部更新优惠活动
            $cart_fav_box = cart_favourable_box($act_id, $act_sel, $user_id, $user_rank['rank_id'], $this->warehouse_id, $this->area_id, $this->area_city);

            $result['cart_fav_box'] = $cart_fav_box;
            $result['is_gift'] = $is_gift;
            $result['is_delete_gift'] = $is_delete_gift;
        }
        $result['msg'] = lang('cart.change_favourable_success');

        return $this->succeed($result);
    }

    /**
     * 购物车领取优惠券列表
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getCoupons(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->couponService->getCouponsList($user_id, $ru_id);

        return $this->succeed($data);
    }

    /**
     * 购物车商品数量
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function cartNum(){

        $num = $this->cartMobileService->cartNum($this->uid);

        return $this->succeed($num);
    }
}
