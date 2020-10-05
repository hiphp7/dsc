<?php

use App\Models\BaitiaoLog;
use App\Models\BonusType;
use App\Models\Brand;
use App\Models\Card;
use App\Models\Cart;
use App\Models\CartCombo;
use App\Models\Category;
use App\Models\CouponsUser;
use App\Models\DeliveryOrder;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\GoodsAttr;
use App\Models\GoodsInventoryLogs;
use App\Models\GoodsTransport;
use App\Models\GoodsTransportExpress;
use App\Models\GoodsTransportExtend;
use App\Models\GroupGoods;
use App\Models\IntelligentWeight;
use App\Models\MerchantsAccountLog;
use App\Models\MerchantsGrade;
use App\Models\OfflineStore;
use App\Models\OrderAction;
use App\Models\OrderCloud;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderInvoice;
use App\Models\OrderReturn;
use App\Models\Pack;
use App\Models\PackageGoods;
use App\Models\PayLog;
use App\Models\Payment;
use App\Models\PresaleActivity;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\RegionWarehouse;
use App\Models\ReturnAction;
use App\Models\ReturnCause;
use App\Models\ReturnGoods;
use App\Models\SeckillGoods;
use App\Models\SellerBillOrder;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\ShopConfig;
use App\Models\StoreGoods;
use App\Models\StoreProducts;
use App\Models\UserAccount;
use App\Models\UserAddress;
use App\Models\UserBonus;
use App\Models\UserOrderNum;
use App\Models\Users;
use App\Models\UsersVatInvoicesInfo;
use App\Models\ValueCard;
use App\Models\ValueCardRecord;
use App\Models\ValueCardType;
use App\Models\VirtualCard;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\CouponsService;
use App\Services\Activity\GroupBuyService;
use App\Services\Activity\PresaleService;
use App\Services\Activity\ValueCardService;
use App\Services\Cart\CartCommonService;
use App\Services\Cart\CartService;
use App\Services\Category\CategoryService;
use App\Services\Coupon\CouponsUserService;
use App\Services\Erp\JigonManageService;
use App\Services\Flow\FlowActivityService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;
use App\Services\Order\OrderRefoundService;
use App\Services\Order\OrderService;
use App\Services\Order\OrderTransportService;
use App\Services\Package\PackageGoodsService;
use App\Services\Payment\PaymentService;
use App\Services\Store\StoreCommonService;
use App\Services\Store\StoreService;
use App\Services\User\UserCommonService;
use App\Services\User\UserRankService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 取得已安装的配送方式
 *
 * @param bool $is_cac true 显示上门自提，false 不显示上门自提
 * @return array
 */
function shipping_list($is_cac = false)
{
    $res = Shipping::where('enabled', 1);

    if ($is_cac == false) {
        //过滤商家“上门取货”
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] > 0) {
            $res = $res->where('shipping_code', '<>', 'cac');
        }
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            if (substr($row['shipping_code'], 0, 5) == 'ship_') {
                unset($arr[$key]);
                continue;
            } else {
                $arr[$key]['shipping_id'] = $row['shipping_id'];
                $arr[$key]['shipping_name'] = $row['shipping_name'];
                $arr[$key]['shipping_code'] = $row['shipping_code'];
            }
        }
    }

    return $arr;
}

/**
 * 取得可用的配送区域的父级地区
 * @param array $region_id
 * @return  array   配送方式数组
 */
function get_parent_region($region_id)
{
    $res = Region::where('region_id', $region_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 获取指定配送的保价费用
 *
 * @access  public
 * @param string $shipping_code 配送方式的code
 * @param float $goods_amount 保价金额
 * @param mix $insure 保价比例
 * @return  float
 */
function shipping_insure_fee($shipping_code, $goods_amount, $insure)
{
    if (strpos($insure, '%') === false) {
        /* 如果保价费用不是百分比则直接返回该数值 */
        return floatval($insure);
    } else {
        $shipping_code = Str::studly($shipping_code);
        $shipping_code = '\\App\\Plugins\\Shipping\\' . $shipping_code . '\\' . $shipping_code;

        if (class_exists($shipping_code)) {
            $shipping = app($shipping_code);
            $insure = floatval($insure) / 100;

            if (method_exists($shipping, 'calculate_insure')) {
                return $shipping->calculate_insure($goods_amount, $insure);
            } else {
                return ceil($goods_amount * $insure);
            }
        } else {
            return false;
        }
    }
}

/**
 * 取得已安装的支付方式列表
 * @return  array   已安装的配送方式列表
 */
function payment_list()
{
    $res = Payment::where('enabled', 1);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

/**
 * 取得支付方式信息
 * @param int $pay_id 支付方式id
 * @return  array   支付方式信息
 */
function payment_info($field, $type = 0)
{
    $row = Payment::where('enabled', 1);

    if ($type == 1) {
        $row = $row->where('pay_code', $field);
    } else {
        $row = $row->where('pay_id', $field);
    }

    $row = app(BaseRepository::class)->getToArrayFirst($row);

    return $row;
}

/**
 * 获得订单需要支付的支付费用
 *
 * @access  public
 * @param integer $payment_id
 * @param float $order_amount
 * @param mix $cod_fee
 * @return  float
 */
function pay_fee($payment_id, $order_amount, $cod_fee = null)
{
    $pay_fee = 0;
    $payment = payment_info($payment_id);

    if ($payment) {
        $rate = ($payment['is_cod'] && !is_null($cod_fee)) ? $cod_fee : $payment['pay_fee'];

        if (strpos($rate, '%') !== false) {
            /* 支付费用是一个比例 */
            $val = floatval($rate) / 100;
            $pay_fee = $val > 0 ? $order_amount * $val / (1 - $val) : 0;
        } else {
            $pay_fee = floatval($rate);
        }
    }

    return round($pay_fee, 2);
}

/**
 * 取得可用的支付方式列表
 * @param bool $support_cod 配送方式是否支持货到付款
 * @param int $cod_fee 货到付款手续费（当配送方式支持货到付款时才传此参数）
 * @param int $is_online 是否支持在线支付
 * @return  array   配送方式数组
 */
function available_payment_list($support_cod = 0, $cod_fee = 0, $is_online = false, $order_amount = 0)
{
    $res = Payment::where('enabled', 1);

    if (!$support_cod) {
        $res = $res->where('is_cod', 0);
    }
    if ($is_online) {
        if ($is_online == 2) {
            $res = $res->where(function ($query) {
                $query->where('is_online', 1)->orWhere('pay_code', 'balance');
            });
        } else {
            $res = $res->where('is_online', 1);
        }
    }

    $res = $res->orderByRaw('pay_order, pay_id desc');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $modules = [];
    if ($res) {
        foreach ($res as $row) {
            if ($row['is_cod'] == '1') {
                $row['pay_fee'] = $cod_fee;
            }

            $row['pay_fee_amount'] = pay_fee($row['pay_id'], $order_amount);

            $row['format_pay_fee'] = strpos($row['pay_fee'], '%') !== false ? $row['pay_fee'] :
                price_format($row['pay_fee'], false);
            $modules[] = $row;
        }

        if (isset($modules)) {
            foreach ($modules as $key => $payment) {
                //去除ecjia的支付方式
                if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                    unset($modules[$key]);
                    continue;
                }
            }
        }
    }

    if (isset($modules)) {
        return $modules;
    } else {
        return [];
    }
}

/**
 * 取得包装列表
 * @return  array   包装列表
 */
function pack_list()
{
    $res = Pack::whereRaw(1);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $list = [];
    if ($res) {
        foreach ($res as $row) {
            $row['format_pack_fee'] = price_format($row['pack_fee'], false);
            $row['format_free_money'] = price_format($row['free_money'], false);
            $list[] = $row;
        }
    }

    return $list;
}

/**
 * 取得包装信息
 * @param int $pack_id 包装id
 * @return  array   包装信息
 */
function pack_info($pack_id)
{
    $res = Pack::where('pack_id', $pack_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 根据订单中的商品总额来获得包装的费用
 *
 * @access  public
 * @param integer $pack_id
 * @param float $goods_amount
 * @return  float
 */
function pack_fee($pack_id, $goods_amount)
{
    $pack = pack_info($pack_id);

    $val = (floatval($pack['free_money']) <= $goods_amount && $pack['free_money'] > 0) ? 0 : floatval($pack['pack_fee']);

    return $val;
}

/**
 * 取得贺卡列表
 * @return  array   贺卡列表
 */
function card_list()
{
    $res = Card::whereRaw(1);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $list = [];
    if ($res) {
        foreach ($res as $row) {
            $row['format_card_fee'] = price_format($row['card_fee'], false);
            $row['format_free_money'] = price_format($row['free_money'], false);
            $list[] = $row;
        }
    }

    return $list;
}

/**
 * 取得贺卡信息
 * @param int $card_id 贺卡id
 * @return  array   贺卡信息
 */
function card_info($card_id)
{
    $res = Card::where('card_id', $card_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 根据订单中商品总额获得需要支付的贺卡费用
 *
 * @access  public
 * @param integer $card_id
 * @param float $goods_amount
 * @return  float
 */
function card_fee($card_id, $goods_amount)
{
    $card = card_info($card_id);

    return ($card['free_money'] <= $goods_amount && $card['free_money'] > 0) ? 0 : $card['card_fee'];
}

/**
 * 取得订单信息
 * @param int $order_id 订单id（如果order_id > 0 就按id查，否则按sn查）
 * @param string $order_sn 订单号
 * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
 */
function order_info($order_id, $order_sn = '', $seller_id = -1)
{
    $ValueCardLib = app(ValueCardService::class);
    $PaymentLib = app(PaymentService::class);

    /* 计算订单各种费用之和的语句 */
    if (CROSS_BORDER === true) // 跨境多商户
    {
        $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + rate_fee) AS total_fee ";
    } else {
        $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";
    }

    $order_id = intval($order_id);

    if ($order_id > 0) {
        //@模板堂-bylu 这里连表查下支付方法表,获取到"pay_code"字段值;
        $order = OrderInfo::selectRaw("*, $total_fee")->where('order_id', $order_id);
    } else {
        //@模板堂-bylu 这里连表查下支付方法表,获取到"pay_code"字段值;
        $order = OrderInfo::selectRaw("*, $total_fee")->where('order_sn', $order_sn);
    }

    if ($seller_id > -1) {
        $order = $order->where('ru_id', $seller_id);
    }

    $order = $order->with([
        'getOrderGoods',
        'getRegionProvince' => function ($query) {
            $query->select('region_id', 'region_name');
        },
        'getRegionCity' => function ($query) {
            $query->select('region_id', 'region_name');
        },
        'getRegionDistrict' => function ($query) {
            $query->select('region_id', 'region_name');
        },
        'getRegionStreet' => function ($query) {
            $query->select('region_id', 'region_name');
        },
        'getSellerNegativeOrder'
    ]);

    $order = app(BaseRepository::class)->getToArrayFirst($order);

    if (empty($order)) {
        return [];
    }

    if ($order) {
        if ($order['cost_amount'] <= 0) {
            $order['cost_amount'] = goods_cost_price($order['order_id']);
        }
        /*获取发票ID start*/
        $user_id = $order['user_id'];
        $order['invoice_id'] = OrderInvoice::whereHas('getOrder', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->value('invoice_id');
        /*获取发票ID end*/

        $where = [
            'order_id' => $order_id
        ];

        $value_card = $ValueCardLib->getValueCardInfo($where);

        $order['use_val'] = isset($value_card['use_val']) ? $value_card['use_val'] : 0;
        $order['vc_dis'] = isset($value_card['vc_dis']) ? $value_card['vc_dis'] : '';

        $payWhere = [
            'pay_id' => $order['pay_id'],
            'enabled' => 1
        ];
        $payment = $PaymentLib->getPaymentInfo($payWhere);

        $order['pay_code'] = isset($payment['pay_code']) ? $payment['pay_code'] : '';
        $order['child_order'] = get_seller_order_child($order['order_id'], $order['main_order_id']);

        $order['formated_goods_amount'] = price_format($order['goods_amount'], false);
        $order['formated_cost_amount'] = $order['cost_amount'] > 0 ? price_format($order['cost_amount'], false) : 0;
        $order['formated_profit_amount'] = price_format($order['total_fee'] - $order['cost_amount'] - $order['shipping_fee'], false);
        $order['formated_discount'] = price_format($order['discount'], false);
        $order['formated_tax'] = price_format($order['tax'], false);
        $order['formated_shipping_fee'] = price_format($order['shipping_fee'], false);
        $order['formated_insure_fee'] = price_format($order['insure_fee'], false);
        $order['formated_pay_fee'] = price_format($order['pay_fee'], false);
        $order['formated_pack_fee'] = price_format($order['pack_fee'], false);
        $order['formated_card_fee'] = price_format($order['card_fee'], false);
        $order['formated_total_fee'] = price_format($order['total_fee'], false);
        $order['formated_money_paid'] = price_format($order['money_paid'], false);
        $order['formated_bonus'] = price_format($order['bonus'], false);
        $order['formated_coupons'] = price_format($order['coupons'], false);
        $order['formated_integral_money'] = price_format($order['integral_money'], false);
        $order['formated_value_card'] = price_format($order['use_val'], false);
        $order['formated_vc_dis'] = (float)$order['vc_dis'] * 10;
        $order['formated_surplus'] = price_format($order['surplus'], false);
        $order['formated_order_amount'] = price_format(abs($order['order_amount']), false);
        $order['formated_realpay_amount'] = price_format($order['money_paid'], false);
        $order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
        $order['pay_points'] = $order['integral']; //by kong  获取积分

        /* 取得区域名 */
        $province = $order['get_region_province']['region_name'] ?? '';
        $city = $order['get_region_city']['region_name'] ?? '';
        $district = $order['get_region_district']['region_name'] ?? '';
        $street = $order['get_region_street']['region_name'] ?? '';
        $order['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $order['formated_rate_fee'] = price_format($order['rate_fee'], false);
        }

        // 推荐人
        if ($order['parent_id'] > 0 && $order['parent_id'] != $order['user_id']) {
            $user = Users::query()->select('user_name', 'reg_time')->where('user_id', $order['parent_id']);
            $user = app(BaseRepository::class)->getToArrayFirst($user);
            if (!empty($user)) {
                $user['reg_time_format'] = local_date($GLOBALS['_CFG']['time_format'], $user['reg_time']);
            }
            $order['parent'] = $user;
        }

        if (empty($order['confirm_take_time']) && $order['order_status'] == OS_CONFIRMED && $order['shipping_status'] == SS_RECEIVED && $order['shipping_status'] == PS_PAYED) {
            $log_time = OrderAction::where('order_status', OS_CONFIRMED)
                ->where('shipping_status', SS_RECEIVED)
                ->where('pay_status', PS_PAYED)
                ->where('order_id', $order['order_id'])
                ->value('log_time');

            $other['confirm_take_time'] = $log_time;

            OrderInfo::where('order_id', $order['order_id'])->update($other);

            $order['confirm_take_time'] = $log_time;
        }

        if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
            $order['mobile'] = app(DscRepository::class)->stringToStar($order['mobile']);
            $order['tel'] = app(DscRepository::class)->stringToStar($order['tel']);
        }

    }
    return $order;
}

/**
 * 判断订单是否已完成
 * @param array $order 订单信息
 * @return  bool
 */
function order_finished($order)
{
    return $order['order_status'] == OS_CONFIRMED &&
        ($order['shipping_status'] == SS_SHIPPED || $order['shipping_status'] == SS_RECEIVED) &&
        ($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYING);
}

/*
 * 获取主订单的订单数量
 */
function get_seller_order_child($order_id, $main_order_id)
{
    $count = 0;
    if ($main_order_id == 0) {
        $count = OrderInfo::where('main_order_id', $order_id)->count();
    }
    return $count;
}

/**
 * 取得订单商品
 * @param int $order_id 订单id
 * @return  array   订单商品数组
 */
function order_goods($order_id)
{
    $res = OrderGoods::selectRaw("*, (goods_price * goods_number) AS subtotal")
        ->where('order_id', $order_id);

    $res = $res->with([
        'getGoods' => function ($query) {
            if (CROSS_BORDER === true) // 跨境多商户
            {
                $query = $query->select('goods_id', 'shop_price', 'is_shipping', 'goods_weight AS goodsweight', 'goods_img', 'give_integral', 'goods_thumb', 'goods_cause', 'free_rate');
            } else {
                $query = $query->select('goods_id', 'shop_price', 'is_shipping', 'goods_weight AS goodsweight', 'goods_img', 'give_integral', 'goods_thumb', 'goods_cause');
            };

            $query->with([
                'getGoodsConsumption'
            ]);
        },
        'getOrder' => function ($query) {
            $query->select('order_id', 'extension_code AS order_extension_code', 'extension_id');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $goods_list = [];
    if ($res) {
        foreach ($res as $row) {

            $goods = $row['get_goods'] ?? [];

            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods']);
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_order']);

            if ($row['extension_code'] == 'package_buy') {
                $row['package_goods_list'] = app(PackageGoodsService::class)->getPackageGoods($row['goods_id']);
            }

            $row['give_integral'] = isset($row['give_integral']) ? $row['give_integral'] : 0;
            if ($row['give_integral'] == '-1') {
                $order = array();
                $order['extension_code'] = $row['extension_code'];
                $order['extension_id'] = $row['extension_id'];
                $order['order_id'] = $row['order_id'];
                $integral = integral_to_give($order, $row['rec_id']);
                $row['give_integral'] = intval($integral['custom_points']);
            }
            $row['warehouse_name'] = RegionWarehouse::where('region_id', $row['warehouse_id'])->value('region_name');

            //ecmoban模板堂 --zhuo start 商品金额促销
            $row['goods_amount'] = $row['goods_price'] * $row['goods_number'];
            if (isset($goods['get_goods_consumption']) && $goods['get_goods_consumption']) {
                $row['amount'] = app(DscRepository::class)->getGoodsConsumptionPrice($goods['get_goods_consumption'], $row['goods_amount']);
            } else {
                $row['amount'] = $row['goods_amount'];
            }

            $row['dis_amount'] = $row['goods_amount'] - $row['amount'];
            $row['discount_amount'] = price_format($row['dis_amount'], false);
            //ecmoban模板堂 --zhuo end 商品金额促销

            if ($row['order_extension_code'] == "presale" && !empty($row['extension_id'])) {
                $row['url'] = app(DscRepository::class)->buildUri('presale', ['act' => 'view', 'presaleid' => $row['extension_id']], $row['goods_name']);
            } elseif ($row['order_extension_code'] == "group_buy") {
                $row['url'] = app(DscRepository::class)->buildUri('group_buy', ['gbid' => $row['extension_id']]);
            } elseif ($row['order_extension_code'] == "snatch") {
                $row['url'] = app(DscRepository::class)->buildUri('snatch', ['sid' => $row['extension_id']]);
            } elseif (substr($row['extension_code'], 0, 7) == "seckill") {
                $row['url'] = app(DscRepository::class)->buildUri('seckill', ['act' => "view", 'secid' => $row['extension_id']]);
            } elseif ($row['order_extension_code'] == "auction") {
                $row['url'] = app(DscRepository::class)->buildUri('auction', ['auid' => $row['extension_id']]);
            } elseif ($row['order_extension_code'] == "exchange_goods") {
                $row['url'] = app(DscRepository::class)->buildUri('exchange_goods', ['gid' => $row['extension_id']]);
            } else {
                $row['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            }

            $row['shop_name'] = app(MerchantCommonService::class)->getShopName($row['ru_id'], 1); //店铺名称
            $row['shopUrl'] = app(DscRepository::class)->buildUri('merchants_store', ['urid' => $row['ru_id']]);
            $row['goods_img'] = isset($row['goods_img']) ? $row['goods_img'] : '';
            $row['goods_img'] = get_image_path($row['goods_img']);

            //图片显示
            $row['goods_thumb'] = isset($row['goods_thumb']) ? $row['goods_thumb'] : '';
            $row['goods_thumb'] = get_image_path($row['goods_thumb']);

            //是否申请退货或者退款
            $row['is_return'] = ReturnGoods::where('rec_id', $row['rec_id'])->count();
            $goods_list[] = $row;
        }
    }
    return $goods_list;
}

/**
 * 取得订单总金额
 * @param int $order_id 订单id
 * @param bool $include_gift 是否包括赠品
 * @return  float   订单总金额
 */
function order_amount($order_id, $include_gift = true)
{
    $res = OrderGoods::selectRaw("SUM(goods_price * goods_number) as total")->where('order_id', $order_id);

    if (!$include_gift) {
        $res = $res->where('is_gift', 0);
    }

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    $total = $res ? $res['total'] : 0;

    return floatval($total);
}

/**
 * 取得某订单商品总重量和总金额（对应 cart_weight_price）
 * @param int $order_id 订单id
 * @return  array   ('weight' => **, 'amount' => **, 'formated_weight' => **)
 */
function order_weight_price($order_id)
{
    $row = OrderGoods::where('order_id', $order_id)
        ->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_weight');
            }
        ]);

    $row = app(BaseRepository::class)->getToArrayGet($row);

    $weight = 0;
    $amount = 0;
    $number = 0;
    if ($row) {
        foreach ($row as $key => $val) {
            $val = app(BaseRepository::class)->getArrayMerge($val, $val['get_goods']);
            $val['goods_weight'] = isset($val['goods_weight']) ? $val['goods_weight'] : 0;
            $weight += $val['goods_weight'] * $val['goods_number'];
            $amount += $val['goods_price'] * $val['goods_number'];
            $number += $val['goods_number'];
        }
    }

    $arr['weight'] = floatval($weight);
    $arr['amount'] = floatval($amount);
    $arr['number'] = intval($number);

    /* 格式化重量 */
    $arr['formated_weight'] = formated_weight($arr['weight']);

    return $arr;
}

/**
 * 获得订单中的费用信息
 *
 * @access  public
 * @param array $order
 * @param array $goods
 * @param array $consignee
 * @param bool $is_gb_deposit 是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
 * @return  array
 */
function order_fee($order, $goods, $consignee, $type = 0, $cart_value = '', $pay_type = 0, $cart_goods_list = '', $warehouse_id = 0, $area_id = 0, $store_id = 0, $store_type = '', $user_id = 0, $rank_id = 0, $rec_type = CART_GENERAL_GOODS)
{
    if (empty($user_id)) {
        $user_id = session('user_id', 0);
    }

    $step = '';
    $shipping_list = [];
    if (is_array($type)) {
        $step = isset($type['step']) ? $type['step'] : '';
        $shipping_list = isset($type['shipping_list']) ? $type['shipping_list'] : [];
        $type = isset($type['type']) ? $type['type'] : 0;
    }

    /* 初始化订单的扩展code */
    if (!isset($order['extension_code'])) {
        $order['extension_code'] = '';
    }

    $GroupBuyLib = app(GroupBuyService::class);
    if ($order['extension_code'] == 'group_buy') {
        $where = [
            'group_buy_id' => $order['extension_id'],
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'user_id' => $user_id
        ];
        $group_buy = $GroupBuyLib->getGroupBuyInfo($where);
    }

    if ($order['extension_code'] == 'presale') {
        $presale = app(PresaleService::class)->presaleInfo($order['extension_id']);
    }

    $total = [
        'real_goods_count' => 0,
        'gift_amount' => 0,
        'goods_price' => 0,
        'cost_price' => 0,
        'market_price' => 0,
        'discount' => 0,
        'pack_fee' => 0,
        'card_fee' => 0,
        'shipping_fee' => 0,
        'shipping_insure' => 0,
        'integral_money' => 0,
        'bonus' => 0,
        'value_card' => 0, //储值卡
        'coupons' => 0, //优惠券 bylu
        'surplus' => 0,
        'cod_fee' => 0,
        'pay_fee' => 0,
        'tax' => 0,
        'presale_price' => 0,
        'dis_amount' => 0,
        'goods_price_formated' => 0,
        'seller_amount' => []
    ];

    /* 商品总价 */

    $arr = [];
    foreach ($goods as $key => $val) {
        /* 统计实体商品的个数 */
        if ($val['is_real']) {
            $total['real_goods_count']++;
        }
        //ecmoban模板堂 --zhuo start 商品金额促销
        $arr[$key]['goods_amount'] = $val['goods_price'] * $val['goods_number'];
        $total['goods_price_formated'] += $arr[$key]['goods_amount'];

        $goods_con = app(CartCommonService::class)->getConGoodsAmount($arr[$key]['goods_amount'], $val['goods_id'], 0, 0, $val['parent_id']);

        $goods_con['amount'] = explode(',', $goods_con['amount']);
        $arr[$key]['amount'] = min($goods_con['amount']);

        $total['goods_price'] += $arr[$key]['amount'];
        $cost_price = get_cost_price($val['goods_id']);
        $total['cost_price'] += $cost_price * $val['goods_number'];
        @$total['seller_amount'][$val['ru_id']] += $arr[$key]['amount'];
        //ecmoban模板堂 --zhuo end 商品金额促销
        if (isset($val['get_presale_activity']['deposit']) && $val['get_presale_activity']['deposit'] >= 0 && $val['rec_type'] == CART_PRESALE_GOODS) {
            $total['presale_price'] += $val['get_presale_activity']['deposit'] * $val['goods_number'];//预售定金
        }
        $total['market_price'] += $val['market_price'] * $val['goods_number'];
        $total['dis_amount'] += $val['dis_amount'];
    }

    $total['saving'] = $total['market_price'] - $total['goods_price'];
    $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

    $total['goods_price_formated'] = price_format($total['goods_price_formated'], false);
    $total['market_price_formated'] = price_format($total['market_price'], false);
    $total['saving_formated'] = price_format($total['saving'], false);
    $total['dis_amount_formated'] = price_format($total['dis_amount'], false);

    /* 折扣 */
    if ($order['extension_code'] != 'group_buy') {
        $discount = compute_discount(3, $cart_value, 0, 0, $user_id, $rank_id, $rec_type);
        $total['discount'] = $discount['discount'];
        if ($total['discount'] > $total['goods_price']) {
            $total['discount'] = $total['goods_price'];
        }
    }

    $bonus_amount = $total['discount'];
    $total['discount_formated'] = price_format($total['discount'], false);

    /* 税额 */
    if ($GLOBALS['_CFG']['can_invoice'] == 1 && isset($order['inv_content'])) {
        $total['tax'] = app(CommonRepository::class)->orderInvoiceTotal($total['goods_price'], $order['inv_content']);
    } else {
        $total['tax'] = 0;
    }

    $total['tax_formated'] = price_format($total['tax'], false);
    /* 包装费用 */
    if (!empty($order['pack_id'])) {
        $total['pack_fee'] = pack_fee($order['pack_id'], $total['goods_price']);
    }
    $total['pack_fee_formated'] = price_format($total['pack_fee'], false);

    /* 贺卡费用 */
    if (!empty($order['card_id'])) {
        $total['card_fee'] = card_fee($order['card_id'], $total['goods_price']);
    }
    $total['card_fee_formated'] = price_format($total['card_fee'], false);

    /* 红包 */
    if (!empty($order['bonus_id'])) {
        $bonus = bonus_info($order['bonus_id']);
        $total['bonus'] = $bonus['type_money'];
        $total['admin_id'] = $bonus['admin_id']; //ecmoban模板堂 --zhuo
    }

    $total['bonus_formated'] = price_format($total['bonus'], false);

    /* 线下红包 */
    if (!empty($order['bonus_kill'])) {
        $total['bonus_kill'] = $order['bonus_kill'];
        $total['bonus_kill_formated'] = price_format($total['bonus_kill'], false);
    }

    $coupons = [];
    if (isset($order['uc_id']) && !empty($order['uc_id'])) {
        $coupons = app(CouponsUserService::class)->getCoupons($order['uc_id'], -1, $user_id);
        $coupons['cou_money'] = $coupons['uc_money'] > 0 ? $coupons['uc_money'] : $coupons['cou_money'];
    }

    /* 优惠券 非免邮 */
    if (!empty($coupons)) {
        if ($coupons['cou_type'] != 5) {
            $total['coupons'] = $coupons['cou_money'];// 优惠券面值 bylu
        }
    }

    $total['coupons_formated'] = price_format($total['coupons'], false);

    /* 储值卡 */
    if (!empty($order['vc_id'])) {
        $value_card = value_card_info($order['vc_id']);
        $total['value_card'] = $value_card['card_money'];
        $total['card_dis'] = $value_card['vc_dis'] < 1 ? $value_card['vc_dis'] * 10 : '';
        $total['vc_dis'] = $value_card['vc_dis'] ? $value_card['vc_dis'] : 1;
    }

    /* 配送费用 */
    $shipping_cod_fee = null;
    if ($store_id > 0 || $total['real_goods_count'] == 0 || $store_type) {
        $total['shipping_fee'] = 0;
    } else {
        $total['shipping_fee'] = get_order_shipping_fee($cart_goods_list, $consignee, $shipping_list, $step, $coupons);
    }

    $total['shipping_fee_formated'] = price_format($total['shipping_fee'], false);
    $total['shipping_insure_formated'] = price_format($total['shipping_insure'], false);

    // 扣除优惠活动金额，红包和积分最多能支付的金额为商品总额
    if ($total['goods_price'] > 0) {
        if ($total['goods_price'] >= $bonus_amount) {
            $max_amount = $total['goods_price'] - $bonus_amount;
        } else {
            $max_amount = 0;
        }
    } else {
        $max_amount = $total['goods_price'];
    }

    $use_value_card = 0;
    /* 计算订单总额 */
    if (isset($group_buy['deposit']) && $order['extension_code'] == 'group_buy' && $group_buy['deposit'] > 0) {
        $total['amount'] = $total['goods_price'] + $total['shipping_fee'];
    } elseif (isset($presale['deposit']) && $order['extension_code'] == 'presale' && $presale['deposit'] >= 0) {
        $total['amount'] = $total['presale_price'] + $total['shipping_fee'];
    } else {
        if (!empty($order['vc_id']) && $total['value_card'] > 0) {//使用储值卡 计算储值卡本身折扣
            $total['amount'] = ($total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee']) * $total['vc_dis'] +
                $total['shipping_insure'] + $total['cod_fee'];
        } else {
            $total['amount'] = $total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee'] +
                $total['shipping_insure'] + $total['cod_fee'];
        }

        // 减去红包金额  //红包支付，如果红包的金额大于订单金额 则去订单金额定义为红包金额的最终结果(相当于订单金额减去本身的金额，为0) ecmoban模板堂 --zhuo
        $use_bonus = min($total['bonus'], $max_amount); // 实际减去的红包金额
        $use_coupons = $total['coupons']; //优惠券抵扣金额

        //还需要支付的订单金额
        if (isset($total['bonus_kill'])) {
            if ($total['amount'] > $total['bonus_kill']) {
                $total['amount'] -= $price = number_format($total['bonus_kill'], 2, '.', '');
            } else {
                $total['amount'] = 0;
            }
        }

        $total['bonus'] = $use_bonus;
        $total['bonus_formated'] = price_format($total['bonus'], false);

        $total['coupons'] = $use_coupons;
        $total['coupons_formated'] = price_format($total['coupons'], false);

        //还需要支付的订单金额 start
        if ($use_bonus > $total['amount']) {
            $total['amount'] = 0;
        } else {
            $total['amount'] -= $use_bonus;
        }

        if ($use_coupons > $total['amount']) {
            $total['amount'] = 0;
        } else {
            $total['amount'] -= $use_coupons;
        }
        //还需要支付的订单金额 end

        $total['amount'] += $total['shipping_fee'];

        $max_amount -= $use_bonus + $use_coupons; // 积分最多还能支付的金额
    }

    /* 积分 */
    $order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;
    if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0) {
        $integral_money = app(DscRepository::class)->valueOfIntegral($order['integral']);

        // 使用积分支付
        $use_integral = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
        $total['amount'] -= $use_integral;
        $total['integral_money'] = $use_integral;
        $order['integral'] = app(DscRepository::class)->integralOfValue($use_integral);
    } else {
        $total['integral_money'] = 0;
        $order['integral'] = 0;
    }

    $total['integral'] = $order['integral'];
    $total['integral_formated'] = price_format($total['integral_money'], false);

    if (!empty($order['vc_id']) && $total['value_card'] > 0) {
        $value1 = $total['value_card']; //储值卡余额
        $value2 = ($total['amount'] - $total['shipping_fee']) * $total['vc_dis']; //使用储值卡折后订单需支付金额
        $use_value_card = min($value1, $value2); //实际减去的储值卡金额
        $total['value_card_formated'] = price_format($use_value_card, false); //实际使用的储值卡金额
        $total['use_value_card'] = $use_value_card;
    }

    //使用储值卡支付
    if ($total['amount'] >= $use_value_card) {
        $total['amount'] -= $use_value_card;
    }

    /* 余额 */
    $order['surplus'] = $order['surplus'] > 0 ? $order['surplus'] : 0;
    if ($total['amount'] > 0) {
        if (isset($order['surplus']) && $order['surplus'] > $total['amount']) {
            $order['surplus'] = $total['amount'];
            $total['amount'] = 0;
        } else {
            $total['amount'] -= floatval($order['surplus']);
        }
    } else {
        $order['surplus'] = 0;
        $total['amount'] = 0;
    }
    $total['surplus'] = $order['surplus'];
    $total['surplus_formated'] = price_format($order['surplus'], false);

    /* 保存订单信息 */
    session([
        'flow_order' => $order
    ]);

    $se_flow_type = session('flow_type', '');

    /* 支付费用 */
    if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 || $se_flow_type != CART_EXCHANGE_GOODS)) {
        $total['pay_fee'] = pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
    }

    $total['pay_fee_formated'] = price_format($total['pay_fee'], false);

    $total['amount'] += $total['pay_fee']; // 订单总额累加上支付费用
    $total['amount_formated'] = price_format($total['amount'], false);

    /* 取得可以得到的积分和红包 */
    if ($order['extension_code'] == 'group_buy') {
        $total['will_get_integral'] = $group_buy['gift_integral'] ?? 0;
    } elseif ($order['extension_code'] == 'exchange_goods') {
        $total['will_get_integral'] = 0;
    } else {
        $total['will_get_integral'] = get_give_integral($cart_value, $user_id);
    }

    $total_bonus = app(FlowActivityService::class)->getTotalBonus($user_id);
    $total['will_get_bonus'] = $order['extension_code'] == 'exchange_goods' ? 0 : price_format($total_bonus, false);
    $total['formated_goods_price'] = price_format($total['goods_price'], false);
    $total['formated_market_price'] = price_format($total['market_price'], false);
    $total['formated_saving'] = price_format($total['saving'], false);

    if ($order['extension_code'] == 'exchange_goods') {
        $exchange_integral = Cart::select('goods_id', 'goods_number')
            ->where('rec_type', CART_EXCHANGE_GOODS)->where('is_gift', 0)->where('goods_id', '>', 0);

        $exchange_integral = $exchange_integral->whereHas('getExchangeGoods');

        $exchange_integral = $exchange_integral->with([
            'getExchangeGoods' => function ($query) {
                $query->select('goods_id', 'exchange_integral');
            }
        ]);

        if (!empty($user_id)) {
            $exchange_integral = $exchange_integral->where('user_id', $user_id);
        } else {
            $session_id = app(SessionRepository::class)->realCartMacIp();
            $exchange_integral = $exchange_integral->where('session_id', $session_id);
        }

        $exchange_integral = app(BaseRepository::class)->getToArrayGet($exchange_integral);

        $integral_num = 0;
        if ($exchange_integral) {
            foreach ($exchange_integral as $key => $row) {
                $row = $row['get_exchange_goods'] ? array_merge($row, $row['get_exchange_goods']) : $row;

                $integral_num += $row['exchange_integral'] * $row['goods_number'];
            }
        }

        $total['exchange_integral'] = $integral_num;
    }

    return $total;
}

/**
 * 取得可用的配送区域的运费
 *
 * @param $cart_goods 购物车商品
 * @param string $consignee 收货信息
 * @param string $shipping_list 配送方式列表
 * @param string $step 步骤
 * @param string $coupons 优惠券
 * @return int
 */
function get_order_shipping_fee($cart_goods, $consignee = '', $shipping_list = '', $step = '', $coupons = '')
{
    $step_array = ['insert_Consignee'];
    $shipping_fee = 0;

    if ($cart_goods) {
        if (!empty($shipping_list)) {
            if (!is_array($shipping_list)) {
                $shipping_list = explode(",", $shipping_list);
            }
        } else {
            $shipping_list = [];
        }

        $have_shipping = 0;
        if (empty($shipping_list)) {
            foreach ($cart_goods as $key => $row) {
                $shipping = isset($row['shipping']) ? $row['shipping'] : [];
                if ($shipping) {
                    if (!empty($step) && in_array($step, $step_array)) {
                        $str_shipping = '';
                        foreach ($shipping as $skey => $srow) {
                            $str_shipping .= $srow['shipping_id'] . ",";
                        }

                        $str_shipping = app(DscRepository::class)->delStrComma($str_shipping);
                        $str_shipping = explode(",", $str_shipping);
                        if (isset($row['tmp_shipping_id']) && $row['tmp_shipping_id'] && in_array($row['tmp_shipping_id'], $str_shipping)) {
                            $have_shipping = 1;
                        } else {
                            $have_shipping = 0;
                        }
                    }

                    foreach ($shipping as $kk => $vv) {
                        if (!empty($step) && in_array($step, $step_array)) {
                            if ($have_shipping == 0) {
                                if (isset($vv['default']) && $vv['default'] == 1) {
                                    $row['tmp_shipping_id'] = $vv['shipping_id'];
                                } elseif ($kk == 0) {
                                    $row['tmp_shipping_id'] = $vv['shipping_id'];
                                }
                            } else {
                                if (isset($vv['default']) && $vv['default'] == 1) {
                                    if ($row['tmp_shipping_id'] != $vv['shipping_id']) {
                                        $row['tmp_shipping_id'] = $vv['shipping_id'];
                                    }
                                }
                            }
                        }

                        /* 优惠券 免邮 start */
                        if (!empty($coupons) && $row['ru_id'] == $coupons['ru_id']) {
                            if ($coupons['cou_type'] == 5) {
                                if ($row['goods_amount'] >= $coupons['cou_man'] || $coupons['cou_man'] == 0) {
                                    $cou_region = get_coupons_region($coupons['cou_id']);
                                    $cou_region = !empty($cou_region) ? explode(",", $cou_region) : [];
                                    if ($cou_region) {
                                        if (!in_array($consignee['province'], $cou_region)) {
                                            $vv['shipping_fee'] = 0;
                                        }
                                    } else {
                                        $vv['shipping_fee'] = 0;
                                    }
                                }
                            }
                        }
                        /* 优惠券 免邮 end */

                        //结算页切换配送方式
                        if (isset($row['tmp_shipping_id'])) {
                            if (isset($vv['shipping_id'])) {
                                if ($row['tmp_shipping_id'] == $vv['shipping_id']) {
                                    //自营时--自提时运费清0
                                    if (isset($row['shipping_code']) && $row['shipping_code'] == 'cac') {
                                        $vv['shipping_fee'] = 0;
                                    }
                                    $shipping_fee += $vv['shipping_fee'];
                                }
                            }
                        } else {
                            if ($vv['default'] == 1) {
                                //自营时--自提时运费清0
                                if ($row['shipping_code'] == 'cac') {
                                    $vv['shipping_fee'] = 0;
                                }
                                $shipping_fee += $vv['shipping_fee'];
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($cart_goods as $key => $row) {
                if ($row['shipping']) {
                    foreach ($row['shipping'] as $skey => $srow) {
                        if ($shipping_list[$key] == $srow['shipping_id'] && $srow['shipping_code'] != 'cac') {
                            $shipping_fee += $srow['shipping_fee'];
                        }
                    }
                }
            }
        }
    }

    return $shipping_fee;
}

/**
 * 修改智能权重里的商品退换货数量
 * @param int $goods_id 订单商品id
 * @param array $return_num 商品退换货数量
 * @return  bool
 */
function update_return_num($goods_id, $return_num)
{
    $res = IntelligentWeight::select('return_number', 'goods_number')->where('goods_id', $goods_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    $res['return_number'] = isset($res['return_number']) ? $res['return_number'] : 0;
    $res['goods_number'] = isset($res['goods_number']) ? $res['goods_number'] : 0;

    $return_num['goods_number'] = $res['goods_number'] - $return_num['return_number'];
    if ($res) {
        $return_num['return_number'] += $res['return_number'];

        $res = IntelligentWeight::where('goods_id', $goods_id)->update($return_num);
        return $res;
    } else {
        $id = IntelligentWeight::insertGetId($return_num);
        return $id;
    }
}

/**
 * 修改订单
 *
 * @param int $order_id
 * @param array $order
 * @return bool
 * @throws Exception
 */
function update_order($order_id = 0, $order = [])
{

    if (empty($order_id) || empty($order)) {
        return false;
    }

    /* 会员中心手动余额输入支付 */
    $old_order_amount = isset($order['old_order_amount']) ? floatval($order['old_order_amount']) : 0;
    $old_order_amount = app(DscRepository::class)->changeFloat($old_order_amount);

    $request_surplus = isset($order['request_surplus']) ? floatval($order['request_surplus']) : 0;
    $request_surplus = app(DscRepository::class)->changeFloat($request_surplus);

    $is_order_amount = isset($order['order_amount']) ? 1 : 0;
    $order['order_amount'] = $is_order_amount ? floatval($order['order_amount']) : 0;

    $new_amount = $order['order_amount'] + $request_surplus;
    $new_amount = app(DscRepository::class)->changeFloat($new_amount);

    /* 获取表字段 */
    $order_other = app(BaseRepository::class)->getArrayfilterTable($order, 'order_info');

    /* 操作主订单更新子订单 */
    if ($is_order_amount == 1 && $order['order_amount'] <= 0) {
        if (isset($order['main_count']) && $order['main_count'] > 0) {
            $order_other['main_pay'] = 2;
        }
    }

    $update = OrderInfo::where('order_id', $order_id)->update($order_other);

    if ($request_surplus) {
        $surplus = $request_surplus;
    } else {
        $surplus = $order['surplus'] ?? 0;
    }

    /* 操作主订单更新子订单 */
    if ($is_order_amount == 1 && (($order['order_amount'] <= 0) || ($order['order_amount'] > 0 && $request_surplus > 0 && $old_order_amount == $new_amount))) {
        if (isset($order['main_count']) && $order['main_count'] > 0) {

            $child_list = OrderInfo::select('order_id', 'order_sn', 'order_status', 'shipping_status', 'pay_status', 'order_amount', 'surplus', 'money_paid')
                ->where('main_order_id', $order_id)
                ->where('pay_status', '<>', PS_PAYED);

            $child_list = app(BaseRepository::class)->getToArrayGet($child_list);

            if ($child_list) {
                $dbRaw = [];
                foreach ($child_list as $key => $val) {
                    if (isset($order['order_status'])) {
                        $dbRaw['order_status'] = $order['order_status'];
                    }

                    if (isset($order['shipping_status'])) {
                        $dbRaw['shipping_status'] = $order['shipping_status'];
                    }

                    if (isset($order['pay_status'])) {
                        $dbRaw['pay_status'] = $order['pay_status'];
                    }

                    $order_amount = $val['order_amount'];
                    if ($val['pay_status'] != PS_PAYED) {
                        if ($surplus > 0) {
                            if ($order_amount > 0) {
                                if ($surplus >= $order_amount) {
                                    $dbRaw['order_amount'] = 0;
                                    $surplus = $surplus - $order_amount;

                                    if ($val['surplus'] > 0) {
                                        $dbRaw['surplus'] = $order_amount + $val['surplus'];
                                    } else {
                                        $dbRaw['surplus'] = $order_amount;
                                    }
                                } else {
                                    $dbRaw['order_amount'] = $order_amount - $surplus;

                                    if ($val['surplus'] > 0) {
                                        $dbRaw['surplus'] = $surplus + $val['surplus'];
                                    } else {
                                        $dbRaw['surplus'] = $surplus;
                                    }

                                    $surplus = 0;
                                }

                                if ($dbRaw['order_amount'] <= 0) {
                                    $dbRaw['pay_status'] = PS_PAYED;
                                }
                            }
                        }
                    }

                    $other = app(BaseRepository::class)->getDbRaw($dbRaw);

                    OrderInfo::where('order_id', $val['order_id'])->update($other);

                    $username = '';
                    if ($request_surplus > 0) {
                        $username = lang('common.buyer');
                    }

                    /* 记录订单操作记录 */
                    order_action($val['order_sn'], $other['order_status'], $other['shipping_status'], $other['pay_status'], lang('common.main_order_pay'), $username);
                }
            }

        }
    }

    return $update;
}

/**
 * 得到新订单号
 * @return  string
 */
function get_order_sn()
{
    $time = explode(" ", microtime());
    $time = $time[1] . ($time[0] * 1000);
    $time = explode(".", $time);
    $time = isset($time[1]) ? $time[1] : 0;
    $time = local_date('YmdHis') + $time;

    /* 选择一个随机的方案 */
    mt_srand((double)microtime() * 1000000);
    return $time . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 取得购物车商品
 *
 * @param int $type 类型：默认普通商品
 * @param string $cart_value
 * @param int $ru_type
 * @param int $warehouse_id
 * @param int $area_id
 * @param int $area_city
 * @param string $consignee
 * @param int $store_id
 * @param int $user_id
 * @param int $is_virtual
 * @return mixed
 * @throws Exception
 */
function cart_goods($type = CART_GENERAL_GOODS, $cart_value = '', $ru_type = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $consignee = '', $store_id = 0, $user_id = 0, $is_virtual = 0)
{
    if (empty($user_id)) {
        $user_id = session('user_id', 0);
    }

    $rec_txt = [
        lang('common.rec_txt.1'),
        lang('common.rec_txt.2'),
        lang('common.rec_txt.3'),
        lang('common.rec_txt.4'),
        lang('common.rec_txt.5'),
        lang('common.rec_txt.6'),
        lang('common.rec_txt.10'),
        lang('common.rec_txt.12'),
        lang('common.rec_txt.13'),
        CART_OFFLINE_GOODS => lang('common.rec_txt.offline'),
    ];

    $arr = Cart::selectRaw('*, goods_price * goods_number AS subtotal')
        ->where('rec_type', $type);

    $cart_value = app(BaseRepository::class)->getExplode($cart_value);

    if ($cart_value) {
        $arr = $arr->whereIn('rec_id', $cart_value);
    }

    if (CROSS_BORDER === true) // 跨境多商户
    {
        if (empty($cart_value)) {
            return [];
        }
    }

    $where = [
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'area_city' => $area_city,
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
    ];
    if ($GLOBALS['_CFG']['open_area_goods'] == 1) {
        //兼容超值礼包购买 商品id和购物车商品id关联
        $arr = $arr->where(function ($query) use($where){
            $query->where(function ($query) use($where){
                $query->whereHas('getGoods', function ($query) use ($where) {
                    app(DscRepository::class)->getAreaLinkGoods($query, $where['area_id'], $where['area_city']);
                });
                $query->where('extension_code','<>', 'package_buy');
            });

            $query->orWhere('extension_code', 'package_buy');
        });
    }

    if ($store_id) {
        $arr = $arr->where('store_id', $store_id);
    }

    if (!empty($user_id)) {
        $arr = $arr->where('user_id', $user_id);
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $arr = $arr->where('session_id', $session_id);
    }

    $goodsWhere = [
        'type' => $type,
        'presale' => CART_PRESALE_GOODS,
        'goods_select' => [
            'goods_id', 'cat_id', 'goods_thumb', 'default_shipping',
            'goods_weight as goodsweight', 'is_shipping', 'freight',
            'tid', 'shipping_fee', 'brand_id', 'cloud_id', 'is_delete',
            'cloud_goodsname', 'dis_commission', 'is_distribution',
            'goods_number as number'
        ]
    ];

    if (CROSS_BORDER === true) // 跨境多商户
    {
        array_push($goodsWhere['goods_select'], 'free_rate');
    }

    if (file_exists(MOBILE_DRP)) {
        // 分销
        array_push($goodsWhere['goods_select'], 'dis_commission_type', 'buy_drp_show', 'membership_card_id');
    }

    $arr = $arr->with([
        'getGoods' => function ($query) use ($goodsWhere) {
            $query = $query->select($goodsWhere['goods_select'])->where('is_delete', 0);

            if ($goodsWhere['type'] == $goodsWhere['presale']) {
                $query = $query->where('is_on_sale', 0);
            }

            $query->with([
                'getGoodsConsumption'
            ]);
        },
        'getWarehouseGoods' => function ($query) use ($where) {
            $query->where('region_id', $where['warehouse_id']);
        },
        'getWarehouseAreaGoods' => function ($query) use ($where) {
            $query = $query->where('region_id', $where['area_id']);

            if ($where['area_pricetype'] == 1) {
                $query->where('city_id', $where['area_city']);
            }
        },
        'getProductsWarehouse' => function ($query) use ($where) {
            $query->where('warehouse_id', $where['warehouse_id']);
        },
        'getProductsArea' => function ($query) use ($where) {
            $query = $query->where('area_id', $where['area_id']);

            if ($where['area_pricetype'] == 1) {
                $query->where('city_id', $where['area_city']);
            }
        },
        'getProducts',
        'getPresaleActivity'
    ]);

    $arr = $arr->orderBy('rec_id', 'desc');
    $arr = app(BaseRepository::class)->getToArrayGet($arr);

    $virtual = 0;

    //查询非超值礼包商品
    if ($arr) {

        if ($GLOBALS['_CFG']['add_shop_price'] == 1) {
            $add_tocart = 1;
        } else {
            $add_tocart = 0;
        }

        $user_rank = [];
        if (empty($user_id)) {
            $user_id = session('user_id', 0);
        } else {
            $rank = app(UserCommonService::class)->getUserRankByUid($user_id);
            $user_rank['rank_id'] = isset($rank['rank_id']) ? $rank['rank_id'] : 1;
            $user_rank['discount'] = isset($rank['discount']) ? $rank['discount'] / 100 : 1;
        }

        /* 格式化价格及礼包商品 */
        foreach ($arr as $key => $value) {

            $goods = $value['get_goods'] ?? [];

            if ($value['model_attr'] == 1) {
                $goods_number = $goods['get_warehouse_goods']['region_number'] ?? 0;
            } elseif ($value['model_attr'] == 2) {
                $goods_number = $goods['get_warehouse_area_goods']['region_number'] ?? 0;
            } else {
                $goods_number = $goods['number'] ?? 0;
            }

            $value = $value['get_goods'] ? array_merge($value, $value['get_goods']) : $value;

            $arr[$key] = $value;

            if ($type == CART_PRESALE_GOODS) {
                $value['deposit'] = $value['get_presale_activity']['deposit'] ?? 0;
            }

            if ($value['extension_code'] == 'virtual_card') {
                $virtual += 1;
            }

            if ($value['extension_code'] != 'package_buy' && $value['is_gift'] == 0 && $value['parent_id'] == 0) {
                /* 判断购物车商品价格是否与目前售价一致，如果不同则返回购物车价格失效 */
                $currency_format = !empty($GLOBALS['_CFG']['currency_format']) ? explode('%', $GLOBALS['_CFG']['currency_format']) : '';
                $attr_id = !empty($value['goods_attr_id']) ? explode(',', $value['goods_attr_id']) : '';

                if ($value['extension_code'] != 'package_buy') {
                    $presale = 0;
                } else {
                    $presale = CART_PACKAGE_GOODS;
                }

                if (count($currency_format) > 1) {
                    $goods_price = trim(app(GoodsCommonService::class)->getFinalPrice($value['goods_id'], $value['goods_number'], true, $attr_id, $value['warehouse_id'], $value['area_id'], $value['area_city'], 0, $presale, $add_tocart, 0, 0, $user_rank), $currency_format[0]);
                    $cart_price = trim($value['goods_price'], $currency_format[0]);
                } else {
                    $goods_price = app(GoodsCommonService::class)->getFinalPrice($value['goods_id'], $value['goods_number'], true, $attr_id, $value['warehouse_id'], $value['area_id'], $value['area_city'], 0, $presale, $add_tocart, 0, 0, $user_rank);
                    $cart_price = $value['goods_price'];
                }

                $goods_price = floatval($goods_price);
                $cart_price = floatval($cart_price);

                if ($goods_price != $cart_price && empty($value['is_gift']) && empty($value['group_id'])) {
                    $value['price_is_invalid'] = 1; //价格已过期
                } else {
                    $value['price_is_invalid'] = 0; //价格未过期
                }

                if ($value['price_is_invalid'] && $value['rec_type'] == 0 && empty($value['is_gift']) && $value['extension_code'] != 'package_buy') {
                    if (session('flow_type') == 0 && $goods_price > 0) {
                        app(CartCommonService::class)->getUpdateCartPrice($goods_price, $value['rec_id']);
                        $value['goods_price'] = $goods_price;
                    }
                }
            }

            $arr[$key]['goods_price'] = $value['goods_price'];
            $arr[$key]['formated_goods_price'] = price_format($value['goods_price'], false);
            $arr[$key]['formated_subtotal'] = price_format($arr[$key]['subtotal'], false);
            $arr[$key]['goods_amount'] = $value['goods_price'] * $value['goods_number'];

            if (CROSS_BORDER === true) {
                $arr[$key]['free_rate'] = isset($value['free_rate']) && is_numeric($value['free_rate']) ? $value['free_rate'] : 1;
            }

            /* 增加是否在购物车里显示商品图 */
            if (($GLOBALS['_CFG']['show_goods_in_cart'] == "2" || $GLOBALS['_CFG']['show_goods_in_cart'] == "3") && $value['extension_code'] != 'package_buy') {
                $value['goods_thumb'] = $goods['goods_thumb'] ?? '';
                if (isset($goods['is_delete']) && $goods['is_delete'] == 1) {
                    $arr[$key]['is_invalid'] = 1;
                }
            }

            if ($value['extension_code'] == 'package_buy') {
                $value['amount'] = 0;
                $arr[$key]['dis_amount'] = 0;
                $arr[$key]['discount_amount'] = price_format($arr[$key]['dis_amount'], false);

                $arr[$key]['package_goods_list'] = app(PackageGoodsService::class)->getPackageGoods($value['goods_id']);

                $activity = get_goods_activity_info($value['goods_id'], ['act_id', 'activity_thumb']);

                if ($activity) {
                    $arr[$key]['goods_thumb'] = !empty($activity['activity_thumb']) ? get_image_path($activity['activity_thumb']) : app(DscRepository::class)->dscUrl('themes/ecmoban_dsc2017/images/17184624079016pa.jpg');
                }

                $package = get_package_goods_info($arr[$key]['package_goods_list']);
                $arr[$key]['goods_weight'] = $package['goods_weight'];
                $arr[$key]['goodsweight'] = $package['goods_weight'];
                $arr[$key]['goods_number'] = $value['goods_number'];
                $arr[$key]['attr_number'] = !app(PackageGoodsService::class)->judgePackageStock($value['goods_id'], $value['goods_number']);
            } else {
                //贡云商品参数
                $arr[$key]['cloud_goodsname'] = $value['cloud_goodsname'] ?? '';
                $arr[$key]['cloud_id'] = $value['cloud_id'] ?? 0;

                //ecmoban模板堂 --zhuo start 商品金额促销
                if (isset($goods['get_goods_consumption']) && $goods['get_goods_consumption']) {
                    $value['amount'] = app(DscRepository::class)->getGoodsConsumptionPrice($goods['get_goods_consumption'], $value['subtotal']);
                } else {
                    $value['amount'] = $value['subtotal'];
                }

                $arr[$key]['dis_amount'] = $value['subtotal'] - $value['amount'];
                $arr[$key]['discount_amount'] = price_format($arr[$key]['dis_amount'], false);
                //ecmoban模板堂 --zhuo end 商品金额促销

                $arr[$key]['goods_thumb'] = get_image_path($value['goods_thumb']);
                $arr[$key]['formated_market_price'] = price_format($value['market_price'], false);
                $arr[$key]['formated_presale_deposit'] = isset($value['deposit']) ? price_format($value['deposit'], false) : price_format(0);
                $arr[$key]['region_name'] = $value['get__region_warehouse']['region_name'] ?? '';

                // 立即购买为普通商品
                $value['rec_type'] = (isset($value['rec_type']) && $value['rec_type'] == 10) ? 0 : $value['rec_type'];
                $arr[$key]['rec_txt'] = $rec_txt[$value['rec_type']];

                if ($value['rec_type'] == 1) {
                    $group_buy = GoodsActivity::select('act_id', 'act_name')
                        ->where('review_status', 3)->where('act_type', GAT_GROUP_BUY)
                        ->where('goods_id', $value['goods_id']);

                    $group_buy = app(BaseRepository::class)->getToArrayFirst($group_buy);

                    if ($group_buy) {
                        $arr[$key]['url'] = app(DscRepository::class)->buildUri('group_buy', ['gbid' => $group_buy['act_id']]);
                        $arr[$key]['act_name'] = $group_buy['act_name'];
                    }
                } elseif ($value['rec_type'] == 5) {
                    $presale = PresaleActivity::select('act_id', 'act_name')
                        ->where('goods_id', $value['goods_id'])
                        ->where('review_status', 3);

                    $presale = app(BaseRepository::class)->getToArrayFirst($presale);

                    if ($presale) {
                        $arr[$key]['act_name'] = $presale['act_name'];
                        $arr[$key]['url'] = app(DscRepository::class)->buildUri('presale', ['act' => 'view', 'presaleid' => $presale['act_id']], $presale['act_name']);
                    }
                } elseif ($value['rec_type'] == 4) {
                    $arr[$key]['url'] = app(DscRepository::class)->buildUri('exchange_goods', ['gid' => $value['goods_id']], $value['goods_name']);
                } else {
                    $arr[$key]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $value['goods_id']], $value['goods_name']);
                }

                //预售商品，不受库存限制
                if ($value['extension_code'] == 'presale' || $value['rec_type'] > 1) {
                    $arr[$key]['attr_number'] = 1;
                } else {
                    //ecmoban模板堂 --zhuo start
                    if ($ru_type == 1 && $store_id == 0) {

                        if ($value['model_attr'] == 1) {
                            $prod = $value['get_products_warehouse'] ?? [];
                        } elseif ($value['model_attr'] == 2) {
                            $prod = $value['get_products_area'] ?? [];
                        } else {
                            $prod = $value['get_products'] ?? [];
                        }

                        $attr_number = $prod ? $prod['product_number'] : 0;

                        if (empty($prod)) { //当商品没有属性库存时
                            $attr_number = ($GLOBALS['_CFG']['use_storage'] == 1) ? $goods_number : 1;
                        }

                        //贡云商品 验证库存
                        if ($value['cloud_id'] > 0 && isset($prod['cloud_product_id'])) {
                            $attr_number = app(JigonManageService::class)->jigonGoodsNumber(['cloud_product_id' => $prod['cloud_product_id']]);
                        }

                        $attr_number = !empty($attr_number) ? $attr_number : 0;
                        $arr[$key]['attr_number'] = $attr_number;
                    } else {
                        $arr[$key]['attr_number'] = $value['goods_number'];
                    }
                    //ecmoban模板堂 --zhuo end
                }

                $arr[$key]['goods_attr_text'] = app(GoodsAttrService::class)->getGoodsAttrInfo($value['goods_attr_id'], 'pice', $value['warehouse_id'], $value['area_id'], $value['area_city']);

                //by kong  切换门店获取商品门店库存 start 20160721
                if ($store_id > 0) {
                    $goodsInfo = StoreGoods::select('goods_number', 'ru_id')
                        ->where('store_id', $store_id)
                        ->where('goods_id', $value['goods_id']);
                    $goodsInfo = app(BaseRepository::class)->getToArrayFirst($goodsInfo);

                    if ($goodsInfo) {
                        $products = app(GoodsWarehouseService::class)->getWarehouseAttrNumber($value['goods_id'], $value['goods_attr_id'], $warehouse_id, $area_id, $area_city, '', $store_id); //获取属性库存
                        $attr_number = $products ? $products['product_number'] : 0;
                    } else {
                        $attr_number = 0;
                    }

                    if ($value['goods_attr_id']) { //当商品没有属性库存时
                        $arr[$key]['attr_number'] = $attr_number;
                    } else {
                        $arr[$key]['attr_number'] = $goodsInfo['goods_number'];
                    }
                }
                //by kong  切换门店获取商品门店库存 end 20160721
            }
        }
    }

    if ($ru_type == 1) {
        $arr = get_cart_goods_ru_list($arr, $ru_type);
        $arr = get_cart_ru_goods_list($arr, $cart_value, $consignee, $store_id, $user_id);
    }

    if ($is_virtual == 1) {

        $total = [
            'goods_price' => 0, // 本店售价合计（有格式）
            'market_price' => 0, // 市场售价合计（有格式）
            'saving' => 0, // 节省金额（有格式）
            'save_rate' => 0, // 节省百分比
            'goods_amount' => 0, // 本店售价合计（无格式）
            'store_goods_number' => 0, // 门店商品
        ];

        $arr = [
            'goodslist' => $arr,
            'virtual' => $virtual,
            'total' => $total
        ];
    }

    return $arr;
}

/**
 * 取得贡云商品并推送
 */
function set_cloud_order_goods($cart_goods = [], $order = [])
{
    $requ = [];

    //判断是否填写回调接口appkey，如果没有返回失败
    $app_key = ShopConfig::where('code', 'cloud_appkey')->value('value');
    if (!$app_key) {
        return $requ;
    }

    //商品信息
    $order_request = [];
    $order_detaillist = [];
    foreach ($cart_goods as $cart_goods_key => $cart_goods_val) {
        if ($cart_goods_val['cloud_id'] > 0) {
            $arr = [];
            $arr['goodName'] = $cart_goods_val['cloud_goodsname']; //商品名称
            $arr['goodId'] = $cart_goods_val['cloud_id']; //商品id
            //获取货品id，库存id
            if ($cart_goods_val['goods_attr_id']) {
                $goods_attr_id = explode(',', $cart_goods_val['goods_attr_id']);

                //获取货品信息
                $products_info = Products::select('cloud_product_id', 'inventoryid')->where('goods_id', $cart_goods_val['goods_id']);

                foreach ($goods_attr_id as $key => $val) {
                    $products_info = $products_info->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                }

                $products_info = app(BaseRepository::class)->getToArrayFirst($products_info);

                $arr['inventoryId'] = $products_info['inventoryid']; //库存id
                $arr['productId'] = $products_info['cloud_product_id']; //货品id
                $arr['productPrice'] = ''; //new
            }
            $arr['quantity'] = $cart_goods_val['goods_number']; //购买数量
            $arr['deliveryWay'] = '3'; //快递方式 3为快递送  上门自提不支持
            $arr['brandId'] = 0; //new
            $arr['channel'] = 0; //new
            $arr['navigateImg1'] = ''; //new
            $arr['salePrice'] = 0; //new
            $arr['storeId'] = 0; //new

            $order_detaillist[] = $arr;
        }
    }

    //初始化数据
    if (!empty($order_detaillist)) {
        $order_request['orderDetailList'] = $order_detaillist;
        $order_request['address'] = $order['address']; //地址
        $order_request['area'] = get_table_date('region', "region_id='" . $order['district'] . "'", ['region_name'], 2); //地区
        $order_request['city'] = get_table_date('region', "region_id='" . $order['city'] . "'", ['region_name'], 2); //城市
        $order_request['province'] = get_table_date('region', "region_id='" . $order['province'] . "'", ['region_name'], 2); //城市
        $order_request['remark'] = $order['postscript']; //备注
        $order_request['mobile'] = intval($order['mobile']); //电话
        $order_request['payType'] = 99; //支付方式 统一用99
        $order_request['linkMan'] = $order['consignee']; //收件人
        $order_request['billType'] = !empty($order['invoice_type']) ? 2 : 1; //发票类型 2:公司，1、个人
        $order_request['billHeader'] = $order['inv_payee']; //发票抬头
        $order_request['isBill'] = 0; //是否开发票 根据开票规则 不直接开票给用户 所以默认传0
        $order_request['taxNumber'] = ''; //税号

        if ($order_request['billType'] == 2) {
            $users_vat_invoices_info = UsersVatInvoicesInfo::select('company_name', 'tax_id')
                ->where('user_id', $order['user_id']);
            $users_vat_invoices_info = app(BaseRepository::class)->getToArrayFirst($users_vat_invoices_info);

            if ($users_vat_invoices_info) {
                $order_request['billHeader'] = $users_vat_invoices_info['company_name'];
                $order_request['taxNumber'] = $users_vat_invoices_info['tax_id'];
            }
        }

        $cloud = app(Cloud::class);
        $is_callable = [$cloud, 'addOrderMall'];

        /* 判断类对象方法是否存在 */
        if (is_callable($is_callable)) {
            $requ = $cloud->addOrderMall($order_request, $order);
            $requ = dsc_decode($requ, true);
        }
    }

    return $requ;
}

/**
 * 确认订单 推送给贡云
 */
function cloud_confirmorder($order_id)
{
    if ($order_id > 0) {
        //获取贡云服订单号  和上次订单总额
        $cloud_order = OrderCloud::select('rec_id', 'parentordersn AS orderSn')
            ->whereHas('getOrderGoods', function ($query) use ($order_id) {
                $query->where('order_id', $order_id);
            });

        $cloud_order = $cloud_order->with([
            'getOrderGoods' => function ($query) {
                $query->select('rec_id')->selectRaw("SUM(goods_number * goods_price) AS paymentFee");
            }
        ]);

        $cloud_order = app(BaseRepository::class)->getToArrayFirst($cloud_order);

        if ($cloud_order) {
            $cloud_order['paymentFee'] = $cloud_order['get_order_goods'] ? floatval($cloud_order['get_order_goods']['paymentFee'] * 100) : 0;

            //获取支付流水号
            $payId = PayLog::where('order_id', $order_id)->where('order_type', PAY_ORDER)->value('log_id');
            $cloud_order['payId'] = $payId ? $payId : 0;

            $cloud_order['payType'] = 99; //支付方式  默认99

            $cloud_dsc_appkey = ShopConfig::where('code', 'cloud_dsc_appkey')->value('value');
            $cloud_order['notifyUrl'] = $GLOBALS['dsc']->url() . "api.php?app_key=" . $cloud_dsc_appkey . "&method=dsc.order.confirmorder.post&format=json&interface_type=1";

            $cloud = app(Cloud::class);
            $is_callable = [$cloud, 'confirmorder'];

            if (is_callable($is_callable)) {
                $cloud->confirmorder($cloud_order);
            }
        }
    }
}

/**
 * 检查某商品是否已经存在于购物车
 *
 * @access  public
 * @param integer $id
 * @param array $spec
 * @param int $type 类型：默认普通商品
 * @return  boolean
 */
function cart_goods_exists($id, $spec, $type = CART_GENERAL_GOODS)
{
    $user_id = session('user_id', 0);
    $session_id = app(SessionRepository::class)->realCartMacIp();

    $goods_attr = '';
    if ($spec) {
        $goods_attr = app(GoodsAttrService::class)->getGoodsAttrInfo($spec);
    }

    /* 检查该商品是否已经存在在购物车中 */
    $res = Cart::where('goods_id', $id)
        ->where('parent_id', 0)
        ->where('rec_type', $type);

    if ($goods_attr) {
        $res = $res->where('goods_attr', $goods_attr);
    }

    if (!empty($user_id)) {
        $res = $res->where('user_id', $user_id);
    } else {
        $res = $res->where('session_id', $session_id);
    }

    $count = $res->count();

    return ($count > 0);
}

/**
 * 获得购物车中商品的总重量、总价格、总数量
 *
 * @access  public
 * @param int $type 类型：默认普通商品
 * @return  array
 */
function cart_weight_price($type = CART_GENERAL_GOODS, $cart_value)
{
    $user_id = session('user_id', 0);
    $session_id = app(SessionRepository::class)->realCartMacIp();

    $cart_value = app(BaseRepository::class)->getExplode($cart_value);

    $package_row['weight'] = 0;
    $package_row['amount'] = 0;
    $package_row['number'] = 0;

    $packages_row['free_shipping'] = 1;

    /* 计算超值礼包内商品的相关配送参数 */
    $row = Cart::where('extension_code', 'package_buy');

    if (!empty($user_id)) {
        $row = $row->where('user_id', $user_id);
    } else {
        $row = $row->where('session_id', $session_id);
    }

    if (!empty($cart_value)) {
        $row = $row->whereIn('rec_id', $cart_value);
    }

    $row = app(BaseRepository::class)->getToArrayGet($row);

    if ($row) {
        $packages_row['free_shipping'] = 0;
        $free_shipping_count = 0;

        foreach ($row as $val) {

            // 如果商品全为免运费商品，设置一个标识变量
            $shipping_count = PackageGoods::where('package_id', $val['goods_id'])
                ->whereHas('getGoods', function ($query) {
                    $query->where('is_shipping', 0);
                });

            $shipping_count = $shipping_count->count();

            if ($shipping_count > 0) {
                // 循环计算每个超值礼包商品的重量和数量，注意一个礼包中可能包换若干个同一商品
                $goods_row = PackageGoods::where('package_id', $val['goods_id'])
                    ->whereHas('getGoods', function ($query) {
                        $query->where('is_shipping', 0)
                            ->where('freight', '<>', 2);
                    });

                $goods_row = $goods_row->with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_weight', 'freight');
                    }
                ]);

                $goods_row = app(BaseRepository::class)->getToArrayGet($goods_row);

                $weight = 0;
                $goods_price = 0;
                $number = 0;
                if ($goods_row) {
                    foreach ($goods_row as $pgkey => $pgval) {
                        $pgval = app(BaseRepository::class)->getArrayMerge($pgval, $pgval['get_goods']);

                        $weight += $pgval['goods_weight'] * $pgval['goods_number'];
                        $goods_price += $pgval['goods_price'] * $pgval['goods_number'];
                        $number += $pgval['goods_number'];
                    }
                }

                $package_row['weight'] += floatval($weight) * $val['goods_number'];
                $package_row['amount'] += floatval($goods_price) * $val['goods_number'];
                $package_row['number'] += intval($number) * $val['goods_number'];
            } else {
                $free_shipping_count++;
            }
        }

        $packages_row['free_shipping'] = $free_shipping_count == count($row) ? 1 : 0;
    }

    /* 获得购物车中非超值礼包商品的总重量 */
    $res = Cart::where('rec_type', $type)
        ->where('extension_code', '<>', 'package_buy');

    $res = $res->whereHas('getGoods', function ($query) {
        $query->where('is_shipping', 0)
            ->where('freight', '<>', 2);
    });

    if (!empty($user_id)) {
        $res = $res->where('user_id', $user_id);
    } else {
        $res = $res->where('session_id', $session_id);
    }

    if (!empty($cart_value)) {
        $res = $res->whereIn('rec_id', $cart_value);
    }

    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'goods_weight');
        }
    ]);


    $res = app(BaseRepository::class)->getToArrayGet($res);

    $weight = 0;
    $amount = 0;
    $number = 0;

    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods']);

            if ($row['freight'] == 1) {
                $weight += 0;
            } else {
                $weight += $row['goods_weight'] * $row['goods_number'];
            }

            $amount += $row['goods_price'] * $row['goods_number'];
            $number += $row['goods_number'];
        }
    }

    $packages_row['weight'] = floatval($weight) + $package_row['weight'];
    $packages_row['amount'] = floatval($amount) + $package_row['amount'];
    $packages_row['number'] = intval($number) + $package_row['number'];

    /* 格式化重量 */
    $packages_row['formated_weight'] = formated_weight($packages_row['weight']);

    return $packages_row;
}

/**
 * 添加商品到购物车（配件组合） by mike
 *
 * @access  public
 * @param integer $goods_id 商品编号
 * @param integer $num 商品数量
 * @param array $spec 规格值对应的id数组
 * @param integer $parent 基本件
 * @return  boolean
 */
function addto_cart_combo($goods_id, $num = 1, $spec = [], $parent = 0, $group = '', $warehouse_id = 0, $area_id = 0, $area_city = 0, $goods_attr = '') //ecmoban模板堂 --zhuo $warehouse_id
{
    $user_id = session('user_id', 0);

    $GoodsLib = app(GoodsService::class);

    if (!is_array($goods_attr)) {
        if (!empty($goods_attr)) {
            $goods_attr = explode(',', $goods_attr);
        } else {
            $goods_attr = [];
        }
    }

    $ok_arr = get_insert_group_main($parent, $num, $goods_attr, 0, $group, $warehouse_id, $area_id, $area_city);

    if ($ok_arr['is_ok'] == 1) { // 商品不存在
        $GLOBALS['err']->add($GLOBALS['_LANG']['group_goods_not_exists'], ERR_NOT_EXISTS);
        return false;
    }
    if ($ok_arr['is_ok'] == 2) { // 商品已下架
        $GLOBALS['err']->add($GLOBALS['_LANG']['group_not_on_sale'], ERR_NOT_ON_SALE);
        return false;
    }
    if ($ok_arr['is_ok'] == 3 || $ok_arr['is_ok'] == 4) { // 商品缺货
        $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['group_shortage']), ERR_OUT_OF_STOCK);
        return false;
    }

    $GLOBALS['err']->clean();
    $_parent_id = $parent;

    /* 取得商品信息 */
    $where = [
        'goods_id' => $goods_id,
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'area_city' => $area_city
    ];
    $goods = $GoodsLib->getGoodsInfo($where);

    if (empty($goods)) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);

        return false;
    }

    /* 是否正在销售 */
    if ($goods['is_on_sale'] == 0) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['not_on_sale'], ERR_NOT_ON_SALE);

        return false;
    }

    /* 不是配件时检查是否允许单独销售 */
    if (empty($parent) && $goods['is_alone_sale'] == 0) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['cannt_alone_sale'], ERR_CANNT_ALONE_SALE);

        return false;
    }

    /* 如果商品有规格则取规格商品信息 配件除外 */
    if ($goods['model_attr'] == 1) {
        $prod = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
    } elseif ($goods['model_attr'] == 2) {
        $prod = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);

        if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
            $prod = $prod->where('city_id', $area_city);
        }
    } else {
        $prod = Products::where('goods_id', $goods_id);
    }

    $prod = app(BaseRepository::class)->getToArrayFirst($prod);

    if (is_spec($spec) && !empty($prod)) {
        $product_info = app(GoodsAttrService::class)->getProductsInfo($goods_id, $spec, $warehouse_id, $area_id, $area_city);
    }
    if (empty($product_info)) {
        $product_info = ['product_number' => 0, 'product_id' => 0];
    }

    /* 检查：库存 */
    if ($GLOBALS['_CFG']['use_storage'] == 1) {
        $is_product = 0;
        //商品存在规格 是货品
        if (is_spec($spec) && !empty($prod)) {
            if (!empty($spec)) {
                /* 取规格的货品库存 */
                if ($num > $product_info['product_number']) {
                    $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], $product_info['product_number']), ERR_OUT_OF_STOCK);

                    return false;
                }
            }
        } else {
            $is_product = 1;
        }

        if ($is_product == 1) {
            //检查：商品购买数量是否大于总库存
            if ($num > $goods['goods_number']) {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], $goods['goods_number']), ERR_OUT_OF_STOCK);

                return false;
            }
        }
    }

    /* 计算商品的促销价格 */
    $warehouse_area['warehouse_id'] = $warehouse_id;
    $warehouse_area['area_id'] = $area_id;
    $warehouse_area['area_city'] = $area_city;

    $spec_price = app(GoodsAttrService::class)->specPrice($spec, $goods_id, $warehouse_area);
    $goods['marketPrice'] += $spec_price;
    $goods_attr = app(GoodsAttrService::class)->getGoodsAttrInfo($spec, 'pice', $warehouse_id, $area_id, $area_city);
    $goods_attr_id = join(',', $spec);

    $session_id = app(SessionRepository::class)->realCartMacIp();
    $sess = empty($user_id) ? $session_id : '';

    /* 初始化要插入购物车的基本件数据 */
    $parent = [
        'user_id' => session('user_id'),
        'session_id' => $sess,
        'goods_id' => $goods_id,
        'goods_sn' => addslashes($goods['goods_sn']),
        'product_id' => $product_info['product_id'],
        'goods_name' => addslashes($goods['goods_name']),
        'market_price' => $goods['marketPrice'],
        'goods_attr' => addslashes($goods_attr),
        'goods_attr_id' => $goods_attr_id,
        'is_real' => $goods['is_real'],
        'model_attr' => $goods['model_attr'], //ecmoban模板堂 --zhuo 属性方式
        'warehouse_id' => $warehouse_id, //ecmoban模板堂 --zhuo 仓库
        'area_id' => $area_id, //ecmoban模板堂 --zhuo 仓库地区
        'area_city' => $area_city,
        'ru_id' => $goods['user_id'], //ecmoban模板堂 --zhuo 商家ID
        'extension_code' => $goods['extension_code'],
        'is_gift' => 0,
        'model_attr' => $goods['model_attr'],
        'commission_rate' => $goods['commission_rate'],
        'is_shipping' => $goods['is_shipping'],
        'rec_type' => CART_GENERAL_GOODS,
        'add_time' => gmtime(),
        'group_id' => $group
    ];

    /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
    /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
    /* 受此优惠 */
    $basic_list = [];
    $res = GroupGoods::select('parent_id', 'goods_price')
        ->where('goods_id', $goods_id)
        ->where('parent_id', $_parent_id)
        ->orderBy('goods_price');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $row) {
            $basic_list[$row['parent_id']] = $row['goods_price'];
        }
    }

    /* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
    foreach ($basic_list as $parent_id => $fitting_price) {
        $attr_info = app(GoodsAttrService::class)->getGoodsAttrInfo($spec, 'pice', $warehouse_id, $area_id, $area_city);

        /* 检查该商品是否已经存在在购物车中 */
        $row = CartCombo::where('goods_id', $goods_id)
            ->where('parent_id', $parent_id)
            ->where('extension_code', '<>', 'package_buy')
            ->where('rec_type', CART_GENERAL_GOODS)
            ->where('group_id', $group);

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $row = $row->where('session_id', $session_id);
        }

        $row = $row->count();

        if ($row) { //如果购物车已经有此物品，则更新
            $num = 1; //临时保存到数据库，无数量限制
            if (is_spec($spec) && !empty($prod)) {
                $goods_storage = $product_info['product_number'];
            } else {
                $goods_storage = $goods['goods_number'];
            }

            if ($GLOBALS['_CFG']['use_storage'] == 0 || $num <= $goods_storage) {
                $fittAttr_price = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0;

                $CartComboOther = [
                    'goods_number' => $num,
                    'commission_rate' => $goods['commission_rate'],
                    'goods_price' => $fittAttr_price,
                    'product_id' => $product_info['product_id'],
                    'goods_attr' => $attr_info,
                    'goods_attr_id' => $goods_attr_id,
                    'market_price' => $goods['marketPrice'],
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];
                $res = CartCombo::where('goods_id', $goods_id)
                    ->where('parent_id', $parent_id)
                    ->where('extension_code', '<>', 'package_buy')
                    ->where('rec_type', CART_GENERAL_GOODS)
                    ->where('group_id', $group);

                if (!empty($user_id)) {
                    $res = $res->where('user_id', $user_id);
                } else {
                    $res = $res->where('session_id', $session_id);
                }

                $res->update($CartComboOther);
            } else {
                $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], $num), ERR_OUT_OF_STOCK);

                return false;
            }
        } //购物车没有此物品，则插入
        else {
            /* 作为该基本件的配件插入 */
            $parent['goods_price'] = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
            $parent['goods_number'] = 1; //临时保存到数据库，无数量限制
            $parent['parent_id'] = $parent_id;

            /* 添加 */
            CartCombo::insert($parent);
        }
    }

    return true;
}

//首次添加配件时，查看主件是否存在，否则添加主件
function get_insert_group_main($goods_id, $num = 1, $goods_spec = [], $parent = 0, $group = '', $warehouse_id = 0, $area_id = 0, $area_city = 0)
{
    $user_id = session('user_id', 0);

    $GoodsLib = app(GoodsService::class);

    $ok_arr['is_ok'] = 0;
    $spec = $goods_spec;

    $GLOBALS['err']->clean();

    /* 取得商品信息 */
    $where = [
        'goods_id' => $goods_id,
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'area_city' => $area_city
    ];
    $goods = $GoodsLib->getGoodsInfo($where);

    if (empty($goods)) {
        $ok_arr['is_ok'] = 1;
        return $ok_arr;
    }

    /* 是否正在销售 */
    if ($goods['is_on_sale'] == 0) {
        $ok_arr['is_ok'] = 2;
        return $ok_arr;
    }

    /* 如果商品有规格则取规格商品信息 */
    if ($goods['model_attr'] == 1) {
        $prod = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
    } elseif ($goods['model_attr'] == 2) {
        $prod = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);

        if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
            $prod = $prod->where('city_id', $area_city);
        }
    } else {
        $prod = Products::where('goods_id', $goods_id);
    }

    $prod = app(BaseRepository::class)->getToArrayFirst($prod);

    if (is_spec($spec) && !empty($prod)) {
        $product_info = app(GoodsAttrService::class)->getProductsInfo($goods_id, $spec, $warehouse_id, $area_id, $area_city);
    }
    if (empty($product_info)) {
        $product_info = ['product_number' => 0, 'product_id' => 0];
    }

    /* 检查：库存 */
    if ($GLOBALS['_CFG']['use_storage'] == 1) {
        $is_product = 0;
        //商品存在规格 是货品
        if (is_spec($spec) && !empty($prod)) {
            if (!empty($spec)) {
                /* 取规格的货品库存 */
                if ($num > $product_info['product_number']) {
                    $ok_arr['is_ok'] = 3;
                    return $ok_arr;
                }
            }
        } else {
            $is_product = 1;
        }

        if ($is_product == 1) {
            //检查：商品购买数量是否大于总库存
            if ($num > $goods['goods_number']) {
                $ok_arr['is_ok'] = 4;
                return $ok_arr;
            }
        }
    }

    /* 计算商品的促销价格 */
    $warehouse_area['warehouse_id'] = $warehouse_id;
    $warehouse_area['area_id'] = $area_id;
    $warehouse_area['area_city'] = $area_city;

    $spec_price = app(GoodsAttrService::class)->specPrice($spec, $goods_id, $warehouse_area);

    $goods_price = app(GoodsCommonService::class)->getFinalPrice($goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city);
    $goods['marketPrice'] += $spec_price;
    $goods_attr = app(GoodsAttrService::class)->getGoodsAttrInfo($spec, 'pice', $warehouse_id, $area_id, $area_city); //ecmoban模板堂 --zhuo
    $goods_attr_id = join(',', $spec);

    $session_id = app(SessionRepository::class)->realCartMacIp();
    $sess = empty(session('user_id')) ? $session_id : '';

    /* 初始化要插入购物车的基本件数据 */
    $parent = [
        'user_id' => $user_id,
        'session_id' => $sess,
        'goods_id' => $goods_id,
        'goods_sn' => addslashes($goods['goods_sn']),
        'product_id' => $product_info['product_id'],
        'goods_name' => addslashes($goods['goods_name']),
        'market_price' => $goods['marketPrice'],
        'goods_attr' => addslashes($goods_attr),
        'goods_attr_id' => $goods_attr_id,
        'is_real' => $goods['is_real'],
        'model_attr' => $goods['model_attr'], //ecmoban模板堂 --zhuo 属性方式
        'warehouse_id' => $warehouse_id, //ecmoban模板堂 --zhuo 仓库
        'area_id' => $area_id, //ecmoban模板堂 --zhuo 仓库地区
        'area_city' => $area_city,
        'ru_id' => $goods['user_id'], //ecmoban模板堂 --zhuo 商家ID
        'extension_code' => $goods['extension_code'],
        'is_gift' => 0,
        'is_shipping' => $goods['is_shipping'],
        'rec_type' => CART_GENERAL_GOODS,
        'add_time' => gmtime(),
        'group_id' => $group
    ];

    $attr_info = app(GoodsAttrService::class)->getGoodsAttrInfo($spec, 'pice', $warehouse_id, $area_id, $area_city);

    /* 检查该套餐主件商品是否已经存在在购物车中 */
    $row = CartCombo::where('goods_id', $goods_id)
        ->where('parent_id', 0)
        ->where('extension_code', '<>', 'package_buy')
        ->where('rec_type', CART_GENERAL_GOODS)
        ->where('group_id', $group);

    if (!empty($user_id)) {
        $row = $row->where('user_id', $user_id);
    } else {
        $row = $row->where('session_id', $session_id);
    }

    $row = $row->where('warehouse_id', $warehouse_id);

    $row = $row->count();

    if ($row) {
        $CartComboOther = [
            'goods_number' => $num,
            'goods_price' => $goods_price,
            'product_id' => $product_info['product_id'],
            'goods_attr' => $attr_info,
            'goods_attr_id' => $goods_attr_id,
            'market_price' => $goods['marketPrice'],
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id
        ];
        $res = CartCombo::where('goods_id', $goods_id)
            ->where('parent_id', 0)
            ->where('extension_code', '<>', 'package_buy')
            ->where('rec_type', CART_GENERAL_GOODS)
            ->where('group_id', $group);

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $res = $res->where('session_id', $session_id);
        }

        $res->update($CartComboOther);
    } else {
        $parent['goods_price'] = max($goods_price, 0);
        $parent['goods_number'] = $num;
        $parent['parent_id'] = 0;

        CartCombo::insert($parent);
    }
}

/**
 * 获取商品的原价、配件价、库存（配件组合） by mike
 * 返回数组
 */
function get_combo_goods_info($goods_id, $num = 1, $spec = [], $parent = 0, $warehouse_area)
{
    $warehouse_id = $warehouse_area['warehouse_id'];
    $area_id = $warehouse_area['area_id'];
    $area_city = $warehouse_area['area_city'];

    $result = [];

    /* 取得商品信息 */
    $goods = Goods::select('goods_id', 'goods_number', 'model_attr')
        ->where('goods_id', $goods_id)->where('is_delete', 0);

    $goods = app(BaseRepository::class)->getToArrayFirst($goods);

    /* 如果商品有规格则取规格商品信息 配件除外 */
    if ($goods['model_attr'] == 1) {
        $prod = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
    } elseif ($goods['model_attr'] == 2) {
        $prod = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);
    } else {
        $prod = Products::where('goods_id', $goods_id);
    }

    $prod = app(BaseRepository::class)->getToArrayFirst($prod);

    if (is_spec($spec) && !empty($prod)) {
        $product_info = app(GoodsAttrService::class)->getProductsInfo($goods_id, $spec, $warehouse_id, $area_id, $area_city);
    }
    if (empty($product_info)) {
        $product_info = ['product_number' => '', 'product_id' => 0];
    }

    //商品库存
    $result['stock'] = $goods['goods_number'];

    //商品存在规格 是货品 检查该货品库存
    if (is_spec($spec) && !empty($prod)) {
        if (!empty($spec)) {
            /* 取规格的货品库存 */
            $result['stock'] = $product_info['product_number'];
        }
    }

    /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
    $res = GroupGoods::where('goods_id', $goods_id)
        ->where('parent_id', $parent)
        ->orderBy('goods_price');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $row) {
            $result['fittings_price'] = $row['goods_price'];
        }
    }

    /* 计算商品的促销价格 */
    $result['fittings_price'] = (isset($result['fittings_price'])) ? $result['fittings_price'] : app(GoodsCommonService::class)->getFinalPrice($goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city);
    $result['spec_price'] = app(GoodsAttrService::class)->specPrice($spec, $goods_id, $warehouse_area);//属性价格
    $result['goods_price'] = app(GoodsCommonService::class)->getFinalPrice($goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city);

    return $result;
}

/**
 * 修改用户
 * @param int $user_id 订单id
 * @param array $user key => value
 * @return  bool
 */
function update_user($user_id, $user)
{
    $res = Users::where('user_id', $user_id)->update($user);

    return $res;
}

/**
 * 取得用户地址列表
 * @param int $user_id 用户id
 * @return  array
 */
function address_list($user_id)
{
    $res = UserAddress::where('user_id', $user_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

/**
 * 取得用户地址信息
 * @param int $address_id 地址id
 * @return  array
 */
function address_info($address_id)
{
    $res = UserAddress::where('address_id', $address_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 取得红包信息
 *
 * @param $bonus_id 红包id
 * @param string $bonus_psd 红包序列号
 * @param int $cart_value
 * @return array
 */
function bonus_info($bonus_id, $bonus_psd = '', $cart_value = 0)
{
    $goods_user = '';
    $where = '';
    if ($cart_value != 0 || !empty($cart_value)) {
        $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;

        $goods_list = Cart::selectRaw('ru_id, ru_id AS user_id')->whereIn('rec_id', $cart_value);

        $goods_list = $goods_list->whereHas('getGoods');

        if ($goods_list) {
            foreach ($goods_list as $key => $row) {
                $goods_user .= $row['user_id'] . ',';
            }
        }

        if (!empty($goods_user)) {
            $goods_user = substr($goods_user, 0, -1);
            $goods_user = explode(',', $goods_user);
            $goods_user = array_unique($goods_user);
            $goods_user = implode(',', $goods_user);
            $goods_user = app(DscRepository::class)->delStrComma($goods_user);
            $goods_user = !is_array($goods_user) ? explode(",", $goods_user) : $goods_user;
            $where = "IF(usebonus_type > 0, usebonus_type = 1, user_id in($goods_user)) ";
        }
    }

    if ($bonus_id > 0) {
        $bonus = UserBonus::where('bonus_id', $bonus_id);
    } else {
        $bonus = UserBonus::where('bonus_password', $bonus_psd);
    }

    $bonus = $bonus->whereHas('getBonusType', function ($query) use ($where) {
        $query->where('review_status', 3);
        if ($where) {
            $query->whereRaw($where);
        }
    });

    $bonus = $bonus->with([
        'getBonusType' => function ($query) {
            $query->selectRaw("type_id, type_name, type_money, send_type, usebonus_type, min_amount, max_amount, send_start_date, send_end_date, use_start_date, use_end_date, min_goods_amount, review_status, review_content, user_id AS admin_id");
        }
    ]);

    $bonus = app(BaseRepository::class)->getToArrayFirst($bonus);

    $bonus = $bonus['get_bonus_type'] ? array_merge($bonus, $bonus['get_bonus_type']) : $bonus;

    return $bonus;
}

/**
 * 取得储值卡信息
 * @param int $value_card_id 储值卡id
 * @param string $value_card_psd 储值卡密码
 * @param array   红包信息
 */
function value_card_info($value_card_id, $value_card_psd = '', $cart_value = 0)
{
    $row = ValueCard::selectRaw('*, user_id as admin_id');
    if ($value_card_id > 0) {
        $row = $row->where('vid', $value_card_id)
            ->with('getValueCardType');
    } else {
        $row = $row->where('value_card_password', $value_card_psd)
            ->where('user_id', 0)
            ->with('getValueCardType');
    }

    $row = app(BaseRepository::class)->getToArrayFirst($row);

    $row = isset($row['get_value_card_type']) ? array_merge($row, $row['get_value_card_type']) : $row;

    return $row;
}

/**
 * 检查红包是否已使用
 * @param int $bonus_id 红包id
 * @return  bool
 */
function bonus_used($bonus_id)
{
    $res = UserBonus::where('bonus_id', $bonus_id)->value('order_id');

    return $res;
}

/**
 * 设置红包为已使用
 * @param int $bonus_id 红包id
 * @param int $order_id 订单id
 * @return  bool
 */
function use_bonus($bonus_id, $order_id)
{
    $other = [
        'order_id' => $order_id,
        'used_time' => gmtime()
    ];
    $res = UserBonus::where('bonus_id', $bonus_id)->update($other);

    return $res;
}

/**
 * 改变储值卡余额
 * @param int $vc_id 储值卡ID
 * @param int $order_id 订单ID
 * @param float $use_val 使用金额
 * @return  bool
 */
function use_value_card($vc_id, $order_id, $use_val)
{
    $valueCard = ValueCard::select('card_money', 'tid')->where('vid', $vc_id);
    $valueCard = app(BaseRepository::class)->getToArrayFirst($valueCard);

    $card_money = $valueCard ? $valueCard['card_money'] : 0;
    $card_money -= $use_val;

    if (empty($valueCard) || $card_money < 0) {
        return false;
    }

    $res = ValueCard::where('vid', $vc_id)->update(['card_money' => $card_money]);

    if (!$res) {
        return false;
    }

    $vc_dis = ValueCardType::where('id', $valueCard['tid'])->value('vc_dis');
    $vc_dis = $vc_dis ? $vc_dis : 0;

    $other = [
        'vc_id' => $vc_id,
        'order_id' => $order_id,
        'use_val' => $use_val,
        'vc_dis' => $vc_dis,
        'record_time' => gmtime()
    ];
    $res = ValueCardRecord::insertGetId($other);

    if (!$res) {
        return false;
    }

    return true;
}

/**
 * 设置优惠券为已使用
 * @param int $bonus_id 优惠券id
 * @param int $order_id 订单id
 * @return  bool
 */
function use_coupons($uc_id, $order_id)
{
    $other = [
        'order_id' => $order_id,
        'is_use_time' => gmtime(),
        'is_use' => 1
    ];
    $res = CouponsUser::where('uc_id', $uc_id)->update($other);

    return $res;
}

/**
 * 设置红包为未使用
 * @param int $bonus_id 红包id
 * @param int $order_id 订单id
 * @return  bool
 */
function unuse_bonus($bonus_id)
{
    $other = [
        'order_id' => 0,
        'used_time' => 0
    ];
    $res = UserBonus::where('bonus_id', $bonus_id)->update($other);

    return $res;
}

/**
 * 设置优惠券为未使用,并删除订单满额返券 bylu
 * @param int $order_id 订单id
 * @return  bool
 */
function unuse_coupons($order_id = 0)
{
    $coupons = OrderInfo::where('order_id', $order_id)->value('coupons');
    //使用了优惠券才退券
    if ($coupons) {
        // 判断当前订单是否满足了返券要求
        $other = [
            'order_id' => 0,
            'is_use_time' => 0,
            'is_use' => 0
        ];
        $res = CouponsUser::where('order_id', $order_id)->update($other);

        return $res;
    }
}

/**
 * 退还订单使用的储值卡消费金额
 * @param int $order_id 订单ID
 * @return  bool
 */
function return_card_money($order_id = 0, $ret_id = 0, $return_sn = '')
{
    $row = ValueCardRecord::where('order_id', $order_id);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if ($row) {
        $time = gmtime();

        $order_info = OrderInfo::where('order_id', $order_id);
        $order_info = app(BaseRepository::class)->getToArrayFirst($order_info);

        /* 更新储值卡金额 */
        ValueCard::where('vid', $row['vc_id'])->increment('card_money', $row['use_val']);

        /* 更新储值卡金额使用日志 */
        $log = [
            'vc_id' => $row['vc_id'],
            'order_id' => $order_id,
            'use_val' => $row['use_val'],
            'vc_dis' => 1,
            'add_val' => $row['use_val'],
            'record_time' => $time
        ];

        ValueCardRecord::insert($log);

        if ($return_sn) {
            $return_note = sprintf(lang('user.order_vcard_return'), $row['use_val']);
            return_action($ret_id, RF_AGREE_APPLY, FF_REFOUND, $return_note);

            $return_sn = "<br/>" . lang('order.order_return_running_number') . "：" . $return_sn;
        }

        $note = sprintf(lang('user.order_vcard_return') . $return_sn, $row['use_val']);
        order_action($order_info['order_sn'], $order_info['order_status'], $order_info['shipping_status'], $order_info['pay_status'], $note, null, 0, $time);
    }
}

/**
 * 订单退款
 * @param array $order 订单
 * @param int $refund_type 退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
 * @param string $refund_note 退款说明
 * @param float $refund_amount 退款金额（如果为0，取订单已付款金额）
 * @param float $shipping_fee 退款运费金额（如果为0，取订单已付款金额）
 * @return  bool
 */
function order_refund($order, $refund_type, $refund_note, $refund_amount = null, $shipping_fee = 0)
{
    /* 检查参数 */
    $user_id = $order['user_id'];
    if ($user_id == 0 && $refund_type == 1) {
        return 'anonymous, cannot return to account balance';
    }

    if (is_null($refund_amount)) {
        $amount = $order['money_paid'] + $order['surplus'];

        if ($amount > 0 && $shipping_fee > 0) {
            $amount = $amount - $order['shipping_fee'] + $shipping_fee;
        }
    } else {
        $amount = $refund_amount + $shipping_fee;
    }

    if ($amount <= 0) {
        return 1;
    }

    if (!in_array($refund_type, [1, 2, 3])) {
        return 'invalid params';
    }

    /* 备注信息 */
    if ($refund_note) {
        $change_desc = $refund_note;
    } else {
        $change_desc = sprintf(lang('admin/order.order_refund'), $order['order_sn']);
    }

    //退款不退发票金额
    if ($order['tax'] > 0) {
        $amount = $amount - $order['tax'];
    }

    if ($refund_type == 1 || $refund_type == 2) {
        //退款更新账单
        $other = [
            'return_shippingfee' => DB::raw("return_shippingfee  + ('$shipping_fee')"),
            'order_status' => $order['order_status'],
            'pay_status' => $order['pay_status'],
            'shipping_status' => $order['shipping_status']
        ];
        SellerBillOrder::where('order_id', $order['order_id'])->increment('return_amount', $refund_amount, $other);
    }

    /* 处理退款 */
    if (1 == $refund_type) {
        /* 如果非匿名，退回余额 */
        if ($user_id > 0) {
            $is_ok = 1;
            if (isset($order['ru_id']) && $order['ru_id'] && $order['chargeoff_status'] == 2) {
                $seller_shopinfo = SellerShopinfo::selectRaw("seller_money, credit_money, (seller_money + credit_money) AS credit")
                    ->where('ru_id', $order['ru_id']);
                $seller_shopinfo = app(BaseRepository::class)->getToArrayFirst($seller_shopinfo);

                if ($seller_shopinfo && $seller_shopinfo['credit'] > 0 && $seller_shopinfo['credit'] >= $amount) {
                    $adminru = get_admin_ru_id();

                    $change_desc = "操作员：【" . $adminru['user_name'] . "】" . $refund_note;
                    $log = [
                        'user_id' => $order['ru_id'],
                        'user_money' => (-1) * $amount,
                        'change_time' => gmtime(),
                        'change_desc' => $change_desc,
                        'change_type' => 2
                    ];

                    MerchantsAccountLog::insert($log);
                    SellerShopinfo::where('ru_id', $order['ru_id'])->increment('seller_money', $log['user_money']);
                } else {
                    $is_ok = 0;
                }
            }

            if ($is_ok == 1) {
                log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
            } else {
                /* 返回失败，不允许退款 */
                return 2;
            }
        }

        return 1;
    } elseif (2 == $refund_type) {
        /* 如果非匿名，退回冻结资金 */
        if ($user_id > 0) {
            log_account_change($user_id, 0, $amount, 0, 0, $change_desc);
        }

        /* user_account 表增加提款申请记录 */
        $account = [
            'user_id' => $user_id,
            'amount' => (-1) * $amount,
            'add_time' => gmtime(),
            'user_note' => $refund_note,
            'process_type' => SURPLUS_RETURN,
            'admin_user' => session()->has('admin_name') ? session('admin_name') : (session()->has('seller_name') ? session('seller_name') : ''),
            'admin_note' => sprintf(lang('admin/order.order_refund'), $order['order_sn']),
            'is_paid' => 0
        ];

        UserAccount::insert($account);

        return 1;
    } else {
        return 1;
    }
}

/**
 * 订单退款
 * 储值卡金额
 *
 * @access  public
 * @param $order_id 订单ID
 * @param $vc_id    储值卡ID
 * @param $refound_vcard    储值卡金额
 */
function get_return_vcard($order_id, $vc_id = 0, $refound_vcard = 0, $return_sn = '', $ret_id = 0)
{
    if ($vc_id && $refound_vcard > 0) {
        $time = gmtime();
        $order_info = OrderInfo::where('order_id', $order_id);
        $order_info = app(BaseRepository::class)->getToArrayFirst($order_info);

        $refound_vcard = empty($refound_vcard) ? 0 : $refound_vcard;

        /* 更新储值卡金额 */
        ValueCard::where('vid', $vc_id)->where('user_id', $order_info['user_id'])->increment('card_money', $refound_vcard);

        /* 更新订单使用储值卡金额 */
        $log = [
            'vc_id' => $vc_id,
            'order_id' => $order_id,
            'use_val' => $refound_vcard,
            'vc_dis' => 1,
            'add_val' => $refound_vcard,
            'record_time' => $time
        ];

        ValueCardRecord::insert($log);

        if ($return_sn) {
            $return_sn = "<br/>退换货-流水号：" . $return_sn;
        }

        $note = sprintf($GLOBALS['_LANG']['order_vcard_return'] . $return_sn, $refound_vcard);
        order_action($order_info['order_sn'], $order_info['order_status'], $order_info['shipping_status'], $order_info['pay_status'], $note, null, 0, $time);

        $return_note = sprintf($GLOBALS['_LANG']['order_vcard_return'], $refound_vcard);
        return_action($ret_id, RF_AGREE_APPLY, FF_REFOUND, $return_note);
    }
}

/**
 * 查询订单退换货已退运费金额
 * refund_type 1 退还余额, 3 不处理, 6 原路退款
 * @param int $order_id
 * @param int $ret_id
 * @return mixed
 */
function order_refound_shipping_fee($order_id = 0, $ret_id = 0)
{
    $price = OrderReturn::selectRaw("SUM(return_shipping_fee) AS return_shipping_fee")
        ->where('order_id', $order_id)
        ->whereIn('refund_type', [1, 3, 6])
        ->where('refound_status', 1); // 已退款

    if ($ret_id > 0) {
        $price = $price->where('ret_id', '<>', $ret_id);
    }

    $price = $price->value('return_shipping_fee');

    return $price;
}

/**
 * 查询订单退换货已退储值卡金额
 */
function get_query_vcard_return($order_id)
{
    $res = OrderAction::where('order_id', $order_id)->where('order_status', OS_RETURNED_PART);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $price = 0;
    if ($res) {
        foreach ($res as $key => $row) {
            $res[$key]['action_note'] = !empty($row['action_note']) ? explode("<br/>", $row['action_note']) : '';
            $res[$key]['action_note'] = isset($res[$key]['action_note'][0]) && !empty($res[$key]['action_note'][0]) ? explode("：", $res[$key]['action_note'][0]) : '';
            $price += isset($res[$key]['action_note'][1]) && !empty($res[$key]['action_note'][1]) ? $res[$key]['action_note'][1] : 0;
        }
    }

    return floatval($price);
}

/**
 * 查询订单退换货已退金额
 * refund_type 1 退还余额, 3 不处理, 6 原路退款
 * @param int $order_id
 * @param int $ret_id
 * @return mixed
 */
function order_refound_fee($order_id = 0, $ret_id = 0)
{
    $price = OrderReturn::selectRaw("SUM(actual_return) AS actual_return")
        ->where('order_id', $order_id)
        ->whereIn('refund_type', [1, 3, 6])
        ->where('refound_status', 1);// 已退款

    if ($ret_id > 0) {
        $price = $price->where('ret_id', '<>', $ret_id);
    }

    $price = $price->value('actual_return');

    return $price;
}

/**
 * 获得购物车中的商品
 *
 * @param string $cart_value
 * @param int $type
 * @param int $warehouse_id
 * @param int $area_id
 * @param int $area_city
 * @param int $uid
 * @return array
 */
function get_cart_goods($cart_value = '', $type = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $uid = 0, $favourable_id = 0)
{

    /* 初始化 */
    $goods_list = [];
    $total = [
        'goods_price' => 0, // 本店售价合计（有格式）
        'market_price' => 0, // 市场售价合计（有格式）
        'saving' => 0, // 节省金额（有格式）
        'save_rate' => 0, // 节省百分比
        'goods_amount' => 0, // 本店售价合计（无格式）
        'store_goods_number' => 0, // 门店商品
    ];

    /* 循环、统计 */
    if ($uid > 0) {
        $user_id = $uid;
    } else {
        $user_id = session('user_id', 0);
    }

    $res = Cart::selectRaw('*, IF(parent_id, parent_id, goods_id) AS pid')
        ->whereIn('rec_type', [CART_GENERAL_GOODS, CART_PACKAGE_GOODS])
        ->where('stages_qishu', '-1')
        ->where('store_id', 0);

    $where = [
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'area_city' => $area_city,
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
    ];
    if ($GLOBALS['_CFG']['open_area_goods'] == 1) {
        $res = $res->whereHas('getGoods', function ($query) use ($where) {
            app(DscRepository::class)->getAreaLinkGoods($query, $where['area_id'], $where['area_city']);
        });
    }

    if (!empty($user_id)) {
        $res = $res->where('user_id', $user_id);
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $res = $res->where('session_id', $session_id);
    }

    if (!empty($cart_value)) {
        $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;
        $res = $res->whereIn('rec_id', $cart_value);
    }

    //把购物车商品参与优惠活动的赠品查询出来
    if ($favourable_id > 0) {
        $favourable_arr['favourable_id'] = $favourable_id;
        $favourable_arr['user_id'] = $user_id;

        $res = $res->orWhere(function ($query) use ($favourable_arr) {
            $query->where('is_gift', $favourable_arr['favourable_id']);
            $query->where('user_id', $favourable_arr['user_id']);
        });
    }

    $goodsWhere = [
        'type' => $type,
        'presale' => CART_PRESALE_GOODS,
        'goods_select' => [
            'goods_id', 'cat_id', 'goods_thumb', 'default_shipping',
            'goods_weight as goodsweight', 'is_shipping', 'freight',
            'tid', 'shipping_fee', 'brand_id', 'cloud_id', 'is_delete',
            'is_xiangou', 'xiangou_num', 'xiangou_start_date', 'xiangou_end_date', 'goods_name',
            'goods_number as number'
        ],
        'getRegionWarehouse'
    ];

    if (CROSS_BORDER === true) // 跨境多商户
    {
        array_push($goodsWhere['goods_select'], 'free_rate');
    }

    $res = $res->with([
        'getGoods' => function ($query) use ($goodsWhere) {
            $query = $query->select($goodsWhere['goods_select'])->where('is_delete', 0);

            if ($goodsWhere['type'] == $goodsWhere['presale']) {
                $query = $query->where('is_on_sale', 0);
            }

            $query->with([
                'getGoodsConsumption'
            ]);
        },
        'getWarehouseGoods' => function ($query) use ($where) {
            $query->where('region_id', $where['warehouse_id']);
        },
        'getWarehouseAreaGoods' => function ($query) use ($where) {
            $query = $query->where('region_id', $where['area_id']);

            if ($where['area_pricetype'] == 1) {
                $query->where('city_id', $where['area_city']);
            }
        },
        'getProductsWarehouse' => function ($query) use ($where) {
            $query->where('warehouse_id', $where['warehouse_id']);
        },
        'getProductsArea' => function ($query) use ($where) {
            $query = $query->where('area_id', $where['area_id']);

            if ($where['area_pricetype'] == 1) {
                $query->where('city_id', $where['area_city']);
            }
        },
        'getProducts',
        'getOfflineStore'
    ]);

    $res = $res->withCount('getStoreGoods as store_count');

    $res = $res->orderByRaw("group_id DESC, parent_id ASC, rec_id DESC");

    $res = app(BaseRepository::class)->getToArrayGet($res);

    /* 用于统计购物车中实体商品和虚拟商品的个数 */
    $virtual_goods_count = 0;
    $real_goods_count = 0;
    $total['subtotal_dis_amount'] = 0;
    $total['subtotal_discount_amount'] = 0;
    $store_type = 0;
    $stages_qishu = 0;

    $cart_value = [];
    if ($res) {

        $user_rank = [];
        if ($user_id > 0) {
            $rank = app(UserCommonService::class)->getUserRankByUid($user_id);
            $user_rank['rank_id'] = isset($rank['rank_id']) ? $rank['rank_id'] : 1;
            $user_rank['discount'] = isset($rank['discount']) ? $rank['discount'] / 100 : 1;
        }

        if ($GLOBALS['_CFG']['add_shop_price'] == 1) {
            $add_tocart = 1;
        } else {
            $add_tocart = 0;
        }

        foreach ($res as $key => $row) {

            $goods = $row['get_goods'] ?? [];

            if ($row['model_attr'] == 1) {
                $goods_number = $goods['get_warehouse_goods']['region_number'] ?? 0;
            } elseif ($row['model_attr'] == 2) {
                $goods_number = $goods['get_warehouse_area_goods']['region_number'] ?? 0;
            } else {
                $goods_number = $goods['number'] ?? 0;
            }

            $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;

            //ecmoban模板堂 --zhuo start 限购
            $nowTime = gmtime();
            if ($row['extension_code'] != 'package_buy') {

                $goods['is_xiangou'] = $goods['is_xiangou'] ?? 0;
                $goods['xiangou_num'] = $goods['xiangou_num'] ?? 0;

                $start_date = $goods['xiangou_start_date'] ?? '';
                $end_date = $goods['xiangou_end_date'] ?? '';

                if ($goods['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
                    $orderGoods = app(OrderGoodsService::class)->getForPurchasingGoods($start_date, $end_date, $row['goods_id'], $user_id);
                    if ($orderGoods['goods_number'] >= $goods['xiangou_num']) {

                        //更新购物车中的商品数量
                        Cart::where('rec_id', $row['rec_id'])->update(['goods_number' => 0]);
                    } else {
                        if ($goods['xiangou_num'] > 0) {
                            if ($goods['is_xiangou'] == 1 && $orderGoods['goods_number'] + $row['goods_number'] > $goods['xiangou_num']) {
                                $cart_Num = $goods['xiangou_num'] - $orderGoods['goods_number'];

                                //更新购物车中的商品数量
                                Cart::where('rec_id', $row['rec_id'])->update(['goods_number' => $cart_Num]);

                                //更新限购购物车内商品数量
                                $row['goods_number'] = $cart_Num;
                            }
                        }
                    }
                }

            }
            //ecmoban模板堂 --zhuo end 限购

            if ($row['extension_code'] != 'package_buy' && $row['is_gift'] == 0 && $row['parent_id'] == 0) {
                /* 判断购物车商品价格是否与目前售价一致，如果不同则返回购物车价格失效 */
                $currency_format = !empty($GLOBALS['_CFG']['currency_format']) ? explode('%', $GLOBALS['_CFG']['currency_format']) : '';
                $attr_id = !empty($row['goods_attr_id']) ? explode(',', $row['goods_attr_id']) : '';

                if ($row['extension_code'] != 'package_buy') {
                    $presale = 0;
                } else {
                    $presale = CART_PACKAGE_GOODS;
                }

                if (count($currency_format) > 1) {
                    $goods_price = trim(app(GoodsCommonService::class)->getFinalPrice($row['goods_id'], $row['goods_number'], true, $attr_id, $row['warehouse_id'], $row['area_id'], $row['area_city'], 0, $presale, $add_tocart, 0, 0, $user_rank), $currency_format[0]);
                    $cart_price = trim($row['goods_price'], $currency_format[0]);
                } else {
                    $goods_price = app(GoodsCommonService::class)->getFinalPrice($row['goods_id'], $row['goods_number'], true, $attr_id, $row['warehouse_id'], $row['area_id'], $row['area_city'], 0, $presale, $add_tocart, 0, 0, $user_rank);
                    $cart_price = $row['goods_price'];
                }

                $goods_price = floatval($goods_price);
                $cart_price = floatval($cart_price);

                if ($goods_price != $cart_price && empty($row['is_gift']) && empty($row['group_id'])) {
                    $row['price_is_invalid'] = 1; //价格已过期
                } else {
                    $row['price_is_invalid'] = 0; //价格未过期
                }

                if ($row['price_is_invalid'] && $row['rec_type'] == 0 && empty($row['is_gift']) && $row['extension_code'] != 'package_buy') {
                    if (session('flow_type') == 0 && $goods_price > 0) {
                        app(CartCommonService::class)->getUpdateCartPrice($goods_price, $row['rec_id']);
                        $row['goods_price'] = $goods_price;
                    }
                }
            }

            //ecmoban模板堂 --zhuo start 商品金额促销
            $row['goods_amount'] = $row['goods_price'] * $row['goods_number'];
            if (isset($goods['get_goods_consumption']) && $goods['get_goods_consumption']) {
                $row['amount'] = app(DscRepository::class)->getGoodsConsumptionPrice($goods['get_goods_consumption'], $row['goods_amount']);
            } else {
                $row['amount'] = $row['goods_amount'];
            }

            $total['goods_price'] += $row['amount'];
            $row['subtotal'] = $row['goods_amount'];
            $row['formated_subtotal'] = price_format($row['goods_amount'], false);
            $row['dis_amount'] = $row['goods_amount'] - $row['amount'];
            $row['dis_amount'] = number_format($row['dis_amount'], 2, '.', '');
            $row['discount_amount'] = price_format($row['dis_amount'], false);
            //ecmoban模板堂 --zhuo end 商品金额促销

            $total['subtotal_dis_amount'] += $row['dis_amount'];
            $total['subtotal_discount_amount'] = price_format($total['subtotal_dis_amount'], false);

            $total['market_price'] += $row['market_price'] * $row['goods_number'];
            $row['goods_price_format'] = price_format($row['goods_price'], false);
            $row['formated_goods_price'] = price_format($row['goods_price'], false);
            $row['formated_market_price'] = price_format($row['market_price'], false);

            $row['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

            $row['region_name'] = $row['get_region_warehouse']['region_name'] ?? '';

            /* 统计实体商品和虚拟商品的个数 */
            if ($row['is_real']) {
                $real_goods_count++;
            } else {
                $virtual_goods_count++;
            }

            /* 查询规格 */
            if (trim($row['goods_attr']) != '') {
                $row['goods_attr'] = addslashes($row['goods_attr']);

                $goods_attr = !is_array($row['goods_attr']) ? explode(",", $row['goods_attr']) : $row['goods_attr'];
                $attr_list = GoodsAttr::select('attr_value')->whereIn('goods_attr_id', $goods_attr);
                $attr_list = app(BaseRepository::class)->getToArrayGet($attr_list);
                $attr_list = app(BaseRepository::class)->getFlatten($attr_list);

                if ($attr_list) {
                    foreach ($attr_list as $attr) {
                        $row['goods_name'] .= ' [' . $attr . '] ';
                    }
                }
            }

            /* 增加是否在购物车里显示商品图 */
            if (($GLOBALS['_CFG']['show_goods_in_cart'] == "2" || $GLOBALS['_CFG']['show_goods_in_cart'] == "3") && $row['extension_code'] != 'package_buy') {
                $row['goods_thumb'] = isset($goods['goods_thumb']) ? get_image_path($goods['goods_thumb']) : '';
                if (isset($goods['is_delete']) && $goods['is_delete'] == 1) {
                    $row['is_invalid'] = 1;
                }
            }
            if ($row['extension_code'] == 'package_buy') {
                $activity = get_goods_activity_info($row['goods_id'], ['act_id', 'activity_thumb']);

                if ($activity) {
                    $row['package_goods_list'] = app(PackageGoodsService::class)->getPackageGoods($activity['act_id']);
                    $row['goods_thumb'] = !empty($activity['activity_thumb']) ? get_image_path($activity['activity_thumb']) : app(DscRepository::class)->dscUrl('themes/ecmoban_dsc2017/images/17184624079016pa.jpg');
                } else {
                    //移除无效超值礼包
                    Cart::where([
                        'goods_id' => $row['goods_id'],
                        'extension_code' => 'package_buy'
                    ])->delete();

                    unset($row);
                    continue;
                }
            }

            /* by kong 判断改商品是否存在门店商品 20160725 start */
            $store_count = $row['store_count'];
            if ($store_count > 0) {
                $store_type++; //循环购物车门店商品数量
                $row['store_type'] = 1;
            } else {
                $row['store_type'] = 0;
            }
            /* by kong 判断改商品是否存在门店商品 20160725 end */

            //循环购物车分期商品数量
            if ($row['stages_qishu'] != -1) {
                $stages_qishu++;
            }

            if ($row['extension_code'] != 'package_buy') {

                if ($row['model_attr'] == 1) {
                    $prod = $row['get_products_warehouse'] ?? [];
                } elseif ($row['model_attr'] == 2) {
                    $prod = $row['get_products_area'] ?? [];
                } else {
                    $prod = $row['get_products'] ?? [];
                }

                $attr_number = $prod ? $prod['product_number'] : 0;

                //当商品没有属性库存时
                if (empty($prod)) {
                    $attr_number = ($GLOBALS['_CFG']['use_storage'] == 1) ? $goods_number : 1;
                }

                //贡云商品 验证库存
                if (isset($row['cloud_id']) && $row['cloud_id'] > 0 && $row['product_id'] > 0) {
                    $attr_number = app(JigonManageService::class)->jigonGoodsNumber(['product_id' => $row['product_id']]);
                }

                $attr_number = !empty($attr_number) ? $attr_number : 0;
                $row['attr_number'] = $attr_number;
            } else {
                if ($row['extension_code'] == 'package_buy') {
                    $row['attr_number'] = !app(PackageGoodsService::class)->judgePackageStock($row['goods_id'], $row['goods_number']);
                } else {
                    $row['attr_number'] = $row['goods_number'];
                }
            }
            $row['product_number'] = $row['attr_number'];
            $row['stores_name'] = $row['get_offline_store']['stores_name'] ?? '';

            //判断是否支持门店自提
            $row['is_chain'] = app(StoreCommonService::class)->judgeStoreGoods($row['goods_id']) && !$row['parent_id'];
            if ($row['is_chain']) {
                $total['store_goods_number'] += 1;
            }

            if ($row['is_checked'] == 1) {
                $cart_value[$row['ru_id']][$key] = $row['rec_id'];
            }

            $goods_list[] = $row;
        }
    } else {
        $cart_value = [];
    }

    $total['goods_amount'] = $total['goods_price'];

    $total['saving'] = price_format($total['market_price'] - $total['goods_price'], false);
    if ($total['market_price'] > 0) {
        $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
                100 / $total['market_price']) . '%' : 0;
    }
    $total['goods_price'] = price_format($total['goods_price'], false);
    $total['market_price'] = price_format($total['market_price'], false);
    $total['real_goods_count'] = $real_goods_count;
    $total['virtual_goods_count'] = $virtual_goods_count;

    if ($type == 1) {
        $goods_list = get_cart_goods_ru_list($goods_list, $type);
        $goods_list = get_cart_ru_goods_list($goods_list);
    }

    $total['store_type'] = $store_type;
    $total['stages_qishu'] = $stages_qishu;

    return ['goods_list' => $goods_list, 'total' => $total, 'cart_value' => $cart_value];
}

/**
 * 区分商家商品
 *
 * @param array $goods_list
 * @param string $cart_value
 * @param string $consignee
 * @param int $store_id
 * @param int $user_id
 * @return array
 */
function get_cart_ru_goods_list($goods_list = [], $cart_value = '', $consignee = '', $store_id = 0, $user_id = 0)
{
    if (empty($user_id)) {
        $user_id = session('user_id', 0);
    }

    //配送方式选择
    $point_id = session('flow_consignee.point_id', 0);
    $consignee_district_id = session('flow_consignee.district', 0);

    $arr = [];
    foreach ($goods_list as $key => $row) {
        $shipping_type = session()->has('merchants_shipping.shipping_type') ? intval(session()->get('merchants_shipping.shipping_type', 0)) : 0;
        $ru_name = app(MerchantCommonService::class)->getShopName($key, 1);

        $arr[$key]['ru_id'] = $key;
        $arr[$key]['shipping_type'] = $shipping_type;
        $arr[$key]['ru_name'] = $ru_name;
        $arr[$key]['url'] = app(DscRepository::class)->buildUri('merchants_store', ['urid' => $key], $ru_name);
        $arr[$key]['goods_amount'] = 0;

        foreach ($row as $gkey => $grow) {
            $arr[$key]['goods_amount'] += $grow['goods_price'] * $grow['goods_number'];
        }

        if ($cart_value) {
            $ru_shippng = get_ru_shippng_info($row, $cart_value, $key, $consignee, $user_id);

            $arr[$key]['shipping'] = $ru_shippng['shipping_list'];
            $arr[$key]['is_freight'] = $ru_shippng['is_freight'];
            $arr[$key]['shipping_rec'] = $ru_shippng['shipping_rec'];

            $arr[$key]['shipping_count'] = !empty($arr[$key]['shipping']) ? count($arr[$key]['shipping']) : 0;
            if (!empty($arr[$key]['shipping'])) {
                $arr[$key]['shipping'] = array_values($arr[$key]['shipping']);
                $arr[$key]['tmp_shipping_id'] = isset($arr[$key]['shipping'][0]['shipping_id']) ? $arr[$key]['shipping'][0]['shipping_id'] : 0; //默认选中第一个配送方式
                foreach ($arr[$key]['shipping'] as $kk => $vv) {
                    $vv['default'] = isset($vv['default']) ? $vv['default'] : 0;
                    if ($vv['default'] == 1) {
                        $arr[$key]['tmp_shipping_id'] = $vv['shipping_id'];
                        continue;
                    }
                }
            }
        }

        /*  @author-bylu 判断当前商家是否允许"在线客服" start */
        $shop_information = app(MerchantCommonService::class)->getShopName($key); //通过ru_id获取到店铺信息;
        $arr[$key]['is_IM'] = isset($shop_information['is_IM']) ? $shop_information['is_IM'] : ''; //平台是否允许商家使用"在线客服";
        //判断当前商家是平台,还是入驻商家 bylu
        if ($key == 0) {
            //判断平台是否开启了IM在线客服
            $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
            if ($kf_im_switch) {
                $arr[$key]['is_dsc'] = true;
            } else {
                $arr[$key]['is_dsc'] = false;
            }
        } else {
            $arr[$key]['is_dsc'] = false;
        }
        /*  @author-bylu  end */
        //自营有自提点--key=ru_id

        if ($key > 0) {
            $basic_info = $shop_information;
        } else {
            $basic_info = SellerShopinfo::where('ru_id', $key);
            $basic_info = app(BaseRepository::class)->getToArrayFirst($basic_info);
        }

        $chat = app(DscRepository::class)->chatQq($basic_info);
        $arr[$key]['kf_type'] = $chat['kf_type'];
        $arr[$key]['kf_qq'] = $chat['kf_qq'];
        $arr[$key]['kf_ww'] = $chat['kf_ww'];

        if ($key == 0 && $consignee_district_id > 0) {
            $where = [
                'district' => $consignee_district_id,
                'point_id' => $point_id,
                'limit' => 1
            ];
            $self_point = app(CartService::class)->getSelfPointCart($where);

            if (!empty($self_point)) {
                $arr[$key]['self_point'] = $self_point[0];
            }
        }
        /*获取门店信息 by kong 20160726 start*/
        if ($store_id > 0) {
            $offline_store = OfflineStore::where('id', $store_id);

            $offline_store = $offline_store->with([
                'getRegionProvince' => function ($query) {
                    $query->selectRaw('region_id, region_name as province');
                },
                'getRegionCity' => function ($query) {
                    $query->selectRaw('region_id, region_name as city');
                },
                'getRegionDistrict' => function ($query) {
                    $query->selectRaw('region_id, region_name as district');
                }
            ]);

            $offline_store = app(BaseRepository::class)->getToArrayFirst($offline_store);

            if ($offline_store) {
                $offline_store = $offline_store['get_region_province'] ? array_merge($offline_store, $offline_store['get_region_province']) : $offline_store;
                $offline_store = $offline_store['get_region_city'] ? array_merge($offline_store, $offline_store['get_region_city']) : $offline_store;
                $offline_store = $offline_store['get_region_district'] ? array_merge($offline_store, $offline_store['get_region_district']) : $offline_store;
                $offline_store['stores_img'] = get_image_path($offline_store['stores_img']);
            }

            $arr[$key]['offline_store'] = $offline_store;
        }

        if ($row) {
            $shipping_rec = isset($ru_shippng['shipping_rec']) && !empty($ru_shippng['shipping_rec']) ? $ru_shippng['shipping_rec'] : [];

            foreach ($row as $k => $v) {
                if ($shipping_rec && in_array($v['rec_id'], $shipping_rec)) {
                    $row[$k]['rec_shipping'] = 0; //不支持配送
                } else {
                    $row[$k]['rec_shipping'] = 1; //支持配送
                }
            }
        }

        /*获取门店信息 by kong 20160726 end*/
        $arr[$key]['goods_list'] = $row;
    }

    $goods_list = array_values($arr);
    return $goods_list;
}

/*
 * 查询商家默认配送方式
 */
function get_ru_shippng_info($cart_goods, $cart_value, $ru_id, $consignee = '', $user_id = 0)
{
    if (empty($user_id)) {
        $user_id = session('user_id', 0);
    }

    //分离商家信息by wu start
    $cart_value_arr = [];
    $cart_freight = [];
    $shipping_rec = [];

    $freight = '';
    foreach ($cart_goods as $cgk => $cgv) {
        if ($cgv['ru_id'] != $ru_id) {
            unset($cart_goods[$cgk]);
        } else {
            $cart_value_list = !is_array($cart_value) ? explode(',', $cart_value) : $cart_value;
            if (in_array($cgv['rec_id'], $cart_value_list)) {
                $cart_value_arr[] = $cgv['rec_id'];

                if ($cgv['freight'] == 2) {
                    if (empty($cgv['tid'])) {
                        $shipping_rec[] = $cgv['rec_id'];
                    }

                    @$cart_freight[$cgv['rec_id']][$cgv['freight']] = $cgv['tid'];
                }

                $freight .= $cgv['freight'] . ",";
            }
        }
    }

    if ($freight) {
        $freight = app(DscRepository::class)->delStrComma($freight);
    }

    $is_freight = 0;
    if ($freight) {
        $freight = explode(",", $freight);
        $freight = array_unique($freight);

        /**
         * 判断是否有《地区运费》
         */
        if (in_array(2, $freight)) {
            $is_freight = 1;
        }
    }

    $cart_value = implode(',', $cart_value_arr);
    //分离商家信息by wu end

    $order = flow_order_info($user_id);

    $seller_shipping = get_seller_shipping_type($ru_id);
    $shipping_id = $seller_shipping && isset($seller_shipping['shipping_id']) ? $seller_shipping['shipping_id'] : 0;

    $consignee = session()->has('flow_consignee') ? session('flow_consignee') : $consignee;

    $region = [0, 0, 0, 0, 0];
    if ($consignee) {
        $consignee['street'] = isset($consignee['street']) ? $consignee['street'] : 0;
        $region = [$consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'], $consignee['street']];
    }

    $insure_disabled = true;
    $cod_disabled = true;

    // 查看购物车中是否全为免运费商品，若是则把运费赋为零
    $shipping_count = Cart::where('extension_code', '<>', 'package_buy')
        ->where('is_shipping', 0)
        ->where('ru_id', $ru_id);

    if (!empty($user_id)) {
        $shipping_count = $shipping_count->where('user_id', $user_id);
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $shipping_count = $shipping_count->where('session_id', $session_id);
    }

    if ($cart_value) {
        $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;

        $shipping_count = $shipping_count->whereIn('rec_id', $cart_value);
    }

    $shipping_count = $shipping_count->count();

    $shipping_list = [];

    if ($is_freight) {
        if ($cart_freight) {
            $list1 = [];
            $list2 = [];

            $tid = '';
            foreach ($cart_freight as $key => $row) {
                if (isset($row[2]) && $row[2]) {
                    $tid .= $row[2] . ',';
                }
            }

            $transport_list = [];
            if ($tid) {
                $tid = trim($tid, ',');
                $tid = explode(',', $tid);

                $transport_list = GoodsTransport::whereIn('tid', $tid);
                $transport_list = app(BaseRepository::class)->getToArrayGet($transport_list);
            }

            if ($transport_list) {
                foreach ($transport_list as $tkey => $trow) {
                    if ($trow['freight_type'] == 1) {
                        $shipping_list1 = Shipping::select('shipping_id', 'shipping_code', 'shipping_name', 'shipping_order')->where('enabled', 1);
                        $shipping_list1 = $shipping_list1->whereHas('getGoodsTransportTpl', function ($query) use ($region, $ru_id, $trow) {
                            $query->whereRaw("(FIND_IN_SET('" . $region[1] . "', region_id) OR FIND_IN_SET('" . $region[2] . "', region_id) OR FIND_IN_SET('" . $region[3] . "', region_id) OR FIND_IN_SET('" . $region[4] . "', region_id))")
                                ->where('user_id', $ru_id)
                                ->where('tid', $trow['tid']);
                        });
                        $shipping_list1 = app(BaseRepository::class)->getToArrayGet($shipping_list1);

                        if (empty($shipping_list1)) {
                            $shipping_rec[] = $key;
                        }

                        $list1[] = $shipping_list1;
                    } else {
                        $shipping_list2 = GoodsTransportExpress::where('tid', $trow['tid'])->where('ru_id', $ru_id);

                        $shipping_list2 = $shipping_list2->whereHas('getGoodsTransportExtend', function ($query) use ($ru_id, $trow, $region) {
                            $query->where('ru_id', $ru_id)
                                ->where('tid', $trow['tid'])
                                ->whereRaw("((FIND_IN_SET('" . $region[1] . "', top_area_id)) OR (FIND_IN_SET('" . $region[2] . "', area_id) OR FIND_IN_SET('" . $region[3] . "', area_id) OR FIND_IN_SET('" . $region[4] . "', area_id)))");
                        });

                        $shipping_list2 = app(BaseRepository::class)->getToArrayGet($shipping_list2);

                        if ($shipping_list2) {
                            $new_shipping = [];
                            foreach ($shipping_list2 as $gtkey => $gtval) {
                                $gt_shipping_id = !is_array($gtval['shipping_id']) ? explode(",", $gtval['shipping_id']) : $gtval['shipping_id'];
                                $new_shipping[] = $gt_shipping_id ? $gt_shipping_id : [];
                            }

                            $new_shipping = app(BaseRepository::class)->getFlatten($new_shipping);

                            if ($new_shipping) {
                                $shippingInfo = Shipping::select('shipping_id', 'shipping_code', 'shipping_name', 'shipping_order')
                                    ->where('enabled', 1)
                                    ->whereIn('shipping_id', $new_shipping);
                                $list2[] = app(BaseRepository::class)->getToArrayGet($shippingInfo);
                            }
                        }

                        if (empty($list2)) {
                            $shipping_rec[] = $key;
                        }
                    }
                }
            }

            $shipping_list1 = get_three_to_two_array($list1);
            $shipping_list2 = get_three_to_two_array($list2);

            if ($shipping_list1 && $shipping_list2) {
                $shipping_list = array_merge($shipping_list1, $shipping_list2);
            } elseif ($shipping_list1) {
                $shipping_list = $shipping_list1;
            } elseif ($shipping_list2) {
                $shipping_list = $shipping_list2;
            }

            if ($shipping_list) {
                //去掉重复配送方式 start
                $new_shipping = [];
                foreach ($shipping_list as $key => $val) {
                    @$new_shipping[$val['shipping_code']][] = $key;
                }

                foreach ($new_shipping as $key => $val) {
                    if (count($val) > 1) {
                        for ($i = 1; $i < count($val); $i++) {
                            unset($shipping_list[$val[$i]]);
                        }
                    }
                }
                //去掉重复配送方式 end

                $shipping_list = collect($shipping_list)->sortBy('shipping_order');
                $shipping_list = $shipping_list->values()->all();
            }
        }

        $configure_value = 0;
        $configure_type = 0;

        if ($shipping_list) {
            $str_shipping = '';
            foreach ($shipping_list as $key => $row) {
                if ($row['shipping_id']) {
                    $str_shipping .= $row['shipping_id'] . ",";
                }
            }

            $str_shipping = app(DscRepository::class)->delStrComma($str_shipping);
            $str_shipping = explode(",", $str_shipping);
            if (in_array($shipping_id, $str_shipping)) {
                $have_shipping = 1;
            } else {
                $have_shipping = 0;
            }

            foreach ($shipping_list as $key => $val) {
                if (substr($val['shipping_code'], 0, 5) != 'ship_') {
                    if ($GLOBALS['_CFG']['freight_model'] == 0) {

                        /* 商品单独设置运费价格 start */
                        if ($cart_goods) {
                            if (count($cart_goods) == 1) {
                                $cart_goods = array_values($cart_goods);

                                if (!empty($cart_goods[0]['freight']) && $cart_goods[0]['is_shipping'] == 0) {
                                    if ($cart_goods[0]['freight'] == 1) {
                                        $configure_value = $cart_goods[0]['shipping_fee'] * $cart_goods[0]['goods_number'];
                                    } else {
                                        $trow = get_goods_transport($cart_goods[0]['tid']);

                                        if ($trow['freight_type']) {
                                            $cart_goods[0]['user_id'] = $cart_goods[0]['ru_id'];
                                            $transport_tpl = get_goods_transport_tpl($cart_goods[0], $region, $val, $cart_goods[0]['goods_number']);

                                            $configure_value = isset($transport_tpl['shippingFee']) ? $transport_tpl['shippingFee'] : 0;
                                        } else {

                                            /**
                                             * 商品运费模板
                                             * 自定义
                                             */
                                            $custom_shipping = app(OrderTransportService::class)->getGoodsCustomShipping($cart_goods);

                                            /* 运费模板配送方式 start */
                                            $transport = ['top_area_id', 'area_id', 'tid', 'ru_id', 'sprice'];
                                            $goods_transport = GoodsTransportExtend::select($transport)
                                                ->where('ru_id', $cart_goods[0]['ru_id'])
                                                ->where('tid', $cart_goods[0]['tid']);

                                            $goods_transport = $goods_transport->whereRaw("(FIND_IN_SET('" . $consignee['city'] . "', area_id))");
                                            $goods_transport = app(BaseRepository::class)->getToArrayFirst($goods_transport);
                                            /* 运费模板配送方式 end */

                                            /* 运费模板配送方式 start */
                                            $ship_transport = ['tid', 'ru_id', 'shipping_fee'];
                                            $goods_ship_transport = GoodsTransportExpress::select($ship_transport)
                                                ->where('ru_id', $cart_goods[0]['ru_id'])
                                                ->where('tid', $cart_goods[0]['tid']);

                                            $goods_ship_transport = $goods_ship_transport->whereRaw("(FIND_IN_SET('" . $val['shipping_id'] . "', shipping_id))");
                                            $goods_ship_transport = app(BaseRepository::class)->getToArrayFirst($goods_ship_transport);
                                            /* 运费模板配送方式 end */

                                            $goods_transport['sprice'] = isset($goods_transport['sprice']) ? $goods_transport['sprice'] : 0;
                                            $goods_ship_transport['shipping_fee'] = isset($goods_ship_transport['shipping_fee']) ? $goods_ship_transport['shipping_fee'] : 0;

                                            /* 是否免运费 start */
                                            if ($custom_shipping && $custom_shipping[$cart_goods[0]['tid']]['amount'] >= $trow['free_money'] && $trow['free_money'] > 0) {
                                                $is_shipping = 1; /* 免运费 */
                                            } else {
                                                $is_shipping = 0; /* 有运费 */
                                            }
                                            /* 是否免运费 end */

                                            if ($is_shipping == 0) {
                                                if ($trow['type'] == 1) {
                                                    $configure_value = $goods_transport['sprice'] * $cart_goods[0]['goods_number'] + $goods_ship_transport['shipping_fee'] * $cart_goods[0]['goods_number'];
                                                } else {
                                                    $configure_value = $goods_transport['sprice'] + $goods_ship_transport['shipping_fee'];
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    /* 有配送按配送区域计算运费 */
                                    $configure_type = 1;
                                }
                            } else {
                                $order_transpor = app(OrderTransportService::class)->getOrderTransport($cart_goods, $consignee, $val['shipping_id'], $val['shipping_code']);

                                if ($order_transpor['freight']) {
                                    /* 有配送按配送区域计算运费 */
                                    $configure_type = 1;
                                }

                                $configure_value = isset($order_transpor['sprice']) ? $order_transpor['sprice'] : 0;
                            }
                        }
                        /* 商品单独设置运费价格 end */

                        $shipping_fee = $shipping_count == 0 ? 0 : $configure_value;
                        $shipping_list[$key]['free_money'] = price_format(0, false);
                    }

                    // 上门自提免配送费
                    if ($val['shipping_code'] == 'cac') {
                        $shipping_fee = 0;
                    }

                    $shipping_list[$key]['shipping_id'] = $val['shipping_id'];
                    $shipping_list[$key]['shipping_name'] = $val['shipping_name'];
                    $shipping_list[$key]['shipping_code'] = $val['shipping_code'];
                    $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
                    $shipping_list[$key]['shipping_fee'] = $shipping_fee;

                    if (isset($val['insure']) && $val['insure']) {
                        $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure'];
                    }

                    /* 当前的配送方式是否支持保价 */
                    if ($val['shipping_id'] == $order['shipping_id']) {
                        if (isset($val['insure']) && $val['insure']) {
                            $insure_disabled = ($val['insure'] == 0);
                        }
                        if (isset($val['support_cod']) && $val['support_cod']) {
                            $cod_disabled = ($val['support_cod'] == 0);
                        }
                    }

                    //默认配送方式
                    if ($have_shipping == 1) {
                        $shipping_list[$key]['default'] = 0;
                        if ($shipping_id == $val['shipping_id']) {
                            $shipping_list[$key]['default'] = 1;
                        }
                    } else {
                        if ($key == 0) {
                            $shipping_list[$key]['default'] = 1;
                        }
                    }

                    $shipping_list[$key]['insure_disabled'] = $insure_disabled;
                    $shipping_list[$key]['cod_disabled'] = $cod_disabled;
                }

                // 兼容过滤ecjia配送方式
                if (substr($val['shipping_code'], 0, 5) == 'ship_') {
                    unset($shipping_list[$key]);
                }
            }

            //去掉重复配送方式 by wu start
            $shipping_type = [];
            foreach ($shipping_list as $key => $val) {
                @$shipping_type[$val['shipping_code']][] = $key;
            }

            foreach ($shipping_type as $key => $val) {
                if (count($val) > 1) {
                    for ($i = 1; $i < count($val); $i++) {
                        unset($shipping_list[$val[$i]]);
                    }
                }
            }
            //去掉重复配送方式 by wu end
        }
    } else {
        $configure_value = 0;

        /* 商品单独设置运费价格 start */
        if ($cart_goods) {
            if (count($cart_goods) == 1) {
                $cart_goods = array_values($cart_goods);

                if (!empty($cart_goods[0]['freight']) && $cart_goods[0]['is_shipping'] == 0) {
                    $configure_value = $cart_goods[0]['shipping_fee'] * $cart_goods[0]['goods_number'];
                } else {
                    /* 有配送按配送区域计算运费 */
                    $configure_type = 1;
                }
            } else {
                $sprice = 0;
                foreach ($cart_goods as $key => $row) {
                    if ($row['is_shipping'] == 0) {
                        $sprice += $row['shipping_fee'] * $row['goods_number'];
                    }
                }

                $configure_value = $sprice;
            }
        }
        /* 商品单独设置运费价格 end */

        $shipping_fee = $shipping_count == 0 ? 0 : $configure_value;

        // 上门自提免配送费
        if (isset($seller_shipping['shipping_code']) && $seller_shipping['shipping_code'] == 'cac') {
            $shipping_fee = 0;
        }

        $shipping_list[0]['free_money'] = price_format(0, false);
        $shipping_list[0]['format_shipping_fee'] = price_format($shipping_fee, false);
        $shipping_list[0]['shipping_fee'] = $shipping_fee;
        $shipping_list[0]['shipping_id'] = isset($seller_shipping['shipping_id']) && !empty($seller_shipping['shipping_id']) ? $seller_shipping['shipping_id'] : 0;
        $shipping_list[0]['shipping_name'] = isset($seller_shipping['shipping_name']) && !empty($seller_shipping['shipping_name']) ? $seller_shipping['shipping_name'] : '';
        $shipping_list[0]['shipping_code'] = isset($seller_shipping['shipping_code']) && !empty($seller_shipping['shipping_code']) ? $seller_shipping['shipping_code'] : '';
        $shipping_list[0]['default'] = 1;
    }

    $arr = ['is_freight' => $is_freight, 'shipping_list' => $shipping_list, 'shipping_rec' => $shipping_rec];
    return $arr;
}

/**
 * 返回固定运费价格
 */
function get_configure_order($configure, $value = 0, $type = 0)
{
    if ($configure) {
        foreach ($configure as $key => $val) {
            if ($val['name'] === 'base_fee') {
                if ($type == 1) {
                    $configure[$key]['value'] += $value;
                } else {
                    $configure[$key]['value'] = $value;
                }
            }
        }
    }

    return $configure;
}

//提交订单配送方式 --ecmoban模板堂 --zhuo
function get_order_post_shipping($shipping, $shippingCode = [], $shippingType = [], $ru_id = 0)
{
    $shipping_list = [];
    if ($shipping) {
        $shipping_id = '';
        $shipping_name = '';
        $shipping_code = '';
        $shipping_type = '';
        foreach ($shipping as $k1 => $v1) {
            $v1 = !empty($v1) ? intval($v1) : 0;
            $shippingCode[$k1] = !empty($shippingCode[$k1]) ? addslashes($shippingCode[$k1]) : '';
            $shippingType[$k1] = empty($shippingType[$k1]) ? 0 : intval($shippingType[$k1]);

            $shippingInfo = shipping_info($v1);

            foreach ($ru_id as $k2 => $v2) {
                if ($k1 == $k2) {
                    $shipping_id .= $v2 . "|" . $v1 . ",";  //商家ID + 配送ID
                    $shipping_name .= $v2 . "|" . $shippingInfo['shipping_name'] . ",";  //商家ID + 配送名称
                    $shipping_code .= $v2 . "|" . $shippingCode[$k1] . ",";  //商家ID + 配送code
                    $shipping_type .= $v2 . "|" . $shippingType[$k1] . ",";  //商家ID + （配送或自提）
                }
            }
        }

        $shipping_id = substr($shipping_id, 0, -1);
        $shipping_name = substr($shipping_name, 0, -1);
        $shipping_code = substr($shipping_code, 0, -1);
        $shipping_type = substr($shipping_type, 0, -1);
        $shipping_list = [
            'shipping_id' => $shipping_id,
            'shipping_name' => $shipping_name,
            'shipping_code' => $shipping_code,
            'shipping_type' => $shipping_type
        ];
    }
    return $shipping_list;
}

/**
 * 获得虚拟商品的卡号密码 by wu
 */
function get_virtual_goods_info($rec_id = 0)
{
    load_helper('code');

    $virtual_info = VirtualCard::where('is_saled', 1)
        ->whereHas('getOrder', function ($query) use ($rec_id) {
            $query->whereHas('getOrderGoods', function ($query) use ($rec_id) {
                $query->where('rec_id', $rec_id);
            });
        });

    $virtual_info = app(BaseRepository::class)->getToArrayGet($virtual_info);

    $virtual = [];
    if ($virtual_info) {
        foreach ($virtual_info as $row) {
            $res['card_sn'] = dsc_decrypt($row['card_sn']);
            $res['card_password'] = dsc_decrypt($row['card_password']);
            $res['end_date'] = local_date($GLOBALS['_CFG']['date_format'], $row['end_date']);
            $virtual[] = $res;
        }
    }

    return $virtual;
}

/**
 * 获得上一次用户采用的支付和配送方式
 *
 * @access  public
 * @return  void
 */
function last_shipping_and_payment()
{
    $user_id = session('user_id', 0);

    $OrderRep = app(OrderService::class);

    $where = [
        'user_id' => $user_id
    ];
    $row = $OrderRep->getOrderInfo($where);

    if (empty($row)) {
        /* 如果获得是一个空数组，则返回默认值 */
        $row = ['shipping_id' => 0, 'pay_id' => 0];
    }

    return $row;
}

/**
 * 处理红包（下订单时设为使用，取消（无效，退货）订单时设为未使用
 * @param int $bonus_id 红包编号
 * @param int $order_id 订单号
 * @param int $is_used 是否使用了
 */
function change_user_bonus($bonus_id, $order_id, $is_used = true)
{
    if ($is_used) {
        $other = [
            'used_time' => gmtime(),
            'order_id' => $order_id
        ];
    } else {
        $other = [
            'used_time' => 0,
            'order_id' => 0
        ];
    }

    UserBonus::where('bonus_id', $bonus_id)->update($other);
}

/**
 * 获得订单信息
 *
 * @param int $user_id
 * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed
 */
function flow_order_info($user_id = 0)
{
    $order = session()->has('flow_order') ? session('flow_order') : [];

    /* 初始化配送和支付方式 */
    if (!isset($order['shipping_id']) || !isset($order['pay_id'])) {
        /* 如果还没有设置配送和支付 */
        if ($user_id > 0) {
            /* 用户已经登录了，则获得上次使用的配送和支付 */
            $arr = last_shipping_and_payment();

            if (!isset($order['shipping_id'])) {
                $order['shipping_id'] = $arr['shipping_id'];
            }
            if (!isset($order['pay_id'])) {
                $order['pay_id'] = $arr['pay_id'];
            }
        } else {
            if (!isset($order['shipping_id'])) {
                $order['shipping_id'] = 0;
            }
            if (!isset($order['pay_id'])) {
                $order['pay_id'] = 0;
            }
        }
    }

    if (!isset($order['pack_id'])) {
        $order['pack_id'] = 0;  // 初始化包装
    }
    if (!isset($order['card_id'])) {
        $order['card_id'] = 0;  // 初始化贺卡
    }
    if (!isset($order['bonus'])) {
        $order['bonus'] = 0;    // 初始化红包
    }
    if (!isset($order['value_card'])) {
        $order['value_card'] = 0;    // 初始化储值卡
    }
    if (!isset($order['coupons'])) {
        $order['coupons'] = 0;    // 初始化优惠券 bylu
    }
    if (!isset($order['integral'])) {
        $order['integral'] = 0; // 初始化积分
    }
    if (!isset($order['surplus'])) {
        $order['surplus'] = 0;  // 初始化余额
    }

    /* 扩展信息 */
    if (session('flow_type') != CART_GENERAL_GOODS) {
        $order['extension_code'] = session('extension_code', '');
        $order['extension_id'] = session('extension_id', 0);
    }

    return $order;
}

/**
 * 合并订单
 * @param array $from_order_sn 从订单号
 * @param string $to_order_sn 主订单号
 * @return  成功返回true，失败返回错误信息
 */
function merge_order($from_order_sn_arr, $to_order_sn)
{
    /* 订单号不能为空 */
    if (empty($from_order_sn_arr) || trim($to_order_sn) == '') {
        return $GLOBALS['_LANG']['order_sn_not_null'];
    }

    /* 订单号不能相同 */
    if (in_array($to_order_sn, $from_order_sn_arr)) {
        return $GLOBALS['_LANG']['two_order_sn_same'];
    }

    $order_id_arr = [];
    $order = $to_order = order_info(0, $to_order_sn);

    foreach ($from_order_sn_arr as $key => $from_order_sn) {
        /* 查询订单商家ID */
        $from_order_seller = get_order_seller_id($from_order_sn, 1);
        $to_order_seller = get_order_seller_id($to_order_sn, 1);

        if (empty($from_order_seller) || empty($to_order_seller) || ($from_order_seller['ru_id'] != $to_order_seller['ru_id'])) {
            return $GLOBALS['_LANG']['seller_order_sn_same'];
        }

        /* 取得订单信息 */
        $from_order = order_info(0, $from_order_sn);

        /* 检查订单是否存在 */
        if (!$from_order) {
            return sprintf($GLOBALS['_LANG']['order_not_exist'], $from_order_sn);
        }

        /* 检查合并的订单是否为普通订单，非普通订单不允许合并 */
        if ($from_order['extension_code'] != '' || $order['extension_code'] != 0) {
            return $GLOBALS['_LANG']['merge_invalid_order'];
        }

        /* 检查订单状态是否是已确认或未确认、未付款、未发货 */
        if ($from_order['order_status'] != OS_CONFIRMED) {
            return sprintf($GLOBALS['_LANG']['os_not_unconfirmed_or_confirmed'], $from_order_sn);
        } elseif ($from_order['pay_status'] != $order['pay_status']) {
            return $GLOBALS['_LANG']['ps_not_same'];
        } elseif ($from_order['shipping_status'] != SS_UNSHIPPED) {
            return sprintf($GLOBALS['_LANG']['ss_not_unshipped'], $from_order_sn);
        }

        /* 检查订单用户是否相同 */
        if ($from_order['user_id'] != $order['user_id']) {
            return $GLOBALS['_LANG']['order_user_not_same'];
        }

        /* 合并订单 */

        $order['order_id'] = '';
        $order['add_time'] = gmtime();

        // 合并商品总额
        $order['goods_amount'] += $from_order['goods_amount'];

        // 合并折扣
        $order['discount'] += $from_order['discount'];

        if ($order['shipping_id'] > 0) {
            $shipping_area = shipping_info($order['shipping_id']);
            $shipping_area['configure'] = !empty($shipping_area['configure']) ? unserialize($shipping_area['configure']) : '';
            $order['shipping_fee'] += $from_order['shipping_fee'];

            // 如果保价了，重新计算保价费
            if ($order['insure_fee'] > 0) {
                $order['insure_fee'] += $from_order['insure_fee'];
            }
        }

        // 重新计算包装费、贺卡费
        if ($order['pack_id'] > 0) {
            $pack = pack_info($order['pack_id']);
            $order['pack_fee'] = $pack['free_money'] > $order['goods_amount'] ? $pack['pack_fee'] : 0;
        }
        if ($order['card_id'] > 0) {
            $card = card_info($order['card_id']);
            $order['card_fee'] = $card['free_money'] > $order['goods_amount'] ? $card['card_fee'] : 0;
        }

        // 红包不变，合并积分、余额、已付款金额
        $order['integral'] += $from_order['integral'];
        $order['integral_money'] = app(DscRepository::class)->valueOfIntegral($order['integral']);
        $order['surplus'] += $from_order['surplus'];
        $order['money_paid'] += $from_order['money_paid'];

        // 计算应付款金额（不包括支付费用）
        $order['order_amount'] = $order['goods_amount'] - $order['discount']
            + $order['shipping_fee']
            + $order['insure_fee']
            + $order['pack_fee']
            + $order['card_fee']
            - $order['bonus']
            - $order['integral_money']
            - $order['surplus']
            - $order['money_paid'];

        // 重新计算支付费
        if ($order['pay_id'] > 0) {
            // 货到付款手续费
            $cod_fee = !empty($shipping_area) ? $shipping_area['pay_fee'] : 0;
            $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);

            // 应付款金额加上支付费
            $order['order_amount'] += $order['pay_fee'];
        }

        /* 返还 from_order 的红包，因为只使用 to_order 的红包 */
        if ($from_order['bonus_id'] > 0) {
            unuse_bonus($from_order['bonus_id']);
        }
        array_push($order_id_arr, $from_order['order_id'], $to_order['order_id']);
    }

    $order_id_arr = array_unique($order_id_arr);

    /* 插入订单表 */
    $order['order_sn'] = get_order_sn();
    $order = app(BaseRepository::class)->getArrayfilterTable($order, 'order_info');

    $order_id = OrderInfo::insertGetId(addslashes_deep($order));

    if (!$order_id) {
        return false;
    }

    /* 更新订单商品 */
    OrderGoods::whereIn('order_id', $order_id_arr)->update(['order_id' => $order_id]);

    load_helper('clips');

    /* 插入支付日志 */
    insert_pay_log($order_id, $order['order_amount'], PAY_ORDER);

    /* 删除原订单 */
    OrderInfo::whereIn('order_id', $order_id_arr)->delete();

    /* 删除原订单支付日志 */
    PayLog::whereIn('order_id', $order_id_arr)->delete();

    /* 返回成功 */
    return true;
}

/**
 * 查询配送区域属于哪个办事处管辖
 * @param array $regions 配送区域（1、2、3、4级按顺序）
 * @return  int     办事处id，可能为0
 */
function get_agency_by_regions($regions)
{
    if (!is_array($regions) || empty($regions)) {
        return 0;
    }

    $regions = app(BaseRepository::class)->getExplode($regions);

    $res = Region::whereIn('region_id', $regions)
        ->where('region_id', '>', 0)
        ->where('agency_id', '>', 0);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $row) {
            $arr[$row['region_id']] = $row['agency_id'];
        }
    }

    if (empty($arr)) {
        return 0;
    }

    $agency_id = 0;
    for ($i = count($regions) - 1; $i >= 0; $i--) {
        if (isset($arr[$regions[$i]])) {
            return $arr[$regions[$i]];
        }
    }
}

/**
 * 获取配送插件的实例
 * @param int $shipping_id 配送插件ID
 * @return  object     配送插件对象实例
 */
function get_shipping_object($shipping_id)
{
    $shipping = shipping_info($shipping_id);
    if (!$shipping) {
        return false;
    }

    if ($shipping['shipping_code']) {
        // 过滤ecjia配送方式
        if (substr($shipping['shipping_code'], 0, 5) == 'ship_') {
            $shipping['shipping_code'] = str_replace('ship_', '', $shipping['shipping_code']);
        }

        $shipping_name = Str::studly($shipping['shipping_code']);
        $shipping = '\\App\\Plugins\\Shipping\\' . $shipping_name . '\\' . $shipping_name;

        if (class_exists($shipping)) {
            $object = app($shipping, []);
            return $object;
        } else {
            return false;
        }
    }
}

/**
 * 改变订单中商品库存
 * @param int $order_id 订单号
 * @param bool $is_dec 是否减少库存
 * @param bool $storage 减库存的时机，2，付款时； 1，下订单时；0，发货时；
 */
function change_order_goods_storage($order_id, $is_dec = true, $storage = 0, $use_storage = 0, $admin_id = 0, $store_id = 0) //ecmoban模板堂 --zhuo
{
    $select = '';

    /* 查询订单商品信息 */
    switch ($storage) {
        case 0:
            $select = "goods_id, send_number AS num, extension_code, product_id, warehouse_id, area_id, area_city";
            break;

        case 1:
        case 2:
            $select = "goods_id, goods_number AS num, extension_code, product_id, warehouse_id, area_id, area_city";
            break;
    }

    $res = [];
    if ($select) {
        $res = OrderGoods::selectRaw($select)
            ->where('order_id', $order_id)
            ->where('is_real', 1);
        $res = app(BaseRepository::class)->getToArrayGet($res);
    }

    if ($res) {
        foreach ($res as $row) {
            if ($row['extension_code'] != "package_buy") {
                if ($is_dec) {
                    change_goods_storage($row['goods_id'], $row['product_id'], -$row['num'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $order_id, $use_storage, $admin_id, $store_id);
                } else {
                    change_goods_storage($row['goods_id'], $row['product_id'], $row['num'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $order_id, $use_storage, $admin_id, $store_id);
                }
            } else {
                $res_goods = PackageGoods::select('goods_id', 'goods_number')
                    ->where('package_id', $row['goods_id']);
                $res_goods = $res_goods->with('getGoods');
                $res_goods = app(BaseRepository::class)->getToArrayGet($res_goods);

                if ($res_goods) {
                    foreach ($res_goods as $row_goods) {
                        $is_goods = $row_goods['get_goods'] ? $row_goods['get_goods'] : [];

                        if ($is_dec) {
                            change_goods_storage($row_goods['goods_id'], $row['product_id'], -($row['num'] * $row_goods['goods_number']), $row['warehouse_id'], $row['area_id'], $row['area_city'], $order_id, $use_storage, $admin_id);
                        } elseif ($is_goods && $is_goods['is_real']) {
                            change_goods_storage($row_goods['goods_id'], $row['product_id'], ($row['num'] * $row_goods['goods_number']), $row['warehouse_id'], $row['area_id'], $row['area_city'], $order_id, $use_storage, $admin_id);
                        }
                    }
                }
            }
        }
    }
}

/**
 * 商品库存增与减 货品库存增与减
 *
 * @param int $goods_id 商品ID
 * @param int $product_id 货品ID
 * @param int $number 增减数量，默认0；
 *
 * @param int $store_id 门店ID
 * @return  bool                    true，成功；false，失败；
 */
function change_goods_storage($goods_id = 0, $product_id = 0, $number = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $order_id = 0, $use_storage = 0, $admin_id = 0, $store_id = 0) //ecmoban模板堂 --zhuo
{
    if ($number == 0) {
        return true; // 值为0即不做、增减操作，返回true
    }

    if (empty($goods_id) || empty($number)) {
        return false;
    }
    $number = ($number > 0) ? '+ ' . $number : $number;

    $goods = Goods::select('model_inventory', 'model_attr')
        ->where('goods_id', $goods_id);
    $goods = app(BaseRepository::class)->getToArrayFirst($goods);

    /* 秒杀活动扩展信息 */
    $extension_code = OrderGoods::where('order_id', $order_id)->value('extension_code');

    if ($extension_code && substr($extension_code, 0, 7) == 'seckill') {
        $is_seckill = true;
        $sec_id = substr($extension_code, 7);
    } else {
        $is_seckill = false;
    }

    /* 处理货品库存 */
    $abs_number = abs($number);
    if (!empty($product_id)) {
        //ecmoban模板堂 --zhuo start
        if (isset($store_id) && $store_id > 0) {
            $res = StoreProducts::where('store_id', $store_id);
        } else {
            if ($goods['model_attr'] == 1) {
                $res = ProductsWarehouse::whereRaw(1);
            } elseif ($goods['model_attr'] == 2) {
                $res = ProductsArea::whereRaw(1);
            } else {
                $res = Products::whereRaw(1);
            }
        }
        //ecmoban模板堂 --zhuo end
        if ($is_seckill) {
            $set_update = "IF(sec_num >= $abs_number, sec_num $number, 0)";
        } elseif ($number < 0) {
            $set_update = "IF(product_number >= $abs_number, product_number $number, 0)";
        } else {
            $set_update = "product_number $number";
        }
        if ($is_seckill) {
            $other = [
                'sec_num' => DB::raw($set_update)
            ];
            SeckillGoods::where('id', $sec_id)->update($other);
        } else {
            $other = [
                'product_number' => DB::raw($set_update)
            ];
            $res->where('goods_id', $goods_id)
                ->where('product_id', $product_id)
                ->update($other);
        }
    } else {
        if ($number < 0) {
            if ($store_id > 0) {
                $set_update = "IF(goods_number >= $abs_number, goods_number $number, 0)";
            } else {
                if ($is_seckill) {
                    $set_update = "IF(sec_num >= $abs_number, sec_num $number, 0)";
                } else {
                    if ($goods['model_inventory'] == 1 || $goods['model_inventory'] == 2) {
                        $set_update = "IF(region_number >= $abs_number, region_number $number, 0)";
                    } else {
                        $set_update = "IF(goods_number >= $abs_number, goods_number $number, 0)";
                    }
                }
            }
        } else {
            if ($store_id > 0) {
                $set_update = "goods_number $number";
            } elseif ($is_seckill) {
                $set_update = " sec_num $number ";
            } else {
                if ($goods['model_inventory'] == 1 || $goods['model_inventory'] == 2) {
                    $set_update = "region_number $number";
                } else {
                    $set_update = "goods_number $number";
                }
            }
        }

        /* 处理商品库存 */ //ecmoban模板堂 --zhuo
        if ($store_id > 0) {
            $other = [
                'goods_number' => DB::raw($set_update)
            ];
            StoreGoods::where('goods_id', $goods_id)
                ->where('store_id', $store_id)
                ->update($other);
        } else {
            if ($goods['model_inventory'] == 1 && !$is_seckill) {
                $other = [
                    'region_number' => DB::raw($set_update)
                ];
                WarehouseGoods::where('goods_id', $goods_id)
                    ->where('region_id', $warehouse_id)
                    ->update($other);
            } elseif ($goods['model_inventory'] == 2 && !$is_seckill) {
                $other = [
                    'region_number' => DB::raw($set_update)
                ];
                $update = WarehouseAreaGoods::where('goods_id', $goods_id)
                    ->where('region_id', $area_id);

                if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                    $update = $update->where('city_id', $area_city);
                }

                $update->update($other);
            } else {
                if ($is_seckill) {
                    $other = [
                        'sec_num' => DB::raw($set_update)
                    ];
                    SeckillGoods::where('id', $sec_id)
                        ->update($other);
                } else {
                    $other = [
                        'goods_number' => DB::raw($set_update)
                    ];
                    Goods::where('goods_id', $goods_id)
                        ->update($other);
                }
            }
        }
    }

    //库存日志
    $logs_other = [
        'goods_id' => $goods_id,
        'order_id' => $order_id,
        'use_storage' => $use_storage,
        'admin_id' => $admin_id,
        'number' => $number,
        'model_inventory' => $goods['model_inventory'],
        'model_attr' => $goods['model_attr'],
        'product_id' => $product_id,
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'add_time' => gmtime()
    ];

    GoodsInventoryLogs::insert($logs_other);
    return true;
}

/**
 * 生成查询订单的sql
 * @param string $type 类型
 * @param string $alias order表的别名（包括.例如 o.）
 * @return  string
 */
function order_take_query_sql($type = 'finished', $alias = '')
{
    /* 已完成订单 */
    if ($type == 'finished') {
        return " AND {$alias}order_status " . db_create_in([OS_SPLITED]) .
            " AND {$alias}shipping_status " . db_create_in([SS_RECEIVED]) .
            " AND {$alias}pay_status " . db_create_in([PS_PAYED]) . " ";
    } else {
        return '函数 order_query_sql 参数错误';
    }
}

/**
 * 生成查询佣金总金额的字段
 * @param string $alias order表的别名（包括.例如 o.）
 * @return  string
 *  + {$alias}shipping_fee  不含运费
 */
function order_commission_field($alias = '', $ru_id = 0)
{
    return "   {$alias}goods_amount + {$alias}tax" .
        " + {$alias}insure_fee + {$alias}pay_fee + {$alias}pack_fee" .
        " + {$alias}card_fee -{$alias}discount -{$alias}coupons - {$alias}integral_money - {$alias}bonus ";
}

/**
 * 生成计算应付款金额的字段
 * @param string $alias order表的别名（包括.例如 o.）
 * @return  string
 */
function order_due_field($alias = '')
{
    return app(OrderService::class)->orderAmountField($alias) .
        " - {$alias}money_paid - {$alias}surplus - {$alias}integral_money" .
        " - {$alias}bonus - {$alias}coupons - {$alias}discount ";
}

/**
 * 生成计算应付款金额的字段
 * @param string $alias order表的别名（包括.例如 o.）
 * @return  string
 */
function order_activity_field_add($alias = '')
{
    return " {$alias}discount + {$alias}coupons + {$alias}integral_money + {$alias}bonus ";
}

/**
 * 计算折扣：根据购物车和优惠活动
 * @return  float   折扣
 * $type 0-默认 1-分单
 * $use_type 购物流程显示 0， 分单使用 1
 */
function compute_discount($type = 0, $newInfo = [], $use_type = 0, $ru_id = 0, $user_id = 0, $user_rank = 0, $rec_type = CART_GENERAL_GOODS)
{
    if (empty($user_id)) {
        $user_id = session('user_id', 0);
        $session_id = app(SessionRepository::class)->realCartMacIp();
    }

    $CategoryLib = app(CategoryService::class);
    if (empty($user_rank)) {
        $user_rank = session('user_rank', 1);
    }

    /* 查询优惠活动 */
    $now = gmtime();
    $user_rank = ',' . $user_rank . ',';
    $favourable_list = FavourableActivity::where('review_status', 3)
        ->where('start_time', '<=', $now)
        ->where('end_time', '>=', $now)
        ->whereIn('act_type', [FAT_DISCOUNT, FAT_PRICE]);

    $favourable_list = $favourable_list->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");

    $favourable_list = app(BaseRepository::class)->getToArrayGet($favourable_list);

    if (!$favourable_list) {
        return ['discount' => 0, 'name' => ''];
    }

    if ($type == 0 || $type == 3) {

        /* 查询购物车商品 */
        $goods_list = Cart::selectRaw("goods_id, goods_price * goods_number AS subtotal, ru_id, act_id")
            ->where('parent_id', 0)
            ->where('is_gift', 0)
            ->where('rec_type', $rec_type);

        if (!empty($user_id)) {
            $goods_list = $goods_list->where('user_id', $user_id);
        } else {
            $goods_list = $goods_list->where('session_id', $session_id);
        }

        if ($type == 3) {
            $goods_list = $goods_list->where('is_checked', 1);
        }

        $goods_list = $goods_list->whereHas('getGoods');

        $goods_list = $goods_list->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'cat_id', 'brand_id');
            }
        ]);

        $goods_list = app(BaseRepository::class)->getToArrayGet($goods_list);

        if ($goods_list) {
            foreach ($goods_list as $key => $row) {
                $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;

                $goods_list[$key] = $row;
            }
        }
    } elseif ($type == 2) {
        $goods_list = [];

        $newInfo = app(BaseRepository::class)->getExplode($newInfo);

        if ($newInfo) {
            foreach ($newInfo as $key => $row) {
                $order_goods = Goods::select('cat_id', 'brand_id')->where('goods_id', $row['goods_id']);
                $order_goods = app(BaseRepository::class)->getToArrayFirst($order_goods);

                $goods_list[$key]['goods_id'] = $row['goods_id'];

                if ($order_goods) {
                    $goods_list[$key]['cat_id'] = $order_goods['cat_id'];
                    $goods_list[$key]['brand_id'] = $order_goods['brand_id'];
                }

                $goods_list[$key]['ru_id'] = $row['ru_id'];
                $goods_list[$key]['subtotal'] = $row['goods_price'] * $row['goods_number'];
            }
        }
    }

    if (!$goods_list) {
        return ['discount' => 0, 'name' => ''];
    }

    /* 初始化折扣 */
    $discount = 0;
    $favourable_name = [];
    $list_array = [];

    /* 循环计算每个优惠活动的折扣 */
    foreach ($favourable_list as $favourable) {
        $total_amount = 0;
        if ($favourable['act_range'] == FAR_ALL) {
            $rs_label = true;
            $mer_ids = [];
            if ($GLOBALS['_CFG']['region_store_enabled']) {
                //卖场促销 liu
                $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id'], 1);
                $rs_label = false;

                if ($mer_ids) {
                    foreach ($goods_list as $goods) {
                        // 当为立即购买时，自动关联当前促销活动
                        if ($rec_type == CART_ONESTEP_GOODS) {
                            $goods['act_id'] = $favourable['act_id'];
                        }
                        if (isset($goods['ru_id']) && (in_array($goods['ru_id'], $mer_ids) || $rs_label)) {
                            if ($use_type == 1) {
                                if ($favourable['user_id'] == $goods['ru_id']) {
                                    $total_amount += $goods['subtotal'];
                                }
                            } else {
                                if ($favourable['userFav_type'] == 1) {
                                    $total_amount += $goods['subtotal'];
                                } else {
                                    if (($favourable['user_id'] == $goods['ru_id'] && $rs_label) || in_array($goods['ru_id'], $mer_ids)) {
                                        $total_amount += $goods['subtotal'];
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                foreach ($goods_list as $goods) {
                    // 当为立即购买时，自动关联当前促销活动
                    if ($rec_type == CART_ONESTEP_GOODS) {
                        $goods['act_id'] = $favourable['act_id'];
                    }
                    if (isset($goods['act_id'])) {
                        if ($goods['act_id'] == $favourable['act_id']) {//购物车匹配促销活动
                            if ($use_type == 1) {
                                if ($favourable['user_id'] == $goods['ru_id']) {
                                    $total_amount += $goods['subtotal'];
                                }
                            } else {
                                if ($favourable['userFav_type'] == 1) {
                                    $total_amount += $goods['subtotal'];
                                } else {
                                    if ($favourable['user_id'] == $goods['ru_id']) {
                                        $total_amount += $goods['subtotal'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($favourable['act_range'] == FAR_CATEGORY) {
            /* 找出分类id的子分类id */
            $id_list = [];
            $raw_id_list = explode(',', $favourable['act_range_ext']);

            $str_cat = '';
            foreach ($raw_id_list as $id) {
                /**
                 * 当前分类下的所有子分类
                 * 返回一维数组
                 */
                $cat_keys = $CategoryLib->getCatListChildren(intval($id));

                if ($cat_keys) {
                    $str_cat .= implode(",", $cat_keys);
                }
            }

            if ($str_cat) {
                $list_array = explode(",", $str_cat);
            }

            $list_array = !empty($list_array) ? array_merge($raw_id_list, $list_array) : $raw_id_list;
            $id_list = arr_foreach($list_array);
            $id_list = array_unique($id_list);

            $ids = join(',', array_unique($id_list));

            foreach ($goods_list as $goods) {
                // 当为立即购买时，自动关联当前促销活动
                if ($rec_type == CART_ONESTEP_GOODS) {
                    $goods['act_id'] = $favourable['act_id'];
                }
                //购物车匹配促销活动
                if (isset($goods['act_id']) && $goods['act_id'] == $favourable['act_id']) {
                    if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
                        if ($use_type == 1) {
                            if ($favourable['user_id'] == $goods['ru_id'] && $favourable['userFav_type'] == 0) {
                                $total_amount += $goods['subtotal'];
                            }
                        } else {
                            if ($favourable['userFav_type'] == 1) {
                                $total_amount += $goods['subtotal'];
                            } else {
                                if ($favourable['user_id'] == $goods['ru_id']) {
                                    $total_amount += $goods['subtotal'];
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($favourable['act_range'] == FAR_BRAND) {
            $favourable['act_range_ext'] = act_range_ext_brand($favourable['act_range_ext'], $favourable['userFav_type'], $favourable['act_range']);
            foreach ($goods_list as $goods) {
                // 当为立即购买时，自动关联当前促销活动
                if ($rec_type == CART_ONESTEP_GOODS) {
                    $goods['act_id'] = $favourable['act_id'];
                }
                //购物车匹配促销活动
                if (isset($goods['act_id']) && $goods['act_id'] == $favourable['act_id']) {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
                        if ($use_type == 1) {
                            if ($favourable['user_id'] == $goods['ru_id']) {
                                $total_amount += $goods['subtotal'];
                            }
                        } else {
                            if ($favourable['userFav_type'] == 1) {
                                $total_amount += $goods['subtotal'];
                            } else {
                                if ($favourable['user_id'] == $goods['ru_id']) {
                                    $total_amount += $goods['subtotal'];
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($favourable['act_range'] == FAR_GOODS) {
            if ($GLOBALS['_CFG']['region_store_enabled']) {
                //卖场促销 liu
                $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id']);
                if ($mer_ids && $favourable['userFav_type'] != 1) {
                    $res = [];
                    if ($favourable['act_range_ext']) {
                        $act_range_ext = !is_array($favourable['act_range_ext']) ? explode(",", $favourable['act_range_ext']) : $favourable['act_range_ext'];
                        $mer_ids = !is_array($mer_ids) ? explode(",", $mer_ids) : $mer_ids;

                        $res = Goods::selectRaw("GROUP_CONCAT(goods_id) AS goods_id")
                            ->whereIn('user_id', $mer_ids)->whereIn('goods_id', $act_range_ext);
                        $res = app(BaseRepository::class)->getToArrayFirst($res);

                        $res = $res ? $res['goods_id'] : [];
                    }

                    if ($res) {
                        $favourable['act_range_ext'] = $res;
                    } else {
                        $favourable['act_range_ext'] = '';
                    }
                }
            }

            foreach ($goods_list as $goods) {
                // 当为立即购买时，自动关联当前促销活动
                if ($rec_type == CART_ONESTEP_GOODS) {
                    $goods['act_id'] = $favourable['act_id'];
                }
                //购物车匹配促销活动
                if (isset($goods['act_id']) && isset($favourable['act_id']) && $goods['act_id'] == $favourable['act_id']) {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
                        if ($use_type == 1) {
                            if ($favourable['user_id'] == $goods['ru_id']) {
                                $total_amount += $goods['subtotal'];
                            }
                        } else {
                            if ($favourable['userFav_type'] == 1) {
                                $total_amount += $goods['subtotal'];
                            } else {
                                if ($favourable['user_id'] == $goods['ru_id']) {
                                    $total_amount += $goods['subtotal'];
                                }
                            }
                        }
                    }
                }
            }
        } else {
            continue;
        }

        /* 如果金额满足条件，累计折扣 */
        if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
            if ($favourable['act_type'] == FAT_DISCOUNT) {
                $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);

                $favourable_name[] = $favourable['act_name'];
            } elseif ($favourable['act_type'] == FAT_PRICE) {
                $discount += $favourable['act_type_ext'];
                $favourable_name[] = $favourable['act_name'];
            }

            if ($rec_type == CART_ONESTEP_GOODS) {
                return ['discount' => $discount, 'name' => $favourable_name, 'favourable' => $favourable];
            }
        }
    }

    return ['discount' => $discount, 'name' => $favourable_name];
}

/**
 * 取得购物车该赠送的积分数
 * @return  int     积分数
 */
function get_give_integral($cart_value, $user_id = 0)
{
    $res = Cart::select('goods_id', 'goods_number', 'goods_price', 'warehouse_id', 'area_id', 'area_city')
        ->where('goods_id', '>', 0)
        ->where('parent_id', 0)
        ->where('rec_type', 0)
        ->where('is_gift', 0);

    if (!empty($user_id)) {
        $res = $res->where('user_id', $user_id);
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $res = $res->where('session_id', $session_id);
    }

    if (!empty($cart_value)) {
        $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;
        $res = $res->whereIn('rec_id', $cart_value);
    }

    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'model_price', 'give_integral');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $price = 0;
    if ($res) {
        foreach ($res as $key => $row) {

            $warehouse_integral = WarehouseGoods::where('goods_id', $row['goods_id'])
                ->where('region_id', $row['warehouse_id'])
                ->value('give_integral');
            $warehouse_integral = $warehouse_integral ? $warehouse_integral : 0;

            $area_integral = WarehouseAreaGoods::where('goods_id', $row['goods_id'])
                ->where('region_id', $row['area_id']);

            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $area_integral = $area_integral->where('city_id', $row['area_city']);
            }

            $area_integral = $area_integral->value('give_integral');

            $area_integral = $area_integral ? $area_integral : 0;

            $model_price = $row['get_goods'] ? $row['get_goods']['model_price'] : 0;
            $goods_integral = $row['get_goods'] ? $row['get_goods']['give_integral'] : 0;

            if ($model_price == 1) {
                $integral = $warehouse_integral;
            } elseif ($model_price == 2) {
                $integral = $area_integral;
            } else {
                $integral = $goods_integral;
            }

            if ($integral <= -1) {
                $integral = $row['goods_price'];
            }

            $price += $integral;
        }
    }

    return $price;
}

/**
 * 取得某订单应该赠送的积分数
 * @param array $order 订单
 * @return  int     积分数
 */
function integral_to_give($order, $rec_id = 0)
{

    /* 判断是否团购 */
    if ($order['extension_code'] == 'group_buy') {
        $GroupBuyLib = app(GroupBuyService::class);

        $where = [
            'group_buy_id' => intval($order['extension_id']),
        ];
        $group_buy = $GroupBuyLib->getGroupBuyInfo($where);

        return ['custom_points' => $group_buy['gift_integral'], 'rank_points' => $order['goods_amount']];
    } else {
        $res = OrderGoods::where('order_id', $order['order_id'])
            ->where('goods_id', '>', 0)
            ->where('parent_id', 0)
            ->where('is_gift', 0)
            ->where('extension_code', '<>', 'package_buy');

        if ($rec_id > 0) {
            $res = $res->where('rec_id', $rec_id);
        }

        $res = $res->with(['getGoods']);

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $custom_points = 0;
        $rank_points = 0;
        if ($res) {
            foreach ($res as $key => $row) {
                $goods = $row['get_goods'];

                if ($row['ru_id'] > 0) {
                    $grade = MerchantsGrade::whereHas("getSellerGrade")
                        ->where('ru_id', $row['ru_id'])
                        ->with([
                            'getSellerGrade'
                        ]);

                    $grade = app(BaseRepository::class)->getToArrayFirst($grade);

                    $give = $grade && $grade['get_seller_grade'] ? $grade['get_seller_grade']['give_integral'] / 100 : 0;
                    $rank = $grade && $grade['get_seller_grade'] ? $grade['get_seller_grade']['rank_integral'] / 100 : 0;
                } else {
                    $give = 1;
                    $rank = 1;
                }

                $give_integral = 0;
                $rank_integral = 0;
                if ($goods) {
                    if ($goods['model_price'] == 1) {
                        $res = WarehouseGoods::where('goods_id', $row['goods_id'])->where('region_id', $row['warehouse_id']);
                        $res = app(BaseRepository::class)->getToArrayFirst($res);

                        $give_integral = $res ? $res['give_integral'] : 0;
                        $rank_integral = $res ? $res['rank_integral'] : 0;
                    } elseif ($goods['model_price'] == 2) {
                        $res = WarehouseAreaGoods::where('goods_id', $row['goods_id'])->where('region_id', $row['area_id']);
                        $res = app(BaseRepository::class)->getToArrayFirst($res);

                        $give_integral = $res ? $res['give_integral'] : 0;
                        $rank_integral = $res ? $res['rank_integral'] : 0;
                    } else {
                        $give_integral = $goods['give_integral'];
                        $rank_integral = $goods['rank_integral'];
                    }
                }

                if ($give_integral > 0) {
                    $row['custom_points'] = $row['goods_number'] * $give_integral;
                } elseif ($give_integral == -1) {
                    $row['custom_points'] = $row['goods_number'] * ($row['goods_price'] * $give);
                }

                if ($rank_integral > 0) {
                    $row['rank_points'] = $row['goods_number'] * $rank_integral;
                } elseif ($rank_integral == -1) {
                    $row['rank_points'] = $row['goods_number'] * ($row['goods_price'] * $rank);
                }

                $custom_points += $row['custom_points'] ?? 0;
                $rank_points += $row['rank_points'] ?? 0;
            }
        }

        $custom_points = $custom_points ? intval($custom_points) : 0;
        $rank_points = $rank_points ? intval($rank_points) : 0;

        $arr = [
            'custom_points' => $custom_points,
            'rank_points' => $rank_points
        ];

        return $arr;
    }
}

/**
 * 发红包：发货时发红包
 * @param int $order_id 订单号
 * @return  bool
 */
function send_order_bonus($order_id)
{
    /* 取得订单应该发放的红包 */
    $bonus_list = order_bonus($order_id);
    /* 如果有红包，统计并发送 */
    if ($bonus_list) {
        /* 用户信息 */
        $user_id = OrderInfo::where('order_id', $order_id)->value('user_id');
        $user_id = $user_id ? $user_id : 0;

        $user = [];
        if ($user_id) {
            $user = Users::select('user_id', 'user_name', 'email')->where('user_id', $user_id);
            $user = app(BaseRepository::class)->getToArrayFirst($user);
        }

        /* 统计 */
        $count = 0;
        $money = '';
        foreach ($bonus_list as $bonus) {
            //优化一个订单只能发一个红包
            if ($bonus['number']) {
                $count = 1;
                $bonus['number'] = 1;
            }
            $money .= strip_tags(price_format($bonus['type_money'])) . ', ';


            $bonus_info = BonusType::where('type_id', $bonus['type_id']);
            $bonus_info = app(BaseRepository::class)->getToArrayFirst($bonus_info);

            if (empty($bonus_info)) {
                $bonus_info = [
                    'date_type' => 0,
                    'valid_period' => 0,
                    'use_start_date' => '',
                    'use_end_date' => '',
                ];
            }

            /* 修改用户红包 */
            $other = [
                'bonus_type_id' => $bonus['type_id'],
                'user_id' => $user_id,
                'bind_time' => gmtime(),
                'date_type' => $bonus_info['date_type'],
            ];
            if ($bonus_info['valid_period'] > 0) {
                $other['start_time'] = $other['bind_time'];
                $other['end_time'] = $other['bind_time'] + $bonus_info['valid_period'] * 3600 * 24;
            } else {
                $other['start_time'] = $bonus_info['use_start_date'];
                $other['end_time'] = $bonus_info['use_end_date'];
            }

            $bonus_id = 0;
            if ($user_id > 0) {
                $bonus_id = UserBonus::insertGetId($other);
            }

            for ($i = 0; $i < $bonus['number']; $i++) {
                if (!$bonus_id) {
                    return $GLOBALS['db']->errorMsg();
                }
            }
        }

        /* 如果有红包，发送邮件 */
        if ($count > 0) {

            $user_name = $user['user_name'] ?? '';
            $email = $user['email'] ?? '';

            $tpl = get_mail_template('send_bonus');
            $GLOBALS['smarty']->assign('user_name', $user_name);
            $GLOBALS['smarty']->assign('count', $count);
            $GLOBALS['smarty']->assign('money', $money);
            $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
            $GLOBALS['smarty']->assign('send_date', local_date($GLOBALS['_CFG']['date_format']));
            $GLOBALS['smarty']->assign('sent_date', local_date($GLOBALS['_CFG']['date_format']));
            $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
            app(CommonRepository::class)->sendEmail($user_name, $email, $tpl['template_subject'], $content, $tpl['is_html']);
        }
    }

    return true;
}

/**
 * [优惠券发放 (发货的时候)]达到条件的的订单,反购物券
 *
 * @param $order_id
 */
function send_order_coupons($order_id)
{
    $order = order_info($order_id);

    //获优惠券信息
    $coupons_buy_info = app(CouponsService::class)->getCouponsTypeInfoNoPage(2);

    //获取会员等级id
//    $user_rank = get_one_user_rank($order['user_id']);//deprecate 1.4.3
    $user_rank_info = app(UserRankService::class)->getUserRankInfo($order['user_id']);
    $user_rank = $user_rank_info['user_rank'] ?? 0;

    if ($coupons_buy_info) {
        foreach ($coupons_buy_info as $k => $v) {

            //判断当前会员等级能不能领取
            $cou_ok_user = !empty($v['cou_ok_user']) ? explode(",", $v['cou_ok_user']) : '';

            if ($cou_ok_user) {
                if (!in_array($user_rank, $cou_ok_user)) {
                    continue;
                }
            } else {
                continue;
            }

            //获取当前的注册券已被发放的数量(防止发放数量超过设定发放数量)
            $num = CouponsUser::where('cou_id', $v['cou_id'])->count();
            if ($v['cou_total'] <= $num) {
                continue;
            }

            //当前用户已经领取的数量,超过允许领取的数量则不再返券
            $cou_user_num = CouponsUser::where('user_id', $order['user_id'])
                ->where('cou_id', $v['cou_id'])
                ->where('is_use', 0)
                ->count();

            if ($cou_user_num < $v['cou_user_num']) {

                //获取订单商品详情
                $order_id = $order['order_id'];
                $goods = Goods::selectRaw("GROUP_CONCAT(goods_id) AS goods_id, GROUP_CONCAT(cat_id) AS cat_id")->whereHas('getOrderGoods', function ($query) use ($order_id) {
                    $query->where('order_id', $order_id);
                });

                $goods = app(BaseRepository::class)->getToArrayFirst($goods);

                $goods_ids = !empty($goods['goods_id']) ? array_unique(explode(",", $goods['goods_id'])) : [];
                $goods_cats = !empty($goods['cat_id']) ? array_unique(explode(",", $goods['cat_id'])) : [];
                $flag = false;

                //返券的金额门槛满足
                if ($order['goods_amount'] >= $v['cou_get_man']) {
                    if ($v['cou_ok_goods']) {
                        $cou_ok_goods = explode(",", $v['cou_ok_goods']);

                        if ($goods_ids) {
                            foreach ($goods_ids as $m => $n) {
                                //商品门槛满足(如果当前订单有多件商品,只要有一件商品满足条件,那么当前订单即反当前券)
                                if (in_array($n, $cou_ok_goods)) {
                                    $flag = true;
                                    break;
                                }
                            }
                        }
                    } elseif ($v['cou_ok_cat']) {
                        $cou_ok_cat = app(CouponsService::class)->getCouChildren($v['cou_ok_cat']);
                        $cou_ok_cat = explode(",", $cou_ok_cat);

                        if ($goods_cats) {
                            foreach ($goods_cats as $m => $n) {
                                //商品门槛满足(如果当前订单有多件商品 ,只要有一件商品的分类满足条件,那么当前订单即反当前券)
                                if (in_array($n, $cou_ok_cat)) {
                                    $flag = true;
                                    break;
                                }
                            }
                        }
                    } else {
                        $flag = true;
                    }

                    //返券
                    if ($flag) {
                        $other = [
                            'user_id' => $order['user_id'],
                            'cou_id' => $v['cou_id'],
                            'cou_money' => $v['cou_money'],
                            'uc_sn' => $v['uc_sn']
                        ];
                        CouponsUser::insert($other);
                    }
                }
            }
        }
    }
}

/**
 * 根据用户ID获取用户等级 bylu
 * @param $user_id 用户ID
 * @return bool
 * @deprecated 1.4.3
 */
function get_one_user_rank($user_id)
{

}

/**
 * 返回订单发放的红包
 * @param int $order_id 订单id
 */
function return_order_bonus($order_id)
{
    /* 取得订单应该发放的红包 */
    $bonus_list = order_bonus($order_id);

    /* 删除 */
    if ($bonus_list) {

        /* 取得订单信息 */
        $user_id = OrderInfo::where('order_id', $order_id)->value('user_id');

        foreach ($bonus_list as $bonus) {
            UserBonus::where('bonus_type_id', $bonus['type_id'])
                ->where('user_id', $user_id)
                ->where('order_id', 0)
                ->take($bonus['number'])
                ->delete();
        }
    }
}

/**
 * 取得订单应该发放的红包
 * @param int $order_id 订单id
 * @return  array
 */
function order_bonus($order_id)
{
    /* 查询按商品发的红包 */
    $day = getdate();
    $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

    $where = [
        'send_type' => SEND_BY_GOODS,
        'today' => $today
    ];
    $list = OrderGoods::selectRaw("goods_id, SUM(goods_number) AS number")
        ->where('order_id', $order_id)
        ->whereHas('getGoods', function ($query) use ($where) {
            $query->whereHas('getBonusType', function ($query) use ($where) {
                $query->where('send_type', $where['send_type'])
                    ->where('send_start_date', '<=', $where['today'])
                    ->where('send_end_date', '>=', $where['today']);
            });
        });

    $list = $list->with([
        'getGoods' => function ($query) {
            $query->bonusTypeInfo();
        }
    ]);

    $list = app(BaseRepository::class)->getToArrayGet($list);
    if ($list) {
        foreach ($list as $key => $row) {
            $list[$key]['number'] = $row['number'];
            $list[$key]['type_id'] = isset($row['get_goods']['get_bonus_type']) && $row['get_goods']['get_bonus_type'] ? $row['get_goods']['get_bonus_type']['type_id'] : 0;
            $list[$key]['type_money'] = isset($row['get_goods']['get_bonus_type']) && $row['get_goods']['get_bonus_type'] ? $row['get_goods']['get_bonus_type']['type_money'] : 0;
        }
    }
    /* 查询定单中非赠品总金额 */
    $amount = order_amount($order_id, false);

    /* 查询订单日期 */
    $order = OrderInfo::select('order_id', 'add_time')
        ->where('order_id', $order_id);

    $order = $order->with([
        'getOrderGoods' => function ($query) {
            $query->select('order_id', 'ru_id');
        }
    ]);

    $order = app(BaseRepository::class)->getToArrayFirst($order);

    $order_time = $order ? $order['add_time'] : '';
    $ru_id = $order && $order['get_order_goods'] ? $order['get_order_goods']['ru_id'] : 0;

    /* 查询按订单发的红包 */
    $bonus = BonusType::select('type_id', 'type_name', 'type_money')
        ->selectRaw("IFNULL(FLOOR('$amount' / min_amount), 1) AS number")
        ->where('send_type', SEND_BY_ORDER)
        ->where('send_start_date', '<=', $order_time)
        ->where('send_end_date', '>=', $order_time)
        ->where('user_id', $ru_id);

    $bonus = app(BaseRepository::class)->getToArrayGet($bonus);
    $list = app(BaseRepository::class)->getArrayMerge($list, $bonus);

    return $list;
}

/**
 * 计算购物车中的商品能享受红包支付的总额
 * @return  float   享受红包支付的总额
 */
function compute_discount_amount($cart_value = '', $user_id = 0, $rec_type = CART_GENERAL_GOODS)
{
    if (empty($user_id)) {
        $user_id = session('user_id', 0);
    }

    $CategoryLib = app(CategoryService::class);

    // 会员等级
    $user_rank = app(UserCommonService::class)->getUserRankByUid($user_id);
    $user_rank['rank_id'] = $user_rank['rank_id'] ?? 0;
    /* 查询优惠活动 */
    $now = gmtime();
    $user_rank = ',' . $user_rank['rank_id'] . ',';

    $favourable_list = FavourableActivity::where('review_status', 3)
        ->where('start_time', '<=', $now)
        ->where('end_time', '>=', $now)
        ->whereIn('act_type', [FAT_DISCOUNT, FAT_PRICE]);

    $favourable_list = $favourable_list->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");

    $favourable_list = app(BaseRepository::class)->getToArrayGet($favourable_list);

    if (!$favourable_list) {
        return 0;
    }

    /* 查询购物车商品 */
    $goods_list = Cart::selectRaw("goods_id, goods_price * goods_number AS subtotal, ru_id")
        ->where('parent_id', 0)
        ->where('is_gift', 0)
        ->where('rec_type', $rec_type);

    if (!empty($user_id)) {
        $goods_list = $goods_list->where('user_id', $user_id);
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $goods_list = $goods_list->where('session_id', $session_id);
    }

    if (!empty($cart_value)) {
        $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;
        $goods_list = $goods_list->whereIn('rec_id', $cart_value);
    }

    $goods_list = $goods_list->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'cat_id', 'brand_id');
        }
    ]);

    $goods_list = app(BaseRepository::class)->getToArrayGet($goods_list);

    if (!$goods_list) {
        return 0;
    } else {
        foreach ($goods_list as $k => $v) {
            $goods_list[$k] = collect($v)->merge($v['get_goods'])->except('get_goods')->all();
        }
    }

    /* 初始化折扣 */
    $discount = 0;
    $favourable_name = [];

    /* 循环计算每个优惠活动的折扣 */
    foreach ($favourable_list as $favourable) {
        $total_amount = 0;
        if ($favourable['act_range'] == FAR_ALL) {
            foreach ($goods_list as $goods) {
                //ecmoban模板堂 --zhuo start
                if ($favourable['userFav_type'] == 1) {
                    $total_amount += $goods['subtotal'];
                } else {
                    if ($favourable['user_id'] == $goods['ru_id']) {
                        $total_amount += $goods['subtotal'];
                    }
                }
                //ecmoban模板堂 --zhuo end
            }
        } elseif ($favourable['act_range'] == FAR_CATEGORY) {
            /* 找出分类id的子分类id */
            $id_list = [];
            $raw_id_list = explode(',', $favourable['act_range_ext']);
            foreach ($raw_id_list as $id) {
                /**
                 * 当前分类下的所有子分类
                 * 返回一维数组
                 */
                $cat_keys = $CategoryLib->getCatListChildren(intval($id));

                $id_list = array_merge($id_list, $cat_keys);
            }
            $ids = join(',', array_unique($id_list));

            foreach ($goods_list as $goods) {
                if (isset($goods['cat_id']) && strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
                    //ecmoban模板堂 --zhuo start
                    if ($favourable['userFav_type'] == 1) {
                        $total_amount += $goods['subtotal'];
                    } else {
                        if ($favourable['user_id'] == $goods['ru_id']) {
                            $total_amount += $goods['subtotal'];
                        }
                    }
                    //ecmoban模板堂 --zhuo end
                }
            }
        } elseif ($favourable['act_range'] == FAR_BRAND) {
            $favourable['act_range_ext'] = act_range_ext_brand($favourable['act_range_ext'], $favourable['userFav_type'], $favourable['act_range']);
            foreach ($goods_list as $goods) {
                if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . ($goods['brand_id'] ?? 0) . ',') !== false) {

                    //ecmoban模板堂 --zhuo start
                    if ($favourable['userFav_type'] == 1) {
                        $total_amount += $goods['subtotal'];
                    } else {
                        if ($favourable['user_id'] == $goods['ru_id']) {
                            $total_amount += $goods['subtotal'];
                        }
                    }
                    //ecmoban模板堂 --zhuo end
                }
            }
        } elseif ($favourable['act_range'] == FAR_GOODS) {
            foreach ($goods_list as $goods) {
                if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
                    //ecmoban模板堂 --zhuo start
                    if ($favourable['userFav_type'] == 1) {
                        $total_amount += $goods['subtotal'];
                    } else {
                        if ($favourable['user_id'] == $goods['ru_id']) {
                            $total_amount += $goods['subtotal'];
                        }
                    }
                    //ecmoban模板堂 --zhuo end
                }
            }
        } else {
            continue;
        }
        if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
            if ($favourable['act_type'] == FAT_DISCOUNT) {
                $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
            } elseif ($favourable['act_type'] == FAT_PRICE) {
                $discount += $favourable['act_type_ext'];
            }
        }
    }


    return $discount;
}

/**
 * 添加礼包到购物车
 *
 * @access  public
 * @param integer $package_id 礼包编号
 * @param integer $num 礼包数量
 * @return  boolean
 */
function add_package_to_cart($package_id, $num = 1, $warehouse_id = 0, $area_id = 0, $area_city = 0, $type = 0)
{
    $user_id = session('user_id', 0);
    $session_id = app(SessionRepository::class)->realCartMacIp();

    $GLOBALS['err']->clean();

    $goods_number = $num;
    if ($type == 0) {
        $goods_number = Cart::where('goods_id', $package_id)
            ->where('extension_code', 'package_buy')
            ->value('goods_number');
        $goods_number = $goods_number ? $goods_number : 0;

        $goods_number = $goods_number + $num;
    }

    /* 取得礼包信息 */
    $package = get_package_info($package_id, $warehouse_id, $area_id, $area_city);

    $is_fail = 0;
    foreach ($package['goods_list'] as $key => $val) {
        if (!$val['stock_number'] || $goods_number * $val['goods_number'] > $val['stock_number']) {
            $is_fail = 2;
            $goods_name = $val['goods_name'];
            break;
        } else {
            if ($num * $val['goods_number'] > $val['stock_number']) {
                $is_fail = 3;
                $goods_name = $val['goods_name'];
                break;
            }
        }
    }

    if ($is_fail) {
        $arr = [
            'error' => $is_fail,
            'goods_name' => $goods_name,
        ];
        return $arr;
    } else {
        if (empty($package)) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);

            return false;
        }

        /* 是否正在销售 */
        if ($package['is_on_sale'] == 0) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['not_on_sale'], ERR_NOT_ON_SALE);

            return false;
        }

        /* 现有库存是否还能凑齐一个礼包 */
        if ($GLOBALS['_CFG']['use_storage'] == '1' && app(PackageGoodsService::class)->judgePackageStock($package_id, $num)) {
            $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['package_nonumer'], 1), ERR_OUT_OF_STOCK);

            return false;
        }

        $sess = empty($user_id) ? $session_id : '';

        /* 初始化要插入购物车的基本件数据 */
        $parent = [
            'user_id' => session('user_id', 0),
            'session_id' => $sess,
            'goods_id' => $package_id,
            'goods_sn' => '',
            'goods_name' => addslashes($package['package_name']),
            'market_price' => $package['market_package'],
            'goods_price' => $package['package_price'],
            'goods_number' => $num,
            'goods_attr' => '',
            'goods_attr_id' => '',
            'warehouse_id' => $warehouse_id, //ecmoban模板堂 --zhuo 仓库
            'area_id' => $area_id, //ecmoban模板堂 --zhuo 仓库地区
            'ru_id' => $package['user_id'],
            'is_real' => $package['is_real'],
            'extension_code' => 'package_buy',
            'is_gift' => 0,
            'rec_type' => CART_GENERAL_GOODS,
            'add_time' => gmtime()
        ];

        /* 如果数量不为0，作为基本件插入 */
        if ($num > 0) {
            /* 检查该商品是否已经存在在购物车中 */
            $row = Cart::select('goods_number')
                ->where('parent_id', 0)
                ->where('goods_id', $package_id)
                ->where('extension_code', 'package_buy')
                ->where('rec_type', CART_GENERAL_GOODS);

            if (!empty($user_id)) {
                $row = $row->where('user_id', $user_id);
            } else {
                $row = $row->where('session_id', $session_id);
            }

            $row = app(BaseRepository::class)->getToArrayFirst($row);

            if ($row) { //如果购物车已经有此物品，则更新

                //超值礼包列表添加
                if ($type == 0) {
                    $num += $row['goods_number'];
                }

                if ($GLOBALS['_CFG']['use_storage'] == 0 || $num > 0) {
                    $res = Cart::where('parent_id', 0)
                        ->where('goods_id', $package_id)
                        ->where('extension_code', 'package_buy')
                        ->where('rec_type', CART_GENERAL_GOODS);

                    if (!empty($user_id)) {
                        $res = $res->where('user_id', $user_id);
                    } else {
                        $res = $res->where('session_id', $session_id);
                    }

                    $res->update(['goods_number' => $num]);
                } else {
                    $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], $num), ERR_OUT_OF_STOCK);
                    return false;
                }
            } else {
                //购物车没有此物品，则插入
                Cart::insert($parent);
            }
        }

        /* 把赠品删除 */
        $res = Cart::where('is_gift', '<>', 0);

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $res = $res->where('session_id', $session_id);
        }

        $res->delete();

        return true;
    }
}

/**
 * 发货单详情
 * @return  array
 */
function get_delivery_info($order_id = 0)
{
    $res = DeliveryOrder::where('order_id', $order_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 得到新发货单号
 * @return  string
 */
function get_delivery_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double)microtime() * 1000000);

    return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 *  by　　Leah
 * @param type $shipping_config
 * @return type
 */
function free_price($shipping_config)
{
    $shipping_config = unserialize($shipping_config);

    $arr = [];

    if (is_array($shipping_config)) {
        foreach ($shipping_config as $key => $value) {
            foreach ($value as $k => $v) {
                $arr['configure'][$value['name']] = $value['value'];
            }
        }
    }
    return $arr;
}

/**
 * 相同商品退换货单 by leah
 * @param type $ret_id
 * @param type $order_sn
 */
function return_order_info_byId($order_id, $refound = true, $is_whole = false)
{
    if (!$refound) {
        if (!$is_whole) {
            $refound_status = $refound === 0 ? 0 : 1;
            //获得唯一一个订单下 申请了全部退换货的退换货订单
            $res = OrderReturn::where('order_id', $order_id)
                ->where('refound_status', $refound_status)
                ->count();
        } else {
            $res = OrderReturn::where('order_id', $order_id)
                ->count();
        }

    } else {
        $res = OrderReturn::where('order_id', $order_id);
        $res = app(BaseRepository::class)->getToArrayGet($res);
    }

    return $res;
}

/**
 * 获取退换货订单是否整单
 */
function get_order_return_rec($order_id, $is_whole = false)
{
    $res = OrderGoods::select('rec_id')
        ->where('order_id', $order_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);
    $order_goods_count = count($res);
    $rec_list = app(BaseRepository::class)->getKeyPluck($res, 'rec_id');

    $res = OrderReturn::select('rec_id')
        ->where('order_id', $order_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);
    $return_goods = app(BaseRepository::class)->getKeyPluck($res, 'rec_id');

    $is_diff = false;

    if ($is_whole) {
        $order_goods_count = 0;
    }

    if (!array_diff($rec_list, $return_goods) && count($return_goods) === 1 && $order_goods_count != 1) {
        $is_diff = true;
    }

    return $is_diff;
}

/**
 * 退货单信息
 *
 * @param int $ret_id
 * @param string $order_sn
 * @param int $order_id
 * @return mixed
 * @throws Exception
 */
function return_order_info($ret_id = 0, $order_sn = '', $order_id = 0)
{
    $ret_id = intval($ret_id);
    if ($ret_id > 0) {
        $res = ReturnGoods::select('rec_id', 'ret_id', 'goods_id', 'return_number', 'refound')
            ->whereHas('getOrderReturn', function ($query) use ($ret_id) {
                $query->where('ret_id', $ret_id);
            });

        $res = $res->with([
            'getOrderReturn',
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'goods_name', 'shop_price', 'user_id AS ru_id');
            },
            'getOrderReturnExtend' => function ($qeury) {
                $qeury->select('ret_id', 'return_number');
            }
        ]);

        $res = app(BaseRepository::class)->getToArrayFirst($res);
        if ($res) {
            $res = isset($res['get_order_return']) ? app(BaseRepository::class)->getArrayMerge($res, $res['get_order_return']) : $res;
            $res = isset($res['get_goods']) ? app(BaseRepository::class)->getArrayMerge($res, $res['get_goods']) : $res;
            $res = isset($res['get_order_return_extend']) ? app(BaseRepository::class)->getArrayMerge($res, $res['get_order_return_extend']) : $res;
        }

        if ($res) {
            $order = OrderInfo::select('order_id', 'order_sn', 'add_time', 'chargeoff_status', 'goods_amount', 'discount', 'chargeoff_status as order_chargeoff_status', 'is_zc_order', 'agency_id', 'country', 'province', 'city', 'district', 'street')
                ->where('order_id', $res['order_id'])
                ->with([
                    'getDeliveryOrder' => function ($query) {
                        $query->select('delivery_id', 'order_id', 'delivery_sn', 'update_time', 'how_oos', 'shipping_fee', 'insure_fee', 'invoice_no');
                    },
                    'getRegionProvince' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionCity' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionDistrict' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionStreet' => function ($query) {
                        $query->select('region_id', 'region_name');
                    }
                ]);

            $order = app(BaseRepository::class)->getToArrayFirst($order);
            $order = app(BaseRepository::class)->getArrayMerge($order, $order['get_delivery_order']);

            $res = app(BaseRepository::class)->getArrayMerge($res, $order);

            if ($res && $res['chargeoff_status'] != 0) {
                $res['chargeoff_status'] = $res['order_chargeoff_status'] ? $res['order_chargeoff_status'] : 0;
            }
        }

        $order = $res;
    } else {
        $order = OrderReturn::whereRaw(1);

        if ($order_id) {
            $order = $order->where('order_id', $order_id);
        } else {
            $order = $order->where('order_sn', $order_sn);
        }

        $order = $order->with([
            'getReturnGoods' => function ($query) {
                $query->select('ret_id', 'return_number', 'refound');
            },
            'orderInfo',
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name');
            }
        ]);

        $order = app(BaseRepository::class)->getToArrayFirst($order);
        if (isset($order['order_info'])) {
            $order = collect($order)->merge($order['order_info'])->except('order_info')->all();
        }
    }

    if ($order) {

        /* 取得区域名 */
        $province = $order['get_region_province']['region_name'] ?? '';
        $city = $order['get_region_city']['region_name'] ?? '';
        $district = $order['get_region_district']['region_name'] ?? '';
        $street = $order['get_region_street']['region_name'] ?? '';
        $order['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

        if (isset($order['discount']) && $order['discount'] > 0) {
            $discount_percent = $order['discount'] / $order['goods_amount'];
            $order['discount_percent_decimal'] = number_format($discount_percent, 2, '.', '');
            $order['discount_percent'] = $order['discount_percent_decimal'] * 100;
        } else {
            $order['discount_percent_decimal'] = 0;
            $order['discount_percent'] = 0;
        }

        $order['attr_val'] = is_string($order['attr_val']) ? $order['attr_val'] : unserialize($order['attr_val']);
        $order['apply_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['apply_time']);
        $order['formated_update_time'] = isset($order['update_time']) ? local_date($GLOBALS['_CFG']['time_format'], $order['update_time']) : '';
        $order['formated_return_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['return_time']);
        $order['formated_add_time'] = isset($order['add_time']) ? local_date($GLOBALS['_CFG']['time_format'], $order['add_time']) : '';
        $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;

        $return_goods = [];
        if ($ret_id > 0) {
            $return_goods = ReturnGoods::select('return_number', 'refound')->where('rec_id', $order['rec_id']);
            $return_goods = app(BaseRepository::class)->getToArrayFirst($return_goods);
        }

        if ($return_goods) {
            $return_number = $return_goods['return_number'];
        } else {
            $return_number = 0;
        }

        $order['return_number'] = $return_number;

        //获取订单商品总数
        $all_goods_number = OrderGoods::selectRaw("SUM(goods_number) AS goods_number")->where('order_id', $order['order_id'])->value('goods_number');

        //如果订单只有一个商品  折扣金额为全部折扣  否则按折扣比例计算
        if ($return_number == $all_goods_number) {
            $order['discount_amount'] = number_format($order['discount'], 2, '.', '');
        } else {
            $order['discount_amount'] = number_format($order['should_return'] * $order['discount_percent_decimal'], 2, '.', ''); //折扣金额
        }

        $return_amount = $order['should_return'] + $order['return_shipping_fee'] - $order['discount_amount'] - $order['goods_bonus'] - $order['goods_coupons'];

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $order['formated_return_rate_price'] = price_format($order['return_rate_price'], false);
            $return_amount = $order['should_return'] + $order['return_shipping_fee'] + $order['return_rate_price'] - $order['discount_amount'] - $order['goods_bonus'] - $order['goods_coupons'];
        }

        $order['should_return1'] = number_format($order['should_return'] - $order['discount_amount'], 2, '.', '');
        $order['formated_goods_amount'] = price_format($order['should_return'], false);
        $order['formated_discount_amount'] = price_format($order['discount_amount'], false);
        $order['formated_should_return'] = price_format($order['should_return'] - $order['discount_amount'], false);
        $order['formated_return_shipping_fee'] = price_format($order['return_shipping_fee'], false);
        $order['formated_return_amount'] = price_format($return_amount, false);
        $order['formated_actual_return'] = price_format($order['actual_return'], false);
        $order['return_status1'] = $order['return_status'];
        if ($order['return_status'] < 0) {
            $order['return_status'] = lang('user.only_return_money');
        } else {
            $order['return_status'] = lang('user.rf.' . $order['return_status']);
        }
        $order['refound_status1'] = $order['refound_status'];
        $order['shop_price'] = isset($order['shop_price']) ? price_format($order['shop_price'], false) : '';
        $order['refound_status'] = lang('user.ff.' . $order['refound_status']);
        $order['address_detail'] = $order['region'] . ' ' . $order['address'];

        $order['formated_goods_coupons'] = isset($order['goods_coupons']) ? price_format($order['goods_coupons'], false) : price_format(0);
        $order['formated_goods_bonus'] = isset($order['goods_bonus']) ? price_format($order['goods_bonus'], false) : price_format(0);

        // 退换货原因
        $parent_id = ReturnCause::where('cause_id', $order['cause_id'])->value('parent_id');
        $parent = ReturnCause::where('cause_id', $parent_id)->value('cause_name');

        $child = ReturnCause::where('cause_id', $order['cause_id'])
            ->value('cause_name');
        if ($parent) {
            $order['return_cause'] = $parent . "-" . $child;
        } else {
            $order['return_cause'] = $child;
        }

        if ($order['return_status1'] == REFUSE_APPLY) {
            $order['action_note'] = ReturnAction::where('ret_id', $order['ret_id'])
                ->where('return_status', REFUSE_APPLY)
                ->orderBy('log_time', 'desc')
                ->value('action_note');
        }

        if (!empty($order['back_other_shipping'])) {
            $order['back_shipp_shipping'] = $order['back_other_shipping'];
        } else {
            if ($order['back_shipping_name'] != "999") {
                $order['back_shipp_shipping'] = get_shipping_name($order['back_shipping_name']);
            } else {
                $order['back_shipp_shipping'] = "其他";
            }
        }

        if ($order['out_shipping_name']) {
            $order['out_shipp_shipping'] = get_shipping_name($order['out_shipping_name']);
        }

        //下单，商品单价
        $goods_price = OrderGoods::where('order_id', $order['order_id'])
            ->where('goods_id', $order['goods_id'])
            ->value('goods_price');
        $order['goods_price'] = price_format($goods_price, false);

        // 取得退换货商品客户上传图片凭证
        $where = [
            'user_id' => $order['user_id'],
            'rec_id' => $order['rec_id']
        ];
        $order['img_list'] = app(OrderRefoundService::class)->getReturnImagesList($where);

        $order['goods_thumb'] = isset($order['goods_thumb']) ? get_image_path($order['goods_thumb']) : '';

        $order['img_count'] = count($order['img_list']);

        //IM or 客服
        if ($GLOBALS['_CFG']['customer_service'] == 0) {
            $ru_id = 0;
        } else {
            $ru_id = $order['ru_id'] ?? 0;
        }

        $shop_information = app(MerchantCommonService::class)->getShopName($ru_id); //通过ru_id获取到店铺信息;
        $order['is_IM'] = isset($shop_information['is_IM']) ? $shop_information['is_IM'] : 0; //平台是否允许商家使用"在线客服";

        $order['shop_name'] = app(MerchantCommonService::class)->getShopName($ru_id, 1);
        $order['shop_url'] = app(DscRepository::class)->buildUri('merchants_store', ['urid' => $ru_id], $order['shop_name']);

        if ($ru_id == 0) {
            //判断平台是否开启了IM在线客服
            $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
            if ($kf_im_switch) {
                $order['is_dsc'] = true;
            } else {
                $order['is_dsc'] = false;
            }
        } else {
            $order['is_dsc'] = false;
        }

        $order['ru_id'] = $ru_id;

        $basic_info = $shop_information;

        $chat = app(DscRepository::class)->chatQq($basic_info);

        $order['kf_type'] = $chat['kf_type'];
        $order['kf_ww'] = $chat['kf_ww'];
        $order['kf_qq'] = $chat['kf_qq'];
    }

    return $order;
}

/**
 * 获得退换货商品
 * by  Leah
 */
function get_return_goods($ret_id = 0)
{
    $ret_id = intval($ret_id);

    $res = ReturnGoods::whereHas('getOrderReturn', function ($query) use ($ret_id) {
        $query->where('ret_id', $ret_id);
    });

    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'goods_thumb', 'brand_id')
                ->with('getBrand');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods']);
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_brand']);

            $row['refound'] = price_format($row['refound'], false);

            //图片显示
            $row['goods_thumb'] = get_image_path($row['goods_thumb']);

            $goods_list[] = $row;
        }
    }

    return $goods_list;
}

/**
 * 取的退换货表单里的商品
 * by Leah
 * @param type $rec_id
 * @return type
 */
function get_return_order_goods($rec_id)
{
    $goods_list = OrderGoods::where('rec_id', $rec_id);

    $goods_list = $goods_list->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'brand_id', 'goods_thumb')
                ->with('getBrand');
        }
    ]);

    $goods_list = app(BaseRepository::class)->getToArrayGet($goods_list);

    if ($goods_list) {
        foreach ($goods_list as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods']);
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_brand']);

            $goods_list[$key] = $row;
            $goods_list[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
        }
    }

    return $goods_list;
}

/**
 * 取的订单上商品中的某一商品
 * by　Leah
 * @param type $rec_id
 */
function get_return_order_goods1($rec_id = 0)
{
    $res = OrderGoods::where('rec_id', $rec_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 计算退款金额
 * by Leah  by kong
 * @param type $order_id
 * @param type $rec_id
 * @param type $num
 * @return type
 */
function get_return_refound($order_id = 0, $rec_id = 0, $num = 0)
{
    $orders = OrderInfo::select('money_paid', 'goods_amount', 'surplus', 'shipping_fee')
        ->where('order_id', $order_id);
    $orders = app(BaseRepository::class)->getToArrayFirst($orders);

    $return_shipping_fee = OrderReturn::selectRaw("SUM(return_shipping_fee) AS return_shipping_fee")
        ->where('order_id', $order_id)
        ->whereIn('return_type', [1, 3])
        ->value('return_shipping_fee');

    $res = OrderGoods::selectRaw("goods_number, goods_price, (goods_number * goods_price) AS goods_amount")
        ->where('rec_id', $rec_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    if ($res && $num > $res['goods_number'] || empty($num)) {
        $num = $res['goods_number'];
    }

    $return_price = $num * $res['goods_price'];
    $return_shipping_fee = $orders['shipping_fee'] - $return_shipping_fee;

    if ($return_price > 0) {
        $return_price = number_format($return_price, 2, '.', '');
    }

    if ($return_shipping_fee > 0) {
        $return_shipping_fee = number_format($return_shipping_fee, 2, '.', '');
    }

    $arr = [
        'return_price' => $return_price,
        'return_shipping_fee' => $return_shipping_fee
    ];

    return $arr;
}

/**
 * 取得用户退换货商品
 * by  leah
 */
function return_order($size = 0, $start = 0)
{
    $user_id = session('user_id', 0);
    $activation_number_type = (intval($GLOBALS['_CFG']['activation_number_type']) > 0) ? intval($GLOBALS['_CFG']['activation_number_type']) : 2;

    $res = OrderReturn::where('user_id', $user_id);
    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'goods_thumb', 'goods_name');
        }
    ]);

    // 检测商品是否存在
    $res = $res->whereHas('getGoods', function ($query) {
        $query->select('goods_id')->where('goods_id', '>', 0);
    });

    $res = $res->orderBy('ret_id', 'desc');

    if ($start > 0) {
        $res = $res->skip($start);
    }
    if ($size > 0) {
        $res = $res->take($size);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $goods_list = [];
    if ($res) {
        foreach ($res as $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods']);

            $row['goods_thumb'] = get_image_path($row['goods_thumb']);
            $row['apply_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['apply_time']);
            $row['should_return'] = price_format($row['should_return'], false);

            $row['order_status'] = '';
            if ($row['return_status'] == 0 && $row['refound_status'] == 0) {
                //  提交退换货后的状态 由用户寄回
                $row['order_status'] .= "<span>" . lang('user.user_return') . "</span>";
            } elseif ($row['return_status'] == 1) {
                //退换商品收到
                $row['order_status'] .= "<span>" . lang('user.get_goods') . "</span>";
            } elseif ($row['return_status'] == 2) {
                //换货商品寄出 （分单）
                $row['order_status'] .= "<span>" . lang('user.send_alone') . "</span>";
            } elseif ($row['return_status'] == 3) {
                //换货商品寄出
                $row['order_status'] .= "<span>" . lang('user.send') . "</span>";
            } elseif ($row['return_status'] == 4) {
                //完成
                $row['order_status'] .= "<span>" . lang('user.complete') . "</span>";
            } elseif ($row['return_status'] == 6) {
                //被拒
                $row['order_status'] .= "<span>" . lang('user.rf.' . $row['return_status']) . "</span>";
            } else {
                //其他
            }

            //维修-退款-换货状态
            if ($row['return_type'] == 0) {
                if ($row['return_status'] == 4) {
                    $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_MAINTENANCE];
                } else {
                    $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOMAINTENANCE];
                }
            } elseif ($row['return_type'] == 1) {
                if ($row['refound_status'] == 1) {
                    $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_REFOUND];
                } else {
                    $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOREFOUND];
                }
            } elseif ($row['return_type'] == 2) {
                if ($row['return_status'] == 4) {
                    $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_EXCHANGE];
                } else {
                    $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOEXCHANGE];
                }
            } elseif ($row['return_type'] == 3) {
                if ($row['refound_status'] == 1) {
                    $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_REFOUND];
                } else {
                    $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOREFOUND];
                }
            }
            $row['activation_type'] = 0;
            //判断是否支持激活
            if ($row['return_status'] == 6) {
                if ($row['activation_number'] < $activation_number_type) {
                    $row['activation_type'] = 1;
                }
            }

            $goods_list[] = $row;
        }
    }

    return $goods_list;
}

/**
 * 获得退换货操作log
 *
 * @param $ret_id
 * @return array
 */
function get_return_action($ret_id)
{
    $res = ReturnAction::where('ret_id', $ret_id)
        ->orderBy('log_time', 'desc')
        ->orderBy('ret_id', 'desc');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $act_list = [];
    if ($res) {
        foreach ($res as $row) {
            $row['return_status'] = lang('user.rf.' . $row['return_status']);
            $row['refound_status'] = lang('user.ff.' . $row['refound_status']);
            $row['action_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['log_time']);

            $act_list[] = $row;
        }
    }

    return $act_list;
}

/**
 *  获取订单里某个商品 信息 BY  Leah
 * @param type $rec_id
 * @return
 */
function rec_goods($rec_id)
{
    $StoreRep = app(StoreService::class);
    $OrderRep = app(OrderService::class);

    $where = [
        'rec_id' => $rec_id
    ];
    $res = $OrderRep->getOrderGoodsInfo($where);

    if (empty($res)) {
        return [];
    }

    $subtotal = $res['goods_price'] * $res['goods_number'];

    if ($res['extension_code'] == 'package_buy') {
        $res['package_goods_list'] = app(PackageGoodsService::class)->getPackageGoods($res['goods_id']);
    }
    $res['market_price'] = price_format($res['market_price'], false);
    $res['goods_price1'] = $res['goods_price'];
    $res['goods_price'] = price_format($res['goods_price'], false);
    $res['subtotal'] = price_format($subtotal, false);

    $res['format_goods_coupons'] = price_format($res['goods_coupons'], false);
    $res['format_goods_bonus'] = price_format($res['goods_bonus'], false);
    $res['format_actual_return'] = price_format($subtotal - $res['goods_coupons'] - $res['goods_bonus'], false);

    $goods = Goods::select('goods_img', 'goods_thumb', 'user_id')
        ->where('goods_id', $res['goods_id']);
    $goods = app(BaseRepository::class)->getToArrayFirst($goods);

    $res['user_name'] = app(MerchantCommonService::class)->getShopName($goods['user_id'], 1);

    $basic_info = $StoreRep->getShopInfo($goods['user_id']);

    $chat = app(DscRepository::class)->chatQq($basic_info);
    $res['kf_type'] = $chat['kf_type'];
    $res['kf_qq'] = $chat['kf_qq'];
    $res['kf_ww'] = $chat['kf_ww'];

    /* 修正商品图片 */
    $res['goods_img'] = get_image_path($goods['goods_img']);
    $res['goods_thumb'] = get_image_path($goods['goods_thumb']);

    $res['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $res['goods_id']], $res['goods_name']);

    return $res;
}

/**
 * 是否有退换货记录 by Leah
 * @param int $rec_id
 * @return
 */
function get_is_refound($rec_id)
{
    $count = OrderReturn::where('rec_id', $rec_id)->count();

    if ($count > 0) {
        $is_refound = 1;
    } else {
        $is_refound = 0;
    }

    return $is_refound;
}

/**
 * 订单单品退款
 * @param array $order 订单
 * @param int $refund_type 退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
 * @param string $refund_note 退款说明
 * @param float $refund_amount 退款金额（如果为0，取订单已付款金额）
 * @return  bool
 */
function order_refound($order, $refund_type, $refund_note, $refund_amount = 0, $operation = '')
{
    $StoreRep = app(StoreService::class);

    /* 检查参数 */
    $user_id = $order['user_id'];
    if ($user_id == 0 && $refund_type == 1) {
        return 'anonymous, cannot return to account balance';
    }

    $in_operation = ['refound'];

    //过滤白条
    if ($refund_type != 5) {
        if (in_array($operation, $in_operation)) {
            $amount = $refund_amount;
        } else {
            $amount = $refund_amount > 0 ? $refund_amount : $order['should_return'];
        }

        if ($amount <= 0) {
            return 1;
        }
    }

    if (!in_array($refund_type, [1, 2, 3, 5])) { //5:白条退款 bylu;
        return 'invalid params';
    }

    /* 备注信息 */
    if ($refund_note) {
        $change_desc = $refund_note;
    } else {
        $change_desc = sprintf(lang('admin/order.order_refund'), $order['order_sn']);
    }

    /* 处理退款 */
    if (1 == $refund_type) {
        /* 如果非匿名，退回余额 */
        if ($user_id > 0) {
            $is_ok = 1;
            if ($order['ru_id'] && $order['chargeoff_status'] == 2) {
                $seller_shopinfo = $StoreRep->getShopInfo($order['ru_id']);

                if ($seller_shopinfo) {
                    $seller_shopinfo['credit'] = $seller_shopinfo['seller_money'] + $seller_shopinfo['credit_money'];
                }

                if ($seller_shopinfo && $seller_shopinfo['credit'] > 0 && $seller_shopinfo['credit'] >= $amount) {
                    $adminru = get_admin_ru_id();

                    $change_desc = "操作员：【" . $adminru['user_name'] . "】，订单退款【" . $order['order_sn'] . "】" . $refund_note;
                    $log = [
                        'user_id' => $order['ru_id'],
                        'user_money' => (-1) * $amount,
                        'change_time' => gmtime(),
                        'change_desc' => $change_desc,
                        'change_type' => 2
                    ];
                    MerchantsAccountLog::insert($log);

                    SellerShopinfo::where('ru_id', $order['ru_id'])->increment('seller_money', $log['user_money']);
                } else {
                    $is_ok = 0;
                }
            }

            if ($is_ok == 1) {
                log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
            } else {
                /* 返回失败，不允许退款 */
                return 2;
            }
        }

        return 1;
    } elseif (2 == $refund_type) {
        return true;
    } elseif (22222 == $refund_type) {
        /* 如果非匿名，退回余额 */
        if ($user_id > 0) {
            log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
        }

        /* user_account 表增加提款申请记录 */
        $account = [
            'user_id' => $user_id,
            'amount' => DB::raw((-1) * $amount),
            'add_time' => gmtime(),
            'user_note' => $refund_note,
            'process_type' => SURPLUS_RETURN,
            'admin_user' => session('admin_name', ''),
            'admin_note' => sprintf(lang('admin/order.order_refund'), $order['order_sn']),
            'is_paid' => 0
        ];

        UserAccount::insert($account);

        return 1;
    } /*  @bylu 白条退款 start */
    elseif (5 == $refund_type) {

        //查询当前退款订单使用了多少余额支付;
        $surplus = OrderInfo::where('order_id', $order['order_id'])->value('surplus');

        //余额退余额,白条退白条;
        if ($surplus != 0.00) {
            log_account_change($user_id, $surplus, 0, 0, 0, lang('baitiao.baitiao') . $change_desc);
        } else {
            $baitiao_info = BaitiaoLog::where('order_id', $order['order_id']);
            $baitiao_info = app(BaseRepository::class)->getToArrayFirst($baitiao_info);

            if ($baitiao_info['is_stages'] == 1) {
                $surplus = $baitiao_info['yes_num'] * $baitiao_info['stages_one_price'];
                log_account_change($user_id, $surplus, 0, 0, 0, lang('baitiao.baitiao_stages') . $change_desc);
            } else {
                $surplus = $order['order_amount'];
                log_account_change($user_id, $surplus, 0, 0, 0, lang('baitiao.baitiao') . $change_desc);
            }
        }

        //将当前退款订单的白条记录表中的退款信息变更为"退款";
        BaitiaoLog::where('order_id', $order['order_id'])->update(['is_refund' => 1]);
        return 1;
    } /*  @bylu 白条退款 end */
    else {
        return 1;
    }
}

/**
 * 退换货 用户积分退还
 * by Leah
 */
function return_surplus_integral_bonus($user_id, $goods_price, $return_goods_price)
{
    $pay_points = Users::where('user_id', $user_id)->value('pay_points');

    $pay_points = $pay_points - $goods_price + $return_goods_price;

    if ($pay_points > 0) {
        $other = [
            'pay_points' => $pay_points
        ];
        Users::where('user_id', $user_id)->update($other);
    }
}

// 重组商家购物车数组  按照优惠活动对购物车商品进行分类 -qin
function cart_by_favourable($merchant_goods)
{
    $CategoryLib = app(CategoryService::class);

    $list_array = [];
    $rec_id = app(CartCommonService::class)->getCartValue();//购物车默认选中
    $cart_value['act_sel_id'] = app(BaseRepository::class)->getExplode($rec_id);

    foreach ($merchant_goods as $key => $row) { // 第一层 遍历商家
        $user_cart_goods = isset($row['goods_list']) && !empty($row['goods_list']) ? $row['goods_list'] : [];
        // 商家发布的优惠活动
        $favourable_list = favourable_list(session('user_rank'), $row['ru_id']);
        // 对优惠活动进行归类
        $sort_favourable = sort_favourable($favourable_list);

        if ($user_cart_goods) {
            foreach ($user_cart_goods as $goods_key => $goods_row) { // 第二层 遍历购物车中商家的商品
                $goods_row['original_price'] = $goods_row['goods_price'] * $goods_row['goods_number'];
                // 活动-全部商品
                if (isset($sort_favourable['by_all']) && $goods_row['extension_code'] != 'package_buy' && substr($goods_row['extension_code'], 0, 7) != 'seckill') {
                    foreach ($sort_favourable['by_all'] as $fav_key => $fav_row) {
                        if (isset($goods_row) && $goods_row && ($goods_row['act_id'] == $fav_row['act_id'] || empty($goods_row['act_id']))) {
                            $mer_ids = true;
                            if ($GLOBALS['_CFG']['region_store_enabled']) {
                                //卖场促销 liu
                                $mer_ids = get_favourable_merchants($fav_row['userFav_type'], $fav_row['userFav_type_ext'], $fav_row['rs_id'], 1, $goods_row['ru_id']);
                            }
                            if ($fav_row['userFav_type'] == 1 || $mer_ids) {
                                if ($goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0) {// 活动商品
                                    if (isset($goods_row) && $goods_row) {
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_id'] = $fav_row['act_id'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_name'] = $fav_row['act_name'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type'] = $fav_row['act_type'];
                                        // 活动类型
                                        switch ($fav_row['act_type']) {
                                            case 0:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.With_a_gift');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                                break;
                                            case 1:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.Full_reduction');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = number_format($fav_row['act_type_ext'], 2); // 满减金额
                                                break;
                                            case 2:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.discount');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = floatval($fav_row['act_type_ext'] / 10); // 折扣百分比
                                                break;

                                            default:
                                                break;
                                        }
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['min_amount'] = $fav_row['min_amount'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                        if (in_array($goods_row['rec_id'], $cart_value['act_sel_id'])) {
                                            @$merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] += $goods_row['subtotal'];
                                        }

                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] = favourable_available($fav_row, $cart_value, $goods_row['ru_id']); // 购物车满足活动最低金额
                                        if ($merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] && $fav_row['act_type'] == 2) {//折扣显示折扣金额
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['goods_fav_amount'] = isset($merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount']) ? price_format($merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] * floatval(100 - intval($fav_row['act_type_ext'])) / 100) : 0;
                                        }
                                        // 购物车中已选活动赠品数量
                                        $cart_favourable = cart_favourable($goods_row['ru_id']);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['favourable_used'] = favourable_used($fav_row, $cart_favourable);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['left_gift_num'] = intval($fav_row['act_type_ext']) - (empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]));

                                        /* 检查购物车中是否已有该优惠 */

                                        // 活动赠品
                                        if ($fav_row['gift']) {
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_gift_list'] = $fav_row['gift'];
                                        }
                                        /* 更新购物车商品促销活动 */
                                        if (empty($goods_row['act_id'])) {
                                            update_cart_goods_fav($goods_row['rec_id'], session('user_id'), $fav_row['act_id']);
                                        }

                                        $goods_row['favourable_list'] = get_favourable_info($goods_row['goods_id'], $goods_row['ru_id'], $goods_row);

                                        // new_list->活动id->act_goods_list
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list'][$goods_row['rec_id']] = $goods_row;

                                        unset($goods_row);

                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list_num'] = count($merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list']);
                                    }
                                }

                                // 赠品
                                if (isset($goods_row) && $goods_row && ($goods_row['is_gift'] == $fav_row['act_id'])) {
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                                    unset($goods_row);
                                }
                            } else {
                                if ($GLOBALS['_CFG']['region_store_enabled']) {
                                    // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                                    $merchant_goods[$key]['new_list'][0]['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                                    $merchant_goods[$key]['new_list'][0]['act_goods_list_num'] = count($merchant_goods[$key]['new_list'][0]['act_goods_list']);
                                }
                            }

                        }
                        continue; // 如果活动包含全部商品，跳出循环体
                    }
                }
                if (empty($goods_row)) {
                    continue;
                }
                // 活动-分类
                if (isset($sort_favourable['by_category']) && $goods_row['extension_code'] != 'package_buy' && substr($goods_row['extension_code'], 0, 7) != 'seckill') {
                    //优惠活动关联分类集合
                    $get_act_range_ext = get_act_range_ext(session('user_rank'), $row['ru_id'], 1); // 1表示优惠范围 按分类

                    $str_cat = '';
                    foreach ($get_act_range_ext as $id) {
                        /**
                         * 当前分类下的所有子分类
                         * 返回一维数组
                         */
                        $cat_keys = $CategoryLib->getCatListChildren(intval($id));

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
                    $cat_id = $goods_row['cat_id']; //购物车商品所属分类ID
                    // 优惠活动ID
                    $favourable_id_list = get_favourable_id($sort_favourable['by_category']);
                    // 判断商品或赠品 是否属于本优惠活动
                    if ((in_array($cat_id, $id_list) && $goods_row['is_gift'] == 0) || in_array($goods_row['is_gift'], $favourable_id_list)) {
                        foreach ($sort_favourable['by_category'] as $fav_key => $fav_row) {
                            if (isset($goods_row) && $goods_row && ($goods_row['act_id'] == $fav_row['act_id'] || empty($goods_row['act_id']))) {
                                //优惠活动关联分类集合
                                $fav_act_range_ext = !empty($fav_row['act_range_ext']) ? explode(',', $fav_row['act_range_ext']) : [];
                                foreach ($fav_act_range_ext as $id) {
                                    /**
                                     * 当前分类下的所有子分类
                                     * 返回一维数组
                                     */
                                    $cat_keys = $CategoryLib->getCatListChildren(intval($id));
                                    $fav_act_range_ext = array_merge($fav_act_range_ext, $cat_keys);
                                }

                                if ($goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0 && in_array($cat_id, $fav_act_range_ext)) { // 活动商品
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_id'] = $fav_row['act_id'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_name'] = $fav_row['act_name'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type'] = $fav_row['act_type'];
                                    // 活动类型
                                    switch ($fav_row['act_type']) {
                                        case 0:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.With_a_gift');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                            break;
                                        case 1:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.Full_reduction');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = number_format($fav_row['act_type_ext'], 2); // 满减金额
                                            break;
                                        case 2:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.discount');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = floatval($fav_row['act_type_ext'] / 10); // 折扣百分比
                                            break;

                                        default:
                                            break;
                                    }

                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['min_amount'] = $fav_row['min_amount'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                    if (in_array($goods_row['rec_id'], $cart_value['act_sel_id'])) {
                                        @$merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] += $goods_row['subtotal'];
                                    }
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] = favourable_available($fav_row, $cart_value, $goods_row['ru_id']); // 购物车满足活动最低金额
                                    if ($merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] && $fav_row['act_type'] == 2) {//折扣显示折扣金额
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['goods_fav_amount'] = isset($merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount']) ? price_format($merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] * floatval(100 - intval($fav_row['act_type_ext'])) / 100) : 0;
                                    }
                                    // 购物车中已选活动赠品数量
                                    $cart_favourable = cart_favourable($goods_row['ru_id']);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['favourable_used'] = favourable_used($fav_row, $cart_favourable);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['left_gift_num'] = intval($fav_row['act_type_ext']) - (empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]));

                                    /* 检查购物车中是否已有该优惠 */

                                    // 活动赠品
                                    if ($fav_row['gift']) {
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_gift_list'] = $fav_row['gift'];
                                    }

                                    /* 更新购物车商品促销活动 */
                                    if (empty($goods_row['act_id'])) {
                                        update_cart_goods_fav($goods_row['rec_id'], session('user_id'), $fav_row['act_id']);
                                    }

                                    $goods_row['favourable_list'] = get_favourable_info($goods_row['goods_id'], $goods_row['ru_id'], $goods_row);

                                    // new_list->活动id->act_goods_list
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list'][$goods_row['rec_id']] = $goods_row;

                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list_num'] = count($merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list']);
                                    unset($goods_row);
                                }

                                if (isset($goods_row) && $goods_row && ($goods_row['is_gift'] == $fav_row['act_id'])) { // 赠品
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                                    unset($goods_row);
                                }
                            }
                            continue;
                        }
                    }
                }
                if (empty($goods_row)) {
                    continue;
                }
                // 活动-品牌
                if (isset($sort_favourable['by_brand']) && $goods_row['extension_code'] != 'package_buy' && substr($goods_row['extension_code'], 0, 7) != 'seckill') {
                    // 优惠活动 品牌集合
                    $get_act_range_ext = get_act_range_ext(session('user_rank'), $row['ru_id'], 2); // 2表示优惠范围 按品牌
                    $brand_id = $goods_row['brand_id'];

                    // 优惠活动ID集合
                    $favourable_id_list = get_favourable_id($sort_favourable['by_brand']);

                    // 是品牌活动的商品或者赠品
                    if ((in_array(trim($brand_id), $get_act_range_ext) && $goods_row['is_gift'] == 0) || in_array($goods_row['is_gift'], $favourable_id_list)) {
                        foreach ($sort_favourable['by_brand'] as $fav_key => $fav_row) {
                            $act_range_ext_str = ',' . $fav_row['act_range_ext'] . ',';
                            $brand_id_str = ',' . $brand_id . ',';

                            if (isset($goods_row) && $goods_row && ($goods_row['act_id'] == $fav_row['act_id'] || empty($goods_row['act_id']))) {
                                if ($goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0 && strstr($act_range_ext_str, trim($brand_id_str))) { // 活动商品
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_id'] = $fav_row['act_id'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_name'] = $fav_row['act_name'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type'] = $fav_row['act_type'];
                                    // 活动类型
                                    switch ($fav_row['act_type']) {
                                        case 0:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.With_a_gift');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                            break;
                                        case 1:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.Full_reduction');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = number_format($fav_row['act_type_ext'], 2); // 满减金额
                                            break;
                                        case 2:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.discount');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = floatval($fav_row['act_type_ext'] / 10); // 折扣百分比
                                            break;

                                        default:
                                            break;
                                    }

                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['min_amount'] = $fav_row['min_amount'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                    if (in_array($goods_row['rec_id'], $cart_value['act_sel_id'])) {
                                        @$merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] += $goods_row['subtotal'];
                                    }
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] = favourable_available($fav_row, $cart_value, $goods_row['ru_id']); // 购物车满足活动最低金额
                                    if ($merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] && $fav_row['act_type'] == 2) {//折扣显示折扣金额
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['goods_fav_amount'] = isset($merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount']) ? price_format($merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] * floatval(100 - intval($fav_row['act_type_ext'])) / 100) : 0;
                                    }
                                    // 购物车中已选活动赠品数量
                                    $cart_favourable = cart_favourable($goods_row['ru_id']);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['favourable_used'] = favourable_used($fav_row, $cart_favourable);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['left_gift_num'] = intval($fav_row['act_type_ext']) - (empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]));

                                    /* 检查购物车中是否已有该优惠 */

                                    // 活动赠品
                                    if ($fav_row['gift']) {
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_gift_list'] = $fav_row['gift'];
                                    }

                                    $goods_row['favourable_list'] = get_favourable_info($goods_row['goods_id'], $goods_row['ru_id'], $goods_row);

                                    /* 更新购物车商品促销活动 */
                                    if (empty($goods_row['act_id'])) {
                                        update_cart_goods_fav($goods_row['rec_id'], session('user_id'), $fav_row['act_id']);
                                    }

                                    // new_list->活动id->act_goods_list
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list'][$goods_row['rec_id']] = $goods_row;

                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list_num'] = count($merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list']);
                                    unset($goods_row);
                                }

                                if (isset($goods_row) && $goods_row && ($goods_row['is_gift'] == $fav_row['act_id'])) { // 赠品
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                                    unset($goods_row);
                                }
                            }
                            continue;
                        }
                    }
                }
                if (empty($goods_row)) {
                    continue;
                }

                // 活动-部分商品
                if (isset($sort_favourable['by_goods']) && $goods_row['extension_code'] != 'package_buy' && substr($goods_row['extension_code'], 0, 7) != 'seckill') {
                    $get_act_range_ext = get_act_range_ext(session('user_rank'), $row['ru_id'], 3); // 3表示优惠范围 按商品
                    // 优惠活动ID集合
                    $favourable_id_list = get_favourable_id($sort_favourable['by_goods']);

                    // 判断购物商品是否参加了活动  或者  该商品是赠品
                    $goods_id = $goods_row['goods_id'];

                    if (in_array($goods_row['goods_id'], $get_act_range_ext) || in_array($goods_row['is_gift'], $favourable_id_list)) {
                        foreach ($sort_favourable['by_goods'] as $fav_key => $fav_row) { // 第三层 遍历活动
                            $act_range_ext_str = ',' . $fav_row['act_range_ext'] . ','; // 优惠活动中的优惠商品
                            $goods_id_str = ',' . $goods_id . ',';

                            // 如果是活动商品
                            if (isset($goods_row) && $goods_row && ($goods_row['act_id'] == $fav_row['act_id'] || empty($goods_row['act_id']))) {
                                if (strstr($act_range_ext_str, $goods_id_str) && $goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0) {
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_id'] = $fav_row['act_id'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_name'] = $fav_row['act_name'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type'] = $fav_row['act_type'];
                                    // 活动类型
                                    switch ($fav_row['act_type']) {
                                        case 0:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.With_a_gift');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                            break;
                                        case 1:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.Full_reduction');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = number_format($fav_row['act_type_ext'], 2); // 满减金额
                                            break;
                                        case 2:
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.discount');
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = floatval($fav_row['act_type_ext'] / 10); // 折扣百分比
                                            break;

                                        default:
                                            break;
                                    }
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['min_amount'] = $fav_row['min_amount'];
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                    if (in_array($goods_row['rec_id'], $cart_value['act_sel_id'])) {
                                        @$merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] += $goods_row['subtotal'];
                                    }
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] = favourable_available($fav_row, $cart_value, $goods_row['ru_id']); // 购物车满足活动最低金额
                                    if ($merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] && $fav_row['act_type'] == 2) {//折扣显示折扣金额
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['goods_fav_amount'] = isset($merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount']) ? price_format($merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] * floatval(100 - intval($fav_row['act_type_ext'])) / 100) : 0;
                                    }
                                    // 购物车中已选活动赠品数量
                                    $cart_favourable = cart_favourable($goods_row['ru_id']);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['favourable_used'] = favourable_used($fav_row, $cart_favourable);
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['left_gift_num'] = intval($fav_row['act_type_ext']) - (empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]));

                                    /* 检查购物车中是否已有该优惠 */

                                    // 活动赠品
                                    if ($fav_row['gift']) {
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_gift_list'] = $fav_row['gift'];
                                    }

                                    /* 更新购物车商品促销活动 */
                                    if (empty($goods_row['act_id'])) {
                                        update_cart_goods_fav($goods_row['rec_id'], session('user_id'), $fav_row['act_id']);
                                    }

                                    $goods_row['favourable_list'] = get_favourable_info($goods_row['goods_id'], $goods_row['ru_id'], $goods_row);
                                    // new_list->活动id->act_goods_list
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list'][$goods_row['rec_id']] = $goods_row;

                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list_num'] = count($merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list']);
                                    unset($goods_row);
                                }

                                // 如果是赠品
                                if (isset($goods_row) && $goods_row && ($goods_row['is_gift'] == $fav_row['act_id'])) {
                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                                    unset($goods_row);
                                }
                            }
                            continue;
                        }
                    }
                }
                if (empty($goods_row)) {
                    continue;
                }

                if ($goods_row) {//如果循环完所有的活动都没有匹配的 那该商品就没有参加活动
                    // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                    $merchant_goods[$key]['new_list'][0]['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                    $merchant_goods[$key]['new_list'][0]['act_goods_list_num'] = count($merchant_goods[$key]['new_list'][0]['act_goods_list']);
                }
            }
        }
    }

    return $merchant_goods;
}

/**
 * 取得某用户等级当前时间可以享受的优惠活动
 * @param int $user_rank 用户等级id，0表示非会员
 * @param int $user_id 商家id
 * @param int $fav_id 优惠活动ID
 * @param int $uid 会员ID
 * @return  array
 *
 * 显示赠品商品 $ru_id 传参
 */
function favourable_list($user_rank, $user_id = -1, $fav_id = 0, $act_sel_id = [], $ru_id = -1, $uid = 0)
{
    /* 购物车中已有的优惠活动及数量 */
    $used_list = cart_favourable($ru_id, $uid);

    /* 当前用户可享受的优惠活动 */
    $favourable_list = [];
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();

    $res = FavourableActivity::where('review_status', 3)
        ->where('start_time', '<=', $now)
        ->where('end_time', '>=', $now);

    $res = $res->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");

    if ($user_id >= 0) {
        $res = $res->whereRaw("IF(userFav_type = 0, user_id = '$user_id', 1 = 1)");
    }
    if ($fav_id > 0) {
        $res = $res->where('act_id', $fav_id);
    }

    $res = $res->orderBy('sort_order');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $favourable) {
            $favourable['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['start_time']);
            $favourable['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $favourable['end_time']);
            $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
            $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);

            if ($favourable['gift']) {
                $favourable['gift'] = unserialize($favourable['gift']);

                foreach ($favourable['gift'] as $key => $value) {
                    $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);

                    // 赠品缩略图
                    $goods = Goods::select('goods_id', 'goods_thumb')
                        ->where('goods_id', $value['id'])
                        ->where('is_on_sale', 1);

                    $goods = app(BaseRepository::class)->getToArrayFirst($goods);

                    $favourable['gift'][$key]['thumb_img'] = $goods ? get_image_path($goods['goods_thumb']) : '';

                    if (!$goods) {
                        unset($favourable['gift'][$key]);
                    }
                }
            }

            $favourable['act_range_desc'] = act_range_desc($favourable);

            $lang_act_type = isset($GLOBALS['_LANG']['fat_ext'][$favourable['act_type']]) ? $GLOBALS['_LANG']['fat_ext'][$favourable['act_type']] : '';
            $favourable['act_type_desc'] = sprintf($lang_act_type, $favourable['act_type_ext']);

            /* 是否能享受 */
            $favourable['available'] = favourable_available($favourable, $act_sel_id, -1, $uid, $user_rank);

            if ($favourable['available']) {
                /* 是否尚未享受 */
                $favourable['available'] = !favourable_used($favourable, $used_list);
            }

            $favourable['act_range_ext'] = act_range_ext_brand($favourable['act_range_ext'], $favourable['userFav_type'], $favourable['act_range']);

            $favourable_list[] = $favourable;
        }
    }

    return $favourable_list;
}

/**
 * 取得购物车中已有的优惠活动及数量
 * @return  array
 */
function cart_favourable($ru_id = -1, $user_id = 0)
{
    if (!$user_id) {
        $user_id = session('user_id', 0);
    }

    $res = Cart::selectRaw('is_gift, COUNT(*) AS num')
        ->where('rec_type', CART_GENERAL_GOODS)
        ->where('is_gift', '>', 0);

    if (!empty($user_id)) {
        $res = $res->where('user_id', $user_id);
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $res = $res->where('session_id', $session_id);
    }

    if ($ru_id > -1) {
        $res = $res->where('ru_id', $ru_id);
    }

    $res = $res->groupBy('is_gift');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $list = [];
    if ($res) {
        foreach ($res as $row) {
            $list[$row['is_gift']] = $row['num'];
        }
    }

    return $list;
}

/**
 * 购物车中是否已经有某优惠
 * @param array $favourable 优惠活动
 * @param array $cart_favourable购物车中已有的优惠活动及数量
 */
function favourable_used($favourable, $cart_favourable)
{
    if ($favourable['act_type'] == FAT_GOODS) {
        return isset($cart_favourable[$favourable['act_id']]) &&
            $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] &&
            $favourable['act_type_ext'] > 0;
    } else {
        return isset($cart_favourable[$favourable['act_id']]);
    }
}

/**
 * 取得优惠范围描述
 * @param array $favourable 优惠活动
 * @return  string
 */
function act_range_desc($favourable)
{
    if ($favourable && $favourable['act_range_ext']) {
        $act_range_ext = !is_array($favourable['act_range_ext']) ? explode(",", $favourable['act_range_ext']) : $favourable['act_range_ext'];

        if ($favourable['act_range'] == FAR_BRAND) {
            $brand_name = Brand::selectRaw("GROUP_CONCAT(brand_name) AS brand_name")->whereIn('brand_id', $act_range_ext)->value('brand_name');
            $brand_name = $brand_name ? $brand_name : '';

            return $brand_name;
        } elseif ($favourable['act_range'] == FAR_CATEGORY) {
            $cat_name = Category::selectRaw("GROUP_CONCAT(cat_name) AS cat_name")->whereIn('cat_id', $act_range_ext)->value('cat_name');
            $cat_name = $cat_name ? $cat_name : '';

            return $cat_name;
        } elseif ($favourable['act_range'] == FAR_GOODS) {
            $goods_name = Goods::selectRaw("GROUP_CONCAT(goods_name) AS goods_name")->whereIn('goods_id', $act_range_ext)->value('goods_name');
            $goods_name = $goods_name ? $goods_name : '';

            return $goods_name;
        }
    }

    return '';
}

/**
 * 根据购物车判断是否可以享受某优惠活动
 * @param array $favourable 优惠活动信息
 * @param strimg $cart_sel_id 购物车选中的商品id
 * @return  bool
 */
function favourable_available($favourable, $act_sel_id = [], $ru_id = -1, $uid = 0, $rank_id = 0)
{

    /* 会员等级是否符合 */
    if ($rank_id) {
        $user_rank = $rank_id;
    } else {
        $user_rank = session('user_rank', 1);
    }

    $user_rank = trim($user_rank, ',');

    $favourable_user_rank = isset($favourable['user_rank']) && !empty($favourable['user_rank']) ? explode(',', $favourable['user_rank']) : [];

    if (!in_array($user_rank, $favourable_user_rank)) {
        return false;
    }

    /* 优惠范围内的商品总额 */
    $amount = cart_favourable_amount($favourable, $act_sel_id, $ru_id, $uid);

    /* 金额上限为0表示没有上限 */
    return $amount >= $favourable['min_amount'] &&
        ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
}

/**
 * 取得购物车中某优惠活动范围内的总金额
 * @param array $favourable 优惠活动
 * @param strimg $cart_sel_id 购物车选中的商品id
 * @return  float
 */
function cart_favourable_amount($favourable, $act_sel_id = ['act_sel_id' => '', 'act_pro_sel_id' => '', 'act_sel' => ''], $ru_id = -1, $user_id = 0)
{
    if (empty($user_id)) {
        $user_id = session('user_id', 0);
    }

    $id_list = [];
    $list_array = [];

    /* 优惠范围内的商品总额 */
    $amount = Cart::selectRaw("SUM(goods_price * goods_number) AS price")
        ->where('rec_type', CART_GENERAL_GOODS)
        ->where('is_gift', 0)
        ->where('is_checked', 1)
        ->where('goods_id', '>', 0);

    if (!empty($user_id)) {
        $amount = $amount->where('user_id', $user_id);
    } else {
        $session_id = app(SessionRepository::class)->realCartMacIp();
        $amount = $amount->where('user_id', $session_id);
    }

    if ($GLOBALS['_CFG']['region_store_enabled']) {
        //卖场促销 liu
        $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id']);

        if ($favourable['userFav_type'] == 0 && $mer_ids) {
            $mer_ids = !is_array($mer_ids) ? explode(',', $mer_ids) : $mer_ids;

            $amount = $amount->whereIn('ru_id', $mer_ids);
        } else {
            if ($ru_id > -1 && !$mer_ids) {
                $amount = $amount->where('ru_id', $ru_id);
            }
        }
    } else {
        if ($favourable['userFav_type'] == 0) {
            $amount = $amount->where('ru_id', $favourable['user_id']);
        } else {
            if ($ru_id > -1) {
                $amount = $amount->where('ru_id', $ru_id);
            }
        }
    }

    $sel_id_list = isset($act_sel_id['act_sel_id']) && $act_sel_id['act_sel_id'] ? $act_sel_id['act_sel_id'] : '';

    if ($sel_id_list && !empty($act_sel_id['act_sel']) && ($act_sel_id['act_sel'] == 'cart_sel_flag')) {
        $sel_id_list = !is_array($sel_id_list) ? explode(',', $sel_id_list) : $sel_id_list;
        $amount = $amount->whereIn('rec_id', $sel_id_list);
    }

    if (isset($act_sel_id['act_sel_id']) && !empty($act_sel_id['act_sel_id'])) {
        $act_sel_id = !is_array($act_sel_id['act_sel_id']) ? explode(',', $act_sel_id['act_sel_id']) : $act_sel_id['act_sel_id'];
        $amount = $amount->whereIn('rec_id', $act_sel_id);
    } else {
        $amount = $amount->where('rec_id', 0);
    }

    $CategoryLib = app(CategoryService::class);

    if ($favourable['act_range_ext']) {
        /* 根据优惠范围修正sql */
        if ($favourable['act_range'] == FAR_ALL) {
            // sql do not change
        } elseif ($favourable['act_range'] == FAR_CATEGORY) {

            /* 取得优惠范围分类的所有下级分类 */
            $cat_list = explode(',', $favourable['act_range_ext']);

            $str_cat = '';
            foreach ($cat_list as $id) {
                /**
                 * 当前分类下的所有子分类
                 * 返回一维数组
                 */
                $cat_keys = $CategoryLib->getCatListChildren(intval($id));

                if ($cat_keys) {
                    $str_cat .= implode(",", $cat_keys);
                }
            }

            if ($str_cat) {
                $list_array = explode(",", $str_cat);
            }

            $list_array = !empty($list_array) ? array_merge($cat_list, $list_array) : $cat_list;
            $id_list = arr_foreach($list_array);
            $id_list = array_unique($id_list);
        } elseif ($favourable['act_range'] == FAR_BRAND) {
            $id_list = explode(',', $favourable['act_range_ext']);

            if ($favourable['userFav_type'] == 1 && $id_list) {
                $id_list = implode(",", $id_list);
                $id_list = act_range_ext_brand($favourable['act_range_ext'], $favourable['userFav_type'], $favourable['act_range']);
                $id_list = explode(",", $id_list);
            }
        } else {
            $id_list = explode(',', $favourable['act_range_ext']);
        }
    }

    $where = [
        'id_list' => $id_list,
        'act_range' => $favourable['act_range'],
        'range_type' => [
            'all' => FAR_ALL,
            'category' => FAR_CATEGORY,
            'brand' => FAR_BRAND
        ]
    ];
    $amount = $amount->whereHas('getGoods', function ($query) use ($where) {
        if ($where['id_list']) {
            $where['id_list'] = !is_array($where['id_list']) ? explode(',', $where['id_list']) : $where['id_list'];

            if ($where['act_range'] == $where['range_type']['all']) {
                // sql do not change
            } elseif ($where['act_range'] == $where['range_type']['category']) {
                $query->whereIn('cat_id', $where['id_list']);
            } elseif ($where['act_range'] == $where['range_type']['brand']) {
                $query->whereIn('brand_id', $where['id_list']);
            } else {
                $query->whereIn('goods_id', $where['id_list']);
            }
        }
    });

    if ($favourable && $favourable['act_id']) {
        $amount = $amount->where('act_id', $favourable['act_id']);
    }

    $amount = app(BaseRepository::class)->getToArrayFirst($amount);

    $amount = $amount ? $amount['price'] : 0;

    return $amount;
}

// 对优惠商品进行归类
function sort_favourable($favourable_list)
{
    $arr = [];
    foreach ($favourable_list as $key => $value) {
        switch ($value['act_range']) {
            case FAR_ALL:
                $arr['by_all'][$key] = $value;
                break;
            case FAR_CATEGORY:
                $arr['by_category'][$key] = $value;
                break;
            case FAR_BRAND:
                $arr['by_brand'][$key] = $value;
                break;
            case FAR_GOODS:
                $arr['by_goods'][$key] = $value;
                break;
            default:
                break;
        }
    }
    return $arr;
}

// 同一商家所有优惠活动包含的所有优惠范围 -qin
function get_act_range_ext($user_rank, $user_id = 0, $act_range)
{
    /* 当前用户可享受的优惠活动 */
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();

    $res = FavourableActivity::where('review_status', 3)
        ->where('start_time', '<=', $now)
        ->where('end_time', '>=', $now);

    $res = $res->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");

    if ($user_id >= 0) {
        $ext_where = '';
        if ($GLOBALS['_CFG']['region_store_enabled']) {
            $ext_where = " AND userFav_type_ext = '' ";
        }

        $res = $res->whereRaw("IF(userFav_type = 0 $ext_where, user_id = '$user_id', 1 = 1)");
    }

    if ($act_range > 0) {
        $res = $res->where('act_range', $act_range);
    }

    $res = $res->orderBy('sort_order');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            if ($row['act_range'] == FAR_GOODS && $GLOBALS['_CFG']['region_store_enabled']) {//卖场促销 liu
                $mer_ids = get_favourable_merchants($row['userFav_type'], $row['userFav_type_ext'], $row['rs_id'], 1);

                if ($row['act_range_ext']) {
                    $act_range_ext = !is_array($row['act_range_ext']) ? explode(",", $row['act_range_ext']) : $row['act_range_ext'];

                    $res = Goods::selectRaw('GROUP_CONCAT(goods_id) AS goods_id')->whereIn('goods_id', $act_range_ext);

                    if ($mer_ids) {
                        $mer_ids = !is_array($mer_ids) ? explode(",", $mer_ids) : $mer_ids;

                        $res = $res->whereIn('user_id', $mer_ids);
                    }

                    $res = app(BaseRepository::class)->getToArrayFirst($res);

                    $res = $res ? explode(",", $res['goods_id']) : [];
                }

                if ($res) {
                    $arr = array_merge($arr, $res);
                }
            } else {
                $row['act_range_ext'] = act_range_ext_brand($row['act_range_ext'], $row['userFav_type'], $row['act_range']);
                $id_list = explode(',', $row['act_range_ext']);
                $arr = array_merge($arr, $id_list);
            }
        }
    }

    return array_unique($arr);
}

// 获取活动id数组
function get_favourable_id($favourable)
{
    $arr = [];
    foreach ($favourable as $key => $value) {
        $arr[$key] = $value['act_id'];
    }

    return $arr;
}

/**
 * $type 0 获取数组差集数值
 * $type 1 获取数组交集数值
 */
function get_sc_str_replace($str1, $str2, $type = 0)
{

    if ($str1) {
        if (!is_array($str1)) {
            $str1 = explode(',', $str1);
        }
    }

    if ($str2) {
        if (!is_array($str2)) {
            $str2 = explode(',', $str2);
        }
    }

    $str = '';
    if ($str1 && $str2) {
        if ($type) {
            $str = array_diff($str1, $str2);
        } else {
            $str = array_intersect($str1, $str2);
        }

        $str = implode(",", $str);
    }

    return $str;
}

/* 查询订单商家ID */
function get_order_seller_id($order = '', $type = 0)
{
    if ($type == 1) {
        $res = OrderGoods::select('ru_id')
            ->whereHas('getOrder', function ($query) use ($order) {
                $query->where('order_sn', $order);
            });
    } else {
        $res = OrderGoods::select('ru_id')->where('order_id', $order);
    }

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/* 查询是否主订单商家 */
function get_order_main_child($order = '', $type = 0)
{
    $res = OrderInfo::whereRaw(1);

    if ($type == 1) {
        $res = $res->where('order_sn', $order);
        $order_id = $res->value('order_id');
    } else {
        $order_id = $order;
    }

    $child_count = OrderInfo::where('main_order_id', $order_id)->count();

    return $child_count;
}

//是否启用白条支付
function get_payment_code($code = 'chunsejinrong')
{
    $PaymentLib = app(PaymentService::class);
    $where = [
        'pay_code' => $code,
        'enabled' => 1
    ];
    $pament = $PaymentLib->getPaymentInfo($where);

    return $pament;
}

/**
 * 获取退款后的订单状态数组 by kong
 * $goods_number_return   类型：退换货商品数量
 * $rec_id   类型：退换货订单中的rec_id
 * $order_goods    类型：订单商品
 * $order_info    类型：订单详情
 */
function get_order_arr($goods_number_return = 0, $rec_id = 0, $order_goods = [], $order_info = [])
{
    $goods_number = 0;
    $goods_count = count($order_goods);
    $i = 1;

    if ($order_goods) {
        foreach ($order_goods as $k => $v) {
            if ($rec_id == $v['rec_id']) {
                $goods_number = $v['goods_number'];
            }

            $count = OrderReturn::where('rec_id', $v['rec_id'])
                ->where('order_id', $v['order_id'])
                ->where('refound_status', 1)
                ->count();

            if ($count > 0) {
                $i++;
            }
        }
    }

    if ($goods_number > $goods_number_return || $goods_count > $i) {
        //单品退货
        $arr = [
            'order_status' => OS_RETURNED_PART
        ];
    } else {
        //整单退货
        $arr = [
            'order_status' => OS_RETURNED,
            'pay_status' => PS_REFOUND,
            'shipping_status' => SS_UNSHIPPED,
            'money_paid' => 0,
            'invoice_no' => '',
            'order_amount' => 0
        ];
    }
    return $arr;
}

/* 获取购物车中同一活动下的商品和赠品 -qin
 *
 * 来源flow.php 转移函数
 *
 * $favourable_id int 优惠活动id
 * $act_sel_id string 活动中选中的cart id
 */
function cart_favourable_box($favourable_id, $act_sel_id = [], $user_id = 0, $rank_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
{
    $CategoryLib = app(CategoryService::class);

    $rank_id = $rank_id ? $rank_id : session('user_rank', 1);

    $fav_res = favourable_list($rank_id, -1, $favourable_id, $act_sel_id, -1, $user_id);
    $favourable_activity = $fav_res[0] ?? [];
    $favourable_activity['act_id'] = $favourable_activity['act_id'] ?? 0;
    $favourable_activity['act_range'] = $favourable_activity['act_range'] ?? '';

    /* 识别pc和wap */
    if (isset($act_sel_id['from']) && $act_sel_id['from'] == 'mobile') {
        //WAP端
        $cart_value = isset($act_sel_id['act_sel_id']) && !empty($act_sel_id['act_sel_id']) ? addslashes($act_sel_id['act_sel_id']) : 0;
    } else {
        //PC端
        $cart_value = isset($act_sel_id['act_pro_sel_id']) && !empty($act_sel_id['act_pro_sel_id']) ? addslashes($act_sel_id['act_pro_sel_id']) : 0;
    }

    $cart_goods = get_cart_goods($cart_value, 1, $warehouse_id, $area_id, $area_city, $user_id, $favourable_id);

    $merchant_goods = $cart_goods['goods_list'];

    $favourable_box = [];

    if ($cart_goods['total']['goods_price']) {
        $favourable_box['goods_amount'] = $cart_goods['total']['goods_price'];
    }

    $list_array = [];
    foreach ($merchant_goods as $key => $row) { // 第一层 遍历商家
        $user_cart_goods = $row['goods_list'];
        //if ($row['ru_id'] == $favourable_activity['user_id']) { //判断是否商家活动
        foreach ($user_cart_goods as $goods_key => $goods_row) { // 第二层 遍历购物车中商家的商品

            $goods_row['original_price'] = $goods_row['goods_price'] * $goods_row['goods_number'];
            $goods_row['goods_price'] = $goods_row['formated_goods_price'];

            if (!empty($act_sel_id)) { // 用来判断同一个优惠活动前面是否全部不选
                $goods_row['sel_checked'] = strstr(',' . $act_sel_id['act_sel_id'] . ',', ',' . $goods_row['rec_id'] . ',') ? 1 : 0; // 选中为1
            }
            if ($goods_row['act_id'] == $favourable_activity['act_id'] || empty($goods_row['act_id'])) {
                // 活动-全部商品
                if ($favourable_activity['act_range'] == 0 && $goods_row['extension_code'] != 'package_buy') {
                    if ($goods_row['is_gift'] == FAR_ALL && $goods_row['parent_id'] == 0) { // 活动商品

                        $favourable_box['act_id'] = $favourable_activity['act_id'];
                        $favourable_box['act_name'] = $favourable_activity['act_name'];
                        $favourable_box['act_type'] = $favourable_activity['act_type'];
                        // 活动类型
                        switch ($favourable_activity['act_type']) {
                            case 0:
                                $favourable_box['act_type_txt'] = lang('flow.With_a_gift');
                                $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                break;
                            case 1:
                                $favourable_box['act_type_txt'] = lang('flow.Full_reduction');
                                $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                break;
                            case 2:
                                $favourable_box['act_type_txt'] = lang('flow.discount');
                                $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                break;

                            default:
                                break;
                        }
                        $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                        $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                        $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id, $goods_row['ru_id'], $user_id);
                        $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id, $goods_row['ru_id'], $user_id, $rank_id); // 购物车满足活动最低金额
                        if ($favourable_box['available'] && $favourable_activity['act_type'] == 2) {//折扣显示折扣金额
                            $favourable_box['goods_fav_amount'] = price_format($favourable_box['cart_fav_amount'] * floatval(100 - $favourable_activity['act_type_ext']) / 100);
                        }
                        // 购物车中已选活动赠品数量
                        $cart_favourable = cart_favourable($goods_row['ru_id'], $user_id);
                        $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                        $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                        $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                        // 活动赠品
                        if ($favourable_activity['gift']) {
                            $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                        }

                        $goods_row['favourable_list'] = get_favourable_info($goods_row['goods_id'], $goods_row['ru_id'], $goods_row, $rank_id);

                        // new_list->活动id->act_goods_list
                        $favourable_box['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                    }
                    // 赠品
                    if ($goods_row['is_gift'] == $favourable_activity['act_id']) {
                        $favourable_box['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                    }
                    continue; // 如果活动包含全部商品，跳出循环体
                }

                if (empty($goods_row)) {
                    continue;
                }

                // 活动-分类
                if ($favourable_activity['act_range'] == FAR_CATEGORY && $goods_row['extension_code'] != 'package_buy') {
                    // 优惠活动关联的 分类集合
                    $get_act_range_ext = get_act_range_ext($rank_id, $row['ru_id'], 1); // 1表示优惠范围 按分类

                    $str_cat = '';
                    foreach ($get_act_range_ext as $id) {

                        /**
                         * 当前分类下的所有子分类
                         * 返回一维数组
                         */
                        $cat_keys = $CategoryLib->getCatListChildren(intval($id));

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
                    $cat_id = $goods_row['cat_id']; //购物车商品所属分类ID

                    // 判断商品或赠品 是否属于本优惠活动
                    if ((in_array(trim($cat_id), $id_list) && $goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0) || ($goods_row['is_gift'] == $favourable_activity['act_id'])) {
                        if ($goods_row['act_id'] == $favourable_activity['act_id'] || empty($goods_row['act_id'])) {
                            //优惠活动关联分类集合
                            $fav_act_range_ext = !empty($favourable_activity['act_range_ext']) ? explode(',', $favourable_activity['act_range_ext']) : [];

                            // 此 优惠活动所有分类
                            foreach ($fav_act_range_ext as $id) {
                                /**
                                 * 当前分类下的所有子分类
                                 * 返回一维数组
                                 */
                                $cat_keys = $CategoryLib->getCatListChildren(intval($id));
                                $fav_act_range_ext = array_merge($fav_act_range_ext, $cat_keys);
                            }

                            if ($goods_row['is_gift'] == 0 && in_array($cat_id, $fav_act_range_ext)) { // 活动商品
                                $favourable_box['act_id'] = $favourable_activity['act_id'];
                                $favourable_box['act_name'] = $favourable_activity['act_name'];
                                $favourable_box['act_type'] = $favourable_activity['act_type'];
                                // 活动类型
                                switch ($favourable_activity['act_type']) {
                                    case 0:
                                        $favourable_box['act_type_txt'] = lang('flow.With_a_gift');
                                        $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                        break;
                                    case 1:
                                        $favourable_box['act_type_txt'] = lang('flow.Full_reduction');
                                        $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                        break;
                                    case 2:
                                        $favourable_box['act_type_txt'] = lang('flow.discount');
                                        $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                        break;

                                    default:
                                        break;
                                }
                                $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id, $goods_row['ru_id'], $user_id);
                                $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id, $goods_row['ru_id'], $user_id, $rank_id); // 购物车满足活动最低金额
                                if ($favourable_box['available'] && $favourable_activity['act_type'] == 2) {//折扣显示折扣金额
                                    $favourable_box['goods_fav_amount'] = price_format($favourable_box['cart_fav_amount'] * floatval(100 - $favourable_activity['act_type_ext']) / 100);
                                }

                                // 购物车中已选活动赠品数量
                                $cart_favourable = cart_favourable($goods_row['ru_id'], $user_id);
                                $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                                $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                //活动赠品
                                if ($favourable_activity['gift']) {
                                    $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                }

                                $goods_row['favourable_list'] = get_favourable_info($goods_row['goods_id'], $goods_row['ru_id'], $goods_row, $rank_id);

                                // new_list->活动id->act_goods_list
                                $favourable_box['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                                $favourable_box['act_goods_list_num'] = count($favourable_box['act_goods_list']);
                            }
                            if ($goods_row['is_gift'] == $favourable_activity['act_id']) { // 赠品
                                $favourable_box['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                            }
                        }

                        continue;
                    }
                }

                if (empty($goods_row)) {
                    continue;
                }

                // 活动-品牌
                if ($favourable_activity['act_range'] == FAR_BRAND && $goods_row['extension_code'] != 'package_buy') {
                    // 优惠活动 品牌集合
                    $get_act_range_ext = get_act_range_ext($rank_id, $row['ru_id'], 2); // 2表示优惠范围 按品牌
                    $brand_id = $goods_row['brand_id'];

                    // 是品牌活动的商品或者赠品
                    if ((in_array(trim($brand_id), $get_act_range_ext) && $goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0) || ($goods_row['is_gift'] == $favourable_activity['act_id'])) {
                        if ($goods_row['act_id'] == $favourable_activity['act_id'] || empty($goods_row['act_id'])) {
                            $act_range_ext_str = ',' . $favourable_activity['act_range_ext'] . ',';
                            $brand_id_str = ',' . $brand_id . ',';

                            if ($goods_row['is_gift'] == 0 && strstr($act_range_ext_str, trim($brand_id_str))) { // 活动商品
                                $favourable_box['act_id'] = $favourable_activity['act_id'];
                                $favourable_box['act_name'] = $favourable_activity['act_name'];
                                $favourable_box['act_type'] = $favourable_activity['act_type'];
                                // 活动类型
                                switch ($favourable_activity['act_type']) {
                                    case 0:
                                        $favourable_box['act_type_txt'] = lang('flow.With_a_gift');
                                        $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                        break;
                                    case 1:
                                        $favourable_box['act_type_txt'] = lang('flow.Full_reduction');
                                        $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                        break;
                                    case 2:
                                        $favourable_box['act_type_txt'] = lang('flow.discount');
                                        $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                        break;

                                    default:
                                        break;
                                }
                                $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id, $goods_row['ru_id'], $user_id);
                                $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id, $goods_row['ru_id'], $user_id, $rank_id); // 购物车满足活动最低金额
                                if ($favourable_box['available'] && $favourable_activity['act_type'] == 2) {//折扣显示折扣金额
                                    $favourable_box['goods_fav_amount'] = price_format($favourable_box['cart_fav_amount'] * floatval(100 - $favourable_activity['act_type_ext']) / 100);
                                }
                                // 购物车中已选活动赠品数量
                                $cart_favourable = cart_favourable($goods_row['ru_id'], $user_id);
                                $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                                $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                //活动赠品
                                if ($favourable_activity['gift']) {
                                    $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                }

                                $goods_row['favourable_list'] = get_favourable_info($goods_row['goods_id'], $goods_row['ru_id'], $goods_row, $rank_id);

                                // new_list->活动id->act_goods_list
                                $favourable_box['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                            }
                            if ($goods_row['is_gift'] == $favourable_activity['act_id']) { // 赠品
                                $favourable_box['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                            }
                        }

                        continue;
                    }
                }

                if (empty($goods_row)) {
                    continue;
                }

                // 活动-部分商品
                if ($favourable_activity['act_range'] == FAR_GOODS && $goods_row['extension_code'] != 'package_buy') {
                    $get_act_range_ext = get_act_range_ext($rank_id, $row['ru_id'], 3); // 3表示优惠范围 按商品
                    // 判断购物商品是否参加了活动  或者  该商品是赠品
                    if (in_array($goods_row['goods_id'], $get_act_range_ext) || ($goods_row['is_gift'] == $favourable_activity['act_id'])) {
                        if ($goods_row['act_id'] == $favourable_activity['act_id'] || empty($goods_row['act_id'])) {
                            $act_range_ext_str = ',' . $favourable_activity['act_range_ext'] . ','; // 优惠活动中的优惠商品
                            $goods_id_str = ',' . $goods_row['goods_id'] . ',';

                            // 如果是活动商品
                            if (strstr($act_range_ext_str, trim($goods_id_str)) && $goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0) {
                                $favourable_box['act_id'] = $favourable_activity['act_id'];
                                $favourable_box['act_name'] = $favourable_activity['act_name'];
                                $favourable_box['act_type'] = $favourable_activity['act_type'];
                                // 活动类型
                                switch ($favourable_activity['act_type']) {
                                    case 0:
                                        $favourable_box['act_type_txt'] = lang('flow.With_a_gift');
                                        $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                        break;
                                    case 1:
                                        $favourable_box['act_type_txt'] = lang('flow.Full_reduction');
                                        $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                        break;
                                    case 2:
                                        $favourable_box['act_type_txt'] = lang('flow.discount');
                                        $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                        break;

                                    default:
                                        break;
                                }
                                $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id, $goods_row['ru_id'], $user_id);
                                $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id, $goods_row['ru_id'], $user_id, $rank_id); // 购物车满足活动最低金额

                                if ($favourable_box['available'] && $favourable_activity['act_type'] == 2) {//折扣显示折扣金额
                                    $favourable_box['goods_fav_amount'] = price_format($favourable_box['cart_fav_amount'] * floatval(100 - $favourable_activity['act_type_ext']) / 100);
                                }

                                // 购物车中已选活动赠品数量
                                $cart_favourable = cart_favourable($goods_row['ru_id'], $user_id);
                                $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                $favourable_box['favourable_used'] = favourable_used($favourable_box, $cart_favourable);
                                $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                // 活动赠品
                                if ($favourable_activity['gift']) {
                                    $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                }

                                $goods_row['favourable_list'] = get_favourable_info($goods_row['goods_id'], $goods_row['ru_id'], $goods_row, $rank_id);

                                // new_list->活动id->act_goods_list
                                $favourable_box['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                            }
                            // 如果是赠品
                            if ($goods_row['is_gift'] == $favourable_activity['act_id']) {
                                $favourable_box['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                            }
                        }
                    }
                } else {
                    if ($goods_row) {//如果循环完所有的活动都没有匹配的 那该商品就没有参加活动
                        $favourable_box[$goods_row['rec_id']] = $goods_row;
                    }
                }
            } else {
                $favourable_box[$goods_row['rec_id']] = $goods_row;
            }
        }
        // } 启用全场通用，修复购物车商品不显示的问题
    }
    return $favourable_box;
}

/*
* 通过商品ID获取成本价
* @param $goods_id   商品ID
*/
function get_cost_price($goods_id)
{
    return Goods::where('goods_id', $goods_id)->value('cost_price');
}

/*
* 通过订单ID获取订单商品的成本合计
* @param $order_id   订单ID
*/
function goods_cost_price($order_id)
{
    $res = OrderGoods::select('goods_id', 'goods_number')->where('order_id', $order_id)
        ->whereHas('getOrder');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $cost_amount = 0;
    if ($res) {
        foreach ($res as $v) {
            $cost_amount += get_cost_price($v['goods_id']) * $v['goods_number'];
        }
    }

    return $cost_amount;
}

/**
 * 退回余额、积分、红包（取消、无效、退货时），把订单使用余额、积分、红包、优惠券设为0
 * @param array $order 订单信息
 */
function return_user_surplus_integral_bonus($order)
{
    /* 处理余额、积分、红包 */
    if ($order['user_id'] > 0 && $order['surplus'] > 0) {
        $surplus = $order['money_paid'] < 0 ? $order['surplus'] + $order['money_paid'] : $order['surplus'];
        log_account_change($order['user_id'], $surplus, 0, 0, 0, sprintf(lang('admin/order.return_order_surplus'), $order['order_sn']), ACT_OTHER, 1);

        OrderInfo::where('order_id', $order['order_id'])
            ->update(['order_amount' => 0]);
    }

    if ($order['user_id'] > 0 && $order['integral'] > 0) {
        log_account_change($order['user_id'], 0, 0, 0, $order['integral'], sprintf(lang('admin/order.return_order_integral'), $order['order_sn']), ACT_OTHER, 1);
    }

    if ($order['bonus_id'] > 0) {
        unuse_bonus($order['bonus_id']);
    }


    /*  @author-bylu 退优惠券 start */
    if ($order['order_id'] > 0) {
        unuse_coupons($order['order_id']);
    }
    /*  @author-bylu  end */


    /*退储值卡 start*/
    if ($order['order_id'] > 0) {
        return_card_money($order['order_id']);
    }
    /*退储值卡 end*/


    /* 修改订单 */
    $arr = [
        'bonus_id' => 0,
        'bonus' => 0,
        'integral' => 0,
        'integral_money' => 0,
        'surplus' => 0
    ];
    update_order($order['order_id'], $arr);
}

//处理支付超时订单
function checked_pay_Invalid_order($pay_effective_time = 0)
{
    if ($pay_effective_time > 0) {
        $pay_effective_time = $pay_effective_time * 60;
        $time = gmtime();

        $order_list = OrderInfo::where('main_count', 0);

        $order_list = $order_list->whereHas('getPayment', function ($query) {
            $query->whereNotIn('pay_code', ['bank', 'cod', 'post']);
        });

        $order_list = $order_list->whereRaw("($time - add_time) > $pay_effective_time")
            ->whereIn('order_status', [OS_UNCONFIRMED, OS_CONFIRMED])
            ->whereIn('shipping_status', [SS_UNSHIPPED, SS_PREPARING])
            ->where('pay_status', PS_UNPAYED);

        $order_list = app(BaseRepository::class)->getToArrayGet($order_list);

        if (!empty($order_list)) {
            foreach ($order_list as $k => $v) {
                $store_order_id = get_store_id($v['order_id']);
                $store_id = ($store_order_id > 0) ? $store_order_id : 0;

                /* 标记订单为“无效” */
                update_order($v['order_id'], ['order_status' => OS_INVALID]);

                /* 记录log */
                order_action($v['order_sn'], OS_INVALID, SS_UNSHIPPED, PS_UNPAYED, $GLOBALS['_LANG']['pay_effective_Invalid']);

                /* 如果使用库存，且下订单时减库存，则增加库存 */
                if ($GLOBALS['_CFG']['use_storage'] == '1' && $GLOBALS['_CFG']['stock_dec_time'] == SDT_PLACE) {
                    change_order_goods_storage($v['order_id'], false, SDT_PLACE, 2, 0, $store_id);
                }
                /* 退还用户余额、积分、红包 */
                return_user_surplus_integral_bonus($v);

                /* 更新会员订单数量 */
                if (isset($v['user_id']) && !empty($v['user_id'])) {
                    $order_nopay = UserOrderNum::where('user_id', $v['user_id'])->value('order_nopay');
                    $order_nopay = $order_nopay ? intval($order_nopay) : 0;

                    if ($order_nopay > 0) {
                        $dbRaw = [
                            'order_nopay' => "order_nopay - 1",
                        ];
                        $dbRaw = app(BaseRepository::class)->getDbRaw($dbRaw);
                        UserOrderNum::where('user_id', $v['user_id'])->update($dbRaw);
                    }
                }

            }
        }
    }
}


function get_favourable_info($goods_id = 0, $ru_id = 0, $goods = [], $rank_id = 0)
{
    $CategoryLib = app(CategoryService::class);

    $gmtime = gmtime();

    $res = FavourableActivity::where('review_status', 3)
        ->where('start_time', '<=', $gmtime)
        ->where('end_time', '>=', $gmtime);

    if ($ru_id > 0) {
        $where = [
            'region_store_enabled' => $GLOBALS['_CFG']['region_store_enabled'],
            'ru_id' => $ru_id
        ];
        $res = $res->where(function ($query) use ($where) {
            $query = $query->where('user_id', $where['ru_id'])
                ->where('userFav_type', 1);

            if ($where['region_store_enabled']) {
                $query = $query->orWhere('userFav_type_ext', '<>', '');
            }
        });
    } else {
        $res = $res->where('user_id', $ru_id);
    }
    if ($rank_id) {
        $user_rank = ',' . $rank_id . ',';
    } else {
        $user_rank = ',' . session('user_rank') . ',';
    }

    if (!empty($goods_id)) {
        $res = $res->whereRaw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'");
    }

    $res = $res->take(15);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $favourable = [];
    if (empty($goods_id)) {
        foreach ($res as $rows) {
            $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
            $favourable[$rows['act_id']]['url'] = 'activity.php';
            $favourable[$rows['act_id']]['time'] = sprintf(lang('common.promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
            $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
            $favourable[$rows['act_id']]['type'] = 'favourable';
            $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
        }
    } else {
        if ($goods) {
            $category_id = isset($goods['cat_id']) && !empty($goods['cat_id']) ? $goods['cat_id'] : 0;
            $brand_id = isset($goods['brand_id']) && !empty($goods['brand_id']) ? $goods['brand_id'] : 0;
        } else {
            $row = Goods::select('cat_id', 'brand_id')->where('goods_id', $goods_id);
            $row = app(BaseRepository::class)->getToArrayFirst($row);

            $category_id = $row ? $row['cat_id'] : 0;
            $brand_id = $row ? $row['brand_id'] : 0;
        }

        foreach ($res as $rows) {
            if ($rows['act_range'] == FAR_ALL) {
                $mer_ids = true;
                if ($GLOBALS['_CFG']['region_store_enabled']) {
                    /* 设置的使用范围 卖场优惠活动 liu */
                    $mer_ids = get_favourable_merchants($rows['userFav_type'], $rows['userFav_type_ext'], $rows['rs_id'], 1, $ru_id);
                }
                if ($mer_ids) {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf(lang('common.promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                    $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                }
            } elseif ($rows['act_range'] == FAR_CATEGORY) {
                /* 找出分类id的子分类id */
                $raw_id_list = explode(',', $rows['act_range_ext']);

                foreach ($raw_id_list as $id) {
                    /**
                     * 当前分类下的所有子分类
                     * 返回一维数组
                     */
                    $cat_keys = $CategoryLib->getCatListChildren(intval($id));
                    $list_array[$rows['act_id']][$id] = $cat_keys;
                }

                $list_array = !empty($list_array) ? array_merge($raw_id_list, $list_array[$rows['act_id']]) : $raw_id_list;
                $id_list = arr_foreach($list_array);
                $id_list = array_unique($id_list);

                $ids = join(',', array_unique($id_list));

                if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false) {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf(lang('common.promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                    $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                }
            } elseif ($rows['act_range'] == FAR_BRAND) {
                $rows['act_range_ext'] = act_range_ext_brand($rows['act_range_ext'], $rows['userFav_type'], $rows['act_range']);
                if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false) {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf(lang('common.promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                    $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                }
            } elseif ($rows['act_range'] == FAR_GOODS) {
                if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false) {
                    $mer_ids = true;
                    if ($GLOBALS['_CFG']['region_store_enabled']) {
                        /* 设置的使用范围 卖场优惠活动 liu */
                        $mer_ids = get_favourable_merchants($rows['userFav_type'], $rows['userFav_type_ext'], $rows['rs_id'], 1, $ru_id);
                    }
                    if ($mer_ids) {
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['url'] = 'activity.php';
                        $favourable[$rows['act_id']]['time'] = sprintf(lang('common.promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                }
            }
        }
    }
    return $favourable;
}

/*
* 更新购物车商品促销活动
*/
function update_cart_goods_fav($rec_id = 0, $user_id = 0, $act_id = 0)
{
    if (empty($user_id)) {
        $user_id = session('user_id', 0);
    }

    $res = Cart::where('rec_id', $rec_id)
        ->where('user_id', $user_id)
        ->update(['act_id' => $act_id]);

    return $res;
}
