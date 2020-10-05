<?php

namespace App\Modules\Admin\Controllers;


use App\Models\Goods;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\PresaleActivity;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\PresaleService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Presale\PresaleManageService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心预售商品管理
 */
class PresaleController extends InitController
{
    protected $presaleService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $commonRepository;
    protected $baseRepository;
    protected $presaleManageService;
    protected $storeCommonService;

    public function __construct(
        DscRepository $dscRepository,
        PresaleService $presaleService,
        MerchantCommonService $merchantCommonService,
        CommonRepository $commonRepository,
        BaseRepository $baseRepository,
        PresaleManageService $presaleManageService,
        StoreCommonService $storeCommonService
    )
    {
        $this->presaleService = $presaleService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
        $this->baseRepository = $baseRepository;
        $this->presaleManageService = $presaleManageService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('goods');
        load_helper('order');
        load_helper('goods', 'admin');

        /* 检查权限 */
        admin_priv('presale');

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

        /*------------------------------------------------------ */
        //-- 预售活动列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['presale_list']);
            $this->smarty->assign('action_link', ['href' => 'presale.php?act=add', 'text' => $GLOBALS['_LANG']['add_presale']]);

            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('presale_cat_link', ['href' => 'presale_cat.php?act=list', 'text' => $GLOBALS['_LANG']['presale_cat_list']]);
            }

            $list = $this->presaleManageService->presaleList($adminru['ru_id']);

            $this->smarty->assign('presale_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            /* 显示商品列表页面 */

            return $this->smarty->display('presale_list.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $list = $this->presaleManageService->presaleList($adminru['ru_id']);

            $this->smarty->assign('presale_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('presale_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑预售活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 初始化/取得预售活动信息 */
            if ($_REQUEST['act'] == 'add') {
                $presale = [
                    'cat_id' => 0,
                    'act_desc' => '',
                    'start_time' => local_date('Y-m-d H:i:s', gmtime() + 86400),
                    'end_time' => local_date('Y-m-d H:i:s', gmtime() + 4 * 86400),
                    'pay_start_time' => local_date('Y-m-d H:i:s', gmtime() + 4 * 86400 + 1),
                    'pay_end_time' => local_date('Y-m-d H:i:s', gmtime() + 6 * 86400)
                ];

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_presale']);
                $this->smarty->assign('form_action', "insert");
            } else {
                $presale_id = intval($_REQUEST['id']);
                if ($presale_id <= 0) {
                    return 'invalid param';
                }

                $presale = $this->presaleService->presaleInfo($presale_id, 0, 0, "seller");
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_presale']);
                $this->smarty->assign('form_action', "update");
            }

            if (empty($presale)) {
                return sys_msg($GLOBALS['_LANG']['error_presale'], 1);
            }

            $this->smarty->assign('presale', $presale);

            $act_desc = isset($presale['act_desc']) ? $presale['act_desc'] : '';

            /* 创建 html editor */
            create_html_editor2('act_desc', 'act_desc', $act_desc);

            /* 模板赋值 */
            $this->smarty->assign('action_link', $this->presaleManageService->listLink($_REQUEST['act'] == 'add'));

            $cat_select = isset($presale['cat_id']) ? presale_cat_list(0, $presale['cat_id'], false, 0, true, '', 1) : '';

            /* 缩进分类 */
            if ($cat_select) {
                foreach ($cat_select as $k => $v) {
                    if ($v['level']) {
                        $level = '';
                        for ($i = 0; $i < $v['level']; $i++) {
                            $level .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                        }
                        $cat_select[$k]['name'] = $level . $v['name'];
                    }
                }
            }

            $this->smarty->assign('cat_select', $cat_select);

            set_default_filter(); //设置默认筛选
            $presale['ru_id'] = isset($presale['ru_id']) && !empty($presale['ru_id']) ? $presale['ru_id'] : '';
            $this->smarty->assign('ru_id', $presale['ru_id']);

            /* 显示模板 */

            return $this->smarty->display('presale_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑预售活动的提交
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert_update') {

            /* 取得预售活动id */
            $presale_id = intval($_POST['act_id']);
            if (isset($_POST['finish']) || isset($_POST['succeed']) || isset($_POST['fail']) || isset($_POST['mail'])) {
                if ($presale_id <= 0) {
                    return sys_msg($GLOBALS['_LANG']['error_presale'], 1);
                }
                $presale = $this->presaleService->presaleInfo($presale_id, 0, 0, "seller");
                if (empty($presale)) {
                    return sys_msg($GLOBALS['_LANG']['error_presale'], 1);
                }
            }

            if (isset($_POST['finish'])) {
                /* 判断订单状态 */
                if ($presale['status'] != GBS_UNDER_WAY) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 结束预售活动，修改结束时间为当前时间 */
                $data = ['end_time' => gmtime()];
                PresaleActivity::where('act_id', $presale_id)->update($data);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'presale.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['succeed'])) {
                /* 设置活动成功 */

                /* 判断订单状态 */
                if ($presale['status'] != GBS_FINISHED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 如果有订单，更新订单信息 */
                if ($presale['total_order'] > 0) {
                    /* 查找该预售活动的已确认或未确认订单（已取消的就不管了） */
                    $res = OrderInfo::select('order_id')
                        ->where('extension_code', 'presale')
                        ->where('extension_id', $presale_id)
                        ->where(function ($query) {
                            $query->where('order_status', OS_CONFIRMED)
                                ->orWhere('order_status', OS_UNCONFIRMED);
                        });
                    $order_id_list = $this->baseRepository->getToArrayGet($res);
                    $order_id_list = $this->baseRepository->getFlatten($order_id_list);


                    /* 更新订单商品价 */
                    $final_price = $presale['trans_price'];
                    $data = ['goods_price' => $final_price];
                    OrderGoods::whereIn('order_id', $order_id_list)->update($data);

                    /* 查询订单商品总额 */
                    $res = OrderGoods::selectRaw('order_id, SUM(goods_number * goods_price) AS goods_amount')
                        ->whereIn('order_id', $order_id_list)
                        ->groupBy('order_id');
                    $res = $this->baseRepository->getToArrayGet($res);

                    foreach ($res as $row) {
                        $order_id = $row['order_id'];
                        $goods_amount = floatval($row['goods_amount']);

                        /* 取得订单信息 */
                        $order = order_info($order_id);

                        /* 判断订单是否有效：余额支付金额 + 已付款金额 >= 保证金 */
                        if ($order['surplus'] + $order['money_paid'] >= $presale['deposit']) {
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

                /* 修改预售活动状态为成功 */
                $data = ['is_finished' => GBS_SUCCEED];
                PresaleActivity::where('act_id', $presale_id)->update($data);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'presale.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['fail'])) {
                /* 设置活动失败 */

                /* 判断订单状态 */
                if ($presale['status'] != GBS_FINISHED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 如果有有效订单，取消订单 */
                if ($presale['valid_order'] > 0) {
                    /* 查找未确认或已确认的订单 */
                    $res = OrderInfo::where('extension_code', 'presale')
                        ->where('extension_id', $presale_id)
                        ->where(function ($query) {
                            $query->where('order_status', OS_CONFIRMED)
                                ->orWhere('order_status', OS_UNCONFIRMED);
                        });
                    $res = $this->baseRepository->getToArrayGet($res);

                    foreach ($res as $order) {
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
                            order_refund($order, 1, $GLOBALS['_LANG']['cancel_order_reason'] . ':' . $order['order_sn'], $money);
                        }

                        /* 更新订单 */
                        $order = addslashes_deep($order);
                        update_order($order['order_id'], $order);
                    }
                }

                /* 修改预售活动状态为失败，记录失败原因（活动说明） */
                $data = [
                    'is_finished' => GBS_FAIL,
                    'act_desc' => $_POST['act_desc']
                ];
                PresaleActivity::where('act_id', $presale_id)->update($data);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $links = [
                    ['href' => 'presale.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } elseif (isset($_POST['mail'])) {
                /* 发送通知邮件 */

                /* 判断订单状态 */
                if ($presale['status'] != GBS_SUCCEED) {
                    return sys_msg($GLOBALS['_LANG']['error_status'], 1);
                }

                /* 取得邮件模板 */
                $tpl = get_mail_template('presale');

                /* 初始化订单数和成功发送邮件数 */
                $count = 0;
                $send_count = 0;

                /* 取得有效订单 */
                $res = OrderInfo::where('extension_code', 'presale')
                    ->where('extension_id', $presale_id)
                    ->where('order_status', OS_CONFIRMED);
                $res = $res->with(['getOrderGoods']);
                $res = $this->baseRepository->getToArrayGet($res);

                foreach ($res as $order) {
                    if (!empty($order['email'])) {
                        $order['goods_number'] = $order['get_order_goods']['goods_number'] ?? 0;
                        /* 邮件模板赋值 */
                        $this->smarty->assign('consignee', $order['consignee']);
                        $this->smarty->assign('add_time', local_date($GLOBALS['_CFG']['time_format'], $order['add_time']));
                        $this->smarty->assign('goods_name', $presale['goods_name']);
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
                /* 保存预售信息 */
                $goods_id = intval($_POST['goods_id']);
                if ($goods_id <= 0) {
                    return sys_msg($GLOBALS['_LANG']['error_goods_null']);
                }

                $info = $this->presaleManageService->goodsPresale($goods_id);
                if ($info && $info['act_id'] != $presale_id) {
                    return sys_msg($GLOBALS['_LANG']['error_goods_exist']);
                }

                $goods_name = Goods::where('goods_id', $goods_id)->value('goods_name');
                $goods_name = $goods_name ? $goods_name : '';

                $act_name = empty($_POST['act_name']) ? $goods_name : $this->dscRepository->subStr($_POST['act_name'], 0, 255, false);

                $deposit = floatval($_POST['deposit']);

                if ($deposit < 0) {
                    $deposit = 0;
                }

                /* 检查开始时间和结束时间是否合理 */
                $start_time = local_strtotime($_POST['start_time']);
                $end_time = local_strtotime($_POST['end_time']);
                $pay_start_time = local_strtotime($_POST['pay_start_time']);//liu
                $pay_end_time = local_strtotime($_POST['pay_end_time']);//liu
                if ($start_time >= $end_time || $pay_start_time >= $pay_end_time || $end_time > $pay_start_time) {//change liu
                    return sys_msg($GLOBALS['_LANG']['invalid_time']);
                }

                $presale = [
                    'act_name' => $act_name,
                    'act_desc' => !empty($_POST['act_desc']) ? trim($_POST['act_desc']) : '',
                    'cat_id' => intval($_POST['cat_id']),
                    'goods_id' => $goods_id,
                    'goods_name' => $goods_name,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'pay_start_time' => $pay_start_time,//liu
                    'pay_end_time' => $pay_end_time,//liu
                    'deposit' => $deposit
                ];

                /* 清除缓存 */
                clear_cache_files();

                /* 保存数据 */
                if ($presale_id > 0) {
                    if (isset($_POST['review_status'])) {
                        $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                        $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                        $presale['review_status'] = $review_status;
                        $presale['review_content'] = $review_content;
                    }

                    /* update */
                    PresaleActivity::where('act_id', $presale_id)->update($presale);

                    /* log */
                    admin_log(addslashes($goods_name) . '[' . $presale_id . ']', 'edit', 'presale');

                    /* 提示信息 */
                    $links = [
                        ['href' => 'presale.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_list']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
                } else {
                    $presale['review_status'] = 3;
                    $presale['user_id'] = $adminru['ru_id'];

                    /* insert */
                    PresaleActivity::insert($presale);

                    /* log */
                    admin_log(addslashes($goods_name), 'add', 'presale');

                    /* 提示信息 */
                    $links = [
                        ['href' => 'presale.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add']],
                        ['href' => 'presale.php?act=list', 'text' => $GLOBALS['_LANG']['back_list']]
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
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }
            $ids = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    $del_count = 0; //初始化删除数量
                    foreach ($ids as $key => $id) {
                        /* 取得预售活动信息 */
                        $presale = $this->presaleService->presaleInfo($id, 0, 0, "seller");

                        /* 如果预售活动已经有订单，不能删除 */
                        if (empty($presale) || $presale['valid_order'] <= 0) {
                            /* 删除预售活动 */
                            PresaleActivity::where('act_id', $id)->delete();

                            $goods_name = isset($presale['goods_name']) ? addslashes($presale['goods_name']) : '';
                            admin_log($goods_name . '[' . $id . ']', 'remove', 'presale');
                            $del_count++;
                        }
                    }

                    /* 如果删除了预售活动，清除缓存 */
                    if ($del_count > 0) {
                        clear_cache_files();
                    }

                    $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'presale.php?act=list'];
                    return sys_msg(sprintf($GLOBALS['_LANG']['batch_drop_success'], $del_count), 0, $links);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];

                    $ids = $this->baseRepository->getExplode($ids);
                    $data = ['review_status' => $review_status];
                    $res = PresaleActivity::whereIn('act_id', $ids)->update($data);

                    if ($res > 0) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'presale.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['integral_goods_adopt_status_success'], 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $filter = dsc_decode($_GET['JSON']);
            $arr = get_goods_list($filter);

            return make_json_result($arr);
        }

        /*------------------------------------------------------ */
        //-- 获取本店价
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'get_price') {
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = request()->get('goods_id', 0);
            $shop_price = $this->presaleManageService->getShopPrice(intval($goods_id));
            return make_json_result($shop_price);
        }

        /*------------------------------------------------------ */
        //-- 编辑保证金
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit_deposit') {
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            $data = ['deposit' => $val];
            PresaleActivity::where('act_id', $id)->update($data);

            clear_cache_files();

            return make_json_result(number_format($val, 2));
        }

        /*------------------------------------------------------ */
        //-- 删除预售活动
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('presale');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 取得预售活动信息 */
            $presale = $this->presaleService->presaleInfo($id, 0, 0, "seller");

            /* 如果预售活动已经有订单，不能删除 */
            if (!empty($presale) && $presale['valid_order'] > 0) {
                return make_json_error($GLOBALS['_LANG']['error_exist_order']);
            }

            /* 删除预售活动 */
            PresaleActivity::where('act_id', $id)->delete();

            $goods_name = isset($presale['goods_name']) ? addslashes($presale['goods_name']) : '';
            admin_log($goods_name . '[' . $id . ']', 'remove', 'presale');

            clear_cache_files();

            $url = 'presale.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }
}
