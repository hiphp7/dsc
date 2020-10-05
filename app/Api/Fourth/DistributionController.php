<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Api\Fourth\Transformer\DistributionTransformer;
use App\Custom\Distribute\Repositories\OrderRepository;
use App\Custom\Distribute\Services\DistributeService;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Drp\DrpService;
use App\Services\Payment\PaymentService;
use App\Services\User\AccountService;
use App\Services\UserRights\RightsCardService;
use App\Services\UserRights\UserRightsService;
use App\Services\Wechat\WechatService;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class DistributionController
 * @package App\Api\Fourth\Controllers
 */
class DistributionController extends Controller
{
    protected $config;
    protected $drpService;
    protected $timeRepository;
    protected $distributionTransformer;
    protected $paymentService;
    protected $wechatService;
    protected $accountService;
    protected $dscRepository;

    public function __construct(
        DrpService $drpService,
        TimeRepository $timeRepository,
        DistributionTransformer $distributionTransformer,
        PaymentService $paymentService,
        WechatService $wechatService,
        AccountService $accountService,
        DscRepository $dscRepository
    )
    {
        //加载外部类
        $files = [
            'common',
            'clips'
        ];
        load_helper($files);

        $this->drpService = $drpService;
        $this->timeRepository = $timeRepository;
        $this->distributionTransformer = $distributionTransformer;
        $this->paymentService = $paymentService;
        $this->wechatService = $wechatService;
        $this->accountService = $accountService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 申请注册分销商页
     * @param Request $request
     * @param RightsCardService $rightsCardService
     * @param CommonRepository $commonRepository
     * @return JsonResponse
     */
    public function application(Request $request, RightsCardService $rightsCardService, CommonRepository $commonRepository)
    {
        $shop_id = $request->input('shop_id', 0); // 分享页面 分销商id

        $parent_id = $request->input('parent_id', 0);

        //返回用户ID
        $user_id = $this->authorization();

        if ($shop_id == 0) {
            //分销商信息
            $shop_info = $this->drpService->shopInfo($user_id, 0);
            if (!empty($shop_info)) {
                // 权益卡已过期
                if (empty($shop_info['membership_status'])) {
                } else {
                    $result['shop_info'] = $shop_info;
                    return $this->succeed($result);
                }
            }
        }

        $drp_config = $this->drpService->drpConfig();

        $result = [];
        if (!empty($drp_config)) {
            $notice = isset($drp_config['notice']['value']) && !empty($drp_config['notice']['value']) ? html_out($drp_config['notice']['value']) : '';
            $result['notice'] = nl2br($notice);

            $novice = isset($drp_config['novice']['value']) && !empty($drp_config['novice']['value']) ? html_out($drp_config['novice']['value']) : '';
            $result['novice'] = nl2br($novice);
        }

        // 分销会员权益卡展示入口： 领取条件满足任意一个即显示,同类型的取最小值 (付费购买、指定商品、消费金额累积、积分兑换)
        $type = 2;
        $card_receive_value = $rightsCardService->cardReceiveValue($type);

        $result['apply_channel'] = [];
        if (!empty($card_receive_value)) {
            foreach ($card_receive_value as $value) {

                $content = [];
                $sort_order = 0;
                $receive_type = $value['type'];
                $value = $value['value'] ?? '';

                // 分享页用户仅显示: goods 购买商品、 buy付费购买
                if (($shop_id > 0 || !$user_id) && $receive_type != 'goods' && $receive_type != 'buy') {
                    continue;
                }

                $val = 0; // 兼容原字段 用于前端
                if ($receive_type == 'integral') {
                    $sort_order = 1; // 消费积分兑换

                    $content['buy_pay_point'] = $value;

                    $val = 3;

                } elseif ($receive_type == 'order') {
                    $sort_order = 2; // 消费金额累积

                    if (!empty($value)) {
                        $content['buy_money'] = $this->dscRepository->getPriceFormat($value, true);
                    }

                    $val = 2;

                } elseif ($receive_type == 'buy') {
                    $sort_order = 3; // 付费购买

                    if (!empty($value)) {
                        $content['price'] = $this->dscRepository->getPriceFormat($value, true);
                    }

                    $val = 4;

                } elseif ($receive_type == 'goods') {
                    $sort_order = 4; // 购买指定商品

                    $val = 1;
                }

                $result['apply_channel'][] = [
                    'value' => $val,
                    'receive_type' => $receive_type,
                    'name' => lang('admin/drpcard.receive_type_' . $receive_type),
                    'content' => $content,
                    'sort_order' => $sort_order
                ];
            }
        }

        $result['apply_channel'] = collect($result['apply_channel'])->sortBy('sort_order')->values()->all();

        // 记录推荐人id
        if ($parent_id > 0) {
            $commonRepository->setDrpShopAffiliate($parent_id);

            //如有上级推荐人（分销商），且关系在有效期内，更新推荐时间 1.4.3
            $this->drpService->updateBindTime($user_id, $parent_id);
        }

        return $this->succeed($result);
    }

    /**
     * 符合类型的会员权益卡
     * @param Request $request
     * @param RightsCardService $rightsCardService
     * @return JsonResponse
     */
    public function drpcard(Request $request, RightsCardService $rightsCardService)
    {
        $this->validate($request, [
            'receive_type' => 'required|string'
        ]);

        //返回用户ID
        $user_id = $this->authorization();

        $type = 2;
        $receive_type = $request->input('receive_type', ''); // 会员权益卡 领取类型

        $result['list'] = $rightsCardService->cardList($type, $receive_type, $user_id);

        if ($receive_type == 'integral' || $receive_type == 'buy') {
            $drp_config = $this->drpService->drpConfig();

            // 文章
            $article_id = $drp_config['agreement_id']['value'] ?? 0;
            $result['agreement_article_title'] = $rightsCardService->getArticleTitle($article_id);
            $result['agreement_id'] = $article_id;

            $novice = $drp_config['novice']['value'] ?? '';
            $result['novice'] = nl2br(html_out($novice));
        }

        return $this->succeed($result);
    }

    /**
     * 会员权益卡信息
     * @param Request $request
     * @param RightsCardService $rightsCardService
     * @return JsonResponse
     */
    public function rightscard(Request $request, RightsCardService $rightsCardService)
    {
        $this->validate($request, [
            'membership_card_id' => 'required|integer'
        ]);

        $membership_card_id = $request->input('membership_card_id', 0); // 会员权益卡id

        $result['info'] = $rightsCardService->cardInfo($membership_card_id);

        return $this->succeed($result);
    }

    /**
     * 会员权益卡权益列表信息
     * @param Request $request
     * @param RightsCardService $rightsCardService
     * @return JsonResponse
     */
    public function rightscardlist(Request $request, RightsCardService $rightsCardService)
    {
        $this->validate($request, [
            'membership_card_id' => 'required|integer'
        ]);

        //返回用户ID
        $user_id = $this->authorization();

        $membership_card_id = $request->input('membership_card_id', 0); // 会员权益卡id

        $result['list'] = $rightsCardService->cardRightsList($membership_card_id, $user_id);

        return $this->succeed($result);
    }

    /**
     * 申请分销商提交
     * @param Request $request
     * @param RightsCardService $rightsCardService
     * @param DistributeService $distributeService
     * @param CommonRepository $commonRepository
     * @return JsonResponse
     */
    public function apply(Request $request, RightsCardService $rightsCardService, DistributeService $distributeService, CommonRepository $commonRepository)
    {
        $this->validate($request, [
            'receive_type' => 'required|string',
            'membership_card_id' => 'required|integer'
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = [];

        // 权益卡领取类型 goods,购买商品 order 消费金额 integral,消费积分 buy 指定金额购买
        $receive_type = $request->input('receive_type', '');
        $membership_card_id = $request->input('membership_card_id', 0);

        // 会员权益卡信息
        $cardInfo = $rightsCardService->cardInfoById($membership_card_id, $receive_type);
        if (empty($cardInfo)) {
            return $this->succeed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
        }

        // 分销配置
        $drp_config = $this->drpService->drpConfig();
        // 分销商自动开店
        $status_check = $drp_config['register']['value'] ?? 0;
        // 分销商审核
        $ischeck = $drp_config['ischeck']['value'] ?? 0;

        $now = $this->timeRepository->getGmTime();

        // 判断是否已经开店 或 是否审核
        $shop_info = $this->drpService->shopInfo($user_id, 0);
        if (empty($shop_info)) {
            // 首次申请
            $repeat = 0; //  是否重新申请: 0 首次申请 1 重新申请
        }

        if (!empty($shop_info)) {
            if (empty($shop_info['membership_status'])) {
                // 重新申请
                $repeat = 1;
            } else {
                // 分销商 未审核判断
                if ($ischeck == 1) {
                    if ($shop_info['audit'] == 0) {
                        $result['error'] = 2;
                        $result['audit'] = $shop_info['audit'];
                        $result['msg'] = lang('drp.drp_shop_audit');
                        return $this->succeed($result);
                    } elseif ($shop_info['audit'] == 2) {
                        $result['error'] = 2;
                        $result['audit'] = $shop_info['audit'];
                        $result['msg'] = lang('drp.drp_shop_audit_2');
                        return $this->succeed($result);
                    }
                }

                // 审核已通过
                if ($shop_info['audit'] == 1) {
                    $result['error'] = 0;
                    $result['audit'] = $shop_info['audit'];
                    $result['msg'] = lang('drp.drp_shop_audit_1');
                    return $this->succeed($result);
                }

                // 已注册
                $result['error'] = 1;
                $result['msg'] = lang('drp.drp_shop_is_register');
                return $this->succeed($result);
            }
        }


        // 成功提示信息
        $msg = lang('drp.drp_shop_register_success');

        // 首次申请
        if (empty($repeat) && empty($shop_info)) {

            // 默认会员信息
            $user_info = $distributeService->userInfo($user_id, ['user_name', 'mobile_phone']);

            $drp_shop = [];
            $drp_shop['user_id'] = $user_id;
            $drp_shop['shop_name'] = $user_info['user_name'] ?? '';
            $drp_shop['real_name'] = $user_info['user_name'] ?? '';
            $drp_shop['mobile'] = $user_info['mobile_phone'] ?? '';
            $drp_shop['create_time'] = $now; // 申请时间
            $drp_shop['type'] = 0;
            $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
            $drp_shop['membership_card_id'] = $membership_card_id;

            if ($ischeck == 1) {
                // 需要审核
                $drp_shop['audit'] = 0;

                $msg = lang('drp.drp_shop_apply_success');
            } else {
                // 不需要审核
                $drp_shop['audit'] = 1;
                // 开店时间
                $drp_shop['open_time'] = $now;
            }

            // 是否自动开店
            $drp_shop['status'] = $status_check == 1 ? 1 : 0;

        } else {

            // 重新申请成为分销商
            if ($shop_info['membership_status'] == 0) {

                $drp_shop = [];
                $drp_shop['id'] = $shop_info['id']; // 分销商id
                $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
                $drp_shop['membership_card_id'] = $membership_card_id;

                if ($ischeck == 1) {
                    // 需要审核
                    $drp_shop['audit'] = 0;

                    $msg = lang('drp.drp_shop_apply_success');
                } else {
                    // 不需要审核
                    $drp_shop['audit'] = 1;
                    // 开店时间
                    $drp_shop['open_time'] = $now;
                }

                // 是否自动开店
                $drp_shop['status'] = $status_check == 1 ? 1 : 0;

            } else {
                return $this->succeed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
            }
        }

        // 分成锁定模式
        $drp_affiliate_mode = $drp_config['drp_affiliate_mode']['value'] ?? 1;

        if ($drp_affiliate_mode == 1) {
            $parent_id = 0;
        } else {
            $parent_id = $commonRepository->getDrpShopAffiliate();
        }

        // 记录log初始值
        $amount = 0;
        $pay_point = 0;
        $log_id = 0;

        if (!empty($cardInfo)) {
            // 记录领取有效期
            $expiry_type = $cardInfo['expiry_type'] ?? '';
            $expiry_date = $cardInfo['expiry_date'] ?? '';
            if ($expiry_type == 'timespan') {
                // 时间间隔类型  记录结束时间戳 作为 过期时间
                $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
            } elseif ($expiry_type == 'days') {
                // 领取时间几天后过期
                $drp_shop['expiry_time'] = $now + intval($expiry_date) * 24 * 60 * 60;
            } else {
                // 永久有效
                $drp_shop['expiry_time'] = 0;
            }

            // 获取领取类型对应的值
            $receive_value_arr = $cardInfo['receive_value_arr'] ?? [];
            $receive_type_value = $receive_value_arr[$receive_type]['value'] ?? '';

            if ($receive_type == 'goods') {
                // 购买商品
                $is_buy_goods = $receive_type_value ?? '';

                if (empty($is_buy_goods)) {
                    return $this->succeed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
                }

                // 获取会员购买的商品是否满足条件
                $order_goods_count = $distributeService->orderGoodsCount($user_id, $is_buy_goods);
                if (empty($order_goods_count)) {
                    // 不满足条件返回,  提示去购买指定商品
                    return $this->succeed(['error' => 1, 'msg' => lang('drp.is_buy_goods_empty')]);
                }

            } elseif ($receive_type == 'order') {
                // 累积消费金额
                $buy = floatval($receive_type_value); // 获取设置金额
                if (empty($buy)) {
                    return $this->succeed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
                }

                // 获取订单金额
                $money = $this->drpService->orderMoney($user_id);
                if ($buy > $money) {
                    return $this->succeed(['error' => 1, 'msg' => sprintf(lang('drp.is_buy_not_satisfaction'), $buy)]);
                }

            } elseif ($receive_type == 'integral') {
                // 消费积分兑换成为分销商
                $pay_point = $request->input('pay_point', 0);
                $pay_point = intval($pay_point);

                if (empty($pay_point)) {
                    return $this->succeed(['error' => 1, 'msg' => lang('drp.user_pay_point_empty')]);
                }

                $receive_type_value = intval($receive_type_value); // 获取设置消费积分兑换值
                if ($receive_type_value > 0 && $pay_point != $receive_type_value) {
                    return $this->succeed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
                }

                $user_note = lang('drp.cash_pay_point_change_desc');
                $res = $this->drpService->cashPayPoint($user_id, $pay_point, $user_note);

                if ($res['error'] > 0) {
                    return $this->succeed($res);
                }

                $log_id = $res['log_id'] ?? 0;
            }

        }

        // 添加分销商
        $res = $this->drpService->updateDrpShop($drp_shop);
        if ($res) {

            // 微信模板消息 - 分销商申请成功通知
            $issend = $drp_config['issend']['value'] ?? 0;
            if ($issend == 1) {
                if (is_wechat_browser() && file_exists(MOBILE_WECHAT)) {
                    $pushData = [
                        'keyword1' => ['value' => $drp_shop['shop_name'], 'color' => '#173177'],
                        'keyword2' => ['value' => $drp_shop['mobile'], 'color' => '#173177'],
                        'keyword3' => ['value' => $drp_shop['create_time'], 'color' => '#173177']
                    ];
                    $url = url('mobile/#/drp');
                    $this->wechatService->push_template('OPENTM207126233', $pushData, $url, $drp_shop['user_id']);
                }
            }

            // 记录权益卡领取
            $account_log = [
                'amount' => $amount,
                'pay_points' => $pay_point,
                'user_note' => lang('admin/drpcard.receive_type_' . $receive_type),
                'receive_type' => $receive_type, // 权益卡领取类型
                'membership_card_id' => $membership_card_id,
                'log_id' => $log_id, // 日志id
                'parent_id' => $parent_id
            ];
            $distributeService->insert_drp_account_log($user_id, $account_log);

            return $this->succeed(['error' => 0, 'msg' => $msg]);
        }

        return $this->succeed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
    }

    /**
     * h5购买流程 收银台 显示订单信息
     * @param Request $request
     * @param RightsCardService $rightsCardService
     * @return JsonResponse
     */
    public function purchasepay(Request $request, RightsCardService $rightsCardService)
    {
        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $membership_card_id = $request->input('membership_card_id', 0);

        $buy_money = 0;
        if ($membership_card_id > 0) {
            $receive_type = 'buy';
            // 会员权益卡信息
            $cardInfo = $rightsCardService->cardInfoById($membership_card_id, $receive_type);
            // 获取领取类型对应的值
            $receive_value_arr = $cardInfo['receive_value_arr'] ?? [];
            $receive_type_value = $receive_value_arr[$receive_type]['value'] ?? '';

            $buy_money = floatval($receive_type_value);
        }

        $result = [];
        if ($buy_money > 0) {
            $order['order_amount'] = $buy_money;

            $result['order_amount_formated'] = $this->dscRepository->getPriceFormat($buy_money, true);

            // 支付方式列表
            $support_cod = 0;
            $cod_fee = 0;
            $is_online = 1;
            $result['paymentList'] = $this->paymentService->availablePaymentList($support_cod, $cod_fee, $is_online);
        }

        return $this->succeed($result);
    }

    /**
     * h5 + app 切换支付方式
     * @param Request $request
     * @param DistributeService $distributeService
     * @param OrderRepository $orderRepository
     * @param CommonRepository $commonRepository
     * @return array|JsonResponse
     * @throws ValidationException
     */
    public function changepayment(Request $request, RightsCardService $rightsCardService, DistributeService $distributeService, OrderRepository $orderRepository, CommonRepository $commonRepository)
    {
        //数据验证
        $this->validate($request, [
            'membership_card_id' => 'required|integer'
        ]);

        $pay_id = $request->input('pay_id', 0);
        $pay_code = $request->input('pay_code', ''); // app 切换支付方式 alipay、wxpay
        $membership_card_id = $request->input('membership_card_id', 0);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $buy_money = 0;
        if ($membership_card_id > 0) {
            $receive_type = 'buy';
            // 会员权益卡信息
            $cardInfo = $rightsCardService->cardInfoById($membership_card_id, $receive_type);
            // 获取领取类型对应的值
            $receive_value_arr = $cardInfo['receive_value_arr'] ?? [];
            $receive_type_value = $receive_value_arr[$receive_type]['value'] ?? '';

            $buy_money = floatval($receive_type_value);
        }

        $order = [];
        $order['order_sn'] = $user_id;
        $order['order_amount'] = $buy_money; //计算此次预付款需要支付的总金额
        $order['subject'] = lang('drp.account_buy');// 购买成为分销商描述

        $pay_online = '';
        if ($order['order_amount'] > 0) {

            if (!empty($pay_code)) {
                $payment_info = $this->paymentService->getPaymentInfo(['pay_code' => $pay_code, 'enabled' => 1]);
            } else {
                $payment_info = $this->paymentService->getPaymentInfo(['pay_id' => $pay_id, 'enabled' => 1]);
            }

            $payment = $this->paymentService->getPayment($payment_info['pay_code']);
            if ($payment === false) {
                return [];
            }
            /* 插入支付日志 */
            $order['log_id'] = $orderRepository->insert_pay_log($order['order_sn'], $order['order_amount'], PAY_REGISTERED, 0, $membership_card_id);

            $pay_online = $payment->get_code($order, unserialize_config($payment_info['pay_config']), $user_id);

            if (!empty($pay_online)) {
                // 分成锁定模式
                $drp_affiliate_mode = $this->drpService->drpConfig('drp_affiliate_mode');
                $drp_affiliate_mode = $drp_affiliate_mode['value'] ?? 1;

                if ($drp_affiliate_mode == 1) {
                    $parent_id = 0;
                } else {
                    $parent_id = $commonRepository->getDrpShopAffiliate();
                }

                // 记录分销商付费购买记录
                $account_log = [
                    'amount' => $order['order_amount'],
                    'pay_points' => 0,
                    'user_note' => $order['subject'],
                    'pay_id' => $payment_info['pay_id'] ?? 0,
                    'payment' => $payment_info['pay_code'] ?? '', // pay_code
                    'receive_type' => 'buy', // 付费购买成为分销商
                    'membership_card_id' => $membership_card_id,
                    'log_id' => $order['log_id'], // 支付日志id
                    'parent_id' => $parent_id
                ];
                $distributeService->insert_drp_account_log($user_id, $account_log);
            }
        }

        return $this->succeed($pay_online);
    }

    /**
     * wxapp 小程序购买流程 收银台
     * @param Request $request
     * @param RightsCardService $rightsCardService
     * @return JsonResponse
     */
    public function wxapppurchasepay(Request $request, RightsCardService $rightsCardService)
    {
        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $membership_card_id = $request->input('membership_card_id', 0);

        $buy_money = 0;
        if ($membership_card_id > 0) {
            $receive_type = 'buy';
            // 会员权益卡信息
            $cardInfo = $rightsCardService->cardInfoById($membership_card_id, $receive_type);
            // 获取领取类型对应的值
            $receive_value_arr = $cardInfo['receive_value_arr'] ?? [];
            $receive_type_value = $receive_value_arr[$receive_type]['value'] ?? '';

            $buy_money = floatval($receive_type_value);
        }

        $order = [];
        $order['order_sn'] = $user_id;
        $order['order_amount'] = $buy_money; //计算此次预付款需要支付的总金额
        $order['subject'] = lang('drp.account_buy');// 购买成为分销商描述

        $order['order_amount_formated'] = $this->dscRepository->getPriceFormat($order['order_amount'], true);

        return $this->succeed($order);
    }

    /**
     * wxapp 小程序微信支付 发起支付
     * @param Request $request
     * @param DistributeService $distributeService
     * @param OrderRepository $orderRepository
     * @param CommonRepository $commonRepository
     * @return array|JsonResponse
     * @throws ValidationException
     */
    public function wxappchangepayment(Request $request, RightsCardService $rightsCardService, DistributeService $distributeService, OrderRepository $orderRepository, CommonRepository $commonRepository)
    {
        //数据验证
        $this->validate($request, [
            'membership_card_id' => 'required|integer'
        ]);

        $pay_code = $request->input('pay_code', 'wxpay'); // 小程序微信支付
        $membership_card_id = $request->input('membership_card_id', 0);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $buy_money = 0;
        if ($membership_card_id > 0) {
            $receive_type = 'buy';
            // 会员权益卡信息
            $cardInfo = $rightsCardService->cardInfoById($membership_card_id, $receive_type);
            // 获取领取类型对应的值
            $receive_value_arr = $cardInfo['receive_value_arr'] ?? [];
            $receive_type_value = $receive_value_arr[$receive_type]['value'] ?? '';

            $buy_money = floatval($receive_type_value);
        }

        $order = [];
        $order['order_sn'] = $user_id;
        $order['order_amount'] = $buy_money; //计算此次预付款需要支付的总金额
        $order['subject'] = lang('drp.account_buy');// 购买成为分销商描述

        $pay_online = '';
        if ($order['order_amount'] > 0) {

            $payment_info = $this->paymentService->getPaymentInfo(['pay_code' => $pay_code, 'enabled' => 1]);
            $payment = $this->paymentService->getPayment($payment_info['pay_code']);
            if ($payment === false) {
                return [];
            }
            
            /* 插入支付日志 */
            $order['log_id'] = $orderRepository->insert_pay_log($order['order_sn'], $order['order_amount'], PAY_REGISTERED, 0, $membership_card_id);
            $pay_online = $payment->get_code($order, unserialize_config($payment_info['pay_config']), $user_id);

            if (!empty($pay_online)) {
                // 分成锁定模式
                $drp_affiliate_mode = $this->drpService->drpConfig('drp_affiliate_mode');
                $drp_affiliate_mode = $drp_affiliate_mode['value'] ?? 1;

                if ($drp_affiliate_mode == 1) {
                    $parent_id = 0;
                } else {
                    $parent_id = $commonRepository->getDrpShopAffiliate();
                }

                // 记录分销商付费购买记录
                $account_log = [
                    'amount' => $order['order_amount'],
                    'pay_points' => 0,
                    'user_note' => $order['subject'],
                    'pay_id' => $payment_info['pay_id'],
                    'payment' => $payment_info['pay_code'] ?? '', // pay_code
                    'receive_type' => 'buy', // 付费购买成为分销商
                    'membership_card_id' => $membership_card_id,
                    'log_id' => $order['log_id'], // 支付日志id
                    'parent_id' => $parent_id
                ];
                $distributeService->insert_drp_account_log($user_id, $account_log);
            }
        }

        return $this->succeed($pay_online);
    }

    /**
     * 查看分销商
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function shop(Request $request)
    {
        $this->validate($request, [
            'shop_id' => 'required|integer',
        ]);

        $result = [
            'error' => 0
        ];

        // 获取分销商信息
        $shop_info = $this->drpService->shopInfo(0, $request->get('shop_id'));
        $shop_info = $this->distributionTransformer->transform($shop_info);
        if ($shop_info) {
            $result['shop_info'] = $shop_info;
            if ($shop_info['audit'] != 1) {
                $result['error'] = 1;
                $result['msg'] = lang('drp.drp_shop_audit');
            }

        } else {
            $result['error'] = 1;
            $result['msg'] = lang('drp.drp_shop_not');
        }

        return $this->succeed($result);
    }

    /**
     * 店铺商品
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function shopGoods(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'status' => 'required|integer',   //1全部  2上新  3促销 4热销
            'model' => 'required|integer',     //0全部  1分类  2商品
        ]);

        $uid = $this->authorization(); // 当前会员id
        $user_id = $request->get('uid');   // 店铺会员id

        // 店铺商品
        $shop_goods = $this->drpService->showGoods($user_id, $request->get('page'), $request->get('size'), $request->get('status'), $request->get('model'), $uid);

        return $this->succeed($shop_goods);
    }

    /**
     * 分销中心
     * @param Request $request
     * @param RightsCardService $rightsCardService
     * @return JsonResponse
     */
    public function index(Request $request, RightsCardService $rightsCardService)
    {
        $this->validate($request, []);

        $result = [
            'error' => 0,
            'audit' => 0
        ];

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //店铺信息
        $shop_info = $this->drpService->shopInfo($user_id, 0);

        if (!isset($shop_info) || empty($shop_info)) {

            // 去注册页面  drp/application
            $result['error'] = 2;
            $result['msg'] = lang('drp.drp_shop_register');
            return $this->succeed($result);
        }

        $drp_config = $this->drpService->drpConfig();

        // 分销商审核
        $ischeck = $drp_config['ischeck']['value'] ?? 0;

        // 未审核判断
        if ($ischeck == 1) {
            if ($shop_info['audit'] != 1) {
                $result['error'] = 1;
                $result['audit'] = $shop_info['audit'];
                $result['msg'] = lang('drp.drp_shop_audit');
                return $this->succeed($result);
            }
        }

        $shop_info = $this->distributionTransformer->transform($shop_info);

        if ($shop_info) {

            $able_day = $drp_config['settlement_time']['value'] ?? 7; // 可分佣天数

            $result['surplus_amount'] = $shop_info['shop_money'];                      //可提现佣金
            $result['totals'] = $this->drpService->get_drp_money(0, $user_id);         //累计佣金
            $result['today_total'] = $this->drpService->get_drp_money(1, $user_id);    //今日收入
            $result['total_amount'] = $this->drpService->get_drp_money(2, $user_id);   //总销售额

            // 1.4.1 remove
            //$userrank = $this->drpService->drp_rank_info($user_id);                    //分销商等级
            //$result['user_rank'] = $userrank['credit_name'];

            // 1.4.1 add
            $result['membership_card_info'] = $rightsCardService->cardInfo($shop_info['membership_card_id']); // 会员权益卡信息
            $result['user_rank'] = $result['membership_card_info']['name'] ?? '';

            // 会员权益卡 过期时间判断
            if (!empty($result['membership_card_info'])) {
                $expiry_data = [
                    'expiry_date' => $result['membership_card_info']['expiry_date'] ?? '',
                    'expiry_type' => $result['membership_card_info']['expiry_type'] ?? '',
                ];
                $expiry = $rightsCardService->checkExpiryTime($expiry_data, $shop_info['expiry_time']);
                // 过期状态 0 未过期、1 已过期、 2 快过期
                if (isset($expiry['expiry_status']) && $expiry['expiry_status'] == 1) {
                    // 更新分销商权益卡状态 并还原会员等级
                    $data = [
                        'membership_status' => 0,
                        'audit' => 0, // 设置为未审核
                        //'status' => 0, // 关闭店铺
                    ];
                    $shop_info['membership_status'] = 0;
                    app(DistributeService::class)->editDrpShop($user_id, $data);
                    $rightsCardService->restoreUsersRank($user_id);
                }

                $result['expiry'] = $expiry;
            }

            $result['day'] = $able_day;
            $result['shop_info'] = $shop_info;                                         //分销商信息
            $result['team_count'] = $this->drpService->drpNextNum($user_id);             //团队数量
            $result['user_count'] = $this->drpService->userNextNum($user_id);            //下级会员数量
            $result['sum_count'] = intval($result['team_count'] + $result['user_count']);// 数量
        }

        $result['banner'] = $this->drpService->drpAds('drp', 'banner', 10); // 获取分销广告位

        return $this->succeed($result);
    }

    /**
     * 我的微店
     * @param Request $request
     * @return JsonResponse
     */
    public function my_shop(Request $request)
    {
        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = [
            'error' => 0,
            'audit' => 0
        ];

        //店铺信息
        $shop_info = $this->drpService->shopInfo($user_id, 0);

        if (!isset($shop_info) || empty($shop_info)) {

            // 去注册页面  drp/application
            $result['error'] = 2;
            $result['msg'] = lang('drp.drp_shop_register');
            return $this->succeed($result);
        }

        $drp_config = $this->drpService->drpConfig();

        // 分销商审核
        $ischeck = $drp_config['ischeck']['value'] ?? 0;

        // 未审核判断
        if ($ischeck == 1) {
            if ($shop_info['audit'] != 1) {
                $result['error'] = 1;
                $result['audit'] = $shop_info['audit'];
                $result['msg'] = lang('drp.drp_shop_audit');
                return $this->succeed($result);
            }
        }

        if ($shop_info['membership_status'] == 0) {
            // 权益卡已禁用
        } elseif ($shop_info['status'] == 0) {
            // 店铺已关闭
            $result['error'] = 3;
            $result['msg'] = lang('drp.drp_shop_status');
            return $this->succeed($result);
        }

        $result['shop_info'] = $shop_info;

        return $this->succeed($result);
    }

    /**
     * 店铺设置
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show(Request $request)
    {
        $this->validate($request, []);

        $result = [
            'error' => 0
        ];
        // 返回用户ID
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //分销商信息
        $shop_info = $this->drpService->shopInfo($user_id, 0);
        $shop_info = $this->distributionTransformer->transform($shop_info);

        if ($shop_info) {
            $result['shop_info'] = $shop_info;
        } else {
            $result['error'] = 1;
        }

        return $this->succeed($result);
    }

    /**
     * 设置头像
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function avatar(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $shop_info = [
            'id' => $request->get('id'),
            'shop_portrait' => $request->get('pic') ? $request->get('pic') : '',
            'user_id' => $user_id
        ];
        $info = $this->drpService->updateDrpShop($shop_info);
        if ($info > 0) {
            // 分销商信息
            $shop_info = $this->drpService->shopInfo($user_id, 0);
            $result['shop_info'] = $this->distributionTransformer->transform($shop_info);
            $result['error'] = 0;
            $result['msg'] = lang('drp.update_avatar_success');
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('drp.update_avatar_fail');
        }

        return $this->succeed($result);
    }

    /**
     * 更新分销商信息
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        $result = [
            'error' => 0,
        ];

        // 返回用户ID
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $shop = [];
        $shop['user_id'] = $user_id;
        $shop['id'] = $request->input('id', 0);
        $shop['shop_name'] = $request->input('shop_name', '');
        $shop['real_name'] = $request->input('real_name', '');
        $shop['mobile'] = $request->input('mobile', '');
        $shop['qq'] = $request->input('qq', '');
        $shop['type'] = $request->input('type', 0);
        $pic = $request->input('pic', '');
        if (!empty($pic)) {
            $shop['shop_img'] = $pic;
        }
        $res = $this->drpService->updateDrpShop($shop);

        if ($res == 1) {
            $result['id'] = $res;
            $result['msg'] = lang('drp.update_shop_info_success');
        } elseif ($res == 2) {
            $result['id'] = $res;
            $result['msg'] = lang('drp.no_update_shop_info');
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('drp.update_shop_info_fail');
        }

        return $this->succeed($result);
    }

    /**
     * 佣金转出页面
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function trans(Request $request)
    {
        $this->validate($request, [
        ]);

        // 返回用户ID
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $result = ['error' => 0,];

        $money = $this->drpService->drpConfig('draw_money');
        $result['min_money'] = $money['value'];
        // 分销商信息
        $shop_info = $this->drpService->shopInfo($user_id, 0);
        $result['max_money'] = $shop_info['shop_money'];

        return $this->succeed($result);
    }

    /**
     * 佣金转到余额
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function transFerred(Request $request)
    {
        $this->validate($request, [
            'amount' => 'required'
        ]);

        $amount = $request->get('amount');
        // 返回用户ID
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $money = $this->drpService->drpConfig('draw_money');
        if ($amount < $money['value']) {
            $result = [
                'error' => 1,
                'msg' => sprintf(lang('drp.ferred_money_no_less'), $money['value'])
            ];
            return $this->succeed($result);
        }

        //分销商信息
        $shop_info = $this->drpService->shopInfo($user_id, 0);
        if ($amount <= $shop_info['shop_money']) {
            //记录会员账目明细
            $info = sprintf(lang('drp.ferred_money_user_money'), $amount);
            $this->accountService->logAccountChange($user_id, $amount, 0, 0, 0, $info, ACT_TRANSFERRED);
            //更新分销用户佣金
            $shop_money = $shop_info['shop_money'] - $amount;
            $this->drpService->drpUpdateShopMoney($user_id, $shop_money);
        } else {
            $result = [
                'error' => 1,
                'msg' => sprintf(lang('drp.ferred_money_most'), $shop_info['shop_money'])
            ];
            return $this->succeed($result);
        }

        $result = [
            'error' => 0,
            'msg' => sprintf(lang('drp.ferred_money_success'), $amount)
        ];

        return $this->succeed($result);
    }

    /**
     * 分销订单
     * @param Request $request
     * @param UserRightsService $userRightsService
     * @return JsonResponse
     * @throws ValidationException
     */
    public function order(Request $request, UserRightsService $userRightsService)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'status' => 'required|integer',  // 2 全部 1分成 0未分成
        ]);

        $result = [];

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $status = $request->get('status');
        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        $offset = [
            'start' => ($page - 1) * $size,
            'limit' => $size
        ];
        $order = $this->drpService->drpLogOrder($user_id, $status, $offset);
        if (!empty($order)) {

            // 商品分销权益
            $userRights = $userRightsService->userRightsInfo('drp_goods');
            if (!empty($userRights)) {
                if (isset($userRights['enable']) && isset($userRights['install']) && $userRights['enable'] == 1 && $userRights['install'] == 1) {
                    // 当前分销商 会员权益卡信息
                    $cardRights = $userRightsService->membershipCardInfoByUserId($user_id, $userRights['id']);
                    $card_rights_configure = $cardRights['0']['rights_configure'] ?? [];
                }
            }

            // 会员卡分销权益
            $drpRights = $userRightsService->userRightsInfo('drp');
            if (!empty($drpRights)) {
                if (isset($drpRights['enable']) && isset($drpRights['install']) && $drpRights['enable'] == 1 && $drpRights['install'] == 1) {
                    // 当前分销商 会员权益卡信息
                    $drpCardRights = $userRightsService->membershipCardInfoByUserId($user_id, $drpRights['id']);
                    $drp_card_rights_configure = $drpCardRights['0']['rights_configure'] ?? [];
                }
            }

            foreach ($order as $key => $value) {

                $level_per = 0;
                $goods_list = [];
                // 普通订单
                if (isset($value['get_order']) && !empty($value['get_order'])) {
                    $value = collect($value)->merge($value['get_order'])->except('get_order')->all();

                    $result[$key]['buy_user_id'] = $value['get_users']['user_id'] ?? 0;
                    $nick_name = $value['get_users']['nick_name'] ?? '';
                    $result[$key]['buy_user_name'] = !empty($nick_name) ? $nick_name : $value['get_users']['user_name'] ?? '';

                    $drp_level_per = 0;
                    if (isset($value['level_percent']) && !empty($value['level_percent']) && $value['level_percent'] > 0) {
                        $drp_level_per = $value['level_percent'] * 100;
                    } else {
                        if ($value['log_type'] == 2) {
                            // 会员卡分销分成比例
                            if (isset($drp_card_rights_configure) && !empty($drp_card_rights_configure)) {
                                $drp_level_per = $drp_card_rights_configure[$value['drp_level']]['value'] ?? 0;
                            }
                        } elseif ($value['log_type'] == 0) {
                            // 商品分销分成比例
                            if (isset($card_rights_configure) && !empty($card_rights_configure)) {
                                $drp_level_per = $card_rights_configure[$value['drp_level']]['value'] ?? 0;
                            }
                        }
                    }
                    $drp_level_per = (float)$drp_level_per;

                    $order_goods = $value['goods'] ?? [];
                    if (!empty($order_goods)) {
                        foreach ($order_goods as $k => $val) {
                            $val = collect($val)->merge($val['get_goods'])->except('get_goods')->all();

                            if ($value['log_type'] == 2) {
                                // 分成比例*实际支付金额
                                $level_per = $drp_level_per;
                            } elseif ($value['log_type'] == 0) {
                                $level_per += $drp_level_per * ($val['drp_money'] / $val['goods_number'] / $val['goods_price']);
                            }

                            $goods_list[$k]['goods_id'] = $val['goods_id'];
                            $goods_list[$k]['goods_price'] = $val['goods_price'];
                            $goods_list[$k]['goods_price_format'] = $this->dscRepository->getPriceFormat($val['goods_price'], true);
                            $goods_list[$k]['goods_number'] = $val['goods_number'];
                            $goods_list[$k]['goods_name'] = $val['goods_name'];
                            $goods_list[$k]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                        }
                    }
                }
                // 付费购买
                if (isset($value['get_drp_account_log']) && !empty($value['get_drp_account_log'])) {
                    $value = collect($value)->merge($value['get_drp_account_log'])->except('get_drp_account_log')->all();

                    $result[$key]['buy_user_id'] = $value['get_users']['user_id'] ?? 0;
                    $nick_name = $value['get_users']['nick_name'] ?? '';
                    $result[$key]['buy_user_name'] = !empty($nick_name) ? $nick_name : $value['get_users']['user_name'] ?? '';

                    // 会员卡分销分成比例
                    $drp_user_level_per = 0;
                    if (isset($value['level_percent']) && !empty($value['level_percent']) && $value['level_percent'] > 0) {
                        $drp_user_level_per = $value['level_percent'] * 100;
                    } else {
                        if (isset($drp_card_rights_configure) && !empty($drp_card_rights_configure)) {
                            $drp_user_level_per = $drp_card_rights_configure[$value['drp_level']]['value'] ?? 0;
                        }
                    }
                    $drp_user_level_per = (float)$drp_user_level_per;

                    $membership_card = $value['user_membership_card'] ?? [];
                    if (!empty($membership_card)) {

                        $level_per = $drp_user_level_per;

                        $goods_list[0]['goods_id'] = $value['membership_card_id'];
                        $goods_list[0]['goods_price'] = $value['amount'];
                        $goods_list[0]['goods_price_format'] = $this->dscRepository->getPriceFormat($value['amount'], true);
                        $goods_list[0]['goods_number'] = 1;
                        $goods_list[0]['goods_name'] = $membership_card['name'];
                        $goods_list[0]['goods_thumb'] = empty($membership_card['background_img']) ? asset('img/membership_card_default.png') : $this->dscRepository->getImagePath($membership_card['background_img']);
                    }
                }

                if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                    $result[$key]['buy_user_name'] = $this->dscRepository->stringToStar($result[$key]['buy_user_name']);
                }

                $result[$key]['order_sn'] = $value['order_sn'] ?? '';
                $result[$key]['order_id'] = $value['order_id'] ?? 0;
                $result[$key]['log_id'] = $value['log_id'] ?? 0;
                $result[$key]['log_type'] = $value['log_type'] ?? 0;
                $result[$key]['status'] = $value['is_separate'];
                $result[$key]['money'] = $value['money'];
                $result[$key]['money_format'] = $this->dscRepository->getPriceFormat($value['money'], true);
                $result[$key]['add_time'] = $value['add_time'];
                $result[$key]['add_time_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $value['add_time']);
                $result[$key]['goods_list'] = $goods_list;
                $result[$key]['level_per'] = round($level_per, 2) . "%";
            }
        }

        return $this->succeed($result);
    }

    /**
     * 分销订单详情
     * @param Request $request
     * @param UserRightsService $userRightsService
     * @return JsonResponse
     * @throws ValidationException
     */
    public function orderDetail(Request $request, UserRightsService $userRightsService)
    {
        $this->validate($request, [
            'order_id' => 'required|integer'
        ]);

        $result = [];

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $order_id = $request->get('order_id');

        $order = $this->drpService->orderDetail($user_id, $order_id);
        if (!empty($order)) {
            $value = $order;

            // 商品分销权益
            $userRights = $userRightsService->userRightsInfo('drp_goods');
            if (!empty($userRights)) {
                if (isset($userRights['enable']) && isset($userRights['install']) && $userRights['enable'] == 1 && $userRights['install'] == 1) {
                    // 当前分销商 会员权益卡信息
                    $cardRights = $userRightsService->membershipCardInfoByUserId($user_id, $userRights['id']);
                    $card_rights_configure = $cardRights['0']['rights_configure'] ?? [];
                }
            }

            // 会员卡分销权益
            $drpRights = $userRightsService->userRightsInfo('drp');
            if (!empty($drpRights)) {
                if (isset($drpRights['enable']) && isset($drpRights['install']) && $drpRights['enable'] == 1 && $drpRights['install'] == 1) {
                    // 当前分销商 会员权益卡信息
                    $drpCardRights = $userRightsService->membershipCardInfoByUserId($user_id, $drpRights['id']);
                    $drp_card_rights_configure = $drpCardRights['0']['rights_configure'] ?? [];
                }
            }

            $level_per = 0;
            $goods_list = [];
            // 普通订单
            if (isset($value['get_order']) && !empty($value['get_order'])) {
                $value = collect($value)->merge($value['get_order'])->except('get_order')->all();

                $result['buy_user_id'] = $value['get_users']['user_id'] ?? 0;
                $nick_name = $value['get_users']['nick_name'] ?? '';
                $result['buy_user_name'] = !empty($nick_name) ? $nick_name : $value['get_users']['user_name'] ?? '';

                $drp_level_per = 0;
                if (isset($value['level_percent']) && !empty($value['level_percent']) && $value['level_percent'] > 0) {
                    $drp_level_per = $value['level_percent'] * 100;
                } else {
                    if ($value['log_type'] == 2) {
                        // 会员卡分销分成比例
                        if (isset($drp_card_rights_configure) && !empty($drp_card_rights_configure)) {
                            $drp_level_per = $drp_card_rights_configure[$value['drp_level']]['value'] ?? 0;
                        }
                    } elseif ($value['log_type'] == 0) {
                        // 商品分销分成比例
                        if (isset($card_rights_configure) && !empty($card_rights_configure)) {
                            $drp_level_per = $card_rights_configure[$value['drp_level']]['value'] ?? 0;
                        }
                    }
                }
                $drp_level_per = (float)$drp_level_per;

                $order_goods = $value['goods'] ?? [];
                if (!empty($order_goods)) {
                    foreach ($order_goods as $k => $val) {
                        $val = collect($val)->merge($val['get_goods'])->except('get_goods')->all();

                        if ($value['log_type'] == 2) {
                            // 分成比例*实际支付金额
                            $level_per = $drp_level_per;
                        } elseif ($value['log_type'] == 0) {
                            $level_per += $drp_level_per * ($val['drp_money'] / $val['goods_number'] / $val['goods_price']);
                        }

                        $goods_list[$k]['goods_id'] = $val['goods_id'];
                        $goods_list[$k]['goods_price'] = $val['goods_price'];
                        $goods_list[$k]['goods_price_format'] = $this->dscRepository->getPriceFormat($val['goods_price'], true);
                        $goods_list[$k]['goods_number'] = $val['goods_number'];
                        $goods_list[$k]['goods_name'] = $val['goods_name'];
                        $goods_list[$k]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                    }
                }
            }

            // 付费购买
            if (isset($value['get_drp_account_log']) && !empty($value['get_drp_account_log'])) {
                $value = collect($value)->merge($value['get_drp_account_log'])->except('get_drp_account_log')->all();

                $result['buy_user_id'] = $value['get_users']['user_id'] ?? 0;
                $nick_name = $value['get_users']['nick_name'] ?? '';
                $result['buy_user_name'] = !empty($nick_name) ? $nick_name : $value['get_users']['user_name'] ?? '';

                // 会员卡分销分成比例
                $drp_user_level_per = 0;
                if (isset($value['level_percent']) && !empty($value['level_percent']) && $value['level_percent'] > 0) {
                    $drp_user_level_per = $value['level_percent'] * 100;
                } else {
                    if (isset($drp_card_rights_configure) && !empty($drp_card_rights_configure)) {
                        $drp_user_level_per = $drp_card_rights_configure[$value['drp_level']]['value'] ?? 0;
                    }
                }
                $drp_user_level_per = (float)$drp_user_level_per;

                $membership_card = $value['user_membership_card'] ?? [];
                if (!empty($membership_card)) {

                    $level_per = $drp_user_level_per;

                    $goods_list[0]['goods_id'] = $value['membership_card_id'];
                    $goods_list[0]['goods_price'] = $value['amount'];
                    $goods_list[0]['goods_price_format'] = $this->dscRepository->getPriceFormat($value['amount'], true);
                    $goods_list[0]['goods_number'] = 1;
                    $goods_list[0]['goods_name'] = $membership_card['name'];
                    $goods_list[0]['goods_thumb'] = $this->dscRepository->getImagePath($membership_card['background_img']);
                }
            }

            if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                $result['buy_user_name'] = $this->dscRepository->stringToStar($result['buy_user_name']);
            }

            $result['order_sn'] = $value['order_sn'] ?? '';
            $result['order_id'] = $value['order_id'] ?? 0;
            $result['log_type'] = $value['log_type'] ?? 0;
            $result['status'] = $value['is_separate'];
            $result['money'] = $value['money'];
            $result['money_format'] = $this->dscRepository->getPriceFormat($value['money'], true);
            $result['add_time'] = $value['add_time'];
            $result['add_time_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $value['add_time']);
            $result['goods_list'] = $goods_list;
            $result['level_per'] = round($level_per, 2) . "%";
        }

        return $this->succeed($result);
    }

    /**
     * 我的团队
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function team(Request $request)
    {
        $this->validate($request, [
//            'user_id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();

        $child_user_id = $request->get('user_id');

        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        $user_id = $child_user_id ? $child_user_id : $user_id;

        $result = [];
        $teaminfo = $this->drpService->teamInfo($user_id, $page, $size);
        $team_info = [];
        if ($teaminfo) {
            foreach ($teaminfo as $key => $value) {
                //贡献佣金
                $money = $this->drpService->moneyAffiliate($child_user_id, $value['user_id']);
                $team_info[$key]['user_id'] = $value['user_id'];
                $team_info[$key]['reg_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $value['reg_time']);
                $team_info[$key]['money'] = $money;
                //会员信息
                $team_info[$key]['user_name'] = !empty($value['nick_name']) ? $value['nick_name'] : $value['user_name'];

                if (isset($value['get_drp_shop']['shop_portrait']) && $value['get_drp_shop']['shop_portrait']) {
                    $team_info[$key]['user_picture'] = $this->dscRepository->getImagePath($value['get_drp_shop']['shop_portrait']);
                } else {
                    $team_info[$key]['user_picture'] = $this->dscRepository->getImagePath($value['user_picture']);
                }
            }
        }
        $result['team_info'] = $team_info;
        $result['next_id'] = $child_user_id;

        return $this->succeed($result);
    }

    /**
     * 团队详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function teamDetail(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|integer'
        ]);

        $child_user_id = $request->get('user_id');

        //团队详情信息
        $info = $this->drpService->teamDetail($child_user_id);

        if (empty($info)) {
            return $this->succeed($info);
        }
        $info['reg_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $info['reg_time']);
        //会员信息
        $info['user_name'] = !empty($info['nick_name']) ? $info['nick_name'] : $info['user_name'];
        if (isset($info['get_drp_shop']['shop_portrait']) && $info['get_drp_shop']['shop_portrait']) {
            $info['user_picture'] = $this->dscRepository->getImagePath($info['get_drp_shop']['shop_portrait']);
        } else {
            $info['user_picture'] = $this->dscRepository->getImagePath($info['user_picture']);
        }
        $info['shop_name'] = $info['get_drp_shop']['shop_name'] ?? '';
        //贡献佣金
        $info['sum_money'] = $this->drpService->moneyAffiliate($info['drp_parent_id'], $child_user_id);
        //今日贡献
        $info['now_money'] = $this->drpService->moneyAffiliate($info['drp_parent_id'], $child_user_id, 1);
        //下线人数
        $info['next_num'] = $this->drpService->drpNextNum($child_user_id);

        return $this->succeed($info);
    }

    /**
     * 下线会员
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function offlineUser(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);

        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        $result = [];

        // 返回用户ID
        $user_id = $this->authorization();

        $child_user_id = $request->get('user_id');

        $user_id = $child_user_id ? $child_user_id : $user_id;

        $user_list = $this->drpService->offlineUser($user_id, $page, $size);

        if ($user_list) {
            foreach ($user_list as $key => $value) {
                $user_list[$key]['user_id'] = $value['user_id'];
                $user_list[$key]['reg_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $value['reg_time']);
                //会员信息
                $user_list[$key]['user_name'] = !empty($value['nick_name']) ? $value['nick_name'] : $value['user_name'];
                $user_list[$key]['user_picture'] = $this->dscRepository->getImagePath($value['user_picture']);
            }
        }
        $result['user_list'] = $user_list;
        $result['next_id'] = $child_user_id;

        return $this->succeed($result);
    }

    /**
     * 我的名片
     * @param Request $request
     * @return JsonResponse
     * @throws FileNotFoundException
     */
    public function userCard(Request $request)
    {
        //返回用户ID
        $user_id = $this->authorization();

        // 生成分销二维码类型  默认 url: 我的店铺链接 或 qrcode: 公众号推荐二维码
        $type = $request->input('type', 'url');

        $data = $this->drpService->createUserCard($user_id, $type);

        if (isset($data['file']) && $data['file']) {
            // 同步镜像上传到OSS
            $this->ossMirror($data['file'], true);
        }

        $result['outImg'] = $data['url'];

        return $this->succeed($result);
    }

    /**
     * 分销排行
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function rankList(Request $request)
    {
        // 返回用户ID
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $page = $request->get('page', 1);
        $size = $request->get('size', 30);

        // 排行前三十
        $offset = [
            'start' => ($page - 1) * $size,
            'limit' => $size
        ];
        $list = $this->drpService->drpRankList($offset);

        // 排行名次
        $rank = $this->drpService->drpRankNum($user_id);

        return $this->succeed(['list' => $list, 'rank' => $rank]);
    }

    /**
     * 佣金明细
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function drpLog(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'status' => 'required|integer'// 全部2  为分成0  已分成1
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $status = $request->get('status');
        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        $offset = [
            'start' => ($page - 1) * $size,
            'limit' => $size
        ];
        $drplog = $this->drpService->drpLog($user_id, $status, $offset);

        return $this->succeed($drplog);
    }

    /**
     * 文章
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function news(Request $request)
    {
        $this->validate($request, []);

        $news = $this->drpService->news();

        return $this->succeed($news);
    }

    /**
     * 分销模式分类选择
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function cartlist(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);
        $uid = $this->authorization();
        $data = $this->drpService->getMobileCategoryChild($cat_id, $uid);
        if ($cat_id > 0) {
            //分销商信息
            $shop_info = $this->drpService->shopInfo($uid, 0);
            $result['data'] = $data;
            $result['type'] = $shop_info['type'];
            return $this->succeed($result);
        }
        return $this->succeed($data);
    }

    /**
     * 分销模式分类选择
     * @param Request $request
     * @return JsonResponse
     */
    public function drpgoods(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);
        $uid = $this->authorization();

        $data = $this->drpService->getCategoryGetGoods($uid, $cat_id);

        return $this->succeed($data);
    }

    /**
     * 分销模式添加代言商品分类
     * @param Request $request
     * @return JsonResponse
     */
    public function addcart(Request $request)
    {
        $id = $request->input('id', []);
        $type = $request->input('type', 0);
        $uid = $this->authorization();
        $data = $this->drpService->addDrpCart($uid, $id, $type);

        return $this->succeed($data);
    }

    /**
     * 分销模式添加代言商品
     * @param Request $request
     * @return JsonResponse
     */
    public function addgoods(Request $request)
    {
        $goods_id = $request->input('goods_id', 0);
        $uid = $this->authorization();
        $data = $this->drpService->addDrpGoods($uid, $goods_id);

        return $this->succeed($data);
    }
}
