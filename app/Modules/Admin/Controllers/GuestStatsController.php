<?php

namespace App\Modules\Admin\Controllers;

use App\Exports\GuestStatsExport;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Order\OrderCommonService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 客户统计
 */
class GuestStatsController extends InitController
{
    protected $orderCommonService;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        OrderCommonService $orderCommonService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->orderCommonService = $orderCommonService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang('statistic', 'admin');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 客户统计列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {

            /* 权限判断 */
            admin_priv('client_flow_stats');

            $_GET['flag'] = isset($_GET['flag']) ? 'download' : '';
            if ($_GET['flag'] == 'download') {
                $filename = 'guest_stats_' . $this->timeRepository->getLocalDate("Y-m-d H:i:s");

                return Excel::download(new GuestStatsExport, $filename . '.xlsx');
            } else {
                $user_num = $this->orderCommonService->GuestStatsUserCount();
                $have_order_usernum = $this->orderCommonService->GuestStatsUserOrderCount();
                $user_all_order = $this->orderCommonService->GuestStatsUserOrderAll();
                $guest_all_order = $this->orderCommonService->GguestAllOrder();

                $user_num = !empty($user_num) ? $user_num : 1;
                $user_all_order['order_num'] = !empty($user_all_order['order_num']) ? $user_all_order['order_num'] : 0;

                /* 赋值到模板 */
                $this->smarty->assign('user_num', $user_num);                    // 会员总数
                $this->smarty->assign('have_order_usernum', $have_order_usernum);          // 有过订单的会员数
                $this->smarty->assign('user_order_turnover', $user_all_order['order_num']); // 会员总订单数
                $this->smarty->assign('user_all_turnover', price_format($user_all_order['turnover']));  //会员购物总额
                $this->smarty->assign('guest_all_turnover', price_format($guest_all_order['turnover'])); //匿名会员购物总额
                $this->smarty->assign('guest_order_num', $guest_all_order['order_num']);              //匿名会员订单总数
                $this->smarty->assign('one_user_order_unm', sprintf('%0.2f', $user_all_order['order_num'] / $user_num));//每会员订单数

                /* 每会员订单数 */
                $this->smarty->assign('ave_user_ordernum', $user_num > 0 ? sprintf("%0.2f", $user_all_order['order_num'] / $user_num) : 0);

                /* 每会员购物额 */
                if ($user_all_order['order_num']) {
                    $this->smarty->assign('ave_user_turnover', $user_num > 0 ? price_format($user_all_order['turnover'] / $user_num) : 0);
                } else {
                    $this->smarty->assign('ave_user_turnover', $GLOBALS['_LANG']['not_order']);
                }

                /* 注册会员购买率 */
                $this->smarty->assign('user_ratio', sprintf("%0.2f", ($user_num > 0 ? $have_order_usernum / $user_num : 0) * 100));

                /* 匿名会员平均订单额 */
                $this->smarty->assign('guest_order_amount', $guest_all_order['order_num'] > 0 ? price_format($guest_all_order['turnover'] / $guest_all_order['order_num']) : 0);

                $this->smarty->assign('all_order', $user_all_order);    //所有订单总数以及所有购物总额
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['report_guest']);
                $this->smarty->assign('lang', $GLOBALS['_LANG']);

                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['down_guest_stats'],
                    'href' => 'guest_stats.php?flag=download']);
                $this->smarty->assign('full_page', 1);

                return $this->smarty->display('guest_stats.dwt');
            }
        }
    }
}
