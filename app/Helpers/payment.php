<?php

use App\Models\BaitiaoLog;
use App\Models\BaitiaoPayLog;
use App\Models\MerchantsAccountLog;
use App\Models\OfflineStore;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\PayLog;
use App\Models\SellerAccountLog;
use App\Models\SellerApplyInfo;
use App\Models\SellerShopinfo;
use App\Models\SellerTemplateApply;
use App\Models\Stages;
use App\Models\StoreOrder;
use App\Models\TemplateMall;
use App\Models\UserAccount;
use App\Models\Users;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Erp\JigonManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;
use App\Services\Team\TeamService;
use App\Services\User\UserBaitiaoService;
use Illuminate\Support\Facades\DB;

/**
 * 取得同步通知返回信息地址
 * @param string $code
 * @return string
 */
function return_url($code = '')
{
    return url('/') . '/' . 'respond.php?code=' . $code;
}

/**
 * 取得异步通知返回信息地址
 *
 * @param string $code
 * @return string
 */
function notify_url($code = '')
{
    return route('notify') . '/' . $code;
}

/**
 *  取得某支付方式信息
 * @param string $field 支付方式代码/支付方式ID
 */
function get_payment($field, $type = 0)
{
    $PaymentRep = app(PaymentService::class);

    $where = [
        'enabled' => 1
    ];

    if ($type == 1) {
        $where['pay_id'] = $field;
    } else {
        $where['pay_code'] = $field;
    }

    $payment = $PaymentRep->getPaymentInfo($where);

    if ($payment) {
        $config_list = $payment['pay_config'] ? unserialize($payment['pay_config']) : [];

        if ($config_list) {
            foreach ($config_list as $config) {
                $payment[$config['name']] = $config['value'];
            }
        }
    }

    return $payment;
}

/**
 *  通过订单sn取得订单ID
 * @param string $order_sn 订单sn
 * @param blob $voucher 是否为会员充值
 */
function get_order_id_by_sn($order_sn, $voucher = 'false')
{
    if ($voucher == 'true') {
        if (is_numeric($order_sn)) {
            $log_id = PayLog::where('order_id', $order_sn)
                ->where('order_type', PAY_SURPLUS)
                ->value('log_id');

            return $log_id;
        } else {
            return 0;
        }
    } else {
        $order_id = 0;
        if (is_numeric($order_sn)) {
            $order_id = OrderInfo::where('order_sn', $order_sn)->value('order_id');
        }

        if (!empty($order_id)) {
            $log_id = PayLog::where('order_id', $order_id)->where('order_type', PAY_ORDER)->value('log_id');

            return $log_id;
        } else {
            return 0;
        }
    }
}

/**
 * 通过订单ID取得订单商品名称
 *
 * @param $order_id
 * @return mixed
 */
function get_goods_name_by_id($order_id)
{
    $res = OrderGoods::select('goods_name')
        ->where('order_id', $order_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);
    $goods_name = app(BaseRepository::class)->getKeyPluck($res, 'goods_name');
    $goods_name = app(BaseRepository::class)->getImplode($goods_name);

    return $goods_name;
}

/**
 * 检查支付的金额是否与订单相符
 *
 * @access  public
 * @param string $log_id 支付编号
 * @param float $money 支付接口返回的金额
 * @return  true
 */
function check_money($log_id, $money)
{
    if (is_numeric($log_id)) {
        $pay = PayLog::where('log_id', $log_id);
        $pay = app(BaseRepository::class)->getToArrayFirst($pay);

        $pay['order_id'] = isset($pay['order_id']) ? $pay['order_id'] : 0;
        $pay['order_amount'] = isset($pay['order_amount']) ? $pay['order_amount'] : 0;

        $order_id = app(BaseRepository::class)->getExplode($pay['order_id']);

        $order = OrderInfo::whereIn('order_id', $order_id);
        $order = app(BaseRepository::class)->getToArrayFirst($order);

        $order['order_amount'] = isset($order['order_amount']) ? $order['order_amount'] : 0;
        $order['surplus'] = isset($order['surplus']) ? $order['surplus'] : 0;

        if ($order['surplus'] > 0) {
            $amount = $order['order_amount'];
        } else {
            $amount = $pay['order_amount'];
        }
    } else {
        return false;
    }

    if ($money == $amount) {
        return true;
    } else {
        return false;
    }
}

/**
 * @param $log_id 支付编号
 * @param int $pay_status 状态
 * @param string $note 备注
 * @param string $order_sn
 * @param int $pay_money
 * @throws Exception
 */
function order_paid($log_id, $pay_status = PS_PAYED, $note = '', $order_sn = '', $pay_money = 0)
{
    $log_id = intval($log_id);

    $OrderLib = app(OrderService::class);

    $time = gmtime();

    /* 取得要修改的支付记录信息 */
    $pay_log = PayLog::where('log_id', $log_id);
    $pay_log = app(BaseRepository::class)->getToArrayFirst($pay_log);

    $pay_order = [];
    if (!empty($order_sn) && $pay_log['order_type'] == PAY_ORDER) {
        $pay_order = OrderInfo::where('order_id', $pay_log['order_id']);
        $pay_order = $pay_order->with([
            'getBaitiaoLog' => function ($query) {
                $query->select('order_id', 'is_stages');
            }
        ]);
        $pay_order = app(BaseRepository::class)->getToArrayFirst($pay_order);

        if ($pay_order && $pay_order['get_baitiao_log']) {
            if ($pay_order['get_baitiao_log']['is_stages']) {
                $pay_order['is_stages'] = $pay_order['get_baitiao_log']['is_stages'];
            } else {
                $pay_order['is_stages'] = 0;
            }
        }
    }

    if (!empty($order_sn) && $pay_order && $pay_order['is_stages'] == 1) {
        /**
         * 白条订单
         */
        $where_other = [
            'id' => $log_id
        ];
        $log_info = app(UserBaitiaoService::class)->getBaitiaoPayLogInfo($where_other);

        $other = [
            'is_pay' => 1,
            'pay_time' => $time
        ];
        BaitiaoPayLog::where('id', $log_id)->update($other);

        Stages::where('order_sn', $order_sn)->increment('yes_num', 1, ['repay_date' => $time]);

        BaitiaoLog::where('log_id', $log_info['log_id'])->increment('yes_num', 1, ['repayed_date' => $time]);

        $baitiao_log_info = app(UserBaitiaoService::class)->getBaitiaoLogInfo(['log_id' => $log_info['log_id']]);
        if ($baitiao_log_info && $baitiao_log_info['stages_total'] == $baitiao_log_info['yes_num'] && $baitiao_log_info['is_repay'] == 0) {
            //已还清,更新白条状态为已还清;
            BaitiaoLog::where('log_id', $log_info['log_id'])->update(['is_repay' => 1]);
        }
    } else {

        /**
         * 普通订单
         */

        /* 取得支付编号 */
        if ($pay_log) {

            $config = app(DscRepository::class)->dscConfig();

            if ($pay_log && $pay_log['is_paid'] == 0) {
                //检查支付金额是否正确
                if ($pay_money && $pay_log['order_amount'] != $pay_money) {
                    $response['status'] = 'error';
                    $response['message'] = lang('payment.pay_money_error');
                    return $response;
                }

                /* 修改此次支付操作的状态为已付款 */
                PayLog::where('log_id', $log_id)->update(['is_paid' => 1]);

                /* 根据记录类型做相应处理 */
                if ($pay_log['order_type'] == PAY_ORDER) {
                    $order_id_arr = explode(',', $pay_log['order_id']);
                    foreach ($order_id_arr as $o_key => $o_val) {
                        /* 取得未支付，未取消，未退款订单信息 */
                        $order = $OrderLib->getUnPayedOrderInfo($o_val);

                        if (!empty($order)) {
                            $order_id = $order['order_id'] ?? 0;
                            $order_sn = $order['order_sn'] ?? '';

                            $pay_fee = order_pay_fee($order['pay_id'], $pay_log['order_amount']);

                            if (isset($order['is_zc_order']) && $order['is_zc_order'] == 1) {
                                /* 众筹状态的更改 */
                                update_zc_project($order_id);
                            }

                            //预售首先支付定金--无需分单
                            if ($order['extension_code'] == 'presale') {
                                $money_paid = $order['money_paid'] + $order['order_amount'];

                                if ($order['pay_status'] == 0) {
                                    /* 修改订单状态为已部分付款 */
                                    $order_amount = $order['goods_amount'] + $order['shipping_fee'] + $order['insure_fee'] + $order['pay_fee'] + $order['tax'] - $order['money_paid'] - $order['order_amount'];

                                    $other = [
                                        'order_status' => OS_CONFIRMED,
                                        'confirm_time' => $time,
                                        'pay_status' => PS_PAYED_PART,
                                        'pay_time' => $time,
                                        'pay_fee' => $pay_fee,
                                        'money_paid' => $money_paid,
                                        'order_amount' => $order_amount
                                    ];
                                    OrderInfo::where('order_id', $order_id)->update($other);

                                    /* 记录订单操作记录 */
                                    order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, PS_PAYED_PART, $note, lang('payment.buyer'));
                                    //更新pay_log
                                    update_pay_log($order_id);
                                } else {
                                    $other = [
                                        'pay_status' => PS_PAYED,
                                        'pay_time' => $time,
                                        'pay_fee' => $pay_fee,
                                        'money_paid' => $money_paid,
                                        'order_amount' => 0
                                    ];

                                    if ($order['main_count'] > 0) {
                                        $other['main_pay'] = 2;
                                    }

                                    OrderInfo::where('order_id', $order_id)->update($other);

                                    /* 记录订单操作记录 */
                                    order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, PS_PAYED, $note, lang('payment.buyer'));

                                    //付款成功后增加预售人数
                                    get_presale_num($order_id);
                                }
                            } else {
                                //判断订单状态
                                if (in_array($order['order_status'], [OS_CANCELED, OS_INVALID, OS_RETURNED])) {
                                    $response['status'] = 'error';
                                    $response['message'] = lang('order.order_status_not_support');
                                    return $response;
                                }

                                //判断付款状态
                                if ($order['pay_status'] == PS_PAYED) {
                                    $response['status'] = 'error';
                                    $response['message'] = lang('payment.pay_repeat');
                                    return $response;
                                }

                                /* 修改普通订单状态为已付款 */
                                $other = [
                                    'order_status' => OS_CONFIRMED,
                                    'confirm_time' => $time,
                                    'pay_status' => $pay_status,
                                    'pay_fee' => $pay_fee,
                                    'pay_time' => $time,
                                    'money_paid' => DB::raw("money_paid + order_amount"),
                                    'order_amount' => 0
                                ];

                                if ($order['main_count'] > 0) {
                                    $other['main_pay'] = 2;
                                }

                                OrderInfo::where('order_id', $order_id)->update($other);

                                //付款成功创建快照
                                create_snapshot($order_id);

                                /* 如果使用库存，且付款时减库存，且订单金额为0，则减少库存 */
                                if (isset($config['use_storage']) && $config['use_storage'] == '1' && $config['stock_dec_time'] == SDT_PAID) {
                                    load_helper('order');
                                    $store_id = StoreOrder::where('order_id', $order_id)->value('store_id');
                                    change_order_goods_storage($order_id, true, SDT_PAID, 15, 0, $store_id);
                                }

                                //检查/改变主订单状态
                                check_main_order_status($order_id);

                                /* 记录订单操作记录 */
                                order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, lang('payment.buyer'));

                                $order_sale = [
                                    'order_id' => $order_id,
                                    'pay_status' => $pay_status,
                                    'shipping_status' => $order['shipping_status']
                                ];

                                get_goods_sale($order_id, $order_sale);
                            }

                            /* 修改普通子订单状态为已付款 */
                            if ($order['main_count'] > 0) {
                                $order_res = OrderInfo::where('main_order_id', $order_id);
                                $order_res = app(BaseRepository::class)->getToArrayGet($order_res);

                                if ($order_res) {
                                    foreach ($order_res as $row) {
                                        $child_pay_fee = order_pay_fee($row['pay_id'], $row['order_amount']);

                                        $other = [
                                            'order_status' => OS_CONFIRMED,
                                            'confirm_time' => $time,
                                            'pay_status' => $pay_status,
                                            'pay_time' => $time,
                                            'pay_fee' => $child_pay_fee,
                                            'money_paid' => DB::raw("order_amount"),
                                            'order_amount' => 0
                                        ];
                                        OrderInfo::where('order_id', $row['order_id'])->update($other);

                                        if ($pay_status == PS_PAYED) {
                                            app(JigonManageService::class)->jigonConfirmOrder($row['order_id']); // 贡云确认订单
                                        }

                                        /* 记录订单操作记录 */
                                        order_action($row['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, lang('payment.buyer'));
                                    }
                                }
                            } else {
                                app(JigonManageService::class)->jigonConfirmOrder($order_id); // 贡云确认订单
                            }

                            /* 如果需要，发短信 */
                            $order_goods = OrderGoods::where('order_id', $order_id);
                            $order_goods = app(BaseRepository::class)->getToArrayFirst($order_goods);

                            $ru_id = $order_goods ? $order_goods['ru_id'] : 0;
                            $stages_qishu = $order_goods ? $order_goods['stages_qishu'] : -1;

                            if ($ru_id == 0) {
                                $sms_shop_mobile = $config['sms_shop_mobile'] ?? '';
                            } else {
                                $sms_shop_mobile = SellerShopinfo::where('ru_id', $ru_id)->value('mobile');
                            }

                            if (isset($config['sms_order_payed']) && $config['sms_order_payed'] == '1' && $sms_shop_mobile != '') {
                                $shop_name = app(MerchantCommonService::class)->getShopName($ru_id, 1);
                                $order_region = $OrderLib->getOrderUserRegion($order_id);
                                //阿里大鱼短信接口参数
                                $smsParams = [
                                    'shop_name' => $shop_name,
                                    'shopname' => $shop_name,
                                    'order_sn' => $order_sn,
                                    'ordersn' => $order_sn,
                                    'consignee' => $order['consignee'],
                                    'order_region' => $order_region,
                                    'orderregion' => $order_region,
                                    'address' => $order['address'],
                                    'order_mobile' => $order['mobile'],
                                    'ordermobile' => $order['mobile'],
                                    'mobile_phone' => $sms_shop_mobile,
                                    'mobilephone' => $sms_shop_mobile
                                ];

                                app(CommonRepository::class)->smsSend($sms_shop_mobile, $smsParams, 'sms_order_payed', false);
                            }

                            //门店处理
                            $stores_order = StoreOrder::where('order_id', $order_id);
                            $stores_order = app(BaseRepository::class)->getToArrayFirst($stores_order);

                            if ($stores_order && $stores_order['store_id'] > 0) {
                                if ($order['mobile']) {
                                    $user_mobile_phone = $order['mobile'];
                                } else {
                                    $user_info = Users::select('mobile_phone', 'user_name')->where('user_id', $order['user_id']);
                                    $user_info = app(BaseRepository::class)->getToArrayFirst($user_info);
                                    $user_mobile_phone = $user_info['mobile_phone'] ?? '';
                                }

                                if (!empty($user_mobile_phone)) {
                                    $pick_code = substr($order['order_sn'], -3) . rand(0, 9) . rand(0, 9) . rand(0, 9);

                                    StoreOrder::where('id', $stores_order['id'])->update(['pick_code' => $pick_code]);

                                    //门店短信处理
                                    $stores_info = OfflineStore::where('id', $stores_order['store_id']);
                                    $stores_info = app(BaseRepository::class)->getToArrayFirst($stores_info);

                                    $store_address = get_area_region_info($stores_info) . $stores_info['stores_address'];
                                    $user_name = $user_info['user_name'] ?? '';

                                    //门店订单->短信接口参数
                                    $store_smsParams = [
                                        'user_name' => $user_name,
                                        'username' => $user_name,
                                        'order_sn' => $order_sn,
                                        'ordersn' => $order_sn,
                                        'code' => $pick_code,
                                        'store_address' => $store_address,
                                        'storeaddress' => $store_address,
                                        'mobile_phone' => $user_mobile_phone,
                                        'mobilephone' => $user_mobile_phone
                                    ];

                                    app(CommonRepository::class)->smsSend($user_mobile_phone, $store_smsParams, 'store_order_code', false);
                                }
                            }

                            /* 将白条订单商品更新为普通订单商品 */
                            if ($stages_qishu > 0) {
                                OrderGoods::where('order_id', $order_id)->update(['stages_qishu' => '-1']);
                            }

                            /* 如果安装微信通,订单支付成功消息提醒 */
                            if (file_exists(MOBILE_WECHAT)) {
                                $pushData = [
                                    'first' => ['value' => lang('wechat.order_pay_first')], // 标题
                                    'keyword1' => ['value' => $order_sn, 'color' => '#173177'], // 订单号
                                    'keyword2' => ['value' => lang('admin/common.paid'), 'color' => '#173177'], // 付款状态
                                    'keyword3' => ['value' => date('Y-m-d', $time), 'color' => '#173177'], // 付款时间
                                    'keyword4' => ['value' => $config['shop_name'], 'color' => '#173177'], // 商户
                                    'keyword5' => ['value' => number_format($pay_log['order_amount'], 2, '.', ''), 'color' => '#173177'], // 支付金额
                                ];
                                $url = dsc_url('/#/user/orderDetail/' . $order_id);

                                app(\App\Services\Wechat\WechatService::class)->push_template('OPENTM204987032', $pushData, $url, $order['user_id']);
                            }

                            if (file_exists(MOBILE_TEAM)) {
                                // 在线支付更新拼团活动状态
                                if (isset($order['team_id']) && $order['team_id'] > 0) {
                                    app(TeamService::class)->updateTeamInfo($order['team_id'], $order['team_parent_id'], $order['user_id']);
                                }
                            }

                            // 开通购买会员权益卡 订单支付成功 更新成为分销商
                            if (file_exists(MOBILE_DRP)) {
                                app(\App\Services\Drp\DrpService::class)->buyOrderUpdateDrpShop($order['user_id'], $order['order_id'], $pay_log);
                            }

                            /* 取得未发货虚拟商品 */
                            $virtual_goods = get_virtual_goods($order_id);

                            if (!empty($virtual_goods)) {
                                /* 虚拟卡发货 */
                                if (virtual_goods_ship($virtual_goods, $msg, $order['order_sn'], true)) {
                                    if ($order['shipping_id'] == -1 || empty($order['shipping_id'])) {

                                        /* 如果没有实体商品，修改发货状态，送积分和红包 */
                                        $count = OrderGoods::where('order_id', $order_id)->where('is_real', 1)->count();

                                        if ($count <= 0) {
                                            /* 修改订单状态 */
                                            update_order($order_id, ['shipping_status' => SS_SHIPPED, 'shipping_time' => $time]);

                                            /* 记录订单操作记录 */
                                            order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $pay_status, $note, lang('payment.buyer'));

                                            /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                                            if ($order['user_id'] > 0) {

                                                /* 计算并发放积分 */
                                                $integral = integral_to_give($order);
                                                log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf(lang('payment.order_gift_integral'), $order['order_sn']));

                                                /* 发放红包 */
                                                send_order_bonus($order_id);

                                                /* 发放优惠券 bylu */
                                                send_order_coupons($order_id);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } elseif ($pay_log['order_type'] == PAY_SURPLUS) {

                    /**
                     * 取得添加预付款的用户以及金额
                     * 超过一天的未付款完成的 不处理
                     */
                    $oneday = $time - 24 * 60 * 60;
                    $user_account = UserAccount::where('id', $pay_log['order_id'])->where('add_time', '>' , $oneday);
                    $user_account = app(BaseRepository::class)->getToArrayFirst($user_account);

                    if ($user_account && $user_account['is_paid'] == 0) {

                        /* 更新会员预付款的到款状态 */
                        UserAccount::where('id', $pay_log['order_id'])->update(['paid_time' => $time, 'is_paid' => 1]);

                        /* 修改会员帐户金额 */
                        log_account_change($user_account['user_id'], $user_account['amount'], 0, 0, 0, lang('payment.surplus_type_0'), ACT_SAVING);

                        $user_info = Users::where('user_id', $user_account['user_id']);
                        $user_info = app(BaseRepository::class)->getToArrayFirst($user_info);

                        //短信接口参数
                        $smsParams = [
                            'user_name' => $user_info['user_name'],
                            'username' => $user_info['user_name'],
                            'user_money' => $user_info['user_money'],
                            'usermoney' => $user_info['user_money'],
                            'op_time' => local_date('Y-m-d H:i:s', $time),
                            'optime' => local_date('Y-m-d H:i:s', $time),
                            'add_time' => local_date('Y-m-d H:i:s', $time),
                            'addtime' => local_date('Y-m-d H:i:s', $time),
                            'examine' => lang('common.through'),
                            'process_type' => lang('common.surplus_type_0'),
                            'processtype' => lang('common.surplus_type_0'),
                            'fmt_amount' => $user_account['amount'],
                            'fmtamount' => $user_account['amount'],
                            'mobile_phone' => $user_info['mobile_phone'] ? $user_info['mobile_phone'] : '',
                            'mobilephone' => $user_info['mobile_phone'] ? $user_info['mobile_phone'] : ''
                        ];

                        //添加条件 by wu
                        if (isset($config['user_account_code']) && $config['user_account_code'] == '1' && $user_info['mobile_phone'] != '') {
                            app(CommonRepository::class)->smsSend($user_info['mobile_phone'], $smsParams, 'user_account_code', false);
                        }
                    }
                } elseif ($pay_log['order_type'] == PAY_APPLYTEMP) {
                    load_helper('visual');

                    //获取订单信息
                    $seller_template_apply = SellerTemplateApply::where('apply_id', $pay_log['order_id']);
                    $seller_template_apply = app(BaseRepository::class)->getToArrayFirst($seller_template_apply);

                    //导入已付款的模板
                    $new_suffix = get_new_dir_name($seller_template_apply['ru_id']); //获取新的模板
                    Import_temp($seller_template_apply['temp_code'], $new_suffix, $seller_template_apply['ru_id']);

                    //更新模板使用数量
                    TemplateMall::where('temp_id', $seller_template_apply['temp_id'])->increment('sales_volume', 1);

                    /* 修改申请的支付状态 */
                    $other = [
                        'pay_status' => 1,
                        'pay_time' => $time,
                        'apply_status' => 1
                    ];
                    SellerTemplateApply::where('apply_id', $pay_log['order_id'])->update($other);
                } elseif ($pay_log['order_type'] == PAY_APPLYGRADE) {

                    /* 修改申请的支付状态 */
                    $other = [
                        'is_paid' => 1,
                        'pay_time' => $time,
                        'pay_status' => 1
                    ];
                    SellerApplyInfo::where('apply_id', $pay_log['order_id'])->update($other);
                } elseif ($pay_log['order_type'] == PAY_TOPUP) {
                    $account_log = SellerAccountLog::where('log_id', $pay_log['order_id']);
                    $account_log = app(BaseRepository::class)->getToArrayFirst($account_log);

                    /* 修改商家充值的支付状态 */
                    SellerAccountLog::where('log_id', $pay_log['order_id'])->update(['is_paid' => 1]);

                    /* 改变商家金额 */
                    SellerShopinfo::where('ru_id', $account_log['ru_id'])->increment('seller_money', $pay_log['order_amount']);

                    $log = [
                        'user_id' => $account_log['ru_id'],
                        'user_money' => $pay_log['order_amount'],
                        'change_time' => $time,
                        'change_desc' => lang('order.merchant_handle_recharge'),
                        'change_type' => 2
                    ];
                    MerchantsAccountLog::insert($log);
                } elseif ($pay_log['order_type'] == PAY_WHOLESALE) {
                    $order_id = $pay_log['order_id'];

                    /* 修改申请的支付状态 */
                    $other = [
                        'pay_status' => $pay_status,
                        'pay_time' => $time
                    ];
                    WholesaleOrderInfo::where('order_id', $order_id)->update($other);

                    /* 修改此次支付操作的状态为已付款 */
                    PayLog::where('order_id', $order_id)->where('order_type', PAY_WHOLESALE)->update(['is_paid' => 1]);

                    //修改子订单状态为已付款
                    $child_num = WholesaleOrderInfo::where('main_order_id', $order_id)->count();

                    if ($child_num > 0 && $order_id > 0) {
                        $order_res = WholesaleOrderInfo::where('main_order_id', $order_id);
                        $order_res = app(BaseRepository::class)->getToArrayGet($order_res);

                        if ($order_res) {
                            foreach ($order_res as $row) {

                                /* 修改此次支付操作子订单的状态为已付款 */
                                PayLog::where('order_id', $row['order_id'])->where('order_type', PAY_WHOLESALE)->update(['is_paid' => 1]);

                                $child_pay_fee = order_pay_fee($row['pay_id'], $row['order_amount']); //获取支付费用

                                //修改子订单支付状态
                                $other = [
                                    'pay_status' => $pay_status,
                                    'pay_time' => $time,
                                    'pay_fee' => $child_pay_fee
                                ];
                                WholesaleOrderInfo::where('order_id', $row['order_id'])->update($other);
                            }
                        }
                    }
                    /* 如果使用库存，且付款时减库存，则减少库存 */
                    if (isset($config['use_storage']) && $config['use_storage'] == '1' && $config['stock_dec_time'] == SDT_PAID) {
                        suppliers_change_order_goods_storage($order_id, true, SDT_PAID);
                    }
                } elseif ($pay_log['order_type'] == PAY_REGISTERED) {
                    // 购买指定金额成为分销商
                    if (file_exists(MOBILE_DRP)) {
                        app(\App\Services\Drp\DrpService::class)->buyUpdateDrpShop($pay_log);
                    }
                }
            } else {
                $order_id = $pay_log['order_id'];

                $order = $OrderLib->getUnPayedOrderInfo($order_id);

                /* 取得未发货虚拟商品 */
                $virtual_goods = get_virtual_goods($order_id);

                if (!empty($virtual_goods)) {
                    /* 虚拟卡发货 */
                    if (virtual_goods_ship($virtual_goods, $msg, $order['order_sn'], true)) {
                        if ($order['shipping_id'] == -1 || empty($order['shipping_id'])) {

                            /* 如果没有实体商品，修改发货状态，送积分和红包 */
                            $count = OrderGoods::where('order_id', $order_id)->where('is_real', 1)->count();

                            if ($count <= 0) {
                                /* 修改订单状态 */
                                update_order($order_id, ['shipping_status' => SS_SHIPPED, 'shipping_time' => $time]);

                                /* 记录订单操作记录 */
                                order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $order['pay_status'], $note, lang('payment.surplus_type_0'));

                                /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                                if ($order['user_id'] > 0) {

                                    /* 计算并发放积分 */
                                    $integral = integral_to_give($order);
                                    log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf(lang('payment.order_gift_integral'), $order['order_sn']));

                                    /* 发放红包 */
                                    send_order_bonus($order_id);

                                    /* 发放优惠券 bylu */
                                    send_order_coupons($order_id);
                                }
                            }
                        }
                    }
                }

                $is_number = order_virtual_card_count($order_id);
                $pay_success = lang('payment.pay_success');
                $virtual_goods_ship_fail = lang('payment.virtual_goods_ship_fail');
                if ($is_number == 1) {
                    $pay_success .= '<br />' . $virtual_goods_ship_fail;
                }
            }
        }
    }

    /* 执行更新会员订单信息 */
    $user_id = $order['user_id'] ?? 0;
    if (empty($user_id)) {
        $user_id = session()->exists('user_id') ? session('user_id', 0) : 0;
    }

    if ($user_id > 0) {
        app(OrderCommonService::class)->getUserOrderNumServer($user_id);
    }
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
function order_pay_fee($payment_id, $order_amount, $cod_fee = null)
{
    $PaymentRep = app(PaymentService::class);

    $where = [
        'pay_id' => $payment_id
    ];
    $payment = $PaymentRep->getPaymentInfo($where);

    $rate = ($payment['is_cod'] && !is_null($cod_fee)) ? $cod_fee : $payment['pay_fee'];

    if (strpos($rate, '%') !== false) {
        /* 支付费用是一个比例 */
        $val = floatval($rate) / 100;
        $pay_fee = $val > 0 ? $order_amount * $val / (1 - $val) : 0;
    } else {
        $pay_fee = floatval($rate);
    }

    return round($pay_fee, 2);
}
