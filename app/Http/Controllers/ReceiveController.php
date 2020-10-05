<?php

namespace App\Http\Controllers;

use App\Models\OrderInfo;
use App\Services\Article\ArticleCommonService;
use App\Services\Order\OrderService;

/**
 * 处理收回确认的页面
 */
class ReceiveController extends InitController
{
    protected $orderService;
    protected $articleCommonService;

    public function __construct(
        OrderService $orderService,
        ArticleCommonService $articleCommonService
    )
    {
        $this->orderService = $orderService;
        $this->articleCommonService = $articleCommonService;
    }


    public function index()
    {

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        /* 取得参数 */
        // 订单号
        $order_id = (int)request()->input('id', 0);
        // 收货人
        $consignee = rawurldecode(trim(request()->input('con', '')));

        $user_id = session('user_id', 0);

        /* 查询订单信息 */
        $where = [
            'order_id' => $order_id,
            'user_id' => $user_id
        ];
        $order = $this->orderService->getOrderInfo($where);

        if (empty($order)) {
            $msg = $GLOBALS['_LANG']['order_not_exists'];
        } /* 检查订单 */
        elseif ($order['shipping_status'] == SS_RECEIVED) {
            $msg = $GLOBALS['_LANG']['order_already_received'];
        } elseif ($order['shipping_status'] != SS_SHIPPED) {
            $msg = $GLOBALS['_LANG']['order_invalid'];
        } elseif ($order['consignee'] != $consignee) {
            $msg = $GLOBALS['_LANG']['order_invalid'];
        } else {
            /* 修改订单发货状态为“确认收货” */
            OrderInfo::where('order_id', $order_id)->where('user_id', $user_id)->update(['shipping_status' => SS_RECEIVED]);

            /* 记录日志 */
            order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], '', $GLOBALS['_LANG']['buyer']);
            $msg = $GLOBALS['_LANG']['act_ok'];
        }

        /* 显示模板 */
        assign_template();
        $position = assign_ur_here();
        $this->smarty->assign('page_title', $position['title']);    // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

        assign_dynamic('receive');

        $this->smarty->assign('msg', $msg);
        return $this->smarty->display('receive.dwt');
    }
}
