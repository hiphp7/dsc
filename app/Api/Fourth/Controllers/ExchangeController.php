<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Exchange\ExchangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class ExchangeController
 * @package App\Api\Fourth\Controllers
 */
class ExchangeController extends Controller
{
    protected $exchangeService;

    public function __construct(
        ExchangeService $exchangeService
    )
    {
        $this->exchangeService = $exchangeService;
    }

    /**
     * 积分商城列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'children' => 'required|integer',
            'min' => 'required|integer',
            'max' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer',
            'sort' => 'required|string',
            'order' => 'required|string',
        ]);
        $info = $request->all();

        $children = $info['children'];
        $min = $info['min'];
        $max = $info['max'];
        $order = $info['order'];
        $page = $info['page'];
        $size = $info['size'];
        $sort = $info['sort'];

        $list = $this->exchangeService->getExchangeGetGoods($children, $min, $max, $page, $size, $sort, $order);

        return $this->succeed($list);
    }

    /**
     * 积分商城商品详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'area_id' => 'required|integer',
        ]);
        $info = $request->all();

        $id = $info['id'] ?? 0;

        $list = $this->exchangeService->getExchangeGoodsInfo($this->uid, $id, $this->warehouse_id, $this->area_id, $this->area_city);

        return $this->succeed($list);
    }

    /**
     * 积分商城购买
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function buy(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'num' => 'required|integer',
            'goods_id' => 'required|integer',
        ]);

        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $goods_id = $request->get('goods_id', 0);
        $number = $request->get('num', 1);
        $attr = $request->get('attr', []);

        $res = $this->exchangeService->buy($user_id, $number, $goods_id, $attr, 0, 0, 0);

        return $this->succeed($res);
    }
}
