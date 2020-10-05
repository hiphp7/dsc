<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Goods;
use App\Models\GoodsConshipping;
use App\Models\GoodsConsumption;
use App\Models\WholesaleCart;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsCommonService;
use App\Services\Order\OrderGoodsService;
use Illuminate\Support\Str;

class CartCommonService
{
    protected $sessionRepository;
    protected $baseRepository;
    protected $goodsCommonService;
    protected $timeRepository;
    protected $orderGoodsService;

    public function __construct(
        SessionRepository $sessionRepository,
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        TimeRepository $timeRepository,
        OrderGoodsService $orderGoodsService
    )
    {
        $this->sessionRepository = $sessionRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->timeRepository = $timeRepository;
        $this->orderGoodsService = $orderGoodsService;
    }

    /**
     * 单品设置阶梯促销最终金额
     *
     * @param int $goods_amount
     * @param int $goods_id
     * @param int $type
     * @param int $shipping_fee
     * @param int $parent_id
     * @return array
     */
    public function getConGoodsAmount($goods_amount = 0, $goods_id = 0, $type = 0, $shipping_fee = 0, $parent_id = 0)
    {
        $arr = [];

        $table = '';
        if ($type == 0) {
            $table = 'goods_consumption';
        } elseif ($type == 1) {
            $table = 'goods_conshipping';

            if (empty($shipping_fee)) {
                $shipping_fee = 0;
            }
        }

        if ($parent_id == 0 && $table) {
            $res = $this->getGoodsConList($goods_id, $table, $type);

            if ($res) {
                $arr = [];
                $arr['amount'] = '';
                foreach ($res as $key => $row) {
                    if ($type == 0) {
                        if ($goods_amount >= $row['cfull']) {
                            $arr[$key]['cfull'] = $row['cfull'];
                            $arr[$key]['creduce'] = $row['creduce'];
                            $arr[$key]['goods_amount'] = $goods_amount - $row['creduce'];

                            if ($arr[$key]['goods_amount'] > 0) {
                                $arr['amount'] .= $arr[$key]['goods_amount'] . ',';
                            }
                        }
                    } elseif ($type == 1) {
                        if ($goods_amount >= $row['sfull']) {
                            $arr[$key]['sfull'] = $row['sfull'];
                            $arr[$key]['sreduce'] = $row['sreduce'];
                            if ($shipping_fee > 0) { //运费要大于0时才参加商品促销活动
                                $arr[$key]['shipping_fee'] = $shipping_fee - $row['sreduce'];
                                $arr['amount'] .= $arr[$key]['shipping_fee'] . ',';
                            } else {
                                $arr['amount'] = '0' . ',';
                            }
                        }
                    }
                }

                if ($type == 0) {
                    if (!empty($arr['amount'])) {
                        $arr['amount'] = substr($arr['amount'], 0, -1);
                    } else {
                        $arr['amount'] = $goods_amount;
                    }
                } elseif ($type == 1) {
                    if (!empty($arr['amount'])) {
                        $arr['amount'] = substr($arr['amount'], 0, -1);
                    } else {
                        $arr['amount'] = $shipping_fee;
                    }
                }
            } else {
                if ($type == 0) {
                    $arr['amount'] = $goods_amount;
                } elseif ($type == 1) {
                    $arr['amount'] = $shipping_fee;
                }
            }

            //消费满最大金额免运费
            if ($type == 1) {
                $largest_amount = Goods::where('goods_id', $goods_id)->value('largest_amount');

                if ($largest_amount > 0 && $goods_amount > $largest_amount) {
                    $arr['amount'] = 0;
                }
            }
        } else {
            if ($type == 0) {
                $arr['amount'] = $goods_amount;
            } elseif ($type == 1) {
                $arr['amount'] = $shipping_fee;
            }
        }

        return $arr;
    }

    /**
     * 查询商品满减促销信息
     *
     * @param int $goods_id
     * @param $table
     * @param int $type
     * @return array|\Illuminate\Support\Collection
     */
    public function getGoodsConList($goods_id = 0, $table, $type = 0)
    {
        if ($table == 'goods_consumption') {
            $res = GoodsConsumption::where('goods_id', $goods_id);
        } else {
            $res = GoodsConshipping::where('goods_id', $goods_id);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        if ($res) {
            $string = '';
            foreach ($res as $key => $row) {
                $arr[$key]['id'] = $row['id'];
                if ($type == 0) {
                    $arr[$key]['cfull'] = $row['cfull'];
                    $arr[$key]['creduce'] = $row['creduce'];
                } elseif ($type == 1) {
                    $arr[$key]['sfull'] = $row['sfull'];
                    $arr[$key]['sreduce'] = $row['sreduce'];
                }
            }

            if ($type == 0) {
                $string = "cfull";
            } elseif ($type == 1) {
                $string = "sfull";
            }

            if ($string) {
                $arr = $this->baseRepository->getSortBy($arr, $string);
            }
        }

        return $arr;
    }

    /**
     * 重新计算购物车中信息
     *
     * mobile使用
     *
     * @param int $user_id
     */
    public function recalculatePriceMobileCart($user_id = 0)
    {
        if ($user_id > 0) {
            $session_id = $this->sessionRepository->realCartMacIp();

            if ($session_id) {
                /* 取得有可能改变价格的商品：除配件和赠品之外的商品 */
                $res = Cart::where('session_id', $session_id)
                    ->where('is_gift', 0)
                    ->where('rec_type', CART_GENERAL_GOODS)
                    ->get();
                $res = $res ? $res->toArray() : [];

                if ($res) {
                    foreach ($res as $row) {
                        $rec_id = Cart::where('goods_id', $row['goods_id'])
                            ->where('user_id', $user_id)
                            ->where('goods_attr_id', $row['goods_attr_id'])
                            ->where('is_real', 1)
                            ->value('rec_id');

                        if ($rec_id > 0) {
                            //更新数量
                            Cart::where('rec_id', $rec_id)->increment('goods_number', $row['goods_number']);
                            Cart::where('rec_id', $row['rec_id'])->delete();
                        } else {
                            $cartOther = [
                                'user_id' => $user_id,
                                'session_id' => '',
                            ];
                            Cart::where('rec_id', $row['rec_id'])->update($cartOther);
                        }
                    }
                }

                /* 删除赠品，重新选择 */
                Cart::where('session_id', $session_id)->where('is_gift', '>', 0)->delete();

                // 供应链更新购物车
                if (is_dir(app_path('Modules/' . Str::studly(SUPPLLY_PATH)))) {
                    $wholesale = WholesaleCart::where('session_id', $session_id)
                        ->where('rec_type', CART_GENERAL_GOODS)
                        ->get();
                    $wholesale = $wholesale ? $wholesale->toArray() : [];

                    if ($wholesale) {
                        foreach ($wholesale as $row) {
                            $cartOther = [
                                'user_id' => $user_id,
                                'session_id' => '',
                            ];
                            WholesaleCart::where('rec_id', $row['rec_id'])->update($cartOther);
                        }
                    }
                }
            }
        }
    }

    /**
     * 重新计算购物车中的商品价格：目的是当用户登录时享受会员价格，当用户退出登录时不享受会员价格
     * 如果商品有促销，价格不变
     *
     * @access  public
     * @return  void
     */
    public function recalculatePriceCart()
    {
        $user_id = session('user_id');
        $session_id = $this->sessionRepository->realCartMacIp();

        if ($user_id > 0) {
            /* 取得有可能改变价格的商品：除配件和赠品之外的商品 */
            $res = Cart::where('session_id', $session_id)
                ->where('is_gift', 0)
                ->where('rec_type', CART_GENERAL_GOODS);

            $res = $this->baseRepository->getToArrayGet($res);

            if ($GLOBALS['_CFG']['add_shop_price'] == 1) {
                $add_tocart = 1;
            } else {
                $add_tocart = 0;
            }

            $nowTime = $this->timeRepository->getGmTime();

            if ($res) {
                foreach ($res as $row) {
                    $attr_id = empty($row['goods_attr_id']) ? [] : explode(',', $row['goods_attr_id']);

                    if ($row['extension_code'] != 'package_buy') {
                        $presale = 0;
                    } else {
                        $presale = CART_PACKAGE_GOODS;
                    }

                    $goods_price = $this->goodsCommonService->getFinalPrice($row['goods_id'], $row['goods_number'], true, $attr_id, $row['warehouse_id'], $row['area_id'], $row['area_city'], 0, $presale, $add_tocart);

                    $rec_id = Cart::where('goods_id', $row['goods_id'])
                        ->where('user_id', $user_id)
                        ->where('extension_code', '<>', 'package_buy')
                        ->where('goods_attr_id', $row['goods_attr_id'])
                        ->where('warehouse_id', $row['warehouse_id'])
                        ->where('is_real', 1)
                        ->where('group_id', '');

                    $rec_id = $rec_id->value('rec_id');

                    $error = 0;
                    if ($row['extension_code'] != 'package_buy') {
                        $xiangouInfo = $this->goodsCommonService->getPurchasingGoodsInfo($row['goods_id']);
                        if ($xiangouInfo) {
                            $start_date = $xiangouInfo['xiangou_start_date'];
                            $end_date = $xiangouInfo['xiangou_end_date'];

                            if ($xiangouInfo['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
                                $orderGoods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $row['goods_id'], $user_id);
                                $cart_number = $orderGoods['goods_number'] + $row['goods_number'];

                                if ($orderGoods['goods_number'] >= $xiangouInfo['xiangou_num']) {
                                    $row['goods_number'] = 0;
                                    $error = 1;
                                } elseif ($cart_number >= $xiangouInfo['xiangou_num']) {
                                    $row['goods_number'] = $xiangouInfo['xiangou_num'] - $orderGoods['goods_number'];
                                    $error = 2;
                                } else {
                                    $error = 0;
                                }
                            } else {
                                $error = 0;
                            }
                        }
                    }

                    if ($error == 1) {
                        Cart::where('goods_id', $row['goods_id'])
                            ->where('rec_id', $row['rec_id'])
                            ->where('warehouse_id', $row['warehouse_id'])
                            ->delete();
                    } else {
                        if ($rec_id > 0) {
                            if ($error == 2) {
                                $cartOther = [
                                    'goods_number' => $row['goods_number']
                                ];

                                Cart::where('rec_id', $rec_id)->update($cartOther);
                            } else {
                                Cart::where('rec_id', $rec_id)->increment('goods_number', $row['goods_number']);
                            }

                            Cart::where('rec_id', $row['rec_id'])->delete();
                        } else {
                            $cartOther = [
                                'user_id' => $user_id,
                                'session_id' => '',
                                'goods_number' => $row['goods_number'],
                            ];

                            if ($row['extension_code'] != 'package_buy') {
                                if ($row['parent_id'] == 0 && $goods_price > 0) {
                                    $cartOther['goods_price'] = $goods_price;
                                }

                                Cart::where('goods_id', $row['goods_id'])
                                    ->where('rec_id', $row['rec_id'])
                                    ->where('warehouse_id', $row['warehouse_id'])
                                    ->update($cartOther);
                            } else {
                                Cart::where('rec_id', $row['rec_id'])->update($cartOther);
                            }
                        }
                    }
                }
            }

            /* 删除赠品，重新选择 */
            $session_id = $this->sessionRepository->realCartMacIp();
            Cart::where('session_id', $session_id)->where('is_gift', '>', 0)->delete();
        }
    }


    public function copyCartToOfflineStore($rec_id = [], $user_id = 0, $store_id, $take_time = '', $store_mobile = '')
    {
        if (empty($store_id)) {
            return false;
        }
        if (empty($user_id)) {
            $user_id = session()->exists('user_id') ? session('user_id', 0) : $user_id;
        }

        if (!is_array($rec_id)) {
            $rec_id = explode(',', $rec_id);
        }

        $cart = Cart::whereIn('rec_id', $rec_id);

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        $cart_list = $cart->get();
        $rec_id = [];
        if ($cart_list) {
            foreach ($cart_list as $item) {
                unset($item['rec_id']);
                $item['rec_type'] = CART_OFFLINE_GOODS;
                $item['store_id'] = $store_id;
                $item['take_time'] = $take_time;
                $item['store_mobile'] = $store_mobile;
//                dd($item->toArray());
                $rec_id[] = Cart::insertGetId($item->toArray());
            }
        }
        return $rec_id;
    }

    /**
     * 清空购物车门店商品
     *
     * @param int $user_id
     */
    public function clearStoreGoods($user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = session()->exists('user_id') ? session('user_id', 0) : $user_id;
        }

        /*->where('rec_type', CART_OFFLINE_GOODS)*/
        $res = Cart::where('store_id', '>', 0);

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();

            $res = $res->where('session_id', $session_id);
        }

        $res->delete();
    }

    /**
     * 清空购物车
     *
     * @param int $user_id
     * @param int $type
     * @param string $cart_value
     * @return mixed
     */
    public function clearCart($user_id = 0, $type = CART_GENERAL_GOODS, $cart_value = '')
    {
        if (empty($user_id)) {
            $user_id = session()->exists('user_id') ? session('user_id', 0) : $user_id;
        }

        $cart = Cart::where('rec_type', $type);

        $cart_value = $this->baseRepository->getExplode($cart_value);

        if (!empty($cart_value)) {
            $cart = $cart->whereIn('rec_id', $cart_value);
        }

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        return $cart->delete();
    }

    /**
     * 获取选中的购物车商品rec_id
     *
     * @param int $is_select 获取类型：0|session , 1|查询表数据
     * @param int $user_id 会员ID
     * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|int|mixed
     */
    public function getCartValue($is_select = 0, $user_id = 0)
    {
        if ($is_select) {

            if ($user_id > 0) {
                $list = Cart::where('user_id', $user_id);
            } else {
                $session_id = $this->sessionRepository->realCartMacIp();
                $list = Cart::where('session_id', $session_id);
            }

            $list = $list->where('is_checked', 1);
            $list = $this->baseRepository->getToArrayGet($list);
            $cart_value = $this->baseRepository->getKeyPluck($list, 'rec_id');
            $cart_value = $cart_value ? $cart_value : 0;
        } else {
            $cart_value = 0;
            if (session()->exists('cart_value')) {
                $cart_value = session('cart_value');
            }
        }

        return $cart_value;
    }

    /**
     * 更新商品最新价格
     *
     * @param int $goods_price
     * @param int $rec_id
     */
    public function updateCartPrice($goods_price = 0, $rec_id = 0)
    {
        if ($goods_price > 0 && $rec_id > 0) {
            Cart::where('rec_id', $rec_id)->where('parent_id', 0)
                ->update([
                    'goods_price' => $goods_price
                ]);
        }
    }

    /**
     * 取得购物车总金额
     *
     * @param array $cartWhere
     * @return float
     */
    public function getCartAmount($cartWhere = [])
    {
        /**
         * @param int $user_id 会员ID
         * @param string $cart_value 购物车ID
         * @param bool $include_gift 是否包括赠品
         * @param int $type 类型：默认普通商品
         * @return float
         */
        $user_id = $cartWhere['user_id'] ?? 0;
        $session_id = $cartWhere['session_id'] ?? 0;
        $cart_value = $cartWhere['cart_value'] ?? 0;
        $include_gift = $cartWhere['include_gift'] ?? true;
        $type = $cartWhere['rec_type'] ?? [CART_GENERAL_GOODS, CART_PACKAGE_GOODS, CART_ONESTEP_GOODS];

        $res = Cart::selectRaw("SUM(goods_price * goods_number) as total");

        if (is_array($type)) {
            $res = $res->whereIn('rec_type', $type);
        } else {
            $res = $res->where('rec_type', $type);
        }

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } elseif (isset($cartWhere['session_id'])) {
            $res = $res->where('session_id', $session_id);
        }

        if (!$include_gift) {
            $res = $res->where('is_gift', 0)
                ->where('goods_id', '>', 0);
        }

        if ($cart_value) {
            $cart_value = $this->baseRepository->getExplode($cart_value);
            $res = $res->whereIn('rec_id', $cart_value);
        }

        $res = $this->baseRepository->getToArrayFirst($res);

        $total = $res ? $res['total'] : 0;

        return floatval($total);
    }

    /**
     * 查询购买N件商品
     *
     * @param int $type
     * @param string $cart_value
     * @param int $user_id
     * @return int
     */
    public function getBuyCartGoodsNumber($type = CART_GENERAL_GOODS, $cart_value = '', $user_id = 0)
    {
        if (empty($user_id)) {
            $user_id = session()->has('user_id') && session()->get('user_id') ? session('user_id') : $user_id;
        }

        $session_id = $this->sessionRepository->realCartMacIp();

        $whereType = [
            'type' => $type,
            'cart_presale' => CART_PRESALE_GOODS
        ];

        /* 促销活动 start */
        $goods_number = Cart::where('rec_type', $type)->where('extension_code', '<>', 'package_buy');

        $goods_number = $goods_number->whereHas('getGoods', function ($query) use ($whereType) {
            if ($whereType['type'] == $whereType['cart_presale']) {
                $query->where('is_on_sale', 0)->where('is_delete', 0);
            } else {
                $query->where('is_on_sale', 1)->where('is_delete', 0);
            }
        });

        if (!empty($user_id)) {
            $goods_number = $goods_number->where('user_id', $user_id);
        } else {
            $goods_number = $goods_number->where('session_id', $session_id);
        }

        if (!empty($cart_value)) {
            $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;

            $goods_number = $goods_number->whereIn('rec_id', $cart_value);
        }

        $goods_number = $goods_number->selectRaw('SUM(goods_number) AS goods_number')->value('goods_number');
        $goods_number = $goods_number ? $goods_number : 0;
        /* 促销活动 end */

        /* 促销活动 start */
        $activity_number = Cart::where('rec_type', $type)->where('extension_code', '<>', 'package_buy');

        $activity_number = $activity_number->whereHas('getGoodsActivity', function ($query) {
            $query->where('review_status', 3);
        });

        if (!empty($user_id)) {
            $activity_number = $activity_number->where('user_id', $user_id);
        } else {
            $activity_number = $activity_number->where('session_id', $session_id);
        }

        if (!empty($cart_value)) {
            $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;

            $activity_number = $activity_number->whereIn('rec_id', $cart_value);
        }

        $activity_number = $activity_number->count();
        /* 促销活动 end */

        return ($goods_number + $activity_number);
    }

    /**
     * @param int $goods_price
     * @param int $rec_id
     */
    public function getUpdateCartPrice($goods_price = 0, $rec_id = 0)
    {
        Cart::where('rec_id', $rec_id)->update([
            'goods_price' => $goods_price
        ]);
    }
}
