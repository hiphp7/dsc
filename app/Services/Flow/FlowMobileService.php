<?php

namespace App\Services\Flow;

use App\Models\AutoSms;
use App\Models\BargainStatisticsLog;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupons;
use App\Models\CouponsUser;
use App\Models\Crons;
use App\Models\ExchangeGoods;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\GoodsExtend;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\PackageGoods;
use App\Models\PresaleActivity;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\RegionWarehouse;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\ShippingArea;
use App\Models\StoreGoods;
use App\Models\StoreOrder;
use App\Models\TeamLog;
use App\Models\UserBonus;
use App\Models\UserMembershipCard;
use App\Models\UserOrderNum;
use App\Models\Users;
use App\Models\UsersPaypwd;
use App\Models\ValueCard;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Plugins\UserRights\Discount\Services\DiscountRightsService;
use App\Plugins\UserRights\DrpGoods\Services\DrpGoodsRightsService;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\BonusService;
use App\Services\Activity\CouponsService;
use App\Services\Activity\GroupBuyService;
use App\Services\Activity\ValueCardService;
use App\Services\Cart\CartCommonService;
use App\Services\Cart\CartGoodsService;
use App\Services\Category\CategoryService;
use App\Services\Coupon\CouponsUserService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Drp\DrpService;
use App\Services\Erp\JigonManageService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\OfflineStore\OfflineStoreService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderService;
use App\Services\Package\PackageGoodsService;
use App\Services\Payment\PaymentService;
use App\Services\Shipping\ShippingService;
use App\Services\Store\StoreService;
use App\Services\Team\TeamService;
use App\Services\User\UserAddressService;
use App\Services\User\UserCommonService;
use App\Services\Wechat\WechatService;
use Illuminate\Support\Facades\DB;

/**
 *
 * Class FlowMobileService
 * @package App\Services\Flow
 */
class FlowMobileService
{
    protected $couponsService;
    protected $BonusService;
    protected $valueCardService;
    protected $flowService;
    protected $timeRepository;
    protected $userAddressService;
    protected $teamService;
    protected $shippingService;
    protected $paymentService;
    protected $offlineStoreService;
    protected $commonService;
    protected $baseRepository;
    protected $wechatService;
    protected $userCommonService;
    protected $orderService;
    protected $commonRepository;
    protected $jigonManageService;
    protected $dscRepository;
    protected $goodsCommonService;
    protected $goodsWarehouseService;
    protected $sessionRepository;
    protected $bonusService;
    protected $config;
    protected $cartCommonService;
    protected $flowUserService;
    protected $orderCommonService;
    protected $packageGoodsService;
    protected $cartRepository;
    protected $cartGoodsService;
    protected $categoryService;
    protected $goodsProdutsService;
    protected $flowOrderService;
    protected $couponsUserService;
    protected $groupBuyService;

    public function __construct(
        TimeRepository $timeRepository,
        UserAddressService $userAddressService,
        PaymentService $paymentService,
        OfflineStoreService $offlineStoreService,
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        SessionRepository $sessionRepository,
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService,
        FlowUserService $flowUserService,
        OrderCommonService $orderCommonService,
        PackageGoodsService $packageGoodsService,
        CartRepository $cartRepository,
        CartGoodsService $cartGoodsService,
        CategoryService $categoryService,
        GoodsProdutsService $goodsProdutsService,
        CouponsService $couponsService,
        BonusService $bonusService,
        ValueCardService $valueCardService,
        ShippingService $shippingService,
        FlowOrderService $flowOrderService,
        CouponsUserService $couponsUserService
    )
    {
        //加载外部类
        $files = [
            'clips',
            'common',
            'main',
            'order',
            'function',
            'base',
            'goods',
            'ecmoban'
        ];
        load_helper($files);
        $this->cartGoodsService = $cartGoodsService;
        $this->categoryService = $categoryService;
        $this->goodsProdutsService = $goodsProdutsService;
        $this->couponsService = $couponsService;
        $this->bonusService = $bonusService;
        $this->valueCardService = $valueCardService;
        $this->timeRepository = $timeRepository;
        $this->userAddressService = $userAddressService;
        $this->teamService = app(TeamService::class);
        $this->shippingService = $shippingService;
        $this->paymentService = $paymentService;
        $this->offlineStoreService = $offlineStoreService;
        $this->baseRepository = $baseRepository;
        $this->wechatService = app(WechatService::class);
        $this->orderService = app(OrderService::class);
        $this->commonRepository = $commonRepository;
        $this->jigonManageService = app(JigonManageService::class);
        $this->dscRepository = $dscRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->groupBuyService = app(GroupBuyService::class);
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->flowUserService = $flowUserService;
        $this->orderCommonService = $orderCommonService;
        $this->packageGoodsService = $packageGoodsService;
        $this->cartRepository = $cartRepository;
        $this->flowOrderService = $flowOrderService;
        $this->couponsUserService = $couponsUserService;
    }

    /**
     * 订单信息确认
     *
     * @param $uid
     * @param int $rec_type
     * @param int $t_id
     * @param int $team_id
     * @param int $bs_id
     * @param int $store_id
     * @return mixed
     * @throws \Exception
     */
    public function OrderInfo($uid, $rec_type = 0, $t_id = 0, $team_id = 0, $bs_id = 0, $store_id = 0, $type_id = 0)
    {
        $lang = lang('common');

        $flow_type = isset($rec_type) ? intval($rec_type) : CART_GENERAL_GOODS;

        // 购物车商品
        $where = [
            'user_id' => $uid,
            'rec_type' => $rec_type,
            'store_id' => $store_id,
            'is_checked' => 1
        ];
        $cart_goods = $this->cartGoodsService->getGoodsCartList($where);

        if (empty($cart_goods)) {
            return [];
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $cbec = app(CrossBorderService::class)->cbecExists();

            if (!empty($cbec)) {
                $excess = $cbec->mobile_check_kj_price($cart_goods);
                if (!empty($excess)) {
                    $store = app(StoreService::class)->getMerchantsStoreInfo($excess['ru_id'], 1);
                    $store_name = $store['check_sellername'] == 0 ? $store['shoprz_brandName'] : ($store['check_sellername'] == 1 ? $store['rz_shopName'] : $store['shop_name']);
                    $msg['error'] = 'excess';
                    $msg['msg'] = $store_name . lang('common.cross_order_excess') . $this->dscRepository->getPriceFormat($excess['limited_amount']);
                    return $msg;
                }
            }
        }

        $list = $this->GoodsInCartByUser($uid, $cart_goods);

        /*收货人地址*/
        $list['consignee'] = $this->userAddressService->getDefaultByUserId($uid);
        if (!isset($list['consignee']['province']) && $store_id <= 0) {
            $msg['error'] = 'address';
            $msg['msg'] = lang('common.address_prompt_two');
            return $msg;
        }

        $list['consignee']['province'] = $list['consignee']['province'] ?? 0;
        $list['consignee']['city'] = $list['consignee']['city'] ?? 0;
        $list['consignee']['district'] = $list['consignee']['district'] ?? 0;

        $list['consignee']['province_name'] = $this->DeliveryArea($list['consignee']['province']);
        $list['consignee']['city_name'] = $this->DeliveryArea($list['consignee']['city']);
        $list['consignee']['district_name'] = $this->DeliveryArea($list['consignee']['district']);

        $result = [];
        foreach ($list['goods_list'] as $key => $value) {
            $result[$value['shop_name']][] = $value;
        }

        $ret = [];
        foreach ($result as $key => $value) {
            array_push($ret, $value);
        }

        $shipping_rec = [];
        $rec_list = [];
        $goods_list = [];
        foreach ($ret as $k => $v) {
            foreach ($v as $key => $val) {
                $goods_list[$k]['shop_name'] = $val['shop_name'];
                $goods_list[$k]['ru_id'] = $val['ru_id'];
                $goods_list[$k]['goods'] = $val['goods'];
                $goods_list[$k]['goods_count'] = $this->SumGoodsNumber($val['goods']);
                $goods_list[$k]['amount'] = $this->dscRepository->getPriceFormat($this->SumGoodsPrice($val['goods']));
                $rec_ids = $this->combination($val['goods']);
                $goods_list[$k]['shipping'] = $this->shippingService->getShippingList($rec_ids, $uid, $val['ru_id'], $list['consignee'], $rec_type);

                //自营有自提点--key=ru_id
                if ($val['ru_id'] == 0 && $list['consignee']['district'] > 0) {
                    $point_id = 0;
                    $self_point = $this->shippingService->getSelfPoint($list['consignee']['district'], $point_id);

                    if (!empty($self_point)) {
                        $goods_list[$k]['self_point'] = $self_point;
                    }
                }

                if (isset($goods_list[$k]['shipping']['shipping_rec']) && $goods_list[$k]['shipping']['shipping_rec']) {
                    $shipping_rec[] = $goods_list[$k]['shipping']['shipping_rec'];
                }

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $cbec = app(CrossBorderService::class)->cbecExists();

                    if (!empty($cbec)) {
                        $list['is_kj'] = 0;
                        $is_kj = $cbec->isKj($val['ru_id']);
                        $list['is_kj'] = empty($is_kj) && $list['is_kj'] == 0 ? 0 : 1;
                    }
                }

                $rec_list[] = $rec_ids ? explode(',', $rec_ids) : [];
            }
        }

        $shipping_rec = $this->baseRepository->getFlatten($shipping_rec);
        $rec_list = $this->baseRepository->getFlatten($rec_list);

        $list['isshipping_list'] = $this->baseRepository->getArrayDiff($rec_list, $shipping_rec); //支持配送购物车商品ID
        $list['isshipping_list'] = $list['isshipping_list'] ? array_values($list['isshipping_list']) : [];

        $list['noshipping_list'] = $shipping_rec; //不支持配送购物车商品ID
        $list['noshipping_list'] = $list['noshipping_list'] ? array_values($list['noshipping_list']) : [];

        $list['goods_list'] = $goods_list;

        $product = [];
        foreach ($list['product'] as $k => $v) {
            $product[$k] = $v['goods'];
        }

        $cart_ru_id = $this->baseRepository->getKeyPluck($cart_goods, 'ru_id');

        $list['coupons_list'] = [];
        if ($this->config['use_coupons'] == 1 && ($flow_type == CART_GENERAL_GOODS || $flow_type == CART_ONESTEP_GOODS || $flow_type == CART_OFFLINE_GOODS)) {
            $list['coupons_list'] = $this->couponsService->getUserCouponsList($uid, true, $list['total'], $cart_goods, true, $cart_ru_id, '', $list['consignee']['province']);

            if ($list['coupons_list']) {
                foreach ($list['coupons_list'] as $k => $v) {
                    $list['coupons_list'][$k]['cou_end_time'] = $this->timeRepository->getLocalDate('Y-m-d', $v['cou_end_time']);
                    $list['coupons_list'][$k]['cou_type_name'] = $v['cou_type'] == VOUCHER_ALL ? $lang['lang_goods_coupons']['all_pay'] : ($v['cou_type'] == VOUCHER_USER ? $lang['lang_goods_coupons']['user_pay'] : ($v['cou_type'] == VOUCHER_SHOPING ? $lang['lang_goods_coupons']['goods_pay'] : ($v['cou_type'] == VOUCHER_LOGIN ? $lang['lang_goods_coupons']['reg_pay'] : ($v['cou_type'] == VOUCHER_SHIPPING ? $lang['lang_goods_coupons']['free_pay'] : $lang['lang_goods_coupons']['not_pay']))));
                    $list['coupons_list'][$k]['cou_goods_name'] = $v['cou_goods'] ? $lang['lang_goods_coupons']['is_goods'] : $lang['lang_goods_coupons']['is_all'];

                    if ($v['spec_cat']) {
                        $list['coupons_list'][$k]['cou_goods_name'] = $lang['lang_goods_coupons']['is_cate'];
                    } elseif ($v['cou_goods']) {
                        $list['coupons_list'][$k]['cou_goods_name'] = $lang['lang_goods_coupons']['is_goods'];
                    } else {
                        $list['coupons_list'][$k]['cou_goods_name'] = $lang['lang_goods_coupons']['is_all'];
                    }
                }
            }
        }

        $list['total']['coupons_count'] = isset($list['coupons_list']) ? count($list['coupons_list']) : 0;
        $list['total']['discount'] = $list['total']['discount'] ?? 0;
        $list['total']['bonus_money'] = 0;
        $list['total']['bonus_id'] = 0;//红包id
        $list['total']['card'] = 0;
        $list['total']['card_money'] = 0;
        $list['total']['coupons_money'] = 0;
        $list['total']['coupons_id'] = 0;//优惠券id
        $list['total']['vc_dis'] = 10;

        if ($list['total']['goods_price'] >= $list['total']['discount']) {
            $list['total']['amount'] = $list['total']['goods_price'] - $list['total']['discount'];
        } else {
            $list['total']['amount'] = 0;
        }

        $list['total']['amount_formated'] = $this->dscRepository->getPriceFormat($list['total']['amount']);
        $list['total']['goods_price_formated'] = $this->dscRepository->getPriceFormat($list['total']['goods_price']);
        $list['total']['integral'] = 0;
        $list['total']['integral_money'] = 0;
        $list['total']['integral_money_formated'] = $this->dscRepository->getPriceFormat($list['total']['integral_money']);
        $list['total']['value_card_id'] = 0;//储值卡id

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $web = app(CrossBorderService::class)->webExists();

            if (!empty($web)) {
                $arr = [
                    'store_id' => $store_id ?? 0
                ];
                $amount = $web->assignNewRatePriceMobile($list['goods_list'], $list['total']['amount'], $arr);
                $list['total']['amount'] = $amount['amount'];
                $list['total']['amount_formated'] = $amount['amount_formated'];
                $list['total']['rate_price'] = $amount['rate_price'];
                $list['total']['rate_formated'] = $amount['format_rate_fee'];
            }
        }

        //积分商城商品添加商品对应的积分
        $list['total']['exchange_integral'] = 0;
        if ($rec_type == CART_EXCHANGE_GOODS) {
            $list['total']['exchange_integral'] = ExchangeGoods::where('goods_id', $cart_goods[0]['goods_id'])->value('exchange_integral');
        }

        $cart_value = [];
        foreach ($cart_goods as $k => $v) {
            $cart_value[$k] = $v['rec_id'];
        }

        /* 取得货到付款手续费 */
        $cod_fee = 0;
        // 显示余额支付
        $is_balance = 1;

        /*取得支付列表*/
        $payment_list = $this->paymentService->availablePaymentList(1, $cod_fee, 0, $is_balance);

        if ($payment_list) {
            foreach ($payment_list as $key => $payment) {
                /* 如果积分商城商品、拼团商品、虚拟商品不显示货到付款则不显示 */
                if ($flow_type == CART_EXCHANGE_GOODS || $flow_type == CART_TEAM_GOODS || $list['total']['real_goods_count'] == 0) {
                    if ($payment ['pay_code'] == 'cod') {
                        unset($payment_list[$key]);
                    }
                }

                unset($payment_list[$key]['pay_config']);
            }
            $list['payment_list'] = $payment_list;
        }

        // 红包
        $list['bonus_list'] = [];
        if ($cart_value && $this->config['use_bonus'] == 1 && ($flow_type == CART_GENERAL_GOODS || $flow_type == CART_ONESTEP_GOODS)) {
            $cart_ru_id = implode(',', $cart_ru_id);
            $list['bonus_list'] = $this->bonusService->getUserBonusInfo($uid, $list['total']['goods_price'], $cart_value, $list['total']['seller_amount'], $cart_ru_id);//可用的红包列表
            foreach ($list['bonus_list'] as $k => $val) {
                $list['bonus_list'][$k]['use_start_date'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['use_start_date']);
                $list['bonus_list'][$k]['use_end_date'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['use_end_date']);
                unset($list['bonus_list'][$k]['get_bonus_type']);
            }
        }

        $user_info = $this->UserIntegral($uid);

        // 消费积分使用
        $list['allow_use_integral'] = 0;
        $list['integral'] = [];
        if ($this->config['use_integral'] == 1 && ($flow_type == CART_GENERAL_GOODS || $flow_type == CART_ONESTEP_GOODS)) {
            $order_integral = $this->AmountIntegral($uid, $rec_type, $store_id);
            if ($order_integral > 0) {
                $order_point = $this->dscRepository->integralOfValue($order_integral);

                if ($user_info['pay_points'] >= $order_point) {
                    $list['integral'][0]['integral'] = $order_point;
                    $list['integral'][0]['integral_money'] = $this->dscRepository->valueOfIntegral($order_point);
                    $list['integral'][0]['integral_money_formated'] = $this->dscRepository->getPriceFormat($list['integral'][0]['integral_money']);
                }

                $list['allow_use_integral'] = empty($list['integral']) ? 0 : 1;
            }
        }

        /*判断储值卡能否使用*/
        $list['use_value_card'] = 0;
        $list['value_card'] = [];
        if ($this->config['use_value_card'] == 1) {//&& ($flow_type == CART_GENERAL_GOODS || $flow_type == CART_ONESTEP_GOODS)
            $list['value_card'] = $this->valueCardService->getUserValueCard($uid, $product);
            $list['use_value_card'] = empty($list['value_card']) ? 0 : 1;
        }

        /*判断余额是否足够*/
        $list['use_surplus'] = 0;
        if ($this->config['use_surplus'] == 1) {
            $use_surplus = $user_info['user_money'] ?? 0;
            $shipping_fee = 0;
            foreach ($list['goods_list'] as $v) {
                if (isset($v['shipping']['default_shipping']['shipping_fee'])) {
                    $shipping_fee += $v['shipping']['default_shipping']['shipping_fee'];
                }
            }
            //if ($use_surplus > ($list['total']['goods_price'] + $shipping_fee) && empty($list['is_kj'])) {
            if ($use_surplus > 0 && empty($list['is_kj'])) {
                $list['use_surplus'] = 1;
                $list['user_money'] = $use_surplus;  // 账户余额
                $list['user_money_formated'] = $this->dscRepository->getPriceFormat($use_surplus);
            }
        }

        // 如果开启用户支付密码
        $list['use_paypwd'] = 0;
        if ($this->config['use_paypwd'] == 1) {
            // 可使用余额，且用户有余额 或  能使用储值卡  显示支付密码
            if ($list['use_surplus'] == 1 || $list['use_value_card'] == 1) {
                $list['use_paypwd'] = 1;
            }
        }

        /*判断门店自提*/
        $list['store_lifting'] = 0;
        if ($product) {
            foreach ($product as $k => $v) {
                if ($v['store_id'] > 0) {
                    $list['store'] = $this->offlineStoreService->infoOfflineStore($v['store_id']);
                    $list['store_lifting'] = 1;
                    break;
                }
            }
        }
        // 门店自提时间与手机号
        $store_cart = Cart::select('store_mobile', 'take_time')->whereIn('rec_id', $cart_value)->where('store_id', $store_id)->first();
        $list['store_cart'] = $store_cart ? $store_cart->toArray() : [];

        $list['can_invoice'] = 0;
        /*是否支持开发票*/
        if ($this->config['can_invoice'] == 1) {
            $list['can_invoice'] = 1;
        }

        $list['how_oos'] = 0;
        /*支持缺货处理*/
        if ($this->config['use_how_oos'] == 1) {
            $list['how_oos'] = 1;
        }

        // 购物车商品类型
        $list['flow_type'] = $flow_type;

        //砍价返回标识
        if ($bs_id) {
            $list['bs_id'] = $bs_id; //砍价参与id
        }

        //拼团返回标识
        if ($t_id) {
            $list['t_id'] = $t_id;       //拼团活动id
            $list['team_id'] = $team_id; //拼团开团id
        }

        // 团购支付保证金标识
        $list['is_group_deposit'] = 0;
        if ($flow_type == 1) {
            $group_buy = $this->groupBuyService->getGroupBuyInfo(['group_buy_id' => $type_id]);
            if (isset($group_buy) && $group_buy['deposit'] > 0) {
                $list['is_group_deposit'] = 1;
            }
        }

        //发票内容
        if ($this->config['can_invoice'] == 1) {
            $list['invoice_content'] = explode("\n", str_replace("\r", '', $this->config['invoice_content']));
        }

        return $list;
    }

    /**
     * 提交订单
     * @param int $uid
     * @param array $flow
     * @return array
     */
    public function Done($uid = 0, $flow = [])
    {
        $done_cart_value = $flow['cart_value'];

        /* 取得购物类型 */
        $flow_type = isset($flow['flow_type']) ? intval($flow['flow_type']) : CART_GENERAL_GOODS;
        $flow_type = ($flow['flow_type'] == CART_ONESTEP_GOODS) ? CART_ONESTEP_GOODS : $flow_type;
        $store_id = isset($flow['store_id']) ? intval($flow['store_id']) : 0;  // 门店id

        /* 检查购物车中是否有商品 */
        $cart_row = Cart::where('parent_id', 0)
            ->where('is_gift', 0)
            ->where('rec_type', $flow_type)
            ->where('user_id', $uid)
            ->where('is_checked', 1)
            ->where('store_id', $store_id)
            ->count();

        if (empty($cart_row)) {
            return ['error' => 1, 'msg' => lang('flow.mobile_null_goods')];
        }

        /* 检查商品库存 */
        /* 如果使用库存，且下订单时减库存，则减少库存 */
        //--库存管理use_storage 1为开启 0为未启用-------  SDT_PLACE：0为发货时 1为下订单时

        if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PLACE) {
            $cart_goods_stock = get_cart_goods($done_cart_value, $flow_type, $flow['warehouse_id'], $flow['area_id'], $flow['area_city'], $uid);

            $_cart_goods_stock = [];
            foreach ($cart_goods_stock['goods_list'] as $value) {
                $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
            }

            $result_stock = $this->get_flow_cart_stock($_cart_goods_stock, $store_id, $flow['warehouse_id'], $flow['area_id'], $flow['area_city'], $uid, $flow_type);
            unset($cart_goods_stock, $_cart_goods_stock);

            if ($result_stock) {
                return $result_stock;
            }
        }

        /* 订单队列 先进先出 */
        $order_fifo = $this->orderService->order_fifo($uid, $done_cart_value);
        if ($order_fifo['error'] > 0) {
            return ['error' => 1, 'msg' => lang('flow.flow_salve_error')];
        }

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($uid);

        $consignee = $this->userAddressService->getDefaultByUserId($uid);
        $consignee['country'] = $consignee['country'] ?? 0;
        $consignee['province'] = $consignee['province'] ?? 0;
        $consignee['city'] = $consignee['city'] ?? 0;
        $consignee['district'] = $consignee['district'] ?? 0;

        /* 检查收货人信息是否完整 */
        if (!$this->flowUserService->checkConsigneeInfo($consignee, $flow_type, $uid) && $store_id <= 0) {
            return ['error' => 1, 'msg' => lang('common.not_set_address')];
        }
        $warehouse_id = get_province_id_warehouse($consignee['province']);

        $area_info = RegionWarehouse::select('region_id', 'regionId', 'region_name', 'parent_id')->where('regionId', $consignee['province'])->first();
        $area_info = $area_info ? $area_info->toArray() : [];

        $area_city = RegionWarehouse::select('region_id', 'regionId', 'region_name', 'parent_id')->where('regionId', $consignee['city'])->first();
        $area_city = $area_city ? $area_city->toArray() : [];

        $area_info['region_id'] = isset($area_info['region_id']) ? $area_info['region_id'] : 0;
        $area_city['region_id'] = isset($area_city['region_id']) ? $area_city['region_id'] : 0;

        $total['how_oos'] = isset($flow['how_oos']) ? intval($flow['how_oos']) : 0;
        $total['card_message'] = isset($flow['card_message']) ? $this->compile_str($flow['card_message']) : '';
        $total['inv_type'] = !empty($flow['inv_type']) ? $this->compile_str($flow['inv_type']) : '';
        $total['inv_payee'] = isset($flow['inv_payee']) ? $this->compile_str($flow['inv_payee']) : '';
        $total['tax_id'] = isset($flow['tax_id']) ? $this->compile_str($flow['tax_id']) : '';
        $total['inv_content'] = isset($flow['inv_content']) ? $this->compile_str($flow['inv_content']) : '';

        $msg = $flow['postscript'];
        $ru_id_arr = $flow['ru_id']; // 商家id数组

        if (!is_array($ru_id_arr) && $ru_id_arr == 0) {
            $ru_id_arr = explode(',', $ru_id_arr);
        } else {
            $ru_id_arr = $this->baseRepository->getExplode($ru_id_arr);
        }

        $shipping_arr = $flow['shipping'] ? $flow['shipping'] : []; // 配送方式数组
        $shipping_type = $flow['shipping_type'] ? $flow['shipping_type'] : [];
        $shipping_code = $flow['shipping_code'] ? $flow['shipping_code'] : [];

        $point_id = $flow['point_id'];
        $shipping_dateStr = $flow['shipping_dateStr'];

        // 分单配送方式
        if ($shipping_arr && count($shipping_arr) == 1) {
            $shipping['shipping_id'] = $shipping_arr[0] ?? 0;
            $shipping['shipping_type'] = $shipping_type[0] ?? 0;
            $shipping['shipping_code'] = $shipping_code[0] ?? 0;
        } else {
            $shipping = $this->get_order_post_shipping($shipping_arr, $shipping_code, $shipping_type, $ru_id_arr);
        }

        // 分单上门自提
        $point_info = [];
        if ($ru_id_arr && count($ru_id_arr) == 1) {
            $point_info['point_id'] = $point_id[0] ?? 0;
            $point_info['shipping_dateStr'] = $shipping_dateStr[0] ?? '';
        } else {
            $point_info = $this->get_order_points($point_id, $shipping_dateStr, $ru_id_arr);
        }

        // 分单买家留言
        if ($msg && count($msg) == 1) {
            $postscript = isset($msg['0']) ? $msg[0] : '';
        } else {
            $postscript = $this->get_order_post_postscript($msg, $ru_id_arr);
        }

        $time = $this->timeRepository->getGmTime();

        $order = [
            'shipping_id' => empty($shipping['shipping_id']) ? 0 : $shipping['shipping_id'],
            'shipping_type' => empty($shipping['shipping_type']) ? 0 : $shipping['shipping_type'],
            'shipping_code' => empty($shipping['shipping_code']) ? 0 : $shipping['shipping_code'],
            'support_cod' => empty($shipping['support_cod']) ? 0 : $shipping['support_cod'],
            'pay_id' => intval($flow['pay_id']),
            'pack_id' => isset($flow['pack']) ? intval($flow['pack']) : 0,
            'card_id' => isset($flow['card']) ? intval($flow['card']) : 0,
            'card_message' => trim($flow['card_message']),
            'surplus' => isset($flow['surplus']) ? floatval($flow['surplus']) : 0.00,
            'integral' => isset($flow['integral']) ? intval($flow['integral']) : 0,
            'use_integral' => isset($flow['use_integral']) ? intval($flow['use_integral']) : 0,
            'is_surplus' => isset($flow['is_surplus']) ? intval($flow['is_surplus']) : 0,
            'bonus_id' => isset($flow['bonus_id']) ? $flow['bonus_id'] : 0,
            'bonus' => isset($flow['bonus']) ? $flow['bonus'] : 0,
            'uc_id' => isset($flow['uc_id']) ? $flow['uc_id'] : 0, //优惠券id bylu
            'vc_id' => isset($flow['vc_id']) ? $flow['vc_id'] : 0, //储值卡ID
            'need_inv' => empty($flow['need_inv']) ? 0 : 1,
            'tax_id' => isset($flow['tax_id']) ? trim($flow['tax_id']) : '', //纳税人识别号
            'inv_type' => isset($flow['inv_type']) ? $flow['inv_type'] : 1,
            'inv_payee' => isset($flow['inv_payee']) ? trim($flow['inv_payee']) : '个人',
            'invoice_id' => isset($flow['invoice_id']) ? $flow['invoice_id'] : 0,
            'invoice' => isset($flow['invoice']) ? $flow['invoice'] : 1,
            'invoice_type' => isset($flow['inv_type']) ? $flow['inv_type'] : 1,
            'inv_content' => isset($flow['inv_content']) ? trim($flow['inv_content']) : '不开发票',
            'vat_id' => isset($flow['vat_id']) ? $flow['vat_id'] : 0,
            'postscript' => empty($postscript) ? '' : $postscript,
            'how_oos' => isset($GLOBALS['_LANG']['oos'][$flow['how_oos']]) ? addslashes($GLOBALS['_LANG']['oos'][$flow['how_oos']]) : '',
            'need_insure' => isset($flow['need_insure']) ? intval($flow['need_insure']) : 0,
            'user_id' => $uid,
            'add_time' => $time,
            'order_status' => OS_CONFIRMED,
            'shipping_status' => SS_UNSHIPPED,
            'pay_status' => PS_UNPAYED,
            'agency_id' => $this->get_agency_by_regions([$consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']]),
            'point_id' => empty($point_info['point_id']) ? 0 : $point_info['point_id'],
            'shipping_dateStr' => empty($point_info['shipping_dateStr']) ? '' : $point_info['shipping_dateStr'],
            'mobile' => isset($flow['store_mobile']) && !empty($flow['store_mobile']) ? addslashes(trim($flow['store_mobile'])) : '',
            'referer' => !empty($flow['referer']) ? $flow['referer'] : 'H5', // 订单来源
        ];


        if (CROSS_BORDER === true) // 跨境多商户
        {
            $order['rel_name'] = isset($flow['rel_name']) ? $flow['rel_name'] : '';
            $order['id_num'] = isset($flow['id_num']) ? $flow['id_num'] : '';
            if (!empty($order['rel_name']) && !empty($order['id_num'])) {
                // 实名认证验证
                $cbecService = app(CrossBorderService::class)->cbecExists();
                $real_data = [
                    'rel_name' => $order['rel_name'],
                    'id_num' => $order['id_num'],
                ];
                $config['identity_auth_status'] = $this->config['identity_auth_status'] ?? 0;
                // 开启验证
                if ($config['identity_auth_status'] == 1) {
                    // 未通过或编辑时必验证
                    $check = false;
                    // 判断修改实名信息时
                    if (empty($consignee['rel_name']) || empty($consignee['id_num']) || $consignee['rel_name'] != $order['rel_name'] || $consignee['id_num'] != $order['id_num']) {
                        $check = true;
                    }
                    // 验证
                    if ((isset($consignee['real_status']) && $consignee['real_status'] == 0) || $check == true) {
                        // 请求接口
                        $res = $cbecService->checkIdentity($order['rel_name'], $order['id_num']);
                        if ($res == true) {
                            // 保存为已实名认证
                            $real_data['real_status'] = 1;
                        } else {
                            // 实名认证信息不匹配
                            return ['error' => 1, 'msg' => lang('flow.user_real_info_error')];
                        }
                    }
                }
                $consignee['address_id'] = isset($flow['address_id']) && !empty($flow['address_id']) ? $flow['address_id'] : $consignee['address_id'];
                $cbecService->updateUserAddress($uid, $consignee['address_id'], $real_data);
                // 保存订单
                $consignee['rel_name'] = $order['rel_name'];
                $consignee['id_num'] = $order['id_num'];
            }
        }

        /* 扩展信息 */
        if (isset($flow['flow_type']) && $flow_type != CART_GENERAL_GOODS && $flow_type != CART_ONESTEP_GOODS) {
            $order['extension_code'] = $flow['extension_code'];
            $order['extension_id'] = $flow['extension_id'];
        } else {
            $order['extension_code'] = '';
            $order['extension_id'] = 0;
        }

        if ($flow_type == CART_BARGAIN_GOODS) { // 砍价
            $order['extension_code'] = 'bargain_buy';
        }
        if ($flow_type == CART_TEAM_GOODS) {// 拼团
            $order['extension_code'] = 'team_buy';
        }
        if ($flow_type == CART_SECKILL_GOODS) {// 秒杀
            $order['extension_code'] = 'seckill';
        }
        if ($flow_type == CART_GROUP_BUY_GOODS) {//团购
            $order['extension_code'] = 'group_buy';
        }
        if ($flow_type == CART_EXCHANGE_GOODS) {// 积分兑换
            $order['extension_code'] = 'exchange_goods';
        }
        if ($flow_type == CART_PRESALE_GOODS) {// 预售
            $order['extension_code'] = 'presale';
        }
        if ($flow_type == CART_AUCTION_GOODS) {// 拍卖
            $order['extension_code'] = 'auction';
        }

        $user_info = $this->UserIntegral($uid);

        /* 检查积分余额是否合法 */
        $user_id = $uid;
        if ($user_id > 0) {

            $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
            if ($order['surplus'] < 0) {
                $order['surplus'] = 0;
            }

            // 该订单允许使用的积分
            $flow_points = $this->flow_available_points($flow['cart_value'], $flow_type, $warehouse_id, $area_info['region_id'], $area_city['region_id'], $user_id);
            $user_points = $user_info['pay_points']; // 用户的积分总数

            $order['integral'] = min($order['integral'], $user_points, $flow_points);

            if ($order['integral'] < 0) {
                $order['integral'] = 0;
            }
        } else {
            $order['surplus'] = 0;
            $order['integral'] = 0;
        }

        //未开启使用积分，积分归0
        if ($flow['use_integral'] == 0) {
            $order['integral'] = 0;
        }

        $cartWhere = [
            'user_id' => $uid,
            'include_gift' => true,
            'rec_type' => $flow_type,
            'cart_value' => $done_cart_value
        ];
        $cart_total = $this->cartCommonService->getCartAmount($cartWhere);

        /* 检查红包是否存在 */
        if ($order['bonus_id'] > 0) {
            $bonus = $this->bonus_info($order['bonus_id']);
            $bonus['min_goods_amount'] = $bonus['min_goods_amount'] ?? 0;
            if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > $cart_total) {
                $order['bonus_id'] = 0;
            }
        } elseif (isset($flow['bonus_sn'])) {
            $bonus_sn = trim($flow['bonus_sn']);
            $bonus = $this->bonus_info(0, $bonus_sn);
            if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > $cart_total || $time > $bonus['use_end_date']) {
            } else {
                if ($user_id > 0) {
                    UserBonus::where('bonus_id', $bonus['bonus_id'])->update(['user_id' => $user_id]);
                }
                $order['bonus_id'] = $bonus['bonus_id'];
                $order['bonus_sn'] = $bonus_sn;
            }
        }

        /* 检查储值卡ID是否存在 */
        if ($order['vc_id'] > 0) {
            $value_card = value_card_info($order['vc_id']);

            if (empty($value_card) || $value_card['user_id'] != $user_id) {
                $order['vc_id'] = 0;
            }
        } elseif (isset($flow['value_card_psd'])) {
            $value_card_psd = trim($flow['value_card_psd']);
            $value_card = value_card_info(0, $value_card_psd);
            if (!(empty($value_card) || $value_card['user_id'] > 0)) {
                if ($user_id > 0 && empty($value_card['end_time'])) {
                    $end_time = ", end_time = '" . $this->timeRepository->getLocalStrtoTime("+" . $value_card['vc_indate'] . " months ") . "' ";
                    ValueCard::where('vid', $value_card['vid'])->update(['user_id' => $user_id, 'bind_time' => $time . "'" . $end_time]);
                    $order['vc_id'] = $value_card['vid'];
                    $order['vc_psd'] = $value_card_psd;
                } elseif ($time > $value_card['end_time']) {
                    $order['vc_id'] = 0;
                }
            }
        }

        /* 检查优惠券是否存在 bylu */
        if ($order['uc_id'] > 0) {
            $coupons = $this->couponsUserService->getCoupons($order['uc_id'], -1, $user_id);
            if (empty($coupons) || $coupons['user_id'] != $user_id || $coupons['is_use'] == 1 || $coupons['cou_man'] > $cart_total) {
                $order['uc_id'] = 0;
            }
        }

        $cart_goods_list = cart_goods($flow_type, $done_cart_value, 1, $warehouse_id, $area_info['region_id'], $area_city['region_id'], $consignee, $store_id, $uid); // 取得商品列表，计算合计

        if (empty($cart_goods_list)) {
            return ['error' => 1, 'msg' => lang('flow.mobile_cartnot_goods')];
        }

        $cart_goods = $this->shippingService->get_new_group_cart_goods($cart_goods_list); // 取得商品列表，计算合计

        // 开通购买会员权益卡验证
        if (file_exists(MOBILE_DRP) && isset($flow['order_membership_card_id']) && $flow['order_membership_card_id'] > 0) {
            $memberCardInfo = app(DiscountRightsService::class)->getMemberCardInfo($user_id, $flow['order_membership_card_id']);
            if (empty($memberCardInfo)) {
                $flow['order_membership_card_id'] = 0;
            } else {
                if (isset($memberCardInfo['membership_card_order_goods']) && $memberCardInfo['membership_card_order_goods']) {
                    // 合并订单商品数组 用于分别记录订单商品权益卡折扣
                    $cart_goods = merge_arrays($cart_goods, $memberCardInfo['membership_card_order_goods']);
                }
            }
        }

        /* 检查商品总额是否达到最低限购金额 */
        if (($flow_type == CART_GENERAL_GOODS || $flow_type == CART_ONESTEP_GOODS) && $cart_total < $this->config['min_goods_amount']) {
            return ['error' => 1, 'msg' => lang('flow.not_meet_low_purchase_limit')];
        }
        /* 收货人信息 */
        foreach ($consignee as $key => $value) {
            if ($key == 'mobile' && !empty($order['mobile'])) {
                $order[$key] = $order['mobile'];  //门店取货手机号
            } else {
                $order[$key] = !empty($value) ? addslashes($value) : '';
            }
        }

        /* 判断是不是实体商品 */
        foreach ($cart_goods as $val) {
            /* 统计实体商品的个数 */
            if (isset($val['is_real']) && $val['is_real']) {
                $is_real_good = 1;
            }
        }

        // 虚拟商品不用选择配送方式
        if (isset($is_real_good)) {
            if (empty($order['shipping_id']) && empty($point_id) && empty($store_id)) {
                return ['error' => 1, 'msg' => lang('flow.please_checked_shipping')];
            }
            if (empty($order['pay_id'])) {
                return ['error' => 1, 'msg' => lang('flow.please_checked_pay')];
            }
        }

        /* 支付方式 */
        $payment = [];
        if ($order['pay_id'] > 0) {
            $payment = payment_info($order['pay_id']);
            $order['pay_name'] = addslashes($payment['pay_name']);
            $order['is_online'] = 1;
        }

        // 验证支付密码
        $pay_pwd = trim($flow['pay_pwd']);

        $users_paypwd = UsersPaypwd::where('user_id', $uid);
        $users_paypwd = $this->baseRepository->getToArrayFirst($users_paypwd);
        // 开启配置支付密码 且使用余额 或 使用储值卡 验证支付密码
        if ($this->config['use_paypwd'] == 1 && ($payment['pay_code'] == 'balance' || $order['vc_id'] > 0 || $order['is_surplus'] == 1)) {
            if (empty($users_paypwd)) {
                // 请启用支付密码
                return ['error' => 1, 'msg' => lang('flow.paypwd_must_open'), 'url' => dsc_url('/#/user/accountsafe')];
            } else {
                if (empty($pay_pwd)) {
                    return ['error' => 1, 'msg' => lang('flow.paypwd_empty')];
                } else {
                    // 支付密码长度限制6位数字
                    if (strlen($pay_pwd) != 6) {
                        return ['error' => 1, 'msg' => lang('flow.paypwd_length_limit')];
                    }
                    $new_password = md5(md5($pay_pwd) . $users_paypwd['ec_salt']);
                    if ($new_password != $users_paypwd['pay_password']) {
                        return ['error' => 1, 'msg' => lang('flow.pay_password_packup_error')];
                    }
                }
            }
        }

        /*
         * 计算订单的费用
         */
        $type = array(
            'type' => 0,
            'shipping_list' => $shipping_arr,
            'step' => 0,
        );

        $total = order_fee($order, $cart_goods, $consignee, $type, $done_cart_value, 0, $cart_goods_list, 0, 0, $store_id, $flow['store_type'], $uid, $user_rank['rank_id'], $flow_type);


        if (CROSS_BORDER === true) // 跨境多商户
        {
            $web = app(CrossBorderService::class)->webExists();

            if (!empty($web)) {
                $arr = [
                    'consignee' => $consignee ?? '',
                    'rec_type' => $flow_type ?? 0,
                    'store_id' => $store_id ?? 0,
                    'cart_value' => $done_cart_value ?? '',
                    'type' => $type ?? 0,
                    'uc_id' => $order['uc_id'] ?? 0
                ];
                $amount = $web->assignNewRatePriceMobileDone($cart_goods_list, $total['amount'], $arr);
                $total['amount'] = $amount['amount'];
                $total['amount_formated'] = $amount['amount_formated'];
                $order['rate_fee'] = $amount['rate_price'];
                $order['format_rate_fee'] = $amount['format_rate_fee'];
            }
        }

        // 开通购买会员权益卡 应付金额 = 原应付金额 - 折扣差价 + 购买权益卡金额
        if (file_exists(MOBILE_DRP) && isset($flow['order_membership_card_id']) && $flow['order_membership_card_id'] > 0) {
            if (isset($memberCardInfo) && !empty($memberCardInfo)) {
                $total['amount'] = $total['amount'] - $flow['membership_card_discount_price'] + $memberCardInfo['membership_card_buy_money'];
            }
        }

        $order['bonus'] = isset($total['bonus']) ? $total['bonus'] : 0;
        $order['coupons'] = isset($total['coupons']) ? $total['coupons'] : 0; //优惠券金额 bylu
        $order['use_value_card'] = isset($total['use_value_card']) ? $total['use_value_card'] : 0; //储值卡使用金额
        $order['goods_amount'] = $total['goods_price'];
        $order['cost_amount'] = isset($total['cost_price']) ? $total['cost_price'] : 0;
        $order['discount'] = $total['discount'] ? $total['discount'] : 0;
        $order['surplus'] = isset($total['surplus']) ? $total['surplus'] : 0;
        $order['tax'] = isset($total['tax']) ? $total['tax'] : 0;

        // 购物车中的商品能享受红包支付的总额
        $discount_amout = compute_discount_amount($flow['cart_value'], $uid, $flow_type);

        // 红包和积分最多能支付的金额为商品总额
        $temp_amout = $order['goods_amount'] - $discount_amout;
        if ($temp_amout <= 0) {
            $order['bonus_id'] = 0;
        }

        /* 配送方式 ecmoban模板堂 --zhuo */
        if (!empty($order['shipping_id'])) {
            if (count($shipping_arr) == 1) {
                $shipping = shipping_info($order['shipping_id']);
            }
            $order['shipping_isarr'] = 0;
            $order['shipping_name'] = addslashes($shipping['shipping_name']);
            $order['shipping_code'] = addslashes($shipping['shipping_code']);
            $shipping_name = !empty($order['shipping_name']) ? explode(",", $order['shipping_name']) : '';
            if ($shipping_name && count($shipping_name) > 1) {
                $order['shipping_isarr'] = 1;
            }
        }

        $order['shipping_fee'] = isset($total['shipping_fee']) ? $total['shipping_fee'] : 0;
        $order['insure_fee'] = isset($total['shipping_insure']) ? $total['shipping_insure'] : 0;

        $order['pay_fee'] = isset($total['pay_fee']) ? $total['pay_fee'] : 0;
        $order['cod_fee'] = isset($total['cod_fee']) ? $total['cod_fee'] : 0;

        /* 商品包装 */
        if ($order['pack_id'] > 0) {
            $pack = pack_info($order['pack_id']);
            $order['pack_name'] = addslashes($pack['pack_name']);
        }
        $order['pack_fee'] = isset($total['pack_fee']) ? $total['pack_fee'] : 0;

        /* 祝福贺卡 */
        if ($order['card_id'] > 0) {
            $card = card_info($order['card_id']);
            $order['card_name'] = addslashes($card['card_name']);
        }
        $order['card_fee'] = isset($total['card_fee']) ? $total['card_fee'] : 0;

        $order['order_amount'] = number_format($total['amount'], 2, '.', '');

        $snapshot = false; // 是否创建快照

        // 在线支付输入了一个金额(含部分使用余额),检查余额是否足够
        if ($order['is_surplus'] == 1 && $order['surplus'] > 0) {
            if ($order['surplus'] > ($user_info['user_money'] + $user_info['credit_line'])) {
                return ['error' => 1, 'msg' => lang('flow.balance_not_enough')];
            }
        }

        /* 如果全部使用余额支付，检查余额是否足够 */
        if ($payment['pay_code'] == 'balance' && $order['order_amount'] > 0) {
            if ($order['surplus'] > 0) { //余额支付里如果输入了一个金额
                $order['order_amount'] = $order['order_amount'] + $order['surplus'];
                $order['surplus'] = 0;
            }

            if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line'])) {
                $order['surplus'] = $user_info['user_money'];
                $order['order_amount'] = $order['order_amount'] - $user_info['user_money'];
                return ['error' => 1, 'msg' => lang('shopping_flow.balance_not_enough')];
            } else {
                if ($flow['flow_type'] == CART_PRESALE_GOODS) {
                    //预售--首次付定金
                    $order['surplus'] = $order['order_amount'];
                    $order['pay_status'] = PS_PAYED_PART; //部分付款
                    $order['order_status'] = OS_CONFIRMED; //已确认
                    $order['order_amount'] = $order['goods_amount'] + $order['shipping_fee'] + $order['insure_fee'] + $order['tax'] - $order['discount'] - $order['surplus'];
                } else {
                    $order['surplus'] = $order['order_amount'];
                    $order['order_amount'] = 0;
                }
            }
            //$payment = payment_info('balance', 1);
            $order['pay_name'] = isset($payment['pay_name']) ? addslashes($payment['pay_name']) : '';
            $order['pay_id'] = $payment['pay_id'] ?? 0;
            $order['is_online'] = 0;
        }

        $stores_sms = 0; //门店提货码是否发送信息 0不发送  1发送
        /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0) {
            $order['order_status'] = OS_CONFIRMED;
            $order['confirm_time'] = $time;
            $order['pay_status'] = PS_PAYED;
            $order['pay_time'] = $time;
            $order['order_amount'] = 0;
            $stores_sms = 1;
            $snapshot = true;
        }

        $order['integral_money'] = $total['integral_money'];
        $order['integral'] = $total['integral'];
        if ($order['extension_code'] == 'exchange_goods') {
            $order['integral_money'] = $this->dscRepository->valueOfIntegral($total['exchange_integral']);
            $order['integral'] = $total['exchange_integral'];
            $order['goods_amount'] = 0;
        }
        $order['from_ad'] = '';

        $affiliate = unserialize($this->config['affiliate']);
        if (isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1) {
            //推荐订单分成
            $parent_id = $this->commonRepository->getUserAffiliate();
            if ($user_id == $parent_id) {
                $parent_id = 0;
            }
        } elseif (isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0) {
            //推荐注册分成
            $parent_id = 0;
        } else {
            //分成功能关闭
            $parent_id = 0;
        }

        // 微分销
        $is_distribution = 0;
        if (file_exists(MOBILE_DRP) && $order['extension_code'] == '') {
            // 订单分销条件
            $result = app(DrpService::class)->orderAffiliate($uid);

            // 是否分销
            $is_distribution = $result['is_distribution'] ?? 0;
            $parent_id = $result['parent_id'] ?? 0; // 推荐人id
        }

        $order['parent_id'] = $parent_id;
        $order['email'] = $user_info['email'] ?? '';

        /* 插入拼团信息记录 sty */
        if ($flow_type == CART_TEAM_GOODS) {
            $order['team_parent_id'] = 0;
            $order ['team_user_id'] = 0;
            if ($flow['team_id'] > 0) {
                $team_info = TeamLog::where('team_id', $flow['team_id'])
                    ->first();
                $team_info = $team_info ? $team_info->toArray() : [];
                if (isset($team_info['status']) && $team_info['status'] > 0) {//参与拼团人数溢出时，开启新的团
                    $team_doods = Cart::where('parent_id', 0)
                        ->where('is_gift', 0)
                        ->where('rec_type', $flow_type)
                        ->where('user_id', $uid)
                        ->where('is_checked', 1)
                        ->where('parent_id', 0)
                        ->first()
                        ->toArray();
                    $team['t_id'] = $flow['t_id'];//拼团活动id
                    $team['goods_id'] = $team_doods['goods_id'];//拼团商品id
                    $team['start_time'] = $time;
                    $team['status'] = 0;
                    // 插入开团活动信息
                    $team_log_id = $this->teamService->addTeamLog($team);
                    $order['team_id'] = $team_log_id;
                    $order['team_parent_id'] = $uid;
                } else {
                    $order ['team_id'] = $flow['team_id'];
                    $order ['team_user_id'] = $uid;
                }
            } else {
                $team_doods = Cart::where('parent_id', 0)
                    ->where('is_gift', 0)
                    ->where('rec_type', $flow_type)
                    ->where('user_id', $uid)
                    ->where('is_checked', 1)
                    ->first();
                $team_doods = $team_doods ? $team_doods->toArray() : [];

                $team['t_id'] = $flow['t_id'];//拼团活动id
                $team['goods_id'] = $team_doods['goods_id'];//拼团商品id
                $team['start_time'] = $time;
                $team['status'] = 0;
                // 插入开团活动信息
                $team_log_id = $this->teamService->addTeamLog($team);
                $order['team_id'] = $team_log_id;
                $order['team_parent_id'] = $uid;
            }
        }
        /* 插入拼团信息记录 end */

        /* 插入订单表 */
        $new_order_id = 0;
        if ($cart_goods) {
            $error_no = 0;
            do {
                $order['order_sn'] = $this->orderCommonService->getOrderSn(); //获取新订单号
                $new_order = $this->baseRepository->getArrayfilterTable($order, 'order_info');
                try {
                    $new_order_id = OrderInfo::insertGetId($new_order);
                } catch (\Exception $e) {
                    $error_no = (stripos($e->getMessage(), '1062 Duplicate entry') !== false) ? 1062 : $e->getCode();

                    if ($error_no > 0 && $error_no != 1062) {
                        die($e->getMessage());
                    }
                }
            } while ($error_no == 1062); //如果是订单号重复则重新提交数据
        }

        $order['order_id'] = $new_order_id;

        $order_rec = [];
        if ($new_order_id > 0) {

            /* 订单优惠券均摊到订单商品， 检测优惠券是否支持商品参与均摊 start */
            $couponsGoods = [];
            $couponSubtotal = 0;
            if (isset($order['coupons']) && $order['coupons'] > 0) {
                $coupons = Coupons::whereHas('getCouponsUser', function ($query) use ($order) {
                    $query->where('uc_id', $order['uc_id']);
                });
                $coupons = $this->baseRepository->getToArrayFirst($coupons);

                if ($coupons) {
                    $is_coupons = 0;
                    if (empty($coupons['cou_goods']) && empty($coupons['spec_cat'])) {
                        $is_coupons = 1;
                    } elseif (!empty($coupons['cou_goods']) && empty($coupons['spec_cat'])) {
                        $is_coupons = 2;
                    } elseif (empty($coupons['cou_goods']) && !empty($coupons['spec_cat'])) {
                        $is_coupons = 3;
                    }

                    $couponsSeller = $coupons['ru_id'];

                    $cou_goods = $this->baseRepository->getExplode($coupons['cou_goods']);
                    $spec_cat = $this->baseRepository->getExplode($coupons['spec_cat']);

                    $catList = [];
                    if ($spec_cat) {
                        foreach ($spec_cat as $key => $cat) {
                            $catList[$key] = $this->categoryService->getCatListChildren($cat);
                        }
                    }

                    $catList = $this->baseRepository->getFlatten($catList);

                    $couponsGoods = [
                        'is_coupons' => $is_coupons,
                        'ru_id' => $couponsSeller,
                        'cou_goods' => $cou_goods,
                        'spec_cat' => $catList,
                    ];

                    /* 购物车商品总金额[排除活动商品：夺宝奇兵、拍卖、超值礼包等，仅支持普通商品] */
                    $sql = [
                        'where' => [
                            [
                                'name' => 'extension_code',
                                'value' => ''
                            ],
                            [
                                'name' => 'rec_type',
                                'value' => 0
                            ],
                            [
                                'name' => 'ru_id',
                                'value' => $couponsSeller
                            ]
                        ]
                    ];

                    if ($couponsGoods['is_coupons'] == 2) {
                        $sql['whereIn'][] = [
                            'name' => 'goods_id',
                            'value' => $couponsGoods['cou_goods']
                        ];
                    } elseif ($couponsGoods['is_coupons'] == 3) {
                        $sql['whereIn'][] = [
                            'name' => 'cat_id',
                            'value' => $couponsGoods['spec_cat']
                        ];
                    }

                    $couponsSumList = $this->baseRepository->getArraySqlGet($cart_goods, $sql);
                    $couponSubtotal = $this->baseRepository->getArraySum($couponsSumList, 'subtotal');
                }
            }
            /* 订单优惠券均摊到订单商品， 检测优惠券是否支持商品参与均摊 end */

            $all_ru_id = $this->baseRepository->getKeyPluck($cart_goods, 'ru_id');

            /* 订单红包均摊到订单商品， 检测红包是否支持店铺商品参与均摊 start */
            $useType = 0;
            $bonus_ru_id = 0;
            $bonus_id = $order['bonus_id'] ?? 0;
            $bonusInfo = [];
            $bonusSubtotal = 0;
            if ($bonus_id > 0) {

                /* 购物车商品总金额[排除活动商品：夺宝奇兵、拍卖、超值礼包等，仅支持普通商品] */
                $sql = [
                    'where' => [
                        [
                            'name' => 'extension_code',
                            'value' => ''
                        ],
                        [
                            'name' => 'rec_type',
                            'value' => 0
                        ]
                    ]
                ];

                $bonusSumList = $this->baseRepository->getArraySqlGet($cart_goods, $sql);
                $bonusSubtotal = $this->baseRepository->getArraySum($bonusSumList, 'subtotal');

                if (count($all_ru_id) > 1) {
                    $bonusInfo = UserBonus::where('bonus_id', $bonus_id)->where('user_id', $user_id);
                    $bonusInfo = $bonusInfo->with([
                        'getBonusType' => function ($query) {
                            $query->select('type_id', 'usebonus_type', 'user_id');
                        }
                    ]);

                    $bonusInfo = $this->baseRepository->getToArrayFirst($bonusInfo);

                    /* [0|自主使用，1|平台和店铺通用] */
                    $useType = $bonusInfo['get_bonus_type']['usebonus_type'] ?? 0;
                    $bonus_ru_id = $bonusInfo['get_bonus_type']['user_id'] ?? 0;
                }
            }
            /* 订单红包均摊到订单商品， 检测红包是否支持店铺商品参与均摊 end */

            /* 自主使用时，店铺商品不计算均摊 */
            if ($useType == 0 && count($all_ru_id) > 1) {
                if ($bonusInfo) {
                    if ($bonus_ru_id > 0) {
                        $sql = [
                            'where' => [
                                [
                                    'name' => 'ru_id',
                                    'value' => 0
                                ],
                                [
                                    'name' => 'extension_code',
                                    'value' => ''
                                ],
                                [
                                    'name' => 'rec_type',
                                    'value' => 0
                                ]
                            ]
                        ];

                        $selfList = $this->baseRepository->getArraySqlGet($cart_goods, $sql);
                        $selfAmount = $this->baseRepository->getArraySum($selfList, 'subtotal');
                        $bonusSubtotal = $bonusSubtotal - $selfAmount;
                    } else {

                        $sql = [
                            'where' => [
                                [
                                    'name' => 'ru_id',
                                    'value' => 0,
                                    'condition' => '>'
                                ],
                                [
                                    'name' => 'extension_code',
                                    'value' => ''
                                ],
                                [
                                    'name' => 'rec_type',
                                    'value' => 0
                                ]
                            ]
                        ];

                        $sellerList = $this->baseRepository->getArraySqlGet($cart_goods, $sql);
                        $sellerAmount = $this->baseRepository->getArraySum($sellerList, 'subtotal');
                        $bonusSubtotal = $bonusSubtotal - $sellerAmount;
                    }
                }
            }

            /* 红包 */
            $goods_bonus = 0;
            $bonus_list = [];

            /* 优惠券 */
            $goods_coupons = 0;
            $coupons_list = [];

            foreach ($cart_goods as $k => $v) {
                $goods_extend = GoodsExtend::select('is_reality', 'is_return', 'is_fast')
                    ->where('goods_id', $v['goods_id'])
                    ->first();
                $goods_extend = $goods_extend ? $goods_extend->toArray() : '';

                $order_goods = [];
                $order_goods['user_id'] = $uid;
                $order_goods['order_id'] = $order['order_id'];
                $order_goods['goods_id'] = $v['goods_id'];
                $order_goods['goods_name'] = $v['goods_name'];
                $order_goods['goods_sn'] = $v['goods_sn'];
                $order_goods['product_id'] = $v['product_id'];
                $order_goods['is_reality'] = isset($goods_extend['is_reality']) ? $goods_extend['is_reality'] : 0;
                $order_goods['is_return'] = isset($goods_extend['is_return']) ? $goods_extend['is_return'] : 0;
                $order_goods['is_fast'] = isset($goods_extend['is_fast']) ? $goods_extend['is_fast'] : 0;
                $order_goods['goods_number'] = $v['goods_number'];
                $order_goods['market_price'] = $v['market_price'];
                $order_goods['commission_rate'] = $v['commission_rate'];
                $order_goods['goods_price'] = $v['goods_price'];
                $order_goods['goods_attr'] = $v['goods_attr'];
                $order_goods['is_real'] = $v['is_real'];
                $order_goods['extension_code'] = $v['extension_code'];
                $order_goods['parent_id'] = $v['parent_id'];
                $order_goods['is_gift'] = $v['is_gift'];
                $order_goods['model_attr'] = $v['model_attr'];
                $order_goods['goods_attr_id'] = $v['goods_attr_id'];
                $order_goods['ru_id'] = $v['ru_id'];
                $order_goods['shopping_fee'] = $v['shopping_fee'];
                $order_goods['warehouse_id'] = $v['warehouse_id'];
                $order_goods['area_id'] = $v['area_id'];
                $order_goods['area_city'] = $v['area_city'];
                $order_goods['freight'] = $v['freight'];
                $order_goods['tid'] = $v['tid'];
                $order_goods['shipping_fee'] = $v['shipping_fee'];
                $order_goods['is_distribution'] = isset($v['is_distribution']) ? $v['is_distribution'] * $is_distribution : 0;

                if (file_exists(MOBILE_DRP)) {
                    // 购买成为分销商商品订单
                    $order_goods['buy_drp_show'] = $v['buy_drp_show'] ?? 0;
                    $order_goods['membership_card_id'] = $v['membership_card_id'] ?? 0;

                    $v['dis_commission'] = $v['dis_commission'] ?? 0;
                    // 即是分销商品，又是会员卡指定购买商品，则优先使用会员卡商品中设置的【会员卡分销】分成奖励
                    if (isset($order_goods['membership_card_id']) && empty($order_goods['membership_card_id'])) {
                        if (isset($v['dis_commission_type']) && $v['dis_commission_type'] == 1) {
                            //商品佣金按照设定数额进行返利
                            $order_goods['drp_money'] = $order_goods['is_distribution'] * ($v['dis_commission'] * $v['goods_number']);
                        } else {
                            //商品佣金按照比例进行返利
                            $order_goods['drp_money'] = $order_goods['is_distribution'] * ($v['dis_commission'] * $v['goods_price'] * $v['goods_number'] / 100);
                        }
                    }
                    // 购买权益卡订单商品折扣
                    if (isset($flow['order_membership_card_id']) && $flow['order_membership_card_id'] > 0 && isset($v['membership_card_discount_price']) && $v['membership_card_discount_price'] > 0) {
                        $order_goods['membership_card_discount_price'] = $v['membership_card_discount_price'];
                    }
                }

                /* 订单红包均摊到订单商品， 检测红包是否支持店铺商品参与均摊 start */
                $isShareAlike = 1;
                if ($useType == 0) {
                    if ($bonusInfo) {
                        if ($bonus_ru_id > 0) {
                            if ($v['ru_id'] == 0) {
                                $isShareAlike = 0;
                            } else {
                                $isShareAlike = 1;
                            }
                        } else {
                            if ($v['ru_id'] > 0) {
                                $isShareAlike = 0;
                            } else {
                                $isShareAlike = 1;
                            }
                        }
                    }
                }

                $is_general_goods = $order_goods['extension_code'] == '' && $v['rec_type'] == 0;
                $keySubtotal = $order_goods['goods_price'] * $order_goods['goods_number'];

                if ($is_general_goods === true && $order['bonus'] > 0 && $bonusSubtotal > 0) {
                    if ($order_goods['goods_price'] > 0 && $isShareAlike == 1) {
                        $order_goods['goods_bonus'] = ($keySubtotal / $bonusSubtotal) * $order['bonus'];
                        $order_goods['goods_bonus'] = $this->dscRepository->changeFloat($order_goods['goods_bonus']);
                    } else {
                        $order_goods['goods_bonus'] = 0;
                    }

                    $bonus_list[$k]['goods_bonus'] = $order_goods['goods_bonus'];
                    $goods_bonus += $order_goods['goods_bonus'];
                }
                /* 订单红包均摊到订单商品， 检测红包是否支持店铺商品参与均摊 end */

                /* 订单优惠券均摊到订单商品， 检测优惠券是否支持商品参与均摊 start */
                $order['coupons'] = $order['coupons'] ?? 0;
                if ($is_general_goods === true && $order['coupons'] > 0 && $couponSubtotal > 0) {
                    $cat_id = $v['cat_id'] ?? 0;
                    if ($couponsGoods && $order_goods['goods_price'] > 0 && $couponsGoods['ru_id'] == $v['ru_id']) {

                        $order_goods['goods_coupons'] = 0;

                        if ($couponsGoods['is_coupons'] == 1) {
                            $order_goods['goods_coupons'] = ($keySubtotal / $couponSubtotal) * $order['coupons'];
                            $order_goods['goods_coupons'] = $this->dscRepository->changeFloat($order_goods['goods_coupons']);

                        } elseif ($couponsGoods['is_coupons'] == 2) {
                            if ($couponsGoods['cou_goods'] && in_array($v['goods_id'], $couponsGoods['cou_goods'])) {
                                $order_goods['goods_coupons'] = ($keySubtotal / $couponSubtotal) * $order['coupons'];
                                $order_goods['goods_coupons'] = $this->dscRepository->changeFloat($order_goods['goods_coupons']);
                            }
                        } elseif ($couponsGoods['is_coupons'] == 3) {
                            if ($cat_id > 0 && in_array($cat_id, $couponsGoods['spec_cat'])) {
                                $order_goods['goods_coupons'] = ($keySubtotal / $couponSubtotal) * $order['coupons'];
                                $order_goods['goods_coupons'] = $this->dscRepository->changeFloat($order_goods['goods_coupons']);
                            }
                        }

                        $coupons_list[$k]['goods_coupons'] = $order_goods['goods_coupons'];
                        $goods_coupons += $order_goods['goods_coupons'];
                    }
                }
                /* 订单优惠券均摊到订单商品， 检测优惠券是否支持商品参与均摊 end */

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    if (isset($rate['rate_arr']) && !empty($rate['rate_arr'])) {
                        foreach ($rate['rate_arr'] as $key => $val)//插入跨境税费
                        {
                            if ($v['goods_id'] == $val['goods_id']) {
                                $order_goods['rate_price'] = $val['rate_price'];
                            }
                        }
                    }
                }

                $recId = OrderGoods::insertGetId($order_goods);

                $coupons_list[$k]['rec_id'] = $recId;
                $bonus_list[$k]['rec_id'] = $recId;
                $order_rec[] = $recId;
            }

            /* 核对均摊优惠券商品金额 */
            $this->dscRepository->collateOrderGoodsBonus($coupons_list, $order['coupons'] ?? 0, $goods_coupons);

            /* 核对均摊红包商品金额 */
            $this->dscRepository->collateOrderGoodsBonus($bonus_list, $order['bonus'], $goods_bonus);
        }

        if ((empty($order_rec)) || (count($cart_goods) != count($order_rec))) {
            OrderInfo::where('order_id', $new_order_id)->delete();
            return 'order_failure';
        }

        $all_ru_id = $this->baseRepository->getKeyPluck($cart_goods, 'ru_id');

        //判断product类型
        if ($new_order_id > 0) {
            $goods_product = OrderGoods::select('rec_id', 'model_attr', 'product_id')
                ->where('order_id', $new_order_id)
                ->get();
            $goods_product = $goods_product ? $goods_product->toArray() : [];
            if ($goods_product) {
                foreach ($goods_product as $gkey => $gval) {
                    //货号调用
                    if ($gval['model_attr'] == 1) {
                        $table = ProductsWarehouse::where('product_id', $gval['product_id']);
                    } elseif ($gval['model_attr'] == 2) {
                        $table = ProductsArea::where('product_id', $gval['product_id']);
                    } else {
                        $table = products::where('product_id', $gval['product_id']);
                    }
                    $product_sn = $table->value('product_sn');
                    OrderGoods::where('rec_id', $gval['rec_id'])->update(['product_sn' => $product_sn]);
                }
            }
        }

        $this->jigonManageService->pushJigonOrderGoods($cart_goods, $order, 'api'); //推送贡云订单

        /*插入门店订单表*/
        $pick_code = '';
        if ($new_order_id > 0 && $store_id > 0 && $ru_id_arr) {
            foreach ($ru_id_arr as $v) {
                if ($stores_sms != 1) {
                    $pick_code = '';
                } else {
                    $pick_code = substr($order['order_sn'], -3) . rand(0, 9) . rand(0, 9) . rand(0, 9);
                }
                $store_order = [
                    'order_id' => $new_order_id,
                    'store_id' => $store_id,
                    'ru_id' => $v,
                    'pick_code' => $pick_code,
                    'take_time' => $flow['take_time'],
                ];
                StoreOrder::insert($store_order);
            }
        }
        //插入门店订单结束

        /* 记录优惠券使用 bylu */
        if ($order['uc_id'] > 0) {
            $this->use_coupons($order['uc_id'], $order['order_id']);
        }

        /* 修改拍卖活动状态 */
        if ($order['extension_code'] == 'auction') {
            $is_finished = 2; //完成状态默认为2(已完成已处理);

            //获取拍卖活动保证金
            $activity_ext_info = GoodsActivity::select('ext_info')
                ->where('act_id', $order['extension_id'])
                ->first();
            $activity_ext_info = $activity_ext_info ? $activity_ext_info->toArray() : '';

            //判断是否存在保证金
            if ($activity_ext_info) {
                $activity_ext_info = unserialize($activity_ext_info['ext_info']);
                //存在保证金状态为1（已完成未处理）
                if ($activity_ext_info['deposit'] > 0) {
                    $is_finished = 1;
                }
            }
            GoodsActivity::where('act_id', $order['extension_id'])->update(['is_finished' => $is_finished]);
        }

        /* 修改砍价活动状态 */
        if ($order['extension_code'] == 'bargain_buy') {
            BargainStatisticsLog::where('id', $flow['bs_id'])->update(['status' => 1]);
        }

        /* 处理储值卡 */
        if ($order['vc_id'] > 0) {
            use_value_card($order['vc_id'], $new_order_id, $order['use_value_card']);
        }
        /* 处理余额、积分、红包 */
        if ($order['user_id'] > 0 && ($order['surplus'] > 0 || $order['integral'] > 0)) {
            if ($order['surplus'] > 0) {
                $order_surplus = $order['surplus'] * (-1);
            } else {
                $order_surplus = 0;
            }
            if ($order['integral'] > 0) {
                $order_integral = $order['integral'] * (-1);
            } else {
                $order_integral = 0;
            }
            log_account_change($order['user_id'], $order_surplus, 0, 0, $order_integral, sprintf(lang('shopping_flow.pay_order'), $order['order_sn']));
        }

        /*判断预售商品是否在支付尾款时间段内*/
        $order['presaletime'] = 0;
        if ($order['extension_code'] == 'presale') {
            $presale = PresaleActivity::select('pay_start_time', 'pay_end_time')
                ->where('act_id', $order['extension_id'])
                ->first();
            $presale = $presale ? $presale->toArray() : [];
            if ($presale) {
                if ($time < $presale['pay_end_time'] && $time > $presale['pay_start_time']) {
                    $order['presaletime'] = 1;
                } else {
                    $order['presaletime'] = 2;
                }
            }
        }

        if ($order['bonus_id'] > 0 && $temp_amout > 0) {
            $this->use_bonus($order['bonus_id'], $new_order_id);
        }

        /** 如果使用库存，且下订单时减库存，则减少库存 */
        if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PLACE) {
            change_order_goods_storage($order['order_id'], true, SDT_PLACE, 1, 0, $store_id);
        }

        /* 如果使用库存，且付款时减库存，且订单金额为0，则减少库存 */
        if ($new_order_id > 0 && $this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PAID && $order['order_amount'] <= 0) {
            change_order_goods_storage($order['order_id'], true, SDT_PAID, 15, 0, $store_id);
        }

        $msg = $order['pay_status'] == PS_UNPAYED ? lang('shopping_flow.order_placed_sms') : lang('shopping_flow.order_placed_sms_ispay');

        /* 插入支付日志 */
        $order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);

        // 开通购买会员权益卡订单记录
        if (file_exists(MOBILE_DRP) && isset($flow['order_membership_card_id']) && $flow['order_membership_card_id'] > 0) {
            if (isset($memberCardInfo) && !empty($memberCardInfo)) {
                // 使用余额 支付订单
                if (($payment['pay_code'] == 'balance' || $order['is_surplus'] == 1) && $order['order_amount'] == 0 && $order['surplus'] > 0) {
                    $membership_card_order_amount = $order['surplus'] + $order['order_amount'];
                } else {
                    $membership_card_order_amount = $order['order_amount'];
                }

                $order_membership_card = [
                    'order_amount' => $membership_card_order_amount > 0 ? $membership_card_order_amount - $memberCardInfo['membership_card_buy_money'] : 0,
                    'membership_card_id' => $flow['order_membership_card_id'],
                    'membership_card_buy_money' => $memberCardInfo['membership_card_buy_money'] ?? 0,
                    'membership_card_discount_price' => $flow['membership_card_discount_price'] ?? 0,
                ];
                app(DiscountRightsService::class)->orderBuyMembershipCard($order, $order_membership_card);
            }
        }

        /* 如果订单金额为0的订单（不分单） 处理虚拟卡 */
        if ($new_order_id > 0 && $order['order_amount'] <= 0 && count($ru_id_arr) == 1) {
            $this->jigonManageService->jigonConfirmOrder($new_order_id); //贡云确认订单

            $virtual_goods = get_virtual_goods($new_order_id);
            if ($virtual_goods && $flow_type != CART_GROUP_BUY_GOODS) {
                /* 虚拟卡发货 */
                $error_msg = '';
                if (virtual_goods_ship_mobile($virtual_goods, $error_msg, $order['order_sn'], true)) {
                    /* 如果没有实体商品，修改发货状态，送积分和红包 */
                    $num = OrderGoods::where('order_id', $order['order_id'])
                        ->where('is_real', 1)
                        ->count();
                    if ($num <= 0) {
                        /* 修改订单状态 OS_CONFIRMED 1，OS_SPLITED 5  */
                        update_order($order['order_id'], ['order_status' => OS_CONFIRMED, 'shipping_status' => SS_SHIPPED, 'shipping_time' => $time]);

                        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                        if ($order['user_id'] > 0) {

                            /* 计算并发放积分 */
                            $integral = integral_to_give($order);
                            log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf(lang('payment.order_gift_integral'), $order['order_sn']));

                            /* 发放红包 */
                            send_order_bonus($order['order_id']);

                            /* 发放优惠券 bylu */
                            send_order_coupons($order['order_id']);
                        }
                    }
                }
            }
        }

        /* 清空购物车 */
        $this->cartCommonService->clearCart($uid, $flow_type, $done_cart_value);

        if (!empty($order['shipping_name'])) {
            $order['shipping_name'] = trim(stripcslashes($order['shipping_name']));
        }

        //订单分子订单 start
        $order_id = $order['order_id'];

        $ru_number = count($all_ru_id);

        $userOrderNumCount = UserOrderNum::where('user_id', $user_id)->count();
        if ($userOrderNumCount == 0) {
            UserOrderNum::insert([
                'user_id' => $uid
            ]);
        }

        /* 更新会员订单信息 */
        //兼容货到付款订单放到未收货订单中
        if ($order['order_amount'] <= 0 || $payment['pay_code'] == 'cod') {
            $dbRaw = [
                'order_all_num' => "order_all_num + " . $ru_number,
                'order_nogoods' => "order_nogoods + " . $ru_number
            ];
            if ($order['extension_code'] == 'team_buy') {
                $dbRaw['order_team_num'] = "order_team_num + " . $ru_number;
            }
            $dbRaw = $this->baseRepository->getDbRaw($dbRaw);

            UserOrderNum::where('user_id', $uid)->update($dbRaw);
        } else {
            $dbRaw = [
                'order_all_num' => "order_all_num + " . $ru_number,
                'order_nopay' => "order_nopay + " . $ru_number
            ];
            $dbRaw = $this->baseRepository->getDbRaw($dbRaw);

            UserOrderNum::where('user_id', $uid)->update($dbRaw);
        }

        if ($order_id && $ru_number > 1) {
            $this->flowOrderService->OrderSeparateBill($order_id);

            $main_pay = 1;
            if ($order['order_amount'] <= 0) {
                $main_pay = 2;
            }

            $updateOrder = [
                'main_count' => $ru_number,
                'main_pay' => $main_pay
            ];

            $child_order_info = get_child_order_info($order_id);
        } else {
            $updateOrder = [
                'ru_id' => $all_ru_id[0]
            ];

            $child_order_info = [];
        }

        OrderInfo::where('order_id', $new_order_id)->update($updateOrder);

        // 微信通模板消息 订单通知
        if (is_wechat_browser() && file_exists(MOBILE_WECHAT)) {
            $pushData = [
                'first' => ['value' => lang('wechat.order_add_first')], // 标题
                'keyword1' => ['value' => $order['order_sn'], 'color' => '#173177'], //订单号
                'keyword2' => ['value' => $order['order_amount'], 'color' => '#ec5151'], //订单应付金额
                'keyword3' => ['value' => date('Y-m-d', $order['add_time']), 'color' => '#173177'], // 下单时间
                'remark' => ['value' => lang('wechat.order_add_remark'), 'color' => '#173177']
            ];
            $url = dsc_url('/#/user/orderDetail/' . $order_id);

            $this->wechatService->push_template('OPENTM415293129', $pushData, $url, $order['user_id']);
        }

        // 使用余额 支付订单(含分单) 记录操作日志
        if (($payment['pay_code'] == 'balance' || $order['is_surplus'] == 1) && $order['order_amount'] == 0 && $order['surplus'] > 0) {
            /* 记录主订单操作记录 */
            order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_PAYED, lang('shopping_flow.flow_surplus_pay'), lang('common.buyer'));

            if (!empty($child_order_info)) {
                /* 记录子订单操作记录 */
                foreach ($child_order_info as $key => $child) {
                    order_action($child['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_PAYED, lang('shopping_flow.flow_surplus_pay'), lang('common.buyer'));
                }
            }

            // 更新拼团信息记录
            if (isset($order['team_id']) && $order['team_id'] > 0) {
                $this->teamService->updateTeamInfo($order['team_id'], $order['team_parent_id'], $order['user_id']);
                app(OrderCommonService::class)->getUserOrderNumServer($order['user_id']);
            }

            // 开通购买会员权益卡 订单支付成功 更新成为分销商
            if (file_exists(MOBILE_DRP) && isset($flow['order_membership_card_id']) && $flow['order_membership_card_id'] > 0) {
                app(DrpService::class)->buyOrderUpdateDrpShop($order['user_id'], $order['order_id']);
            }

            // 微信通模板消息 余额变动提醒
            if (is_wechat_browser() && file_exists(MOBILE_WECHAT)) {
                // 查询用户最新当前余额
                $user_money = Users::where('user_id', $order['user_id'])->value('user_money');
                $user_money = $user_money ? $user_money : 0;
                $order_surplus = $order_surplus ?? 0;
                $pay_time = $this->timeRepository->getLocalDate($this->config['time_format'], $order['pay_time']);
                $pushData = [
                    'keyword1' => ['value' => $pay_time, 'color' => '#173177'], //变动时间
                    'keyword2' => ['value' => lang('shopping_flow.consume_deduction'), 'color' => '#173177'], //变动类型
                    'keyword3' => ['value' => $order_surplus, 'color' => '#ec5151'], // 变动金额
                    'keyword4' => ['value' => $user_money, 'color' => '#ec5151'], // 当前余额
                    'remark' => ['value' => lang('common.user_money_select'), 'color' => '#173177'],
                ];
                $url = dsc_url('/#/user/account'); // 进入用户余额页面
                $this->wechatService->push_template('OPENTM401833445', $pushData, $url, $order['user_id']);
            }
        }

        //门店发送短信
        if ($stores_sms == 1 && $store_id > 0) {
            /*门店下单时未填写手机号码 则用会员绑定号码*/
            if ($order['mobile']) {
                $user_mobile_phone = $order['mobile'];
            } else {
                $user_mobile_phone = !empty($user_info['mobile_phone']) ? $user_info['mobile_phone'] : '';
            }

            if (!empty($user_mobile_phone)) {
                $user_name = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
                //发送提货码
                $content = [
                    'code' => $pick_code,
                    'user_name' => $user_name
                ];

                $this->commonRepository->smsSend($user_mobile_phone, $content, 'store_order_code');
            }
        }

        //对单商家下单
        if ($order_id > 0 && count($all_ru_id) == 1) {
            $sellerId = $all_ru_id['0'];
            if ($sellerId == 0) {
                // 平台
                $sms_shop_mobile = $this->config['sms_shop_mobile'];
                $service_email = $this->config['service_email'];
            } else {
                // 商家
                $seller_shopinfo = SellerShopinfo::select('mobile', 'seller_email')
                    ->where('ru_id', $sellerId)
                    ->first();
                $seller_shopinfo = $seller_shopinfo ? $seller_shopinfo->toArray() : [];
                $sms_shop_mobile = !isset($seller_shopinfo['mobile']) ? '' : $seller_shopinfo['mobile'];
                $service_email = !isset($seller_shopinfo['seller_email']) ? '' : $seller_shopinfo['seller_email'];
            }

            //是否开启下单自动发短信、邮件 by wu start
            $auto_sms = Crons::select('*')
                ->where('cron_code', 'auto_sms')
                ->where('enable', 1)
                ->first();
            $auto_sms = $auto_sms ? $auto_sms->toArray() : [];

            // 下单或付款发短信
            if (!empty($sms_shop_mobile) && ($this->config['sms_order_placed'] == '1' || $this->config['sms_order_payed'] == '1')) {
                if (!empty($auto_sms)) {
                    $autoData = [
                        'item_type' => 1,
                        'user_id' => $order['user_id'],
                        'ru_id' => $sellerId,
                        'order_id' => $order_id,
                        'add_time' => $time
                    ];
                    AutoSms::insert($autoData);
                } else {
                    $content = [
                        'consignee' => $order['consignee'],
                        'order_mobile' => $order['mobile'],
                        'ordermobile' => $order['mobile'], // 兼容变量
                    ];

                    // 下单发短信
                    if ($this->config['sms_order_placed'] == '1') {
                        $this->commonRepository->smsSend($sms_shop_mobile, $content, 'sms_order_placed');
                    }

                    // 下单与付款发送短信 若同时开启 须间隔1s
                    if ($this->config['sms_order_placed'] == '1' && $this->config['sms_order_payed'] == '1') {
                        sleep(1);
                    }
                    // 余额支付等金额为0的订单 付款发短信
                    if ($stores_sms == 1 && $this->config['sms_order_payed'] == '1') {
                        $this->commonRepository->smsSend($sms_shop_mobile, $content, 'sms_order_payed');
                    }
                }
            }
            /* 给商家发邮件 */
            /* 增加是否给客服发送邮件选项 */
            if ($this->config['send_service_email'] && $service_email != '') {
                if (!empty($auto_sms)) {
                    $autoData = [
                        'item_type' => 2,
                        'user_id' => $order['user_id'],
                        'ru_id' => $sellerId,
                        'order_id' => $order_id,
                        'add_time' => $time
                    ];
                    AutoSms::insert($autoData);
                }
            }
        }

        // 分成插入drp_log
        if (file_exists(MOBILE_DRP)) {
            app(DrpGoodsRightsService::class)->orderDrpLog('drp_goods', $user_id);
        }

        if ($new_order_id > 0) {
            if ($order['extension_code'] == 'presale') {
                //付款成功后增加预售人数
                get_presale_num($new_order_id);
            }
            /* 更新商品销量 */
            get_goods_sale($new_order_id);

            // 付款成功创建快照
            if ($snapshot) {
                create_snapshot($order['order_id']);
            }
        }

        return $order['order_sn'];
    }

    /**
     * 选择支付方式
     * @param $uid
     * @param $order_sn
     * @return array
     */
    public function PayCheck($uid, $order_sn = '', $store_id = 0)
    {
        $order = [];
        if ($order_sn) {
            $order_info = OrderInfo::where('order_sn', $order_sn)
                ->where('user_id', $uid)
                ->first();
            $order_info = $order_info ? $order_info->toArray() : '';
        }

        $child_order = OrderInfo::select('order_id')
            ->where('main_order_id', $order_info['order_id'])
            ->count();

        if ($child_order > 1) {
            $child_order_info = get_child_order_info($order_info['order_id']);
        }

        //门店信息
        if ($store_id > 0) {
            $store_info = $this->offlineStoreService->infoOfflineStore($store_id);
            if (!empty($store_info)) {
                $store_info['province_name'] = $this->get_region_name($store_info['province']);
                $store_info['city_name'] = $this->get_region_name($store_info['city']);
                $store_info['district_name'] = $this->get_region_name($store_info['district']);
            }
        }

        if ($order_info) {
            $order['order_sn'] = $order_info['order_sn'];

            $payment = payment_info($order_info['pay_id']);
            $payment['pay_name'] = $payment['pay_name'] ?? '';
            $payment['pay_code'] = $payment['pay_code'] ?? '';
            $payment['is_online'] = $payment['is_online'] ?? 0;
            $payment['pay_desc'] = $payment['pay_desc'] ?? '';

            $order['pay_name'] = $payment['pay_name'];
            $order['pay_code'] = $payment['pay_code'];
            $order['pay_desc'] = $payment['pay_desc'];

            // 手机端 使用余额支付订单并且订单状态已付款
            $order['is_surplus'] = ($order_info['surplus'] > 0 && $order_info['pay_status'] == PS_PAYED) ? 1 : 0;
            // 是否在线支付
            $order['is_online'] = ($payment['is_online'] == 1 && $payment['pay_code'] != 'balance') ? 1 : 0;
            $order['pay_result'] = ($order_info['pay_id'] == 0 && $order_info['pay_status'] == PS_PAYED) ? 1 : 0; //余额支付订单并且订单状态已付款
            $order['cod_fee'] = 0;
            $shipping_id = explode('|', $order_info['shipping_id']);
            foreach ($shipping_id as $k => $v) {
                if ($v) {
                    $order['support_cod'] = Shipping::where('shipping_id', $v)->value('support_cod');
                }
            }
            $order['pay_status'] = $order_info['pay_status'];
            $order['order_id'] = $order_info['order_id'];
            $order['order_amount'] = $order_info['order_amount'];
            $order['order_amount_format'] = $this->dscRepository->getPriceFormat($order_info['order_amount']);
            $order['child_order'] = isset($child_order) ? $child_order : 0;
            $order['child_order_info'] = isset($child_order_info) ? $child_order_info : [];
            $order['store_info'] = isset($store_info) ? $store_info : [];
            $order['extension_code'] = $order_info['extension_code'];
            $order['is_zc_order'] = $order_info['is_zc_order'];
            $order['zc_goods_id'] = $order_info['zc_goods_id'];
            if ($order['extension_code'] == 'team_buy') {
                $team_id = $order_info['team_id'];
                $order['url'] = dsc_url('/#/team/wait') . '?' . http_build_query(['team_id' => $team_id, 'status' => 1], '', '&');
                $order['support_cod'] = 0; // 过滤货到付款
            }
            if ($order['is_zc_order'] == 1 && $order['zc_goods_id'] > 0) {
                $order['extension_code'] = 'crowd_buy';
            }
        }

        return $order;
    }


    /**
     * 改变订单中商品库存
     * @param int $order_id 订单号
     * @param bool $is_dec 是否减少库存
     * @param bool $storage 减库存的时机，1，下订单时；0，发货时；
     */
    public function changeOrderGoodsStorage($order_id, $is_dec = true, $storage = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        /* 查询订单商品信息 */
        switch ($storage) {
            case 0:
                $res = OrderGoods::where('order_id', $order_id)
                    ->where('is_real', 1)
                    ->groupBy('goods_id')
                    ->groupBy('product_id')
                    ->select(['sum(send_number) as num', 'goods_id,max(extension_code) as extension_code', 'product_id'])
                    ->get();
                $res = $res ? $res->toArray() : '';
                break;

            case 1:
                $res = OrderGoods::where(['order_id' => $order_id])->where(['is_real' => 1])
                    ->groupBy('goods_id')
                    ->groupBy('product_id')
                    ->selectRaw('sum(goods_number) as num, goods_id,max(extension_code) as extension_code, product_id')
                    ->get();
                $res = $res ? $res->toArray() : '';
                break;
        }
        foreach ($res as $key => $row) {
            if ($row['extension_code'] != "package_buy") {
                if ($is_dec) {
                    $this->changeGoodsStorageMobile($row['goods_id'], $row['product_id'], -$row['num'], $warehouse_id, $area_id, $area_city);
                } else {
                    $this->changeGoodsStorageMobile($row['goods_id'], $row['product_id'], $row['num'], $warehouse_id, $area_id, $area_city);
                }
            }
        }
    }

    /**
     * 商品库存增与减 货品库存增与减
     *
     * @param $goods_id
     * @param $product_id
     * @param int $number
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return bool true，成功；false，失败；
     */
    public function changeGoodsStorageMobile($goods_id, $product_id, $number = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        if ($number == 0) {
            return true; // 值为0即不做、增减操作，返回true
        }

        if (empty($goods_id) || empty($number)) {
            return false;
        }

        $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');
        $model_attr = $model_attr ? $model_attr : 0;

        $number = ($number > 0) ? '+' . $number : $number;

        $abs_number = abs($number);

        /* 处理货品库存 */
        if (!empty($product_id)) {

            if ($model_attr == 1) {
                $res = ProductsWarehouse::whereRaw(1);
            } elseif ($model_attr == 2) {
                $res = ProductsArea::whereRaw(1);
            } else {
                $res = Products::whereRaw(1);
            }

            if ($number < 0) {
                $set_update = "IF(product_number >= $abs_number, product_number $number, 0)";
            } else {
                $set_update = "product_number $number";
            }

            $other = [
                'product_number' => DB::raw($set_update)
            ];
            $products_query = $res->where('goods_id', $goods_id)
                ->where('product_id', $product_id)
                ->update($other);

            return $products_query ? true : false;
        } else {
            /* 处理商品库存 */
            if ($model_attr == 1 || $model_attr == 2) {
                $set_update = "IF(region_number >= $abs_number, region_number $number, 0)";
            } else {
                $set_update = "IF(goods_number >= $abs_number, goods_number $number, 0)";
            }

            if ($model_attr == 1) {
                $other = [
                    'region_number' => DB::raw($set_update)
                ];
                $query = WarehouseGoods::where('goods_id', $goods_id)
                    ->where('region_id', $warehouse_id)
                    ->update($other);
            } elseif ($model_attr == 2) {
                $other = [
                    'region_number' => DB::raw($set_update)
                ];
                $query = WarehouseAreaGoods::where('goods_id', $goods_id)
                    ->where('region_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $query = $query->where('city_id', $area_city);
                }

                $query->update($other);
            } else {
                $other = [
                    'goods_number' => DB::raw($set_update)
                ];
                $query = Goods::where('goods_id', $goods_id)
                    ->update($other);
            }

            return $query ? true : false;
        }
    }

    /**
     * 使用优惠券
     * @param int $uc_id
     * @param int $uid
     * @param array $total
     * @return array
     */
    public function ChangeCou($uc_id = 0, $uid = 0, $total = [])
    {
        $coupons = CouponsUser::where('uc_id', $uc_id)
            ->where('user_id', $uid)
            ->where('is_use', 0);

        $time = $this->timeRepository->getGmTime();

        $coupons = $coupons->whereHas('getCoupons', function ($query) use ($time) {
            $query->where('cou_end_time', '>', $time);
        });

        $coupons = $coupons->with([
            'getCoupons' => function ($query) {
                $query->select('cou_id', 'cou_type');
            }
        ]);

        $coupons = $coupons->groupBy('uc_id');

        $coupons = $this->baseRepository->getToArrayFirst($coupons);

        $coupons = isset($coupons['get_coupons']) ? $this->baseRepository->getArrayMerge($coupons, $coupons['get_coupons']) : $coupons;

        $total['success_type'] = 0;

        $total['bonus_money'] = $total['bonus_money'] ?? 0;
        $total['coupons_money'] = $total['coupons_money'] ?? 0;
        $total['vc_dis'] = isset($total['vc_dis']) ? round($total['vc_dis'] / 10, 2) : 1;
        $total['integral_money'] = $total['integral_money'] ?? 0;
        $total['card'] = isset($total['card']) && $total['card'] ? floatval($total['card']) : 0;
        $total['card_money'] = isset($total['card_money']) && $total['card_money'] ? floatval($total['card_money']) : 0;
        $total['discount'] = $total['discount'] ?? 0;
        $total['bonus_id'] = isset($total['bonus_id']) && $total['bonus_id'] ? $total['bonus_id'] : 0; //红包id
        $total['coupons_id'] = $total['coupons_id'] ?? $uc_id; //优惠券id
        $total['value_card_id'] = $total['value_card_id'] ?? 0; //储值卡id
        $total['surplus'] = $total['surplus'] ?? 0;; //余额
        $total['shopping_fee'] = $total['shopping_fee'] ?? 0;; //运费


        /* 订单优惠后价格 */
        $amount = $total['goods_price'] - $total['discount'] - $total['bonus_money'] - $total['integral_money'] - $total['card_money'] - $total['surplus'];
        $amount = $amount > 0 ? $amount : 0;

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $total['amount'] -= $total['rate_price'];
        }

        // 开通购买会员特价权益卡
        $total['order_membership_card_id'] = $total['order_membership_card_id'] ?? 0; // 权益卡id
        if (file_exists(MOBILE_DRP) && $total['order_membership_card_id'] > 0) {
            // $amount = $amount + membership_card_buy_money - membership_card_discount_price
            $total['membership_card_buy_money'] = $total['membership_card_buy_money'] ?? 0; // 权益卡购买金额
            $total['membership_card_discount_price'] = $total['membership_card_discount_price'] ?? 0;  // 权益卡购买折扣
            $amount = $amount + $total['membership_card_buy_money'] - $total['membership_card_discount_price'];
        }

        if ($uc_id > 0 && $coupons) {
            $total['success_type'] = 1;
            $coupons['cou_money'] = floatval($coupons['cou_money']);

            $total['coupons_money'] = sprintf("%.2f", $coupons['cou_money']);
            $total['coupons_money_formated'] = $this->dscRepository->getPriceFormat($coupons['cou_money']);
            $total['cou_type'] = $coupons['cou_type'];

            if ($amount > 0) {
                if ($total['card_money'] > 0 && $amount == $total['card_money']) {
                    $total['card_money'] -= $total['coupons_money'];
                } else {
                    if ($amount > $total['coupons_money']) {
                        $total['amount'] = $amount - $total['coupons_money'];
                    } else {
                        $total['amount'] = 0;
                    }
                }
            } else {
                $total['amount'] = 0;
            }

            //免邮券
            if ($coupons['cou_type'] == VOUCHER_SHIPPING) {
                $shopping_fee = $total['shopping_fee'];
                $total['shopping_fee'] = $shopping_fee;
                $total['shopping_fee_formated'] = $this->dscRepository->getPriceFormat($shopping_fee);
            }
        } else {
            if (isset($total['cou_type']) && $total['cou_type'] == VOUCHER_SHIPPING) {
                if (isset($total['shipping_fee'])) {
                    unset($total['shipping_fee']);
                }
            }

            if ($total['amount'] == 0 && $total['card_money'] > 0) {
                $total['card_money'] += $total['coupons_money'];
            } else {
                if ($amount > $total['coupons_money']) {
                    $total['amount'] += $total['coupons_money'];
                } else {
                    $total['amount'] = $amount;
                }
            }
            $total['coupons_money'] = 0;
            $total['cou_type'] = 0;

        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $total['amount'] += $total['rate_price'];
        }
        $total['vc_dis'] = $total['vc_dis'] * 10;
        $total['amount_formated'] = $this->dscRepository->getPriceFormat($total['amount']);
        $total['card_money_formated'] = $this->dscRepository->getPriceFormat($total['card_money']);
        $total['coupons_money_formated'] = $this->dscRepository->getPriceFormat($total['coupons_money']);

        return $total;
    }

    /**
     * 使用红包
     * @param $userId
     * @param $cou_id
     * @return array
     */
    public function ChangeBon($bonus_id, $uid, $total = [])
    {
        $bonus = Userbonus::where('bonus_id', $bonus_id)
            ->where('user_id', $uid)
            ->where('used_time', 0);

        $bonus = $bonus->with([
            'getBonusType'
        ]);

        $bonus = $this->baseRepository->getToArrayFirst($bonus);
        $bonus = isset($bonus['get_bonus_type']) ? $this->baseRepository->getArrayMerge($bonus, $bonus['get_bonus_type']) : $bonus;

        $total['success_type'] = 0;

        $total['bonus_money'] = $total['bonus_money'] ?? 0;
        $total['coupons_money'] = $total['coupons_money'] ?? 0;
        $total['vc_dis'] = isset($total['vc_dis']) ? round($total['vc_dis'] / 10, 2) : 1;
        $total['integral_money'] = $total['integral_money'] ?? 0;
        $total['card'] = isset($total['card']) && $total['card'] ? floatval($total['card']) : 0;
        $total['card_money'] = isset($total['card_money']) && $total['card_money'] ? floatval($total['card_money']) : 0;
        $total['discount'] = $total['discount'] ?? 0;
        $total['bonus_id'] = isset($total['bonus_id']) && $total['bonus_id'] ? $total['bonus_id'] : $bonus_id; //红包id
        $total['coupons_id'] = $total['coupons_id'] ?? 0; //优惠券id
        $total['value_card_id'] = $total['value_card_id'] ?? 0; //储值卡id
        $total['surplus'] = $total['surplus'] ?? 0;; //余额

        $amount = $total['goods_price'] - $total['discount'] - $total['integral_money'] - $total['card_money'] - $total['coupons_money'] - $total['surplus'];
        $amount = $amount > 0 ? $amount : 0;

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $total['amount'] -= $total['rate_price'];
        }

        // 开通购买会员特价权益卡
        $total['order_membership_card_id'] = $total['order_membership_card_id'] ?? 0; // 权益卡id
        if (file_exists(MOBILE_DRP) && $total['order_membership_card_id'] > 0) {
            // $amount = $amount + membership_card_buy_money - membership_card_discount_price
            $total['membership_card_buy_money'] = $total['membership_card_buy_money'] ?? 0; // 权益卡购买金额
            $total['membership_card_discount_price'] = $total['membership_card_discount_price'] ?? 0;  // 权益卡购买折扣
            $amount = $amount + $total['membership_card_buy_money'] - $total['membership_card_discount_price'];
        }

        if ($bonus_id > 0 && $bonus) {
            $bonus['type_money'] = floatval($bonus['type_money']);

            $total['bonus_money'] = $bonus['type_money'];
            $total['success_type'] = 1;

            if ($amount > 0) {
                if ($total['card_money'] > 0 && $amount == $total['card_money']) {
                    $total['card_money'] -= $total['bonus_money'];
                } else {
                    if ($amount > $total['bonus_money']) {
                        $total['amount'] = $amount - $total['bonus_money'];
                    } else {
                        $total['amount'] = 0;
                    }
                }
            } else {
                $total['amount'] = 0;
            }
        } else {
            if ($total['amount'] == 0 && $total['card_money'] > 0) {
                $total['card_money'] += $total['bonus_money'];
            } else {
                if ($amount > $total['bonus_money']) {
                    $total['amount'] += $total['bonus_money'];
                } else {
                    $total['amount'] = $amount;
                }
            }
            $total['bonus_money'] = 0;
            $total['bonus_id'] = 0;

        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $total['amount'] += $total['rate_price'];
        }
        $total['vc_dis'] = $total['vc_dis'] * 10;
        $total['amount_formated'] = $this->dscRepository->getPriceFormat($total['amount']);
        $total['card_money_formated'] = $this->dscRepository->getPriceFormat($total['card_money']);
        $total['bonus_money_formated'] = $this->dscRepository->getPriceFormat($total['bonus_money']);

        return $total;
    }

    /**
     * 使用积分
     * @param $userId
     * @param $cou_id
     * @return array
     */
    public function ChangeInt($uid, $total = [], $integral_type = 0, $cart_value = [], $flow_type)
    {
        //计算订单可使用积分及积分金额
        $consignee = $this->userAddressService->getDefaultByUserId($uid);

        $warehouse_id = get_province_id_warehouse($consignee['province']);
        $area_info = RegionWarehouse::where('regionId', $consignee['province'])->first();
        $area_info = $area_info ? $area_info->toArray() : [];
        $area_info['region_id'] = isset($area_info['region_id']) ? $area_info['region_id'] : 0;
        $area_city = RegionWarehouse::where('regionId', $consignee['city'])->first();
        $area_city = $area_city ? $area_city->toArray() : [];
        $area_city['region_id'] = isset($area_city['region_id']) ? $area_city['region_id'] : 0;

        $integral = $this->flow_available_points($cart_value, $flow_type, $warehouse_id, $area_info['region_id'], $area_city['region_id'], $uid);
        $integral_money = $this->dscRepository->getValueOfIntegral($integral);

        $integral_type = intval($integral_type);

        /* 会员支付积分 */
        $pay_points = Users::where('user_id', $uid)
            ->value('pay_points');
        $pay_points = $pay_points ? $pay_points : 0;

        $total['success_type'] = 0;

        $total['bonus_money'] = $total['bonus_money'] ?? 0;
        $total['coupons_money'] = $total['coupons_money'] ?? 0;
        $total['vc_dis'] = isset($total['vc_dis']) ? round($total['vc_dis'] / 10, 2) : 1;
        $total['integral_money'] = $total['integral_money'] ?? 0;
        $total['card'] = isset($total['card']) && $total['card'] ? floatval($total['card']) : 0;
        $total['card_money'] = isset($total['card_money']) && $total['card_money'] ? floatval($total['card_money']) : 0;
        $total['discount'] = $total['discount'] ?? 0;
        $total['bonus_id'] = $total['bonus_id'] ?? 0; //红包id
        $total['coupons_id'] = $total['coupons_id'] ?? 0; //优惠券id
        $total['value_card_id'] = $total['value_card_id'] ?? 0; //储值卡id
        $total['surplus'] = $total['surplus'] ?? 0;; //余额

        $total['amount'] = isset($total['amount']) ? floatval($total['amount']) : 0;

        $amount = $total['goods_price'] - $total['discount'] - $total['bonus_money'] - $total['coupons_money'] - $total['surplus'];

        if ($total['amount'] == 0 && $total['card_money'] > 0 && $integral_money > 0 && $amount == $total['card_money']) {
            if ($total['card_money'] > $integral_money) {
                $total['card_money'] -= $integral_money;
            } else {
                $integral_money -= $total['card_money'];
                $total['card_money'] = 0;
            }
        }

        // 开通购买会员特价权益卡
        $total['order_membership_card_id'] = $total['order_membership_card_id'] ?? 0; // 权益卡id
        if (file_exists(MOBILE_DRP) && $total['order_membership_card_id'] > 0) {
            // $amount = $amount + membership_card_buy_money - membership_card_discount_price
            $total['membership_card_buy_money'] = $total['membership_card_buy_money'] ?? 0; // 权益卡购买金额
            $total['membership_card_discount_price'] = $total['membership_card_discount_price'] ?? 0;  // 权益卡购买折扣
            $amount = $amount + $total['membership_card_buy_money'] - $total['membership_card_discount_price'];
        }

        /* 选择状态 */
        if ($integral_type == 1) {
            if ($pay_points > $integral && $integral > 0) {
                $total['success_type'] = 1;

                if ($amount >= $integral_money) {
                    $total['integral'] = $integral;
                    $amount -= $integral_money;
                    $total['integral_money'] = $integral_money;
                } else {
                    $total['integral'] = $this->dscRepository->getIntegralOfValue($amount);
                    $total['integral_money'] = $amount;
                    $amount = 0;
                }
            }

            if ($total['card_money'] >= $amount) {
                $total['card_money'] = $amount;
                $total['amount'] = 0;
            } else {
                $total['amount'] = $amount - $total['card_money'];
            }
        } else {
            $total['integral_money'] = isset($total['integral_money']) ? floatval($total['integral_money']) : 0;

            if ($total['integral_money'] > 0) {
                if ($total['amount'] == 0 && $total['card_money'] > 0) {
                    $total['card_money'] += $total['integral_money'];

                    $card_money = ValueCard::where('vid', $total['value_card_id'])->value('card_money');
                    $card_money = $card_money ? $card_money : 0;

                    /* 获取用户储值卡的实际金额 */
                    if ($total['card_money'] > $card_money) {
                        $total['card_money'] = $card_money;
                        $total['amount'] = $amount - $total['card_money'];
                    }
                } else {
                    $total['amount'] += $total['integral_money'];
                }

                $total['integral'] = 0;
                $total['integral_money'] = 0;

                $total['success_type'] = 1;
            }
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $total['amount'] += $total['rate_price'];
        }
        $total['vc_dis'] = $total['vc_dis'] * 10;
        $total['amount_formated'] = $this->dscRepository->getPriceFormat($total['amount']);
        $total['integral_money_formated'] = $this->dscRepository->getPriceFormat($total['integral_money']);
        $total['card_money_formated'] = $this->dscRepository->getPriceFormat($total['card_money']);

        return $total;
    }

    /**
     * 余额使用
     * @param $userId
     * @param $total
     * @param $surplus
     * @return array
     */
    public function ChangeSurplus($uid, $total = [], $surplus = 0, $shopping_fee = 0)
    {
        // 余额使用
        $total['success_type'] = 0;
        $total['bonus_money'] = $total['bonus_money'] ?? 0;
        $total['coupons_money'] = $total['coupons_money'] ?? 0;
        $total['vc_dis'] = isset($total['vc_dis']) ? round($total['vc_dis'] / 10, 2) : 1;
        $total['integral_money'] = $total['integral_money'] ?? 0;
        $total['card'] = isset($total['card']) && $total['card'] ? floatval($total['card']) : 0;
        $total['card_money'] = isset($total['card_money']) && $total['card_money'] ? floatval($total['card_money']) : 0;
        $total['discount'] = $total['discount'] ?? 0;
        $total['bonus_id'] = $total['bonus_id'] ?? 0; //红包id
        $total['coupons_id'] = $total['coupons_id'] ?? 0; //优惠券id
        $total['value_card_id'] = $total['value_card_id'] ?? 0; //储值卡id
        $total['surplus'] = isset($total['surplus']) && $total['surplus'] ? $total['surplus'] : $surplus; //余额
        $total['amount'] = isset($total['amount']) ? floatval($total['amount']) : 0;
        $amount = $total['goods_price'] - $total['discount'] - $total['integral_money'] - $total['coupons_money'] - $total['bonus_money'];
        $amount = $amount > 0 ? $amount : 0;

        if ($total['card_money'] > 0) {
            $amount = $amount - $total['card_money'];
        }

        // 开通购买会员特价权益卡
        $total['order_membership_card_id'] = $total['order_membership_card_id'] ?? 0; // 权益卡id
        if (file_exists(MOBILE_DRP) && $total['order_membership_card_id'] > 0) {
            // $amount = $amount + membership_card_buy_money - membership_card_discount_price
            $total['membership_card_buy_money'] = $total['membership_card_buy_money'] ?? 0; // 权益卡购买金额
            $total['membership_card_discount_price'] = $total['membership_card_discount_price'] ?? 0;  // 权益卡购买折扣
            $amount = $amount + $total['membership_card_buy_money'] - $total['membership_card_discount_price'];
        }

        // 减去运费
        if ($shopping_fee > 0) {
            $amount += $shopping_fee;
        }

        $user_money = Users::where('user_id', $uid)->value('user_money');
        if ($surplus > $user_money) {
            $surplus = $user_money;
        }

        if ($surplus > 0) {
            $total['success_type'] = 1;

            if ($amount >= $surplus) {

                $total['surplus'] = $surplus;
                $amount -= $surplus;
                $total['amount'] = $amount;
            } else {
                $total['surplus'] = $amount;
                $total['amount'] = 0;
            }

        } else {
            if ($total['surplus'] > 0) {
                $total['amount'] += ($total['surplus'] - $shopping_fee);
                $total['surplus'] = 0;
                $total['success_type'] = 1;
            }
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $total['amount'] += $total['rate_price'];
        }

        $total['vc_dis'] = $total['vc_dis'] * 10;
        $total['amount_formated'] = $this->dscRepository->getPriceFormat($total['amount']);
        $total['card_money_formated'] = $this->dscRepository->getPriceFormat($total['card_money']);
        $total['surplus_formated'] = $this->dscRepository->getPriceFormat($total['surplus']);

        return $total;
    }


    /**
     * 使用储值卡
     * @param $userId
     * @param $cou_id
     * @return array
     */
    public function ChangeCard($vid, $uid, $total = [])
    {
        $card = ValueCard::from('value_card as vc')
            ->select('vc.card_money', 'vc_dis')
            ->leftjoin('value_card_type as vt', 'vt.id', 'vc.tid')
            ->where('vc.vid', $vid)
            ->where('vc.user_id', $uid)
            ->first();
        $card = $card ? $card->toArray() : '';

        $total['success_type'] = 0;

        $total['bonus_money'] = $total['bonus_money'] ?? 0;
        $total['coupons_money'] = $total['coupons_money'] ?? 0;
        $total['vc_dis'] = isset($total['vc_dis']) ? round($total['vc_dis'] / 10, 2) : 1;
        $total['integral_money'] = $total['integral_money'] ?? 0;
        $total['card'] = isset($total['card']) && $total['card'] ? floatval($total['card']) : 0;
        $total['card_money'] = isset($total['card_money']) && $total['card_money'] ? floatval($total['card_money']) : 0;
        $total['discount'] = $total['discount'] ?? 0;
        $total['bonus_id'] = isset($total['bonus_id']) && $total['bonus_id'] ? $total['bonus_id'] : 0; //红包id
        $total['coupons_id'] = $total['coupons_id'] ?? 0; //优惠券id
        $total['value_card_id'] = $vid ?? 0; //储值卡id
        $total['surplus'] = $total['surplus'] ?? 0;; //余额


        $total['amount'] = $total['goods_price'] - $total['bonus_money'] - $total['coupons_money'] - $total['discount'] - $total['integral_money'] - $total['surplus'];

        // 开通购买会员特价权益卡
        $total['order_membership_card_id'] = $total['order_membership_card_id'] ?? 0; // 权益卡id
        if (file_exists(MOBILE_DRP) && $total['order_membership_card_id'] > 0) {
            // $amount = $amount + membership_card_buy_money - membership_card_discount_price
            $total['membership_card_buy_money'] = $total['membership_card_buy_money'] ?? 0; // 权益卡购买金额
            $total['membership_card_discount_price'] = $total['membership_card_discount_price'] ?? 0;  // 权益卡购买折扣
            $total['amount'] = $total['amount'] + $total['membership_card_buy_money'] - $total['membership_card_discount_price'];
        }

        if ($vid > 0 && $card) {
            $total['success_type'] = 1;

            $total['card'] = $card['card_money'];
            if ($card['card_money'] > $total['amount'] * $card['vc_dis']) {
                $total['card_money'] = $total['amount'] * $card['vc_dis'];
                $total['amount'] = 0;
            } else {
                $total['card_money'] = $card['card_money'];
                $total['amount'] = $total['amount'] * $card['vc_dis'] - $total['card_money'];
                $total['amount'] = ($total['amount'] > 0) ? $total['amount'] : 0;
            }

            $total['vc_dis'] = $card['vc_dis'] * 10;
        } else {
            $total['card'] = 0;
            $total['card_money'] = 0;

            $total['vc_dis'] = 0;
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $total['amount'] += $total['rate_price'];
        }
        $total['card_formated'] = $this->dscRepository->getPriceFormat($total['card_money']);
        $total['amount_formated'] = $this->dscRepository->getPriceFormat($total['amount']);
        $total['card_money_formated'] = $this->dscRepository->getPriceFormat($total['card_money']);

        return $total;
    }

    /*获取可抵扣的积分金额*/
    public function AmountIntegral($uid, $rec_type, $store_id = 0)
    {
        $res = Cart::from('cart as c')
            ->select('g.integral', 'c.goods_number')
            ->leftjoin('goods as g', 'g.goods_id', 'c.goods_id')
            ->where('c.user_id', $uid)
            ->where('c.rec_type', $rec_type)
            ->where('c.is_checked', 1)
            ->where('store_id', $store_id)
            ->get();
        $res = $res ? $res->toArray() : '';

        $integral = 0;

        if ($res) {
            foreach ($res as $k => $v) {
                $integral += $v['integral'] * $v['goods_number'];
            }
        }
        return $integral;
    }

    /**
     * 获取用户积分金额等信息
     * @param int $uid
     * @return array
     */
    public function UserIntegral($uid = 0)
    {
        $res = Users::select('user_name', 'nick_name', 'pay_points', 'user_money', 'credit_line', 'email', 'mobile_phone')
            ->where('user_id', $uid)
            ->first();
        $user = $res ? $res->toArray() : [];

        if (!empty($user)) {
            $user['user_point'] = $user['pay_points'];
            $user['integral'] = $user['pay_points'] * $this->config['integral_scale'] / 100;
        }

        return $user;
    }

    /*商品单组总价*/
    public function SumGoodsPrice($arr)
    {
        $sum = 0;
        foreach ($arr as $val) {
            $sum += $val['goods_price'] * $val['goods_number'];
        }
        return $sum;
    }

    /*商品单组总数*/
    public function SumGoodsNumber($arr)
    {
        $sum = 0;
        foreach ($arr as $val) {
            $sum += $val['goods_number'];
        }
        return $sum;
    }


    /**
     * 根据用户ID获取购物车商品列表
     * @param int $uid
     * @param array $cart_goods
     * @return mixed
     */
    public function GoodsInCartByUser($uid = 0, $cart_goods = [])
    {
        $cart = [];

        if (empty($cart_goods)) {
            return [];
        }

        foreach ($cart_goods as $key => $value) {
            if (isset($value['goods_id'])) {
                $cart[$key] = $value;
            }
        }

        $now = $this->timeRepository->getGmTime();

        $total = ['goods_price' => 0, 'market_price' => 0, 'goods_number' => 0, 'presale_price' => 0, 'dis_amount' => 0, 'seller_amount' => []];

        /* 用于统计购物车中实体商品和虚拟商品的个数 */
        $virtual_goods_count = 0;
        $real_goods_count = 0;
        $goods_list = [];

        if ($cart) {
            foreach ($cart as $k => $v) {
                // 计算总价
                $total['goods_price'] += $v['goods_price'] * $v['goods_number'];
                $total['market_price'] += $v['market_price'] * $v['goods_number'];
                $total['goods_number'] += $v['goods_number'];
                $total['dis_amount'] += $v['dis_amount'];

                $total['seller_amount'][$v['ru_id']] = isset($total['seller_amount'][$v['ru_id']]) ? $total['seller_amount'][$v['ru_id']] + $v['goods_price'] * $v['goods_number'] : $v['goods_price'] * $v['goods_number'];

                if ($v['rec_type'] == CART_PRESALE_GOODS) {
                    $v['deposit'] = PresaleActivity::where('goods_id', $v['goods_id'])->where('start_time', '<', $now)->where('end_time', '>', $now)->value('deposit');

                    $total['presale_price'] += $v['deposit'] * $v['goods_number'];//预售定金

                    if ($total['presale_price'] > 0) {
                        $total['goods_price'] = $total['presale_price'];
                    }
                }

                $goods_list[$k] = $v;

                /* 统计实体商品和虚拟商品的个数 */
                if ($v['is_real']) {
                    $real_goods_count++;
                } else {
                    $virtual_goods_count++;
                }
            }
        }

        $ru_id_goods_list = [];
        $product_list = [];
        $package_goods_count = 0;
        $package_list_total = 0;

        foreach ($goods_list as $key => $row) {
            $row['goods']['rec_id'] = $row['rec_id'];
            $row['goods']['user_id'] = $row['user_id'];
            $row['goods']['cat_id'] = $row['cat_id'] ?? 0;
            $row['goods']['brand_id'] = $row['brand_id'] ?? 0;
            $row['goods']['goods_name'] = $row['goods_name'];
            $row['goods']['goods_id'] = $row['goods_id'];
            $row['goods']['market_price'] = $row['market_price'];
            $row['goods']['market_price_format'] = $this->dscRepository->getPriceFormat($row['market_price']);
            $row['goods']['goods_price'] = $row['goods_price'];
            $row['goods']['goods_price_format'] = $this->dscRepository->getPriceFormat($row['goods_price']);
            $row['goods']['goods_number'] = $row['goods_number'];
            $row['goods']['goods_attr'] = $row['goods_attr'];

            //判断商品类型，如果是超值礼包则修改链接和缩略图
            if ($row['extension_code'] == 'package_buy') {
                /* 取得礼包信息 */
                $activity_thumb = GoodsActivity::where('act_id', $row['goods_id'])->value('activity_thumb');
                $activity_thumb = $activity_thumb ? $activity_thumb : '';

                $row['goods_thumb'] = $activity_thumb;

                $package_goods = PackageGoods::select('package_id', 'goods_id', 'goods_number', 'admin_id')
                    ->where('package_id', $row['goods_id']);
                $package_goods = $package_goods->whereHas('getGoods');
                $package_goods = $package_goods->with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_sn', 'goods_name', 'goods_number as product_number', 'market_price', 'goods_thumb', 'is_real', 'shop_price');
                    }
                ]);
                $package_goods = $this->baseRepository->getToArrayGet($package_goods);
                if (!empty($package_goods)) {
                    foreach ($package_goods as $k => $val) {

                        $val = $this->baseRepository->getArrayMerge($val, $val['get_goods']);

                        unset($val['get_goods']);

                        $package_goods[$k]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                        $package_goods[$k]['market_price_format'] = $this->dscRepository->getPriceFormat($val['market_price']);
                        $package_goods[$k]['rank_price_format'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                    }
                }

                $row['goods']['package_goods_list'] = $package_goods;

                $subtotal = $row['goods_price'] * $row['goods_number'];
                $package_goods_count++;
                if (!empty($package_goods)) {
                    foreach ($package_goods as $package_goods_val) {
                        $package_goods_val['shop_price'] = $package_goods_val['shop_price'] ?? 0;
                        $package_goods_val['goods_number'] = $package_goods_val['goods_number'] ?? 1;
                        $package_list_total += $package_goods_val['shop_price'] * $package_goods_val['goods_number'];
                    }

                    $row['goods']['package_list_total'] = $package_list_total;
                    $row['goods']['package_list_saving'] = $subtotal - $package_list_total;
                    $row['goods']['format_package_list_total'] = $this->dscRepository->getPriceFormat($row['goods']['package_list_total']);
                    $row['goods']['format_package_list_saving'] = $this->dscRepository->getPriceFormat($row['goods']['package_list_saving']);
                }
            }

            $row['goods']['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $row['goods']['is_real'] = $row['is_real'];
            $row['goods']['goods_attr_id'] = $row['goods_attr_id'];
            $row['goods']['is_shipping'] = $row['is_shipping'];
            $row['goods']['ru_id'] = $row['ru_id'];
            $row['goods']['warehouse_id'] = $row['warehouse_id'];
            $row['goods']['area_id'] = $row['area_id'];
            $row['goods']['area_city'] = $row['area_city'];
            $row['goods']['stages_qishu'] = $row['stages_qishu'];
            $row['goods']['add_time'] = $row['add_time'];
            $row['goods']['goods_sn'] = $row['goods_sn'];
            $row['goods']['product_id'] = $row['product_id'];
            $row['goods']['extension_code'] = $row['extension_code'];
            $row['goods']['parent_id'] = $row['parent_id'];
            $row['goods']['is_gift'] = $row['is_gift'];
            $row['goods']['model_attr'] = $row['model_attr'];
            $row['goods']['act_id'] = $row['act_id'];
            $row['goods']['store_id'] = $row['store_id'];

            if (isset($row['membership_card_id']) && !empty($row['membership_card_id'])) {
                // 符合领取设置为指定商品的 会员权益卡id
                $row['goods']['membership_card_id'] = $row['membership_card_id'] ?? 0;
                $row['goods']['membership_card_name'] = UserMembershipCard::where('id', $row['membership_card_id'])->value('name');
            }

            // 按ru_id分组
            $ru_id_goods_list[$row['ru_id']]['shop_name'] = $row['shop_name'];
            $ru_id_goods_list[$row['ru_id']]['user_id'] = $row['user_id'];
            $ru_id_goods_list[$row['ru_id']]['ru_id'] = $row['ru_id'];
            $ru_id_goods_list[$row['ru_id']]['goods'][] = $row['goods'];

            $product_list[$key]['goods'] = $row['goods'];
        }

        foreach ($ru_id_goods_list as $key => $value) {
            //商家配送方式
            $shipping = ShippingArea::from('shipping_area as sa')
                ->select('sa.*', 's.shipping_id', 's.shipping_name', 's.insure')
                ->leftjoin('shipping as s', 's.shipping_id', 'sa.shipping_id')
                ->where('sa.ru_id', $value['ru_id'])
                ->get();
            $shipping = $shipping ? $shipping->toArray() : [];

            $ship = [];
            if ($shipping) {
                foreach ($shipping as $k => $val) {
                    if ($val['ru_id'] == $value['ru_id']) {
                        $val['shipping']['ru_id'] = $val['ru_id'];
                        $val['shipping']['configure'] = $val['configure'];
                        $ship[] = $val['shipping'];
                    }
                }
            }

            $ru_id_goods_list[$key]['shop_info'] = $ship;
        }

        /* 计算折扣 */
        $total['discount'] = $this->computeDiscountCheck($ru_id_goods_list, $uid);
        if ($total['discount'] > $total['goods_price']) {
            $total['discount'] = $total['goods_price'];
        }

        $total['discount'] = $total['discount'] + $total['dis_amount'];

        $total['saving'] = round($total['market_price'] - $total['goods_price'], 2);
        if ($total['saving'] > 0) {
            $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;
        }
        $total['saving_formated'] = $this->dscRepository->getPriceFormat($total['saving'], false);
        $total['discount_formated'] = $this->dscRepository->getPriceFormat($total['discount'], false);
        $total['dis_amount_formated'] = $this->dscRepository->getPriceFormat($total['dis_amount'], false);
        $total['goods_price_formated'] = $this->dscRepository->getPriceFormat($total['goods_price'], false);
        $total['market_price_formated'] = $this->dscRepository->getPriceFormat($total['market_price'], false);
        $total['real_goods_count'] = $real_goods_count;
        $total['virtual_goods_count'] = $virtual_goods_count;

        return ['goods_list' => $ru_id_goods_list, 'total' => $total, 'product' => $product_list, 'get_goods_list' => $goods_list];
    }

    /**
     * 计算购物车中的商品能享受红包支付的总额
     * @param array $goods_list
     * @param int $uid
     * @return
     */
    public function computeDiscountCheck($goods_list = [], $uid = 0)
    {
        /* 查询优惠活动 */
        $now = $this->timeRepository->getGmTime();

        if (isset($uid) && $uid > 0) {
            $rank = $this->userCommonService->getUserRankByUid($uid);
            $data['rank_id'] = isset($rank['rank_id']) ? $rank['rank_id'] : 1;
            $data['discount'] = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $data['rank_id'] = 1;
            $data['discount'] = 100;
        }

        $user_rank = ',' . $data['rank_id'] . ',';

        $favourable_list = FavourableActivity::where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
            ->wherein('act_type', [FAT_DISCOUNT, FAT_PRICE])
            ->get();

        $favourable_list = $favourable_list ? $favourable_list->toArray() : [];

        if (empty($favourable_list)) {
            return 0;
        }

        if (empty($goods_list)) {
            return 0;
        }

        foreach ($goods_list as $key => $good) {
            foreach ($good['goods'] as $k => $v) {
                $good_property = [];
                if ($v['goods_attr_id']) {
                    $good_property = explode(',', $v['goods_attr_id']);
                }
                if ($v['extension_code'] != 'package_buy') {
                    $goods_list[$key]['price'] = $this->getFinalPrice($uid, $v['goods_id'], $v['goods_number'], true, $good_property, $v['warehouse_id'], $v['area_id'], $v['area_city']);
                    $goods_list[$key]['amount'] = $v['goods_number'];
                }
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
                    if (($favourable['userFav_type'] == 1 && $goods['ru_id'] == 0) || $favourable['userFav_type'] == 0) {
                        foreach ($goods['goods'] as $v) {
                            if (isset($v['act_id']) && $v['act_id'] == $favourable['act_id']) {
                                $total_amount += $v['goods_price'] * $v['goods_number'];
                            }
                        }
                    }
                }
            } elseif ($favourable['act_range'] == FAR_CATEGORY) {
                // /* 找出分类id的子分类id */
                $id_list = [];
                $raw_id_list = explode(',', $favourable['act_range_ext']);
                foreach ($raw_id_list as $id) {
                    $cat_list = $this->categoryService->getCatListChildren($id);
                    $id_list = array_merge($id_list, $cat_list);
                    array_unshift($id_list, $id);
                }
                $ids = join(',', array_unique($id_list));
                foreach ($goods_list as $goods) {
                    if (($favourable['userFav_type'] == 1 && $goods['ru_id'] == 0) || $favourable['userFav_type'] == 0) {
                        foreach ($goods['goods'] as $v) {
                            if (isset($v['act_id']) && $v['act_id'] == $favourable['act_id']) {
                                if (isset($v['cat_id']) && strpos(',' . $ids . ',', ',' . $v['cat_id'] . ',') !== false) {
                                    $total_amount += $v['goods_price'] * $v['goods_number'];
                                }
                            }
                        }
                    }
                }
            } elseif ($favourable['act_range'] == FAR_BRAND) {
                foreach ($goods_list as $goods) {
                    if (($favourable['userFav_type'] == 1 && $goods['ru_id'] == 0) || $favourable['userFav_type'] == 0) {
                        foreach ($goods['goods'] as $v) {
                            if (isset($v['act_id']) && $v['act_id'] == $favourable['act_id']) {
                                if (isset($v['brand_id']) && strpos(',' . $favourable['act_range_ext'] . ',', ',' . $v['brand_id'] . ',') !== false) {
                                    $total_amount += $v['goods_price'] * $v['goods_number'];
                                }
                            }
                        }
                    }
                }
            } elseif ($favourable['act_range'] == FAR_GOODS) {
                foreach ($goods_list as $goods) {
                    if (($favourable['userFav_type'] == 1 && $goods['ru_id'] == 0) || $favourable['userFav_type'] == 0) {
                        foreach ($goods['goods'] as $v) {
                            if (isset($v['act_id']) && $v['act_id'] == $favourable['act_id']) {
                                if (isset($v['goods_id']) && strpos(',' . $favourable['act_range_ext'] . ',', ',' . $v['goods_id'] . ',') !== false) {
                                    $total_amount += $v['goods_price'] * $v['goods_number'];
                                }
                            }
                        }
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
     * 获取商品分类树
     * @param int $cat_id
     * @return array
     */
    public function catList($cat_id = 0)
    {
        $arr = [];
        $count = Category::where('parent_id', $cat_id)
            ->where('is_show', 1)
            ->count();
        if ($count > 0) {
            $res = Category::select('cat_id', 'cat_name', 'touch_icon', 'parent_id', 'cat_alias_name', 'is_show')
                ->where('parent_id', $cat_id)
                ->where('is_show', 1)
                ->orderby('sort_order', 'ASC')
                ->orderby('cat_id', 'ASC')
                ->get()
                ->toArray();

            if ($res === null) {
                return [];
            }

            foreach ($res as $key => $row) {
                if (isset($row['cat_id'])) {
                    $arr[$row['cat_id']]['cat_id'] = $row['cat_id'];
                    $child_tree = $this->catList($row['cat_id']);
                    if ($child_tree) {
                        $arr[$row['cat_id']]['child_tree'] = $child_tree;
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 取得商品最终使用价格
     *
     * @param $uid
     * @param $goods_id 商品编号
     * @param string $goods_num 购买数量
     * @param bool $is_spec_price 是否加入规格价格
     * @param array $property 规格ID的数组或者逗号分隔的字符串
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return float|int|mixed
     */
    public function getFinalPrice($uid, $goods_id, $goods_num = '1', $is_spec_price = false, $property = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $final_price = 0; //商品最终购买价格
        $volume_price = 0; //商品优惠价格
        $promote_price = 0; //商品促销价格
        $user_price = 0; //商品会员价格
        $spec_price = 0;

        //如果需要加入规格价格
        if ($is_spec_price) {
            $spec_price = $this->goodsProdutsService->goodsPropertyPrice($goods_id, $property, $warehouse_id, $area_id, $area_city);
        }

        //取得商品优惠价格列表
        $price_list = $this->goodsCommonService->getVolumePriceList($goods_id);
        if (!empty($price_list)) {
            foreach ($price_list as $value) {
                if ($goods_num >= $value['number']) {
                    $volume_price = $value['price'];
                }
            }
        }

        if ($uid > 0) {
            $rank = $this->userCommonService->getUserRankByUid($uid);
            $rank_id = isset($rank['rank_id']) ?? 1;
            $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
        } else {
            $rank_id = 1;
            $user_discount = 100;
        }

        $goods = Goods::where('goods_id', $goods_id);

        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $goods = $goods->with([
            'getMemberPrice' => function ($query) use ($rank_id) {
                $query->where('user_rank', $rank_id);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
        ]);

        $goods = $this->baseRepository->getToArrayFirst($goods);

        $promote_price = 0;
        if ($goods) {
            $goods['user_price'] = $goods['get_member_price'] ? $goods['get_member_price']['user_price'] : 0;

            $price = [
                'model_price' => isset($goods['model_price']) ? $goods['model_price'] : 0,
                'user_price' => isset($goods['get_member_price']['user_price']) ? $goods['get_member_price']['user_price'] : 0,
                'percentage' => isset($goods['get_member_price']['percentage']) ? $goods['get_member_price']['percentage'] : 0,
                'warehouse_price' => isset($goods['get_warehouse_goods']['warehouse_price']) ? $goods['get_warehouse_goods']['warehouse_price'] : 0,
                'region_price' => isset($goods['get_warehouse_area_goods']['region_price']) ? $goods['get_warehouse_area_goods']['region_price'] : 0,
                'shop_price' => isset($goods['shop_price']) ? $goods['shop_price'] : 0,
                'warehouse_promote_price' => isset($goods['get_warehouse_goods']['warehouse_promote_price']) ? $goods['get_warehouse_goods']['warehouse_promote_price'] : 0,
                'region_promote_price' => isset($goods['get_warehouse_area_goods']['region_promote_price']) ? $goods['get_warehouse_area_goods']['region_promote_price'] : 0,
                'promote_price' => isset($goods['promote_price']) ? $goods['promote_price'] : 0,
                'wg_number' => isset($goods['get_warehouse_goods']['region_number']) ? $goods['get_warehouse_goods']['region_number'] : 0,
                'wag_number' => isset($goods['get_warehouse_area_goods']['region_number']) ? $goods['get_warehouse_area_goods']['region_number'] : 0,
                'goods_number' => isset($goods['goods_number']) ? $goods['goods_number'] : 0
            ];

            $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount / 100, $goods);

            $goods['shop_price'] = $price['shop_price'];
            $goods['promote_price'] = $price['promote_price'];
            $goods['goods_number'] = $price['goods_number'];

            if ($goods['promote_price'] > 0) {
                $promote_price = $this->goodsCommonService->getBargainPrice($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
            } else {
                $promote_price = 0;
            }
        } else {
            $goods['user_price'] = 0;
            $goods['shop_price'] = 0;
        }

        /* 计算商品的促销价格 */
        sort($property);
        if ($this->config['add_shop_price'] == 0) {
            $promote_price = $this->goodsProdutsService->goodsPropertyPrice($goods_id, $property, $warehouse_id, $area_id, $area_city, 'product_promote_price');
        }

        $time = $this->timeRepository->getGmTime();
        $now_promote = 0;
        //当前商品正在促销时间内
        if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date'] && $goods['is_promote']) {
            $now_promote = 1;
        }

        //取得商品会员价格列表
        if (!empty($is_spec_price) && $this->config['add_shop_price'] == 0) {
            /* 会员等级价格 */

            if ($goods['user_price'] > 0 && $goods['user_price'] < $spec_price) {
                $user_price = $goods['user_price'];
            } else {
                if ($now_promote == 1) {
                    $user_price = $promote_price;
                } else {
                    $user_price = $spec_price * $user_discount / 100;
                }
            }
        } else {
            $user_price = $goods['shop_price'];
        }

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && $now_promote == 0) {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        } elseif (!empty($volume_price) && $now_promote == 0) {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        } elseif (empty($volume_price) && $now_promote == 1) {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        } elseif (!empty($volume_price) && $now_promote == 1) {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        } else {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($property)) {
                if ($this->config['add_shop_price'] == 1) {
                    $final_price += $spec_price;
                }
            }
        }

        if ($this->config['add_shop_price'] == 0) {
            if ($promote_price == 0 && $now_promote == 0) {
                //返回商品属性价
                $final_price = $spec_price * $user_discount / 100;
            }
        }

        //返回商品最终购买价格
        return $final_price;
    }

    //提交订单配送方式 --ecmoban模板堂 --zhuo
    public function get_order_post_shipping($shipping, $shippingCode = [], $shippingType = [], $ru_id = 0)
    {
        $shipping_list = [];
        if ($shipping) {
            $shipping_id = '';
            $shipping_name = '';
            $shipping_code = '';
            $shipping_type = '';
            $support_cod = '';
            foreach ($shipping as $k1 => $v1) {
                $v1 = !empty($v1) ? intval($v1) : 0;
                $shippingCode[$k1] = !empty($shippingCode[$k1]) ? addslashes($shippingCode[$k1]) : '';
                $shippingType[$k1] = empty($shippingType[$k1]) ? 0 : intval($shippingType[$k1]);

                $shippingInfo = $this->shipping_info($v1);

                foreach ($ru_id as $k2 => $v2) {
                    if ($k1 == $k2) {
                        $shipping_id .= $v2 . "|" . $v1 . ",";  //商家ID + 配送ID
                        $shipping_name .= $v2 . "|" . $shippingInfo['shipping_name'] . ",";  //商家ID + 配送名称
                        $shipping_code .= $v2 . "|" . $shippingCode[$k1] . ",";  //商家ID + 配送code
                        $shipping_type .= $v2 . "|" . $shippingType[$k1] . ",";  //商家ID + （配送或自提）
                        $support_cod = $shippingInfo['support_cod'];
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
                'shipping_type' => $shipping_type,
                'support_cod' => $support_cod
            ];
        }
        return $shipping_list;
    }

    /**
     * 取得配送方式信息
     * @param int $shipping 配送方式id
     * @return  array   配送方式信息
     */
    public function shipping_info($shipping, $select = [])
    {
        $row = Shipping::where('enabled', 1);

        $where = '';
        if (is_array($shipping)) {
            if (isset($shipping['shipping_code'])) {
                $row = $row->where('shipping_code', $shipping['shipping_code']);
            } elseif (isset($shipping['shipping_id'])) {
                $row = $row->where('shipping_id', $shipping['shipping_id']);
            }
        } else {
            $row = $row->where('shipping_id', $shipping);
        }

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        if (!empty($row)) {
            $row['pay_fee'] = 0.00;
        }

        return $row;
    }

    /*根据地区ID获取具体配送地区*/
    public function DeliveryArea($region_id)
    {
        $res = Region::where('region_id', $region_id)
            ->value('region_name');
        return $res;
    }

    /**
     * 过滤用户输入的基本数据，防止script攻击
     *
     * @access      public
     * @return      string
     */
    public function compile_str($str)
    {
        $arr = ['<' => '＜', '>' => '＞', '"' => '”', "'" => '’'];

        return strtr($str, $arr);
    }

    //提交订单自提点分单
    public function get_order_points($point_id_arr, $shipping_dateStr_arr, $ru_id = [])
    {
        $points_info = [];
        if ($point_id_arr) {
            $point_id = '';
            $shipping_dateStr = '';
            foreach ($point_id_arr as $k1 => $v1) {
                $v1 = !empty($v1) ? $v1 : 0;
                $shipping_dateStr_arr[$k1] = !empty($shipping_dateStr_arr[$k1]) ? addslashes($shipping_dateStr_arr[$k1]) : '';
                foreach ($ru_id as $k2 => $v2) {
                    if ($k1 == $k2) {
                        $point_id .= $v2 . "|" . $v1 . ",";  //商家ID + 配送ID
                        $shipping_dateStr .= $v2 . "|" . $shipping_dateStr_arr[$k1] . ",";  //商家ID + 配送名称
                    }
                }
            }

            $point_id = substr($point_id, 0, -1);
            $shipping_dateStr = substr($shipping_dateStr, 0, -1);
            $points_info = [
                'point_id' => $point_id,
                'shipping_dateStr' => $shipping_dateStr,
            ];
        }
        return $points_info;
    }

    //提交订单买家留言
    public function get_order_post_postscript($postscript, $ru_id = [])
    {
        $postscript_value = '';
        if ($postscript) {
            foreach ($postscript as $k1 => $v1) {
                $v1 = !empty($v1) ? trim($v1) : '';
                foreach ($ru_id as $k2 => $v2) {
                    if ($k1 == $k2) {
                        $postscript_value .= $v2 . "|" . $v1 . ",";  //商家ID + 留言内容
                    }
                }
            }

            $postscript_value = substr($postscript_value, 0, -1);
        }

        return $postscript_value;
    }

    /**
     * 查询配送区域属于哪个办事处管辖
     * @param array $regions 配送区域（1、2、3、4级按顺序）
     * @return  int     办事处id，可能为0
     */
    public function get_agency_by_regions($regions)
    {
        if (!is_array($regions) || empty($regions)) {
            return 0;
        }

        $regions = !is_array($regions) ? explode(',', $regions) : $regions;

        $res = Region::whereIn('region_id', $regions)
            ->where('region_id', '>', 0)
            ->where('agency_id', '>', 0);
        $res = $res->get();

        $res = $res ? $res->toArray() : [];

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
     * 取得红包信息
     * @param int $bonus_id 红包id
     * @param string $bonus_sn 红包序列号
     * @param array   红包信息
     */
    public function bonus_info($bonus_id, $bonus_psd = '', $cart_value = 0)
    {
        $goods_user = '';
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
                $goods_user = $this->dscRepository->delStrComma($goods_user);
            }
        }

        if ($bonus_id > 0) {
            $bonus = UserBonus::where('bonus_id', $bonus_id);
        } else {
            $bonus = UserBonus::where('bonus_password', $bonus_psd);
        }

        if ($goods_user) {
            $goods_user = !is_array($goods_user) ? explode(",", $goods_user) : $goods_user;

            $bonus = $bonus->whereHas('getBonusType', function ($query) use ($goods_user) {
                $query->where('review_status', 3)
                    ->whereRaw("IF(t.usebonus_type > 0, t.usebonus_type = 1, t.user_id in($goods_user))");
            });

            $bonus = $bonus->with([
                'getBonusType' => function ($query) {
                    $query->selectRaw("*, user_id AS admin_id");
                }
            ]);
        }

        $bonus = $bonus->first();

        $bonus = $bonus ? $bonus->toArray() : [];

        $bonus = isset($bonus['get_bonus_type']) ? array_merge($bonus, $bonus['get_bonus_type']) : $bonus;

        return $bonus;
    }

    /**
     * 取得购物车总金额
     * @params  boolean $include_gift   是否包括赠品
     * @param int $type 类型：默认普通商品
     * @return  float   购物车总金额
     */
    public function cart_amount($uid, $include_gift = true, $type = CART_GENERAL_GOODS, $cart_value = '')
    {
        $res = Cart::where('user_id', $uid);

        if (!$include_gift) {
            $res = $res->where('is_gift', 0)->where('goods_id', '>', 0);
        }

        if ($cart_value) {
            $res = $res->whereIn('rec_id', $cart_value);
        }
        $res = $res->sum('goods_price', '*', 'goods_number');

        return $res;
    }

    /**
     * 设置优惠券为已使用
     * @param int $bonus_id 优惠券id
     * @param int $order_id 订单id
     * @return  bool
     */
    public function use_coupons($cou_id, $order_id)
    {
        $time = $this->timeRepository->getGmTime();

        $res = CouponsUser::where('uc_id', $cou_id)
            ->update(['order_id' => $order_id, 'is_use_time' => $time, 'is_use' => 1]);

        return $res;
    }

    /**
     * 设置红包为已使用
     * @param int $bonus_id 红包id
     * @param int $order_id 订单id
     * @return  bool
     */
    public function use_bonus($bonus_id, $order_id)
    {
        $time = $this->timeRepository->getGmTime();

        $res = UserBonus::where('bonus_id', $bonus_id)
            ->update(['order_id' => $order_id, 'used_time' => $time]);

        return $res;
    }

    public function combination($goods = [])
    {
        $res = [];
        foreach ($goods as $k => $v) {
            $res[$k] = $v['rec_id'];
        }

        return implode(',', $res);
    }

    public function get_region_name($region_id)
    {
        return Region::where('region_id', $region_id)->value('region_name');
    }

    /**
     * 获得用户的可用积分
     *
     * @access private
     * @return integral
     */
    public function flow_available_points($cart_value, $flow_type = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $user_id = 0)
    {
        $res = Cart::from('cart as c')
            ->select('g.model_price', 'g.integral', 'wg.pay_integral as wg_pay_integral', 'wag.pay_integral as  wag_pay_integral', 'c.goods_number')
            ->leftJoin('goods as g', 'c.goods_id', '=', 'g.goods_id');

        if ($cart_value) {
            $res = $res->whereIn('c.rec_id', $cart_value);
        }

        $area_pricetype_config = $this->config['area_pricetype'];
        $res = $res->leftJoin('warehouse_goods as wg', function ($join) use ($warehouse_id) {
            $join->on('g.goods_id', '=', 'wg.goods_id')->Where('wg.region_id', $warehouse_id);
        })->leftJoin('warehouse_area_goods as wag', function ($join) use ($area_id, $area_city, $area_pricetype_config) {
            $join->on('g.goods_id', '=', 'wag.goods_id')->Where('wag.region_id', $area_id);
            if ($area_pricetype_config == 1) {
                $join->where('wag.city_id', $area_city);
            }
        });

        if (!empty($user_id)) {
            $res = $res->where('c.user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = $res->where('c.session_id', $session_id);
        }

        $res = $res->where('c.rec_type', $flow_type)
            ->get();
        $res = $res ? $res->toArray() : [];


        $result = 0;
        if ($res) {
            foreach ($res as $key => $val) {
                $ar = 0;
                if ($val['model_price'] < 1) {
                    $ar = $val['integral'];
                } else {
                    if ($val['model_price'] < 2) {
                        $ar = $val['wg_pay_integral'];
                    } else {
                        $ar = $val['wag_pay_integral'];
                    }
                }
                if ($ar > 0) {
                    $result += $val['integral'] * $val['goods_number'];
                }
            }
        }

        $scale = 0;
        if ($result) {
            $scale = $this->config['integral_scale'];
        }
        return $scale > 0 ? round($result / $scale * 100) : 0;
    }

    public function get_flow_cart_stock($arr, $store_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $user_id = 0, $flow_type = 0)
    {
        $lang = lang('shopping_flow');

        if ($arr) {
            foreach ($arr as $rec_id => $val) {
                $val = intval(make_semiangle($val));
                if ($val <= 0 || !is_numeric($rec_id)) {
                    continue;
                }

                $where = [
                    'rec_id' => $rec_id,
                    'user_id' => $user_id,
                    'rec_type' => $flow_type
                ];

                $goods = $this->get_cart_info($where);

                if (empty($goods)) {
                    continue;
                }
                $goods_info = [];
                $goods_info['goods_id'] = $goods['goods_id'];
                if ($store_id > 0) {
                    $goods_number = StoreGoods::where('goods_id', $goods['goods_id'])
                        ->where('store_id', $store_id)
                        ->value('goods_number');
                } else {
                    $where = [
                        'area_pricetype' => $this->config['area_pricetype'],
                        'warehouse_id' => $warehouse_id,
                        'area_id' => $area_id,
                        'area_city' => $area_city
                    ];

                    $goods_info = Goods::where('goods_id', $goods['goods_id']);

                    $goods_info = $goods_info->with([
                        'getWarehouseGoods' => function ($query) use ($where) {
                            if (isset($where['warehouse_id'])) {
                                $query->where('region_id', $where['warehouse_id']);
                            }
                        },
                        'getWarehouseAreaGoods' => function ($query) use ($where) {
                            $query = $query->where('region_id', $where['area_id']);

                            if ($where['area_pricetype'] == 1) {
                                $query->where('city_id', $where['area_city']);
                            }
                        }
                    ]);

                    $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

                    $goods_number = 0;
                    if ($goods_info) {
                        /* 库存 */
                        if (isset($goods_info['wg_number']) && $goods_info['model_price'] == 1) {
                            $goods_number = $goods_info['get_warehouse_goods']['region_number'] ?? 0;
                        } elseif (isset($goods_info['wag_number']) && $goods_info['model_price'] == 2) {
                            $goods_number = $goods_info['get_warehouse_area_goods']['region_number'] ?? 0;
                        } else {
                            $goods_number = $goods_info['goods_number'];
                        }
                    }
                }
                $goods_info['goods_number'] = $goods_number;

                $goods['product_id'] = intval($goods['product_id']);

                //系统启用了库存，检查输入的商品数量是否有效
                if (intval($this->config['use_storage']) > 0 && $goods['extension_code'] != 'package_buy' && $store_id == 0) {
                    /* 是货品 */
                    if (!empty($goods['product_id'])) {
                        $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_info['goods_id'], $goods['goods_attr_id'], $warehouse_id, $area_id, $area_city, '', $store_id); //获取属性库存
                        $product_number = $products ? $products['product_number'] : 0;

                        if ($product_number < $val) {
                            $msg = sprintf(
                                $lang['stock_insufficiency'],
                                '"' . mb_substr($goods_info['goods_name'], 0, 10) . '...' . '"',
                                $product_number,
                                $product_number
                            );
                            $result = ['msg' => $msg, 'error' => 1];
                            return $result;
                        }
                    } else {
                        if ($goods_info['goods_number'] < $val) {
                            $msg = sprintf(
                                $lang['stock_insufficiency'],
                                '"' . mb_substr($goods_info['goods_name'], 0, 10) . '...' . '"',
                                $goods_info['goods_number'],
                                $goods_info['goods_number']
                            );
                            $result = ['msg' => $msg, 'error' => 1];
                            return $result;
                        }
                    }
                } elseif (intval($this->config['use_storage']) > 0 && $store_id > 0) {
                    $goodsInfo = StoreGoods::where('store_id', $store_id)->where('goods_id', $goods_info['goods_id'])->first();
                    $goodsInfo = $goodsInfo ? $goodsInfo->toArray() : [];

                    $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_info['goods_id'], $goods['goods_attr_id'], $warehouse_id, $area_id, $area_city, '', $store_id); //获取属性库存
                    $attr_number = $products ? $products['product_number'] : 0;
                    if ($goods['goods_attr_id']) { //当商品没有属性库存时
                        $goods_info['goods_number'] = $attr_number;
                    } else {
                        $goods_info['goods_number'] = $goodsInfo['goods_number'];
                    }
                    if ($goods_info['goods_number'] < $val) {
                        $msg = sprintf(
                            $lang['stock_store_shortage'],
                            '"' . mb_substr($goods_info['goods_name'], 0, 10) . '...' . '"',
                            $goods_info['goods_number'],
                            $goods_info['goods_number']
                        );
                        $result = ['msg' => $msg, 'error' => 1];
                        return $result;
                    }
                } elseif (intval($this->config['use_storage']) > 0 && $goods['extension_code'] == 'package_buy') {
                    if ($this->packageGoodsService->judgePackageStock($goods['goods_id'], $val)) {
                        $msg = $lang['package_stock_insufficiency'];
                        $result = ['msg' => $msg, 'error' => 1];
                        return $result;
                    }
                } elseif ($goods_info['cloud_id'] > 0) {
                    $cloud_number = 0;

                    if ($cloud_number < $val) {
                        $msg = sprintf(
                            $lang['stock_insufficiency'],
                            '"' . mb_substr($goods_info['goods_name'], 0, 10) . '...' . '"',
                            $cloud_number,
                            $cloud_number
                        );
                        $result = ['msg' => $msg, 'error' => 1];
                        return $result;
                    }
                }
            }
        }
    }

    /**
     * 购物车商品信息
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function get_cart_info($where = [])
    {
        $user_id = isset($where['user_id']) ? $where['user_id'] : 0;
        $where['rec_type'] = isset($where['rec_type']) ? $where['rec_type'] : CART_GENERAL_GOODS;

        $row = Cart::selectRaw("*, COUNT(*) AS cart_number, SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount")
            ->where('rec_type', $where['rec_type']);

        /* 附加查询条件 start */
        if (isset($where['rec_id'])) {
            $where['rec_id'] = !is_array($where['rec_id']) ? explode(",", $where['rec_id']) : $where['rec_id'];

            if (is_array($where['rec_id'])) {
                $row = $row->whereIn('rec_id', $where['rec_id']);
            } else {
                $row = $row->where('rec_id', $where['rec_id']);
            }
        }

        if (isset($where['stages_qishu'])) {
            $row = $row->where('stages_qishu', $where['stages_qishu']);
        }

        if (isset($where['store_id'])) {
            $row = $row->where('store_id', $where['store_id']);
        } else {
            $row = $row->where('store_id', 0);
        }
        /* 附加查询条件 end */

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $row = $row->where('session_id', $session_id);
        }

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        return $row;
    }

    /**
     * 使用余额支付
     * @param $uid
     * @param string $order_sn
     * @return array
     */
    public function Balance($uid, $order_sn = '')
    {
        $order = [];
        $time = $this->timeRepository->getGmTime();

        if ($order_sn) {
            $order_info = OrderInfo::where('order_sn', $order_sn)
                ->where('user_id', $uid)
                ->first();
            $order_info = $order_info ? $order_info->toArray() : '';

            if ($order_info && ($order_info['order_status'] != OS_CONFIRMED || $order_info['pay_status'] != PS_PAYED)) {//订单详情

                $user_info = $this->UserIntegral($uid);

                /* 如果全部使用余额支付，检查余额是否足够 */
                if ($order_info['order_amount'] > 0) {
                    if ($order_info['order_amount'] > ($user_info['user_money'] + $user_info['credit_line'])) {
                        $order_info['surplus'] = $user_info['user_money'];
                        $order_info['order_amount'] = $order_info['order_amount'] - $user_info['user_money'];
                        //ecmoban模板堂 --zhuo
                        return ['msg' => L('balance_not_enough')];
                    } else {
                        $order['surplus'] = $order_info['surplus'] + $order_info['order_amount'];
                        $pay_money = $order_info['order_amount'] * (-1);
                        $order['order_amount'] = 0;
                    }

                    $payment = payment_info('balance', 1);
                    $order['pay_name'] = addslashes($payment['pay_name']);
                    $order['pay_id'] = $payment['pay_id'];
                    $order['is_online'] = 0;
                    $order['pay_name'] = $payment['pay_name'];
                    $order['pay_code'] = $payment['pay_code'];
                    $order['pay_desc'] = $payment['pay_desc'] ?? '';
                }

                /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
                if ($order['order_amount'] <= 0) {
                    $order['order_status'] = OS_CONFIRMED;
                    $order['confirm_time'] = $time;
                    $order['pay_status'] = PS_PAYED;
                    $order['pay_time'] = $time;
                    $order['order_amount'] = 0;
                    $order['is_surplus'] = 1;

                    if ($order_info['main_count'] > 0) {
                        $order['main_pay'] = 2;
                    }
                }
                //更新余额
                log_account_change($uid, $pay_money, 0, 0, 0, sprintf(lang('presale.presale_pay_end_money'), $order_sn));

                update_order($order_info['order_id'], $order);
            } else {
                $order = $order_info;
            }
        }

        return $order;
    }

    /**再次购买
     * @param $user_id
     * @param int $order_id
     * @return array
     * 最小起订量、限购、促销价格、会员价
     */
    public function BuyAgain($user_id, $order_id = 0, $from = 'wap')
    {
        $result = ['error' => 0, 'msg' => ''];
        if ($user_id && $order_id) {
            $res = OrderGoods::select(['goods_id', 'product_id', 'goods_attr', 'goods_attr_id', 'warehouse_id', 'area_id', 'ru_id', 'area_city', 'extension_code'])
                ->where(['user_id' => $user_id, 'order_id' => $order_id])
                ->with([
                    'getGoods' => function ($query) {
                        $query->selectRaw("*, goods_id AS id");
                    }
                ]);
            $res = $this->baseRepository->getToArrayGet($res);

            $cant_buy_goods = [];//不可以购买的商品
            $time = $this->timeRepository->getGmTime();
            if ($res) {
                //PC购物车勾选是通过session中的cart_value的值
                if ($from === 'pc') {
                    session(['cart_value' => '']);
                } else {
                    //把所有此用户的购物车商品全部取消勾选
                    Cart::where('user_id', $user_id)->update(['is_checked' => 0]);
                }

                foreach ($res as $key => $row) {
                    //检查商品的库存是否可以再次购买
                    if ($row['product_id']) {
                        $num = Products::where('product_id', $row['product_id'])->where('goods_id', $row['goods_id'])->value('product_number');
                        //库存为0，把数组赋值给不可以购买的商品数组
                        if ($num <= 0) {
                            $cant_buy_goods[$key] = $row;
                            continue;
                        }
                    } else {
                        $num = Goods::where('goods_id', $row['goods_id'])->value('goods_number');
                        //库存为0，把数组赋值给不可以购买的商品数组
                        if ($num <= 0) {
                            $cant_buy_goods[$key] = $row;
                            continue;
                        }
                    }

                    $row = array_merge($row, $row['get_goods']);
                    unset($row['get_goods']);

                    //检查商品是否上架，删除,开启限购并且当前时间正在限购时间内，开启最小起订量，并且在规定时间内
                    if ($row['is_delete'] == 1 || $row['is_show'] == 0 || $row['is_alone_sale'] == 0 || $row['extension_code'] != '' || ($row['is_xiangou'] == 1 && $row['xiangou_start_date'] < $time && $row['xiangou_end_date'] > $time) || ($row['is_minimum'] == 1 && $row['minimum_start_date'] < $time && $row['minimum_end_date'] > $time)) {
                        $cant_buy_goods[$key] = $row;
                        continue;
                    }

                    $arr = isset($row['goods_attr_id']) ? explode(',', $row['goods_attr_id']) : [];
                    $final_price = $this->getFinalPrice($user_id, $row['goods_id'], 1, true, $arr, $row['warehouse_id'], $row['area_id'], $row['area_city']);

                    $add = Cart::where(['goods_id' => $row['goods_id'], 'user_id' => $user_id]);
                    if ($row['product_id']) {
                        $add = $add->where('product_id', $row['product_id']);
                    }
                    $add = $add->value('rec_id');

                    //如果购物车已有此商品则添加一个数量
                    if ($add > 0) {
                        //更新购物车数量及增加勾选
                        $up = Cart::where(['goods_id' => $row['goods_id'], 'user_id' => $user_id]);
                        if ($row['product_id']) {
                            $up = $up->where('product_id', $row['product_id']);
                        }

                        //pc和手机端勾选不同
                        if ($from === 'pc') {
                            $this->cartCommonService->getCartValue($add);
                            $up->increment('goods_number', 1);
                        } else {
                            $up->increment('goods_number', 1, ['is_checked' => 1]);
                        }
                    } else {
                        //如果购物车无此商品，则插入
                        $cart = [
                            'user_id' => $user_id,
                            'session_id' => $user_id,
                            'goods_id' => $row['goods_id'],
                            'goods_sn' => $row['goods_sn'],
                            'product_id' => $row['product_id'],
                            'goods_name' => $row['goods_name'],
                            'market_price' => $row['market_price'],
                            'goods_attr' => $row['goods_attr'],
                            'goods_attr_id' => $row['goods_attr_id'],
                            'is_real' => 1,
                            'model_attr' => 0,
                            'warehouse_id' => '0',
                            'area_id' => '0',
                            'ru_id' => $row['ru_id'],
                            'extension_code' => '',
                            'is_gift' => 0,
                            'is_shipping' => 0,
                            'rec_type' => '0',
                            'add_time' => $time,
                            'freight' => $row['freight'],
                            'tid' => $row['tid'],
                            'shipping_fee' => $row['shipping_fee'],
                            'commission_rate' => $row['commission_rate'],
                            'store_id' => 0,
                            'store_mobile' => 0,
                            'take_time' => '',
                            'goods_price' => $final_price,
                            'goods_number' => '1',
                            'parent_id' => 0,
                            'stages_qishu' => '-1',
                            'is_checked' => 1
                        ];

                        //pc和手机端勾选不同
                        if ($from === 'pc') {
                            $new_cart = $this->baseRepository->getArrayfilterTable($cart, 'cart');
                            $rec_id = Cart::insertGetId($new_cart);

                            if ($rec_id > 0) {
                                $this->cartRepository->pushCartValue($rec_id);
                            }
                        } else {
                            Cart::insert($cart);
                        }
                    }
                }
                if ($cant_buy_goods) {
                    foreach ($cant_buy_goods as $key => $v) {
                        $goods_thumb = $v['goods_thumb'] ?? (isset($v['get_goods']['goods_thumb']) ? $v['get_goods']['goods_thumb'] : '');
                        $cant_buy_goods[$key]['goods_thumb'] = $this->dscRepository->getImagePath($goods_thumb);
                    }
                }

                $result['error'] = 0;
                $result['msg'] = lang('user.return_to_cart_success');
                $result['cant_buy_goods'] = $cant_buy_goods;
            } else {
                $result['error'] = 1;
                $result['msg'] = lang('user.unknow_error');
            }
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('user.unknow_error');
        }

        return $result;
    }
}
