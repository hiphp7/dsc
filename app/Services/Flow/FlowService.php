<?php

namespace App\Services\Flow;

use App\Models\Cart;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Category\CategoryService;

/**
 * 商城会员
 * Class User
 * @package App\Services
 */
class FlowService
{
    protected $categoryService;
    protected $config;
    protected $sessionRepository;
    protected $dscRepository;

    public function __construct(
        CategoryService $categoryService,
        SessionRepository $sessionRepository,
        DscRepository $dscRepository
    )
    {
        $this->categoryService = $categoryService;
        $this->sessionRepository = $sessionRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 添加优惠活动（赠品）到购物车
     *
     * @param int $act_id 优惠活动id
     * @param int $id 赠品id
     * @param int $price 赠品价格
     */
    public function getAddGiftToCart($act_id = 0, $id = 0, $price = 0)
    {
        $user_id = session('user_id', 0);
        $session_id = $this->sessionRepository->realCartMacIp();

        $goods = Goods::where('goods_id', $id)->first();
        $goods = $goods ? $goods->toArray() : [];

        if ($goods) {
            $other = [
                'user_id' => $user_id,
                'goods_id' => $goods['goods_id'],
                'goods_sn' => $goods['goods_sn'],
                'goods_name' => $goods['goods_name'],
                'market_price' => $goods['market_price'],
                'goods_price' => $price,
                'goods_number' => 1,
                'is_real' => $goods['is_real'],
                'extension_code' => $goods['extension_code'],
                'parent_id' => 0,
                'is_gift' => $act_id,
                'rec_type' => CART_GENERAL_GOODS,
                'ru_id' => $goods['user_id']
            ];

            if (empty($user_id)) {
                $other['session_id'] = $session_id;
            } else {
                $other['session_id'] = '';
            }

            Cart::insert($other);
        }
    }

    /**
     * 添加优惠活动（非赠品）到购物车
     *
     * @param int $act_id 优惠活动id
     * @param string $act_name 优惠活动name
     * @param int $amount 优惠金额
     */
    public function getAddFavourableToCart($act_id = 0, $act_name = '', $amount = 0)
    {
        $user_id = session('user_id', 0);
        $session_id = $this->sessionRepository->realCartMacIp();

        $other = [
            'user_id' => $user_id,
            'goods_id' => 0,
            'goods_name' => $act_name,
            'goods_price' => (-1) * $amount,
            'goods_number' => 1,
            'is_real' => 0,
            'parent_id' => 0,
            'is_gift' => $act_id,
            'rec_type' => CART_GENERAL_GOODS
        ];

        if (empty($user_id)) {
            $other['session_id'] = $session_id;
        } else {
            $other['session_id'] = '';
        }

        Cart::insert($other);
    }

    /**
     * 获取购物车中同一活动下的商品和赠品
     *
     * @param $favourable_id 优惠活动id
     * @param array $act_sel_id 活动中选中的rec_id
     * @param int $ru_id 店铺ID
     * @return array
     * @throws \Exception
     */
    public function getCartAddFavourableBox($favourable_id, $act_sel_id = [], $ru_id = 0)
    {
        $fav_res = favourable_list(session('user_rank'), -1, $favourable_id, $act_sel_id);
        $favourable_activity = $fav_res[0];

        $cart_goods = get_cart_goods('', 1);
        $merchant_goods = $cart_goods['goods_list'];

        $favourable_box = [];

        if ($cart_goods['total']['goods_price']) {
            $favourable_box['goods_amount'] = $cart_goods['total']['goods_price'];
        }

        $list_array = [];
        foreach ($merchant_goods as $key => $row) { // 第一层 遍历商家
            $user_cart_goods = $row['goods_list'];
            if ($row['ru_id'] == $ru_id) { //判断是否商家活动
                foreach ($user_cart_goods as $key1 => $row1) { // 第二层 遍历购物车中商家的商品
                    $row1['original_price'] = $row1['goods_price'] * $row1['goods_number'];
                    if (!empty($act_sel_id)) { // 用来判断同一个优惠活动前面是否全部不选
                        $row1['sel_checked'] = strstr(',' . $act_sel_id['act_sel_id'] . ',', ',' . $row1['rec_id'] . ',') ? 1 : 0; // 选中为1
                    }
                    // 活动-全部商品
                    if ($favourable_activity['act_range'] == 0 && $row1['extension_code'] != 'package_buy') {
                        if ($row1['is_gift'] == FAR_ALL) { // 活动商品
                            if ($row1 && ($favourable_activity['act_id'] == $row1['act_id'])) {
                                $favourable_box['act_id'] = $favourable_activity['act_id'];
                                $favourable_box['act_name'] = $favourable_activity['act_name'];
                                $favourable_box['act_type'] = $favourable_activity['act_type'];
                                // 活动类型
                                switch ($favourable_activity['act_type']) {
                                    case 0:
                                        $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['With_a_gift'];
                                        $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                        break;
                                    case 1:
                                        $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['Full_reduction'];
                                        $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                        break;
                                    case 2:
                                        $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['discount'];
                                        $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                        break;

                                    default:
                                        break;
                                }
                                $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id);
                                $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id); // 购物车满足活动最低金额
                                // 购物车中已选活动赠品数量
                                $cart_favourable = cart_favourable($row['ru_id']);
                                $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                                $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                // 活动赠品
                                if ($favourable_activity['gift']) {
                                    $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                }

                                $row1['favourable_list'] = get_favourable_info($row1['goods_id'], $row1['ru_id'], $row1);

                                // new_list->活动id->act_goods_list
                                $favourable_box['act_goods_list'][$row1['rec_id']] = $row1;
                                unset($row1);
                            }
                        } else { // 赠品
                            $favourable_box['act_cart_gift'][$row1['rec_id']] = $row1;
                        }
                        continue; // 如果活动包含全部商品，跳出循环体
                    }

                    // 活动-分类
                    if ($favourable_activity['act_range'] == FAR_CATEGORY && $row1['extension_code'] != 'package_buy') {
                        // 优惠活动关联的 分类集合
                        $get_act_range_ext = get_act_range_ext(session('user_rank'), $row['ru_id'], 1); // 1表示优惠范围 按分类

                        $str_cat = '';
                        foreach ($get_act_range_ext as $id) {

                            /**
                             * 当前分类下的所有子分类
                             * 返回一维数组
                             */
                            $cat_keys = $this->categoryService->getArrayKeysCat(intval($id));

                            if ($cat_keys) {
                                $str_cat .= implode(",", $cat_keys);
                            }
                        }

                        if ($str_cat) {
                            $list_array = explode(",", $str_cat);
                        }

                        $list_array = !empty($list_array) ? array_merge($get_act_range_ext, $list_array) : $get_act_range_ext;
                        $id_list = arr_foreach($list_array);
                        $id_list = array_unique($id_list);
                        $cat_id = $row1['cat_id']; //购物车商品所属分类ID
                        // 判断商品或赠品 是否属于本优惠活动
                        if ((in_array(trim($cat_id), $id_list) && $row1['is_gift'] == 0) || ($row1['is_gift'] == $favourable_activity['act_id'])) {
                            if ($row1) {
                                //优惠活动关联分类集合
                                $fav_act_range_ext = !empty($favourable_activity['act_range_ext']) ? explode(',', $favourable_activity['act_range_ext']) : [];

                                // 此 优惠活动所有分类
                                foreach ($fav_act_range_ext as $id) {
                                    /**
                                     * 当前分类下的所有子分类
                                     * 返回一维数组
                                     */
                                    $cat_keys = $this->categoryService->getArrayKeysCat(intval($id));
                                    $fav_act_range_ext = array_merge($fav_act_range_ext, $cat_keys);
                                }

                                if ($row1['is_gift'] == 0 && in_array($cat_id, $fav_act_range_ext) && ($favourable_activity['act_id'] == $row1['act_id'])) { // 活动商品
                                    $favourable_box['act_id'] = $favourable_activity['act_id'];
                                    $favourable_box['act_name'] = $favourable_activity['act_name'];
                                    $favourable_box['act_type'] = $favourable_activity['act_type'];
                                    // 活动类型
                                    switch ($favourable_activity['act_type']) {
                                        case 0:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['With_a_gift'];
                                            $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                            break;
                                        case 1:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['Full_reduction'];
                                            $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                            break;
                                        case 2:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['discount'];
                                            $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                            break;

                                        default:
                                            break;
                                    }
                                    $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                    $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                    $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id);
                                    $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id); // 购物车满足活动最低金额
                                    // 购物车中已选活动赠品数量
                                    $cart_favourable = cart_favourable($row['ru_id']);
                                    $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                    $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                                    $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                    // 活动赠品
                                    if ($favourable_activity['gift']) {
                                        $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                    }

                                    $row1['favourable_list'] = get_favourable_info($row1['goods_id'], $row1['ru_id'], $row1);

                                    // new_list->活动id->act_goods_list
                                    $favourable_box['act_goods_list'][$row1['rec_id']] = $row1;

                                    $favourable_box['act_goods_list_num'] = count($favourable_box['act_goods_list']);
                                }

                                if ($row1['is_gift'] == $favourable_activity['act_id']) { // 赠品
                                    $favourable_box['act_cart_gift'][$row1['rec_id']] = $row1;
                                }

                                unset($row1);
                            }

                            continue;
                        }
                    }

                    // 活动-品牌
                    if ($favourable_activity['act_range'] == FAR_BRAND && $row1['extension_code'] != 'package_buy') {
                        // 优惠活动 品牌集合
                        $get_act_range_ext = get_act_range_ext(session('user_rank'), $row['ru_id'], 2); // 2表示优惠范围 按品牌
                        $brand_id = $row1['brand_id'];

                        // 是品牌活动的商品或者赠品
                        if ((in_array(trim($brand_id), $get_act_range_ext) && $row1['is_gift'] == 0) || ($row1['is_gift'] == $favourable_activity['act_id'])) {
                            if ($row1) {
                                $act_range_ext_str = ',' . $favourable_activity['act_range_ext'] . ',';
                                $brand_id_str = ',' . $brand_id . ',';
                                if ($row1['is_gift'] == 0 && strstr($act_range_ext_str, trim($brand_id_str)) && ($favourable_activity['act_id'] == $row1['act_id'])) { // 活动商品
                                    $favourable_box['act_id'] = $favourable_activity['act_id'];
                                    $favourable_box['act_name'] = $favourable_activity['act_name'];
                                    $favourable_box['act_type'] = $favourable_activity['act_type'];
                                    // 活动类型
                                    switch ($favourable_activity['act_type']) {
                                        case 0:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['With_a_gift'];
                                            $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                            break;
                                        case 1:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['Full_reduction'];
                                            $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                            break;
                                        case 2:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['discount'];
                                            $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                            break;

                                        default:
                                            break;
                                    }
                                    $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                    $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                    $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id);
                                    $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id); // 购物车满足活动最低金额
                                    // 购物车中已选活动赠品数量
                                    $cart_favourable = cart_favourable();
                                    $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                    $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                                    $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                    // 活动赠品
                                    if ($favourable_activity['gift']) {
                                        $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                    }

                                    $row1['favourable_list'] = get_favourable_info($row1['goods_id'], $row1['ru_id'], $row1);

                                    // new_list->活动id->act_goods_list
                                    $favourable_box['act_goods_list'][$row1['rec_id']] = $row1;
                                }
                                if ($row1['is_gift'] == $favourable_activity['act_id']) { // 赠品
                                    $favourable_box['act_cart_gift'][$row1['rec_id']] = $row1;
                                }

                                unset($row1);
                            }

                            continue;
                        }
                    }

                    // 活动-部分商品
                    if ($favourable_activity['act_range'] == FAR_GOODS && $row1['extension_code'] != 'package_buy') {
                        $get_act_range_ext = get_act_range_ext(session('user_rank'), $row['ru_id'], 3); // 3表示优惠范围 按商品
                        // 判断购物商品是否参加了活动  或者  该商品是赠品
                        if (in_array($row1['goods_id'], $get_act_range_ext) || ($row1['is_gift'] == $favourable_activity['act_id'])) {
                            if ($row1) {
                                $act_range_ext_str = ',' . $favourable_activity['act_range_ext'] . ','; // 优惠活动中的优惠商品
                                $goods_id_str = ',' . $row1['goods_id'] . ',';
                                // 如果是活动商品
                                if (strstr($act_range_ext_str, trim($goods_id_str)) && ($row1['is_gift'] == 0) && ($favourable_activity['act_id'] == $row1['act_id'])) {
                                    $favourable_box['act_id'] = $favourable_activity['act_id'];
                                    $favourable_box['act_name'] = $favourable_activity['act_name'];
                                    $favourable_box['act_type'] = $favourable_activity['act_type'];
                                    // 活动类型
                                    switch ($favourable_activity['act_type']) {
                                        case 0:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['With_a_gift'];
                                            $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                            break;
                                        case 1:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['Full_reduction'];
                                            $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                            break;
                                        case 2:
                                            $favourable_box['act_type_txt'] = $GLOBALS['_LANG']['discount'];
                                            $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                            break;

                                        default:
                                            break;
                                    }
                                    $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                    $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                    $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id);
                                    $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id); // 购物车满足活动最低金额
                                    // 购物车中已选活动赠品数量
                                    $cart_favourable = cart_favourable($row['ru_id']);
                                    $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                    $favourable_box['favourable_used'] = favourable_used($favourable_box, $cart_favourable);
                                    $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                    // 活动赠品
                                    if ($favourable_activity['gift']) {
                                        $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                    }

                                    $row1['favourable_list'] = get_favourable_info($row1['goods_id'], $row1['ru_id'], $row1);

                                    // new_list->活动id->act_goods_list
                                    $favourable_box['act_goods_list'][$row1['rec_id']] = $row1;
                                }
                                // 如果是赠品
                                if ($row1['is_gift'] == $favourable_activity['act_id']) {
                                    $favourable_box['act_cart_gift'][$row1['rec_id']] = $row1;
                                }

                                unset($row1);
                            }
                        }
                    } else {
                        // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                        $favourable_box[$row1['rec_id']] = $row1;
                    }
                }
            }
        }

        return $favourable_box;
    }

    /**
     * 判断购物车商品是否存在
     */
    public function getIsCartGoods($user_id = 0, $cart_value = '')
    {
        $res = OrderGoods::where('user_id', $user_id)->where('order_id', 0);

        if ($cart_value) {
            $rec_list = explode(",", $cart_value);
            foreach ($rec_list as $key => $val) {
                $res = $res->whereRaw("FIND_IN_SET('$val', cart_recid)");
            }
        }

        $count = $res->count();

        return $count;
    }

    /* 订单商品 */
    public function getOrderCartValue($user_id, $cart_value = '')
    {
        $res = OrderGoods::select('order_id')->where('user_id', $user_id)->where('order_id', 0);

        if ($cart_value) {
            $rec_list = explode(",", $cart_value);
            foreach ($rec_list as $key => $val) {
                $res = $res->whereRaw("FIND_IN_SET('$val', cart_recid)");
            }
        }

        $res = $res->whereHas('getOrder', function ($query) {
            $query->where('main_count', 0);
        });

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     * 删除订单里面重复商品
     */
    public function getDelUniqueGoods($order_id = 0)
    {
        $res = OrderGoods::selectRaw("GROUP_CONCAT(cart_recid) AS cart_recid")->where('order_id', $order_id)->first();
        $res = $res ? $res->toArray() : [];
        $order_list = $res ? $res['cart_recid'] : '';

        if ($order_list) {
            $order_list = explode(",", $order_list);
            $unique_values = array_count_values($order_list);

            foreach ($unique_values as $key => $row) {
                if ($row > 1) {
                    $num = $row - 1;

                    OrderGoods::where('order_id', $order_id)->where('cart_recid', $key)->orderBy('rec_id', 'desc')->take($num)->delete();
                }
            }
        }
    }

    public function get_order_post_postscript($postscript, $ru_id = [])
    {
        $postscript_value = '';
        if ($postscript) {
            foreach ($ru_id as $k => $v) {
                $postscript_value .= $v . "|" . $postscript . ",";  //商家ID + 留言内容
            }

            $postscript_value = substr($postscript_value, 0, -1);
        }
        return $postscript_value;
    }
}
