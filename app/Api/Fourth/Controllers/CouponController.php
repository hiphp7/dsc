<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Coupon\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    protected $couponService;

    /**
     * CouponController constructor.
     * @param CouponService $couponService
     */
    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * 优惠券列表 好券集市- 全场券、会员券、免邮券
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
            'status' => 'required|integer'
        ]);

        $user_id = $this->authorization();

        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        $status = $request->input('status', 0);
        $cou_id = $request->input('cou_id', 0);

        $coupon_list = $this->couponService->listCoupon($user_id, $status, $page, $size, $cou_id);

        return $this->succeed($coupon_list);
    }

    /**
     * 任务集市- 购物券
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function couponsGoods(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        $user_id = $this->authorization();

        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        $coupon_goods_list = $this->couponService->get_coupons_goods_list($user_id, $page, $size);

        return $this->succeed($coupon_goods_list);
    }

    /**
     * 返回优惠券详情页面，参见：/coupons.php?act=coupons_info&id=23
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'cou_id' => 'required|integer',
        ]);

        $coupon_id = $request->get('cou_id', 0);

        $coupon = $this->couponService->getDetail($coupon_id);

        return $this->succeed($coupon);
    }

    /**
     * 领取优惠券
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function receive(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'cou_id' => 'required|integer',
        ]);

        //返回用户ID
        $user_id = $this->authorization();

        $cou_id = $request->input('cou_id', 0);

        $verify_info = $this->couponService->receiveCoupon($user_id, $cou_id);

        return $this->succeed($verify_info);
    }

    /**
     * 会员中心优惠券列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function coupon(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'type' => 'required|integer',
        ]);

        //返回用户ID
        $user_id = $this->authorization();

        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        $type = $request->input('type', 0);

        $usercoupons = $this->couponService->userCoupons($user_id, $page, $size, $type);

        return $this->succeed($usercoupons);
    }

    /**
     * 商品详情优惠券列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function goods(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'goods_id' => 'required|integer',
            'ru_id' => 'required|integer',
        ]);
        //返回用户ID
        $user_id = $this->authorization();

        $goods_id = $request->input('goods_id', 0);
        $ru_id = $request->input('ru_id', 0);
        $size = $request->input('size', 10);

        $goods_coupons = $this->couponService->goodsCoupons($user_id, $goods_id, $ru_id, $size);

        return $this->succeed($goods_coupons['res']);
    }
}
