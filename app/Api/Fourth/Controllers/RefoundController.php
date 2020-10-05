<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\User\RefoundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class RefoundController
 * @package App\Api\Fourth\Controllers
 */
class RefoundController extends Controller
{
    protected $refoundService;

    /**
     * RefoundController constructor.
     * @param RefoundService $refoundService
     */
    public function __construct(
        RefoundService $refoundService
    )
    {
        $this->refoundService = $refoundService;
    }

    /**
     * 退换货列表
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);
        $order_id = $request->input('order_id', 0);
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //退换货列表。列表显示全部退换货订单，角标显示的是未完成的退换货订单数量  包含待同意未退款  已同意未退款这两种状态
        $list = $this->refoundService->getRefoundList($user_id, $order_id, $page, $size);

        return $this->succeed($list);
    }

    /**
     * 退换货商品列表
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function returngoods(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => 'required|integer',
        ]);
        $order_id = $request->input('order_id');

        $list = $this->refoundService->getGoodsOrder($order_id);

        return $this->succeed($list);
    }

    /**
     * 退换货申请
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function applyreturn(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => 'required|integer',
            'rec_id' => 'required|string',
        ]);
        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $order_id = $request->input('order_id', 0);
        $rec_id = $request->input('rec_id', '');
        $rec_id = !empty($rec_id) ? explode(',', $rec_id) : [];

        $data = $this->refoundService->applyReturn($user_id, $order_id, $rec_id);

        return $this->succeed($data);
    }

    /**
     * 退换货详情
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function returndetail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer',
        ]);
        $ret_id = $request->input('ret_id');


        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //退换货详情
        $info = $this->refoundService->returnDetail($user_id, $ret_id);

        return $this->succeed($info);
    }

    /**
     * 提交退换货申请
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function submit_return(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'rec_id' => 'required|string',
            'last_option' => 'required|string',
            'return_brief' => 'required|string',
            'chargeoff_status' => 'required|string',
            'return_type' => 'required|integer',
        ]);

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $rec_id = $request->input('rec_id', '');
        $rec_id = !empty($rec_id) ? explode(',', $rec_id) : [];
        $last_option = $request->input('last_option');
        $return_remark = $request->input('return_remark', '');
        $return_brief = $request->input('return_brief');
        $chargeoff_status = $request->input('chargeoff_status');

        $info = $request->all();

        // 提交退换货
        $info = $this->refoundService->submitReturn($user_id, $rec_id, $last_option, $return_remark, $return_brief, $chargeoff_status, $info);

        return $this->succeed($info);
    }

    /**
     * 编辑退换货快递信息
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function edit_express(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer',
            'order_id' => 'required|integer',
            'shipping_id' => 'required|integer',
            'express_name' => 'required|string',
            'express_sn' => 'required|string',
        ]);
        $info = $request->all();

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //编辑退换货
        $info = $this->refoundService->editExpress($user_id, $info);

        return $this->succeed($info);
    }

    /**
     * 取消退换货申请
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function cancel(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer'
        ]);

        $ret_id = $request->input('ret_id', 0);

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->refoundService->cancle_return($user_id, $ret_id);

        return $this->succeed($data);
    }

    /**
     * 退换货订单确认收货
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function affirm_receive(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer'
        ]);

        $ret_id = $request->input('ret_id', 0);

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->refoundService->AffirmReceivedOrderReturn($user_id, $ret_id);

        return $this->succeed($data);
    }

    /**
     * 激活退换货订单
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function active_return_order(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer'
        ]);

        $ret_id = $request->input('ret_id', 0);

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->refoundService->ActivationReturnOrder($user_id, $ret_id);

        return $this->succeed($data);
    }

    /**
     * 删除已完成退换货订单
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function delete_return_order(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ret_id' => 'required|integer'
        ]);

        $ret_id = $request->input('ret_id', 0);

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->refoundService->DeleteReturnOrder($user_id, $ret_id);

        return $this->succeed($data);
    }
}
