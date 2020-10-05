<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Goods;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\AuctionService;
use App\Services\Activity\BonusService;
use App\Services\Activity\CouponsService;
use App\Services\Activity\SnatchService;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\CommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Payment\PaymentService;
use App\Services\User\UserCommonService;
use App\Services\User\UserOrderService;

class UserActivityController extends InitController
{
    protected $dscRepository;
    protected $userCommonService;
    protected $articleCommonService;
    protected $commonRepository;
    protected $commonService;
    protected $config;
    protected $crowdFundService;
    protected $snatchService;
    protected $bonusService;
    protected $auctionService;
    protected $couponsService;
    protected $merchantCommonService;
    protected $userOrderService;
    protected $paymentService;

    public function __construct(
        DscRepository $dscRepository,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        CommonRepository $commonRepository,
        CommonService $commonService,
        SnatchService $snatchService,
        BonusService $bonusService,
        AuctionService $auctionService,
        CouponsService $couponsService,
        MerchantCommonService $merchantCommonService,
        UserOrderService $userOrderService,
        PaymentService $paymentService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->userCommonService = $userCommonService;
        $this->articleCommonService = $articleCommonService;
        $this->commonRepository = $commonRepository;
        $this->commonService = $commonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->snatchService = $snatchService;
        $this->bonusService = $bonusService;
        $this->auctionService = $auctionService;
        $this->couponsService = $couponsService;
        $this->merchantCommonService = $merchantCommonService;
        $this->userOrderService = $userOrderService;
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $this->dscRepository->helpersLang(['user']);

        $user_id = session('user_id', 0);
        $action = addslashes(request()->input('act', 'default'));
        $action = $action ? $action : 'default';

        $not_login_arr = $this->userCommonService->notLoginArr();

        $ui_arr = $this->userCommonService->uiArr('activity');

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
        //-- 活动中心-夺宝奇兵列表
        /* ------------------------------------------------------ */
        if ($action == 'snatch_list') {
            $page = (int)request()->input('page', 1);
            //获取会员的全部夺宝奇兵
            $all_snatch = $this->snatchService->getAllSnatch($user_id);
            $this->smarty->assign('all_snatch', $all_snatch);

            //获取进行中的拍卖
            $is_going = $this->snatchService->getAllSnatch($user_id, 1);
            $this->smarty->assign('is_going', $is_going);

            //获取结束的拍卖
            $is_finished = $this->snatchService->getAllSnatch($user_id, 2);
            $this->smarty->assign('is_finished', $is_finished);

            //获取全部拍卖列表
            $snatch_list = $this->snatchService->getSnatchGoodsList($user_id, $all_snatch, $page);

            $this->smarty->assign('snatch_list', $snatch_list);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 夺宝奇兵列表
        /* ------------------------------------------------------ */
        elseif ($action == 'snatch_to_query') {
            $snatch = strip_tags(urldecode(request()->input('snatch', '')));
            $snatch = json_str_iconv($snatch);
            $result = ['error' => 0, 'message' => '', 'content' => '', 'order_id' => ''];

            $snatch = dsc_decode($snatch);
            $snatch->keyword = addslashes(trim($snatch->keyword));

            $page = (int)request()->input('page', 1);

            $all_snatch = $this->snatchService->getAllSnatch($user_id, $snatch);
            $snatch_list = $this->snatchService->getSnatchGoodsList($user_id, $all_snatch, $page, $snatch);

            $date_keyword = '';
            $status_keyword = '';

            if ($snatch->idTxt == 'submitDate') {
                $date_keyword = $snatch->keyword;
                $status_keyword = $snatch->status_keyword;
            } elseif ($snatch->idTxt == 'status_list') {
                $date_keyword = $snatch->date_keyword;
                $status_keyword = $snatch->keyword;
            } elseif ($snatch->idTxt == 'payId' || $snatch->idTxt == 'to_finished' || $snatch->idTxt == 'to_confirm_order' || $snatch->idTxt == 'to_unconfirmed' || $snatch->idTxt == 'signNum') {
                $status_keyword = $snatch->keyword;
            }

            $result['date_keyword'] = $date_keyword;
            $result['status_keyword'] = $status_keyword;
            $this->smarty->assign('snatch_list', $snatch_list);
            $this->smarty->assign('date_keyword', $date_keyword);
            $this->smarty->assign('status_keyword', $status_keyword);

            $insert_arr = [
                'act' => $snatch->action,
                'filename' => 'user'
            ];

            $this->smarty->assign('no_records', insert_get_page_no_records($insert_arr));
            $this->smarty->assign('open_delivery_time', $this->config['open_delivery_time']);
            $this->smarty->assign('action', $snatch->action);

            $result['content'] = $this->smarty->fetch("library/user_snatch_list.lbi");

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 我的红包列表
        /* ------------------------------------------------------ */
        elseif ($action == 'bonus') {
            load_helper('transaction');

            $page = (int)request()->input('page', 1);
            $size = 10;

            $bonus = $this->bonusService->getUserBounsNewList($user_id, $page, 0, 'bouns_available_gotoPage', 0, $size);
            $this->smarty->assign('bonus', $bonus); //可用

            $bonus1 = $this->bonusService->getUserBounsNewList($user_id, $page, 1, 'bouns_expire_gotoPage', 0, $size);
            $this->smarty->assign('bonus1', $bonus1); //即将到期

            $bonus2 = $this->bonusService->getUserBounsNewList($user_id, $page, 2, 'bouns_useup_gotoPage', 0, $size);
            $this->smarty->assign('bonus2', $bonus2); //已使用

            $bonus3 = $this->bonusService->getUserBounsNewList($user_id, $page, 3, 'bouns_invalid__gotoPage', 0, $size);
            $this->smarty->assign('bonus3', $bonus3); //已过期

            $this->smarty->assign('size', $size);

            $bonus3 = $this->bonusService->getUserBounsNewList($user_id, $page, 0, '', 1);
            $bouns_amount = $this->bonusService->getBounsAmountList($bonus3);
            $this->smarty->assign('bouns_amount', $bouns_amount);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 活动中心-拍卖活动列表
        /* ------------------------------------------------------ */
        elseif ($action == 'auction_list') {
            $page = (int)request()->input('page', 1);
            //获取会员竞拍的全部拍卖
            $all_auction = $this->auctionService->getAllAuction($user_id);
            $this->smarty->assign('all_auction', $all_auction);

            //获取进行中的拍卖
            $is_going = $this->auctionService->getAllAuction($user_id, 1);
            $this->smarty->assign('is_going', $is_going);

            //获取结束的拍卖
            $is_finished = $this->auctionService->getAllAuction($user_id, 2);
            $this->smarty->assign('is_finished', $is_finished);

            //获取全部拍卖列表
            $auction_list = $this->auctionService->getAuctionGoodsList($user_id, $all_auction, $page);

            $this->smarty->assign('auction_list', $auction_list);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 查询拍卖列表
        /* ------------------------------------------------------ */
        elseif ($action == 'auction_to_query') {
            $auction = strip_tags(urldecode(request()->input('auction', '')));
            $auction = json_str_iconv($auction);
            $result = ['error' => 0, 'message' => '', 'content' => '', 'order_id' => ''];

            $page = (int)request()->input('page', 1);

            $auction = dsc_decode($auction);
            $auction->keyword = addslashes(trim($auction->keyword));

            $all_auction = $this->auctionService->getAllAuction($user_id, $auction);
            $auction_list = $this->auctionService->getAuctionGoodsList($user_id, $all_auction, $page, $auction);

            $date_keyword = '';
            $status_keyword = '';

            if ($auction->idTxt == 'submitDate') {
                $date_keyword = $auction->keyword;
                $status_keyword = isset($auction->status_keyword) ? $auction->status_keyword : '';
            } elseif ($auction->idTxt == 'status_list') {
                $date_keyword = isset($auction->date_keyword) ? $auction->date_keyword : '';
                $status_keyword = $auction->keyword;
            } elseif ($auction->idTxt == 'payId' || $auction->idTxt == 'to_finished' || $auction->idTxt == 'to_confirm_order' || $auction->idTxt == 'to_unconfirmed' || $auction->idTxt == 'signNum') {
                $status_keyword = $auction->keyword;
            }

            $result['date_keyword'] = $date_keyword;
            $result['status_keyword'] = $status_keyword;
            $this->smarty->assign('auction_list', $auction_list);
            $this->smarty->assign('date_keyword', $date_keyword);
            $this->smarty->assign('status_keyword', $status_keyword);

            $insert_arr = [
                'act' => $auction->action,
                'filename' => 'user'
            ];

            $this->smarty->assign('no_records', insert_get_page_no_records($insert_arr));
            $this->smarty->assign('open_delivery_time', $this->config['open_delivery_time']);
            $this->smarty->assign('action', $auction->action);

            $result['content'] = $this->smarty->fetch("library/user_auction_list.lbi");

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 优惠券列表
        /* ------------------------------------------------------ */
        elseif ($action == 'coupons') {
            load_helper('transaction');

            //获取当前登入用户的优惠券列表;
            $coupons_list = $this->couponsService->getUserCouponsList($user_id, true);

            //初始化
            $no_use = [];
            $yes_use = [];
            $yes_time = [];
            $no_time = [];

            if ($coupons_list) {
                foreach ($coupons_list as $k => $v) {
                    $v['cou_start_time_date'] = local_date('Y-m-d', $v['cou_start_time']);
                    $v['cou_end_time_date'] = local_date('Y-m-d', $v['cou_end_time']);
                    $v['add_time'] = local_date('Y-m-d', $v['add_time']); //订单生成时间即算优惠券使用时间
                    //如果指定了使用的优惠券的商品,取出允许使用优惠券的商品
                    if (!empty($v['cou_goods'])) {
                        $cou_goods = !is_array($v['cou_goods']) ? explode(",", $v['cou_goods']) : $v['cou_goods'];
                        $goods_list = Goods::whereIn('goods_id', $cou_goods)->get();
                        $goods_list = $goods_list ? $goods_list->toArray() : [];

                        $v['goods_list'] = $goods_list;
                    }

                    //获取店铺名称区分平台和店铺(平台发的全平台用,商家发的商家店铺用)
                    if ($v['ru_id']) {
                        $v['store_name'] = sprintf($GLOBALS['_LANG']['use_limit'], $this->merchantCommonService->getShopName($v['ru_id'], 1));
                    }

                    //格式化类型名称
                    $v['cou_type_name'] = $v['cou_type'] == VOUCHER_LOGIN ? $GLOBALS['_LANG']['vouchers_login'] : ($v['cou_type'] == VOUCHER_SHOPING ? $GLOBALS['_LANG']['vouchers_shoping'] : ($v['cou_type'] == VOUCHER_ALL ? $GLOBALS['_LANG']['vouchers_all'] : ($v['cou_type'] == VOUCHER_USER ? $GLOBALS['_LANG']['vouchers_user'] : ($v['cou_type'] == VOUCHER_SHIPPING ? $GLOBALS['_LANG']['vouchers_free'] : ($v['cou_type'] == VOUCHER_SHOP_CONLLENT ? $GLOBALS['_LANG']['vouchers_shop_conllent'] : $GLOBALS['_LANG']['unknown'])))));

                    if ($v['spec_cat']) {
                        $v['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_cate'];
                    } elseif ($v['cou_goods']) {
                        $v['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_goods'];
                    } else {
                        $v['cou_goods_name'] = $GLOBALS['_LANG']['lang_goods_coupons']['is_all'];
                    }

                    if ($v['is_use'] == 0 && $v['cou_end_time'] > gmtime()) {
                        $no_use[] = $v;
                    }
                    //已使用
                    if ($v['is_use'] == 1) {
                        $yes_use[] = $v;
                    }
                    //已过期
                    if ($v['cou_end_time'] < gmtime() && $v['is_use'] == 0) {
                        $yes_time[] = $v;
                    }
                    //即将过期(3天后过期)
                    $three_date = gmtime() + 3600 * 24 * 3;
                    if ($v['cou_end_time'] < $three_date && $v['cou_end_time'] > gmtime() && $v['is_use'] == 0) {
                        $no_time[] = $v;
                    }
                }
            }

            $this->smarty->assign('no_use', $no_use);
            $this->smarty->assign('yes_use', $yes_use);
            $this->smarty->assign('yes_time', $yes_time);
            $this->smarty->assign('no_time', $no_time);
            $this->smarty->assign('no_use_count', count($no_use));
            $this->smarty->assign('yes_use_count', count($yes_use));
            $this->smarty->assign('yes_time_count', count($yes_time));
            $this->smarty->assign('no_time_count', count($no_time));

            $this->smarty->assign('action', $action);
            $this->smarty->assign('page_title', $GLOBALS['_LANG']['user_vouchers']);
            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 活动中心-拍卖活动订单
        /* ------------------------------------------------------ */
        elseif ($action == 'auction' || $action == 'auction_order_recycle') {
            load_helper('transaction');
            if ($action == 'auction') {
                $show_type = 0;
            } elseif ($action == 'auction_order_recycle') {
                $show_type = 1;
            }

            $page = (int)request()->input('page', 1);
            $this->smarty->assign('status_list', $GLOBALS['_LANG']['cs']);   // 订单状态

            if ($action == 'auction') {
                $type = 0;
                $this->smarty->assign('action', $action);
            } elseif ($action == 'auction_order_recycle') { //订单回收站
                $type = 1;
                $this->smarty->assign('action', $action);
            }

            //待确认
            $where = [
                'user_id' => $user_id,
                'show_type' => $show_type,
                'order_status' => OS_UNCONFIRMED,
                'is_zc_order' => 0,
                'action' => $action
            ];
            $unconfirmed = $this->userOrderService->GetOrderWhereCount($where);
            $this->smarty->assign('unconfirmed', $unconfirmed);

            //待付款
            $pay_id = $this->paymentService->paymentIdList(false);
            $where = [
                'user_id' => $user_id,
                'show_type' => $type,
                'order_status' => [OS_CONFIRMED, OS_SPLITED],
                'pay_status' => [PS_UNPAYED, PS_PAYED_PART],
                'shipping_status' => [SS_SHIPPED, SS_RECEIVED],
                'pay_id' => $pay_id,
                'is_zc_order' => 0,
                'action' => $action
            ];
            $pay_count = $this->userOrderService->getOrderWhereCount($where);
            $this->smarty->assign('pay_count', $pay_count);

            //已发货,用户尚未确认收货
            $where = [
                'user_id' => $user_id,
                'show_type' => $type,
                'order_status' => [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART],
                'pay_status' => [PS_PAYED, PS_PAYING],
                'shipping_status' => SS_SHIPPED,
                'is_zc_order' => 0,
                'action' => $action
            ];
            $to_confirm_order = $this->userOrderService->getOrderWhereCount($where);
            $this->smarty->assign('to_confirm_order', $to_confirm_order);

            //已发货，用户已确认收货
            $where = [
                'user_id' => $user_id,
                'show_type' => $type,
                'order_status' => [OS_CONFIRMED, OS_SPLITED],
                'pay_status' => [PS_PAYED, PS_PAYING],
                'shipping_status' => SS_RECEIVED,
                'is_zc_order' => 0,
                'action' => $action
            ];
            $to_finished = $this->userOrderService->getOrderWhereCount($where);
            $this->smarty->assign('to_finished', $to_finished);

            $size = 10;
            $where = [
                'user_id' => $user_id,
                'show_type' => $type,
                'is_zc_order' => 0,
                'page' => $page,
                'size' => $size
            ];

            $record_count = $this->userOrderService->getUserOrdersCount($where, $action);

            $where['record_count'] = $record_count;
            $auction = $this->userOrderService->getUserOrdersList($where, $action);

            $this->smarty->assign('orders', $auction);
            $this->smarty->assign('allorders', $record_count);
            return $this->smarty->display('user_transaction.dwt');
        }

    }
}
