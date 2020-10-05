<?php

namespace App\Services\Activity;

use App\Models\CollectStore;
use App\Models\Coupons;
use App\Models\CouponsRegion;
use App\Models\CouponsUser;
use App\Models\Goods;
use App\Models\OrderInfo;
use App\Models\Region;
use App\Models\UserRank;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 活动 ->【优惠券】
 */
class CouponsService
{
    protected $categoryService;
    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        CategoryService $categoryService,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 格式化优惠券数据(注册送、购物送除外)
     *
     * @param array $cou_data
     * @param int $user_id
     * @return array
     * @throws \Exception
     */
    public function getFromatCoupons($cou_data = [], $user_id = 0)
    {

        //当前时间;
        $time = $this->timeRepository->getGmTime();

        //优化数据;
        foreach ($cou_data as $k => $v) {

            //优惠券剩余量
            if (!isset($v['cou_surplus'])) {
                $cou_data[$k]['cou_surplus'] = 100;
            }

            //可使用优惠券的商品; bylu
            if (!empty($v['cou_goods'])) {
                $v['cou_goods'] = $this->dscRepository->delStrComma($v['cou_goods']);

                $v['cou_goods'] = !is_array($v['cou_goods']) ? explode(",", $v['cou_goods']) : $v['cou_goods'];

                $cou_goods_arr = Goods::select(['goods_id', 'goods_name', 'goods_thumb'])
                    ->whereIn('goods_id', $v['cou_goods'])->get();

                $cou_goods_arr = $cou_goods_arr ? $cou_goods_arr->toArray() : [];

                if (!empty($cou_goods_arr)) {
                    foreach ($cou_goods_arr as $g_key => $g_val) {
                        if ($g_val['goods_thumb']) {
                            $cou_goods_arr[$g_key]['goods_thumb'] = $this->dscRepository->getImagePath($g_val['goods_thumb']);
                        }
                    }
                }

                $cou_data[$k]['cou_goods_name'] = $cou_goods_arr;
            }

            //可领券的会员等级;
            if (!empty($v['cou_ok_user'])) {
                $v['cou_ok_user'] = !is_array($v['cou_ok_user']) ? explode(",", $v['cou_ok_user']) : $v['cou_ok_user'];

                $name = UserRank::selectRaw('GROUP_CONCAT(rank_name) AS rank_name')->whereIn('rank_id', $v['cou_ok_user'])->first();

                $name = $name ? $name->toArray() : [];

                $cou_data[$k]['cou_ok_user_name'] = $name ? $name['rank_name'] : '';
            }

            //可使用的店铺;
            $cou_data[$k]['store_name'] = sprintf($GLOBALS['_LANG']['use_limit'], $this->merchantCommonService->getShopName($v['ru_id'], 1));


            //时间戳转时间;
            $cou_data[$k]['cou_start_time_format'] = $this->timeRepository->getLocalDate('Y/m/d', $v['cou_start_time']);
            $cou_data[$k]['cou_end_time_format'] = $this->timeRepository->getLocalDate('Y/m/d', $v['cou_end_time']);

            //判断是否已过期;
            if ($v['cou_end_time'] < $time) {
                $cou_data[$k]['is_overdue'] = 1;
            } else {
                $cou_data[$k]['is_overdue'] = 0;
            }

            if (!empty($v['cou_goods'])) {
                $goodstype = lang('common.spec_goods');
            } elseif (!empty($v['spec_cat'])) {
                $goodstype = lang('common.spec_cat');
            } else {
                $goodstype = lang('common.all_goods');
            }

            if ($v['cou_type'] == VOUCHER_ALL) {
                $cou_type_name = lang('coupons.vouchers_all');
            } elseif ($v['cou_type'] == VOUCHER_USER) {
                $cou_type_name = lang('coupons.vouchers_user');
            } elseif ($v['cou_type'] == VOUCHER_SHIPPING) {
                $cou_type_name = lang('coupons.vouchers_shipping');
            } else {
                $cou_type_name = lang('coupons.unknown');
            }

            //优惠券种类;
            $cou_data[$k]['cou_type_name'] = $cou_type_name . "[$goodstype]";

            //是否已经领取过了
            if ($user_id > 0) {
                $count = CouponsUser::where('cou_id', $v['cou_id'])->where('user_id', $user_id)->count();

                if ($v['cou_user_num'] <= $count) {
                    $cou_data[$k]['cou_is_receive'] = 1;
                } else {
                    $cou_data[$k]['cou_is_receive'] = 0;
                }
            }
        }

        return $cou_data;
    }

    /**
     * //取出各条优惠券剩余总数(注册送、购物送除外)
     * @param array $cou_type
     * @return  array
     */
    public function getCouponsSurplus($cou_type = [], $num = 0)
    {

        //当前时间;
        $time = $this->timeRepository->getGmTime();

        $res = Coupons::whereNotIn('cou_type', $cou_type)
            ->where('review_status', 3)
            ->where('cou_end_time', '>', $time);

        $res = $res->withCount('getCouponsUser as use_num');

        $res = $res->with([
            'getCouponsUser' => function ($query) {
                $query->select('uc_id', 'cou_id');
            }
        ]);

        if ($num) {
            $res = $res->take($num);
        }

        $res = $res->orderBy('cou_id', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $row = $this->baseRepository->getArrayMerge($row, $row['get_coupons_user']);

                $res[$key] = $row;
                $res[$key]['cou_surplus'] = ($row['cou_total'] > $row['use_num']) ? floor(($row['cou_total'] - $row['use_num']) / $row['cou_total'] * 100) : 0;
            }
        }

        return $res;
    }

    /**
     * //取出各条优惠券剩余总数(注册送、购物送除外)
     * @param array $cou_type
     * @return  array
     */
    public function getCouponsData($cou_type = [], $num = 0, $cou_surplus = [])
    {
        //当前时间;
        $time = $this->timeRepository->getGmTime();

        $res = Coupons::whereNotIn('cou_type', $cou_type)
            ->where('review_status', 3)
            ->where('cou_end_time', '>', $time);

        $res = $res->with([
            'getCouponsUser' => function ($query) {
                $query->select('uc_id', 'user_id', 'is_use', 'cou_id');
            }
        ]);

        $res = $res->orderBy('cou_id', 'desc');

        if ($num) {
            $res = $res->take($num);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $row = $this->baseRepository->getArrayMerge($row, $row['get_coupons_user']);
                $res[$key] = $row;

                if ($cou_surplus) {
                    //格式化各优惠券剩余总数
                    foreach ($cou_surplus as $m => $n) {
                        if ($row['cou_id'] == $n['cou_id']) {
                            $res[$key]['cou_surplus'] = $n['cou_surplus'];
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * //任务集市(限购物券(购物满额返券))
     * @param array $cou_type
     * @return  array
     */
    public function getCouponsGoods($cou_type = [], $num = 0)
    {
        $time = $this->timeRepository->getGmTime();

        $res = Coupons::whereIn('cou_type', $cou_type)
            ->where('review_status', 3)
            ->where('cou_end_time', '>', $time);

        if ($num) {
            $res = $res->take($num);
        }

        $res = $res->get();

        $cou_goods = $res ? $res->toArray() : [];

        if ($cou_goods) {
            foreach ($cou_goods as $k => $v) {

                //商品图片(没有指定商品时为默认图片)
                if ($v['cou_ok_goods']) {
                    $v['cou_ok_goods'] = $this->dscRepository->delStrComma($v['cou_ok_goods']);

                    $v['cou_ok_goods'] = !is_array($v['cou_ok_goods']) ? explode(",", $v['cou_ok_goods']) : $v['cou_ok_goods'];

                    $cou_goods_arr = Goods::select(['goods_id', 'goods_name', 'goods_thumb'])->whereIn('goods_id', $v['cou_ok_goods'])->get();

                    $cou_goods_arr = $cou_goods_arr ? $cou_goods_arr->toArray() : [];

                    if (!empty($cou_goods_arr)) {
                        foreach ($cou_goods_arr as $g_key => $g_val) {
                            if ($g_val['goods_thumb']) {
                                $cou_goods_arr[$g_key]['goods_thumb'] = $this->dscRepository->getImagePath($g_val['goods_thumb']);
                            }
                        }
                    }
                    $cou_goods[$k]['cou_ok_goods_name'] = $cou_goods_arr;
                } else {
                    $cou_goods[$k]['cou_ok_goods_name'][0]['goods_thumb'] = $this->dscRepository->getImagePath("images/coupons_default.png");
                }
                //可使用的店铺;
                $cou_goods[$k]['store_name'] = sprintf($GLOBALS['_LANG']['use_limit'], $this->merchantCommonService->getShopName($v['ru_id'], 1));
                $cou_goods[$k]['cou_end_time_format'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['cou_end_time']);
            }
        }

        return $cou_goods;
    }

    /**
     * //免邮神券
     * @param array $cou_type
     * @return  array
     */
    public function getCouponsShipping($cou_type = [], $num = 0, $cou_surplus = [])
    {
        $time = $this->timeRepository->getGmTime();

        $res = Coupons::whereIn('cou_type', $cou_type)
            ->where('review_status', 3)
            ->where('cou_end_time', '>', $time);

        if ($num) {
            $res = $res->take($num);
        }

        $res = $res->get();

        $cou_shipping = $res ? $res->toArray() : [];

        //格式化各优惠券剩余总数
        if ($cou_shipping) {
            foreach ($cou_shipping as $k => $v) {
                if ($cou_surplus) {
                    foreach ($cou_surplus as $m => $n) {
                        if ($v['cou_id'] == $n['cou_id']) {
                            $cou_shipping[$k]['cou_surplus'] = $n['cou_surplus'];
                        }
                    }
                }
            }
        }

        return $cou_shipping;
    }

    /**
     * //优惠券总数
     * @param array $cou_type
     * @return  array
     */
    public function getCouponsCount($cou_type = [], $type = '')
    {
        $time = $this->timeRepository->getGmTime();

        $res = Coupons::where('review_status', 3)
            ->where('cou_end_time', '>', $time);

        if ($type == 'all') {
            $res = $res->where('cou_type', VOUCHER_ALL);
        } elseif ($type == 'member') {
            $res = $res->where('cou_type', VOUCHER_USER);
        } elseif ($type == 'shipping') {
            $res = $res->where('cou_type', VOUCHER_SHIPPING);
        } elseif ($type == 'goods') {
            $res = $res->whereIn('cou_type', $cou_type);
        } else {
            $res = $res->whereNotIn('cou_type', $cou_type);
        }

        return $res->count();
    }

    /**
     * //优惠券列表
     * @param array $cou_type
     * @return  array
     */
    public function getCouponsList($cou_type = [], $type = '', $sort = 'cou_id', $order = 'desc', $start, $size, $cou_surplus = [])
    {
        $order = in_array($order, ['asc', 'desc']) ? $order : 'desc';
        $time = $this->timeRepository->getGmTime();

        $res = Coupons::where('review_status', 3)
            ->where('cou_end_time', '>', $time);

        if ($cou_type) {
            $res = $res->whereNotIn('cou_type', $cou_type);
        }

        if (is_numeric($type)) {
            $res = $res->where('cou_type', $type);
        } else {
            if ($type == 'all') {
                $res = $res->where('cou_type', VOUCHER_ALL);
            } elseif ($type == 'member') {
                $res = $res->where('cou_type', VOUCHER_USER);
            } elseif ($type == 'shipping') {
                $res = $res->where('cou_type', VOUCHER_SHIPPING);
            }
        }

        $res = $res->with([
            'getCouponsUser' => function ($query) {
                $query->select('cou_id', 'user_id', 'is_use');
            }
        ]);

        $res = $res->orderBy($sort, $order);

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $cou_data = $res ? $res->toArray() : [];

        //格式化各优惠券剩余总数
        if ($cou_data) {
            foreach ($cou_data as $k => $v) {
                $v = $v['get_coupons_user'] ? array_merge($v, $v['get_coupons_user']) : $v;

                if ($cou_surplus) {
                    foreach ($cou_surplus as $m => $n) {
                        if ($v['cou_id'] == $n['cou_id']) {
                            $cou_data[$k]['cou_surplus'] = $n['cou_surplus'];
                        }
                    }
                } else {

                    //商品图片(没有指定商品时为默认图片)
                    if ($v['cou_ok_goods']) {
                        $v['cou_ok_goods'] = $this->dscRepository->delStrComma($v['cou_ok_goods']);

                        $v['cou_ok_goods'] = !is_array($v['cou_ok_goods']) ? explode(",", $v['cou_ok_goods']) : $v['cou_ok_goods'];

                        $cou_goods_arr = Goods::select(['goods_id', 'goods_name', 'goods_thumb'])->whereIn('goods_id', $v['cou_ok_goods'])->get();

                        $cou_goods_arr = $cou_goods_arr ? $cou_goods_arr->toArray() : [];

                        if (!empty($cou_goods_arr)) {
                            foreach ($cou_goods_arr as $g_key => $g_val) {
                                if ($g_val['goods_thumb']) {
                                    $cou_goods_arr[$g_key]['goods_thumb'] = $this->dscRepository->getImagePath($g_val['goods_thumb']);
                                }
                            }
                        }
                        $cou_data[$k]['cou_ok_goods_name'] = $cou_goods_arr;
                    } else {
                        $cou_data[$k]['cou_ok_goods_name'][0]['goods_thumb'] = $this->dscRepository->getImagePath("images/coupons_default.png");
                    }
                    $cou_data[$k]['cou_end_time_format'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['cou_end_time']);

                    //判断是否已过期,0过期，1未过期 by yanxin;
                    if ($v['cou_end_time'] < $time) {
                        $cou_data[$k]['is_overtime'] = 0;
                    } else {
                        $cou_data[$k]['is_overtime'] = 1;
                    }

                    $v['cou_money'] = $v['cou_money'] ?? 0;
                    $cou_data[$k]['format_cou_money'] = $this->dscRepository->getPriceFormat($v['cou_money']);

                    //可使用的店铺;
                    $cou_data[$k]['store_name'] = sprintf($GLOBALS['_LANG']['use_limit'], $this->merchantCommonService->getShopName($v['ru_id'], 1));
                }
            }
        }

        return $cou_data;
    }

    /**
     * //取出当前优惠券信息(未过期,剩余总数大于0)
     * @param int $cou_id
     * @return  array
     */
    public function getCouponsHaving($cou_id = 0)
    {

        //当前时间;
        $time = $this->timeRepository->getGmTime();

        $count = CouponsUser::where('cou_id', $cou_id)->count();

        $res = Coupons::where('cou_id', $cou_id)
            ->where('review_status', 3)
            ->where('cou_end_time', '>', $time);

        $res = $res->orderBy('cou_id');

        $res = $this->baseRepository->getToArrayFirst($res);

        if ($res) {
            $res['cou_surplus'] = $res['cou_total'] - $count;
        }

        return $res['cou_surplus'] > 0 ? $res : [];
    }

    /**
     * //获取免邮券不包邮地区
     * @param int $cou_id
     * @return  array
     */
    public function getCouponsRegionList($cou_id = 0)
    {
        $arr = ['free_value' => '', 'free_value_name' => ''];

        $arr['free_value'] = CouponsRegion::where('cou_id', $cou_id)->value('region_list');

        if ($arr['free_value']) {
            $free_value = !is_array($arr['free_value']) ? explode(",", $arr['free_value']) : $arr['free_value'];

            $region_list = Region::selectRaw('GROUP_CONCAT(region_name) as region_name')->whereIn('region_id', $free_value)->first();

            $arr['free_value_name'] = $region_list ? $region_list->toArray() : [];
            $arr['free_value_name'] = implode(',', $arr['free_value_name']);
        }

        return $arr;
    }

    /**
     * 剩余数量
     * @param int $cou_id
     * @return  array
     */
    public function getRemainingNumber($cou_id = 0)
    {
        $total = CouponsUser::where('cou_id', $cou_id)->count();

        $user_num = $total ? $total : 0;

        $count = Coupons::where('cou_id', $cou_id)->where('cou_total', '>', $user_num)->count();

        return $count;
    }

    /**
     * 获取用户拥有的优惠券 默认返回所有用户所拥有的优惠券
     *
     * @param string $user_id 用户ID
     * @param bool $is_use 找出当前用户可以使用的
     * @param string $total 订单总价
     * @param array $cart_goods 商品信息
     * @param bool $user 用于区分是否会员中心里取数据(会员中心里的优惠券不能分组)
     * @param int $cart_ru_id
     * @param string $act_type
     * @param int $province
     * @return array
     * @throws \Exception
     */
    public function getUserCouponsList($user_id = '', $is_use = false, $total = '', $cart_goods = [], $user = true, $cart_ru_id = -1, $act_type = 'user', $province = 0)
    {
        $time = $this->timeRepository->getGmTime();

        //可使用的(平台用平台发的,商家用商家发的,当订单中混合了平台与商家的商品时,各自计算各自的商品总价是否达到各自发放的优惠券门槛,达到的话当前整个订单即可使用该优惠券)
        if ($is_use && isset($total) && $cart_goods) {
            $res = [];

            // 生成商家数据结构
            foreach ($cart_goods as $k => $v) {
                $res[$v['ru_id']] = [
                    'order_total' => 0,
                    'seller_id' => null,
                    'goods_id' => '',
                    'cat_id' => '',
                    'goods' => [],
                ];
            }

            // 统计数据
            foreach ($cart_goods as $k => $v) {
                $v['cat_id'] = $v['cat_id'] ?? 0;

                $v['subtotal'] = $v['goods_price'] * $v['goods_number'];
                $res[$v['ru_id']]['order_total'] += $v['goods_price'] * $v['goods_number'] - $v['dis_amount'];
                $res[$v['ru_id']]['seller_id'] = $v['ru_id'];
                $res[$v['ru_id']]['goods_id'] .= $v['goods_id'] . ",";
                $res[$v['ru_id']]['cat_id'] .= $v['cat_id'] . ",";
                $res[$v['ru_id']]['goods'][$v['goods_id']] = $v;
            }

            $arr = [];
            $couarr = [];
            foreach ($res as $key => $row) {
                $row['goods_id'] = $this->dscRepository->delStrComma($row['goods_id']);
                $row['cat_id'] = $this->dscRepository->delStrComma($row['cat_id']);

                $coupons_user = CouponsUser::select('uc_id', 'cou_id', 'cou_money AS uc_money')
                    ->where('order_id', 0)
                    ->where('user_id', $user_id);

                if ($cart_ru_id != -1) {
                    $coupons_user = $coupons_user->where('is_use', 0);
                }

                $where = [
                    'ru_id' => $row['seller_id'],
                    'order_total' => $row['order_total'],
                    'time' => $time
                ];
                $coupons_user = $coupons_user->whereHas('getCoupons', function ($query) use ($where) {
                    $query->where('ru_id', $where['ru_id'])
                        ->where('cou_man', '<=', $where['order_total'])
                        ->where('review_status', 3)
                        ->where('cou_start_time', '<', $where['time'])
                        ->where('cou_end_time', '>', $where['time']);
                });

                $coupons_user = $coupons_user->with(['getCoupons']);
                $coupons_user = $coupons_user->groupBy('uc_id');
                $coupons_user = $this->baseRepository->getToArrayGet($coupons_user);

                $couarr[$key] = $coupons_user;

                if ($couarr[$key]) {
                    foreach ($couarr[$key] as $ckey => $crow) {
                        $crow = $this->baseRepository->getArrayMerge($crow, $crow['get_coupons']);
                        $couarr[$key][$ckey] = $crow;

                        $couarr[$key][$ckey]['shop_name'] = $this->merchantCommonService->getShopName($crow['ru_id'], 1);

                        if ($crow['cou_type'] == VOUCHER_SHIPPING) {
                            if ($province > 0) {
                                $region_list = CouponsRegion::where('cou_id', $crow['cou_id'])->whereRaw("!FIND_IN_SET('$province', region_list)")->value('region_list');
                            } else {
                                $region_list = CouponsRegion::where('cou_id', $crow['cou_id'])->value('region_list');
                            }

                            if ($region_list) {
                                $region_list = !is_array($region_list) ? explode(",", $region_list) : $region_list;
                                $region_list = Region::select('region_name')->whereIn('region_id', $region_list)->get();
                                $region_list = $region_list ? collect($region_list)->flatten()->all() : [];

                                $couarr[$key][$ckey]['region_list'] = $region_list ? implode(",", $region_list) : '';
                            } else {
                                $couarr[$key][$ckey]['region_list'] = '';
                            }
                        }
                    }
                }

                $goods_ids = [];
                if (isset($row['goods_id']) && $row['goods_id'] && !is_array($row['goods_id'])) {
                    $goods_ids = explode(",", $row['goods_id']);
                    $goods_ids = array_unique($goods_ids);
                }

                $goods_cats = [];
                if (isset($row['cat_id']) && $row['cat_id'] && !is_array($row['cat_id'])) {
                    $goods_cats = explode(",", $row['cat_id']);
                    $goods_cats = array_unique($goods_cats);
                }

                if (($goods_ids || $goods_cats) && $couarr[$key]) {
                    foreach ($couarr[$key] as $rk => $rrow) {
                        if ($rrow['cou_goods']) {
                            $cou_goods = explode(",", $rrow['cou_goods']); //可使用优惠券商品
                            $cou_goods_prices = 0;
                            foreach ($goods_ids as $m => $n) {
                                if (in_array($n, $cou_goods)) {
                                    $cou_goods_prices += $row['goods'][$n]['subtotal'];
                                    if ($cou_goods_prices >= $rrow['cou_man']) {
                                        $arr[] = $rrow;
                                        break;
                                    }
                                }
                            }
                        } elseif ($rrow['spec_cat']) {
                            $spec_cat = $this->getCouChildren($rrow['spec_cat']);
                            $spec_cat = $this->baseRepository->getExplode($spec_cat);
                            $cou_goods_prices = 0;
                            foreach ($goods_cats as $m => $n) {
                                if (in_array($n, $spec_cat)) {
                                    foreach ($row['goods'] as $key => $val) {
                                        if ($n == $val['cat_id']) {
                                            $cou_goods_prices += $val['subtotal'];
                                        }
                                    }
                                    if ($cou_goods_prices >= $rrow['cou_man']) {
                                        $arr[] = $rrow;
                                        continue;
                                    }
                                }
                            }
                        } else {
                            $arr[] = $rrow;
                        }
                    }
                }
            }

            /* 去除重复 */
            $arr = $this->baseRepository->getArrayUnique($arr);
            return $arr;
        } else {
            if (!empty($user_id) && $user) {
                $user_id = !is_array($user_id) ? explode(",", $user_id) : $user_id;
                $couponsRes = CouponsUser::selectRaw("*, cou_money AS uc_money")->whereIn('user_id', $user_id);
            } elseif (!empty($user_id)) {
                $user_id = !is_array($user_id) ? explode(",", $user_id) : $user_id;
                $couponsRes = CouponsUser::selectRaw("*, cou_money AS uc_money")->whereIn('user_id', $user_id);
            } else {
                return [];
            }

            $where = [
                'act_type' => $act_type,
                'time' => $time
            ];
            $couponsRes = $couponsRes->whereHas('getCoupons', function ($query) use ($where) {
                $query = $query->where('review_status', 3);

                if ($where['act_type'] == 'cart') {
                    $query->where('cou_start_time', '<', $where['time'])
                        ->where('cou_end_time', '>', $where['time']);
                }
            });

            $couponsRes = $couponsRes->with(['getCoupons'])
                ->groupBy('uc_id')
                ->get();

            $couponsRes = $couponsRes ? $couponsRes->toArray() : [];

            if ($couponsRes) {
                foreach ($couponsRes as $key => $row) {
                    $row = $row['get_coupons'] ? array_merge($row, $row['get_coupons']) : $row;

                    if ($act_type != 'cart') {
                        $order = OrderInfo::select('order_sn', 'add_time', 'coupons AS order_coupons')->where('order_id', $row['order_id'])->first();
                        $order = $order ? $order->toArray() : [];
                        $row['order_sn'] = $order['order_sn'] ?? '';
                        $row['add_time'] = $order['add_time'] ?? '';
                        $row['order_coupons'] = $order['order_coupons'] ?? '';
                    }

                    $couponsRes[$key] = $row;
                    // 处理价格显示整数
                    $couponsRes[$key]['cou_money'] = intval($couponsRes[$key]['cou_money']);
                    $couponsRes[$key]['uc_money'] = intval($couponsRes[$key]['uc_money']);
                    $couponsRes[$key]['order_coupons'] = isset($couponsRes[$key]['order_coupons']) ? intval($couponsRes[$key]['order_coupons']) : '';

                    $couponsRes[$key]['shop_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1);
                    if ($row['cou_type'] == VOUCHER_SHIPPING) {
                        if ($province > 0) {
                            $region_list = CouponsRegion::where('cou_id', $row['cou_id'])->whereRaw("!FIND_IN_SET('$province', region_list)")->value('region_list');
                        } else {
                            $region_list = CouponsRegion::where('cou_id', $row['cou_id'])->value('region_list');
                        }

                        if ($region_list) {
                            $region_list = !is_array($region_list) ? explode(",", $region_list) : $region_list;
                            $region_list = Region::select('region_name')->whereIn('region_id', $region_list)->get();
                            $region_list = $region_list ? collect($region_list)->flatten()->all() : [];

                            $couponsRes[$key]['region_list'] = $region_list ? implode(",", $region_list) : '';
                        } else {
                            $couponsRes[$key]['region_list'] = '';
                        }
                    }
                }
            }

            /* 去除重复 */
            $couponsRes = $this->baseRepository->getArrayUnique($couponsRes);
            return $couponsRes;
        }
    }

    /**
     * 优惠券分类
     *
     * @access  public
     * @param array $cat
     *
     * @return array
     */
    public function getCouChildren($cat = '')
    {
        $catlist = '';
        if ($cat) {
            $cat = explode(",", $cat);

            foreach ($cat as $key => $row) {
                $row = intval($row);
                $list = $this->categoryService->getCatListChildren($row);
                $list = $this->baseRepository->getImplode($list);
                $catlist .= $list . ",";
            }

            $catlist = $this->dscRepository->delStrComma($catlist, 0, -1);
            $catlist = array_unique(explode(",", $catlist));
            $catlist = implode(",", $catlist);
            $cat = implode(",", $cat);
            $catlist = !empty($catlist) ? $catlist . "," . $cat : $cat;

            $catlist = $this->dscRepository->delStrComma($catlist);
        }

        return $catlist;
    }

    /**
     * 领取优惠券
     *
     * @param int $cou_id
     * @param int $user_id
     * @return array
     * @throws \Exception
     */
    public function Couponsreceive($cou_id = 0, $user_id = 0)
    {
        $result = [];
        //当前时间
        $time = $this->timeRepository->getGmTime();

        $result['is_over'] = 0;

        //取出当前优惠券信息(未过期,剩余总数大于0)
        $cou_data = $this->getCouponsHaving($cou_id);

        //判断券是不是被领取完了
        if (!$cou_data) {
            return $result = [
                'status' => 'error',
                'msg' => lang('common.lang_coupons_receive_failure')
            ];
        }

        //判断是否已经领取了,并且还没有使用(根据创建优惠券时设定的每人可以领取的总张数为准,防止超额领取)
        $cou_user_num = CouponsUser::where('user_id', $user_id)->where('cou_id', $cou_id)->count();

        if ($cou_data['cou_user_num'] <= $cou_user_num) {
            return $result = [
                'status' => 'error',
                'msg' => sprintf(lang('common.lang_coupons_user_receive'), $cou_data['cou_user_num'])
            ];
        } else {
            $result['is_over'] = 1;
        }

        //判断当前会员是否已经关注店铺
        if ($cou_data['cou_type'] == VOUCHER_SHOP_CONLLENT) {
            $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $cou_data['ru_id'])->value('rec_id');
            if (empty($rec_id)) {
                //关注店铺
                $other = [
                    'user_id' => $user_id,
                    'ru_id' => $cou_data['ru_id'],
                    'add_time' => $this->timeRepository->getGmTime(),
                    'is_attention' => 1
                ];
                CollectStore::insert($other);
            }
        }
        //领券
        $uc_sn = $time . rand(10, 99);

        $userData = [
            'user_id' => $user_id,
            'cou_money' => $cou_data['cou_money'],
            'cou_id' => $cou_id,
            'uc_sn' => $uc_sn
        ];

        $uc_id = CouponsUser::insertGetId($userData);

        if ($uc_id) {
            return $result = [
                'status' => 'ok',
                'msg' => lang('common.lang_coupons_receive_succeed')
            ];
        }
    }

    /***获取用户优惠券发放领取情况
     * @param $cou_id 优惠券ID
     * @return array
     */
    public function get_coupons_info2($cou_id)
    {
        $filter['record_count'] = CouponsUser::where('cou_id', $cou_id)->count();
        /* 分页大小 */
        $filter = page_and_size($filter);

        $row = CouponsUser::selectRaw("*, cou_money AS uc_money")->where('cou_id', $cou_id);

        $row = $row->with(['getCoupons' => function ($query) {
            $query->select('cou_id', 'cou_money');
        }]);

        $row = $row->orderBy('uc_id');

        if ($filter['start'] > 0) {
            $row = $row->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $row = $row->take($filter['page_size']);
        }

        $row = $this->baseRepository->getToArrayGet($row);
        if ($row) {
            foreach ($row as $key => $val) {
                $val = $this->baseRepository->getArrayMerge($val, $val['get_coupons']);
                $row[$key]['cou_money'] = !empty($val['uc_money']) ? $val['uc_money'] : $val['cou_money'];

                //使用时间
                if ($val['is_use_time']) {
                    $row[$key]['is_use_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['is_use_time']);
                } else {
                    $row[$key]['is_use_time'] = '';
                }
                //订单号
                if ($val['order_id']) {
                    $row[$key]['order_sn'] = OrderInfo::where('order_id', $val['order_id'])->value('order_sn');
                }
                //所属会员
                if ($val['user_id']) {
                    $row[$key]['user_name'] = Users::where('user_id', $val['user_id'])->value('user_name');

                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $row[$key]['user_name'] = $this->dscRepository->stringToStar($row[$key]['user_name']);
                    }
                }
            }
        }
        $arr = ['item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /* 等级重组 */
    public function get_rank_arr($rank_list, $cou_ok_user = '')
    {
        $cou_ok_user = !empty($cou_ok_user) ? explode(",", $cou_ok_user) : [];

        $arr = [];
        if ($rank_list) {
            foreach ($rank_list as $key => $row) {
                $arr[$key]['rank_id'] = $key;
                $arr[$key]['rank_name'] = $row;

                if ($cou_ok_user && in_array($key, $cou_ok_user)) {
                    $arr[$key]['is_checked'] = 1;
                } else {
                    $arr[$key]['is_checked'] = 0;
                }
            }
        }

        return $arr;
    }

    /**
     * 获取优惠券类型信息(不带分页)
     *
     * @param string $cou_type 优惠券类型 1:注册送,2:购物送,3:全场送,4:会员送  默认返回所有类型数据
     * @return array
     */
    public function getCouponsTypeInfoNoPage($cou_type = '1,2,3,4')
    {
        if (empty($cou_type)) {
            return [];
        }

        $cou_type = !is_array($cou_type) ? explode(",", $cou_type) : $cou_type;

        //获取格林尼治时间戳(用于判断优惠券是否已过期)
        $time = $this->timeRepository->getGmTime();

        $arr = Coupons::where('review_status', 3)
            ->whereIn('cou_type', $cou_type)
            ->where('cou_end_time', '>', $time);

        $arr = $this->baseRepository->getToArrayGet($arr);

        //生成优惠券编号
        if ($arr) {
            foreach ($arr as $k => $v) {
                $arr[$k]['uc_sn'] = $time . rand(10, 99);
            }
        }

        return $arr;
    }
}
