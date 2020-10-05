<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Activity\BonusService as ActivityBonusService;
use App\Services\Bonus\BonusService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class UserController
 * @package App\Api\Fourth\Controllers
 */
class BonusController extends Controller
{
    protected $bonusService;
    protected $activitybonusService;

    public function __construct(
        BonusService $bonusService,
        ActivityBonusService $activitybonusService
    )
    {
        $this->bonusService = $bonusService;
        $this->activitybonusService = $activitybonusService;
    }

    /**
     * 红包列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);
        $info = $request->all();
        $page = $info['page'];
        $size = $info['size'];
        $id = $info['id'] ?? 0;


        $user_id = $this->authorization();

        $bonus_list = $this->bonusService->listBonus($user_id, $status = 4, $page, $size, $id);


        return $this->succeed($bonus_list);
    }

    /**
     * 领取红包
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function receive(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'type_id' => 'required|integer',
        ]);
        //返回用户ID
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $type_id = $request->input('type_id', 0);

        $verify_info = $this->bonusService->receiveBonus($user_id, $type_id);

        return $this->succeed($verify_info);
    }

    /**
     * 会员添加红包
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'bonus_sn' => 'required|string',
            'bonus_password' => 'required|string',
        ]);
        //返回用户ID
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $info['user_id'] = $user_id;

        $bonus = $this->bonusService->addBonus($info);

        return $this->succeed($bonus);
    }

    /**
     * 会员中心红包列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function bonus(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'type' => 'required|integer',
        ]);

        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        $type = $request->input('type', 0); // 0 未使用 1 已使用 2 已过期

        //返回用户ID
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $bonus = $this->bonusService->userBonus($user_id, $type, $page, $size);

        return $this->succeed($bonus);
    }

    /**
     * 提交订单页红包列表
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function flowbonus(Request $request)
    {
        $goods_amount = $request->input('goods_amount', 0); // 商品总价
        $cart_value = $request->input('cart_value', '');

        //返回用户ID
        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $bonus_list = $this->activitybonusService->getUserBonusInfo($user_id, $goods_amount, $cart_value, $seller_amount = [], $cart_ru_id = -1);

        return $this->succeed($bonus_list);
    }
}
