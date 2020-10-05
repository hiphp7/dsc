<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Api\Fourth\Transformers\AddressTransformer;
use App\Services\User\UserAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class AddressController
 * @package App\Api\Fourth\Controllers
 */
class AddressController extends Controller
{
    protected $addressTransformer;
    protected $userAddressService;

    public function __construct(
        AddressTransformer $addressTransformer,
        UserAddressService $userAddressService
    )
    {
        $this->addressTransformer = $addressTransformer;
        $this->userAddressService = $userAddressService;
    }

    /**
     * 返回所有收货地址列表
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, []);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //收货地址列表
        $addressList = $this->userAddressService->getUserAddressList($user_id, 10);

        $addressList = $this->addressTransformer->transformCollection($addressList);

        return $this->succeed($addressList);
    }

    /**
     * 添加收货地址
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'consignee' => 'required|string',
            'country' => 'required|integer',
            'province' => 'required|integer',
            'city' => 'required|integer',
            'district' => 'required|integer',
            'address' => 'required|string',
            'mobile' => 'required|size:11'
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //整合参数
        $address = [
            'address_id' => $request->get('address_id', 0),
            'consignee' => $request->get('consignee'),
            'mobile' => $request->get('mobile'),
            'country' => $request->get('country'),
            'province' => $request->get('province'),
            'city' => $request->get('city'),
            'district' => $request->get('district'),
            'street' => $request->get('street') ?? 0,
            'address' => $request->get('address'),
            'user_id' => $user_id,
        ];

        $result = $this->userAddressService->updateAddress($address);

        return $this->succeed($result);
    }

    /**
     * 收货地址详情
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'address_id' => 'required|integer'
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = $this->userAddressService->getUserAddressInfo($request->get('address_id'), $user_id);

        return $this->succeed($result);
    }

    /**
     * 更新收货地址
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'address_id' => 'required|integer',
            'consignee' => 'required|string',
            'country' => 'required|integer',
            'province' => 'required|integer',
            'city' => 'required|integer',
            'district' => 'required|integer',
            'street' => 'required|integer',
            'address' => 'required|string',
            'mobile' => 'required|size:11'
        ]);

        //返回会员id
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //校验收货地址是否为当前用户的信息
        $address = $this->userAddressService->getUserAddressInfo($request->get('address_id'), $user_id);
        if (empty($address)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //整合参数
        $address = [
            'address_id' => $request->get('address_id'),
            'consignee' => $request->get('consignee'),
            'mobile' => $request->get('mobile'),
            'country' => $request->get('country'),
            'province' => $request->get('province'),
            'city' => $request->get('city'),
            'district' => $request->get('district'),
            'street' => $request->get('street'),
            'address' => $request->get('address'),
            'user_id' => $user_id,
        ];

        $result = $this->userAddressService->updateAddress($address, false);

        return $this->succeed($result);
    }

    /**
     * 删除收货地址
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function destroy(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'address_id' => 'required|integer'
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = $this->userAddressService->dropConsignee($request->get('address_id'), $user_id);

        return $this->succeed($result);
    }

    /**
     * 设置默认收货地址
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function setDefault(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'address_id' => 'required|integer'
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = $this->userAddressService->getUpdateFlowConsignee($request->get('address_id'), $user_id);

        return $this->succeed($result);
    }

    /**
     * 同步微信收货地址
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function wximport(Request $request)
    {
        //数据验证
        $this->validate($request, []);

        $wximport = $request->all();

        $result = $this->userAddressService->wximportInfo($wximport);

        return $this->succeed($result);
    }


}
