<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\User\CollectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class CollectController
 * @package App\Api\Fourth\Controllers
 */
class CollectController extends Controller
{
    protected $collectService;

    public function __construct(
        CollectService $collectService
    )
    {
        $this->collectService = $collectService;
    }


    /**
     * 收藏店铺列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function shop(Request $request)
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
        $page = $request->get('page');
        $size = $request->get('size');

        $region_id = $request->get('region_id', 0);
        $area_id = $request->get('area_id', 0);
        $area_city = $request->get('area_city', 0);

        //收藏店铺列表
        $ShopList = $this->collectService->getUserShopList($user_id, $page, $size, $region_id, $area_id, $area_city);

        return $this->succeed($ShopList);
    }

    /**
     * 收藏店铺
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function collectShop(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'ru_id' => 'required|integer',
        ]);

        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //店铺id
        $shop_id = $request->post('ru_id');

        //收藏店铺
        $res = $this->collectService->collectShop($shop_id, $user_id);

        if ($res == 1) {
            $result['error'] = 0;
            $result['msg'] = lang('common.Cancel_attention');
        } elseif ($res == 2) {
            $result['error'] = 0;
            $result['msg'] = lang('common.follow_yes');
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('common.unknown_error');
        }

        return $this->succeed($result);
    }

    /**
     * 关注商品列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function goods(Request $request)
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
        $page = $request->get('page');
        $size = $request->get('size');

        //关注商品
        $GoodsList = $this->collectService->getUserGoodsList($user_id, $page, $size);

        return $this->succeed($GoodsList);
    }

    /**
     * 关注商品
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function collectGoods(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'goods_id' => 'required|integer',
        ]);

        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //商品id
        $goods_id = $request->post('goods_id');

        //关注商品
        $res = $this->collectService->collectGoods($goods_id, $user_id);

        if ($res == 1) {
            $result['error'] = 0;
            $result['msg'] = lang('common.Cancel_attention');
        } elseif ($res == 2) {
            $result['error'] = 0;
            $result['msg'] = lang('common.follow_yes');
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('common.unknown_error');
        }

        return $this->succeed($result);
    }
}
