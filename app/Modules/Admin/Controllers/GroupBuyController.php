<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\GroupBuyService;
use App\Services\Goods\GoodsService;
use App\Services\Goods\GroupBuyManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderRefoundService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心团购商品管理
 */
class GroupBuyController extends InitController
{
    protected $groupBuyService;
    protected $goodsService;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $commonService;
    protected $groupBuyManageService;
    protected $dscRepository;
    protected $commonRepository;
    protected $orderRefoundService;
    protected $storeCommonService;

    public function __construct(
        GroupBuyService $groupBuyService,
        GoodsService $goodsService,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        GroupBuyManageService $groupBuyManageService,
        DscRepository $dscRepository,
        CommonRepository $commonRepository,
        OrderRefoundService $orderRefoundService,
        StoreCommonService $storeCommonService
    )
    {
        $this->groupBuyService = $groupBuyService;
        $this->goodsService = $goodsService;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->groupBuyManageService = $groupBuyManageService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->orderRefoundService = $orderRefoundService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('goods');
        load_helper('order');

        /* 检查权限 */
        admin_priv('group_by');

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /* ------------------------------------------------------ */
        //-- 团购活动列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['group_buy_list']);
            $this->smarty->assign('action_link', ['href' => 'group_buy.php?act=add', 'text' => $GLOBALS['_LANG']['add_group_buy']]);

            $list = $this->groupBuyManageService->groupBuyList($adminru['ru_id']);

            $this->smarty->assign('group_buy_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            /* 显示商品列表页面 */

            return $this->smarty->display('group_buy_list.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $list = $this->groupBuyManageService->groupBuyList($adminru['ru_id']);

            $this->smarty->assign('group_buy_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('group_buy_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑团购活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 初始化/取得团购活动信息 */
            if ($_REQUEST['act'] == 'add') {
                $group_buy = [
                    'act_id' => 0,
                    'start_time' => date('Y-m-d H:i:s', time() + 86400),
                    'end_time' => date('Y-m-d H:i:s', time() + 4 * 86400),
                    'price_ladder' => [['amount' => 0, 'price' => 0]]
                ];
            } else {
                $group_buy_id = intval($_REQUEST['id']);
                if ($group_buy_id <= 0) {
                    return 'invalid param';
                }

                $where = [
                    'group_buy_id' => $group_buy_id,
                    'path' => 'admin'
                ];
                $group_buy = $this->groupBuyService->getGroupBuyInfo($where);
            }
            $this->smarty->assign('group_buy', $group_buy);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_group_buy']);
            $this->smarty->assign('action_link', $this->groupBuyManageService->listLink($_REQUEST['act'] == 'add'));
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            if ($_REQUEST['act'] == 'edit') {
                $this->smarty->assign('form_action', 'update');
            } else {
                $this->smarty->assign('form_action', 'insert');
            }


            set_default_filter(); //设置默认筛选

            /* 显示模板 */

            return $this->smarty->display('group_buy_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑团购活动的提交
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert_update') {
            /* 取得团购活动id */
            $group_buy_id = intval($_POST['act_id']);
            if (isset($_POST['finish']) || isset($_POST['succeed']) || isset($_POST['fail']) || isset($_POST['mail'])) {
                if ($group_buy_id <= 0) {
                    return sys_msg($GLOBALS['_LANG']['error_group_buy'], 1);
                }

                $where = [
                    'group_buy_id' => $group_buy_id,
                    'path' => 'admin'
                ];
                $group_buy = $this->groupBuyService->getGroupBuyInfo($where);
                if (empty($group_buy)) {
                    return sys_msg($GLOBALS['_LANG']['error_group_buy'], 1);
                }
            }

            if (isset($_POST['finish'])) {
                /* 判断订单状态 */
                if ($group_buy['status'] != GBS_UNDER_WAY) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 结束团购活动，修改结束时间为当前时间 */
                $data = ['end_time' => gmtime()];
                GoodsActivity::where('act_id', $group_buy_id)->update($data);
                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'group_buy.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['succeed'])) {
                /* 设置活动成功 */

                /* 判断订单状态 */
                if ($group_buy['status'] != GBS_FINISHED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 如果有订单，更新订单信息 */
                if ($group_buy['total_order'] > 0) {
                    /* 查找该团购活动的已确认或未确认订单（已取消的就不管了） */
                    $res = OrderInfo::select('order_id');
                    $res = $res->where('extension_code', 'group_buy')->where('extension_id', $group_buy_id);
                    $res = $res->where(function ($query) {
                        $query->where('order_status', OS_CONFIRMED)->orWhere('order_status', OS_UNCONFIRMED);
                    });
                    $order_id_list = $this->baseRepository->getToArrayGet($res);
                    $order_id_list = $this->baseRepository->getFlatten($order_id_list);

                    /* 更新订单商品价 */
                    $final_price = $group_buy['trans_price'];
                    $data = ['goods_price' => $final_price];
                    OrderGoods::whereIn('order_id', $order_id_list)->update($data);

                    /* 查询订单商品总额 */
                    $res = OrderGoods::selectRaw('order_id,SUM(goods_number * goods_price) AS goods_amount');
                    $res = $res->whereIn('order_id', $order_id_list)
                        ->groupBy('order_id');
                    $res = $this->baseRepository->getToArrayGet($res);

                    foreach ($res as $row) {
                        $order_id = $row['order_id'];
                        $goods_amount = floatval($row['goods_amount']);

                        /* 取得订单信息 */
                        $order = order_info($order_id);

                        /* 判断订单是否有效：余额支付金额 + 已付款金额 >= 保证金 */
                        if ($order['surplus'] + $order['money_paid'] >= $group_buy['deposit']) {
                            /* 有效，设为已确认，更新订单 */

                            // 更新商品总额
                            $order['goods_amount'] = $goods_amount;

                            // 如果保价，重新计算保价费用
                            if ($order['insure_fee'] > 0) {
                                $shipping = shipping_info($order['shipping_id']);
                                $order['insure_fee'] = shipping_insure_fee($shipping['shipping_code'], $goods_amount, $shipping['insure']);
                            }

                            // 重算支付费用
                            $order['order_amount'] = $order['goods_amount'] + $order['shipping_fee']
                                + $order['insure_fee'] + $order['pack_fee'] + $order['card_fee']
                                - $order['money_paid'] - $order['surplus'];
                            if ($order['order_amount'] > 0) {
                                $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount']);
                            } else {
                                $order['pay_fee'] = 0;
                            }

                            // 计算应付款金额
                            $order['order_amount'] += $order['pay_fee'];

                            // 计算付款状态
                            if ($order['order_amount'] > 0) {
                                $order['pay_status'] = PS_UNPAYED;
                                $order['pay_time'] = 0;
                            } else {
                                $order['pay_status'] = PS_PAYED;
                                $order['pay_time'] = gmtime();
                            }

                            // 如果需要退款，退到帐户余额
                            if ($order['order_amount'] < 0) {
                                // todo （现在手工退款）
                            }

                            // 订单状态
                            $order['order_status'] = OS_CONFIRMED;
                            $order['confirm_time'] = gmtime();
                            $order['add_time'] = gmtime();

                            // 更新订单
                            $order = addslashes_deep($order);
                            update_order($order_id, $order);
                        } else {
                            /* 无效，取消订单，退回已付款 */

                            // 修改订单状态为已取消，付款状态为未付款
                            $order['order_status'] = OS_CANCELED;
                            $order['to_buyer'] = $GLOBALS['_LANG']['cancel_order_reason'];
                            $order['pay_status'] = PS_UNPAYED;
                            $order['pay_time'] = 0;

                            /* 如果使用余额或有已付款金额，退回帐户余额 */
                            $money = $order['surplus'] + $order['money_paid'];
                            if ($money > 0) {
                                $order['surplus'] = 0;
                                $order['money_paid'] = 0;
                                $order['order_amount'] = $money;

                                // 退款到帐户余额
                                order_refund($order, 1, $GLOBALS['_LANG']['cancel_order_reason'] . ':' . $order['order_sn']);
                            }

                            /* 更新订单 */
                            $order = addslashes_deep($order);
                            update_order($order['order_id'], $order);
                        }
                    }
                }

                /* 修改团购活动状态为成功 */
                $data = ['is_finished' => GBS_SUCCEED];
                GoodsActivity::where('act_id', $group_buy_id)->update($data);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'group_buy.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['fail'])) {

                /* 设置活动失败 */

                /* 判断订单状态 */
                if ($group_buy['status'] != GBS_FINISHED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 如果有有效订单，取消订单 */
                if ($group_buy['valid_order'] > 0) {
                    /* 查找未确认或已确认的订单 */
                    $res = OrderInfo::where('extension_code', 'group_buy')->where('extension_id', $group_buy_id);
                    $res = $res->where(function ($query) {
                        $query->where('order_status', OS_CONFIRMED)->orWhere('order_status', OS_UNCONFIRMED);
                    });
                    $res = $this->baseRepository->getToArrayGet($res);
                    foreach ($res as $order) {
                        // 修改订单状态为已取消，付款状态为未付款
                        $order['order_status'] = OS_CANCELED;
                        $order['to_buyer'] = $GLOBALS['_LANG']['cancel_order_reason'];
                        $order['pay_status'] = PS_UNPAYED;
                        $order['pay_time'] = 0;

                        // - 订单如果使用了余额 退余额
                        $surplus = empty($order['surplus']) ? 0 : $order['surplus'];
                        if ($surplus > 0) {
                            $order['surplus'] = 0;
                            $order['money_paid'] = empty($order['money_paid']) ? 0 : $order['money_paid'] - $surplus;
                            $order['order_amount'] = $surplus;
                            // 退款到账户余额
                            order_refund($order, 1, $GLOBALS['_LANG']['cancel_order_reason'] . ':' . $order['order_sn'], $surplus);
                        }

                        // - 订单在线支付部分 原路退款
                        $money_paid = empty($order['money_paid']) ? 0 : $order['money_paid'];
                        if ($money_paid > 0) {
                            $order['money_paid'] = 0;
                            $order['order_amount'] = $order['order_amount'] + $money_paid;
                            // 原路退款
                            $refundOrder = [
                                'order_id' => $order['order_id'],
                                'pay_id' => $order['pay_id'],
                                'pay_status' => $order['pay_status'],
                                'referer' => $order['referer'],
                                'return_sn' => $order['order_sn'],
                            ];
                            $this->orderRefoundService->refoundPay($refundOrder, $money_paid);
                        }

                        // - 订单使用了储值卡 退储值卡
                        $use_val = $this->orderRefoundService->returnValueCardMoney($order['order_id']);
                        if ($use_val > 0) {
                            $order['order_amount'] = $order['order_amount'] + $use_val;
                        }

                        /* 更新订单 */
                        $order = addslashes_deep($order);
                        update_order($order['order_id'], $order);
                    }
                }

                /* 修改团购活动状态为失败，记录失败原因（活动说明） */
                $act_desc = isset($_POST['act_desc']) ? $_POST['act_desc'] : '';
                $data = [
                    'is_finished' => GBS_FAIL,
                    'act_desc' => $act_desc
                ];
                GoodsActivity::where('act_id', $group_buy_id)->update($data);
                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'group_buy.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['mail'])) {
                /* 发送通知邮件 */

                /* 判断订单状态 */
                if ($group_buy['status'] != GBS_SUCCEED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 取得邮件模板 */
                $tpl = get_mail_template('group_buy');

                /* 初始化订单数和成功发送邮件数 */
                $count = 0;
                $send_count = 0;

                /* 取得有效订单 */
                $res = OrderInfo::where('extension_code', 'group_buy')
                    ->where('extension_id', $group_buy_id)
                    ->where('order_status', OS_CONFIRMED);
                $res = $res->whereHas('getOrderGoods');
                $res = $res->with(['getOrderGoods' => function ($query) {
                    $query->select('order_id', 'goods_number');
                }]);
                $res = $res->whereHas('getUsers');
                $res = $res->with(['getUsers' => function ($query) {
                    $query->select('user_id', 'email');
                }]);
                $res = $this->baseRepository->getToArrayGet($res);
                foreach ($res as $order) {
                    $order['email'] = '';
                    $order['goods_number'] = 0;
                    if (isset($order['get_users']) && !empty($order['get_users'])) {
                        $order['email'] = $order['get_users']['email'];
                    }
                    if (!empty($order['email'])) {
                        if (isset($order['get_order_goods']) && !empty($order['get_order_goods'])) {
                            $order['goods_number'] = $order['get_order_goods']['goods_number'];
                        }

                        /* 邮件模板赋值 */
                        $this->smarty->assign('consignee', $order['consignee']);
                        $this->smarty->assign('add_time', local_date($GLOBALS['_CFG']['time_format'], $order['add_time']));
                        $this->smarty->assign('goods_name', $group_buy['goods_name']);
                        $this->smarty->assign('goods_number', $order['goods_number']);
                        $this->smarty->assign('order_sn', $order['order_sn']);
                        $this->smarty->assign('order_amount', price_format($order['order_amount']));
                        $this->smarty->assign('shop_url', $this->dsc->url() . 'user_order.php?act=order_detail&order_id=' . $order['order_id']);
                        $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
                        $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));

                        /* 取得模板内容，发邮件 */
                        $content = $this->smarty->fetch('str:' . $tpl['template_content']);
                        if ($this->commonRepository->sendEmail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html'])) {
                            $send_count++;
                        }
                    }

                    $count++;
                }

                /* 提示信息 */
                return sys_msg(sprintf($GLOBALS['_LANG']['mail_result'], $count, $send_count));
            } else {
                /* 保存团购信息 */
                $goods_id = intval($_POST['goods_id']);
                if ($goods_id <= 0) {
                    return sys_msg($GLOBALS['_LANG']['error_goods_null']);
                }
                $info = $this->groupBuyManageService->goodsGroupBuy($goods_id);
                if ($info && $info['act_id'] != $group_buy_id) {
                    return sys_msg($GLOBALS['_LANG']['error_goods_exist']);
                }

                $goods_name = Goods::where('goods_id', $goods_id)->value('goods_name');
                $goods_name = $goods_name ? $goods_name : '';

                $act_name = empty($_POST['act_name']) ? $goods_name : $this->dscRepository->subStr($_POST['act_name'], 0, 255, false);

                $deposit = floatval($_POST['deposit']);
                if ($deposit < 0) {
                    $deposit = 0;
                }

                $restrict_amount = intval($_POST['restrict_amount']);
                if ($restrict_amount < 0) {
                    $restrict_amount = 0;
                }

                $gift_integral = intval($_POST['gift_integral']);
                if ($gift_integral < 0) {
                    $gift_integral = 0;
                }

                $price_ladder = [];
                $count = count($_POST['ladder_amount']);
                for ($i = $count - 1; $i >= 0; $i--) {
                    /* 如果数量小于等于0，不要 */
                    $amount = intval($_POST['ladder_amount'][$i]);
                    if ($amount <= 0) {
                        continue;
                    }

                    /* 如果价格小于等于0，不要 */
                    $price = round(floatval($_POST['ladder_price'][$i]), 2);
                    if ($price <= 0) {
                        continue;
                    }

                    /* 加入价格阶梯 */
                    $price_ladder[$amount] = ['amount' => $amount, 'price' => $price];
                }

                //如果保证金为0，并且阶梯价格没有数量为1的情况下，创建数量为1，价格为商品市场价的阶梯等级
                if ($deposit == 0) {
                    //判断阶梯价格中是否有数量为一的,没有则插入
                    if (!in_array(1, $_POST['ladder_amount'])) {
                        $amount = 1;
                        $price = Goods::where('goods_id', $goods_id)->value('market_price');
                        $price = $price ? $price : 0;

                        /* 加入价格阶梯 */
                        $price_ladder[$amount] = ['amount' => $amount, 'price' => $price];
                    }
                }

                if (count($price_ladder) < 1) {
                    return sys_msg($GLOBALS['_LANG']['error_price_ladder']);
                }

                /* 限购数量不能小于价格阶梯中的最大数量 */
                $amount_list = array_keys($price_ladder);
                if ($restrict_amount > 0 && max($amount_list) > $restrict_amount) {
                    return sys_msg($GLOBALS['_LANG']['error_restrict_amount']);
                }

                ksort($price_ladder);
                $price_ladder = array_values($price_ladder);

                /* 检查开始时间和结束时间是否合理 */
                $start_time = local_strtotime($_POST['start_time']);
                $end_time = local_strtotime($_POST['end_time']);
                if ($start_time >= $end_time) {
                    return sys_msg($GLOBALS['_LANG']['invalid_time']);
                }
                $is_hot = isset($_REQUEST['is_hot']) ? $_REQUEST['is_hot'] : 0;
                $is_new = isset($_REQUEST['is_new']) ? $_REQUEST['is_new'] : 0;
                $group_buy = [
                    'act_name' => $act_name,
                    'act_desc' => $_POST['act_desc'] ?? '',
                    'act_type' => GAT_GROUP_BUY,
                    'goods_id' => $goods_id,
                    'goods_name' => $goods_name,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'review_status' => 3,
                    'is_hot' => $is_hot,
                    'is_new' => $is_new,
                    'ext_info' => serialize([
                        'price_ladder' => $price_ladder,
                        'restrict_amount' => $restrict_amount,
                        'gift_integral' => $gift_integral,
                        'deposit' => $deposit
                    ])
                ];

                /* 清除缓存 */
                clear_cache_files();

                /* 保存数据 */
                if ($group_buy_id > 0) {
                    if (isset($_POST['review_status'])) {
                        $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                        $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                        $group_buy['review_status'] = $review_status;
                        $group_buy['review_content'] = $review_content;
                    }

                    /* update */
                    GoodsActivity::where('act_id', $group_buy_id)
                        ->where('act_type', GAT_GROUP_BUY)
                        ->update($group_buy);

                    /* log */
                    admin_log(addslashes($goods_name) . '[' . $group_buy_id . ']', 'edit', 'group_buy');

                    /* todo 更新活动表 */

                    /* 提示信息 */
                    $links = [
                        ['href' => 'group_buy.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_list']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
                } else {
                    $group_buy['user_id'] = $adminru['ru_id']; //ecmoban模板堂 --zhuo
                    /* insert */
                    GoodsActivity::insert($group_buy);
                    /* log */
                    admin_log(addslashes($goods_name), 'add', 'group_buy');

                    /* 提示信息 */
                    $links = [
                        ['href' => 'group_buy.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add']],
                        ['href' => 'group_buy.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['add_success'], 0, $links);
                }
            }
        }


        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg(lang('admin/group_buy.empty_data'), 1);
            }
            $ids = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
            $del_count = count($_POST['checkboxes']);

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    /* 取得团购活动信息 */

                    foreach ($_POST['checkboxes'] as $key => $id) {
                        /* 取得团购活动信息 */

                        $where = [
                            'group_buy_id' => $id,
                            'path' => 'seller'
                        ];
                        $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

                        /* 如果团购活动已经有订单，不能删除 */
                        if (isset($group_buy['valid_order']) && $group_buy['valid_order'] > 0) {
                            continue;
                        }

                        /* 删除团购活动 */
                        GoodsActivity::where('act_id', $id)->delete();

                        if (isset($group_buy['goods_name'])) {
                            admin_log(addslashes($group_buy['goods_name']) . '[' . $id . ']', 'remove', 'group_buy');
                        }
                    }

                    /* 如果删除了团购活动，清除缓存 */
                    if ($del_count > 0) {
                        clear_cache_files();
                    }

                    $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'group_buy.php?act=list'];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $del_count), 0, $links);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];
                    $review_content = !empty($_POST['review_content']) ? trim($_POST['review_content']) : '';

                    $ids = $this->baseRepository->getExplode($ids);
                    $data = ['review_status' => $review_status];
                    $res = GoodsActivity::whereIn('act_id', $ids)->update($data);

                    if ($res > 0) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'group_buy.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg(lang('admin/group_buy.audit_success'), 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索单条商品信息
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'group_goods') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $filter = dsc_decode($_GET['JSON']);

            $where = [
                'goods_id' => $filter->goods_id
            ];
            $arr = $this->goodsService->getGoodsInfo($where);

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $filter = dsc_decode($_GET['JSON']);
            $arr = get_goods_list($filter);

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 编辑保证金
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_deposit') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            $ext_info = GoodsActivity::where('act_id', $id)->where('act_type', GAT_GROUP_BUY)->value('ext_info');
            $ext_info = $ext_info ? $ext_info : '';
            $ext_info = unserialize($ext_info);
            $ext_info['deposit'] = $val;

            $data = ['ext_info' => serialize($ext_info)];
            GoodsActivity::where('act_id', $id)->update($data);

            clear_cache_files();

            return make_json_result(number_format($val, 2));
        }

        /*------------------------------------------------------ */
        //-- 编辑限购
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_restrict_amount') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $ext_info = GoodsActivity::where('act_id', $id)->where('act_type', GAT_GROUP_BUY)->value('ext_info');
            $ext_info = unserialize($ext_info);
            $ext_info['restrict_amount'] = $val;

            $data = ['ext_info' => serialize($ext_info)];
            GoodsActivity::where('act_id', $id)->update($data);

            clear_cache_files();

            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 删除团购活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('group_by');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 取得团购活动信息 */

            $where = [
                'group_buy_id' => $id,
                'path' => 'seller'
            ];
            $group_buy = $this->groupBuyService->getGroupBuyInfo($where);

            /* 如果团购活动已经有订单，不能删除 */
            if (isset($group_buy['valid_order']) && $group_buy['valid_order'] > 0) {
                return make_json_error($GLOBALS['_LANG']['error_exist_order']);
            }

            /* 删除团购活动 */
            GoodsActivity::where('act_id', $id)->delete();

            if (isset($group_buy['goods_name'])) {
                admin_log(addslashes($group_buy['goods_name']) . '[' . $id . ']', 'remove', 'group_buy');
            }

            clear_cache_files();

            $url = 'group_buy.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }
}
