<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\BaitiaoLog;
use App\Models\BaitiaoPayLog;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\Payment;
use App\Models\Stages;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\CommonService;
use App\Services\User\UserBaitiaoService;
use App\Services\User\UserCommonService;

class UserBaitiaoController extends InitController
{
    protected $dscRepository;
    protected $userCommonService;
    protected $config;
    protected $articleCommonService;
    protected $commonRepository;
    protected $commonService;
    protected $userBaitiaoService;
    protected $baseRepository;

    public function __construct(
        DscRepository $dscRepository,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        CommonRepository $commonRepository,
        CommonService $commonService,
        UserBaitiaoService $userBaitiaoService,
        BaseRepository $baseRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->userCommonService = $userCommonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->articleCommonService = $articleCommonService;
        $this->commonRepository = $commonRepository;
        $this->commonService = $commonService;
        $this->userBaitiaoService = $userBaitiaoService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        $this->dscRepository->helpersLang(['user']);

        $user_id = session('user_id', 0);
        $action = addslashes(trim(request()->input('act', 'default')));
        $action = $action ? $action : 'default';

        $not_login_arr = $this->userCommonService->notLoginArr();

        $ui_arr = $this->userCommonService->uiArr('baitiao');

        /* 未登录处理 */
        $requireUser = $this->userCommonService->requireLogin(session('user_id'), $action, $not_login_arr, $ui_arr);
        $action = $requireUser['action'];
        $require_login = $requireUser['require_login'];

        if ($require_login == 1) {
            //未登录提交数据。非正常途径提交数据！
            return dsc_header('location:' . $this->dscRepository->dscUrl('user.php'));
        }

        /* 区分登录注册底部样式 */
        $footer = $this->userCommonService->userFooter();
        if (in_array($action, $footer)) {
            $this->smarty->assign('footer', 1);
        }

        $is_apply = $this->userCommonService->merchantsIsApply($user_id);
        $this->smarty->assign('is_apply', $is_apply);

        $user_default_info = $this->userCommonService->getUserDefault($user_id);
        $this->smarty->assign('user_default_info', $user_default_info);

        /* 如果是显示页面，对页面进行相应赋值 */
        if (in_array($action, $ui_arr)) {
            assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['user_core']);
            $this->smarty->assign('page_title', $position['title']); // 页面标题
            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版
            $this->smarty->assign('ur_here', $position['ur_here']);

            $this->smarty->assign('car_off', $this->config['anonymous_buy']);

            /* 是否显示积分兑换 */
            if (!empty($this->config['points_rule']) && unserialize($this->config['points_rule'])) {
                $this->smarty->assign('show_transform_points', 1);
            }

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助
            $this->smarty->assign('data_dir', DATA_DIR);   // 数据目录
            $this->smarty->assign('action', $action);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $info = $user_default_info;

            if ($user_id) {
                //验证邮箱
                if (isset($info['is_validated']) && !$info['is_validated'] && $this->config['user_login_register'] == 1) {
                    $Location = url('/') . '/' . 'user.php?act=user_email_verify';
                    return dsc_header('location:' . $Location);
                }
            }

            $count = AdminUser::where('ru_id', session('user_id'))->count();
            if ($count) {
                $is_merchants = 1;
            } else {
                $is_merchants = 0;
            }

            $this->smarty->assign('is_merchants', $is_merchants);
            $this->smarty->assign('shop_reg_closed', $this->config['shop_reg_closed']);

            $this->smarty->assign('filename', 'user');
        } else {
            if (!in_array($action, $not_login_arr) || $user_id == 0) {
                $referer = '?back_act=' . urlencode(request()->server('REQUEST_URI'));
                $back_act = $this->dscRepository->dscUrl('user.php' . $referer);
                return dsc_header('location:' . $back_act);
            }
        }

        $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();
        $wholesaleUse = $this->commonService->judgeWholesaleUse(session('user_id'), 1);
        $wholesale_use = $supplierEnabled && $wholesaleUse ? 1 : 0;

        $this->smarty->assign('wholesale_use', $wholesale_use);

        /* ------------------------------------------------------ */
        //-- 白条列表
        /* ------------------------------------------------------ */
        if ($action == 'baitiao') {
            load_helper('transaction');
            assign_template();

            $page = (int)request()->input('page', 1);

            //所有待付款白条的总额和条数
            $baitiao_balance = $this->userBaitiaoService->getBaitiaoBalance($user_id);

            $bt_info = $baitiao_balance['bt_info'];

            $record_count = BaitiaoLog::where('user_id', $user_id)->count();

            $pager = get_pager('user_baitiao.php', ['act' => $action], $record_count, $page);

            //白条记录
            $bt_log = $this->userBaitiaoService->getBaitiaoLogList($user_id, $pager['size'], $pager['start']);

            if (isset($bt_info['amount'])) {

                //所有待还款白条的总额和条数
                $repay_bt = $this->userBaitiaoService->getRepayBt($user_id);

                if (isset($repay_bt['total_amount']) && $repay_bt['total_amount'] > 0) {
                    $remain_amount = floatval($bt_info['amount']) - floatval($repay_bt['total_amount']);
                } else {
                    $remain_amount = floatval($bt_info['amount']);
                }
            } else {
                $remain_amount = 0;
            }

            $this->smarty->assign('action', 'baitiao');
            $this->smarty->assign('remain_amount', $remain_amount);
            $this->smarty->assign('bt_info', $bt_info);
            $this->smarty->assign('repay_bt', $baitiao_balance);
            $this->smarty->assign('bt_logs', $bt_log);

            $this->smarty->assign("page", $page);
            $this->smarty->assign('pager', $pager);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 白条付款记录
        /* ------------------------------------------------------ */
        elseif ($action == 'baitiao_pay_log') {
            load_helper('transaction');
            assign_template();

            $page = (int)request()->input('page', 1);
            $id = (int)request()->input('log_id', 0);

            $log = BaitiaoLog::where('user_id', $user_id);

            if ($id) {
                $log = $log->where('log_id', $id);
            }

            $log = $this->baseRepository->getToArrayGet($log);
            $log_id = $this->baseRepository->getKeyPluck($log, 'log_id');

            $record_count = 0;
            if ($log_id) {
                $record_count = BaitiaoPayLog::whereIn('log_id', $log_id)->count();
            }

            if ($id) {
                $param = ['act' => $action, 'log_id' => $id];
            } else {
                $param = ['act' => $action];
            }

            $pager = get_pager('user_baitiao.php', $param, $record_count, $page);

            $pay_list = $this->userBaitiaoService->getBaitiaoPayLogList($log_id, $pager['size'], $pager['start']);

            $this->smarty->assign('pay_list', $pay_list);
            $this->smarty->assign("page", $page);
            $this->smarty->assign('pager', $pager);

            //会员会员白条信息
            $where_other = [
                'user_id' => $user_id
            ];
            $bt_info = $this->userBaitiaoService->getBaitiaoInfo($where_other);

            //所有待付款白条的总额和条数
            $baitiao_balance = $this->userBaitiaoService->getBaitiaoBalance($user_id);
            $remain_amount = $baitiao_balance['balance'];

            $this->smarty->assign('action', 'baitiao_pay_log');
            $this->smarty->assign('remain_amount', $remain_amount);
            $this->smarty->assign('bt_info', $bt_info);
            $this->smarty->assign('repay_bt', $baitiao_balance);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 白条付款
        /* ------------------------------------------------------ */
        elseif ($action == 'repay_bt') {
            load_helper('transaction');
            load_helper('payment');
            load_helper('order');
            load_helper('clips');

            $this->dscRepository->helpersLang('user');

            assign_template();

            get_request_filter();

            $order_id = (int)request()->input('order_id', 0);
            $pay_id = (int)request()->input('pay_id', 0);
            $stages_num = (int)request()->input('stages_num', 0);

            if ($pay_id < 1) {
                $payInfo = Payment::where('pay_code', 'chunsejinrong');
                $payInfo = $this->baseRepository->getToArrayFirst($payInfo);

                if ($payInfo) {
                    OrderInfo::where('order_id', $order_id)
                        ->where('user_id', $user_id)
                        ->update([
                            'pay_id' => $payInfo['pay_id'],
                            'pay_name' => $payInfo['pay_name']
                        ]);
                }

                $pay_id = $payInfo['pay_id'] ?? 0;
            }

            /* 检查支付方式 */
            if (empty($user_id) || empty($order_id) || empty($pay_id) || empty($stages_num)) {
                return show_message($GLOBALS['_LANG']['payment_coupon'], $GLOBALS['_LANG']['baitiao'], 'user_baitiao.php?act=baitiao');
            }

            $payment_info = payment_info($pay_id);
            if (empty($payment_info)) {
                return dsc_header("Location: ./\n");
            }

            /* 取得订单 */
            $order = order_info($order_id);
            if (empty($order)) {
                return dsc_header("Location: ./\n");
            }

            /* 检查订单用户跟当前用户是否一致 */
            if ($user_id != $order['user_id']) {
                return show_message($GLOBALS['_LANG']['payment_coupon'], $GLOBALS['_LANG']['baitiao'], 'user_baitiao.php?act=baitiao');
            }

            /*  检查订单是否是白条分期付款的,白条分期的付款订单,价格只付1期 bylu */
            $where_other = ['order_id' => $order['order_id']];
            $stages_info = $this->userBaitiaoService->getBaitiaoLogInfo($where_other);

            /* 已还款 */
            $where_pay_other = [
                'baitiao_id' => $stages_info['baitiao_id'],
                'log_id' => $stages_info['log_id'],
                'stages_num' => $stages_num,
                'is_pay' => 1
            ];

            $bt_pay_log_info = $this->userBaitiaoService->getBaitiaoPayLogInfo($where_pay_other);
            if ($bt_pay_log_info) {
                return show_message($GLOBALS['_LANG']['payment_coupon'] . "," . $GLOBALS['_LANG']['baitiao_is_pay'], $GLOBALS['_LANG']['baitiao'], 'user_baitiao.php?act=baitiao');
            }

            /* 检查当前白条订单是否已还清钱款 */
            if ($stages_info['stages_total'] == $stages_info['yes_num'] && $stages_info['is_repay'] == 1) {
                return show_message($GLOBALS['_LANG']['payment_coupon'], $GLOBALS['_LANG']['label_order'], 'user.php');
            }

            $stages_rate = 0;
            $stages_one_price = 0;
            //如果是白条分期订单;
            if ($stages_info['is_stages'] == 1) {
                $order_amount = $stages_info['stages_one_price'];
                $stages_one_price = $order_amount; //每期分期的价格;
                //获得分期费率;
                $goods_id = OrderGoods::where('order_id', $order['order_id'])->value('goods_id');
                $stages_rate = Goods::where('goods_id', $goods_id)->value('stages_rate');
            } else {
                $order_amount = $order['order_amount'] - $order['pay_fee'];
                $pay_fee = pay_fee($pay_id, $order_amount);
                $order_amount += $pay_fee;
            }

            /* 如果全部使用余额支付，检查余额是否足够 */
            if ($payment_info['pay_code'] == 'balance' && $order_amount > 0) {

                $user_info = Users::where('user_id', $user_id)->first();
                $user_info = $user_info ? $user_info->toArray() : [];

                if ($order['surplus'] > 0) { //余额支付里如果输入了一个金额
                    $order_amount = $order['order_amount'] + $order['surplus'];
                    $order['surplus'] = 0;

                    if ($order['pay_code'] == 'chunsejinrong') {
                        $order_amount = $order['money_paid'];
                    }
                }

                $bt_time = gmtime();
                if ($user_info && $order_amount > ($user_info['user_money'] + $user_info['credit_line'])) {
                    return show_message($GLOBALS['_LANG']['balance_insufficient']);
                } else {
                    //如果是白条分期订单,每次还款额度为每期的金额 bylu;
                    if ($stages_info['is_stages'] == 1) {
                        //白条分期扣款;
                        log_account_change(session('user_id'), $order_amount * (-1), 0, 0, 0, sprintf($GLOBALS['_LANG']['Ious_Prompt_one'], $stages_info['yes_num'] + 1, $order['order_sn']));
                    } else {
                        //普通白条扣款;
                        log_account_change(session('user_id'), $order_amount * (-1), 0, 0, 0, sprintf($GLOBALS['_LANG']['Ious_Prompt_two'], $order['order_sn']));
                    }

                    /* 如果是白条分期订单;还款期数+1 bylu */
                    if ($stages_info['is_stages'] == 1) {
                        $is_pay_bt = BaitiaoLog::where('order_id', $order['order_id'])->increment('yes_num', 1, ['repayed_date' => $bt_time]);

                        Stages::where('order_sn', $order['order_sn'])->increment('yes_num', 1, ['repay_date' => $bt_time]);

                        //判断当前订单是否已经还清分期数 bylu;
                        $stages_info_2 = BaitiaoLog::where('order_id', $order['order_id'])->first();
                        $stages_info_2 = $stages_info_2 ? $stages_info_2->toArray() : [];

                        if ($stages_info_2 && ($stages_info_2['stages_total'] == $stages_info_2['yes_num']) && $stages_info_2['is_repay'] == 0) {
                            //已还清,更新白条状态为已还清;
                            BaitiaoLog::where('order_id', $stages_info_2['order_id'])->update(['is_repay' => 1]);
                        }
                    } else {

                        //修改白条消费记录支付状态
                        $other = [
                            'is_repay' => 1,
                            'repayed_date' => $bt_time
                        ];
                        $is_pay_bt = BaitiaoLog::where('order_id', $order['order_id'])->update($other);
                    }

                    if ($is_pay_bt) {

                        //修改白条消费记录支付状态
                        $other = [
                            'is_pay' => 1,
                            'pay_time' => $bt_time
                        ];
                        BaitiaoPayLog::where('baitiao_id', $stages_info['baitiao_id'])
                            ->where('log_id', $stages_info['log_id'])
                            ->where('stages_num', $stages_num)
                            ->update($other);

                        return show_message($GLOBALS['_LANG']['Ious_Payment_success'], $GLOBALS['_LANG']['my_Ious'], 'user_baitiao.php?act=baitiao');
                    } else {
                        return show_message($GLOBALS['_LANG']['pay_fail']);
                    }
                }
            }

            //如果不是白条分期订单,才更新订单总价 bylu;
            if ($stages_info['is_stages'] != 1) {
                OrderInfo::where('order_id', $order_id)->update('order_amount', $order_amount);
            }

            $order = get_order_detail($order_id, $user_id);
            $payment_list = available_payment_list(false, 0, 2);

            //@author-bylu 过滤掉白条支付;
            if ($payment_list) {
                foreach ($payment_list as $k => $v) {
                    if ($v['pay_name'] == $GLOBALS['_LANG']['ious_pay']) {
                        unset($payment_list[$k]);
                    }
                }
            }

            /* 已还款 */
            $where_pay_other = [
                'baitiao_id' => $stages_info['baitiao_id'],
                'log_id' => $stages_info['log_id'],
                'stages_num' => $stages_num,
                'is_pay' => 0
            ];
            $bt_pay_log_info = $this->userBaitiaoService->getBaitiaoPayLogInfo($where_pay_other);

            if ($stages_info && $stages_info['is_repay'] == 0) {
                if ($bt_pay_log_info && $bt_pay_log_info['pay_id']) {
                    $payment = [
                        'pay_id' => $bt_pay_log_info['pay_id'],
                        'pay_code' => $bt_pay_log_info['pay_code']
                    ];

                    if ($payment && strpos($payment['pay_code'], 'pay_') === false) {
                        /* 调用相应的支付方式文件 */
                        $payObject = $this->commonRepository->paymentInstance($payment['pay_code']);
                        if (!is_null($payObject)) {
                            /* 判断类对象方法是否存在 */
                            if (is_callable([$payObject, 'orderQuery'])) {
                                $order_other = [
                                    'order_sn' => $order['order_sn'],
                                    'log_id' => $bt_pay_log_info['id'],
                                    'order_amount' => $order['order_amount'],
                                ];
                                $payObject->orderQuery($order_other);

                                $is_pay = BaitiaoPayLog::where('id', $bt_pay_log_info['id'])->value('is_pay');

                                if ($is_pay == 1) {
                                    return show_message($GLOBALS['_LANG']['payment_coupon'] . "," . $GLOBALS['_LANG']['baitiao_is_pay'], $GLOBALS['_LANG']['baitiao'], 'user_baitiao.php?act=baitiao');
                                }
                            }
                        }
                    }
                }
            }

            //@author-bylu 生成支付按钮;
            $payment_info = payment_info($pay_id);
            //无效支付方式
            if ($payment_info === false) {
                $order['pay_online'] = '';
            } else {
                //分期支付金额
                $order['order_amount'] = $order_amount;

                //取得支付信息，生成支付代码
                $payment = unserialize_config($payment_info['pay_config']);

                //获取需要支付的log_id
                $order['log_id'] = $bt_pay_log_info['id'] ?? 0;

                /* 调用相应的支付方式文件 *//* 当 支付方式不为"在线支付"才引类 bylu */
                if ($order['pay_name'] != $GLOBALS['_LANG']['pay_noline']) {
                    if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                        /* 调用相应的支付方式文件 */
                        $payObject = $this->commonRepository->paymentInstance($payment_info['pay_code']);
                        /* 取得在线支付方式的支付按钮 */
                        if (!is_null($payObject)) {
                            $order['pay_online'] = $payObject->get_code($order, $payment);
                        }
                    }
                }

                $order['pay_desc'] = $payment_info['pay_desc'];
                $order['user_name'] = session('user_name') ? addslashes(session('user_name')) : '';

                //修改白条消费记录支付方式信息
                $other = [
                    'pay_id' => $payment_info['pay_id'],
                    'pay_code' => $payment_info['pay_code']
                ];
                BaitiaoPayLog::where('baitiao_id', $stages_info['baitiao_id'])
                    ->where('log_id', $stages_info['log_id'])
                    ->where('stages_num', $stages_num)
                    ->update($other);
            }

            $this->smarty->assign('payment_info', $payment_info);
            $this->smarty->assign('action', 'repay_bt');
            $this->smarty->assign('order', $order);
            $this->smarty->assign('stages_info', $stages_info);
            $this->smarty->assign('stages_rate', $stages_rate);
            $this->smarty->assign('stages_one_price', $stages_one_price);
            $this->smarty->assign('payment_list', $payment_list);
            $this->smarty->assign('stages_num', $stages_num);

            return $this->smarty->display('user_transaction.dwt');
        }

    }
}
