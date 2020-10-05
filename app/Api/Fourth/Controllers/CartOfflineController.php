<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\DiscountService;
use App\Services\Cart\CartCommonService;
use App\Services\Cart\CartMobileService;
use App\Services\Coupon\CouponService;
use App\Services\Goods\GoodsCommonService;
use App\Services\OfflineStore\OfflineStoreService;
use App\Services\User\UserCommonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class CartOfflineController
 * @package App\Api\Fourth\Controllers
 */
class CartOfflineController extends Controller
{
    protected $config;
    protected $cartMobileService;
    protected $discountService;
    protected $couponService;
    protected $goodsCommonService;
    protected $dscRepository;
    protected $userCommonService;
    protected $cartCommonService;
    protected $offlineStoreService;

    public function __construct(
        CartMobileService $cartMobileService,
        DiscountService $discountService,
        CouponService $couponService,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService,
        OfflineStoreService $offlineStoreService
    )
    {
        $this->cartMobileService = $cartMobileService;
        $this->discountService = $discountService;
        $this->couponService = $couponService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->goodsCommonService = $goodsCommonService;
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->offlineStoreService = $offlineStoreService;
    }

    /**
     * 门店购物车更新
     * rec_type = CART_OFFLINE_GOODS
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'rec_id' => 'required|string',
            'store_id' => 'required|integer',
        ]);

        $rec_id = $request->input('rec_id', '');// 当前点击购物车的id
        $store_id = $request->input('store_id', 0);
        $take_time = $request->input('take_time', '');
        $store_mobile = $request->input('store_mobile', 0);

        if (empty($rec_id)) {
            return $this->failed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
        }
//        $rec_id = [$rec_id];

        //清空门店购物车
        $this->cartCommonService->clearStoreGoods($this->uid);

        //普通购物车商品复制到门店线下购物车（CART_OFFLINE_GOODS）
        $copy = $this->cartCommonService->copyCartToOfflineStore($rec_id, $this->uid, $store_id, $take_time, $store_mobile);
        if ($copy === false) {
            return $this->failed(['error' => 1, 'msg' => lang('common.illegal_operate')]);
        }

        $result['error'] = 0;

        return $this->succeed($result);
    }
}
