<?php

namespace App\Services\Order;

use App\Models\DeliveryOrder;
use App\Models\OfflineStore;
use App\Models\OrderDelayed;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\PresaleActivity;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Models\ShippingPoint;
use App\Models\StoreOrder;
use App\Models\TeamLog;
use App\Models\UserOrderNum;
use App\Models\ValueCardRecord;
use App\Plugins\UserRights\Discount\Services\DiscountRightsService;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\GroupBuyService;
use App\Services\Activity\PackageService;
use App\Services\Comment\CommentService;
use App\Services\Commission\CommissionService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Drp\DrpService;
use App\Services\Store\StoreService;
use App\Services\Team\TeamService;
use App\Services\User\UserOrderService;

/**
 * 会员订单
 * Class order
 * @package App\Services
 */
class OrderMobileService
{
    protected $commentService;
    protected $timeRepository;
    protected $orderApiService;
    protected $config;
    protected $commonService;
    protected $storeService;
    protected $packageService;
    protected $commonRepository;
    protected $orderRefoundService;
    protected $commissionService;
    protected $dscRepository;
    protected $teamService;
    protected $orderStatusService;
    protected $userOrderService;
    protected $baseRepository;
    protected $orderCommonService;
    protected $groupBuyService;

    public function __construct(
        OrderApiService $orderApiService,
        TimeRepository $timeRepository,
        StoreService $storeService,
        CommentService $commentService,
        TeamService $teamService,
        PackageService $packageService,
        CommonRepository $commonRepository,
        OrderRefoundService $orderRefoundService,
        CommissionService $commissionService,
        DscRepository $dscRepository,
        OrderStatusService $orderStatusService,
        UserOrderService $userOrderService,
        BaseRepository $baseRepository,
        OrderCommonService $orderCommonService
    )
    {
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
        $this->orderApiService = $orderApiService;
        $this->timeRepository = $timeRepository;
        $this->storeService = $storeService;
        $this->commentService = $commentService;
        $this->teamService = $teamService;
        $this->packageService = $packageService;
        $this->commonRepository = $commonRepository;
        $this->orderRefoundService = $orderRefoundService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
        $this->orderStatusService = $orderStatusService;
        $this->userOrderService = $userOrderService;
        $this->config = $this->dscRepository->dscConfig();
        $this->baseRepository = $baseRepository;
        $this->orderCommonService = $orderCommonService;
        $this->groupBuyService = app(GroupBuyService::class);
    }

    /**
     * 订单列表
     *
     * @param $uid
     * @param $status
     * @param $type
     * @param $page
     * @param $size
     * @return array
     * @throws \Exception
     */
    public function orderList($uid, $status, $type, $page, $size)
    {
        $List = $this->orderApiService->getUserOrders($uid, $status, $type, $page, $size);

        $orderList = [];

        $os = lang('user.os');
        $ps = lang('user.ps');
        $ss = lang('user.ss');

        $noTime = $this->timeRepository->getGmTime();
        $sign_time = $this->config['sign']; //发货日期起可退换货时间

        foreach ($List as $k => $v) {
            $orderList[$k]['order_id'] = $v['order_id'];
            $orderList[$k]['order_sn'] = $v['order_sn'];
            $orderList[$k]['consignee'] = $v['consignee'];
            $orderList[$k]['main_order_id'] = $v['main_order_id'];
            $orderList[$k]['is_delete'] = $v['is_delete'];
            $orderList[$k]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $v['add_time']); // 时间
            $orderList[$k]['order_status'] = $os[$v['order_status']] . ',' . $ps[$v['pay_status']] . ',' . $ss[$v['shipping_status']];
            $orderList[$k]['order_count'] = 0;
            $orderList[$k]['pay_code'] = Payment::where('pay_id', $v['pay_id'])->value('pay_code');

            if (isset($v['main_order_id'])) {
                $orderList[$k]['order_count'] = OrderInfo::where('main_order_id', $v['main_order_id'])->where('main_order_id', '>', 0)->count();
            }

            $orderList[$k]['order_child'] = OrderInfo::where('main_order_id', $v['order_id'])->count();

            $delivery = DeliveryOrder::select('invoice_no', 'shipping_name', 'update_time')->where('order_id', $v['order_id'])->first();

            $delivery = $delivery ? $delivery->toArray() : [];
            if (isset($delivery['update_time'])) {
                $orderList[$k]['delivery_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $delivery['update_time']);
            }

            $v['user_order'] = $v['order_status'];
            $v['user_shipping'] = $v['shipping_status'];
            $v['user_pay'] = $v['pay_status'];
            if ($v['user_order'] == OS_UNCONFIRMED || ($v['extension_code'] == 'group_buy' && $v['user_order'] == OS_CONFIRMED && $v['user_pay'] != PS_PAYED)) {
                $v['handler'] = 1; //取消订单
                $v['handler_this'] = ['order_id' => $v['order_id']];
                $v['online_pay'] = ['order_sn' => $v['order_sn']];
            } elseif ($v['user_order'] == OS_CONFIRMED || $v['user_order'] == OS_SPLITED || $v['user_order'] == OS_SPLITING_PART || $v['user_order'] == OS_RETURNED_PART || $v['user_order'] == OS_ONLY_REFOUND) {
                /* 对配送状态的处理 */
                if ($v['user_shipping'] == SS_SHIPPED || $v['user_shipping'] == SS_SHIPPED_PART) {
                    $v['handler'] = 2; //确认收货
                    $v['handler_this'] = ['order_id' => $v['order_id']];
                } elseif ($v['user_shipping'] == SS_RECEIVED) {
                    $v['handler'] = 4; // 已完成
                    $v['handler_this'] = ['order_id' => $v['order_id']];
                } else {
                    if ($v['user_pay'] == PS_UNPAYED) {
                        $v['handler'] = "<a class=\"btn-default-new br-5\" href=\"" . '" >' . lang('user.pay_money') . '</a>';
                    } else {
                        $v['handler'] = "<a  class=\"btn-default-new br-5\" href=\"" . '">' . lang('user.view_order') . '</a>';
                        $v['handler_this'] = ['order_id' => $v['order_id']];
                    }
                }
            } else {
                $v['handler'] = '<a class="btn-default-new br-5">' . $os[$v['user_order']] . '</a>';
            }

            if ($v['user_order'] == OS_SPLITED && $v['user_shipping'] == SS_RECEIVED && $v['user_pay'] == PS_PAYED) {
                $orderList[$k]['order_status'] = lang('user.ss_received');
            }

            if ($v['user_order'] == OS_SPLITED && $v['user_shipping'] == SS_RECEIVED && $v['user_pay'] == PS_PAYED) {
                $v['order_status'] = lang('user.ss_received');
                //添加晒单评价操作
                if (isset($v['sign']) && $v['sign'] > 0) {
                } else {
                    $v['handler'] = 3; //晒单评价
                }
                //返修退换货按钮
            } elseif ($v['user_order'] == OS_CANCELED && $v['user_shipping'] == SS_UNSHIPPED && $v['user_pay'] == PS_UNPAYED) {
                $v['handler'] = '';
            } else {
                if (!($v['user_order'] == OS_UNCONFIRMED && $v['user_shipping'] == SS_UNSHIPPED && $v['user_pay'] == PS_UNPAYED) && $v['handler'] != 2) {
                    $v['handler'] = '';
                }
            }

            $orderList[$k]['order_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $v['add_time']);
            //延迟收货
            $delay = 0;
            if ($v['user_order'] == OS_SPLITED && $v['user_pay'] == PS_PAYED && $v['user_shipping'] == SS_SHIPPED) {
                //时间满足必须满足   距自动确认收货前多少天可申请 < （自动确认收货时间+发货时间）-当前时间
                //$a < ($b + $c) - $noTime;
                $order_delay_day = $this->config['order_delay_day'] * 86400;//$a
                $auto_delivery_time = $v['auto_delivery_time'] * 86400;//$b
                $shipping_time = $v['shipping_time'];//$c
                if ($order_delay_day > (($auto_delivery_time + $shipping_time) - $noTime)) {
                    $map['review_status'] = ['neq', 1];
                    $map['order_id'] = $v['order_id'];
                    $num = OrderDelayed::where($map)->count();
                    if ($this->config['open_order_delay'] == 1 && $num < $this->config['order_delay_num']) {
                        $delay = 1;
                    }
                }
            }
            $orderList[$k]['delay'] = $delay;

            $dataTotalNumber = 0;
            // 删除标识
            $orderList[$k]['order_del'] = 0;
            if ($v['user_order'] == OS_CANCELED || $v['user_order'] == OS_INVALID || (($v['user_order'] == OS_UNCONFIRMED || $v['user_order'] == OS_CONFIRMED) && $v['user_shipping'] == SS_UNSHIPPED && ($v['user_pay'] == PS_UNPAYED || $v['user_pay'] == PS_PAYING)) || ($v['user_order'] == OS_SPLITED && $v['user_shipping'] == SS_RECEIVED && $v['user_pay'] == PS_PAYED)) {
                $orderList[$k]['order_del'] = 1;
            }

            if ($v['is_delete'] == 1) {
                $orderList[$k]['is_restore'] = 1;
            } else {
                $orderList[$k]['is_restore'] = 0;
            }

            if ($v['user_order'] == OS_SPLITED && $v['user_shipping'] == SS_RECEIVED && $v['user_pay'] == PS_PAYED) {
                $v['delete_yes'] = 1;
            } elseif (($v['user_order'] == OS_CONFIRMED || $v['user_order'] == OS_UNCONFIRMED || $v['user_order'] == OS_CANCELED) && $v['user_shipping'] == SS_UNSHIPPED && $v['user_pay'] == PS_UNPAYED) {
                $v['delete_yes'] = 1;
            } elseif ($v['user_order'] == OS_INVALID && $v['user_pay'] == PS_PAYED_PART && $v['user_shipping'] == SS_UNSHIPPED) {
                $v['delete_yes'] = 1;
            } else {
                $v['delete_yes'] = 0;
            }

            $orderList[$k]['delete_yes'] = $v['delete_yes'];

            $goods = $v['get_order_goods_list'] ?? [];
            $order_is_package_buy = 0;//过滤超值礼包

            if ($goods) {
                foreach ($goods as $key => $val) {
                    $val = $val['get_goods'] ? array_merge($val, $val['get_goods']) : $val;
                    $orderList[$k]['order_goods'][$key]['goods_id'] = $val['goods_id'];
                    $orderList[$k]['order_goods'][$key]['goods_price'] = $this->dscRepository->getPriceFormat($val['goods_price'] + $val['shipping_fee']);

                    $val['goods_thumb'] = $val['goods_thumb'] ?? '';
                    $orderList[$k]['order_goods'][$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);

                    if ($val['extension_code'] == 'package_buy') {
                        $order_is_package_buy = $order_is_package_buy + 1;//过滤超值礼包
                        $activity = get_goods_activity_info($val['goods_id']);

                        $activity['goods_thumb'] = $activity['goods_thumb'] ?? '';
                        $orderList[$k]['order_goods'][$key]['goods_thumb'] = $this->dscRepository->getImagePath($activity['goods_thumb']);
                    }
                    $orderList[$k]['order_goods'][$key]['goods_name'] = $val['goods_name'];
                    $orderList[$k]['order_goods'][$key]['goods_sn'] = $val['goods_sn'];
                    $orderList[$k]['order_goods'][$key]['drp_money'] = $this->dscRepository->getPriceFormat($val['drp_money']);
                    $orderList[$k]['order_goods'][$key]['ru_id'] = $val['ru_id'];
                    $orderList[$k]['order_goods'][$key]['parent_id'] = $val['parent_id'];
                    $orderList[$k]['order_goods'][$key]['is_gift'] = $val['is_gift'];
                }
            } else {
                $orderList[$k]['order_goods'] = [];
            }

            /* 退换货显示处理 start */
            $handler_return = 0;
            // 已确认或已分单 已收货 未支付 （货到付款）可退货
            if (($v['user_order'] == OS_CONFIRMED || $v['user_order'] == OS_SPLITED) && $v['user_shipping'] == SS_RECEIVED && $v['user_pay'] == PS_UNPAYED) {
                $handler_return = 1;
            }
            // 已付款 未发货 可退款
            if ($v['user_shipping'] == SS_UNSHIPPED && $v['user_pay'] == PS_PAYED) {
                $handler_return = 1;
            }
            // 判断发货日期起可退换货时间, 且订单未取消 已支付 可退换货
            if ($v['user_order'] != OS_CANCELED && $v['user_pay'] == PS_PAYED && $sign_time > 0) {
                $day = (($noTime - $v['shipping_time']) / 3600 / 24);
                if ($day < $sign_time) {
                    $handler_return = 1;
                }
            }
            // 订单商品是否已申请退款(部分退款1，全退款0)
            $order_goods_count = count($goods);
            $return_goods = OrderReturn::where('order_id', $v['order_id'])->where('user_id', $v['user_id'])->count();
            if ($return_goods < $order_goods_count) {
                $handler_return = 1;
            } elseif ($order_goods_count == $return_goods) {
                $handler_return = 0;
            }

            $orderList[$k]['handler_return'] = $handler_return;
            /* 退换货显示处理 end */

            $province = Region::where('region_id', $v['province'])->value('region_name');
            $city = Region::where('region_id', $v['city'])->value('region_name');
            $district = Region::where('region_id', $v['district'])->value('region_name');

            $district_name = !empty($district) ? $district : '';
            $address_detail = $province . "&nbsp;" . $city . "市" . "&nbsp;" . $district_name;

            $orderList[$k]['order_goods_num'] = count($goods);
            // 店铺名称
            $shopinfo = $this->orderApiService->getShopInfo($v['order_id']);
            $user_name = $this->orderApiService->getOrderStore($v['order_id']);
            $orderList[$k]['shop_id'] = $shopinfo['shop_id'];

            if ($v['main_count'] > 0) {
                $orderList[$k]['shop_name'] = $this->config['shop_name'];
            } else {
                $orderList[$k]['shop_name'] = isset($shopinfo['shop_name']) ? $shopinfo['shop_name'] : '';
            }

            $orderList[$k]['kf_qq'] = isset($user_name['kf_qq']) ? $user_name['kf_qq'] : '';
            $orderList[$k]['kf_ww'] = isset($user_name['kf_ww']) ? $user_name['kf_ww'] : '';
            $orderList[$k]['kf_type'] = isset($user_name['kf_type']) ? $user_name['kf_type'] : '';
            $orderList[$k]['invoice_no'] = $v['invoice_no'];
            $orderList[$k]['email'] = $v['email'];
            $orderList[$k]['shipping_name'] = $v['shipping_name'];
            $orderList[$k]['shipping_id'] = $v['shipping_id'];
            $orderList[$k]['address_detail'] = $address_detail;
            $orderList[$k]['tel'] = $v['tel'];
            $orderList[$k]['handler'] = $v['handler'];
            $orderList[$k]['online_pay'] = $v['online_pay'] ?? '';
            $orderList[$k]['team_id'] = $v['team_id'];
            $orderList[$k]['extension_code'] = $v['extension_code'];
            $orderList[$k]['total_number'] = $dataTotalNumber; // 配送状态
            $orderList[$k]['goods_amount_formated'] = $this->dscRepository->getPriceFormat($v['goods_amount']);
            $orderList[$k]['money_paid_formated'] = $this->dscRepository->getPriceFormat($v['money_paid']);
            $orderList[$k]['order_amount_formated'] = $this->dscRepository->getPriceFormat($v['order_amount']);
            $orderList[$k]['shipping_fee_formated'] = $this->dscRepository->getPriceFormat($v['shipping_fee']);

            $orderList[$k]['invoice_no'] = $v['invoice_no'];
            $orderList[$k]['total_amount'] = $v['order_amount']; // 总金额

            //过滤超值礼包 订单中的商品是否全部是超值礼包
            if (count($goods) === $order_is_package_buy) {
                $orderList[$k]['extension_code'] = 'package_buy';
            }
            //是否是门店订单
            $is_store_order = StoreOrder::where('order_id', $v['order_id'])->count();
            $orderList[$k]['is_store_order'] = $is_store_order > 0 ? 1 : 0;

            if ($v['user_pay'] == PS_PAYED) {
                $total_fee_order = $v['money_paid'] + $v['surplus'];
                $orderList[$k]['is_pay'] = 1;
            } else {
                $amount = $v['goods_amount'] + $v['insure_fee'] + $v['pay_fee'] + $v['pack_fee'] + $v['card_fee'] + $v['tax'];

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $amount += $v['rate_fee'];
                }

                if ($amount > $v['discount']) {
                    $amount -= $v['discount'];
                } else {
                    $amount = 0;
                }

                if ($amount > $v['bonus']) {
                    $amount -= $v['bonus'];
                } else {
                    $amount = 0;
                }

                if ($amount > $v['coupons']) {
                    $amount -= $v['coupons'];
                } else {
                    $amount = 0;
                }

                if ($amount > $v['integral_money']) {
                    $amount -= $v['integral_money'];
                } else {
                    $amount = 0;
                }

                $total_fee_order = $amount + $v['shipping_fee'];
                $orderList[$k]['is_pay'] = 0;
            }

            $orderList[$k]['failure'] = 0;
            //验证拼团订单是否失败
            if (file_exists(MOBILE_TEAM)) {
                if ($v['team_id'] > 0) {
                    $failure = $this->getTeamInfo($v['team_id'], $v['order_id']);
                    $orderList[$k]['failure'] = $failure;
                }
            }


            $orderList[$k]['total_amount_formated'] = $this->dscRepository->getPriceFormat($total_fee_order);
        }

        return $orderList;
    }

    /**
     * 订单详情
     * @param $args
     * @return mixed
     */
    public function orderDetail($args)
    {

        $lang = lang('user');

        $order = OrderInfo::where('user_id', $args['uid'])
            ->where('order_id', $args['order_id']);

        $order = $order->with([
            'getOrderGoodsList' => function ($query) {
                $query->with('getGoods');
            }
        ]);

        $order = $order->first();

        $order = $order ? $order->toArray() : [];

        if (empty($order)) {
            return [];
        }

        $order = $this->userOrderService->mainShipping($order);

        // 店铺名称
        $shopinfo = $this->orderApiService->getShopInfo($args['order_id']);

        $user_name = $this->orderApiService->getOrderStore($args['order_id']);

        $address = $this->getRegionName($order['country']);
        $address .= $this->getRegionName($order['province']);
        $address .= $this->getRegionName($order['city']);
        $address .= $this->getRegionName($order['district']);
        $address .= $order['address'];
        //订单使用储值卡
        $card_info = $this->value_card_record($args['order_id']);
        $card_amount_money = $card_info['use_val'] ?? '';
        $card_vc_id = $card_info['vc_id'] ?? 0;
        $pay_code = Payment::where('pay_id', $order['pay_id'])->value('pay_code');
        $pay_code = empty($pay_code) ? '' : $pay_code;

        if ($order['main_count'] > 0) {
            $shopinfo['shop_name'] = $this->config['shop_name'];
            $shopinfo['shop_id'] = 0;
        }

        $list = [
            'add_time' => $this->timeRepository->getLocalDate($this->config['time_format'], $order['add_time']),
            'address' => $address,
            'consignee' => $order['consignee'],
            'mobile' => $order['mobile'],
            'shop_id' => $order['ru_id'],
            'shop_name' => $shopinfo['shop_name'],
            'kf_qq' => $user_name['kf_qq'],
            'kf_ww' => $user_name['kf_ww'],
            'kf_type' => $user_name['kf_type'],
            'money_paid' => $order['money_paid'],
            'money_paid_formated' => $this->dscRepository->getPriceFormat($order['money_paid'], false),
            'goods_amount' => $order['goods_amount'],
            'goods_amount_formated' => $this->dscRepository->getPriceFormat($order['goods_amount'], false),
            'order_amount' => $order['order_amount'],
            'order_amount_formated' => $this->dscRepository->getPriceFormat($order['order_amount'], false),
            'order_id' => $order['order_id'],
            'order_sn' => $order['order_sn'],
            'tax_id' => $order['tax_id'], //纳税人识别码
            'inv_payee' => $order['inv_payee'],   //个人还是公司名称 ，增值发票时此值为空
            'inv_content' => $order['inv_content'],//发票明细
            'vat_id' => $order['vat_id'],//增值发票对应的id
            'invoice_type' => $order['invoice_type'],// 0普通发票，1增值发票
            //'invoice_no' => $order['invoice_no'],// 发货单号
            'order_status' => $this->orderStatusService->orderStatus($order['order_status']),
            'pay_status' => $this->orderStatusService->payStatus($order['pay_status']),
            'shipping_status' => $this->orderStatusService->shipStatus($order['shipping_status']),
            'pay_time' => $this->timeRepository->getLocalDate($this->config['time_format'], $order['pay_time']),
            'shipping_time' => $this->timeRepository->getLocalDate($this->config['time_format'], $order['shipping_time']),
            'pay_fee' => $order['pay_fee'],
            'pay_fee_formated' => $this->dscRepository->getPriceFormat($order['pay_fee'], false),
            'pay_name' => $order['pay_name'],
            'pay_note' => $order['pay_note'],
            'pay_code' => $pay_code,
            'pack_name' => $order['pack_name'],
            'pack_id' => $order['pack_id'],
            'card_name' => $order['card_name'],
            'card_id' => $order['card_id'],
            'card_amount' => $card_amount_money,
            'vc_id' => $card_vc_id,
            'parent_id' => $order['parent_id'],
            'shipping_fee' => $order['shipping_fee'],
            'bonus_id' => $order['bonus_id'],
            'bonus' => $this->dscRepository->getPriceFormat($order['bonus']),
            'discount' => $order['discount'],
            'shipping_fee_formated' => $this->dscRepository->getPriceFormat($order['shipping_fee'], false),
            'discount_formated' => $this->dscRepository->getPriceFormat($order['discount'], false),
            'shipping_id' => $order['shipping_id'],
            'shipping_name' => $order['shipping_name'],
            'total_amount' => $order['order_amount'],
            'team_id' => $order['team_id'],
            'team_parent_id' => $order['team_parent_id'],
            'team_user_id' => $order['team_user_id'],
            'team_price' => $order['team_price'],
            'total_amount_formated' => $this->dscRepository->getPriceFormat($order['order_amount'], false),
            'coupons_type' => $order['coupons'] > 0 ? 1 : 0,
            'coupons' => $this->dscRepository->getPriceFormat($order['coupons']),
            'integral' => $order['integral'],
            'integral_money' => $this->dscRepository->getPriceFormat($order['integral_money']),
            'surplus' => $order['surplus'],
            'surplus_formated' => $this->dscRepository->getPriceFormat($order['surplus']),
            'exchange_goods' => $order['extension_code'] == 'exchange_goods' ? 1 : 0,
            'postscript' => isset($order['postscript']) ? $order['postscript'] : '',//用户留言 --1.3.7
            'main_count' => $order['main_count'],
            'extension_code' => $order['extension_code'],
            'extension_id' => $order['extension_id'],
        ];
        $list['order_status'] = $list['order_status'] . ',' . $list['pay_status'] . ',' . $list['shipping_status'];

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $list['rate_fee'] = $order['rate_fee'];
            $list['rate'] = $this->dscRepository->getPriceFormat($list['rate_fee']);

            $cbec = app(CrossBorderService::class)->cbecExists();

            if (!empty($cbec)) {
                $list['is_kj'] = 0;
                $is_kj = $cbec->isKj($order['ru_id']);
                $list['is_kj'] = empty($is_kj) && $list['is_kj'] == 0 ? 0 : 1;
            }
        }

        if (file_exists(MOBILE_DRP)) {
            // 购买开通权益卡折扣
            $order_membership_card = app(DiscountRightsService::class)->getOrderInfoMembershipCard($order['order_id'], $order['user_id']);
            if (!empty($order_membership_card)) {
                $list['membership_card_id'] = $order_membership_card['membership_card_id'];

                $list['membership_card_buy_money'] = $order_membership_card['membership_card_buy_money'] ?? 0;
                $list['membership_card_discount_price'] = $order_membership_card['membership_card_discount_price'] ?? 0;

                $list['membership_card_buy_money_formated'] = $this->dscRepository->getPriceFormat($order_membership_card['membership_card_buy_money']);
                $list['membership_card_discount_price_formated'] = $this->dscRepository->getPriceFormat($order_membership_card['membership_card_discount_price']);
            } else {
                $list['membership_card_id'] = 0;
            }
        }

        /* 订单追踪 */
        if (!empty($order['invoice_no'])) {
            $list['tracker'] = route('tracker', ['order_sn' => $order['order_sn']]);
        }

        /*
        * 正常订单显示支付倒计时
        */

        $pay_code = Payment::where('pay_code', 'cod')->value('pay_id');
        if ($order['extension_code'] != 'presale' && ($order['order_status'] == OS_UNCONFIRMED || ($order['order_status'] == OS_CONFIRMED && $order['pay_status'] == PS_UNPAYED)) && $pay_code != $order['pay_id']) {

            $pay_effective_time = isset($this->config['pay_effective_time']) && $this->config['pay_effective_time'] > 0 ? intval($this->config['pay_effective_time']) : 0; //订单时效
            $pay_effective_time = $order['add_time'] + $pay_effective_time * 60;
            if ($pay_effective_time < $this->timeRepository->getGmTime()) {
                $list['pay_effective_time'] = 0;
            } else {
                $list['pay_effective_time'] = $pay_effective_time;
            }
        }


        if ($order['shipping_status'] == SS_UNSHIPPED && ($order['order_status'] == OS_UNCONFIRMED || ($order['order_status'] == OS_CONFIRMED && $order['pay_status'] == PS_UNPAYED))) {
            $list['handler'] = 1; // 取消订单
        } elseif ($order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED) {
            /* 对配送状态的处理 */
            if ($order['shipping_status'] == SS_SHIPPED) {
                $list['handler'] = 2;// 确认收货
            } elseif ($order['shipping_status'] == SS_RECEIVED) {
                $list['handler'] = 4; // 已完成
            } else {
                if ($order['pay_status'] == PS_UNPAYED) {
                    $list['handler'] = 5; // 付款
                } else {
                    $list['handler'] = ''; // 查看订单
                }
            }
        } elseif ($order['order_status'] == OS_CANCELED) {
            $list['handler'] = 7; // 已取消
        } elseif ($order['order_status'] == OS_INVALID) {
            $list['handler'] = 8; // 无效
        } else {
            $list['handler'] = 6; // 已确认
        }

        if (!empty($list)) {
            $orderGoods = $order['get_order_goods_list'] ?? [];

            $goodsList = [];
            $total_number = 0;
            $goods_count = 0;
            $package_goods_count = 0;
            $package_list_total = 0;

            if ($orderGoods) {
                foreach ($orderGoods as $k => $v) {

                    $goodsList[$k]['goods_number'] = $v['goods_number'];
                    $v = $v['get_goods'] ? array_merge($v, $v['get_goods']) : $v;
                    $goodsList[$k]['goods_id'] = $v['goods_id'];
                    $goodsList[$k]['goods_name'] = $v['goods_name'];

                    if ($v['extension_code'] == 'package_buy') {
                        /* 取得礼包信息 */
                        $package = $this->packageService->getPackageInfo($v['goods_id']);
                        $v['package_goods_list'] = $package['goods_list'];
                        $v['goods_thumb'] = $package['activity_thumb'];
                    }
                    $v['goods_thumb'] = $v['goods_thumb'] ?? '';
                    $goodsList[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);

                    $goodsList[$k]['goods_price'] = $v['goods_price'];
                    $goodsList[$k]['goods_price_formated'] = $this->dscRepository->getPriceFormat($v['goods_price'], false);
                    $goodsList[$k]['goods_sn'] = $v['goods_sn'];
                    $shop_message = $this->storeService->getMerchantsStoreInfo($v['ru_id'], 1);
                    $goodsList[$k]['shop_name'] = $shop_message['shop_name'];
                    $total_number += $v['goods_number'];
                    $goodsList[$k]['parent_id'] = $v['parent_id'];
                    $goodsList[$k]['goods_attr'] = $v['goods_attr'];
                    $goodsList[$k]['is_gift'] = $v['is_gift'];
                    $goodsList[$k]['is_real'] = $v['is_real'];
                    if ($v['is_real'] == 0) {
                        $goodsList[$k]['virtual_goods'] = $this->orderApiService->get_virtual_goods_info($v['rec_id']);
                    }
                    $goodsList[$k]['extension_code'] = $v['extension_code'];

                    //礼包统计
                    if ($v['extension_code'] == 'package_buy') {
                        $goodsList[$k]['package_goods_list'] = $v['package_goods_list'];
                        $subtotal = $v['goods_price'] * $v['goods_number'];
                        $package_goods_count++;
                        foreach ($v['package_goods_list'] as $package_goods_val) {
                            $package_list_total += $package_goods_val['shop_price'] * $package_goods_val['goods_number'];
                        }

                        $goodsList[$k]['package_list_total'] = $package_list_total;
                        $goodsList[$k]['package_list_saving'] = $subtotal - $package_list_total;
                        $goodsList[$k]['format_package_list_total'] = $this->dscRepository->getPriceFormat($goodsList[$k]['package_list_total']);
                        $goodsList[$k]['format_package_list_saving'] = $this->dscRepository->getPriceFormat($goodsList[$k]['package_list_saving']);
                    } else {
                        $goods_count++;
                    }
                    $goodsList[$k]['is_single'] = $v['is_single'];
                    $goodsList[$k]['freight'] = $v['freight'];
                    $goodsList[$k]['drp_money'] = $v['drp_money'];
                    $goodsList[$k]['shipping_fee'] = $v['shipping_fee'];
                    $goodsList[$k]['commission_rate'] = $v['commission_rate'];
                }
            }

            $list['goods'] = $goodsList;
            $list['total_number'] = $total_number;
        }

        //延迟收货
        $list['delay'] = 0;
        if ($order['order_status'] == OS_SPLITED && $order['pay_status'] == PS_PAYED && $order['shipping_status'] == SS_SHIPPED) {
            $order_delay_day = $this->config['order_delay_day'] * 86400;
            $auto_delivery_time = $order['auto_delivery_time'] * 86400;
            $shipping_time = $order['shipping_time'];

            if ($order_delay_day > (($auto_delivery_time + $shipping_time) - $this->timeRepository->getGmTime())) {
                $num = OrderDelayed::where('review_status', '<>', 1)->where('order_id', $list['order_id'])->count();

                if ($this->config['open_order_delay'] == 1 && $num < $this->config['order_delay_num']) {
                    $list['delay'] = 1;
                }
            }
        }

        $delay_type = OrderDelayed::where('order_id', $list['order_id'])
            ->orderBy('delayed_id', 'DESC')
            ->value('review_status');
        if (isset($delay_type)) {
            if ($delay_type == 0) {
                $list['delay_type'] = $lang['is_confirm'][$delay_type];//"未审核";
            }
            if ($delay_type == 1) {
                $list['delay_type'] = $lang['is_confirm'][$delay_type];//"已审核";
            }
            if ($delay_type == 2) {
                $list['delay_type'] = $lang['is_confirm'][$delay_type];//"审核未通过";
            }
        } else {
            $list['delay_type'] = $lang['applied']; // 审请
        }

        if ($order['extension_code'] == 'presale') {//预售是否可支付尾款
            $list['presale_final_pay'] = 0;
            $time = $this->timeRepository->getGmTime();
            $presale = PresaleActivity::select('pay_start_time', 'pay_end_time')->where('act_id', $order['extension_id'])->first();
            $presale = $presale ? $presale->toArray() : [];
            if ($presale && $presale['pay_start_time'] <= $time && $presale['pay_end_time'] > $time) {
                $list['presale_final_pay'] = 1;//支付尾款时间内
            }
        }

        /* 获取订单门店信息  start */
        $stores = StoreOrder::select('id', 'store_id', 'pick_code', 'take_time')
            ->where('order_id', $args['order_id'])
            ->first();
        $stores = $stores ? $stores->toArray() : '';

        if (!empty($stores)) {
            $list['store_id'] = $stores['store_id'];
            $list['pick_code'] = $stores['pick_code'];
            $list['take_time'] = $stores['take_time'];

            $offline_store = OfflineStore::from('offline_store as o')
                ->select('o.*', 'p.region_name as province', 'c.region_name as city', 'd.region_name as district')
                ->leftjoin('region as p', 'p.region_id', 'o.province')
                ->leftjoin('region as c', 'c.region_id', 'o.city')
                ->leftjoin('region as d', 'd.region_id', 'o.district')
                ->where('o.id', $list['store_id'])
                ->first();
            $offline_store = $offline_store ? $offline_store->toArray() : [];

            $list['offline_store'] = $offline_store;
        }

        /* 自提点信息 */
        if (!empty($order['point_id'])) {
            $point = ShippingPoint::where('id', $order['point_id'])->first();
            $point = $point ? $point->toArray() : [];
            if ($point) {
                $order['shipping_datestr'] = $order['shipping_datestr'] ?? '';
                $point['pickDate'] = $this->timeRepository->getLocalDate('Y', $this->timeRepository->getLocalStrtoTime($order['add_time'])) . '年' . $order['shipping_datestr'];

                $list['point'] = $point;
            }
        }
        $list['failure'] = 0;
        //验证拼团订单是否失败
        if (file_exists(MOBILE_TEAM)) {
            if ($list['team_id'] > 0) {
                $failure = $this->getTeamInfo($list['team_id'], $list['order_id']);
                $list['failure'] = $failure;
            }
        }

        // 团购支付保证金标识
        $list['is_group_deposit'] = 0;
        if ($order['extension_code'] == 'group_buy') {
            $group_buy = $this->groupBuyService->getGroupBuyInfo(['group_buy_id' => $order['extension_id']]);
            if (isset($group_buy) && $group_buy['deposit'] > 0 && $group_buy['is_finished'] == 0) {
                $list['is_group_deposit'] = 1;
            }
        }


        return $list;
    }

    /**
     * 获取拼团信息,验证失败提示
     */
    public function getTeamInfo($team_id = 0, $order_id = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $info = TeamLog::from('team_log as tl')
            ->select('tg.team_num', 'tg.validity_time', 'tg.is_team', 'tl.start_time', 'tl.status', 'oi.order_status', 'oi.pay_status')
            ->leftjoin('order_info as oi', 'tl.team_id', '=', 'oi.team_id')
            ->leftjoin('team_goods as tg', 'tl.t_id', '=', 'tg.id')
            ->where('tl.team_id', $team_id)
            ->where('oi.order_id', $order_id)
            ->first();

        $team_info = $info ? $info->toArray() : [];

        $end_time = ($team_info['start_time'] + ($team_info['validity_time'] * 3600));

        if ($time < $end_time && $team_info['status'] == 1 && $team_info['pay_status'] != 2 && $team_info['order_status'] != 4) {
            //参团 ：拼团完成、未结束、未付款订单过期
            $failure = 1;
        } elseif ($time > $end_time && $team_info['status'] == 1 && $team_info['pay_status'] != 2 && $team_info['order_status'] != 4) {
            //参团 ：拼团结束，完成，未付款过期
            $failure = 1;
        } elseif (($time > $end_time || $time < $end_time) && $team_info['status'] != 1 && $team_info['order_status'] == 2) {
            //订单取消
            $failure = 1;
        } elseif ($time > $end_time && $team_info['status'] != 1 && $team_info['pay_status'] != 2 && $team_info['order_status'] != 2) {
            //未付款
            $failure = 1;
        } elseif ($team_info['status'] != 1 && ($time > $end_time || $team_info['is_team'] != 1)) {
            //开团：未成功
            $failure = 1;
        } else {
            $failure = 0;
        }
        return $failure;
    }


    /**
     * 订单列表数量
     * @param $uid
     * @return array
     */
    public function orderNum($uid)
    {
        $eval = $this->commentService->getUserOrderCommentCount($uid);
        $count = $this->orderApiService->getUserOrdersReturnCount($uid);

        return [
            'all' => $this->orderApiService->getOrderCount($uid, 0), //订单数量
            'nopay' => $this->orderApiService->getOrderCount($uid, 1), //待付款订单数量
            'nogoods' => $this->orderApiService->getOrderCount($uid, 2), //待收货订单数量
            'isfinished' => $this->orderApiService->getOrderCount($uid, 3), //已完成订单数量
            'isdelete' => $this->orderApiService->getOrderCount($uid, 4), //回收站订单数量
            'team_num' => $this->teamService->teamOrderNum($uid),
            'not_comment' => $eval,  //待评价订单数量
            'return_count' => $count, //待同意状态退换货申请数量
        ];
    }

    /**
     * 确认订单收货
     *
     * @param $uid
     * @param $order_id
     * @return array|bool
     * @throws \Exception
     */
    public function orderConfirm($uid, $order_id)
    {
        $order = OrderInfo::where('user_id', $uid)
            ->where('order_id', $order_id)
            ->with([
                'getPayment'
            ])
            ->first();
        $order = $order ? $order->toArray() : [];
        if (empty($order)) {
            return [];
        }

        if ($order['shipping_status'] == SS_RECEIVED) {
            /* 检查订单 */
            return ['error' => lang('common.order_already_received')];
        } elseif ($order['shipping_status'] != SS_SHIPPED && $order['shipping_status'] != SS_SHIPPED_PART) {
            return ['error' => lang('common.order_invalid')];
        } else {

            /* 修改订单发货状态为“确认收货” */
            $confirm_take_time = $this->timeRepository->getGmTime();
            $data = [
                'shipping_status' => SS_RECEIVED,
                'confirm_take_time' => $confirm_take_time
            ];
            $up = OrderInfo::where('user_id', $uid)->where('order_id', $order_id)->update($data);

            if ($up) {

                $payment = $order['get_payment'] ?? [];

                if ($payment && $payment['pay_code'] != 'cod' && $order['is_zc_order'] == 0) {
                    /* 更新会员订单信息 */
                    $dbRaw = [
                        'order_nogoods' => "order_nogoods - 1",
                        'order_isfinished' => "order_isfinished + 1"
                    ];
                    $dbRaw = $this->baseRepository->getDbRaw($dbRaw);

                    UserOrderNum::where('user_id', $uid)->where('order_nogoods', '>', 0)->update($dbRaw);
                }

                load_helper(['common', 'ecmoban']);
                /* 记录日志 */
                order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], lang('common.receive'), lang('common.buyer'), 0, $confirm_take_time);

                $seller_id = OrderGoods::where('order_id', $order_id)->value('ru_id');
                $value_card = ValueCardRecord::where('order_id', $order_id)->value('use_val');

                $return_amount_info = $this->orderRefoundService->orderReturnAmount($order_id);

                if ($order['order_amount'] > 0 && $order['order_amount'] > $order['rate_fee']) {
                    $order_amount = $order['order_amount'] - $order['rate_fee'];
                } else {
                    $order_amount = $order['order_amount'];
                }

                $other = [
                    'user_id' => $order['user_id'] ?? '',
                    'seller_id' => $seller_id ?? 0,
                    'order_id' => $order['order_id'],
                    'order_sn' => $order['order_sn'],
                    'order_status' => $order['order_status'],
                    'shipping_status' => SS_RECEIVED,
                    'pay_status' => $order['pay_status'],
                    'order_amount' => $order_amount,
                    'return_amount' => $return_amount_info['return_amount'],
                    'goods_amount' => $order['goods_amount'],
                    'tax' => $order['tax'] ?? '',
                    'shipping_fee' => $order['shipping_fee'] ?? '',
                    'insure_fee' => $order['insure_fee'] ?? '',
                    'pay_fee' => $order['pay_fee'] ?? '',
                    'pack_fee' => $order['pack_fee'] ?? '',
                    'card_fee' => $order['card_fee'] ?? '',
                    'bonus' => $order['bonus'] ?? '',
                    'integral_money' => $order['integral_money'] ?? '',
                    'coupons' => $order['coupons'] ?? '',
                    'discount' => $order['discount'] ?? '',
                    'value_card' => $value_card ?? '',
                    'money_paid' => $order['money_paid'] ?? '',
                    'surplus' => $order['surplus'] ?? '',
                    'confirm_take_time' => $confirm_take_time,
                    'rate_fee' => $order['rate_fee'],
                    'return_rate_fee' => $return_amount_info['return_rate_price']
                ];

                if ($this->config['sms_order_received'] == '1') {
                    //获取店铺客服电话
                    $seller_shop_info = SellerShopinfo::where('ru_id', $order['ru_id']);
                    $seller_shop_info = $this->baseRepository->getToArrayFirst($seller_shop_info);
                    //阿里大鱼短信接口参数
                    if (isset($seller_shop_info['mobile']) && $seller_shop_info['mobile']) {
                        $smsParams = [
                            'ordersn' => $order['order_sn'],
                            'consignee' => $order['consignee'],
                            'ordermobile' => $order['mobile']
                        ];

                        $this->commonRepository->smsSend($seller_shop_info['mobile'], $smsParams, 'sms_order_received');
                    }
                }

                if ($seller_id) {
                    $this->commissionService->getOrderBillLog($other);
                    $this->commissionService->setBillOrderReturn($return_amount_info['ret_id'], $other['order_id']);
                }

                // 确认收货 购买成为分销商商品 绑定权益卡
                if (file_exists(MOBILE_DRP) && $order['pay_status'] == PS_PAYED) {
                    app(DrpService::class)->buyGoodsUpdateDrpShop($order);
                }
                return true;
            }
            return false;
        }
    }


    /**
     * 延迟收货申请
     * @param $uid
     * @param $orderId
     * @return mixed
     */
    public function orderDelay($uid = 0, $orderId)
    {
        $lang = lang('user');

        $review_status = ['neq', 1];
        $order = OrderInfo::where('order_id', $orderId)->first();

        // 验证订单所属
        if ($order->user_id != $uid) {
            $result['error'] = 1;
            $result['msg'] = $lang['not_your_order'];
            return $result;
        }

        //判断开关config['open_order_delay']
        if ($this->config['open_order_delay'] != 1) {
            $result['error'] = 1;
            $result['msg'] = $lang['order_delayed_wrong'];
            return $result;
        }

        //判断订单状态
        if (!in_array($order->order_status, [OS_CONFIRMED, OS_SPLITED]) || $order->shipping_status != SS_SHIPPED) { //发货状态
            $result['error'] = 2;
            $result['msg'] = $lang['order_delayed_wrong'];
            return $result;
        }

        //判断时间config['order_delay_day']
        $nowTime = $this->timeRepository->getGmTime();
        $delivery_time = $order->shipping_time + 24 * 3600 * $order->auto_delivery_time;
        $order_delay_day = (isset($this->config['order_delay_day']) && $this->config['order_delay_day'] > 0) ? intval($this->config['order_delay_day']) : 3;//如无配置，最多可提前3天申请
        $order_delay_num = (isset($this->config['order_delay_num']) && $this->config['order_delay_num'] > 0) ? intval($this->config['order_delay_num']) : 3;//如无配置，最多可申请3次
        if (($nowTime > $delivery_time || ($delivery_time - $nowTime) / 86400 > $order_delay_day)) {
            $result['error'] = 1;
            $result['msg'] = sprintf(lang('user.order_delay_day_desc'), $order_delay_day);
            return $result;
        }

        $num = OrderDelayed::where('order_id', $orderId)
            ->where('review_status', $review_status)
            ->count();

        //TODO 延迟收货和pc暂没统一
        if ($num < 1) {
            $delay_num = OrderDelayed::where('order_id', $orderId)->count();
            if ($delay_num < $this->config['order_delay_num']) {
                $action_log = [
                    'order_id' => $orderId,
                    'apply_time' => $nowTime
                ];
                OrderDelayed::insertGetId($action_log);
                $result['error'] = 0;
                $result['msg'] = $lang['application_is_successful'];
            } else {
                $result['error'] = 1;
                $result['msg'] = $lang['much_applications'];
            }
        } else {
            $result['error'] = 1;
            $result['msg'] = $lang['not_audit_applications'];
        }

        return $result;
    }

    /**
     * 取消订单
     * @param $args
     * @return mixed
     * 订单状态只能是“未确认”或“已确认”
     * 发货状态只能是“未发货”
     * 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
     */
    public function orderCancel($args)
    {
        $order = OrderInfo::where('user_id', $args['uid'])
            ->where('order_id', $args['order_id']);

        $order = $order->first();

        $order = $order ? $order->toArray() : [];

        if (empty($order)) {
            return false;
        }

        if ($order['user_id'] != $args['uid']) {
            return ['error' => 1, 'msg' => '不是本人订单'];
        }

        // 订单状态只能是“未确认”或“已确认”
        if ($order['order_status'] != OS_UNCONFIRMED && $order['order_status'] != OS_CONFIRMED) {
            return ['error' => 1, 'msg' => '订单不能取消'];
        }
        // 发货状态只能是“未发货”
        if ($order['shipping_status'] != SS_UNSHIPPED) {
            return ['error' => 1, 'msg' => '订单已确认'];
        }
        // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
        if ($order['pay_status'] != PS_UNPAYED) {
            return ['error' => 1, 'msg' => '订单已付款，请与商家联系'];
        }

        $res = $this->orderApiService->orderCancel($args['uid'], $args['order_id']);

        return $res;
    }

    /**
     * 删除订单
     *
     * @param array $args
     * @return mixed
     */
    public function orderDelete($args = [])
    {
        $order = OrderInfo::where('user_id', $args['uid'])
            ->where('order_id', $args['order_id'])
            ->first();

        if ($order->is_delete == 1) {

            $is_delete = 2;

            //隐藏会员查看订单
            $order->is_delete = $is_delete;

            if ($order->main_count > 0) {
                OrderInfo::where('main_order_id', $order->order_id)
                    ->update([
                        'is_delete' => $is_delete
                    ]);
            }
        } else {
            //放入订单回收站
            $order->is_delete = 1;
        }

        $res = $order->save();

        $this->orderCommonService->getUserOrderNumServer($args['uid']);

        return $res;
    }

    /**
     * 订单还原
     *
     * @param $args
     * @return mixed
     */
    public function orderRestore($args = [])
    {
        $order = OrderInfo::where('user_id', $args['uid'])
            ->where('order_id', $args['order_id'])
            ->first();

        $order->is_delete = 0;

        if ($order->main_count > 0) {
            OrderInfo::where('main_order_id', $order->order_id)
                ->update([
                    'is_delete' => 0
                ]);
        }

        $res = $order->save();

        $this->orderCommonService->getUserOrderNumServer($args['uid']);

        return $res;
    }

    /**
     * 订单跟踪
     * @param $type
     * @param $postid
     * @return string
     * @throws \Exception
     */
    public function orderTrack($type, $postid)
    {
        $res = $this->orderApiService->getExpress($type, $postid);

        return ($res['error'] == 0) ? $res['data'] : '';
    }

    /**
     * 获取地区名称
     * @param $regionId
     * @return mixed
     */
    public function getRegionName($regionId)
    {
        $regionName = Region::where('region_id', $regionId)
            ->pluck('region_name')
            ->toArray();
        if (empty($regionName)) {
            return '';
        }

        return $regionName[0];
    }

    /**
     * 获取储值卡使用金额及储值卡id
     * @param $order_id
     * @return $res
     */
    public function value_card_record($order_id)
    {
        $res = ValueCardRecord::select('use_val', 'vc_id')->where('order_id', $order_id)->first();
        $res = $res ? $res->toArray() : [];
        if ($res) {
            $res['use_val'] = $this->dscRepository->getPriceFormat($res['use_val']);
        }

        return $res;
    }

    /**获取发货单信息H5
     * @param string $order_sn
     * @return mixed
     */
    public function getTrackerOrderInfo($order_sn = '')
    {
        $deliver_order = DeliveryOrder::select('delivery_id', 'delivery_sn', 'invoice_no', 'shipping_id', 'shipping_name')->where('order_sn', $order_sn)->with(['getDeliveryGoods' => function ($query) {
            $query->select('delivery_id', 'goods_id');
            $query->with(['getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            }]);

        }, 'getShipping' => function ($query) {
            $query->select('shipping_id', 'shipping_code');
        }]);

        $deliver_order = app(BaseRepository::class)->getToArrayGet($deliver_order);

        if ($deliver_order) {
            foreach ($deliver_order as $key => $row) {
                $arr[$key]['invoice_no'] = $row['invoice_no'] ?? '';//订单物流单号
                $arr[$key]['shipping_name'] = $row['shipping_name'] ?? '';//快递名称
                $arr[$key]['shipping_code'] = '';
                $shop_code = $row['get_shipping']['shipping_code'] ?? '';
                if ($shop_code) {
                    $shippingObject = app(CommonRepository::class)->shippingInstance($shop_code);
                    if (!is_null($shippingObject)) {
                        $arr[$key]['shipping_code'] = $shippingObject->get_code_name();
                    }
                }

                //订单商品图片
                $img = [];
                if ($row['get_delivery_goods']) {
                    foreach ($row['get_delivery_goods'] as $k => $del) {
                        $img[$k]['goods_img'] = get_image_path($del['get_goods']['goods_thumb'] ?? '');
                    }
                }
                $arr[$key]['img'] = $img;
            }
        }

        return $arr;
    }
}
