<?php

namespace App\Services\Flow;

use App\Models\AutoSms;
use App\Models\BonusType;
use App\Models\Cart;
use App\Models\Coupons;
use App\Models\CouponsRegion;
use App\Models\CouponsUser;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\PayLog;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\UserBonus;
use App\Models\ValueCardRecord;
use App\Models\VirtualCard;
use App\Plugins\UserRights\Discount\Services\DiscountRightsService;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryService;
use App\Services\Coupon\CouponsUserService;
use App\Services\Cron\CronService;
use App\Services\Drp\DrpConfigService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderTransportService;

class FlowOrderService
{
    protected $baseRepository;
    protected $orderTransportService;
    protected $couponsUserService;
    protected $dscRepository;
    protected $commonRepository;
    protected $timeRepository;
    protected $sessionRepository;
    protected $merchantCommonService;
    protected $config;
    protected $orderCommonService;
    protected $categoryService;

    public function __construct(
        BaseRepository $baseRepository,
        OrderTransportService $orderTransportService,
        CouponsUserService $couponsUserService,
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        TimeRepository $timeRepository,
        SessionRepository $sessionRepository,
        MerchantCommonService $merchantCommonService,
        OrderCommonService $orderCommonService,
        CategoryService $categoryService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->orderTransportService = $orderTransportService;
        $this->couponsUserService = $couponsUserService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->timeRepository = $timeRepository;
        $this->sessionRepository = $sessionRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderCommonService = $orderCommonService;
        $this->categoryService = $categoryService;
    }

    /**
     * 分单插入数据
     *
     * @param int $order_id
     * @throws \Exception
     */
    public function OrderSeparateBill($order_id = 0)
    {
        $orderInfo = get_main_order_info($order_id, 1);

        $row = OrderInfo::where('order_id', $order_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        $newOrder = $orderInfo['newOrder'];
        $orderBonus = $orderInfo['orderBonus'];
        $newInfo = $orderInfo['newInfo'];
        $orderFavourable = $orderInfo['orderFavourable'];
        $surplus = $row['surplus']; //主订单余额
        $integral_money = $row['integral_money']; //主订单积分金额
        $integral = $row['integral']; //主订单积分数量
        $discount = $row['discount']; //主订单折扣金额
        $commonuse_discount = $this->separateOrderFav($discount, $orderFavourable); //全场通用折扣金额
        $discount_child = 0;
        $main_pay_fee = $row['pay_fee']; //主订单支付手续费
        $bonus_id = $row['bonus_id']; //主订单红包ID
        $main_bonus = $row['bonus']; //主订单红包金额
        $coupons = $row['coupons']; //主订单优惠券金额
        $main_goods_amount = $row['goods_amount']; //主订单商品金额
        $usebonus_type = $this->bonusAllGoods($bonus_id); //全场通用红包 val:1

        //是否开启下单自动发短信、邮件 start
        $auto_sms = app(CronService::class)->getSmsOpen();

        /**
         * 获取主订单储值卡使用金额
         */
        $cartUseVal = ValueCardRecord::select('vc_id', 'use_val', 'vc_dis')
            ->where('order_id', $order_id);
        $cartUseVal = $this->baseRepository->getToArrayFirst($cartUseVal);

        $vc_id = $cartUseVal ? $cartUseVal['vc_id'] : 0;
        $use_val = $cartUseVal ? $cartUseVal['use_val'] : 0;
        $vc_dis = $cartUseVal ? $cartUseVal['vc_dis'] : 0;

        // 主订单购买权益卡金额与折扣
        $order_membership_card = [];
        if (file_exists(MOBILE_DRP)) {
            $order_membership_card = app(DiscountRightsService::class)->getOrderInfoMembershipCard($order_id, $row['user_id']);
        }

        $shipping_id = $row['shipping_id'];
        $shipping_name = $row['shipping_name'];
        $shipping_code = $row['shipping_code'];
        $shipping_type = $row['shipping_type'];
        $postscript_desc = $row['postscript'];

        $flow_type = intval(session('flow_type', CART_GENERAL_GOODS));

        $order_goods = [];
        $i = 0;
        foreach ($newInfo as $key => $info) {
            $i += 1;
            $order_goods[$key] = $info;

            $shipping = $this->sellerShippingOrder($key, $shipping_id, $shipping_name, $shipping_code, $shipping_type);

            $row['shipping_id'] = $shipping['shipping_id'];
            $row['shipping_name'] = $shipping['shipping_name'];
            $row['shipping_code'] = $shipping['shipping_code'];
            $row['shipping_type'] = $shipping['shipping_type'];

            $postscript = explode(',', $postscript_desc);//留言分单
            $row['postscript'] = '';
            if ($postscript) {
                foreach ($postscript as $postrow) {
                    $postrow = explode('|', $postrow);
                    if ($postrow[0] == $key) {//$key是商家ID
                        $row['postscript'] = $postrow[1] ?? '';
                    }
                }
            }

            // 插入订单表 start
            $order_sn = $this->getOrderChildSn(); //获取新订单号
            $row['order_sn'] = $order_sn;

            session([
                'order_done_sn' => $row['order_sn']
            ]);

            $row['main_order_id'] = $order_id; //获取主订单ID
            $row['goods_amount'] = $newOrder[$key]['goods_amount']; //商品总金额

            //折扣 start
            if ($commonuse_discount['has_terrace'] == 1) {
                if ($key == 0) { //优惠活动全场通用折扣金额算入平台
                    $row['discount'] = $commonuse_discount['discount']; //全场通用折扣金额
                } else {
                    $row['discount'] = $orderFavourable[$key]['compute_discount']['discount']; //全场通用折扣金额
                }
            } else {
                $row['discount'] = $orderFavourable[$key]['compute_discount']['discount'] + $commonuse_discount['discount']; //折扣金额
                $commonuse_discount['discount'] = 0;
            }
            //折扣 end

            $cou_type = 0;

            /* 优惠券 */
            $order_coupons = $this->getUserOrderCoupons($order_id, $key, 1);
            if ($order_coupons) {
                $cou_type = 1;
                $row['coupons'] = $coupons;
                $coupons = 0;
            } else {
                $row['coupons'] = 0;
            }

            //获取默认运费模式运费 by wu start
            $row['shipping_fee'] = 0;
            $sellerOrderInfo = [];
            $sellerOrderInfo['ru_id'] = $key;
            $sellerOrderInfo['weight'] = 0;
            $sellerOrderInfo['goods_price'] = 0;
            $sellerOrderInfo['number'] = 0;
            $sellerOrderInfo['region'] = [$row['country'], $row['province'], $row['city'], $row['district'], $row['street']];
            $sellerOrderInfo['shipping_id'] = $row['shipping_id'];

            if (!empty($newOrder[$key]['ru_list'])) {
                foreach ($newOrder[$key]['ru_list'] as $k => $v) {
                    if (isset($v['order_id'])) {
                        $sellerOrderInfo['weight'] += floatval($v['weight']);
                        $sellerOrderInfo['goods_price'] += floatval($v['goods_price']);
                        $sellerOrderInfo['number'] += intval($v['number']);
                    }
                }
                $row['shipping_fee'] = $this->getSellerShippingFee($sellerOrderInfo, $order_goods[$key]);
            }

            $couponsInfo = [];
            if (isset($row['uc_id']) && !empty($row['uc_id'])) {
                $couponsInfo = $this->couponsUserService->getCoupons($row['uc_id']);
            }

            /* 优惠券 免邮 start */
            if (!empty($couponsInfo) && $key == $couponsInfo['ru_id']) {
                if ($couponsInfo['cou_type'] == 5) {
                    if ($newOrder[$key]['goods_amount'] >= $couponsInfo['cou_man'] || $couponsInfo['cou_man'] == 0) {
                        $cou_region = CouponsRegion::where('cou_id', $couponsInfo['cou_id'])->value('region_list');
                        $cou_region = !empty($cou_region) ? explode(",", $cou_region) : [];
                        if ($cou_region) {
                            if (!in_array($row['province'], $cou_region)) {
                                $row['shipping_fee'] = 0;
                            }
                        } else {
                            $row['shipping_fee'] = 0;
                        }
                    }
                }
            }
            /* 优惠券 免邮 end */

            //订单应付金额
            $row['order_amount'] = $newOrder[$key]['goods_amount'];

            /* 均摊支付费用 */
            $row['pay_fee'] = ($newOrder[$key]['goods_amount'] / $main_goods_amount) * $main_pay_fee;

            /* 加支付费用金额 */
            $row['order_amount'] += $row['pay_fee'];

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $row['order_amount'] += $newOrder[$key]['rate_fee'];
            }

            //规避折扣之后订单金额为负数
            if ($commonuse_discount['has_terrace'] == 0) {
                if ($discount_child > 0) {
                    $row['discount'] += $discount_child;
                }
                if ($row['discount'] > 0) {
                    if ($row['order_amount'] > $row['discount']) {
                        $row['order_amount'] -= $row['discount'];
                    } else {
                        $discount_child = $row['discount'] - $row['order_amount']; //剩余折扣金额
                        $row['discount'] = $row['order_amount'];
                        $row['order_amount'] = 0;
                    }
                }
            } else {
                $row['order_amount'] -= $row['discount'];
            }

            //减去优惠券金额 start
            if ($row['coupons'] > 0) {
                if ($row['order_amount'] >= $row['coupons']) {
                    $row['order_amount'] -= $row['coupons'];
                } else {
                    $row['coupons'] = $row['order_amount'];
                    $row['order_amount'] = 0;
                }
            }
            //减去优惠券金额 end

            // 减去红包 start
            if ($usebonus_type == 1) {

                $bonus = ($newOrder[$key]['goods_amount'] / $main_goods_amount) * $main_bonus;

                if ($bonus > 0) {
                    if ($row['order_amount'] >= $bonus) {
                        $row['order_amount'] = $row['order_amount'] - $bonus;
                        $row['bonus'] = $bonus;
                    } else {
                        $row['bonus'] = $row['order_amount'];
                        $row['order_amount'] = 0;
                    }

                    $row['bonus_id'] = $bonus_id;
                } else {
                    $row['bonus'] = 0;
                    $row['bonus_id'] = 0;
                }
            } else {
                if (isset($orderBonus[$key]['bonus']['type_money'])) {
                    $use_bonus = min($orderBonus[$key]['bonus']['type_money'], $row['order_amount']); // 实际减去的红包金额
                    $row['order_amount'] -= $use_bonus;
                    $row['bonus'] = $orderBonus[$key]['bonus']['type_money'];
                } else {
                    $row['bonus'] = 0;
                    $row['bonus_id'] = 0;
                }
            }
            // 减去红包 end

            //积分 start
            if ($row['order_amount'] > 0 && $integral_money > 0) {

                //子订单商品可支付积分金额
                $integral_ratio = $this->getIntegralRatio($order_id, $key);

                //当总积分金额大于店铺订单的积分可用金额
                if ($integral_ratio > 0) {
                    if ($integral_money >= $integral_ratio && $row['order_amount'] > $integral_ratio) {

                        /* 当总积分金额大于店铺订单的积分可用金额并且订单金额大于可用积分金额 */
                        $integral_money -= $integral_ratio;
                        $row['order_amount'] -= $integral_ratio;
                        $row['integral_money'] = $integral_ratio;
                        $row['integral'] = $this->dscRepository->integralOfValue($integral_ratio);
                    } else {
                        if ($integral_money > $row['order_amount']) {
                            $integral_money -= $row['order_amount'];
                            $row['integral_money'] = $row['order_amount'];
                            $row['integral'] = $this->dscRepository->integralOfValue($row['order_amount']);

                            $row['order_amount'] = 0;
                        } else {
                            $row['order_amount'] -= $integral_money;
                            $row['integral_money'] = $integral_money;
                            $row['integral'] = $this->dscRepository->integralOfValue($integral_money);

                            $integral_money = 0;
                        }
                    }
                } else {
                    $row['integral_money'] = 0;
                    $row['integral'] = 0;
                }
            } else {
                $integral_money = 0;
                $row['integral_money'] = 0;
                $row['integral'] = 0;
            }
            //积分 end

            /* 税额 */
            $row['tax'] = $this->commonRepository->orderInvoiceTotal($newOrder[$key]['goods_amount'], $row['inv_content']);
            $row['order_amount'] += $row['shipping_fee'] + $row['tax'];

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $row['rate_fee'] = $newOrder[$key]['rate_fee'];
            }

            // 开通购买会员权益卡 应付金额 = 原应付金额 - 折扣差价 + 购买权益卡金额
            if (file_exists(MOBILE_DRP) && !empty($order_membership_card)) {
                if ($order_membership_card['membership_card_id'] > 0) {
                    $row['order_amount'] = $row['order_amount'] - $order_membership_card['membership_card_discount_price'] + $order_membership_card['membership_card_buy_money'];
                }
            }

            /* 储值卡start */
            $cartValOther = [];
            if ($use_val > 0) {

                /**
                 * 先去除运费，在计算积分
                 */
                $row['order_amount'] -= $row['shipping_fee'];

                if ($use_val > $row['order_amount']) {
                    $use_val = $use_val - $row['order_amount'];
                    $cartValOther['use_val'] = $row['order_amount'];
                    $row['order_amount'] = 0;
                } else {
                    $row['order_amount'] -= $use_val;
                    $cartValOther['use_val'] = $use_val;
                    $use_val = 0;
                }

                $cartValOther['vc_id'] = $vc_id;
                $cartValOther['vc_dis'] = $vc_dis;

                /**
                 * 完成积分计算补充回运费金额
                 */
                $row['order_amount'] += $row['shipping_fee'];
            }
            /* 储值卡end */

            //余额 start
            if ($surplus > 0) {
                if ($surplus >= $row['order_amount']) {
                    $surplus = $surplus - $row['order_amount'];
                    $row['surplus'] = $row['order_amount']; //订单金额等于当前使用余额
                    $row['order_amount'] = 0;
                } else {
                    $row['order_amount'] = $row['order_amount'] - $surplus;
                    $row['surplus'] = $surplus;
                    $surplus = 0;
                }
            } else {
                $row['surplus'] = 0;
            }
            //余额 end

            $row['order_amount'] = number_format($row['order_amount'], 2, '.', ''); //格式化价格为一个数字

            /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
            if ($row['order_amount'] <= 0) {
                $row['order_status'] = OS_CONFIRMED;
                $row['confirm_time'] = $this->timeRepository->getGmTime();
                $row['pay_status'] = PS_PAYED;
                $row['pay_time'] = $this->timeRepository->getGmTime();
            }

            unset($row['order_id']);
            //商家---剔除自提点信息
            if ($row['shipping_code'] != 'cac') {
                $row['point_id'] = 0;
                $row['shipping_dateStr'] = '';
            }

            //商家ID
            $row['ru_id'] = $key;

            $new_orderId = $this->AddToOrder($row);

            /* 记录分单订单使用储值卡 */
            if ($cartValOther && $new_orderId > 0) {
                $cartValOther['order_id'] = $new_orderId;
                $cartValOther['record_time'] = $this->timeRepository->getGmTime();
                ValueCardRecord::insert($cartValOther);
            }

            if ($new_orderId > 0) {
                //修改优惠券使用order_id
                if ($cou_type == 1) {
                    CouponsUser::where('user_id', $row['user_id'])
                        ->where('order_id', $order_id)
                        ->update(['order_id' => $new_orderId]);
                }

                /* 如果需要，发短信 */
                if ($key == 0) {
                    $sms_shop_mobile = $this->config['sms_shop_mobile']; //手机
                } else {
                    $sms_shop_mobile = SellerShopinfo::where('ru_id', $key)->value('mobile');
                }

                /* 给商家发短信 */
                if ($this->config['sms_order_placed'] == '1' && $sms_shop_mobile != '') {
                    if (!empty($auto_sms)) {
                        $other = [
                            'item_type' => 1,
                            'user_id' => $row['user_id'],
                            'ru_id' => $key,
                            'order_id' => $new_orderId,
                            'add_time' => $this->timeRepository->getGmTime()
                        ];
                        AutoSms::insert($other);
                    } else {
                        $shop_name = $this->merchantCommonService->getShopName($key, 1);
                        $order_region = $this->getFlowOrderUserRegion($new_orderId);
                        //阿里大鱼短信接口参数
                        $smsParams = [
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
                            'mobile_phone' => $sms_shop_mobile,
                            'mobilephone' => $sms_shop_mobile
                        ];

                        $this->commonRepository->smsSend($sms_shop_mobile, $smsParams, 'sms_order_placed');
                    }
                }

                $cost_amount = 0;
                $goods_bonus = 0;
                $bonus_list = [];
                $order_goods[$key] = array_values($order_goods[$key]);
                for ($j = 0; $j < count($order_goods[$key]); $j++) {
                    $order_goods[$key][$j]['order_id'] = $new_orderId;
                    unset($order_goods[$key][$j]['rec_id']);
                    $order_goods[$key][$j]['goods_name'] = addslashes($order_goods[$key][$j]['goods_name']);
                    $order_goods[$key][$j]['goods_attr'] = addslashes($order_goods[$key][$j]['goods_attr']);

                    unset($order_goods[$key][$j]['get_goods']);

                    $order_goods[$key][$j] = $this->baseRepository->getArrayfilterTable($order_goods[$key][$j], 'order_goods');

                    /* 订单红包均摊到订单商品 */
                    if ($order_goods[$key][$j]['goods_price'] > 0) {
                        $key_goods_amount = $order_goods[$key][$j]['goods_price'] * $order_goods[$key][$j]['goods_number'];
                        $order_goods[$key][$j]['goods_bonus'] = ($key_goods_amount / $newOrder[$key]['goods_amount']) * $row['bonus'];
                    } else {
                        $order_goods[$key][$j]['goods_bonus'] = 0;
                    }

                    /* 商品红包四舍五入金额 */
                    $order_goods[$key][$j]['goods_bonus'] = number_format($order_goods[$key][$j]['goods_bonus'], 2, '.', '');
                    $bonus_list[$j]['goods_bonus'] = $order_goods[$key][$j]['goods_bonus'];
                    $goods_bonus += $order_goods[$key][$j]['goods_bonus'];

                    $bonus_list[$j]['rec_id'] = OrderGoods::insertGetId($order_goods[$key][$j]);

                    /* 虚拟卡 */
                    $virtual_goods = $this->getVirtualGoods($new_orderId);

                    if ($virtual_goods && $flow_type != CART_GROUP_BUY_GOODS && $row['order_amount'] <= 0) {
                        /* 虚拟卡发货 */
                        if ($this->orderVirtualGoodsShip($virtual_goods, $new_orderId, $order_sn)) {

                            /* 如果没有实体商品，修改发货状态，送积分和红包 */
                            $count = OrderGoods::where('order_id', $new_orderId)
                                ->where('is_real', 1)
                                ->count();

                            if ($count <= 0) {
                                /* 修改订单状态 */
                                OrderInfo::where('order_id', $new_orderId)->update([
                                    'shipping_status' => SS_SHIPPED,
                                    'shipping_time' => $this->timeRepository->getGmTime()
                                ]);
                            }
                        }
                    }

                    $cost_price = $order_goods[$key][$j]['cost_price'] ?? 0;
                    $cost_amount += $cost_price * $order_goods[$key][$j]['goods_number'];
                }

                $this->dscRepository->collateOrderGoodsBonus($bonus_list, $row['bonus'], $goods_bonus);

                //更新子订单成本价格
                OrderInfo::where('order_id', $new_orderId)->update([
                    'cost_amount' => $cost_amount
                ]);

                /* 插入支付日志 */
                $row['log_id'] = $this->insertPayLog($new_orderId, $row['order_amount'], PAY_ORDER);

                $this->orderCommonService->orderAction($row['order_sn'], $row['order_status'], $row['shipping_status'], $row['pay_status'], lang('common.main_order_pay'), lang('common.buyer'));
            }
        }
    }

    /**
     * 将支付LOG插入数据表
     *
     * @access  public
     * @param integer $id 订单编号
     * @param float $amount 订单金额
     * @param integer $type 支付类型
     * @param integer $is_paid 是否已支付
     *
     * @return  int
     */
    public function insertPayLog($id, $amount, $type = PAY_SURPLUS, $is_paid = 0)
    {
        if ($id) {
            $pay_log = [
                'order_id' => $id,
                'order_amount' => $amount,
                'order_type' => $type,
                'is_paid' => $is_paid
            ];

            $log_id = PayLog::insertGetId($pay_log);
        } else {
            $log_id = 0;
        }

        return $log_id;
    }

    /**
     * 获得订单地址信息
     *
     * @param int $order_id
     * @return string
     */
    private function getFlowOrderUserRegion($order_id = 0)
    {

        /* 取得区域名 */
        $res = OrderInfo::where('order_id', $order_id);

        $res = $res->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name as province_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name as city_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name as district_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name as street_name');
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);

        $region = '';
        if ($res) {
            $res = $res['get_region_province'] ? array_merge($res, $res['get_region_province']) : $res;
            $res = $res['get_region_city'] ? array_merge($res, $res['get_region_city']) : $res;
            $res = $res['get_region_district'] ? array_merge($res, $res['get_region_district']) : $res;
            $res = $res['get_region_street'] ? array_merge($res, $res['get_region_street']) : $res;


            $province_name = isset($res['province_name']) && $res['province_name'] ? $res['province_name'] : '';
            $city_name = isset($res['city_name']) && $res['city_name'] ? $res['city_name'] : '';
            $district_name = isset($res['district_name']) && $res['district_name'] ? $res['district_name'] : '';
            $street_name = isset($res['street_name']) && $res['street_name'] ? $res['street_name'] : '';

            $region = $province_name . " " . $city_name . " " . $district_name . " " . $street_name;
            $region = trim($region);
        }

        return $region;
    }

    /**
     * 合计全场通用优惠活动折扣金额
     *
     * @param int $discount_all
     * @param array $orderFavourable
     * @return array
     */
    private function separateOrderFav($discount_all = 0, $orderFavourable = [])
    {
        $discount = 0;
        $has_terrace = '';
        if ($orderFavourable) {
            foreach ($orderFavourable as $key => $row) {
                $discount += $row['compute_discount']['discount'];
                $has_terrace .= $key . ",";
            }
        }

        if ($has_terrace != '') {
            $has_terrace = substr($has_terrace, 0, -1);
            $has_terrace = explode(",", $has_terrace);
        }

        if (in_array(0, $has_terrace)) {
            $has_terrace = 1; //有平台商品
        } else {
            $has_terrace = 0; //无平台商品
        }

        $discount_all = number_format(($discount_all), 2, '.', '');
        $discount = number_format(($discount), 2, '.', '');
        $commonuse_discount = $discount_all - $discount;

        return ['discount' => $commonuse_discount, 'has_terrace' => $has_terrace];
    }

    /**
     * 查询订单是否红包全场通用
     *
     * @param int $bonus_id
     * @return mixed
     */
    private function bonusAllGoods($bonus_id = 0)
    {
        $usebonus_type = BonusType::whereHas('getUserBonus', function ($query) use ($bonus_id) {
            $query->where('bonus_id', $bonus_id);
        })
            ->value('usebonus_type');

        return $usebonus_type;
    }

    /**
     * 商家配送方式分单分组
     *
     * @param array $ru_id
     * @param array $shipping_id
     * @param array $shipping_name
     * @param array $shipping_code
     * @param array $shipping_type
     * @return array
     */
    private function sellerShippingOrder($ru_id = [], $shipping_id = [], $shipping_name = [], $shipping_code = [], $shipping_type = [])
    {
        $shipping_id = explode(',', $shipping_id);
        $shipping_name = explode(',', $shipping_name);
        $shipping_code = explode(',', $shipping_code);
        $shipping_type = explode(',', $shipping_type);

        $shippingId = '';
        $shippingName = '';
        $shippingCode = '';
        $shippingType = '';

        foreach ($shipping_id as $key => $row) {
            $row = explode('|', $row);
            if ($row[0] == $ru_id) {
                $shippingId = $row[1] ?? 0;
            }
        }

        foreach ($shipping_name as $key => $row) {
            $row = explode('|', $row);
            if ($row[0] == $ru_id) {
                $shippingName = $row[1] ?? '';
            }
        }

        if ($shipping_code) {
            foreach ($shipping_code as $key => $row) {
                $row = explode('|', $row);
                if ($row[0] == $ru_id) {
                    $shippingCode = $row[1] ?? '';
                }
            }
        }

        if ($shipping_type) {
            foreach ($shipping_type as $key => $row) {
                $row = explode('|', $row);
                if ($row[0] == $ru_id) {
                    $shippingType = $row[1] ?? 0;
                }
            }
        }

        $shipping = [
            'shipping_id' => $shippingId,
            'shipping_name' => $shippingName,
            'shipping_code' => $shippingCode,
            'shipping_type' => $shippingType
        ];

        return $shipping;
    }

    /**
     * 得到新订单号
     *
     * @return array|int|string
     */
    private function getOrderChildSn()
    {
        $time = explode(" ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time = explode(".", $time);
        $time = isset($time[1]) ? $time[1] : 0;
        $time = $this->timeRepository->getLocalDate('YmdHis') + $time;

        /* 选择一个随机的方案 */
        mt_srand((double)microtime() * 1000000);
        $time = $time . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        if (session('order_done_sn') == $time) {
            $time += 1;
        }

        return $time;
    }

    /**
     * 查询订单使用的优惠券
     *
     * @param $order_id
     * @param int $ru_id
     * @param int $type
     * @return mixed
     */
    private function getUserOrderCoupons($order_id, $ru_id = 0, $type = 0)
    {
        $res = CouponsUser::selectRaw("*, cou_money AS uc_money")
            ->where('order_id', $order_id);

        if ($type) {
            $res = $res->whereHas('getCoupons', function ($query) use ($ru_id) {
                $query->where('ru_id', $ru_id);
            });
        }

        $res = $res->with([
            'getCoupons' => function ($query) {
                $query->select('cou_id', 'cou_name', 'cou_money');
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);
        $res = isset($res['get_coupons']) ? $this->baseRepository->getArrayMerge($res, $res['get_coupons']) : $res;

        if ($res) {
            if (isset($res['uc_money']) && $res['uc_money'] > 0) {
                $res['cou_money'] = $res['uc_money'];
            }
        }

        return $res;
    }

    /**
     * 商家订单运费
     *
     * @param array $sellerOrderInfo
     * @param array $cart_goods
     * @return int
     */
    private function getSellerShippingFee($sellerOrderInfo = [], $cart_goods = [])
    {

        //获取配送区域
        $val = Shipping::where('shipping_id', $sellerOrderInfo['shipping_id']);
        $val = $this->baseRepository->getToArrayFirst($val);

        if (empty($val)) {
            return 0;
        }

        if ($sellerOrderInfo['region']) {
            $sellerOrderInfo['region'] = array_values($sellerOrderInfo['region']);
        }

        $consignee['country'] = $sellerOrderInfo['region'][0];
        $consignee['province'] = $sellerOrderInfo['region'][1];
        $consignee['city'] = $sellerOrderInfo['region'][2];
        $consignee['district'] = $sellerOrderInfo['region'][3];
        $consignee['street'] = $sellerOrderInfo['region'][4];
        $order_transpor = $this->orderTransportService->getOrderTransport($cart_goods, $consignee, $val['shipping_id'], $val['shipping_code']);

        $shippingFee = 0;
        if ($order_transpor['freight']) {
            $shippingFee += $order_transpor['sprice']; //有配送按配送区域计算运费
        } else {
            $shippingFee = $order_transpor['sprice'];
        }

        return $shippingFee;
    }

    /**
     * 获取子订单可支付积分金额
     *
     * @param int $order_id
     * @param int $ru_id
     * @return float
     */
    private function getIntegralRatio($order_id = 0, $ru_id = 0)
    {
        // 获取订单商品总共可用积分
        $integral_total = $this->getIntegral($order_id, $ru_id);

        return $integral_total;
    }


    /**
     * 订单商品总共可用积分
     *
     * @param int $order_id
     * @param int $ru_id
     * @return int
     */
    private function getIntegral($order_id = 0, $ru_id = 0)
    {
        $res = OrderGoods::select('goods_id', 'goods_price', 'goods_number', 'ru_id', 'model_attr', 'warehouse_id', 'area_id')
            ->where('order_id', $order_id);

        $res = $res->with([
            'getGoods',
            'getWarehouseGoods',
            'getWarehouseAreaGoods'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        $integral_money = 0;
        if ($res) {
            foreach ($res as $key => $v) {

                $goods_integral = $v['get_goods']['integral'] ?? 0;

                $warehouse_pay_integral = $v['get_warehouse_goods']['pay_integral'] ?? 0;

                $area_pay_integral = $v['get_warehouse_area_goods']['pay_integral'] ?? 0;

                $area_pay_integral = $area_pay_integral ? $area_pay_integral : 0;

                if ($v['model_attr'] == 1) {
                    $integral = $warehouse_pay_integral;
                } elseif ($v['model_attr'] == 2) {
                    $integral = $area_pay_integral;
                } else {
                    $integral = $goods_integral;
                }

                /**
                 * 取最小兑换积分
                 */
                $integral_list = [
                    $this->dscRepository->integralOfValue($v['goods_price'] * $v['goods_number']),
                    $this->dscRepository->integralOfValue($integral * $v['goods_number'])
                ];

                $integral = $this->baseRepository->getArrayMin($integral_list);
                $integral = $this->dscRepository->valueOfIntegral($integral);

                $v['integral'] = $this->dscRepository->integralOfValue($integral);
                $v['integral_money'] = $integral;

                $arr[$v['ru_id']]['goods_list'][$key] = $v;
            }

            $goods_list = $arr[$ru_id]['goods_list'];
            $integral_money = $this->baseRepository->getArraySum($goods_list, 'integral_money');
        }

        return $integral_money;
    }

    /**
     * 插入订单表
     *
     * @param array $order
     * @param array $cart_value
     * @return int
     */
    public function AddToOrder($order = [], $cart_value = [])
    {
        $order_other = $this->baseRepository->getArrayfilterTable($order, 'order_info');
        $cart_value = $this->baseRepository->getExplode($cart_value);

        $order_id = 0;
        if (!empty($cart_value)) {
            $count = Cart::whereIn('rec_id', $cart_value)->count();
        } else {
            $count = 1;
        }

        if ($count > 0) {
            return OrderInfo::insertGetId($order_other);
        }

        return $order_id;
    }

    /**
     * 插入订单商品数据列表
     *
     * @param array $where
     * @param array $order
     * @param array $all_ru_id
     * @return array
     */
    public function AddToOrderGoods($where = [], $order = [], $all_ru_id = [])
    {
        $rec_list = [];
        if ($where['order_id'] > 0) {
            $user_id = isset($where['user_id']) && !empty($where['user_id']) ? $where['user_id'] : 0;
            $flow_type = isset($where['rec_type']) && !empty($where['rec_type']) ? $where['rec_type'] : CART_GENERAL_GOODS;
            $rec_id = isset($where['rec_id']) && !empty($where['rec_id']) ? $where['rec_id'] : 0;

            $res = Cart::selectRaw("*, (goods_price * goods_number) as subtotal")->where('rec_type', $flow_type);

            if (!empty($user_id)) {
                $res = $res->where('user_id', $user_id);
            } else {
                $session_id = $this->sessionRepository->realCartMacIp();
                $res = $res->where('session_id', $session_id);
            }

            if ($rec_id) {
                $rec_id = $this->baseRepository->getExplode($rec_id);
                $res = $res->whereIn('rec_id', $rec_id);
            }

            $res = $res->with([
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'is_distribution', 'dis_commission', 'cat_id')->with([
                        'getGoodsExtend' => function ($query) {
                            $query->select('goods_id', 'is_reality', 'is_return', 'is_fast');
                        }
                    ]);
                }
            ]);

            $res = $this->baseRepository->getToArrayGet($res);
            /* 附加查询条件 end */

            /* 订单优惠券均摊到订单商品， 检测优惠券是否支持商品参与均摊 start */
            $couponsGoods = [];
            $couponSubtotal = 0;
            if (isset($order['coupons']) && $order['coupons'] > 0) {
                if ($res) {
                    foreach ($res as $key => $val) {

                        /* 普通商品 */
                        $is_general_goods = $val['extension_code'] == '' && $val['rec_type'] == 0;

                        $cat_id = 0;
                        if ($is_general_goods === true) {
                            $cat_id = $val['get_goods']['cat_id'] ?? 0;
                        }
                        $val['cat_id'] = $cat_id;

                        $res[$key] = $val;
                    }
                };

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

                    $couponsSumList = $this->baseRepository->getArraySqlGet($res, $sql);
                    $couponSubtotal = $this->baseRepository->getArraySum($couponsSumList, 'subtotal');
                }
            }
            /* 订单优惠券均摊到订单商品， 检测优惠券是否支持商品参与均摊 end */

            /* 订单红包均摊到订单商品， 检测红包是否支持店铺商品参与均摊 start */
            $bonusSubtotal = 0;
            $useType = 0;
            $bonus_ru_id = 0;
            $bonus_id = $order['bonus_id'] ?? 0;
            $bonusInfo = [];
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

                $bonusSumList = $this->baseRepository->getArraySqlGet($res, $sql);
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
                if ($bonusInfo && $bonusSubtotal > 0) {
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

                        $selfList = $this->baseRepository->getArraySqlGet($res, $sql);
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

                        $sellerList = $this->baseRepository->getArraySqlGet($res, $sql);

                        $sellerAmount = $this->baseRepository->getArraySum($sellerList, 'subtotal');
                        $bonusSubtotal = $bonusSubtotal - $sellerAmount;
                    }
                }
            }

            $cartOther = [];
            if ($res) {
                /* 红包 */
                $goods_bonus = 0;
                $bonus_list = [];

                /* 优惠券 */
                $goods_coupons = 0;
                $coupons_list = [];
                foreach ($res as $key => $val) {
                    $cartOther[$key] = [
                        'order_id' => $where['order_id'],
                        'cart_recid' => $val['rec_id'],
                        'user_id' => $user_id,
                        'goods_id' => $val['goods_id'],
                        'goods_name' => $val['goods_name'],
                        'goods_sn' => $val['goods_sn'],
                        'product_id' => $val['product_id'],
                        'goods_number' => $val['goods_number'],
                        'market_price' => $val['market_price'],
                        'goods_price' => $val['goods_price'],
                        'stages_qishu' => $val['stages_qishu'],
                        'goods_attr' => $val['goods_attr'],
                        'is_real' => $val['is_real'],
                        'extension_code' => $val['extension_code'],
                        'parent_id' => $val['parent_id'],
                        'is_gift' => $val['is_gift'],
                        'model_attr' => $val['model_attr'],
                        'goods_attr_id' => $val['goods_attr_id'],
                        'ru_id' => $val['ru_id'],
                        'shopping_fee' => $val['shopping_fee'],
                        'warehouse_id' => $val['warehouse_id'],
                        'area_id' => $val['area_id'],
                        'area_city' => $val['area_city'],
                        'freight' => $val['freight'],
                        'tid' => $val['tid'],
                        'shipping_fee' => $val['shipping_fee'],
                        'cost_price' => $val['cost_price']
                    ];

                    /* 订单红包均摊到订单商品， 检测红包是否支持店铺商品参与均摊 start */
                    $isShareAlike = 1;
                    if ($useType == 0) {
                        if ($bonusInfo) {
                            if ($bonus_ru_id > 0) {
                                if ($val['ru_id'] == 0) {
                                    $isShareAlike = 0;
                                } else {
                                    $isShareAlike = 1;
                                }
                            } else {
                                if ($val['ru_id'] > 0) {
                                    $isShareAlike = 0;
                                } else {
                                    $isShareAlike = 1;
                                }
                            }
                        }
                    }

                    /* 普通商品 */
                    $is_general_goods = $val['extension_code'] == '' && $val['rec_type'] == 0;
                    $keySubtotal = $val['goods_price'] * $val['goods_number'];

                    $order['bonus'] = $order['bonus'] ?? 0;
                    if ($is_general_goods === true && $order['bonus'] > 0 && $bonusSubtotal > 0) {
                        if ($val['goods_price'] > 0 && $isShareAlike == 1) {
                            $cartOther[$key]['goods_bonus'] = ($keySubtotal / $bonusSubtotal) * $order['bonus'];
                            $cartOther[$key]['goods_bonus'] = $this->dscRepository->changeFloat($cartOther[$key]['goods_bonus']);
                        } else {
                            $cartOther[$key]['goods_bonus'] = 0;
                        }

                        $bonus_list[$key]['goods_bonus'] = $cartOther[$key]['goods_bonus'];
                        $goods_bonus += $cartOther[$key]['goods_bonus'];
                    }
                    /* 订单红包均摊到订单商品， 检测红包是否支持店铺商品参与均摊 end */

                    /* 订单优惠券均摊到订单商品， 检测优惠券是否支持商品参与均摊 start */
                    $order['coupons'] = $order['coupons'] ?? 0;
                    if ($is_general_goods === true && $order['coupons'] > 0 && $couponSubtotal > 0) {
                        $cat_id = $val['cat_id'] ?? 0;
                        if ($couponsGoods && $val['goods_price'] > 0 && $couponsGoods['ru_id'] == $val['ru_id']) {

                            $cartOther[$key]['goods_coupons'] = 0;

                            if ($couponsGoods['is_coupons'] == 1) {
                                $cartOther[$key]['goods_coupons'] = ($keySubtotal / $couponSubtotal) * $order['coupons'];
                                $cartOther[$key]['goods_coupons'] = $this->dscRepository->changeFloat($cartOther[$key]['goods_coupons']);

                            } elseif ($couponsGoods['is_coupons'] == 2) {
                                if ($couponsGoods['cou_goods'] && in_array($val['goods_id'], $couponsGoods['cou_goods'])) {
                                    $cartOther[$key]['goods_coupons'] = ($keySubtotal / $couponSubtotal) * $order['coupons'];
                                    $cartOther[$key]['goods_coupons'] = $this->dscRepository->changeFloat($cartOther[$key]['goods_coupons']);
                                }
                            } elseif ($couponsGoods['is_coupons'] == 3) {
                                if ($cat_id > 0 && in_array($cat_id, $couponsGoods['spec_cat'])) {
                                    $cartOther[$key]['goods_coupons'] = ($keySubtotal / $couponSubtotal) * $order['coupons'];
                                    $cartOther[$key]['goods_coupons'] = $this->dscRepository->changeFloat($cartOther[$key]['goods_coupons']);
                                }
                            }

                            $coupons_list[$key]['goods_coupons'] = $cartOther[$key]['goods_coupons'];
                            $goods_coupons += $cartOther[$key]['goods_coupons'];
                        }
                    }
                    /* 订单优惠券均摊到订单商品， 检测优惠券是否支持商品参与均摊 end */

                    if (CROSS_BORDER === true) // 跨境多商户
                    {
                        $cartOther[$key]['rate_price'] = 0;
                        if (isset($where['rate_arr']) && !empty($where['rate_arr'])) {
                            foreach ($where['rate_arr'] as $k => $v)//插入跨境税费
                            {
                                if ($val['goods_id'] == $v['goods_id']) {
                                    $cartOther[$key]['rate_price'] = $v['rate_price'];
                                }
                            }
                        }
                    }

                    if (file_exists(MOBILE_DRP)) {
                        // 分销配置 1.4.1 edit
                        $drp_config = app(DrpConfigService::class)->drpConfig();
                        $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;

                        $is_distribution = 0;
                        if ($drp_affiliate == 1) {
                            $parent_id = $this->commonRepository->getUserAffiliate();
                            if ($parent_id) {
                                $is_distribution = 1;
                            } else {
                                $is_distribution = 0;
                            }
                        }

                        $goodsInfo = $val['get_goods'] ? $val['get_goods'] : [];

                        if ($goodsInfo) {
                            $goodsExtend = $goodsInfo['get_goods_extend'] ? $goodsInfo['get_goods_extend'] : [];

                            $cartOther[$key]['is_reality'] = $goodsExtend['is_reality'] ?? 0;
                            $cartOther[$key]['is_return'] = $goodsExtend['is_return'] ?? 0;
                            $cartOther[$key]['is_fast'] = $goodsExtend['is_fast'] ?? 0;
                        } else {
                            $goodsInfo['is_distribution'] = 0;
                            $goodsInfo['dis_commission'] = 0;
                            $cartOther[$key]['is_reality'] = 0;
                            $cartOther[$key]['is_return'] = 0;
                            $cartOther[$key]['is_fast'] = 0;
                        }

                        $cartOther[$key]['is_distribution'] = $goodsInfo['is_distribution'] * $is_distribution;
                        $cartOther[$key]['drp_money'] = ($goodsInfo['dis_commission'] * $goodsInfo['is_distribution'] * $val['goods_price'] * $val['goods_number']) / 100 * $is_distribution;
                        $cartOther[$key]['commission_rate'] = $val['commission_rate'];
                        //微分销end
                    }

                    unset($cartOther[$key]['get_goods']);

                    $recId = OrderGoods::insertGetId($cartOther[$key]);

                    $coupons_list[$key]['rec_id'] = $recId;
                    $bonus_list[$key]['rec_id'] = $recId;
                    $rec_list[] = $recId;
                }

                /* 核对均摊优惠券商品金额 */
                $this->dscRepository->collateOrderGoodsBonus($coupons_list, $order['coupons'] ?? 0, $goods_coupons);

                /* 核对均摊红包商品金额 */
                $this->dscRepository->collateOrderGoodsCoupons($bonus_list, $order['bonus'] ?? 0, $goods_bonus);

                if ($rec_list) {
                    /* 清空购物车 */
                    $this->getClearCart($flow_type, $rec_id);
                } else {
                    $count = OrderGoods::where('order_id', $where['order_id'])->count();
                    if ($count <= 0) {
                        OrderInfo::where('order_id', $where['order_id'])->delete();
                    }
                }
            }
        }

        return $rec_list;
    }

    /**
     * 清空购物车
     *
     * @param int $type 类型：默认普通商品
     * @param string $rec_id
     */
    private function getClearCart($type = CART_GENERAL_GOODS, $rec_id = '')
    {
        $user_id = session('user_id', 0);

        $cart = Cart::where('rec_type', $type);

        if (!empty($rec_id)) {
            $rec_id = $this->baseRepository->getExplode($rec_id);
            $cart = $cart->whereIn('rec_id', $rec_id);
        }

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        $cart->delete();
    }

    /**
     * 返回订单中的虚拟商品
     *
     * @param int $order_id 订单id值
     * @return array
     */
    public function getVirtualGoods($order_id = 0)
    {
        $res = OrderGoods::selectRaw("goods_id, goods_name, (goods_number - send_number) AS num, extension_code")
            ->where('order_id', $order_id)
            ->where('is_real', 0)
            ->where('extension_code', 'virtual_card')
            ->whereRaw("(goods_number - send_number) > 0");

        $res = $this->baseRepository->getToArrayGet($res);

        $virtual_goods = [];
        if ($res) {
            foreach ($res as $row) {
                $virtual_goods[$row['extension_code']][] = ['goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']];
            }
        }

        return $virtual_goods;
    }

    /**
     * 虚拟商品发货
     *
     * @param $virtual_goods 虚拟商品数组
     * @param int $order_id 订单ID
     * @param string $order_sn 订单号
     * @return bool
     * @throws \Exception
     */
    private function orderVirtualGoodsShip(&$virtual_goods, $order_id = 0, $order_sn = '')
    {
        if ($virtual_goods) {
            foreach ($virtual_goods as $code => $goods_list) {
                /* 只处理虚拟卡 */
                if ($code == 'virtual_card') {
                    foreach ($goods_list as $goods) {
                        if (!$this->virtualCardShipping($goods, $order_id, $order_sn)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * 虚拟卡发货
     *
     * @param array $goods
     * @param int $order_id
     * @param string $order_sn
     * @return bool
     */
    private function virtualCardShipping($goods = [], $order_id = 0, $order_sn = '')
    {
        /* 检查有没有缺货 */
        $num = VirtualCard::where('goods_id', $goods['goods_id'])
            ->where('is_saled', 0)
            ->count();

        if ($num < $goods['num']) {
            return false;
        }

        /* 取出卡片信息 */
        $arr = VirtualCard::where('goods_id', $goods['goods_id'])
            ->where('is_saled', 0)
            ->take($goods['num']);

        $arr = $this->baseRepository->getToArrayGet($arr);

        $card_ids = [];
        if ($arr) {
            foreach ($arr as $virtual_card) {
                /* 卡号和密码解密 */
                if (($virtual_card['crc32'] == 0 || $virtual_card['crc32'] == crc32(AUTH_KEY) || $virtual_card['crc32'] == crc32(OLD_AUTH_KEY)) === false) {
                    return false;
                } else {
                    $card_ids[] = $virtual_card['card_id'];
                }
            }
        }

        /* 标记已经取出的卡片 */
        $other = [
            'is_saled' => 1,
            'order_sn' => $order_sn
        ];
        $res = VirtualCard::whereIn('card_id', $card_ids)->update($other);
        if (!$res) {
            return false;
        }

        /* 更新库存 */
        Goods::where('goods_id', $goods['goods_id'])->increment('goods_number', -$goods['num']);

        $order = [];
        if (true) {
            /* 更新订单信息 */
            $res = OrderGoods::where('order_id', $order_id)
                ->where('goods_id', $goods['goods_id'])
                ->update(['send_number' => $goods['num']]);

            if (!$res) {
                return false;
            }
        }

        if (!$order) {
            return false;
        }

        return true;
    }
}
