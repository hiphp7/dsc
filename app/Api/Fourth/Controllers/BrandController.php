<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Brand\BrandMobileService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    protected $brandMobileService;

    public function __construct(
        BrandMobileService $brandMobileService
    )
    {
        $this->brandMobileService = $brandMobileService;
    }

    /**
     * 品牌首页
     * @param Request $request
     * @return JsonResponse
     */
    public function Index(Request $request)
    {
        $num = $request->input('num', 20);

        $data = $this->brandMobileService->index($num);

        return $this->succeed($data);
    }

    /**
     * 品牌详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function Detail(Request $request)
    {
        $this->validate($request, [
            'brand_id' => 'required|integer',
        ]);

        $brand_id = $request->get('brand_id');
        $cat_id = $request->get('cat');

        $data = $this->brandMobileService->detail($brand_id, $cat_id);

        return $this->succeed($data);
    }

    /**
     * 品牌商品列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function GoodsList(Request $request)
    {
        $this->validate($request, [
            'brand_id' => 'required|integer',
        ]);

        $brand_id = $request->input('brand_id', 0);
        $cat_id = $request->input('cat', 0);
        $size = $request->input('size', 10);
        $page = $request->input('page', 1);
        $sort = $request->input('sort', 'goods_id');
        $order = $request->input('order', 'DESC');
        $type = $request->input('type', '');

        $ext = [
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
        ];
        $data = $this->brandMobileService->BrandGoodsList($this->uid, $brand_id, $cat_id, $type, $size, $page, $sort, $order, $ext);

        return $this->succeed($data);
    }

    /**
     * 品牌列表
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function BrandList(Request $request)
    {
        // 缓存
        $list = cache('brand_list');
        if (is_null($list)) {
            $list = $this->brandMobileService->get_brands_letter();
            cache()->forever('brand_list', $list);
        }

        return $this->succeed($list);
    }
}
