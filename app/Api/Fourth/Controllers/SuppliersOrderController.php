<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Services\Wholesale\OrderService;
use App\Services\Wholesale\ReturnOrderService;
use App\Services\Wholesale\WholesaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class SuppliersOrderController
 * @package App\Api\Fourth\Controllers
 */
class SuppliersOrderController extends Controller
{
    protected $wholesale;
    protected $baseRepository;
    protected $returnOrderService;
    protected $orderService;

    public function __construct(
        WholesaleService $wholesale,
        BaseRepository $baseRepository,
        ReturnOrderService $returnOrderService,
        OrderService $orderService
    )
    {
        $this->wholesale = $wholesale;
        $this->baseRepository = $baseRepository;
        $this->returnOrderService = $returnOrderService;
        $this->orderService = $orderService;
    }

    /**
     * 订单列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function orderlist(Request $request)
    {

        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $record_count = WholesaleOrderInfo::mainOrderCount()
            ->where('user_id', $user_id)
            ->count();

        $data = [];
        $data['page'] = $request->get('page') ? intval($request->get('page')) : 1;
        $data['size'] = $request->get('size') ? intval($request->get('size')) : 10;
        $data['user_id'] = $user_id;
        $list = $this->orderService->getWholesaleOrders($record_count, $data);
        $res = $list['order_list'] ?? [];

        return $this->succeed($res);
    }

    /**
     * 退换货订单商品列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function wholesalegoodsorder(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => 'required|integer',
        ]);

        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $order_id = $request->get('order_id') ? intval($request->get('order_id')) : 1;
        $res = $this->returnOrderService->getwholesalegoodsorder($order_id);

        return $this->succeed($res);
    }

    /**
     * 退换货详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function applyreturn(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => 'required|integer',
            'rec_id' => 'required|integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $order_id = $request->get('order_id') ? intval($request->get('order_id')) : 1;
        $rec_id = $request->get('rec_id') ? intval($request->get('rec_id')) : 1;

        $res = $this->returnOrderService->applyreturn($rec_id, $order_id, $user_id);
        return $this->succeed($res);
    }

    /**
     * 退换货申请
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function submitreturn(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'rec_id' => 'required|integer',
            'last_option' => 'required|string',
            'return_brief' => 'required|string',
            'chargeoff_status' => 'required|string',
            'return_type' => 'required|integer',
        ]);

        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $res = $this->returnOrderService->submitReturn($user_id, $info);

        return $this->succeed($res);
    }

    /**
     * 删除已完成退换货订单
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function deletereturn(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $ret_id = $request->post('ret_id') ? intval($request->post('ret_id')) : 1;

        $res = $this->returnOrderService->deleteReturn($user_id, $ret_id);
        return $this->succeed($res);
    }

    /**
     * 确认订单
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function affirmOrder(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => 'required|integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $order_id = $request->get('order_id') ? intval($request->post('order_id')) : 0;

        $result = [];
        $res = $this->orderService->wholesaleAffirmReceived($order_id, $user_id);
        if ($res) {
            $result['error'] = 0;
            $result['msg'] = lang('user.complete_user');
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('user.unknow_error');
        }
        return $this->succeed($result);
    }

    /**
     * 供应链退换货订单列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function returnorderlist(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = [];
        $data['page'] = $request->get('page') ? intval($request->get('page')) : 1;
        $data['size'] = $request->get('size') ? intval($request->get('size')) : 10;
        $data['user_id'] = $user_id;
        $list = $this->returnOrderService->getReturnOrdersList($data);

        return $this->succeed($list);
    }

    /**
     * 供应链退换货订单详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function returnorderdetail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer'
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = [];
        $data['ret_id'] = $request->get('ret_id') ? intval($request->get('ret_id')) : 0;
        $data['user_id'] = $user_id;
        $list = $this->returnOrderService->getReturnOrdersDetail($data);

        return $this->succeed($list);
    }

    /**
     * 激活供应链退换货订单
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function activationreturnorder(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = [];
        $data['ret_id'] = $request->post('ret_id') ? intval($request->post('ret_id')) : 0;
        $data['user_id'] = $user_id;
        $res = $this->returnOrderService->activationReturnOrder($data);

        return $this->succeed($res);
    }
}
