<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\UserOrderNum;
use App\Proxy\ShippingProxy;
use App\Services\Order\OrderMobileService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class OrderController
 * @package App\Api\Fourth\Controllers
 */
class OrderController extends Controller
{
    /**
     * @var ShippingProxy
     */
    protected $shippingProxy;

    /**
     * @var OrderMobileService
     */
    protected $orderMobileService;

    /**
     * OrderController constructor.
     * @param OrderMobileService $orderMobileService
     * @param ShippingProxy $shippingProxy
     */
    public function __construct(
        OrderMobileService $orderMobileService,
        ShippingProxy $shippingProxy
    )
    {
        $this->orderMobileService = $orderMobileService;
        $this->shippingProxy = $shippingProxy;
    }

    /**
     * 订单列表
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function List(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => "required|integer",
            'size' => "required|integer",
            'status' => "required|integer",
            'type' => "required|string"
        ]);

        $status = $request->input('status', 0);
        $type = $request->input('type', '');
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        $uid = $this->authorization();

        $res = $this->orderMobileService->orderList($uid, $status, $type, $page, $size);

        $orderNum = UserOrderNum::where('user_id', $this->uid)->first();
        $orderNum = $orderNum ? $orderNum->toArray() : [];

        $orderCount = [
            'all' => $orderNum['order_all_num'] ?? 0, //订单数量
            'nopay' => $orderNum['order_nopay'] ?? 0, //待付款订单数量
            'nogoods' => $orderNum['order_nogoods'] ?? 0, //待收货订单数量
            'isfinished' => $orderNum['order_isfinished'] ?? 0, //已完成订单数量
            'isdelete' => $orderNum['order_isdelete'] ?? 0, //回收站订单数量
            'team_num' => $orderNum['order_team_num'] ?? 0, //拼团订单数量
            'not_comment' => $orderNum['order_not_comment'] ?? 0,  //待评价订单数量
            'return_count' => $orderNum['order_return_count'] ?? 0 //待同意状态退换货申请数量
        ];

        return $this->succeed(['list' => $res, 'count' => $orderCount]);
    }

    /**
     * 订单详情
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function Detail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => "required|integer"
        ]);

        $args['order_id'] = $request->get('order_id');
        $args['uid'] = $this->authorization();

        $order = $this->orderMobileService->orderDetail($args);

        if (CROSS_BORDER === true && $order['is_kj'] > 0) // 跨境多商户
        {
            $order['cross_border'] = true;
        }

        return $this->succeed($order);
    }

    /**
     * 订单确认收货
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function Confirm(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => "required|integer"
        ]);

        $user_id = $this->authorization();

        $order = $this->orderMobileService->orderConfirm($user_id, $request->get('order_id'));

        return $this->succeed($order);
    }

    /**
     * 延迟收货申请
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function Delay(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => "required|integer"
        ]);

        $user_id = $this->authorization();

        $info = $this->orderMobileService->orderDelay($user_id, $request->get('order_id'));

        return $this->succeed($info);
    }


    /**
     * 订单取消
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function Cancel(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => "required|integer"
        ]);

        $args['order_id'] = $request->get('order_id');
        $args['uid'] = $this->authorization();

        $order = $this->orderMobileService->orderCancel($args);

        return $this->succeed($order);
    }

    /**
     * 订单跟踪
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function tracker(Request $request)
    {
        // 数据验证
        $validator = Validator::make($request->all(), [
            'type' => "required|string",
            'postid' => "required|string"
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }

        $type = $request->get('type');
        $post_id = $request->get('postid');

        $res = $this->shippingProxy->getExpress($type, $post_id);

        if ($res['error']) {
            return $this->failed($res['data']);
        } else {
            return $this->succeed($res['data']);
        }
    }

    /**发货单信息查询接口
     * @param Request $request
     * @return JsonResponse
     */
    public function tracker_order(Request $request)
    {
        // 数据验证
        $validator = Validator::make($request->all(), [
            'order_sn' => "required|string",
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }

        $order_sn = $request->get('order_sn', '');

        $res = $this->orderMobileService->getTrackerOrderInfo($order_sn);

        return $this->succeed($res);
    }

    /**
     * 订单删除
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function Delete(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => "required|integer"
        ]);

        $args['order_id'] = $request->get('order_id');
        $args['uid'] = $this->authorization();

        $order = $this->orderMobileService->orderDelete($args);

        return $this->succeed($order);
    }

    /**
     * 还原订单
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function Restore(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => "required|integer"
        ]);

        $args['order_id'] = $request->get('order_id');
        $args['uid'] = $this->authorization();

        $order = $this->orderMobileService->orderRestore($args);

        return $this->succeed($order);
    }
}
